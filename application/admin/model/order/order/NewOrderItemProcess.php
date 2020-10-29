<?php

namespace app\admin\model\order\order;

use think\Model;

class NewOrderItemProcess extends Model
{
    //数据库
    protected $connection = 'database.db_new_order';

    // 表名
    protected $name = 'order_item_process';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    
}
