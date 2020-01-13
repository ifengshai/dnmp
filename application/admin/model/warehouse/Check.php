<?php

namespace app\admin\model\warehouse;

use think\Model;


class Check extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'check_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联模型
    public function purchaseOrder()
    {
        return $this->belongsTo('app\admin\model\purchase\PurchaseOrder', 'purchase_id')
        ->setEagerlyType(0)->joinType('left');
    }

    //关联模型
    public function supplier()
    {
        return $this->belongsTo('app\admin\model\purchase\Supplier', 'supplier_id')->setEagerlyType(0)->joinType('left');
    }

    
    //关联模型
    public function orderReturn()
    {
        return $this->belongsTo('app\admin\model\saleaftermanage\OrderReturn', 'order_return_id')->setEagerlyType(0)->joinType('left');
    }

    public function checkItem()
    {
        return $this->hasMany('CheckItem','check_id');
    }
}
