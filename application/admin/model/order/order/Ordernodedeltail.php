<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class Ordernodedeltail extends Model 
{



    //数据库
     protected $connection = 'database';


    // 表名
    protected $table = 'fa_order_node_detail';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


   
    
   
}
