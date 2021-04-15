<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use think\Db;
use think\Env;
use app\enum\Site;
use app\admin\model\web\WebUsers;
use app\admin\model\web\WebGroup;

/**
 * Class WebData
 * @package app\admin\controller\shell
 * @author wpl
 * @date   2021/4/14 17:27
 */
class WebData extends Backend
{
    protected $noNeedLogin = ['*'];

    /**
     * 消费主题名称
     * @var bool|mixed|string|null
     * @author wpl
     * @date   2021/4/14 17:39
     */
    private $topicName;
    /**
     * 消费地址
     * @var bool|mixed|string|null
     * @author wpl
     * @date   2021/4/14 17:40
     */
    private $topicIp;

    public function _initialize()
    {
        parent::_initialize();
        $this->topicName = Env::get('topic.topicName');
        $this->topicIp = Env::get('topic.topicIp');
    }

    /**
     * 同步数据
     * @throws \Exception
     * @author wpl
     * @date   2021/4/14 17:28
     */
    public function syc_data()
    {
        /**
         * 代码中的输出注释都可以打开供调试使用
         * 对 中台生产的 用户信息、购物车、VIP订单 进行消费
         */
        // 设置将要消费消息的主题
        $topic = $this->topicName;
        $host = $this->topicIp;
        $group_id = '0';
        $conf = new \RdKafka\Conf();
        // 当有新的消费进程加入或者退出消费组时，kafka 会自动重新分配分区给消费者进程，这里注册了一个回调函数，当分区被重新分配时触发
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;
                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(null);
                    break;
                default:
                    throw new \Exception($err);
            }
        });
        // 配置groud.id 具有相同 group.id 的consumer 将会处理不同分区的消息，
        // 所以同一个组内的消费者数量如果订阅了一个topic，
        // 那么消费者进程的数量多于 多于这个topic 分区的数量是没有意义的。
        $conf->set('group.id', $group_id);

        // 添加 kafka集群服务器地址
        $conf->set('metadata.broker.list', $host); //'localhost:9092,localhost:9093,localhost:9094,localhost:9095'

        // 针对低延迟进行了优化的配置。这允许PHP进程/请求尽快发送消息并快速终止
        $conf->set('socket.timeout.ms', 50);
        //多进程和信号
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, [SIGIO]);
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', 1);
        }

        $topicConf = new \RdKafka\TopicConf();
        // 在interval.ms的时间内自动提交确认、建议不要启动, 1是启动，0是未启动
        $topicConf->set('auto.commit.enable', 0);
        $topicConf->set('auto.commit.interval.ms', 100);
        //smallest：简单理解为从头开始消费，largest：简单理解为从最新的开始消费
        $topicConf->set('auto.offset.reset', 'smallest');
        // 设置offset的存储为broker
        //$topicConf->set('offset.store.method', 'broker');
        // 设置offset的存储为file
        //$topicConf->set('offset.store.method', 'file');
        // 设置offset的存储路径
        $topicConf->set('offset.store.path', 'kafka_offset.log');
        //$topicConf->set('offset.store.path', __DIR__);

        $consumer = new \RdKafka\KafkaConsumer($conf);

        // 更新订阅集（自动分配partitions ）
        $consumer->subscribe([$topic]);


        $orders_prescriptions_params = [];
        while (true) {
            //设置120s为超时
            $message = $consumer->consume(120 * 1000);
            if (!empty($message)) {
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR: //没有错误
                        //拆解对象为数组，并根据业务需求处理数据
                        $payload = json_decode($message->payload, true);
                        $key = $message->key;
                        //根据kafka中不同key，调用对应方法传递处理数据
                        //对该条message进行处理，比如用户数据同步， 记录日志。
                        if ($payload) {
                            //根据库名判断站点
                            $site = 0;
                            switch ($payload['database']) {
                                case 'zeelool':
                                    $site = Site::ZEELOOL;
                                    break;
                                case 'voogueme':
                                    $site = Site::VOOGUEME;
                                    break;
                                case 'nihao':
                                    $site = Site::NIHAO;
                                    break;
                                case 'meeloog':
                                    $site = Site::MEELOOG;
                                    break;
                                case 'zeelool_es':
                                    $site = Site::ZEELOOL_ES;
                                    break;
                                case 'zeelool_de':
                                    $site = Site::ZEELOOL_DE;
                                    break;
                                case 'zeelool_jp':
                                    $site = Site::ZEELOOL_JP;
                                    break;
                                case 'morefun':
                                    $site = Site::WESEEOPTICAL;
                                    break;
                                case 'voogueme_acc':
                                    $site = Site::VOOGUEME_ACC;
                                    break;
                            }
                            //用户表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'customer_entity') {
                                WebUsers::setInsertData($payload['data'], $site);
                            }
                            //用户表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'customer_entity') {
                                WebUsers::setUpdateData($payload['data'], $site);
                            }

                            //用户分组表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'customer_group') {
                                WebGroup::setInsertData($payload['data'], $site);
                            }
                            //用户表分组更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'customer_group') {
                                WebGroup::setUpdateData($payload['data'], $site);
                            }

                            //批发站主表
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'orders') {
                                $order_params = [];
                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    $params['entity_id'] = $v['id'];
                                    $params['site'] = $site;
                                    $params['increment_id'] = $v['order_no'];
                                    $params['status'] = $v['order_status'] ?: '';
                                    $params['store_id'] = $v['source'];
                                    $params['base_grand_total'] = $v['base_actual_amount_paid'];
                                    $params['grand_total'] = $v['actual_amount_paid'];
                                    $params['total_qty_ordered'] = $v['goods_quantity'];
                                    $params['base_currency_code'] = $v['base_currency'];
                                    $params['order_currency_code'] = $v['now_currency'];
                                    $params['shipping_method'] = $v['freight_type'];
                                    $params['shipping_title'] = $v['freight_description'];
                                    $params['customer_email'] = $v['email'];
                                    $params['base_to_order_rate'] = $v['rate'];
                                    $params['base_shipping_amount'] = $v['freight_price'];
                                    $params['payment_method'] = $v['payment_type'];
                                    $params['created_at'] = strtotime($v['created_at']) + 28800;
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    $params['last_trans_id'] = $v['payment_order_no'];
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }

                                    //插入订单主表
                                    $order_id = $this->order->insertGetId($params);
                                    $order_params[$k]['site'] = $site;
                                    $order_params[$k]['order_id'] = $order_id;
                                    $order_params[$k]['entity_id'] = $v['id'];
                                    $order_params[$k]['increment_id'] = $v['order_no'];
                                }
                                //插入订单处理表
                                $this->orderprocess->saveAll($order_params);
                            }


                            //批发站主表
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'orders') {
                                $order_params = [];
                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    $params['increment_id'] = $v['order_no'];
                                    $params['status'] = $v['order_status'] ?: '';
                                    $params['store_id'] = $v['source'];
                                    $params['base_grand_total'] = $v['base_actual_amount_paid'];
                                    $params['grand_total'] = $v['actual_amount_paid'];
                                    $params['total_qty_ordered'] = $v['goods_quantity'];
                                    $params['base_currency_code'] = $v['base_currency'];
                                    $params['order_currency_code'] = $v['now_currency'];
                                    $params['shipping_method'] = $v['freight_type'];
                                    $params['shipping_title'] = $v['freight_description'];
                                    $params['customer_email'] = $v['email'];
                                    $params['base_to_order_rate'] = $v['rate'];
                                    $params['payment_method'] = $v['payment_type'];
                                    $params['base_shipping_amount'] = $v['freight_price'];
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    $params['last_trans_id'] = $v['payment_order_no'];
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }
                                    $this->order->where(['entity_id' => $v['id'], 'site' => $site])->update($params);
                                }
                            }


                            //批发站地址表插入时或更新时更新主表地址
                            if (($payload['type'] == 'UPDATE' || $payload['type'] == 'INSERT') && $payload['table'] == 'orders_addresses') {
                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    if ($v['type'] == 1) {
                                        $params['country_id'] = $v['country'];
                                        $params['region'] = $v['region'];
                                        $params['city'] = $v['city'];
                                        $params['street'] = $v['street'];
                                        $params['postcode'] = $v['postcode'];
                                        $params['telephone'] = $v['telephone'];
                                        $params['customer_firstname'] = $v['firstname'];
                                        $params['customer_lastname'] = $v['lastname'];
                                        $params['firstname'] = $v['firstname'];
                                        $params['lastname'] = $v['lastname'];
                                        $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                        $this->order->where(['entity_id' => $v['order_id'], 'site' => $site])->update($params);
                                    }
                                }
                            }


                            //更新主表
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'sales_flat_order') {

                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    $params['base_grand_total'] = $v['base_grand_total'];
                                    $params['grand_total'] = $v['grand_total'];
                                    $params['total_item_count'] = $v['total_item_count'];
                                    $params['total_qty_ordered'] = $v['total_qty_ordered'];
                                    $params['increment_id'] = $v['increment_id'];
                                    $params['order_type'] = $v['order_type'];
                                    if ($v['status']) {
                                        $params['status'] = $v['status'];
                                    }

                                    $params['base_currency_code'] = $v['base_currency_code'];
                                    $params['order_currency_code'] = $v['order_currency_code'];
                                    $params['shipping_method'] = $v['shipping_method'];
                                    $params['shipping_title'] = $v['shipping_description'];
                                    $params['customer_email'] = $v['customer_email'];
                                    $params['customer_firstname'] = $v['customer_firstname'];
                                    $params['customer_lastname'] = $v['customer_lastname'];
                                    $params['taxno'] = $v['cpf'];
                                    $params['base_to_order_rate'] = $v['base_to_order_rate'];
                                    $params['mw_rewardpoint'] = $v['mw_rewardpoint'];
                                    $params['mw_rewardpoint_discount'] = $v['mw_rewardpoint_discount'];
                                    $params['base_shipping_amount'] = $v['base_shipping_amount'];
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }

                                    $this->order->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);
                                }
                            }

                            //地址表插入时或更新时更新主表地址
                            if (($payload['type'] == 'UPDATE' || $payload['type'] == 'INSERT') && $payload['table'] == 'sales_flat_order_address') {
                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    if ($v['address_type'] == 'shipping') {
                                        $params['country_id'] = $v['country_id'];
                                        $params['region'] = $v['region'];
                                        $params['region_id'] = $v['region_id'];
                                        $params['city'] = $v['city'];
                                        $params['street'] = $v['street'];
                                        $params['postcode'] = $v['postcode'];
                                        $params['telephone'] = $v['telephone'];
                                        $params['firstname'] = $v['firstname'];
                                        $params['lastname'] = $v['lastname'];
                                        $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                        $this->order->where(['entity_id' => $v['parent_id'], 'site' => $site])->update($params);
                                    }
                                }
                            }

                            //支付表插入时或更新时更新主表地址
                            if (($payload['type'] == 'UPDATE' || $payload['type'] == 'INSERT') && $payload['table'] == 'sales_flat_order_payment') {
                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    $params['payment_method'] = $v['method'];
                                    $params['last_trans_id'] = $v['last_trans_id'];
                                    $this->order->where(['entity_id' => $v['parent_id'], 'site' => $site])->update($params);
                                }
                            }


                            //批发站处方表更新
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'orders_prescriptions') {
                                foreach ($payload['data'] as $k => $v) {
                                    $orders_prescriptions_params[$v['id']]['prescription'] = $v['prescription'];
                                    $orders_prescriptions_params[$v['id']]['name'] = $v['name'];
                                }
                            }

                            //批发站新增子表
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'orders_items') {
                                foreach ($payload['data'] as $k => $v) {
                                    $options = [];
                                    //处方解析 不同站不同字段
                                    if ($site == 5) {
                                        $options = $this->wesee_prescription_analysis($orders_prescriptions_params[$v['orders_prescriptions_id']]['prescription']);
                                    }

                                    $options['item_id'] = $v['id'];
                                    $options['site'] = $site;
                                    $options['magento_order_id'] = $v['order_id'];
                                    $options['sku'] = $this->getTrueSku($v['goods_sku']);
                                    $options['qty'] = $v['goods_count'];
                                    $options['base_row_total'] = $v['original_total_price'];
                                    $options['product_id'] = $v['goods_id'];
                                    $options['frame_regural_price'] = $v['goods_original_price'];
                                    $options['frame_price'] = $v['goods_total_price'];
                                    $options['index_price'] = $v['lens_total_price'];
                                    $options['frame_color'] = $v['goods_color'];
                                    $options['goods_type'] = $v['goods_type'];
                                    $options['prescription_type'] = $orders_prescriptions_params[$v['orders_prescriptions_id']]['name'];
                                    unset($orders_prescriptions_params[$v['orders_prescriptions_id']]);

                                    $order_prescription_type = $options['order_prescription_type'];
                                    unset($options['order_prescription_type']);
                                    if ($options) {
                                        $options_id = $this->orderitemoption->insertGetId($options);
                                        $data = []; //子订单表数据
                                        for ($i = 0; $i < $v['goods_count']; $i++) {
                                            $data[$i]['item_id'] = $v['id'];
                                            $data[$i]['magento_order_id'] = $v['order_id'];
                                            $data[$i]['site'] = $site;
                                            $data[$i]['option_id'] = $options_id;
                                            $data[$i]['sku'] = $options['sku'];
                                            $data[$i]['order_prescription_type'] = $order_prescription_type;
                                            $data[$i]['created_at'] = strtotime($v['created_at']) + 28800;
                                            $data[$i]['updated_at'] = strtotime($v['updated_at']) + 28800;
                                        }
                                        $this->orderitemprocess->insertAll($data);
                                    }
                                }
                            }


                            //批发站处方表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'orders_prescriptions') {

                                foreach ($payload['data'] as $k => $v) {
                                    $orders_prescriptions_params[$v['id']]['prescription'] = $v['prescription'];
                                    $orders_prescriptions_params[$v['id']]['name'] = $v['name'];
                                }
                            }

                            //批发站更新子表
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'orders_items') {
                                foreach ($payload['data'] as $k => $v) {
                                    $options = [];
                                    //处方解析 不同站不同字段
                                    if ($site == 5) {
                                        $options = $this->wesee_prescription_analysis($orders_prescriptions_params[$v['orders_prescriptions_id']]['prescription']);
                                    }

                                    $options['sku'] = $this->getTrueSku($v['goods_sku']);
                                    $options['qty'] = $v['goods_count'];
                                    $options['base_row_total'] = $v['original_total_price'];
                                    $options['prescription_type'] = $orders_prescriptions_params[$v['orders_prescriptions_id']]['name'];
                                    unset($orders_prescriptions_params[$v['orders_prescriptions_id']]);
                                    $order_prescription_type = $options['order_prescription_type'];
                                    unset($options['order_prescription_type']);
                                    if ($options) {
                                        $this->orderitemoption->where(['item_id' => $v['id'], 'site' => $site])->update($options);

                                        $this->orderitemprocess->where(['item_id' => $v['id'], 'site' => $site])->update(['order_prescription_type' => $order_prescription_type, 'sku' => $options['sku']]);
                                    }
                                }
                            }


                            //新增子表
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'sales_flat_order_item') {
                                foreach ($payload['data'] as $k => $v) {
                                    $options = [];
                                    //处方解析 不同站不同字段
                                    if ($site == 1) {
                                        $options = $this->zeelool_prescription_analysis($v['product_options']);
                                    } elseif ($site == 2) {
                                        $options = $this->voogueme_prescription_analysis($v['product_options']);
                                    } elseif ($site == 3) {
                                        $options = $this->nihao_prescription_analysis($v['product_options']);
                                    } elseif ($site == 4) {
                                        $options = $this->meeloog_prescription_analysis($v['product_options']);
                                    } elseif ($site == 5) {
                                        $options = $this->wesee_prescription_analysis($v['product_options']);
                                    } elseif ($site == 9) {
                                        $options = $this->zeelool_es_prescription_analysis($v['product_options']);
                                    } elseif ($site == 10) {
                                        $options = $this->zeelool_de_prescription_analysis($v['product_options']);
                                    } elseif ($site == 11) {
                                        $options = $this->zeelool_jp_prescription_analysis($v['product_options']);
                                    } elseif ($site == 12) {
                                        $options = $this->voogueme_acc_prescription_analysis($v['product_options']);
                                    }

                                    $options['item_id'] = $v['item_id'];
                                    $options['site'] = $site;
                                    $options['magento_order_id'] = $v['order_id'];
                                    $options['sku'] = $v['sku'];
                                    $options['qty'] = $v['qty_ordered'];
                                    $options['base_row_total'] = $v['base_row_total'];
                                    $options['product_id'] = $v['product_id'];
                                    $order_prescription_type = $options['order_prescription_type'];
                                    unset($options['order_prescription_type']);
                                    if ($options) {
                                        $options_id = $this->orderitemoption->insertGetId($options);
                                        $data = []; //子订单表数据
                                        for ($i = 0; $i < $v['qty_ordered']; $i++) {
                                            $data[$i]['item_id'] = $v['item_id'];
                                            $data[$i]['magento_order_id'] = $v['order_id'];
                                            $data[$i]['site'] = $site;
                                            $data[$i]['option_id'] = $options_id;
                                            $data[$i]['sku'] = $v['sku'];
                                            $data[$i]['order_prescription_type'] = $order_prescription_type;
                                            $data[$i]['created_at'] = strtotime($v['created_at']) + 28800;
                                            $data[$i]['updated_at'] = strtotime($v['updated_at']) + 28800;
                                        }
                                        $this->orderitemprocess->insertAll($data);
                                    }
                                }
                            }

                            //更新子表
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'sales_flat_order_item') {
                                foreach ($payload['data'] as $k => $v) {
                                    $options = [];
                                    //处方解析 不同站不同字段
                                    if ($site == 1) {
                                        $options = $this->zeelool_prescription_analysis($v['product_options']);
                                    } elseif ($site == 2) {
                                        $options = $this->voogueme_prescription_analysis($v['product_options']);
                                    } elseif ($site == 3) {
                                        $options = $this->nihao_prescription_analysis($v['product_options']);
                                    } elseif ($site == 4) {
                                        $options = $this->meeloog_prescription_analysis($v['product_options']);
                                    } elseif ($site == 5) {
                                        $options = $this->wesee_prescription_analysis($v['product_options']);
                                    } elseif ($site == 9) {
                                        $options = $this->zeelool_es_prescription_analysis($v['product_options']);
                                    } elseif ($site == 10) {
                                        $options = $this->zeelool_de_prescription_analysis($v['product_options']);
                                    } elseif ($site == 11) {
                                        $options = $this->zeelool_jp_prescription_analysis($v['product_options']);
                                    } elseif ($site == 12) {
                                        $options = $this->voogueme_acc_prescription_analysis($v['product_options']);
                                    }

                                    $options['sku'] = $v['sku'];
                                    $options['qty'] = $v['qty_ordered'];
                                    $options['base_row_total'] = $v['base_row_total'];
                                    $order_prescription_type = $options['order_prescription_type'];
                                    unset($options['order_prescription_type']);
                                    if ($options) {
                                        $this->orderitemoption->where(['item_id' => $v['item_id'], 'site' => $site])->update($options);

                                        $this->orderitemprocess->where(['item_id' => $v['item_id'], 'site' => $site])->update(['order_prescription_type' => $order_prescription_type, 'sku' => $options['sku']]);
                                    }
                                }
                            }
                        }

                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF: //没有数据
                        echo "No more messages; will wait for more\n";
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT: //超时
                        echo "Timed out\n";
                        break;
                    default:
                        echo "nothing \n";
                        throw new \Exception($message->errstr(), $message->err);
                        break;
                }
            }
        }
    }


}