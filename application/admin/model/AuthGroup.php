<?php

namespace app\admin\model;

use think\Model;

class AuthGroup extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function getNameAttr($value, $data)
    {
        return __($value);
    }
    /***
     * 获取所有的分组
     */
    public function getAllGroup()
    {
        $result = $this->where('status','=','normal')->field('id,name')->select();
        if(!$result){
            return false;
        }
        $resultArr = [];
        foreach ($result as $key => $val){
            $resultArr[$val['id']] =$val['name'];
        }
        return $result ? $resultArr : false;
    }
}
