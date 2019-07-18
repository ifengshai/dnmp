<?php

namespace app\admin\model\purchase;

use think\Model;


class PurchaseReturn extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'purchase_return';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    //关联模型
    public function purchaseReturnItem()
    {
        return $this->hasMany('purchaseReturnItem','return_id');
    }
    
    //关联模型
    public function purchaseOrder()
    {
        return $this->belongsTo('app\admin\model\purchase\PurchaseOrder', 'purchase_id')->setEagerlyType(0);
    }

    //关联模型
    public function supplier()
    {
        return $this->belongsTo('app\admin\model\purchase\Supplier', 'supplier_id')->setEagerlyType(0);
    }






}
