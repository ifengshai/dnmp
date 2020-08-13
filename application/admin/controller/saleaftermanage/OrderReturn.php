<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\infosynergytaskmanage\InfoSynergyTask;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use think\Db;
use think\Cache;
use app\common\controller\Backend;
use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\SaleAfterIssue;
use app\admin\model\saleaftermanage\SaleAfterTask;
use app\admin\model\saleaftermanage\OrderReturnItem;
use app\admin\model\saleaftermanage\OrderReturnRemark;
use think\Request;
use fast\Trackingmore;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

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
    protected $zeelool = null;
    protected $modelItem = null;
    protected $relationSearch = true;
    protected $noNeedRight = [
        'machining'
    ];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\OrderReturn;
        $this->modelItem = new \app\admin\model\saleaftermanage\OrderReturnItem;
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
                    if ($item) {
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
        if (2 < $row['order_status']) {
            $this->error(__('Cannot edit in this state'), 'saleaftermanage/order_return');
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
    {
    }

    /***
     * 订单检索功能(修改之后)
     */
    public function search(Request $request)
    {
        $order_platform = intval(input('order_platform', 1));
        $customer_email = input('email', '');
        if ($request->isPost()) {
            //获取输入的订单号
            $increment_id = trim($request->post('increment_id'));
           
            //获取输入的平台
            if (!$order_platform) {
                $order_platform = trim($request->post('order_platform'));
            }

            //获取客户邮箱地址
            if (!$customer_email) {
                $customer_email = trim($request->post('customer_email'));
            }
            //获取客户姓名
            $customer_name  = $input_name =  trim($request->post('customer_name'));
            //获取客户电话
            $customer_phone = trim($request->post('customer_phone'));
            //获取运单号
            $track_number   = trim($request->post('track_number'));
            if ($order_platform < 1) {
                
                return json(['code' => 0,'msg' => '请选择正确的订单平台']);
            }
            if ($customer_name) {
                $customer_name = explode(' ', $customer_name);
            }
            //求出用户的所有订单信息
            $customer = (new SaleAfterTask())->getCustomerEmail($order_platform, $increment_id, $customer_name, $customer_phone, $track_number, $customer_email);
            if (!$customer) {
                return json(['code' => 0,'msg' => '找不到订单信息，请重新尝试']);
                // $this->error('找不到订单信息，请重新尝试', 'saleaftermanage/order_return/search?ref=addtabs');
            }
            //求出所有的订单号
            $allIncrementOrder = $customer['increment_id'];
            //求出会员的个人信息
            $customerInfo = $customer['info'];
            unset($customer['info']);
            unset($customer['increment_id']);
            Db::name('info_synergy_task')->query("set time_zone='+8:00'");
            Db::name('sale_after_task')->query("set time_zone='+8:00'");
            Db::name('order_return')->query("set time_zone='+8:00'");
            //如果存在vip订单的话查询这个订单是否在协同任务当中
            if ($customerInfo['arr_order_vip']) {
                $infoSynergyTaskResult = Db::name('info_synergy_task')->where('order_platform', $order_platform)->where('synergy_order_number', 'in', $allIncrementOrder)->whereOr('synergy_order_number', 'in', $customerInfo['arr_order_vip'])->order('id desc')->select();
            } else {
                $infoSynergyTaskResult = Db::name('info_synergy_task')->where('order_platform', $order_platform)->where('synergy_order_number', 'in', $allIncrementOrder)->order('id desc')->select();
            }

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
                    switch ($v['synergy_status']) {
                        case 1:
                            $infoSynergyTaskResult[$k]['synergy_status'] = '<span style="color:#f39c12">处理中</span>';
                            break;
                        case 2:
                            $infoSynergyTaskResult[$k]['synergy_status'] = '<span style="color:#18bc9c">处理完成</span>';
                            break;
                        case 3:
                            $infoSynergyTaskResult[$k]['synergy_status'] = '<span style="color:#e74c3c">取消</span>';
                            break;
                        default:
                            $infoSynergyTaskResult[$k]['synergy_status'] = '<span style="color:#0073b7">新建</span>';
                            break;
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
                        $saleAfterTaskResult[$k]['task_status'] = '<span style="color:#f39c12">处理中</span>';
                    } elseif ($v['task_status'] == 2) {
                        $saleAfterTaskResult[$k]['task_status'] = '<span style="color:#18bc9c">处理完成</span>';
                    } elseif ($v['task_status'] == 3) {
                        $saleAfterTaskResult[$k]['task_status'] = '<span style="color:#e74c3c">取消</span>';
                    } else {
                        $saleAfterTaskResult[$k]['task_status'] = '<span style="color:#0073b7">新建</span>';
                    }
                    if ($v['prty_id']) {
                        $saleAfterTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
                    }
                    if ($v['problem_id']) {
                        $saleAfterTaskResult[$k]['problem_id'] = $issueList[$v['problem_id']];
                    }
                    switch ($v['handle_scheme']) {
                        case 1:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '部分退款';
                            break;
                        case 2:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '退全款';
                            break;
                        case 3:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '补发';
                            break;
                        case 4:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '加钱补发';
                            break;
                        case 5:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '退款+补发';
                            break;
                        case 6:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '折扣买新';
                            break;
                        case 7:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '发放积分';
                            break;
                        case 8:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '安抚';
                            break;
                        case 9:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '长时间未回复';
                            break;
                        default:
                            $saleAfterTaskResult[$k]['handle_scheme'] = '请选择';
                            break;
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
            // $this->view->assign('workOrderListResult', $workOrderListResult);
            $this->view->assign('saleAfterTaskResult', $saleAfterTaskResult);
            $this->view->assign('orderReturnResult', $orderReturnResult);
            $this->view->assign('orderInfoResult', $customer);

        
            $this->view->assign('orderPlatformId', $order_platform);
            $this->view->assign('orderPlatform', $orderPlatformList[$order_platform]);
            $this->view->assign('customerInfo', $customerInfo);
            //如果查询订单
            if ($increment_id) {
                $this->view->assign('increment_id', $increment_id);
            }
            //如果查询邮箱
            if ($customer_email) {
                $this->view->assign('customer_email', $customer_email);
            }
            //如果查询客户姓名
            if ($customer_name) {
                $this->view->assign('customer_name', $input_name);
            }
            //如果查询客户电话
            if ($customer_phone) {
                $this->view->assign('customer_phone', $customer_phone);
            }
            //如果查询运单号
            if ($track_number) {
                $this->view->assign('track_number', $track_number);
            }
            //上传订单平台
            $this->view->assign('order_platform', $order_platform);
            $this->view->engine->layout(false);
            $html = $this->view->fetch('test');
            return json(['code' => 1,'data' => $html]);
        }
        // $serviceArr = config('search.platform');
        // $sessionId  = session('admin.id');
        // foreach ($serviceArr as $key => $kv) {
        //     if (in_array($sessionId, $kv)) {
        //         $default = $key;
        //     }
        // }
        // //默认的客服分组值
        // if ($default) {
        //     $this->view->assign("default", $default);
        // }
        $this->view->assign("order_platform", $order_platform);
        $this->view->assign("customer_email", $customer_email);
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        return $this->view->fetch();
    }

    /***
     * 配货记录
     * @param Request $request
     */
    public function machining(Request $request)
    {
        $order_number = $request->get('order_number');
        $order_platform = $request->get('order_platform');
        if ($order_platform == 1) {
            $model = new \app\admin\model\order\order\Zeelool();
        } elseif ($order_platform == 2) {
            $model = new \app\admin\model\order\order\Voogueme();
        } elseif ($order_platform == 3) {
            $model = new \app\admin\model\order\order\Nihao();
        }
        $field = 'custom_print_label_new,custom_print_label_person_new,custom_print_label_created_at_new,
        custom_is_match_frame_new,custom_match_frame_person_new,custom_match_frame_created_at_new,
        custom_is_match_lens_new,custom_match_lens_created_at_new,custom_match_lens_person_new,
        custom_is_send_factory_new,custom_match_factory_person_new,custom_match_factory_created_at_new,
        custom_is_delivery_new,custom_match_delivery_person_new,custom_match_delivery_created_at_new';
        $row = $model->where(['increment_id' => $order_number])->field($field)->find();
        $list = [
            [
                'id' => 1,
                'content' => '打标签',
                'createtime' => $row['custom_print_label_created_at_new'],
                'person' => $row['custom_print_label_person_new']
            ],
            [
                'id' => 2,
                'content' => '配镜架',
                'createtime' => $row['custom_match_frame_created_at_new'],
                'person' => $row['custom_match_frame_person_new']
            ],
            [
                'id' => 3,
                'content' => '配镜片',
                'createtime' => $row['custom_match_lens_created_at_new'],
                'person' => $row['custom_match_lens_person_new']
            ],
            [
                'id' => 4,
                'content' => '加工',
                'createtime' => $row['custom_match_factory_created_at_new'],
                'person' => $row['custom_match_factory_person_new']
            ],
            [
                'id' => 5,
                'content' => '提货',
                'createtime' => $row['custom_match_delivery_created_at_new'],
                'person' => $row['custom_match_delivery_person_new']
            ],
        ];
        $this->assign('list', $list);
        $this->assign('row', $row);
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
                if (1 != $v['order_status']) {
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
                if (1 != $v['order_status']) {
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
                if (2 != $v['order_status']) {
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
                if (2 != $v['order_status']) {
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
    /***
     * 获取订单物流信息
     */
    public function get_logistics_info($track_number = null, $entity_id = null, $order_platform = null)
    {
        if (!empty($track_number) && !empty($entity_id) && !empty($order_platform)) {
            $this->zeelool = new \app\admin\model\order\order\Zeelool;
            $express = $this->zeelool->getExpressData($order_platform, $entity_id);
            if ($express) {
                //缓存一个小时
                // $express_data = session('order_checkDetail_' . $express['track_number'] . '_' . date('YmdH'));
                $express_data = Cache::get('orderReturn_get_logistics_info_' . $express['track_number']);
                if (!$express_data) {
                    try {
                        //查询物流信息
                        //$title = str_replace(' ', '-', $express['title']);
                        switch ($express['title']) {
                            case 'DHL (Deprecated)':
                                $title = 'dhl';
                                break;
                            case 'China Post':
                                $title = 'china-ems';
                                break;
                            case 'china-ems':
                                $title = 'china-ems';
                                break;
                            case 'DHL':
                                $title = 'dhl';
                                break;
                            case 'USPS':
                                $title = 'usps';
                                break;
                        }
                        $track = new Trackingmore();
                        $track = $track->getRealtimeTrackingResults($title, $express['track_number']);
                        $express_data = $track['data']['items'][0];
                        //session('order_checkDetail_' . $express['track_number'] . '_' . date('YmdH'), $express_data);
                        Cache::get('orderReturn_get_logistics_info_' . $express['track_number'], $express_data, 3600);
                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                    }
                }
                $this->view->assign("express_data", $express_data);
            }
            return $this->view->fetch();
        } else {
            $this->error('参数错误,请重新尝试');
        }
    }
    /***
     * 导入退件数据 zeelool
     */
    public function zeelool_order_return()
    {
        $result = Db::table('zeelool_order_return')->field('id,status,increment_id,customer_email,customer_name,return_shipping_number,return_remark
        ,check_remark,refund_amount,is_visable,created_operator,created_at')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[$k]['order_id']        = $v['id'];
            $arr[$k]['increment_id']    = $v['increment_id'];
            $arr[$k]['customer_email']  = $v['customer_email'];
            $arr[$k]['customer_name']   = $v['customer_name'];
            $arr[$k]['return_shipping_number']    = $v['return_shipping_number'];
            $arr[$k]['return_remark']    = isset($v['return_remark']) ? $v['return_remark'] : '';
            $arr[$k]['return_money_remark']    = isset($v['check_remark']) ? $v['check_remark'] : '';
            $arr[$k]['return_money']    = isset($v['refund_amount']) ? $v['refund_amount'] : 0;
            $arr[$k]['create_person']    = $v['created_operator'];
            $arr[$k]['create_time']    = $v['created_at'];
            $arr[$k]['order_platform'] = 1;
            $arr[$k]['final_loss_amount'] = isset($v['final_loss_amount']) ? $v['final_loss_amount'] : 0;
            $arr[$k]['final_loss_remark'] = isset($v['final_loss_remark']) ? $v['final_loss_remark'] : '';
            if (0 == $v['is_visable']) {
                $arr[$k]['is_del'] = 2;
            } else {
                $arr[$k]['is_del'] = 1;
            }
            if ('new' == $v['status']) {
                $arr[$k]['order_status'] = 1;
            } elseif ('check' == $v['status']) {
                $arr[$k]['order_status'] = 3;
                $arr[$k]['quality_status'] = 1;
            } elseif ('stock' == $v['status']) {
                $arr[$k]['order_status'] = 3;
                $arr[$k]['quality_status'] = 1;
                $arr[$k]['in_stock_status'] = 1;
            }
        }
        $this->model->allowField(true)->saveAll($arr);
    }
    //导入退件商品数据 zeelool
    public function zeelool_order_return_item()
    {
        $result = Db::table('zeelool_order_return_item')->alias('m')->join('fa_order_return o', 'o.order_id=m.order_return_id')->where(['o.order_platform' => 1])->field('m.*,o.id as oid')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            //echo $v['return_sku'].'<br/>';
            $arr[$k]['order_return_id'] = $v['oid'];
            $arr[$k]['return_sku']      = isset($v['return_sku']) ? $v['return_sku'] : '';
            $arr[$k]['return_sku_qty']  = isset($v['return_sku_qty'])  ? $v['return_sku_qty'] : 0;
            $arr[$k]['arrived_sku_qty'] = isset($v['arrived_sku_qty']) ? $v['arrived_sku_qty'] : 0;
            $arr[$k]['check_sku_qty']   = isset($v['check_sku_qty']) ? $v['check_sku_qty'] : 0;
            $arr[$k]['damage_sku_qty']  = isset($v['damage_sku_qty']) ? $v['damage_sku_qty'] : 0;
            $arr[$k]['create_time']     = $v['created_at'];
            $arr[$k]['is_visable']      = $v['is_visable'];
        }
        $this->modelItem->allowField(true)->saveAll($arr);
    }
    //导入退件商品数据 voogueme
    public function voogueme_order_return()
    {
        $result = Db::table('voogueme_order_return')->field('id,status,increment_id,customer_email,customer_name,return_shipping_number,return_remark
        ,check_remark,refund_amount,is_visable,created_operator,created_at')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[$k]['order_id']        = $v['id'];
            $arr[$k]['increment_id']    = $v['increment_id'];
            $arr[$k]['customer_email']  = $v['customer_email'];
            $arr[$k]['customer_name']   = $v['customer_name'];
            $arr[$k]['return_shipping_number']    = $v['return_shipping_number'];
            $arr[$k]['return_remark']    = isset($v['return_remark']) ? $v['return_remark'] : '';
            $arr[$k]['return_money_remark']    = isset($v['check_remark']) ? $v['check_remark'] : '';
            $arr[$k]['return_money']    = isset($v['refund_amount']) ? $v['refund_amount'] : 0;
            $arr[$k]['create_person']    = $v['created_operator'];
            $arr[$k]['create_time']    = $v['created_at'];
            $arr[$k]['order_platform'] = 2;
            $arr[$k]['final_loss_amount'] = isset($v['final_loss_amount']) ? $v['final_loss_amount'] : 0;
            $arr[$k]['final_loss_remark'] = isset($v['final_loss_remark']) ? $v['final_loss_remark'] : '';
            if (0 == $v['is_visable']) {
                $arr[$k]['is_del'] = 2;
            } else {
                $arr[$k]['is_del'] = 1;
            }
            if ('new' == $v['status']) {
                $arr[$k]['order_status'] = 1;
            } elseif ('check' == $v['status']) {
                $arr[$k]['order_status'] = 3;
                $arr[$k]['quality_status'] = 1;
            } elseif ('stock' == $v['status']) {
                $arr[$k]['order_status'] = 3;
                $arr[$k]['quality_status'] = 1;
                $arr[$k]['in_stock_status'] = 1;
            }
        }
        $this->model->allowField(true)->saveAll($arr);
    }
    //导入退件商品数据 voogueme
    public function voogueme_order_return_item()
    {
        $result = Db::table('voogueme_order_return_item')->alias('m')->join('fa_order_return o', 'o.order_id=m.order_return_id')->where(['o.order_platform' => 2])->field('m.*,o.id as oid')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            //echo $v['return_sku'].'<br/>';
            $arr[$k]['order_return_id'] = $v['oid'];
            $arr[$k]['return_sku']      = isset($v['return_sku']) ? $v['return_sku'] : '';
            $arr[$k]['return_sku_qty']  = isset($v['return_sku_qty'])  ? $v['return_sku_qty'] : 0;
            $arr[$k]['arrived_sku_qty'] = isset($v['arrived_sku_qty']) ? $v['arrived_sku_qty'] : 0;
            $arr[$k]['check_sku_qty']   = isset($v['check_sku_qty']) ? $v['check_sku_qty'] : 0;
            $arr[$k]['damage_sku_qty']  = isset($v['damage_sku_qty']) ? $v['damage_sku_qty'] : 0;
            $arr[$k]['create_time']     = $v['created_at'];
            $arr[$k]['is_visable']      = $v['is_visable'];
        }
        $this->modelItem->allowField(true)->saveAll($arr);
    }
    //导入退件商品数据 nihao
    public function nihao_order_return()
    {
        $result = Db::table('nihao_order_return')->field('id,status,increment_id,customer_email,customer_name,return_shipping_number,return_remark
        ,check_remark,refund_amount,is_visable,created_operator,created_at')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[$k]['order_id']        = $v['id'];
            $arr[$k]['increment_id']    = $v['increment_id'];
            $arr[$k]['customer_email']  = $v['customer_email'];
            $arr[$k]['customer_name']   = $v['customer_name'];
            $arr[$k]['return_shipping_number']    = $v['return_shipping_number'];
            $arr[$k]['return_remark']    = isset($v['return_remark']) ? $v['return_remark'] : '';
            $arr[$k]['return_money_remark']    = isset($v['check_remark']) ? $v['check_remark'] : '';
            $arr[$k]['return_money']    = isset($v['refund_amount']) ? $v['refund_amount'] : 0;
            $arr[$k]['create_person']    = $v['created_operator'];
            $arr[$k]['create_time']    = $v['created_at'];
            $arr[$k]['order_platform'] = 3;
            $arr[$k]['final_loss_amount'] = isset($v['final_loss_amount']) ? $v['final_loss_amount'] : 0;
            $arr[$k]['final_loss_remark'] = isset($v['final_loss_remark']) ? $v['final_loss_remark'] : '';
            if (0 == $v['is_visable']) {
                $arr[$k]['is_del'] = 2;
            } else {
                $arr[$k]['is_del'] = 1;
            }
            if ('new' == $v['status']) {
                $arr[$k]['order_status'] = 1;
            } elseif ('check' == $v['status']) {
                $arr[$k]['order_status'] = 3;
                $arr[$k]['quality_status'] = 1;
            } elseif ('stock' == $v['status']) {
                $arr[$k]['order_status'] = 3;
                $arr[$k]['quality_status'] = 1;
                $arr[$k]['in_stock_status'] = 1;
            }
        }
        $this->model->allowField(true)->saveAll($arr);
    }
    //导入退件商品数据 nihao
    public function nihao_order_return_item()
    {
        $result = Db::table('nihao_order_return_item')->alias('m')->join('fa_order_return o', 'o.order_id=m.order_return_id')->where(['o.order_platform' => 3])->field('m.*,o.id as oid')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            //echo $v['return_sku'].'<br/>';
            $arr[$k]['order_return_id'] = $v['oid'];
            $arr[$k]['return_sku']      = isset($v['return_sku']) ? $v['return_sku'] : '';
            $arr[$k]['return_sku_qty']  = isset($v['return_sku_qty'])  ? $v['return_sku_qty'] : 0;
            $arr[$k]['arrived_sku_qty'] = isset($v['arrived_sku_qty']) ? $v['arrived_sku_qty'] : 0;
            $arr[$k]['check_sku_qty']   = isset($v['check_sku_qty']) ? $v['check_sku_qty'] : 0;
            $arr[$k]['damage_sku_qty']  = isset($v['damage_sku_qty']) ? $v['damage_sku_qty'] : 0;
            $arr[$k]['create_time']     = $v['created_at'];
            $arr[$k]['is_visable']      = $v['is_visable'];
        }
        $this->modelItem->allowField(true)->saveAll($arr);
    }
}
