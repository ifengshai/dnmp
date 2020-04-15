<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderMeasure extends Model
{

    // 表名
    protected $name = 'work_order_measure';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    /**
     * 求出工单的措施列表
     *
     * @Description
     * @author lsw
     * @since 2020/04/15 16:25:24 
     * @param [type] $id
     * @return void
     */
     static public function workMeasureList($id)
    {
        return WorkOrderMeasure::where(['work_id'=>$id])->column('measure_choose_id');
    }
}
