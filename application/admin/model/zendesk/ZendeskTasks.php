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
            $start = $createat[0];
            $end = $createat[3];

        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
        }
        $starttime = strtotime($start);
        $endtime   = strtotime($end);
        //求出中间的所有数
        $arr = [];
        for ($starttime; $starttime <= $endtime; $starttime += 86400) {
            $arr[] = $starttime;
        }
        $where['is_public'] = 1;
        if($platform){
            $where['platform'] = $platform;
            $task_where['type'] = $platform;
        }
        if($group_id){
            //查询客服类型
            $group_admin_id = Db::name('admin')->where(['group_id'=>$group_id,'status'=>'normal'])->column('id');
            $where['due_id'] = $task_where['admin_id'] = array('in',$group_admin_id);
        }
        if($admin_id){
            $where['due_id'] = $task_where['admin_id'] = $admin_id;
        }
        //未达标天数
        $no_qualified_day = 0;
        foreach ($arr as $v) {
            $where['update_time'] = $task_where['create_time'] = ['between', [date('Y-m-d 00:00:00', $v), date('Y-m-d H:i:s', $v + 86400)]];
            //这天的回复量
            $customerReply = $this->zendeskComments->where($where)->count();
            //这天的目标量
            $check_count  =  $this->where($task_where)->value('target_count');
            if ($customerReply < $check_count) {
                $no_qualified_day++;
            }
        }
        return $no_qualified_day;
    }
    public function unupto_day($time_str){
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
    }

}
