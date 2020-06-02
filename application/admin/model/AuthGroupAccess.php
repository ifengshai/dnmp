<?php

namespace app\admin\model;

use think\Model;

class AuthGroupAccess extends Model
{
    //
    /**
     * 判断当前用户所拥有的站点权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 17:57:30 
     * @return void
     */
    public function getUserPrivilege($user_id)
    {
        // $result = AuthGroupAccess::where(['user_id'=>$user_id])->select();
        // $result = collection($result)->toArray();
        // if(!$result){
        //     $arr_group = [];
        //     foreach($result as $k=>$v){
        //         $arr_group[] = $v['group_id'];
        //     }
        //     $info = $this->where('id','in',$arr_group)->field('id,rules')->select();
        //     $info = 
        // }
        //$result = $this->alias('')
    }
}
