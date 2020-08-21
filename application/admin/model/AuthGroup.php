<?php

namespace app\admin\model;

use think\Model;
use think\Cache;

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
    /**
     * 获取一个分组的所有下级分组
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-06-29 10:24:34
     * @return void
     */
    public function getAllNextGroup_yuan($group_id) 
    {
        $rs = $this->where('pid','in',$group_id)->field('id')->select();
        if(!$rs){
            return false;
        }
        static $arr = [];
        foreach ($rs as $v){
            $arr[] = $v['id'];
            $this->getAllNextGroup($v['id']);
        }
        return $arr;
    }
    public function getAllNextGroup($group_id) 
    {   //$arr = Cache::get('AuthGroup_getAllNextGroup_'.$group_id);
        // if($arr){
        //     return $arr;
        // }
        $rs = $this->field('id,pid')->select();
        if(!$rs){
            return false;
        }
        $info = $this->get_all_child($rs,$group_id);
        //Cache::set('AuthGroup_getAllNextGroup_'.$group_id,$info);
        return $info;
    }

    function get_all_child($array,$id){
        $arr = array();
        foreach($array as $v){
            if($v['pid'] == $id){
                $arr[] = $v['id'];
                $arr = array_merge($arr,$this->get_all_child($array,$v['id']));
            };
        };
        return $arr;
    }

    /*
     * 根据指定组id获取角色组下面所有的子角色组
     * */
    public static function getChildrenIds($ids)
    {
        static $output = [];
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $child_ids = self::whereIn('pid', $ids)->column('id');
        if (!empty($child_ids)){
            $output = array_merge($output, $child_ids);
            static::getChildrenIds($child_ids);
        }
        return $output;
    }

}
