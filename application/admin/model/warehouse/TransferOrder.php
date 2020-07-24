<?php

namespace app\admin\model\warehouse;

use think\Model;


class TransferOrder extends Model
{

    

    

    // 表名
    protected $name = 'transfer_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    public function transferorderitem()
    {
        return $this->belongsTo('app\admin\model\TransferOrderItem', 'id', 'transfer_order_id', [], 'LEFT')->setEagerlyType(0);
    }
}
