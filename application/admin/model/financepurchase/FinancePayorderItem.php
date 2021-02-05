<?php

namespace app\admin\model\financepurchase;

use think\Db;
use think\Model;


class FinancePayorderItem extends Model
{

    // 表名
    protected $name = 'finance_payorder_item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];
}
