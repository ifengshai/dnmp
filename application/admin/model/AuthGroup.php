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
        // $info = Cache::get('AuthGroup_getAllNextGroup_'.$group_id);
        // if($info){
        //     return $info;
        // }
        //static $arr;
        $where['pid'] = ['in',$group_id];
        $where['status'] = 'normal';
        $result =$this->where($where)->column('id');
        if(!$result){
            return false;
        }
        $arr[] = $result;
        $this->getAllNextGroup($arr);
        Cache::set('AuthGroup_getAllNextGroup_'.$group_id, $arr); 
        return $arr;
    }
    public function getAllNextGroup($group_id) 
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
}
