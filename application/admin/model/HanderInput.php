<?php

namespace app\admin\model;

use think\Model;


class HanderInput extends Model
{
    // 表名
    protected $table = 'zeelool_hander_input_stock';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    //关联模型
    public function item_list()
    {
        return $this->belongsTo('app\admin\model\HanderInputItem', 'id', 'input_stock_id', [])->setEagerlyType(0);
    }


    public function item()
    {
        return $this->hasMany('HanderInputItem', 'input_stock_id');
    }
}
