<?php

namespace app\admin\model\warehouse;

use think\Model;


class StockTransferInOrderItem extends Model
{


    // 表名
    protected $name = 'stock_transfer_in_order_item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

}
