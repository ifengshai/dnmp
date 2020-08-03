<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;
use think\Cache;
use app\admin\model\AuthGroupAccess;
use app\admin\model\Admin;
use app\admin\model\zendesk\ZendeskAgents;
use think\Db;

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
        $this->zendesk = new \app\admin\model\zendesk\Zendesk;
        $this->problem_type = new \app\admin\model\saleaftermanage\WorkOrderProblemType();
        $this->problem_step = new \app\admin\model\saleaftermanage\WorkOrderProblemStep();
        $this->zendeskComments = new \app\admin\model\zendesk\ZendeskComments;
        $this->zendeskTasks = new \app\admin\model\zendesk\ZendeskTasks;
    }
    /**
     * 客服数据大屏
     *
     * @Description
     * @author mjj
     * @since 2020/07/23 16:55:02 
     * @return void
     */
    public function customer_data_screen()
    {
        $platform = input('platform') ? input('platform') : 1;

        $workorder_situation = $this->model->workorder_situation($platform);

        $worknum_situation = $this->zendesk->worknum_situation($platform);
        $this->view->assign(compact('workorder_situation', 'worknum_situation'));
        return $this->view->fetch();
    }
    /**
     * ajax获取工单概况
     *
     * @Description
     * @author mjj
     * @since 2020/07/24 10:15:10 
     * @return void
     */
    public function workorder_situation()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform = $params['platform'] ? $params['platform'] : 0;
            $workorder_situation = $this->model->workorder_situation($platform);
            $this->success('', '', $workorder_situation);
        }
    }
    /**
     * ajax获取工作量概况
     *
     * @Description
     * @author mjj
     * @since 2020/07/24 13:58:28 
     * @return void
     */
    public function worknum_situation()
    {

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform = $params['platform'] ? $params['platform'] : 0;
            $workload_time = $params['workload_time'] ? $params['workload_time'] : '';

            $workorder_situation = $this->zendesk->worknum_situation($platform, $workload_time);
            $this->success('', '', $workorder_situation);
        }
    }
    /**
     * ajax获取工作量中的折线图数据
     *
     * @Description
     * @author mjj
     * @since 2020/07/24 13:58:28 
     * @return void
     */
    public function worknum_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform = $params['platform'];
            $workload_time = $params['workload_time'];
            $title_type = $params['title_type'] ? $params['title_type'] : 1;
            if ($platform) {
                $where['c.platform'] = $platform;
            }
            if ($title_type == 1) {
                $where['c.is_admin'] = 0;
            } else {
                $where['c.is_admin'] = 1;
            }
            $where['z.channel'] = array('neq', 'voice');
            if ($workload_time) {
                $createat = explode(' ', $workload_time);
                $where['c.update_time'] = ['between', [$createat[0], $createat[0]  . ' 23:59:59']];
                $date_arr = array(
                    $createat[0] => $this->zendeskComments->alias('c')->join('fa_zendesk z', 'c.zid=z.id')->where($where)->count()
                );
                if ($createat[0] != $createat[3]) {
                    for ($i = 0; $i <= 100; $i++) {
                        $m = $i + 1;
                        $deal_date = date_create($createat[0]);
                        date_add($deal_date, date_interval_create_from_date_string("$m days"));
                        $next_day = date_format($deal_date, "Y-m-d");
                        $where['c.update_time'] = ['between', [$next_day, $next_day  . ' 23:59:59']];
                        $date_arr[$next_day] = $this->zendeskComments->alias('c')->join('fa_zendesk z', 'c.zid=z.id')->where($where)->count();
                        if ($next_day == $createat[3]) {
                            break;
                        }
                    }
                }
            } else {
                //默认显示一周的数据
                for ($i = 6; $i >= 0; $i--) {
                    $next_day = date("Y-m-d", strtotime("-$i day"));
                    $where['c.update_time'] = ['between', [$next_day, $next_day  . ' 23:59:59']];
                    $date_arr[$next_day] = $this->zendeskComments->alias('c')->join('fa_zendesk z', 'c.zid=z.id')->where($where)->count();
                }
            }
            if ($title_type == 1) {
                $name = '新增工单量';
            } else {
                $name = '已回复工单量';
            }
            $json['xcolumnData'] = array_keys($date_arr);
            $json['column'] = [$name];
            $json['columnData'] = [
                [
                    'name' => $name,
                    'type' => 'line',
                    'smooth' => true,
                    'data' => array_values($date_arr)
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * ajax获取工单处理概况中的饼图数据
     *
     * @Description
     * @author mjj
     * @since 2020/07/24 13:58:28 
     * @return void
     */
    public function workorder_question_type()
    {
        //异步调用图标数据
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform    = $params['platform'] ? $params['platform'] : 0;
            if ($params['create_time']) {
                $time = explode(' ', $params['create_time']);
                $map['complete_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
                $map1['operation_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
                $map1['operation_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }

            if ($params['key'] == 'echart1') {
                //工单问题类型统计
                $columnData = $this->model->workorder_question_type($platform, $map);
                foreach ($columnData as $k => $v) {
                    $column[] = $v['name'];
                }
            } elseif ($params['key'] == 'echart3') {
                //问题类型统计
                $columnData = $this->model->workorder_measures($platform, $map1);
                foreach ($columnData as $k => $v) {
                    $column[] = $v['name'];
                }
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        }
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
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->zendeskTasks  = new \app\admin\model\zendesk\ZendeskTasks;
        //处理量
        $deal_num = $this->zendeskTasks->dealnum_statistical(1);
        //未达标天数
        $no_up_to_day = $this->zendeskTasks->not_up_to_standard_day(1);
        //人效
        $positive_effect_num = $this->zendeskComments->positive_effect_num(1);
        //获取表格内容
        $customer_data = $this->get_worknum_table(1);
        $this->view->assign(compact('deal_num', 'no_up_to_day', 'positive_effect_num', 'customer_data'));
        return $this->view->fetch();
    }
    /*
     * 获取工作量统计表格中的数据
     * */
    public function get_worknum_table($platform = 0, $time_str1 = '', $time_str2 = '', $group_id = 0)
    {
        $data = array();
        $i = 0;
        if ($time_str1) {
            $createat1 = explode(' ', $time_str1);
            $one_time = $createat1[0].' - '.$createat1[3];
            $where['create_time'] = ['between', [$createat1[0] . ' ' . $createat1[1], $createat1[3]  . ' ' . $createat1[4]]];
            $time_time = $time_str1;
        }else{
            $seven_startdate = date("Y-m-d", strtotime("-6 day"));
            $seven_enddate = date("Y-m-d");
            $one_time = $seven_startdate.' - '.$seven_enddate;
            $where['create_time'] = ['between', [$seven_startdate, $seven_enddate]];
            $time_time = '';
        }
        //查询所有客服人员
        $all_service_ids = Db::name('zendesk_tasks')->where($where)->column('admin_id');
        $all_service = array_unique($all_service_ids);
        foreach ($all_service as $item=>$value){
            $admin = Db::name('admin')->where('id',$value)->field('nickname,group_id')->find();
            $data[$i]['admin_id'] = $value;
            //用户姓名
            $data[$i]['name'] = $admin['nickname'];
            //分组名称
            if ($admin['group_id'] == 1) {
                $data[$i]['group_name'] = 'A组';
            } elseif ($admin['group_id'] == 2) {
                $data[$i]['group_name'] = 'B组';
            } else {
                $data[$i]['group_name'] = '';
            }
            $data[$i]['time'] = $time_time;
            //时间
            $data[$i]['one']['time'] = $one_time;
            //处理量
            $data[$i]['one']['deal_num'] = $this->zendeskTasks->dealnum_statistical($platform, $time_str1, $admin['group_id'], $value);
            //未达标天数
            $data[$i]['one']['no_up_to_day'] = $this->zendeskTasks->not_up_to_standard_day($platform, $time_str1, $admin['group_id'], $value);
            if ($time_str2) {
                $createat2 = explode(' ', $time_str2);
                $two_time = $createat2[0] . ' - ' . $createat2[3];
                //对比时间
                $data[$i]['two']['time'] = $two_time;
                //对比处理量
                $data[$i]['two']['deal_num'] = $this->zendeskTasks->dealnum_statistical($platform, $time_str2, $admin['group_id'], $value);
                //对比未达标天数
                $data[$i]['two']['no_up_to_day'] = $this->zendeskTasks->not_up_to_standard_day($platform, $time_str2, $admin['group_id'], $value);
            }
            $i++;
        }
        return $data;
    }
    /*
     * ajax获取工作量统计信息
     * */
    public function workload_worknum()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform = $params['platform'];
            $time_str = $params['time_str'];
            $contrast_time_str = $params['contrast_time_str'];
            $group_id = $params['group_id'];
            $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
            //处理量
            $arr['deal_num'] = $this->zendeskTasks->dealnum_statistical($platform, $time_str, $group_id);
            //未达标天数
            $arr['no_up_to_day'] = $this->zendeskTasks->not_up_to_standard_day($platform, $time_str, $group_id);
            //人效
            $arr['positive_effect_num'] = $this->zendeskComments->positive_effect_num($platform, $time_str, $group_id);
            //获取表格中的时间
            $customer_data = $this->get_worknum_table($platform,$time_str,$contrast_time_str,$group_id);
            if($customer_data){
                $str = '<thead><tr><th style="text-align: center; vertical-align: middle;">姓名</th><th style="text-align: center; vertical-align: middle;">分组</th><th style="text-align: center; vertical-align: middle;">日期</th><th style="text-align: center; vertical-align: middle;">处理量</th><th style="text-align: center; vertical-align: middle;">未达标天数</th><th style="text-align: center; vertical-align: middle;">操作</th></tr></thead>';
                foreach ($customer_data as $item=>$value){
                    $str .= '<tr><td style="text-align: center; vertical-align: middle;">'.$value['name'].'</td><td id="today_sales_money" style="text-align: center; vertical-align: middle;">'.$value['group_name'].'</td>';
                    if($value['two']){
                        $str .= '<td id="today_order_num" style="text-align: center; vertical-align: middle;"><ul class="customer_table"><li>'.$value['one']['time'].'</li><hr style="height:1px;border:none;border-top:1px solid #c1bebe;" /><li>'.$value['two']['time'].'</li></ul></td><td id="today_order_success" style="text-align: center; vertical-align: middle;"><ul class="customer_table"><li>'.$value['one']['deal_num'].'</li><hr style="height:1px;border:none;border-top:1px solid #c1bebe;" /><li>'.$value['two']['deal_num'].'</li></ul></td><td id="today_unit_price" style="text-align: center; vertical-align: middle;"><ul class="customer_table"><li>'.$value['one']['no_up_to_day'].'</li><hr style="height:1px;border:none;border-top:1px solid #c1bebe;" /><li>'.$value['two']['no_up_to_day'].'</li></ul></td>';
                    }else{
                        $str .= '<td id="today_order_num" style="text-align: center; vertical-align: middle;">'.$value['one']['time'].'</td><td id="today_order_success" style="text-align: center; vertical-align: middle;">'.$value['one']['deal_num'].'</td><td id="today_unit_price" style="text-align: center; vertical-align: middle;">'.$value['one']['no_up_to_day'].'</td>';
                    }
                    $str .= '<td class="click_look" data-id="'.$value['admin_id'].'" data-value="'.$value['time'].'">点击查看</td></tr>';
                }
                $arr['customer_data'] = $str;
            } else {
                $arr['customer_data'] = '';
            }

            return json(['code' => 1, 'data' => $arr]);
        }
    }
    /*
     * ajax获取处理量的折线图
     * */
    public function dealnum_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform = $params['platform'];
            $time_str = $params['time_str'];
            $group_id = $params['group_id'];
            $admin_id = $params['admin_id'];
            if($platform){
                $where['type'] = $platform;
            }
            if($group_id){
                //查询客服类型
                $group_admin_id = Db::name('admin')->where(['group_id' => $group_id, 'status' => 'normal'])->column('id');
                $where['admin_id'] = array('in', $group_admin_id);
            }
            if($admin_id){
                $where['admin_id'] = $admin_id;
            }
            if($time_str){
                $createat = explode(' ', $time_str);
                $where['create_time'] = ['between', [$createat[0], $createat[0]  . ' 23:59:59']];
                $date_arr = array(
                    $createat[0] => $this->zendeskTasks->where($where)->sum('reply_count')
                );
                if ($createat[0] != $createat[3]) {
                    for ($i = 0; $i <= 100; $i++) {
                        $m = $i + 1;
                        $deal_date = date_create($createat[0]);
                        date_add($deal_date, date_interval_create_from_date_string("$m days"));
                        $next_day = date_format($deal_date, "Y-m-d");
                        $where['create_time'] = ['between', [$next_day, $next_day  . ' 23:59:59']];
                        $date_arr[$next_day] = $this->zendeskTasks->where($where)->sum('reply_count');
                        if ($next_day == $createat[3]) {
                            break;
                        }
                    }
                }
            } else {

                for ($i = 6; $i >= 0; $i--) {
                    $j = $i-1;
                    $next_day = date("Y-m-d", strtotime("-$i day"));
                    $next_next_day = date("Y-m-d", strtotime("-$j day"));
                    $where['create_time'] = ['between', [$next_day, $next_next_day]];
                    $date_arr[$next_day] = $this->zendeskTasks->where($where)->sum('reply_count');
                }
            }

            $name = '处理量';
            $json['xcolumnData'] = array_keys($date_arr);
            $json['column'] = [$name];
            $json['columnData'] = [
                [
                    'name' => $name,
                    'type' => 'line',
                    'smooth' => true,
                    'data' => array_values($date_arr)
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /*
     * 处理量折线图弹窗
     * */
    public function dealnum_alert_line(){
        $params = $this->request->param();
        $admin_id = $params['admin_id'];
        $time_str = $params['time_str'];
        $this->view->assign(compact('admin_id', 'time_str'));
        return $this->view->fetch();
    }
    /*
     * 工单处理统计
     * */
    public function worklist_deal(){
        $kefumanage = config('workorder.kefumanage');
        $examine = [];
        foreach ($kefumanage as $k => $v) {
            $examine[] = $k;
        }
        $examine[] = config('workorder.customer_manager');
        $examinePerson = $this->customers();

        $examineArr = [];
        foreach ($examinePerson as $ek => $ev) {
            if (in_array($ek, $examine)) {
                $examineArr[$ek] = $ev;
            }
        }
        //左边右边的措施
        $step = config('workorder.step');
        $start = date("Y-m-d", strtotime("-6 day"));
        $end = date("Y-m-d 23:59:59");
        $map_create['create_time'] =  $map_measure['w.create_time'] = ['between', [$start,$end]];
        $workorder_handle_left_data = $this->workorder_handle_left($map_create, $examineArr);
        $workorder_handle_right_data = $this->workorder_handle_right($map_measure, $step);
        //跟单概况 start
        $warehouse_problem_type = config('workorder.warehouse_problem_type');
        $warehouse_handle       = $this->warehouse_handle($map_create, $warehouse_problem_type);
        //跟单概况 end
        $this->view->assign(compact('workorder_handle_left_data', 'workorder_handle_right_data','examineArr','step','warehouse_handle','warehouse_problem_type'));
        return $this->view->fetch();
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
        //分组数据  韩雨薇组成员
        $infoOne = $this->customers_by_group(1);

        //白青青组成员
        $infoTwo = $this->customers_by_group(2);

        //总览数据start
        //1.今天数据  10 => 默认全部
        $todayData = $this->workload->gettodayData(10);
        //昨天数据
        $yesterdayData = $this->workload->getyesterdayData(10);
        //过去7天数据
        $servenData = $this->workload->getSevenData(10);
        //过去30天数据
        $thirdData = $this->workload->getthirdData(10);

        //总览数据end

        //工作量概况start
        //$this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        $start = date('Y-m-d', strtotime('-1 day'));
        $end   = date('Y-m-d');
        $yesterStart = date('Y-m-d', strtotime('-1 day'));
        $workload_map['create_time'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d 00:00:00', time() + 3600 * 24)]];
        $workload['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 00:00:00', time())]];
        $customerReply = $this->workload_info_original($workload_map, $start, $end, 10);
        $customerType = $this->getCustomerType();
        if (!empty($customerReply)) {
            unset($customerReply['handleNum']);
            unset($customerReply['noQualifiyDay']);
            $replyArr = [];
            $replyArr['one']['counter'] = $replyArr['one']['no_qualified_day'] = 0;
            $replyArr['two']['counter'] = $replyArr['two']['no_qualified_day'] = 0;
            foreach ($customerReply as $ok => $ov) {
                if (array_key_exists($ov['due_id'], $infoOne)) {
                    $replyArr[$ov['due_id']]['create_user_name'] = $infoOne[$ov['due_id']];
                    $replyArr[$ov['due_id']]['group']       = $ov['group'];
                    $replyArr[$ov['due_id']]['counter']   = $ov['counter'];
                    $replyArr[$ov['due_id']]['no_qualified_day'] = $ov['no_qualified_day'];
                    $replyArr['one']['counter']          += $replyArr[$ov['due_id']]['counter'];
                    $replyArr['one']['no_qualified_day'] += $replyArr[$ov['due_id']]['no_qualified_day'];
                }
                if (array_key_exists($ov['due_id'], $infoTwo)) {
                    $replyArr[$ov['due_id']]['create_user_name'] = $infoTwo[$ov['due_id']];
                    $replyArr[$ov['due_id']]['group']       = $ov['group'];
                    $replyArr[$ov['due_id']]['counter']   = $ov['counter'];
                    $replyArr[$ov['due_id']]['no_qualified_day'] = $ov['no_qualified_day'];
                    $replyArr['two']['counter']          += $replyArr[$ov['due_id']]['counter'];
                    $replyArr['two']['no_qualified_day'] += $replyArr[$ov['due_id']]['no_qualified_day'];
                }
            }
        }
        $yesterdayWorkload = $this->getyesterdayWorkloadNum();
        if (!empty($yesterdayWorkload)) {
            $replyArr['one']['yester_num'] = $replyArr['two']['yester_num'] = 0;
            $yesterdayWorkload = collection($yesterdayWorkload)->toArray();
            foreach ($yesterdayWorkload as $k => $v) {
                if (array_key_exists($v['due_id'], $infoOne)) {
                    $replyArr[$v['due_id']]['yester_num'] = $v['counter'];
                    $replyArr['one']['yester_num']        += $replyArr[$v['due_id']]['yester_num'];
                }
                if (array_key_exists($v['due_id'], $infoTwo)) {
                    $replyArr[$v['due_id']]['yester_num'] = $v['counter'];
                    $replyArr['two']['yester_num']        += $replyArr[$v['due_id']]['yester_num'];
                }
            }
        }
        //工作量概况end

        //工单统计信息
        $map_measure['w.create_time'] = $map['complete_time']   = $map_create['create_time'] = ['between', [date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), 1, date("Y"))), date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("t"), date("Y")))]];
        $workList = $this->works_info_original([], $map);
        $workArr  = [];
        if (!empty($workList)) {
            unset($workList['workOrderNum'], $workList['totalOrderMoney'], $workList['replacementNum'], $workList['refundMoneyNum'], $workList['refundMoney']);
            $workArr['one']['counter'] = $workArr['one']['base_grand_total'] = $workArr['one']['coupon'] = $workArr['one']['refund_num'] = $workArr['one']['replacement_num'] = $workArr['one']['total_refund_money'] = 0;
            $workArr['two']['counter'] = $workArr['two']['base_grand_total'] = $workArr['two']['coupon'] = $workArr['two']['refund_num'] = $workArr['two']['replacement_num'] = $workArr['two']['total_refund_money'] = 0;
            foreach ($workList as $ok => $ov) {
                if (array_key_exists($ov['create_user_id'], $infoOne)) {
                    $workArr[$ov['create_user_id']]['create_user_name'] = $infoOne[$ov['create_user_id']];
                    //$workArr[$ov['create_user_id']]['create_num']       = $this->model->where($map_create)->where('create_user_id', $ov['create_user_id'])->count('*');
                    $workArr[$ov['create_user_id']]['counter']   = $ov['counter'];
                    $workArr[$ov['create_user_id']]['base_grand_total'] = $ov['base_grand_total'];
                    $workArr[$ov['create_user_id']]['coupon']    = $ov['coupon'];
                    $workArr[$ov['create_user_id']]['refund_num'] = $ov['refund_num'];
                    $workArr[$ov['create_user_id']]['replacement_num'] = $ov['replacement_num'];
                    $workArr[$ov['create_user_id']]['total_refund_money'] = $ov['total_refund_money'];
                    //$workArr['one']['create_num']           += $workArr[$ov['create_user_id']]['create_num'];
                    $workArr['one']['counter']              += $workArr[$ov['create_user_id']]['counter'];
                    $workArr['one']['base_grand_total']     += $workArr[$ov['create_user_id']]['base_grand_total'];
                    $workArr['one']['coupon']               += $workArr[$ov['create_user_id']]['coupon'];
                    $workArr['one']['refund_num']           += $workArr[$ov['create_user_id']]['refund_num'];
                    $workArr['one']['replacement_num']      += $workArr[$ov['create_user_id']]['replacement_num'];
                    $workArr['one']['total_refund_money']   += $workArr[$ov['create_user_id']]['total_refund_money'];
                }
                if (array_key_exists($ov['create_user_id'], $infoTwo)) {
                    $workArr[$ov['create_user_id']]['create_user_name'] = $infoTwo[$ov['create_user_id']];
                    //$workArr[$ov['create_user_id']]['create_num']       = $this->model->where($map_create)->where('create_user_id', $ov['create_user_id'])->count('*');
                    $workArr[$ov['create_user_id']]['counter']   = $ov['counter'];
                    $workArr[$ov['create_user_id']]['base_grand_total'] = $ov['base_grand_total'];
                    $workArr[$ov['create_user_id']]['coupon']    = $ov['coupon'];
                    $workArr[$ov['create_user_id']]['refund_num'] = $ov['refund_num'];
                    $workArr[$ov['create_user_id']]['replacement_num'] = $ov['replacement_num'];
                    $workArr[$ov['create_user_id']]['total_refund_money'] = $ov['total_refund_money'];
                    //$workArr['two']['create_num']           += $workArr[$ov['create_user_id']]['create_num'];
                    $workArr['two']['counter']              += $workArr[$ov['create_user_id']]['counter'];
                    $workArr['two']['base_grand_total']     += $workArr[$ov['create_user_id']]['base_grand_total'];
                    $workArr['two']['coupon']               += $workArr[$ov['create_user_id']]['coupon'];
                    $workArr['two']['refund_num']           += $workArr[$ov['create_user_id']]['refund_num'];
                    $workArr['two']['replacement_num']      += $workArr[$ov['create_user_id']]['replacement_num'];
                    $workArr['two']['total_refund_money']   += $workArr[$ov['create_user_id']]['total_refund_money'];
                }
            }
        }
        $thisMonthWorkOrderNum = $this->getThisMonthWorkOrderNum();
        if (!empty($thisMonthWorkOrderNum)) {
            $workArr['one']['create_num'] =    $workArr['two']['create_num'] = 0;
            $thisMonthWorkOrderNum = collection($thisMonthWorkOrderNum)->toArray();
            foreach ($thisMonthWorkOrderNum as $k => $v) {
                if (array_key_exists($v['create_user_id'], $infoOne)) {
                    $workArr[$v['create_user_id']]['create_num'] = $v['counter'];
                    $workArr['one']['create_num']           += $workArr[$v['create_user_id']]['create_num'];
                }
                if (array_key_exists($v['create_user_id'], $infoTwo)) {
                    $workArr[$v['create_user_id']]['create_num'] = $v['counter'];
                    $workArr['two']['create_num']           += $workArr[$v['create_user_id']]['create_num'];
                }
            }
        }
        //工单处理概况信息start
        //1.求出三个审批人
        $kefumanage = config('workorder.kefumanage');
        $examine = [];
        foreach ($kefumanage as $k => $v) {
            $examine[] = $k;
        }
        $examine[] = config('workorder.customer_manager');
        $examinePerson = $this->customers();

        $examineArr = [];
        foreach ($examinePerson as $ek => $ev) {
            if (in_array($ek, $examine)) {
                $examineArr[$ek] = $ev;
            }
        }
        //左边右边的措施
        $step = config('workorder.step');
        $workorder_handle_left_data = $this->workorder_handle_left($map_create, $examineArr);
        $workorder_handle_right_data = $this->workorder_handle_right($map_measure, $step);
        //工单处理概况信息end
        //跟单概况 start
        $warehouse_problem_type = config('workorder.warehouse_problem_type');
        $warehouse_handle       = $this->warehouse_handle($map_create, $warehouse_problem_type);
        //跟单概况 end
        $orderPlatformList = config('workorder.platform');
        $this->view->assign(compact(
            'orderPlatformList',
            'workList',
            'infoOne',
            'infoTwo',
            'workArr',
            'examineArr',
            'workorder_handle_left_data',
            'step',
            'workorder_handle_right_data',
            'warehouse_problem_type',
            'warehouse_handle',
            'todayData',
            'yesterdayData',
            'servenData',
            'thirdData',
            'replyArr',
            'customerType'
        ));
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
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $time = explode(' ', $params['time']);
            $map['create_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            $today = date('Y-m-d');
            $platform = $params['platform'];
            //1.今天数据
            $todayData = $this->workload->gettodayData($platform);
            //昨天数据
            $yesterdayData = $this->workload->getyesterdayData($platform);
            //过去7天数据
            $servenData = $this->workload->getSevenData($platform);
            //过去30天数据
            $thirdData = $this->workload->getthirdData($platform);
            if ($today == $time[0]) {
                $info = $this->workload->gettodayData($platform);
            } else {
                $info = $this->workload->gettwoTimeData($time[0], $time[3], $platform);
            }

            $data = [
                'todayData' => $todayData,
                'yesterdayData' => $yesterdayData,
                'servenData' => $servenData,
                'thirdData'  => $thirdData,
                'start'      => $time[0],
                'end'        => $time[3],
                'info'       => $info
            ];
            $this->success('', '', $data);
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
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $timeOne = explode(' ', $params['time']);
                $map_create['create_time'] = $map_measure['w.create_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $map_create['create_time'] = $map_measure['w.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            //1.求出三个审批人
            $kefumanage = config('workorder.kefumanage');
            $examine = [];
            foreach ($kefumanage as $k => $v) {
                $examine[] = $k;
            }
            $examine[] = config('workorder.customer_manager');
            $examinePerson = $this->customers();
            $examineArr = [];
            foreach ($examinePerson as $ek => $ev) {
                if (in_array($ek, $examine)) {
                    $examineArr[$ek] = $ev;
                }
            }
            $step = config('workorder.step');
            $workorder_handle_left_data = $this->workorder_handle_left($map_create, $examineArr);
            $workorder_handle_right_data = $this->workorder_handle_right($map_measure, $step);
            $data = [
                'examineArr' => $examineArr,
                'step'       => $step,
                'workorder_handle_left_data' => $workorder_handle_left_data,
                'workorder_handle_right_data' => $workorder_handle_right_data
            ];
            $this->success('', '', $data);
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
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $timeOne = explode(' ', $params['time']);
                $map_create['create_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $map_create['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            //1.求出三个审批人
            $warehouse_problem_type = config('workorder.warehouse_problem_type');
            $warehouse_data       = $this->warehouse_handle($map_create, $warehouse_problem_type);
            $data = [
                'warehouse_problem_type' => $warehouse_problem_type,
                'warehouse_data' => $warehouse_data
            ];
            $this->success('', '', $data);
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
    public function workorder_handle_left($map, $examinArr)
    {
        $where['is_check'] = 1;
        $where['work_type'] = 1;
        $where['work_status'] = ['in', [2, 3, 4, 5, 6]];
        //$where['work_status'] = ['lt',2];
        //$where['work_status'] = ['neq',7];
        //求出主管的超时时间
        $time_out = config('workorder.manage_time_out');
        $workList = $this->model->where($map)->where($where)->field('assign_user_id,submit_time,check_time')->select();
        $workList = collection($workList)->toArray($workList);
        if (!empty($workList)) {
            $arr = [];
            foreach ($examinArr as $ek => $ev) {
                $arr[$ek]['no_time_out_checked'] = $arr[$ek]['time_out_checked'] = $arr[$ek]['no_time_out_check'] = $arr[$ek]['time_out_check'] = 0;
            }
            foreach ($workList as $k => $v) {
                if (array_key_exists($v['assign_user_id'], $examinArr)) {
                    //审批时间存在证明已经审批
                    if ($v['check_time']) {
                        //如果两个时间差小于指定超时时间说明未超时
                        if ($time_out > (strtotime($v['check_time']) - strtotime($v['submit_time']))) {
                            //未超时已审批
                            $arr[$v['assign_user_id']]['no_time_out_checked']++;
                        } else {
                            //超时已审批
                            $arr[$v['assign_user_id']]['time_out_checked']++;
                        }
                    } else {
                        //审批时间不存在证明没有审批,判断提交时间和现在的时间比较是否超时
                        //如果两个时间差小于指定超时时间说明未超时
                        if ($time_out > (strtotime("now") - strtotime($v['submit_time']))) {
                            //未超时未审批
                            $arr[$v['assign_user_id']]['no_time_out_check']++;
                        } else {
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
    public function workorder_handle_right($map, $step)
    {
        //$where['is_check'] = 1;
        $where['work_type'] = 1;
        //where('id','not in',[1,5,8]);
        $where['work_status'] = ['in', [3, 5, 6]];
        //$where['work_status'] = ['neq',7];
        //求出措施的超时时间
        $time_out = config('workorder.step_time_out');
        $workMeasure = $this->model->where($map)->where($where)->alias('w')->join('work_order_measure m', 'w.id=m.work_id')->field('w.check_time,m.operation_time,m.measure_choose_id')->select();
        $workMeasure = collection($workMeasure)->toArray($workMeasure);
        if (!empty($workMeasure)) {
            $arr = [];
            //no_time_out_handled 未超时已处理
            //time_out_handled  超时已处理
            //no_time_out_handle 未超时未处理
            //time_out_handle 超时未处理
            foreach ($step as $ek => $ev) {
                $arr[$ek]['no_time_out_handled'] = $arr[$ek]['time_out_handled'] = $arr[$ek]['no_time_out_handle'] = $arr[$ek]['time_out_handle'] = 0;
            }
            foreach ($workMeasure as $k => $v) {
                if (array_key_exists($v['measure_choose_id'], $step)) {
                    //处理时间存在证明已经审批
                    if ($v['operation_time']) {
                        //如果存在超时时间
                        if ($time_out[$v['measure_choose_id']]) {
                            //如果两个时间差小于指定超时时间说明未超时
                            if ($time_out[$v['measure_choose_id']] > (strtotime($v['operation_time']) - strtotime($v['check_time']))) {
                                //未超时已处理
                                $arr[$v['measure_choose_id']]['no_time_out_handled']++;
                            } else {
                                //超时已处理
                                $arr[$v['measure_choose_id']]['time_out_handled']++;
                            }
                        } else { //如果不存在超时时间
                            $arr[$v['measure_choose_id']]['no_time_out_handled']++;
                        }
                    } else {
                        //审批时间不存在证明没有审批,判断提交时间和现在的时间比较是否超时
                        //如果两个时间差小于指定超时时间说明未超时
                        //如果存在超时时间
                        if ($time_out[$v['measure_choose_id']]) {
                            //如果两个时间差小于指定超时时间说明未超时
                            if ($time_out[$v['measure_choose_id']] > (strtotime("now") - strtotime($v['check_time']))) {
                                //未超时已处理
                                $arr[$v['measure_choose_id']]['no_time_out_handle']++;
                            } else {
                                //超时已处理
                                $arr[$v['measure_choose_id']]['time_out_handle']++;
                            }
                        } else { //如果不存在超时时间
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
    public function warehouse_handle($map, $warehouse_problem_type)
    {
        $where['work_type'] = 2;
        $where['work_status'] = ['in', [3, 5, 6]];
        //$where['work_status'] = ['lt',2];
        //求出主管的超时时间
        $time_out = config('workorder.warehouse_time_out');
        $workList = $this->model->where($map)->where($where)->field('problem_type_id,check_time,complete_time')->select();
        $workList = collection($workList)->toArray($workList);
        if (!empty($workList)) {
            $arr = [];
            //no_time_out_handled 未超时已处理
            //time_out_handled  超时已处理
            //no_time_out_handle 未超时未处理
            //time_out_handle 超时未处理
            foreach ($warehouse_problem_type as $ek => $ev) {
                $arr[$ek]['no_time_out_handled'] = $arr[$ek]['time_out_handled'] = $arr[$ek]['no_time_out_handle'] = $arr[$ek]['time_out_handle'] = 0;
            }
            foreach ($workList as $k => $v) {
                if (array_key_exists($v['problem_type_id'], $warehouse_problem_type)) {
                    //处理时间存在证明已经审批
                    if ($v['complete_time']) {
                        //如果两个时间差小于指定超时时间说明未超时
                        if ($time_out[$v['problem_type_id']] > (strtotime($v['complete_time']) - strtotime($v['check_time']))) {
                            //未超时已审批
                            $arr[$v['problem_type_id']]['no_time_out_handled']++;
                        } else {
                            //超时已审批
                            $arr[$v['problem_type_id']]['time_out_handled']++;
                        }
                    } else {
                        //审批时间不存在证明没有审批,判断提交时间和现在的时间比较是否超时
                        //如果两个时间差小于指定超时时间说明未超时
                        if ($time_out[$v['problem_type_id']] > (strtotime("now") - strtotime($v['check_time']))) {
                            //未超时未审批
                            $arr[$v['problem_type_id']]['no_time_out_handle']++;
                        } else {
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
     * 计算未达标天数
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 14:41:32
     * @return void
     */
    public function calculate_no_qualified_day($admin_id, $start, $end)
    {
        $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        $this->ZendeskTasks     = new \app\admin\model\zendesk\ZendeskTasks;
        $starttime = strtotime($start);
        $endtime   = strtotime($end);
        //求出中间的所有数
        $arr = [];
        for ($starttime; $starttime <= $endtime; $starttime += 86400) {
            $arr[] = $starttime;
        }
        $where['is_public'] = 1;
        $where['due_id'] = $assignee['admin_id'] =  $admin_id;
        //未达标天数
        $no_qualified_day = 0;
        foreach ($arr as $v) {
            $map['create_time'] = $assignee['create_time'] = ['between', [date('Y-m-d 00:00:00', $v), date('Y-m-d H:i:s', $v + 86400)]];
            //这天的回复量
            $customerReply = $this->zendeskComments->where($where)->where($map)->count("*");
            //这天的目标量
            $check_count  =  $this->ZendeskTasks->where($assignee)->value('check_count');
            if ($customerReply < $check_count) {
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
                $mapOne['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            if ($params['two_time']) {
                $timeTwo = explode(' ', $params['two_time']);
                $mapTwo['complete_time'] = ['between', [$timeTwo[0] . ' ' . $timeTwo[1], $timeTwo[3] . ' ' . $timeTwo[4]]];
            }
            if (10 != $params['order_platform']) {
                $where['work_platform'] = $params['order_platform'];
            } else {
                $where['work_platform'] = ['in', [1, 2, 3]];
            }
            //员工分组
            $customer_type = $params['customer_type'];
            //员工分类
            $customer_category = $params['customer_category'];
            $worklistOne = $this->works_info($where, $mapOne, $customer_type, $customer_category);
            if (!empty($mapTwo)) {
                $worklistTwo = $this->works_info($where, $mapTwo, $customer_type, $customer_category);
            }
            // dump($worklistOne);dump($worklistTwo);
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
                    $start = date('Y-m-d', strtotime('-6 day'));
                    $end   = date('Y-m-d');
                }
                //销毁变量
                unset($worklistOne['workOrderNum'], $worklistOne['totalOrderMoney'], $worklistOne['replacementNum'], $worklistOne['refundMoneyNum'], $worklistOne['refundMoney']);
                $this->view->assign([
                    'type' => 2,
                    'allCustomers'  => $worklistOne,
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
                    $startOne = date('Y-m-d', strtotime('-6 day'));
                    $endOne   = date('Y-m-d');
                }
                $startTwo = $timeTwo[0];
                $endTwo   = $timeTwo[3];
                //销毁变量
                unset($worklistOne['workOrderNum'], $worklistOne['totalOrderMoney'], $worklistOne['replacementNum'], $worklistOne['refundMoneyNum'], $worklistOne['refundMoney']);
                unset($worklistTwo['workOrderNum'], $worklistTwo['totalOrderMoney'], $worklistTwo['replacementNum'], $worklistTwo['refundMoneyNum'], $worklistTwo['refundMoney']);
                $this->view->assign([
                    'type'         => 3,
                    'worklistOne'  => $worklistOne,
                    'worklistTwo'  => $worklistTwo,
                    'startOne'     => $startOne,
                    'endOne'       => $endOne,
                    'startTwo'     => $startTwo,
                    'endTwo'       => $endTwo,
                    'startTwo'     => $startTwo,
                    'endTwo'       => $endTwo,
                    'platform'     => $platform
                ]);
            }
            $orderPlatformList = config('workorder.platform');
            $this->view->assign(
                [
                    'customerType' => $customer_type,
                    'customerCategory' => $customer_category
                ]
            );
            $this->view->assign(compact('orderPlatformList', 'workOrderNum', 'totalOrderMoney', 'replacementNum', 'refundMoneyNum', 'refundMoney', 'type', 'category'));
        } else {
            //默认显示
            //根据筛选时间求出客服部门下面所有有数据人员
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d');
            $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            $where['work_type'] = $whereCreate['work_type'] = 1;
            $where['work_platform'] = $whereCreate['work_platform'] = 1;
            $where['work_status'] = 6;
            $workList = $this->model->where($where)->where($map)->field('count(*) as counter,sum(base_grand_total) as base_grand_total,
            sum(is_refund) as refund_num,create_user_id,create_user_name')->group('create_user_id')->select();

            //计算工单创建总量 2020.07.28 18:45 jhh 取所有状态下的工单包括新建取消待审核审核成功审核拒绝部分处理已处理取消的
            $where1 = $where;
            $where1['work_status'] = ['in', [0, 1, 2, 3, 4, 5, 6, 7]];
            $map1['create_time'] = $map['complete_time'];
            $workListAllcounter = $this->model->where($where1)->where($map1)->field('count(*) as create_counter,create_user_id')->group('create_user_id')->select();

            $workListAllcounter = array_column(collection($workListAllcounter)->toArray(), NULL, 'create_user_id');
            //  dump(collection($workList)->toArray());
            //  dump($workListAllcounter);
            //$where['replacement_order'] = ['neq',''];
            //补发单数和优惠券发放量
            $replacementOrder = $this->model->where($where)->where($map)->field('count(replacement_order !="" or null) as counter,count(coupon_str !="" or null),create_user_id')->group('create_user_id')->select();
            $workList = collection($workList)->toArray();
            $replacementOrder = collection($replacementOrder)->toArray();

            foreach ($workList as $kk => $vv) {
                $workList[$kk]['create_counter'] = $workListAllcounter[$vv['create_user_id']]['create_counter'];
            }
            // dump($workList);
            if (!empty($replacementOrder)) {
                $replacementArr = $couponArr = [];
                foreach ($replacementOrder as $rk => $rv) {
                    $replacementArr[$rv['create_user_id']] = $rv['counter'];
                    $couponArr[$rv['create_user_id']] = $rv['coupon'];
                }
            }
            //客服分组
            //$kefumanage = config('workorder.kefumanage');
            //整个客服部门人员
            $allCustomers = $this->newCustomers();
            $workOrderNum = $totalOrderMoney = $replacementNum = $refundMoneyNum = $refundMoney = 0;
            foreach ($allCustomers as $k => $v) {
                if (is_array($replacementArr)) {
                    //客服的补发订单数
                    if (array_key_exists($v['id'], $replacementArr)) {
                        $allCustomers[$k]['replacement_num'] = $replacementArr[$v['id']];
                        //优惠券发放量
                        $allCustomers[$k]['coupon']          = $couponArr[$v['id']];
                        //累计补发单数
                        $replacementNum += $replacementArr[$v['id']];
                    } else {
                        $allCustomers[$k]['replacement_num'] = 0;
                        $allCustomers[$k]['coupon'] = 0;
                    }
                } else {
                    $allCustomers[$k]['replacement_num'] = 0;
                    $allCustomers[$k]['coupon'] = 0;
                }
                //累计退款金额 
                $allCustomers[$k]['total_refund_money'] = $this->calculate_refund_money($v['id'], $map, $where['work_platform']);
                if (0 < $allCustomers[$k]['total_refund_money']) {
                    $refundMoney += $allCustomers[$k]['total_refund_money'];
                }
                if (!empty($workList)) {
                    foreach ($workList as $wk => $wv) {
                        if ($v['id'] == $wv['create_user_id']) {
                            $allCustomers[$k]['counter'] = $wv['counter'];
                            $allCustomers[$k]['create_counter'] = $wv['create_counter'];
                            $allCustomers[$k]['base_grand_total'] = $wv['base_grand_total'];
                            $allCustomers[$k]['refund_num'] = $wv['refund_num'];
                            //累计工单完成量
                            $workOrderNum += $wv['counter'];
                            //累计订单总金额
                            $totalOrderMoney += $wv['base_grand_total'];
                            //累计退款单数
                            $refundMoneyNum += $wv['refund_num'];
                        }
                    }
                }
            }
            $orderPlatformList = config('workorder.platform');
            $this->view->assign('type', 1);
            $this->view->assign(compact('orderPlatformList', 'allCustomers', 'start', 'end', 'workOrderNum', 'totalOrderMoney', 'replacementNum', 'refundMoneyNum', 'refundMoney'));
        }
        //客服数据
        $customer_type = config('workorder.customer_type');
        $customer_category = config('workorder.customer_category');
        $this->view->assign(compact('customer_type', 'customer_category'));
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
    public function calculate_refund_money($create_user_id, $map, $type)
    {
        $where['create_user_id'] = $create_user_id;
        $where['refund_money']   = ['GT', 0];
        $where['work_type'] = 1;
        $where['work_status'] = 6;
        if ($type != 10) {
            $where['work_platform'] = $type;
        }
        $info = $this->model->where($where)->where($map)->field('base_to_order_rate,refund_money')->select();
        if (!empty($info)) {
            $refund_money = 0;
            foreach ($info as $v) {
                if (0 < $v['base_to_order_rate']) {
                    $refund_money += round($v['refund_money'] / $v['base_to_order_rate'], 2);
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
    public function customers()
    {
        $kefumanage = config('workorder.kefumanage');
        $arr = [];
        foreach ($kefumanage as $k => $v) {
            $arr[] = $k;
            foreach ($v as $val) {
                $arr[] = $val;
            }
        }
        $result  = Admin::where('id', 'in', $arr)->column('id,nickname');
        //$result[1]  = 'Admin';
        $result[75] = '王伟';
        return $result;
    }
    /**
     * 获取客服人员信息(全部)新
     *
     * @Description
     * @author lsw
     * @since 2020/05/28 15:59:10
     * @return void
     */
    public function newCustomers()
    {
        $kefumanage = config('workorder.kefumanage');
        $arr = [];
        foreach ($kefumanage as $k => $v) {
            $arr[] = $k;
            foreach ($v as $val) {
                $arr[] = $val;
            }
        }
        $arr[] = 75;
        $result  = Admin::where('id', 'in', $arr)->field('id,nickname')->select();
        if (!empty($result)) {
            $result = collection($result)->toArray();
            foreach ($result as $k => $v) {
                if (in_array($v['id'], $kefumanage[95]) || (95 == $v['id'])) {
                    $result[$k]['group'] = 'B组';
                } elseif (in_array($v['id'], $kefumanage[117]) || (117 == $v['id'])) {
                    $result[$k]['group'] = 'A组';
                } else {
                    $result[$k]['group'] = '未知';
                }
            }
        }
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
    public function customers_by_group($type)
    {
        $kefumanage = config('workorder.kefumanage');
        $arr = [];
        if (1 == $type) {
            foreach ($kefumanage[117] as $v) {
                $arr[] = $v;
            }
            $arr[] = 117;
        } elseif (2 == $type) {
            foreach ($kefumanage[95] as $v) {
                $arr[] = $v;
            }
            $arr[] = 95;
        } else {
            foreach ($kefumanage as $k => $v) {
                $arr[] = $k;
                foreach ($v as $val) {
                    $arr[] = $val;
                }
            }
            $result[75] = '王伟';
        }
        // $result[1]  = 'Admin';
        // $result[75] = '王伟';
        $result  = Admin::where('id', 'in', $arr)->column('id,nickname');
        return $result;
    }
    /**
     * 获取本月工单创建量
     *
     * @Description
     * @author lsw
     * @since 2020/05/27 18:46:56 
     * @return void
     */
    public function getThisMonthWorkOrderNum()
    {
        $map_create['create_time'] = ['between', [date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), 1, date("Y"))), date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("t"), date("Y")))]];
        $where['create_user_id'] = ['neq', 0];
        return $this->model->where($map_create)->where($where)->field('count(*) as counter,create_user_id')->group('create_user_id')->select();
    }
    /**
     * 获取昨天工作量
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 10:01:17 
     * @return void
     */
    public function getyesterdayWorkloadNum()
    {
        $where['is_public'] = 1;
        $where['is_admin']  = 1;
        $where['author_id'] = ['neq', '382940274852'];
        $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        $workload['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 00:00:00', time())]];
        return $this->zendeskComments->where($where)->where($workload)->field('count(*) as counter,due_id')->group('due_id')->select();
    }
    /**
     * 获取工作量信息
     *
     * @Description
     * @author lsw
     * @since 2020/05/23 15:49:44
     * @return void
     */
    public function workload_info($map, $start, $end, $platform, $customer_type = 0, $customer_category = 0, $customer_workload = 0)
    {
        $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        //默认显示
        //根据筛选时间求出客服部门下面所有有数据人员
        //$start = date('Y-m-d', strtotime('-30 day'));
        //$end   = date('Y-m-d');
        //$map['c.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
        $where['is_public'] = 1;
        $where['is_admin']  = 1;
        //$where['due_id']    = ['neq',0];
        //$where['author_id'] = ['neq','382940274852'];
        //平台
        if ($platform < 10) {
            $where['platform'] = $platform;
        }
        if (1 == $customer_type) {
            $type = $this->customers_by_group(1);
            //B组员工      
        } elseif (2 == $customer_type) {
            $type = $this->customers_by_group(2);
        } else { //全部
            $type = $this->customers_by_group(0);
        }
        $type_arr = $category_arr = $group_arr = [];
        if (!empty($type)) {
            foreach ($type as $k => $v) {
                $type_arr[] = $k;
            }
        }
        //正式员工
        if (1 == $customer_category) {
            $category = $this->getCustomerFormal(1);
        } elseif (2 == $customer_category) { //非正式员工
            $category = $this->getCustomerFormal(2);
        } else {
            //全部
            $category = $this->getCustomerFormal(0);
        }
        if (!empty($category)) {
            foreach ($category as $k => $v) {
                $category_arr[] = $v;
            }
        }
        //客服员工分组 电话/邮件
        if (1 == $customer_workload) {
            $customer_group = $this->getAllCustomerType(1);
        } elseif (2 == $customer_workload) {
            $customer_group = $this->getAllCustomerType(2);
        }
        if (!empty($customer_group)) {
            foreach ($customer_group as $k => $v) {
                $group_arr[] = $k;
            }
        }
        if (count($group_arr) < 1) {
            $filterPerson = array_intersect($type_arr, $category_arr);
        } else {
            $filterPerson = array_intersect($type_arr, $category_arr, $group_arr);
        }
        $where['due_id'] = ['in', $filterPerson];


        // if(count($type_arr)>0 && count($category_arr)==0){
        //     $filterPerson  = $type_arr;
        //     $where['due_id'] = ['in',$type_arr];
        // }elseif(count($type_arr)>0 && count($category_arr)>0){
        //     $filterPerson = array_intersect($type_arr,$category_arr);
        //     $where['due_id'] = ['in',$filterPerson];
        // }elseif(count($type_arr) == 0 && count($category_arr)>0){
        //     $filterPerson = $category_arr;
        //     $where['due_id'] = ['in',$category_arr];
        // }else{
        //     $where['due_id'] = ['neq',0];
        // }

        //整个客服部门人员
        $arrCustomers = $this->newCustomers();
        $allCustomers = [];
        if (isset($filterPerson)) {
            foreach ($arrCustomers as $k => $v) {
                if (in_array($v['id'], $filterPerson)) {
                    $allCustomers[$k]['id'] = $v['id'];
                    $allCustomers[$k]['nickname'] = $v['nickname'];
                    $allCustomers[$k]['group'] = $v['group'];
                }
            }
        } else {
            $allCustomers = $arrCustomers;
        }
        //获取组别
        $customerArr     = $this->getCustomerType();
        //客服处理量
        $customerReply = $this->zendeskComments->where($where)->where($map)->field('count(*) as counter,due_id')->group('due_id')->select();
        $customerReply = collection($customerReply)->toArray();
        if (!empty($allCustomers)) {
            $handleNum = $noQualifiyDay =  0;
            foreach ($allCustomers as $k => $v) {
                if (!empty($customerReply)) {
                    foreach ($customerReply as $ck => $cv) {
                        if ($v['id'] == $cv['due_id']) {
                            $allCustomers[$k]['counter'] = $cv['counter'];
                            $allCustomers[$k]['no_qualified_day'] = $this->calculate_no_qualified_day($cv['due_id'], $start, $end);
                            $handleNum += $cv['counter'];
                            $noQualifiyDay += $allCustomers[$k]['no_qualified_day'];
                        }
                    }
                }
                if (!empty($customerArr)) {
                    if (array_key_exists($v['id'], $customerArr)) {
                        $allCustomers[$k]['workload_group'] = $customerArr[$v['id']];
                    }
                }
            }
            $allCustomers['handleNum'] = $handleNum;
            $allCustomers['noQualifiyDay'] = $noQualifiyDay;
        }
        return $allCustomers ? $allCustomers : false;
    }
    /**
     * 原先的workload_info 
     *
     * @Description
     * @author lsw
     * @since 2020/05/29 10:13:28 
     * @param [type] $map
     * @param [type] $start
     * @param [type] $end
     * @param [type] $platform
     * @param integer $customer_type
     * @param integer $customer_category
     * @return void
     */
    public function workload_info_original($map, $start, $end, $platform, $customer_type = 0, $customer_category = 0)
    {
        $this->zendeskComments  = new \app\admin\model\zendesk\ZendeskComments;
        //默认显示
        //根据筛选时间求出客服部门下面所有有数据人员
        //$start = date('Y-m-d', strtotime('-30 day'));
        //$end   = date('Y-m-d');
        //$map['c.create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];
        $where['is_public'] = 1;
        $where['is_admin']  = 1;
        //$where['due_id']    = ['neq',0];
        //$where['author_id'] = ['neq','382940274852'];
        //平台
        if ($platform < 10) {
            $where['platform'] = $platform;
        }
        if (1 == $customer_type) {
            $type = $this->customers_by_group(1);
            //B组员工      
        } elseif (2 == $customer_type) {
            $type = $this->customers_by_group(2);
        }

        //AB组员工ID
        $type_arr = $category_arr = [];
        if (!empty($type)) {
            foreach ($type as $k => $v) {
                $type_arr[] = $k;
            }
        }
        //正式员工
        if (1 == $customer_category) {
            $category = $this->getCustomerFormal(1);
        } elseif (2 == $customer_category) { //非正式员工
            $category = $this->getCustomerFormal(2);
        }

        if (!empty($category)) {
            foreach ($category as $k => $v) {
                $category_arr[] = $v;
            }
        }

        //AB组员工为真 并且 
        if (count($type_arr) > 0 && count($category_arr) == 0) {
            $where['due_id'] = ['in', $type_arr];
        } elseif (count($type_arr) > 0 && count($category_arr) > 0) {
            $final_arr = array_intersect($type_arr, $category_arr);
            $where['due_id'] = ['in', $final_arr];
        } elseif (count($type_arr) == 0 && count($category_arr) > 0) {
            $where['due_id'] = ['in', $category_arr];
        } else {
            $where['due_id'] = ['neq', 0];
        }
        //客服处理量
        $customerReply = $this->zendeskComments->where($where)->where($map)->field('count(*) as counter,due_id')->group('due_id')->select();
        $customerReply = collection($customerReply)->toArray();

        //客服分组
        $info = $this->customers();
        $kefumanage = config('workorder.kefumanage');
        //客服组信息电话、邮件
        //$customerType = $this->getCustomerType();
        if (!empty($customerReply)) {
            $handleNum = $noQualifiyDay = 0;
            foreach ($customerReply as $k => $v) {
                //客服分组
                if (in_array($v['due_id'], $kefumanage[95]) || (95 == $v['due_id'])) {
                    $customerReply[$k]['group'] = 'B组';
                } elseif (in_array($v['due_id'], $kefumanage[117]) || (117 == $v['due_id'])) {
                    $customerReply[$k]['group'] = 'A组';
                } else {
                    $customerReply[$k]['group'] = '未知';
                }
                if (array_key_exists($v['due_id'], $info)) {
                    $customerReply[$k]['create_user_name'] = $info[$v['due_id']];
                }
                // if(count($customerType)>1){
                //     if(array_key_exists($v['due_id'],$customerType)){
                //         $customerReply[$k]['customer_type'] = $customerType[$v['due_id']];
                //     }
                // }else{
                //     $customerReply[$k]['customer_type'] = 0;
                // }
                $customerReply[$k]['no_qualified_day'] = $this->calculate_no_qualified_day($v['due_id'], $start, $end);
                $handleNum += $v['counter'];
                $noQualifiyDay += $customerReply[$k]['no_qualified_day'];
            }
            $customerReply['handleNum'] = $handleNum;
            $customerReply['noQualifiyDay'] = $noQualifiyDay;
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
    public function works_info($where, $map, $customer_type = 0, $customer_category = 0)
    {
        if (!empty($where['work_platform']) && ($where['work_platform'] != 10)) {
            //站点
            $site  = $where['work_platform'];
        } else {
            $site  = 10;
        }
        $where['work_type'] = 1;
        $where['work_status'] = 6;
        //A组员工
        if (1 == $customer_type) {
            $type = $this->customers_by_group(1);
            //B组员工      
        } elseif (2 == $customer_type) {
            $type = $this->customers_by_group(2);
        }
        $type_arr = $category_arr = [];
        if (!empty($type)) {
            foreach ($type as $k => $v) {
                $type_arr[] = $k;
            }
        }
        //正式员工
        if (1 == $customer_category) {
            $category = $this->getCustomerFormal(1);
        } elseif (2 == $customer_category) { //非正式员工
            $category = $this->getCustomerFormal(2);
        }
        if (!empty($category)) {
            foreach ($category as $k => $v) {
                $category_arr[] = $v;
            }
        }
        if (count($type_arr) > 0 && count($category_arr) == 0) {
            //求出筛选的人
            $filterPerson  = $type_arr;
            $where['create_user_id'] = ['in', $type_arr];
        } elseif (count($type_arr) > 0 && count($category_arr) > 0) {
            $filterPerson = array_intersect($type_arr, $category_arr);
            $where['create_user_id'] = ['in', $filterPerson];
        } elseif (count($type_arr) == 0 && count($category_arr) > 0) {
            $filterPerson = $category_arr;
            $where['create_user_id'] = ['in', $category_arr];
        }
        //整个客服部门人员
        $arrCustomers = $this->newCustomers();
        $allCustomers = [];
        if (isset($filterPerson)) {
            foreach ($arrCustomers as $k => $v) {
                if (in_array($v['id'], $filterPerson)) {
                    $allCustomers[$k]['id'] = $v['id'];
                    $allCustomers[$k]['nickname'] = $v['nickname'];
                    $allCustomers[$k]['group'] = $v['group'];
                }
            }
        } else {
            $allCustomers = $arrCustomers;
        }
        $workList = $this->model->where($where)->where($map)->field('count(*) as counter,sum(base_grand_total) as base_grand_total,
        sum(is_refund) as refund_num,create_user_id,create_user_name')->group('create_user_id')->select();
        //    dump(collection($workList)->toArray());
        //$where['replacement_order'] = ['neq',''];
        $replacementOrder = $this->model->where($where)->where($map)->field('count(replacement_order !="" or null) as counter,count(coupon_str !="" or null) as coupon,create_user_id')->group('create_user_id')->select();

        //计算工单创建总量 2020.07.28 18:45 jhh 取所有状态下的工单包括新建取消待审核审核成功审核拒绝部分处理已处理取消的
        $where['work_status'] = ['in', [0, 1, 2, 3, 4, 5, 6, 7]];
        $map1['create_time'] = $map['complete_time'];
        $workListAllcounter = $this->model->where($where)->where($map1)->field('count(*) as create_counter,create_user_id,create_user_name')->group('create_user_id')->select();
        //  dump(collection($workListAllcounter)->toArray());

        $workList = collection($workList)->toArray();

        $workListAllcounter = array_column(collection($workListAllcounter)->toArray(), NULL, 'create_user_id');
        // $workList = array_merge_recursive(array_column($workList,NULL,'create_user_id'),$workListAllcounter);
        //  dump($workList);

        $replacementOrder = collection($replacementOrder)->toArray();
        foreach ($workList as $kk => $vv) {
            $workList[$kk]['create_counter'] = $workListAllcounter[$vv['create_user_id']]['create_counter'];
        }
        // dump($workList);
        if (!empty($replacementOrder)) {
            $replacementArr = [];
            foreach ($replacementOrder as $rk => $rv) {
                $replacementArr[$rv['create_user_id']] = $rv['counter'];
                $couponArr[$rv['create_user_id']] = $rv['coupon'];
            }
        }

        $workOrderNum = $totalOrderMoney = $replacementNum = $refundMoneyNum = $refundMoney = 0;
        foreach ($allCustomers as $k => $v) {
            if (isset($replacementArr) && is_array($replacementArr)) {
                //客服的补发订单数
                if (array_key_exists($v['id'], $replacementArr)) {
                    $allCustomers[$k]['replacement_num'] = $replacementArr[$v['id']];
                    //优惠券发放量
                    $allCustomers[$k]['coupon']          = $couponArr[$v['id']];
                    //累计补发单数
                    $replacementNum += $replacementArr[$v['id']];
                } else {
                    $allCustomers[$k]['replacement_num'] = 0;
                    $allCustomers[$k]['coupon'] = 0;
                }
            } else {
                $allCustomers[$k]['replacement_num'] = 0;
                $allCustomers[$k]['coupon'] = 0;
            }
            //累计退款金额
            $allCustomers[$k]['total_refund_money'] = $this->calculate_refund_money($v['id'], $map, $site);
            if (0 < $allCustomers[$k]['total_refund_money']) {
                $refundMoney += $allCustomers[$k]['total_refund_money'];
            }
            if (!empty($workList)) {
                foreach ($workList as $wk => $wv) {
                    if ($v['id'] == $wv['create_user_id']) {
                        $allCustomers[$k]['counter'] = $wv['counter'];
                        $allCustomers[$k]['create_counter'] = $wv['create_counter'];
                        $allCustomers[$k]['base_grand_total'] = $wv['base_grand_total'];
                        $allCustomers[$k]['refund_num'] = $wv['refund_num'];
                        //累计工单完成量
                        $workOrderNum += $wv['counter'];
                        //累计订单总金额
                        $totalOrderMoney += $wv['base_grand_total'];
                        //累计退款单数
                        $refundMoneyNum += $wv['refund_num'];
                    }
                }
            }
        }
        $allCustomers['workOrderNum']    = $workOrderNum;
        $allCustomers['totalOrderMoney'] = $totalOrderMoney;
        $allCustomers['replacementNum']  = $replacementNum;
        $allCustomers['refundMoneyNum']  = $refundMoneyNum;
        $allCustomers['refundMoney']     = $refundMoney;

        return $allCustomers ? $allCustomers : false;
    }
    /**
     * 原先的 works_info
     *
     * @Description
     * @author lsw
     * @since 2020/05/29 09:03:55 
     * @param [type] $where
     * @param [type] $map
     * @param integer $customer_type
     * @param integer $customer_category
     * @return void
     */
    public function works_info_original($where, $map, $customer_type = 0, $customer_category = 0)
    {
        $where['work_type'] = 1;
        $where['work_status'] = 6;
        //A组员工
        if (1 == $customer_type) {
            $type = $this->customers_by_group(1);
            //B组员工      
        } elseif (2 == $customer_type) {
            $type = $this->customers_by_group(2);
        }
        $type_arr = $category_arr = [];
        if (!empty($type)) {
            foreach ($type as $k => $v) {
                $type_arr[] = $k;
            }
        }
        //正式员工
        if (1 == $customer_category) {
            $category = $this->getCustomerFormal(1);
        } elseif (2 == $customer_category) { //非正式员工
            $category = $this->getCustomerFormal(2);
        }
        if (!empty($category)) {
            foreach ($category as $k => $v) {
                $category_arr[] = $v;
            }
        }
        if (count($type_arr) > 0 && count($category_arr) == 0) {
            $where['create_user_id'] = ['in', $type_arr];
        } elseif (count($type_arr) > 0 && count($category_arr) > 0) {
            $final_arr = array_intersect($type_arr, $category_arr);
            $where['create_user_id'] = ['in', $final_arr];
        } elseif (count($type_arr) == 0 && count($category_arr) > 0) {
            $where['create_user_id'] = ['in', $category_arr];
        }
        $workList = $this->model->where($where)->where($map)->field('count(*) as counter,sum(base_grand_total) as base_grand_total,
        sum(is_refund) as refund_num,create_user_id,create_user_name')->group('create_user_id')->select();
        //$where['replacement_order'] = ['neq',''];
        $replacementOrder = $this->model->where($where)->where($map)->field('count(replacement_order !="" or null) as counter,count(coupon_str !="" or null),create_user_id')->group('create_user_id')->select();
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
                if (in_array($v['create_user_id'], $kefumanage[95]) || (95 == $v['create_user_id'])) {
                    $workList[$k]['group'] = 'B组';
                } elseif (in_array($v['create_user_id'], $kefumanage[117]) || ($v['create_user_id'] == 117)) {
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
                $workList[$k]['total_refund_money'] = $this->calculate_refund_money($v['create_user_id'], $map, 10);
                if (0 < $workList[$k]['total_refund_money']) {
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
     * @author wpl
     * @since 2020/07/31 10:18:10 
     * @return void
     */
    public function detail()
    {

        //异步调用图标数据
        if ($this->request->isAjax()) {

            $create_time = input('time');
            $platform    = input('order_platform', 1);
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['create_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            $site = input('platform', 1); //默认zeelool
            $key = input('key');
            $problem_id = input('problem_id', 2); //默认问题类型为物流仓库
            $step_problem_id = input('step_problem_id', 5); //默认类型为物流仓库->关税
            //查询各分类占比 默认订单修改 zeelool 
            $problem_type_data = $this->problem_type->getProblemTypeData($problem_id, $site, $map);

            //查询默认分类
            $problem =  $this->problem_type->getProblemType($problem_id);

            //查询默认下各措施占比
            $problem_data = $this->problem_step->getProblemData($step_problem_id, $site, $map);

            //问题措施比统计
            if ('echart1' == $key) {
                //循环数组根据id获取客服问题类型
                $column = $columnData = [];
                $i = 0;
                foreach ($problem_type_data as $k => $v) {
                    $column[] = $problem[$v['problem_type_id']];
                    $columnData[$i]['name'] = $problem[$v['problem_type_id']];
                    $columnData[$i]['value'] = $v['num'];
                    $i++;
                }
            } elseif ('echart2' == $key) {
                $column = array_column($problem_data, 'name');
                $columnData = $problem_data;
            }

            //求出问题大分类的总数,措施的总数
            $problem_type_total = array_sum(array_column($problem_type_data, 'num'));
            $problem_step_total = array_sum(array_column($problem_data, 'value'));

            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json, 'list_data' => ['problem_type_data' => $problem_type_data, 'problem_data' => $problem_data, 'problem' => $problem, 'problem_type_total' => $problem_type_total, 'problem_step_total' => $problem_step_total]]);
        }

        $create_time = input('create_time');
        $platform    = input('order_platform', 1);

        if ($create_time) {
            $time = explode(' ', $create_time);
            $map['create_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
        } else {
            $map['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
        }

        //问题大分类统计、措施统计
        $problem_type = $this->problem_type->getProblemBelongType();

        //查询各分类占比 默认物流仓库 zeelool 
        $problem_type_data = $this->problem_type->getProblemTypeData(2, 1, $map);

        //查询默认分类 物流仓库
        $problem =  $this->problem_type->getProblemType(2);

        //查询默认下各措施占比 默认关税
        $problem_data = $this->problem_step->getProblemData(5, 1, $map);

        //求出问题大分类的总数,措施的总数
        $problem_type_total = array_sum(array_column($problem_type_data, 'num'));
        $problem_step_total = array_sum(array_column($problem_data, 'value'));
        $orderPlatformList = config('workorder.platform');
        $this->view->assign(compact('orderPlatformList'));
        $this->view->assign(compact('problem_type', 'problem_type_data', 'problem', 'problem_data', 'problem_type_total', 'problem_step_total', 'platform'));
        return $this->view->fetch();
    }


    /**
     * 切换问题类型  弃用
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
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            $value = $params['value'];
            $order_platform = $params['platform'];
            //问题类型统计
            $problem_data = $this->get_problem_type_data($order_platform, $map, $value);
            //问题类型数组
            $customer_problem_arr   = config('workorder.new_customer_problem_classify_arr')[$value];
            //客服问题列表
            $customer_problem_list  = config('workorder.customer_problem_type');
            //仓库问题列表
            $warehouse_problem_list = config('workorder.warehouse_problem_type');
            //循环数组根据id获取客服问题类型
            $column = $columnData = [];
            foreach ($customer_problem_arr as $k => $v) {
                if ($value <= 4) {
                    $column[] = $customer_problem_list[$v];
                } else {
                    $column[] = $warehouse_problem_list[$v];
                }
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
     * 根据措施切换问题类型 弃用
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
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            $value = $params['value'];
            $order_platform = $params['platform'];
            $problem = $params['problem'];
            //问题类型统计
            $data = $this->get_problem_step_data($order_platform, $map, $value, $problem);
            //问题类型数组
            $step = config('workorder.step');
            $column = array_merge($step);
            $columnData = [];
            foreach ($column as $k => $v) {
                $columnData[$k]['name'] = $v;
                $columnData[$k]['value'] = $data['step'][$k];
            }
            $json['column'] = $column;
            $json['columnData'] = $columnData;
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 异步获取二级联动数据 弃用
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
            $data = $this->problem_type->getProblemType($value);
            $this->success('', '', $data);
        }
    }
    /**
     *获取workorder的统计数据 弃用
     *问题大分类统计、措施统计
     * @Description
     * @author lsw
     * @since 2020/05/12 10:02:54
     * @return void
     */
    public function get_workorder_data($platform, $map)
    {
        $arr = Cache::get('CustomerService_get_workorder_data_' . $platform . md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($platform < 10) {
            $where['work_platform'] = $warehouse['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        $warehouse['work_type'] = 2;
        //订单修改数组
        //$changeOrderArr = config('workorder.customer_problem_classify_arr')[1];
        //
        //问题总数组
        $problem_arr = config('workorder.new_customer_problem_classify_arr');
        //问题结果
        $result = [];
        foreach ($problem_arr as $k => $v) {
            //问题大分类的统计
            if ($k <= 4) {
                $result['problem_type'][] = $this->model->where($where)->where($map)->where('problem_type_id', 'in', $v)->count('id');
            } else {
                $result['problem_type'][] = $this->model->where($warehouse)->where($map)->count('id');
            }
        }
        //所有完成的work_id
        $all_work_id = $this->model->where($where)->where($map)->column('id');
        //措施总数组
        $step_arr = config('workorder.step');
        $where_step['operation_type'] = 1;
        foreach ($step_arr as $sk => $sv) {
            $result['step'][] = $this->step->where($where_step)->where('measure_choose_id', $sk)->where('work_id', 'in', $all_work_id)->count('id');
        }
        Cache::set('CustomerService_get_workorder_data_' . $platform . md5(serialize($map)), $result, 7200);
        return $result;
    }
    /**
     * 问题类型统计 弃用
     *
     * @Description
     * @author lsw
     * @since 2020/05/12 14:46:20
     * @return void
     */
    public function get_problem_type_data($platform, $map, $problem_type)
    {
        $arr = Cache::get('CustomerService_get_problem_type_data_' . $platform . '_' . $problem_type . md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($platform < 10) {
            $where['work_platform'] = $warehouse['work_platform'] = $platform;
        }
        $where['work_type'] = 1;
        $warehouse['work_type'] = 2;
        //所有的问题组
        $problem_arr = config('workorder.new_customer_problem_classify_arr');
        //当前的问题组
        $current_problem_arr = $problem_arr[$problem_type];
        $result = [];
        foreach ($current_problem_arr as $k => $v) {
            if ($problem_type <= 4) {
                $result[$k] = $this->model->where($where)->where($map)->where('problem_type_id', $v)->count('id');
            } else {
                $result[$k] = $this->model->where($warehouse)->where($map)->where('problem_type_id', $v)->count('id');
            }
        }
        Cache::set('CustomerService_get_problem_type_data_' . $platform . '_' . $problem_type . md5(serialize($map)), $result, 7200);
        return $result;
    }
    /**
     * 问题措施比统计 弃用
     *
     * @Description
     * @author lsw
     * @since 2020/05/12 15:16:48
     * @param [type] $platform
     * @param [type] $map
     * @param [type] $problem_type
     * @param [type] $step_id
     * @param [type] $problem 是否是仓库问题  5 是 其他不是 默认 1
     * @return void
     */
    public function get_problem_step_data($platform, $map, $problem_id, $problem = 1)
    {
        $arr = Cache::get('CustomerService_get_problem_step_data_' . $platform . '_' . $problem_id . $problem . md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($platform < 10) {
            $where['work_platform'] = $platform;
        }
        if ($problem != 5) {
            $where['work_type'] = 1;
        } else {
            $where['work_type'] = 2;
        }

        $result = $info = [];
        $result = $this->model->where($where)->where($map)->where('problem_type_id', $problem_id)->column('id');
        $where_step['operation_type'] = 1;
        $step_arr = config('workorder.step');
        foreach ($step_arr as $k => $v) {
            $info['step'][]  = $this->step->where($where_step)->where('work_id', 'in', $result)->where('measure_choose_id', $k)->count('id');
        }
        Cache::set('CustomerService_get_problem_step_data_' . $platform . '_' . $problem_id . $problem . md5(serialize($map)), $info, 7200);
        return $info;
    }
    /**
     * 异步获取第二个饼图右边的数据 弃用
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
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            $value          = $params['value'];
            $problem_data = $this->get_problem_type_data($order_platform, $map, $value);
            $problem_form_total = 0;
            foreach ($problem_data as $dv) {
                $problem_form_total += $dv;
            }
            $customer_problem_arr   = config('workorder.new_customer_problem_classify_arr')[$value];
            //客服问题列表
            $customer_problem_list  = config('workorder.customer_problem_type');
            //仓库问题列表
            $warehouse_problem_list = config('workorder.warehouse_problem_type');
            $customer_arr = [];
            foreach ($customer_problem_arr as $k => $v) {
                if ($value <= 4) {
                    $customer_arr[] = $customer_problem_list[$v];
                } else {
                    $customer_arr[] = $warehouse_problem_list[$v];
                }
            }
            $data['problem_data'] =  $problem_data;
            $data['problem_form_total'] =  $problem_form_total;
            $data['customer_arr'] =  $customer_arr;
            $this->success('', '', $data);
        }
    }
    /**
     * 异步获取第四个饼图右边数据 弃用
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
                $map['complete_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            $value          = $params['value'];
            $problem        = $params['problem'];
            $step_data = $this->get_problem_step_data($order_platform, $map, $value, $problem);
            $step_four_total = 0;
            //求出措施总数据
            foreach ($step_data['step'] as $tv) {
                $step_four_total += $tv;
            }
            //问题类型统计
            $step = array_merge(config('workorder.step'));
            $data['step_data'] =  $step_data;
            $data['step_four_total'] =  $step_four_total;
            $data['step'] =  $step;
            $this->success('', '', $data);
        }
    }

    /**
     * 获取正式与非正式员工
     *
     * @Description
     * @author lsw
     * @since 2020/05/28 11:19:33 
     * @return void
     */
    public function getCustomerFormal($type = 1)
    {
        $kefumanage = config('workorder.kefumanage');
        $arr = $info = [];
        foreach ($kefumanage as $k => $v) {
            $arr[] = $k;
            foreach ($v as $val) {
                $arr[] = $val;
            }
        }
        $arr[] = 75;
        $result  = Admin::where('id', 'in', $arr)->column('id,createtime');
        //区分员工时限
        $time_out = config('workorder.customer_category_time');
        if (!empty($result)) {
            if (1 == $type) {
                foreach ($result as $k => $v) {
                    if (($v + $time_out) < strtotime("now")) {
                        $info[] = $k;
                    }
                }
            } elseif (2 == $type) {
                foreach ($result as $k => $v) {
                    if (($v + $time_out) >= strtotime("now")) {
                        $info[] = $k;
                    }
                }
            } else { //全部员工
                foreach ($result as $k => $v) {
                    $info[] = $k;
                }
            }
        }
        return $info ? $info : [];
    }
    /**
     * 获取客服是 电话组或者邮件组
     *
     * @Description
     * @author lsw
     * @since 2020/06/08 13:55:44 
     * @return void
     */
    public function getCustomerType()
    {
        $info =  ZendeskAgents::field('admin_id,agent_type')->select();
        if (!$info) {
            return false;
        }
        $arr = [];
        foreach ($info as $v) {
            switch ($v['agent_type']) {
                case 1:
                    $agent_value = '邮件组';
                    break;
                case 2:
                    $agent_value = '电话组';
                    break;
                default:
                    $agent_value = '未知';
                    break;
            }
            $arr[$v['admin_id']] = $agent_value;
        }
        return $arr ?: [];
    }
    /**
     * 获取客服的邮件组还是电话组或者全部
     *
     * @Description
     * @author lsw
     * @since 2020/06/08 15:23:40 
     * @return void
     */
    public function getAllCustomerType($type)
    {
        if (0 != $type) {
            $where['agent_type'] = $type;
        }
        return  ZendeskAgents::where($where)->column('admin_id,agent_type');
    }
}
