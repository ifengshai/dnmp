<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class OrderReturnItem extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'order_return_item';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /***
     * 根据
     */
    public function getOrderReturnItem($order_return_id)
    {
        return $this->where('order_return_id','=',$order_return_id)->select();
    }

    







}
