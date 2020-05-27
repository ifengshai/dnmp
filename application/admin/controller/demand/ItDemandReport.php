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

        $month_first_01 = date('Y-m-01', strtotime('-1 month'));//上月第一天
        $month_last_01 = date('Y-m-t', strtotime('-1 month'));//上月最后一天

        $month_first_02 = date('Y-m-01', strtotime('-2 month'));//上月第一天
        $month_last_02 = date('Y-m-t', strtotime('-2 month'));//上月最后一天
        
        dump($month_first);
        dump($month_last);
        dump($month_first_01);
        dump($month_last_01);
        dump($month_first_02);
        dump($month_last_02);
        $task_list = $this->itWebTask
            ->where('is_del', 1)
            ->whereTime('createtime', 'between', [$month_first, $month_last])
            ->select();
            $task_list = collection($task_list)->toArray();
            dump($task_list);

        if ($this->request->isAjax()) {
            
            //网站组--目标--start
            /**
             * 短期任务：10个
             * 中期任务：20个
             * 长期任务：30个
             */
            


            //网站组--目标--end
            $json['columnData'] = [
                [
                    'name'=> '直接访问',
                    'type'=>'bar',
                    'data'=> [320, 332, 301, 334, 390, 330, 320]
                ],
                [
                    'name'=> '邮件营销',
                    'type'=>'bar',
                    'data'=>  [120, 132, 101, 134, 90, 230, 210]
                ],
                [
                    'name'=> '联盟广告',
                    'type'=>'bar',
                    'data'=> [220, 182, 191, 234, 290, 330, 310]
                ],
 
            ];

            $json['xColumnName'] = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
            $json['column'] = ['直接访问', '邮件营销', '联盟广告', '视频广告', '搜索引擎', '百度', '谷歌', '必应', '其他'];
            return json(['code' => 1, 'data' => $json]);
            
        }

        return $this->view->fetch();
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