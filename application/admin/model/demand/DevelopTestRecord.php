<?php

namespace app\admin\model\demand;

use think\Model;


class DevelopTestRecord extends Model
{


    // 表名
    protected $name = 'develop_test_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


}
