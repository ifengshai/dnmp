<?php

/**
 * 子订单
 */
namespace app\admin\model\order;

use think\Model;
use think\Db;
class OrderItemOption extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order_shell';

    // 表名
    protected $table = 'fa_order_item_option';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    
}