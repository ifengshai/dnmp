<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;

class ItDemandReport extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItWebDemand;
        $this->itWebTaskItem = new \app\admin\model\demand\ItWebTaskItem;
        $this->itWebTask = new \app\admin\model\demand\ItWebTask;
    }



    /**
     * 统计列表
     *
     * @Description
     * @author Lx
     * @since 2020/05/23 15:53:16 
     */
    public function statistical(){
        $month_first = date("Y-m-01",time());//本月第一天
        $month_last = date('Y-m-d', strtotime("$month_first +1 month -1 day"));//本月最后一天

        $month_01 = date('Y-m', strtotime('-1 month'));//上月
        $month_first_01 = date('Y-m-01', strtotime('-1 month'));//上月第一天
        $month_last_01 = date('Y-m-t', strtotime('-1 month'));//上月最后一天

        $month_02 = date('Y-m', strtotime('-2 month'));
        $month_first_02 = date('Y-m-01', strtotime('-2 month'));//第一天
        $month_last_02 = date('Y-m-t', strtotime('-2 month'));//最后一天

        $month_03 = date('Y-m', strtotime('-3 month'));
        $month_first_03 = date('Y-m-01', strtotime('-3 month'));//第一天
        $month_last_03 = date('Y-m-t', strtotime('-3 month'));//最后一天

        $month_04 = date('Y-m', strtotime('-4 month'));
        $month_first_04 = date('Y-m-01', strtotime('-4 month'));//第一天
        $month_last_04 = date('Y-m-t', strtotime('-4 month'));//最后一天

        $month_05 = date('Y-m', strtotime('-5 month'));
        $month_first_05 = date('Y-m-01', strtotime('-5 month'));//第一天
        $month_last_05 = date('Y-m-t', strtotime('-5 month'));//最后一天
        

        
       

        

        if ($this->request->isAjax()) {
            
            //网站组--目标(短期任务：10个,中期任务：20个,长期任务：30个)--start
            $task_month = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first, $month_last])->sum('type')*10;//本月
            $task_month_01 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->sum('type')*10;
            $task_month_02 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->sum('type')*10;
            $task_month_03 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_03, $month_last_03])->sum('type')*10;
            $task_month_04 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_04, $month_last_04])->sum('type')*10;
            $task_month_05 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_05, $month_last_05])->sum('type')*10;
            //网站组--目标--end

            //网站组--BUG(普通：1个,小概率：2个)--start
            $bug0_month = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 0)->whereTime('create_time', 'between', [$month_first, $month_last])->count();
            $bug1_month = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 1)->whereTime('create_time', 'between', [$month_first, $month_last])->count()*2;
            $bug_month = $bug0_month+$bug1_month;
    
            $bug0_month_01 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 0)->whereTime('create_time', 'between', [$month_first_01, $month_last_01])->count();
            $bug1_month_01 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 1)->whereTime('create_time', 'between', [$month_first_01, $month_last_01])->count()*2;
            $bug_month_01 = $bug0_month_01+$bug1_month_01;
    
            $bug0_month_02 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 0)->whereTime('create_time', 'between', [$month_first_02, $month_last_02])->count();
            $bug1_month_02 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 1)->whereTime('create_time', 'between', [$month_first_02, $month_last_02])->count()*2;
            $bug_month_02 = $bug0_month_02+$bug1_month_02;
    
            $bug0_month_03 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 0)->whereTime('create_time', 'between', [$month_first_03, $month_last_03])->count();
            $bug1_month_03 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 1)->whereTime('create_time', 'between', [$month_first_03, $month_last_03])->count()*2;
            $bug_month_03 = $bug0_month_03+$bug1_month_03;
    
            $bug0_month_04 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 0)->whereTime('create_time', 'between', [$month_first_04, $month_last_04])->count();
            $bug1_month_04 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 1)->whereTime('create_time', 'between', [$month_first_04, $month_last_04])->count()*2;
            $bug_month_04 = $bug0_month_04+$bug1_month_04;
    
            $bug0_month_05 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 0)->whereTime('create_time', 'between', [$month_first_05, $month_last_05])->count();
            $bug1_month_05 = $this->model->where('is_del', 1)->where('type', 1)->where('is_small_probability', 1)->whereTime('create_time', 'between', [$month_first_05, $month_last_05])->count()*2;
            $bug_month_05 = $bug0_month_05+$bug1_month_05;
            //网站组--BUG--end

            //网站组--需求(普通：1个,中等：3个,复杂：5个)--start
            $demand1_month = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 1)->whereTime('create_time', 'between', [$month_first, $month_last])->count();
            $demand2_month = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 2)->whereTime('create_time', 'between', [$month_first, $month_last])->count()*3;
            $demand3_month = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 3)->whereTime('create_time', 'between', [$month_first, $month_last])->count()*5;
            $demand_month = $demand1_month+$demand2_month+$demand3_month;
    
            $demand1_month_01 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 1)->whereTime('create_time', 'between', [$month_first_01, $month_last_01])->count();
            $demand2_month_01 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 2)->whereTime('create_time', 'between', [$month_first_01, $month_last_01])->count()*3;
            $demand3_month_01 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 3)->whereTime('create_time', 'between', [$month_first_01, $month_last_01])->count()*5;
            $demand_month_01 = $demand1_month_01+$demand2_month_01+$demand3_month_01;
    
            $demand1_month_02 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 1)->whereTime('create_time', 'between', [$month_first_02, $month_last_02])->count();
            $demand2_month_02 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 2)->whereTime('create_time', 'between', [$month_first_02, $month_last_02])->count()*3;
            $demand3_month_02 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 3)->whereTime('create_time', 'between', [$month_first_02, $month_last_02])->count()*5;
            $demand_month_02 = $demand1_month_02+$demand2_month_02+$demand3_month_02;
    
            $demand1_month_03 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 1)->whereTime('create_time', 'between', [$month_first_03, $month_last_03])->count();
            $demand2_month_03 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 2)->whereTime('create_time', 'between', [$month_first_03, $month_last_03])->count()*3;
            $demand3_month_03 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 3)->whereTime('create_time', 'between', [$month_first_03, $month_last_03])->count()*5;
            $demand_month_03 = $demand1_month_03+$demand2_month_03+$demand3_month_03;
    
            $demand1_month_04 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 1)->whereTime('create_time', 'between', [$month_first_04, $month_last_04])->count();
            $demand2_month_04 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 2)->whereTime('create_time', 'between', [$month_first_04, $month_last_04])->count()*3;
            $demand3_month_04 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 3)->whereTime('create_time', 'between', [$month_first_04, $month_last_04])->count()*5;
            $demand_month_04 = $demand1_month_04+$demand2_month_04+$demand3_month_04;
    
            $demand1_month_05 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 1)->whereTime('create_time', 'between', [$month_first_05, $month_last_05])->count();
            $demand2_month_05 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 2)->whereTime('create_time', 'between', [$month_first_05, $month_last_05])->count()*3;
            $demand3_month_05 = $this->model->where('is_del', 1)->where('type', 2)->where('all_complexity', 3)->whereTime('create_time', 'between', [$month_first_05, $month_last_05])->count()*5;
            $demand_month_05 = $demand1_month_05+$demand2_month_05+$demand3_month_05;
            //网站组--需求--end

            //合计--start
            $all = $task_month+$bug_month+$demand_month;
            $all_01 = $task_month_01+$bug_month_01+$demand_month_01;
            $all_02 = $task_month_02+$bug_month_02+$demand_month_02;
            $all_03 = $task_month_03+$bug_month_03+$demand_month_03;
            $all_04 = $task_month_04+$bug_month_04+$demand_month_04;
            $all_05 = $task_month_05+$bug_month_05+$demand_month_05;
            //合计--end

            $json['columnData'] = [
                [
                    'name'=> '开发',
                    'type'=>'bar',
                    'data'=> [$task_month_05, $task_month_04, $task_month_03, $task_month_02, $task_month_01, $task_month]
                ],
                [
                    'name'=> 'BUG',
                    'type'=>'bar',
                    'data'=>  [$bug_month_05, $bug_month_04, $bug_month_03, $bug_month_02, $bug_month_01, $bug_month]
                ],
                [
                    'name'=> '需求',
                    'type'=>'bar',
                    'data'=> [$demand_month_05, $demand_month_04, $demand_month_03, $demand_month_02, $demand_month_01, $demand_month]
                ],
                [
                    'name'=> '合计',
                    'type'=>'bar',
                    'data'=> [$all_05, $all_04, $all_03, $all_02, $all_01, $all]
                ],
 
            ];

            $json['xColumnName'] = [$month_05, $month_04, $month_03, $month_02, $month_01, '本月'];
            //$json['column'] = ['直接访问', '邮件营销', '联盟广告', '视频广告', '搜索引擎', '百度', '谷歌', '必应', '其他'];
            return json(['code' => 1, 'data' => $json]);
            
        }

        return $this->view->fetch();
    }
    /**
     * 网站统计任务量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 15:11:21 
     * @return void
     */
    public function web_score_statistics(){
        $month = input('month');
        if($month == 0){
            $stime = date("Y-m-01",time());//本月第一天
            $etime = date('Y-m-d', strtotime("$stime +1 month -1 day"));//本月最后一天
        }else{
            $stime = date('Y-m-01', strtotime('-'.$month.' month'));//上月第一天
            $etime = date('Y-m-t', strtotime('-'.$month.' month'));//上月最后一天
        }
        $smap['create_time'] = ['between', [$stime, $etime]];
        $task_item_smap['itWebTask.createtime'] = ['between', [$stime, $etime]];
        $task_smap['createtime'] = ['between', [$stime, $etime]];

        //统计个人 需求数量，bug数量，开发任务数量，疑难数量，总数量
        //遍历每个人获取相应数据
        $web_designer_user_arr = config('demand.' . 'web_designer_user');
        $phper_user_arr = config('demand.' . 'phper_user');
        $app_user_arr = config('demand.' . 'app_user');
        $test_user_arr = config('demand.' . 'test_user');

        $list = $this->model
            ->where('is_del', 1)
            ->where($smap)
            ->select();

        $list = collection($list)->toArray();//获取一个月的需求与bug

        $task_item_list = $this->itWebTaskItem
            ->with("itWebTask")
            ->where('is_del', 1)
            ->where($task_item_smap)
            ->select();

        $task_item_list = collection($task_item_list)->toArray();//获取一个月的开发任务

        $web_user_total = $this->PersonalJobNum($web_designer_user_arr,'web_designer_user_id','前端',$list,$task_item_list);
        $php_user_total = $this->PersonalJobNum($phper_user_arr,'phper_user_id','后端',$list,$task_item_list);
        $app_user_total = $this->PersonalJobNum($app_user_arr,'app_user_id','APP',$list,$task_item_list);
        $test_user_total =$this->PersonalJobNum($test_user_arr,'test_user_id','测试',$list,$task_item_list);
        $personal_total=array_merge($web_user_total,$php_user_total,$app_user_total,$test_user_total);
        dump($personal_total);exit;
    }
    /**
     * 网站统计逾期任务量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 17:55:38 
     * @return void
     */
    public function web_outtime_statistics(){
        $month = input('month');
        if($month == 0){
            $stime = date("Y-m-01",time());//本月第一天
            $etime = date('Y-m-d', strtotime("$stime +1 month -1 day"));//本月最后一天
        }else{
            $stime = date('Y-m-01', strtotime('-'.$month.' month'));//上月第一天
            $etime = date('Y-m-t', strtotime('-'.$month.' month'));//上月最后一天
        }
        $smap['create_time'] = ['between', [$stime, $etime]];
        $task_item_smap['itWebTask.createtime'] = ['between', [$stime, $etime]];
        $task_smap['createtime'] = ['between', [$stime, $etime]];

        //统计个人 需求数量，bug数量，开发任务数量，疑难数量，总数量
        //遍历每个人获取相应数据
        $web_designer_user_arr = config('demand.' . 'web_designer_user');
        $phper_user_arr = config('demand.' . 'phper_user');
        $app_user_arr = config('demand.' . 'app_user');

        $list = $this->model
            ->where('is_del', 1)
            ->where($smap)
            ->select();

        $list = collection($list)->toArray();//获取一个月的需求与bug

        $task_item_list = $this->itWebTaskItem
            ->with("itWebTask")
            ->where('is_del', 1)
            ->where($task_item_smap)
            ->select();

        $task_item_list = collection($task_item_list)->toArray();//获取一个月的开发任务

        $web_user_total = $this->PersonalOuttimeNum($web_designer_user_arr,'web_designer_user_id','前端',$list,$task_item_list);
        $php_user_total = $this->PersonalOuttimeNum($phper_user_arr,'phper_user_id','后端',$list,$task_item_list);
        $app_user_total = $this->PersonalOuttimeNum($app_user_arr,'app_user_id','APP',$list,$task_item_list);
        $personal_total=array_merge($web_user_total,$php_user_total,$app_user_total);
        dump($personal_total);exit;
    }
    /**
     * 获取复杂度/时间
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 16:07:57 
     * @return void
     */
    public function getcomplexity($usertype){
        $arr = array();
        switch ($usertype)
        {
            case 'web_designer_user_id':
                $arr['complexity'] = 'web_designer_complexity' ;
                $arr['completetime'] = 'web_designer_finish_time' ;
                $arr['experttetime'] = 'web_designer_expect_time' ;
                break;  
            case 'phper_user_id':
                $arr['complexity'] = 'phper_complexity' ;
                $arr['completetime'] = 'phper_finish_time' ;
                $arr['experttetime'] = 'phper_expect_time' ;
                break;
            case 'app_user_id':
                $arr['complexity'] = 'app_complexity' ;
                $arr['completetime'] = 'app_finish_time' ;
                $arr['experttetime'] = 'app_expect_time' ;
                break;
            case 'test_user_id':
                $arr['complexity'] = 'test_complexity' ;
                $arr['completetime'] = 'test_finish_time' ;
                break;
        }
        return $arr;
    }
    /**
     * 获取每个人的工作量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 15:40:22 
     * @return void
     */
    public function PersonalJobNum($user_arr=[],$field_name='',$group_name='',$demand_list=[],$item_list=[]){
        $web_user = array();
        $i = 0;
        foreach ($user_arr as $uk => $uv) {
            if ($uk) {
                $bug_num = 0;      //bug总数量
                $bug_total = 0;      //bug总量
                $demand_num = 0;   //需求总数量
                $demand_total = 0;   //需求总量
                $task_num = 0;     //开发总数量
                $task_total = 0;     //开发总量
                $score_num = 0;    //总数量
                $score_total = 0;    //总量
                $web_user[$i]['user_name'] = $uv;
                $web_user[$i]['user_id'] = $uk;
                $web_user[$i]['group_name'] = $group_name;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        if ($v['type'] == 1) {
                            $bug_num++;
                            $bug_total++;
                            $score_num++;
                            $score_total++;
                        } elseif ($v['type'] == 2) {
                            $complexity = $this->getcomplexity($field_name)['complexity'];
                            if($v[$complexity] == 1){
                                $demand_num++;
                                $demand_total++;
                                $score_num++;
                                $score_total++;
                            }elseif($v[$complexity] == 2){
                                $demand_num++;
                                $demand_total += 3;
                                $score_num++;
                                $score_total += 3;
                            }
                            elseif($v[$complexity] == 3){
                                $demand_num++;
                                $demand_total += 5;
                                $score_num++;
                                $score_total += 5;
                            }   
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        if($v['it_web_task']['type'] == 1){
                            $task_num++;
                            $task_total += 10;
                            $score_num++;
                            $score_total += 10;
                        }elseif($v['it_web_task']['type'] == 2){
                            $task_num++;
                            $task_total += 20;
                            $score_num++;
                            $score_total += 20;
                        }else{
                            $task_num++;
                            $task_total += 30;
                            $score_num++;
                            $score_total += 30;
                        }
                    }
                    
                }
                $web_user[$i]['bug_num'] = $bug_num;
                $web_user[$i]['bug_total'] = $bug_total;
                $web_user[$i]['demand_num'] = $demand_num;
                $web_user[$i]['demand_total'] = $demand_total;
                $web_user[$i]['task_num'] = $task_num;
                $web_user[$i]['task_total'] = $task_total;
                $web_user[$i]['score_num'] = $score_num;
                $web_user[$i]['score_total'] = $score_total;
                $i++;
            }
        }
        return $web_user;
    }
     /**
     * 获取每个人逾期的工作量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 15:40:22 
     * @return void
     */
    public function PersonalOuttimeNum($user_arr=[],$field_name='',$group_name='',$demand_list=[],$item_list=[]){
        $web_user = array();
        $i = 0;
        foreach ($user_arr as $uk => $uv) {
            if ($uk) {
                $bug_num = 0;      //bug总数量
                $bug_total = 0;      //bug逾期总量
                $demand_num = 0;   //需求总数量
                $demand_total = 0;   //需求逾期总量
                $task_num = 0;     //开发总数量
                $task_total = 0;     //开发逾期总量
                $score_num = 0;    //总数量
                $score_total = 0;    //总逾期量
                $web_user[$i]['user_name'] = $uv;
                $web_user[$i]['user_id'] = $uk;
                $web_user[$i]['group_name'] = $group_name;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        $arr = $this->getcomplexity($field_name);
                        if ($v['type'] == 1) {
                            $bug_date = $v['create_time'];
                            $createtime = date('Y-m-d H:i:s',strtotime("$bug_date+1day"));
                            if($createtime > $arr['completetime']){
                                $bug_num++;
                                $score_total++;
                            }
                            $bug_total++;
                            $score_num++;
                        } elseif ($v['type'] == 2) {
                            $complexity = $arr['complexity'];
                            if($v[$complexity] == 1){
                                $demand_num++;
                                $demand_total++;
                                $score_num++;
                                $score_total++;
                            }elseif($v[$complexity] == 2){
                                $demand_num++;
                                $demand_total += 3;
                                $score_num++;
                                $score_total += 3;
                            }
                            elseif($v[$complexity] == 3){
                                $demand_num++;
                                $demand_total += 5;
                                $score_num++;
                                $score_total += 5;
                            }   
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        if($v['it_web_task']['type'] == 1){
                            $task_num++;
                            $task_total += 10;
                            $score_num++;
                            $score_total += 10;
                        }elseif($v['it_web_task']['type'] == 2){
                            $task_num++;
                            $task_total += 20;
                            $score_num++;
                            $score_total += 20;
                        }else{
                            $task_num++;
                            $task_total += 30;
                            $score_num++;
                            $score_total += 30;
                        }
                    }
                    
                }
                $web_user[$i]['bug_num'] = $bug_num;
                $web_user[$i]['bug_total'] = $bug_total;
                $web_user[$i]['demand_num'] = $demand_num;
                $web_user[$i]['demand_total'] = $demand_total;
                $web_user[$i]['task_num'] = $task_num;
                $web_user[$i]['task_total'] = $task_total;
                $web_user[$i]['score_num'] = $score_num;
                $web_user[$i]['score_total'] = $score_total;
                $i++;
            }
        }
        return $web_user;
    }
    /*
     * 取出配置文件的数据，
     * $user_id string 数据格式以逗号分隔
     * $config_name string 配置名称
     * */
    public function extract_username($user_id, $config_name)
    {
        $user_id_arr = explode(',', $user_id);
        $user_name_arr = array();
        foreach ($user_id_arr as $v) {
            $user_name_arr[] = config('demand.' . $config_name)[$v];
        }
        $user_name = implode(',', $user_name_arr);
        return $user_name;
    }


    //  二维数组排序
    public function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }



    public function statisticsPersonalNum($user_arr=[],$field_name='',$group_name='',$demand_list=[],$item_list=[]){
        $web_user_total=array();
        foreach ($user_arr as $uk => $uv) {
            if ($uk) {
                $web_user = array();
                $web_user['user_name'] = $uv;
                $web_user['user_id'] = $uk;
                $web_user['group_name'] = $group_name;
                $web_user['bug_num'] = 0;
                $web_user['demand_num'] = 0;
                $web_user['difficult_num'] = 0;
                $web_user['task_num'] = 0;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        if ($v['type'] == 1) {
                            $web_user['bug_num']+= 1;
                        } elseif ($v['type'] == 2) {
                            $web_user['demand_num'] += 1;
                        } elseif ($v['type'] == 3) {
                            $web_user['difficult_num'] += 1;
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        $web_user['task_num'] += 1;
                    }
                }
                $web_user['total'] = $web_user['bug_num'] + $web_user['demand_num'] + $web_user['difficult_num'] + $web_user['task_num'];
                array_push($web_user_total, $web_user);
            }
        }
        if ($web_user_total){
            return $this->arraySequence($web_user_total, 'total');
        }
        return $web_user_total;
    }

    /**
     * 技术部网站需求列表
     */
    public function index()
    {
        $stime = date("Y-m-d 00:00:00", strtotime("-3 month"));
        $etime = date('Y-m-d H:i:s', time());
        $smap['create_time'] = ['between', [$stime, $etime]];
        $task_item_smap['itWebTask.createtime'] = ['between', [$stime, $etime]];
        $task_smap['createtime'] = ['between', [$stime, $etime]];

        //统计个人 需求数量，bug数量，开发任务数量，疑难数量，总数量
        //遍历每个人获取相应数据
        $web_designer_user_arr = config('demand.' . 'web_designer_user');
        $phper_user_arr = config('demand.' . 'phper_user');
        $app_user_arr = config('demand.' . 'app_user');
        $test_user_arr = config('demand.' . 'test_user');

        $list = $this->model
            ->where('is_del', 1)
            ->where($smap)
            ->select();

        $list = collection($list)->toArray();//获取近三个月的需求与bug


        $task_item_list = $this->itWebTaskItem
            ->with("itWebTask")
            ->where('is_del', 1)
            ->where($task_item_smap)
            ->select();

        $task_item_list = collection($task_item_list)->toArray();//获取近三个月的开发任务

        $web_user_total = $this->statisticsPersonalNum($web_designer_user_arr,'web_designer_user_id','前端',$list,$task_item_list);
        $php_user_total = $this->statisticsPersonalNum($phper_user_arr,'phper_user_id','后端',$list,$task_item_list);
        $app_user_total = $this->statisticsPersonalNum($app_user_arr,'app_user_id','APP',$list,$task_item_list);
        $test_user_total =$this->statisticsPersonalNum($test_user_arr,'test_user_id','测试',$list,$task_item_list);
        $personal_total=array_merge($web_user_total,$php_user_total,$app_user_total,$test_user_total);


        //获取站点任务数据
        $task_web=new \app\admin\model\demand\ItWebTask();

       $it_task_list= $task_web
            ->where($task_smap)
            ->where('is_del', 1)
            ->select();
        //站点数据获取
        $site_type_total=array();

        $site_type_arr = config('demand.' . 'siteType');
        foreach ($site_type_arr as $uk => $uv) {
            if ($uk) {
                $site_type = array();
                $site_type['user_name'] = $uv;
                $site_type['bug_num'] = 0;
                $site_type['demand_num'] = 0;
                $site_type['difficult_num'] = 0;
                $site_type['task_num'] = 0;
                foreach ($list as $k => $v) {
                    if ($v['site_type']==$uk) {
                        if ($v['type'] == 1) {
                            $site_type['bug_num'] += 1;
                        } elseif ($v['type'] == 2) {
                            $site_type['demand_num'] += 1;
                        } elseif ($v['type'] == 3) {
                            $site_type['difficult_num'] += 1;
                        }
                    }
                }
                foreach ($it_task_list as $k => $v) {
                    if ($v['site_type'] == $uk) {
                        $site_type['task_num']  += 1;
                    }
                }
                $site_type['total'] = $site_type['bug_num'] + $site_type['demand_num'] + $site_type['difficult_num'] + $site_type['task_num'];
                array_push($site_type_total, $site_type);
            }
        }

        if ($web_user_total){
            $site_type_total= $this->arraySequence($site_type_total, 'total');
        }
        //

        $this->view->assign('personal_total', $personal_total);
        $this->view->assign('site_type_total', $site_type_total);
        return $this->view->fetch();
    }


    /**
     * 需求与bug列表
     */

    public function demand_list()
    {
        //dump(input());exit;
        //设置过滤方法
        $stime = date("Y-m-d 00:00:00", strtotime("-7 day"));
        $etime = date('Y-m-d H:i:s', time());
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            $smap = array();
            if ($filter['Allgroup_sel'] == 1) {
                $smap['web_designer_group'] = 1;
            }
            if ($filter['Allgroup_sel'] == 2) {
                $smap['phper_group'] = 1;
            }
            if ($filter['Allgroup_sel'] == 3) {
                $smap['app_group'] = 1;
            }
            if ($filter['Allgroup_sel'] == 4) {
                $smap['test_group'] = 1;
            }
            if ($smap) {
                unset($filter['Allgroup_sel']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            $status_stint = [1, 2, 3, 4, 5, 6];


            $smap['create_time'] = ['between', [$stime, $etime]];

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($smap)
                ->where('is_del', 1)
                ->where('status', 'in', $status_stint)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($smap)
                ->where('is_del', 1)
                ->where('status', 'in', $status_stint)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人

                $list[$k]['allcomplexity'] = config('demand.allComplexity')[$v['all_complexity']];//复杂度
                $list[$k]['hope_time'] = date('m-d H:i', strtotime($v['hope_time']));//预计时间

                /*分配*/
                $list[$k]['Allgroup'] = array();
                if ($v['web_designer_group'] == 1) {
                    $list[$k]['Allgroup'][] = '前端';
                    $list[$k]['web_designer_user_name'] = $this->extract_username($v['web_designer_user_id'], 'web_designer_user');
                    $list[$k]['web_designer_expect_time'] = date('m-d H:i', strtotime($v['web_designer_expect_time']));
                    if ($v['web_designer_is_finish'] == 1) {
                        $list[$k]['web_designer_finish_time'] = date('m-d H:i', strtotime($v['web_designer_finish_time']));
                    }
                }
                if ($v['phper_group'] == 1) {
                    $list[$k]['Allgroup'][] = '后端';
                    $list[$k]['phper_user_name'] = $this->extract_username($v['phper_user_id'], 'phper_user');
                    $list[$k]['phper_expect_time'] = date('m-d H:i', strtotime($v['phper_expect_time']));
                    if ($v['phper_is_finish'] == 1) {
                        $list[$k]['phper_finish_time'] = date('m-d H:i', strtotime($v['phper_finish_time']));
                    }
                }
                if ($v['app_group'] == 1) {
                    $list[$k]['Allgroup'][] = 'APP';
                    $list[$k]['app_user_name'] = $this->extract_username($v['app_user_id'], 'app_user');
                    $list[$k]['app_expect_time'] = date('m-d H:i', strtotime($v['app_expect_time']));
                    if ($v['app_is_finish'] == 1) {
                        $list[$k]['app_finish_time'] = date('m-d H:i', strtotime($v['app_finish_time']));
                    }
                }
                if ($v['test_group'] == 1) {
                    foreach (explode(',', $v['test_user_id']) as $t) {
                        $list[$k]['test_user_id_arr'][] = config('demand.test_user')[$t];
                    }
                }
                /*分配*/

                /*当前状态*/
                if ($v['status'] == 1) {
                    $list[$k]['status_str'] = 'New';
                } elseif ($v['status'] == 2) {
                    $list[$k]['status_str'] = '待通过';
                } elseif ($v['status'] == 3) {
                    if ($v['web_designer_group'] == 0 && $v['phper_group'] == 0 && $v['app_group'] == 0) {
                        $list[$k]['status_str'] = '待分配';
                    } else {
                        $list[$k]['status_str'] = '开发ing';
                    }
                } elseif ($v['status'] == 4) {
                    if ($v['test_group'] == 1) {
                        if ($v['entry_user_confirm'] == 0) {
                            $list[$k]['status_str'] = '待测试,待确认';
                        } else {
                            $list[$k]['status_str'] = '待测试,已确认';
                        }
                    } else {
                        $list[$k]['status_str'] = '待上线';
                    }

                } elseif ($v['status'] == 5) {
                    if ($v['test_group'] == 1) {
                        if ($v['entry_user_confirm'] == 0) {
                            $list[$k]['status_str'] = '待确认';
                        } else {
                            $list[$k]['status_str'] = '待上线';
                        }
                    } else {
                        $list[$k]['status_str'] = '待上线';
                    }
                } elseif ($v['status'] == 6) {

                    $list[$k]['status_str'] = '待回归测试';
                } elseif ($v['status'] == 7) {

                    $list[$k]['status_str'] = '已完成';
                }

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();

    }


    /**
     * 七日未完成开发任务列表
     */

    public function undone_task()
    { //dump(input());exit;
        //设置过滤方法
        $stime = date("Y-m-d 00:00:00", strtotime("-7 day"));
        $etime = date('Y-m-d H:i:s', time());
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\demand\ItWebTask();
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //自定义姓名搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['nickname']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . $filter['nickname'] . '%'];
                $id = $admin->where($smap)->value('id');
                $task_ids = $this->itWebTaskItem->where('person_in_charge', $id)->column('task_id');
                $map['id'] = ['in', $task_ids];
                unset($filter['nickname']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams(null);
            $between_map['createtime'] = ['between', [$stime, $etime]];

            $total = $this->model
                ->where($where)
                ->where($between_map)
                ->where('is_complete', '=', 0)//未完成的任务
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($between_map)
                ->where('is_complete', '=', 0)//未完成的任务
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);

        }
        return $this->view->fetch();
    }


    /*
    public function undone_task()
    {
        //设置过滤方法
        $stime = date("Y-m-d 00:00:00", strtotime("-7 day"));
        $etime = date('Y-m-d 00:00:00', strtotime("+1 day"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\demand\ItWebTask;
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //自定义姓名搜索
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $between_map['it_web_task.createtime'] = ['between', [$stime, $etime]];
            $addWhere="it_web_task.is_complete= '0' OR  itwebtaskitem.is_complete = '0' ";

            $rep    = $this->request->get('filter');
            $where.='1=1 ';
            if($rep != '{}'){
                $whereArr = json_decode($rep,true);
                if(!empty($whereArr)){
                    foreach($whereArr as $key => $whereval){
                        $where .=" AND  it_web_task.".$key." = '".$whereval."' ";
                    }
                }
            }

            $total = $this->model->alias('it_web_task')->join('fa_it_web_task_item itwebtaskitem','it_web_task.id=itwebtaskitem.task_id','left')
                ->where($where)
                ->where($addWhere)
                ->where($between_map)
                ->distinct('it_web_task.id')
                ->order('it_web_task.id', $order)
                ->limit($offset, $limit)
                ->field('it_web_task.id,it_web_task.type,it_web_task.title,it_web_task.desc,it_web_task.closing_date,it_web_task.is_test_adopt,it_web_task.complete_date,it_web_task.is_complete,it_web_task.test_adopt_time,it_web_task.create_person,it_web_task.createtime,it_web_task.site_type,it_web_task.test_regression_adopt,it_web_task.test_regression_adopt_time,it_web_task.test_regression_person')
                ->count();


            $list = $this->model->alias('it_web_task')->join('fa_it_web_task_item itwebtaskitem','it_web_task.id=itwebtaskitem.task_id','left')
                ->where($where)
                ->where($addWhere)
                ->where($between_map)
                ->distinct('it_web_task.id')
                ->order('it_web_task.id', $order)
                ->limit($offset, $limit)
                ->field('it_web_task.id,it_web_task.type,it_web_task.title,it_web_task.desc,it_web_task.closing_date,it_web_task.is_test_adopt,it_web_task.complete_date,it_web_task.is_complete,it_web_task.test_adopt_time,it_web_task.create_person,it_web_task.createtime,it_web_task.site_type,it_web_task.test_regression_adopt,it_web_task.test_regression_adopt_time,it_web_task.test_regression_person')
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);

        }
        return $this->view->fetch();
    }

    */

}