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
     * 人效统计
     * */
    public function positive_effect_num($platform = 0,$time_str = '',$group_id = 0){
        $this->zendeskTasks = new \app\admin\model\zendesk\ZendeskTasks;
        //查询所有客服人员
        $all_service = Db::name('zendesk_agents')->column('admin_id');
        $group_one = array();
        $group_two = array();
        $work_arr = array();
        $nowork_arr = array();
        foreach ($all_service as $item=>$value){
            //查询用户的入职时间
            $admin_info = Db::name('admin')->where(['id'=>$value,'status'=>'normal'])->field('group_id,createtime')->select();
            if($admin_info['group_id'] == 1){
                $group_one[] = $value;
            }elseif($admin_info['group_id'] == 2){
                $group_two[] = $value;
            }
            $create_date = date('Y-m-d',$admin_info['createtime']);
            $create_date = date("Y-m-d", strtotime("$create_date +2 month"));
            $now_date = date('Y-m-d');
            if($now_date >= $create_date){
                $work_arr[] = $value;
            }else{
                $nowork_arr[] = $value;
            }
        }
        $where['c.is_admin'] = 1;
        $where['z.channel'] = array('neq','voice');
        if($platform){
            $where['platform'] = $platform;
            $task_where['type'] = $platform;
        }
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['c.update_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
            $task_where['create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            //默认显示一周的数据
            $seven_startdate = date("Y-m-d", strtotime("-6 day"));
            $seven_enddate = date("Y-m-d 23:59:59");
            $where['c.update_time'] = ['between', [$seven_startdate, $seven_enddate]];
            $task_where['create_time'] = ['between', [$seven_startdate, $seven_enddate]];
        }
        if($group_id){
            //查询客服类型
           if($group_id == 1){
               $group_admin_id = $group_one;
           }else{
               $group_admin_id = $group_two;
           }
            $where['c.due_id'] = array('in',$group_admin_id);
            $task_where['admin_id'] = array('in',$group_admin_id);
        }
        //全部转正人员统计
        $all_already_num = $this->alias('c')->join('fa_zendesk z','c.zid=z.id')->where($where)->count();
        $people_day = $this->zendeskTasks->where($task_where)->count();
        if($people_day == 0){
            $all_positive_num = 0;
        }else{
            $all_positive_num = round($all_already_num/$people_day,2);
        }
        //转正人员统计
        $work_already_num = $this->alias('c')->join('fa_zendesk z','c.zid=z.id')->where($where)->where(['c.due_id'=>['in',$work_arr]])->count();
        $work_people_day = $this->zendeskTasks->where($task_where)->where(['admin_id'=>['in',$work_arr]])->count();
        if($work_people_day == 0){
            $work_positive_num = 0;
        }else{
            $work_positive_num = round($work_already_num/$work_people_day,2);
        }
        //非转正人员统计
        $nowork_already_num = $this->alias('c')->join('fa_zendesk z','c.zid=z.id')->where($where)->where(['c.due_id'=>['in',$nowork_arr]])->count();
        $nowork_people_day = $this->zendeskTasks->where($task_where)->where(['admin_id'=>['in',$nowork_arr]])->count();
        if($nowork_people_day == 0){
            $nowork_positive_num = 0;
        }else{
            $nowork_positive_num = round($nowork_already_num/$nowork_people_day,2);
        }
        $arr = array(
            'all_positive_num' => $all_positive_num,
            'work_positive_num' => $work_positive_num,
            'nowork_positive_num' => $nowork_positive_num
        );
        return $arr;
    }


    

    







}
