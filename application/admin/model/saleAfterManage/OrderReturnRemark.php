<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class OrderReturnRemark extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'order_return_remark';

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
     * 根据退货单列表id获取备注详细信息
     */
    public function getOrderReturnRemark($order_return_id)
    {
        return $this->where('order_return_id','=',$order_return_id)->select();
    }









}