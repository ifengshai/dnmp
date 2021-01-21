<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\financepurchase\StatementItem;
use app\admin\model\purchase\PurchaseOrder;
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
            foreach ($list as $k=>$v){
                $list[$k]['supplier_name'] = $this->supplier->where('id',$v['supplier_id'])->value('supplier_name');
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
            $list= $this->request->post("list/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                dump($params);
                dump($list);
                die;
                Db::startTrans();
                $this->statement->startTrans();
                try {
                    $statemet = [];
                    $statemet['statement_number'] = $params['order_number'];
                    $statemet['status'] = $params['status'];
                    $statemet['pay_type'] = 1;
                    $statemet['supplier_id'] = $params['supplier_id'];
                    $statemet['base_currency_code'] = $params['base_currency_code'];
                    $statemet['wait_statement_total'] = $params['product_total'];
                    $statemet['create_time'] = time();
                    $statemet['create_person'] = session('admin.nickname');
                    $statemet_id = Db::name('finance_statement')->insertGetId($statemet);


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
            $this->success('添加成功！！', url('PurchasePay/index'));
        }
        $instock = new Instock();
        $supplier_id = 1;
        //供应商详细信息
        $supplier = Db::name('supplier')->where('id', $supplier_id)->find();
        $list =$instock
            ->alias('a')
            ->join('check_order b','a.check_id = b.id','left')
            ->join('check_order_item f','f.check_id = b.id')
            ->join('purchase_order c','b.purchase_id = c.id','left')
            ->join('purchase_order_item d','d.purchase_id = c.id')
            ->join('in_stock_item e','a.id = e.in_stock_id')
            ->where('b.supplier_id',$supplier_id)
            ->where('a.id','in',$ids)
            ->where('a.status',2)//已审核通过的入库单
            ->field('c.purchase_number,a.id,d.purchase_price,c.purchase_freight,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
            ->select();
        $all = 0;
        foreach ($list as $k=>$v){
            //批次 第几批的
            $list[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id',$v['batch_id'])->value('batch');
            //入库金额 质检合格数量*采购单价
            $list[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['quantity_num'],2,'.','');
            //退货金额 质检不合格数量*采购单价
            $list[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'],2,'.','');
            //预付金额 已支付预付金额
            $list[$k]['wait_pay'] = Db::name('finance_purchase')->where('purchase_id',$v['purchase_id'])->value('pay_grand_total');
            $list[$k]['now_wait_pay'] = $list[$k]['wait_pay'];
            $data = [];
            $map = [];
            if ($v['batch_id'] == 0){
                $map['purchase_id'] = ['=',$v['purchase_id']];
                //采购数量无批次 应该是采购单数量
                $list[$k]['arrival_num'] = Db::name('purchase_order_item')->where('purchase_id',$v['purchase_id'])->value('purchase_num');
                switch ($v['pay_type']) {
                    case 1:
                        //无批次预付款 待结算金额公式=入库金额+运费-已支付预付金额
                        $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'] - $list[$k]['now_wait_pay'];
                        break;
                    case 2:
                        //无批次全款预付 待结算金额 = 入库金额 - 已支付预付金额
                        $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'] - $list[$k]['now_wait_pay'];
                        break;
                    default:
                        //货到付款的 待结算金额 = 入库金额 + 运费
                        $list[$k]['all_money'] = $list[$k]['in_stock_money'] + $v['purchase_freight'];
                }
            }else{
                $map['purchase_id'] = ['=',$v['purchase_id']];
                $map['batch_id'] = ['=',$v['batch_id']];
                //采购数量有批次 应该是采购批次的数量
                $list[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id',$v['batch_id'])->value('arrival_num');
                //采购批次是第一批 待结算金额 = 采购批次入库数量*采购单价-预付款金额
                if ($list[$k]['purchase_batch'] == 1){
                    $list[$k]['all_money'] = $list[$k]['in_stock_money'] - $list[$k]['now_wait_pay'];
                }else{
                    //不是第一批 批次待结算金额=采购批次入库数量*采购单价
                    $list[$k]['all_money'] = $list[$k]['in_stock_money'];
                }
            }
            //采购单物流单详情
            $row = Db::name('logistics_info')->where($map)->field('logistics_number,logistics_company_no')->find();
            //物流单快递100接口
            if ($row['logistics_number']) {
                $arr = explode(',', $row['logistics_number']);
                //物流公司编码
                $company = explode(',', $row['logistics_company_no']);
                foreach ($arr as $kk => $vv) {
                    try {
                        //快递单号
                        $param['express_id'] = trim($vv);
                        $param['code'] = trim($company[$kk]);
                        $data[$kk] = Hook::listen('express_query', $param)[0];
                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                    }
                }
            }
            //拿物流单接口返回的倒数第二条数据的时间作为揽件的时间 并且加一个月后的月底作为当前采购单批次的 结算周期
            if (!empty($data[0])){
                $list[$k]['period'] = date("Y-m-t", strtotime($data[0]['data'][count($data[0]['data'])-2]['time'].'+'.$supplier['period'].'month'));
            }else{
                $list[$k]['period'] = '获取不到物流单详情';
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
            $all += $list[$k]['all_money'];
        }
        $supplier['period'] = $supplier['period'] == 0 ? '无账期':$supplier['period'] . '个月';
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
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $update['status'] = $params['status'];
                    $update['pay_type'] = $params['pay_type'];
                    $update['pay_rate'] = $params['pay_rate'];
                    $update['pay_grand_total'] = $params['pay_grand_total'];
                    $result = Db::name('finance_purchase')->where('order_number',$params['order_number'])->update($update);
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
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
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
            $supplier = Db::name('supplier')->where('id',$supplier_id)->field('period,currency')->find();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $instock
                ->alias('a')
                ->join('check_order b','a.check_id = b.id','left')
                ->join('check_order_item f','f.check_id = b.id')
                ->join('purchase_order c','b.purchase_id = c.id','left')
                ->join('purchase_order_item d','d.purchase_id = c.id')
                ->join('in_stock_item e','a.id = e.in_stock_id')
                ->where('b.supplier_id',$supplier_id)
                ->where('a.id','in',$ids)
                ->where('a.status',2)//已审核通过的入库单
                ->count();
            $list =$instock
                ->alias('a')
                ->join('check_order b','a.check_id = b.id','left')
                ->join('check_order_item f','f.check_id = b.id')
                ->join('purchase_order c','b.purchase_id = c.id','left')
                ->join('purchase_order_item d','d.purchase_id = c.id')
                ->join('in_stock_item e','a.id = e.in_stock_id')
                ->where('b.supplier_id',$supplier_id)
                ->where('a.id','in',$ids)
                ->where('a.status',2)//已审核通过的入库单
                ->field('c.purchase_number,a.id,d.purchase_price,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
                ->select();
            foreach ($list as $k=>$v){
                //批次 第几批的
                $list[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id',$v['batch_id'])->value('batch');
                //入库金额 质检合格数量*采购单价
                $list[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['quantity_num'],2,'.','');
                //退货金额 质检不合格数量*采购单价
                $list[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'],2,'.','');
                //预付金额 已支付预付金额
                $list[$k]['wait_pay'] = Db::name('finance_purchase')->where('purchase_id',$v['purchase_id'])->value('pay_grand_total');
                $list[$k]['now_wait_pay'] = $list[$k]['wait_pay'];
                $data = [];
                $map = [];
                if ($v['batch_id'] == 0){
                    $map['purchase_id'] = ['=',$v['purchase_id']];
                    //采购数量无批次 应该是采购单数量
                    $list[$k]['arrival_num'] = Db::name('purchase_order_item')->where('purchase_id',$v['purchase_id'])->value('purchase_num');
                }else{
                    $map['purchase_id'] = ['=',$v['purchase_id']];
                    $map['batch_id'] = ['=',$v['batch_id']];
                    //采购数量有批次 应该是采购批次的数量
                    $list[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id',$v['batch_id'])->value('arrival_num');
                }
                //采购单物流单详情
                $row = Db::name('logistics_info')->where($map)->field('logistics_number,logistics_company_no')->find();
                //物流单快递100接口
                if ($row['logistics_number']) {
                    $arr = explode(',', $row['logistics_number']);
                    //物流公司编码
                    $company = explode(',', $row['logistics_company_no']);
                    foreach ($arr as $kk => $vv) {
                        try {
                            //快递单号
                            $param['express_id'] = trim($vv);
                            $param['code'] = trim($company[$kk]);
                            $data[$kk] = Hook::listen('express_query', $param)[0];
                        } catch (\Exception $e) {
                            $this->error($e->getMessage());
                        }
                    }
                }
                //拿物流单接口返回的倒数第二条数据的时间作为揽件的时间 并且加一个月后的月底作为当前采购单批次的 结算周期
                if (!empty($data[0])){
                    $list[$k]['period'] = date("Y-m-t", strtotime($data[0]['data'][count($data[0]['data'])-2]['time'].'+'.$supplier['period'].'month'));
                }else{
                    $list[$k]['period'] = '获取不到物流单详情';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }
}
