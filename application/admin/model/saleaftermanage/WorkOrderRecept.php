<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderRecept extends Model
{

    // 表名
    protected $name = 'work_order_recept';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    /**
     * 根据工单ID获取工单承接人
     * @param work_id 工单ID
     * @Description
     * @author lsw
     * @since 2020/04/21 09:33:24 
     * @return void
     */
    static function getWorkOrderReceptPerson($work_id)
    {
       return WorkOrderRecept::where(['work_id'=>$work_id])->select();
    }
}
