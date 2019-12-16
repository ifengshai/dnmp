<?php

namespace app\admin\model;

use think\Model;


class ProductGrade extends Model
{
    // 表名
    protected $name = 'product_grade';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    
}
