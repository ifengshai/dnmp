<?php

namespace app\admin\model\purchase;

use think\Model;
use think\Db;

class PurchaseBatch extends Model
{
    // 表名
    protected $name = 'purchase_batch';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 采购单明细表
     */
    public function purchaseOrderItem()
    {
        return $this->hasMany('PurchaseOrderItem', 'purchase_id');
    }

}
