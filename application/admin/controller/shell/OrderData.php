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
        $this->order = new \app\admin\model\order\Order();
        $this->orderitemoption = new \app\admin\model\order\OrderItemOption();
        $this->orderprocess = new \app\admin\model\order\OrderProcess();
        $this->orderitemprocess = new \app\admin\model\order\OrderItemProcess();
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
        $topic = 'test';
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
                                case 'zeelool_test':
                                    $site = 1;
                                    break;
                                case 'vuetest_voogueme':
                                    $site = 2;
                                    break;
                                case 'nihao_test':
                                    $site = 3;
                                    break;
                                case 'meeloog':
                                    $site = 4;
                                    break;
                                case 'wesee_test':
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

                                dump($payload['data']);
                                $params = [];
                                $order_params = [];
                                foreach ($payload['data'] as $k => $v) {
                                   
                                    $order_ids = $this->order->where('site=' . $site . ' and increment_id=' . $v['increment_id'])->value('id');
                                    $order_ids2 = $this->order->where('site=' . $site . ' and entity_id=' . $v['entity_id'])->value('id');
                                    if ($order_ids) {
                                        $this->order->where('site=' . $site . ' and increment_id=' . $v['increment_id'])->delete();
                                        $this->orderprocess->where('site=' . $site . ' and increment_id=' . $v['increment_id'])->delete();
                                        
                                        //删除子订单表
                                        $this->orderitemoption->where('site=' . $site . ' and order_id=' . $order_ids)->delete();
                                        $this->orderitemprocess->where('site=' . $site . ' and order_id=' . $order_ids)->delete();
                                       
                                    }

                                    if ($order_ids2) {
                                        $this->orderprocess->where('site=' . $site . ' and entity_id=' . $v['entity_id'])->delete();
                                        $this->order->where('site=' . $site . ' and entity_id=' . $v['entity_id'])->delete();
                                        $this->orderitemoption->where('site=' . $site . ' and order_id=' . $order_ids2)->delete();
                                        $this->orderitemprocess->where('site=' . $site . ' and order_id=' . $order_ids2)->delete();
                                    }

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
                                    $params['created_at'] = strtotime($v['created_at']) + 28800;
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
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
                                $params = [];
                                foreach ($payload['data'] as $k => $v) {
                                    $params['base_grand_total'] = $v['base_grand_total'];
                                    $params['total_item_count'] = $v['total_qty_ordered'];
                                    $params['order_type'] = $v['order_type'];
                                    if ($v['status']) {
                                        $params['status'] = $v['status'];
                                    }

                                    $params['base_currency_code'] = $v['base_currency_code'];
                                    $params['shipping_method'] = $v['shipping_method'];
                                    $params['shipping_title'] = $v['shipping_description'];
                                    $params['customer_email'] = $v['customer_email'];
                                    $params['customer_firstname'] = $v['customer_firstname'];
                                    $params['customer_lastname'] = $v['customer_lastname'];
                                    $params['taxno'] = $v['taxno'];
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                   
                                    $this->order->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);
                                }
                            }

                            //地址表插入时或更新时更新主表地址
                            if (($payload['type'] == 'UPDATE' || $payload['type'] == 'INSERT') && $payload['table'] == 'sales_flat_order_address') {
                                $params = [];
                                foreach ($payload['data'] as $k => $v) {
                                    $params['country_id'] = $v['country_id'];
                                    $params['region'] = $v['region'];
                                    $params['city'] = $v['city'];
                                    $params['street'] = $v['street'];
                                    $params['postcode'] = $v['postcode'];
                                    $params['telephone'] = $v['telephone'];
                                    $params['updated_at'] = strtotime($v['updated_at'])+ 28800;
                                    $this->order->where(['entity_id' => $v['parent_id'], 'site' => $site])->update($params);
                                }
                            }


                            //新增子表
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'sales_flat_order_item') {

                                $options = [];
                                foreach ($payload['data'] as $k => $v) {
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

                            //新增子表
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'sales_flat_order_item') {

                                $options = [];
                                foreach ($payload['data'] as $k => $v) {
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
                                    unset($options['order_prescription_type']);
                                    if ($options) {
                                        $this->orderitemoption->where(['item_id' => $v['item_id'], 'site' => $site])->update($options);
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
                        // var_dump("##################");
                        break;
                    default:
                        // var_dump("nothing");
                        throw new \Exception($message->errstr(), $message->err);
                        break;
                }
            } else {
                // var_dump('this is empty obj!!!');
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
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['lens_data_name'] ?: '';
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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;

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
        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;

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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;
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
        if ($options['info_buyRequest']['tmplens']['degrees']) {
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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;

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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;

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

        //镜片编码
        $arr['lens_number'] = $options['info_buyRequest']['tmplens']['lens_number'] ?? 0;

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

        //判断加工类型
        $result = $this->set_processing_type($arr);
        $arr = array_merge($arr, $result);
        return $arr;
    }

    /**
     * 判断定制现片逻辑
     */
    protected function set_processing_type($params = [])
    {
        /**
         * 判断定制现片逻辑
         * 1、渐进镜 Progressive
         * 2、偏光镜 镜片类型包含Polarized
         * 3、染色镜 镜片类型包含Lens with Color Tint 或 Tinted 或 Color Tint
         * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
         */
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
            if ((((float) urldecode($arr['od_sph']) >= -8 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -8 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -6 && (float) urldecode($arr['od_cyl']) <= -4.25) || ((float) urldecode($arr['os_cyl']) >= -6 && (float) urldecode($arr['os_cyl']) <= -4.25))) {
                $arr['is_custom_lens'] = 1;
                $arr['order_prescription_type'] = 3;
            }
        }

        if ($params['lens_number'] == '24100000' || $params['lens_number'] == '24200000') {
            /**
             * 1.67非球面绿膜 现片
             * SPH:-3.00～-12.00 CYL:0.00～-2.00（不含-0.25）
             */
            if ((((float) urldecode($arr['od_sph']) >= -12 && (float) urldecode($arr['od_sph']) <= -3) || ((float) urldecode($arr['os_sph']) >= -12 && (float) urldecode($arr['os_sph']) <= -3)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) <= 0 && (float) urldecode($arr['od_cyl']) != -0.25))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -15 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -15 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -13 && (float) urldecode($arr['od_sph']) <= -3) || ((float) urldecode($arr['os_sph']) >= -13 && (float) urldecode($arr['os_sph']) <= -3)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -8 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -8 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -4 && (float) urldecode($arr['od_cyl']) < 0) || ((float) urldecode($arr['os_cyl']) >= -4 && (float) urldecode($arr['os_cyl']) < 0))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -12 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -12 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) < 0 && (float) urldecode($arr['od_cyl']) != -0.25))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -3 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -3 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) < 0) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) < 0))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -8 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -8 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) < 0) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) < 0))) {
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
            if ((((float) urldecode($arr['od_sph']) >= -8 && (float) urldecode($arr['od_sph']) < 0) || ((float) urldecode($arr['os_sph']) >= -8 && (float) urldecode($arr['os_sph']) < 0)) && (((float) urldecode($arr['od_cyl']) >= -2 && (float) urldecode($arr['od_cyl']) <= 0.5) || ((float) urldecode($arr['os_cyl']) >= -2 && (float) urldecode($arr['os_cyl']) <= 0.5))) {
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
            if (!$res) {
                continue;
            }
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
            if (!$order_id) {
                continue;
            }
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
        $list = $this->order->where('total_qty_ordered=0')->limit(3000)->select();
        $list = collection($list)->toArray();
        $params = [];
        foreach ($list as $k => $v) {
            $qty = $this->orderitemoption->where(['magento_order_id' => $v['entity_id'],'site' => $v['site']])->sum('qty');
            $params[$k]['total_qty_ordered'] = $qty;
            $params[$k]['id'] = $v['id'];
            echo $k . "\n";
        }
        $this->order->saveAll($params);
        echo 'ok';
    }
}
