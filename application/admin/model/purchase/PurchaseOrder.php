<?php

namespace app\admin\model\purchase;

use think\Model;


class PurchaseOrder extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'purchase_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
