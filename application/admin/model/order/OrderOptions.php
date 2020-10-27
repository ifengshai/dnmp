<?php

/**
 * 处方
 */
namespace app\admin\model\order;

use think\Model;
use think\Db;


class OrderOptions extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order';

    // 表名
    protected $table = 'fa_order_options';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    
}