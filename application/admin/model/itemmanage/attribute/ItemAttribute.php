<?php

namespace app\admin\model\itemmanage\attribute;

use think\Db;
use think\Model;


class ItemAttribute extends Model
{

    

    

    // 表名
    protected $name = 'item_attribute';
    
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
     * 获取所有眼镜形状
     */
    public function getAllFrameShape()
    {
        return [0=>'请选择',1=>'长方形', 2=>'正方形', 3 =>'猫眼', 4 =>'圆形', 5 =>'飞行款', 6 =>'多边形', 7=>'蝴蝶款'];
    }
    /***
     * 获得所有框型
     */
    public function getAllShape()
    {
        return [0=>'请选择',1=>'全框',2=>'半框',3=>'无框'];
    }
    /***
     * 获取所有材质
     */
    public function getAllTexture()
    {
        return [ 0=>'请选择',1 => '塑料', 2 =>'板材', 3 =>'TR90', 4 =>'金属', 5 =>'钛', 6 =>'尼龙', 7=>'木质'];
    }

    /***
     * 获取适合类型
     */
    public function getFrameGender()
    {
        return [0=>'请选择',1 =>'男', 2 =>'女', 3 =>'都适合'];
    }
    public function getFrameSize(){
        //Z站尺寸型号
        return [0=>'请选择',1=>'N',2=>'M',3=>'W'];
        //V站尺寸型号
        //return [0=>'请选择',1=>'S',2=>'M',3=>'L'];
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
        $attrArr = $this->getTableFields();
        if(!in_array($value,$attrArr)){
            if($type == 1){
                $sql = "alter table fa_item_attribute add {$value} enum ({$str}) comment '{$comment}'";
            }else{
                $sql = "alter table fa_item_attribute add {$value} VARCHAR(100) NOT NULL DEFAULT '' comment '{$comment}'";
            }
            $result = Db::execute($sql);
            if($result !== false){
                return true;
            }else{
                return false;
            }
        }else{
            return 1;
        }

    }
}
