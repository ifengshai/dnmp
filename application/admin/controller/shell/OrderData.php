<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use think\Db;

class OrderData extends Backend
{
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $this->orderprocess = new \app\admin\model\order\order\NewOrderProcess();
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->meeloog = new \app\admin\model\order\order\Meeloog();
        $this->wesee = new \app\admin\model\order\order\Weseeoptical();
        $this->zeelool_es = new \app\admin\model\order\order\ZeeloolEs();
        $this->zeelool_de = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelool_jp = new \app\admin\model\order\order\ZeeloolJp();
    }

    /**
     * 处理订单数据
     *
     * @Description
     * @author wpl
     * @since 2020/10/21 14:55:50 
     * @return void
     */
    public function process_order_data()
    {
        /**
         * 代码中的输出注释都可以打开供调试使用
         * 对 中台生产的  用户信息 进行消费
         */
        // 设置将要消费消息的主题
        $topic = 'mojing_order';
        $host = '127.0.0.1:9092';
        $group_id = '0';
        $conf = new \RdKafka\Conf();
        // 当有新的消费进程加入或者退出消费组时，kafka 会自动重新分配分区给消费者进程，这里注册了一个回调函数，当分区被重新分配时触发
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;
                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(NULL);
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
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
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
                            switch ($payload['database']) {
                                case 'zeelool':
                                    $site = 1;
                                    break;
                                case 'voogueme':
                                    $site = 2;
                                    break;
                                case 'nihao':
                                    $site = 3;
                                    break;
                                case 'meeloog':
                                    $site = 4;
                                    break;
                                case 'wesee':
                                    $site = 5;
                                    break;
                                case 'zeelool_es':
                                    $site = 9;
                                    break;
                                case 'zeelool_de':
                                    $site = 10;
                                    break;
                                case 'zeelool_jp':
                                    $site = 11;
                                    break;
                            }
                            //主表
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'sales_flat_order') {
                                $order_params = [];
                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    $params['entity_id'] = $v['entity_id'];
                                    $params['site'] = $site;
                                    $params['increment_id'] = $v['increment_id'];
                                    $params['status'] = $v['status'] ?: '';
                                    $params['store_id'] = $v['store_id'];
                                    $params['base_grand_total'] = $v['base_grand_total'];
                                    $params['total_item_count'] = $v['total_item_count'];
                                    $params['total_qty_ordered'] = $v['total_qty_ordered'];
                                    $params['order_type'] = $v['order_type'];
                                    $params['base_currency_code'] = $v['base_currency_code'];
                                    $params['order_currency_code'] = $v['order_currency_code'];
                                    $params['shipping_method'] = $v['shipping_method'];
                                    $params['shipping_title'] = $v['shipping_description'];
                                    $params['country_id'] = $v['country_id'];
                                    $params['region'] = $v['region'];
                                    $params['city'] = $v['city'];
                                    $params['street'] = $v['street'];
                                    $params['postcode'] = $v['postcode'];
                                    $params['telephone'] = $v['telephone'];
                                    $params['customer_email'] = $v['customer_email'];
                                    $params['customer_firstname'] = $v['customer_firstname'];
                                    $params['customer_lastname'] = $v['customer_lastname'];
                                    $params['taxno'] = $v['taxno'];
                                    $params['base_to_order_rate'] = $v['base_to_order_rate'];
                                    $params['mw_rewardpoint'] = $v['mw_rewardpoint'];
                                    $params['mw_rewardpoint_discount'] = $v['mw_rewardpoint_discount'];
                                    $params['base_shipping_amount'] = $v['base_shipping_amount'];
                                    $params['created_at'] = strtotime($v['created_at']) + 28800;
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }
                                   
                                    //插入订单主表
                                    $order_id = $this->order->insertGetId($params);
                                    $order_params[$k]['site'] = $site;
                                    $order_params[$k]['order_id'] = $order_id;
                                    $order_params[$k]['entity_id'] = $v['entity_id'];
                                    $order_params[$k]['increment_id'] = $v['increment_id'];
                                }
                                //插入订单处理表
                                $this->orderprocess->saveAll($order_params);
                            }

                            //更新主表
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'sales_flat_order') {

                                foreach ($payload['data'] as $k => $v) {
                                    $params = [];
                                    $params['base_grand_total'] = $v['base_grand_total'];
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
                                    $params['taxno'] = $v['taxno'];
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

                            //新增子表
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'sales_flat_order_item') {
                                foreach ($payload['data'] as $k => $v) {
                                    $options = [];
                                    //处方解析 不同站不同字段
                                    if ($site == 1) {
                                        $options =  $this->zeelool_prescription_analysis($v['product_options']);
                                    } elseif ($site == 2) {
                                        $options =  $this->voogueme_prescription_analysis($v['product_options']);
                                    } elseif ($site == 3) {
                                        $options =  $this->nihao_prescription_analysis($v['product_options']);
                                    } elseif ($site == 4) {
                                        $options =  $this->meeloog_prescription_analysis($v['product_options']);
                                    } elseif ($site == 5) {
                                        $options =  $this->wesee_prescription_analysis($v['product_options']);
                                    } elseif ($site == 9) {
                                        $options =  $this->zeelool_es_prescription_analysis($v['product_options']);
                                    } elseif ($site == 10) {
                                        $options =  $this->zeelool_de_prescription_analysis($v['product_options']);
                                    } elseif ($site == 11) {
                                        $options =  $this->zeelool_jp_prescription_analysis($v['product_options']);
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
                                        $options =  $this->zeelool_prescription_analysis($v['product_options']);
                                    } elseif ($site == 2) {
                                        $options =  $this->voogueme_prescription_analysis($v['product_options']);
                                    } elseif ($site == 3) {
                                        $options =  $this->nihao_prescription_analysis($v['product_options']);
                                    } elseif ($site == 4) {
                                        $options =  $this->meeloog_prescription_analysis($v['product_options']);
                                    } elseif ($site == 5) {
                                        $options =  $this->wesee_prescription_analysis($v['product_options']);
                                    } elseif ($site == 9) {
                                        $options =  $this->zeelool_es_prescription_analysis($v['product_options']);
                                    } elseif ($site == 10) {
                                        $options =  $this->zeelool_de_prescription_analysis($v['product_options']);
                                    } elseif ($site == 11) {
                                        $options =  $this->zeelool_jp_prescription_analysis($v['product_options']);
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
            } else {
                echo "error\n";
            }
        }
    }

    /**
     * Zeelool 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function zeelool_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['lenstype_data_name'] ?: '';
        //镜片名称
        $index_name = $options['info_buyRequest']['tmplens']['lens_data_name'] ?: $options['info_buyRequest']['tmplens']['index_type'];
        $arr['index_name'] = $index_name ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coating_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coating_base_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_base_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['lens_base_price'];
        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['lens_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];

        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_data_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;
        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        $arr['os_add'] = $options_params['os_add'];
        $arr['od_add'] = $options_params['od_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 仅镜架逻辑
         * 镜片名称为空 或者 Plastic Lenses 或者 Frame Only
         * 
         * 现货处方镜逻辑
         * 
         * 
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);
        return $arr;
    }


    /**
     * Voogueme 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function voogueme_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coatiing_base_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_base_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['lens_base_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['index_color'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;

        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['index_id'];
        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];
        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';
        $arr['os_sph'] = $options_params['os_sph'] ?: '';
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        //V站左右眼add是反的
        $arr['os_add'] = $options_params['od_add'];
        $arr['od_add'] = $options_params['os_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);

        return $arr;
    }


    /**
     * Nihao 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function nihao_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['lens_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['third_name'] ?: '';
        //光度等参数
        $options_params = json_decode($options['info_buyRequest']['tmplens']['prescription'], true);

        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['four_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coating_base_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_base_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['lens_base_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;

        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['four_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['third_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];

        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        $arr['os_add'] = $options_params['os_add'];
        $arr['od_add'] = $options_params['od_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);

        return $arr;
    }

    /**
     * Meeloog 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function meeloog_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coatiing_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['index_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;

        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['index_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];

        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        $arr['os_add'] = $options_params['os_add'];
        $arr['od_add'] = $options_params['od_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);
        return $arr;
    }

    /**
     * Wesee 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function wesee_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coatiing_base_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['lens_base_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;

        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['index_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];

        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['os_add'] = $options_params['os_add'];
        $arr['od_add'] = $options_params['od_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        //判断是否为成品老花镜
        if ($options['info_buyRequest']['tmplens']['degrees'] && !$arr['index_type']) {
            $arr['od_sph'] = $options['info_buyRequest']['tmplens']['degrees'];
            $arr['os_sph'] = $options['info_buyRequest']['tmplens']['degrees'];
            $arr['index_type'] = '1.61 Index Standard  Reading Glasses - Non Prescription';
            $arr['index_name'] = '1.61 Index Standard  Reading Glasses - Non Prescription';
        }

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);
        return $arr;
    }

    /**
     * 西语站 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function zeelool_es_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_name'] ?: '';
        //图片id
        $arr['prescription_pic_id'] = $options['info_buyRequest']['tmplens']['prescription_pic_id'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coatiing_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['index_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;

        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['index_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];

        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        //小语种站左右眼add是反的
        $arr['os_add'] = $options_params['od_add'];
        $arr['od_add'] = $options_params['os_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);

        return $arr;
    }

    /**
     * 德语站 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function zeelool_de_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coatiing_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['index_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;
        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['index_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];
        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        //小语种站左右眼add是反的
        $arr['os_add'] = $options_params['od_add'];
        $arr['od_add'] = $options_params['os_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);
        return $arr;
    }


    /**
     * 日语站 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function zeelool_jp_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = array();
        foreach ($prescription_params as $key => $value) {
            $arr_value = explode("=", $value);
            $options_params[$arr_value[0]] = $arr_value[1];
        }
        //处方类型
        $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['coatiing_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['index_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
        //镜片分类
        $arr['goods_type'] = $options['info_buyRequest']['tmplens']['goods_type'] ?? 0;
        $arr['color_id'] = $options['info_buyRequest']['tmplens']['color_id'];
        $arr['coating_id'] = $options['info_buyRequest']['tmplens']['coating_id'];
        $arr['index_id'] = $options['info_buyRequest']['tmplens']['index_id'];

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['info_buyRequest']['tmplens']['web_lens_name'];
        //光度参数
        $arr['od_sph'] = $options_params['od_sph'] ?: '';;
        $arr['os_sph'] = $options_params['os_sph'] ?: '';;
        $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
        $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
        $arr['od_axis'] = $options_params['od_axis'];
        $arr['os_axis'] = $options_params['os_axis'];
        $arr['pd_l'] = $options_params['pd_l'];
        $arr['pd_r'] = $options_params['pd_r'];
        $arr['pd'] = $options_params['pd'];
        $arr['pdcheck'] = $options_params['pdcheck'];
        $arr['prismcheck'] = $options_params['prismcheck'];
        //日语站左右眼add恢复正常
        $arr['os_add'] = $options_params['os_add'];
        $arr['od_add'] = $options_params['od_add'];
        $arr['od_pv'] = $options_params['od_pv'];
        $arr['os_pv'] = $options_params['os_pv'];
        $arr['od_pv_r'] = $options_params['od_pv_r'];
        $arr['os_pv_r'] = $options_params['os_pv_r'];
        $arr['od_bd'] = $options_params['od_bd'];
        $arr['os_bd'] = $options_params['os_bd'];
        $arr['od_bd_r'] = $options_params['od_bd_r'];
        $arr['os_bd_r'] = $options_params['os_bd_r'];

        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);
        return $arr;
    }


    /**
     * 判断定制现片逻辑
     */
    public function set_processing_type($params = [])
    {
        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */
        $arr = [];
        $lens_number = config('LENS_NUMBER');
        if (in_array($params['lens_number'], $lens_number)) {
            $arr['order_prescription_type'] = 3;
            $arr['is_custom_lens'] = 1;
        }

        //仅镜框
        if ($params['lens_number'] == '10000000' || !$params['lens_number']) {
            $arr['order_prescription_type'] = 1;
        }

        if ($params['lens_number'] == '23100000' || $params['lens_number'] == '23100001') {
            /**
             * 1.61非球面绿膜 定制片
             * SPH:0.00～-8.00 CYL:-4.25～-6.00
             */
            if ((((float) urldecode($params['od_sph']) >= -8 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -8 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -6 && (float) urldecode($params['od_cyl']) <= -4.25) || ((float) urldecode($params['os_cyl']) >= -6 && (float) urldecode($params['os_cyl']) <= -4.25))) {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '24100000' || $params['lens_number'] == '24200000') {
            /**
             * 1.67非球面绿膜 现片
             * SPH:-3.00～-12.00 CYL:0.00～-2.00（不含-0.25）
             */
            if ((((float) urldecode($params['od_sph']) >= -12 && (float) urldecode($params['od_sph']) <= -3) || ((float) urldecode($params['os_sph']) >= -12 && (float) urldecode($params['os_sph']) <= -3)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) <= 0 && (float) urldecode($params['od_cyl']) != -0.25))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '25100000') {
            /**
             * 1.71非球面绿膜 现片
             * SPH:0.00～-15.00 CYL:0.00～-2.00（不含-0.25）
             */
            if ((((float) urldecode($params['od_sph']) >= -15 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -15 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '26100000') {
            /**
             * 1.74非球面绿膜 现片
             * SPH:-3.00～-13.00 CYL:0.00～-2.00（不含-0.25）
             */
            if ((((float) urldecode($params['od_sph']) >= -13 && (float) urldecode($params['od_sph']) <= -3) || ((float) urldecode($params['os_sph']) >= -13 && (float) urldecode($params['os_sph']) <= -3)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '23200000' || $params['lens_number'] == '23200001') {
            /**
             * 1.61防蓝光 现片
             * SPH:0.00～-8.00 CYL:0.00～-2.00
             * SPH:0.00～-8.00 CYL:-2.25～-4.00
             */
            if ((((float) urldecode($params['od_sph']) >= -8 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -8 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -4 && (float) urldecode($params['od_cyl']) < 0) || ((float) urldecode($params['os_cyl']) >= -4 && (float) urldecode($params['os_cyl']) < 0))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '25200000' || $params['lens_number'] == '25302000' || $params['lens_number'] == '25303000') {
            /**
             * 1.57变色 现片 1.71变色灰
             * SPH:0.00～-3.00 CYL:0.00～-2.00
             */
            if ((((float) urldecode($params['od_sph']) >= -12 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -12 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) < 0 && (float) urldecode($params['od_cyl']) != -0.25))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '23302000' || $params['lens_number'] == '22306000' || $params['lens_number'] == '22305000') {
            /**
             * 1.71防蓝光 现片
             * SPH:0.00～-12.00 CYL:0.00～-2.00（不含-0.25）
             */
            if ((((float) urldecode($params['od_sph']) >= -3 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -3 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) < 0) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) < 0))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }


        if ($params['lens_number'] == '23302000' || $params['lens_number'] == '23303000' || $params['lens_number'] == '23302001' || $params['lens_number'] == '23303001') {
            /**
             * 1.61变色灰 现片
             * SPH:0.00～-8.00 CYL:0.00～-2.00
             */
            if ((((float) urldecode($params['od_sph']) >= -8 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -8 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) < 0) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) < 0))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '23304000' || $params['lens_number'] == '23306000' || $params['lens_number'] == '23305000') {
            /**
             * 1.61变色蓝 现片
             * SPH:0.00～-8.00 CYL:0.00～-2.00
             */
            if ((((float) urldecode($params['od_sph']) >= -8 && (float) urldecode($params['od_sph']) < 0) || ((float) urldecode($params['os_sph']) >= -8 && (float) urldecode($params['os_sph']) < 0)) && (((float) urldecode($params['od_cyl']) >= -2 && (float) urldecode($params['od_cyl']) <= 0.5) || ((float) urldecode($params['os_cyl']) >= -2 && (float) urldecode($params['os_cyl']) <= 0.5))) {
                $arr['order_prescription_type'] = 2;
            } elseif ((float) urldecode($params['od_sph']) == 0 && (float) urldecode($params['os_sph']) == 0) {
                $arr['order_prescription_type'] = 2;
            } else {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }
        //定制处方镜
        if ($arr['is_custom_lens'] == 1) {
            $arr['order_prescription_type'] = 3;
        }

        //默认如果不是仅镜架 或定制片 则为现货处方镜
        if ($arr['order_prescription_type'] != 1 && $arr['order_prescription_type'] != 3) {
            $arr['order_prescription_type'] = 2;
        }

        return $arr;
    }



    /**
     * 批量生成子订单表子单号
     * 
     * @Description
     * @todo 计划任务 10分钟一次
     * @author wpl
     * @since 2020/10/28 17:36:27 
     * @return void
     */
    public function set_order_item_number_shell()
    {
        //查询未生成子单号的数据
        $list = $this->orderitemprocess->where('LENGTH(trim(item_order_number))=0')->limit(3000)->select();
        $list = collection($list)->toArray();
        foreach ($list as $v) {
            $res = $this->order->where(['entity_id' => $v['magento_order_id'], 'site' => $v['site']])->field('id,increment_id')->find();
            $data = $this->orderitemprocess->where(['magento_order_id' => $v['magento_order_id'], 'site' => $v['site']])->select();
            $item_params = [];
            foreach ($data as $key => $val) {
                $item_params[$key]['id'] = $val['id'];
                $str = '';
                if ($key < 9) {
                    $str = '0' . ($key + 1);
                } else {
                    $str = $key + 1;
                }

                $item_params[$key]['item_order_number'] = $res->increment_id . '-' . $str;
                $item_params[$key]['order_id'] = $res->id;
            }
            //更新数据
            if ($item_params) $this->orderitemprocess->saveAll($item_params);

            echo $v['id'] . "\n";
            usleep(10000);
        }

        echo "ok";
    }

    /**
     * 批量更新order表主键
     *
     * @Description
     * @todo 计划任务 10分钟一次
     * @author wpl
     * @since 2020/10/28 17:58:46 
     * @return void
     */
    public function set_order_id()
    {
        //查询未生成子单号的数据
        $list = $this->orderitemoption->where('order_id', 0)->field('id,site,magento_order_id')->limit(4000)->select();
        $list = collection($list)->toArray();
        $params = [];
        foreach ($list as $k => $v) {
            $order_id = $this->order->where(['entity_id' => $v['magento_order_id'], 'site' => $v['site']])->value('id');
            $params[$k]['id'] = $v['id'];
            $params[$k]['order_id'] = $order_id;
            echo $v['id'] . "\n";
        }
        //更新数据
        if ($params) $this->orderitemoption->saveAll($params);
        echo "ok";
    }


    /**
     * 更新订单商品总数量
     *
     * @Description
     * @author wpl
     * @since 2020/11/03 15:04:12 
     * @return void
     */
    public function order_total_qty_ordered()
    {
        $list = $this->order->where('total_qty_ordered=0')->limit(5000)->select();
        $list = collection($list)->toArray();
        $params = [];
        foreach ($list as $k => $v) {
            $qty = $this->orderitemoption->where(['magento_order_id' => $v['entity_id'], 'site' => $v['site']])->sum('qty');
            $params[$k]['total_qty_ordered'] = $qty;
            $params[$k]['id'] = $v['id'];
            echo $k . "\n";
        }
        $this->order->saveAll($params);
        echo 'ok';
    }








    ################################################处理旧数据脚本##########################################################################

    /**
     * 处理主单旧数据
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 15:47:45 
     * @return void
     */
    public function process_order_data_temp()
    {
        $this->zeelool_old_order(1);
        // $this->zeelool_old_order(5);
    }
    protected function zeelool_old_order($site)
    {
        if ($site == 1) {
            $list = $this->zeelool->where(['entity_id' => 557772])->select();
        } elseif ($site == 5) {
            $id = $this->order->where('site=' . $site . ' and entity_id < 1375')->max('entity_id');
            $list = $this->wesee->where(['entity_id' => ['between', [$id, 1375]]])->limit(3000)->select();
        }

        $list = collection($list)->toArray();

        $order_params = [];
        foreach ($list as $k => $v) {
            $count = $this->order->where('site=' . $site . ' and entity_id=' . $v['entity_id'])->count();
            if ($count > 0) {
                continue;
            }
            $params = [];
            $params['entity_id'] = $v['entity_id'];
            $params['site'] = $site;
            $params['increment_id'] = $v['increment_id'];
            $params['status'] = $v['status'] ?: '';
            $params['store_id'] = $v['store_id'];
            $params['base_grand_total'] = $v['base_grand_total'];
            $params['total_item_count'] = $v['total_qty_ordered'];
            $params['order_type'] = $v['order_type'];
            $params['base_currency_code'] = $v['base_currency_code'];
            $params['shipping_method'] = $v['shipping_method'];
            $params['shipping_title'] = $v['shipping_description'];
            $params['country_id'] = $v['country_id'];
            $params['region'] = $v['region'];
            $params['city'] = $v['city'];
            $params['street'] = $v['street'];
            $params['postcode'] = $v['postcode'];
            $params['telephone'] = $v['telephone'];
            $params['customer_email'] = $v['customer_email'];
            $params['customer_firstname'] = $v['customer_firstname'];
            $params['customer_lastname'] = $v['customer_lastname'];
            $params['taxno'] = $v['taxno'];
            $params['base_to_order_rate'] = $v['base_to_order_rate'];
            $params['mw_rewardpoint_discount'] = $v['mw_rewardpoint_discount'];
            $params['mw_rewardpoint'] = $v['mw_rewardpoint'];
            $params['base_shipping_amount'] = $v['base_shipping_amount'];
            $params['created_at'] = strtotime($v['created_at']) + 28800;
            $params['updated_at'] = strtotime($v['updated_at']) + 28800;
            //插入订单主表
            $order_id = $this->order->insertGetId($params);
            $order_params[$k]['site'] = $site;
            $order_params[$k]['order_id'] = $order_id;
            $order_params[$k]['entity_id'] = $v['entity_id'];
            $order_params[$k]['increment_id'] = $v['increment_id'];

            echo $v['entity_id'] . "\n";
            usleep(10000);
        }
        //插入订单处理表
        if ($order_params) $this->orderprocess->saveAll($order_params);
        echo "ok";
    }



    public function order_address_data_shell()
    {
        $this->order_address_data(1);
        $this->order_address_data(2);
        $this->order_address_data(3);
        $this->order_address_data(4);
        $this->order_address_data(5);
        $this->order_address_data(9);
        $this->order_address_data(10);
        $this->order_address_data(11);
    }

    /**
     * 地址处理
     *
     * @Description
     * @author wpl
     * @since 2020/11/02 18:31:12 
     * @return void
     */
    protected function order_address_data($site)
    {
        $list = $this->order->where('firstname is null and site = ' . $site)->limit(3000)->select();
        $list = collection($list)->toArray();
        $entity_id = array_column($list, 'entity_id');
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 2) {
            $res = Db::connect('database.db_voogueme')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 3) {
            $res = Db::connect('database.db_nihao')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 4) {
            $res = Db::connect('database.db_meeloog')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 5) {
            $res = Db::connect('database.db_weseeoptical')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 9) {
            $res = Db::connect('database.db_zeelool_es')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 10) {
            $res = Db::connect('database.db_zeelool_de')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        } elseif ($site == 11) {
            $res = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
        }
        $params = [];
        foreach ($list as $k => $v) {
            $params[$k]['id'] = $v['id'];
            $params[$k]['firstname'] = $res[$v['entity_id']]['firstname'];
            $params[$k]['lastname'] = $res[$v['entity_id']]['lastname'];
            // $params[$k]['city'] = $res[$v['entity_id']]['city'];
            // $params[$k]['street'] = $res[$v['entity_id']]['street'];
            // $params[$k]['postcode'] = $res[$v['entity_id']]['postcode'];
            // $params[$k]['telephone'] = $res[$v['entity_id']]['telephone'];
        }
        $this->order->saveAll($params);
        echo $site . 'ok';
    }

    public function order_data_shell()
    {

        $this->order_data(1);
        $this->order_data(2);
        $this->order_data(3);
        // $this->order_data(4);
        $this->order_data(5);
        $this->order_data(9);
        $this->order_data(10);
        $this->order_data(11);
    }

    /**
     * 地址处理
     *
     * @Description
     * @author wpl
     * @since 2020/11/02 18:31:12 
     * @return void
     */
    protected function order_data($site)
    {
        $list = $this->order->where('payment_time is null and site = ' . $site)->limit(3000)->select();
        $list = collection($list)->toArray();
        $entity_id = array_column($list, 'entity_id');
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 2) {
            $res = Db::connect('database.db_voogueme')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 3) {
            $res = Db::connect('database.db_nihao')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 4) {
            $res = Db::connect('database.db_meeloog')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 5) {
            $res = Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 9) {
            $res = Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 10) {
            $res = Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        } elseif ($site == 11) {
            $res = Db::connect('database.db_zeelool_jp')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('payment_time', 'entity_id');
        }
        $params = [];
        foreach ($list as $k => $v) {
            $params[$k]['id'] = $v['id'];
            $params[$k]['payment_time'] = strtotime($res[$v['entity_id']]) + 28800;
            // $params[$k]['region'] = $res[$v['entity_id']]['region'];
            // $params[$k]['city'] = $res[$v['entity_id']]['city'];
            // $params[$k]['street'] = $res[$v['entity_id']]['street'];
            // $params[$k]['postcode'] = $res[$v['entity_id']]['postcode'];
            // $params[$k]['telephone'] = $res[$v['entity_id']]['telephone'];
        }
        $this->order->saveAll($params);
        echo $site . 'ok';
    }


    /**
     * 临时处理订单子表数据
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 16:47:50 
     * @return void
     */
    public function order_item_data_shell()
    {
        $this->order_item_shell(1);
    }

    protected function order_item_shell($site)
    {
        ini_set('memory_limit', '2280M');
        if ($site == 1) {
            // $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 929673')->max('item_id');
            $list = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where(['item_id' => ['>', 929673]])->where(['item_id' => ['<', 1026606]])->select();
        }

        // elseif ($site == 2) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 515947')->max('item_id');
        //     $list = Db::connect('database.db_voogueme')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 515947]]])->limit(3000)->select();
        // } elseif ($site == 3) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 76642')->max('item_id');
        //     $list = Db::connect('database.db_nihao')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 76642]]])->limit(3000)->select();
        // } elseif ($site == 4) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 4111')->max('item_id');
        //     $list = Db::connect('database.db_meeloog')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 4111]]])->limit(3000)->select();
        // } elseif ($site == 5) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 14134')->max('item_id');
        //     $list = Db::connect('database.db_weseeoptical')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 14134]]])->limit(3000)->select();
        // } elseif ($site == 9) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 139')->max('item_id');
        //     $list = Db::connect('database.db_zeelool_es')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 139]]])->limit(3000)->select();
        // } elseif ($site == 10) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 1038')->max('item_id');
        //     $list = Db::connect('database.db_zeelool_de')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 1038]]])->limit(3000)->select();
        // } elseif ($site == 11) {
        //     $id = $this->orderitemoption->where('site=' . $site . ' and item_id < 215')->max('item_id');
        //     $list = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_item')->where(['item_id' => ['between', [$id, 215]]])->limit(3000)->select();
        // }

        foreach ($list as $k => $v) {
            $count = $this->orderitemprocess->where('site=' . $site . ' and item_id=' . $v['item_id'])->count();
            if ($count > 0) {
                continue;
            }
            $options = [];
            //处方解析 不同站不同字段
            if ($site == 1) {
                $options =  $this->zeelool_prescription_analysis($v['product_options']);
            } elseif ($site == 2) {
                $options =  $this->voogueme_prescription_analysis($v['product_options']);
            } elseif ($site == 3) {
                $options =  $this->nihao_prescription_analysis($v['product_options']);
            } elseif ($site == 4) {
                $options =  $this->meeloog_prescription_analysis($v['product_options']);
            } elseif ($site == 5) {
                $options =  $this->wesee_prescription_analysis($v['product_options']);
            } elseif ($site == 9) {
                $options =  $this->zeelool_es_prescription_analysis($v['product_options']);
            } elseif ($site == 10) {
                $options =  $this->zeelool_de_prescription_analysis($v['product_options']);
            } elseif ($site == 11) {
                $options =  $this->zeelool_jp_prescription_analysis($v['product_options']);
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
            echo $v['item_id'] . "\n";
            usleep(10000);
        }
        echo "ok";
    }

    /**
     * 订单支付临时表
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 17:06:50 
     * @return void
     */
    public function order_payment_data_shell()
    {
        $this->order_payment_data(1);
        $this->order_payment_data(2);
        $this->order_payment_data(3);
        $this->order_payment_data(4);
        $this->order_payment_data(5);
        $this->order_payment_data(9);
        $this->order_payment_data(10);
        $this->order_payment_data(11);
    }

    /**
     * 支付方式处理
     *
     * @Description
     * @author wpl
     * @since 2020/11/02 18:31:12 
     * @return void
     */
    protected function order_payment_data($site)
    {
        $list = $this->order->where('last_trans_id is null and site = ' . $site)->limit(4000)->select();
        $list = collection($list)->toArray();
        $entity_id = array_column($list, 'entity_id');
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 2) {
            $res = Db::connect('database.db_voogueme')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 3) {
            $res = Db::connect('database.db_nihao')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 4) {
            $res = Db::connect('database.db_meeloog')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 5) {
            $res = Db::connect('database.db_weseeoptical')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 9) {
            $res = Db::connect('database.db_zeelool_es')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 10) {
            $res = Db::connect('database.db_zeelool_de')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 11) {
            $res = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        }
        if ($res) {
            $params = [];
            foreach ($list as $k => $v) {
                $params[$k]['id'] = $v['id'];
                $params[$k]['last_trans_id'] = $res[$v['entity_id']] ?: 0;
            }
            $this->order->saveAll($params);
            echo $site . 'ok';
        }
    }


    /**
     * 支付方式处理
     *
     * @Description
     * @author wpl
     * @since 2020/11/02 18:31:12 
     * @return void
     */
    public function order_product_id_data()
    {
        $list = $this->orderitemoption->where('product_id is null')->limit(4000)->select();
        $list = collection($list)->toArray();

        $params = [];
        foreach ($list as $k => $v) {

            if ($v['site'] == 1) {
                $product_id = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 2) {
                $product_id = Db::connect('database.db_voogueme')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 3) {
                $product_id = Db::connect('database.db_nihao')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 4) {
                $product_id = Db::connect('database.db_meeloog')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 5) {
                $product_id = Db::connect('database.db_weseeoptical')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 9) {
                $product_id = Db::connect('database.db_zeelool_es')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 10) {
                $product_id = Db::connect('database.db_zeelool_de')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            } elseif ($v['site'] == 11) {
                $product_id = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_item')->where('order_id', $v['magento_order_id'])->where('item_id', $v['item_id'])->value('product_id');
            }
            if (!$product_id) continue;
            $params[$k]['id'] = $v['id'];
            $params[$k]['product_id'] = $product_id;
            echo $k . "\n";
        }
        $this->orderitemoption->saveAll($params);
        echo 'ok';
    }

    /**
     * 临时处理订单子表数据
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 16:47:50 
     * @return void
     */
    public function order_item_shell_temp()
    {
        $this->order_item_data_shell_temp(1);
        $this->order_item_data_shell_temp(2);
        $this->order_item_data_shell_temp(3);
        $this->order_item_data_shell_temp(4);
        $this->order_item_data_shell_temp(5);
        $this->order_item_data_shell_temp(9);
        $this->order_item_data_shell_temp(10);
        $this->order_item_data_shell_temp(11);
        // $this->order_item_shell(5);

    }

    protected function order_item_data_shell_temp($site)
    {
        $list = $this->orderitemprocess->where('site=' . $site . ' and distribution_status = 3')->limit(3000)->select();
        $list = collection($list)->toArray();
        $item_ids = array_column($list, 'item_id');

        if ($site == 1) {
            $item_data = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 2) {
            $item_data = Db::connect('database.db_voogueme')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 3) {
            $item_data = Db::connect('database.db_nihao')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 4) {
            $item_data = Db::connect('database.db_meeloog')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 5) {
            $item_data = Db::connect('database.db_weseeoptical')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 9) {
            $item_data = Db::connect('database.db_zeelool_es')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 10) {
            $item_data = Db::connect('database.db_zeelool_de')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        } elseif ($site == 11) {
            $item_data = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_item')->where(['item_id' => ['in', $item_ids]])->column('product_options', 'item_id');
        }
        $option_params = [];
        foreach ($list as $k => $v) {
            $options = [];
            //处方解析 不同站不同字段
            if ($site == 1) {
                $options =  $this->zeelool_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 2) {
                $options =  $this->voogueme_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 3) {
                $options =  $this->nihao_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 4) {
                $options =  $this->meeloog_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 5) {
                $options =  $this->wesee_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 9) {
                $options =  $this->zeelool_es_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 10) {
                $options =  $this->zeelool_de_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 11) {
                $options =  $this->zeelool_jp_prescription_analysis($item_data[$v['item_id']]);
            }
            $option_params[$k]['order_prescription_type'] = $options['order_prescription_type'];
            $option_params[$k]['id'] = $v['id'];
            echo $v['item_id'] . "\n";
            usleep(10000);
        }

        $this->orderitemprocess->saveAll($option_params);
        echo "ok";
    }




    public function process_order_type()
    {
        $item_order_number = [
            '1500001280-01',
            '400431302-01',
            '100186219-01',
            '100185788-02',
            '400431855-02',
            '500017545-01',
            '400431933-01',
            '130081448-01',
            '430244324-01',
            '400432328-01',
            '500017432-03',
            '430244755-01',
            '400269948-01',
            '130081454-01',
            '400428532-02',
            '400406466-02',
            '100184817-03',
            '100184577-01',
            '400361462-01',
            '400428559-01',
            '500017433-02',
            '400431037-01',
            '500017491-01',
            '400431075-02',
            '400430721-02',
            '400431031-04',
            '400431302-03',
            '600125316-02',
            '600125327-01',
            '100185800-03',
            '400432111-01',
            '400394667-01',
            '400431094-01',
            '100184817-01',
            '400427792-01',
            '100172309-01',
            '400427792-03',
            '400431026-04',
            '400431032-03',
            '130081160-01',
            '400431930-03',
            '400431072-02',
            '400431073-01',
            '100175755-01',
            '500017521-01',
            '400431067-01',
            '130081459-02',
            '400368020-02',
            '400427795-02',
            '100186181-04',
            '400430534-02',
            '130081458-01',
            '300042718-01',
            '400428761-04',
            '300042718-02',
            '530002669-01',
            '400429863-01',
            '100184577-02',
            '100185174-01',
            '400431324-01',
            '600125379-02',
            '130081451-01',
            '500017437-01',
            '100185595-01',
            '400432057-01',
            '400431067-02',
            '400429528-01',
            '430244175-01',
            '400431141-02',
            '400431112-02',
            '100185310-02',
            '430243078-01',
            '130081459-01',
            '400417777-01',
            '100185112-01',
            '130081449-01',
            '600125272-01',
            '430244004-02',
            '400431076-04',
            '100185799-01',
            '530002672-01',
            '400431109-01',
            '400431071-01',
            '400430848-01',
            '400430452-02',
            '100185788-01',
            '400431030-01',
            '400429274-02',
            '400431075-04',
            '400431096-01',
            '430243999-02',
            '400431045-01',
            '430243961-01',
            '400431281-02',
            '500017429-01',
            '400431114-01',
            '400431321-01',
            '400431068-04',
            '430243970-01',
            '100185799-02',
            '100185793-01',
            '400359728-01',
            '400431044-02',
            '4690000406-01',
            '400431087-01',
            '500017250-02',
            '100186111-02',
            '430244676-01',
            '430243994-01',
            '430243966-01',
            '400431141-01',
            '300044973-01',
            '400431240-01',
            '130081578-02',
            '400431102-04',
            '130081377-03',
            '400431039-01',
            '400431072-01',
            '100185801-01',
            '500005024-01',
            '400431095-02',
            '400431107-01',
            '400431041-01',
            '400431044-03',
            '400431087-03',
            '430244319-03',
            '400429616-02',
            '100185789-01',
            '400431520-04',
            '400430275-01',
            '400431101-01',
            '400431075-01',
            '400431044-01',
            '100185791-01',
            '400431040-02',
            '400430935-02',
            '100185576-01',
            '130081450-01',
            '430243618-01',
            '430243789-01',
            '430243776-01',
            '430243967-01',
            '400431062-01',
            '100185789-02',
            '430243996-01',
            '400430621-01',
            '500017361-01',
            '430243963-03',
            '430243860-02',
            '430244003-01',
            '400430848-03',
            '400431070-02',
            '400431031-02',
            '400431107-02',
            '400431095-03',
            '430243986-02',
            '400431046-01',
            '430244004-01',
            '130081444-03',
            '430244060-02',
            '430244012-02',
            '530002672-02',
            '400368020-01',
            '500017441-01',
            '100119588-01',
            '100185437-03',
            '400431026-01',
            '430243964-02',
            '130081482-03',
            '600125326-01',
            '430243987-01',
            '430244007-01',
            '130081444-01',
            '430244001-01',
            '130081720-01',
            '600125005-01',
            '400431043-01',
            '100185801-02',
            '400431059-02',
            '430243703-01',
            '400431070-01',
            '400431031-01',
            '400431138-01',
            '430243659-01',
            '400430359-01',
            '430243976-02',
            '400429421-01',
            '100185798-02',
            '400431033-01',
            '400431077-01',
            '130081370-02',
            '130081444-04',
            '430243930-02',
            '400427961-03',
            '600125323-01',
            '400431029-01',
            '400431427-01',
            '500017440-02',
            '430243884-02',
            '130081269-02',
            '400431076-02',
            '400431026-06',
            '400431104-01',
            '430244008-01',
            '500017432-01',
            '130081289-02',
            '400431102-01',
            '400431094-02',
            '430243726-03',
            '430243621-02',
            '430242730-01',
            '400431269-02',
            '500017432-02',
            '400427961-05',
            '100186013-01',
            '300045146-02',
            '430244235-01',
            '100186178-01',
            '130081445-01',
            '430243613-01',
            '600124974-01',
            '400430613-01',
            '400429545-01',
            '430243613-02',
            '400428516-01',
            '130081484-01',
            '430243835-11',
            '430243402-01',
            '500007037-01',
            '400431302-02',
            '400431092-01',
            '430243844-01',
            '100186194-01',
            '400431062-02',
            '100186313-03',
            '500017272-02',
            '430243478-01',
            '400431694-01',
            '400429793-01',
            '430243647-02',
            '430238875-02',
            '430243986-01',
            '430243608-01',
            '400431070-03',
            '400431032-02',
            '400428794-01',
            '600125229-02',
            '130081601-01',
            '100185628-01',
            '400431026-03',
            '400431486-01',
            '400431130-01',
            '400431112-01',
            '400431094-03',
            '400274216-03',
            '500017492-01',
            '660001750-01',
            '400431031-03',
            '100185987-04',
            '400428445-01',
            '400431076-03',
            '400431084-01',
            '600125250-04',
            '100186313-02',
            '100185066-01',
            '430243200-01',
            '430242822-02',
            '130081645-01',
            '400431089-01',
            '400431255-01',
            '400431163-02',
            '530002670-01',
            '400417552-01',
            '430245083-01',
            '430244307-01',
            '400428201-01',
            '500017438-02',
            '400427792-04',
            '430243965-01',
            '400431654-01',
            '400428754-06',
            '400431627-01',
            '430243835-10',
            '430243954-01',
            '430244003-02',
            '430242854-02',
            '430242854-02',
            '130081446-03',
            '100186116-01',
            '430243992-01',
            '500017434-03',
            '100185352-01',
            '100185794-02',
            '430243963-04',
            '400429648-02',
            '400431993-01',
            '430244298-01',
            '400430829-01',
            '430243963-02',
            '100185785-02',
            '430244642-02',
            '130081578-05',
            '100184817-02',
            '400420751-01',
            '100185799-03',
            '100185791-02',
            '400431938-01',
            '400431122-01',
            '430243976-01',
            '430244060-01',
            '130081575-01',
            '400427792-02',
            '130081307-03',
            '400431088-02',
            '430244449-01',
            '400431449-01',
            '100185796-03',
            '400429040-03',
            '130081292-01',
            '100185423-02',
            '130081444-02',
            '400430915-03',
            '100186408-01',
            '400432020-01',
            '430242770-01',
            '100186401-01',
            '130081273-01',
            '430241981-05',
            '400432192-01',
            '100186232-02',
            '130081578-01',
            '100186290-01',
            '130081525-01',
            '130081590-01',
            '130081370-01',
            '600125417-02',
            '100185791-03',
            '400431102-02',
            '400432078-01',
            '100186246-01',
            '600125326-02',
            '400431098-02',
            '400430768-01',
            '600125325-01',
            '430243999-01',
            '500017438-01',
            '430243608-02',
            '130081002-03',
            '430244711-01',
            '130081016-01',
            '100185794-01',
            '100185756-02',
            '2700000472-02',
            '400432144-05',
            '100185798-03',
            '400430848-07',
            '500017434-02',
            '430243964-01',
            '130081578-03',
            '100186307-01',
            '400431255-02',
            '400427961-04',
            '430243561-01',
            '430243901-01',
            '530002672-03',
            '430243961-02',
            '100185213-01',
            '600125382-01',
            '400431078-01',
            '100186307-03',
            '400431554-02',
            '430242680-01',
            '430243993-01',
            '400368020-06',
            '400431925-01',
            '400430941-03',
            '130081446-02',
            '500017294-01',
            '100185522-02',
            '430242460-01',
            '430244719-01',
            '100185796-02',
            '530002674-01',

        ];

        $orderitemprocess = new \app\admin\model\order\OrderItemProcess();
        $list = $orderitemprocess->where(['item_order_number' => ['in', $item_order_number]])->select();
        $option_params = [];
        foreach ($list as $k => $v) {
            $site = $v['site'];
            $options = [];
            //处方解析 不同站不同字段
            if ($site == 1) {
                $item_data = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->zeelool_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 2) {
                $item_data = Db::connect('database.db_voogueme')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->voogueme_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 3) {
                $item_data = Db::connect('database.db_nihao')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->nihao_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 4) {
                $item_data = Db::connect('database.db_meeloog')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->meeloog_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 5) {
                $item_data = Db::connect('database.db_weseeoptical')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->wesee_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 9) {
                $item_data = Db::connect('database.db_zeelool_es')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->zeelool_es_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 10) {
                $item_data = Db::connect('database.db_zeelool_de')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->zeelool_de_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 11) {
                $item_data = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('product_options', 'item_id');
                $options =  $this->zeelool_jp_prescription_analysis($item_data[$v['item_id']]);
            }
            $option_params[$k]['order_prescription_type'] = $options['order_prescription_type'];
            $option_params[$k]['id'] = $v['id'];
            echo $v['item_id'] . "\n";
            usleep(10000);
        }

        $this->orderitemprocess->saveAll($option_params);
        echo "ok";



        echo "ok";
    }



}
