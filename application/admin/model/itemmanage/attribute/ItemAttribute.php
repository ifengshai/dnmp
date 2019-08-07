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
            return $result ? $result : false;
        }else{
            return 1;
        }

    }






}
