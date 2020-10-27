<?php

/**
 * 订单数据解析
 * 执行时间：
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
        $this->orderitem = new \app\admin\model\order\OrderItem();
        $this->orderoptions = new \app\admin\model\order\OrderOptions();
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
        $topic = 'order_data';
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

        //指定topic分配partitions使用那个分区
        // $consumer->assign([
        //     new \RdKafka\TopicPartition("zzy8", 0),
        //     new \RdKafka\TopicPartition("zzy8", 1),
        // ]);

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
                                    $model = $this->zeelool;
                                    break;
                                case 'voogueme':
                                    $model = $this->voogueme;
                                    break;
                                case 'nihao':
                                    $model = $this->nihao;
                                    break;
                                case 'meeloog':
                                    $model = $this->meeloog;
                                    break;
                                case 'wesee':
                                    $model = $this->wesee;
                                    break;
                                case 'zeelool_es':
                                    $model = $this->zeelool_es;
                                    break;
                                case 'zeelool_de':
                                    $model = $this->zeelool_de;
                                    break;
                                case 'zeelool_jp':
                                    $model = $this->zeelool_jp;
                                    break;
                            }

                            if ($payload['type'] == 'INSERT') {
                                
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
     * 处理订单处方数据
     *
     * @Description
     * @author wpl
     * @since 2020/10/21 14:55:50 
     * @return void
     */
    public function process_order_options_data()
    {
        //查询订单表最大id
        $id = $this->orderoptions->max('item_id');

        $list = Db::connect('database.db_zeelool')
            ->table('sales_flat_order_item')
            ->alias('a')
            ->field('a.*,b.increment_id as order_number')
            ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
            ->where(['item_id' => ['>', $id]])
            ->limit(200)
            ->select();
        $arr = []; //处方表数据
        $data = []; //子订单表数据
        foreach ($list as $k => $v) {
            $arr['item_id'] = $v['item_id'];
            $arr['order_id'] = $v['order_id'];
            $arr['sku'] = $v['sku'];
            $arr['qty'] = $v['qty_ordered'];
            $arr['base_row_total'] = $v['base_row_total'];
            $options = unserialize($v['product_options']);
            $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
            $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_name'] ?: '';
            $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
            $options_params = array();
            foreach ($prescription_params as $key => $value) {
                $arr_value = explode("=", $value);
                $options_params[$arr_value[0]] = $arr_value[1];
            }
            $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
            $arr['coatiing_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
            $arr['coatiing_price'] = $options['info_buyRequest']['tmplens']['coatiing_price'];
            $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
            $arr['index_price'] = $options['info_buyRequest']['tmplens']['index_price'];
            $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
            $arr['is_special_price'] = $options['info_buyRequest']['tmplens']['is_special_price'] ?? 0;
            $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
            $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
            $arr['od_sph'] = $options_params['od_sph'] ?: '';;
            $arr['os_sph'] = $options_params['os_sph'] ?: '';;
            $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
            $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
            $arr['od_axis'] = $options_params['od_axis'];
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
            $arr['is_prescription'] = 0;
            $arr['is_custom_lens'] = 0;

            $options_id = $this->orderoptions->insertGetId($arr);
            for ($i = 0; $i < $v['qty_ordered']; $i++) {
                $data[$i]['item_id'] = $v['item_id'];
                $data[$i]['order_id'] = $v['order_id'];
                $data[$i]['option_id'] = $options_id;
                $str = '';
                if ($i < 10) {
                    $str = '0' . $i + 1;
                } else {
                    $str = $i + 1;
                }
                $data[$i]['item_order_number'] = $v['order_number'] . '-' . $str;
                $data[$i]['sku'] = $v['sku'];
                $data[$i]['created_at'] = strtotime($v['created_at']);
                $data[$i]['updated_at'] = strtotime($v['updated_at']);
            }
            $this->orderitem->insertAll($data);


            echo $k . "\n";
        }
        echo 'ok';
        die;
    }
}
