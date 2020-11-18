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

    ];

    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => '我的', 'field' => 'label', 'value' => 1],
            ['name' => '未完成', 'field' => 'label', 'value' => 2],
            ['name' => 'BUG任务', 'field' => 'label', 'value' => 3],
            ['name' => '开发任务', 'field' => 'label', 'value' => 4],
            ['name' => '其他任务', 'field' => 'label', 'value' => 5],

        ];
    }
}
