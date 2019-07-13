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
    protected $append = [

    ];
    

    







}
