<?php

namespace app\admin\model\itemmanage;

use think\Model;


class Itempresell extends Model
{

    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'item_presell';
    
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
