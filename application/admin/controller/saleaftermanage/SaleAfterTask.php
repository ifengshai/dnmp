<?php

namespace app\admin\controller\saleaftermanage;

use think\Db;
use fast\Tree;
use app\admin\model\Admin;
use think\Request;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\controller\Backend;
use app\admin\model\AuthGroup;
use app\admin\model\saleaftermanage\SaleAfterIssue;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\SaleAfterTaskRemark;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


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
    protected $worklist = null;
    protected $workremark = null;
    protected $changesku = null;
    protected $relationSearch = true;
    protected $groupdata = [];
    // protected $noNeedLogin = [
    //     'updateRepId',
    //     'updateMoreRepId',
    //     'updateCategoryVoogueme',
    //     'updateCategoryNihao',
    //     'updateCategoryProblemIdNihao',
    //     'contrastCategory',
    //     'zeeloolSaleImport'
    // ];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\SaleAfterTask;
        $this->worklist = new \app\admin\model\saleaftermanage\WorkOrderList;
        $this->workremark = new \app\admin\model\saleaftermanage\WorkOrderRemark;
        $this->changesku  = new \app\admin\model\saleaftermanage\WorkOrderChangeSku;
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
        $groupName = [];
        foreach ($result as $k => $v) {
            //$groupName['group_id'][] = $v['id'];
            $groupName[$v['id']] = $v['name'];
        }
        //$staffArr = array_keys($groupName);
        $staffList = (new Admin())->getStaffListss();
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
        $task_number = input('task_number');
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
            $issueArr = (new SaleAfterIssue())->getAjaxIssueList();
            $solveScheme = $this->model->getSolveScheme();
            foreach ($list as $key => $val){
				if ($val['problem_id']) {
                    $deptNumArr = explode(',', $val['problem_id']);
                    $list[$key]['problem'] = '';
                    foreach ($deptNumArr as $values) {
                        $list[$key]['problem'] .= $issueArr[$values] . ' ';
                    }
                }
                if($val['dept_id']){
                    $list[$key]['dept_id']= $deptArr[$val['dept_id']];

                }
                if($val['rep_id']){
                    $list[$key]['rep_id'] = $repArr[$val['rep_id']];
                }
                if($val['handle_scheme']){
                    $schemeArr = explode(',',$val['handle_scheme']);
                    $list[$key]['handle_scheme'] = '';
                    foreach($schemeArr as $sval){
                        $list[$key]['handle_scheme'] .= $solveScheme[$sval] . ' ';
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('task_number', $task_number ?? '');
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if((1==count($params['problem_id'])) && (in_array("",$params['problem_id']))){
                $this->error(__('Please select the problem category'));
            }
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
				if(1<=count($params['problem_id'])){
					$params['problem_id'] = implode(',',$params['problem_id']);
                }
                $handle_scheme = $params['handle_scheme'];
                if(1<=count($params['handle_scheme'])){
                    $params['handle_scheme'] = implode(',',$params['handle_scheme']);
                }
                //检查是否存在已经添加过的订单以及类型
				$checkInfo = $this->model->checkOrderInfo($params['order_number'],$params['problem_id']);
				if($checkInfo){
					$this->error(__('存在同样任务类型的未处理订单'));
                }
                if(in_array(1,$handle_scheme) || in_array(2,$handle_scheme)){
                    if(('0' == $params['refund_way'])||(0 == $params['refund_money'])){
                        $this->error(__('请选择退款方式和退款金额'));
                    }
                }
                if(in_array(3,$handle_scheme)){
                    if(empty($params['replacement_order'])){
                        $this->error(__('请填写补发单号'));
                    } 
                }
                if(in_array(4,$handle_scheme)){
                    if(empty($params['replacement_order']) || empty($params['make_up_price_order'])){
                        $this->error(__('请填写补发单号和补差价订单号'));
                    }                   
                }
                if(in_array(5,$handle_scheme)){
                    if(('0' == $params['refund_way']) || (0 == $params['refund_money']) || empty($params['replacement_order']) ){
                        $this->error(__('请填写退款方式,补发单号和补差价订单号'));
                    }                    
                }
                if(in_array(6,$handle_scheme)){
                    if(empty($params['give_coupon'])){
                        $this->error('请填写赠送的优惠券');
                    }
                }
                if(in_array(7,$handle_scheme)){
                    if(0 == $params['integral']){
                        $this->error('请填写发放积分数量');
                    }                    
                }
                // if($handle_scheme){
                //     switch($handle_scheme){
                //         case in_array(1,$handle_scheme):
                //         case in_array(2,$handle_scheme):
                //         if(('0' == $params['refund_way'])||(0 == $params['refund_money'])){
                //             $this->error(__('请选择退款方式和退款金额'));
                //         }
                //     break;
                //         case in_array(3,$handle_scheme):
                //         if(empty($params['replacement_order'])){
                //             $this->error(__('请填写补发单号'));
                //         }
                //     break;    
                //         case in_array(4,$handle_scheme):
                //         if(empty($params['replacement_order']) || empty($params['make_up_price_order'])){
                //             $this->error(__('请填写补发单号和补差价订单号'));
                //         }
                //     break;    
                //         case in_array(5,$handle_scheme):
                //         if(('0' == $params['refund_way']) || (0 == $params['refund_money']) || empty($params['replacement_order']) ){
                //             $this->error(__('请填写退款方式,补发单号和补差价订单号'));
                //         }
                //     break;    
                //         case in_array(6,$handle_scheme):
                //         if(empty($params['give_coupon'])){
                //             $this->error('请填写赠送的优惠券');
                //         }
                //     break;    
                //         case in_array(7,$handle_scheme):
                //         if(0 == $params['integral']){
                //             $this->error('请填写发放积分数量');
                //         }
                //     break;
                //     }
                // }
                //exit;
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
                    //$result = $this->model->allowField(true)->save($params);
                    $taskData = $params;
                    unset($taskData['task_remark']);
                    if(0<$taskData['refund_money']){
                        $taskData['is_refund'] = 2;
                    }else{
                        $taskData['is_refund'] = 1;
                    }
                    $resultId = Db::name('sale_after_task')->insertGetId($taskData);
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
                if ($resultId !== false) {
                    if(!empty($params['task_remark'])){
                        $data = [];
                        $data['tid'] = $resultId;
                        $data['remark_record'] = strip_tags($params['task_remark']);
                        $data['create_person'] = session('admin.nickname');
                        $data['create_time']   = date("Y-m-d H:i:s",time());
                        (new SaleAfterTaskRemark())->allowField(true)->save($data);
                    }
                    $this->success('','saleaftermanage/sale_after_task/index');
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $serviceArr = config('search.platform');
        $sessionId  = session('admin.id');
        foreach($serviceArr as $key => $kv){
             if(in_array($sessionId,$kv)){
                    $default= $key; 
             }   
        }
        //默认的客服分组值
        if($default){
            $this->view->assign("default",$default);
        }
        $this->view->assign('userId',session('admin.id'));
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
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
            $this->error(__('该任务已经处理完成，无需再次编辑'),'saleaftermanage/sale_after_task/index',50,50);
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
			if((1==count($params['problem_id'])) && (in_array("",$params['problem_id']))){
                $this->error(__('Please select the problem category'));
            }
            $params = $this->preExcludeFields($params);
			if(1<=count($params['problem_id'])){
				$params['problem_id'] = implode(',',$params['problem_id']);
			}
            $handle_scheme = $params['handle_scheme'];
            if(1<=count($params['handle_scheme'])){
                $params['handle_scheme'] = implode(',',$params['handle_scheme']);
            }                
            if(0<$params['refund_money']){
                $params['is_refund'] = 2;
            }else{
                $params['is_refund'] = 1;
            }
            if(in_array(1,$handle_scheme) || in_array(2,$handle_scheme)){
                if(('0' == $params['refund_way'])||(0 == $params['refund_money'])){
                    $this->error(__('请选择退款方式和退款金额'));
                }
            }
            if(in_array(3,$handle_scheme)){
                if(empty($params['replacement_order'])){
                    $this->error(__('请填写补发单号'));
                } 
            }
            if(in_array(4,$handle_scheme)){
                if(empty($params['replacement_order']) || empty($params['make_up_price_order'])){
                    $this->error(__('请填写补发单号和补差价订单号'));
                }                   
            }
            if(in_array(5,$handle_scheme)){
                if(('0' == $params['refund_way']) || (0 == $params['refund_money']) || empty($params['replacement_order']) ){
                    $this->error(__('请填写退款方式,补发单号和补差价订单号'));
                }                    
            }
            if(in_array(6,$handle_scheme)){
                if(empty($params['give_coupon'])){
                    $this->error('请填写赠送的优惠券');
                }
            }
            if(in_array(7,$handle_scheme)){
                if(0 == $params['integral']){
                    $this->error('请填写发放积分数量');
                }                    
            }            
            // if($handle_scheme){
            //     switch($handle_scheme){
            //         case in_array(1,$handle_scheme):
            //         case in_array(2,$handle_scheme):
            //         if(('0' == $params['refund_way'])||(0 == $params['refund_money'])){
            //             $this->error(__('请选择退款方式和退款金额'));
            //         }
            //         case in_array(3,$handle_scheme):
            //         if(empty($params['replacement_order'])){
            //             $this->error(__('请填写补发单号'));
            //         }
            //         case in_array(4,$handle_scheme):
            //         if(empty($params['replacement_order']) || empty($params['make_up_price_order'])){
            //             $this->error(__('请填写补发单号和补差价订单号'));
            //         }
            //         case in_array(5,$handle_scheme):
            //         if(('0' == $params['refund_way']) || (0 == $params['refund_money']) || empty($params['replacement_order']) ){
            //             $this->error(__('请填写退款方式,补发单号和补差价订单号'));
            //         }
            //         case in_array(6,$handle_scheme):
            //         if(empty($params['give_coupon'])){
            //             $this->error('请填写赠送的优惠券');
            //         }
            //         case in_array(7,$handle_scheme):
            //         if(0 == $params['integral']){
            //             $this->error('请填写发放积分数量');
            //         }
            //         break;
            //     }
            // }
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
                    $data['tid'] = $row['id'];
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
                if($ordertype<1 || $ordertype>11){ //不在平台之内
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
                }elseif(5 == $ordertype){
                    $result = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($order_number);
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
        } elseif( 5 == $result['order_platform']){
            $orderInfo = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        }
		$issueArr = (new SaleAfterIssue())->getAjaxIssueList();
		$result['problem_id'] = explode(',', $result['problem_id']);
        $this->view->assign('row',$result);
		$this->view->assign('issueArr',$issueArr);
        $this->view->assign('orderPlatform',$result['order_platform']);
        $this->view->assign('orderInfo',$orderInfo);
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
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
            $row = $this->model->get($idss);
            if(2 == $row['task_status']){
                $this->error('已经处理完成,无需再次操作！！');
            }
            if(false == $row['handle_scheme']){
              return   $this->error('没有选择解决方案，无法处理完成');
            }
            $data = [];
            $data['task_status'] = 2;
            $data['handle_time'] = date("Y-m-d H:i:s",time());
            $data['complete_time'] = date("Y-m-d H:i:s",time());
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
            $list = $this->model->where($map)->field('id,task_status,handle_scheme')->select();
            foreach($list as $val){
                if ( 2 <= $val['task_status']) {
                    $this->error('此状态无法处理完成操作！！');
                }
                if(false == $val['handle_scheme']){
                    $this->error('没有选择解决方案，无法处理完成');
                }
            }
            $data = [];
            $data['task_status'] = 2;
            $data['handle_time'] = date("Y-m-d H:i:s",time());
            $data['complete_time'] = date("Y-m-d H:i:s",time());
            $result = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if (false !== $result) {
                return $this->success('处理成功');
            } else {
                return $this->error('处理失败');
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
        if($row['task_status'] >=2){ //如果任务已经处理完成
            $this->error('该状态无法处理！！','saleaftermanage/sale_after_task');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
			if((1==count($params['problem_id'])) && (in_array("",$params['problem_id']))){
                $this->error(__('Please select the problem category'));
            }
			if(1<=count($params['problem_id'])){
				$params['problem_id'] = implode(',',$params['problem_id']);
            }
            if(2 == $params['task_status']){
                $params['complete_time'] = date("Y-m-d H:i:s",time());
                if(in_array(0,$params['handle_scheme'])){
                    $this->error('请选择正确的解决方案');    
                }
            }
            $handle_scheme = $params['handle_scheme'];
            if(1<=count($params['handle_scheme'])){
                $params['handle_scheme'] = implode(',',$params['handle_scheme']);
            }            
            $tid = $params['id'];
            unset($params['id']);
            if ($params) {
                $params = $this->preExcludeFields($params);
                if(0<$params['refund_money']){
                    $params['is_refund'] = 2;
                }else{
                    $params['is_refund'] = 1;
                }
                if(in_array(1,$handle_scheme) || in_array(2,$handle_scheme)){
                    if(('0' == $params['refund_way'])||(0 == $params['refund_money'])){
                        $this->error(__('请选择退款方式和退款金额'));
                    }
                }
                if(in_array(3,$handle_scheme)){
                    if(empty($params['replacement_order'])){
                        $this->error(__('请填写补发单号'));
                    } 
                }
                if(in_array(4,$handle_scheme)){
                    if(empty($params['replacement_order']) || empty($params['make_up_price_order'])){
                        $this->error(__('请填写补发单号和补差价订单号'));
                    }                   
                }
                if(in_array(5,$handle_scheme)){
                    if(('0' == $params['refund_way']) || (0 == $params['refund_money']) || empty($params['replacement_order']) ){
                        $this->error(__('请填写退款方式,补发单号和补差价订单号'));
                    }                    
                }
                if(in_array(6,$handle_scheme)){
                    if(empty($params['give_coupon'])){
                        $this->error('请填写赠送的优惠券');
                    }
                }
                if(in_array(7,$handle_scheme)){
                    if(0 == $params['integral']){
                        $this->error('请填写发放积分数量');
                    }                    
                }                
                // if($handle_scheme){
                //     switch($handle_scheme){
                //         case in_array(1,$handle_scheme):
                //         case in_array(2,$handle_scheme):
                //         if(('0' == $params['refund_way'])||(0 == $params['refund_money'])){
                //             $this->error(__('请选择退款方式和退款金额'));
                //         }
                //         case in_array(3,$handle_scheme):
                //         if(empty($params['replacement_order'])){
                //             $this->error(__('请填写补发单号'));
                //         }
                //         case in_array(4,$handle_scheme):
                //         if(empty($params['replacement_order']) || empty($params['make_up_price_order'])){
                //             $this->error(__('请填写补发单号和补差价订单号'));
                //         }
                //         case in_array(5,$handle_scheme):
                //         if(('0' == $params['refund_way']) || (0 == $params['refund_money']) || empty($params['replacement_order']) ){
                //             $this->error(__('请填写退款方式,补发单号和补差价订单号'));
                //         }
                //         case in_array(6,$handle_scheme):
                //         if(empty($params['give_coupon'])){
                //             $this->error('请填写赠送的优惠券');
                //         }
                //         case in_array(7,$handle_scheme):
                //         if(0 == $params['integral']){
                //             $this->error('请填写发放积分数量');
                //         }
                //         break;
                //     }
                // }
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
    /**
     * 取消
     */
    public function closed($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,task_status')->select();
            foreach ($row as $v) {
                if ( 0 != $v['task_status']) {
                    $this->error('只有新建状态才能操作！！');
                }
            }
            $data['task_status'] = 3;
            $result = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if (false !== $result) {
                return $this->success('确认成功');
            } else {
                return $this->error('确认失败');
            }
        } else {
            return $this->error('404 Not Found');
        }
    }
    //批量导出xls
/*     public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere.= " AND sale_after_task.id IN ({$ids})";
        }

        list($where) = $this->buildparams();
        $list = $this->model
        ->with(['saleAfterIssue'])
        ->where($where)
        ->where($addWhere)
        ->select();
        // dump($list);
        // exit;
        $repArr  = (new Admin())->getAllStaff();
        $list = collection($list)->toArray();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "任务单号")
            ->setCellValue("B1", "任务状态")
            ->setCellValue("C1", "订单平台");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "订单来源")
            ->setCellValue("E1", "订单号");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "订单SKU")
            ->setCellValue("G1", "客户姓名");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "客户邮箱")
            ->setCellValue("I1", "订单状态")
            ->setCellValue("J1", "承接人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "是否含有退款")
            ->setCellValue("L1", "退款金额")
            ->setCellValue("M1", "任务优先级")
            ->setCellValue("N1", "问题分类")
            ->setCellValue("O1", "问题描述")
            ->setCellValue("P1", "解决方案")
            ->setCellValue("Q1", "关税")
            ->setCellValue("R1", "赠送的优惠券")
            ->setCellValue("S1", "补差价订单号")
            ->setCellValue("T1", "补发订单号")
            ->setCellValue("U1", "创建人")
            ->setCellValue("V1", "创建时间")
            ->setCellValue("W1", "处理时间")
            ->setCellValue("X1", "完成时间")
            ->setCellValue("Y1", "退款方式");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('售后任务数据');

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['task_number']);
            switch($value['task_status']){
                case 1:
                $value['task_status'] = '处理中';
                break;
                case 2:
                $value['task_status'] = '处理完成';
                break;
                case 3:
                $value['task_status'] = '取消';
                default:
                $value['task_status'] = '未处理';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['task_status']);
            switch($value['order_platform']){
                case 2:
                $value['order_platform'] = 'voogueme';
                break;
                case 3:
                $value['order_platform'] = 'nihao';
                break;
                case 4:
                $value['order_platform'] = 'amazon';
                break;
                case 5:
                $value['order_platform'] = 'wesee';
                break;
                default:
                $value['order_platform'] = 'zeelool';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['order_platform']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['order_source'] == 1 ? 'pc端' :'移动端');
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['order_skus']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['customer_name']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['customer_email']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['order_status']);
            if($value['rep_id']){
                //$list[$key]['rep_id'] = $repArr[$value['rep_id']];
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $repArr[$value['rep_id']]);
            }else{
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['rep_id']);
            }
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['is_refund'] == 1 ? '无' : '有');
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['refund_money']);
            switch($value['prty_id']){
                case 2:
                $value['prty_id'] = '中级';
                break;
                case 3:
                $value['prty_id'] = '低级';
                break;
                default:
                $value['prty_id'] = '高级';
                break;        

            }
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['prty_id']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['sale_after_issue']['name']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['problem_desc']);
            switch($value['handle_scheme']){
                case 1:
                $value['handle_scheme'] = '部分退款';
                break;
                case 2:
                $value['handle_scheme'] = '退全款';
                break;
                case 3:
                $value['handle_scheme'] = '补发';
                break;
                case 4:
                $value['handle_scheme'] = '加钱补发';
                break;
                case 5:
                $value['handle_scheme'] = '退款+补发';
                break;
                case 6:
                $value['handle_scheme'] = '折扣买新';
                break; 
                case 7:
                $value['handle_scheme'] = '发放积分';
                break;
                case 8:
                $value['handle_scheme'] = '安抚';
                break;
                case 9:
                $value['handle_scheme'] = '长时间未回复';
                break;
                default:
                $value['handle_scheme'] = '请选择';
                break;                                                                                                                                                                      
            }
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['handle_scheme']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['tariff']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['give_coupon']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['make_up_price_order']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 1 + 2), $value['replacement_order']);
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 1 + 2), $value['handle_time']);
            $spreadsheet->getActiveSheet()->setCellValue("X" . ($key * 1 + 2), $value['complete_time']);
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($key * 1 + 2), $value['refund_way']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('W')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('X')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Y')->setWidth(20);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:P' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
       

        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '售后数据' . date("YmdHis", time());;
        // dump($spreadsheet);

        // if (!$spreadsheet) return false;
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
    } */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere.= " AND sale_after_task.id IN ({$ids})";
        }

        list($where) = $this->buildparams();
        $list = $this->model
        ->with(['saleAfterIssue'])
        ->where($where)
        ->where($addWhere)
        ->select();
        $repArr  = (new Admin())->getAllStaff();
        $list = collection($list)->toArray();
		$issueArr = (new SaleAfterIssue())->getAjaxIssueList();
		if(!$list){
			return false;
		}
		$arr = [];
		foreach($list as $keys => $vals){
			$arr[] = $vals['id'];
		}
			$info = (new SaleAfterTaskRemark())->fetchRelevanceRecord($arr);
	    if($info){
			$info = collection($info)->toArray();
		}else{
			$info = [];	
		}
		
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "任务单号")
            ->setCellValue("B1", "任务状态")
            ->setCellValue("C1", "订单平台");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "订单来源")
            ->setCellValue("E1", "订单号");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "订单SKU")
            ->setCellValue("G1", "客户姓名");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "客户邮箱")
            ->setCellValue("I1", "订单状态")
            ->setCellValue("J1", "承接人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "是否含有退款")
            ->setCellValue("L1", "退款金额")
            ->setCellValue("M1", "任务优先级")
            ->setCellValue("N1", "问题分类")
            ->setCellValue("O1", "问题描述")
            ->setCellValue("P1", "解决方案")
            ->setCellValue("Q1", "关税")
            ->setCellValue("R1", "赠送的优惠券")
            ->setCellValue("S1", "补差价订单号")
            ->setCellValue("T1", "补发订单号")
            ->setCellValue("U1", "创建人")
            ->setCellValue("V1", "创建时间")
            ->setCellValue("W1", "处理时间")
            ->setCellValue("X1", "完成时间")
            ->setCellValue("Y1", "退款方式")
			->setCellValue("Z1","处理备注");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('售后任务数据');

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['task_number']);
            switch($value['task_status']){
                case 1:
                $value['task_status'] = '处理中';
                break;
                case 2:
                $value['task_status'] = '处理完成';
                break;
                case 3:
                $value['task_status'] = '取消';
				break;
                default:
                $value['task_status'] = '未处理';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['task_status']);
            switch($value['order_platform']){
                case 2:
                $value['order_platform'] = 'voogueme';
                break;
                case 3:
                $value['order_platform'] = 'nihao';
                break;
                case 4:
                $value['order_platform'] = 'amazon';
                break;
                case 5:
                $value['order_platform'] = 'wesee';
                break;
                default:
                $value['order_platform'] = 'zeelool';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['order_platform']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['order_source'] == 1 ? 'pc端' :'移动端');
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['order_skus']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['customer_name']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['customer_email']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['order_status']);
            if($value['rep_id']){
                //$list[$key]['rep_id'] = $repArr[$value['rep_id']];
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $repArr[$value['rep_id']]);
            }else{
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['rep_id']);
            }
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['is_refund'] == 1 ? '无' : '有');
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['refund_money']);
            switch($value['prty_id']){
                case 2:
                $value['prty_id'] = '中级';
                break;
                case 3:
                $value['prty_id'] = '低级';
                break;
                default:
                $value['prty_id'] = '高级';
                break;        

            }
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['prty_id']);
			if ($value['problem_id']) {
                $repNumArr = explode(',', $value['problem_id']);
                $value['problem'] = '';
                foreach ($repNumArr as $vals) {
                    $value['problem'] .= $issueArr[$vals] . ' ';
                }
                $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['problem']);
            }else{
                $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['problem_id']);
            }
            //$spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['sale_after_issue']['name']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), str_replace('&nbsp;','',strip_tags($value['problem_desc'])));
            switch($value['handle_scheme']){
                case 1:
                $value['handle_scheme'] = '部分退款';
                break;
                case 2:
                $value['handle_scheme'] = '退全款';
                break;
                case 3:
                $value['handle_scheme'] = '补发';
                break;
                case 4:
                $value['handle_scheme'] = '加钱补发';
                break;
                case 5:
                $value['handle_scheme'] = '退款+补发';
                break;
                case 6:
                $value['handle_scheme'] = '折扣买新';
                break; 
                case 7:
                $value['handle_scheme'] = '发放积分';
                break;
                case 8:
                $value['handle_scheme'] = '安抚';
                break;
                case 9:
                $value['handle_scheme'] = '长时间未回复';
                break;
                default:
                $value['handle_scheme'] = '请选择';
                break;                                                                                                                                                                      
            }
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['handle_scheme']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['tariff']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['give_coupon']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['make_up_price_order']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 1 + 2), $value['replacement_order']);
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 1 + 2), $value['handle_time']);
            $spreadsheet->getActiveSheet()->setCellValue("X" . ($key * 1 + 2), $value['complete_time']);
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($key * 1 + 2), $value['refund_way']);
			if(array_key_exists($value['id'],$info)){
				$value['handle_result'] = $info[$value['id']];
				$spreadsheet->getActiveSheet()->setCellValue("Z" . ($key * 1 + 2), $value['handle_result']);
			}else{
				$spreadsheet->getActiveSheet()->setCellValue("Z" . ($key * 1 + 2), '');
			}
			
			
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('W')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('X')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Y')->setWidth(20);
		$spreadsheet->getActiveSheet()->getColumnDimension('Z')->setWidth(200);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:P' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
       

        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '售后数据' . date("YmdHis", time());;
        // dump($spreadsheet);

        // if (!$spreadsheet) return false;
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
     * 异步获取解决方案
     *
     * @Description
     * @author lsw
     * @since 2020/04/01 11:29:25 
     * @return void
     */
    public function getAjaxHandleScheme()
    {
        if($this->request->isAjax()){
            $json = $this->model->getSolveScheme();
            $arrToObject = (object)($json);
            return json($arrToObject);
        }else{
            return $this->error('404 Not Found');    
        }
    }
    /**
     * 售后任务导入到工单列表
     *
     * @Description
     * @author lsw
     * @since 2020/04/27 10:13:42 
     * @return void
     */
    public function saleAfterToWorkOrder()
    {
        
        set_time_limit(0);
        $page = input("page") ?:1;
        $start = ($page - 1)*10000;
        $result = Db::name('sale_after_task')->limit($start,10000)->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $k => $v){
            $arr[$k]['work_platform'] = $v['order_platform'];
            $arr[$k]['work_type'] = 1;
            $arr[$k]['platform_order'] = $v['order_number'];
            $arr[$k]['order_pay_method'] = $v['refund_way'];
            $arr[$k]['order_sku']      = $v['order_skus'];
            switch($v['task_status']){
                case 1:
                $arr[$k]['work_status'] = 5;
                break;
                case 2:
                $arr[$k]['work_status'] = 6;
                break;
                case 3:
                $arr[$k]['work_status'] = 0;
                break;
                default:
                $arr[$k]['work_status'] = 1;
                break;
            }
            $arr[$k]['work_level'] = $v['prty_id'];
            $arr[$k]['problem_description'] = $v['problem_desc'];
            $arr[$k]['work_picture']  = $v['upload_photos'];
            $arr[$k]['create_user_name'] = $v['create_person'];
            $arr[$k]['create_time']   = $v['create_time'];
            $arr[$k]['submit_time']   = $v['handle_time'];
            $arr[$k]['complete_time'] = $v['complete_time'];
            $arr[$k]['replenish_increment_id'] = $v['make_up_price_order'];
            $arr[$k]['coupon_str']    = $v['give_coupon'];
            $arr[$k]['integral']      = $v['integral'];  
            $arr[$k]['email']         = $v['customer_email'];
            $arr[$k]['refund_money']  = $v['refund_money'];
            $arr[$k]['refund_way']    = $v['refund_way'];
            $arr[$k]['recept_person_id'] = $v['rep_id'];
            $arr[$k]['original_id']   = $v['id'];

        }
            $info = $this->worklist->saveAll($arr);
            if($info){
                echo 'ok';
            }else{
                echo 'error';
            }
    }
    /**
     * 处理售后处理备注
     */
    public function getSaleAfterRemark()
    {
        set_time_limit(0);
        $page = input("page") ?:1;
        $start = ($page - 1)*10000;
        $result = Db::name('sale_after_task_remark')->alias('m')->join('work_order_list w','m.tid=w.original_id','left')->field('m.*,w.id as wid')->limit($start,10000)->order('m.id')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $k => $v){
            $arr[$k]['work_id'] = $v['wid'];
            $arr[$k]['remark_type'] = 3;
            $arr[$k]['remark_record'] = $v['remark_record'];
            $arr[$k]['create_person'] = $v['create_person'];
            $arr[$k]['create_time'] = $v['create_time'];
        }
        $info = $this->workremark->saveAll($arr);
        if($info){
            echo 'ok';
        }else{
            echo 'error';
        }
    }
    /**
     * 协同任务导入到工单列表
     *
     * @Description
     * @author lsw
     * @since 2020/04/27 16:57:56 
     * @return void
     */
    public function infoSynergyToWorkOrder()
    {
        set_time_limit(0);
        $page = input("page") ?:1;
        $start = ($page - 1)*10000;
        $result = Db::name('info_synergy_task')->limit($start,10000)->select();
        if(!$result){
            return false;
        }
        foreach($result as $k => $v){
            $arr[$k]['work_platform'] = $v['order_platform'];
            if(('马奇' == $v['create_person']) || ('陈爱利' == $v['create_person']) ||('陈爱丽' == $v['create_person'])){
                $arr[$k]['work_type'] = 2;
            }else{
                $arr[$k]['work_type'] = 1;
            }
            $arr[$k]['platform_order'] = $v['synergy_order_number'];
            $arr[$k]['order_pay_method'] = $v['refund_way'];
            $arr[$k]['order_sku']      = $v['order_skus'];
            switch($v['synergy_status']){
                case 1:
                $arr[$k]['work_status'] = 5;
                break;
                case 2:
                $arr[$k]['work_status'] = 6;
                break;
                case 3:
                $arr[$k]['work_status'] = 0;
                break;
                default:
                $arr[$k]['work_status'] = 1;
                break;
            }
            $arr[$k]['work_level'] = $v['prty_id'];
            $arr[$k]['problem_description'] = $v['problem_desc'];
            $arr[$k]['work_picture']  = $v['upload_photos'];
            $arr[$k]['create_user_name'] = $v['create_person'];
            $arr[$k]['create_time']   = $v['create_time'];
            $arr[$k]['complete_time'] = $v['complete_time'];
            $arr[$k]['replenish_increment_id'] = $v['make_up_price_order'];
            $arr[$k]['refund_money']  = $v['refund_money'];
            $arr[$k]['refund_way']    = $v['refund_way'];
            if($v['rep_id']){
            $arr[$k]['recept_person_id']  = str_replace("+",",",$v['rep_id']);
            }
            $arr[$k]['synergy_id']   = $v['id'];

        }
            $info = $this->worklist->saveAll($arr);
            if($info){
                echo 'ok';
            }else{
                echo 'error';
            }
    }
    /**
     * 协同任务的备注导入到工单的备注
     *
     * @Description
     * @author lsw
     * @since 2020/04/28 09:36:41 
     * @return void
     */
    public function getinfoSynergyRemark()
    {
        set_time_limit(0);
        $page = input("page") ?:1;
        $start = ($page - 1)*10000;
        $result = Db::name('info_synergy_task_remark')->alias('m')->join('work_order_list w','m.tid=w.synergy_id','left')->field('m.*,w.id as wid')->limit($start,10000)->order('m.id')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $k => $v){
            $arr[$k]['work_id'] = $v['wid'];
            $arr[$k]['remark_type'] = 3;
            $arr[$k]['remark_record'] = $v['remark_record'];
            $arr[$k]['create_person'] = $v['create_person'];
            $arr[$k]['create_time'] = $v['create_time'];
        }
        $info = $this->workremark->saveAll($arr);
        if($info){
            echo 'ok';
        }else{
            echo 'error';
        }
    }
    /**
     * 协同任务更改sku信息到新的库
     *
     * @Description
     * @author lsw
     * @since 2020/04/28 09:47:46 
     * @return void
     */    
    public function infoSynergyChangeSku()
    {
        set_time_limit(0);
        $page = input("page") ?:1;
        $start = ($page - 1)*1000;
        $result = Db::name('info_synergy_task_change_sku')->alias('m')->join('work_order_list w','m.tid=w.synergy_id','left')->field('m.*,w.id as wid')->limit($start,1000)->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $k =>$v){
            $arr[$k]['work_id']         = $v['wid'];
            $arr[$k]['increment_id']    = $v['increment_id'];
            $arr[$k]['platform_type']   = $v['platform_type'];
            $arr[$k]['original_name']   = $v['original_name'];
            $arr[$k]['original_sku']    = $v['original_sku'];
            $arr[$k]['original_number'] = $v['original_number'];
            $arr[$k]['change_type']     = $v['change_type'];    
            $arr[$k]['change_sku']      = $v['change_sku'];
            $arr[$k]['change_number']   = $v['change_number'];
            $arr[$k]['recipe_type']     = $v['recipe_type'];
            $arr[$k]['lens_type']       = $v['lens_type'];
            $arr[$k]['coating_type']    = $v['coating_type'];
            $arr[$k]['second_name']     = $v['second_name'];
            $arr[$k]['zsl']             = $v['zsl'];
            $arr[$k]['od_sph']          = $v['od_sph'];
            $arr[$k]['od_cyl']          = $v['od_cyl'];
            $arr[$k]['od_axis']         = $v['od_axis'];
            $arr[$k]['od_add']          = $v['od_add'];
            $arr[$k]['pd_r']            = $v['pd_r'];
            $arr[$k]['od_pv']           = $v['od_pv'];
            $arr[$k]['od_bd']           = $v['od_bd'];
            $arr[$k]['od_pv_r']         = $v['od_pv_r'];
            $arr[$k]['od_bd_r']         = $v['od_bd_r'];
            $arr[$k]['os_sph']          = $v['os_sph'];
            $arr[$k]['os_cyl']          = $v['os_cyl'];
            $arr[$k]['os_axis']         = $v['os_axis'];
            $arr[$k]['os_add']          = $v['os_add'];
            $arr[$k]['pd_l']            = $v['pd_l'];
            $arr[$k]['os_pv']           = $v['os_pv'];
            $arr[$k]['os_bd']           = $v['os_bd'];
            $arr[$k]['os_pv_r']         = $v['os_pv_r'];
            $arr[$k]['os_bd_r']         = $v['os_bd_r'];
            $arr[$k]['create_person']   = $v['create_person'];
            $arr[$k]['update_time']     = $v['update_time'];
            $arr[$k]['create_time']     = $v['create_time'];
        }
        $info = $this->changesku->saveAll($arr);
        if($info){
            echo 'ok';
        }else{
            echo 'error';
        }        
    }
    /**
     * 更新完成时间
     *
     * @Description
     * @author lsw
     * @since 2020/04/28 14:16:49 
     * @return void
     */
    public function updateTime()
    {
        // $result = Db::name('order_complate')->select();
        // $sql 
    }
}
