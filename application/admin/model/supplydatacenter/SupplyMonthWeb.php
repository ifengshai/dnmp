<?php

namespace app\admin\model\supplydatacenter;

use think\Db;
use think\Model;


class SupplyMonthWeb extends Model
{

    // 表名
    protected $name = 'datacenter_supply_month_web';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];
}
