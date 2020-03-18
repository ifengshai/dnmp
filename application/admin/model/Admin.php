<?php

namespace app\admin\model;

use fast\Random;
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

    /**
     * 钉钉添加用户并绑定关系
     * @param $user
     * @param string $departmentId
     * @return mixed
     */
    public static function userAdd($user,$departmentId = '')
    {
        //判断有无部门，有则去第一个
        if(!$departmentId && !empty($user['department'])){
            $departmentId = $user['department'][0];
        }
        $username = str_replace(' ','',pinyin($user['name']));
        //排除用户名拼音一样的问题
        $count = self::where('username',$username)->count();
        $count = $count ?: '';
        $salt = Random::alnum();
        $password = md5(md5($username) . $salt);
        $data = [
            'username' => $username.$count,
            'nickname' => $user['name'],
            'password' => $password,
            'salt' => $salt,
            'avatar' => $user['avatar'] ?: '/assets/img/avatar.png',
            'email' => $user['email'] ?? '',
            'status' => 'normal',
            'position' => $user['position'],
            'mobile' => $user['mobile'],
            'department_id' => $departmentId,
            'userid' => $user['userid'],
            'unionid' => $user['unionid']
        ];
        $userAdd = self::create($data);
        //存在部门id则绑定角色
        if($departmentId){
            $groupId = AuthGroup::where('department_id',$departmentId)->value('id');
            AuthGroupAccess::create([
                'uid' => $userAdd->id,
                'group_id' => $groupId
            ]);
        }
        return $userAdd->id;
    }

    /**
     * 钉钉修改用户信息同步
     * @param $user
     * @param string $id
     * @return string
     */
    public static function userUpdate($user,$id = '')
    {
        $departmentId = '';
        //判断有无部门，有则去第一个
        if(!empty($user['department'])){
            $departmentId = $user['department'][0];
        }
        $data = [
            'avatar' => $user['avatar'] ?: '/assets/img/avatar.png',
            'email' => $user['email'] ?? '',
            'position' => $user['position'],
            'mobile' => $user['mobile'],
            'department_id' => $departmentId,
            'userid' => $user['userid'],
            'unionid' => $user['unionid']
        ];
        self::update($data,['id' => $id]);
        //获取用户原始的departmentId
        $preGroupId = AuthGroupAccess::where('uid',$id)->value('group_id');
        $groupId = AuthGroup::where('department_id',$departmentId)->value('id');
        if($preGroupId != $groupId){
            if($preGroupId){ //修改
                AuthGroupAccess::where('uid',$id)->setField('group_id',$groupId);
            }else{
                AuthGroupAccess::create([
                    'uid' => $id,
                    'group_id' => $groupId
                ]);
            }
        }
        return $id;
    }
}
