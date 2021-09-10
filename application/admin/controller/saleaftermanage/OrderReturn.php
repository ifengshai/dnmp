<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\infosynergytaskmanage\InfoSynergyTask;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\saleaftermanage\WorkOrderRecept;
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
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;

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
    protected $noNeedRight = ['machining', 'order_detail', 'logistics_node'];
    //17track key
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\OrderReturn;
        $this->ordernodedeltail = new \app\admin\model\order\order\Ordernodedeltail;
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

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
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
            $result = ["total" => $total, "rows" => $list];

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
                            $data['create_time'] = date("Y-m-d H:i:s", time());
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
     *
     * @param null $ids
     *
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
                        foreach ($item as $arr) {
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
                        $dataRemark['create_time'] = date("Y-m-d H:i:s", time());
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
            $arrToObject = (object)($json);

            return json($arrToObject);
        } else {
            $arr = [
                1,
                2,
                3,
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

            //抖店视图
            if ($order_platform == 13) {
                return $this->itemDouDian($request);
            };

            //获取输入的订单号
            $increment_id = trim($request->post('increment_id'));

            $order_platform = trim($request->post('order_platform'));

            if (!$order_platform) {
                return json(['code' => 0, 'msg' => '请选择正确的订单平台']);
            }
            //获取客户邮箱地址
            $customer_email = trim($request->post('customer_email'));

            //获取客户姓名
            $customer_name = $input_name = trim($request->post('customer_name'));

            //获取客户电话
            $customer_phone = trim($request->post('customer_phone'));

            //去除客户电话特殊符号
            if ($customer_phone) {
                $customer_phone = preg_replace('/\D/', '', $customer_phone);
            }

            //获取运单号
            $track_number = trim($request->post('track_number'));

            //获取交易号
            $transaction_id = trim($request->post('transaction_id'));

            if ($customer_name) {
                $customer_name = explode(' ', $customer_name);
            }

            //如果邮箱不为空,查询该邮箱下的所有邮件信息
            if (!empty($customer_email)) {
                $email_select = Db::table('fa_zendesk ze')
                    ->join("mojing.fa_admin ad", 'ze.due_id = ad.id', 'left')
                    ->field('ze.id as ze_id,ze.ticket_id,ze.subject,ze.to_email,ze.due_id,ze.create_time,ze.update_time,ze.status as ze_status,ad.nickname')
                    ->where('ze.email', $customer_email)
                    ->select();
                $this->assign('email_select', $email_select);
            }

            //求出用户的所有订单信息
            $customer = (new SaleAfterTask())->getCustomerEmail($order_platform, $increment_id, $customer_name, $customer_phone, $track_number, $transaction_id, $customer_email);

            if ($customer) {
                foreach ($customer as $key => $item) {
                    //客户签收时间
                    $orderNodeData = Db::table('fa_order_node')->where('order_number', $item['increment_id'])->find();
                    $customer[$key]['signing_time'] = $orderNodeData['signing_time'];
                    $customer[$key]['shipment_type'] = $orderNodeData['shipment_type'];
                }
            }

            if (!$customer) {
                return json(['code' => 0, 'msg' => '找不到订单信息，请重新尝试']);
            }

            //求出会员的个人信息
            $customerInfo = $customer['info'];
            unset($customer['info']);
            unset($customer['increment_id']);

            //求出订单平台
            $orderPlatformList = (new MagentoPlatform())->getOrderPlatformList();

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
            //如果查询交易号
            if ($transaction_id) {
                $this->view->assign('transaction_id', $transaction_id);
            }
            //上传订单平台
            $this->view->assign('order_platform', $order_platform);
            $this->view->engine->layout(false);
            if ($order_platform == 13) {
                $html = $this->view->fetch('doudian_item');
            } else {
                $html = $this->view->fetch('item');
            }

            return json(['code' => 1, 'data' => $html]);
        }

        $ids = input('param.ids');
        $increment_id = input('increment_id');

        if (!empty($ids)) {
            $row = Db::connect('database.db_zeelool')->table('oc_customer_after_sales_work_order')->where('id', $ids)->find();
            $this->view->assign("customer_email", $row['email']);
        } else {
            $row = ['increment_id' => $increment_id];
            $this->view->assign("customer_email", $customer_email);
        }
        $this->view->assign("order_platform", $order_platform);
        $this->view->assign("increment_id", $row['increment_id']);
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());

        return $this->view->fetch();
    }

    /**
     * 抖店检索页面
     * @author wangpenglei
     * @date   2021/6/8 17:50
     */
    protected function itemDouDian(Request $request)
    {
        //获取输入的订单号
        $increment_id = trim($request->post('increment_id'));
        if ($increment_id) {
            $where['a.increment_id'] = $increment_id;
        }

        //获取输入的平台
        $order_platform = trim($request->post('order_platform', 13));
        if (!$order_platform) {
            return json(['code' => 0, 'msg' => '请选择正确的订单平台']);
        }

        //获取客户姓名
        $customer_name = trim($request->post('customer_name'));
        if ($customer_name) {
            $where['a.customer_firstname'] = $customer_name;
        }

        //获取客户电话
        $customer_phone = trim($request->post('customer_phone'));
        if ($customer_phone) {
            $where['a.telephone'] = $customer_phone;
        }

        //获取运单号
        $track_number = trim($request->post('track_number'));
        if ($track_number) {
            $where['c.track_number'] = $track_number;
        }

        if (empty($where)) {
            return json(['code' => 0, 'msg' => '请至少输入一个筛选项']);
        }

        $where['c.is_repeat'] = 0;
        $where['c.is_split'] = 0;
        $order = new NewOrder();
        $orderList = $order->alias('a')
            ->field('a.id,a.increment_id,a.order_type,c.agent_way_title,c.track_number,a.entity_id,a.status,a.total_qty_ordered,a.order_currency_code,a.base_grand_total,a.base_shipping_amount,a.created_at')
            ->where($where)
            ->where('a.site', $order_platform)
            ->join(['fa_order_process' => 'c'], 'a.id=c.order_id')
            ->select();
        $orderList = collection($orderList)->toArray();
        $orderItem = new NewOrderItemProcess();
        foreach ($orderList as $k => $v) {
            switch ($v['order_type']) {
                case 2:
                    $orderList[$k]['order_type'] = '<span style="color:#f39c12">批发</span>';
                    break;
                case 3:
                    $orderList[$k]['order_type'] = '<span style="color:#18bc9c">网红</span>';
                    break;
                case 4:
                    $orderList[$k]['order_type'] = '<span style="color:#e74c3c">补发</span>';
                    break;
                default:
                    $orderList[$k]['order_type'] = '<span style="color:#0073b7">普通</span>';
                    break;
            }
            $orderList[$k]['created_at'] = date('Y-m-d H:i:s', $v['created_at']);

            $orderItemResult = $orderItem->alias('a')
                ->field('b.*,a.item_order_number')
                ->where(['a.order_id' => $v['id']])
                ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
                ->select();
            $orderItemResult = collection($orderItemResult)->toArray();
            $skus = array_column($orderItemResult, 'sku');

            //查询虚拟仓库存
            $itemPlatForm = new ItemPlatformSku();
            $stockList = $itemPlatForm->where(['sku' => ['in', $skus], 'platform_type' => 13])->column('stock', 'sku');
            foreach ($orderItemResult as $key => $val) {
                $orderItemResult[$key]['stock'] = $stockList[$val['sku']];
            }
            $orderList[$k]['item'] = $orderItemResult;

            //查询工单
            $workList = new \app\admin\model\saleaftermanage\WorkOrderList();
            $workResult = $workList->where(['platform_order' => $v['increment_id']])->select();
            $workResult = collection($workResult)->toArray();
            foreach ($workResult as &$vv) {
                //排列sku
                if ($vv['order_sku']) {
                    $vv['order_sku_arr'] = explode(',', $v['order_sku']);
                }

                //工单类型
                if ($vv['work_type'] == 1) {
                    $vv['work_type_str'] = '客服工单';
                } else {
                    $vv['work_type_str'] = '仓库工单';
                }

                //工单等级
                if ($vv['work_level'] == 1) {
                    $vv['work_level_str'] = '低';
                } elseif ($vv['work_level'] == 2) {
                    $vv['work_level_str'] = '中';
                } elseif ($vv['work_level'] == 3) {
                    $vv['work_level_str'] = '高';
                }

                switch ($vv['work_status']) {
                    case 0:
                        $vv['work_status'] = '取消';
                        break;
                    case 1:
                        $vv['work_status'] = '新建';
                        break;
                    case 2:
                        $vv['work_status'] = '待审核';
                        break;
                    case 3:
                        $vv['work_status'] = '待处理';
                        break;
                    case 4:
                        $vv['work_status'] = '审核拒绝';
                        break;
                    case 5:
                        $vv['work_status'] = '部分处理';
                        break;
                    case 6:
                        $vv['work_status'] = '已处理';
                        break;
                    default:
                        break;
                }


                $step_arr = WorkOrderMeasure::where('work_id', $vv['id'])->select();
                $step_arr = collection($step_arr)->toArray();
                foreach ($step_arr as $keys => $values) {
                    $recept = WorkOrderRecept::where('measure_id', $values['id'])->where('work_id', $vv['id'])->select();
                    $recept_arr = collection($recept)->toArray();
                    $step_arr[$keys]['recept_user'] = implode(',', array_column($recept_arr, 'recept_person'));

                    $step_arr[$keys]['recept'] = $recept_arr;
                    if ($values['operation_type'] == 0) {
                        $step_arr[$keys]['operation_type'] = '未处理';
                    } elseif ($values['operation_type'] == 1) {
                        $step_arr[$keys]['operation_type'] = '处理完成';
                    } elseif ($values['operation_type'] == 2) {
                        $step_arr[$keys]['operation_type'] = '处理失败';
                    }
                }

                $vv['step'] = $step_arr;
            }
            unset($vv);
            $orderList[$k]['workOrderList'] = $workResult;


        }
        //上传订单平台
        $this->view->assign('orderList', $orderList);
        $this->view->assign('order_platform', $order_platform);
        $this->view->engine->layout(false);
        $html = $this->view->fetch('doudian_item');

        return json(['code' => 1, 'data' => $html]);
    }

    /***
     * 配货记录
     *
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
                'id'         => 1,
                'content'    => '打标签',
                'createtime' => $row['custom_print_label_created_at_new'],
                'person'     => $row['custom_print_label_person_new'],
            ],
            [
                'id'         => 2,
                'content'    => '配镜架',
                'createtime' => $row['custom_match_frame_created_at_new'],
                'person'     => $row['custom_match_frame_person_new'],
            ],
            [
                'id'         => 3,
                'content'    => '配镜片',
                'createtime' => $row['custom_match_lens_created_at_new'],
                'person'     => $row['custom_match_lens_person_new'],
            ],
            [
                'id'         => 4,
                'content'    => '加工',
                'createtime' => $row['custom_match_factory_created_at_new'],
                'person'     => $row['custom_match_factory_person_new'],
            ],
            [
                'id'         => 5,
                'content'    => '提货',
                'createtime' => $row['custom_match_delivery_created_at_new'],
                'person'     => $row['custom_match_delivery_person_new'],
            ],
        ];
        $this->assign('list', $list);
        $this->assign('row', $row);

        return $this->view->fetch();
    }

    /**
     * @param null $order_number
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 配货记录 --新
     */
    public function order_detail($order_number = null)
    {
        $order_number = input('param.order_number');

        $new_order = new NewOrder();
        $new_order_process = new NewOrderProcess();
        $order_number = $order_number ?? $this->request->get('order_number');

        $new_order_item_process_id = $new_order->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->where('a.increment_id', $order_number)
            ->field('b.id,b.sku,b.distribution_status')
            ->select();
        $new_order_item_process_id2 = array_column($new_order_item_process_id, 'sku', 'id');

        $is_shendan = $new_order_process->where('increment_id', $order_number)->field('check_time,check_status,delivery_time')->find();
        //子单节点日志
        foreach ($new_order_item_process_id as $k => $v) {
            $distribution_log[$v['id']] = Db::name('distribution_log')->where('item_process_id', $v['id'])->select();
        }

        $new_order_item_process_id1 = array_column($new_order_item_process_id, 'id');
        $distribution_log_times = Db::name('distribution_log')
            ->where('item_process_id', 'in', $new_order_item_process_id1)
            ->where('distribution_node', 1)
            ->order('create_time asc')
            ->column('create_time');
        //查询订单详情
        $ruleList = collection($this->ordernodedeltail->where('order_number', $order_number)->order('node_type asc')->field('node_type,create_time,handle_user_name,shipment_type,track_number')->select())->toArray();

        $new_ruleList = array_column($ruleList, null, 'node_type');
        $key_list = array_keys($new_ruleList);

        $id = $this->request->get('id');
        $label = $this->request->get('label', 1);

        $this->view->assign(compact('order_number', 'id', 'label'));
        $this->view->assign("list", $new_ruleList);
        $this->view->assign("is_shendan", $is_shendan);
        $this->view->assign("distribution_log_times", $distribution_log_times);
        $this->view->assign("distribution_log", $distribution_log);
        $this->view->assign("key_list", $key_list);
        $this->view->assign("new_order_item_process_id2", $new_order_item_process_id2);

        return $this->view->fetch();
    }


    /**
     *
     * 物流节点
     */
    public function logistics_node()
    {
        $entity_id = input('param.entity_id');
        $site = input('param.order_platform');
        if ($site == 13) {
            //获取订单信息对应的所有物流信息
            $courier = Db::name('order_node_courier_third')
                ->alias('a')
                ->join(['fa_order_node' => 'b'], 'a.order_id=b.order_id and a.site=b.site')
                ->where('a.order_id', $entity_id)->where('a.site', $site)
                ->order('create_time desc')
                ->field('a.content,a.create_time,a.site,a.track_number,a.shipment_data_type')
                ->select();
        } else {
            //获取订单信息对应的所有物流信息
            $courier = Db::name('order_node_courier')
                ->alias('a')
                ->join(['fa_order_node' => 'b'], 'a.order_id=b.order_id and a.site=b.site')
                ->where('a.order_id', $entity_id)->where('a.site', $site)
                ->order('create_time desc')
                ->field('a.content,a.create_time,a.site,a.track_number,a.shipment_data_type')
                ->select();
        }

        $courier_one = $courier[0];
        unset($courier[0]);
        $courier_two = array_values($courier);
        $this->assign('courier_one', $courier_one);
        $this->assign('courier_two', $courier_two);

        return $this->view->fetch();
    }


    /***
     * 异步查询模糊订单
     *
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
     *
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
     *
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
     *
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
     *
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
     * 异步查询模糊交易号
     *
     * @param Request $request
     */
    public function ajaxGetLikeTransaction(Request $request)
    {
        if ($this->request->isAjax()) {
            $orderType = $request->post('orderType');
            $transaction_id = $request->post('transaction_id');
            $result = (new SaleAfterTask())->getLikeTransaction($orderType, $transaction_id);
            if (!$result) {
                $this->error('交易号不存在，请重新尝试');
            }
            $this->success('', '', $result, 0);
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
                return $this->error('退货单不是新建状态,无法变成收到退货状态');
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
                return $this->error('退货单不是退货收到状态,无法变成退货质检状态');
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
                return $this->error('退货单不是退货质检状态,无法变成同步库存状态');
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
                return $this->error('退货单不是退货质检状态,无法变成同步库存状态');
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
        $track_number = input('track_number') ?: null;
        $entity_id = input('entity_id') ?: null;
        $order_platform = input('order_platform') ?: null;
        if (!empty($track_number) && !empty($entity_id) && !empty($order_platform)) {
           $this->error('缺少参数');
        }

        $express_data = Cache::get('orderReturn_get_logistics_info_' . $track_number);
        if (!$express_data) {
            try {
                $carrier = $this->getCarrier('UPS');
                $trackingConnector = new TrackingConnector($this->apiKey);
                $trackInfo = $trackingConnector->getTrackInfoMulti([
                    [
                        'number'  => $track_number,
                        'carrier' => $carrier['carrierId'],
                    ],
                ]);

                $express_data = $trackInfo['data']['accepted'][0]['track']['z1'];
                Cache::get('orderReturn_get_logistics_info_' . $track_number, $express_data, 3600);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->view->assign("express_data", $express_data);
        $this->view->assign("title", 'UPS');
        $this->view->assign("track_number", $track_number);

        return $this->view->fetch();
    }

    /**
     * 获取快递号
     *
     * @param $title
     *
     * @return mixed|string
     */
    protected function getCarrier($title)
    {
        $carrierId = '';
        if (stripos($title, 'post') !== false) {
            $carrierId = 'chinapost';
            $title = 'China Post';
        } elseif (stripos($title, 'ems') !== false) {
            $carrierId = 'chinaems';
            $title = 'China Ems';
        } elseif (stripos($title, 'dhl') !== false) {
            $carrierId = 'dhl';
            $title = 'DHL';
        } elseif (stripos($title, 'fede') !== false) {
            $carrierId = 'fedex';
            $title = 'Fedex';
        } elseif (stripos($title, 'usps') !== false) {
            $carrierId = 'usps';
            $title = 'Usps';
        } elseif (stripos($title, 'yanwen') !== false) {
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        } elseif (stripos($title, 'cpc') !== false) {
            $carrierId = 'cpc';
            $title = 'Canada Post';
        } elseif (stripos($title, 'sua') !== false) {
            $carrierId = 'sua';
            $title = 'SUA';
        } elseif (stripos($title, 'cod') !== false) {
            $carrierId = 'cod';
            $title = 'COD';
        } elseif (stripos($title, 'tnt') !== false) {
            $carrierId = 'tnt';
            $title = 'TNT';
        } else {
            $carrierId = 'ups';
            $title = 'UPS';
        }

        $carrier = [
            'dhl'       => '100001',
            'chinapost' => '03011',
            'chinaems'  => '03013',
            'cpc'       => '03041',
            'fedex'     => '100003',
            'usps'      => '21051',
            'yanwen'    => '190012',
            'sua'       => '190111',
            'cod'       => '10021',
            'tnt'       => '100004',
            'ups'       => '100002',
        ];
        if ($carrierId) {
            return ['title' => $title, 'carrierId' => $carrier[$carrierId]];
        }

        return ['title' => $title, 'carrierId' => $carrierId];
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
            $arr[$k]['order_id'] = $v['id'];
            $arr[$k]['increment_id'] = $v['increment_id'];
            $arr[$k]['customer_email'] = $v['customer_email'];
            $arr[$k]['customer_name'] = $v['customer_name'];
            $arr[$k]['return_shipping_number'] = $v['return_shipping_number'];
            $arr[$k]['return_remark'] = isset($v['return_remark']) ? $v['return_remark'] : '';
            $arr[$k]['return_money_remark'] = isset($v['check_remark']) ? $v['check_remark'] : '';
            $arr[$k]['return_money'] = isset($v['refund_amount']) ? $v['refund_amount'] : 0;
            $arr[$k]['create_person'] = $v['created_operator'];
            $arr[$k]['create_time'] = $v['created_at'];
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
            $arr[$k]['return_sku'] = isset($v['return_sku']) ? $v['return_sku'] : '';
            $arr[$k]['return_sku_qty'] = isset($v['return_sku_qty']) ? $v['return_sku_qty'] : 0;
            $arr[$k]['arrived_sku_qty'] = isset($v['arrived_sku_qty']) ? $v['arrived_sku_qty'] : 0;
            $arr[$k]['check_sku_qty'] = isset($v['check_sku_qty']) ? $v['check_sku_qty'] : 0;
            $arr[$k]['damage_sku_qty'] = isset($v['damage_sku_qty']) ? $v['damage_sku_qty'] : 0;
            $arr[$k]['create_time'] = $v['created_at'];
            $arr[$k]['is_visable'] = $v['is_visable'];
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
            $arr[$k]['order_id'] = $v['id'];
            $arr[$k]['increment_id'] = $v['increment_id'];
            $arr[$k]['customer_email'] = $v['customer_email'];
            $arr[$k]['customer_name'] = $v['customer_name'];
            $arr[$k]['return_shipping_number'] = $v['return_shipping_number'];
            $arr[$k]['return_remark'] = isset($v['return_remark']) ? $v['return_remark'] : '';
            $arr[$k]['return_money_remark'] = isset($v['check_remark']) ? $v['check_remark'] : '';
            $arr[$k]['return_money'] = isset($v['refund_amount']) ? $v['refund_amount'] : 0;
            $arr[$k]['create_person'] = $v['created_operator'];
            $arr[$k]['create_time'] = $v['created_at'];
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
            $arr[$k]['return_sku'] = isset($v['return_sku']) ? $v['return_sku'] : '';
            $arr[$k]['return_sku_qty'] = isset($v['return_sku_qty']) ? $v['return_sku_qty'] : 0;
            $arr[$k]['arrived_sku_qty'] = isset($v['arrived_sku_qty']) ? $v['arrived_sku_qty'] : 0;
            $arr[$k]['check_sku_qty'] = isset($v['check_sku_qty']) ? $v['check_sku_qty'] : 0;
            $arr[$k]['damage_sku_qty'] = isset($v['damage_sku_qty']) ? $v['damage_sku_qty'] : 0;
            $arr[$k]['create_time'] = $v['created_at'];
            $arr[$k]['is_visable'] = $v['is_visable'];
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
            $arr[$k]['order_id'] = $v['id'];
            $arr[$k]['increment_id'] = $v['increment_id'];
            $arr[$k]['customer_email'] = $v['customer_email'];
            $arr[$k]['customer_name'] = $v['customer_name'];
            $arr[$k]['return_shipping_number'] = $v['return_shipping_number'];
            $arr[$k]['return_remark'] = isset($v['return_remark']) ? $v['return_remark'] : '';
            $arr[$k]['return_money_remark'] = isset($v['check_remark']) ? $v['check_remark'] : '';
            $arr[$k]['return_money'] = isset($v['refund_amount']) ? $v['refund_amount'] : 0;
            $arr[$k]['create_person'] = $v['created_operator'];
            $arr[$k]['create_time'] = $v['created_at'];
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
            $arr[$k]['return_sku'] = isset($v['return_sku']) ? $v['return_sku'] : '';
            $arr[$k]['return_sku_qty'] = isset($v['return_sku_qty']) ? $v['return_sku_qty'] : 0;
            $arr[$k]['arrived_sku_qty'] = isset($v['arrived_sku_qty']) ? $v['arrived_sku_qty'] : 0;
            $arr[$k]['check_sku_qty'] = isset($v['check_sku_qty']) ? $v['check_sku_qty'] : 0;
            $arr[$k]['damage_sku_qty'] = isset($v['damage_sku_qty']) ? $v['damage_sku_qty'] : 0;
            $arr[$k]['create_time'] = $v['created_at'];
            $arr[$k]['is_visable'] = $v['is_visable'];
        }
        $this->modelItem->allowField(true)->saveAll($arr);
    }
}
