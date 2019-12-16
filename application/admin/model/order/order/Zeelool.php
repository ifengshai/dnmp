<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class Zeelool extends Model
{



    //数据库
    protected $connection = 'database.db_zeelool';


    // 表名
    protected $table = 'sales_flat_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    
    //名称获取器
    public function getCustomerFirstnameAttr($value, $data)
    {
        return $data['customer_firstname'] . ' ' . $data['customer_lastname'];
    }

    /**
     * 获取订单地址详情
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getOrderDetail($ordertype, $entity_id)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            default:
                return false;
                break;
        }
        $map['address_type'] = 'shipping';
        $map['parent_id'] = $entity_id;
        $result = Db::connect($db)
            ->table('sales_flat_order_address')
            ->where($map)
            ->find();
        if (!$result) {
            return false;
        }
        return $result;
    }


    /**
     * 获取订单商品详情 多站公用方法
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getGoodsDetail($ordertype, $entity_id)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            default:
                return false;
                break;
        }
        $map['order_id'] = $entity_id;
        $result = Db::connect($db)
            ->field('sku,name,qty_ordered,custom_prescription,original_price,price,discount_amount,product_options')
            ->table('sales_flat_order_item')
            ->where($map)
            ->select();
        foreach ($result as $k => &$v) {
            $v['product_options'] = unserialize($v['product_options']);
            $prescription_params = $v['product_options']['info_buyRequest']['tmplens']['prescription'];
            $lens_params = array();
            //处理处方参数
            foreach (explode("&", $prescription_params) as $prescription_key => $prescription_value) {
                $arr_value = explode("=", $prescription_value);
                if ($arr_value[0]) {
                    $lens_params[$arr_value[0]] = $arr_value[1];
                }


                //处理ADD转换    
                if (@$lens_params['os_add'] && @$lens_params['od_add']) {
                    $lens_params['total_add'] = '';
                } else {
                    $lens_params['total_add'] = @$lens_params['os_add'];
                }
                //处理PD转换  
                if (@$lens_params['pdcheck'] == 'on') {
                    $lens_params['pd'] = '';
                }

                if (@$lens_params['prismcheck'] != 'on') {
                    $lens_params['od_pv'] = '';
                    $lens_params['od_bd'] = '';
                    $lens_params['od_pv_r'] = '';
                    $lens_params['od_bd_r'] = '';

                    $lens_params['os_pv'] = '';
                    $lens_params['os_bd'] = '';
                    $lens_params['os_pv_r'] = '';
                    $lens_params['os_bd_r'] = '';
                }
            }
            $v['product_options']['prescription'] = $lens_params;
            $v['product_options']['tmplens'] = $v['product_options']['info_buyRequest']['tmplens'];
        }
        unset($v);

        if (!$result) {
            return false;
        }
        return $result;
    }


    /**
     * 获取订单支付详情 多站公用方法
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getPayDetail($ordertype, $entity_id)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            default:
                return false;
                break;
        }
        $map['parent_id'] = $entity_id;
        $result = Db::connect($db)
            ->table('sales_flat_order_payment')
            ->field('additional_information,base_amount_paid,base_amount_ordered,base_shipping_amount,method,last_trans_id')
            ->where($map)
            ->find();

        $result['additional_information'] =  unserialize($result['additional_information']);

        if (!$result) {
            return false;
        }
        return $result;
    }

    /**
     * 获取物流单号
     */
    public function getExpressData($ordertype, $entity_id)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            default:
                return false;
                break;
        }
        $map['order_id'] = $entity_id;
        $result = Db::connect($db)
            ->table('sales_flat_shipment_track')
            ->field('track_number,title')
            ->where($map)
            ->find();
        if (!$result) {
            return false;
        }
        return $result;
    }
    /***
     * 获取zeelool订单的成本信息  create@lsw
     * @param totalId 所有的
     * @param thisPageId 当前页面的ID 
     */
    public function getOrderCostInfo($totalId,$thisPageId)
    {

        $arr = [];
        if(!$totalId || !$thisPageId){
            return $arr;
        }
        //原先逻辑已经废弃(总付款金额)
        //$totalMap['parent_id'] = ['in',$totalId];
        // $payInfo = Db::connect($this->connection)->table('sales_flat_order_payment')->where($totalMap)->sum('base_amount_paid');
        // $arr['totalPayInfo'] = $payInfo;
        // $thisPageIdMap['parent_id'] = ['in',$thisPageId];
        // $thisPageInfo = Db::connect($this->connection)->table('sales_flat_order_payment')->where($thisPageIdMap)->field('parent_id,base_amount_paid')->select();
        // if(!$thisPageInfo){
        //     return $arr;
        // }
        // $thisPageInfo = collection($thisPageInfo)->toArray($thisPageInfo);
        // foreach($thisPageInfo as  $v){
        //         $arr['thisPagePayPrice'][$v['parent_id']] = round($v['base_amount_paid'],2);
        // }
        //求出总付款金额
        $totalMap['entity_id'] = ['in',$totalId];
        $totalMap['status']    = ['in',['processing','complete','creditcard_proccessing','free_processing']];
        $payInfo = $this->model->where($totalMap)->where($totalMap)->field('entity_id,base_total_paid,base_total_due')->select();
        if(!$payInfo){
            return $arr;
        }
        $payInfo = collection($payInfo)->toArray();
        foreach($payInfo as $v){
            $arr['totalPayInfo'] +=round($v['base_total_paid']+$v['base_total_paid'],2);
        }
        //求出镜架成本start
        //1.求出所有的订单号
        $frameTotalMap['entity_id'] = ['in',$totalId];
        $frameThisPageMap['entity_id'] = ['in',$thisPageId];
        $order['increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameTotalMap)->column('increment_id');
        if(!$order['increment_id']){
            return $arr;
        }
        //2.求出本页面的订单号
        $order['this_increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameThisPageMap)->column('increment_id');
        if(!$order['this_increment_id']){
            return $arr;
        }
        //求出镜架成本start
        $arr['totalFramePrice'] = $arr['totalLensPrice'] = 0;
        $outStockMap['order_number'] = ['in',$order['increment_id']];
        $frameInfo = Db::table('fa_outstock_log')->alias('g')->where($outStockMap)->join('purchase_order_item m','g.purchase_id=m.purchase_id and g.sku=m.sku')
        ->field('g.sku,g.order_number,g.out_stock_num,g.purchase_id,m.purchase_price')->select(); 
        if(!$frameInfo){
            return $arr;
        }
        $frameInfo = collection($frameInfo)->toArray();
        foreach($frameInfo as $fv){
             $arr['totalFramePrice'] +=round($fv['out_stock_num']*$fv['purchase_price'],2);
            if(in_array($fv['order_number'],$order['this_increment_id'])){
                $arr['thispageFramePrice'][$fv['order_number']] = round($fv['out_stock_num']*$fv['purchase_price'],2);
            }
        }
        //求出镜架成本end
        //求出镜片成本start
        $lensInfo = Db::table('fa_lens_outorder')->where($outStockMap)->field('order_number,num,price')->select();
        if(!$lensInfo){
            return $arr;
        }
        $lensInfo = collection($lensInfo)->toArray();
        foreach($lensInfo as  $lv){
            $arr['totalLensPrice'] += round($lv['num']*$lv['price'],2);
            if(in_array($lv['order_number'],$order['this_increment_id'])){
                $arr['thispageLensPrice'][$lv['order_number']] = round($lv['num']*$lv['price'],2);
            }
        }
        //求出镜片成本end
            return $arr;
    }
}
