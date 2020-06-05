<?php

namespace app\admin\model\demand;

use think\Model;

class DevelopWebTaskItem extends Model
{
    // 表名
    protected $name = 'develop_web_task_item';
    //关联模型
    public function developwebtask()
    {
        return $this->belongsTo('DevelopWebTask', 'task_id','', [], 'left')->setEagerlyType(0);
    }
}

