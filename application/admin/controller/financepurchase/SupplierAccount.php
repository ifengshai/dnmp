<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\purchase\PurchaseOrder;
use app\admin\model\warehouse\Instock;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Hook;
use think\Request;

class SupplierAccount extends Backend
{
    protected $noNeedRight = [];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
    }

    /**
     * 供应商结算列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/15
     * Time: 13:49:14
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
            if ($filter['statement_status']) {
                $statement_status = $filter['statement_status'];
                unset($filter['statement_status']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            $map = [];
            $map['status'] = ['=', 1];

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $lists = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $lists = collection($lists)->toArray();
            foreach ($lists as $kkk => $vvv) {
                $instock = new Instock();
                $supplier_id = $vvv['id'];
                $check_order_ids = Db::name('check_order')->where('supplier_id', $supplier_id)->column('id');
                //入库单已经创建过结算单并且结算单不是已取消状态的不可再重新结算
                $instock_ids = Db::name('finance_statement_item')
                    ->alias('a')
                    ->join('finance_statement b', 'a.statement_id = b.id')
                    ->where('b.status', 'in', [0, 1, 3, 4, 6])
                    ->where('b.supplier_id', $supplier_id)
                    ->column('a.in_stock_id');
                $list = $instock
                    ->alias('a')
                    ->join('check_order b', 'a.check_id = b.id', 'left')
                    ->join('check_order_item f', 'f.check_id = b.id')
                    ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
                    ->join('purchase_order_item d', 'd.purchase_id = c.id')
                    ->join('in_stock_item e', 'a.id = e.in_stock_id')
                    ->where('b.supplier_id', $supplier_id)
                    ->where('a.status', 2)//已审核通过的入库单
                    ->where('a.check_id', 'in', $check_order_ids)
                    ->where('a.id', 'not in', $instock_ids)
                    ->where('c.id', '>', 16475)
                    ->field('c.purchase_number,a.id,d.purchase_price,c.purchase_freight,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
                    ->select();
                $wait_pay_money = 0;
                $now = date('Y-m-t', time());
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
                        //采购批次是第一批 待结算金额 = 采购批次入库数量*采购单价-预付款金额
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
                        $list[$k]['periods'] = date("Y-m-t",strtotime(($row['collect_time'] . '+' . $vvv['period'] . 'month')));
                    }else{
                        $list[$k]['periods'] = date("Y-m-t",strtotime(($row['createtime'] . '+' . $vvv['period'] . 'month')));
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
                    //所有的待结算的总和是所有的待结算
                    $all += $list[$k]['all_money'];
                    if ($list[$k]['periods'] <= $now) {
                        //结算账期是本月底的算入本期待结算
                        $wait_pay_money += $list[$k]['all_money'];
                    }
                }
                $lists[$kkk]['now_wait_total'] = round($wait_pay_money, 2);
                $lists[$kkk]['all_wait_total'] = round($all, 2);
                if ($wait_pay_money == 0) {
                    $lists[$kkk]['statement_status'] = 1;
                } else {
                    $lists[$kkk]['statement_status'] = 2;
                }
                if ($statement_status) {
                    if ($lists[$kkk]['statement_status'] != $statement_status) {
                        unset($lists[$kkk]);
                    }
                }
            }
            // $lists = array_merge($lists);
            // $total = count($lists);
            // dump($lists);die;
            $result = array("total" => $total, "rows" => $lists);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 某个供应商的本期待结算金额 总待结算金额 待结算明细
     * @params $suppiler_id 供应商id
     * @return $data['now_wait_statement'] 本期待结算金额
     * @return $data['all_wait_statement'] 全部待结算金额
     * @return $data['wait_statement_detail'] 待结算明细
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/18
     * Time: 13:44:10
     */
    public function supplier_wait_statement($supplier_id = null)
    {
        $instock = new Instock();
        $supplier_id = 1;
        //供应商详细信息
        $supplier = Db::name('supplier')->where('id', $supplier_id)->field('period,currency')->find();
        $all_purchase_order = $instock
            ->alias('a')
            ->join('check_order b', 'a.check_id = b.id', 'left')
            ->join('check_order_item f', 'f.check_id = b.id')
            ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
            ->join('purchase_order_item d', 'd.purchase_id = c.id')
            ->join('in_stock_item e', 'a.id = e.in_stock_id')
            ->where('b.supplier_id', $supplier_id)
            ->where('a.status', 2)//已审核通过的入库单
            ->where('b.batch_id', '>', 0)//已审核通过的入库单
            ->field('c.purchase_number,a.id,d.purchase_price,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
            ->select();
        dump(collection($supplier)->toArray());

        foreach ($all_purchase_order as $k => $v) {
            //批次 第几批的
            $all_purchase_order[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id', $v['batch_id'])->value('batch');
            $all_purchase_order[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id', $v['batch_id'])->value('arrival_num');
            $all_purchase_order[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['in_stock_num'], 2, '.', '');
            $all_purchase_order[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'], 2, '.', '');
        }
        dump(collection($all_purchase_order)->toArray());
        $data['now_wait_statement'] = 1;
        $data['all_wait_statement'] = 1;
        $data['wait_statement_detail'] = 1;
        return $data;
    }

    //供应商结算详情
    public function detail()
    {
        $ids = input('ids');
        $supplier = Db::name('supplier')->where('id', $ids)->find();
        $supplier['period'] = $supplier['period'] == 0 ? '无账期' : $supplier['period'] . '个月';
        $instock = new Instock();
        // $supplier_id = 1;
        $supplier_id = $supplier['id'];
        $check_order_ids = Db::name('check_order')->where('supplier_id', $supplier_id)->column('id');
        //入库单已经创建过结算单并且结算单不是已取消状态的不可再重新结算
        $instock_ids = Db::name('finance_statement_item')
            ->alias('a')
            ->join('finance_statement b', 'a.statement_id = b.id')
            ->where('b.status', 'in', [0, 1, 3, 4, 6])
            ->where('b.supplier_id', $supplier_id)
            ->column('a.in_stock_id');
        //供应商详细信息
        $list = $instock
            ->alias('a')
            ->join('check_order b', 'a.check_id = b.id', 'left')
            ->join('check_order_item f', 'f.check_id = b.id')
            ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
            ->join('purchase_order_item d', 'd.purchase_id = c.id')
            ->join('in_stock_item e', 'a.id = e.in_stock_id')
            ->where('b.supplier_id', $supplier_id)
            ->where('a.status', 2)//已审核通过的入库单
            ->where('a.check_id', 'in', $check_order_ids)
            ->where('a.id', 'not in', $instock_ids)
            ->where('c.id', '>', 16475)
            ->field('c.purchase_number,a.id,d.purchase_price,c.purchase_freight,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
            ->select();
        // dump($instock->getLastSql());
        // dump($list);
        // die;
        $wait_pay_money = 0;
        $now = date('Y-m-t', time());
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
                //采购批次是第一批 待结算金额 = 采购批次入库数量*采购单价-预付款金额
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
            //所有的待结算的总和是所有的待结算
            $all += $list[$k]['all_money'];
            if ($list[$k]['period'] <= $now) {
                //结算账期是本月底的算入本期待结算
                $wait_pay_money += $list[$k]['all_money'];
            }
        }
        $all_wait_pay_money = $all;
        $this->assignconfig('supplier_id', $ids);
        $this->assign('supplier', $supplier);
        $this->assign('wait_pay_money', round($wait_pay_money, 2));
        $this->assign('all_wait_pay_money', round($all_wait_pay_money, 2));
        return $this->view->fetch();
    }

    /**
     * 待结算列表 供应商详情 供应商待结算明细
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/19
     * Time: 19:07:57
     */
    public function table1()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['period']) {
                $time = explode(' ', $filter['period']);
                $timeBegin = strtotime($time[0] . $time[1]);
                $timeEnd = strtotime($time[3] . $time[4]);
                unset($filter['period']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            $instock = new Instock();
            $supplier_id = input('supplier_id');
            //入库单已经创建过结算单并且结算单不是已取消状态的不可再重新结算
            $instock_ids = Db::name('finance_statement_item')
                ->alias('a')
                ->join('finance_statement b', 'a.statement_id = b.id')
                ->where('b.status', 'in', [0, 1, 3, 4, 6])
                ->where('b.supplier_id', $supplier_id)
                ->column('a.in_stock_id');

            //供应商详细信息
            $supplier = Db::name('supplier')->where('id', $supplier_id)->field('period,currency')->find();
            //所有的质检单
            $check_order_ids = Db::name('check_order')->where('supplier_id', $supplier_id)->column('id');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $instock
                ->alias('a')
                ->join('check_order b', 'a.check_id = b.id', 'left')
                ->join('check_order_item f', 'f.check_id = b.id')
                ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
                ->join('purchase_order_item d', 'd.purchase_id = c.id')
                ->join('in_stock_item e', 'a.id = e.in_stock_id')
                ->where('b.supplier_id', $supplier_id)
                ->where('a.status', 2)//已审核通过的入库单
                ->where('a.check_id', 'in', $check_order_ids)//已审核通过的入库单
                ->where('a.id', 'not in', $instock_ids)
                ->where('c.id', '>', 16475)
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $instock
                ->alias('a')
                ->join('check_order b', 'a.check_id = b.id', 'left')
                ->join('check_order_item f', 'f.check_id = b.id')
                ->join('purchase_order c', 'b.purchase_id = c.id', 'left')
                ->join('purchase_order_item d', 'd.purchase_id = c.id')
                ->join('in_stock_item e', 'a.id = e.in_stock_id')
                ->where('b.supplier_id', $supplier_id)
                ->where('a.status', 2)//已审核通过的入库单
                ->where('a.check_id', 'in', $check_order_ids)//已审核通过的入库单
                ->where('a.id', 'not in', $instock_ids)
                ->where('c.id', '>', 16475)
                ->field('c.purchase_number,a.id,d.purchase_price,c.purchase_freight,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
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
                if ($timeBegin && $timeEnd) {
                    if (strtotime($list[$k]['period']) < $timeBegin || strtotime($list[$k]['period']) > $timeEnd) {
                        unset($list[$k]);
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }

    /**
     * 待结算列表 供应商详情 供应商结算记录
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/19
     * Time: 19:38:04
     */
    public function table2()
    {
        $statement = new \app\admin\model\financepurchase\Statement();
        $supplier_id = input('supplier_id');
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $statement
                ->where($where)
                ->order($sort, $order)
                ->where('supplier_id', $supplier_id)
                ->count();

            $list = $statement
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->where('supplier_id', $supplier_id)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }
}
