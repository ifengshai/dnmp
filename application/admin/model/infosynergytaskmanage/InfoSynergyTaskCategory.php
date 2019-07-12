<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;


class InfoSynergyTaskCategory extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task_category';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function taskCategory()
    {
        return [1=>'一级分类',2=>'二级分类',3=>'三级分类'];
    }
    







}
