<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\financepurchase\StatementItem;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\purchase\PurchaseOrder;
use app\admin\model\StockLog;
use app\admin\model\warehouse\Instock;
use app\api\controller\Ding;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use think\Request;

class Statement extends Backend
{
    protected $noNeedRight = [];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\financepurchase\Statement();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
        $this->statement = new \app\admin\model\financepurchase\Statement();
        $this->statement_detail = new StatementItem();

        $this->financepurchase = new \app\admin\model\financepurchase\FinancePurchase;
        $this->statementitem = new \app\admin\model\financepurchase\StatementItem;
        $this->payorder = new \app\admin\model\financepurchase\FinancePayorder;
        $this->payorder_item = new \app\admin\model\financepurchase\FinancePayorderItem;
        $this->batch = new \app\admin\model\purchase\PurchaseBatch();
        $this->batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
        $this->purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $this->outstockItem = new \app\admin\model\warehouse\OutStockItem;
        $this->instockItem = new \app\admin\model\warehouse\InstockItem;
        $this->financecost = new \app\admin\model\finance\FinanceCost();
        return parent::_initialize();
    }

    /**
     * 结算单列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/20
     * Time: 11:11:07
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
            $filter = json_decode($this->request->get('filter'), true);
            $map = [];
            if ($filter['supplier_name']){
                $supplier = Db::name('supplier')->where('supplier_name','like','%' . trim($filter['supplier_name']) . '%')->value('id');
                $map['supplier_id'] = ['=',$supplier];
                unset($filter['supplier_name']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['purchase_person']){
                $supplier = Db::name('supplier')->where('purchase_person','like','%' . trim($filter['purchase_person']) . '%')->column('id');
                $map['supplier_id'] = ['in',$supplier];
                unset($filter['purchase_person']);
                $this->request->get(['filter' => json_encode($filter)]);
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
                $supplier = $this->supplier->where('id', $v['supplier_id'])->find();
                $list[$k]['supplier_name'] = $supplier['supplier_name'];
                if ($supplier['period'] == 0) {
                    $list[$k]['period'] = '无账期';
                } else {
                    $list[$k]['period'] = $supplier['period'] . '个月';
                }
                $list[$k]['purchase_person'] = $supplier['purchase_person'];
                $statement = Db::name('finance_purchase')->where('pay_type', 3)->where('purchase_id', $v['id'])->where('status', 'in', [0, 1, 2, 3, 4, 6])->find();
                if (!empty($statement)) {
                    $list[$k]['can_create'] = 0;
                } else {
                    $list[$k]['can_create'] = 1;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加结算单
     * 有两个入口一个从结算单列表手动添加 一个从待结算列表页面添加
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/20
     * Time: 19:03:09
     */
    public function add($ids = null)
    {
        $ids = input('ids');
        $supplier_id = input('supplier_id');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $list = $this->request->post("list/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                //涉及到金额计算的 要在后端进行重复校验 以免出现结算单金额错误的情况
                $kou_money = array_sum(array_column($list, 'kou_money'));
                if (($params['product_total'] + $kou_money) != $params['product_total1']) {
                    $this->error(__('金额计算错误，请关闭页面后重试', ''));
                }
                foreach ($list as $k => $v) {
                    if ($v['kou_money'] > 0) {
                        if (empty($v['kou_reason'])) {
                            $this->error(__('有扣款金额不能没有扣款原因', ''));
                        }
                    }
                    if (($v['all_money'] + $v['kou_money']) != $v['all_money1']) {
                        $this->error(__('采购单' . $v['name'] . '金额计算错误，请关闭页面后重试', ''));
                    }
                }
                Db::startTrans();
                try {
                    $statemet = [];
                    $statemet['statement_number'] = $params['order_number'];
                    $statemet['status'] = $params['status'];
                    //结算单应该都是尾款类型的
                    $statemet['pay_type'] = 3;
                    $statemet['supplier_id'] = $params['supplier_id'];
                    $statemet['base_currency_code'] = $params['base_currency_code'];
                    $statemet['wait_statement_total'] = $params['product_total'];
                    $statemet['remark'] = $params['remark'];
                    $statemet['create_time'] = time();
                    $statemet['create_person'] = session('admin.nickname');
                    $statemet_id = Db::name('finance_statement')->insertGetId($statemet);

                    $arr = [];
                    foreach ($list as $k => $v) {
                        $arr[$k]['statement_id'] = $statemet_id;
                        $arr[$k]['purchase_id'] = $v['purchase_id'];
                        $arr[$k]['purchase_batch'] = $v['purchase_batch'];
                        $arr[$k]['purchase_batch_id'] = $v['batch_id'];
                        $arr[$k]['supplier_id'] = $params['supplier_id'];
                        $arr[$k]['before_total'] = $v['wait_pay'] ? $v['wait_pay'] : 0;
                        $arr[$k]['now_before_total'] = $v['now_wait_pay'] ? $v['now_wait_pay'] : 0;
                        $arr[$k]['now_pay_total'] = $v['now_wait_pay'] ? $v['now_wait_pay'] : 0;
                        $arr[$k]['wait_statement_total'] = $v['all_money'];
                        $arr[$k]['freight'] = $v['purchase_freight'];
                        $arr[$k]['instock_num'] = $v['quantity_num'];
                        $arr[$k]['instock_total'] = $v['in_stock_money'];
                        $arr[$k]['return_num'] = $v['unqualified_num'];
                        $arr[$k]['return_total'] = $v['unqualified_num_money'];
                        $arr[$k]['deduction_total'] = empty($v['kou_money']) ? 0 : $v['kou_money'];
                        $arr[$k]['deduction_reason'] = $v['kou_reason'];
                        $arr[$k]['arrival_num'] = $v['arrival_num'];
                        $arr[$k]['in_stock_id'] = $v['in_stock_id'];
                        //结算单应该都是尾款类型
                        $arr[$k]['pay_type'] = 3;
                        $arr[$k]['purchase_name'] = $v['purchase_name'];
                        $arr[$k]['period'] = $v['period'];
                        $arr[$k]['purchase_number'] = $v['purchase_number'];
                    }
                    // dump($arr);die;
                    Db::name('finance_statement_item')->insertAll($arr);
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
        $instock = new Instock();
        // $supplier_id = 1;
        //供应商详细信息
        $supplier = Db::name('supplier')->where('id', $supplier_id)->find();
        $list = $instock
            ->alias('a')
            ->join('check_order b', 'a.check_id = b.id', 'left')
            ->join('check_order_item f', 'f.check_id = b.id')
            ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
            ->join('purchase_order_item d', 'd.purchase_id = c.id')
            ->join('in_stock_item e', 'a.id = e.in_stock_id')
            ->where('b.supplier_id', $supplier_id)
            ->where('a.id', 'in', $ids)
            ->where('a.status', 2)//已审核通过的入库单
            ->where('c.id', '>', 16475)
            ->field('c.purchase_number,a.id,d.purchase_price,c.purchase_freight,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
            ->select();
        $all = 0;
        foreach ($list as $k => $v) {
            //批次 第几批的
            $list[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id', $v['batch_id'])->value('batch');
            //入库金额 质检合格数量*采购单价
            $list[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['quantity_num'], 2, '.', '');
            //退货金额 质检不合格数量*采购单价
            $list[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'], 2, '.', '');
            //预付金额 已支付预付金额
            $list[$k]['wait_pay'] = Db::name('finance_purchase')->where('purchase_id', $v['purchase_id'])->value('pay_grand_total');
            $list[$k]['now_wait_pay'] = $list[$k]['wait_pay'];
            $data = [];
            $map = [];
            if ($v['batch_id'] == 0) {
                $map['purchase_id'] = ['=', $v['purchase_id']];
                //采购数量无批次 应该是采购单数量
                $list[$k]['arrival_num'] = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->value('purchase_num');
                switch ($v['pay_type']) {
                    case 1:
                        //无批次预付款 待结算金额公式=入库金额 +运费-已支付预付金额
                        $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'] - $list[$k]['now_wait_pay'];
                        break;
                    case 2:
                        //无批次全款预付 待结算金额 = 入库金额 +运费 - 已支付预付金额
                        $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'] - $list[$k]['now_wait_pay'];
                        break;
                    default:
                        //货到付款的 待结算金额 = 入库金额 + 运费
                        $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'];
                }
            } else {
                $map['purchase_id'] = ['=', $v['purchase_id']];
                $map['batch_id'] = ['=', $v['batch_id']];
                //采购数量有批次 应该是采购批次的数量
                $list[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id', $v['batch_id'])->value('arrival_num');
                //采购批次是第一批 待结算金额 = 采购批次入库数量*采购单价-预付款金额 + 运费
                if ($list[$k]['purchase_batch'] == 1) {
                    $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'] - $list[$k]['now_wait_pay'];
                } else {
                    //不是第一批 批次待结算金额 = 采购批次入库数量*采购单价
                    $list[$k]['all_money'] = $list[$k]['in_stock_money'];
                }
            }
            //采购单物流单详情
            $row = Db::name('logistics_info')->where($map)->field('logistics_number,logistics_company_no,collect_time,createtime')->find();
            if(!empty($row['collect_time'])){
                $list[$k]['period'] = date("Y-m-t",strtotime(($row['collect_time'] . '+' . $supplier['period'] . 'month')));
            }else{
                $list[$k]['period'] = date("Y-m-t",strtotime(($row['createtime'] . '+' . $supplier['period'] . 'month')));
            }
            switch ($v['pay_type']) {
                case 1:
                    $list[$k]['pay_type'] = '预付款';
                    break;
                case 2:
                    $list[$k]['pay_type'] = '全款预付';
                    break;
                case 3:
                    $list[$k]['pay_type'] = '尾款';
                    break;
            }
            // dump($list[$k]['all_money']);
            $all += $list[$k]['all_money'];
        }
        $supplier['period'] = $supplier['period'] == 0 ? '无账期' : $supplier['period'] . '个月';
        $this->assignconfig('supplier_id', $ids);
        $this->assign('supplier', $supplier);
        $this->assign('list', $list);
        $this->assign('all', $all);
        //生成结算单号
        $order_number = 'JS' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('order_number', $order_number);
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 编辑结算单
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
            $list = $this->request->post("list/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                //涉及到金额计算的 要在后端进行重复校验 以免出现结算单金额错误的情况
                $kou_money = array_sum(array_column($list, 'kou_money'));
                if (($params['product_total'] + $kou_money) != $params['product_total1']) {
                    $this->error(__('金额计算错误，请关闭页面后重试', ''));
                }
                foreach ($list as $k => $v) {
                    if ($v['kou_money'] > 0) {
                        if (empty($v['kou_reason'])) {
                            $this->error(__('有扣款金额不能没有扣款原因', ''));
                        }
                    }
                    if (($v['all_money'] + $v['kou_money']) != $v['all_money1']) {
                        $this->error(__('采购单' . $v['name'] . '金额计算错误，请关闭页面后重试', ''));
                    }
                }
                Db::startTrans();
                try {
                    //更新主表待结算总金额
                    Db::name('finance_statement')->where('id', $ids)->update(['wait_statement_total' => $params['product_total'], 'status' => $params['status']]);
                    foreach ($list as $k => $v) {
                        Db::name('finance_statement_item')->where('id', $v['in_stock_id'])->update(['deduction_total' => $v['kou_money'], 'deduction_reason' => $v['kou_reason']]);
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
            $this->success('提交成功！！');

        }
        $supplier_id = $row['supplier_id'];
        //供应商详细信息
        $supplier = Db::name('supplier')->where('id', $supplier_id)->find();
        $supplier['period'] = $supplier['period'] == 0 ? '无账期' : $supplier['period'] . '个月';
        $list = Db::name('finance_statement_item')->where('statement_id', $row['id'])->select();
        $kou_money = array_sum(array_column($list, 'deduction_total'));
        foreach ($list as $k => $v) {
            switch ($v['pay_type']) {
                case 1:
                    $list[$k]['pay_type'] = '预付款';
                    break;
                case 2:
                    $list[$k]['pay_type'] = '全款预付';
                    break;
                case 3:
                    $list[$k]['pay_type'] = '尾款';
                    break;
            }
        }
        // dump($list);
        $this->assign('supplier', $supplier);
        $this->assign('list', $list);
        $this->assign('old_all_money', round($kou_money + $row['wait_statement_total'], 2));
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 结算单详情
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
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $list = $this->request->post("list/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                Db::startTrans();
                try {
                    //更新主表待结算总金额
                    Db::name('finance_statement')->where('id', $ids)->update(['wait_statement_total' => $params['product_total'], 'status' => $params['status']]);
                    foreach ($list as $k => $v) {
                        Db::name('finance_statement_item')->where('id', $v['in_stock_id'])->update(['deduction_total' => $v['kou_money'], 'deduction_reason' => $v['deduction_reason']]);
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
            $this->success('提交成功！！', url('PurchasePay/index'));

        }
        $supplier_id = $row['supplier_id'];
        // $supplier_id = 1;
        //供应商详细信息
        $supplier = Db::name('supplier')->where('id', $supplier_id)->find();
        $supplier['period'] = $supplier['period'] == 0 ? '无账期' : $supplier['period'] . '个月';
        $list = Db::name('finance_statement_item')->where('statement_id', $row['id'])->select();
        foreach ($list as $k => $v) {
            switch ($v['pay_type']) {
                case 1:
                    $list[$k]['pay_type'] = '预付款';
                    break;
                case 2:
                    $list[$k]['pay_type'] = '全款预付';
                    break;
                case 3:
                    $list[$k]['pay_type'] = '尾款';
                    break;
            }
        }
        // dump($list);
        $this->assign('supplier', $supplier);
        $this->assign('list', $list);
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    //审核
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        $status = $this->request->post("status");
        // dump($ids);
        // dump($status);die;
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        foreach ($row as $v) {
            if ($v['status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }
        Db::startTrans();
        try {
            //更新主表状态
            Db::name('finance_statement')->where('id', 'in', $ids)->update(['status' => $status]);

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

    //对账
    public function setStatuss()
    {
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        // dump(collection($row)->toArray());die;
        foreach ($row as $v) {
            if ($v['status'] !== 3) {
                $this->error('只有待对账状态才能操作！！');
            }
        }
        Db::startTrans();
        try {
            foreach ($row as $v) {
                if ($v['wait_statement_total'] > 0) {
                    $status = 6;
                } else {
                    //结算单金额为负的时候 对账要进行成本计算操作
                    $statement_items = Db::name('finance_statement_item')->where('statement_id', $v['id'])->select();
                    foreach ($statement_items as $kk => $vv) {
                        if ($vv['purchase_batch'] > 0) {
                            //有批次判断所有的批次是否都已结算 都已结算的话计算采购成本
                            $all_batch = Db::name('purchase_batch')->where('purchase_id', $vv['purchase_id'])->count();
                            //结算单子表里有几条结算数据
                            $all_items = Db::name('finance_statement_item')->where('purchase_id', $vv['purchase_id'])->select();
                            $instock_total = array_sum(array_column($all_items, 'instock_total'));
                            $deduction_total = array_sum(array_column($all_items, 'deduction_total'));
                            $instock_num = array_sum(array_column($all_items, 'instock_num'));
                            if ($all_batch == count($all_items)) {
                                $actual_purchase_price = round(($instock_total - $deduction_total + $all_items[0]['freight']) / $instock_num ,2);
                                //更新采购单成本
                                Db::name('purchase_order_item')->where('purchase_id',$vv['purchase_id'])->update(['actual_purchase_price'=>$actual_purchase_price]);
                                //入库总数量
                                $count = $this->instockItem->alias('i')->join('fa_in_stock s','i.in_stock_id=s.id')->join('fa_check_order c','s.check_id=c.id')->where('c.purchase_id',$vv['purchase_id'])->sum('i.in_stock_num');
                                $purchase_order = $this->purchase_item->alias('i')->join('fa_purchase_order o','i.purchase_id=o.id')->where('i.purchase_id',$vv['purchase_id'])->field('round(o.purchase_total/purchase_num,2) purchase_price,actual_purchase_price,i.sku')->find();
                                //实际采购成本和预估成本不一致，冲减差值
                                if($purchase_order['purchase_price'] != $purchase_order['actual_purchase_price']){
                                    //计算订单出库数量
                                    $out_count1 = $this->item->where('purchase_id',$vv['purchase_id'])->where('item_order_number','<>','')->where('sku',$purchase_order['sku'])->where('library_status',2)->count();
                                    //计算出库数量
                                    $out_count2 = $this->outstockItem->alias('i')->join('fa_out_stock s','s.id=i.out_stock_id','left')->where('s.purchase_id',$vv['purchase_id'])->where('status',2)->where('i.sku',$purchase_order['sku'])->sum('out_stock_num');
                                    $out_count = $out_count1+$out_count2;
                                    $result['purchase_id'] = $vv['purchase_id'];
                                    $result['create_time'] = time();
                                    //误差数量
                                    $result['count'] = $count-$out_count;
                                    //误差单价
                                    $result['price'] = round($purchase_order['actual_purchase_price']-$purchase_order['purchase_price'],2);
                                    //误差总金额
                                    $result['total'] = round($result['count']*$result['price'],2);
                                    Db::name('finance_cost_error')->insert($result);
                                    /**************************************计算成本冲减end****************************************/
                                    /**************************************成本核算start****************************************/
                                    if($out_count1 != 0){
                                        //订单出库
                                        $order = $this->item->where('purchase_id',$vv['purchase_id'])->where('item_order_number','<>','')->where('sku',$purchase_order['sku'])->where('library_status',2)->select();
                                        $result1 = array();
                                        foreach ($order as $kk1=>$vv1){
                                            //拆分订单号
                                            $order_number = explode('-',$vv1['item_order_number']);
                                            $order_number = $order_number[0];
                                            if(isset($result1[$order_number])){
                                                $result1[$order_number] += 1;
                                            }else{
                                                $result1[$order_number] = 1;
                                            }
                                        }
                                        foreach ($result1 as $rr1=>$ss1){
                                            //获取成本核算中的订单数据
                                            $cost_order_info = $this->financecost->where(['order_number' => $rr1, 'type' => 2,'bill_type'=>8])->find();
                                            //如果有出库数据，需要添加冲减暂估结算金额和增加成本核算数据
                                            $arr1['type'] = 2;   //类型：成本
                                            $arr1['bill_type'] = 10;    //单据类型：暂估结算金额
                                            $arr1['frame_cost'] = round($ss1*$purchase_order['purchase_price'],2);    //镜架成本：剩余预估单价*剩余数量
                                            $arr1['order_number'] = $rr1;  //订单号
                                            $arr1['site'] = $cost_order_info['site'];  //站点
                                            $arr1['order_type'] = $cost_order_info['order_type'];  //订单类型
                                            $arr1['order_money'] = $cost_order_info['order_money'];  //订单金额
                                            $arr1['income_amount'] = $cost_order_info['income_amount'];  //收入金额
                                            $arr1['action_type'] = 2;  //动作类型：冲减
                                            $arr1['order_currency_code'] = $cost_order_info['order_currency_code'];  //币种
                                            $arr1['is_carry_forward'] = $cost_order_info['is_carry_forward'];  //是否结转
                                            $arr1['payment_time'] = $cost_order_info['payment_time'];  //订单支付时间
                                            $arr1['payment_method'] = $cost_order_info['payment_method'];  //订单支付方式
                                            $arr1['createtime'] = time();  //创建时间
                                            $arr1['cycle_id'] = $cost_order_info['cycle_id'];  //关联周期结转单id
                                            Db::name('finance_cost')->insert($arr1);
                                            //增加成本核算记录
                                            $arr2['type'] = 2;   //类型：成本
                                            $arr2['bill_type'] = 8;    //单据类型：实际结算金额
                                            $arr2['frame_cost'] = round($ss1*$purchase_order['actual_purchase_price'],2);    //镜架成本：剩余实际单价*剩余数量
                                            $arr2['order_number'] = $rr1;  //订单号
                                            $arr2['site'] = $cost_order_info['site'];  //站点
                                            $arr2['order_type'] = $cost_order_info['order_type'];  //订单类型
                                            $arr2['order_money'] = $cost_order_info['order_money'];  //订单金额
                                            $arr2['income_amount'] = $cost_order_info['income_amount'];  //收入金额
                                            $arr2['action_type'] = 1;  //动作类型：增加
                                            $arr2['order_currency_code'] = $cost_order_info['order_currency_code'];  //币种
                                            $arr2['is_carry_forward'] = $cost_order_info['is_carry_forward'];  //是否结转
                                            $arr2['payment_time'] = $cost_order_info['payment_time'];  //订单支付时间
                                            $arr2['payment_method'] = $cost_order_info['payment_method'];  //订单支付方式
                                            $arr2['createtime'] = time();  //创建时间
                                            $arr2['cycle_id'] = $cost_order_info['cycle_id'];  //关联周期结转单id
                                            Db::name('finance_cost')->insert($arr2);
                                        }
                                    }
                                    if($out_count2 != 0){
                                        //出库单出库
                                        $outorder = $this->outstockItem->alias('i')->join('fa_out_stock s','s.id=i.out_stock_id','left')->where('s.purchase_id',$vv['purchase_id'])->where('status',2)->where('i.sku',$purchase_order['sku'])->group('s.out_stock_number')->field('s.id,s.out_stock_number,sum(i.out_stock_num) count')->select();
                                        foreach ($outorder as $rr2=>$ss2){
                                            //如果有出库数据，需要添加冲减暂估结算金额和增加成本核算数据
                                            $arr3['type'] = 2;   //类型：成本
                                            $arr3['bill_type'] = 11;    //单据类型：暂估结算金额
                                            $arr3['frame_cost'] = round($ss2['count']*$purchase_order['purchase_price'],2);    //镜架成本：剩余预估单价*剩余数量
                                            $arr3['order_number'] = $ss2['out_stock_number'];  //出库单号
                                            $arr3['out_stock_id'] = $ss2['id'];  //出库单id
                                            $arr3['action_type'] = 2;  //动作类型：冲减
                                            $arr3['order_currency_code'] = 'CNY';  //币种
                                            $arr3['createtime'] = time();  //创建时间
                                            Db::name('finance_cost')->insert($arr3);
                                            //增加成本核算记录
                                            $arr4['type'] = 2;   //类型：成本
                                            $arr4['bill_type'] = 9;    //单据类型：实际结算金额
                                            $arr4['frame_cost'] = round($ss2['count']*$purchase_order['actual_purchase_price'],2);    //镜架成本：剩余实际单价*剩余数量
                                            $arr4['order_number'] = $ss2['out_stock_number'];  //出库单号
                                            $arr4['out_stock_id'] = $ss2['id'];  //出库单id
                                            $arr4['action_type'] = 1;  //动作类型：增加
                                            $arr4['order_currency_code'] = 'CNY';  //币种
                                            $arr4['createtime'] = time();  //创建时间
                                            Db::name('finance_cost')->insert($arr4);
                                        }
                                    }
                                    /**************************************成本核算end****************************************/
                                }
                            }
                        } else {

                            //无批次直接计算采购成本
                            $actual_purchase_price = round(($vv['instock_total'] - $vv['deduction_total'] + $vv['freight']) / $vv['instock_num'],2);
                            //更新采购单成本
                            Db::name('purchase_order_item')->where('purchase_id',$vv['purchase_id'])->update(['actual_purchase_price'=>$actual_purchase_price]);
                            //入库总数量
                            $count = $this->instockItem->alias('i')->join('fa_in_stock s','i.in_stock_id=s.id')->join('fa_check_order c','s.check_id=c.id')->where('c.purchase_id',$vv['purchase_id'])->sum('i.in_stock_num');
                            $purchase_order = $this->purchase_item->alias('i')->join('fa_purchase_order o','i.purchase_id=o.id')->where('i.purchase_id',$vv['purchase_id'])->field('round(o.purchase_total/purchase_num,2) purchase_price,actual_purchase_price,i.sku')->find();
                            //实际采购成本和预估成本不一致，冲减差值
                            if($purchase_order['purchase_price'] != $purchase_order['actual_purchase_price']){
                                //计算订单出库数量
                                $out_count1 = $this->item->where('purchase_id',$vv['purchase_id'])->where('item_order_number','<>','')->where('sku',$purchase_order['sku'])->where('library_status',2)->count();
                                //计算出库数量
                                $out_count2 = $this->outstockItem->alias('i')->join('fa_out_stock s','s.id=i.out_stock_id','left')->where('s.purchase_id',$vv['purchase_id'])->where('status',2)->where('i.sku',$purchase_order['sku'])->sum('out_stock_num');
                                $out_count = $out_count1+$out_count2;
                                $result['purchase_id'] = $vv['purchase_id'];
                                $result['create_time'] = time();
                                //误差数量
                                $result['count'] = $count-$out_count;
                                //误差单价
                                $result['price'] = round($purchase_order['actual_purchase_price']-$purchase_order['purchase_price'],2);
                                //误差总金额
                                $result['total'] = round($result['count']*$result['price'],2);
                                Db::name('finance_cost_error')->insert($result);
                                /**************************************计算成本冲减end****************************************/
                                /**************************************成本核算start****************************************/
                                if($out_count1 != 0){
                                    //订单出库
                                    $order = $this->item->where('purchase_id',$vv['purchase_id'])->where('item_order_number','<>','')->where('sku',$purchase_order['sku'])->where('library_status',2)->select();
                                    $result1 = array();
                                    foreach ($order as $kk1=>$vv1){
                                        //拆分订单号
                                        $order_number = explode('-',$vv1['item_order_number']);
                                        $order_number = $order_number[0];
                                        if(isset($result1[$order_number])){
                                            $result1[$order_number] += 1;
                                        }else{
                                            $result1[$order_number] = 1;
                                        }
                                    }
                                    foreach ($result1 as $rr1=>$ss1){
                                        //获取成本核算中的订单数据
                                        $cost_order_info = $this->financecost->where(['order_number' => $rr1, 'type' => 2,'bill_type'=>8])->find();
                                        //如果有出库数据，需要添加冲减暂估结算金额和增加成本核算数据
                                        $arr1['type'] = 2;   //类型：成本
                                        $arr1['bill_type'] = 10;    //单据类型：暂估结算金额
                                        $arr1['frame_cost'] = round($ss1*$purchase_order['purchase_price'],2);    //镜架成本：剩余预估单价*剩余数量
                                        $arr1['order_number'] = $rr1;  //订单号
                                        $arr1['site'] = $cost_order_info['site'];  //站点
                                        $arr1['order_type'] = $cost_order_info['order_type'];  //订单类型
                                        $arr1['order_money'] = $cost_order_info['order_money'];  //订单金额
                                        $arr1['income_amount'] = $cost_order_info['income_amount'];  //收入金额
                                        $arr1['action_type'] = 2;  //动作类型：冲减
                                        $arr1['order_currency_code'] = $cost_order_info['order_currency_code'];  //币种
                                        $arr1['is_carry_forward'] = $cost_order_info['is_carry_forward'];  //是否结转
                                        $arr1['payment_time'] = $cost_order_info['payment_time'];  //订单支付时间
                                        $arr1['payment_method'] = $cost_order_info['payment_method'];  //订单支付方式
                                        $arr1['createtime'] = time();  //创建时间
                                        $arr1['cycle_id'] = $cost_order_info['cycle_id'];  //关联周期结转单id
                                        Db::name('finance_cost')->insert($arr1);
                                        //增加成本核算记录
                                        $arr2['type'] = 2;   //类型：成本
                                        $arr2['bill_type'] = 8;    //单据类型：实际结算金额
                                        $arr2['frame_cost'] = round($ss1*$purchase_order['actual_purchase_price'],2);    //镜架成本：剩余实际单价*剩余数量
                                        $arr2['order_number'] = $rr1;  //订单号
                                        $arr2['site'] = $cost_order_info['site'];  //站点
                                        $arr2['order_type'] = $cost_order_info['order_type'];  //订单类型
                                        $arr2['order_money'] = $cost_order_info['order_money'];  //订单金额
                                        $arr2['income_amount'] = $cost_order_info['income_amount'];  //收入金额
                                        $arr2['action_type'] = 1;  //动作类型：增加
                                        $arr2['order_currency_code'] = $cost_order_info['order_currency_code'];  //币种
                                        $arr2['is_carry_forward'] = $cost_order_info['is_carry_forward'];  //是否结转
                                        $arr2['payment_time'] = $cost_order_info['payment_time'];  //订单支付时间
                                        $arr2['payment_method'] = $cost_order_info['payment_method'];  //订单支付方式
                                        $arr2['createtime'] = time();  //创建时间
                                        $arr2['cycle_id'] = $cost_order_info['cycle_id'];  //关联周期结转单id
                                        Db::name('finance_cost')->insert($arr2);
                                    }
                                }
                                if($out_count2 != 0){
                                    //出库单出库
                                    $outorder = $this->outstockItem->alias('i')->join('fa_out_stock s','s.id=i.out_stock_id','left')->where('s.purchase_id',$vv['purchase_id'])->where('status',2)->where('i.sku',$purchase_order['sku'])->group('s.out_stock_number')->field('s.id,s.out_stock_number,sum(i.out_stock_num) count')->select();
                                    foreach ($outorder as $rr2=>$ss2){
                                        //如果有出库数据，需要添加冲减暂估结算金额和增加成本核算数据
                                        $arr3['type'] = 2;   //类型：成本
                                        $arr3['bill_type'] = 11;    //单据类型：暂估结算金额
                                        $arr3['frame_cost'] = round($ss2['count']*$purchase_order['purchase_price'],2);    //镜架成本：剩余预估单价*剩余数量
                                        $arr3['order_number'] = $ss2['out_stock_number'];  //出库单号
                                        $arr3['out_stock_id'] = $ss2['id'];  //出库单id
                                        $arr3['action_type'] = 2;  //动作类型：冲减
                                        $arr3['order_currency_code'] = 'CNY';  //币种
                                        $arr3['createtime'] = time();  //创建时间
                                        Db::name('finance_cost')->insert($arr3);
                                        //增加成本核算记录
                                        $arr4['type'] = 2;   //类型：成本
                                        $arr4['bill_type'] = 9;    //单据类型：实际结算金额
                                        $arr4['frame_cost'] = round($ss2['count']*$purchase_order['actual_purchase_price'],2);    //镜架成本：剩余实际单价*剩余数量
                                        $arr4['order_number'] = $ss2['out_stock_number'];  //出库单号
                                        $arr4['out_stock_id'] = $ss2['id'];  //出库单id
                                        $arr4['action_type'] = 1;  //动作类型：增加
                                        $arr4['order_currency_code'] = 'CNY';  //币种
                                        $arr4['createtime'] = time();  //创建时间
                                        Db::name('finance_cost')->insert($arr4);
                                    }
                                }
                                /**************************************成本核算end****************************************/
                            }
                        }
                    }
                    //结算金额为负的话 要计算采购单成本 （所有批次入库数量乘以采购单价 - 扣款金额 ）/ 入库数量
                    $status = 4;
                }
                // die;
                //更新主表状态
                Db::name('finance_statement')->where('id', $v['id'])->update(['status' => $status]);
            }
            // die;

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

    //创建结算单中间采购批次信息表
    public function table1()
    {
        $ids = input('ids');
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $instock = new Instock();
            $supplier_id = 1;
            //供应商详细信息
            $supplier = Db::name('supplier')->where('id', $supplier_id)->field('period,currency')->find();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $instock
                ->alias('a')
                ->join('check_order b', 'a.check_id = b.id', 'left')
                ->join('check_order_item f', 'f.check_id = b.id')
                ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
                ->join('purchase_order_item d', 'd.purchase_id = c.id')
                ->join('in_stock_item e', 'a.id = e.in_stock_id')
                ->where('b.supplier_id', $supplier_id)
                ->where('a.id', 'in', $ids)
                ->where('a.status', 2)//已审核通过的入库单
                ->count();
            $list = $instock
                ->alias('a')
                ->join('check_order b', 'a.check_id = b.id', 'left')
                ->join('check_order_item f', 'f.check_id = b.id')
                ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
                ->join('purchase_order_item d', 'd.purchase_id = c.id')
                ->join('in_stock_item e', 'a.id = e.in_stock_id')
                ->where('b.supplier_id', $supplier_id)
                ->where('a.id', 'in', $ids)
                ->where('a.status', 2)//已审核通过的入库单
                ->where('c.id', '>', 16475)
                ->field('c.purchase_number,a.id,d.purchase_price,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
                ->select();
            foreach ($list as $k => $v) {
                //批次 第几批的
                $list[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id', $v['batch_id'])->value('batch');
                //入库金额 质检合格数量*采购单价
                $list[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['quantity_num'], 2, '.', '');
                //退货金额 质检不合格数量*采购单价
                $list[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'], 2, '.', '');
                //预付金额 已支付预付金额
                $list[$k]['wait_pay'] = Db::name('finance_purchase')->where('purchase_id', $v['purchase_id'])->value('pay_grand_total');
                $list[$k]['now_wait_pay'] = $list[$k]['wait_pay'];
                $data = [];
                $map = [];
                if ($v['batch_id'] == 0) {
                    $map['purchase_id'] = ['=', $v['purchase_id']];
                    //采购数量无批次 应该是采购单数量
                    $list[$k]['arrival_num'] = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->value('purchase_num');
                } else {
                    $map['purchase_id'] = ['=', $v['purchase_id']];
                    $map['batch_id'] = ['=', $v['batch_id']];
                    //采购数量有批次 应该是采购批次的数量
                    $list[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id', $v['batch_id'])->value('arrival_num');
                }
                //采购单物流单详情
                $row = Db::name('logistics_info')->where($map)->field('logistics_number,logistics_company_no,collect_time,createtime')->find();
                
                if(!empty($row['collect_time'])){
                    $list[$k]['period'] = date("Y-m-t",strtotime(($row['collect_time'] . '+' . $supplier['period'] . 'month')));
                }else{
                    $list[$k]['period'] = date("Y-m-t",strtotime(($row['createtime'] . '+' . $supplier['period'] . 'month')));
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }
}
