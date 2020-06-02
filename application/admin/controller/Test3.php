<?php

namespace app\admin\controller;

use app\admin\model\Elaticsearch;
use app\common\controller\Backend;
use think\Db;


class Test3 extends Backend{

    public function _initialize()
    {
        parent::_initialize();

       //$this->es = new Elaticsearch();
    }
    /**
     * id 订单号，物流商，运单号，当前节点状态，从上网到最终状态的时间有多久(如果大状态为4，则代表最终状态)
     *
     * @Description
     * @author mjj
     * @since 2020/06/02 10:11:53 
     * @return void
     */
    public function export_order_node(){
        //查询物流结点
        $where['d.order_node'] = 3;
        $where['d.node_type'] = 8;
        $where['d.create_time'] = ['between', ['2020-05-01', '2020-05-31']];
        $order = Db::name('order_node')->alias('o')->field('o.order_id,o.shipment_type,o.track_number,o.node_type,d.create_time')->where($where)->join(['fa_order_node_detail' => 'd'], 'o.order_id=d.order_id')->select();
        $arr = array();
        $i = 0;
        $file_content = '';
        foreach($order as $key=>$item){
            $arr[$i]['order_id'] = $item['order_id'];
            $arr[$i]['shipment_type'] = $item['shipment_type'];
            $arr[$i]['track_number'] = $item['track_number'];
            $arr[$i]['node_type'] = $item['node_type'];
            $arr[$i]['create_time'] = $item['create_time'];
            //查询是否有最终状态时间
            $where_detail['order_node'] = 4;
            $endtime = Db('order_node_detail')->where(['order_node'=>4])->value('create_time');
            if($endtime){
                $arr[$i]['create_time'] = $item['create_time'];
                $hour=floor((strtotime($endtime)-strtotime($item['create_time']))%86400/3600);
                $hour_num = $hour%24;
                $arr[$i]['day'] = floor($hour/24).'天'.$hour_num.'个小时';
            }else{
                $arr[$i]['create_time'] = '';
                $arr[$i]['day'] = 0;
            }
            $file_content = $file_content . implode(',',$arr[$i]);
            $file_content = $file_content . "\n";
            echo $i." is ok\n";
            $i++;
        }
        $export_str = array('订单号','物流商','运单号','当前节点状态','上网时间','最终状态时间','时间长短');
        $file_title = implode(',',$export_str) ." \n";
        $file = $file_title . $file_content ;
        file_put_contents('/www/wwwroot/mojing/li_exl.csv',$file);
        exit;
    }
}