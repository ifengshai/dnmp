<?php

namespace app\admin\model\finance;

use think\Db;
use think\Model;


class FinanceCycle extends Model
{

    // 表名
    protected $name = 'finance_cycle';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [];

   
}
