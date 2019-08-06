<?php

namespace app\admin\model\itemmanage\attribute;

use think\Model;
use think\Db;
//use app\admin\model\itemmanage\attribute\ItemAttributePropertyValue;
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

    //追加字段
    /***
     * @param $type 输入类型  1 单选 2 多选 3 输入
     * @param $value 字段英文名称
     * @param $comment 字段注释
     * @param string $str 如果单选设定的enum类型
     */
    public function appendField($type,$value,$comment,$str='')
    {
        $rs = $this->where('name_en','=',$value)->field('id,name_en')->find();
        if(!$rs){
            if($type == 1){
                $sql = "alter table fa_item_attribute add {$value} enum ({$str}) comment '{$comment}'";
            }else{
                $sql = "alter table fa_item_attribute add {$value} VARCHAR(100) NOT NULL DEFAULT '' comment '{$comment}'";
            }
            $result = Db::execute($sql);
            return $result ? $result : false;
        }else{
            return 1;
        }

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

}
