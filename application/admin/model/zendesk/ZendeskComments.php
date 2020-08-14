<?php

namespace app\admin\model\zendesk;

use app\admin\model\zendesk\ZendeskAgents;
use think\Db;
use think\Model;


class ZendeskComments extends Model
{
    // 表名
    protected $name = 'zendesk_comments';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [

    ];
    public function agent()
    {
        return $this->hasOne(ZendeskAgents::class,'admin_id','due_id');
    }
    /*
      * 统计处理量
      * */
    public function dealnum_statistical($platform = 0,$time_str = '',$group_id = 0,$admin_id = 0){
        if($platform){
            $where['platform'] = $platform;
        }
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $where['create_time'] = ['between', [$start,$end]];
        }
        if($group_id){
            //查询客服类型
            $group_admin_id = Db::name('admin')->where(['group_id'=>$group_id])->column('id');
            $where['due_id'] = array('in',$group_admin_id);
        }
        if($admin_id){
            $where['due_id'] = $admin_id;
        }
        $where['is_admin'] = 1;
        $where['is_public'] = 1;
        $count = $this->where($where)->count();
        return $count;
    }

    

    







}
