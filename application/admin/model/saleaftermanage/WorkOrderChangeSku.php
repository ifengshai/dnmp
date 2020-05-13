<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderChangeSku extends Model
{

    // 表名
    protected $name = 'work_order_change_sku';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    /**
     * 获取一个工单的镜框变化表
     *
     * @Description
     * @author lsw
     * @since 2020/04/16 10:46:27 
     * @param [type] $work_id
     * @param [type] $ordertype
     * @param [type] $order_number
     * @return void
     */
    public function getOrderChangeSku($work_id,$ordertype,$order_number,$change_type)
    {
        $where['work_id'] = $work_id;
        $where['platform_type'] = $ordertype;
        $where['increment_id'] = $order_number;
        $where['change_type'] = $change_type;
        return WorkOrderChangeSku :: where($where)->select();
    }

}
