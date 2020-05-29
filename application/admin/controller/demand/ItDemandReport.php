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

        $this->developmodel = new \app\admin\model\demand\DevelopDemand;
        $this->developWebTaskItem = new \app\admin\model\demand\DevelopWebTaskItem;
        $this->developWebTask = new \app\admin\model\demand\DevelopWebTask;
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

        if ($this->request->isAjax()) {
            $web_type = input('web_type');
            if($web_type == 'web'){
                //网站组--目标(短期任务：10个,中期任务：20个,长期任务：30个)--start
                $task_month = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first, $month_last])->sum('type')*10;//本月
                $task_month_01 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->sum('type')*10;
                $task_month_02 = $this->itWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->sum('type')*10;
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
                //网站组--需求--end

                //合计--start
                $all = $task_month+$bug_month+$demand_month;
                $all_01 = $task_month_01+$bug_month_01+$demand_month_01;
                $all_02 = $task_month_02+$bug_month_02+$demand_month_02;
                //合计--end
            }else{
                //开发组--目标(短期任务：10个,中期任务：20个,长期任务：30个)--start
                $task_month = $this->developWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first, $month_last])->sum('type')*10;//本月
                $task_month_01 = $this->developWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->sum('type')*10;
                $task_month_02 = $this->developWebTask->where('is_del', 1)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->sum('type')*10;
                //开发组--目标--end

                //开发组--BUG(普通：1个)--start
                $bug_month = $this->developmodel->where('is_del', 1)->where('type', 1)->whereTime('createtime', 'between', [$month_first, $month_last])->count();
                $bug_month_01 = $this->developmodel->where('is_del', 1)->where('type', 1)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->count();
                $bug_month_02 = $this->developmodel->where('is_del', 1)->where('type', 1)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->count();
                //开发组--BUG--end
                
                //开发组--需求(普通：1个,中等：3个,复杂：5个)--start
                $demand1_month = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 1)->whereTime('createtime', 'between', [$month_first, $month_last])->count();
                $demand2_month = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 2)->whereTime('createtime', 'between', [$month_first, $month_last])->count()*3;
                $demand3_month = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 3)->whereTime('createtime', 'between', [$month_first, $month_last])->count()*5;
                $demand_month = $demand1_month+$demand2_month+$demand3_month;

                $demand1_month_01 = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 1)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->count();
                $demand2_month_01 = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 2)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->count()*3;
                $demand3_month_01 = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 3)->whereTime('createtime', 'between', [$month_first_01, $month_last_01])->count()*5;
                $demand_month_01 = $demand1_month_01+$demand2_month_01+$demand3_month_01;

                $demand1_month_02 = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 1)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->count();
                $demand2_month_02 = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 2)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->count()*3;
                $demand3_month_02 = $this->developmodel->where('is_del', 1)->where('type', 2)->where('complexity', 3)->whereTime('createtime', 'between', [$month_first_02, $month_last_02])->count()*5;
                $demand_month_02 = $demand1_month_02+$demand2_month_02+$demand3_month_02;
                //开发组--需求--end

                //合计--start
                $all = $task_month+$bug_month+$demand_month;
                $all_01 = $task_month_01+$bug_month_01+$demand_month_01;
                $all_02 = $task_month_02+$bug_month_02+$demand_month_02;
                //合计--end
            }
            $json['columnData'] = [
                [
                    'name'=> '开发',
                    'type'=>'bar',
                    'data'=> [$task_month_02, $task_month_01, $task_month]
                ],
                [
                    'name'=> 'BUG',
                    'type'=>'bar',
                    'data'=>  [$bug_month_02, $bug_month_01, $bug_month]
                ],
                [
                    'name'=> '需求',
                    'type'=>'bar',
                    'data'=> [$demand_month_02, $demand_month_01, $demand_month]
                ],
                [
                    'name'=> '合计',
                    'type'=>'bar',
                    'data'=> [$all_02, $all_01, $all]
                ],
 
            ];

            $json['xColumnName'] = [$month_02, $month_01, '本月'];
            //$json['column'] = ['直接访问', '邮件营销', '联盟广告', '视频广告', '搜索引擎', '百度', '谷歌', '必应', '其他'];
            return json(['code' => 1, 'data' => $json]);
        }
        $date_arr = array(
            date("Y-m",time()),
            date('Y-m', strtotime('-1 month')),
            date('Y-m', strtotime('-2 month')),
        );
        $month = input('month') ? input('month') : 0;
        $type = input('type') ? input('type') : 'web';
        if($type == 'web'){
            $web_score_statistics = $this->web_score_statistics($month);
            $web_outtime_statistics = $this->web_outtime_statistics($month);
            //统计站点任务量
                //bug统计
                $zeelool0_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=1 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $zeelool1_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=1 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $zeelool2_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=1 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $voogueme0_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=2 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $voogueme1_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=2 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $voogueme2_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=2 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $nihao0_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $nihao1_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $nihao2_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $wesee0_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $wesee1_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $wesee2_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $others0_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=4 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $others1_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=4 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $others2_bug = $this->model->query("select sum(if(is_small_probability=0,1,2)) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=4 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];
                
                //需求统计
                $zeelool0_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=1 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $zeelool1_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=1 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $zeelool2_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=1 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $voogueme0_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=2 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $voogueme1_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=2 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $voogueme2_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=2 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $nihao0_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $nihao1_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $nihao2_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=3 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $wesee0_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=4 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $wesee1_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=4 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $wesee2_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=4 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $others0_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=5 and create_time between '".$month_first."' and '".$month_last."'")[0]['count'];
                $others1_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=5 and create_time between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $others2_demand = $this->model->query("select sum(case all_complexity when 1 then 1 when 2 then 3 else 5 end) as count from fa_it_web_demand where is_del=1 and type=1 and site_type=5 and create_time between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                //开发任务统计
                $zeelool0_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=1 and createtime between '".$month_first."' and '".$month_last."'")[0]['count'];
                $zeelool1_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=1 and createtime between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $zeelool2_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=1 and createtime between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $voogueme0_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=2 and createtime between '".$month_first."' and '".$month_last."'")[0]['count'];
                $voogueme1_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=2 and createtime between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $voogueme2_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=2 and createtime between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $nihao0_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=3 and createtime between '".$month_first."' and '".$month_last."'")[0]['count'];
                $nihao1_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=3 and createtime between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $nihao2_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=3 and createtime between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $wesee0_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=4 and createtime between '".$month_first."' and '".$month_last."'")[0]['count'];
                $wesee1_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=4 and createtime between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $wesee2_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=4 and createtime between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];

                $others0_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=5 and createtime between '".$month_first."' and '".$month_last."'")[0]['count'];
                $others1_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=5 and createtime between '".$month_first_01."' and '".$month_last_01."'")[0]['count'];
                $others2_task = $this->developmodel->query("select sum(case type when 1 then 10 when 2 then 20 else 30 end) as count from fa_it_web_task where is_del=1 and site_type=5 and createtime between '".$month_first_02."' and '".$month_last_02."'")[0]['count'];
                
                $sum_total = array(
                    array(
                        'date'=>date("Y-m",time()),
                        'total1' => $zeelool0_bug+$zeelool0_demand+$zeelool0_task,
                        'total2' => $voogueme0_bug+$voogueme0_demand+$voogueme0_task,
                        'total3' => $nihao0_bug+$nihao0_demand+$nihao0_task,
                        'total4' => $wesee0_bug+$wesee0_demand+$wesee0_task,
                        'total5' => $others0_bug+$others0_demand+$others0_task,
                    ),
                    array(
                        'date'=>date('Y-m', strtotime('-1 month')),
                        'total1' => $zeelool1_bug+$zeelool1_demand+$zeelool1_task,
                        'total2' => $voogueme1_bug+$voogueme1_demand+$voogueme1_task,
                        'total3' => $nihao1_bug+$nihao1_demand+$nihao1_task,
                        'total4' => $wesee1_bug+$wesee1_demand+$wesee1_task,
                        'total5' => $others1_bug+$others1_demand+$others1_task,
                    ),
                    array(
                        'date'=>date('Y-m', strtotime('-2 month')),
                        'total1' => $zeelool2_bug+$zeelool2_demand+$zeelool2_task,
                        'total2' => $voogueme2_bug+$voogueme2_demand+$voogueme2_task,
                        'total3' => $nihao2_bug+$nihao2_demand+$nihao2_task,
                        'total4' => $wesee2_bug+$wesee2_demand+$wesee2_task,
                        'total5' => $others2_bug+$others2_demand+$others2_task,
                    ),
                );
                $this->assign('sum_total',$sum_total);
               
        }else{
            $web_score_statistics = $this->develop_score_statistics($month);
            $web_outtime_statistics = $this->develop_outtime_statistics($month);
        }
        $this->assign('web_score_statistics',$web_score_statistics);
        $this->assign('web_outtime_statistics',$web_outtime_statistics);
        $this->assign('date_arr',$date_arr);
        $this->assign('month',$month);
        $this->assign('type',$type);

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
    public function web_score_statistics($month){
        if($month == 0){
            $stime = date("Y-m-01",time());//本月第一天
            $etime = date('Y-m-d', strtotime("$stime +1 month -1 day"));//本月最后一天
        }else{
            $stime = date('Y-m-01', strtotime('-'.$month.' month'));//上月第一天
            $etime = date('Y-m-t', strtotime('-'.$month.' month'));//上月最后一天
        }
        $smap['create_time'] = ['between', [$stime, $etime]];
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
            ->where($task_smap)
            ->select();

        $task_item_list = collection($task_item_list)->toArray();//获取一个月的开发任务
        
        $web_user_total = $this->PersonalJobNum($web_designer_user_arr,'web_designer_user_id','前端',$list,$task_item_list);
        $php_user_total = $this->PersonalJobNum($phper_user_arr,'phper_user_id','后端',$list,$task_item_list);
        $app_user_total = $this->PersonalJobNum($app_user_arr,'app_user_id','APP',$list,$task_item_list);
        $test_user_total =$this->PersonalJobNum($test_user_arr,'test_user_id','测试',$list,$task_item_list);
        $personal_total=array_merge($web_user_total,$php_user_total,$app_user_total,$test_user_total);
        return $personal_total;
    }
    /**
     * 网站统计逾期任务量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 17:55:38 
     * @return void
     */
    public function web_outtime_statistics($month){
        if($month == 0){
            $stime = date("Y-m-01",time());//本月第一天
            $etime = date('Y-m-d', strtotime("$stime +1 month -1 day"));//本月最后一天
        }else{
            $stime = date('Y-m-01', strtotime('-'.$month.' month'));//上月第一天
            $etime = date('Y-m-t', strtotime('-'.$month.' month'));//上月最后一天
        }
        $smap['create_time'] = ['between', [$stime, $etime]];
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
            ->where($task_smap)
            ->select();

        $task_item_list = collection($task_item_list)->toArray();//获取一个月的开发任务

        $web_user_total = $this->PersonalOuttimeNum($web_designer_user_arr,'web_designer_user_id','前端',$list,$task_item_list);
        $php_user_total = $this->PersonalOuttimeNum($phper_user_arr,'phper_user_id','后端',$list,$task_item_list);
        $app_user_total = $this->PersonalOuttimeNum($app_user_arr,'app_user_id','APP',$list,$task_item_list);
        $personal_total=array_merge($web_user_total,$php_user_total,$app_user_total);
        return $personal_total;
    }
    /**
     * 开发统计任务量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 15:11:21 
     * @return void
     */
    public function develop_score_statistics($month){
        if($month == 0){
            $stime = date("Y-m-01",time());//本月第一天
            $etime = date('Y-m-d', strtotime("$stime +1 month -1 day"));//本月最后一天
        }else{
            $stime = date('Y-m-01', strtotime('-'.$month.' month'));//上月第一天
            $etime = date('Y-m-t', strtotime('-'.$month.' month'));//上月最后一天
        }
        $smap['createtime'] = ['between', [$stime, $etime]];
        $task_smap['createtime'] = ['between', [$stime, $etime]];

        //统计个人 需求数量，bug数量，开发任务数量，疑难数量，总数量
        //遍历每个人获取相应数据
        $phper_user_arr = config('develop_demand.phper_user');
        
        $list = $this->developmodel
            ->where('is_del', 1)
            ->where($smap)
            ->select();

        $list = collection($list)->toArray();//获取一个月的需求与bug

        $task_item_list = $this->developWebTaskItem
            ->with("developwebtask")
            ->where('is_del', 1)
            ->where($task_smap)
            ->select();
        $task_item_list = collection($task_item_list)->toArray();//获取一个月的开发任务
        $php_user_total = $this->DevelopJobNum($phper_user_arr,'assign_developer_ids','开发',$list,$task_item_list);
        return $php_user_total;
    }
    /**
     * 开发统计逾期任务量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 17:55:38 
     * @return void
     */
    public function develop_outtime_statistics($month){
        if($month == 0){
            $stime = date("Y-m-01",time());//本月第一天
            $etime = date('Y-m-d', strtotime("$stime +1 month -1 day"));//本月最后一天
        }else{
            $stime = date('Y-m-01', strtotime('-'.$month.' month'));//上月第一天
            $etime = date('Y-m-t', strtotime('-'.$month.' month'));//上月最后一天
        }
        $smap['createtime'] = ['between', [$stime, $etime]];
        $task_smap['createtime'] = ['between', [$stime, $etime]];

        //统计个人 需求数量，bug数量，开发任务数量，疑难数量，总数量
        //遍历每个人获取相应数据
        $phper_user_arr = config('develop_demand.phper_user');

        $list = $this->developmodel
            ->where('is_del', 1)
            ->where($smap)
            ->select();

        $list = collection($list)->toArray();//获取一个月的需求与bug

        $task_item_list = $this->developWebTaskItem
        ->with("developwebtask")
        ->where('is_del', 1)
        ->where($task_smap)
        ->select();
        $task_item_list = collection($task_item_list)->toArray();//获取一个月的开发任务

        $php_user_total = $this->DevelopOuttimeNum($phper_user_arr,'assign_developer_ids','开发',$list,$task_item_list);
       
        return $php_user_total;
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
     * 获取网站每个人的工作量
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
                $score_total = 0;    //总量
                $web_user[$i]['user_name'] = $uv;
                $web_user[$i]['user_id'] = $uk;
                $web_user[$i]['group_name'] = $group_name;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        if ($v['type'] == 1) {
                            $bug_num++;
                            if($v['is_small_probability'] == 1){
                                $bug_total+=2;
                                $score_total+=2;
                            }else{
                                $bug_total++;
                                $score_total++;
                            }
                        } elseif ($v['type'] == 2) {
                            $complexity = $this->getcomplexity($field_name)['complexity'];
                            $demand_num++;
                            if($v[$complexity] == 1){
                                $demand_total++;
                                $score_total++;
                            }elseif($v[$complexity] == 2){
                                $demand_total += 3;
                                $score_total += 3;
                            }
                            elseif($v[$complexity] == 3){
                                $demand_total += 5;
                                $score_total += 5;
                            }   
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        $task_num++;
                        if($v['type'] == 1){
                            $task_total += 10;
                            $score_total += 10;
                        }elseif($v['type'] == 2){
                            $task_total += 20;
                            $score_total += 20;
                        }else{
                            $task_total += 30;
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
                $web_user[$i]['score_total'] = $score_total;
                $i++;
            }
        }
        return $web_user;
    }
     /**
     * 获取网站每个人逾期的工作量
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
                $bug_person_num = 0;      //个人bug逾期数量
                $demand_num = 0;   //需求总数量
                $demand_person_num = 0;   //个人需求逾期数量
                $task_num = 0;     //开发总数量
                $task_person_num = 0;     //个人开发逾期数量
                $web_user[$i]['user_name'] = $uv;
                $web_user[$i]['user_id'] = $uk;
                $web_user[$i]['group_name'] = $group_name;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        $arr = $this->getcomplexity($field_name);
                        if ($v['type'] == 1) {
                            $bug_num++;
                            $bug_date = $v['create_time'];
                            $createtime = date('Y-m-d H:i:s',strtotime("$bug_date+1day"));
                            if($createtime < $arr['completetime']){
                                $bug_person_num++;
                            }
                        } elseif ($v['type'] == 2) {
                            $demand_num++;
                            if($arr['experttetime']<$arr['completetime']){
                                $demand_person_num++;
                            } 
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        $task_num++;
                        if($v['plan_date'] < $v['complete_date']){
                            $task_person_num++;
                        }
                    }
                }
                $web_user[$i]['bug_num'] = $bug_num;
                $web_user[$i]['bug_person_num'] = $bug_person_num;
                $web_user[$i]['demand_num'] = $demand_num;
                $web_user[$i]['demand_person_num'] = $demand_person_num;
                $web_user[$i]['task_num'] = $task_num;
                $web_user[$i]['task_person_num'] = $task_person_num;
                $i++;
            }
        }
        return $web_user;
    }
    /**
     * 获取开发每个人的工作量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 15:40:22 
     * @return void
     */
    public function DevelopJobNum($user_arr=[],$field_name='',$group_name='',$demand_list=[],$item_list=[]){
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
                $score_total = 0;    //总量
                $web_user[$i]['user_name'] = $uv;
                $web_user[$i]['user_id'] = $uk;
                $web_user[$i]['group_name'] = $group_name;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        if ($v['type'] == 1) {
                            $bug_num++;
                            if($v['is_small_probability'] == 1){
                                $bug_total+=2;
                                $score_total+=2;
                            }else{
                                $bug_total++;
                                $score_total++;
                            }
                        } elseif ($v['type'] == 2) {
                            $complexity = $v['complexity'];
                            $demand_num++;
                            if($v[$complexity] == 1){
                                $demand_total++;
                                $score_total++;
                            }elseif($v[$complexity] == 2){
                                $demand_total += 3;
                                $score_total += 3;
                            }
                            elseif($v[$complexity] == 3){
                                $demand_total += 5;
                                $score_total += 5;
                            }   
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        $task_num++;
                        if($v['type'] == 1){
                            $task_total += 10;
                            $score_total += 10;
                        }elseif($v['type'] == 2){
                            $task_total += 20;
                            $score_total += 20;
                        }else{
                            $task_total += 30;
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
                $web_user[$i]['score_total'] = $score_total;
                $i++;
            }
        }
        return $web_user;
    }
     /**
     * 获取网站每个人逾期的工作量
     *
     * @Description
     * @author mjj
     * @since 2020/05/27 15:40:22 
     * @return void
     */
    public function DevelopOuttimeNum($user_arr=[],$field_name='',$group_name='',$demand_list=[],$item_list=[]){
        $web_user = array();
        $i = 0;
        foreach ($user_arr as $uk => $uv) {
            if ($uk) {
                $bug_num = 0;      //bug总数量
                $bug_person_num = 0;      //个人bug逾期数量
                $demand_num = 0;   //需求总数量
                $demand_person_num = 0;   //个人需求逾期数量
                $task_num = 0;     //开发总数量
                $task_person_num = 0;     //个人开发逾期数量
                $web_user[$i]['user_name'] = $uv;
                $web_user[$i]['user_id'] = $uk;
                $web_user[$i]['group_name'] = $group_name;
                foreach ($demand_list as $k => $v) {
                    if (in_array($uk, explode(',', $v[$field_name]))) {
                        if ($v['type'] == 1) {
                            $bug_num++;
                            $bug_date = $v['createtime'];
                            $createtime = date('Y-m-d H:i:s',strtotime("$bug_date+1day"));
                            if($createtime < $v['finish_time']){
                                $bug_person_num++;
                            }
                        } elseif ($v['type'] == 2) {
                            $demand_num++;
                            if($v['estimated_time']<$v['finish_time']){
                                $demand_person_num++;
                            } 
                        }
                    }
                }
                foreach ($item_list as $k => $v) {
                    if ($v['person_in_charge'] == $uk) {
                        $task_num++;
                        if($v['plan_date'] < $v['complete_date']){
                            $task_person_num++;
                        }
                    }
                }
                $web_user[$i]['bug_num'] = $bug_num;
                $web_user[$i]['bug_person_num'] = $bug_person_num;
                $web_user[$i]['demand_num'] = $demand_num;
                $web_user[$i]['demand_person_num'] = $demand_person_num;
                $web_user[$i]['task_num'] = $task_num;
                $web_user[$i]['task_person_num'] = $task_person_num;
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