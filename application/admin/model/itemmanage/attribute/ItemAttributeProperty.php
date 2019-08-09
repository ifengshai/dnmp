<?php

namespace app\admin\model\itemmanage\attribute;

use think\Model;
use think\Db;
use app\admin\model\itemmanage\attribute\ItemAttributePropertyValue;
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
    /***
     * @param $field 字段的英文名称
     */
    public function itemAttrProperty($field)
    {
        $result = $this->where('name_en','=',$field)->field('id,name_en')->find();
        return $result ? $result : false;
    }
    /***
     * 获取商品属性项详情
     */
    public function getAttrPropertyDetail($id)
    {
        $result = $this->where('id','=',$id)->find();
        if(!$result){
            return false;
        }
        $result['value'] = (new ItemAttributePropertyValue())->getAttrPropertyValue($id);
        return $result;
    }
    /***
     *商品属性项列表
     */
    public function propertyList()
    {
        $result = $this->where('status','=',1)->field('id,name_cn')->select();
        if(!$result){
            return [0=>'商品属性不存在,请先添加属性'];
        }
        $arr = [];
        foreach($result as $key=>$val){
            $arr[$val['id']] = $val['name_cn'];
        }
        return $arr;
    }
}
