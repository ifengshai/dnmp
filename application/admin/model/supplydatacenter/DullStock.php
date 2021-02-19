<?php

namespace app\admin\model\supplydatacenter;

use think\Model;


class DullStock extends Model
{

    // 表名
    protected $name = 'supply_dull_stock';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];
}
