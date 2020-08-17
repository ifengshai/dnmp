<?php

namespace app\admin\model\demand;

use think\Model;


class ItWebTask extends Model
{

    // 表名
    protected $name = 'it_web_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    public function itwebtaskitem()
    {
        return $this->belongsTo('app\admin\model\demand\ItWebTaskItem', 'id', 'task_id','', 'LEFT')->setEagerlyType(0);
    }
}
