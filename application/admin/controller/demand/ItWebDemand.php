<?php

namespace app\admin\controller\demand;

use app\api\controller\Ding;
use app\common\controller\Backend;
use app\common\model\Auth;
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
    protected $noNeedRight=['del','distribution','test_handle','detail','demand_review','del','edit','rdc_demand_pass'];  //解决创建人无删除权限问题 暂定
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
    public function start_time($priority,$node_time){
        $day_17 = mktime(17,0,0,date('m'),date('d'),date('Y'));//当天5点
        $week_17 = strtotime ("+17 hour", strtotime("friday"));//本周5，下午5点

        $data = array();
        switch ($priority){
            case 1:
                $data['start_time'] = date('Y-m-d H:i',time());
                $data['end_time'] = date('Y-m-d H:i',strtotime('+'.$node_time.'day'));
                break;
            case 2:
                $data['start_time'] = date('Y-m-d H:i',$day_17);
                $data['end_time'] = date('Y-m-d H:i',strtotime ("+".$node_time." day", $day_17));
                break;
            case 3:
                $data['start_time'] = date('Y-m-d H:i',$week_17);
                $data['end_time'] = date('Y-m-d H:i',strtotime ("+".$node_time." day", $week_17));
                break;
            case 4:
                $data['start_time'] = date('Y-m-d H:i',$week_17);
                $data['end_time'] = date('Y-m-d H:i',strtotime ("+".$node_time." day", $week_17));
                break;
            case 5:
                $data['start_time'] = date('Y-m-d H:i',$week_17);
                $data['end_time'] = date('Y-m-d H:i',strtotime ("+".$node_time." day", $week_17));
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
    public function extract_username($user_id,$config_name){
        $user_id_arr = explode(',',$user_id);
        $user_name_arr = array();
        foreach ($user_id_arr as $v){
            $user_name_arr[] = config('demand.'.$config_name)[$v];
        }
        $user_name = implode(',',$user_name_arr);
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
        $time_update['status'] = 2;
        $time_update['demand_type'] = 1;
        $time = date('Y-m-d H:i',time());
        $this->model->allowField(true)->save($time_update, ['start_time' => ['elt', $time],'status'=>1,'pm_audit_status'=>3]);

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
           
            //筛选提出人
            if ($filter['entry_user_name']){
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['entry_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                $map['entry_user_id'] = $id;
                unset($filter['entry_user_name']);
                unset($smap['nickname']);
            }
            $adminId = session('admin.id');
            //我的
            if($filter['label'] == 1){
                //是否是开发主管
                $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                if (in_array($adminId, $authUserIds)) {
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.php_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(phper_user_id,{$usersIdStr})";    
                }

                //是否是测试主管
                $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                if (in_array($adminId, $testAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.test_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(test_user_id,{$usersIdStr})";    
                }

                //是否是前端主管
                $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                if (in_array($adminId, $webAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.web_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(web_designer_user_id,{$usersIdStr})";    
                }

                //是否是app主管
                $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                if (in_array($adminId, $appAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.app_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(app_user_id,{$usersIdStr})";    
                }

                //不是主管
                if (!$meWhere) {
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
                    $map['status'] = ['in', [1, 2, 3]];
                }
            } elseif ($filter['label'] == 3) { //BUG任务
                $map['type'] = 1;
            } elseif ($filter['label'] == 4) { //开发任务
                $map['type'] = 5;
            } elseif ($filter['label'] == 5) { //其他任务
                $map['type'] = ['in', [2, 3, 4]];
            } 
            unset($filter['label']);
            $map['demand_type'] = 1; //默认任务列表
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            //检查有没有权限
            $permissions['demand_pm_status'] = $this->auth->check('demand/it_web_demand/pm_status');//产品确认权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add');//新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del');//删除权限
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution');//开发响应
            $permissions['demand_test_handle'] = $this->auth->check('demand/it_web_demand/test_handle');//测试响应


            foreach ($list as $k => $v){
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人
                $list[$k]['detail'] = '';//前台调用详情字段使用，并无实际意义

                $list[$k]['create_time'] = date('Y-m-d H:i',strtotime($v['create_time']));
                $list[$k]['develop_finish_time'] = date('Y-m-d H:i',strtotime($v['develop_finish_time']));
                $list[$k]['test_finish_time'] = date('Y-m-d H:i',strtotime($v['test_finish_time']));
                $list[$k]['all_finish_time'] = date('Y-m-d H:i',strtotime($v['all_finish_time']));
                $list[$k]['node_time'] = $v['node_time']?$v['node_time'].'Day':'-';//预计时间
                //检查权限
                $list[$k]['demand_pm_status'] = $permissions['demand_pm_status'];//产品确认权限
                $list[$k]['demand_add'] = $permissions['demand_add'];//新增权限
                $list[$k]['demand_del'] = $permissions['demand_del'];//删除权限
                $list[$k]['demand_distribution'] = $permissions['demand_distribution'];//开发响应
                $list[$k]['demand_test_handle'] = $permissions['demand_test_handle'];//测试响应

                //获取各组负责人
                $list[$k]['web_designer_user_name'] = '';
                if($v['web_designer_user_id']){
                    //获取php组长&组员
                    $web_userid_arr = explode(',',$v['web_designer_user_id']);
                    $web_users =  Db::name("admin")
                        ->whereIn("id", $web_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['web_designer_user_name'] = $web_users;
                }

                $list[$k]['php_user_name'] = '';
                if($v['phper_user_id']){
                    //获取php组长&组员
                    $php_userid_arr = explode(',',$v['phper_user_id']);
                    $php_users =  Db::name("admin")
                        ->whereIn("id", $php_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['php_user_name'] = $php_users;
                }

                $list[$k]['app_user_name'] = '';
                if($v['app_user_id']){
                    //获取php组长&组员
                    $app_userid_arr = explode(',',$v['app_user_id']);
                    $app_users =  Db::name("admin")
                        ->whereIn("id", $app_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['app_user_name'] = $app_users;
                }

                $list[$k]['test_user_name'] = '';
                if($v['test_user_id']){
                    //获取php组长&组员
                    $test_userid_arr = explode(',',$v['test_user_id']);
                    $test_users =  Db::name("admin")
                        ->whereIn("id", $test_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['test_user_name'] = $test_users;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
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
           
            //筛选提出人
            if ($filter['entry_user_name']){
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['entry_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                $map['entry_user_id'] = $id;
                unset($filter['entry_user_name']);
                unset($smap['nickname']);
            }
            $adminId = session('admin.id');
            //我的
            if($filter['label'] == 1){
                //是否是开发主管
                $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                if (in_array($adminId, $authUserIds)) {
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.php_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(phper_user_id,{$usersIdStr})";    
                }

                //是否是测试主管
                $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                if (in_array($adminId, $testAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.test_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(test_user_id,{$usersIdStr})";    
                }

                //是否是前端主管
                $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                if (in_array($adminId, $webAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.web_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(web_designer_user_id,{$usersIdStr})";    
                }

                //是否是app主管
                $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                if (in_array($adminId, $appAuthUserIds)) {
                    $usersId = [];
                    //组员ID
                    $usersId = Auth::getGroupUserId(config('demand.app_group_person_id'));
                    $usersId = array_merge($usersId, $adminId);
                    $usersIdStr = implode(',', $usersId);
                    $meWhere = "FIND_PART_IN_SET(app_user_id,{$usersIdStr})";    
                }

                //不是主管
                if (!$meWhere) {
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
                    $map['status'] = ['in', [1, 2, 3]];
                }
            } elseif ($filter['label'] == 3) { //BUG任务
                $map['type'] = 1;
            } elseif ($filter['label'] == 4) { //开发任务
                $map['type'] = 5;
            } elseif ($filter['label'] == 5) { //其他任务
                $map['type'] = ['in', [2, 3, 4]];
            } 
            unset($filter['label']);
            $map['demand_type'] = 2; //默认任务列表

            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            //检查有没有权限
            $permissions['demand_pm_status'] = $this->auth->check('demand/it_web_demand/pm_status');//产品确认权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add');//新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del');//删除权限
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution');//开发响应
            $permissions['demand_test_handle'] = $this->auth->check('demand/it_web_demand/test_handle');//测试响应
            $permissions['pm_status'] = $this->auth->check('demand/it_web_demand/pm_status');

            foreach ($list as $k => $v){
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人
                $list[$k]['detail'] = '';//前台调用详情字段使用，并无实际意义

                $list[$k]['create_time'] = date('m-d H:i',strtotime($v['create_time']));
                $list[$k]['node_time'] = $v['node_time']?$v['node_time'].'Day':'-';//预计时间
                //检查权限
                $list[$k]['demand_pm_status'] = $permissions['demand_pm_status'];//产品确认权限
                $list[$k]['demand_add'] = $permissions['demand_add'];//新增权限
                $list[$k]['demand_del'] = $permissions['demand_del'];//删除权限
                $list[$k]['demand_distribution'] = $permissions['demand_distribution'];//开发响应
                $list[$k]['demand_test_handle'] = $permissions['demand_test_handle'];//测试响应
                $list[$k]['pm_status'] = $permissions['pm_status'];

                //获取各组负责人
                $list[$k]['web_designer_user_name'] = '';
                if($v['web_designer_user_id']){
                    //获取php组长&组员
                    $web_userid_arr = explode(',',$v['web_designer_user_id']);
                    $web_users =  Db::name("admin")
                        ->whereIn("id", $web_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['web_designer_user_name'] = $web_users ? implode(',',$web_users) : '-';
                }

                $list[$k]['php_user_name'] = '';
                if($v['phper_user_id']){
                    //获取php组长&组员
                    $php_userid_arr = explode(',',$v['phper_user_id']);
                    $php_users =  Db::name("admin")
                        ->whereIn("id", $php_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['php_user_name'] = $php_users ? implode(',',$php_users) : '-';
                }

                $list[$k]['app_user_name'] = '';
                if($v['app_user_id']){
                    //获取php组长&组员
                    $app_userid_arr = explode(',',$v['app_user_id']);
                    $app_users =  Db::name("admin")
                        ->whereIn("id", $app_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['app_user_name'] = $app_users ? implode(',',$app_users) : '-';
                }

                $list[$k]['test_user_name'] = '';
                if($v['test_user_id']){
                    //获取php组长&组员
                    $test_userid_arr = explode(',',$v['test_user_id']);
                    $test_users =  Db::name("admin")
                        ->whereIn("id", $test_userid_arr)
                        ->column('nickname','id');
                    $list[$k]['test_user_name'] = $test_users ? implode(',',$test_users) : '-';
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * RDC 产品通过按钮
     * */
    public function rdc_demand_pass(){
        if ($this->request->isPost()) {
            $params = input();

            $row = $this->model->get($params['ids']);
            $row = $row->toArray();

            $time_data = $this->start_time($row['priority'],$row['node_time']);
            $add['start_time'] = $time_data['start_time'];
            $add['end_time'] = $time_data['end_time'];

            $add['pm_audit_status'] = 3;
            $add['pm_audit_status_time'] = date('Y-m-d H:i',time());

            /*提出人直接确认*/
            $add['pm_confirm'] = 1;
            $add['pm_confirm_time'] = date('Y-m-d H:i',time());
            $add['entry_user_confirm'] = 1;
            $add['entry_user_confirm_time'] = date('Y-m-d H:i',time());
            /*提出人直接确认*/

            $add['status'] = 2;
            $res = $this->model->allowField(true)->save($add,['id'=> $params['ids']]);
            if ($res) {
                //任务评审状态变为“通过”时 推送给抄送人
                if ($row['copy_to_user_id']) {
                    $usersId = explode(',',$row['copy_to_user_id']);
                    Ding::cc_ding($usersId,  '任务ID:' . $params['ids'] . '+任务已抄送给你', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                }

                //任务激活 推送主管
                //是否是开发主管
                $authUserIds = Auth::getGroupUserId(config('demand.php_group_id')) ?: [];
                //是否是前端主管
                $webAuthUserIds = Auth::getGroupUserId(config('demand.web_group_id')) ?: [];
                //是否是app主管
                $appAuthUserIds = Auth::getGroupUserId(config('demand.app_group_id')) ?: [];
                $usersIds = array_merge($authUserIds, $webAuthUserIds, $appAuthUserIds);
                Ding::cc_ding($usersIds,  '任务ID:' . $params['ids'] . '+任务激活，等待响应', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');

                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = input();

            if($params){
                if($params['is_user_confirm'] == 1){
                    //提出人确认
                    $row = $this->model->get(['id' => $params['ids']]);
                    $row_arr = $row->toArray();

                    $pm_status = $this->auth->check('demand/it_web_demand/pm_status');//产品确认权限
                    $user_id = $this->auth->id;
                    $data = array();
                    if($pm_status){
                        //有产品的权限，说明当前登录者是产品
                        if($user_id == $row_arr['entry_user_id']){
                            //如果当前用户有产品权限，又和提出人是一个人，则一次确定，全部确定
                            $data['entry_user_confirm'] =  1;
                            $data['entry_user_confirm_time'] =  date('Y-m-d H:i',time());
                        }
                        $data['pm_confirm'] =  1;
                        $data['pm_confirm_time'] =  date('Y-m-d H:i',time());
                    }else{
                        //没有产品的权限，还能进来这个方法，说明是运营，也就是提出人
                        $data['entry_user_confirm'] =  1;
                        $data['entry_user_confirm_time'] =  date('Y-m-d H:i',time());
                    }

                    //如果当前登录人有产品确认权限，并且提出人==当前登录的人，则一个确认，就可以直接当成提出人确认&产品确认。

                    $res = $this->model->allowField(true)->save($data,['id'=> $params['ids']]);
                    if ($res) {
                        //$res = $this ->model ->get(input('ids'));
                        //Ding::dingHook('test_group_finish', $res);
                        $this->success('成功');
                    } else {
                        $this->error('失败');
                    }

                }else{
                    //正常新增
                    $data = $params['row'];

                    if($params['demand_type'] == 2){
                        //RDC
                        $add['demand_type'] = 2;
                        $add['priority'] = 1;
                        $add['node_time'] = $data['node_time'];
                    }
                    $add['type'] = $data['type'];
                    $add['site'] = $data['site'];
                    $add['site_type'] = implode(',',$data['site_type']);
                    $add['entry_user_id'] = $this->auth->id;
                    $add['copy_to_user_id'] = implode(',',$data['copy_to_user_id']);
                    $add['title'] = $data['title'];
                    $add['content'] = $data['content'];
                    $add['accessory'] = $data['accessory'];
                    $add['is_emergency'] = $data['is_emergency'] ? $data['is_emergency'] : 0;
                    //以下默认状态
                    $add['status'] = 1;
                    $add['create_time'] = date('Y-m-d H:i',time());
                    $add['pm_audit_status'] = 1;
                    $result = $this->model->allowField(true)->save($add);

                    if($result){
                        //首次添加 钉钉推送产品
                        Ding::cc_ding(80,  '任务ID:' . $this->model->id . '+任务等待评审', $data['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                        $this->success('添加成功');
                    }else{
                        $this->error('新增失败，请联系技术，并说明操作过程');
                    }
                }
            }
        }

        $this->view->assign('demand_type',input('demand_type'));
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                if($params['pm_audit_status']){
                    //产品提交
                    $row = $this->model->get($params['id']);
                    $row = $row->toArray();
                    $add['site_type'] = implode(',',$params['site_type']);

                    if($row['status'] == 1){
                        if($params['priority'] == 1){
                            if($params['pm_audit_status'] == 3){
                                $add['status'] = 2;
                            }
                        }
                    }else{
                        if($row['priority'] != $params['priority'] || $row['node_time'] != $params['node_time'] || $row['site_type'] != $add['site_type']){
                            $add['status'] = 2;

                            $add['web_designer_group'] = 0;
                            $add['web_designer_complexity'] = null;
                            $add['web_designer_expect_time'] = null;

                            $add['phper_group'] = 0;
                            $add['phper_complexity'] = null;
                            $add['phper_expect_time'] = null;

                            $add['app_group'] = 0;
                            $add['app_complexity'] = null;
                            $add['app_expect_time'] = null;

                            $add['develop_finish_status'] = 1;
                        }
                    }

                    $add['priority'] = $params['priority'];

                    $add['node_time'] = $params['node_time'];
                    $time_data = $this->start_time($params['priority'],$params['node_time']);
                    $add['start_time'] = $time_data['start_time'];
                    $add['end_time'] = $time_data['end_time'];
                    $add['pm_audit_status'] = $params['pm_audit_status'];
                    $add['pm_audit_status_time'] = date('Y-m-d H:i',time());
                }
                $add['type'] = $params['type'];
                $add['site'] = $params['site'];

                $add['copy_to_user_id'] = implode(',',$params['copy_to_user_id']);
                $add['title'] = $params['title'];
                $add['content'] = $params['content'];
                $add['accessory'] = $params['accessory'];
                $add['is_emergency'] = $params['is_emergency'] ? $params['is_emergency'] : 0;
                if($params['demand_type'] == 2){
                    $add['node_time'] = $params['node_time'];
                }
                $res = $this->model->allowField(true)->save($add,['id'=> $params['id']]);
                if ($res) {
                    //Ding::dingHook(__FUNCTION__, $this ->model ->get($params['id']));
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $row = $this->model->get($ids);
        $row = $row->toArray();
        $row['site_type_arr'] = explode(',',$row['site_type']);
        $row['copy_to_user_id_arr'] = explode(',',$row['copy_to_user_id']);

        $this->view->assign('demand_type',input('demand_type'));
        $this->view->assign("type", input('type'));
        $this->view->assign("row", $row );

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

            $data['is_del'] =  0;
            $res = $this->model->allowField(true)->save($data,['id'=> $params['id']]);
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
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            //$params['type']的值，1：响应编辑，2：完成
            if($params['type'] == 2){
                $update = array();
                if($params['web_status'] == 1){
                    $update['web_designer_is_finish'] = 1;
                    $update['web_designer_finish_time'] =  date('Y-m-d H:i',time());
                }

                if($params['php_status'] == 1){
                    $update['phper_is_finish'] = 1;
                    $update['phper_finish_time'] =  date('Y-m-d H:i',time());
                }

                if($params['app_status'] == 1){
                    $update['app_is_finish'] = 1;
                    $update['app_finish_time'] =  date('Y-m-d H:i',time());
                }

                $res = $this->model->allowField(true)->save($update,['id'=> $params['id']]);
                if($res){
                    $flag = 0;//需要几个组进行处理本需求
                    $num = 0;//已经几个组完成了本需求
                    //判断状态
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();

                    if($row_arr['web_designer_group'] == 1){
                        $flag += 1;
                        if($row_arr['web_designer_is_finish'] == 1){
                            $num += 1;
                        }
                    }
                    if($row_arr['phper_group'] == 1){
                        $flag += 1;
                        if($row_arr['phper_is_finish'] == 1){
                            $num += 1;
                        }
                    }
                    if($row_arr['app_group'] == 1){
                        $flag += 1;
                        if($row_arr['app_is_finish'] == 1){
                            $num += 1;
                        }
                    }

                    if($flag == $num){
                        //如果全部完成，则更新本条目状态
                        $update = array();
                        $update['develop_finish_status'] = 3;
                        $update['develop_finish_time'] = date('Y-m-d H:i',time());
                        $update['test_status'] = 3;
                        $this->model->allowField(true)->save($update,['id'=> $params['id']]);

                        //任务完成 钉钉推送抄送人 提出人
                        Ding::cc_ding($row->entry_user_id,  '任务ID:' . $params['id'] . '+任务已上线，等待确认', $row->title, $this->request->domain() . url('index') . '?ref=addtabs');
                        if ($row->copy_to_user_id) {
                            $usersId = explode(',',$row->copy_to_user_id);
                            Ding::cc_ding($usersId,  '任务ID:' . $params['id'] . '+任务已完成', $row->title, $this->request->domain() . url('index') . '?ref=addtabs');
                        }

                        //测试主管
                        $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                        Ding::cc_ding($testAuthUserIds,  '任务ID:' .  $params['id'] . '+任务等待完成', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    }
                    
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }

            if ($params['type'] == 1) {
                $update = array();
                if($params['web_status'] == 1){
                    if(!$params['web_designer_group']){
                        $this->error('需求响应必选');
                    }
                    $update['web_designer_group'] = $params['web_designer_group'];
                    if($params['web_designer_group'] == 1){
                        if(!$params['web_designer_expect_time']){
                            $this->error('计划完成时间必选');
                        }
                        $update['web_designer_expect_time'] = $params['web_designer_expect_time'].' 22:00:00';
                        if(!$params['web_designer_complexity']){
                            $this->error('预期难度必选');
                        }
                        $update['web_designer_complexity'] = $params['web_designer_complexity'];
                    }else{
                        $update['web_designer_expect_time'] = null;
                        $update['web_designer_complexity'] = null;
                    }
                }

                if($params['php_status'] == 1){
                    if(!$params['phper_group']){
                        $this->error('需求响应必选');
                    }
                    $update['phper_group'] = $params['phper_group'];
                    if($params['phper_group'] == 1){
                        if(!$params['phper_expect_time']){
                            $this->error('计划完成时间必选');
                        }
                        $update['phper_expect_time'] = $params['phper_expect_time'].' 22:00:00';
                        if(!$params['phper_complexity']){
                            $this->error('预期难度必选');
                        }
                        $update['phper_complexity'] = $params['phper_complexity'];
                    }else{
                        $update['phper_expect_time'] = null;
                        $update['phper_complexity'] = null;
                    }
                }

                if($params['app_status'] == 1){
                    if(!$params['app_group']){
                        $this->error('需求响应必选');
                    }
                    $update['app_group'] = $params['app_group'];
                    if($params['app_group'] == 1){
                        if(!$params['app_expect_time']){
                            $this->error('计划完成时间必选');
                        }
                        $update['app_expect_time'] = $params['app_expect_time'].' 22:00:00';
                        if(!$params['app_complexity']){
                            $this->error('预期难度必选');
                        }
                        $update['app_complexity'] = $params['app_complexity'];
                    }else{
                        $update['app_expect_time'] = null;
                        $update['app_complexity'] = null;
                    }
                }

                $res = $this->model->allowField(true)->save($update,['id'=> $params['id']]);
                if ($res) {
                    //判断是否达到下一个阶段的状态
                    $develop_finish_status = array();
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();
                    if($row_arr['develop_finish_status'] == 1 && $row_arr['status'] == 2){
                        if(strpos($row_arr['site_type'],'3') !== false){
                            if($row_arr['web_designer_group'] != 0 && $row_arr['phper_group'] != 0 && $row_arr['app_group'] != 0){
                                //可以进入下一个状态
                                $develop_finish_status['develop_finish_status'] = 2;
                                $develop_finish_status['status'] = 3;
                                $this->model->allowField(true)->save($develop_finish_status,['id'=> $params['id']]);
                            }
                        }else{
                            if($row_arr['web_designer_group'] != 0 && $row_arr['phper_group'] != 0){
                                //可以进入下一个状态
                                $develop_finish_status['develop_finish_status'] = 2;
                                $develop_finish_status['status'] = 3;
                                $this->model->allowField(true)->save($develop_finish_status,['id'=> $params['id']]);
                            }
                        }
                    }

                    //开发中
                    if ($develop_finish_status['develop_finish_status'] == 2) {
                        //测试主管
                        $testAuthUserIds = Auth::getGroupUserId(config('demand.test_group_id')) ?: [];
                        Ding::cc_ding($testAuthUserIds,  '任务ID:' .  $params['id'] . '+任务等待确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
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

        $row_arr['start_time'] = date('Y-m-d',strtotime($row_arr['start_time']));
        $row_arr['end_time'] = date('Y-m-d',strtotime($row_arr['end_time']));

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
     * 测试确认--通过--上线
     * 测试权限
     */
    public function test_handle($ids = null)
    {
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $update = array();
                $label = 0;
                if($params['type'] == 'queren'){
                    $update['test_group'] = $params['test_group'];
                    $update['test_status'] = 2;
                    $update['test_confirm_time'] = date('Y-m-d H:i',time());
                }

                if($params['type'] == 'tongguo'){
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();
                    if($params['status'] == 1){
                        //通过
                        if($params['test_group'] == 1 || $params['test_group'] == 2){
                            $update['test_is_finish'] = 1;
                            $update['test_finish_time'] = date('Y-m-d H:i',time());
                        }
                        $update['test_status'] = 4;

                        $label = 1;
                    }else{
                        //未通过
                        if($row_arr['web_designer_group'] == 1){
                            $update['web_designer_is_finish'] = 0;
                            $update['web_designer_finish_time'] = null;
                        }
                        if($row_arr['phper_group'] == 1){
                            $update['phper_is_finish'] = 0;
                            $update['phper_finish_time'] = null;
                        }
                        if($row_arr['app_group'] == 1){
                            $update['app_is_finish'] = 0;
                            $update['app_finish_time'] = null;
                        }
                        $update['develop_finish_status'] = 2;
                        $update['develop_finish_time'] = null;

                        $label = 2;
                    }
                }

                if($params['type'] == 'shangxian'){
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();

                    $time = date('Y-m-d H:i',time());
                    $update['test_status'] = 5;
                    $update['all_finish_time'] = $time;
                    if($update['all_finish_time'] > $row_arr['end_time']){
                        $update['status'] = 5;
                    }else{
                        $update['status'] = 4;
                    }

                    $label = 3;
                }

                $res = $this->model->allowField(true)->save($update,['id'=> $params['id']]);
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
                        Ding::cc_ding($usersIds,  '任务ID:' .  $params['id'] . '+测试未通过', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
                    } elseif ($label == 3) { //任务上线 通知提出人
                        Ding::cc_ding($row['entry_user_id'],  '任务ID:' .  $params['id'] . '+任务已上线，等待确认', $row['title'], $this->request->domain() . url('index') . '?ref=addtabs');
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

        $row_arr['start_time'] = date('Y-m-d',strtotime($row_arr['start_time']));
        $row_arr['end_time'] = date('Y-m-d',strtotime($row_arr['end_time']));

        $time_arr = array();
        if($row_arr['web_designer_group'] == 1){
            $time_arr[] = strtotime($row_arr['web_designer_expect_time']);
        }
        if($row_arr['phper_group'] == 1){
            $time_arr[] = strtotime($row_arr['phper_expect_time']);
        }
        if($row_arr['app_group'] == 1){
            $time_arr[] = strtotime($row_arr['app_expect_time']);
        }
        rsort($time_arr);

        $day_num = strtotime($row_arr['end_time']) - $time_arr[0];

        if($day_num<0){
            $day = 0;
        }else{
            $day = ceil($day_num/(3600*24));
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
        if($row_arr['test_user_id']){
            if (in_array($this->auth->id,explode(',',$row_arr['test_user_id']))) {
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
    public function detail($ids = null){
        if ($this->request->isAjax()) {
            $params = $this->request->post();
            $row = $this->model->get($ids);
            if ($params) {
                $update['is_small_probability'] = $params['is_small_probability'];
                $update['is_low_level_error'] = $params['is_low_level_error'];
                $update['is_difficult'] = $params['is_difficult'];
                $update['web_designer_user_id'] = $params['web_designer_user_id'] ? implode(',',$params['web_designer_user_id']) : null;
                $update['phper_user_id'] = $params['phper_user_id'] ? implode(',',$params['phper_user_id']) : null;
                $update['app_user_id'] = $params['app_user_id'] ? implode(',',$params['app_user_id']) : null;
                $update['test_user_id'] = $params['test_user_id'] ? implode(',',$params['test_user_id']) : null;

                $res = $this->model->allowField(true)->save($update,['id'=> $params['id']]);
                if ($res) {
                    $web_designer_user_id = $params['web_designer_user_id'] ?: [];
                    $phper_user_id = $params['phper_user_id']  ?: [];
                    $app_user_id = $params['app_user_id']  ?: [];
                    $test_user_id = $params['test_user_id']  ?: [];
                    $usersIds = array_merge($web_designer_user_id, $phper_user_id, $app_user_id, $test_user_id);
                    Ding::cc_ding($usersIds,  '任务ID:' .  $params['id'] . '+任务已分配', $row->title, $this->request->domain() . url('index') . '?ref=addtabs');
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $row = $this->model->get($ids);
        $row = $row->toArray();
        $row['web_designer_user_id'] = explode(',',$row['web_designer_user_id']);
        $row['phper_user_id'] = explode(',',$row['phper_user_id']);
        $row['app_user_id'] = explode(',',$row['app_user_id']);
        $row['test_user_id'] = explode(',',$row['test_user_id']);

        //获取各组人员
        $authgroup = new AuthGroup();

        //获取php组长&组员
        $php_group_ids = $authgroup->getChildrenIds(config('demand.php_group_id'));
        $p_id[] = config('demand.php_group_id');
        $php_group_ids = array_merge($php_group_ids,$p_id);
        $php_users =  Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $php_group_ids)
            ->where('status','normal')
            ->column('nickname','id');

        //获取web组长&组员
        $web_group_ids = $authgroup->getChildrenIds(config('demand.web_group_id'));
        $w_id[] = config('demand.web_group_id');
        $web_group_ids = array_merge($web_group_ids,$w_id);
        $web_users =  Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $web_group_ids)
            ->where('status','normal')
            ->column('nickname','id');

        //获取app组长&组员
        $app_group_ids = $authgroup->getChildrenIds(config('demand.app_group_id'));
        $a_id[] = config('demand.app_group_id');
        $app_group_ids = array_merge($app_group_ids,$a_id);
        $app_users =  Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $app_group_ids)
            ->where('status','normal')
            ->column('nickname','id');

        //获取test组长&组员
        $test_group_ids = $authgroup->getChildrenIds(config('demand.test_group_id'));
        $t_id[] = config('demand.test_group_id');
        $test_group_ids = array_merge($test_group_ids,$t_id);
        $test_users =  Db::name("auth_group_access")
            ->alias("aga")
            ->join("admin a", "aga.uid=a.id")
            ->field("a.*")
            ->whereIn("aga.group_id", $test_group_ids)
            ->where('status','normal')
            ->column('nickname','id');

        //获取评论--测试站
        $test_review = Db::name("it_web_demand_review")
            ->where('type',1)
            ->where('pid',$ids)
            ->select();
        //获取评论--正式站
        $review = Db::name("it_web_demand_review")
            ->where('type',2)
            ->where('pid',$ids)
            ->select();

        //确认权限
        $this->view->assign("test_status", $this->auth->check('demand/it_web_demand/test_handle'));//测试分配权限
        $this->view->assign("distribution_status", $this->auth->check('demand/it_web_demand/distribution'));//开发分配权限

        $this->view->assign('php_users', $php_users);
        $this->view->assign('web_users', $web_users);
        $this->view->assign('app_users', $app_users);
        $this->view->assign('test_users', $test_users);
        $this->view->assign('test_review', $test_review);
        $this->view->assign('review', $review);
        $this->view->assign("row", $row );
        return $this->view->fetch();
    }

    /**
     * 查看详情--评论
     * 任何人都有权限
     * */
    public function demand_review(){
        if ($this->request->isAjax()) {
            $params = $this->request->post();
            if ($params) {
                if($params['content'] == ''){
                    $this->error('内容不能为空');
                }

                $update['pid'] = $params['pid'];
                $update['type'] = $params['type'];

                $users =  Db::name("auth_group_access")
                    ->alias("aga")
                    ->join("auth_group ag", "aga.group_id=ag.id")
                    ->field("ag.*")
                    ->where('aga.uid',$this->auth->id)
                    ->find();
                $update['group_id'] = $users['id'];
                $update['group_name'] = $users['name'];
                $update['user_id'] = $this->auth->id;
                $update['user_name'] = $this->auth->nickname;
                $update['content'] = $params['content'];
                $update['create_time'] = date('Y-m-d H:i:s',time());

                $res = $this->ItWebDemandReview->allowField(true)->save($update);
                if ($res) {

                    //Ding::dingHook(__FUNCTION__, $this ->model ->get($params['id']));
                    $this->success('成功',$url = null, $update);
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }
}
