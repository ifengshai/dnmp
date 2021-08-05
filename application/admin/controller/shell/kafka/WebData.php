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
 * @author  wpl
 * @date    2021/4/14 17:27
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
                        echo $payload['database'] . '-' . $payload['type'] . '-' . $payload['table'];
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

                            //VIP订单表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'vip_orders') {
                                WebVipOrder::setInsertData($payload['data'], $site);
                            }
                            //VIP订单表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'vip_orders') {
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

                            //购物车表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'carts') {
                                WebShoppingCart::setInsertData($payload['data'], $site);
                            }
                            //购物车表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'carts') {
                                WebShoppingCart::setUpdateData($payload['data'], $site);
                            }

                            //批发站用户表添加
                            if ($payload['type'] == 'INSERT' && $payload['table'] == 'users') {
                                WebUsers::setInsertData($payload['data'], $site);
                            }

                            //批发站用户表更新
                            if ($payload['type'] == 'UPDATE' && $payload['table'] == 'users') {
                                WebUsers::setUpdateData($payload['data'], $site);
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
        echo "ok";
    }

    public function process_list_v()
    {

        $this->process_data(2);

        echo "ok";
    }

    public function process_list_n()
    {

        $this->process_data(3);
        echo "ok";
    }

    public function process_list_de()
    {
        $this->process_data(1);
        $this->process_data(3);
        $this->process_data(9);
        $this->process_data(10);
        $this->process_data(11);
        $this->process_data(12);
        echo "ok";
    }


    /**
     * 处理购物车旧数据
     * @author wpl
     * @date   2021/4/26 15:59
     */
    public function process_data($site)
    {
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool');
        } elseif ($site == 2) {
            $res = Db::connect('database.db_voogueme');
        } elseif ($site == 3) {
            $res = Db::connect('database.db_nihao');
        } elseif ($site == 9) {
            $res = Db::connect('database.db_zeelool_es');
        } elseif ($site == 10) {
            $res = Db::connect('database.db_zeelool_de');
        } elseif ($site == 11) {
            $res = Db::connect('database.db_zeelool_jp');
        } elseif ($site == 12) {
            $res = Db::connect('database.db_voogueme_acc');
        }

        $starttime = '2021-06-19 10:00:00';
        $endtime = '2021-06-19 23:00:00';
        $list = $res->table('sales_flat_quote')
            ->where(['created_at' => ['between', [$starttime, $endtime]]])
            ->field('entity_id,store_id,is_active,items_count,items_qty,base_currency_code,quote_currency_code,grand_total,base_grand_total,customer_email,customer_id,updated_at,created_at')
            ->chunk(10000, function ($carts) use ($site) {
                $carts = collection($carts)->toArray();
                $params = [];
                foreach ($carts as $key => $v) {

                    $count = Db::name('web_shopping_cart')->where(['entity_id' => $v['entity_id'], 'site' => $site])->count();
                    if ($count > 0) {
                        continue;
                    }

                    $params[$key]['entity_id'] = $v['entity_id'];
                    $params[$key]['store_id'] = $v['store_id'] ?: 0;
                    $params[$key]['is_active'] = $v['is_active'] ?: 0;
                    $params[$key]['site'] = $site;
                    $params[$key]['items_count'] = $v['items_count'] ?: 0;
                    $params[$key]['items_qty'] = $v['items_qty'] ?: 0;
                    $params[$key]['base_currency_code'] = $v['base_currency_code'] ?: 0;
                    $params[$key]['quote_currency_code'] = $v['quote_currency_code'] ?: 0;
                    $params[$key]['grand_total'] = $v['grand_total'] ?: 0;
                    $params[$key]['base_grand_total'] = $v['base_grand_total'] ?: 0;
                    $params[$key]['customer_id'] = $v['customer_id'] ?: 0;
                    $params[$key]['customer_email'] = $v['customer_email'] ?: '';
                    $params[$key]['created_at'] = strtotime($v['created_at']) ?: 0;
                    $params[$key]['updated_at'] = strtotime($v['updated_at']) ?: 0;
                    echo $v['entity_id'] . PHP_EOL;
                }
                Db::name('web_shopping_cart')->insertAll(array_values($params));
            });
    }

}