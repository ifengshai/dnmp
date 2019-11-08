<?php

namespace app\admin\controller\saleaftermanage;

use think\Db;
use fast\Tree;
use app\admin\model\Admin;
use think\Request;
use app\common\controller\Backend;
use app\admin\model\AuthGroup;
use app\admin\model\saleaftermanage\SaleAfterIssue;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\SaleAfterTaskRemark;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;


/**
 * 
 *
 * @icon fa fa-circle-o
 */
class SaleAfterTask extends Backend
{
    
    /**
     * SaleAfterTask模型对象
     * @var \app\admin\model\saleAfterManage\SaleAfterTask
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $groupdata = [];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\SaleAfterTask;
        //新加内容
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();
        Tree::instance()->init($groupList);
        $result = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        } else {
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $result = array_merge($result, Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['pid'])));
            }
        }
        //dump($result);
        $groupName = [];
        foreach ($result as $k => $v) {
            //$groupName['group_id'][] = $v['id'];
            $groupName[$v['id']] = $v['name'];
        }
        $staffArr = array_keys($groupName);
        $staffList = (new Admin())->getStaffList($staffArr);
        $this->groupdata = $groupName;
        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_ids' => $this->auth->getGroupIds()]);
        $this->view->assign('staffList',$staffList);
        $this->view->assign('groupdata', $this->groupdata);
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign('prtyIdList',$this->model->getPrtyIdList());
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(1,0));
        $this->view->assign('getTabList',$this->model->getTabList());

    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
               ->with(['saleAfterIssue'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
               ->with(['saleAfterIssue'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();
            foreach ($list as $key => $val){
                if($val['dept_id']){
                    $list[$key]['dept_id']= $deptArr[$val['dept_id']];

                }
                if($val['rep_id']){
                    $list[$key]['rep_id'] = $repArr[$val['rep_id']];
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
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if(!isset($params['problem_id'])){
                $this->error(__('Please select the problem category'));
            }
            if ($params) {
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
                    $params['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname'); //创建人
                    $params['create_time']   = date("Y-m-d H:i:s",time());
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
                    $this->success('','saleaftermanage/sale_after_task/index');
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['task_status'] ==2){ //如果任务已经处理完成
            $this->error(__('该任务已经处理完成，无需再次编辑'),'saleaftermanage/sale_after_task/index',30);
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
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
                    // if(!empty($params['task_remark'])){
                    //     $data = [];
                    //     $data['tid'] = $tid;
                    //     $data['remark_record'] = strip_tags($params['task_remark']);
                    //     $data['create_person'] = session('admin.nickname');
                    //     $data['create_time']   = date("Y-m-d H:i:s",time());
                    //     (new SaleAfterTaskRemark())->allowField(true)->save($data);
                    // }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if (1 == $row['order_platform']) {
            $orderInfo = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($row['order_number']);
        } elseif (2 == $row['order_platform']) {
            $orderInfo = VooguemePrescriptionDetailHelper::get_one_by_increment_id($row['order_number']);
        } elseif (3 == $row['order_platform']) {
            $orderInfo = NihaoPrescriptionDetailHelper::get_one_by_increment_id($row['order_number']);
        }
        //求出本条记录相对应的售后备注记录
        $row->task_remark = (new SaleAfterTaskRemark())->getRelevanceRecord($ids);
        $this->view->assign("row", $row);
        $this->view->assign('orderInfo',$orderInfo);
        $this->view->assign('orderPlatform',$result['order_platform']);
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
        return $this->view->fetch();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /***
     * 异步请求获取订单所在平台和订单号处理(原先)
     * @param
     */
    // public function ajax( Request $request)
    // {
    //     if($this->request->isAjax()){
    //         $ordertype = $request->post('ordertype');
    //         $order_number = $request->post('order_number');
    //         if($ordertype<1 || $ordertype>5){ //不在平台之内
    //            return  $this->error('选择平台错误，请重新选择','','error',0);
    //         }
    //         if(!$order_number){
    //            return  $this->error('订单号不存在，请重新选择','','error',0);
    //         }
    //         $result = $this->model->getOrderInfo($ordertype,$order_number);
    //         if(!$result){
    //             return $this->error('找不到这个订单，请重新尝试','','error',0);
    //         }
    //         return $this->success('','',$result,0);
    //     }else{
    //         return $this->error('404 Not Found');
    //     }


    // }
    /***
     * 修改之后的异步请求获取订单所在平台和订单号处理
     */
        public function ajax(Request $request){
            if($this->request->isAjax()){
                $ordertype = $request->post('ordertype');
                $order_number = $request->post('order_number');
                if($ordertype<1 || $ordertype>5){ //不在平台之内
                    return $this->error('选择平台错误,请重新选择','','error',0);
                }
                if(!$order_number){
                    return  $this->error('订单号不存在，请重新选择','','error',0);
                }
                if ($ordertype == 1) {
                    $result = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 2) {
                    $result = VooguemePrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 3) {
                    $result = NihaoPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                }
                if(!$result){
                    return $this->error('找不到这个订单,请重新尝试','','error',0);
                }
                    return $this->success('','',$result,0);
            }else{
                return $this->error('404 Not Found');
            }
        }    
    /***
     * 异步获取订单平台
     * type 为 2 没有选择平台
     */
    public function getAjaxOrderPlatformList()
    {
        if($this->request->isAjax()){
            $json = (new MagentoPlatform())->getOrderPlatformList();
            if(!$json){
                $json = [0=>'请添加订单平台'];
            }
            $arrToObject = (object)($json);
            return json($arrToObject);
        }else{
            return $this->error('404 Not Found');
        }
    }

    /***
     * 异步获取问题列表
     */
    public function ajaxGetIssueList()
    {
        if($this->request->isAjax()){
            $json = (new SaleAfterIssue())->getAjaxIssueList();
            if(!$json){
                $json = [0=>'请先添加任务问题'];
            }
            $arrToObject = (object)($json);
            return json($arrToObject);
        }else{
            return $this->error('404 Not Found');    
        }

    }
    /***
     * 查看任务详情
     * $param id  任务ID
     */
    public function detail(Request $request)
    {
        $id = $request->param('ids');
        if(!$id){
            $this->error('参数错误，请重新尝试','saleaftermanage/sale_after_task/index');
        }
        $result = $this->model->getTaskDetail($id);
        $result['problem_desc'] = strip_tags($result['problem_desc']);
        if(!$result){
            $this->error('任务信息不存在，请重新尝试','saleaftermanage/sale_after_task/index');
        }
        if (1 == $result['order_platform']) {
            $orderInfo = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        } elseif (2 == $result['order_platform']) {
            $orderInfo = VooguemePrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        } elseif (3 == $result['order_platform']) {
            $orderInfo = NihaoPrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        }
        $this->view->assign('row',$result);
        $this->view->assign('orderPlatform',$result['order_platform']);
        $this->view->assign('orderInfo',$orderInfo);
        return $this->view->fetch();
    }
    /***
     * 异步更新任务状态(单个)
     */
    public function completeAjax(Request $request)
    {
        if($this->request->isGet()){
            $idss = $request->param('idss');
            if(!$idss){
              return   $this->$this->error('处理失败，请重新尝试');
            }
            $data = [];
            $data['task_status'] = 2;
            $data['handle_time'] = date("Y-m-d H:i:s",time());
            $result = $this->model->where('id',$idss)->update($data);
            if($result !== false){
              return  $this->success('处理成功','saleaftermanage/sale_after_task');
            }
        }else{
            return $this->error('请求失败,请勿请求');
        }
    }

    /***
     * 异步更新任务状态(多个)
     */
    public function completeAjaxAll($ids=null)
    {
        if($this->request->isAjax()){
            if(!$ids){
              return   $this->error('处理失败，请重新尝试');
            }
            $map['id'] = ['in',$ids];
            $list = $this->model->where($map)->field('id,task_status')->select();
            $arr = [];
            foreach($list as $val){
                // if($val['task_status'] ==1){
                //     $arr[] = $val['id'];    
                // }
                $arr[] = $val['id'];
            }
            if(!empty($arr)){
                $data = [];
                $data['task_status'] = 2;
                $data['handle_time'] = date("Y-m-d H:i:s",time());
                $result = $this->model->where('id','in',$arr)->update($data);
                if($result !== false){
                  return  $this->success('操作成功');
                }
            }else{
                return $this->error(__('Select the updated record does not meet the requirements, please re-select'));
            }

        }else{
            return $this->error('请求失败,请勿请求');
        }
    }
    /***
     * 处理任务
     */
    public function handle_task($ids=null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['task_status'] ==2){ //如果任务已经处理完成
            $this->error('该任务已经处理完成，无需再次处理','saleaftermanage/sale_after_task','',0);
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $tid = $params['id'];
            unset($params['id']);
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
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
                    if(!empty($params['task_remark'])){
                        $data = [];
                        $data['tid'] = $tid;
                        $data['remark_record'] = strip_tags($params['task_remark']);
                        $data['create_person'] = session('admin.nickname');
                        $data['create_time']   = date("Y-m-d H:i:s",time());
                        (new SaleAfterTaskRemark())->allowField(true)->save($data);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //求出本条记录相对应的售后备注记录
        $row->task_remark = (new SaleAfterTaskRemark())->getRelevanceRecord($ids);
        $row['problem_desc'] = strip_tags($row['problem_desc']);
        $this->view->assign("row", $row);
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
        return $this->view->fetch();
    }
}
