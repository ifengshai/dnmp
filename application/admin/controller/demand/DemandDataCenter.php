<?php

namespace app\admin\controller\demand;

use app\admin\model\AuthGroup;
use app\common\controller\Backend;
use app\common\model\Auth;
use think\Db;

/**
 * 技术部网站组需求管理
 *
 * @icon fa fa-circle-o
 */
class DemandDataCenter extends Backend
{

    /**
     * ItWebDemand模型对象
     * @var \app\admin\model\demand\ItWebDemand
     */
    protected $model = null;
    protected $noNeedRight = [
        'del', 'distribution', 'test_handle', 'detail', 'demand_review', 'del', 'edit', 'rdc_demand_pass','confirm_list'
    ];  //解决创建人无删除权限问题 暂定

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItWebDemand;
        $this->view->assign('getTabList', $this->model->getTabList());
        $this->ItWebDemandReview = new \app\admin\model\demand\ItWebDemandReview;
        $this->assignconfig('admin_id', session('admin.id'));
    }


    /**
     *  数据统计页面  默认当月数据
     * @return string
     * @author fanzhigang
     * @date   2021/9/22 11:07
     */
    public function index()
    {

        $date = input('create_time');
        //默认本月数据
        if (!$date) {
            $date = date("Y-m-01 00:00:00").' - '.date("Y-m-d H:i:s", time());
        }
        $time = explode(' - ', $date);
        $start_time = date('Y-m-d  H:i:s', strtotime($time[0]));
        $end_time = date('Y-m-d  H:i:s', strtotime($time[1]));


        //统计个人 需求数量，bug数量，开发任务数量，疑难数量，总数量
        //遍历每个人获取相应数据
        $web_designer_user_arr = config('demand.'.'web_designer_user');
        $authgroup = new AuthGroup();
        $php_group_ids = $authgroup->getChildrenIds(config('demand.php_group_id'));
        $p_id[] = config('demand.php_group_id');
        $php_group_ids = array_merge($php_group_ids, $p_id);
       /* $php_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $php_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();*/
        $php_users=array(['id'=>'192','nickname'=>'卢志恒'],
            ['id'=>'227','nickname'=>'刘松巍'],
            ['id'=>'229','nickname'=>'周正晖'],
            ['id'=>'335','nickname'=>'吴钢剑'],
            ['id'=>'350','nickname'=>'张靖威'],
            ['id'=>'442','nickname'=>'吴晓碟'],
            ['id'=>'184','nickname'=>'樊志刚'],
            ['id'=>'352','nickname'=>'戈杨华'],
            ['id'=>'204','nickname'=>'王恒刚'],
        );

        $web_group_ids = $authgroup->getChildrenIds(config('demand.web_group_id'));
        $w_id[] = config('demand.web_group_id');
        $web_group_ids = array_merge($web_group_ids, $w_id);
       /* $web_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $web_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();*/
        $web_users=array(['id'=>'309','nickname'=>'王逢超'],
            ['id'=>'393','nickname'=>'王鹏辉'],
            ['id'=>'515','nickname'=>'赵一帆'],
            ['id'=>'534','nickname'=>'刘格优'],
            ['id'=>'558','nickname'=>'关亚可'],
            ['id'=>'580','nickname'=>'王亚飞'],
            ['id'=>'603','nickname'=>'郑博文']
        );

        //获取app组长&组员
        $app_group_ids = $authgroup->getChildrenIds(config('demand.app_group_id'));
        $a_id[] = config('demand.app_group_id');
        $app_group_ids = array_merge($app_group_ids, $a_id);
        /*$app_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $app_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();*/
        $app_users=array(['id'=>'194','nickname'=>'杨志豪'],
            ['id'=>'340','nickname'=>'张俊鹏'],
            ['id'=>'525','nickname'=>'李亚峰'],
        );

        //获取test组长&组员
        $test_group_ids = $authgroup->getChildrenIds(config('demand.test_group_id'));
        $t_id[] = config('demand.test_group_id');
        $test_group_ids = array_merge($test_group_ids, $t_id);
        /*$test_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $test_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();*/

        $test_users=array(['id'=>'280','nickname'=>'刘超'],
            ['id'=>'242','nickname'=>'张鹏'],
            ['id'=>'195','nickname'=>'马红亚'],
            ['id'=>'200','nickname'=>'陈亚蒙'],
            ['id'=>'454','nickname'=>'王蕾'],
            ['id'=>'541','nickname'=>'周丹丹']
        );
        $sql = "SELECT
	site ,
	count(1) as 'allcont',
	sum(all_finish_time> node_time) as 'outSum',
	sum(type = 1) AS 'type1',
	sum(type = 2) AS 'type2',
    sum(type = 3) AS 'type3',
    sum(type = 4) AS 'type4',
    sum(type = 5) AS 'type5',
    sum(type !=1) AS 'demandSum',
	sum(phper_user_id IS NOT NULL) as 'phper',
	sum(web_designer_user_id IS NOT NULL) as 'weber',
	sum(test_user_id IS NOT NULL) as 'tester',
	sum(app_user_id IS NOT NULL) as 'apper',
    sum(TIMESTAMPDIFF(DAY,pm_audit_status_time,all_finish_time)>3) as 'confirm'
FROM
	`mojing`.`fa_it_web_demand`
WHERE
	create_time > '$start_time'
AND create_time < '$end_time'
and is_del=1
GROUP BY
	site";
        //各站点数据
        $allDemandData = $this->model->query($sql);
        foreach ($allDemandData as $k => $v) {
            if ($v['site'] == 1) {
                $allDemandData[$k]['site_type'] = 'zeelool';
            } elseif ($v['site'] == 2) {
                $allDemandData[$k]['site_type'] = 'voogueme';
            } elseif ($v['site'] == 3) {
                $allDemandData[$k]['site_type'] = 'meeloog';
            } elseif ($v['site'] == 4) {
                $allDemandData[$k]['site_type'] = 'vicmoo';
            } elseif ($v['site'] == 5) {
                $allDemandData[$k]['site_type'] = 'wesee';
            } elseif ($v['site'] == 6) {
                $allDemandData[$k]['site_type'] = 'rufoo';
            } elseif ($v['site'] == 7) {
                $allDemandData[$k]['site_type'] = 'toloog';
            } elseif ($v['site'] == 8) {
                $allDemandData[$k]['site_type'] = 'other';
            } elseif ($v['site'] == 9) {
                $allDemandData[$k]['site_type'] = 'ZeeloolEs';
            } elseif ($v['site'] == 10) {
                $allDemandData[$k]['site_type'] = 'ZeeloolDe';
            } elseif ($v['site'] == 11) {
                $allDemandData[$k]['site_type'] = 'ZeeloolJp';
            } elseif ($v['site'] == 12) {
                $allDemandData[$k]['site_type'] = 'voogmechic';
            }  elseif ($v['site'] == 15) {
                $allDemandData[$k]['site_type'] = 'ZeeloolFr';
            } elseif ($v['site'] == 66) {
                $allDemandData[$k]['site_type'] = '网红管理工具';
            } else {
                $allDemandData[$k]['site_type'] = "";
            }


        }

        $phpData = $this->statisticsphper($php_users, $start_time, $end_time);

        $appData = $this->statisticsApp($app_users, $start_time, $end_time);
        $testData = $this->statisticsTest($test_users, $start_time, $end_time);
        $webData = $this->statisticsWeb($web_users, $start_time, $end_time);
        $demandtUser = array_merge($phpData, $appData, $webData);

        //执行个人统计数据

        $this->view->assign('demand_data', $allDemandData);
        $this->view->assign('demand_user_data', $demandtUser);
        $this->view->assign('demand_test_data', $testData);
        $this->view->assign('created_at',$date);
        //测试统计数据
        return $this->view->fetch();
    }


    public function statisticsphper(array $user_arr, $start_time, $end_time)
    {
        $array = array();
        foreach ($user_arr as $user) {
            $userId = $user['id'];
            $sql = "SELECT 
sum(phper_working_hour) as 'woring',
count(1) as 'demand',
	sum(
		phper_expect_time < phper_finish_time
	) as 'outCount',
	sum(if( phper_expect_time < phper_finish_time,TIMESTAMPDIFF(HOUR, phper_expect_time, phper_finish_time),0)) as 'outHour',
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where phper_user_id like '%$userId%' and is_del=1  and create_time > '$start_time'
AND create_time < '$end_time'";
            $oneUser = $this->model->query($sql);
            foreach ($oneUser as $k => $v) {
                $v['nickname'] = $user['nickname'];
                array_push($array, $v);
            }
        }
        return $array;

    }

    public function statisticsApp(array $user_arr, $start_time, $end_time)
    {
        $array = array();
        foreach ($user_arr as $user) {
            $userId = $user['id'];
            $sql = "SELECT 
sum(app_working_hour) as 'woring',
count(1) as 'demand',
	sum(
		app_finish_time > app_expect_time
	) as 'outCount',
	sum(if( app_expect_time < app_finish_time,TIMESTAMPDIFF(HOUR, app_expect_time, app_finish_time),0)) as 'outHour',
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where app_user_id like '%$userId%' and is_del=1  and create_time > '$start_time'
AND create_time < '$end_time'";
            $oneUser = $this->model->query($sql);
            foreach ($oneUser as $k => $v) {
                $v['nickname'] = $user['nickname'];
                array_push($array, $v);
            }
        }
        return $array;

    }

    public function statisticsTest(array $user_arr, $start_time, $end_time)
    {
        $array = array();
        foreach ($user_arr as $user) {
            $userId = $user['id'];
            $sql = "SELECT 
sum(test_working_hour) as 'woring',
count(1) as 'demand',
	sum(
		node_time < test_finish_time
	) as 'outCount',
		sum(if( node_time < test_finish_time,TIMESTAMPDIFF(HOUR, node_time, test_finish_time),0)) as 'outHour',
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where test_user_id like '%$userId%' and is_del=1  and create_time > '$start_time'
AND create_time < '$end_time'";
            $oneUser = $this->model->query($sql);
            foreach ($oneUser as $k => $v) {
                $v['nickname'] = $user['nickname'];
                array_push($array, $v);
            }
        }
        return $array;

    }

    public function statisticsWeb(array $user_arr, $start_time, $end_time)
    {
        $array = array();
        foreach ($user_arr as $user) {
            $userId = $user['id'];
            $sql = "SELECT 
sum(web_designer_working_hour) as 'woring',
count(1) as 'demand',
	sum(
		web_designer_expect_time < web_designer_finish_time
	) as 'outCount',
			sum(if( web_designer_expect_time < web_designer_finish_time,TIMESTAMPDIFF(HOUR, web_designer_expect_time, web_designer_finish_time),0)) as 'outHour',

	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where web_designer_user_id like '%$userId%' and is_del=1   and create_time > '$start_time'
AND create_time < '$end_time'";
            $oneUser = $this->model->query($sql);
            foreach ($oneUser as $k => $v) {
                $v['nickname'] = $user['nickname'];
                array_push($array, $v);
            }
        }
        return $array;

    }

    /**
     * 大于三天需求
     * @author fanzhigang
     * @date   2021/9/22 20:24
     */
    public function confirm_list(){

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            //筛选开发进度


            $time = explode(' - ', $filter['create_time']);
            $start_time = date('Y-m-d  H:i:s', strtotime($time[0]));
            $end_time = date('Y-m-d  H:i:s', strtotime($time[1]));
            //筛选任务人
            if ($filter['task_user_name']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['task_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                if (empty($id)){
                    $this->error('查无此人,请输入正确名称');
                }
                //前端负责人id 后端负责人id 测试负责人id
                $task_map = "FIND_IN_SET({$id},web_designer_user_id)  or FIND_IN_SET({$id},phper_user_id)  or FIND_IN_SET({$id}, test_user_id)";
                unset($filter['task_user_name']);
                unset($smap['nickname']);
            }

           if ($filter['label'] == 2) { //BUG任务
               $mapLabel="TIMESTAMPDIFF(DAY,pm_audit_status_time,all_finish_time)>3 and create_time > '$start_time' AND create_time < '$end_time' ";
            } else  { //开发任务
                $mapLabel="all_finish_time> node_time and create_time > '$start_time' AND create_time < '$end_time' ";
            }

            unset($filter['label']);
            unset($filter['create_time']);
//            $map['demand_type'] = 1; //默认任务列表
            $this->request->get(['filter' => json_encode($filter)]);

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($mapLabel)
                ->where($task_map)
                ->order($sort, $order)
                ->count();
//            dump($this->model->getLastSql());
            $list = $this->model
                ->where($where)
                ->where($mapLabel)
                ->where($task_map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
//            dump($this->model->getLastSql());
//            die();

            $list = collection($list)->toArray();
            //检查有没有权限
            $permissions['demand_pm_status'] = $this->auth->check('demand/it_web_demand/pm_status'); //产品确认权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add'); //新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del'); //删除权限
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution'); //开发响应
            $permissions['demand_test_handle'] = $this->auth->check('demand/it_web_demand/test_handle'); //测试响应

            foreach ($list as $k => $v) {
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname']; //取提出人
                $list[$k]['detail'] = ''; //前台调用详情字段使用，并无实际意义

                $list[$k]['create_time'] = date('m-d H:i', strtotime($v['create_time']));
                $list[$k]['develop_finish_time'] = $v['develop_finish_time'] ? date('m-d H:i', strtotime($v['develop_finish_time'])) : '';
                $list[$k]['test_finish_time'] = $v['test_finish_time'] ? date('m-d H:i', strtotime($v['test_finish_time'])) : '';
                $list[$k]['all_finish_time'] = $v['all_finish_time'] ? date('m-d H:i', strtotime($v['all_finish_time'])) : '';
//                $list[$k]['node_time'] = $v['node_time'] ? $v['node_time'] . 'Day' : '-'; //预计时间
                $list[$k]['node_time'] = $v['node_time'] ? $v['node_time'] : '-'; //预计时间
                //检查权限
                $list[$k]['demand_pm_status'] = $permissions['demand_pm_status']; //产品确认权限
                $list[$k]['demand_add'] = $permissions['demand_add']; //新增权限
                $list[$k]['demand_del'] = $permissions['demand_del']; //删除权限
                $list[$k]['demand_distribution'] = $permissions['demand_distribution']; //开发响应
                $list[$k]['demand_test_handle'] = $permissions['demand_test_handle']; //测试响应

                //获取各组负责人
                $list[$k]['web_designer_user_name'] = '';
                if ($v['web_designer_user_id']) {
                    //获取php组长&组员
                    $web_userid_arr = explode(',', $v['web_designer_user_id']);
                    $web_users = Db::name("admin")
                        ->whereIn("id", $web_userid_arr)
                        ->column('nickname', 'id');
                    $list[$k]['web_designer_user_name'] = $web_users;
                }

                $list[$k]['php_user_name'] = '';
                if ($v['phper_user_id']) {
                    //获取php组长&组员
                    $php_userid_arr = explode(',', $v['phper_user_id']);
                    $php_users = Db::name("admin")
                        ->whereIn("id", $php_userid_arr)
                        ->column('nickname', 'id');
                    $list[$k]['php_user_name'] = $php_users;
                }

                $list[$k]['app_user_name'] = '';
                if ($v['app_user_id']) {
                    //获取php组长&组员
                    $app_userid_arr = explode(',', $v['app_user_id']);
                    $app_users = Db::name("admin")
                        ->whereIn("id", $app_userid_arr)
                        ->column('nickname', 'id');
                    $list[$k]['app_user_name'] = $app_users;
                }

                $list[$k]['test_user_name'] = '';
                if ($v['test_user_id']) {
                    //获取php组长&组员
                    $test_userid_arr = explode(',', $v['test_user_id']);
                    $test_users = Db::name("admin")
                        ->whereIn("id", $test_userid_arr)
                        ->column('nickname', 'id');
                    $list[$k]['test_user_name'] = $test_users;
                }
            }
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        //限制各主管没有添加权限
        $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
        $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
        $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
        $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
        $userIds = array_merge($authUserIds, $testAuthUserIds, $webAuthUserIds, $appAuthUserIds);
        if (in_array(session('admin.id'), $userIds)) {
            $this->assign('auth_label', 0);
        } else {
            $this->assign('auth_label', 1);
        }

        return $this->view->fetch();
    }

}



