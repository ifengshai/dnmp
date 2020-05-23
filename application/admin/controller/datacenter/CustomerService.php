<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;
use think\Cache;
use app\admin\model\AuthGroupAccess;
use app\admin\model\Admin;

class CustomerService extends Backend
{
    protected $model = null;
    protected $step  = null;
    protected $workload = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model   = new \app\admin\model\saleaftermanage\WorkOrderList;
        $this->step    = new \app\admin\model\saleaftermanage\WorkOrderMeasure;
        $this->workload = new \app\admin\model\WorkloadStatistics;
    }
    /**
     * 客服数据(首页)
     *
     * @Description
     * @author lsw
     * @since 2020/05/11 14:42:10
     * @return void
     */
    public function index()
    {
        //分组数据
        $infoOne = $this->customers_by_group(1);
        $infoTwo = $this->customers_by_group(2);
        //总览数据start
        //1.今天数据
        $todayData = $this->workload->gettodayData(1);
        //昨天数据
        $yesterdayData = $this->workload->getyesterdayData(1);
        //过去7天数据
        $servenData = $this->workload->getSevenData(1);
        //过去30天数据
        $thirdData = $this->workload->getthirdData(1);

        //总览数据end

        //工作量概况start
        $start = date('Y-m-d', strtotime('-7 day'));
        $end   = date('Y-m-d');
        $yesterStart = date('Y-m-d', strtotime('-1 day'));
        $workload_map['c.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];        
        $customerReply = $this->workload_info($workload_map,$start,$end,1);
        if(!empty($customerReply)){
            unset($customerReply['handleNum']);
            $replyArr = [];
            foreach ($customerReply as $ok =>$ov) {
                if (array_key_exists($ov['assign_id'], $infoOne)) {
                    $replyArr[$ov['assign_id']]['create_user_name'] = $infoOne[$ov['assign_id']];
                    $replyArr[$ov['assign_id']]['group']       = $ov['group'];
                    $replyArr[$ov['assign_id']]['yester_num'] = $this->calculate_no_qualified_day($ov['assign_id'],$yesterStart,$end);
                    $replyArr[$ov['assign_id']]['counter']   = $ov['counter'];
                    $replyArr[$ov['assign_id']]['no_qualified_day'] = $ov['no_qualified_day'];
                }
                if (array_key_exists($ov['create_user_id'], $infoTwo)) {
                    $replyArr[$ov['assign_id']]['create_user_name'] = $infoTwo[$ov['assign_id']];
                    $replyArr[$ov['assign_id']]['group']       = $ov['group'];
                    $replyArr[$ov['assign_id']]['yester_num'] = $this->calculate_no_qualified_day($ov['assign_id'],$yesterStart,$end);
                    $replyArr[$ov['assign_id']]['counter']   = $ov['counter'];
                    $replyArr[$ov['assign_id']]['no_qualified_day'] = $ov['no_qualified_day'];
                }                
            }            
        }
        //工作量概况end

        //工单统计信息
        $map['complete_time'] = $map_create['create_time'] = $map_measure['w.create_time'] = ['between', [date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y"))), date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")))]];

        $workList = $this->works_info([],$map);
        if(!empty($workList)){
            unset($workList['workOrderNum'],$workList['totalOrderMoney'],$workList['replacementNum'],$workList['refundMoneyNum'],$workList['refundMoney']);
            $workArr = [];
            foreach ($workList as $ok =>$ov) {
                if (array_key_exists($ov['create_user_id'], $infoOne)) {
                    $workArr[$ov['create_user_id']]['create_user_name'] = $infoOne[$ov['create_user_id']];
                    $workArr[$ov['create_user_id']]['create_num']       = $this->model->where($map_create)->where('create_user_id',$ov['create_user_id'])->count('*');
                    $workArr[$ov['create_user_id']]['counter']   = $ov['counter'];
                    $workArr[$ov['create_user_id']]['base_grand_total'] = $ov['base_grand_total'];
                    $workArr[$ov['create_user_id']]['coupon']    = $ov['coupon'];
                    $workArr[$ov['create_user_id']]['refund_num'] = $ov['refund_num'];
                    $workArr[$ov['create_user_id']]['replacement_num'] = $ov['replacement_num'];
                    $workArr[$ov['create_user_id']]['total_refund_money'] = $ov['total_refund_money'];
                }
                if (array_key_exists($ov['create_user_id'], $infoTwo)) {
                    $workArr[$ov['create_user_id']]['create_user_name'] = $infoTwo[$ov['create_user_id']];
                    $workArr[$ov['create_user_id']]['create_num']       = $this->model->where($map_create)->where('create_user_id',$ov['create_user_id'])->count('*');
                    $workArr[$ov['create_user_id']]['counter']   = $ov['counter'];
                    $workArr[$ov['create_user_id']]['base_grand_total'] = $ov['base_grand_total'];
                    $workArr[$ov['create_user_id']]['coupon']    = $ov['coupon'];
                    $workArr[$ov['create_user_id']]['refund_num'] = $ov['refund_num'];
                    $workArr[$ov['create_user_id']]['replacement_num'] = $ov['replacement_num'];
                    $workArr[$ov['create_user_id']]['total_refund_money'] = $ov['total_refund_money'];
                }                
            }        
        }
        //工单处理概况信息start
        //1.求出三个审批人
        $kefumanage = config('workorder.kefumanage');
        $examine = [];
        foreach($kefumanage as $k => $v){
            $examine[] = $k;
        }
        $examine[] = config('workorder.customer_manager');
        $examinePerson = $this->customers();
        $examineArr = [];
        foreach($examinePerson as $ek => $ev){
            if(in_array($ek,$examine)){
                $examineArr[$ek] = $ev;
            }
        }
        //左边右边的措施
        $step = config('workorder.step');
        $workorder_handle_left_data = $this->workorder_handle_left($map_create,$examineArr);
        $workorder_handle_right_data = $this->workorder_handle_right($map_measure,$step);
        //工单处理概况信息end
        //跟单概况 start
        $warehouse_problem_type = config('workorder.warehouse_problem_type');
        $warehouse_handle       = $this->warehouse_handle($map_create,$warehouse_problem_type);
        //跟单概况 end 
        $orderPlatformList = config('workorder.platform');
        $this->view->assign(compact('orderPlatformList', 'workList','infoOne','infoTwo','workArr','examineArr','workorder_handle_left_data',
        'step','workorder_handle_right_data','warehouse_problem_type','warehouse_handle','todayData','yesterdayData','servenData','thirdData','replyArr'));
        return $this->view->fetch();
    }
    /**
     * 首页工作量概况
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 11:02:02 
     * @return void
     */
    public function workload_general()
    {
        if($this->request->isAjax()){
            $params = $this->request->param();
            $time = explode(' ', $params['time']);
            $map['create_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            $platform = $params['platform'];
            //1.今天数据
            $todayData = $this->workload->gettodayData($platform);
            //昨天数据
            $yesterdayData = $this->workload->getyesterdayData($platform);
            //过去7天数据
            $servenData = $this->workload->getSevenData($platform);
            //过去30天数据
            $thirdData = $this->workload->getthirdData($platform);            
            $info = $this->workload->gettwoTimeData($time[0],$time[3],$platform);
            $data = [
                'todayData' => $todayData,
                'yesterdayData' => $yesterdayData,
                'servenData' => $servenData,
                'thirdData'  => $thirdData,
                'start'      => $time[0],
                'end'        => $time[3],   
                'info'       => $info   
            ];
            $this->success('','',$data);
        }
    }
    /**
     * 首页工单处理概况异步请求
     *
     * @Description
     * @author lsw
     * @since 2020/05/22 09:46:17 
     * @return void
     */
    public function workorder_general()
    {
        if($this->request->isAjax()){
            $params = $this->request->param();
            if ($params['time']) {
                $timeOne = explode(' ', $params['time']);
                $map_create['create_time'] = $map_measure['w.create_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $map_create['create_time'] = $map_measure['w.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            //1.求出三个审批人
            $kefumanage = config('workorder.kefumanage');
            $examine = [];
            foreach($kefumanage as $k => $v){
                $examine[] = $k;
            }
            $examine[] = config('workorder.customer_manager');
            $examinePerson = $this->customers();
            $examineArr = [];
            foreach($examinePerson as $ek => $ev){
                if(in_array($ek,$examine)){
                    $examineArr[$ek] = $ev;
                }
            }
            $step = config('workorder.step');
            $workorder_handle_left_data = $this->workorder_handle_left($map_create,$examineArr);
            $workorder_handle_right_data = $this->workorder_handle_right($map_measure,$step);
            $data =[
                'examineArr' => $examineArr,
                'step'       => $step,
                'workorder_handle_left_data' => $workorder_handle_left_data,
                'workorder_handle_right_data' => $workorder_handle_right_data 
            ];
            $this->success('','',$data);                       
        }
    }
    /**
     * 
     *
     * @Description
     * @author lsw
     * @since 2020/05/22 11:20:48 
     * @return void
     */
    public function warehouse_general()
    {
        if($this->request->isAjax()){
            $params = $this->request->param();
            if ($params['time']) {
                $timeOne = explode(' ', $params['time']);
                $map_create['create_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $map_create['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            //1.求出三个审批人
            $warehouse_problem_type = config('workorder.warehouse_problem_type');
            $warehouse_data       = $this->warehouse_handle($map_create,$warehouse_problem_type);
            $data =[
                'warehouse_problem_type' => $warehouse_problem_type,
                'warehouse_data' => $warehouse_data 
            ];
            $this->success('','',$data);                       
        }
    }
    /**
     * 首页工单处理概况左边部分
     *
     * @Description
     * @author lsw
     * @since 2020/05/21 15:47:25 
     * @return void
     */
    public function workorder_handle_left($map,$examinArr)
    {
        $where['is_check'] = 1;
        $where['work_type'] = 1;
        $where['work_status'] = ['lt',2];
        //求出主管的超时时间
        $time_out = config('workorder.manage_time_out');
        $workList = $this->model->where($map)->where($where)->field('assign_user_id,submit_time,check_time')->select();
        $workList = collection($workList)->toArray($workList);
        if(!empty($workList)){
            $arr = [];
            foreach($examinArr as $ek => $ev){
                $arr[$ek]['no_time_out_checked'] = $arr[$ek]['time_out_checked'] = $arr[$ek]['no_time_out_check'] = $arr[$ek]['time_out_check'] = 0; 
            }
            foreach($workList as $k =>$v){
                if(array_key_exists($v['assign_user_id'],$examinArr)){
                    //审批时间存在证明已经审批
                    if($v['check_time']){
                        //如果两个时间差小于指定超时时间说明未超时
                        if( $time_out >(strtotime($v['check_time']) - strtotime($v['submit_time']))){
                            //未超时已审批
                            $arr[$v['assign_user_id']]['no_time_out_checked']++; 
                        }else{
                            //超时已审批
                            $arr[$v['assign_user_id']]['time_out_checked']++;
                        }
                    }else{
                        //审批时间不存在证明没有审批,判断提交时间和现在的时间比较是否超时
                        //如果两个时间差小于指定超时时间说明未超时
                        if( $time_out>(strtotime("now") - strtotime($v['submit_time']))){
                            //未超时未审批
                            $arr[$v['assign_user_id']]['no_time_out_check']++;
                        }else{
                            //超时未审批
                            $arr[$v['assign_user_id']]['time_out_check']++;
                        }
                    }
                }
            }
        }
        return $arr ?: false;
    }
    /**
     * 首页工单处理右边部分
     *
     * @Description
     * @author lsw
     * @since 2020/05/21 18:24:43 
     * @return void
     */
    public function workorder_handle_right($map,$step)
    {
        $where['is_check'] = 1;
        $where['work_type'] = 1;
        $where['work_status'] = ['lt',2];
        //求出措施的超时时间
        $time_out = config('workorder.step_time_out');
        $workMeasure = $this->model->where($map)->where($where)->alias('w')->join('work_order_measure m','w.id=m.work_id')->field('w.check_time,m.operation_time,m.measure_choose_id')->select();
        $workMeasure = collection($workMeasure)->toArray($workMeasure);
        if(!empty($workMeasure)){
            $arr = [];
            //no_time_out_handled 未超时已处理
            //time_out_handled  超时已处理
            //no_time_out_handle 未超时未处理
            //time_out_handle 超时未处理
            foreach($step as $ek => $ev){
                $arr[$ek]['no_time_out_handled'] = $arr[$ek]['time_out_handled'] = $arr[$ek]['no_time_out_handle'] = $arr[$ek]['time_out_handle'] = 0; 
            }
            foreach($workMeasure as $k =>$v){
                if(array_key_exists($v['measure_choose_id'],$step)){
                    //处理时间存在证明已经审批
                if($v['operation_time']){
                        //如果存在超时时间
                    if($time_out[$v['measure_choose_id']]){
                        //如果两个时间差小于指定超时时间说明未超时
                        if( $time_out[$v['measure_choose_id']] >(strtotime($v['operation_time']) - strtotime($v['check_time']))){
                            //未超时已处理
                            $arr[$v['measure_choose_id']]['no_time_out_handled']++; 
                        }else{
                            //超时已处理
                            $arr[$v['measure_choose_id']]['time_out_handled']++;
                        }
                    }else{ //如果不存在超时时间
                            $arr[$v['measure_choose_id']]['no_time_out_handled']++;      
                    }

                }else{
                        //审批时间不存在证明没有审批,判断提交时间和现在的时间比较是否超时
                        //如果两个时间差小于指定超时时间说明未超时
                        //如果存在超时时间
                        if($time_out[$v['measure_choose_id']]){
                            //如果两个时间差小于指定超时时间说明未超时
                            if( $time_out[$v['measure_choose_id']] >(strtotime("now") - strtotime($v['check_time']))){
                                //未超时已处理
                                $arr[$v['measure_choose_id']]['no_time_out_handle']++; 
                            }else{
                                //超时已处理
                                $arr[$v['measure_choose_id']]['time_out_handle']++;
                            }
                        }else{ //如果不存在超时时间
                                $arr[$v['measure_choose_id']]['no_time_out_handle']++;      
                        }
                    }
                }
            }
        }
        return $arr ?: false;
    }
    /**
     * 首页跟单处理
     *
     * @Description
     * @author lsw
     * @since 2020/05/22 08:46:37 
     * @return void
     */
    public function warehouse_handle($map,$warehouse_problem_type)
    {
        $where['work_type'] = 2;
        $where['work_status'] = ['lt',2];
        //求出主管的超时时间
        $time_out = config('workorder.warehouse_time_out');
        $workList = $this->model->where($map)->where($where)->field('problem_type_id,check_time,complete_time')->select();
        $workList = collection($workList)->toArray($workList);
        if(!empty($workList)){
            $arr = [];
            //no_time_out_handled 未超时已处理
            //time_out_handled  超时已处理
            //no_time_out_handle 未超时未处理
            //time_out_handle 超时未处理
            foreach($warehouse_problem_type as $ek => $ev){
                $arr[$ek]['no_time_out_handled'] = $arr[$ek]['time_out_handled'] = $arr[$ek]['no_time_out_handle'] = $arr[$ek]['time_out_handle'] = 0; 
            }
            foreach($workList as $k =>$v){
                if(array_key_exists($v['problem_type_id'],$warehouse_problem_type)){
                    //处理时间存在证明已经审批
                    if($v['complete_time']){
                        //如果两个时间差小于指定超时时间说明未超时
                        if( $time_out[$v['problem_type_id']] >(strtotime($v['complete_time']) - strtotime($v['check_time']))){
                            //未超时已审批
                            $arr[$v['problem_type_id']]['no_time_out_handled']++; 
                        }else{
                            //超时已审批
                            $arr[$v['problem_type_id']]['time_out_handled']++;
                        }
                    }else{
                        //审批时间不存在证明没有审批,判断提交时间和现在的时间比较是否超时
                        //如果两个时间差小于指定超时时间说明未超时
                        if( $time_out[$v['problem_type_id']]>(strtotime("now") - strtotime($v['check_time']))){
                            //未超时未审批
                            $arr[$v['problem_type_id']]['no_time_out_handle']++;
                        }else{
                            //超时未审批
                            $arr[$v['problem_type_id']]['time_out_handle']++;
                        }
                    }
                }
            }
        }
        return $arr ?: false;
    }
    /**
     * 工作量统计
     *
     * @Description
     * @author lsw
     * @since 2020/05/18 09:18:08
     * @return void
     */
    public function workload()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();
            $platform = $params['order_platform'];
            if ($params['one_time']) {
                $timeOne = explode(' ', $params['one_time']);
                $mapOne['c.create_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $mapOne['c.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            if ($params['two_time']) {
                $timeTwo = explode(' ', $params['two_time']);
                $mapTwo['c.create_time'] = ['between', [$timeTwo[0] . ' ' . $timeTwo[1], $timeTwo[3] . ' ' . $timeTwo[4]]];
            }
            $worklistOne = $this->workload_info($mapOne,$timeOne[0],$timeOne[3],$platform);
            if (!empty($mapTwo)) {
                $worklistTwo = $this->workload_info($mapOne,$timeTwo[0],$timeTwo[3],$platform);
            }
            //只有一个没有第二个
            if ($worklistOne && !$mapTwo) {
                //取出总数
                $handleNum          = $worklistOne['handleNum'];
                if ($timeOne) {
                    $start = $timeOne[0];
                    $end   = $timeOne[3];
                } else {
                    $start = date('Y-m-d', strtotime('-7 day'));
                    $end   = date('Y-m-d');
                }
                //销毁变量
                unset($worklistOne['handleNum']);
                $this->view->assign([
                    'type'=>2,
                    'customerReply'  => $worklistOne,
                    'start'     => $start,
                    'end'       => $end,
                    'platform'  => $platform
                    ]);
            } elseif ($worklistOne && $worklistTwo) { //两个提交的数据
                //取出总数
                $handleNum       = $worklistOne['handleNum'] + $worklistTwo['handleNum'];
                if ($timeOne) {
                    $startOne = $timeOne[0];
                    $endOne   = $timeOne[3];
                } else {
                    $startOne = date('Y-m-d', strtotime('-7 day'));
                    $endOne   = date('Y-m-d');
                }
                $startTwo = $timeTwo[0];
                $endTwo   = $timeTwo[3]; 
                //销毁变量
                unset($worklistOne['handleNum'],$worklistTwo['handleNum']);
                $info = $this->customers();
                $workArr = [];
                foreach ($worklistOne as $ok =>$ov) {
                    if (array_key_exists($ov['assign_id'], $info)) {
                        $workArr[$ov['assign_id']]['create_user_name'] = $info[$ov['create_user_id']];
                        $workArr[$ov['assign_id']]['group']            = $ov['group'];
                        $workArr[$ov['assign_id']]['one']['counter']   = $ov['counter'];
                        $workArr[$ov['assign_id']]['one']['no_qualified_day'] = $ov['no_qualified_day'];
                    }
                }
                foreach ($worklistTwo as $tk =>$tv) {
                    if (array_key_exists($tv['assign_id'], $info)) {
                        $workArr[$tv['assign_id']]['create_user_name'] = $info[$tv['create_user_id']];
                        $workArr[$tv['assign_id']]['group']            = $tv['group'];
                        $workArr[$tv['assign_id']]['two']['counter']   = $tv['counter'];
                        $workArr[$tv['assign_id']]['two']['no_qualified_day'] = $tv['no_qualified_day'];
                    }
                }
                $this->view->assign([
                     'type'         =>3,
                     'workListOne'  => $worklistOne,
                     'workListTwo'  => $worklistTwo,
                     'startOne'     => $startOne,
                     'endOne'       => $endOne,
                     'startTwo'     => $startTwo,
                     'endTwo'       => $endTwo,
                     'startTwo'     => $startTwo,
                     'endTwo'       => $endTwo,
                     'platform'     => $platform,
                     'info'         => $info,
                     'workArr'      => $workArr
                     ]);
            }
            $orderPlatformList = config('workorder.platform');
            $this->view->assign(compact('orderPlatformList', 'handleNum'));
        } else {
            $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
            //默认显示
            //根据筛选时间求出客服部门下面所有有数据人员
            $start = date('Y-m-d', strtotime('-7 day'));
            $end   = date('Y-m-d');
            $map['c.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            $where['c.is_public'] = 1;
            //平台
            $where['z.type'] = 1;
            //客服处理量
            $customerReply = $this->zendeskComments->alias('c')->join('fa_zendesk z','c.author_id=z.assignee_id')->where($where)->where($map)->field('count(*) as counter,z.assign_id')->group('c.author_id')->select();
            $customerReply = collection($customerReply)->toArray();
            //客服分组
            $info = $this->customers();
            $kefumanage = config('workorder.kefumanage');
            if (!empty($customerReply)) {
                $handleNum = 0;
                foreach ($customerReply as $k => $v) {
                    //客服分组
                    if (in_array($v['assign_id'], $kefumanage[95])) {
                        $customerReply[$k]['group'] = 'B组';
                    } elseif (in_array($v['assign_id'], $kefumanage[117])) {
                        $customerReply[$k]['group'] = 'A组';
                    } else {
                        $customerReply[$k]['group'] = '未知';
                    }
                    if(array_key_exists($v['assign_id'], $info)){
                        $customerReply[$k]['create_user_name'] = $info[$v['assign_id']];
                    }
                        $customerReply[$k]['no_qualified_day'] = $this->calculate_no_qualified_day($v['assign_id'],$start,$end);
                        $handleNum+=$v['counter'];                    
                }
            }
            $orderPlatformList = config('workorder.platform');
            $this->view->assign('type', 1);
            $this->view->assign(compact('orderPlatformList', 'customerReply', 'start', 'end','handleNum'));
        }
        return $this->view->fetch();
    }
    /**
     * 计算未达标天数
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 14:41:32 
     * @return void
     */
    public function calculate_no_qualified_day($admin_id,$start,$end)
    {
        $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        $this->ZendeskTasks     = new \app\admin\model\zendesk\ZendeskTasks;
        $starttime = strtotime($start);
        $endtime   = strtotime($end);
        //求出中间的所有数
        $arr = [];
        for($starttime;$starttime<=$endtime;$starttime+=86400){
            $arr[] = $starttime;
        }
        $where['c.is_public'] = 1;
        $where['z.assignee_id'] = $assignee['assignee_id'] =  $admin_id;
        //未达标天数
        $no_qualified_day = 0;
        foreach($arr as $v){
            $map['c.create_time'] =$assignee['create_time'] = ['between', [date('Y-m-d 00:00:00', $v), date('Y-m-d H:i:s', $v+86400)]];
            //这天的回复量
            $customerReply = $this->zendeskComments->alias('c')->join('fa_zendesk z','c.author_id=z.assignee_id')->where($where)->where($map)->count("*");
            //这天的目标量
            $check_count  =  $this->ZendeskTasks->where($assignee)->value('check_count');
            if($customerReply<$check_count){
                $no_qualified_day++;
            }
        }
        return $no_qualified_day;
    }
    /**
     * 工单统计
     *
     * @Description
     * @author lsw
     * @since 2020/05/15 10:27:04
     * @return void
     */
    public function workstatistics()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();
            $platform = $params['order_platform'];
            if ($params['one_time']) {
                $timeOne = explode(' ', $params['one_time']);
                $mapOne['complete_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $mapOne['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            if ($params['two_time']) {
                $timeTwo = explode(' ', $params['two_time']);
                $mapTwo['complete_time'] = ['between', [$timeTwo[0] . ' ' . $timeTwo[1], $timeTwo[3] . ' ' . $timeTwo[4]]];
            }
            if (10 !=$params['order_platform']) {
                $where['work_platform'] = $params['order_platform'];
            }
            $worklistOne = $this->works_info($where, $mapOne);
            if (!empty($mapTwo)) {
                $worklistTwo = $this->works_info($where, $mapTwo);
            }
            //只有一个没有第二个
            if ($worklistOne && !$mapTwo) {
                //取出总数
                $workOrderNum       = $worklistOne['workOrderNum'];
                $totalOrderMoney    = $worklistOne['totalOrderMoney'];
                $replacementNum     = $worklistOne['replacementNum'];
                $refundMoneyNum     = $worklistOne['refundMoneyNum'];
                $refundMoney        = $worklistOne['refundMoney'];
                if ($timeOne) {
                    $start = $timeOne[0];
                    $end   = $timeOne[3];
                } else {
                    $start = date('Y-m-d', strtotime('-7 day'));
                    $end   = date('Y-m-d');
                }
                //销毁变量
                unset($worklistOne['workOrderNum'],$worklistOne['totalOrderMoney'],$worklistOne['replacementNum'],$worklistOne['refundMoneyNum'],$worklistOne['refundMoney']);
                $this->view->assign([
                    'type'=>2,
                    'workList'  => $worklistOne,
                    'start'     => $start,
                    'end'       => $end,
                    'platform'  => $platform
                    ]);
            } elseif ($worklistOne && $worklistTwo) { //两个提交的数据
                //取出总数
                $workOrderNum       = $worklistOne['workOrderNum'] + $worklistTwo['workOrderNum'];
                $totalOrderMoney    = $worklistOne['totalOrderMoney'] + $worklistTwo['totalOrderMoney'];
                $replacementNum     = $worklistOne['replacementNum'] + $worklistTwo['replacementNum'];
                $refundMoneyNum     = $worklistOne['refundMoneyNum'] + $worklistTwo['refundMoneyNum'];
                $refundMoney        = $worklistOne['refundMoney'] + $worklistTwo['refundMoney'];
                if ($timeOne) {
                    $startOne = $timeOne[0];
                    $endOne   = $timeOne[3];
                } else {
                    $startOne = date('Y-m-d', strtotime('-7 day'));
                    $endOne   = date('Y-m-d');
                }
                $startTwo = $timeTwo[0];
                $endTwo   = $timeTwo[3];
                 
                //销毁变量
                unset($worklistOne['workOrderNum'],$worklistOne['totalOrderMoney'],$worklistOne['replacementNum'],$worklistOne['refundMoneyNum'],$worklistOne['refundMoney']);
                unset($worklistTwo['workOrderNum'],$worklistTwo['totalOrderMoney'],$worklistTwo['replacementNum'],$worklistTwo['refundMoneyNum'],$worklistTwo['refundMoney']);
                $info = $this->customers();
                $workArr = [];
                foreach ($worklistOne as $ok =>$ov) {
                    if (array_key_exists($ov['create_user_id'], $info)) {
                        $workArr[$ov['create_user_id']]['create_user_name'] = $info[$ov['create_user_id']];
                        $workArr[$ov['create_user_id']]['group']            = $ov['group'];
                        $workArr[$ov['create_user_id']]['one']['counter']   = $ov['counter'];
                        $workArr[$ov['create_user_id']]['one']['base_grand_total'] = $ov['base_grand_total'];
                        $workArr[$ov['create_user_id']]['one']['coupon']    = $ov['coupon'];
                        $workArr[$ov['create_user_id']]['one']['refund_num'] = $ov['refund_num'];
                        $workArr[$ov['create_user_id']]['one']['replacement_num'] = $ov['replacement_num'];
                        $workArr[$ov['create_user_id']]['one']['total_refund_money'] = $ov['total_refund_money'];
                    }
                }
                foreach ($worklistTwo as $tk =>$tv) {
                    if (array_key_exists($tv['create_user_id'], $info)) {
                        $workArr[$tv['create_user_id']]['create_user_name'] = $info[$tv['create_user_id']];
                        $workArr[$tv['create_user_id']]['group']            = $tv['group'];
                        $workArr[$tv['create_user_id']]['two']['counter']   = $tv['counter'];
                        $workArr[$tv['create_user_id']]['two']['base_grand_total'] = $tv['base_grand_total'];
                        $workArr[$tv['create_user_id']]['two']['coupon']    = $tv['coupon'];
                        $workArr[$tv['create_user_id']]['two']['refund_num'] = $tv['refund_num'];
                        $workArr[$tv['create_user_id']]['two']['replacement_num'] = $tv['replacement_num'];
                        $workArr[$tv['create_user_id']]['two']['total_refund_money'] = $tv['total_refund_money'];
                    }
                }
                $this->view->assign([
                     'type'         =>3,
                     'workListOne'  => $worklistOne,
                     'workListTwo'  => $worklistTwo,
                     'startOne'     => $startOne,
                     'endOne'       => $endOne,
                     'startTwo'     => $startTwo,
                     'endTwo'       => $endTwo,
                     'startTwo'     => $startTwo,
                     'endTwo'       => $endTwo,
                     'platform'     => $platform,
                     'info'         => $info,
                     'workArr'      => $workArr
                     ]);
            }
            
            $orderPlatformList = config('workorder.platform');
            $this->view->assign(compact('orderPlatformList', 'workOrderNum', 'totalOrderMoney', 'replacementNum', 'refundMoneyNum', 'refundMoney'));
        } else {
            //默认显示
            //根据筛选时间求出客服部门下面所有有数据人员
            $start = date('Y-m-d', strtotime('-7 day'));
            $end   = date('Y-m-d');
            $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            $where['work_type'] = $whereCreate['work_type'] = 1;
            $where['work_platform'] = $whereCreate['work_platform'] = 1;
            $where['work_status'] = 6;
            $workList = $this->model->where($where)->where($map)->field('count(*) as counter,sum(base_grand_total) as base_grand_total,
            sum(is_refund) as refund_num,create_user_id,create_user_name')->group('create_user_id')->select();
            $where['replacement_order'] = ['neq',''];
            //补发单数和优惠券发放量
            $replacementOrder = $this->model->where($where)->where($map)->field('count(replacement_order) as counter,count(coupon_str) as coupon,create_user_id')->group('create_user_id')->select();
            $workList = collection($workList)->toArray();
            $replacementOrder = collection($replacementOrder)->toArray();
            if (!empty($replacementOrder)) {
                $replacementArr = $couponArr = [];
                foreach ($replacementOrder as $rk => $rv) {
                    $replacementArr[$rv['create_user_id']] = $rv['counter'];
                    $couponArr[$rv['create_user_id']] = $rv['coupon'];
                }
            }
            //客服分组
            $kefumanage = config('workorder.kefumanage');
            if (!empty($workList)) {
                $workOrderNum = $totalOrderMoney = $replacementNum = $refundMoneyNum = $refundMoney = 0;
                foreach ($workList as $k => $v) {
                    //客服分组
                    if (in_array($v['create_user_id'], $kefumanage[95])) {
                        $workList[$k]['group'] = 'B组';
                    } elseif (in_array($v['create_user_id'], $kefumanage[117])) {
                        $workList[$k]['group'] = 'A组';
                    } else {
                        $workList[$k]['group'] = '未知';
                    }
                    if (is_array($replacementArr)) {
                        //客服的补发订单数
                        if (array_key_exists($v['create_user_id'], $replacementArr)) {
                            $workList[$k]['replacement_num'] = $replacementArr[$v['create_user_id']];
                            //优惠券发放量
                            $workList[$k]['coupon']          = $couponArr[$v['create_user_id']];
                            //累计补发单数
                            $replacementNum += $replacementArr[$v['create_user_id']];
                        } else {
                            $workList[$k]['replacement_num'] = 0;
                            $workList[$k]['coupon'] = 0;
                        }
                    } else {
                        $workList[$k]['replacement_num'] = 0;
                        $workList[$k]['coupon'] = 0;
                    }
                    
                    //累计退款金额
                    $workList[$k]['total_refund_money'] = $this->calculate_refund_money($v['create_user_id'], $map);
                    if (0<$workList[$k]['total_refund_money']) {
                        $refundMoney += $workList[$k]['total_refund_money'];
                    }
                    //累计工单完成量
                    $workOrderNum += $v['counter'];
                    //累计订单总金额
                    $totalOrderMoney += $v['base_grand_total'];
                    //累计退款单数
                    $refundMoneyNum += $v['refund_num'];
                }
            }
            $orderPlatformList = config('workorder.platform');
            $this->view->assign('type', 1);
            $this->view->assign(compact('orderPlatformList', 'workList', 'start', 'end', 'workOrderNum', 'totalOrderMoney', 'replacementNum', 'refundMoneyNum', 'refundMoney'));
        }
        return $this->view->fetch();
    }
    /**
     * 计算某个用户的退款金额
     *
     * @Description
     * @author lsw
     * @since 2020/05/19 10:06:11
     * @return void
     */
    public function calculate_refund_money($create_user_id, $map)
    {
        $where['create_user_id'] = $create_user_id;
        $where['refund_money']   = ['GT',0];
        $where['work_type'] = 1;
        $where['work_status'] = 6;
        $info = $this->model->where($where)->where($map)->field('base_to_order_rate,refund_money')->select();
        if (!empty($info)) {
            $refund_money = 0;
            foreach ($info as $v) {
                if (0<$v['base_to_order_rate']) {
                    $refund_money += round($v['refund_money']/$v['base_to_order_rate'], 2);
                } else {
                    $refund_money += $v['refund_money'];
                }
            }
        }
        return $info ? $refund_money : 0;
    }
    /**
     * 获取客服人员信息(全部)
     *
     * @Description
     * @author lsw
     * @since 2020/05/19 15:59:10
     * @return void
     */
    private function customers()
    {
        $kefumanage = config('workorder.kefumanage');
        $arr = [];
        foreach ($kefumanage as $k=> $v) {
            $arr[] = $k;
            foreach ($v as $val) {
                $arr[] = $val;
            }
        }
        $result  = Admin::where('id', 'in', $arr)->column('id,nickname');
        $result[1]  = 'Admin';
        $result[75] = '王伟';
        return $result;
    }
    /**
     * 获取客服人员信息分组
     *
     * @Description
     * @author lsw
     * @since 2020/05/20 16:36:20 
     * @return void
     */
    private function customers_by_group($type)
    {
        $kefumanage = config('workorder.kefumanage');
        $arr = [];
        if(1 ==$type){
            foreach ($kefumanage[117] as $v) {
                    $arr[] = $v;
            }   
        }elseif(2 == $type){
            foreach ($kefumanage[95] as $v) {
                    $arr[] = $v;
            }
        }
        // $result[1]  = 'Admin';
        // $result[75] = '王伟'; 
        $result  = Admin::where('id', 'in', $arr)->column('id,nickname');
        return $result;
    }
    /**
     * 获取工作量信息
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 15:49:44 
     * @return void
     */
    public function workload_info($map,$start,$end,$platform)
    {
        
        $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        //默认显示
        //根据筛选时间求出客服部门下面所有有数据人员
        //$start = date('Y-m-d', strtotime('-30 day'));
        //$end   = date('Y-m-d');
        //$map['c.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
        $where['c.is_public'] = 1;
        //平台
        if($platform<10){
            $where['z.type'] = $platform;
        }
        
        //客服处理量
        $customerReply = $this->zendeskComments->alias('c')->join('fa_zendesk z','c.author_id=z.assignee_id')->where($where)->where($map)->field('count(*) as counter,z.assign_id')->group('c.author_id')->select();
        $customerReply = collection($customerReply)->toArray();
        //客服分组
        $info = $this->customers();
        $kefumanage = config('workorder.kefumanage');
        if (!empty($customerReply)) {
            $handleNum = 0;
            foreach ($customerReply as $k => $v) {
                //客服分组
                if (in_array($v['assign_id'], $kefumanage[95])) {
                    $customerReply[$k]['group'] = 'B组';
                } elseif (in_array($v['assign_id'], $kefumanage[117])) {
                    $customerReply[$k]['group'] = 'A组';
                } else {
                    $customerReply[$k]['group'] = '未知';
                }
                if(array_key_exists($v['assign_id'], $info)){
                    $customerReply[$k]['create_user_name'] = $info[$v['assign_id']];
                }
                    $customerReply[$k]['no_qualified_day'] = $this->calculate_no_qualified_day($v['assign_id'],$start,$end);
                    $handleNum+=$v['counter'];                    
            }
        }
        return $customerReply ? $customerReply : false;
    }
    /**
     * 获取工单的信息
     *
     * @Description
     * @author lsw
     * @since 2020/05/15 16:42:47
     * @return void
     */
    public function works_info($where, $map)
    {
        $where['work_type'] = 1;
        $where['work_status'] = 6;
        $workList = $this->model->where($where)->where($map)->field('count(*) as counter,sum(base_grand_total) as base_grand_total,
        sum(is_refund) as refund_num,create_user_id,create_user_name')->group('create_user_id')->select();
        $where['replacement_order'] = ['neq',''];
        $replacementOrder = $this->model->where($where)->where($map)->field('count(replacement_order) as counter,count(coupon_str) as coupon,create_user_id')->group('create_user_id')->select();
        $workList = collection($workList)->toArray();
        $replacementOrder = collection($replacementOrder)->toArray();
        if (!empty($replacementOrder)) {
            $replacementArr = [];
            foreach ($replacementOrder as $rk => $rv) {
                $replacementArr[$rv['create_user_id']] = $rv['counter'];
                $couponArr[$rv['create_user_id']] = $rv['coupon'];
            }
        }
        //客服分组
        $kefumanage = config('workorder.kefumanage');
        if (!empty($workList)) {
            $workOrderNum = $totalOrderMoney = $replacementNum = $refundMoneyNum = $refundMoney = 0;
            foreach ($workList as $k => $v) {
                //客服分组
                if (in_array($v['create_user_id'], $kefumanage[95])) {
                    $workList[$k]['group'] = 'B组';
                } elseif (in_array($v['create_user_id'], $kefumanage[117])) {
                    $workList[$k]['group'] = 'A组';
                } else {
                    $workList[$k]['group'] = '未知';
                }
                //如果存在补发单数数组
                if (is_array($replacementArr)) {
                    //客服的补发订单数
                    if (array_key_exists($v['create_user_id'], $replacementArr)) {
                        $workList[$k]['replacement_num'] = $replacementArr[$v['create_user_id']];
                        //优惠券发放量
                        $workList[$k]['coupon']          = $couponArr[$v['create_user_id']];                        
                        //累计补发单数
                        $replacementNum += $replacementArr[$v['create_user_id']];
                    } else {
                        $workList[$k]['replacement_num'] = 0;
                        //优惠券发放量
                        $workList[$k]['coupon']          = 0;
                    }
                } else { //如果不存在补发单数的数组
                    $workList[$k]['replacement_num'] = 0;
                    $workList[$k]['coupon']          = 0;
                }

                //累计退款金额
                $workList[$k]['total_refund_money'] = $this->calculate_refund_money($v['create_user_id'], $map);
                if (0<$workList[$k]['total_refund_money']) {
                    $refundMoney += $workList[$k]['total_refund_money'];
                }
                //累计工单完成量
                $workOrderNum += $v['counter'];
                //累计订单总金额
                $totalOrderMoney += $v['base_grand_total'];
                //累计退款单数
                $refundMoneyNum += $v['refund_num'];
            }
            $workList['workOrderNum']    = $workOrderNum;
            $workList['totalOrderMoney'] = $totalOrderMoney;
            $workList['replacementNum']  = $replacementNum;
            $workList['refundMoneyNum']  = $refundMoneyNum;
            $workList['refundMoney']     = $refundMoney;
        }
        return $workList ? $workList : false;
    }
    /**
     * 工单问题措施详情
     *
     * @Description
     * @author lsw
     * @since 2020/05/11 14:50:29
     * @return void
     */
    public function detail()
    {
        $create_time = input('create_time');
        $platform    = input('order_platform', 1);
        //异步调用图标数据
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            //问题措施比统计
            if ('echart1' == $params['key']) {
                //问题大分类统计、措施统计
                $data = $this->get_workorder_data($order_platform, $map);
                $customer_problem_classify = config('workorder.customer_problem_classify');
                $column = array_keys($customer_problem_classify);
                $columnData = [];
                foreach ($column as $k =>$v) {
                    $columnData[$k]['name'] = $v;
                    $columnData[$k]['value'] = $data['problem_type'][$k];
                }
                // $json['column'] = $column;
                // $json['columnData'] = $columnData;
                // return json(['code' => 1, 'data' => $json]);
            } elseif ('echart2' == $params['key']) {
                //问题类型统计
                $problem_data = $this->get_problem_type_data($order_platform, $map, 1);
                //问题类型数组
                $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
                $customer_problem_list  = config('workorder.customer_problem_type');
                //循环数组根据id获取客服问题类型
                $column = $columnData = [];
                foreach ($customer_problem_arr as $k => $v) {
                    $column[] = $customer_problem_list[$v];
                }
                foreach ($column as $ck => $cv) {
                    $columnData[$ck]['name'] = $cv;
                    $columnData[$ck]['value'] = $problem_data[$ck];
                }
                // $json['column'] = $column;
                // $json['columnData'] = $columnData;
                // return json(['code' => 1, 'data' => $json]);
            } elseif ('echart3' == $params['key']) {
                //问题大分类统计、措施统计
                $data = $this->get_workorder_data($order_platform, $map);
                $step = config('workorder.step');
                $column = array_merge($step);
                $columnData = [];
                foreach ($column as $k =>$v) {
                    $columnData[$k]['name'] = $v;
                    $columnData[$k]['value'] = $data['step'][$k];
                }
                // $json['column'] = $column;
                // $json['columnData'] = $columnData;
                // return json(['code' => 1, 'data' => $json]);
            } elseif ('echart4' == $params['key']) {
                //问题类型统计
                $data = $this->get_problem_step_data($order_platform, $map, 1);
                //问题类型数组
                $step = config('workorder.step');
                $column = array_merge($step);
                $columnData = [];
                foreach ($column as $k =>$v) {
                    $columnData[$k]['name'] = $v;
                    $columnData[$k]['value'] = $data['step'][$k];
                }
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        // if (false == $data) {
            //     return $this->error('没有对应的时间数据，请重新尝试');
            // }
            // return $this->success('', '', $data, 0);
        } elseif ($this->request->isPost()) { //提交获取右边的数据信息
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            //问题大分类统计、措施统计
            $data = $this->get_workorder_data($platform, $map);
            //问题分类数据
            $problem_data = $this->get_problem_type_data($platform, $map, 1);
            //求出措施数据
            $step_data    = $this->get_problem_step_data($platform, $map, 1);
            //求出问题大分类的总数,措施的总数
            $problem_type_total = $step_total = $problem_form_total = $step_four_total =  0 ;
            foreach ($data['problem_type'] as $pv) {
                $problem_type_total += $pv;
            }
            foreach ($data['step'] as $sv) {
                $step_total += $sv;
            }
            //求出问题类型的总数
            foreach ($problem_data as $dv) {
                $problem_form_total +=$dv;
            }
            //求出措施总数据
            foreach ($step_data['step'] as $tv) {
                $step_four_total+= $tv;
            }
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            //求出默认的问题类型，饼图2右边展示的东东
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
            $customer_problem_list  = config('workorder.customer_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                $customer_arr[] = $customer_problem_list[$v];
            }
            //提交过后的平台数值
            //$this->view->assign('platformNew',$platform);
            $this->view->assign(compact('data', 'problem_type_total', 'step_total', 'problem_form_total', 'step_four_total', 'problem_data', 'step_data', 'step', 'customer_arr'));
        } else { //默认获取右边的信息
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            //问题大分类统计、措施统计
            $data = $this->get_workorder_data($platform, $map);
            //问题分类数据
            $problem_data = $this->get_problem_type_data($platform, $map, 1);
            //求出措施数据
            $step_data    = $this->get_problem_step_data($platform, $map, 1);
            //求出问题大分类的总数,措施的总数
            $problem_type_total = $step_total = $problem_form_total = $step_four_total =  0 ;
            foreach ($data['problem_type'] as $pv) {
                $problem_type_total += $pv;
            }
            foreach ($data['step'] as $sv) {
                $step_total += $sv;
            }
            //求出问题类型的总数
            foreach ($problem_data as $dv) {
                $problem_form_total +=$dv;
            }
            //求出措施总数据
            foreach ($step_data['step'] as $tv) {
                $step_four_total+= $tv;
            }
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            //求出默认的问题类型，饼图2右边展示的东东
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
            $customer_problem_list  = config('workorder.customer_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                $customer_arr[] = $customer_problem_list[$v];
            }
            $this->view->assign(compact('data', 'problem_type_total', 'step_total', 'problem_form_total', 'step_four_total', 'problem_data', 'step_data', 'step', 'customer_arr'));
        }

        //第四个饼图二级tab联动默认的二级数据 start
        $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[1];
        $customer_problem_list  = config('workorder.customer_problem_type');
        //循环数组根据id获取客服问题类型
        $column = [];
        foreach ($customer_problem_arr as $k => $v) {
            $column[$v] = $customer_problem_list[$v];
        }
        //第四个饼图二级tab联动默认的二级数据 end
        //第二张和第四张tab默认显示数据 start
        $customer_problem_classify = config('workorder.customer_problem_classify');
        $problem_type = array_keys($customer_problem_classify);
        //第二张和第四张tab默认显示数据 end
        $orderPlatformList = config('workorder.platform');
        $this->view->assign(compact('orderPlatformList', 'create_time', 'platform', 'problem_type', 'column'));
        return $this->view->fetch();
    }
    /**
     * 切换问题类型
     *
     * @Description
     * @author lsw
     * @since 2020/05/13 18:47:18
     * @return void
     */
    public function problem()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $value = $params['value'];
            $order_platform = $params['platform'];
            //问题类型统计
            $problem_data = $this->get_problem_type_data($order_platform, $map, $value);
            //问题类型数组
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[$value];
            $customer_problem_list  = config('workorder.customer_problem_type');
            //循环数组根据id获取客服问题类型
            $column = $columnData = [];
            foreach ($customer_problem_arr as $k => $v) {
                $column[] = $customer_problem_list[$v];
            }
            foreach ($column as $ck => $cv) {
                $columnData[$ck]['name'] = $cv;
                $columnData[$ck]['value'] = $problem_data[$ck];
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 根据措施切换问题类型
     *
     * @Description
     * @author lsw
     * @since 2020/05/14 11:37:10
     * @return void
     */
    public function step()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $value = $params['value'];
            $order_platform = $params['platform'];
            //问题类型统计
            $data = $this->get_problem_step_data($order_platform, $map, $value);
            //问题类型数组
            $step = config('workorder.step');
            $column = array_merge($step);
            $columnData = [];
            foreach ($column as $k =>$v) {
                $columnData[$k]['name'] = $v;
                $columnData[$k]['value'] = $data['step'][$k];
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 异步获取二级联动数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/14 10:26:21
     * @return void
     */
    public function get_problem_by_classify()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $value = $params['value'];
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[$value];
            $customer_problem_list  = config('workorder.customer_problem_type');
            //循环数组根据id获取客服问题类型
            $column = [];
            foreach ($customer_problem_arr as $k => $v) {
                $column[$v] = $customer_problem_list[$v];
            }
            $this->success('', '', $column);
        }
    }
    /**
     *获取workorder的统计数据
     *问题大分类统计、措施统计
     * @Description
     * @author lsw
     * @since 2020/05/12 10:02:54
     * @return void
     */
    public function get_workorder_data($platform, $map)
    {
        $arr = Cache::get('CustomerService_get_workorder_data_'.$platform.md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($platform<10) {
            $where['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        //订单修改数组
        //$changeOrderArr = config('workorder.customer_problem_classify_arr')[1];
        //
        //问题总数组
        $problem_arr = config('workorder.customer_problem_classify_arr');
        //问题结果
        $result = [];
        foreach ($problem_arr as $v) {
            //问题大分类的统计
            $result['problem_type'][] = $this->model->where($where)->where($map)->where('problem_type_id', 'in', $v)->count('id');
        }
        //所有完成的work_id
        $all_work_id = $this->model->where($where)->where($map)->column('id');
        //措施总数组
        $step_arr = config('workorder.step');
        $where_step['operation_type'] = 1;
        foreach ($step_arr as $sk=>$sv) {
            $result['step'][] = $this->step->where($where_step)->where('measure_choose_id', $sk)->where('work_id', 'in', $all_work_id)->count('id');
        }
        Cache::set('CustomerService_get_workorder_data_'.$platform.md5(serialize($map)), $result, 7200);
        return $result;
    }
    /**
     * 问题类型统计
     *
     * @Description
     * @author lsw
     * @since 2020/05/12 14:46:20
     * @return void
     */
    public function get_problem_type_data($platform, $map, $problem_type)
    {
        $arr = Cache::get('CustomerService_get_problem_type_data_'.$platform.'_'.$problem_type.md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($platform<10) {
            $where['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        //所有的问题组
        $problem_arr = config('workorder.customer_problem_classify_arr');
        //当前的问题组
        $current_problem_arr = $problem_arr[$problem_type];
        $result = [];
        foreach ($current_problem_arr as $k =>$v) {
            $result[$k] = $this->model->where($where)->where($map)->where('problem_type_id', $v)->count('id');
        }
        Cache::set('CustomerService_get_problem_type_data_'.$platform.'_'.$problem_type.md5(serialize($map)), $result, 7200);
        return $result;
    }
    /**
     * 问题措施比统计
     *
     * @Description
     * @author lsw
     * @since 2020/05/12 15:16:48
     * @param [type] $platform
     * @param [type] $map
     * @param [type] $problem_type
     * @param [type] $step_id
     * @return void
     */
    public function get_problem_step_data($platform, $map, $problem_id)
    {
        $arr = Cache::get('CustomerService_get_problem_step_data_'.$platform.'_'.$problem_id.md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($platform<10) {
            $where['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        $result = $info = [];
        $result = $this->model->where($where)->where($map)->where('problem_type_id', $problem_id)->column('id');
        $where_step['operation_type'] = 1;
        $step_arr = config('workorder.step');
        foreach ($step_arr as $k =>$v) {
            $info['step'][]  = $this->step->where($where_step)->where('work_id', 'in', $result)->where('measure_choose_id', $k)->count('id');
        }
        Cache::set('CustomerService_get_problem_step_data_'.$platform.'_'.$problem_id.md5(serialize($map)), $info, 7200);
        return $info;
    }
    /**
     * 异步获取第二个饼图右边的数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/14 18:51:57
     * @return void
     */
    public function get_two_pie_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            $value          = $params['value'];
            $problem_data = $this->get_problem_type_data($order_platform, $map, $value);
            $problem_form_total = 0;
            foreach ($problem_data as $dv) {
                $problem_form_total +=$dv;
            }
            $customer_problem_arr   = config('workorder.customer_problem_classify_arr')[$value];
            $customer_problem_list  = config('workorder.customer_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                $customer_arr[] = $customer_problem_list[$v];
            }
            $data['problem_data'] =  $problem_data;
            $data['problem_form_total'] =  $problem_form_total;
            $data['customer_arr'] =  $customer_arr;
            $this->success('', '', $data);
        }
    }
    /**
     * 异步获取第四个饼图右边数据
     *
     * @Description
     * @author lsw
     * @since 2020/05/15 09:18:21
     * @return void
     */
    public function get_four_pie_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            $value          = $params['value'];
            $step_data = $this->get_problem_step_data($order_platform, $map, $value);
            $step_four_total = 0;
            //求出措施总数据
            foreach ($step_data['step'] as $tv) {
                $step_four_total+= $tv;
            }
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            $data['step_data'] =  $step_data;
            $data['step_four_total'] =  $step_four_total;
            $data['step'] =  $step;
            $this->success('', '', $data);
        }
    }
}
