<?php

namespace app\admin\controller\demand;

use app\api\controller\Ding;
use app\common\controller\Backend;
use app\common\model\Auth;
use think\Db;
use think\Request;

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
    protected $noNeedRight=['del'];  //解决创建人无删除权限问题 暂定
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItWebDemand;
        $this->view->assign('getTabList', $this->model->getTabList());
        $this->testRecordModel = new \app\admin\model\demand\ItTestRecord;
        $this->assignconfig('admin_id', session('admin.id'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
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
     * 技术部网站需求列表
     */
    public function index()
    {
        //dump(input());exit;
        //设置过滤方法
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

            if ($filter['entry_user_name']){
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . trim($filter['entry_user_name']) . '%'];
                $id = $admin->where($smap)->value('id');
                if (!empty($id)){
                    $smap['entry_user_id'] = $id;
                }else{
                    $smap['entry_user_id'] =  trim($filter['entry_user_name']);
                }
                unset($filter['entry_user_name']);
                unset($smap['nickname']);
            }
            $meWhere = '';
            //我的
            if(isset($filter['me_task'])){

                $adminId = session('admin.id');
                //是否是主管
                $authUserIds = Auth::getUsersId('demand/it_web_demand/test_distribution') ?: [];
                //判断是否是测试
                if(in_array($adminId,$authUserIds)){
                    $meWhere = "(status = 1 or test_group = 1)";
                }
                //判断是否是普通的测试
                $testAuthUserIds = Auth::getUsersId('demand/it_web_demand/test_group_finish') ?: [];
                if(!in_array($adminId,$authUserIds) && in_array($adminId,$testAuthUserIds)){
                    $meWhere = "(test_group = 1 and FIND_IN_SET({$adminId},test_user_id))";
                }
                //显示有分配权限的人，此类人跟点上线的是一类人，此类人应该可以查看所有的权限
                $assignAuthUserIds = Auth::getUsersId('demand/it_web_demand/distribution') ?: [];
                if(in_array($adminId,$assignAuthUserIds)){
                    $meWhere = "1 = 1";
                }
                //拼接我创建的所有和负责人是我的,抄送人是我的
                if($meWhere){
                    $meWhere .= "  or entry_user_id = {$adminId} or FIND_IN_SET({$adminId},web_designer_user_id) or FIND_IN_SET({$adminId},phper_user_id) or FIND_IN_SET({$adminId},test_user_id) or FIND_IN_SET({$adminId},copy_to_user_id)";
                }else{
                    $meWhere .= "entry_user_id = {$adminId} or FIND_IN_SET({$adminId},web_designer_user_id) or FIND_IN_SET({$adminId},phper_user_id) or FIND_IN_SET({$adminId},test_user_id) or FIND_IN_SET({$adminId},copy_to_user_id)";
                }
                unset($filter['me_task']);
            } elseif(isset($filter['none_complete'])){//未完成
                $meWhere="status !=7";
                unset($filter['none_complete']);
            }

            $user_map='';
            if ($filter['all_user_name']){
                $admin = new \app\admin\model\Admin();
                $admin_user['nickname'] = ['like', '%' . trim($filter['all_user_name']) . '%'];
                $id = $admin->where($admin_user)->value('id');
                if (!empty($id)){
                    $user_map = "FIND_IN_SET({$id},web_designer_user_id) or FIND_IN_SET({$id},phper_user_id) or FIND_IN_SET({$id},app_user_id) ";
                }else{
                    $user_map="web_designer_user_id =  '".trim($filter['all_user_name'])."'";
                }
                unset($filter['all_user_name']);
                unset($admin_user['nickname']);
            }



            if(isset($filter['Allgroup_sel'])){
                unset($filter['Allgroup_sel']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($smap)
                ->where($meWhere)
                ->where($user_map)
                ->where('type', 2)
                ->where('is_del', 1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($smap)
                ->where($meWhere)
                ->where($user_map)
                ->where('type', 2)
                ->where('is_del', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            //检查有没有权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add');//新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del');//删除权限
            $permissions['demand_through_demand'] = $this->auth->check('demand/it_web_demand/through_demand');//开发通过
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution');//开发分配
            $permissions['demand_test_distribution'] = $this->auth->check('demand/it_web_demand/test_distribution');//测试分配
            $permissions['demand_finish'] = $this->auth->check('demand/it_web_demand/group_finish');//开发完成
            $permissions['demand_test_finish'] = $this->auth->check('demand/it_web_demand/test_group_finish');//测试完成
            $permissions['demand_test_record_bug'] = $this->auth->check('demand/it_web_demand/test_record_bug');//测试完成
            $permissions['demand_add_online'] = $this->auth->check('demand/it_web_demand/add_online');//上线需求
            $permissions['demand_opt_test_duty'] = $this->auth->check('demand/it_web_demand/opt_test_duty');//是否扣测试绩效
            $permissions['demand_opt_work_time'] = $this->auth->check('demand/it_web_demand/opt_work_time');//是否扣非加班处理问题

            foreach ($list as $k => $v){
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人

                $list[$k]['allcomplexity'] = config('demand.allComplexity')[$v['all_complexity']];//复杂度
                $list[$k]['hope_time'] = date('m-d H:i',strtotime($v['hope_time']));//预计时间

                /*分配*/
                $list[$k]['Allgroup'] = array();
                if($v['web_designer_group'] == 1){
                    $list[$k]['Allgroup'][] = '前端';
                    $list[$k]['web_designer_user_name'] = $this->extract_username($v['web_designer_user_id'],'web_designer_user');
                    $list[$k]['web_designer_expect_time'] = date('m-d H:i',strtotime($v['web_designer_expect_time']));
                    if($v['web_designer_is_finish'] == 1){
                        $list[$k]['web_designer_finish_time'] = date('m-d H:i',strtotime($v['web_designer_finish_time']));
                    }
                }
                if($v['phper_group'] == 1){
                    $list[$k]['Allgroup'][] = '后端';
                    $list[$k]['phper_user_name'] = $this->extract_username($v['phper_user_id'],'phper_user');
                    $list[$k]['phper_expect_time'] = date('m-d H:i',strtotime($v['phper_expect_time']));
                    if($v['phper_is_finish'] == 1){
                        $list[$k]['phper_finish_time'] = date('m-d H:i',strtotime($v['phper_finish_time']));
                    }
                }
                if($v['app_group'] == 1){
                    $list[$k]['Allgroup'][] = 'APP';
                    $list[$k]['app_user_name'] = $this->extract_username($v['app_user_id'],'app_user');
                    $list[$k]['app_expect_time'] = date('m-d H:i',strtotime($v['app_expect_time']));
                    if($v['app_is_finish'] == 1){
                        $list[$k]['app_finish_time'] = date('m-d H:i',strtotime($v['app_finish_time']));
                    }
                }
                if($v['test_group'] == 1){
                    foreach (explode(',',$v['test_user_id']) as $t){
                        $list[$k]['test_user_id_arr'][] = config('demand.test_user')[$t];
                    }
                }
                /*分配*/

                /*当前状态*/
                if($v['status'] == 1){
                    $list[$k]['status_str'] = 'New';
                }elseif ($v['status'] == 2){
                    $list[$k]['status_str'] = '待通过';
                }elseif ($v['status'] == 3){
                    if($v['web_designer_group'] == 0 && $v['phper_group'] == 0 && $v['app_group'] == 0){
                        $list[$k]['status_str'] = '待分配';
                    }else{
                        $list[$k]['status_str'] = '开发ing';
                    }
                }elseif ($v['status'] == 4){
                    if($v['test_group'] == 1){
                        if($v['entry_user_confirm'] == 0){
                            $list[$k]['status_str'] = '待测试,待确认';
                        }else{
                            $list[$k]['status_str'] = '待测试,已确认';
                        }
                    }else{
                        $list[$k]['status_str'] = '待上线';
                    }

                }elseif ($v['status'] == 5){
                    if($v['test_group'] == 1){
                        if($v['entry_user_confirm'] == 0){
                            $list[$k]['status_str'] = '待确认';
                        }else{
                            $list[$k]['status_str'] = '待上线';
                        }
                    }else{
                        $list[$k]['status_str'] = '待上线';
                    }
                }elseif ($v['status'] == 6){

                    $list[$k]['status_str'] = '待回归测试';
                }elseif ($v['status'] == 7){

                    $list[$k]['status_str'] = '已完成';
                }

                /*当前状态*/
                //$this->user_id = $this->auth->id;
                //权限赋值
                $list[$k]['demand_add'] = $permissions['demand_add'];
                $list[$k]['demand_del'] = $permissions['demand_del'];
                $list[$k]['demand_through_demand'] = $permissions['demand_through_demand'];
                $list[$k]['demand_distribution'] = $permissions['demand_distribution'];
                $list[$k]['demand_test_distribution'] = $permissions['demand_test_distribution'];
                $list[$k]['demand_finish'] = $permissions['demand_finish'];
                $list[$k]['demand_test_finish'] = $permissions['demand_test_finish'];
                $list[$k]['demand_test_record_bug'] = $permissions['demand_test_record_bug'];
                $list[$k]['demand_add_online'] = $permissions['demand_add_online'];
                $list[$k]['demand_opt_test_duty'] = $permissions['demand_opt_test_duty'];
                $list[$k]['demand_opt_work_time'] = $permissions['demand_opt_work_time'];

                //判断当前登录人是否显示应该操作的按钮
                if($v['test_group'] == 1 && $v['test_user_id'] != ''){
                    if(in_array($this->auth->id, explode(',', $v['test_user_id']))){
                        $list[$k]['is_test_record_hidden'] = 1;//显示
                        $list[$k]['is_test_finish_hidden'] = 1;//显示
                        $list[$k]['is_test_detail_log'] = 0;//不显示
                    }
                }
                if($this->auth->id == $v['entry_user_id']){
                    $list[$k]['is_entry_user_hidden'] = 1;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 技术部网站bug列表
     * */
    public function bug_list(){
        //设置过滤方法
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
            $meWhere = '';
            //我的
            if(isset($filter['me_task'])){

                $adminId = session('admin.id');
                //是否是主管
                $authUserIds = Auth::getUsersId('demand/it_web_demand/test_distribution') ?: [];
                //判断是否是测试
                if(in_array($adminId,$authUserIds)){
                    $meWhere = "(status = 1 or test_group = 1)";
                }
                //判断是否是普通的测试
                $testAuthUserIds = Auth::getUsersId('demand/it_web_demand/test_group_finish') ?: [];
                if(!in_array($adminId,$authUserIds) && in_array($adminId,$testAuthUserIds)){
                    $meWhere = "(test_group = 1 and FIND_IN_SET({$adminId},test_user_id))";
                }
                //显示有分配权限的人，此类人跟点上线的是一类人，此类人应该可以查看所有的权限
                $assignAuthUserIds = Auth::getUsersId('demand/it_web_demand/distribution') ?: [];
                if(in_array($adminId,$assignAuthUserIds)){
                    $meWhere = "1 = 1";
                }
                //拼接我创建的所有和负责人是我的,抄送人是我的
                if($meWhere){
                    $meWhere .= "  or entry_user_id = {$adminId} or FIND_IN_SET({$adminId},web_designer_user_id) or FIND_IN_SET({$adminId},phper_user_id) or FIND_IN_SET({$adminId},test_user_id) or FIND_IN_SET({$adminId},copy_to_user_id)";
                }else{
                    $meWhere .= "entry_user_id = {$adminId} or FIND_IN_SET({$adminId},web_designer_user_id) or FIND_IN_SET({$adminId},phper_user_id) or FIND_IN_SET({$adminId},test_user_id) or FIND_IN_SET({$adminId},copy_to_user_id)";
                }
                unset($filter['me_task']);
            } elseif(isset($filter['none_complete'])){//未完成
                $meWhere="status !=7";
                unset($filter['none_complete']);
            }

            if(isset($filter['Allgroup_sel'])){
                unset($filter['Allgroup_sel']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($smap)
                ->where($meWhere)
                ->where('type', 1)
                ->where('is_del', 1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($smap)
                ->where($meWhere)
                ->where('type', 1)
                ->where('is_del', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            //检查有没有权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add');//新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del');//删除权限
            $permissions['demand_through_demand'] = $this->auth->check('demand/it_web_demand/through_demand');//开发通过
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution');//开发分配
            $permissions['demand_test_distribution'] = $this->auth->check('demand/it_web_demand/test_distribution');//测试分配
            $permissions['demand_finish'] = $this->auth->check('demand/it_web_demand/group_finish');//开发完成
            $permissions['demand_test_finish'] = $this->auth->check('demand/it_web_demand/test_group_finish');//测试完成
            $permissions['demand_test_record_bug'] = $this->auth->check('demand/it_web_demand/test_record_bug');//测试完成
            $permissions['demand_add_online'] = $this->auth->check('demand/it_web_demand/add_online');//上线需求
            $permissions['demand_opt_test_duty'] = $this->auth->check('demand/it_web_demand/opt_test_duty');//上线需求
            $permissions['demand_opt_work_time'] = $this->auth->check('demand/it_web_demand/opt_work_time');//上线需求
            foreach ($list as $k => $v){
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人

                $list[$k]['allcomplexity'] = config('demand.allComplexity')[$v['all_complexity']];//复杂度
                $list[$k]['hope_time'] = date('m-d H:i',strtotime($v['hope_time']));//预计时间

                /*分配*/
                $list[$k]['Allgroup'] = array();
                if($v['web_designer_group'] == 1){
                    $list[$k]['Allgroup'][] = '前端';
                    $list[$k]['web_designer_user_name'] = $this->extract_username($v['web_designer_user_id'],'web_designer_user');
                    $list[$k]['web_designer_expect_time'] = date('m-d H:i',strtotime($v['web_designer_expect_time']));
                    if($v['web_designer_is_finish'] == 1){
                        $list[$k]['web_designer_finish_time'] = date('m-d H:i',strtotime($v['web_designer_finish_time']));
                    }
                }
                if($v['phper_group'] == 1){
                    $list[$k]['Allgroup'][] = '后端';
                    $list[$k]['phper_user_name'] = $this->extract_username($v['phper_user_id'],'phper_user');
                    $list[$k]['phper_expect_time'] = date('m-d H:i',strtotime($v['phper_expect_time']));
                    if($v['phper_is_finish'] == 1){
                        $list[$k]['phper_finish_time'] = date('m-d H:i',strtotime($v['phper_finish_time']));
                    }
                }
                if($v['app_group'] == 1){
                    $list[$k]['Allgroup'][] = 'APP';
                    $list[$k]['app_user_name'] = $this->extract_username($v['app_user_id'],'app_user');
                    $list[$k]['app_expect_time'] = date('m-d H:i',strtotime($v['app_expect_time']));
                    if($v['app_is_finish'] == 1){
                        $list[$k]['app_finish_time'] = date('m-d H:i',strtotime($v['app_finish_time']));
                    }
                }
                if($v['test_group'] == 1){
                    foreach (explode(',',$v['test_user_id']) as $t){
                        $list[$k]['test_user_id_arr'][] = config('demand.test_user')[$t];
                    }
                }
                /*分配*/

                /*当前状态*/
                if($v['status'] == 1){
                    $list[$k]['status_str'] = 'New';
                }elseif ($v['status'] == 2){
                    $list[$k]['status_str'] = '待通过';
                }elseif ($v['status'] == 3){
                    if($v['web_designer_group'] == 0 && $v['phper_group'] == 0 && $v['app_group'] == 0){
                        $list[$k]['status_str'] = '待分配';
                    }else{
                        $list[$k]['status_str'] = '开发ing';
                    }
                }elseif ($v['status'] == 4){
                    if($v['test_group'] == 1){
                        if($v['entry_user_confirm'] == 0){
                            $list[$k]['status_str'] = '待测试';
                        }else{
                            $list[$k]['status_str'] = '待测试';
                        }
                    }else{
                        $list[$k]['status_str'] = '待上线';
                    }

                }elseif ($v['status'] == 5){
                    if($v['test_group'] == 1){
                        if($v['entry_user_confirm'] == 0){
                            $list[$k]['status_str'] = '待确认';
                        }else{
                            $list[$k]['status_str'] = '待上线';
                        }
                    }else{
                        $list[$k]['status_str'] = '待上线';
                    }
                }elseif ($v['status'] == 6){

                    $list[$k]['status_str'] = '待回归测试';
                }elseif ($v['status'] == 7){

                    $list[$k]['status_str'] = '已完成';
                }

                /*当前状态*/
                //$this->user_id = $this->auth->id;
                //权限赋值
                $list[$k]['demand_add'] = $permissions['demand_add'];
                $list[$k]['demand_del'] = $permissions['demand_del'];
                $list[$k]['demand_through_demand'] = $permissions['demand_through_demand'];
                $list[$k]['demand_distribution'] = $permissions['demand_distribution'];
                $list[$k]['demand_test_distribution'] = $permissions['demand_test_distribution'];
                $list[$k]['demand_finish'] = $permissions['demand_finish'];
                $list[$k]['demand_test_finish'] = $permissions['demand_test_finish'];
                $list[$k]['demand_test_record_bug'] = $permissions['demand_test_record_bug'];
                $list[$k]['demand_add_online'] = $permissions['demand_add_online'];
                $list[$k]['demand_opt_test_duty'] = $permissions['demand_opt_test_duty'];
                $list[$k]['demand_opt_work_time'] = $permissions['demand_opt_work_time'];

                //判断当前登录人是否显示应该操作的按钮
               /* if($v['test_group'] == 1 && $v['test_user_id'] != ''){
                    if(in_array($this->auth->id, explode(',', $v['test_user_id']))){
                        $list[$k]['is_test_record_hidden'] = 1;
                        $list[$k]['is_test_finish_hidden'] = 1;
                    }
                }
                */

                if($this->auth->id == $v['entry_user_id']){
                    $list[$k]['is_entry_user_hidden'] = 1;
                }

                if($v['test_group'] == 1 && $v['test_user_id'] != ''){
                    if(in_array($this->auth->id, explode(',', $v['test_user_id']))){
                        $list[$k]['is_test_record_hidden'] = 1;
                        $list[$k]['is_test_finish_hidden'] = 1;
                        $list[$k]['is_test_detail_log'] = 0;//不显示
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 技术部网站疑难列表
     * */
    public function difficult_list(){
        //设置过滤方法
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
            if($smap){
                unset($filter['Allgroup_sel']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($smap)
                ->where('type', 3)
                ->where('is_del', 1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($smap)
                ->where('type', 3)
                ->where('is_del', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            //检查有没有权限
            $permissions['demand_add'] = $this->auth->check('demand/it_web_demand/add');//新增权限
            $permissions['demand_del'] = $this->auth->check('demand/it_web_demand/del');//删除权限
            $permissions['demand_through_demand'] = $this->auth->check('demand/it_web_demand/through_demand');//开发通过
            $permissions['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution');//开发分配
            $permissions['demand_test_distribution'] = $this->auth->check('demand/it_web_demand/test_distribution');//测试分配
            $permissions['demand_finish'] = $this->auth->check('demand/it_web_demand/group_finish');//开发完成
            $permissions['demand_test_finish'] = $this->auth->check('demand/it_web_demand/test_group_finish');//测试完成
            $permissions['demand_test_record_bug'] = $this->auth->check('demand/it_web_demand/test_record_bug');//测试完成
            $permissions['demand_add_online'] = $this->auth->check('demand/it_web_demand/add_online');//上线需求

            foreach ($list as $k => $v){
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人

                $list[$k]['allcomplexity'] = config('demand.allComplexity')[$v['all_complexity']];//复杂度
                $list[$k]['hope_time'] = date('m-d H:i',strtotime($v['hope_time']));//预计时间

                /*分配*/
                $list[$k]['Allgroup'] = array();
                if($v['web_designer_group'] == 1){
                    $list[$k]['Allgroup'][] = '前端';
                    $list[$k]['web_designer_user_name'] = $this->extract_username($v['web_designer_user_id'],'web_designer_user');
                    $list[$k]['web_designer_expect_time'] = date('m-d H:i',strtotime($v['web_designer_expect_time']));
                    if($v['web_designer_is_finish'] == 1){
                        $list[$k]['web_designer_finish_time'] = date('m-d H:i',strtotime($v['web_designer_finish_time']));
                    }
                }
                if($v['phper_group'] == 1){
                    $list[$k]['Allgroup'][] = '后端';
                    $list[$k]['phper_user_name'] = $this->extract_username($v['phper_user_id'],'phper_user');
                    $list[$k]['phper_expect_time'] = date('m-d H:i',strtotime($v['phper_expect_time']));
                    if($v['phper_is_finish'] == 1){
                        $list[$k]['phper_finish_time'] = date('m-d H:i',strtotime($v['phper_finish_time']));
                    }
                }
                if($v['app_group'] == 1){
                    $list[$k]['Allgroup'][] = 'APP';
                    $list[$k]['app_user_name'] = $this->extract_username($v['app_user_id'],'app_user');
                    $list[$k]['app_expect_time'] = date('m-d H:i',strtotime($v['app_expect_time']));
                    if($v['app_is_finish'] == 1){
                        $list[$k]['app_finish_time'] = date('m-d H:i',strtotime($v['app_finish_time']));
                    }
                }
                if($v['test_group'] == 1){
                    foreach (explode(',',$v['test_user_id']) as $t){
                        $list[$k]['test_user_id_arr'][] = config('demand.test_user')[$t];
                    }
                }
                /*分配*/

                /*当前状态*/
                if($v['status'] == 1){
                    $list[$k]['status_str'] = 'New';
                }elseif ($v['status'] == 2){
                    $list[$k]['status_str'] = '待通过';
                }elseif ($v['status'] == 3){
                    if($v['web_designer_group'] == 0 && $v['phper_group'] == 0 && $v['app_group'] == 0){
                        $list[$k]['status_str'] = '待分配';
                    }else{
                        $list[$k]['status_str'] = '开发ing';
                    }
                }elseif ($v['status'] == 4){
                    if($v['test_group'] == 1){
                        if($v['entry_user_confirm'] == 0){
                            $list[$k]['status_str'] = '待测试,待确认';
                        }else{
                            $list[$k]['status_str'] = '待测试,已确认';
                        }
                    }else{
                        $list[$k]['status_str'] = '待上线';
                    }

                }elseif ($v['status'] == 5){
                    if($v['test_group'] == 1){
                        if($v['entry_user_confirm'] == 0){
                            $list[$k]['status_str'] = '待确认';
                        }else{
                            $list[$k]['status_str'] = '待上线';
                        }
                    }else{
                        $list[$k]['status_str'] = '待上线';
                    }
                }elseif ($v['status'] == 6){

                    $list[$k]['status_str'] = '待回归测试';
                }elseif ($v['status'] == 7){

                    $list[$k]['status_str'] = '已完成';
                }

                /*当前状态*/
                //$this->user_id = $this->auth->id;
                //权限赋值
                $list[$k]['demand_add'] = $permissions['demand_add'];
                $list[$k]['demand_del'] = $permissions['demand_del'];
                $list[$k]['demand_through_demand'] = $permissions['demand_through_demand'];
                $list[$k]['demand_distribution'] = $permissions['demand_distribution'];
                $list[$k]['demand_test_distribution'] = $permissions['demand_test_distribution'];
                $list[$k]['demand_finish'] = $permissions['demand_finish'];
                $list[$k]['demand_test_finish'] = $permissions['demand_test_finish'];
                $list[$k]['demand_test_record_bug'] = $permissions['demand_test_record_bug'];
                $list[$k]['demand_add_online'] = $permissions['demand_add_online'];

                //判断当前登录人是否显示应该操作的按钮
                if($v['test_group'] == 1 && $v['test_user_id'] != ''){
                    if(in_array($this->auth->id, explode(',', $v['test_user_id']))){
                        $list[$k]['is_test_record_hidden'] = 1;
                        $list[$k]['is_test_finish_hidden'] = 1;
                        $list[$k]['is_test_detail_log'] = 0;//不显示
                    }
                }
                if($this->auth->id == $v['entry_user_id']){
                    $list[$k]['is_entry_user_hidden'] = 1;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add()
    {
       /* $url = 'http://mj.com/admin_1biSSnWyfW.php/demand/it_web_demand/index?ref=addtabs';
        $user_id[] =  '0550643549844645';//李想
        $user_id[] =  '0333543233781107';//张晓
        $res = (new Ding())->ding_notice($user_id,$url,'新需求来了1111111111','测试内容222222222222');
        dump($res);exit;*/

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params = input();

            if ($params) {
                if($params['is_user_confirm'] == 1){
                    //提出人确认
                    $data['entry_user_confirm'] =  1;
                    $data['entry_user_confirm_time'] =  date('Y-m-d H:i',time());
                    $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
                    if ($res) {
                        $res = $this ->model ->get(input('ids'));
                        Ding::dingHook('test_group_finish', $res);
                        $this->success('成功');
                    } else {
                        $this->error('失败');
                    }
                }else{
                    //新增
                    $params = $params['row'];
                    if ($params['copy_to_user_id']) {
                        $params['copy_to_user_id'] = implode(",", $params['copy_to_user_id']);
                    }
                    $params['entry_user_id'] = $this->auth->id;

                    $params = $this->preExcludeFields($params);

                    if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                        $params[$this->dataLimitField] = $this->auth->id;
                    }

                    $result = false;
                    Db::startTrans();
                    try {
                        //是否采用模型验证
                        if ($this->modelValidate) {
                            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                            $this->model->validateFailException(true)->validate($validate);
                        }
                        $result = $this->model->allowField(true)->save($params);
                        Db::commit();
                    } catch (ValidateException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    if ($result !== false) {
                        Ding::dingHook(__FUNCTION__, $this ->model);
                        $this->success();
                    } else {
                        $this->error(__('No rows were inserted'));
                    }
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('demand_type',input('demand_type'));
        /*$user_id = $this->auth->id;
        $user_name = $this->auth->username;
        $this->view->assign('user_id',$this->auth->id);
        $this->view->assign('user_name', $this->auth->username);*/
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
                if ($params['copy_to_user_id']) {
                    $params['copy_to_user_id'] = implode(",", $params['copy_to_user_id']);
                }
                $res = $this->model->allowField(true)->save($params,['id'=> input('ids')]);
                if ($res) {
                    Ding::dingHook(__FUNCTION__, $this ->model ->get(input('ids')));
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row = $this->model->get($ids);
        $row = $row->toArray();
        //如果已分配app人员
        $copy_to_user_id_arr = array();
        if($row['copy_to_user_id']){
            $copy_userids = explode(',',$row['copy_to_user_id']);
            foreach ($copy_userids as $k => $v){
                $copy_to_user_id_arr[$k]['user_id'] = $v;
                $copy_to_user_id_arr[$k]['user_name'] = config('demand.copyToUserId')[$v];
            }
        }

        $this->view->assign('demand_type',input('demand_type'));
        $this->view->assign("copy_to_user_id_arr", $copy_to_user_id_arr );
        $this->view->assign("row", $row );
        return $this->view->fetch();
    }

    /**
     * 逻辑删除
     * */
    public function del($ids = "")
    {
        if ($this->request->isAjax()) {
            $data['is_del'] =  2;
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    /**
     * 测试分配
     * 测试组权限
     */
    public function test_distribution($ids = null)
    {
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $update_date = array();
                if($params['status'] == 1){
                    if($params['test_group'] == 1){
                        if(!$params['test_user_id']){
                            $this->error('未分配测试责任人');
                        }
                        $update_date['test_group'] = $params['test_group'];
                        $update_date['test_complexity'] = $params['test_complexity'];
                        $update_date['test_user_id'] = implode(',',$params['test_user_id']);
                    }else{
                        $update_date['test_group'] = $params['test_group'];
                        $update_date['test_complexity'] = 0;
                        $update_date['test_user_id'] = '';
                    }
                    if($params['demand_type'] == 2){
                        $update_date['status'] = 2;
                    }else{
                        $update_date['status'] = 3;
                        $update_date['test_complexity'] = 0;
                    }

                }
                $update_date['test_confirm_time'] =  date('Y-m-d H:i',time());
                $res = $this->model->allowField(true)->save($update_date,['id'=> $params['id']]);
                if ($res) {
                    Ding::dingHook(__FUNCTION__, $this ->model ->get($params['id']));
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $ids = $ids ?? input('id');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();

        //如果已测试人员
        if($row_arr['test_group'] == 1){
            if($row_arr['test_user_id']){
                $test_userids = explode(',',$row_arr['test_user_id']);
                foreach ($test_userids as $k4 => $v4){
                    $test_userid_arr[$k4]['user_id'] = $v4;
                    $test_userid_arr[$k4]['user_name'] = config('demand.test_user')[$v4];
                }
            }
        }

        $this->view->assign("test_userid_arr", $test_userid_arr);
        $this->view->assign('demand_type',input('demand_type'));
        $this->view->assign("row", $row_arr);
        return $this->view->fetch('distribution');
    }

    /**
     * 通过需求&标记为小概率
     * 开发组权限
     * */
    public function through_demand($ids = null)
    {
        if ($this->request->isAjax()) {
            $params = input();
            if($params['small_probability'] == 1){
                $data['is_small_probability'] =  $params['val'];
            }else{
                $data['status'] =  3;
            }
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                Ding::dingHook(__FUNCTION__, $this ->model ->get(input('ids')));
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    /**
     * 开发分配
     * 开发组权限
     */
    public function distribution($ids = null)
    {
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $update_date = array();
                if($params['status'] == 3){
                    if($params['web_designer_group'] == 1){
                        if(!$params['web_designer_user_id']){
                            $this->error('未分配前端责任人');
                        }
                        $update_date['web_designer_group'] = $params['web_designer_group'];
                        $update_date['web_designer_complexity'] = $params['web_designer_complexity'];
                        $update_date['web_designer_expect_time'] = $params['web_designer_expect_time'];
                        $update_date['web_designer_user_id'] = implode(',',$params['web_designer_user_id']);
                    }else{
                        $update_date['web_designer_group'] = $params['web_designer_group'];
                        $update_date['web_designer_complexity'] = '';
                        $update_date['web_designer_expect_time'] = null;
                        $update_date['web_designer_user_id'] = '';
                    }
                    if($params['phper_group'] == 1){
                        if(!$params['phper_user_id']){
                            $this->error('未分配后端责任人');
                        }
                        $update_date['phper_group'] = $params['phper_group'];
                        $update_date['phper_complexity'] = $params['phper_complexity'];
                        $update_date['phper_expect_time'] = $params['phper_expect_time'];
                        $update_date['phper_user_id'] = implode(',',$params['phper_user_id']);
                    }else{
                        $update_date['phper_group'] = $params['phper_group'];
                        $update_date['phper_complexity'] = '';
                        $update_date['phper_expect_time'] = null;
                        $update_date['phper_user_id'] = '';
                    }
                    if($params['app_group'] == 1){
                        if(!$params['app_user_id']){
                            $this->error('未分配app责任人');
                        }
                        $update_date['app_group'] = $params['app_group'];
                        $update_date['app_complexity'] = $params['app_complexity'];
                        $update_date['app_expect_time'] = $params['app_expect_time'];
                        $update_date['app_user_id'] = implode(',',$params['app_user_id']);
                    }else{
                        $update_date['app_group'] = $params['app_group'];
                        $update_date['app_complexity'] = '';
                        $update_date['app_expect_time'] = null;
                        $update_date['app_user_id'] = '';
                    }

                }

                $res = $this->model->allowField(true)->save($update_date,['id'=> $params['id']]);
                if ($res) {
                    Ding::dingHook(__FUNCTION__, $this ->model ->get($params['id']));
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $ids = $ids ?? input('id');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();

        //如果已分配前端人员
        $web_userid_arr = array();
        if($row_arr['web_designer_user_id']){
            $web_userids = explode(',',$row_arr['web_designer_user_id']);
            foreach ($web_userids as $k1 => $v1){
                $web_userid_arr[$k1]['user_id'] = $v1;
                $web_userid_arr[$k1]['user_name'] = config('demand.web_designer_user')[$v1];
            }
        }

        //如果已分配后端人员
        $phper_userid_arr = array();
        if($row_arr['phper_user_id']){
            $phper_userids = explode(',',$row_arr['phper_user_id']);
            foreach ($phper_userids as $k2 => $v2){
                $phper_userid_arr[$k2]['user_id'] = $v2;
                $phper_userid_arr[$k2]['user_name'] = config('demand.phper_user')[$v2];
            }
        }

        //如果已分配app人员
        $app_userid_arr = array();
        if($row_arr['app_user_id']){
            $app_userids = explode(',',$row_arr['app_user_id']);
            foreach ($app_userids as $k3 => $v3){
                $app_userid_arr[$k3]['user_id'] = $v3;
                $app_userid_arr[$k3]['user_name'] = config('demand.app_user')[$v3];
            }
        }
        if($row_arr['type'] == 2){
            $demand_type = 2;
        }
        $this->view->assign('demand_type',$demand_type);
        $this->view->assign("web_userid_arr", $web_userid_arr);
        $this->view->assign("phper_userid_arr", $phper_userid_arr);
        $this->view->assign("app_userid_arr", $app_userid_arr);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }



    /**
     * 开发完成方法
     * 开发组权限
     */
    public function group_finish($ids = null)
    {
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if($params['group_finish'] == 1){
                    $flag = 0;//3可以更新status值，其他都不能更新
                    $update_date = array();
                    //前端点击完成方法
                    if($params['web_finish'] == 1){
                        $update_date['web_designer_is_finish'] = 1;
                        $update_date['web_designer_finish_time'] =  date('Y-m-d H:i',time());
                        $update_date['web_designer_note'] =  $params['web_designer_note'];
                        if ($params['type']==1){
                            $update_date['is_small_probability'] =  $params['is_small_probability'];
                        }
                        $res = $this->model->allowField(true)->save($update_date,['id'=> $params['id']]);
                    }

                    //后端点击完成方法
                    if($params['php_finish'] == 1){
                        $update_date['phper_is_finish'] = 1;
                        $update_date['phper_finish_time'] =  date('Y-m-d H:i',time());
                        $update_date['phper_note'] =  $params['phper_note'];
                        if ($params['type']==1){
                            $update_date['is_small_probability'] =  $params['is_small_probability'];
                        }
                        $res = $this->model->allowField(true)->save($update_date,['id'=> $params['id']]);
                    }

                    //app点击完成方法
                    if($params['app_finish'] == 1){
                        $update_date['app_is_finish'] = 1;
                        $update_date['app_finish_time'] =  date('Y-m-d H:i',time());
                        $update_date['app_note'] =  $params['app_note'];
                        if ($params['type']==1){
                            $update_date['is_small_probability'] =  $params['is_small_probability'];
                        }
                        $res = $this->model->allowField(true)->save($update_date,['id'=> $params['id']]);
                    }

                    //判断状态
                    $row = $this->model->get(['id' => $params['id']]);
                    $row_arr = $row->toArray();

                    if(($row_arr['web_designer_group'] == 1 && $row_arr['web_designer_is_finish'] == 1) || $row_arr['web_designer_group'] == 0){
                        $flag += 1;
                    }

                    if(($row_arr['phper_group'] == 1 && $row_arr['phper_is_finish'] == 1) || $row_arr['phper_group'] == 0){
                        $flag += 1;
                    }

                    if(($row_arr['app_group'] == 1 && $row_arr['app_is_finish'] == 1) || $row_arr['app_group'] == 0){
                        $flag += 1;
                    }

                    if($flag == 3){
                        if($row_arr['test_group'] == 2){
                            $update_status['status'] = 5;
                        }else{
                            if($params['demand_type'] == 1 || $params['demand_type'] == 3){
                                $update_status['entry_user_confirm'] = 1;
                                $update_status['entry_user_confirm_time'] = date('Y-m-d H:i',time());
                            }
                            $update_status['status'] = 4;
                        }
                        $res_status = $this->model->allowField(true)->save($update_status,['id'=> $params['id']]);
                        if ($res_status) {
                            Ding::dingHook(__FUNCTION__, $row);
                            $this->success('成功');
                        } else {
                            $this->error('失败');
                        }
                    }else{
                        if ($res) {
                            $this->success('成功');
                        } else {
                            $this->error('失败');
                        }
                    }
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $ids = $ids ?? input('id');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
        $year_time = date('Y-m-d H:i',time());

        //如果已分配前端人员
        $web_userid_arr = array();
        if($row_arr['web_designer_user_id']){
            $web_userid_arr['group'] = $row_arr['web_designer_group'];
            $web_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['web_designer_complexity']];
            $web_userid_arr['user_name'] = $this->extract_username($row_arr['web_designer_user_id'],'web_designer_user');
            $web_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['web_designer_expect_time']));
            $web_userid_arr['is_finish'] = $row_arr['web_designer_is_finish'];
            $web_userid_arr['finish_time'] = $row_arr['web_designer_finish_time'];
            $web_userid_arr['note'] = $row_arr['web_designer_note'];
        }
        //如果已分配后端人员
        $phper_userid_arr = array();
        if($row_arr['phper_user_id']){
            $phper_userid_arr['group'] = $row_arr['phper_group'];
            $phper_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['phper_complexity']];
            $phper_userid_arr['user_name'] = $this->extract_username($row_arr['phper_user_id'],'phper_user');
            $phper_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['phper_expect_time']));
            $phper_userid_arr['is_finish'] = $row_arr['phper_is_finish'];
            $phper_userid_arr['finish_time'] = $row_arr['phper_finish_time'];
            $phper_userid_arr['note'] = $row_arr['phper_note'];
        }

        //如果已分配app人员
        $app_userid_arr = array();
        if($row_arr['app_user_id']){
            $app_userid_arr['group'] = $row_arr['app_group'];
            $app_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['app_complexity']];
            $app_userid_arr['user_name'] = $this->extract_username($row_arr['app_user_id'],'app_user');
            $app_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['app_expect_time']));
            $app_userid_arr['is_finish'] = $row_arr['app_is_finish'];
            $app_userid_arr['finish_time'] = $row_arr['app_finish_time'];
            $app_userid_arr['note'] = $row_arr['app_note'];
        }


        $this->view->assign("web_userid_arr", $web_userid_arr);
        $this->view->assign("phper_userid_arr", $phper_userid_arr);
        $this->view->assign("app_userid_arr", $app_userid_arr);
        $this->view->assign("year_time", $year_time);
        $this->view->assign('demand_type',input('demand_type'));
        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }

    /**
     * 测试完成方法
     * 测试组权限
     */
    public function test_group_finish($ids = null)
    {
        if ($this->request->isAjax()) {
            $is_all_test = input('is_all_test');
            if($is_all_test == 1){
                $ding_type = '_end';
                $data['status'] =  7;
                $data['return_test_is_finish'] =  1;
                $data['return_test_finish_time'] =  date('Y-m-d H:i',time());
                $data['all_finish_time'] =  date('Y-m-d H:i',time());
            }else{
                $ding_type = '_wait';
                $data['status'] =  5;
                $data['test_is_finish'] =  1;
                $data['test_finish_time'] =  date('Y-m-d H:i',time());
            }
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                // Ding::dingHook(__FUNCTION__ . $ding_type, $this ->model ->get(input('ids')));
                Ding::dingHook(__FUNCTION__, $this ->model ->get(input('ids')));
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }

        $ids = $ids ?? input('id');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
        $year_time = date('Y-m-d H:i',time());

        //如果已分配前端人员
        $web_userid_arr = array();
        if($row_arr['web_designer_user_id']){
            $web_userid_arr['group'] = $row_arr['web_designer_group'];
            $web_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['web_designer_complexity']];
            $web_userid_arr['user_name'] = $this->extract_username($row_arr['web_designer_user_id'],'web_designer_user');
            $web_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['web_designer_expect_time']));
            $web_userid_arr['is_finish'] = $row_arr['web_designer_is_finish'];
            $web_userid_arr['finish_time'] = $row_arr['web_designer_finish_time'];
        }
        //如果已分配后端人员
        $phper_userid_arr = array();
        if($row_arr['phper_user_id']){
            $phper_userid_arr['group'] = $row_arr['phper_group'];
            $phper_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['phper_complexity']];
            $phper_userid_arr['user_name'] = $this->extract_username($row_arr['phper_user_id'],'phper_user');
            $phper_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['phper_expect_time']));
            $phper_userid_arr['is_finish'] = $row_arr['phper_is_finish'];
            $phper_userid_arr['finish_time'] = $row_arr['phper_finish_time'];
        }

        //如果已分配app人员
        $app_userid_arr = array();
        if($row_arr['app_user_id']){
            $app_userid_arr['group'] = $row_arr['app_group'];
            $app_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['app_complexity']];
            $app_userid_arr['user_name'] = $this->extract_username($row_arr['app_user_id'],'app_user');
            $app_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['app_expect_time']));
            $app_userid_arr['is_finish'] = $row_arr['app_is_finish'];
            $app_userid_arr['finish_time'] = $row_arr['app_finish_time'];
        }


        $this->view->assign("web_userid_arr", $web_userid_arr);
        $this->view->assign("phper_userid_arr", $phper_userid_arr);
        $this->view->assign("app_userid_arr", $app_userid_arr);
        $this->view->assign("year_time", $year_time);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }

    /**
     * 测试记录问题
     * 测试组权限
     */
    public function test_record_bug(){
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['opt_type']){
                    if ($params['opt_type']==1){
                        $data['is_complete']=1;
                        $where['id'] = $params['id'];
                        $res = $this->testRecordModel->allowField(true)->save($data, $where);
                        if ($res) {
                            $this->success('成功');

                        } else {
                            $this->error('失败');
                        }
                    }elseif ($params['opt_type']==2){

                        $data['is_del']=2;
                        $where['id']=$params['id'];
                        $res = $this->testRecordModel->allowField(true)->save($data,$where);
                        if ($res) {
                            $this->success('成功');
                        } else {
                            $this->error('失败');
                        }

                    }

                }else{
                $params['create_time'] =  date('Y-m-d H:i',time());
                $params['create_user_id'] =  $this->auth->id;
                $res_status = $this->testRecordModel->allowField(true)->save($params);

                if ($res_status) {
                    Ding::dingHook(__FUNCTION__, $this->model->get(['id' => $params['pid']]));
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $ids = $ids ?? input('ids');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
        $year_time = date('Y-m-d H:i',time());

        if($row['web_designer_group'] == 1 && $row['web_designer_user_id'] != ''){
            $row_arr['web_designer_username'] = $this->extract_username($row['web_designer_user_id'],'web_designer_user');
        }
        if($row['phper_group'] == 1 && $row['phper_user_id'] != ''){
            $row_arr['phper_username'] = $this->extract_username($row['phper_user_id'],'phper_user');
        }
        if($row['app_group'] == 1 && $row['app_user_id'] != ''){
            $row_arr['app_username'] = $this->extract_username($row['app_user_id'],'app_user');
        }
        /*测试日志--测试环境*/
        $left_test_list = $this->testRecordModel
            ->where('pid',$ids)
            ->where('is_del',1)
            ->where('type', $row_arr['type'])
            ->where('environment_type', 1)
            ->order('id', 'desc')
            ->select();
        $left_test_list = collection($left_test_list)->toArray();
        foreach ($left_test_list as $k_left => $v_left){
            if($v_left['responsibility_group'] == 1){
                $left_test_list[$k_left]['responsibility_user_name'] = $this->extract_username($row['web_designer_user_id'],'web_designer_user');
            }
            if($v_left['responsibility_group'] == 2){
                $left_test_list[$k_left]['responsibility_user_name'] = $this->extract_username($row['phper_user_id'],'phper_user');
            }
            if($v_left['responsibility_group'] == 3){
                $left_test_list[$k_left]['responsibility_user_name'] = $this->extract_username($row['app_user_id'],'app_user');
            }
            $left_test_list[$k_left]['create_time'] = date('m-d H:i',strtotime($v_left['create_time']));
            $left_test_list[$k_left]['create_user_name'] = config('demand.test_user')[$v_left['create_user_id']];
        }

        /*测试日志--正式环境*/
        $right_test_list = $this->testRecordModel
            ->where('pid',$ids)
            ->where('is_del',1)
            ->where('type', $row_arr['type'])
            ->where('environment_type', 2)
            ->order('id', 'desc')
            ->select();
        $right_test_list = collection($right_test_list)->toArray();
        foreach ($right_test_list as $k_right => $v_right){
            if($v_right['responsibility_group'] == 1){
                $right_test_list[$k_right]['responsibility_user_name'] = $this->extract_username($row['web_designer_user_id'],'web_designer_user');
            }
            if($v_right['responsibility_group'] == 2){
                $right_test_list[$k_right]['responsibility_user_name'] = $this->extract_username($row['phper_user_id'],'phper_user');
            }
            if($v_right['responsibility_group'] == 3){
                $right_test_list[$k_right]['responsibility_user_name'] = $this->extract_username($row['app_user_id'],'app_user');
            }
            $right_test_list[$k_right]['create_time'] = date('m-d H:i',strtotime($v_right['create_time']));
            $right_test_list[$k_right]['create_user_name'] = config('demand.test_user')[$v_right['create_user_id']];
        }

        $bug_type = config('demand.bug_type');//严重类型
        $this->view->assign("bug_type", $bug_type);
        $this->view->assign("left_test_list", $left_test_list);
        $this->view->assign("right_test_list", $right_test_list);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }

    /**
     * 上线需求
     * 开发组权限
     * */
    public function add_online($ids = null)
    {
        if ($this->request->isAjax()) {
            $ids = $ids ?? input('ids');
            $row = $this->model->get(['id' => $ids]);
            $row_arr = $row->toArray();

            if($row_arr['test_group'] == 2){
                $data['status'] =  7;
                $data['all_finish_time'] =  date('Y-m-d H:i',time());
            }else if($row_arr['test_group'] == 1){
                $data['status'] =  6;
            }
            $res = $this->model->allowField(true)->save($data,['id'=> $ids]);
            if ($res) {
                Ding::dingHook(__FUNCTION__, $this ->model ->get($ids));
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    /**
     * 本条目详情，包含测试日志，以及bug回复日志
     * 测试组权限
     */
    public function detail_log($ids=null){

        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['opt_type']==1){
                    $data['is_complete']=1;
                    $where['id'] = $params['id'];
                    $res = $this->testRecordModel->allowField(true)->save($data, $where);
                    if ($res) {
                        $this->success('成功');

                    } else {
                        $this->error('失败');
                    }
                }elseif ($params['opt_type']==2){

                    $data['is_del']=2;
                    $where['id']=$params['id'];
                    $res = $this->testRecordModel->allowField(true)->save($data,$where);
                    if ($res) {
                        $this->success('成功');
                    } else {
                        $this->error('失败');
                    }

                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $ids = $ids ?? input('ids');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
        $year_time = date('Y-m-d H:i',time());

        if($row['web_designer_group'] == 1 && $row['web_designer_user_id'] != ''){
            $row_arr['web_designer_username'] = $this->extract_username($row['web_designer_user_id'],'web_designer_user');
        }
        if($row['phper_group'] == 1 && $row['phper_user_id'] != ''){
            $row_arr['phper_username'] = $this->extract_username($row['phper_user_id'],'phper_user');
        }
        if($row['app_group'] == 1 && $row['app_user_id'] != ''){
            $row_arr['app_username'] = $this->extract_username($row['app_user_id'],'app_user');
        }
        /*测试日志--测试环境*/
        $left_test_list = $this->testRecordModel
            ->where('pid',$ids)
            ->where('is_del',1)
            ->where('type', $row_arr['type'])
            ->where('environment_type', 1)
            ->order('id', 'desc')
            ->select();
        $left_test_list = collection($left_test_list)->toArray();
        foreach ($left_test_list as $k_left => $v_left){
            if($v_left['responsibility_group'] == 1){
                $left_test_list[$k_left]['responsibility_user_name'] = $this->extract_username($row['web_designer_user_id'],'web_designer_user');
            }
            if($v_left['responsibility_group'] == 2){
                $left_test_list[$k_left]['responsibility_user_name'] = $this->extract_username($row['phper_user_id'],'phper_user');
            }
            if($v_left['responsibility_group'] == 3){
                $left_test_list[$k_left]['responsibility_user_name'] = $this->extract_username($row['app_user_id'],'app_user');
            }
            $left_test_list[$k_left]['create_time'] = date('m-d H:i',strtotime($v_left['create_time']));
            $left_test_list[$k_left]['create_user_name'] = config('demand.test_user')[$v_left['create_user_id']];
        }

        /*测试日志--正式环境*/
        $right_test_list = $this->testRecordModel
            ->where('pid',$ids)
            ->where('is_del',1)
            ->where('type', $row_arr['type'])
            ->where('environment_type', 2)
            ->order('id', 'desc')
            ->select();
        $right_test_list = collection($right_test_list)->toArray();
        foreach ($right_test_list as $k_right => $v_right){
            if($v_right['responsibility_group'] == 1){
                $right_test_list[$k_right]['responsibility_user_name'] = $this->extract_username($row['web_designer_user_id'],'web_designer_user');
            }
            if($v_right['responsibility_group'] == 2){
                $right_test_list[$k_right]['responsibility_user_name'] = $this->extract_username($row['phper_user_id'],'phper_user');
            }
            if($v_right['responsibility_group'] == 3){
                $right_test_list[$k_right]['responsibility_user_name'] = $this->extract_username($row['app_user_id'],'app_user');
            }
            $right_test_list[$k_right]['create_time'] = date('m-d H:i',strtotime($v_right['create_time']));
            $right_test_list[$k_right]['create_user_name'] = config('demand.test_user')[$v_right['create_user_id']];
        }
        $bug_type = config('demand.bug_type');//严重类型
        $this->view->assign('demand_type',input('demand_type'));
        $this->view->assign("bug_type", $bug_type);
        $this->view->assign("left_test_list", $left_test_list);
        $this->view->assign("right_test_list", $right_test_list);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }


    /**
    *  bug列表页面
     * 是否扣除测试绩效
     */

    public function opt_test_duty($ids = null)
    {
        if ($this->request->isAjax()) {
            $is_test_duty = input('is_test_duty');
            if($is_test_duty == 1){
                $data['is_test_duty'] =  1;
            }else{
                $data['is_test_duty'] =  0;
            }
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                // Ding::dingHook(__FUNCTION__ . $ding_type, $this ->model ->get(input('ids')));
//                Ding::dingHook(__FUNCTION__, $this ->model ->get(input('ids')));
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }

        $ids = $ids ?? input('id');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
        $year_time = date('Y-m-d H:i',time());

        //如果已分配前端人员
        $web_userid_arr = array();
        if($row_arr['web_designer_user_id']){
            $web_userid_arr['group'] = $row_arr['web_designer_group'];
            $web_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['web_designer_complexity']];
            $web_userid_arr['user_name'] = $this->extract_username($row_arr['web_designer_user_id'],'web_designer_user');
            $web_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['web_designer_expect_time']));
            $web_userid_arr['is_finish'] = $row_arr['web_designer_is_finish'];
            $web_userid_arr['finish_time'] = $row_arr['web_designer_finish_time'];
        }
        //如果已分配后端人员
        $phper_userid_arr = array();
        if($row_arr['phper_user_id']){
            $phper_userid_arr['group'] = $row_arr['phper_group'];
            $phper_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['phper_complexity']];
            $phper_userid_arr['user_name'] = $this->extract_username($row_arr['phper_user_id'],'phper_user');
            $phper_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['phper_expect_time']));
            $phper_userid_arr['is_finish'] = $row_arr['phper_is_finish'];
            $phper_userid_arr['finish_time'] = $row_arr['phper_finish_time'];
        }

        //如果已分配app人员
        $app_userid_arr = array();
        if($row_arr['app_user_id']){
            $app_userid_arr['group'] = $row_arr['app_group'];
            $app_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['app_complexity']];
            $app_userid_arr['user_name'] = $this->extract_username($row_arr['app_user_id'],'app_user');
            $app_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['app_expect_time']));
            $app_userid_arr['is_finish'] = $row_arr['app_is_finish'];
            $app_userid_arr['finish_time'] = $row_arr['app_finish_time'];
        }


        $this->view->assign("web_userid_arr", $web_userid_arr);
        $this->view->assign("phper_userid_arr", $phper_userid_arr);
        $this->view->assign("app_userid_arr", $app_userid_arr);
        $this->view->assign("year_time", $year_time);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }


    /**
    *  bug列表页面
     * 是否工作时间处理问题
     */

    public function opt_work_time($ids = null)
    {
        if ($this->request->isAjax()) {
            $is_test_duty = input('is_work_time');
            if($is_test_duty == 1){
                $data['is_work_time'] =  1;
            }else{
                $data['is_work_time'] =  0;
            }
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                // Ding::dingHook(__FUNCTION__ . $ding_type, $this ->model ->get(input('ids')));
//                Ding::dingHook(__FUNCTION__, $this ->model ->get(input('ids')));
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }

        $ids = $ids ?? input('id');
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();
        $year_time = date('Y-m-d H:i',time());

        //如果已分配前端人员
        $web_userid_arr = array();
        if($row_arr['web_designer_user_id']){
            $web_userid_arr['group'] = $row_arr['web_designer_group'];
            $web_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['web_designer_complexity']];
            $web_userid_arr['user_name'] = $this->extract_username($row_arr['web_designer_user_id'],'web_designer_user');
            $web_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['web_designer_expect_time']));
            $web_userid_arr['is_finish'] = $row_arr['web_designer_is_finish'];
            $web_userid_arr['finish_time'] = $row_arr['web_designer_finish_time'];
        }
        //如果已分配后端人员
        $phper_userid_arr = array();
        if($row_arr['phper_user_id']){
            $phper_userid_arr['group'] = $row_arr['phper_group'];
            $phper_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['phper_complexity']];
            $phper_userid_arr['user_name'] = $this->extract_username($row_arr['phper_user_id'],'phper_user');
            $phper_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['phper_expect_time']));
            $phper_userid_arr['is_finish'] = $row_arr['phper_is_finish'];
            $phper_userid_arr['finish_time'] = $row_arr['phper_finish_time'];
        }

        //如果已分配app人员
        $app_userid_arr = array();
        if($row_arr['app_user_id']){
            $app_userid_arr['group'] = $row_arr['app_group'];
            $app_userid_arr['complexity'] = config('demand.allComplexity')[$row_arr['app_complexity']];
            $app_userid_arr['user_name'] = $this->extract_username($row_arr['app_user_id'],'app_user');
            $app_userid_arr['expect_time'] = date('Y-m-d H:i',strtotime($row_arr['app_expect_time']));
            $app_userid_arr['is_finish'] = $row_arr['app_is_finish'];
            $app_userid_arr['finish_time'] = $row_arr['app_finish_time'];
        }


        $this->view->assign("web_userid_arr", $web_userid_arr);
        $this->view->assign("phper_userid_arr", $phper_userid_arr);
        $this->view->assign("app_userid_arr", $app_userid_arr);
        $this->view->assign("year_time", $year_time);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }



}
