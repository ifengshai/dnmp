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
 /*
     * 人效统计
     * */
    public function positive_effect_num($platform = 0,$time_str = '',$group_id = 0){
        if($platform){
            $where['type'] = $platform;
        }
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            //默认显示一周的数据
            $seven_startdate = date("Y-m-d", strtotime("-6 day"));
            $seven_enddate = date("Y-m-d 23:59:59");
            $where['create_time'] = ['between', [$seven_startdate, $seven_enddate]];
        }
        if($group_id){
            //查询客服类型
            $group_admin_id = Db::name('admin')->where('group_id',$group_id)->column('id');
            $where['admin_id'] = array('in',$group_admin_id);
        }
        //全部转正人员统计
        $all_already_num = $this->where($where)->sum('reply_count');
        $people_day = $this->where($where)->count();
        if($people_day == 0){
            $all_positive_num = 0;
        }else{
            $all_positive_num = round($all_already_num/$people_day,2);
        }
        //获取该范围内的人员id
        $customer_ids = $this->where($where)->column('admin_id');
        $work_ids = array();
        $nowork_ids = array();
        foreach($customer_ids as $id){
            //查询该用户是否离职, 用户的入职时间
            $admin_info = Db::name('admin')->where('id',$id)->field('createtime,status')->find();
            if($admin_info['status'] == 'normal'){
                $create_date = date('Y-m-d',$admin_info['createtime']);
                $create_date = date("Y-m-d", strtotime("$create_date +2 month"));
                $now_date = date('Y-m-d');
                if($now_date >= $create_date){
                    $work_ids[] = $id;
                }else{
                    $nowork_ids[] = $id;
                }
            }
        }
        //转正人员统计
        $where['admin_id'] = array('in',$work_ids);
        $work_already_num = $this->where($where)->sum('reply_count');
        $work_people_day = $this->where($where)->count();
        if($work_people_day == 0){
            $work_positive_num = 0;
        }else{
            $work_positive_num = round($work_already_num/$work_people_day,2);
        }
        //非转正人员统计
        $where['admin_id'] = array('in',$nowork_ids);
        $nowork_already_num = $this->where($where)->sum('reply_count');
        $nowork_people_day = $this->where($where)->count();
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

    //未达标天数统计
    public function not_up_to_standard_day($platform = 0,$time_str = '',$group_id=0,$admin_id = 0){
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            //默认显示一周的数据
            $seven_startdate = date("Y-m-d", strtotime("-6 day"));
            $seven_enddate = date("Y-m-d 23:59:59");
            $where['create_time'] = ['between', [$seven_startdate, $seven_enddate]];
        }

        if($group_id){
            //查询该组别下的人员
            $ids = Db::name('admin')->where('group_id',$group_id)->column('id');
            $where['admin_id'] = array('in',$ids);
        }

        if($admin_id){
            $where['admin_id'] = $admin_id;
        }

        $where[]  = ['exp', Db::raw("target_count > reply_count")];
        if($platform){
            $where['type'] = $platform;
        }

        $count = $this->where($where)->count();
        return $count;
    }
}
