<?php

namespace app\admin\model\itemmanage;

use think\Model;


class GoodsStockLog extends Model
{

    //制定数据库连接
    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'stock_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

}
