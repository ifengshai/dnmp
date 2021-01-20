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
            $map = [];
            $map['status'] = ['=',1];

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k=>$v){
                $list[$k]['now_wait_total'] = 1;
                $list[$k]['all_wait_total'] = 1;
                $list[$k]['statement_status'] = 1;
            }
            $result = array("total" => $total, "rows" => $list);
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
        $supplier = Db::name('supplier')->where('id',$supplier_id)->field('period,currency')->find();
        $all_purchase_order =$instock
            ->alias('a')
            ->join('check_order b','a.check_id = b.id','left')
            ->join('check_order_item f','f.check_id = b.id')
            ->join('purchase_order c','b.purchase_id = c.id','left')
            ->join('purchase_order_item d','d.purchase_id = c.id')
            ->join('in_stock_item e','a.id = e.in_stock_id')
            ->where('b.supplier_id',$supplier_id)
            ->where('a.status',2)//已审核通过的入库单
            ->where('b.batch_id','>',0)//已审核通过的入库单
            ->field('c.purchase_number,a.id,d.purchase_price,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
            ->select();
        dump(collection($supplier)->toArray());

        foreach ($all_purchase_order as $k=>$v){
            //批次 第几批的
            $all_purchase_order[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id',$v['batch_id'])->value('batch');
            $all_purchase_order[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id',$v['batch_id'])->value('arrival_num');
            $all_purchase_order[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['in_stock_num'],2,'.','');
            $all_purchase_order[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'],2,'.','');
        }
        dump(collection($all_purchase_order)->toArray());
        $data['now_wait_statement'] = 1;
        $data['all_wait_statement'] = 1;
        $data['wait_statement_detail'] = 1;
        return $data;
    }

    public function detail()
    {
        $tab = [['id'=>1,'name'=>'待结算明细'],['id'=>2,'name'=>'结算记录']];
        $this->assign('tab',$tab);
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
                ->where('a.status',2)//已审核通过的入库单
                ->field('c.purchase_number,a.id,d.purchase_price,f.quantity_num,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id,c.purchase_name,c.pay_type,e.in_stock_num,f.arrivals_num,f.quantity_num,f.unqualified_num')
                ->select();
            foreach ($list as $k=>$v){
                //批次 第几批的
                $list[$k]['purchase_batch'] = Db::name('purchase_batch')->where('id',$v['batch_id'])->value('batch');
                $list[$k]['arrival_num'] = Db::name('purchase_batch_item')->where('purchase_batch_id',$v['batch_id'])->value('arrival_num');
                $list[$k]['in_stock_money'] = number_format($v['purchase_price'] * $v['quantity_num'],2,'.','');
                $list[$k]['unqualified_num_money'] = number_format($v['purchase_price'] * $v['unqualified_num'],2,'.','');
                $list[$k]['wait_pay'] = Db::name('finance_purchase')->where('purchase_id',$v['purchase_id'])->value('pay_grand_total');
                $list[$k]['now_wait_pay'] = $list[$k]['wait_pay'];
                $data = [];
                $map = [];
                if ($v['batch_id'] == 0){
                    $map['purchase_id'] = ['=',$v['purchase_id']];
                }else{
                    $map['purchase_id'] = ['=',$v['purchase_id']];
                    $map['batch_id'] = ['=',$v['batch_id']];
                }
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
                ->count();

            $list = $statement
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }
}
