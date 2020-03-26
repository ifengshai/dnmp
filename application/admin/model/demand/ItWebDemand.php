<?php

namespace app\admin\model\demand;

use think\Model;


class ItWebDemand extends Model
{

    

    

    // 表名
    protected $name = 'it_web_demand';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'hope_time_format'
    ];

    /**
     * hopetime格式化
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getHopeTimeFormatAttr($value, $data)
    {
        return date('Y-m-d H:i',strtotime($data['hope_time']));
    }

}
