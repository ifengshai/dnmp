<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;

class Nihao extends Model
{

    

    //数据库
    // protected $connection = 'database';
    protected $connection = 'database.db_nihao_online';

    
    // 表名
    protected $table = 'sales_flat_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
     //名称获取器
     public function getCustomerFirstnameAttr($value, $data)
     {
         return $data['customer_firstname'] . ' ' . $data['customer_lastname'];
     }
    







}
