<?php

namespace app\admin\model\zendesk;

use think\Model;
use think\Db;

class ZendeskTasks extends Model
{
    // 表名
    protected $name = 'zendesk_tasks';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [

    ];

    //未达标天数统计
    public function not_up_to_standard_day($platform = 0,$time_str = '',$group_id = 0,$admin_id = 0){
        $this->zendeskComments = new \app\admin\model\zendesk\ZendeskComments;
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['update_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
            $task_where['create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $where['update_time'] = ['between', [$start,$end]];
            $task_where['create_time'] = ['between', [$start,$end]];
        }
        //时间段内的回复数量
        if($platform){
            $where['platform'] = $platform;
            $task_where['type'] = $platform;
        }
        if($group_id){
            //查询客服类型
            $group_admin_id = Db::name('admin')->where(['group_id'=>$group_id,'status'=>'normal'])->column('id');
            $where['due_id'] = array('in',$group_admin_id);
            $task_where['admin_id'] = array('in',$group_admin_id);
        }
        if($admin_id){
            $where['due_id'] = $admin_id;
            $task_where['admin_id'] = $admin_id;
        }
        //回复数量
        $reply_num = $this->zendeskComments->where($where)->count();
        //目标量
        $complete_num = $this->where($task_where)->sum('target_count');
        if($reply_num>=$complete_num){
            $count = 0;
        }else{
            $count = $complete_num - $reply_num;
        }
        return $count;
    }


}
