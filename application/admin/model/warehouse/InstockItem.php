<?php

namespace app\admin\model\warehouse;

use think\Db;
use think\Model;


class InstockItem extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'in_stock_item';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    //根据入库单ID计算金额,计算总共的金额 create@lsw
    public function calculateMoneyAccordInStock($arr = [])
    {
        if( 0 == count($arr)){
            return 0.00;
        }
        $map['in_stock_id'] = ['in',$arr];
        $result = $this->where($map)->alias('o')->join('purchase_order_item m','o.sku=m.sku and o.purchase_id=m.purchase_id')->field('o.in_stock_num,m.purchase_price')->select();
        if(!$result){
            return 0.00;
        }
        $info = collection($result)->toArray();
        $returnArr['total_money'] = 0.00;
        foreach($info as $val){
            $returnArr['total_money'] += round($val['in_stock_num']*$val['purchase_price'],2); 
        }
        return $returnArr;
    }

    //计算页面的总金额,传入本页面的ID create@lsw
    public function calculateMoneyAccordInStockThisPageId($arr =[])
    {
        if(0 == count($arr)){
            return 0.00;
        }
        $map['in_stock_id'] = ['in',$arr];
        $result = $this->where($map)->alias('o')->join('purchase_order_item m','o.sku=m.sku and o.purchase_id=m.purchase_id')->field('o.in_stock_num,o.in_stock_id,m.purchase_price')->select();
        if(!$result){
            return 0.00;
        }
        $info = collection($result)->toArray();
        $returnArr = [];
        foreach($info as $val){
            $returnArr[$val['in_stock_id']] += round($val['in_stock_num']*$val['purchase_price'],2); 
        }
        return $returnArr; 
    }
    //求出采购商品的信息,传入本页面ID create@lsw
    public function getPurchaseItemInfo($arr = [])
    {
        if(0 == count($arr)){
            return false;
        }
        $map['in_stock_id'] = ['in',$arr];
        $result = $this->where($map)->alias('o')->join('purchase_order_item m','o.sku=m.sku and o.purchase_id=m.purchase_id')->field('o.sku,o.in_stock_num,o.in_stock_id,m.product_name,m.purchase_price')->select();
        if(!$result){
            return false;
        }
        $info = collection($result)->toArray();
        foreach($info as $key => $val){
            $info[$key]['total_money'] = round($val['in_stock_num'] * $val['purchase_price'],2);
        }
        return $info;
    }
}
