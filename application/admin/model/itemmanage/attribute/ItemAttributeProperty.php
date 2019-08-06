<?php

namespace app\admin\model\itemmanage\attribute;

use think\Model;


class ItemAttributeProperty extends Model
{

    

    

    // 表名
    protected $name = 'item_attribute_property';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    //返回属性项的值是否必须填写
    public function attProValIsRequired()
    {
        return [1=>'必填',2=>'选填'];
    }
    //返回属性项的输入方式
    public function attProValInputMode()
    {
        return [1=>'单选',2=>'多选',3=>'输入'];
    }







}
