<?php

namespace app\admin\controller\demand;

use app\api\controller\Ding;
use app\common\controller\Backend;
use app\common\model\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Request;
use app\admin\model\AuthGroup;

/**
 * 技术部网站组需求管理
 *
 * @icon fa fa-circle-o
 */
class ItWebDemand extends Backend
{

    /**
     * ItWebDemand模型对象
     * @var \app\admin\model\demand\ItWebDemand
     */
    protected $model = null;
    protected $noNeedRight = ['del', 'distribution', 'test_handle', 'detail', 'demand_review', 'del', 'edit', 'rdc_demand_pass'];  //解决创建人无删除权限问题 暂定

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItWebDemand;
        $this->view->assign('getTabList', $this->model->getTabList());
        $this->ItWebDemandReview = new \app\admin\model\demand\ItWebDemandReview;
        $this->assignconfig('admin_id', session('admin.id'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /*
     *  根据优先级和任务周期，返回任务开始时间和结束时间
     *  $priority  优先级
     *  $node_time  任务周期
     * */
    public function start_time($priority, $node_time)
    {
        $day_17 = mktime(17, 0, 0, date('m'), date('d'), date('Y')); //当天5点
        $week_17 = strtotime("+17 hour", strtotime("friday")); //本周5，下午5点
        $data = array();
        switch ($priority) {
            case 1:
                $data['start_time'] = date('Y-m-d H:i', time());
                $data['end_time'] = $node_time;
//                $data['end_time'] = date('Y-m-d H:i', strtotime('+' . $node_time . 'day'));
                break;
            case 2:
                $data['start_time'] = date('Y-m-d H:i', $day_17);
                $data['end_time'] = $node_time;
//                $data['end_time'] = date('Y-m-d H:i', strtotime("+" . $node_time . " day", $day_17));
                break;
            case 3:
                $data['start_time'] = date('Y-m-d H:i', $week_17);
                $data['end_time'] = $node_time;
//                $data['end_time'] = date('Y-m-d H:i', strtotime("+" . $node_time . " day", $week_17));
                break;
            case 4:
                $data['start_time'] = date('Y-m-d H:i', $week_17);
                $data['end_time'] = $node_time;
//                $data['end_time'] = date('Y-m-d H:i', strtotime("+" . $node_time . " day", $week_17));
                break;
            case 5:
                $data['start_time'] = date('Y-m-d H:i', $week_17);
                $data['end_time'] = $node_time;
//                $data['end_time'] = date('Y-m-d H:i', strtotime("+" . $node_time . " day", $week_17));
                break;
            default:
                $data['start_time'] = '';
                $data['end_time'] = '';
        }
        return $data;
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

    /**
     * 产品权限
     */
    public function pm_status($ids = null)
    {
    }

    /**
     * 技术部网站需求列表
     */
    public function index()
    {
//        $time_update['status'] = 2;
//        $time_update['demand_type'] = 1;
//        $time = date('Y-m-d H:i', time());
//        $this->model->allowField(true)->save($time_update, ['start_time' => ['elt', $time], 'status' => 1, 'pm_audit_status' => 3]);

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            //筛选开发进度
            if ($filter['develop_finish_status1']) {
                $map['develop_finish_status'] = $filter['develop_finish_status1'];
                unset($filter['develop_finish_status1']);
            }

            //筛选测试进度
            if ($filter['test_status1']) {
                $map['test_status'] = $filter['test_status1'];
                unset($filter['test_status1']);
            }
            if ($filter['end_time']) {
                $time = explode(' - ', $filter['end_time']);
                $map['all_finish_time'] = ['between', [$time[0], $time[1]]];
                unset($filter['end_time']);
            }

            //筛选提出人
            if ($filter['entry_user_name']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['entry_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                $map['entry_user_id'] = $id;
                unset($filter['entry_user_name']);
                unset($smap['nickname']);
            }

            //筛选任务人
            if ($filter['task_user_name']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['task_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                //前端负责人id 后端负责人id 测试负责人id
                $task_map = "FIND_IN_SET({$id},web_designer_user_id)  or FIND_IN_SET({$id},phper_user_id)  or FIND_IN_SET({$id}, test_user_id)";

                unset($filter['task_user_name']);
                unset($smap['nickname']);
            }
            $adminId = session('admin.id');
            //我的
            $meWhere = '1=1';
            if ($filter['label'] == 1) {
                //是否是开发主管
                $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                if (in_array($adminId, $authUserIds)) {
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.php_group_person_id')) ?: [];
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},phper_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},phper_user_id)";
                        }
                    }
                }

                //是否是测试主管
                $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                if (in_array($adminId, $testAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.test_group_person_id')) ?: [];
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},test_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},test_user_id)";
                        }
                    }
                }

                //是否是前端主管
                $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                if (in_array($adminId, $webAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.web_group_person_id'));
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},web_designer_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},web_designer_user_id)";
                        }
                    }
                }

                //是否是app主管
                $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                if (in_array($adminId, $appAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.app_group_person_id'));
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},app_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},app_user_id)";
                        }
                    }
                }

                //不是主管
                if ($meWhere === '1=1') {
                    $meWhere = "FIND_IN_SET({$adminId},web_designer_user_id) or FIND_IN_SET({$adminId},phper_user_id) or FIND_IN_SET({$adminId},app_user_id) or FIND_IN_SET({$adminId},test_user_id) or FIND_IN_SET({$adminId},entry_user_id) or FIND_IN_SET({$adminId},copy_to_user_id)";
                }
            } elseif ($filter['label'] == 2) { //未完成
                /**
                 * 其他人：展示任务状态为未激活、激活、已响应的任务
                 * 产品：展示评审状态为待审、pending的任务
                 */
                //是否为产品
                $authUserIds = array_merge(Auth::getGroupUserId(config('demand.product_group_id')) ?: [], Auth::getGroupUserId(config('demand.product_group_person_id')) ?: []);
                //是否为测试
                $testAuthUserIds = array_merge(Auth::getGroupUserId(config('demand.test_group_id')) ?: [], Auth::getGroupUserId(config('demand.test_group_person_id')) ?: []);
                $map['status'] = ['eq', 3];
                $map['develop_finish_status'] = ['in', [1, 2, 3]];
                if (in_array($adminId, $authUserIds)) {
                    $map['pm_audit_status'] = ['in', [1, 2, 3, 4]];
                    $map['status'] = ['eq', 3];
                } elseif (in_array($adminId, $testAuthUserIds)) {
                    //测试 未上线都算未完成
                    $map['test_status'] = ['in', [1, 2, 3, 4]];
                    $map['status'] = ['eq', 3];
                } else {
                    //非产品 非测试  未激活、激活、已响应的任务
                }
            } elseif ($filter['label'] == 3) { //BUG任务
                $map['type'] = 1;
            } elseif ($filter['label'] == 4) { //开发任务
                $map['type'] = 5;
            } elseif ($filter['label'] == 5) { //其他任务
                $map['type'] = ['in', [2, 3, 4]];
            } elseif ($filter['label'] == 6) { //需求排期表
                $val = 'priority';
            }
            unset($filter['label']);
            $map['demand_type'] = 1; //默认任务列表
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->where($task_map)
                ->order($sort, $order)
                ->count();
            if ($val == 'priority') {
                $list = $this->model
                    ->where($where)
                    ->where($meWhere)
                    ->where($map)
                    ->where($task_map)
                    ->order('priority', 'desc')
                    ->limit($offset, $limit)
                    ->select();
            } else {
                $list = $this->model
                    ->where($where)
                    ->where($meWhere)
                    ->where($map)
                    ->where($task_map)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }


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
            $result = array("total" => $total, "rows" => $list);
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

    /**
     * 网站端临时数据导出
     */
    public function batch_export_xls()
    {

        $filter = json_decode($this->request->get('filter'), true);
        //筛选开发进度
        $map = [];
        if ($filter['phper_group']) {
            $map['phper_group'] = $filter['phper_group'];
            unset($filter['phper_group']);
        }

        $starDay = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m")-1, 1));
        $endDay = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), 0));
        $type = input('param.type');

        $where['is_del'] = ['eq', 1];
        $where['demand_type'] = ['eq', $type];
        $where['end_time'] = ['between', [$starDay, $endDay]];
        $list = $this->model
            ->where($where)
            ->where($map)
            ->order('id desc')
            ->select();

        $list = collection($list)->toArray();

        foreach ($list as $k => $v) {
            $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
            $web_designer_user_id = $this->auth->getUserInfo($list[$k]['web_designer_user_id']);
            $copy_to_user_id = $this->auth->getUserInfo($list[$k]['copy_to_user_id']);
            $list[$k]['entry_user_name'] = $user_detail['nickname']; //取提出人
            $list[$k]['web_designer_user_id'] = $web_designer_user_id['nickname']; //取提出人
            $list[$k]['copy_to_user_id'] = $copy_to_user_id['nickname']; //取提出人
            //获取各组负责人
            $list[$k]['web_designer_user_name'] = '';
            if ($v['web_designer_user_id']) {
                //获取前端组长&组员
                $web_userid_arr = explode(',', $v['web_designer_user_id']);
                $web_users = Db::name("admin")
                    ->whereIn("id", $web_userid_arr)
                    ->column('nickname', 'id');

                if (!empty($web_users)) {
                    $list[$k]['web_designer_user_name'] = implode(',', $web_users);
                } else {
                    $list[$k]['web_designer_user_name'] = '';
                }

            }

            $list[$k]['php_user_name'] = '';
            if ($v['phper_user_id']) {
                //获取php组长&组员
                $php_userid_arr = explode(',', $v['phper_user_id']);
                $php_users = Db::name("admin")
                    ->whereIn("id", $php_userid_arr)
                    ->column('nickname', 'id');
                if (!empty($php_users)) {
                    $list[$k]['php_user_name'] = implode(',', $php_users);
                } else {
                    $list[$k]['php_user_name'] = '';
                }
            }

            $list[$k]['app_user_name'] = '';
            if ($v['app_user_id']) {
                //获取php组长&组员
                $app_userid_arr = explode(',', $v['app_user_id']);
                $app_users = Db::name("admin")
                    ->whereIn("id", $app_userid_arr)
                    ->column('nickname', 'id');
                if (!empty($app_users)) {
                    $list[$k]['app_user_name'] = implode(',', $app_users);
                } else {
                    $list[$k]['app_user_name'] = '';
                }
            }
            //站点
            if ($v['site'] == 1) {
                $list[$k]['site'] = 'zeelool';
            } elseif ($v['site'] == 2) {
                $list[$k]['site'] = 'voogueme';
            } elseif ($v['site'] == 3) {
                $list[$k]['site'] = 'meeloog';
            } elseif ($v['site'] == 4) {
                $list[$k]['site'] = 'vicmoo';
            } elseif ($v['site'] == 5) {
                $list[$k]['site'] = 'wesee';
            } elseif ($v['site'] == 6) {
                $list[$k]['site'] = 'rufoo';
            } elseif ($v['site'] == 7) {
                $list[$k]['site'] = 'toloog';
            } elseif ($v['site'] == 8) {
                $list[$k]['site'] = 'other';
            } elseif ($v['site'] == 9) {
                $list[$k]['site'] = 'ZeeloolEs';
            } elseif ($v['site'] == 10) {
                $list[$k]['site'] = 'ZeeloolDe';
            } elseif ($v['site'] == 11) {
                $list[$k]['site'] = 'ZeeloolJp';
            } else {
                $list[$k]['site'] = 'voogmechic';
            }
//
            //任务类型
            if ($v['site_type'] == 1) {
                $list[$k]['site_type'] = 'bug';
            } elseif ($v['site_type'] == 2) {
                $list[$k]['site_type'] = '维护';
            } elseif ($v['site_type'] == 3) {
                $list[$k]['site_type'] = '优化';
            } elseif ($v['site_type'] == 4) {
                $list[$k]['site_type'] = '新功能';
            } else {
                $list[$k]['site_type'] = '开发';
            }
            //功能模块
            if ($v['functional_module'] == 1) {
                $list[$k]['functional_module'] = '购物车';
            } elseif ($v['functional_module'] == 2) {
                $list[$k]['functional_module'] = '个人中心';
            } elseif ($v['functional_module'] == 3) {
                $list[$k]['functional_module'] = '列表页';
            } elseif ($v['functional_module'] == 4) {
                $list[$k]['functional_module'] = '详情页';
            } elseif ($v['functional_module'] == 5) {
                $list[$k]['functional_module'] = '首页';
            } elseif ($v['functional_module'] == 6) {
                $list[$k]['functional_module'] = '优惠券';
            } elseif ($v['functional_module'] == 7) {
                $list[$k]['functional_module'] = '支付页';
            } elseif ($v['functional_module'] == 8) {
                $list[$k]['functional_module'] = 'magento后台';
            } else {
                $list[$k]['functional_module'] = '活动页';
            }
            //难易程度
            if ($v['web_designer_complexity'] == 1) {
                $list[$k]['web_designer_complexity'] = '简单';
            } elseif ($v['web_designer_complexity'] == 2) {
                $list[$k]['web_designer_complexity'] = '中等';
            } else {
                $list[$k]['web_designer_complexity'] = '复杂';
            }

            //难易程度
            if ($v['phper_complexity'] == 1) {
                $list[$k]['phper_complexity'] = '简单';
            } elseif ($v['phper_complexity'] == 2) {
                $list[$k]['phper_complexity'] = '中等';
            } else {
                $list[$k]['phper_complexity'] = '复杂';
            }

            //是否需要前端
            if ($v['web_designer_group'] == 0) {
                $list[$k]['web_designer_group'] = '未确认';
            } elseif ($v['web_designer_group'] == 1) {
                $list[$k]['web_designer_group'] = '需要';
            } else {
                $list[$k]['web_designer_group'] = '不需要';
            }

            //是否需要前端
            if ($v['phper_group'] == 0) {
                $list[$k]['phper_group'] = '未确认';
            } elseif ($v['phper_group'] == 1) {
                $list[$k]['phper_group'] = '需要';
            } else {
                $list[$k]['phper_group'] = '不需要';
            }

            if ($v['test_group'] == 0) {
                $list[$k]['test_group'] = '未确认';
            } elseif ($v['test_group'] == 1) {
                $list[$k]['test_group'] = '需要';
            } else {
                $list[$k]['test_group'] = '不需要';
            }

            //是否超时
            if ($v['end_time'] < $v['node_time']) {
                $list[$k]['overtime'] = '否';
            } else {
                $list[$k]['overtime'] = '是';
            }
            //是否拒绝
            if ($v['web_remarks'] !== null) {
                $list[$k]['web_remarks'] = '是';
            } else {
                $list[$k]['web_remarks'] = '否';
            }

            if ($v['pm_audit_status'] !== 2) {
                $list[$k]['pm_audit_status'] = '否';
            } else {
                $list[$k]['pm_audit_status'] = '是';
            }
            if ($v['secondary_operation'] !== 2) {
                $list[$k]['pm_audit_status'] = '否';
            } else {
                $list[$k]['pm_audit_status'] = '是';
            }
        }

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();


        //常规方式：利用setCellValue()填充数据
        $spreadsheet
            ->setActiveSheetIndex(0)->setCellValue("A1", "需求ID")
            ->setCellValue("B1", "站点")
            ->setCellValue("C1", "提出人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "任务类型")
            ->setCellValue("E1", "功能模块")
            ->setCellValue("F1", "标题")
            ->setCellValue("G1", "创建时间")
            ->setCellValue("H1", "产品审核通过时间")
            ->setCellValue("I1", "开发责任人")
            ->setCellValue("J1", "期望时间")
            ->setCellValue("K1", "实际开发完成时间")
            ->setCellValue("L1", "需求上线时间")
            ->setCellValue("M1", "前端预期难易度")
            ->setCellValue("N1", "后端预期难易度")
            ->setCellValue("O1", "是否需要前端")
            ->setCellValue("P1", "是否需要测试")
            ->setCellValue("Q1", "前端负责人")
            ->setCellValue("R1", "后端负责人")
            ->setCellValue("S1", "APP负责人")
            ->setCellValue("T1", "后端预计完成时间")
            ->setCellValue("U1", "是否需要后端");
        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['site']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['entry_user_name']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['site_type']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['functional_module']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['title']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['pm_confirm_time']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['copy_to_user_id']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['node_time']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['develop_finish_time']);
            //测试确认时间
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['test_confirm_time']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['web_designer_complexity']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['phper_complexity']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['web_designer_group']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['test_group']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['web_designer_user_name']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['php_user_name']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['app_user_name']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 1 + 2), $value['phper_expect_time']);
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 1 + 2), $value['phper_group']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:R' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        if ($type ==1){
            $savename = '网站需求列表' . date("YmdHis", time());
        }else{
            $savename = '网站RDC列表' . date("YmdHis", time());
        }
        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');

    }


    /**
     * RDC列表
     *
     * @Description
     * @author wpl
     * @since 2020/08/11 10:00:55 
     * @return void
     */
    public function rdc_demand_list()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            //筛选开发进度
            if ($filter['develop_finish_status1']) {
                $map['develop_finish_status'] = $filter['develop_finish_status1'];
                unset($filter['develop_finish_status1']);
            }

            //筛选测试进度
            if ($filter['test_status1']) {
                $map['test_status'] = $filter['test_status1'];
                unset($filter['test_status1']);
            }
            //筛选任务人
            if ($filter['task_user_name']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['task_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                //前端负责人id 后端负责人id 测试负责人id
                $task_map = "FIND_IN_SET({$id},web_designer_user_id)  or FIND_IN_SET({$id},phper_user_id)  or FIND_IN_SET({$id}, test_user_id)";
                unset($filter['task_user_name']);
                unset($smap['nickname']);
            }
            //筛选提出人
            if ($filter['entry_user_name']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['entry_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                $map['entry_user_id'] = $id;
                unset($filter['entry_user_name']);
                unset($smap['nickname']);
            }
            $adminId = session('admin.id');
            //我的
            $meWhere = '1=1';
            if ($filter['label'] == 1) {
                //是否是开发主管
                $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                if (in_array($adminId, $authUserIds)) {
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.php_group_person_id')) ?: [];
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},phper_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},phper_user_id)";
                        }
                    }
                }

                //是否是测试主管
                $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                if (in_array($adminId, $testAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.test_group_person_id')) ?: [];
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},test_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},test_user_id)";
                        }
                    }
                }

                //是否是前端主管
                $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                if (in_array($adminId, $webAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.web_group_person_id'));
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},web_designer_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},web_designer_user_id)";
                        }
                    }
                }

                //是否是app主管
                $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                if (in_array($adminId, $appAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.app_group_person_id'));
                    $usersId = array_merge($usersId, [$adminId]);
                    foreach ($usersId as $k => $v) {
                        if ($k == 0) {
                            $meWhere .= " and locate({$v},app_user_id)";
                        } else {
                            $meWhere .= " or locate({$v},app_user_id)";
                        }
                    }
                }

                //不是主管
                if ($meWhere === '1=1') {
                    $meWhere = "FIND_IN_SET({$adminId},web_designer_user_id) or FIND_IN_SET({$adminId},phper_user_id) or FIND_IN_SET({$adminId},app_user_id) or FIND_IN_SET({$adminId},test_user_id) or FIND_IN_SET({$adminId},entry_user_id) or FIND_IN_SET({$adminId},copy_to_user_id)";
                }
            } elseif ($filter['label'] == 2) { //未完成
                /**
                 * 其他人：展示任务状态为未激活、激活、已响应的任务
                 * 产品：展示评审状态为待审、pending的任务
                 */
                //是否为产品
                $authUserIds = array_merge(Auth::getGroupUserId(config('demand.product_group_id')), Auth::getGroupUserId(config('demand.product_group_person_id')));
                if (in_array($adminId, $authUserIds)) {
                    $map['pm_audit_status'] = ['in', [1, 2]];
                } else {
                    //非产品
                    $map['status'] = ['eq', 3];
                }
            } elseif ($filter['label'] == 3) { //BUG任务
                $map['type'] = 1;
            } elseif ($filter['label'] == 4) { //开发任务
                $map['type'] = 5;
            } elseif ($filter['label'] == 5) { //其他任务
                $map['type'] = ['in', [2, 3, 4]];
            } elseif ($filter['label'] == 6) {
                $val = 'priority';
            }
            unset($filter['label']);
            $map['demand_type'] = 2; //默认任务列表

            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->where($task_map)
                ->order($sort, $order)
                ->count();
            if ($val == 'priority') {
                $list = $this->model
                    ->where($where)
                    ->where($meWhere)
                    ->where($map)
                    ->where($task_map)
                    ->order($val, $order)
                    ->limit($offset, $limit)
                    ->select();
            } else {
                $list = $this->model
                    ->where($where)
                    ->where($meWhere)
                    ->where($map)
                    ->where($task_map)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }

            $list = collection($list)->toArray();

            //检查有没有权限
            $permissions['demand_pm_status'] = $this->auth->check('demand/it_web_demand/pm_status'); //产品确认权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add'); //新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del'); //删除权限
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution'); //开发响应
            $permissions['demand_test_handle'] = $this->auth->check('demand/it_web_demand/test_handle'); //测试响应
            $permissions['pm_status'] = $this->auth->check('demand/it_web_demand/pm_status');

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
                $list[$k]['pm_status'] = $permissions['pm_status'];

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
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * RDC 产品通过/拒绝按钮
     * */
    public function rdc_demand_pass($ids = null)
    {
        if ($this->request->isPost()) {
            $params = input();

            $row = $this->model->get($params['id']);
            $row = $row->toArray();

            if ($params['pm_audit_status'] == 4) {
                $add['pm_audit_status'] = 4;
            } else {
                $time_data = $this->start_time($row['priority'], $row['node_time']);
                $add['start_time'] = $time_data['start_time'];
                $add['end_time'] = $time_data['end_time'];
                $add['pm_audit_status'] = 3;

                /*提出人直接确认*/
                $add['pm_confirm'] = 1;
                $add['pm_confirm_time'] = date('Y-m-d H:i', time());
                $add['entry_user_confirm'] = 1;
                $add['entry_user_confirm_time'] = date('Y-m-d H:i', time());
                /*提出人直接确认*/

                $add['status'] = 2;
            }
            $add['pm_audit_status_time'] = date('Y-m-d H:i', time());
            $add['remark'] = $params['row']['remark'];//备注

            $res = $this->model->allowField(true)->save($add, ['id' => $params['id']]);
            if ($res) {

                if ($params['pm_audit_status'] == 4) {
                    Ding::cc_ding($row['entry_user_id'], '任务ID:' . $params['id'] . '+任务被拒绝', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                } else {
                    //任务评审状态变为“通过”时 推送给抄送人
                    if ($row['copy_to_user_id']) {
                        $usersId = explode(',', $row['copy_to_user_id']);
                        Ding::cc_ding($usersId, '任务ID:' . $params['id'] . '+任务已抄送给你', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    }

                    //任务激活 推送主管
                    //是否是开发主管
                    $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                    //是否是前端主管
                    $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                    //是否是app主管
                    $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                    $usersIds = array_merge($authUserIds, $webAuthUserIds, $appAuthUserIds);
                    Ding::cc_ding($usersIds, '任务ID:' . $params['id'] . '+任务激活，等待响应', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                }

                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }

        $row = $this->model->get($ids);
        $row = $row->toArray();
        $row['site_type_arr'] = explode(',', $row['site_type']);
        $row['copy_to_user_id_arr'] = explode(',', $row['copy_to_user_id']);

        $admin = new \app\admin\model\Admin();
        $userList = $admin->where('status', 'normal')->column('nickname', 'id');
        $userList = collection($userList)->toArray();

        $this->view->assign('userlist', $userList);

        $this->view->assign('demand_type', input('demand_type'));
        $this->view->assign("type", input('type'));
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = input();

            if ($params) {

                if ($params['is_user_confirm'] == 1) {
                    //提出人确认
                    $row = $this->model->get(['id' => $params['ids']]);
                    $row_arr = $row->toArray();

                    $pm_status = $this->auth->check('demand/it_web_demand/pm_status'); //产品确认权限
                    $user_id = $this->auth->id;
                    $data = array();
                    if ($pm_status) {
                        //有产品的权限，说明当前登录者是产品
                        if ($user_id == $row_arr['entry_user_id']) {
                            //如果当前用户有产品权限，又和提出人是一个人，则一次确定，全部确定
                            $data['entry_user_confirm'] = 1;
                            $data['entry_user_confirm_time'] = date('Y-m-d H:i', time());
                        }
                        $data['pm_confirm'] = 1;
                        $data['pm_confirm_time'] = date('Y-m-d H:i', time());
                    } else {
                        //没有产品的权限，还能进来这个方法，说明是运营，也就是提出人
                        $data['entry_user_confirm'] = 1;
                        $data['entry_user_confirm_time'] = date('Y-m-d H:i', time());
                    }


                    //如果当前登录人有产品确认权限，并且提出人==当前登录的人，则一个确认，就可以直接当成提出人确认&产品确认。

                    $res = $this->model->allowField(true)->save($data, ['id' => $params['ids']]);
                    if ($res) {
                        //$res = $this ->model ->get(input('ids'));
                        //Ding::dingHook('test_group_finish', $res);
                        $this->success('成功');
                    } else {
                        $this->error('失败');
                    }
                } else {
                    //正常新增
                    $data = $params['row'];

                    if ($params['demand_type'] == 2) {
                        //RDC
                        $add['demand_type'] = 2;
                        $add['priority'] = 1;
                        $add['node_time'] = $data['node_time'];
                    }
                    $add['type'] = $data['type'];
                    $add['site'] = $data['site'];
                    $add['site_type'] = implode(',', $data['site_type']);
                    $add['entry_user_id'] = $this->auth->id;
                    $add['copy_to_user_id'] = implode(',', $data['copy_to_user_id']);
                    $add['title'] = $data['title'];
                    $add['content'] = $data['content'];
                    $add['accessory'] = $data['accessory'];
                    $add['is_emergency'] = $data['is_emergency'] ? $data['is_emergency'] : 0;
                    //以下默认状态
                    $add['status'] = 1;
                    $add['create_time'] = date('Y-m-d H:i', time());
                    $add['pm_audit_status'] = 1;

                    if (!empty($data['important_reasons'])) {
                        $add['important_reasons'] = implode(',', $data['important_reasons']);
                    }
                    $add['functional_module'] = $data['functional_module'];
                    $result = $this->model->allowField(true)->save($add);

                    if ($result) {
                        //首次添加 钉钉推送产品
                        Ding::cc_ding(80, '任务ID:' . $this->model->id . '+任务等待评审', $data['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                        $this->success('添加成功');
                    } else {
                        $this->error('新增失败，请联系技术，并说明操作过程');
                    }
                }
            }
        }
        $admin = new \app\admin\model\Admin();
        $userList = $admin->where('status', 'normal')->column('nickname', 'id');
        $userList = collection($userList)->toArray();

        $this->view->assign('userlist', $userList);
        $this->view->assign('demand_type', input('demand_type'));
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
//            dump($params);die();
//            empty($params['priority']) && $this->error('数据异常');
//            empty($params['pm_audit_status']) && $this->error('数据异常,请刷新页面');
            if ($params) {
                if ($params['pm_audit_status'] == 4) {//拒绝
                    $add['pm_audit_status'] = $params['pm_audit_status'];
                    $add['pm_audit_status_time'] = date('Y-m-d H:i', time());
                } else {

                    if ($params['pm_audit_status']) {
                        //产品提交
                        $row = $this->model->get($params['id']);
                        $row = $row->toArray();
                        $add['site_type'] = implode(',', $params['site_type']);
                        if ($params['title'] !== $params['start_title'] || $params['content'] !== $params['start_content'] || $params['accessory'] !== $params['start_accessory']) {
                            $add['secondary_operation'] = 2;
                        }
                        //status  状态  1 未激活 2 激活 3 已响应 4 完成 5超时完成
                        //priority  优先级
                        //pm_audit_status  产品审核状态 1 等待审核 2pend  3通过  4拒绝
//                        if ($row['status'] == 1) {
//                            if ($params['priority'] == 1) {
//                                if ($params['pm_audit_status'] == 3) {
//                                    $add['status'] = 3;
//                                }
//                            }
//                        } else {
                        //产品要求  优先级  类型  被修改的时候  开发进度的记录会被重置功能去除
//                        if ($row['priority'] != $params['priority'] || $row['site_type'] != $add['site_type']) {
//                            $add['web_designer_group'] = 0;
//                            $add['web_designer_complexity'] = null;
//                            $add['web_designer_expect_time'] = null;
//                            $add['phper_group'] = 0;
//                            $add['phper_complexity'] = null;
//                            $add['phper_expect_time'] = null;
//                            $add['app_group'] = 0;
//                            $add['app_complexity'] = null;
//                            $add['app_expect_time'] = null;
//                            $add['develop_finish_status'] = 1;
//                        }
//                        }
                        if ($params['pm_audit_status'] == 3) {
                            $add['status'] = 3;
                        }
                        empty($params['importance']) && $this->error('请选择重要程度');
                        empty($params['degree_of_urgency']) && $this->error('请选择紧急程度');
                        empty($params['development_difficulty']) && $this->error('请选择开发难度');
                        empty($params['priority']) && $this->error('请选择优先级');
                        empty($params['node_time']) && $this->error('任务周期不能为空');
                        $add['priority'] = $params['priority'];
                        $add['node_time'] = $params['node_time'];

                        //老版本计算周期方法，摒弃掉
//                      $time_data = $this->start_time($params['priority'], $params['node_time']);
//                      $add['start_time'] = $time_data['start_time'];
//                      $add['end_time'] = $time_data['end_time'];
                        $add['node_time'] = $params['node_time'];
                        $add['pm_audit_status'] = $params['pm_audit_status'];
                        $add['pm_audit_status_time'] = date('Y-m-d H:i', time());
                    }
                    $add['product_remarks'] = $params['product_remarks'];
                    $add['type'] = $params['type'];
                    $add['site'] = $params['site'];
                    //非空
                    if (!empty($params['copy_to_user_id'])) {
                        $add['copy_to_user_id'] = implode(',', $params['copy_to_user_id']);
                    }
                    $add['title'] = $params['title'];
                    $add['content'] = $params['content'];
                    $add['remark'] = $params['remark'];
                    $add['accessory'] = $params['accessory'];
                    $add['is_emergency'] = $params['is_emergency'] ? $params['is_emergency'] : 0;
//                    if ($params['demand_type'] == 2) {
//                        $add['node_time'] = $params['node_time'];
//                    }
                    $add['functional_module'] = $params['functional_module'];
                    $add['importance'] = $params['importance'];
                    $add['degree_of_urgency'] = $params['degree_of_urgency'];
                    $add['development_difficulty'] = $params['development_difficulty'];

                    $add['priority'] = $params['priority'];
                    if (!empty($params['important_reasons'])) {
                        $add['important_reasons'] = implode(',', $params['important_reasons']);
                    }
                }
                $res = $this->model->allowField(true)->save($add, ['id' => $params['id']]);
                if ($res) {
                    //如果产品通过审核  并且该需求需要app协助
                    if ($params['pm_audit_status'] == 3 && in_array('3',$params['site_type'])) {
                        //将该需求存储到app表
                        $app_add['it_web_demand_id'] = $params['id'];//对应it_web表id
                        $app_add['site'] = $params['site'];//站点
                        $app_add['functional_module'] = $params['functional_module'];//功能模块
                        $app_add['type'] = $params['type'];//类型
                        if (!empty($params['important_reasons'])) {
                            $app_add['important_reasons'] = implode(',', $params['important_reasons']);//重要原因
                        }
                        $app_add['importance'] = $params['importance'];//重要程度
                        $app_add['degree_of_urgency'] = $params['degree_of_urgency'];//紧急程度
                        $app_add['development_difficulty'] = $params['development_difficulty'];//开发难度
                        $app_add['priority'] = $params['priority'];//优先级
                        $app_add['node_time'] = $params['node_time'];//预期时间
                        $app_add['title'] = $params['title'];//标题
                        $app_add['content'] = $params['content'];//内容
                        $app_add['product_remarks'] = $params['product_remarks'];//备注
                        $app_add['accessory'] = $params['accessory'];//附件
                        $app_add['entry_user_id'] = $params['entry_user_id'];//提出人id
                        $app_add['site_type'] = implode(',', $params['site_type']);//设备端
                        $app_add['create_time'] = date('Y-m-d H:i:s', time());//创建时间
                        if (!empty($params['copy_to_user_id'])) {
                            $app_add['copy_to_user_id'] = implode(',', $params['copy_to_user_id']);//抄送人
                        }
                        Db::name('it_app_demand')->insert($app_add);
                        //Ding::dingHook(__FUNCTION__, $this ->model ->get($params['id']));
                    }
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
                $this->error(__('Parameter %s can not be empty', ''));
            }
        }
        $row = $this->model->get($ids);
        $row = $row->toArray();
        $row['site_type_arr'] = explode(',', $row['site_type']);
        $row['copy_to_user_id_arr'] = explode(',', $row['copy_to_user_id']);
        $row['important_reasons'] = explode(',', $row['important_reasons']);
        $this->view->assign('demand_type', input('demand_type'));

        $this->view->assign("type", input('type'));
        $this->view->assign("row", $row);
        $admin = new \app\admin\model\Admin();
        $userList = $admin->where('status', 'normal')->column('nickname', 'id');
        $userList = collection($userList)->toArray();

        $this->view->assign('userlist', $userList);
        //确认权限
        $this->view->assign('pm_status', $this->auth->check('demand/it_web_demand/pm_status'));
        $this->view->assign('admin_id', session('admin.id'));
        return $this->view->fetch();
    }



    /**
     * 逻辑删除
     * */
    public function del($ids = "")
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            $data['is_del'] = 0;
            $res = $this->model->allowField(true)->save($data, ['id' => $params['id']]);
            if ($res) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    /**
     * 开发响应
     * 开发完成
     */
    public function distribution($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            //$params['type']的值，1：响应编辑，2：完成
            if ($params['type'] == 2) {
                $update = array();
                if ($params['web_status'] == 1) {
                    $update['web_designer_is_finish'] = 1;
                    $update['web_designer_finish_time'] = date('Y-m-d H:i', time());
                }

                if ($params['php_status'] == 1) {
                    $update['phper_is_finish'] = 1;
                    $update['phper_finish_time'] = date('Y-m-d H:i', time());
                }

                if ($params['app_status'] == 1) {
                    $update['app_is_finish'] = 1;
                    $update['app_finish_time'] = date('Y-m-d H:i', time());
                }

                $res = $this->model->allowField(true)->save($update, ['id' => $params['id']]);
                if ($res) {
                    $flag = 0; //需要几个组进行处理本需求
                    $num = 0; //已经几个组完成了本需求
                    //判断状态
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();

                    if ($row_arr['web_designer_group'] == 1) {
                        $flag += 1;
                        if ($row_arr['web_designer_is_finish'] == 1) {
                            $num += 1;
                        }
                    }
                    if ($row_arr['phper_group'] == 1) {
                        $flag += 1;
                        if ($row_arr['phper_is_finish'] == 1) {
                            $num += 1;
                        }
                    }
                    if ($row_arr['app_group'] == 1) {
                        $flag += 1;
                        if ($row_arr['app_is_finish'] == 1) {
                            $num += 1;
                        }
                    }

                    if ($flag == $num) {
                        //如果全部完成，则更新本条目状态
                        $update = array();
                        $update['develop_finish_status'] = 3;
                        $update['develop_finish_time'] = date('Y-m-d H:i', time());
                        $update['test_status'] = 3;
                        $this->model->allowField(true)->save($update, ['id' => $params['id']]);

                        //任务完成 钉钉推送抄送人 提出人
                        Ding::cc_ding($row->entry_user_id, '任务ID:' . $params['id'] . '+任务已完成', $row->title, $this->request->domain() . url('index') . '?ref=addtabs');

                        if ($row->copy_to_user_id) {
                            $usersId = explode(',', $row->copy_to_user_id);
                            Ding::cc_ding($usersId, '任务ID:' . $params['id'] . '+任务已完成', $row->title, $this->request->domain() . url('index') . '?ref=addtabs');
                        }
                        //测试主管
                        //$testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                        $testAuthUserIds = config('demand.test_user');
                        Ding::cc_ding($testAuthUserIds, '任务ID:' . $params['id'] . '+任务已完成，等待测试', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    }

                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }

            if ($params['type'] == 1) {
                $update = array();
                if ($params['web_status'] == 1) {
                    if (!$params['web_designer_group']) {
                        $this->error('需求响应必选');
                    }
                    $update['web_designer_group'] = $params['web_designer_group'];
                    if ($params['web_designer_group'] == 1) {
                        if (!$params['web_designer_expect_time']) {
                            $this->error('计划完成时间必选');
                        }
                        $update['web_designer_expect_time'] = $params['web_designer_expect_time'] . ' 22:00:00';
                        if (!$params['web_designer_complexity']) {
                            $this->error('预期难度必选');
                        }
                        $update['web_designer_complexity'] = $params['web_designer_complexity'];
                    } else {
                        $update['web_designer_expect_time'] = null;
                        $update['web_designer_complexity'] = null;
                    }
                }

                if ($params['php_status'] == 1) {
                    if (!$params['phper_group']) {
                        $this->error('需求响应必选');
                    }
                    $update['phper_group'] = $params['phper_group'];
                    if ($params['phper_group'] == 1) {
                        if (!$params['phper_expect_time']) {
                            $this->error('计划完成时间必选');
                        }
                        $update['phper_expect_time'] = $params['phper_expect_time'] . ' 22:00:00';
                        if (!$params['phper_complexity']) {
                            $this->error('预期难度必选');
                        }
                        $update['phper_complexity'] = $params['phper_complexity'];
                    } else {
                        $update['phper_expect_time'] = null;
                        $update['phper_complexity'] = null;
                    }
                }

                if ($params['app_status'] == 1) {
                    if (!$params['app_group']) {
                        $this->error('需求响应必选');
                    }
                    $update['app_group'] = $params['app_group'];
                    if ($params['app_group'] == 1) {
                        if (!$params['app_expect_time']) {
                            $this->error('计划完成时间必选');
                        }
                        $update['app_expect_time'] = $params['app_expect_time'] . ' 22:00:00';
                        if (!$params['app_complexity']) {
                            $this->error('预期难度必选');
                        }
                        $update['app_complexity'] = $params['app_complexity'];
                    } else {
                        $update['app_expect_time'] = null;
                        $update['app_complexity'] = null;
                    }
                }
                $update['status'] = 3;
                $res = $this->model->allowField(true)->save($update, ['id' => $params['id']]);
                if ($res) {
                    //确认后 钉钉通知
                    $row = $this->model->get(['id' => $params['id']]);
                    $info = $row->toArray();
                    if ($params['web_status'] == 1 || $params['php_status'] == 1 || $params['app_status'] == 1) {
                        Ding::cc_ding($info['entry_user_id'], '任务ID:' . $params['id'] . '+任务已被确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    }
                    //判断是否达到下一个阶段的状态
                    $develop_finish_status = array();
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();
                    if ($row_arr['develop_finish_status'] == 1 && $row_arr['status'] == 3) {
                        if (strpos($row_arr['site_type'], '3') !== false) {
                            if ($row_arr['web_designer_group'] != 0 && $row_arr['phper_group'] != 0 && $row_arr['app_group'] != 0) {
                                //可以进入下一个状态
                                $develop_finish_status['develop_finish_status'] = 2;
                                $develop_finish_status['status'] = 3;
                                $this->model->allowField(true)->save($develop_finish_status, ['id' => $params['id']]);
                            }
                        } else {
                            if ($row_arr['web_designer_group'] != 0 && $row_arr['phper_group'] != 0) {
                                //可以进入下一个状态
                                $develop_finish_status['develop_finish_status'] = 2;
                                $develop_finish_status['status'] = 3;
                                $this->model->allowField(true)->save($develop_finish_status, ['id' => $params['id']]);
                            }
                        }
                    }

                    //开发中
                    if ($develop_finish_status['develop_finish_status'] == 2) {
                        //测试主管
                        $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                        Ding::cc_ding($testAuthUserIds, '任务ID:' . $params['id'] . '+任务等待确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    }
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
        }

        $ids = $ids ?? input('ids');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
//        dump($row_arr);die();
        $row_arr['start_time'] = date('Y-m-d', strtotime($row_arr['start_time']));
        $row_arr['end_time'] = date('Y-m-d', strtotime($row_arr['end_time']));

        $status = array(
            1 => '确认',
            2 => '不涉及',
        );

        $this->view->assign("distribution_status", $this->auth->check('demand/it_web_demand/distribution'));
        $this->view->assign("status", $status);
        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }

    /**
     * 开发响应
     *开发拒绝
     */
    public function distriRefuse()
    {
        if ($this->request->isAjax()) {
            $params = input('param.');
            $updateValue['develop_finish_status'] = 4;
            if ($params['status'] == 1) {
                $updateValue['web_remarks'] = $params['remarks'];
                $updateValue['web_designer_group'] = 2;

            } elseif ($params['status'] == 2) {
                $updateValue['phper_remarks'] = $params['remarks'];
                $updateValue['phper_group'] = 2;
            } else {
                $updateValue['app_remarks'] = $params['remarks'];
                $updateValue['app_group'] = 2;
            }
            $save = $this->model->where('id=' . $params['ids'])->update($updateValue);
            if ($save) {
                $row = $this->model->get(['id' => $params['ids']]);
                $info = $row->toArray();
                Ding::cc_ding($info['entry_user_id'], '任务ID:' . $params['ids'] . '任务已被拒绝,', '任务标题：' . $info['title'] . ',拒绝原因：' . $params['remarks'], $this->request->domain() . url('index') . '?ref=addtabs');
                $this->success('成功');
            } else {
                $this->success('失败');
            }
        }

    }

    /**
     * 测试确认--通过--上线
     * 测试权限
     */
    public function test_handle($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                //查看该需求是否被确认过
                $row = $this->model->get(['id' => $params['id']])->toArray();
                if ($row['web_designer_group'] == 0 && $row['phper_group'] == 0 && $row['app_group'] == 0) {
                    $this->error('开发还未确认，暂时无法操作');
                }
                $update = array();
                $label = 0;
                if ($params['type'] == 'queren') {

                    $update['test_group'] = $params['test_group'];
                    $update['test_status'] = 2;
                    $update['test_confirm_time'] = date('Y-m-d H:i', time());
                }

                if ($params['type'] == 'tongguo') {
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();

                    if (!$params['status']) {
                        $this->error('点太快啦，请等页面加载完成在点击。');
                    }
                    if ($params['status'] == 1) {
                        //通过
                        if ($params['test_group'] == 1 || $params['test_group'] == 2) {
                            $update['test_is_finish'] = 1;
                            $update['test_finish_time'] = date('Y-m-d H:i', time());
                        }
                        $update['test_status'] = 4;

                        $time = date('Y-m-d H:i', time());
//                        if ($time > $row_arr['end_time']) {
                        if ($time > $row_arr['node_time']) {
                            $update['status'] = 5;
                        } else {
                            $update['status'] = 4;
                        }

                        $label = 1;
                    } else {
                        //未通过
                        if ($row_arr['web_designer_group'] == 1) {
                            $update['web_designer_is_finish'] = 0;
                            $update['web_designer_finish_time'] = null;
                        }
                        if ($row_arr['phper_group'] == 1) {
                            $update['phper_is_finish'] = 0;
                            $update['phper_finish_time'] = null;
                        }
                        if ($row_arr['app_group'] == 1) {
                            $update['app_is_finish'] = 0;
                            $update['app_finish_time'] = null;
                        }
                        $update['develop_finish_status'] = 2;
                        $update['develop_finish_time'] = null;

                        $label = 2;
                    }
                }


                if ($params['type'] == 'shangxian') {
                    /*$row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();*/

                    $time = date('Y-m-d H:i', time());
                    $update['all_finish_time'] = $time;

                    $update['test_status'] = 5;

                    $label = 3;
                }

                $res = $this->model->allowField(true)->save($update, ['id' => $params['id']]);
                if ($res) {
                    //未通过 推送给主管
                    if ($label == 2) {
                        //任务激活 推送主管
                        //是否是开发主管
                        $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                        //是否是前端主管
                        $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                        //是否是app主管
                        $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                        $usersIds = array_merge($authUserIds, $webAuthUserIds, $appAuthUserIds);
                        Ding::cc_ding($usersIds, '任务ID:' . $params['id'] . '+测试未通过', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    } elseif ($label == 3) { //任务上线 通知提出人
                        //任务上线 通知提出人 + 抄送人
                        if (!empty($row['copy_to_user_id'])) {
                            $copy_to_user_id = explode(',', $row['copy_to_user_id']);
                            if (in_array($row['entry_user_id'], $copy_to_user_id)) {
                                $recipient = $copy_to_user_id;
                            } else {
                                $copy_to_user_id[] = $row['entry_user_id'];
                                $recipient = $copy_to_user_id;
                            }
                            Ding::cc_ding($recipient, '任务ID:' . $params['id'] . '+任务已上线，等待确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                        } else {
                            Ding::cc_ding($row['entry_user_id'], '任务ID:' . $params['id'] . '+任务已上线，等待确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                        }

//                        Ding::cc_ding($row['entry_user_id'], '任务ID:' . $params['id'] . '+任务已上线，等待确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    } else if ($label == 1) {
                        //测试通过 推送给对应开发者
                        $user_all = array_merge(array_filter(array($row['app_user_id'], $row['phper_user_id'], $row['web_designer_user_id'])));
                        Ding::cc_ding($user_all, '任务ID:' . $params['id'] . '+测试已通过，等待上线', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    }

                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $ids = $ids ?? input('ids');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();

        $row_arr['start_time'] = date('Y-m-d', strtotime($row_arr['start_time']));
        $row_arr['end_time'] = date('Y-m-d', strtotime($row_arr['end_time']));

        $time_arr = array();
        if ($row_arr['web_designer_group'] == 1) {
            $time_arr[] = strtotime($row_arr['web_designer_expect_time']);
        }
        if ($row_arr['phper_group'] == 1) {
            $time_arr[] = strtotime($row_arr['phper_expect_time']);
        }
        if ($row_arr['app_group'] == 1) {
            $time_arr[] = strtotime($row_arr['app_expect_time']);
        }
        rsort($time_arr);

//        $day_num = strtotime($row_arr['end_time']) - $time_arr[0];
        $day_num = strtotime($row_arr['node_time']) - $time_arr[0];

        if ($day_num < 0) {
            $day = 0;
        } else {
            $day = ceil($day_num / (3600 * 24));
        }

        $status = array(
            1 => '确认任务',
            2 => '不需测试',
        );
        $this->view->assign("status", $status);
        $this->view->assign("day", $day);
        $this->view->assign("row", $row_arr);

        //确认权限
        $user_status = 0;
        if ($row_arr['test_user_id']) {
            if (in_array($this->auth->id, explode(',', $row_arr['test_user_id']))) {
                $user_status = 1;
            }
        }
        $this->view->assign("user_status", $user_status);
        $this->view->assign("test_status", $this->auth->check('demand/it_web_demand/test_handle'));

        return $this->view->fetch();
    }

    /**
     * 查看详情
     * 包含操作：标记异常，分配
     * */
    public function detail($ids = null)
    {
        if ($this->request->isAjax()) {
            $params = $this->request->post();
            $row = $this->model->get($ids);
            if ($params) {
                $update['is_small_probability'] = $params['is_small_probability'];
                $update['is_low_level_error'] = $params['is_low_level_error'];
                $update['is_difficult'] = $params['is_difficult'];
                $update['web_designer_user_id'] = $params['web_designer_user_id'] ? implode(',', $params['web_designer_user_id']) : null;
                $update['phper_user_id'] = $params['phper_user_id'] ? implode(',', $params['phper_user_id']) : null;
                $update['app_user_id'] = $params['app_user_id'] ? implode(',', $params['app_user_id']) : null;
                $update['test_user_id'] = $params['test_user_id'] ? implode(',', $params['test_user_id']) : null;

                $res = $this->model->allowField(true)->save($update, ['id' => $params['id']]);
                if ($res) {
                    $web_designer_user_id = $params['web_designer_user_id'] ?: [];
                    $phper_user_id = $params['phper_user_id'] ?: [];
                    $app_user_id = $params['app_user_id'] ?: [];
                    $test_user_id = $params['test_user_id'] ?: [];
                    $usersIds = array_merge($web_designer_user_id, $phper_user_id, $app_user_id, $test_user_id);
                    Ding::cc_ding($usersIds, '任务ID:' . $params['id'] . '+任务已分配', $row->title, $this->request->domain() . url('index') . '?ref=addtabs');
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $row = $this->model->get($ids);
        $row = $row->toArray();
        $row['web_designer_user_id'] = explode(',', $row['web_designer_user_id']);
        $row['phper_user_id'] = explode(',', $row['phper_user_id']);
        $row['app_user_id'] = explode(',', $row['app_user_id']);
        $row['test_user_id'] = explode(',', $row['test_user_id']);

        //获取各组人员
        $authgroup = new AuthGroup();

        //获取php组长&组员
        $php_group_ids = $authgroup->getChildrenIds(config('demand.php_group_id'));
        $p_id[] = config('demand.php_group_id');
        $php_group_ids = array_merge($php_group_ids, $p_id);
        $php_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $php_group_ids)
            ->where('status', 'normal')
            ->column('nickname', 'id');

        //获取web组长&组员
        $web_group_ids = $authgroup->getChildrenIds(config('demand.web_group_id'));
        $w_id[] = config('demand.web_group_id');
        $web_group_ids = array_merge($web_group_ids, $w_id);
        $web_users = Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $web_group_ids)
            ->where('status', 'normal')
            ->column('nickname', 'id');

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
            ->column('nickname', 'id');

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
            ->column('nickname', 'id');

        //获取评论--测试站
        $test_review = Db::name("it_web_demand_review")
            ->where('type', 1)
            ->where('pid', $ids)
            ->select();
        //获取评论--正式站
        $review = Db::name("it_web_demand_review")
            ->where('type', 2)
            ->where('pid', $ids)
            ->select();

        //确认权限
        $this->view->assign("test_status", $this->auth->check('demand/it_web_demand/test_handle')); //测试分配权限
        $this->view->assign("distribution_status", $this->auth->check('demand/it_web_demand/distribution')); //开发分配权限

        $this->view->assign('php_users', $php_users);
        $this->view->assign('web_users', $web_users);
        $this->view->assign('app_users', $app_users);
        $this->view->assign('test_users', $test_users);
        $this->view->assign('test_review', $test_review);
        $this->view->assign('review', $review);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 查看详情--评论
     * 任何人都有权限
     * */
    public function demand_review()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->post();
            if ($params) {
                if ($params['content'] == '') {
                    $this->error('内容不能为空');
                }

                $update['pid'] = $params['pid'];
                $update['type'] = $params['type'];

                $users = Db::name("auth_group_access")
                    ->alias("aga")
                    ->join("auth_group ag", "aga.group_id=ag.id")
                    ->field("ag.*")
                    ->where('aga.uid', $this->auth->id)
                    ->find();
                $update['group_id'] = $users['id'];
                $update['group_name'] = $users['name'];
                $update['user_id'] = $this->auth->id;
                $update['user_name'] = $this->auth->nickname;
                $update['content'] = $params['content'];
                $update['create_time'] = date('Y-m-d H:i:s', time());

                $res = $this->ItWebDemandReview->allowField(true)->save($update);
                if ($res) {

                    //Ding::dingHook(__FUNCTION__, $this ->model ->get($params['id']));
                    $this->success('成功', $url = null, $update);
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }


}



