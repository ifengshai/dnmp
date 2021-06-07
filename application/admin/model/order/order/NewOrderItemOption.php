<?php

namespace app\admin\model\order\order;

use think\Model;
use think\model\relation\BelongsTo;

class NewOrderItemOption extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order';

    // 表名
    protected $name = 'order_item_option';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * @return BelongsTo
     * @author fangke
     * @date   5/18/21 10:34 AM
     */
    public function newOrder(): BelongsTo
    {
        return $this->belongsTo(NewOrder::class, 'order_id', 'id');
    }
}
