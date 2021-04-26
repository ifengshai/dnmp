<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell\kafka;

use app\common\controller\Backend;
use think\Db;
use think\Env;
use app\enum\Site;
use app\admin\model\web\WebUsers;
use app\admin\model\web\WebGroup;
use app\admin\model\web\WebVipOrder;
use app\admin\model\web\WebShoppingCart;

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
        while (true) {
            //设置120s为超时
            $message = $consumer->consume(120 * 1000);
            if (!empty($message)) {
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR: //没有错误
                        //拆解对象为数组，并根据业务需求处理数据
                        $payload = json_decode($message->payload, true);
                        $key = $message->key;
                        echo $payload['database'].'-'.$payload['type'].'-'.$payload['table'];
                        //根据kafka中不同key，调用对应方法传递处理数据
                        //对该条message进行处理，比如用户数据同步， 记录日志。
                        if ($payload) {
                            //根据库名判断站点
                            $site = 0;
                            switch ($payload['database']) {
                                case Env::get('site_table.zeelool'):
                                    $site = Site::ZEELOOL;
                                    break;
                                case Env::get('site_table.voogueme'):
                                    $site = Site::VOOGUEME;
                                    break;
                                case Env::get('site_table.nihao'):
                                    $site = Site::NIHAO;
                                    break;
                                case Env::get('site_table.meeloog'):
                                    $site = Site::MEELOOG;
                                    break;
                                case Env::get('site_table.zeelool_es'):
                                    $site = Site::ZEELOOL_ES;
                                    break;
                                case Env::get('site_table.zeelool_de'):
                                    $site = Site::ZEELOOL_DE;
                                    break;
                                case Env::get('site_table.zeelool_jp'):
                                    $site = Site::ZEELOOL_JP;
                                    break;
                                case Env::get('site_table.wesee'):
                                    $site = Site::WESEEOPTICAL;
                                    break;
                                case Env::get('site_table.voogueme_acc'):
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

                            //VIP订单表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'oc_vip_order') {
                                WebVipOrder::setInsertData($payload['data'], $site);
                            }
                            //VIP订单表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'oc_vip_order') {
                                WebVipOrder::setUpdateData($payload['data'], $site);
                            }

                            //购物车表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'sales_flat_quote') {
                                WebShoppingCart::setInsertData($payload['data'], $site);
                            }
                            //购物车表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'sales_flat_quote') {
                                WebShoppingCart::setUpdateData($payload['data'], $site);
                            }

                            //批发站用户表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'users') {
                                WebUsers::setInsertWeseeData($payload['data'], $site);
                            }

                            //批发站用户表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'users') {
                                WebUsers::setUpdateWeseeData($payload['data'], $site);
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

    public function process_list()
    {
        $this->process_data(1);
        $this->process_data(2);
        $this->process_data(3);
        $this->process_data(9);
        $this->process_data(10);
        $this->process_data(11);
        $this->process_data(12);
    }

    /**
     * 处理旧数据
     * @author wpl
     * @date   2021/4/26 15:59
     */
    public function process_data($site)
    {

        if ($site == 1) {
            $entity_id = WebShoppingCart::where(['entity_id' => ['<', 18266819], 'site' => 1])->max('entity_id');
            $res = Db::connect('database.db_zeelool')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        } elseif ($site == 2) {
            $entity_id = WebShoppingCart::where(['entity_id' => ['<', 2048469], 'site' => 2])->max('entity_id');
            $res = Db::connect('database.db_voogueme')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        } elseif ($site == 3) {
            $entity_id = WebShoppingCart::where(['entity_id' => ['<', 2568158], 'site' => 3])->max('entity_id');
            $res = Db::connect('database.db_nihao')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        } elseif ($site == 9) {
            $entity_id = WebShoppingCart::where(['site' => 9])->max('entity_id');
            $res = Db::connect('database.db_zeelool_es')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        } elseif ($site == 10) {
            $entity_id = WebShoppingCart::where(['entity_id' => ['<', 59581], 'site' => 10])->max('entity_id');
            $res = Db::connect('database.db_zeelool_de')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        } elseif ($site == 11) {
            $entity_id = WebShoppingCart::where(['entity_id' => ['<', 30131], 'site' => 11])->max('entity_id');
            $res = Db::connect('database.db_zeelool_jp')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        } elseif ($site == 12) {
            $entity_id = WebShoppingCart::where(['site' => 12])->max('entity_id');
            $res = Db::connect('database.db_voogueme_acc')->table('sales_flat_quote')->where(['entity_id' => ['>', $entity_id]])->limit(1000)->select();
        }
        $res = collection($res)->toArray();
        WebShoppingCart::setInsertData($res, $site);
        echo $site.'--ok'."\n";
    }


}