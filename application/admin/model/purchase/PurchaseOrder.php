<?php

namespace app\admin\model\purchase;

use think\Model;

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
        $where['purchase_status'] = 7;
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
}
