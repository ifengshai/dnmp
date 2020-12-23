<?php

namespace app\admin\model\supplydatacenter;

use think\Db;
use think\Model;


class Supply extends Model
{

    // 表名
    protected $name = 'datacenter_day_supply';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];
}
