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
    protected $append = [
        'status_format'
    ];

    public function getStatusFormatAttr($value, $data)
    {
        $status = ['1' => '未处理', '2' => '处理完成', '3' => '处理失败'];
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


}
