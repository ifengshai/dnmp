<?php

namespace app\admin\model\demand;

use think\Model;


class DevelopDemand extends Model
{

    

    

    // 表名
    protected $name = 'develop_demand';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => '我的', 'field' => 'me_task', 'value' => 1],
        ];
    }
    







}
