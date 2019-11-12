<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\infosynergytaskmanage\InfoSynergyTask;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\SaleAfterIssue;
use app\admin\model\saleaftermanage\SaleAfterTask;
use app\admin\model\saleaftermanage\OrderReturnItem;
use app\admin\model\saleaftermanage\OrderReturnRemark;
use think\Request;

/**
 * 退货列管理
 *
 * @icon fa fa-circle-o
 */
class OrderReturn extends Backend
{

    /**
     * OrderReturn模型对象
     * @var \app\admin\model\saleaftermanage\OrderReturn
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\OrderReturn;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
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
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign('getTabList', (new SaleAfterTask())->getTabList());
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
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
                    $params['return_order_number'] = 'WRB' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname');
                    $result = $this->model->allowField(true)->save($params);

                    //添加物流汇总表
                    if ($params['return_shipping_number']) {
                        $logistics = new \app\admin\model\LogisticsInfo();
                        $list['logistics_number'] = $params['return_shipping_number'];
                        $list['type'] = 3;
                        $list['order_number'] = $params['return_order_number'];
                        $logistics->addLogisticsInfo($list);
                    }

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
                    $item = $params['item'];
                    if($item){
                        foreach ($item as $arr) {
                            $data = [];
                            $data['order_return_id'] = $this->model->id;
                            $data['return_sku'] = $arr['item_sku'];
                            $data['return_sku_name'] = $arr['item_name'];
                            $data['sku_qty'] = $arr['sku_qty'];
                            $data['return_sku_qty'] = $arr['return_sku_qty'];
                            // $data['arrived_sku_qty'] = $arr['arrived_sku_qty'];
                            // $data['check_sku_qty']   = $arr['check_sku_qty'];
                            $data['create_time']     = date("Y-m-d H:i:s", time());
                            (new OrderReturnItem())->allowField(true)->save($data);
                        }
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        //dump((new SaleAfterIssue())->getIssueList(2));
        $this->view->assign('issueList', (new SaleAfterIssue())->getIssueList(2));
        return $this->view->fetch();
    }

    /***
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if(2 < $row['order_status']){
            $this->error(__('Cannot edit in this state'),'saleaftermanage/order_return');
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
                    if ($params['item']) {
                        $item = $params['item'];
                        foreach ($item as  $arr) {
                            $data = [];
                            $data['return_sku'] = $arr['item_sku'];
                            $data['return_sku_name'] = $arr['item_name'];
                            $data['sku_qty'] = $arr['sku_qty'];
                            $data['return_sku_qty'] = $arr['return_sku_qty'];
                            // $data['arrived_sku_qty'] = $arr['arrived_sku_qty'];
                            // $data['check_sku_qty']   = $arr['check_sku_qty'];
                            (new OrderReturnItem())->where('order_return_id', '=', $tid)->update($data);
                        }
                    }
                    if ($params['task_remark']) {
                        $dataRemark = [];
                        $dataRemark['order_return_id'] = $tid;
                        $dataRemark['remark_record'] = strip_tags($params['task_remark']);
                        $dataRemark['create_person'] = session('admin.nickname');
                        $dataRemark['create_time']   = date("Y-m-d H:i:s", time());
                        (new OrderReturnRemark())->allowField(true)->save($dataRemark);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->view->assign('orderReturnRemark', (new OrderReturnRemark())->getOrderReturnRemark($row['id']));
        $this->view->assign("orderReturnItem", (new OrderReturnItem())->getOrderReturnItem($row['id']));
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        $this->view->assign('issueList', (new SaleAfterIssue())->getIssueList(2));
        return $this->view->fetch();
    }
    public function getAjaxOrderPlatformList()
    {
        if ($this->request->isAjax()) {
            $json = (new MagentoPlatform())->getOrderPlatformList();
            if (!$json) {
                $json = [0 => '请添加订单平台'];
            }
            $arrToObject = (object) ($json);
            return json($arrToObject);
        } else {
            $arr = [
                1, 2, 3
            ];
            return $arr;
            $json = json_encode($arr);
            return $this->success('ok', '', $json);
        }
    }
    /**
     *退货单详情信息
     */
    public function detail(Request $request)
    {
        $id = $request->param('ids');
        if (!$id) {
            $this->error('参数错误，请重新尝试', 'saleaftermanage/order_return/index');
        }
        $row = $this->model->get($id);
        if (!$row) {
            $this->error('退货信息不存在，请重新尝试', 'saleaftermanage/order_return/index');
        }
        $this->view->assign("row", $row);
        $this->view->assign('orderReturnRemark', (new OrderReturnRemark())->getOrderReturnRemark($row['id']));
        $this->view->assign("orderReturnItem", (new OrderReturnItem())->getOrderReturnItem($row['id']));
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        $this->view->assign('issueList', (new SaleAfterIssue())->getIssueList(2));
        return $this->view->fetch();
    }
    /***
     * 新建退货单审核不通过
     */
    public function checkNoPass()
    { }
    /***
     * 新建订单检索功能(原先)
     * 
     */
    // public function search(Request $request)
    // {
    //     if ($request->isPost()) {
    //         //获取输入的订单号
    //         $increment_id = $request->post('increment_id');
    //         //            dump($increment_id);
    //         //            exit;
    //         //获取输入的平台
    //         $order_platform = $request->post('order_platform');
    //         //获取客户邮箱地址
    //         $customer_email = $request->post('customer_email');
    //         //获取客户姓名
    //         $customer_name  = $request->post('customer_name');
    //         //获取客户电话
    //         $customer_phone = $request->post('customer_phone');
    //         //获取运单号
    //         $track_number   = $request->post('track_number');
    //         if ($order_platform < 1) {
    //             return $this->error('请选择正确的订单平台');
    //         }
    //         if ($customer_name) {
    //             $customer_name = explode(' ', $customer_name);
    //         }
    //         //求出用户的所有订单信息
    //         $customer = (new SaleAfterTask())->getCustomerEmail($order_platform, $increment_id, $customer_name, $customer_phone, $track_number, $customer_email);
    //         //            dump($customer);
    //         //            exit;
    //         if (!$customer) {
    //             $this->error('找不到订单信息，请重新尝试', '/admin/saleaftermanage/order_return/search?ref=addtabs');
    //         }
    //         //求出所有的订单号
    //         $allIncrementOrder = $customer['increment_id'];
    //         //求出会员的个人信息
    //         $customerInfo = $customer['info'];
    //         unset($customer['info']);
    //         unset($customer['increment_id']);
    //         $infoSynergyTaskResult = Db::name('info_synergy_task')->where('order_platform', $order_platform)->where('synergy_order_number', 'in', $allIncrementOrder)->order('id desc')->select();
    //         $saleAfterTaskResult = Db::name('sale_after_task')->where('order_platform', $order_platform)->where('order_number', 'in', $allIncrementOrder)->order('id desc')->select();
    //         $orderReturnResult = Db::name('order_return')->where('order_platform', $order_platform)->where('increment_id', 'in', $allIncrementOrder)->order('id desc')->select();
    //         //求出承接部门和承接人
    //         $deptArr = (new AuthGroup())->getAllGroup();
    //         $repArr  = (new Admin())->getAllStaff();
    //         //求出订单平台
    //         $orderPlatformList = (new MagentoPlatform())->getOrderPlatformList();
    //         //求出任务优先级
    //         $prtyIdList     = (new SaleAfterTask())->getPrtyIdList();
    //         //求出售后问题分类列表
    //         $issueList      = (new SaleAfterIssue())->getAjaxIssueList();
    //         //求出信息协同任务分类列表
    //         $synergyTaskList = (new InfoSynergyTaskCategory())->getSynergyTaskCategoryList();
    //         //求出关联单据类型
    //         $relateOrderType = (new InfoSynergyTask())->orderType();
    //         if (!empty($infoSynergyTaskResult)) {
    //             foreach ($infoSynergyTaskResult as $k => $v) {
    //                 if ($v['dept_id']) {
    //                     $deptNumArr = explode('+', $v['dept_id']);
    //                     $infoSynergyTaskResult[$k]['dept_id'] = '';
    //                     foreach ($deptNumArr as $values) {
    //                         $infoSynergyTaskResult[$k]['dept_id'] .= $deptArr[$values] . ' ';
    //                     }
    //                 }
    //                 if ($v['rep_id']) {
    //                     $repNumArr = explode('+', $v['rep_id']);
    //                     $infoSynergyTaskResult[$k]['rep_id'] = '';
    //                     foreach ($repNumArr as $vals) {
    //                         $infoSynergyTaskResult[$k]['rep_id'] .= $repArr[$vals] . ' ';
    //                     }
    //                 }
    //                 if ($v['order_platform']) {
    //                     $infoSynergyTaskResult[$k]['order_platform'] = $orderPlatformList[$v['order_platform']];
    //                 }
    //                 if ($v['prty_id']) {
    //                     $infoSynergyTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
    //                 }
    //                 if ($v['synergy_task_id']) {
    //                     $infoSynergyTaskResult[$k]['synergy_task_id'] = $synergyTaskList[$v['synergy_task_id']];
    //                 }
    //                 if ($v['synergy_order_id']) {
    //                     $infoSynergyTaskResult[$k]['synergy_order_id'] = $relateOrderType[$v['synergy_order_id']];
    //                 }
    //             }
    //         }
    //         if (!empty($saleAfterTaskResult)) {
    //             foreach ($saleAfterTaskResult as $k => $v) {
    //                 if ($v['dept_id']) {
    //                     $saleAfterTaskResult[$k]['dept_id'] = $deptArr[$v['dept_id']];
    //                 }
    //                 if ($v['rep_id']) {
    //                     $saleAfterTaskResult[$k]['rep_id'] = $repArr[$v['rep_id']];
    //                 }
    //                 if ($v['order_platform']) {
    //                     $saleAfterTaskResult[$k]['order_platform'] = $orderPlatformList[$v['order_platform']];
    //                 }
    //                 if ($v['task_status'] == 1) {
    //                     $saleAfterTaskResult[$k]['task_status'] = '处理中';
    //                 } elseif ($v['task_status'] == 2) {
    //                     $saleAfterTaskResult[$k]['task_status'] = '处理完成';
    //                 } else {
    //                     $saleAfterTaskResult[$k]['task_status'] = '未处理';
    //                 }
    //                 if ($v['prty_id']) {
    //                     $saleAfterTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
    //                 }
    //                 if ($v['problem_id']) {
    //                     $saleAfterTaskResult[$k]['problem_id'] = $issueList[$v['problem_id']];
    //                 }
    //             }
    //         }
    //         if (!empty($orderReturnResult)) {
    //             foreach ($orderReturnResult as $k1 => $v1) {
    //                 if ($v1['order_platform']) {
    //                     $orderReturnResult[$k1]['order_platform'] = $orderPlatformList[$v1['order_platform']];
    //                 }
    //             }
    //         }
    //         $this->view->assign('infoSynergyTaskResult', $infoSynergyTaskResult);
    //         $this->view->assign('saleAfterTaskResult', $saleAfterTaskResult);
    //         $this->view->assign('orderReturnResult', $orderReturnResult);
    //         $this->view->assign('orderInfoResult', $customer);
    //         $this->view->assign('orderPlatform', $orderPlatformList[$order_platform]);
    //         $this->view->assign('customerInfo', $customerInfo);
    //     }
    //     $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
    //     return $this->view->fetch();
    // }
    /***
     * 订单检索功能(修改之后)
     */
    public function search(Request $request)
    {
        if ($request->isPost()) {
            //获取输入的订单号
            $increment_id = $request->post('increment_id');
            //            dump($increment_id);
            //            exit;
            //获取输入的平台
            $order_platform = $request->post('order_platform');
            //获取客户邮箱地址
            $customer_email = $request->post('customer_email');
            //获取客户姓名
            $customer_name  = $request->post('customer_name');
            //获取客户电话
            $customer_phone = $request->post('customer_phone');
            //获取运单号
            $track_number   = $request->post('track_number');
            if ($order_platform < 1) {
                return $this->error('请选择正确的订单平台');
            }
            if ($customer_name) {
                $customer_name = explode(' ', $customer_name);
            }
            //求出用户的所有订单信息
            $customer = (new SaleAfterTask())->getCustomerEmail($order_platform, $increment_id, $customer_name, $customer_phone, $track_number, $customer_email);
                    //    dump($customer);
                    //    exit;
            if (!$customer) {
                $this->error('找不到订单信息，请重新尝试', 'saleaftermanage/order_return/search?ref=addtabs');
            }
            //求出所有的订单号
            $allIncrementOrder = $customer['increment_id'];
            //求出会员的个人信息
            $customerInfo = $customer['info'];
            unset($customer['info']);
            unset($customer['increment_id']);
            $infoSynergyTaskResult = Db::name('info_synergy_task')->where('order_platform', $order_platform)->where('synergy_order_number', 'in', $allIncrementOrder)->order('id desc')->select();
            $saleAfterTaskResult = Db::name('sale_after_task')->where('order_platform', $order_platform)->where('order_number', 'in', $allIncrementOrder)->order('id desc')->select();
            $orderReturnResult = Db::name('order_return')->where('order_platform', $order_platform)->where('increment_id', 'in', $allIncrementOrder)->order('id desc')->select();
            //求出承接部门和承接人
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();
            //求出订单平台
            $orderPlatformList = (new MagentoPlatform())->getOrderPlatformList();
            //求出任务优先级
            $prtyIdList     = (new SaleAfterTask())->getPrtyIdList();
            //求出售后问题分类列表
            $issueList      = (new SaleAfterIssue())->getAjaxIssueList();
            //求出信息协同任务分类列表
            $synergyTaskList = (new InfoSynergyTaskCategory())->getSynergyTaskCategoryList();
            //求出关联单据类型
            $relateOrderType = (new InfoSynergyTask())->orderType();
            if (!empty($infoSynergyTaskResult)) {
                foreach ($infoSynergyTaskResult as $k => $v) {
                    if ($v['dept_id']) {
                        $deptNumArr = explode('+', $v['dept_id']);
                        $infoSynergyTaskResult[$k]['dept_id'] = '';
                        foreach ($deptNumArr as $values) {
                            $infoSynergyTaskResult[$k]['dept_id'] .= $deptArr[$values] . ' ';
                        }
                    }
                    if ($v['rep_id']) {
                        $repNumArr = explode('+', $v['rep_id']);
                        $infoSynergyTaskResult[$k]['rep_id'] = '';
                        foreach ($repNumArr as $vals) {
                            $infoSynergyTaskResult[$k]['rep_id'] .= $repArr[$vals] . ' ';
                        }
                    }
                    if ($v['order_platform']) {
                        $infoSynergyTaskResult[$k]['order_platform'] = $orderPlatformList[$v['order_platform']];
                    }
                    if ($v['prty_id']) {
                        $infoSynergyTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
                    }
                    if ($v['synergy_task_id']) {
                        $infoSynergyTaskResult[$k]['synergy_task_id'] = $synergyTaskList[$v['synergy_task_id']];
                    }
                    if ($v['synergy_order_id']) {
                        $infoSynergyTaskResult[$k]['synergy_order_id'] = $relateOrderType[$v['synergy_order_id']];
                    }
                }
            }
            if (!empty($saleAfterTaskResult)) {
                foreach ($saleAfterTaskResult as $k => $v) {
                    if ($v['dept_id']) {
                        $saleAfterTaskResult[$k]['dept_id'] = $deptArr[$v['dept_id']];
                    }
                    if ($v['rep_id']) {
                        $saleAfterTaskResult[$k]['rep_id'] = $repArr[$v['rep_id']];
                    }
                    if ($v['order_platform']) {
                        $saleAfterTaskResult[$k]['order_platform'] = $orderPlatformList[$v['order_platform']];
                    }
                    if ($v['task_status'] == 1) {
                        $saleAfterTaskResult[$k]['task_status'] = '处理中';
                    } elseif ($v['task_status'] == 2) {
                        $saleAfterTaskResult[$k]['task_status'] = '处理完成';
                    } else {
                        $saleAfterTaskResult[$k]['task_status'] = '未处理';
                    }
                    if ($v['prty_id']) {
                        $saleAfterTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
                    }
                    if ($v['problem_id']) {
                        $saleAfterTaskResult[$k]['problem_id'] = $issueList[$v['problem_id']];
                    }
                }
            }
            if (!empty($orderReturnResult)) {
                foreach ($orderReturnResult as $k1 => $v1) {
                    if ($v1['order_platform']) {
                        $orderReturnResult[$k1]['order_platform'] = $orderPlatformList[$v1['order_platform']];
                    }
                }
            }
            $this->view->assign('infoSynergyTaskResult', $infoSynergyTaskResult);
            $this->view->assign('saleAfterTaskResult', $saleAfterTaskResult);
            $this->view->assign('orderReturnResult', $orderReturnResult);
            $this->view->assign('orderInfoResult', $customer);
            $this->view->assign('orderPlatform', $orderPlatformList[$order_platform]);
            $this->view->assign('customerInfo', $customerInfo);
            //如果查询订单
            if($increment_id){
                $this->view->assign('increment_id',$increment_id);
            }
            //如果查询邮箱
            if($customer_email){
                $this->view->assign('customer_email',$customer_email);    
            }
            //如果查询客户姓名
            if($customer_name){
                $this->view->assign('customer_name',$customer_name);
            }
            //如果查询客户电话
            if($customer_phone){
                $this->view->assign('customer_phone',$customer_phone);
            }
            //如果查询运单号
            if($track_number){
                $this->view->assign('track_number',$track_number);
            }
            //上传订单平台
                $this->view->assign('order_platform',$order_platform);

        }
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        return $this->view->fetch();
    }
    /***
     * 异步查询模糊订单
     * @param Request $request
     */
    public function ajaxGetLikeOrder(Request $request)
    {
        if ($this->request->isAjax()) {
            $orderType = $request->post('orderType');
            $order_number = $request->post('order_number');
            $result = (new SaleAfterTask())->getLikeOrder($orderType, $order_number);
            if (!$result) {
                return $this->error('订单不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }

    /***
     * 异步查询模糊邮箱
     * @param Request $request
     */
    public function ajaxGetLikeEmail(Request $request)
    {
        if ($this->request->isAjax()) {
            $orderType = $request->post('orderType');
            $email = $request->post('email');
            $result = (new SaleAfterTask())->getLikeEmail($orderType, $email);
            if (!$result) {
                return $this->error('订单不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }

    /***
     * 异步查询模糊电话
     * @param Request $request
     */
    public function ajaxGetLikePhone(Request $request)
    {
        if ($this->request->isAjax()) {
            $orderType = $request->post('orderType');
            $customer_phone = $request->post('customer_phone');
            $result = (new SaleAfterTask())->getLikePhone($orderType, $customer_phone);
            if (!$result) {
                return $this->error('订单不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }

    /***
     * 异步查询模糊姓名
     * @param Request $request
     */
    public function ajaxGetLikeName(Request $request)
    {
        if ($this->request->isAjax()) {
            $orderType = $request->post('orderType');
            $customer_name = $request->post('customer_name');
            $result = (new SaleAfterTask())->getLikeName($orderType, $customer_name);
            if (!$result) {
                return $this->error('订单不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }

    /***
     * 异步查询模糊运单号
     * @param Request $request
     */
    public function ajaxGetLikeTrackNumber(Request $request)
    {
        if ($this->request->isAjax()) {
            $orderType = $request->post('orderType');
            $track_number = $request->post('track_number');
            $result = (new SaleAfterTask())->getLikeTrackNumber($orderType, $track_number);
            if (!$result) {
                return $this->error('运单号不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 改变退货订单状态,从新建变成收到退货
     */
    public function receive($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['order_status'] != 1) {
                return   $this->error('退货单不是新建状态,无法变成收到退货状态');
            }
            $map['id'] = ['in', $ids];
            $data['order_status'] = 2;
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
    /**
     * 改变订单状态,从收到退货变成退货质检
     */
    public function quality($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (2 != $row['order_status']) {
                return   $this->error('退货单不是退货收到状态,无法变成退货质检状态');
            }
            $map['id'] = ['in', $ids];
            $data['order_status'] = 3;
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
    /***
     * 改变订单状态,从退货质检到同步库存
     */
    public function syncStock($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (3 != $row['order_status']) {
                return   $this->error('退货单不是退货质检状态,无法变成同步库存状态');
            }
            $map['id'] = ['in', $ids];
            $data['order_status'] = 4;
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
    /***
     * 改变订单状态,从同步库存到退款状态
     */
    public function refund($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (4 != $row['order_status']) {
                return   $this->error('退货单不是退货质检状态,无法变成同步库存状态');
            }
            $map['id'] = ['in', $ids];
            $data['order_status'] = 5;
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
    public function closed($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,order_status')->select();
            foreach ($row as $v) {
                if ( 1 != $v['order_status']) {
                    $this->error('只有新建状态才能操作！！');
                }
            }
            $data['order_status'] = 5;
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
    //退货单提交审核
    public function submitAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,order_status')->select();
            foreach ($row as $v) {
                if ( 1 != $v['order_status']) {
                    $this->error('只有新建状态才能操作！！');
                }
            }
            $data['order_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('提交成功');
            } else {
                $this->error('提交失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
        /***
     * 多个一起审核通过
     */
    public function morePassAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,order_status')->select();
            foreach ($row as $v) {
                if ( 2 != $v['order_status']) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['order_status'] = 3;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个一起审核拒绝
     */
    public function moreAuditRefused($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,order_status')->select();
            foreach ($row as $v) {
                if ( 2 != $v['order_status']) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['order_status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('审核拒绝成功');
            } else {
                $this->error('审核拒绝失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
}
