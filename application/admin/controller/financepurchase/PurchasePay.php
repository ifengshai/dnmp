<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\financepurchase\FinancePurchase;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemCategory;
use app\admin\model\purchase\PurchaseOrder;
use app\api\controller\Ding;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Request;

class PurchasePay extends Backend
{
    protected $noNeedRight = ['is_conditions'];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FinancePurchase();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
        $this->workflow = new \app\admin\model\financepurchase\FinancePurchaseWorkflow();
        $this->workflowrecords = new \app\admin\model\financepurchase\FinancePurchaseWorkflowRecords();
    }

    /**
     * 采购付款申请单列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 14:03:39
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
            $map = [];
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['supplier_name']) {
                $supplier = Db::name('supplier')->where('supplier_name', 'like', '%' . trim($filter['supplier_name']) . '%')->value('id');
                $map['supplier_id'] = ['=', $supplier];
                unset($filter['supplier_name']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            if ($filter['status'] == 0) {
                unset($filter['status']);
                $this->request->get(['filter' => json_encode($filter)]);
            } elseif ($filter['status'] == 1) {
                //查询我的待审核列表
                $userid = session('admin.id');
                //查询审批记录表我的待审核
                $finance_purchase_id = Db::name('finance_purchase_workflow_records')->where(['assignee_id' => $userid, 'audit_status' => 0])->column('finance_purchase_id');
                $map['id'] = ['in', $finance_purchase_id];
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['supplier_name'] = $this->supplier->where('id', $v['supplier_id'])->value('supplier_name');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('label', 0);
        $label_list = ['全部', '我的待审批'];
        $this->assign('label_list', $label_list);
        return $this->view->fetch();
    }

    /**
     * 添加采购付款生清单
     * 有两个入口一个从采购列表过来 一个从当前页面添加
     * 当前页面添加需要手动输入采购单号
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:03:09
     */
    public function add($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $reason = $this->request->post("reason/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                Db::startTrans();
                try {
                    //校验是否存在未完成的付款申请单
                    if ($params['pay_type'] == 1 || $params['pay_type'] == 2) {
                        $finance_pirchase = $this->model->where('purchase_id', $params['purchase_id'])->where('status', 'in', [0, 1, 2])->find();
                    } else {
                        $finance_pirchase = $this->model->where('order_number', $params['order_number'])->where('status', 'in', [0, 1, 2])->find();
                    }
                    if (!empty($finance_pirchase)) {
                        $this->error('当前单号存在未完成的付款申请单，请检查后重试');
                    }

                    $insert['order_number'] = $params['order_number'];
                    $insert['pay_type'] = $params['pay_type'];
                    switch ($insert['pay_type']) {
                        case 1:
                            $insert['pay_rate'] = 0.3;
                            break;
                        case 2:
                            $insert['pay_rate'] = 1;
                            break;
                        case 3:
                            $insert['pay_rate'] = 1;
                            break;
                        default:
                            $insert['pay_rate'] = 0;
                    }
                    $insert['status'] = $params['status'];
                    $insert['remark'] = $params['remark'];
                    $insert['purchase_id'] = $params['purchase_id'];
                    $insert['1688_number'] = $params['1688_number'];
                    $insert['supplier_id'] = $params['supplier_id'];
                    $insert['order_number'] = $params['order_number'];
                    $insert['pay_grand_total'] = $params['pay_grand_total'];
                    $insert['base_currency_code'] = $params['base_currency_code'];
                    $insert['create_time'] = time();
                    $insert['create_person'] = session('admin.nickname');
                    $finance_purchase_id = Db::name('finance_purchase')->insertGetId($insert);
                    //提交审核 生成审批单
                    if ($insert['status'] == 1) {
                        $this->workflow->setData($finance_purchase_id, $insert['pay_grand_total']);
                    }
                    $label = input('label');
                    //结算单页面过来的创建付款申请单 需要更新结算的付款申请单id字段
                    if ($label == 'statement') {
                        $ids = input('ids');
                        Db::name('finance_statement')->where('id', $ids)->update(['finance_purcahse_id' => $finance_purchase_id]);
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
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            $this->success('添加成功！！');
        }
        $label = input('label');
        //采购单页面过来的创建付款申请单
        if ($label == 'purchase') {
            $ids = $ids ? $ids : input('ids');
            $purchase_order = $this->purchase_order->where('id', $ids)->find();
            $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
            $this->assign('purchase_order', $purchase_order);
            $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
            //获取当前sku的分类
            $item = new Item();
            $category_id = $item->where('sku', $puchase_detail['sku'])->value('category_id');
            if (!empty($category_id)) {
                $type = $this->category($category_id);
                $puchase_detail['type'] = $type;
            }
            $this->assign('purchase_detail', $puchase_detail);
            //查询采购单对应的供应商信息
            $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            switch ($data['currency']) {
                case 1:
                    $data['currency'] = '人民币';
                    break;
                case 2:
                    $data['currency'] = '美元';
                    break;
            }
            switch ($purchase_order['pay_type']) {
                case 1:
                    $all = number_format(($puchase_detail['purchase_total'] + $purchase_order['purchase_freight']) * 0.3, 2, ".", "");
                    break;
                case 2:
                    $all = number_format($puchase_detail['purchase_total'] + $purchase_order['purchase_freight'], 2, ".", "");
                    break;
            }
            $this->assign('supplier', $data);
            $this->assign('all', $all);
            //生成付款申请单编号
            $order_number = 'PR' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $this->assign('order_number', $order_number);
            $this->assignconfig('newdatetime', date('Y-m-d H:i:s'));
            return $this->view->fetch();
        }
        //结算单页面过来的创建付款申请单
        if ($label == 'statement') {
            $ids = $ids ? $ids : input('ids');
            //结算单详情
            $statement = Db::name('finance_statement')->where('id', $ids)->find();
            //供应商详情
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            //结算单对应的所有的 采购单 有批次的采购单会有两条
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $item = new Item();
                $category_id = $item->where('sku', $puchase_details['sku'])->value('category_id');
                $type = $this->category($category_id);
                $puchase_detail[$k]['type'] = $type;
                // dump($type);die;
                $puchase_detail[$k]['sku'] = $puchase_details['sku'];
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];

                if (!empty($v['purchase_batch']) && $v['purchase_batch'] != 1) {
                    $puchase_detail[$k]['freight'] = 0.00;
                }
            }
            // dump($puchase_detail);
            $this->assign('statement', $statement);
            $this->assign('purchase_detail', $puchase_detail);
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            $this->assign('supplier', $data);
            //生成付款申请单编号
            $order_number = 'PR' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $this->assign('order_number', $order_number);
            $this->assign('id', $ids);
            $this->assignconfig('newdatetime', date('Y-m-d H:i:s'));
            return $this->view->fetch('add_statement');
        }
    }


    /**
     * 添加采购付款生清单
     * 有两个入口一个从采购列表过来 一个从当前页面添加
     * 当前页面添加需要手动输入采购单号
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:03:09
     */
    public function batch_add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $ids = $params['ids'];
            Db::startTrans();
            try {
                //查询采购单数据
                $list = $this->purchase_order->alias('a')->field('a.*,b.currency')->where(['a.id' => ['in', $ids]])->join(['fa_supplier' => 'b'], 'a.supplier_id=b.id')->select();
                foreach ($list as $k => $v) {
                    //校验是否存在未完成的付款申请单
                    $finance_pirchase = $this->model->where('purchase_id', $v['id'])->where('status', 'in', [0, 1, 2])->find();
                    if (!empty($finance_pirchase)) {
                        $this->error('当前单号存在未完成的付款申请单，请检查后重试');
                    }
                    //生成付款申请单编号
                    $insert['order_number'] = 'PR' . date('YmdHis') . rand(100, 999) . rand(100, 999);;
                    $insert['pay_type'] = $v['pay_type'];
                    switch ($insert['pay_type']) {
                        case 1:
                            $insert['pay_rate'] = 0.3;
                            break;
                        case 2:
                            $insert['pay_rate'] = 1;
                            break;
                        case 3:
                            $insert['pay_rate'] = 1;
                            break;
                        default:
                            $insert['pay_rate'] = 0;
                    }
                    $insert['status'] = $params['status'];
                    $insert['remark'] = $params['remark'];
                    $insert['purchase_id'] = $v['id'];
                    $insert['1688_number'] = $v['1688_number'];
                    $insert['supplier_id'] = $v['supplier_id'];
                    $insert['pay_grand_total'] = $insert['pay_rate'] ? $v['purchase_total'] * $insert['pay_rate'] : $v['purchase_total'];
                    $insert['base_currency_code'] = $v['currency'];
                    $insert['file'] = $params['file']; //附件
                    $insert['create_time'] = time();
                    $insert['create_person'] = session('admin.nickname');
                    $finance_purchase_id = Db::name('finance_purchase')->insertGetId($insert);
                    //如果提交审核 生成审批工作流
                    if ($params['status'] == 1) {
                        $this->workflow->setData($finance_purchase_id, $insert['pay_grand_total']);
                    }

                    // $label = input('label');
                    //结算单页面过来的创建付款申请单 需要更新结算的付款申请单id字段
                    // if ($label == 'statement') {
                    //     $ids = input('ids');
                    //     Db::name('finance_statement')->where('id', $ids)->update(['finance_purcahse_id' => $finance_purchase_id]);
                    // }
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
            $this->success('添加成功！！');
        }
        //采购单id
        $ids = input('ids');
        $this->assign('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 是否满足选择的采购单id 是否为同一个供应商 状态为已审核
     *
     * @Description
     * @author wpl
     * @since 2021/03/06 09:31:50 
     * @return boolean
     */
    public function is_conditions()
    {
        if ($this->request->isPost()) {
            //采购单id
            $ids = $this->request->post('ids/a');
            $list = $this->purchase_order->field('purchase_status,supplier_id,purchase_number,pay_type')->where(['id' => ['in', $ids]])->select();
            $list = collection($list)->toArray();
            //判断是否为同一个供应商
            $num = count(array_unique(array_column($list, 'supplier_id')));
            if ($num > 1) {
                $this->error('选择的采购单必须为同一供应商');
            }
            foreach ($list as $k => $v) {
                //判断采购单状态是否为已审核、待发货、待收货
                if (!in_array($v['purchase_status'], [2, 5, 6])) {
                    $this->error('存在非已审核状态采购单,单号：' . $v['purchase_number']);
                }

                //判断采购单付款方式 不能为货到付款
                if ($v['pay_type'] == 3) {
                    $this->error('货到付款采购单不能创建付款申请单,单号：' . $v['purchase_number']);
                }

                //判断是否已创建过付款申请单
                $count = $this->model->where('purchase_id', $v['id'])->where('status', 'in', [0, 1, 2, 4])->count();
                if ($count > 0) {
                    $this->error('存在已创建过付款申请单的采购单,单号：' . $v['purchase_number']);
                }
            }
            $this->success();
        }
    }


    /**
     * 编辑采购付款申请单
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:04:17
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $reason = $this->request->post("reason/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $update['status'] = $params['status'];
                    $update['pay_type'] = $params['pay_type'];
                    $update['remark'] = $params['remark'];
                    $update['pay_grand_total'] = $params['pay_grand_total'];
                    switch ($params['pay_type']) {
                        case 1:
                            $update['pay_rate'] = 0.3;
                            break;
                        case 2:
                            $update['pay_rate'] = 1;
                            break;
                        case 3:
                            $update['pay_rate'] = 1;
                            break;
                        default:
                            $update['pay_rate'] = 0;
                    }

                    //采购单信息
                    $purchase_order = $this->purchase_order->where('id', $params['purchase_id'])->find();
                    //提交审核 创建审批单
                    if ($params['status'] == 1) {
                        $this->workflow->setData($ids, $row->pay_grand_total);
                    }
                    $result = Db::name('finance_purchase')->where('order_number', $params['order_number'])->update($update);
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
                    $this->success('添加成功！！');
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if ($row['pay_type'] == 3) {
            //结算单对应的付款申请单
            //结算单详情
            $statement = Db::name('finance_statement')->where('id', $row['purchase_id'])->find();
            //供应商详情
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            //结算单对应的所有的 采购单 有批次的采购单会有两条
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $item = new Item();
                $category_id = $item->where('sku', $puchase_details['sku'])->value('category_id');
                $type = $this->category($category_id);
                $puchase_detail[$k]['type'] = $type;
                $puchase_detail[$k]['sku'] = $puchase_details['sku'];
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];
                if ($v['purchase_batch'] !== 1) {
                    $puchase_detail[$k]['freight'] = 0.00;
                }
            }
            $this->assign('statement', $statement);
            $this->assign('purchase_detail', $puchase_detail);
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            $this->assign('supplier', $data);
            $this->assign('id', $ids);
            $this->assign('row', $row);
            $this->assign('order_number', $row['order_number']);
            return $this->view->fetch('edit_statement');
        } else {
            //采购单对应的付款申请单
            $purchase_order = $this->purchase_order->where('id', $row['purchase_id'])->find();
            $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
            $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
            //查询采购单对应的供应商信息
            $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            switch ($data['currency']) {
                case 1:
                    $data['currency'] = '人民币';
                    break;
                case 2:
                    $data['currency'] = '美元';
                    break;
            }
            $this->assign('purchase_order', $purchase_order);
            $this->assign('purchase_detail', $puchase_detail);
            $this->assign('order_number', $row['order_number']);
            $this->assign('supplier', $data);
            $this->assign('row', $row);
            $this->view->assign("row", $row);
            return $this->view->fetch();
        }
    }

    /**
     * 采购付款申请单详情
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:04:37
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($row['pay_type'] == 3) {
            //结算单对应的付款申请单
            //结算单详情
            $statement = Db::name('finance_statement')->where('id', $row['purchase_id'])->find();
            //供应商详情
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            //结算单对应的所有的 采购单 有批次的采购单会有两条
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $item = new Item();
                $category_id = $item->where('sku', $puchase_details['sku'])->value('category_id');
                $type = $this->category($category_id);
                $puchase_detail[$k]['type'] = $type;
                $puchase_detail[$k]['sku'] = $puchase_details['sku'];
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];

                if (!empty($v['purchase_batch']) && $v['purchase_batch'] != 1) {
                    $puchase_detail[$k]['freight'] = 0.00;
                }
            }
            $this->assign('statement', $statement);
            $this->assign('purchase_detail', $puchase_detail);
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            $this->assign('supplier', $data);
            $this->assign('order_number', $row['order_number']);
            $this->assign('row', $row);
            return $this->view->fetch('detail_statement');
        } else {
            $purchase_order = $this->purchase_order->where('id', $row['purchase_id'])->find();
            $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
            $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
            //查询采购单对应的供应商信息
            $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            switch ($data['currency']) {
                case 1:
                    $data['currency'] = '人民币';
                    break;
                case 2:
                    $data['currency'] = '美元';
                    break;
            }
            $this->assign('purchase_order', $purchase_order);
            $this->assign('purchase_detail', $puchase_detail);
            $this->assign('order_number', $row['order_number']);
            $this->assign('supplier', $data);
            $this->assign('row', $row);
            $this->view->assign("row", $row);
            return $this->view->fetch();
        }
    }

    /**
     * 审批记录
     *
     * @Description
     * @author wpl
     * @since 2021/03/06 18:39:57 
     * @param [type] $ids
     * @return void
     */
    public function check_detail($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\financepurchase\FinancePurchaseWorkflowRecords();
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $ids = input('ids');
            if ($ids) {
                $map['finance_purchase_id'] = $ids;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $admin = new \app\admin\model\Admin();
            $adminList = $admin->where(['status' => 'normal'])->column('nickname', 'id');
            foreach ($list as $k => $v) {
                $list[$k]['assignee_id'] = $adminList[$v['assignee_id']];
                $list[$k]['handle_date'] = $v['handle_date'] ? date('Y-m-d H:i:s', $v['handle_date']) : '';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 添加页面获取采购单 供应商各种信息
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/14
     * Time: 17:24:43
     */
    public function getPurchaseDetail()
    {
        //采购单页面过来的创建付款申请单
        $ids = input('purchase_number');
        $pay_type = input('pay_type');
        if (strlen($ids) !== 22) {
            $this->error('请输入正确的单号！！');
        }
        //选择尾款付款类型 关联结算单
        if ($pay_type == 3) {
            $statement = Db::name('finance_statement')->where('statement_number', $ids)->find();
            empty($statement) && $this->error('当前结算单号不存在！！');
            $statement['status'] !== 6 && $this->error('当前结算单号未完成 请检查结算单状态！！');
            $statement['purchase_type'] = '';
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            $data1['statement'] = $statement;
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];
            }
            $data1['item'] = $puchase_detail;
            $data1['data'] = $data;
            // dump($data1);die;
        } else { //选择预付款或者全款预付 关联采购单
            $purchase_order = $this->purchase_order->where('purchase_number', $ids)->field('id,purchase_type,purchase_number,purchase_name,purchase_total,supplier_id')->find();
            if (!$purchase_order) {
                $this->error('请输入正确的采购单号！！');
            }
            $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
            $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
            //查询采购单对应的供应商信息
            $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            switch ($data['currency']) {
                case 1:
                    $data['currency'] = '人民币';
                    break;
                case 2:
                    $data['currency'] = '美元';
                    break;
            }
            $data1['purchase_order'] = $purchase_order;
            $data1['purchase_detail'] = $puchase_detail;
            $data1['data'] = $data;
            // dump(collection($data1)->toArray());die;
        }
        $this->success('', '', $data1);
    }

    /**
     * 取消
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/15
     * Time: 11:14:20
     */
    public function cancel($ids = null)
    {
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->get($ids);
        if ($row['status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res !== false) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }

    //审核
    public function setStatus()
    {
        if ($this->request->isPost()) {
            $status = $this->request->post("status");
            //拒绝时id是字符串
            if ($status == 3) {
                $ids = $this->request->post("ids");
            } else {
                $ids = $this->request->post("ids/a");
            }
            $remarks = $this->request->post("remarks");
            if (!$ids) {
                $this->error('缺少参数！！');
            }

            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->select();
            foreach ($row as $v) {
                if ($v['status'] !== 1) {
                    $this->error('只有待审核状态才能操作！！');
                }

                //判断审核人是否正确
                $count = $this->workflowrecords->where(['finance_purchase_id' => $v['id'], 'assignee_id' => session('admin.id'), 'audit_status' => 0])->count();
                if ($count < 1) {
                    $this->error('审核人不对,单号：' . $v['order_number']);
                }
            }
            Db::startTrans();
            try {

                foreach ($row as $v) {
                    if ($status == 2) {
                        //审核时判断是否为最后一个人审核 并且为审核通过 如果为最后一个则修改付款申请表状态为审核通过0
                        $userid = $this->workflow->where(['finance_purchase_id' => $v['id']])->order('flow_sort desc')->value('post_id');
                        if ($userid == session('admin.id')) {
                            //更新主表状态
                            Db::name('finance_purchase')->where('id', $v['id'])->update(['status' => $status]);

                            //插入审核记录表
                            $this->workflowrecords->where(['finance_purchase_id' => $v['id'], 'assignee_id' => session('admin.id'), 'audit_status' => 0])->update(['handle_date' => time(), 'remarks' => $remarks, 'audit_status' => 1]);
                        } else {
                            //插入审核记录表
                            $this->workflowrecords->where(['finance_purchase_id' => $v['id'], 'assignee_id' => session('admin.id'), 'audit_status' => 0])->update(['handle_date' => time(), 'remarks' => $remarks, 'audit_status' => 1]);

                            //如果非最后一人审核 则插入下一个需要审核的审核记录
                            //查询下个审核人
                            $flow_sort = $this->workflow->where(['finance_purchase_id' => $v['id'], 'post_id' => session('admin.id')])->value('flow_sort');
                            $post_id = $this->workflow->where(['finance_purchase_id' => $v['id'], 'flow_sort' => $flow_sort + 1])->value('post_id');
                            $this->workflowrecords->insert(['finance_purchase_id' => $v['id'], 'assignee_id' => $post_id, 'createtime' => time()]);
                        }
                    } else {
                        //更新主表状态
                        Db::name('finance_purchase')->where('id', $v['id'])->update(['status' => $status]);

                        //插入审核记录表
                        $this->workflowrecords->where(['finance_purchase_id' => $v['id'], 'assignee_id' => session('admin.id'), 'audit_status' => 0])->update(['handle_date' => time(), 'remarks' => $remarks, 'audit_status' => 2]);
                    }
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

            $this->success();
        }
        $ids = input('ids');
        $this->assign('ids', $ids);
        return $this->view->fetch('check');
    }


    protected function category($category_id)
    {
        $item_category = new ItemCategory();
        $sku_category = $item_category->where('id', $category_id)->find();
        return $sku_category['name'];
    }
}
