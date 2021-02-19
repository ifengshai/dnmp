<?php

namespace app\admin\model\demand;

use think\Model;


class ItAppDemand extends Model
{

    

    

    // 表名
    protected $name = 'it_app_demand';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'node_time_text'
    ];
    

    



    public function getNodeTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['node_time']) ? $data['node_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setNodeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
