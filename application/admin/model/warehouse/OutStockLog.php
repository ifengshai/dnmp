<?php

namespace app\admin\model\warehouse;

use think\Model;


class OutStockLog extends Model
{
    // 表名
    protected $name = 'outstock_log';

    //根据出库单ID计算金额,计算总共的金额 create@lsw
    public function calculateMoneyAccordOutStock($arr = [])
    {
        if(0 == count($arr)){
            return 0.00;
        }
        $map['out_stock_id'] = ['in',$arr];
        $result = $this->where($map)->alias('o')->join('purchase_order_item m','o.sku=m.sku and o.purchase_id=m.purchase_id')->field('o.out_stock_num,m.purchase_price')->select();
        if(!$result){
            return 0.00;
        }
        $info = collection($result)->toArray();
        $returnArr['total_money'] = 0.00;
        foreach($info as $val){
            $returnArr['total_money'] += round($val['out_stock_num']*$val['purchase_price'],2); 
        }
        return $returnArr;
    }
    //计算页面的总金额,传入本页面的ID create@lsw
    public function calculateMoneyAccordThisPageId($arr = [])
    {
        if(0 == count($arr)){
            return 0.00;
        }
        $map['out_stock_id'] = ['in',$arr];
        $result = $this->where($map)->alias('o')->join('purchase_order_item m','o.sku=m.sku and o.purchase_id=m.purchase_id')->field('o.out_stock_num,o.out_stock_id,m.purchase_price')->select();
        if(!$result){
            return 0.00;
        }
        $info = collection($result)->toArray();
        $returnArr = [];
        foreach($info as $val){
            $returnArr[$val['out_stock_id']] += round($val['out_stock_num']*$val['purchase_price'],2); 
        }
        return $returnArr;   
    }
    //求出采购商品的信息,传入本页面ID create@lsw
    public function getPurchaseItemInfo($arr = [])
    {
        if(0 == count($arr)){
            return false;
        }
        $map['out_stock_id'] = ['in',$arr];
        $result = $this->where($map)->alias('o')->join('purchase_order_item m','o.sku=m.sku and o.purchase_id=m.purchase_id')->field('o.sku,o.out_stock_num,o.out_stock_id,m.product_name,m.purchase_price')->select();
        if(!$result){
            return false;
        }
        $info = collection($result)->toArray();
        foreach($info as $key => $val){
            $info[$key]['total_money'] = round($val['out_stock_num'] * $val['purchase_price'],2);
        }
        return $info;
    }

}
