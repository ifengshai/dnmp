<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class WorkloadStatistics extends Model
{
    // 表名
    protected $name = 'workload_statistics';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];   
    /**
     * 获取昨天数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 09:57:00 
     * @return void
     */
    public function getyesterdayData($type=1)
    {
        $map['create_date'] = date("Y-m-d", strtotime("-1 day"));
        if($type<10){
            $where['platform'] = $type;
            return $this->where($map)->where($where)->find();
        }else{
            $info = $this->where($map)->select();
            if(!empty($info)){
                $arr['wait_num'] = $arr['increment_num'] = $arr['reply_num'] = $arr['waiting_num'] = $arr['pending_num'] = 0;
                foreach($info as  $k){
                    $arr['wait_num']+=$k['wait_num'];
                    $arr['increment_num']+=$k['increment_num'];
                    $arr['reply_num']+=$k['reply_num'];
                    $arr['waiting_num']+=$k['waiting_num'];
                    $arr['pending_num']+=$k['pending_num'];
                }
            }else{
                $arr['wait_num']       = 0;
                $arr['increment_num']  = 0;
                $arr['reply_num']      = 0;
                $arr['waiting_num']    = 0;
                $arr['pending_num']    = 0;                
            }
            return $arr;
        }
        
    }
    /**
     * 获取今天数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 10:18:39 
     * @return void
     */
    public function gettodayData($type=1)
    {
        if($type<10){
            $where['type'] = $type;
        }
        //zendesk
        $zendesk_model = Db::name('zendesk');
        //zendesk_comments
        //$zendesk_comments = Db::name('zendesk_comments');
        //计算前一天的销量
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59");
        $map['create_time'] = $date['c.create_time'] = $update['update_time'] =  ['between', [$stime, $etime]];
        //获取昨天待处理的open、new量
        $wait_num = $zendesk_model->where($where)->where(['status' => ['in','1,2'],'channel' => ['neq','voice']])->where($map)->count("*");
        //获取昨天新增的open、new量
        $increment_num = $zendesk_model->where($where)->where(['status' => ['in','1,2'],'channel' => ['neq','voice']])->where($update)->count("*");
        //获取昨天已回复量
        //$reply_num  = $zendesk_comments->where($map)->where(['is_public'=>1])->count("*");
        $reply_num  = $zendesk_model->alias('z')->join('fa_zendesk_comments c','z.id=c.zid')->where($date)->where(['is_public'=>1])->count("*");
        //获取昨天待分配的open、new量
        $waiting_num = $zendesk_model->where($where)->where(['status' => ['in','1,2'],'channel' => ['neq','voice']])->where($map)->where(['is_hide'=>1])->count("*");
        //获取昨天的pendding量
        $pending_num = $zendesk_model->where($where)->where(['status' => ['eq','3'],'channel' => ['neq','voice']])->where($map)->count("*");
        $data['wait_num']       = $wait_num;
        $data['increment_num']  = $increment_num;
        $data['reply_num']      = $reply_num;
        $data['waiting_num']    = $waiting_num;
        $data['pending_num']    = $pending_num;
        return $data;
    }    
    /**
     * 过去7天数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 10:08:17 
     * @return void
     */
    public function getSevenData($type=1)
    {
        $stime = date("Y-m-d", strtotime("-7 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        if($type<10){
            $map['platform'] = $type;
        }
        $info = $this->where($map)->select();
        if(!empty($info)){
            $arr['wait_num'] = $arr['increment_num'] = $arr['reply_num'] = $arr['waiting_num'] = $arr['pending_num'] = 0;
            foreach($info as  $k){
                $arr['wait_num']+=$k['wait_num'];
                $arr['increment_num']+=$k['increment_num'];
                $arr['reply_num']+=$k['reply_num'];
                $arr['waiting_num']+=$k['waiting_num'];
                $arr['pending_num']+=$k['pending_num'];
            }
        }else{
            $arr['wait_num']       = 0;
            $arr['increment_num']  = 0;
            $arr['reply_num']      = 0;
            $arr['waiting_num']    = 0;
            $arr['pending_num']    = 0; 
        }
        return $arr;        
    }
    /**
     * 过去30天数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 10:12:05 
     * @return void
     */
    public function getthirdData($type)
    {
        $stime = date("Y-m-d", strtotime("-30 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        if($type<10){
            $map['platform'] = $type;
        }
        $info = $this->where($map)->select();
        if(!empty($info)){
            $arr['wait_num'] = $arr['increment_num'] = $arr['reply_num'] = $arr['waiting_num'] = $arr['pending_num'] = 0;
            foreach($info as  $k){
                $arr['wait_num']+=$k['wait_num'];
                $arr['increment_num']+=$k['increment_num'];
                $arr['reply_num']+=$k['reply_num'];
                $arr['waiting_num']+=$k['waiting_num'];
                $arr['pending_num']+=$k['pending_num'];
            }
        }else{
            $arr['wait_num']       = 0;
            $arr['increment_num']  = 0;
            $arr['reply_num']      = 0;
            $arr['waiting_num']    = 0;
            $arr['pending_num']    = 0; 
        }
        return $arr;
    }
    /**
     * 获取两段时间的数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 11:15:06 
     * @return void
     */
    public function gettwoTimeData($starttime,$endtime,$type)
    {
        if(empty($starttime) || empty($endtime)){
            return [];
        }
        $map['create_date'] = ['between',[$starttime,$endtime]];
        if($type<10){
            $map['platform'] = $type;
        }
        $info = $this->where($map)->select();
        if(!empty($info)){
            $arr['wait_num'] = $arr['increment_num'] = $arr['reply_num'] = $arr['waiting_num'] = $arr['pending_num'] = 0;
            foreach($info as  $k){
                $arr['wait_num']+=$k['wait_num'];
                $arr['increment_num']+=$k['increment_num'];
                $arr['reply_num']+=$k['reply_num'];
                $arr['waiting_num']+=$k['waiting_num'];
                $arr['pending_num']+=$k['pending_num'];
            }
        }else{
            $arr['wait_num']       = 0;
            $arr['increment_num']  = 0;
            $arr['reply_num']      = 0;
            $arr['waiting_num']    = 0;
            $arr['pending_num']    = 0; 
        }
        return $arr;        
    }

    /**
     * 统计30天订单量
     *
     * @Description
     * @author wpl
     * @since 2020/03/18 14:34:01 
     * @return void
     */
    public function get30daysNum()
    {
        $stime = date("Y-m-d", strtotime("-30 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        return $this->where($map)->sum('all_sales_num');
    }
}
