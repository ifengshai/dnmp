<?php

namespace app\admin\model\zendesk;

use think\Model;


class ZendeskAccount extends Model
{

    

    

    // 表名
    protected $name = 'zendesk_account';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;
    // 追加属性
    protected $append = [

    ];
    /**
     * 根据站点获取账户列表
     *
     * @Description
     * @author lsw
     * @since 2020/03/30 15:50:47 
     * @return void
     */
    public function getAccountList($platform,$isEdit=1)
    {
        $where['account_type'] = $platform;
        if(1 == $isEdit){
            $where['is_used']      = 1;
        }
        $result = $this->where($where)->field('id,account_user')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $v){
            $arr[$v['id']] = $v['account_user'];
        }
        return $arr;
    }
    /**
     * 根据用户ID获取用户的名称
     *
     * @Description
     * @author lsw
     * @since 2020/03/30 16:52:47 
     * @return void
     */
    public function getNameById($id)
    {
        return $this->where(['id'=>$id])->field('id,account_id,account_user')->find()->toArray();
    }
}
