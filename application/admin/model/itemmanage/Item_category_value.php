<?php

namespace app\admin\model\itemmanage;

use think\Model;


class Item_category_value extends Model
{

    

    protected $connection = 'database.db_stock';

    // 表名
    protected $name = 'item_category_value';
    
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
     * 查找所有的编码规则
     */
    public function getAllCodeType()
    {
        $result = $this->column('code_type');
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $v){
            $arr['0'] = '无';
            $arr[$v] = $v;
        }
        return $arr;
    }
    /***
     * 根据编码查询需要更新的数据
     * @param code_type 
     */
    public function getTextureValueAndColor($code_type)
    {
        return  $this->where(['code_type'=>$code_type])->field('texture_value,color_value')->find();
    }
    







}
