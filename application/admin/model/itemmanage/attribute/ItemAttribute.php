<?php

namespace app\admin\model\itemmanage\attribute;

use think\Db;
use think\Model;


class ItemAttribute extends Model
{

    

    

    // 表名
    protected $name = 'item_attribute';
    protected $pk = 'id';
    
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
     * 获取所有眼镜形状(原先)
     */
//    public function getAllFrameShape()
//    {
//        return [0=>'请选择',1=>'长方形', 2=>'正方形', 3 =>'猫眼', 4 =>'圆形', 5 =>'飞行款', 6 =>'多边形', 7=>'蝴蝶款'];
//    }
    public function getAllFrameShape()
    {
        return [0=>'请选择',1=>'rectangle', 2=>'square', 3 =>'cateye', 4 =>'oval', 5 =>'aviator', 6 =>'geometric', 7=>'蝴蝶款'];
    }
    /***
     * 获得所有框型(原先)
     */
//    public function getAllShape()
//    {
//        return [0=>'请选择',1=>'全框',2=>'半框',3=>'无框'];
//    }
    public function getAllShape()
    {
        return [0=>'请选择',1=>'full_rim',2=>'semi_rim',3=>'rimless'];
    }
    /***
     * 获取所有材质(原先)
     */
//    public function getAllTexture()
//    {
//        return [ 0=>'请选择',1 => '塑料', 2 =>'板材', 3 =>'TR90', 4 =>'金属', 5 =>'钛', 6 =>'尼龙', 7=>'木质',8=>'混合材质',9=>'合金',10=>'其他材质'];
//    }
    public function getAllTexture()
    {
        return [ 0=>'请选择',1 => 'plastic', 2 =>'acetate', 3 =>'tr90', 4 =>'metal',5=>'titanium', 6 =>'尼龙', 7=>'木质',8=>'混合材质',9=>'alloy',10=>'other_material'];
    }
    /***
     * 获取材质对应的编码 和 getAllTexture下标对应
     */
    public function getTextureEncode($id)
    {
        $arr =[1=>'P',2=>'A',3=>'T',4=>'M',5=>'I',6=>'N',7=>'W',8=>'X'];
        return $arr[$id];
    }
    /***
     * 获取适合类型(原先)
     */
//    public function getFrameGender()
//    {
//        return [0=>'请选择',1 =>'男', 2 =>'女', 3 =>'都适合'];
//    }
    public function getFrameGender()
    {
        return [0=>'请选择',1 =>'man', 2 =>'woman', 3 =>'neutral'];
    }
    /***
     * 获取尺寸型号
     * @return array
     */
    public function getFrameSize(){
        //Z站尺寸型号
        return [0=>'请选择',1=>'N',2=>'M',3=>'W'];
        //V站尺寸型号
        //return [0=>'请选择',1=>'S',2=>'M',3=>'L'];
    }

    /***
     * 获取镜架所有的颜色(原先)
     */
//    public function getFrameColor()
//    {
//        return [0=>'请选择',1=>'红', 2=> '橙', 3=>'黄', 4=>'绿', 5=>'蓝', 6=>'紫', 7=>'黑', 8=>'白', 9=>'灰', 10=>'褐'];
//    }
    public function getFrameColor()
    {
        return [0=>'请选择',1=>'red', 2=> 'orange', 3=>'yellow', 4=>'green', 5=>'blue', 6=>'purple', 7=>'black', 8=>'white', 9=>'gray', 10=>'brown'];
    }
    /***
     * 获取眼镜类型(原先)
     */
//    public function getGlassesType()
//    {
//        return [0=>'请选择',1=>'处方镜',  2 =>'太阳镜', 3=>'老花镜'];
//    }
    public function getGlassesType()
    {
        return [0=>'请选择',1=>'progressive', 2 =>'sunglasses', 3=>'readers'];
    }
    /***
     * 获取所有线下采购产地
     */
    public function getOrigin()
    {
        return ['O'=>'线上采购','S'=>'深圳','W'=>'温州','T'=>'台州','X'=>'厦门','Y'=>'鹰潭','D'=>'丹阳','G'=>'广州','C'=>'重庆'];
    }
    /***
     * 配镜类型(原先)
     */
//    public function getFrameType()
//    {
//        return [0=>'请选择',1=>'单焦点',2=>'多焦点'];
//    }
    public function getFrameType()
    {
        return [0=>'请选择',1=>'Single vision',2=>'Progressive'];
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
