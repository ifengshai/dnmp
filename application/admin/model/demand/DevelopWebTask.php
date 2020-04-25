<?php

namespace app\admin\model\demand;

use think\Model;


class DevelopWebTask extends Model
{

    

    

    // 表名
    protected $name = 'develop_web_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function developwebtaskitem()
    {
        return $this->belongsTo('app\admin\model\DevelopWebTaskItem', 'id', 'task_id', [], 'LEFT')->setEagerlyType(0);
    }
}
