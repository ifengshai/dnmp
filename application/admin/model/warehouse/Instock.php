<?php

namespace app\admin\model\warehouse;

use think\Model;


class Instock extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'in_stock';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联模型采购单
    public function purchaseorder()
    {
        return $this->belongsTo('app\admin\model\purchase\PurchaseOrder', 'purchase_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    //关联模型采购单
    public function instocktype()
    {
        return $this->belongsTo('InstockType', 'type_id')->setEagerlyType(0);
    }

    //关联模型 商品信息
    public function instockItem()
    {
        return $this->hasMany('InstockItem', 'in_stock_id');
    }
}
