<?php

namespace app\admin\model;

use think\Model;


class NewProductNotSatisfied extends Model
{
    // 表名
    protected $name = 'new_product_not_satisfied';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

   
}
