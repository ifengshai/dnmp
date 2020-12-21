<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;


class Test extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';
    protected $str1 = 'Arrived Shipping Partner Facility, Awaiting Item.';
    protected $str2 = 'Delivered to Air Transport.';
    protected $str3 = 'In Transit to Next Facility.';
    protected $str4 = 'Arrived in the Final Destination Country.';
    protected $str30 = 'Out for delivery or arrived at local facility, you may schedule for delivery or pickup. Please be aware of the collection deadline.'; //到达待取
    protected $str35 = 'Attempted for delivery but failed, this may due to several reasons. Please contact the carrier for clarification.'; //投递失败
    protected $str40 = 'Delivered successfully.'; //投递成功
    protected $str50 = 'Item might undergo unusual shipping condition, this may due to several reasons, most likely item was returned to sender, customs issue etc.'; //可能异常


    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->user = new \app\admin\model\Admin();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $this->ordernode = new \app\admin\model\OrderNode();
    }

    public function test001()
    {
        $track_number = '9400111108296818283602';
        $order_number = '100171868';
        //根据物流单号查询发货物流渠道
        $shipment_data_type = Db::connect('database.db_delivery')->table('ld_deliver_order')->where(['track_number' => $track_number, 'increment_id' => $order_number])->value('agent_way_title');

        dump($shipment_data_type);
        die;
    }


    public function site_reg()
    {
        $this->reg_shipment('database.db_zeelool', 1);
        $this->reg_shipment('database.db_voogueme', 2);
        $this->reg_shipment('database.db_nihao', 3);
        $this->reg_shipment('database.db_meeloog', 4);
    }


    /**
     * 批量 注册物流
     * 每天跑一次，查找遗漏注册的物流单号，进行注册操作
     */
    public function reg_shipment($site_str, $site_type)
    {
        $order_shipment = Db::connect($site_str)
            ->table('sales_flat_shipment_track')->alias('a')
            ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
            ->field('a.entity_id,a.order_id,a.track_number,a.title,a.updated_at,a.created_at,b.increment_id')
            ->where('a.created_at', '>=', '2020-07-31 00:00:00')
            ->where('a.title', '=', 'noLogisticswaypoolCarriercode')
            // ->where('a.handle', '=', '0')
            ->group('a.order_id')
            ->select();
        $shipment_reg = [];
        foreach ($order_shipment as $k => $v) {
            if ($v['title'] == 'noLogisticswaypoolCarriercode') {
                $title = 'FedEx';
            } else {
                $title = $v['title'];
            }
            $title = strtolower(str_replace(' ', '-', $title));
            $carrier = $this->getCarrier($title);
            $shipment_reg[$k]['number'] = $v['track_number'];
            $shipment_reg[$k]['carrier'] = $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] = $v['order_id'];
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        foreach ($order_group as $key => $val) {
            $trackingConnector->registerMulti($val);
            usleep(500000);
        }
        echo $site_str . ' is ok' . "\n";
    }


    /**
     * 获取快递号
     * @param $title
     * @return mixed|string
     */
    public function getCarrier($title)
    {
        $carrierId = '';
        if (stripos($title, 'post') !== false) {
            $carrierId = 'chinapost';
            $title = 'China Post';
        } elseif (stripos($title, 'eub') !== false) {
            $carrierId = 'eub';
            $title = 'EUB';
        } elseif (stripos($title, 'ems') !== false) {
            $carrierId = 'chinaems';
            $title = 'China Ems';
        } elseif (stripos($title, 'dhl') !== false) {
            $carrierId = 'dhl';
            $title = 'DHL';
        } elseif (stripos($title, 'fede') !== false) {
            $carrierId = 'fedex';
            $title = 'Fedex';
        } elseif (stripos($title, 'usps') !== false) {
            $carrierId = 'usps';
            $title = 'Usps';
        } elseif (stripos($title, 'yanwen') !== false) {
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        } elseif (stripos($title, 'cpc') !== false) {
            $carrierId = 'cpc';
            $title = 'Canada Post';
        }
        $carrier = [
            'dhl' => '100001',
            'chinapost' => '03011',
            'eub' => '03011',
            'chinaems' => '03013',
            'cpc' =>  '03041',
            'fedex' => '100003',
            'usps' => '21051',
            'yanwen' => '190012'
        ];
        if ($carrierId) {
            return ['title' => $title, 'carrierId' => $carrier[$carrierId]];
        }
        return ['title' => $title, 'carrierId' => $carrierId];
    }

    /**
     * 更新物流表状态
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 18:16:48 
     * @return void
     */
    protected function setLogisticsStatus($params)
    {
        switch ($params['site']) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            default:
                return false;
                break;
        }
        unset($params['site']);
        $url = $url . 'magic/order/logistics';
        $client = new Client(['verify' => false]);
        //请求URL
        $response = $client->request('POST', $url, array('form_params' => $params));
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody);
        return $res;
    }


    /**
     * 获取订单节点数据
     *
     * @Description
     * @author wpl
     * @since 2020/05/14 09:55:00 
     * @return void
     */
    public function setOrderNoteDataMeeloog()
    {
        $this->meeloog = new \app\admin\model\order\order\Meeloog();
        $users = $this->user->column('id', 'nickname');
        $field = 'status,custom_print_label,custom_print_label_person,custom_print_label_created_at,custom_is_match_frame,custom_match_frame_person,
        custom_match_frame_created_at,custom_is_match_lens,custom_match_lens_created_at,custom_match_lens_person,custom_is_send_factory,
        custom_match_factory_person,custom_match_factory_created_at,custom_is_delivery,custom_match_delivery_person,custom_match_delivery_created_at,
        custom_order_prescription_type,a.created_at,a.updated_at,b.track_number,b.created_at as create_time,b.title,a.entity_id,a.increment_id,a.custom_order_prescription_type
        ';
        $map['a.created_at'] = ['>=', '2020-03-31 00:00:00'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review']];
        $zeelool_data = $this->meeloog->alias('a')->field($field)
            ->join(['sales_flat_shipment_track' => 'b'], 'a.entity_id=b.order_id', 'left')
            ->where($map)->select();
        foreach ($zeelool_data as $key => $v) {
            $list = [];
            $k = 0;
            //下单
            $list[$k]['order_node'] = 0;
            $list[$k]['node_type'] = 0;
            $list[$k]['content'] = 'Your order has been created.';
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = 4;
            $list[$k]['order_id'] = $v['entity_id'];
            $list[$k]['order_number'] = $v['increment_id'];
            $list[$k]['shipment_type'] = '';
            $list[$k]['track_number'] = '';
            $list[$k]['handle_user_id'] = 0;
            $list[$k]['handle_user_name'] = '';
            $data['order_node'] = 0;
            $data['node_type'] = 0;

            if (in_array($v['status'], ['processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review'])) {

                //支付
                $list[$k + 1]['order_node'] = 0;
                $list[$k + 1]['node_type'] = 1;
                $list[$k + 1]['content'] = 'Your payment has been successful.';
                $list[$k + 1]['create_time'] = $v['updated_at'];
                $list[$k + 1]['site'] = 4;
                $list[$k + 1]['order_id'] = $v['entity_id'];
                $list[$k + 1]['order_number'] = $v['increment_id'];
                $list[$k + 1]['shipment_type'] = '';
                $list[$k + 1]['track_number'] = '';
                $list[$k + 1]['handle_user_id'] = 0;
                $list[$k + 1]['handle_user_name'] = '';

                $data['order_node'] = 0;
                $data['node_type'] = 1;
            }

            $data['create_time'] = $v['created_at'];
            $data['site'] = 4;
            $data['order_id'] = $v['entity_id'];
            $data['order_number'] = $v['increment_id'];
            $data['update_time'] = $v['created_at'];
            //打标签
            if ($v['custom_print_label'] == 1) {
                $list[$k + 2]['order_node'] = 1;
                $list[$k + 2]['node_type'] = 2;
                $list[$k + 2]['content'] = 'Order is under processing';
                $list[$k + 2]['create_time'] = $v['custom_print_label_created_at'];
                $list[$k + 2]['site'] = 4;
                $list[$k + 2]['order_id'] = $v['entity_id'];
                $list[$k + 2]['order_number'] = $v['increment_id'];
                $list[$k + 2]['handle_user_id'] = $users[$v['custom_print_label_person']];
                $list[$k + 2]['handle_user_name'] = $v['custom_print_label_person'];
                $list[$k + 2]['shipment_type'] = '';
                $list[$k + 2]['track_number'] = '';

                $data['order_node'] = 1;
                $data['node_type'] = 2;
                $data['update_time'] = $v['custom_print_label_created_at'];
            }

            //判断订单是否为仅镜架
            if ($v['custom_order_prescription_type'] == 1) {
                if ($v['custom_is_match_frame'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3;
                    $list[$k + 3]['content'] = 'The product(s) is/are ready, waiting for Quality Inspection';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at'];
                    $list[$k + 3]['site'] = 4;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at'];
                }

                if ($v['custom_is_delivery'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 6;
                    $list[$k + 4]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 4]['create_time'] = $v['custom_match_delivery_created_at'];
                    $list[$k + 4]['site'] = 4;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_delivery_person']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_delivery_person'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at'];
                }

                if ($v['track_number']) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 7; //出库
                    $list[$k + 5]['content']  = 'Leave warehouse, Waiting for being picked up.';
                    $list[$k + 5]['create_time'] = $v['create_time'];
                    $list[$k + 5]['site'] = 4;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['shipment_type'] = $v['title'];
                    $list[$k + 5]['track_number'] = $v['track_number'];
                    $list[$k + 5]['handle_user_id'] = 0;
                    $list[$k + 5]['handle_user_name'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            } else {

                if ($v['custom_is_match_frame'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3; //配镜架
                    $list[$k + 3]['content'] = 'Frame(s) is/are ready, waiting for lenses';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at'];
                    $list[$k + 3]['site'] = 4;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at'];
                }

                if ($v['custom_is_match_lens'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 4; //配镜片
                    $list[$k + 4]['content'] = 'Lenses production completed, waiting for customizing';
                    $list[$k + 4]['create_time'] = $v['custom_match_lens_created_at'];
                    $list[$k + 4]['site'] = 4;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_lens_person']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_lens_person'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 4;
                    $data['update_time'] = $v['custom_match_lens_created_at'];
                }

                if ($v['custom_is_send_factory'] == 1) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 5; //加工
                    $list[$k + 5]['content'] = 'Customizing completed, waiting for Quality Inspection';
                    $list[$k + 5]['create_time'] = $v['custom_match_factory_created_at'];
                    $list[$k + 5]['site'] = 4;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['handle_user_id'] = $users[$v['custom_match_factory_person']];
                    $list[$k + 5]['handle_user_name'] = $v['custom_match_factory_person'];
                    $list[$k + 5]['shipment_type'] = '';
                    $list[$k + 5]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 5;
                    $data['update_time'] = $v['custom_match_factory_created_at'];
                }


                if ($v['custom_is_delivery'] == 1) {
                    $list[$k + 6]['order_node'] = 2;
                    $list[$k + 6]['node_type'] = 6; //质检
                    $list[$k + 6]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 6]['create_time'] = $v['custom_match_delivery_created_at'];
                    $list[$k + 6]['site'] = 4;
                    $list[$k + 6]['order_id'] = $v['entity_id'];
                    $list[$k + 6]['order_number'] = $v['increment_id'];
                    $list[$k + 6]['handle_user_id'] = $users[$v['custom_match_delivery_person']];
                    $list[$k + 6]['handle_user_name'] = $v['custom_match_delivery_person'];
                    $list[$k + 6]['shipment_type'] = '';
                    $list[$k + 6]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at'];
                }

                if ($v['track_number']) {
                    $list[$k + 7]['order_node'] = 2;
                    $list[$k + 7]['node_type'] = 7; //出库
                    $list[$k + 7]['create_time'] = $v['create_time'];
                    $list[$k + 7]['site'] = 4;
                    $list[$k + 7]['order_id'] = $v['entity_id'];
                    $list[$k + 7]['order_number'] = $v['increment_id'];
                    $list[$k + 7]['shipment_type'] = $v['title'];
                    $list[$k + 7]['track_number'] = $v['track_number'];
                    $list[$k + 7]['handle_user_id'] = 0;
                    $list[$k + 7]['handle_user_name'] = '';
                    $list[$k + 7]['content'] = 'Leave warehouse, Waiting for being picked up.';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            }
            $data['shipment_type'] = $v['title'];
            $data['track_number'] = $v['track_number'];


            $count = Db::name('order_node')->where(['order_id' => $v['entity_id'], 'site' => 4])->count();
            if ($count > 0) {
                Db::name('order_node')->where(['order_id' => $v['entity_id'], 'site' => 4])->update($data);
            } else {
                Db::name('order_node')->insert($data);
            }
            $this->ordernodedetail->saveAll($list);
            echo $key . "\n";
        }
        echo 'ok';
    }

    /**
     * 处理SKU编码
     *
     * @Description
     * @author wpl
     * @since 2020/12/18 10:07:12 
     * @return void
     */
    public function process_sku_number()
    {
        $list = Db::name('zzzz_temp')->select();
        foreach($list as $k => $v) {
            
        }
        
    }


    public function order_data3()
    {
        $list = Db::table('fa_order_log')->where(['site' => 3])->order('id desc')->select();
        $wesee = new \app\admin\model\order\order\Nihao();
        foreach ($list as $k => $v) {
            $data['custom_print_label_new'] = 0;
            $data['custom_print_label_person_new'] = '';
            $data['custom_print_label_created_at_new'] = '0000-00-00';
            $data['custom_is_match_frame_new'] = 0;
            $data['custom_match_frame_person_new'] = '';
            $data['custom_match_frame_created_at_new'] = '0000-00-00';
            $data['custom_is_match_lens_new'] = 0;
            $data['custom_match_lens_created_at_new'] = '0000-00-00';
            $data['custom_match_lens_person_new'] = '';
            $data['custom_is_send_factory_new'] = 0;
            $data['custom_match_factory_person_new'] = '';
            $data['custom_match_factory_created_at_new'] = '0000-00-00';
            $data['custom_is_delivery_new'] = 0;
            $data['custom_match_delivery_person_new'] = '';
            $data['custom_match_delivery_created_at_new'] = '0000-00-00';
            $wesee->where(['entity_id' => ['in', $v['order_ids']]])->update($data);
        }
    }

    public function order_data()
    {
        $list = Db::table('fa_order_log')->where(['site' => 3])->order('id desc')->select();
        $wesee = new \app\admin\model\order\order\Nihao();

        foreach ($list as $k => $v) {
            $data = [];
            if ($v['type'] == 1) {
                $data['custom_print_label_new'] = 1;
                $data['custom_print_label_person_new'] = $v['create_person'];
                $data['custom_print_label_created_at_new'] = $v['createtime'];
            } elseif ($v['type'] == 2) {
                $data['custom_is_match_frame_new'] = 1;
                $data['custom_match_frame_person_new'] = $v['create_person'];
                $data['custom_match_frame_created_at_new'] = $v['createtime'];
            } elseif ($v['type'] == 3) {
                $data['custom_is_match_lens_new'] = 1;
                $data['custom_match_lens_created_at_new'] = $v['createtime'];
                $data['custom_match_lens_person_new'] = $v['create_person'];
            } elseif ($v['type'] == 4) {
                $data['custom_is_send_factory_new'] = 1;
                $data['custom_match_factory_person_new'] = $v['create_person'];
                $data['custom_match_factory_created_at_new'] = $v['createtime'];
            } elseif ($v['type'] == 5) {
                $data['custom_is_delivery_new'] = 1;
                $data['custom_match_delivery_person_new'] = $v['create_person'];
                $data['custom_match_delivery_created_at_new'] = $v['createtime'];
            }
            if ($data) {
                $wesee->where(['entity_id' => ['in', $v['order_ids']]])->update($data);
            }
        }
        echo "ok";
    }

    public function order_data2()
    {
        $nihao = new \app\admin\model\order\order\Nihao();
        $data['custom_print_label_new'] = 1;
        $data['custom_is_match_frame_new'] = 1;
        $data['custom_is_match_lens_new'] = 1;
        $data['custom_is_send_factory_new'] = 1;
        $data['custom_is_delivery_new'] = 1;
        $nihao->where(['created_at' => ['<', '2020-01-01']])->update($data);
    }

    /***************处理工单旧数据*********************** */
    public function process_worklist_data()
    {
        /**
         * 判断措施是否为 id = 3主单取消   changesku表需插入所有子订单
         * 判断措施如果id = 19 更改镜框 需插入对应sku 所有子订单
         * 判断措施id = 20 更改镜片 需插入对应sku 所有子订单
         */
        $work = new \app\admin\model\saleaftermanage\WorkOrderList();
        $order = new \app\admin\model\order\order\NewOrder();
        $orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $list = $work->where(['work_status' => 1])->select();
        foreach ($list as $k => $v) {
            //插入主表
            Db::table('fa_work_order_list_copy1')->insert($v);
            //查询措施表
            $res = Db::table('fa_work_order_measure')->where(['work_id' => $v['id']])->select();
            //查询订单号所有子单
            $order_list = $order->alias('a')->field('b.*')->where(['increment_id' => $v['platform_order'], 'site' => $v['work_platform']])
                ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
                ->select();

            foreach ($res as $k1 => $v1) {
                //插入措施表
                $id =  Db::table('fa_work_order_measure_copy1')->insertGetId($v1);
                //措施为取消
                if ($v1['measure_choose_id'] == 3) {
                    //查询change sku表
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id']])->find();

                    $change_sku_data = [];
                    foreach ($order_list as $key => $val) {
                        $change_sku_data[$key]['work_id'] = $v['work_id'];
                        $change_sku_data[$key]['increment_id'] = $change_sku_list['increment_id'];
                        $change_sku_data[$key]['platform_type'] = $change_sku_list['platform_type'];
                        $change_sku_data[$key]['original_name'] = $change_sku_list['original_name'];
                        $change_sku_data[$key]['original_sku'] = $val['original_sku'];
                        $change_sku_data[$key]['original_number'] = 1;
                        $change_sku_data[$key]['change_type'] = 3;
                        $change_sku_data[$key]['create_person'] = $change_sku_list['create_person'];
                        $change_sku_data[$key]['create_time'] = $change_sku_list['create_time'];
                        $change_sku_data[$key]['update_time'] = $change_sku_list['update_time'];
                        $change_sku_data[$key]['measure_id'] = $id;
                        $change_sku_data[$key]['item_order_number'] = $val['item_order_number'];
                    }
                    if ($change_sku_data) {
                        Db::table('fa_work_order_change_sku_copy1')->insertAll($change_sku_data);
                    }
                }

                //措施为更改镜框
                if ($v1['measure_choose_id'] == 1) {
                    //查询change sku表内容
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id']])->select();
                    foreach ($change_sku_list as $k2 => $v2) {
                        //查询订单号所有子单
                        $order_list = $order->alias('a')->field('b.item_order_number')
                            ->where(['a.increment_id' => $v2['platform_order'], 'a.site' => $v2['platform_type'], 'b.sku' => $v2['sku']])
                            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
                            ->select();
                        $measure = [];
                        $change_sku_data = [];
                        foreach ($order_list as $k3 => $v3) {
                            $measure['work_id'] = $v['id'];
                            $measure['measure_choose_id'] = 19;
                            $measure['measure_content'] = '更改镜框';
                            $measure['create_time'] = $v1['create_time'];
                            $measure['operation_type'] = $v1['operation_type'];
                            $measure['operation_time'] = $v1['operation_time'];
                            $measure['sku_change_type'] = $v1['sku_change_type'];
                            $measure['item_order_number'] = $v3['item_order_number'];
                            $id = Db::table('fa_work_order_measure_copy1')->insertGetId($measure);
                            $change_sku_data[$key]['work_id'] = $v['work_id'];
                            $change_sku_data[$key]['increment_id'] = $v2['increment_id'];
                            $change_sku_data[$key]['platform_type'] = $v2['platform_type'];
                            $change_sku_data[$key]['original_name'] = $v2['original_name'];
                            $change_sku_data[$key]['original_sku'] = $v2['original_sku'];
                            $change_sku_data[$key]['original_number'] = 1;
                            $change_sku_data[$key]['change_type'] = 1;
                            $change_sku_data[$key]['change_sku'] = $v2['change_sku'];
                            $change_sku_data[$key]['change_number'] = 1;
                            $change_sku_data[$key]['create_person'] = $v2['create_person'];
                            $change_sku_data[$key]['create_time'] = $v2['create_time'];
                            $change_sku_data[$key]['update_time'] = $v2['update_time'];
                            $change_sku_data[$key]['measure_id'] = $id;
                            $change_sku_data[$key]['item_order_number'] = $v3['item_order_number'];
                        }

                        if ($change_sku_data) {
                            Db::table('fa_work_order_change_sku_copy1')->insertAll($change_sku_data);
                        }

                    }
                }


                //措施为更改镜片
                if ($v1['measure_choose_id'] == 12) {
                    //查询change sku表内容
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id']])->select();
                    foreach ($change_sku_list as $k2 => $v2) {
                        //查询订单号所有子单
                        $order_list = $order->alias('a')->field('b.item_order_number')
                            ->where(['a.increment_id' => $v2['platform_order'], 'a.site' => $v2['platform_type'], 'b.sku' => $v2['sku']])
                            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
                            ->select();
                        $measure = [];
                        $change_sku_data = [];
                        foreach ($order_list as $k3 => $v3) {
                            $measure['work_id'] = $v['id'];
                            $measure['measure_choose_id'] = 20;
                            $measure['measure_content'] = '更改镜片';
                            $measure['create_time'] = $v1['create_time'];
                            $measure['operation_type'] = $v1['operation_type'];
                            $measure['operation_time'] = $v1['operation_time'];
                            $measure['sku_change_type'] = $v1['sku_change_type'];
                            $measure['item_order_number'] = $v3['item_order_number'];
                            $id = Db::table('fa_work_order_measure_copy1')->insertGetId($measure);
                            $change_sku_data[$key]['work_id'] = $v['work_id'];
                            $change_sku_data[$key]['increment_id'] = $v2['increment_id'];
                            $change_sku_data[$key]['platform_type'] = $v2['platform_type'];
                            $change_sku_data[$key]['original_name'] = $v2['original_name'];
                            $change_sku_data[$key]['original_sku'] = $v2['original_sku'];
                            $change_sku_data[$key]['original_number'] = 1;
                            $change_sku_data[$key]['change_type'] = 1;
                            $change_sku_data[$key]['change_sku'] = $v2['change_sku'];
                            $change_sku_data[$key]['change_number'] = 1;
                            $change_sku_data[$key]['create_person'] = $v2['create_person'];
                            $change_sku_data[$key]['create_time'] = $v2['create_time'];
                            $change_sku_data[$key]['update_time'] = $v2['update_time'];
                            $change_sku_data[$key]['measure_id'] = $id;
                            $change_sku_data[$key]['item_order_number'] = $v3['item_order_number'];
                        }

                        if ($change_sku_data) {
                            Db::table('fa_work_order_change_sku_copy1')->insertAll($change_sku_data);
                        }

                    }
                }

                //插入措施表

                //查询change sku表 

                //插入sku表


            }
        }
    }
}
