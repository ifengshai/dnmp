<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;


/**
 * 会员接口
 */
class ThirdApi extends Api
{
    protected $noNeedLogin = '*';
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
    }
    /*
     * 17track物流查询webhook访问方法
     * */
    public function track_return()
    {
        $track_info = file_get_contents("php://input");
        $track_arr = json_decode($track_info, true);
        $verify_sign = $track_arr['event'] . '/' . json_encode($track_arr['data']) . '/' . $this->apiKey;
        $verify_sign = hash("sha256", $verify_sign);
//        file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt', $verify_sign . "\r\n", FILE_APPEND);
//        file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt', $track_arr['sign'] . "\r\n", FILE_APPEND);
//        file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt', $track_info . "\r\n", FILE_APPEND);
        // if($verify_sign == $track_arr['sign']){
        //妥投给maagento接口
        if ($track_arr['data']['track']['e'] ==40){
            $order_node = Db::name('order_node')->field('site,order_id,order_number,shipment_type,shipment_data_type')->where('track_number', $track_arr['data']['number'])->find();
            $url = config('url.zeelool_url').'magic/order/updateOrderStatus';
            $value['increment_id']  = $order_node['order_number'];
            $this->request_post($url,$value);
        }
        if ($track_arr['event'] != 'TRACKING_STOPPED') {
            // file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt',$track_info."\r\n",FILE_APPEND);
            $order_node = Db::name('order_node')->field('site,order_id,order_number,shipment_type,shipment_data_type')->where('track_number', $track_arr['data']['number'])->find();

            $add['site'] = $order_node['site'];
            $add['order_id'] = $order_node['order_id'];
            $add['order_number'] = $order_node['order_number'];
            $add['shipment_type'] = $order_node['shipment_type'];
            $add['shipment_data_type'] = $order_node['shipment_data_type'];
            $add['track_number'] = $track_arr['data']['number'];

            $this->total_track_data($track_arr['data']['track'], $add);

        }
        // }
    }

    public function test(){
        $url = 'https://z.zhaokuangyi.com/magic/order/updateOrderStatus';
        $order_node['order_number'] = '400324285';
        $value['increment_id'] =$order_node['order_number'];
        $data = $this->request_post($url,$value);
        dump($data);die();
    }


    /**
     * @author wgj
     * @Date 2020/10/21 14:48
     * @param $data
     * @param $add
     * order_node总物流total_track_data()
     */
    public function total_track_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);

        if (!empty($trackdetail)) {
            $order_node_detail['order_node'] = 3;
            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
            $order_node_detail['track_number'] = $add['track_number'];
            //获取物流明细表中的描述
            $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');
            foreach ($trackdetail as $k => $v) {
                if(!in_array($v['z'],$contents)){
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }
                if ($k == 1) {
                    //更新上网
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if($all_num - 1 == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //DHL
    public function dhl_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        
        if (!empty($trackdetail)) {
            $order_node_detail['order_node'] = 3;
            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
            $order_node_detail['track_number'] = $add['track_number'];
            //获取物流明细表中的描述
            $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');
            foreach ($trackdetail as $k => $v) {
                if(!in_array($v['z'],$contents)){
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }
                if ($k == 1) {
                    //更新上网
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if($all_num - 1 == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }
    //fedex
    public function fedex_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);
        if (!empty($trackdetail)) {
            $order_node_detail['order_node'] = 3;
            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            //获取物流明细表中的描述
            $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');
            foreach ($trackdetail as $k => $v) {
                if(!in_array($v['z'],$contents)){
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }
                
                if ($k == 1) {
                    //更新上网
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if($all_num -1  == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //usps1
    public function usps_1_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);
        if (!empty($trackdetail)) {
            $order_node_detail['order_node'] = 3;
            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            //获取物流明细表中的描述
            $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');
            foreach ($trackdetail as $k => $v) {
                if(!in_array($v['z'],$contents)){
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }
                
                if ($k == 1) {
                    //更新上网
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if($all_num - 1 == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //usps2
    public function usps_2_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $all_num = count($trackdetail);
        $time = '';
        if (!empty($trackdetail)) {
            $order_node_detail['order_node'] = 3;
            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            //根据出库时间，+1天后就是上网，再+1天就是运输中
            $where['track_number'] = $add['track_number'];
            $where['order_node'] = 2;
            $where['node_type'] = 7;
            $order_node_detail_time = Db::name('order_node_detail')->where($where)->field('create_time')->find();
            $time = date('Y-m-d H:i', strtotime(($order_node_detail_time['create_time'] . " +1 day")));

            //获取物流明细表中的描述
            $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');
            foreach ($trackdetail as $k => $v) {
                if(!in_array($v['z'],$contents)){
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }

                if ($data['e'] != 0) {
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $time = date('Y-m-d H:i', strtotime(($time . " +1 day")));
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $time;
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }

                    //运输中
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $time = date('Y-m-d H:i', strtotime(($time . " +1 day")));
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $time;
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if($all_num - 1 == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //usps_3杜明明&&燕文&&china post   通用
    public function tongyong_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);
        
        if (!empty($trackdetail)) {
            $order_node_detail['order_node'] = 3;
            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            //获取物流明细表中的描述
            $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');
            foreach ($trackdetail as $k => $v) {
                if(!in_array($v['z'],$contents)){
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }
                if ($k == 1) {
                    //更新上网
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                //结果
                if($all_num - 1 == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
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



    public function request_post($url = '', $post_data = array()) {
        if (empty($url) || empty($post_data)) {
            return false;
        }

        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);


        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }


}
