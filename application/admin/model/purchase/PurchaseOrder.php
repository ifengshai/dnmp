<?php

namespace app\admin\model\purchase;

use think\Model;
use think\Db;
class PurchaseOrder extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'purchase_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    /**
     * 获取采购单
     */
    public function getPurchaseData()
    {
        $where['purchase_status'] = ['in', [6, 7]];
        $data = $this->where($where)->order('createtime desc')->column('purchase_number', 'id');
        return $data;
    }



    /**
     * 获取采购单
     */
    public function getPurchaseReturnData($check_status = [0, 1], $instock_status, $return_status = [])
    {
        if ($instock_status) {
            $where['stock_status'] = ['in', $instock_status];
        }

        if ($return_status) {
            $where['return_status'] = ['in', $return_status];
        }
        
        $where['purchase_status'] = ['in', [6, 7]];
        $where['check_status']  = ['in', $check_status];
        $data = $this->where($where)->order('createtime desc')->column('purchase_number', 'id');
        return $data;
    }


    /**
     * 采购单明细表
     */
    public function purchaseOrderItem()
    {
        return $this->hasMany('PurchaseOrderItem', 'purchase_id');
    }
    /***
     * 获取采购单供应商表
     *
     */
    public function supplier()
    {
        return $this->belongsTo('app\admin\model\purchase\Supplier', 'supplier_id', 'id')->setEagerlyType(0);
    }
    /**
     * 获取供应商名称(废弃) create@lsw
     */
    public function fetchSupplierAccountPurchaseOrder($arr = [])
    {
        $map['id'] = ['in',$arr]; 
        $result = Db::name('supplier')->where($map)->field('id,supplier_name')->select();
        $info = collection($result)->toArray($result);
        if(!$info){
            return false;
        }
        $arr = [];
        foreach($info as $val){
            $arr[$val['id']] = $arr[$val['supplier_name']];
        }
        return $arr;
    }
    /***
     * 求出总共的实际采购金额和本页面的实际采购金额 create@lsw
     */
    public function calculatePurchaseOrderMoney($totalArr = [],$thisPageIdArr = [])
    {
        if( (0 == count($totalArr)) || (0 == count($thisPageIdArr))){
            return 0.00;
        }
        //首先求出总的邮费
        $postAgeMap['id'] = ['in',$totalArr];
        $totalPostage = $this->where($postAgeMap)->sum('purchase_freight');
        $totalMap['p.purchase_id'] = ['in',$totalArr];
        //求出所有的实际采购金额
        $arr = [];
        $arr['total_money'] = 0;
        $purchaseResult = Db::name('purchase_order_item')->alias('p')->where($totalMap)->join('check_order_item m','p.sku=m.sku and p.purchase_id = m.purchase_id')
        ->field('p.purchase_id,p.purchase_price,m.quantity_num,m.unqualified_num')->select();
        if(!$purchaseResult){
            $arr['total_money'] = $totalPostage;
            $arr['thisPageArr'] = [];
            return $arr;
        }
        $purchaseResult = collection($purchaseResult)->toArray();
        foreach($purchaseResult  as $v){
            $arr['total_money'] += round($v['purchase_price']*($v['quantity_num']+$v['unqualified_num']),2);
            if(in_array($v['purchase_id'],$thisPageIdArr)){
                $arr['thisPageArr'][$v['purchase_id']] = round($v['purchase_price']*($v['quantity_num']+$v['unqualified_num']),2);
            }
        } 
        $arr['total_money']+=$totalPostage;
        return $arr;
    }
    /***
     * 求出总共退款金额和本页面的实际退款金额 create@lsw
     */
    public function calculatePurchaseReturnMoney($totalArr = [],$thisPageIdArr = [])
    {
        if( (0 == count($totalArr)) || (0 == count($thisPageIdArr))){
            return false;
        }
        $map['purchase_id'] = ['in',$totalArr];
        $returnResult = Db::name('purchase_return')->where($map)->field('purchase_id,round(sum(return_money),2) return_money')->group('purchase_id')->select();
        $arr = [];
        $arr['return_money'] = 0;
        if(!$returnResult){
            $arr['thisPageArr'] = [];
            return $arr;
        }
        $returnResult = collection($returnResult)->toArray();
        foreach($returnResult as $v){
            $arr['return_money'] += $v['return_money'];  
            if(in_array($v['purchase_id'],$thisPageIdArr)){
                $arr['thisPageArr'][$v['purchase_id']] = $v['return_money'];
            }
        }
        return $arr;
    }
}
