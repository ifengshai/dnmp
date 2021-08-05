<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\admin\controller\elasticsearch\async\AsyncOrder;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\WaveOrder;
use app\admin\model\warehouse\StockSku;
use app\common\controller\Backend;
use app\enum\Site;
use think\Db;
use app\admin\model\lens\LensPrice;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Env;
use think\exception\DbException;
use think\Log;
use think\Model;

class OrderData extends Backend
{
    protected $noNeedLogin = ['*'];
    /**
     * @var bool|mixed|string|null
     * @author wpl
     * @date   2021/5/6 14:17
     */
    private $topicName;
    /**
     * @var bool|mixed|string|null
     * @author wpl
     * @date   2021/5/6 14:17
     */
    private $topicIp;
    /**
     * @var
     * @author wpl
     * @date   2021/5/14 14:19
     */
    private $orderitemprocess;

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
        $this->asyncOrder = new AsyncOrder();
        $this->topicName = Env::get('topic.orderTopicName');
        $this->topicIp = Env::get('topic.topicIp');
        $this->asyncOrder = new AsyncOrder();
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
        $order_lens_type = [];
        while (true) {
            //设置120s为超时
            $message = $consumer->consume(120 * 1000);
            if (!empty($message)) {
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR: //没有错误
                        //拆解对象为数组，并根据业务需求处理数据
                        $payload = json_decode($message->payload, true);
                        //对该条message进行处理，比如用户数据同步， 记录日志
                        echo $payload['database'] . '-' . $payload['type'] . '-' . $payload['table'];

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
                                case Env::get('site_table.zeelool_fr'):
                                    $site = Site::ZEELOOL_FR;
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
                                    $params['grand_total'] = $v['grand_total'];
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
                                    $params['taxno'] = $v['cpf'];
                                    $params['base_to_order_rate'] = $v['base_to_order_rate'];
                                    $params['mw_rewardpoint'] = $v['mw_rewardpoint'];
                                    $params['mw_rewardpoint_discount'] = $v['mw_rewardpoint_discount'];
                                    $params['base_shipping_amount'] = $v['base_shipping_amount'];
                                    $params['base_discount_amount'] = $v['base_discount_amount'];
                                    $params['customer_id'] = $v['customer_id'] ?: 0;
                                    $params['quote_id'] = $v['quote_id'];
                                    $params['created_at'] = strtotime($v['created_at']) + 28800;
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    $params['coupon_code'] = $v['coupon_code'];
                                    $params['coupon_rule_name'] = $v['coupon_rule_name'];
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }

                                    if ($site == Site::ZEELOOL_DE || $site == Site::ZEELOOL_FR) {
                                        $params['pay_method'] = $v['pay_method'];
                                    }

                                    //插入订单主表
                                    $order_id = $this->order->insertGetId($params);
                                    //es同步订单数据，插入
                                    $this->asyncOrder->runInsert($params, $order_id);
                                    $order_params[$k]['site'] = $site;
                                    $order_params[$k]['order_id'] = $order_id;
                                    $order_params[$k]['entity_id'] = $v['entity_id'];
                                    $order_params[$k]['increment_id'] = $v['increment_id'];

                                    $order_lens_type[$site] = [];
                                }
                                //插入订单处理表
                                $this->orderprocess->saveAll($order_params);
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
                                    $params['base_discount_amount'] = $v['base_discounts_price'];
                                    $params['customer_id'] = $v['user_id'] ?: 0;
                                    $params['payment_method'] = $v['payment_type'];
                                    $params['created_at'] = strtotime($v['created_at']) + 28800;
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    $params['last_trans_id'] = $v['payment_order_no'];
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }

                                    //插入订单主表
                                    $order_id = $this->order->insertGetId($params);
                                    //es同步订单数据，插入
                                    $this->asyncOrder->runInsert($params, $order_id);
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
                                    $params['base_discount_amount'] = $v['base_discounts_price'];
                                    $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                                    $params['last_trans_id'] = $v['payment_order_no'];
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }
                                    $this->order->where(['entity_id' => $v['id'], 'site' => $site])->update($params);
                                    //es同步订单数据，插入
                                    $this->asyncOrder->runUpdate($v['id'], $site);
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
                                        //es同步订单数据，插入
                                        $this->asyncOrder->runUpdate($v['order_id'], $site);
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
                                    $params['quote_id'] = $v['quote_id'];
                                    $params['base_discount_amount'] = $v['base_discount_amount'];
                                    $params['customer_id'] = $v['customer_id'] ?: 0;
                                    $params['coupon_code'] = $v['coupon_code'];
                                    $params['coupon_rule_name'] = $v['coupon_rule_name'];
                                    if (isset($v['payment_time'])) {
                                        $params['payment_time'] = strtotime($v['payment_time']) + 28800;
                                    }

                                    if ($site == Site::ZEELOOL_DE || $site == Site::ZEELOOL_FR) {
                                        $params['pay_method'] = $v['pay_method'];
                                    }

                                    $this->order->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);
                                    //es同步订单数据，插入
                                    $this->asyncOrder->runUpdate($v['entity_id'], $site);
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
                                        //es同步订单数据，插入
                                        $this->asyncOrder->runUpdate($v['parent_id'], $site);
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
                                    //es同步订单数据，插入
                                    $this->asyncOrder->runUpdate($v['parent_id'], $site);
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
                                    $options['base_original_price'] = round($v['base_goods_price'] * $v['goods_count'], 4);
                                    $options['base_discount_amount'] = $v['base_goods_discounts_price'];
                                    $options['single_base_original_price'] = $v['base_goods_price'];
                                    $options['single_base_discount_amount'] = round($v['base_goods_discounts_price'] / $v['goods_count'], 4);
                                    $options['prescription_type'] = $orders_prescriptions_params[$v['orders_prescriptions_id']]['name'];
                                    unset($orders_prescriptions_params[$v['orders_prescriptions_id']]);
                                    $order_prescription_type = $options['order_prescription_type'];
                                    unset($options['order_prescription_type']);
                                    unset($options['is_prescription_abnormal']);
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

                                    $options['base_original_price'] = round($v['base_goods_price'] * $v['goods_count'], 4);
                                    $options['base_discount_amount'] = $v['base_goods_discounts_price'];
                                    $options['single_base_original_price'] = $v['base_goods_price'];
                                    $options['single_base_discount_amount'] = round($v['base_goods_discounts_price'] / $v['goods_count'], 4);

                                    $options['prescription_type'] = $orders_prescriptions_params[$v['orders_prescriptions_id']]['name'];
                                    unset($orders_prescriptions_params[$v['orders_prescriptions_id']]);
                                    $order_prescription_type = $options['order_prescription_type'];
                                    unset($options['order_prescription_type']);
                                    unset($options['is_prescription_abnormal']);
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
                                    } elseif ($site == 9) {
                                        $options = $this->zeelool_es_prescription_analysis($v['product_options']);
                                    } elseif ($site == 10) {
                                        $options = $this->zeelool_de_prescription_analysis($v['product_options']);
                                    } elseif ($site == 11) {
                                        $options = $this->zeelool_jp_prescription_analysis($v['product_options']);
                                    } elseif ($site == 12) {
                                        $options = $this->voogueme_acc_prescription_analysis($v['product_options']);
                                    } elseif ($site == 15) {
                                        $options = $this->zeelool_fr_prescription_analysis($v['product_options']);
                                    }

                                    $options['item_id'] = $v['item_id'];
                                    $options['site'] = $site;
                                    $options['magento_order_id'] = $v['order_id'];
                                    $options['sku'] = $v['sku'];
                                    $options['name'] = $v['name'] ?? '';
                                    $options['qty'] = $v['qty_ordered'];
                                    $options['base_row_total'] = $v['base_row_total'];
                                    $options['product_id'] = $v['product_id'];
                                    $options['base_original_price'] = round($v['base_original_price'] * $v['qty_ordered'], 4);
                                    $options['base_discount_amount'] = $v['base_discount_amount'];
                                    $options['single_base_original_price'] = $v['base_original_price'];
                                    $options['single_base_discount_amount'] = round($v['base_discount_amount'] / $v['qty_ordered'], 4);
                                    $order_prescription_type = $options['order_prescription_type'];
                                    $is_prescription_abnormal = $options['is_prescription_abnormal'] ?: 0;
                                    unset($options['order_prescription_type']);
                                    unset($options['is_prescription_abnormal']);
                                    if ($options) {
                                        $options_id = $this->orderitemoption->insertGetId($options);
                                        $data = []; //子订单表数据
                                        for ($i = 0; $i < $v['qty_ordered']; $i++) {
                                            $data[$i]['item_id'] = $v['item_id'];
                                            $data[$i]['magento_order_id'] = $v['order_id'];
                                            $data[$i]['site'] = $site;
                                            $data[$i]['option_id'] = $options_id;
                                            $data[$i]['sku'] = $v['sku'];
                                            $data[$i]['order_prescription_type'] = $order_prescription_type ?: '';
                                            $data[$i]['is_prescription_abnormal'] = $is_prescription_abnormal;
                                            $data[$i]['created_at'] = strtotime($v['created_at']) + 28800;
                                            $data[$i]['updated_at'] = strtotime($v['updated_at']) + 28800;
                                        }

                                        $this->orderitemprocess->insertAll($data);

                                        //判断如果子订单处方是否为定制片 子订单有定制片则主单为定制
                                        if (($order_prescription_type == 3 || $order_lens_type[$site][$v['order_id']] == 3) && in_array($site, [1, 3])) {
                                            $order_lens_type[$site][$v['order_id']] = 3;
                                            $this->order->where(['entity_id' => $v['order_id'], 'site' => $site])->update(['is_custom_lens' => 1, 'stock_id' => 2]);
                                            $this->orderitemprocess->where(['magento_order_id' => $v['order_id'], 'site' => $site])->update(['stock_id' => 2]);
                                        }
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
                                    } elseif ($site == 15) {
                                        $options = $this->zeelool_fr_prescription_analysis($v['product_options']);
                                    }

                                    $options['sku'] = $v['sku'];
                                    $options['qty'] = $v['qty_ordered'];
                                    $options['base_row_total'] = $v['base_row_total'];
                                    $options['base_original_price'] = round($v['base_original_price'] * $v['qty_ordered'], 4);
                                    $options['base_discount_amount'] = $v['base_discount_amount'];
                                    $options['single_base_original_price'] = $v['base_original_price'];
                                    $options['single_base_discount_amount'] = round($v['base_discount_amount'] / $v['qty_ordered'], 4);
                                    $order_prescription_type = $options['order_prescription_type'] ?: '';
                                    $is_prescription_abnormal = $options['is_prescription_abnormal'] ?: 0;
                                    unset($options['order_prescription_type']);
                                    unset($options['is_prescription_abnormal']);

                                    if ($options) {
                                        $this->orderitemoption->where(['item_id' => $v['item_id'], 'site' => $site])->update($options);

                                        $this->orderitemprocess->where(['item_id' => $v['item_id'], 'site' => $site])->update([
                                            'order_prescription_type'  => $order_prescription_type,
                                            'sku'                      => $options['sku'],
                                            'is_prescription_abnormal' => $is_prescription_abnormal,
                                        ]);

                                        //判断如果子订单处方是否为定制片 子订单有定制片则主单为定制
                                        if ($order_prescription_type == 3 && in_array($site, [1, 3])) {
                                            $this->order->where(['entity_id' => $v['order_id'], 'site' => $site])->update(['is_custom_lens' => 1, 'stock_id' => 2]);
                                            $this->orderitemprocess->where(['magento_order_id' => $v['order_id'], 'site' => $site])->update(['stock_id' => 2]);
                                        }
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
     * Wesee 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return void
     */
    protected function wesee_prescription_analysis($data)
    {
        $options = json_decode($data, true);
        //镜片类型
        $arr['index_type'] = $options['lens_name'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['lens_name'] ?: '';
        $options_params = $options['prescription'];
        // //处方类型
        // $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
        //镀膜名称
        $arr['coating_name'] = $options['coatiing_name'] ?: '';
        //镀膜价格
        $arr['coating_price'] = $options['coatiing_base_price'];
        //镜框价格
        $arr['frame_price'] = $options['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['lens_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['color_name'];

        //镜片+镀膜价格
        $arr['lens_price'] = $options['lens_price'] ?? 0;
        //镜框+镜片+镀膜价格
        $arr['total'] = $options['lens_price'] ?? 0;

        $arr['index_id'] = $options['lens_id'];
        //镜片编码
        $arr['lens_number'] = $options['lens_number'] ?? 0;
        $arr['web_lens_name'] = $options['lens_name'] ?: '';

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
        // if ($options['degrees'] && !$arr['index_type']) {
        //     $arr['od_sph'] = $options['degrees'];
        //     $arr['os_sph'] = $options['degrees'];
        //     $arr['index_type'] = '1.61 Index Standard  Reading Glasses - Non Prescription';
        //     $arr['index_name'] = '1.61 Index Standard  Reading Glasses - Non Prescription';
        // }

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
     * 批发站 匹配到s，r结尾的sku
     *
     * @param $sku
     *
     * @date  2020/12/23 18:01
     */
    protected function getTrueSku($sku)
    {
        $sku = trim($sku);
        $temp_arr = explode('-', $sku);
        if (count($temp_arr) >= 2) {
            $first = rtrim($temp_arr[0], 'S');
            $first = rtrim($first, 'R');
            $second = $temp_arr[1];
            $sku = $first . '-' . $second;
        }

        return $sku;
    }

    /**
     * Zeelool 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return array
     */
    protected function zeelool_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['lenstype_data_name'] ?: '';

        $arr['index_type_price'] = $options['info_buyRequest']['tmplens']['lenstype_price'] ?: '';
        //镜片名称
        $index_name = $options['info_buyRequest']['tmplens']['lens_data_name'] ?: $options['info_buyRequest']['tmplens']['index_type'];
        $arr['index_name'] = $index_name ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = [];
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
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
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
     * @return array
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
        $options_params = [];
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
     * @return array
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
        $arr['coating_price'] = $options['info_buyRequest']['tmplens']['four_price'];
        //镜框价格
        $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
        //镜片价格
        $arr['index_price'] = $options['info_buyRequest']['tmplens']['third_price'];
        //镜框原始价格
        $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
        //镜片颜色
        $arr['index_color'] = $options['info_buyRequest']['tmplens']['color_name'];
        //镜框颜色
        $arr['frame_color'] = $options['options'][0]['value'];
        //镜片+镀膜价格
        $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens_price'] ?? 0;
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
     * @return array
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
        $options_params = [];
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
     * 西语站 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return array
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
        $options_params = [];
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
     * @return array
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
        $options_params = [];
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
     * @return array
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
        $options_params = [];
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
        $arr['combo'] = $options['info_buyRequest']['tmplens']['combo'] ?? 0;

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
        $arr['is_prescription_abnormal'] = $arr['combo'] == 1 ? 1 : $arr['is_prescription_abnormal'];

        return $arr;
    }


    /**
     * 饰品站 处方解析逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/10/28 10:16:53 
     * @return array
     */
    protected function voogueme_acc_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['ring_size'] = $options['info_buyRequest']['tmplens']['ring_size'] ?: '';
        //证书
        $arr['gra_certificate'] = $options['info_buyRequest']['tmplens']['gra_certificate'] ?: '';

        $arr['type'] = $options['info_buyRequest']['tmplens']['type'] ?: '';

        $arr['stone_shape'] = $options['info_buyRequest']['tmplens']['stone_shape'] ?: '';

        $arr['stone_type'] = $options['info_buyRequest']['tmplens']['stone_type'] ?: '';

        $arr['carat_weight'] = $options['info_buyRequest']['tmplens']['carat_weight'] ?: '';

        $arr['metal'] = $options['info_buyRequest']['tmplens']['metal'] ?: '';

        $arr['plating'] = $options['info_buyRequest']['tmplens']['plating'] ?: '';


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
    protected function zeelool_fr_prescription_analysis($data)
    {
        $options = unserialize($data);
        //镜片类型
        $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //镜片名称
        $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
        //光度等参数
        $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
        $options_params = [];
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
     *
     * @param array $params
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author wpl
     * @date   2021/5/19 10:18
     */
    public function set_processing_type($params = [])
    {
        $arr = [];
//        //判断处方是否异常
//        $list = $this->is_prescription_abnormal($params);
//        $arr = array_merge($arr, $list);
        $arr['is_prescription_abnormal'] = 0;
        $arr['order_prescription_type'] = 0;

        //斜视值大于1 默认为定制片
        if ($params['od_pv'] >= 1 || $params['os_pv'] >= 1 || $params['od_pv_r'] >= 1 || $params['os_pv_r'] >= 1) {
            $arr['order_prescription_type'] = 3;

            return $arr;
        }

        //仅镜框
        if ($params['lens_number'] == '10000000' || !$params['lens_number']) {
            $arr['order_prescription_type'] = 1;
        } else {
            $od_sph = (float)urldecode($params['od_sph']);
            $os_sph = (float)urldecode($params['os_sph']);
            $od_cyl = (float)urldecode($params['od_cyl']);
            $os_cyl = (float)urldecode($params['os_cyl']);
            //判断是否为现片，其余为定制
            $lensData = LensPrice::where(['lens_number' => $params['lens_number'], 'type' => 1])->select();
            $tempArr = [];
            foreach ($lensData as $v) {

                if (!$v['sph_start']) {
                    $v['sph_start'] = $v['sph_end'];
                }

                if (!$v['cyl_start']) {
                    $v['cyl_start'] = $v['cyl_end'];
                }

                $v['sph_start'] = (float)$v['sph_start'];
                $v['sph_end'] = (float)$v['sph_end'];
                $v['cyl_start'] = (float)$v['cyl_start'];
                $v['cyl_end'] = (float)$v['cyl_end'];

                if ($od_sph >= $v['sph_start'] && $od_sph <= $v['sph_end'] && $od_cyl >= $v['cyl_start'] && $od_cyl <= $v['cyl_end']) {
                    $tempArr['od'] = 1;
                }
                if ($os_sph >= $v['sph_start'] && $os_sph <= $v['sph_end'] && $os_cyl >= $v['cyl_start'] && $os_cyl <= $v['cyl_end']) {
                    $tempArr['os'] = 1;
                }
            }

            if ($tempArr['od'] == 1 && $tempArr['os'] == 1) {
                $arr['order_prescription_type'] = 2;
            }
        }

        //默认如果不是仅镜架 或定制片 则为现货处方镜
        if ($arr['order_prescription_type'] != 1 && $arr['order_prescription_type'] != 2) {
            $arr['order_prescription_type'] = 3;
        }

        return $arr;
    }

    /**
     * 判断处方是否异常
     *
     * @param array $params
     *
     * @author wpl
     * @date   2021/4/23 9:31
     */
    protected function is_prescription_abnormal(array $params = []): array
    {
        $list = [];
        $od_sph = (float)urldecode($params['od_sph']);
        $os_sph = (float)urldecode($params['os_sph']);
        $od_cyl = (float)urldecode($params['od_cyl']);
        $os_cyl = (float)urldecode($params['os_cyl']);
        //截取镜片编码第一位
        $str = substr($params['lens_number'], 0, 1);

        /**
         * 判断处方是否异常规则
         * 1、SPH值或CYL值的“+”“_”号不一致
         * 2、左右的SPH或CYL 绝对值相差超过3
         * 3、有SPH或CYL无PD
         * 4、有PD无SPH及CYL
         */
        if (($od_sph < 0 && $os_sph > 0) || ($od_sph > 0 && $os_sph < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        if ($od_sph == 0 && ($os_sph > 0 || $os_sph < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        if ($os_sph == 0 && ($od_sph > 0 || $od_sph < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        if (($os_cyl < 0 && $od_cyl > 0) || ($os_cyl > 0 && $od_cyl < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        //绝对值相差超过3
        $odDifference = abs($od_sph) - abs($os_sph);
        $osDifference = abs($od_cyl) - abs($os_cyl);
        if (abs($odDifference) > 3 || abs($osDifference) > 3) {
            $list['is_prescription_abnormal'] = 1;
        }

        //有PD无SPH和CYL
        if (($params['pdcheck'] == 'on' || $params['pd'] > 0) && (!$od_sph && !$os_sph && !$od_cyl && !$os_cyl && $str == '2')) {
            $list['is_prescription_abnormal'] = 1;
        }

        //有SPH或CYL无PD
        if (($params['pdcheck'] != 'on' && $params['pd'] <= 0) && ($od_sph || $os_sph || $od_cyl || $os_cyl) && $str == '3') {
            $list['is_prescription_abnormal'] = 1;
        }

        $list['is_prescription_abnormal'] = $list['is_prescription_abnormal'] == 1 ?: 0;

        return $list;
    }

    /**
     * 批量生成子订单表子单号
     *
     * @Description
     * @return void
     * @todo      计划任务 10分钟一次
     * @author wpl
     * @since 2020/10/28 17:36:27 
     */
    public function set_order_item_number_shell()
    {
        //查询未生成子单号的数据
        $list = $this->orderitemprocess->where("item_order_number=''")->limit(3000)->select();
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
                $item_params[$key]['order_id'] = $res->id ?: 0;
            }
            //更新数据
            if ($item_params) {
                $this->orderitemprocess->saveAll($item_params);
            }

            echo $v['id'] . "\n";
            usleep(10000);
        }

        echo "ok";
    }

    /**
     * 批量更新order表主键
     *
     * @Description
     * @return void
     * @todo      计划任务 10分钟一次
     * @author wpl
     * @since 2020/10/28 17:58:46 
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
            $params[$k]['order_id'] = $order_id ?: 0;
            echo $v['id'] . "\n";
        }
        //更新数据
        if ($params) {
            $this->orderitemoption->saveAll($params);
        }
        echo "ok";
    }


    #######################################生成波次单################################################

    /**
     * 创建波次单
     *
     * @Description
     * @author wpl
     * @since 2021/03/23 17:47:29 
     * @return void
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function create_wave_order()
    {
        $this->setOrderPrescriptionType();
        /**
         *
         * 生成规则
         * 1）按业务模式：品牌独立站、第三方平台店铺
         * 2）按时间段
         * 第一波次：00:00-2:59:59
         * 第二波次：3：00-5:59:59
         * 第三波次：6:00-8:59:59
         * 第四波次：9:00-11:59:59
         * 第五波次：12:00-14:59:59
         * 第六波次：15:00-17:59:59
         * 第七波次：18:00-20:59:59
         * 第八波次：21:00-23:59:59
         *
         */
        $where['b.is_print'] = 0;
        $where['b.wave_order_id'] = 0;
        $where['b.is_prescription_abnormal'] = 0;
        $where['a.status'] = ['in', ['processing']];
        $list = $this->order->where($where)->alias('a')->field('b.id,b.sku,a.created_at,a.updated_at,entity_id,a.site,a.is_custom_lens,a.stock_id')
            ->join(['fa_order_item_process' => 'b'], 'a.entity_id=b.magento_order_id and a.site=b.site')
            ->order('id desc')
            ->select();
        $list = collection($list)->toArray();
        //第三方站点id
        $third_site = [13, 14];
        $waveOrder = new WaveOrder();
        $itemPlatform = new ItemPlatformSku();
        foreach ($list as $k => $v) {
            //判断波次类型
            if (in_array($v['site'], $third_site)) {
                $type = 2;
            } else {
                $type = 1;
            }
            $time = $v['updated_at'] > 28800 ? $v['updated_at'] : $v['created_at'];
            //判断波次时间段
            $wave_time_type = 0;
            if (strtotime(date('Y-m-d 00:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 02:59:59', $time))) {
                $wave_time_type = 1;
            } elseif (strtotime(date('Y-m-d 03:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 05:59:59', $time))) {
                $wave_time_type = 2;
            } elseif (strtotime(date('Y-m-d 06:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 08:59:59', $time))) {
                $wave_time_type = 3;
            } elseif (strtotime(date('Y-m-d 09:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 11:59:59', $time))) {
                $wave_time_type = 4;
            } elseif (strtotime(date('Y-m-d 12:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 14:59:59', $time))) {
                $wave_time_type = 5;
            } elseif (strtotime(date('Y-m-d 15:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 17:59:59', $time))) {
                $wave_time_type = 6;
            } elseif (strtotime(date('Y-m-d 18:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 20:59:59', $time))) {
                $wave_time_type = 7;
            } elseif (strtotime(date('Y-m-d 21:00:00', $time)) <= $time and $time <= strtotime(date('Y-m-d 23:59:59', $time))) {
                $wave_time_type = 8;
            }

            if ($v['stock_id'] == 2) {
                $stockId = 2; //丹阳仓
            } else {
                $stockId = 1; //郑州仓
            }
            $id = $waveOrder
                ->where([
                    'type'           => $type,
                    'wave_time_type' => $wave_time_type,
                    'stock_id'       => $stockId,//丹阳仓
                    'order_date'     => ['between', [strtotime(date('Y-m-d 00:00:00', $time)), strtotime(date('Y-m-d 23:59:59', $time))]],
                ])
                ->value('id');

            if (!$id) {
                $params = [];
                $params['wave_order_number'] = 'BC' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $params['type'] = $type;
                $params['wave_time_type'] = $wave_time_type;
                $params['stock_id'] = $stockId;
                $params['order_date'] = $time;
                $params['createtime'] = time();
                $id = $waveOrder->insertGetId($params);
            }

            //转换平台SKU
            $sku = $itemPlatform->getTrueSku($v['sku'], $v['site']);
            //根据sku查询库位排序
            $stockSku = new StockSku();
            $where = [];
            $where['c.type'] = 2;//默认拣货区
            $where['b.status'] = 1;//启用状态
            $where['a.is_del'] = 1;//正常状态
            $where['b.stock_id'] = $stockId;//查询对应仓库
            $location_data = $stockSku
                ->alias('a')
                ->where($where)
                ->where(['a.sku' => $sku])
                ->field('b.coding,b.picking_sort')
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->join(['fa_warehouse_area' => 'c'], 'b.area_id=c.id')
                ->find();
            $this->orderitemprocess->where(['id' => $v['id']])->update(['wave_order_id' => $id, 'location_code' => $location_data['coding'], 'picking_sort' => $location_data['picking_sort']]);
        }
        echo "ok";
    }


    /**
     * Z站仅镜架 分丹阳仓
     * @author wangpenglei
     * @date   2021/8/3 10:53
     */
    protected function setOrderPrescriptionType()
    {
        $order = new NewOrder();
        $orderitemprocess = new NewOrderItemProcess();
        //查询Z站所有订单
        $list = $order->where('order_prescription_type', 0)
            ->where('site', 1)
            ->where('created_at', '>', strtotime('2021-07-22 16:30:00'))
            ->field('id,entity_id')
            ->select();
        foreach ($list as $key => $value) {
            $order_type = $orderitemprocess->where('magento_order_id', $value['entity_id'])->where('site', 1)->column('order_prescription_type');
            //查不到结果跳过 防止子单表延迟两分钟查不到数据
            if (!$order_type) {
                continue;
            }

            $data = [];
            if (in_array(3, $order_type)) {
                $type = 3;
            } elseif (in_array(2, $order_type)) {
                $type = 2;
            } else {
                $type = 1;
                //如果Z站全为仅镜框 则分到丹阳仓
                $data['stock_id'] = 2;
                $orderitemprocess->where('magento_order_id', $value['entity_id'])->where('site', 1)->update(['stock_id' => 2, 'wave_order_id' => 0]);
            }

            $data['order_prescription_type'] = $type;
            $data['updated_at'] = time();
            $order->where('id', $value['id'])->update($data);
            echo $value['id'] . ' is ok' . "\n";
            usleep(100000);
        }
    }
    ########################################end##############################################


    ################################################处理旧数据脚本#############################

    /**
     * 批发站主表
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 09:37:37 
     *
     * @param     [type] $site
     *
     * @return void
     */
    public function wesee_old_order_paymethod()
    {
        $site = 5;
        $list = Db::connect('database.db_wesee_temp')->table('orders')->where('id>1875')->select();
        foreach ($list as $k => $v) {

            $params = [];

            $params['payment_method'] = $v['payment_type'];
            $params['last_trans_id'] = $v['payment_order_no'];

            //插入订单主表
            $this->order->where(['site' => 5, 'entity_id' => $v['id']])->update($params);
        }

        echo "ok";
    }


    /**
     * 批发站主表
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 09:37:37 
     *
     * @param     [type] $site
     *
     * @return void
     */
    public function wesee_old_order()
    {
        $site = 5;
        $list = Db::connect('database.db_wesee_temp')->table('orders')->where('id>1875')->select();

        $order_params = [];
        foreach ($list as $k => $v) {
            $count = $this->order->where('site=' . $site . ' and entity_id=' . $v['id'])->count();
            if ($count > 0) {
                continue;
            }
            $params = [];
            $params['entity_id'] = $v['id'];
            $params['site'] = $site;
            $params['increment_id'] = $v['order_no'];
            $params['status'] = $v['order_status'] ?: '';
            $params['store_id'] = $v['source'];
            $params['base_grand_total'] = $v['actual_amount_paid'];
            $params['total_qty_ordered'] = $v['goods_quantity'];
            $params['base_currency_code'] = $v['base_currency'];
            $params['order_currency_code'] = $v['now_currency'];
            $params['shipping_method'] = $v['freight_type'];
            $params['shipping_title'] = $v['freight_description'];
            $params['customer_email'] = $v['email'];
            $params['base_to_order_rate'] = $v['rate'];
            $params['base_shipping_amount'] = $v['freight_price'];
            $params['created_at'] = strtotime($v['created_at']) + 28800;
            $params['updated_at'] = strtotime($v['updated_at']) + 28800;
            if (isset($v['payment_time'])) {
                $params['payment_time'] = strtotime($v['payment_time']) + 28800;
            }

            //插入订单主表
            $order_id = $this->order->insertGetId($params);
            $order_params[$k]['site'] = $site;
            $order_params[$k]['order_id'] = $order_id;
            $order_params[$k]['entity_id'] = $v['id'];
            $order_params[$k]['increment_id'] = $v['order_no'];

            echo $v['entity_id'] . "\n";
            usleep(10000);
        }
        //插入订单处理表
        if ($order_params) {
            $this->orderprocess->saveAll($order_params);
        }
        echo "ok";
    }


    /**
     * 地址处理
     *
     * @Description
     * @return void
     * @since  2020/11/02 18:31:12
     * @author wpl
     */
    public function wesee_order_address_data()
    {
        $site = 5;
        $list = Db::connect('database.db_wesee_temp')->table('orders_addresses')->where('order_id>1875 and order_id<1916')->where('type=1')->select();
        $params = [];
        foreach ($list as $k => $v) {
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
        echo $site . 'ok';
    }


    /**
     * 地址处理
     *
     * @Description
     * @return void
     * @since  2020/11/02 18:31:12
     * @author wpl
     */
    public function wesee_order_item_data()
    {
        $site = 5;
        $list = Db::connect('database.db_wesee_temp')
            ->table('orders_items')->alias('a')
            ->join(['orders_prescriptions' => 'b'], 'a.orders_prescriptions_id=b.id')
            ->where('order_id>1100 and order_id<1801')->select();
        foreach ($list as $k => $v) {
            $options = [];
            //处方解析 不同站不同字段
            $options = $this->wesee_prescription_analysis($v['prescription']);
            $options['prescription_type'] = $v['name'];
            unset($options['order_prescription_type']);
            $this->orderitemoption->where(['item_id' => $v['id'], 'site' => 5, 'magento_order_id' => $v['order_id']])->update($options);
        }

        echo $site . 'ok';
    }


    /**
     * 处理漏单数据  - 01
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 15:47:45 
     * @return void
     */
    public function process_order_data_temp()
    {
        $this->zeelool_old_order(1);
        $this->zeelool_old_order(2);
        $this->zeelool_old_order(3);
        $this->zeelool_old_order(10);
        $this->zeelool_old_order(11);
    }

    protected function zeelool_old_order($site)
    {
        if ($site == 1) {
            $entity_id = [
                1021739,
                1021740,
                1021741,
                1021742,
                1021743,
                1021744,
                1021745,
                1021746,
                1021747,
                1021748,
                1021749,
                1021750,
                1021751,
                1021752,
                1021753,
                1021754,
                1021755,
                1021756,
                1021757,
                1021758,
                1021759,
                1021760,
                1021761,
                1021762,
                1021763,
                1021764,
                1021765,
                1021766,
                1021767,
                1021768,
                1021769,
                1021770,
                1021771,
                1021772,
                1021773,
                1021774,
                1021775,
                1021776,
                1021777,
                1021778,
                1021779,
                1021780,
                1021781,
                1021782,
                1021783,
                1021784,
                1021785,
                1021786,
                1021787,
                1021788,
                1021789,
                1021790,
                1021791,
                1021792,
                1021793,
                1021794,
                1021795,
                1021796,
                1021797,
                1021798,
                1021799,
                1021800,
                1021801,
                1021802,
                1021803,
                1021804,
                1021805,
                1021806,
                1021807,
                1021808,
                1021809,
                1021810,
                1021811,
                1021812,
                1021813,
                1021814,
                1021815,
                1021816,
                1021817,
                1021818,
                1021819,
                1021820,
                1021821,
                1021822,
                1021823,
                1021824,
                1021825,
                1021826,
                1021827,
                1021828,
                1021829,
                1021830,
                1021831,
                1021832,
                1021833,
                1021834,
                1021835,
                1021836,
                1021837,
                1021838,
                1021839,
                1021840,
                1021841,
                1021842,
                1021843,
                1021844,
                1021845,
                1021846,
                1021847,
                1021848,
                1021849,
                1021850,
                1021851,
                1021852,
                1021853,
                1021854,
                1021855,
                1021856,
                1021857,
                1021858,
                1021859,
                1021860,
                1021861,
                1021862,
                1021863,
                1021864,
                1021865,
                1021866,
                1021867,
                1021868,
                1021869,
                1021870,
                1021871,
                1021872,
                1021873,
                1021874,
                1021875,
                1021876,
                1021877,
                1021878,
                1021879,
                1021880,
                1021881,
                1021882,
                1021883,
                1021884,
                1021885,
                1021886,
                1021887,
                1021888,
                1021889,
                1021890,
                1021891,
                1021892,
                1021893,
                1021894,
                1021895,
                1021896,
                1021897,
                1021898,
                1021899,
                1021900,
                1021901,
                1021902,
                1021903,
                1021904,
                1021905,
                1021906,
                1021907,
                1021908,
                1021909,
                1021910,
                1021911,
                1021912,
                1021913,
                1021914,
                1021915,
                1021916,
                1021917,
                1021918,
                1021919,
                1021920,
                1021921,
                1021922,
                1021923,
                1021924,
                1021925,
                1021926,
                1021927,
                1021928,
                1021929,
                1021930,
                1021931,
                1021932,
                1021933,
                1021934,
                1021935,
                1021936,
                1021937,
                1021938,
                1021939,
                1021940,
                1021941,
                1021942,
                1021943,
                1021944,
                1021945,
                1021946,
                1021947,
                1021948,
                1021949,
                1021950,
                1021951,
                1021952,
                1021953,
                1021954,
                1021955,
                1021956,
                1021957,
                1021958,
                1021959,
                1021960,
                1021961,
                1021962,
                1021963,
                1021964,
                1021965,
                1021966,
                1021967,
                1021968,
            ];
            $list = Db::connect('database.db_zeelool')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->select();
        } elseif ($site == 2) {
            $entity_id = [
                508413,
                508414,
                508415,
                508416,
                508417,
                508418,
                508419,
                508420,
                508421,
                508422,
                508423,
                508424,
                508425,
                508426,
                508427,
                508428,
                508429,
                508430,
                508431,
                508432,
                508433,
                508434,
                508435,
                508436,
                508437,
                508438,
                508439,
                508440,
                508441,
                508442,
                508443,
                508444,
                508445,
                508446,
                508447,
                508448,
                508449,
                508450,
                508451,
                508452,
                508453,
                508454,
                508455,
                508456,
                508457,
                508458,
                508459,
                508460,
                508461,
                508462,
                508463,
                508464,
                508465,
                508466,
                508467,
                508468,
                508469,
                508470,
                508471,
                508472,
                508473,
                508474,
                508475,
                508476,
                508477,
                508478,
                508479,
                508480,
                508481,
                508482,
                508483,
                508484,
                508485,
                508486,
                508487,
                508488,
                508489,
                508490,
                508491,
                508492,
                508493,
                508494,
                508495,
                508496,
                508497,
            ];
            $list = Db::connect('database.db_voogueme')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->select();
        } elseif ($site == 3) {
            $entity_id = [
                86182,
                86183,
                86184,
                86185,
                86186,
                86187,
                86188,
                86189,
                86190,
                86191,
                86192,
                86193,
            ];
            $list = Db::connect('database.db_nihao')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->select();
        } elseif ($site == 10) {
            $entity_id = [
                19141,
                19142,
                19143,
                19144,
                19145,
            ];
            $list = Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->select();
        } elseif ($site == 11) {
            $entity_id = [
                11758,
                11759,
                11760,
                11761,
                11762,
                11763,
                11764,
                11765,
                11766,
                11767,
                11768,
                11769,
                11770,
                11771,
                11772,
                11773,
                11774,
                11775,
            ];
            $list = Db::connect('database.db_zeelool_jp')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->select();
        }

        if ($entity_id) {
            $this->order->where(['entity_id' => ['in', $entity_id], 'site' => $site])->delete();
            $this->orderitemprocess->where(['magento_order_id' => ['in', $entity_id], 'site' => $site])->delete();
            $this->orderitemoption->where(['magento_order_id' => ['in', $entity_id], 'site' => $site])->delete();
            $this->orderprocess->where(['entity_id' => ['in', $entity_id], 'site' => $site])->delete();
        }

        $list = collection($list)->toArray();

        $order_params = [];
        foreach ($list as $k => $v) {
            $params = [];
            $params['entity_id'] = $v['entity_id'];
            $params['site'] = $site;
            $params['increment_id'] = $v['increment_id'];
            $params['status'] = $v['status'] ?: '';
            $params['store_id'] = $v['store_id'];
            $params['base_grand_total'] = $v['base_grand_total'];
            $params['grand_total'] = $v['grand_total'];
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
            $params['taxno'] = $v['cpf'];
            $params['base_to_order_rate'] = $v['base_to_order_rate'];
            $params['mw_rewardpoint'] = $v['mw_rewardpoint'];
            $params['mw_rewardpoint_discount'] = $v['mw_rewardpoint_discount'];
            $params['base_shipping_amount'] = $v['base_shipping_amount'];
            $params['base_discount_amount'] = $v['base_discount_amount'];
            $params['customer_id'] = $v['customer_id'] ?: 0;
            $params['quote_id'] = $v['quote_id'];
            $params['created_at'] = strtotime($v['created_at']) + 28800;
            $params['updated_at'] = strtotime($v['updated_at']) + 28800;
            if (isset($v['payment_time'])) {
                $params['payment_time'] = strtotime($v['payment_time']) + 28800;
            }
            $params['coupon_code'] = $v['coupon_code'];
            $params['coupon_rule_name'] = $v['coupon_rule_name'];
            //插入订单主表
            $order_id = $this->order->insertGetId($params);
            //es同步订单数据，插入
            $this->asyncOrder->runInsert($params, $order_id);
            $order_params[$k]['site'] = $site;
            $order_params[$k]['order_id'] = $order_id;
            $order_params[$k]['entity_id'] = $v['entity_id'];
            $order_params[$k]['increment_id'] = $v['increment_id'];
            echo $v['increment_id'] . "\n";
            usleep(10000);
        }
        //插入订单处理表
        if ($order_params) {
            $this->orderprocess->saveAll($order_params);
        }
        echo "ok";
    }


    /**
     * 处理漏单数据  - 02
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 15:47:45 
     * @return void
     */
    public function process_order_data_address_temp()
    {
        $this->order_address_data_shell(1);
        $this->order_address_data_shell(2);
        $this->order_address_data_shell(3);
        $this->order_address_data_shell(10);
        $this->order_address_data_shell(11);
    }

    public function order_address_data_shell($site)
    {

        if ($site == 1) {
            $entity_id = [
                1021739,
                1021740,
                1021741,
                1021742,
                1021743,
                1021744,
                1021745,
                1021746,
                1021747,
                1021748,
                1021749,
                1021750,
                1021751,
                1021752,
                1021753,
                1021754,
                1021755,
                1021756,
                1021757,
                1021758,
                1021759,
                1021760,
                1021761,
                1021762,
                1021763,
                1021764,
                1021765,
                1021766,
                1021767,
                1021768,
                1021769,
                1021770,
                1021771,
                1021772,
                1021773,
                1021774,
                1021775,
                1021776,
                1021777,
                1021778,
                1021779,
                1021780,
                1021781,
                1021782,
                1021783,
                1021784,
                1021785,
                1021786,
                1021787,
                1021788,
                1021789,
                1021790,
                1021791,
                1021792,
                1021793,
                1021794,
                1021795,
                1021796,
                1021797,
                1021798,
                1021799,
                1021800,
                1021801,
                1021802,
                1021803,
                1021804,
                1021805,
                1021806,
                1021807,
                1021808,
                1021809,
                1021810,
                1021811,
                1021812,
                1021813,
                1021814,
                1021815,
                1021816,
                1021817,
                1021818,
                1021819,
                1021820,
                1021821,
                1021822,
                1021823,
                1021824,
                1021825,
                1021826,
                1021827,
                1021828,
                1021829,
                1021830,
                1021831,
                1021832,
                1021833,
                1021834,
                1021835,
                1021836,
                1021837,
                1021838,
                1021839,
                1021840,
                1021841,
                1021842,
                1021843,
                1021844,
                1021845,
                1021846,
                1021847,
                1021848,
                1021849,
                1021850,
                1021851,
                1021852,
                1021853,
                1021854,
                1021855,
                1021856,
                1021857,
                1021858,
                1021859,
                1021860,
                1021861,
                1021862,
                1021863,
                1021864,
                1021865,
                1021866,
                1021867,
                1021868,
                1021869,
                1021870,
                1021871,
                1021872,
                1021873,
                1021874,
                1021875,
                1021876,
                1021877,
                1021878,
                1021879,
                1021880,
                1021881,
                1021882,
                1021883,
                1021884,
                1021885,
                1021886,
                1021887,
                1021888,
                1021889,
                1021890,
                1021891,
                1021892,
                1021893,
                1021894,
                1021895,
                1021896,
                1021897,
                1021898,
                1021899,
                1021900,
                1021901,
                1021902,
                1021903,
                1021904,
                1021905,
                1021906,
                1021907,
                1021908,
                1021909,
                1021910,
                1021911,
                1021912,
                1021913,
                1021914,
                1021915,
                1021916,
                1021917,
                1021918,
                1021919,
                1021920,
                1021921,
                1021922,
                1021923,
                1021924,
                1021925,
                1021926,
                1021927,
                1021928,
                1021929,
                1021930,
                1021931,
                1021932,
                1021933,
                1021934,
                1021935,
                1021936,
                1021937,
                1021938,
                1021939,
                1021940,
                1021941,
                1021942,
                1021943,
                1021944,
                1021945,
                1021946,
                1021947,
                1021948,
                1021949,
                1021950,
                1021951,
                1021952,
                1021953,
                1021954,
                1021955,
                1021956,
                1021957,
                1021958,
                1021959,
                1021960,
                1021961,
                1021962,
                1021963,
                1021964,
                1021965,
                1021966,
                1021967,
                1021968,
            ];
            $list = Db::connect('database.db_zeelool')
                ->table('sales_flat_order_address')
                ->where(['parent_id' => ['in', $entity_id]])
                ->where(['address_type' => 'shipping'])->select();

        } elseif ($site == 2) {
            $entity_id = [
                508413,
                508414,
                508415,
                508416,
                508417,
                508418,
                508419,
                508420,
                508421,
                508422,
                508423,
                508424,
                508425,
                508426,
                508427,
                508428,
                508429,
                508430,
                508431,
                508432,
                508433,
                508434,
                508435,
                508436,
                508437,
                508438,
                508439,
                508440,
                508441,
                508442,
                508443,
                508444,
                508445,
                508446,
                508447,
                508448,
                508449,
                508450,
                508451,
                508452,
                508453,
                508454,
                508455,
                508456,
                508457,
                508458,
                508459,
                508460,
                508461,
                508462,
                508463,
                508464,
                508465,
                508466,
                508467,
                508468,
                508469,
                508470,
                508471,
                508472,
                508473,
                508474,
                508475,
                508476,
                508477,
                508478,
                508479,
                508480,
                508481,
                508482,
                508483,
                508484,
                508485,
                508486,
                508487,
                508488,
                508489,
                508490,
                508491,
                508492,
                508493,
                508494,
                508495,
                508496,
                508497,
            ];
            $list = Db::connect('database.db_voogueme')
                ->table('sales_flat_order_address')
                ->where(['parent_id' => ['in', $entity_id]])
                ->where(['address_type' => 'shipping'])->select();

        } elseif ($site == 3) {
            $entity_id = [
                86182,
                86183,
                86184,
                86185,
                86186,
                86187,
                86188,
                86189,
                86190,
                86191,
                86192,
                86193,
            ];
            $list = Db::connect('database.db_nihao')
                ->table('sales_flat_order_address')
                ->where(['parent_id' => ['in', $entity_id]])
                ->where(['address_type' => 'shipping'])->select();
        } elseif ($site == 10) {
            $entity_id = [
                19141,
                19142,
                19143,
                19144,
                19145,
            ];

            $list = Db::connect('database.db_zeelool_de')
                ->table('sales_flat_order_address')
                ->where(['parent_id' => ['in', $entity_id]])
                ->where(['address_type' => 'shipping'])->select();

        } elseif ($site == 11) {
            $entity_id = [
                11758,
                11759,
                11760,
                11761,
                11762,
                11763,
                11764,
                11765,
                11766,
                11767,
                11768,
                11769,
                11770,
                11771,
                11772,
                11773,
                11774,
                11775,
            ];

            $list = Db::connect('database.db_zeelool_jp')
                ->table('sales_flat_order_address')
                ->where(['parent_id' => ['in', $entity_id]])
                ->where(['address_type' => 'shipping'])->select();
        }


        foreach ($list as $k => $v) {
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


    /**
     * 订单支付临时表 - 03
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
        $this->order_payment_data(10);
        $this->order_payment_data(11);
    }

    /**
     * 支付方式处理 - 03
     *
     * @Description
     * @return void
     * @since  2020/11/02 18:31:12
     * @author wpl
     */
    protected function order_payment_data($site)
    {
        $list = $this->order->where('last_trans_id is null and site = ' . $site)->limit(4000)->select();
        $list = collection($list)->toArray();
        $entity_id = array_column($list, 'entity_id');
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('method,last_trans_id', 'parent_id');
        } elseif ($site == 2) {
            $res = Db::connect('database.db_voogueme')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('method,last_trans_id', 'parent_id');
        } elseif ($site == 3) {
            $res = Db::connect('database.db_nihao')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('method,last_trans_id', 'parent_id');
        } elseif ($site == 4) {
            $res = Db::connect('database.db_meeloog')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 5) {
            $res = Db::connect('database.db_weseeoptical')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 9) {
            $res = Db::connect('database.db_zeelool_es')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id', 'parent_id');
        } elseif ($site == 10) {
            $res = Db::connect('database.db_zeelool_de')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('method,last_trans_id', 'parent_id');
        } elseif ($site == 11) {
            $res = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('method,last_trans_id', 'parent_id');
        } elseif ($site == 15) {
            $res = Db::connect('database.db_zeelool_fr')->table('sales_flat_order_payment')->where(['parent_id' => ['in', $entity_id]])->column('last_trans_id,method', 'parent_id');
        }
        if ($res) {
            $params = [];
            foreach ($list as $k => $v) {
                $params[$k]['id'] = $v['id'];
                $params[$k]['last_trans_id'] = $res[$v['entity_id']]['last_trans_id'] ?: 0;
                $params[$k]['payment_method'] = $res[$v['entity_id']]['method'];
            }
            $this->order->saveAll($params);
            echo $site . 'ok';
        }
    }


    /**
     * 临时处理订单子表数据 - 04
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

        if ($site == 1) {
            $entity_id = [
                1021651,
                1021652,
                1021653,
                1021654,
                1021655,
                1021656,
                1021657,
                1021658,
                1021659,
                1021660,
                1021661,
                1021662,
                1021663,
                1021664,
                1021665,
                1021666,
                1021667,
                1021668,
                1021669,
                1021670,
                1021671,
                1021672,
                1021673,
                1021674,
                1021675,
                1021676,
                1021677,
                1021678,
                1021679,
                1021680,
                1021681,
                1021682,
                1021683,
                1021684,
                1021685,
                1021686,
                1021687,
                1021688,
                1021689,
                1021690,
                1021691,
                1021692,
                1021693,
                1021694,
                1021695,
                1021696,
                1021697,
                1021698,
                1021699,
                1021700,
                1021701,
                1021702,
                1021703,
                1021704,
                1021705,
                1021706,
                1021707,
                1021708,
                1021709,
                1021710,
                1021711,
                1021712,
                1021713,
                1021714,
                1021715,
                1021716,
                1021717,
                1021718,
                1021719,
                1021720,
                1021721,
                1021722,
                1021723,
                1021724,
                1021725,
                1021726,
                1021727,
                1021728,
                1021729,
                1021730,
                1021731,
                1021732,
                1021733,
                1021734,
                1021735,
                1021736,
                1021737,
                1021738,
            ];
            $list = Db::connect('database.db_zeelool')
                ->table('sales_flat_order_item')
                ->where(['order_id' => ['in', $entity_id]])
                ->select();

        } elseif ($site == 2) {
            $entity_id = [
                508413,
                508414,
                508415,
                508416,
                508417,
                508418,
                508419,
                508420,
                508421,
                508422,
                508423,
                508424,
                508425,
                508426,
                508427,
                508428,
                508429,
                508430,
                508431,
                508432,
                508433,
                508434,
                508435,
                508436,
                508437,
                508438,
                508439,
                508440,
                508441,
                508442,
                508443,
                508444,
                508445,
                508446,
                508447,
                508448,
                508449,
                508450,
                508451,
                508452,
                508453,
                508454,
                508455,
                508456,
                508457,
                508458,
                508459,
                508460,
                508461,
                508462,
                508463,
                508464,
                508465,
                508466,
                508467,
                508468,
                508469,
                508470,
                508471,
                508472,
                508473,
                508474,
                508475,
                508476,
                508477,
                508478,
                508479,
                508480,
                508481,
                508482,
                508483,
                508484,
                508485,
                508486,
                508487,
                508488,
                508489,
                508490,
                508491,
                508492,
                508493,
                508494,
                508495,
                508496,
                508497,
            ];

            $list = Db::connect('database.db_voogueme')
                ->table('sales_flat_order_item')
                ->where(['order_id' => ['in', $entity_id]])
                ->select();

        } elseif ($site == 3) {
            $entity_id = [
                86182,
                86183,
                86184,
                86185,
                86186,
                86187,
                86188,
                86189,
                86190,
                86191,
                86192,
                86193,
            ];
            $list = Db::connect('database.db_nihao')
                ->table('sales_flat_order_item')
                ->where(['order_id' => ['in', $entity_id]])
                ->select();

        } elseif ($site == 10) {
            $entity_id = [
                19141,
                19142,
                19143,
                19144,
                19145,
            ];

            $list = Db::connect('database.db_zeelool_de')
                ->table('sales_flat_order_item')
                ->where(['order_id' => ['in', $entity_id]])
                ->select();


        } elseif ($site == 11) {
            $entity_id = [
                11758,
                11759,
                11760,
                11761,
                11762,
                11763,
                11764,
                11765,
                11766,
                11767,
                11768,
                11769,
                11770,
                11771,
                11772,
                11773,
                11774,
                11775,
            ];

            $list = Db::connect('database.db_zeelool_jp')
                ->table('sales_flat_order_item')
                ->where(['order_id' => ['in', $entity_id]])
                ->select();

        }


        foreach ($list as $k => $v) {
            $count = $this->orderitemprocess->where('site=' . $site . ' and item_id=' . $v['item_id'])->count();
            if ($count > 0) {
                continue;
            }
            $options = [];
            //处方解析 不同站不同字段
            //处方解析 不同站不同字段
            if ($site == 1) {
                $options = $this->zeelool_prescription_analysis($v['product_options']);
            } elseif ($site == 2) {
                $options = $this->voogueme_prescription_analysis($v['product_options']);
            } elseif ($site == 3) {
                $options = $this->nihao_prescription_analysis($v['product_options']);
            } elseif ($site == 10) {
                $options = $this->zeelool_de_prescription_analysis($v['product_options']);
            } elseif ($site == 11) {
                $options = $this->zeelool_jp_prescription_analysis($v['product_options']);
            }

            $options['item_id'] = $v['item_id'];
            $options['site'] = $site;
            $options['magento_order_id'] = $v['order_id'];
            $options['sku'] = $v['sku'];
            $options['qty'] = $v['qty_ordered'];
            $options['base_row_total'] = $v['base_row_total'];
            $options['product_id'] = $v['product_id'];
            $options['base_original_price'] = round($v['base_original_price'] * $v['qty_ordered'], 4);
            $options['base_discount_amount'] = $v['base_discount_amount'];
            $options['single_base_original_price'] = $v['base_original_price'];
            $options['single_base_discount_amount'] = round($v['base_discount_amount'] / $v['qty_ordered'], 4);
            $order_prescription_type = $options['order_prescription_type'];
            $is_prescription_abnormal = $options['is_prescription_abnormal'] ?: 0;
            unset($options['order_prescription_type']);
            unset($options['is_prescription_abnormal']);
            if ($options) {
                $options_id = $this->orderitemoption->insertGetId($options);
                $data = []; //子订单表数据
                for ($i = 0; $i < $v['qty_ordered']; $i++) {
                    $data[$i]['item_id'] = $v['item_id'];
                    $data[$i]['magento_order_id'] = $v['order_id'];
                    $data[$i]['site'] = $site;
                    $data[$i]['option_id'] = $options_id;
                    $data[$i]['sku'] = $v['sku'];
                    $data[$i]['order_prescription_type'] = $order_prescription_type ?: '';
                    $data[$i]['is_prescription_abnormal'] = $is_prescription_abnormal;
                    $data[$i]['created_at'] = strtotime($v['created_at']) + 28800;
                    $data[$i]['updated_at'] = strtotime($v['updated_at']) + 28800;
                }

                $this->orderitemprocess->insertAll($data);

                //判断如果子订单处方是否为定制片 子订单有定制片则主单为定制
                if ($order_prescription_type == 3 && in_array($site, [1, 3])) {
                    $this->order->where(['entity_id' => $v['order_id'], 'site' => $site])->update(['is_custom_lens' => 1, 'stock_id' => 2, 'updated_at' => time() + 28800]);
                    $this->orderitemprocess->where(['magento_order_id' => $v['order_id'], 'site' => $site])->update(['stock_id' => 2]);
                }
                $this->order->where(['entity_id' => $v['order_id'], 'site' => $site])->update(['updated_at' => time() + 28800]);
            }
            echo $v['item_id'] . "\n";
            usleep(10000);
        }
        echo "ok";
    }


    public function order_data_shell_de()
    {
        $this->order_data(5);
        $this->order_data(9);
        $this->order_data(10);
        $this->order_data(11);
        $this->order_data(12);
    }

    /**
     * 地址处理
     *
     * @Description
     * @return void
     * @since  2020/11/02 18:31:12
     * @author wpl
     */
    protected function order_data($site)
    {
        $list = $this->order->field('id,entity_id')->where('base_discount_amount is null and site = ' . $site)->limit(4000)->select();
        $list = collection($list)->toArray();
        $entity_id = array_column($list, 'entity_id');
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        } elseif ($site == 2) {
            $res = Db::connect('database.db_voogueme')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        } elseif ($site == 3) {
            $res = Db::connect('database.db_nihao')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        } elseif ($site == 4) {
            $res = Db::connect('database.db_weseeoptical')->table('orders')->where(['id' => ['in', $entity_id]])->column('base_discounts_price as base_discount_amount,user_id as customer_id', 'entity_id');
        } elseif ($site == 9) {
            $res = Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        } elseif ($site == 10) {
            $res = Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        } elseif ($site == 11) {
            $res = Db::connect('database.db_zeelool_jp')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        } elseif ($site == 12) {
            $res = Db::connect('database.db_voogueme_acc')->table('sales_flat_order')->where(['entity_id' => ['in', $entity_id]])->column('base_discount_amount,customer_id', 'entity_id');
        }
        $params = [];
        foreach ($list as $k => $v) {
            $params[$k]['id'] = $v['id'];
            $params[$k]['base_discount_amount'] = $res[$v['entity_id']]['base_discount_amount'] ?? 0;
            $params[$k]['customer_id'] = $res[$v['entity_id']]['customer_id'] ?? 0;
        }
        $this->order->saveAll($params);
        usleep(100000);
        echo $site . 'ok';
    }


    /**
     * 支付方式处理
     *
     * @Description
     * @return void
     * @since  2020/11/02 18:31:12
     * @author wpl
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
            if (!$product_id) {
                continue;
            }
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
        // $this->order_item_data_shell_temp(11);
        // $this->order_item_shell(5);

    }

    protected function order_item_data_shell_temp($site)
    {
        $list = $this->orderitemoption->where('site=' . $site . ' and lens_number = 22304000')->limit(3000)->select();
        $list = collection($list)->toArray();
        $option_params = [];
        foreach ($list as $k => $v) {
            //处方解析 不同站不同字段
            $result = $this->set_processing_type($v);
            $this->orderitemprocess->where(['site' => $v['site'], 'item_id' => $v['item_id'], 'order_id' => $v['order_id']])->update(['order_prescription_type' => $result['order_prescription_type']]);
            echo $v['item_id'] . "\n";
            usleep(10000);
        }

        // $this->orderitemprocess->saveAll($option_params);
        echo "ok";
    }

    public function process_order_type()
    {
        $item_order_number = [];
        $orderItemOption = new \app\admin\model\order\OrderItemOption();
        $list = $orderItemOption->where('base_original_price is null')->limit(4000)->select();
        $option_params = [];
        foreach ($list as $k => $v) {
            $site = $v['site'];
            $options = [];
            //处方解析 不同站不同字段
            if ($site == 1) {
                $item_data = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->zeelool_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 2) {
                $item_data = Db::connect('database.db_voogueme')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->voogueme_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 3) {
                $item_data = Db::connect('database.db_nihao')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->nihao_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 5) {
                $item_data = Db::connect('database.db_weseeoptical')->table('orders_items')->where(['id' => $v['item_id']])->column('goods_count as qty_ordered,base_goods_price as base_original_price,base_goods_discounts_price as base_discount_amount', 'item_id');
//                $options = $this->wesee_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 9) {
                $item_data = Db::connect('database.db_zeelool_es')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->zeelool_es_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 10) {
                $item_data = Db::connect('database.db_zeelool_de')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->zeelool_de_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 11) {
                $item_data = Db::connect('database.db_zeelool_jp')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->zeelool_jp_prescription_analysis($item_data[$v['item_id']]);
            } elseif ($site == 12) {
                $item_data = Db::connect('database.db_voogueme_acc')->table('sales_flat_order_item')->where(['item_id' => $v['item_id']])->column('qty_ordered,base_original_price,base_discount_amount', 'item_id');
//                $options = $this->zeelool_jp_prescription_analysis($item_data[$v['item_id']]);
            }
            $params = [];
            $params['base_original_price'] = $item_data[$v['item_id']]['base_original_price'] ?: 0;
            $params['base_discount_amount'] = $item_data[$v['item_id']]['base_discount_amount'];
            $params['single_base_original_price'] = $item_data[$v['item_id']]['qty_ordered'] > 0 ? round($item_data[$v['item_id']]['base_original_price'] / $item_data[$v['item_id']]['qty_ordered'], 4) : 0;
            $params['single_base_discount_amount'] = $item_data[$v['item_id']]['qty_ordered'] > 0 ? round($item_data[$v['item_id']]['base_discount_amount'] / $item_data[$v['item_id']]['qty_ordered'], 4) : 0;
            $orderItemOption->where(['id' => $v['id']])->update($params);

            echo $v['id'] . "\n";
            usleep(10000);
        }
        echo "ok";
    }

    /**
     * 地址处理
     *
     * @Description
     * @return void
     * @since  2020/11/02 18:31:12
     * @author wpl
     */
    protected function order_address_data($site)
    {
        $list = $this->order->where('firstname is null and site = ' . $site)->limit(3000)->select();
        $list = collection($list)->toArray();
        $entity_id = array_column($list, 'entity_id');
        if ($site == 1) {
            $res = Db::connect('database.db_zeelool')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('telephone,postcode,country_id,region,region_id,city,street,lastname,firstname', 'parent_id');
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
        } elseif ($site == 12) {
            $res = Db::connect('database.db_voogueme_acc')->table('sales_flat_order_address')->where(['parent_id' => ['in', $entity_id]])->column('lastname,firstname', 'parent_id');
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

    public function batch_payment_time()
    {
        $model = Db::connect('database.db_weseeoptical');
        $orders = $model->table('orders')
            ->field('order_no,payment_time')
            ->select();
        foreach ($orders as $value) {
            $time = strtotime($value['payment_time']) + 8 * 3600;
            $this->order
                ->where('site', 5)
                ->where('increment_id', $value['order_no'])
                ->update(['payment_time' => $time]);
            echo $value['order_no'] . ' is ok' . "\n";
            usleep(10000);
        }
    }
}
