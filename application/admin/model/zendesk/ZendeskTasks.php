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
            $where['admin_id'] = $ids;
        }
        if($admin_id){
            $where['admin_id'] = $admin_id;
        }
        $where['target_count']  = array('exp',Db::raw(' > reply_count '));
        if($platform){
            $where['type'] = $platform;
        }

        $count = $this->where($where)->count();

        return $count;
    }
    /*
    * 统计处理量
    * */
    public function dealnum_statistical($platform = 0,$time_str = '',$group_id = 0,$admin_id = 0){
        if($platform){
            $where['type'] = $platform;
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
            $where['admin_id'] = array('in',$group_admin_id);
        }
        if($admin_id){
            $where['admin_id'] = $admin_id;
        }
        $count = $this->where($where)->sum('reply_count');
        return $count;
    }
}
