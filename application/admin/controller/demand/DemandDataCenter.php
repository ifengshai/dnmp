<?php

namespace app\admin\controller\demand;

use app\admin\model\AuthGroup;
use app\common\controller\Backend;
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
        'del', 'distribution', 'test_handle', 'detail', 'demand_review', 'del', 'edit', 'rdc_demand_pass'
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
        $php_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $php_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();

        $web_group_ids = $authgroup->getChildrenIds(config('demand.web_group_id'));
        $w_id[] = config('demand.web_group_id');
        $web_group_ids = array_merge($web_group_ids, $w_id);
        $web_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $web_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();
        $web_group_ids=array(['id'=>'192','niname'=>'卢志恒'],
            ['id'=>'','niname'=>''],
            ['id'=>'','niname'=>'']);

        //获取app组长&组员
        $app_group_ids = $authgroup->getChildrenIds(config('demand.app_group_id'));
        $a_id[] = config('demand.app_group_id');
        $app_group_ids = array_merge($app_group_ids, $a_id);
        $app_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $app_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();

        //获取test组长&组员
        $test_group_ids = $authgroup->getChildrenIds(config('demand.test_group_id'));
        $t_id[] = config('demand.test_group_id');
        $test_group_ids = array_merge($test_group_ids, $t_id);
        $test_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $test_group_ids)
            ->where('status', 'normal')
            ->field('nickname,id')
            ->select();

        $sql = "SELECT
	site ,
	count(1) as 'allcont',
	sum(all_finish_time> node_time) as 'outSum',
	sum(type = 1) AS 'tpye1',
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
            } else {
                $allDemandData[$k]['site_type'] = "";
            }

        }

        $phpData = $this->statisticsphper($php_users, $start_time, $end_time);

        $appData = $this->statisticsApp($app_users, $start_time, $end_time);
        $testData = $this->statisticsTest($test_users, $start_time, $end_time);
        $webData = $this->statisticsWeb($web_users, $start_time, $end_time);
        $demandtUser = array_merge($phpData, $appData, $webData);

        dump($phpData);
        dump($allDemandData);
        dump($demandtUser);
        dump($testData);
        //执行个人统计数据

        $this->view->assign('demand_data', $allDemandData);
        $this->view->assign('demand_user_data', $demandtUser);
        $this->view->assign('demand_test_data', $testData);
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
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where phper_user_id like '%$userId%' and create_time > '$start_time'
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
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where app_user_id like '%$userId%' and create_time > '$start_time'
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
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where test_user_id like '%$userId%' and create_time > '$start_time'
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
	sum(type = 1) AS 'bugCount',
	sum(type !=1) AS 'demandCount'

from fa_it_web_demand where web_designer_user_id like '%$userId%' and create_time > '$start_time'
AND create_time < '$end_time'";
            $oneUser = $this->model->query($sql);
            foreach ($oneUser as $k => $v) {
                $v['nickname'] = $user['nickname'];
                array_push($array, $v);
            }
        }
        return $array;

    }

}



