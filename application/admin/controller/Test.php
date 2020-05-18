<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;

class Test extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';

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
    }

    /**
     * 批量 获取物流明细
     * 莫删除
     */
    public function track_shipment_num(){
        $order_shipment = Db::connect('database.db_zeelool')
            ->table('sales_flat_shipment_track')
            ->field('entity_id,track_number,title,updated_at,order_id')
            ->where('created_at','>=','2020-04-10 00:00:00')
            ->limit(10)
            ->select();

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach($order_shipment as $k => $v){
            $order_num = Db::connect('database.db_zeelool')
                ->table('sales_flat_order')
                ->field('increment_id')
                ->where('entity_id','=',$v['order_id'])
                ->find();

            $title = strtolower(str_replace(' ', '-', $v['title']));

            $carrier = $this->getCarrier($title);

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                //'number' => $v['track_number'],
                //'carrier' => $carrier['carrierId']
                'number' => 'LO546092713CN',
                'carrier' => '03011'
            ]]);


            $add['site'] = 1;
            $add['order_id'] = $v['order_id'];
            $add['order_number'] = $order_num['increment_id'];
            $add['shipment_type'] = $v['title'];
            $add['track_number'] = $v['track_number'];


            if($trackInfo['code'] == 0 && $trackInfo['data']['accepted']){
                $trackdata = $trackInfo['data']['accepted'][0]['track'];
                if($v['title'] == 'USPS'){
                    $data = $this->china_post_data($trackdata,$add);
                }





            }

            dump($add);
            dump($trackdetail);
            dump($trackdata);
            exit;

        }
    }
    public function china_post_data($data,$add){
        $trackdetail = array_reverse($data['z1']);

        foreach ($trackdetail as $k => $v){
            $add['create_time'] = $v['a'];
            $add['content'] = $v['z'];
            $add['courier_status'] = $data['e'];
            //Db::name('order_node_courier')->insert($add);//插入日志表

            $courier['order_node'] = 3;
            $courier['create_time'] = $v['a'];

            $courier['handle_user_id'] = 0;
            $courier['handle_user_name'] = 'system';
            $courier['site'] = $add['site'];
            $courier['order_id'] = $add['order_id'];
            $courier['order_number'] = $add['order_number'];
            $courier['shipment_type'] = $add['shipment_type'];
            $courier['track_number'] = $add['track_number'];

            if (stripos($v['z'], '已收件，揽投员') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7){
                    $update_order_node['order_node'] = 3;
                    $update_order_node['node_type'] = 8;
                    $update_order_node['update_time'] = time();
                }
                dump($order_node_date);

                $courier['node_type'] = 8;
                $courier['content'] = '上网';
                $courier['create_time'] = $v['a'];
            }

            if (stripos($v['z'], '已交航空公司运输') !== false) {
                $courier['node_type'] = 9;
                $courier['content'] = '交航';
                $courier['create_time'] = $v['a'];


                $courier['node_type'] = 10;
                $courier['content'] = '运输中';
                $courier['create_time'] = date('Y-m-d H:i',strtotime(($v['a']." +7 day")));
            }

            if (stripos($v['z'], '已到达寄达地') !== false) {
                $courier['node_type'] = 11;
                $courier['content'] = '到达目的地';
                $courier['create_time'] = $v['a'];
            }


        }

        dump($add);
        dump($data);
        exit;

    }


    /**
     * 批量 注册物流
     * 莫删除
     */
    public function reg_shipment()
    {
        $order_shipment = Db::connect('database.db_nihao')
            ->table('sales_flat_shipment_track')
            ->field('entity_id,track_number,title,updated_at')
            ->where('created_at', '>=', '2020-04-10 00:00:00')
            ->select();

        foreach ($order_shipment as $k => $v) {
            $title = strtolower(str_replace(' ', '-', $v['title']));
            if ($title == 'china-post') {
                $order_shipment[$k]['title'] = 'china-ems';
            }
            $carrier = $this->getCarrier($v['title']);
            $shipment_reg[$k]['number'] =  $v['track_number'];
            $shipment_reg[$k]['carrier'] =  $carrier['carrierId'];
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        foreach ($order_group as $key => $val) {
            $aa = $trackingConnector->registerMulti($val);
            sleep(1);
            echo $key . "\n";
        }
        dump($order_group[$key]);
        echo 'all is ok' . "\n";
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
     * 获取订单节点数据
     *
     * @Description
     * @author wpl
     * @since 2020/05/14 09:55:00 
     * @return void
     */
    public function setOrderNoteData()
    {
        $users = $this->user->column('id', 'nickname');
        $field = 'status,custom_print_label_new,custom_print_label_person_new,custom_print_label_created_at_new,custom_is_match_frame_new,custom_match_frame_person_new,
        custom_match_frame_created_at_new,custom_is_match_lens_new,custom_match_lens_created_at_new,custom_match_lens_person_new,custom_is_send_factory_new,
        custom_match_factory_person_new,custom_match_factory_created_at_new,custom_is_delivery_new,custom_match_delivery_person_new,custom_match_delivery_created_at_new,
        custom_order_prescription_type,a.created_at,a.updated_at,b.track_number,b.created_at as create_time,b.title,a.entity_id,a.increment_id,a.custom_order_prescription_type
        ';
        $map['a.created_at'] = ['>=', '2020-01-01 00:00:00'];
        // $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
        $zeelool_data = $this->zeelool->alias('a')->field($field)
            ->join(['sales_flat_shipment_track' => 'b'], 'a.entity_id=b.order_id', 'left')
            ->where($map)->limit(100)->select();


        foreach ($zeelool_data as $v) {
            $list = [];
            $k = 0;
            //下单
            $list[$k]['order_node'] = 0;
            $list[$k]['node_type'] = 0;
            $list[$k]['content'] = 'Your order has been created.';
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = 1;
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
                $list[$k + 1]['site'] = 1;
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
            $data['site'] = 1;
            $data['order_id'] = $v['entity_id'];
            $data['order_number'] = $v['increment_id'];
            $data['update_time'] = $v['created_at'];
            //打标签
            if ($v['custom_print_label_new'] == 1) {
                $list[$k + 2]['order_node'] = 1;
                $list[$k + 2]['node_type'] = 2;
                $list[$k + 2]['content'] = 'Order is under processing';
                $list[$k + 2]['create_time'] = $v['custom_print_label_created_at_new'];
                $list[$k + 2]['site'] = 1;
                $list[$k + 2]['order_id'] = $v['entity_id'];
                $list[$k + 2]['order_number'] = $v['increment_id'];
                $list[$k + 2]['handle_user_id'] = $users[$v['custom_print_label_person_new']];
                $list[$k + 2]['handle_user_name'] = $v['custom_print_label_person_new'];
                $list[$k + 2]['shipment_type'] = '';
                $list[$k + 2]['track_number'] = '';

                $data['order_node'] = 1;
                $data['node_type'] = 2;
                $data['update_time'] = $v['custom_print_label_created_at_new'];
            }

            //判断订单是否为仅镜架
            if ($v['custom_order_prescription_type'] == 1) {
                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3;
                    $list[$k + 3]['content'] = 'The product(s) is/are ready, waiting for Quality Inspection';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 1;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 6;
                    $list[$k + 4]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 4]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 4]['site'] = 1;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 7; //出库
                    $list[$k + 5]['content']  = '';
                    $list[$k + 5]['create_time'] = $v['create_time'];
                    $list[$k + 5]['site'] = 1;
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
        
                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3; //配镜架
                    $list[$k + 3]['content'] = 'Frame(s) is/are ready, waiting for lenses';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 1;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_match_lens_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 4; //配镜片
                    $list[$k + 4]['content'] = 'Lenses production completed, waiting for customizing';
                    $list[$k + 4]['create_time'] = $v['custom_match_lens_created_at_new'];
                    $list[$k + 4]['site'] = 1;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_lens_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_lens_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 4;
                    $data['update_time'] = $v['custom_match_lens_created_at_new'];
                }

                if ($v['custom_is_send_factory_new'] == 1) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 5; //加工
                    $list[$k + 5]['content'] = 'Customizing completed, waiting for Quality Inspection';
                    $list[$k + 5]['create_time'] = $v['custom_match_factory_created_at_new'];
                    $list[$k + 5]['site'] = 1;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['handle_user_id'] = $users[$v['custom_match_factory_person_new']];
                    $list[$k + 5]['handle_user_name'] = $v['custom_match_factory_person_new'];
                    $list[$k + 5]['shipment_type'] = '';
                    $list[$k + 5]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 5;
                    $data['update_time'] = $v['custom_match_factory_created_at_new'];
                }


                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 6]['order_node'] = 2;
                    $list[$k + 6]['node_type'] = 6; //质检
                    $list[$k + 6]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 6]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 6]['site'] = 1;
                    $list[$k + 6]['order_id'] = $v['entity_id'];
                    $list[$k + 6]['order_number'] = $v['increment_id'];
                    $list[$k + 6]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 6]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 6]['shipment_type'] = '';
                    $list[$k + 6]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 7]['order_node'] = 2;
                    $list[$k + 7]['node_type'] = 7; //出库
                    $list[$k + 7]['create_time'] = $v['create_time'];
                    $list[$k + 7]['site'] = 1;
                    $list[$k + 7]['order_id'] = $v['entity_id'];
                    $list[$k + 7]['order_number'] = $v['increment_id'];
                    $list[$k + 7]['shipment_type'] = $v['title'];
                    $list[$k + 7]['track_number'] = $v['track_number'];
                    $list[$k + 7]['handle_user_id'] = 0;
                    $list[$k + 7]['handle_user_name'] = '';
                    $list[$k + 7]['content'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            }
            $data['shipment_type'] = $v['title'];
            $data['track_number'] = $v['track_number'];


            $count = Db::name('order_node')->where('order_id', $v['entity_id'])->count();
            if ($count > 0) {
                Db::name('order_node')->where('order_id', $v['entity_id'])->update($data);
            } else {
                Db::name('order_node')->insert($data);
            }
            $res = $this->ordernodedetail->saveAll($list);
            dump($res);
        }
    }
}
