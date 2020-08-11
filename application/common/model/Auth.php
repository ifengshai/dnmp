<?php

namespace app\common\model;

use think\Model;
use think\Db;

class Auth extends Model
{
    /**
     * 根据权限URL获取拥有此权限的用户
     *
     * @Description
     * @author wpl
     * @since 2020/04/22 11:08:26 
     * @param [type] $url 权限地址 要求和菜单规则必须一致 eg:warehouse/check/index
     * @return void
     */
    public static function getUsersId($url)
    {
        //如果是超级管理员
        //获取规则id
        $authRule = new \app\admin\model\AuthRule();
        $ruleId = $authRule->where('name', $url)->value('id');
        if (!$ruleId) {
            return false;
        }
        //获取拥有此菜单规则的角色组
        $authGroup = new \app\admin\model\AuthGroup();
        $map[] = ['exp', Db::raw("FIND_IN_SET( {$ruleId}, rules)")];
        $authGroupId = $authGroup->where($map)->column('id');
        if (!$authGroupId) {
            return false;
        }
        //根据角色组获取用户id
        $authGroupAccess = new \app\admin\model\AuthGroupAccess();
        $uids = $authGroupAccess->where(['group_id' => ['in', $authGroupId]])->column('uid');
        $uids = array_merge($uids,[1]);
        return $uids ?: [];
    }

    /**
     * 根据组ID获取用户ID
     *
     * @Description
     * @author wpl
     * @since 2020/08/11 10:31:37 
     * @return void
     */
    public static function getGroupUserId($groupId = null)
    {  
        //根据角色组获取用户id
        $authGroupAccess = new \app\admin\model\AuthGroupAccess();
        $uids = $authGroupAccess->where(['group_id' => $groupId])->column('uid');
        return $uids ?: [];
    }
}
