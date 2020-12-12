<?php

namespace app\common\model;

use think\Model;


class OcCustomerAfterSalesWorkOrder extends Model
{



    //库名
    protected $connection = 'database.db_zeelool';

    // 表名
    protected $table = 'oc_customer_after_sales_work_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function comments(){
//        return $this->hasMany('Zendesk'.'email')->field('id ,ticket_id,subject,to_email,assignee_id,create_time,update_time,status');
        return $this->hasMany('Zendesk','email');
    }









}
