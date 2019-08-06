<?php

namespace app\admin\model\itemmanage\attribute;

use think\Model;


class ItemAttributePropertyValue extends Model
{

    

    

    // 表名
    protected $name = 'item_attribute_property_value';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /***
     * 获取商品属性值列表
     * @param $id
     */
    public function getAttrPropertyValue($id)
    {
        $result = $this->where('property_id','=',$id)->select();
        return $result ? $result : false;
    }
    







}
