<?php

namespace app\admin\model\itemmanage\attribute;

use think\Model;


class ItemAttributePropertyGroup extends Model
{
    //制定数据库连接
    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'item_attribute_property_group';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function groupStatus()
    {
        return [1=>'启用',2=>'禁用'];
    }
    public function propertyGroupList()
    {
        $result = $this->where('status','=',1)->field('id,name')->select();
        if(!$result){
            return [0=>'商品属性组不存在,请先添加商品属性组'];
        }
        $arr = [];
        foreach($result as $key=>$val){
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }
    







}
