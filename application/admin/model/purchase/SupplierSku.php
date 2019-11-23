<?php

namespace app\admin\model\purchase;

use think\Model;


class SupplierSku extends Model
{

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'supplier_sku';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    //关联模型
    public function supplier()
    {
        return $this->belongsTo('supplier', 'supplier_id')->setEagerlyType(0);
    }

    //根据SKUID 匹配sku
    public function getSkuData($skuid)
    {
        $map['skuid'] = $skuid;
        $map['status'] = 1;
        return $this->where($map)->value('sku');
    }

    //根据SKUID 匹配sku
    public function getSupplierData($skuid)
    {
        $map['skuid'] = $skuid;
        $map['status'] = 1;
        return $this->where($map)->value('supplier_sku');
    }

    //根据sku 获取供应商sku
    public function getSupplierSkuData($sku) 
    {
        return $this->where('sku', '=', $sku)->value('supplier_sku');
    }
}
