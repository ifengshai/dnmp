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
    protected $str30 = 'Out for delivery or arrived at local facility, you may schedule for delivery or pickup. Please be aware of the collection deadline.';//到达待取
    protected $str35 = 'Attempted for delivery but failed, this may due to several reasons. Please contact the carrier for clarification.';//投递失败
    protected $str40 = 'Delivered successfully.';//投递成功
    protected $str50 = 'Item might undergo unusual shipping condition, this may due to several reasons, most likely item was returned to sender, customs issue etc.';//可能异常

    public function _initialize()
    {
        parent::_initialize();
    }
    /*
     * 17track物流查询webhook访问方法
     * */
    public function track_return(){
        $track_info = file_get_contents("php://input");
        $track_arr = json_decode($track_info,true);
        $verify_sign = $track_arr['event'].'/'.json_encode($track_arr['data']).'/'.$this->apiKey;
        $verify_sign = hash("sha256",$verify_sign);
        if($verify_sign == $track_arr['sign']){
            if($track_arr['event'] != 'TRACKING_STOPPED'){
                file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt',$track_info."\r\n",FILE_APPEND);
                $order_node = Db::name('order_node')->field('site,order_id,order_number,shipment_type')->where('track_number',$track_arr['data']['number'])->find();
                $add['site'] = $order_node['site'];
                $add['order_id'] = $order_node['order_id'];
                $add['order_number'] = $order_node['order_number'];
                $add['shipment_type'] = $order_node['shipment_type'];
                $add['track_number'] = $track_arr['data']['number'];
    
                if (stripos($order_node['shipment_type'], 'Post') !== false) {
                    $this->china_post_data($track_arr['data']['track'], $add);
                }
    
                if (stripos($order_node['shipment_type'], 'DHL') !== false) {
                    $this->dhl_data($track_arr['data']['track'], $add);
                }
    
                if (stripos($order_node['shipment_type'], 'yanwen') !== false) {
                    $this->yanwen_data($track_arr['data']['track'], $add);
                }
    
                if (stripos($order_node['shipment_type'], 'USPS') !== false) {
                    $this->usps_data($track_arr['data']['track'], $add);
                }
    
                if (stripos($order_node['shipment_type'], 'fede') !== false) {
                    $this->fedex_data($track_arr['data']['track'], $add);
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
        //注册和推送之间的物流差别处理，暂时使用start
        $already_count = Db::name('order_node_courier')->where('track_number', $add['track_number'])->count();
        $cha_count = $all_num-$already_count;
        if($cha_count == 1){
            $trackdetail = array();
            $trackdetail = array('0'=>$data['z0']);
        }elseif($cha_count < 1){
            $trackdetail = array();
        }else{
            //删除原来的数据
            Db::name('order_node_courier')->where('track_number', $add['track_number'])->delete();
        }
        //注册和推送之间的物流差别处理，暂时使用end
        if(!empty($trackdetail)){
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                Db::name('order_node_courier')->insert($add); //插入物流日志表
    
                $order_node_detail['order_node'] = 3;
                $order_node_detail['create_time'] = $v['a'];
    
                $order_node_detail['handle_user_id'] = 0;
                $order_node_detail['handle_user_name'] = 'system';
                $order_node_detail['site'] = $add['site'];
                $order_node_detail['order_id'] = $add['order_id'];
                $order_node_detail['order_number'] = $add['order_number'];
                $order_node_detail['shipment_type'] = $add['shipment_type'];
                $order_node_detail['track_number'] = $add['track_number'];
    
                if (stripos($v['z'], 'Shipment information sent to FedEx') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
    
                if (stripos($v['z'], 'In transit') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                            //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        } else {
                            $update_order_node['node_type'] = 9;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                        }
                    }
                }
    
                if (stripos($v['z'], 'Arrived at FedEx location') !== false || stripos($v['z'], 'Departed FedEx location') !== false || stripos($v['z'], 'clearance') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        $time = '';
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
    
                if ($all_num - 1 == $k) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                        if(in_array($data['e'],[30,35,40,50])){
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            $update_order_node['signing_time'] = $v['a'];//更新签收时间
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

    //usps
    public function usps_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);
         //注册和推送之间的物流差别处理，暂时使用start
         $already_count = Db::name('order_node_courier')->where('track_number', $add['track_number'])->count();
         $cha_count = $all_num-$already_count;
         if($cha_count == 1){
             $trackdetail = array();
             $trackdetail = array('0'=>$data['z0']);
         }elseif($cha_count < 1){
             $trackdetail = array();
         }else{
             //删除原来的数据
             Db::name('order_node_courier')->where('track_number', $add['track_number'])->delete();
         }
         //注册和推送之间的物流差别处理，暂时使用end
         if(!empty($trackdetail)){
            $track_num = substr($add['track_number'] , 0 , 10);
            if($data['e'] == 40  || $data['e'] == 30 ||  $data['e'] == 35 ){
                //做假数据。
                foreach ($trackdetail as $k => $v) {
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'],'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                    if ($count < 1) {
                        Db::name('order_node_courier')->insert($add); //插入物流日志表
                    }
                    
                    $order_node_detail['order_node'] = 3;
    
                    $order_node_detail['handle_user_id'] = 0;
                    $order_node_detail['handle_user_name'] = 'system';
                    $order_node_detail['site'] = $add['site'];
                    $order_node_detail['order_id'] = $add['order_id'];
                    $order_node_detail['order_number'] = $add['order_number'];
                    $order_node_detail['shipment_type'] = $add['shipment_type'];
                    $order_node_detail['track_number'] = $add['track_number'];
    
                    if (stripos($v['z'], 'Picked Up') !== false || stripos($v['z'], 'Shipping Partner Facility') !== false || stripos($v['z'], 'Shipping Label Created') !== false) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                        if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                            $update_order_node['order_node'] = 3;
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                            $order_node_detail['node_type'] = 8;
                            $order_node_detail['content'] = $this->str1;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " +1 day")));
    
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            $time = date('Y-m-d H:i', strtotime(($time . " +5 day")));
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        
    
                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                            $time = date('Y-m-d H:i', strtotime(($order_node_date['update_time'] . " +1 day")));
    
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            $time = date('Y-m-d H:i', strtotime(($time . " +5 day")));
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $update_order_node['order_node'] = 3;
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $time;
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        }
    
                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                            $time = date('Y-m-d H:i', strtotime(($order_node_date['update_time'] . " +5 day")));
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $update_order_node['order_node'] = 3;
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $time;
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        }
                    }
    
                
                    if (stripos($v['z'], 'Accepted at USPS Origin Facility') !== false || stripos($v['z'], 'Arrived at Post Office') !== false || stripos($v['z'], 'Arrived at') !== false || stripos($v['z'], 'Accepted at') !== false || stripos($v['z'], 'Arrived in the Final Destination Country') !== false) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                            $update_order_node['node_type'] = 11;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
        
                            $order_node_detail['node_type'] = 11;
                            $order_node_detail['content'] = $this->str4;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
    
                    if ($all_num - 1 == $k) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                            if($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50 ){
                                $update_order_node['order_node'] = 4;
                                $update_order_node['node_type'] = $data['e'];
                                $update_order_node['update_time'] = $v['a'];
                                $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
            }else{
                if($track_num == '9200190255' || $track_num == '9205990255'){
                    //郭伟峰
                    foreach ($trackdetail as $k => $v) {
                        $add['create_time'] = $v['a'];
                        $add['content'] = $v['z'];
                        $add['courier_status'] = $data['e'];
                        $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'],'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                        if ($count < 1) {
                            Db::name('order_node_courier')->insert($add); //插入物流日志表
                        }
                        
                        $order_node_detail['order_node'] = 3;
    
                        $order_node_detail['handle_user_id'] = 0;
                        $order_node_detail['handle_user_name'] = 'system';
                        $order_node_detail['site'] = $add['site'];
                        $order_node_detail['order_id'] = $add['order_id'];
                        $order_node_detail['order_number'] = $add['order_number'];
                        $order_node_detail['shipment_type'] = $add['shipment_type'];
                        $order_node_detail['track_number'] = $add['track_number'];
    
                        if (stripos($v['z'], 'Picked Up') !== false || stripos($v['z'], 'Shipping Partner Facility') !== false) {
                            $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
    
                        if (stripos($v['z'], 'Departed Shipping Partner Facility') !== false) {
                            $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                            if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                                $update_order_node['node_type'] = 9;
                                $update_order_node['update_time'] = $v['a'];
                                Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                                $order_node_detail['node_type'] = 9;
                                $order_node_detail['content'] = $this->str2;
                                $order_node_detail['create_time'] = $v['a'];
                                Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                $time = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                            }
                        }
    
                        if (stripos($v['z'], 'Arrived at') !== false || stripos($v['z'], 'Accepted at') !== false) {
                            $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                            if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                                $update_order_node['node_type'] = 11;
                                $update_order_node['update_time'] = $v['a'];
                                Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
            
                                $order_node_detail['node_type'] = 10;
                                $order_node_detail['content'] = $this->str3;
                                $order_node_detail['create_time'] = $time;
                                Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                $time = '';
            
                                $order_node_detail['node_type'] = 11;
                                $order_node_detail['content'] = $this->str4;
                                $order_node_detail['create_time'] = $v['a'];
                                Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            }
                        }
    
                        if ($all_num - 1 == $k) {
                            $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                            if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                                if($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50 ){
                                    $update_order_node['order_node'] = 4;
                                    $update_order_node['node_type'] = $data['e'];
                                    $update_order_node['update_time'] = $v['a'];
                                    $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
                }else{
                    $track_num = substr($track_num , 0 , 4);
                    if($track_num == '9400'){
                        //加诺
                        $detail_num = 0;//判断是否该到达目的国
                        foreach ($trackdetail as $k => $v) {
                            $add['create_time'] = $v['a'];
                            $add['content'] = $v['z'];
                            $add['courier_status'] = $data['e'];
                            $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'],'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                            if ($count < 1) {
                                Db::name('order_node_courier')->insert($add); //插入物流日志表
                            }
                            
                            $order_node_detail['order_node'] = 3;
        
                            $order_node_detail['handle_user_id'] = 0;
                            $order_node_detail['handle_user_name'] = 'system';
                            $order_node_detail['site'] = $add['site'];
                            $order_node_detail['order_id'] = $add['order_id'];
                            $order_node_detail['order_number'] = $add['order_number'];
                            $order_node_detail['shipment_type'] = $add['shipment_type'];
                            $order_node_detail['track_number'] = $add['track_number'];
        
                            if (stripos($v['z'], 'Shipping Label Created') !== false) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
        
                            if (stripos($v['z'], 'Accepted at USPS Origin Facility') !== false && $detail_num == 0) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                                    $update_order_node['node_type'] = 9;
                                    $update_order_node['update_time'] = $v['a'];
                                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                                    $order_node_detail['node_type'] = 9;
                                    $order_node_detail['content'] = $this->str2;
                                    $order_node_detail['create_time'] = $v['a'];
                                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                    $detail_num = 1;
                                    $time = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                                }
                            }
        
                            if ((stripos($v['z'], 'Accepted at USPS Origin Facility') !== false || stripos($v['z'], 'Arrived at Post Office') !== false) && $detail_num == 1) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                                    $update_order_node['node_type'] = 11;
                                    $update_order_node['update_time'] = $v['a'];
                                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                
                                    $order_node_detail['node_type'] = 10;
                                    $order_node_detail['content'] = $this->str3;
                                    $order_node_detail['create_time'] = $time;
                                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                    $time = '';
                
                                    $order_node_detail['node_type'] = 11;
                                    $order_node_detail['content'] = $this->str4;
                                    $order_node_detail['create_time'] = $v['a'];
                                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                }
                            }
        
                            if ($all_num - 1 == $k) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                                    if($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50 ){
                                        $update_order_node['order_node'] = 4;
                                        $update_order_node['node_type'] = $data['e'];
                                        $update_order_node['update_time'] = $v['a'];
                                        $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
                    }else{
                        //杜明明
                        foreach ($trackdetail as $k => $v) {
                            $add['create_time'] = $v['a'];
                            $add['content'] = $v['z'];
                            $add['courier_status'] = $data['e'];
                            $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'],'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                            if ($count < 1) {
                                Db::name('order_node_courier')->insert($add); //插入物流日志表
                            }
                
                            $order_node_detail['order_node'] = 3;
                            $order_node_detail['create_time'] = $v['a'];
                
                            $order_node_detail['handle_user_id'] = 0;
                            $order_node_detail['handle_user_name'] = 'system';
                            $order_node_detail['site'] = $add['site'];
                            $order_node_detail['order_id'] = $add['order_id'];
                            $order_node_detail['order_number'] = $add['order_number'];
                            $order_node_detail['shipment_type'] = $add['shipment_type'];
                            $order_node_detail['track_number'] = $add['track_number'];
                
                            if (stripos($v['z'], 'Picked Up') !== false || stripos($v['z'], 'Shipping Partner Facility') !== false) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
                
                            if (stripos($v['z'], 'Delivered to Air Transport') !== false) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                                    $update_order_node['node_type'] = 9;
                                    $update_order_node['update_time'] = $v['a'];
                                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                                    $order_node_detail['node_type'] = 9;
                                    $order_node_detail['content'] = $this->str2;
                                    $order_node_detail['create_time'] = $v['a'];
                                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                    $time = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                                }
                            }
    
                            if (stripos($v['z'], 'Arrived in the Final Destination Country') !== false) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                                    $update_order_node['node_type'] = 11;
                                    $update_order_node['update_time'] = $v['a'];
                                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                                    $order_node_detail['node_type'] = 10;
                                    $order_node_detail['content'] = $this->str3;
                                    $order_node_detail['create_time'] = $time;
                                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                
                                    $order_node_detail['node_type'] = 11;
                                    $order_node_detail['content'] = $this->str4;
                                    $order_node_detail['create_time'] = $v['a'];
                                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                                }
                            }
                
                            if ($all_num - 1 == $k) {
                                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                                    if($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50 ){
                                        $update_order_node['order_node'] = 4;
                                        $update_order_node['node_type'] = $data['e'];
                                        $update_order_node['update_time'] = $v['a'];
                                        $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
            }
         }
    }
    //燕文专线 匹配规则有问题
    public function yanwen_data($data,$add){
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        //注册和推送之间的物流差别处理，暂时使用start
        $already_count = Db::name('order_node_courier')->where('track_number', $add['track_number'])->count();
        $cha_count = $all_num-$already_count;
        if($cha_count == 1){
            $trackdetail = array();
            $trackdetail = array('0'=>$data['z0']);
        }elseif($cha_count < 1){
            $trackdetail = array();
        }else{
            //删除原来的数据
            Db::name('order_node_courier')->where('track_number', $add['track_number'])->delete();
        }
        //注册和推送之间的物流差别处理，暂时使用end
        if(!empty($trackdetail)){
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                Db::name('order_node_courier')->insert($add); //插入物流日志表
    
                $order_node_detail['order_node'] = 3;
                $order_node_detail['create_time'] = $v['a'];
    
                $order_node_detail['handle_user_id'] = 0;
                $order_node_detail['handle_user_name'] = 'system';
                $order_node_detail['site'] = $add['site'];
                $order_node_detail['order_id'] = $add['order_id'];
                $order_node_detail['order_number'] = $add['order_number'];
                $order_node_detail['shipment_type'] = $add['shipment_type'];
                $order_node_detail['track_number'] = $add['track_number'];
    
                if (stripos($v['z'], 'Picked up') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
    
                if (stripos($v['z'], 'Last mile') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                            //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        } else {
                            $update_order_node['node_type'] = 9;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                        }
                    }
                }
    
                if (stripos($v['z'], 'Shipping information received by') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        $time = '';
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
    
                if ($all_num - 1 == $k) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                        if(in_array($data['e'],[30,35,40,50])){
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
    //DHL 匹配规则有问题
    public function dhl_data($data,$add){
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        //注册和推送之间的物流差别处理，暂时使用start
        $already_count = Db::name('order_node_courier')->where('track_number', $add['track_number'])->count();
        $cha_count = $all_num-$already_count;
        if($cha_count == 1){
            $trackdetail = array();
            $trackdetail = array('0'=>$data['z0']);
        }elseif($cha_count < 1){
            $trackdetail = array();
        }else{
            //删除原来的数据
            Db::name('order_node_courier')->where('track_number', $add['track_number'])->delete();
        }
        //注册和推送之间的物流差别处理，暂时使用end
        if(!empty($trackdetail)){
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                Db::name('order_node_courier')->insert($add); //插入物流日志表
    
                $order_node_detail['order_node'] = 3;
                $order_node_detail['create_time'] = $v['a'];
    
                $order_node_detail['handle_user_id'] = 0;
                $order_node_detail['handle_user_name'] = 'system';
                $order_node_detail['site'] = $add['site'];
                $order_node_detail['order_id'] = $add['order_id'];
                $order_node_detail['order_number'] = $add['order_number'];
                $order_node_detail['shipment_type'] = $add['shipment_type'];
                $order_node_detail['track_number'] = $add['track_number'];
    
                if ($data['e'] != 0) {
                    if ($k == 1) { //第二条作为上网
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
                }
    
    
                if (stripos($v['z'], 'Departed Facility') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                            //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        } else {
                            $update_order_node['node_type'] = 9;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                        }
                    }
                }
    
                if (stripos($v['z'], 'Customs status updated') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        $time = '';
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
    
                if ($all_num - 1 == $k) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                        if(in_array($data['e'],[30,35,40,50])){
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
    //E邮宝
    public function china_post_data($data,$add){
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        //注册和推送之间的物流差别处理，暂时使用start
        $already_count = Db::name('order_node_courier')->where('track_number', $add['track_number'])->count();
        $cha_count = $all_num-$already_count;
        if($cha_count == 1){
            $trackdetail = array();
            $trackdetail = array('0'=>$data['z0']);
        }elseif($cha_count < 1){
            $trackdetail = array();
        }else{
            //删除原来的数据
            Db::name('order_node_courier')->where('track_number', $add['track_number'])->delete();
        }
        //注册和推送之间的物流差别处理，暂时使用end
        if(!empty($trackdetail)){
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                Db::name('order_node_courier')->insert($add); //插入物流日志表
    
                $order_node_detail['order_node'] = 3;
                $order_node_detail['create_time'] = $v['a'];
    
                $order_node_detail['handle_user_id'] = 0;
                $order_node_detail['handle_user_name'] = 'system';
                $order_node_detail['site'] = $add['site'];
                $order_node_detail['order_id'] = $add['order_id'];
                $order_node_detail['order_number'] = $add['order_number'];
                $order_node_detail['shipment_type'] = $add['shipment_type'];
                $order_node_detail['track_number'] = $add['track_number'];
    
                if (stripos($v['z'], '已收件，揽投员') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
    
                if (stripos($v['z'], '已交航空公司运输') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                            //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                            $update_order_node['node_type'] = 10;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $order_node_detail['node_type'] = 10;
                            $order_node_detail['content'] = $this->str3;
                            $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +7 day")));
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        } else {
                            $update_order_node['node_type'] = 9;
                            $update_order_node['update_time'] = $v['a'];
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            $order_node_detail['node_type'] = 9;
                            $order_node_detail['content'] = $this->str2;
                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
    
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " +7 day")));
                        }
                    }
                }
    
                if (stripos($v['z'], '已到达寄达地') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $time;
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        $time = '';
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
    
                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
    
                if ($all_num - 1 == $k) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                        if(in_array($data['e'],[30,35,40,50])){
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            $update_order_node['signing_time'] = $v['a'];//更新签收时间
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
}
