<?php

namespace app\admin\model;

use think\Model;


class HanderInputItem extends Model
{
    // 表名
    protected $table = 'zeelool_hander_input_stock_item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联模型
    public function item()
    {
        return $this->belongsTo('app\admin\model\HanderInput', 'input_stock_id', 'id', [])->setEagerlyType(0);
    }

}
