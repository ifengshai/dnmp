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
            $where['c.platform'] = $platform;
        }
        $where['c.due_id'] = ['neq',0];
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['c.create_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $where['c.create_time'] = ['between', [$start,$end]];
        }
        if($group_id){
            //查询客服类型
            $group_admin_id = Db::name('admin')->where(['group_id'=>$group_id])->column('id');
            $where['c.due_id'] = array('in',$group_admin_id);
        }
        if($admin_id){
            $where['c.due_id'] = $admin_id;
        }
        $where['c.is_admin'] = 1;
        $where['c.is_public'] = ['neq',2];
        $where['z.channel'] = ['neq','voice'];
        $count = $this->alias('c')->join('fa_zendesk z','c.zid=z.id')->where($where)->count();
        return $count;
    }

    /**计算客服评价数量以及好评率
     * @param $platform
     * @param $time_str1
     * @param $value
     * @param  int  $is_vip
     *
     * @author liushiwei
     * @date   2021/9/15 10:26
     */
    public function dealnum_estimate($platform,$time_str ='',$admin_id=0,$is_vip=1)
    {
        if($platform){
            $where['c.platform'] = $platform;
        }
        $where['c.due_id'] = ['neq',0];
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['c.zendesk_update_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $where['c.zendesk_update_time'] = ['between', [$start,$end]];
        }
        if($admin_id){
            $where['c.due_id'] = $admin_id;
        }
        $where['c.is_admin'] = 1;
        $where['c.is_public'] = ['neq',2];
        $where['z.channel'] = ['neq','voice'];
        //关闭状态的
        $where['c.status'] = 5;

        $count = $this->alias('c')->join('fa_zendesk z','c.zid=z.id')->where($where)->count();
        return $count;
    }

    

    







}
