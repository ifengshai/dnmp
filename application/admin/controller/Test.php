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

    
}
