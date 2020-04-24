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
    protected $append = [
        'status_format'
    ];

    public function getStatusFormatAttr($value, $data)
    {
        $status = ['0' => '未处理', '1' => '处理完成', '2' => '处理失败'];
        return $status[$data['recept_status']];
    }
    /**
     * 措施
     * @return \think\model\relation\HasOne
     */
    public function measure()
    {
        return $this->hasOne(WorkOrderMeasure::class,'id','measure_id');
    }
    /**
     * 获取承接的记录ID
     *
     * @Description
     * @author lsw
     * @since 2020/04/21 16:01:58 
     * @param [type] $id
     * @return void
     */
    public function getOneRecept($id)
    {
        return $this->where(['id'=>$id])->find()->toArray();
    }

}
