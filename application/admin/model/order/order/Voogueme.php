<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class Voogueme extends Model
{
    //数据库
    // protected $connection = 'database';
    protected $connection = 'database.db_voogueme';

    
    // 表名
    protected $table = 'sales_flat_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
     //名称获取器
     public function getCustomerFirstnameAttr($value, $data)
     {
         return $data['customer_firstname'] . ' ' . $data['customer_lastname'];
     }

    /***
     * 获取nihao订单的成本信息  create@lsw
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
        // $totalMap['parent_id'] = ['in',$totalId];
        // //总付款金额
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
