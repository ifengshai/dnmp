<?php

namespace app\admin\model\itemmanage\attribute;

use think\Db;
use think\Model;


class ItemAttribute extends Model
{

    

    

    // 表名
    protected $name = 'item_attribute';
    
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
