<?php

namespace app\admin\model\demand;

use think\Model;

class ItWebTaskItem extends Model
{
    // 表名
    protected $name = 'it_web_task_item';



    //关联模型
    public function itWebTask()
    {
        return $this->belongsTo('ItWebTask', 'task_id','', [], 'left')->setEagerlyType(0);
    }
}
