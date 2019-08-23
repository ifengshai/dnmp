<?php

namespace app\admin\model\warehouse;

use think\Model;


class TempProduct extends Model
{

    // 表名
    protected $name = 'temp_product';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    



}
