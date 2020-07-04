<?php

namespace app\admin\model\purchase;

use think\Model;
use think\Db;

class PurchaseBatchItem extends Model
{
    // 表名
    protected $name = 'purchase_batch_item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

   

}
