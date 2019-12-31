<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Admin extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

    /***
     * 根据部门id获取部门下面的人员
     * @param $id
     * @return array|bool
     */
    public function getStaffListss($id)
    {
        $id = [31,32,33,34];
        $result = $this->alias('a')->join(' auth_group_access g','a.id = g.uid')->where('a.status','=','normal')->where('g.group_id','in',$id)->field('a.id,a.nickname
        ')->select();
        if(!$result){
            return false;
        }
        $groupStaff = [];
        foreach($result as $val){
            $groupStaff[0] = '请选择...';
            $groupStaff[$val['id']] = $val['nickname'];
        }
        return $result ? $groupStaff : false;
    }
    public function getStaffList($id)
    {
        $result = $this->alias('a')->join(' auth_group_access g','a.id = g.uid')->where('a.status','=','normal')->where('g.group_id','in',$id)->field('a.id,a.nickname
        ')->select();
        if(!$result){
            return false;
        }
        $groupStaff = [];
        foreach($result as $val){
            $groupStaff[0] = '请选择...';
            $groupStaff[$val['id']] = $val['nickname'];
        }
        return $result ? $groupStaff : false;
    }
    /***
     * 获取所有部门的人员
     */
    public function getAllStaff()
    {
        $result = $this->where('status','=','normal')->field('id,nickname')->select();
        if(!$result){
            return false;
        }
        $allStaff = [];
        foreach ($result as $key=>$val){
            $allStaff[$val['id']] = $val['nickname'];
        }
        return $result ? $allStaff : false;
    }
}
