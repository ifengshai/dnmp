<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class OrderPrescription extends Backend
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //处方信息
        $time_str = '2019-11-24 05:58:59 - 2019-11-27 16:58:59';
        $prescrition = $this->prescrtion_data(1,$time_str);
        $data = $prescrition['data'];
        $total = $prescrition['total'];
        $coating = $this->coating_data();
        $this->view->assign(compact('data', 'total', 'coating'));
        return $this->view->fetch();
    }
    //处方统计
    function prescrtion_data($site = 1,$time_str = ''){
        if(!$time_str){
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' 00:00:00 - ' .$end.' 00:00:00';
        }
        $createat = explode(' ', $time_str);
        $order_num_where['day_date'] = ['between', [$createat[0], $createat[3]]];
        $order_num = Db::name('datacenter_day')->where($order_num_where)->sum('order_num');
        $single_vision_num = $this->prescrtion_num('SingleVision',$site,$time_str);
        $single_vision_rate = $order_num ? round($single_vision_num/$order_num*100,0).'%' : 0;
        $single_vision_arr = array(
            'name'=>'single vision',
            'num'=>$single_vision_num,
            'rate'=>$single_vision_rate
        );
        $progressive_num = $this->prescrtion_num('Progressive',$site,$time_str);
        $progressive_rate = $order_num ? round($progressive_num/$order_num*100,0).'%' : 0;
        $progressive_arr = array(
            'name'=>'progressive',
            'num'=>$progressive_num,
            'rate'=>$progressive_rate
        );
        $reading_glasses_num = $this->prescrtion_num('Readingglasses',$site,$time_str);
        $reading_glasses_rate = $order_num ? round($reading_glasses_num/$order_num*100,0).'%' : 0;
        $reading_glasses_arr = array(
            'name'=>'reading glasses',
            'num'=>$reading_glasses_num,
            'rate'=>$reading_glasses_rate
        );
        $reading_glassesno_num = $this->prescrtion_num('ReadingGlassesNon',$site,$time_str);
        $reading_glassesno_rate = $order_num ? round($reading_glassesno_num/$order_num*100,0).'%' : 0;
        $reading_glassesno_arr = array(
            'name'=>'reading glasses no prescription',
            'num'=>$reading_glassesno_num,
            'rate'=>$reading_glassesno_rate
        );
        $no_prescription_num = $this->prescrtion_num('NonPrescription',$site,$time_str);
        $no_prescription_rate = $order_num ? round($no_prescription_num/$order_num*100,0).'%' : 0;
        $no_prescription_arr = array(
            'name'=>'no prescription',
            'num'=>$no_prescription_num,
            'rate'=>$no_prescription_rate
        );
        $sunglasses_num = $this->prescrtion_num('Sunglasses',$site,$time_str);
        $sunglasses_rate = $order_num ? round($sunglasses_num/$order_num*100,0).'%' : 0;
        $sunglasses_arr = array(
            'name'=>'sunglasses',
            'num'=>$sunglasses_num,
            'rate'=>$sunglasses_rate
        );
        $sunglassesno_num = $this->prescrtion_num('SunGlassesNoprescription',$site,$time_str);
        $sunglassesno_rate = $order_num ? round($sunglassesno_num/$order_num*100,0).'%' : 0;
        $sunglassesno_arr = array(
            'name'=>'sunglasses non-prescription',
            'num'=>$sunglassesno_num,
            'rate'=>$sunglassesno_rate
        );
        $sports_single_vision_num = $this->prescrtion_num('SportsSingleVision',$site,$time_str);
        $sports_single_vision_rate = $order_num ? round($sports_single_vision_num/$order_num*100,0).'%' : 0;
        $sports_single_vision_arr = array(
            'name'=>'sports single vision',
            'num'=>$sports_single_vision_num,
            'rate'=>$sports_single_vision_rate
        );
        $sports_progressive_num = $this->prescrtion_num('SportsProgressive',$site,$time_str);
        $sports_progressive_rate = $order_num ? round($sports_progressive_num/$order_num*100,0).'%' : 0;
        $sports_progressive_arr = array(
            'name'=>'sports progressive',
            'num'=>$sports_progressive_num,
            'rate'=>$sports_progressive_rate
        );
        $frame_only_num = $order_num-$single_vision_num-$progressive_num-$reading_glasses_num-$reading_glassesno_num-$no_prescription_num-$sunglasses_num-$sunglassesno_num-$sports_single_vision_num-$sports_progressive_num;
        $frame_only_rate = $order_num ? round($frame_only_num/$order_num*100,0).'%' : 0;
        $frame_only_arr = array(
            'name'=>'frame only',
            'num'=>$frame_only_num,
            'rate'=>$frame_only_rate
        );
        $arr = [$frame_only_arr,$single_vision_arr,$progressive_arr,$reading_glasses_arr,$reading_glassesno_arr,$no_prescription_arr,$sunglasses_arr,$sunglassesno_arr,$sports_single_vision_arr,$sports_progressive_arr];
        $result = array(
            'data'=>$arr,
            'total'=>$order_num
        );
        return $result;
    }
    function prescrtion_num($flag,$site,$time_str){
        if($site == 2){
            $order_model = Db::connect('database.db_voogueme');
        }elseif($site == 3){
            $order_model = Db::connect('database.db_nihao');
        }else{
            $order_model = Db::connect('database.db_zeelool');
        }
        $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        if(!$time_str){
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' 00:00:00 - ' .$end.' 00:00:00';
        }
        $createat = explode(' ', $time_str);
        $where['p.created_at'] = ['between', [$createat[0], $createat[3]]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $where['p.prescription_type'] = $flag;
        $count = $order_model->table('sales_flat_order_item_prescription')->alias('p')->join('sales_flat_order o','p.order_id=o.entity_id')->where($where)->count();
        return $count;
    }
    //镀膜
    public function coating_data($site = 1,$time_str = ''){
        if($site == 2){
            $order_model = Db::connect('database.db_voogueme');
        }elseif($site == 3){
            $order_model = Db::connect('database.db_nihao');
        }else{
            $order_model = Db::connect('database.db_zeelool');
        }
        $order_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        if(!$time_str){
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' 00:00:00 - ' .$end.' 00:00:00';
        }
        $createat = explode(' ', $time_str);
        $where['created_at'] = ['between', [$createat[0], $createat[3]]];
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $orderids = $order_model->table('sales_flat_order')->where($where)->column('entity_id');

    }


}
