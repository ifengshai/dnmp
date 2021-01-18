<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;

class PayOrder extends Backend
{
    public function _initialize()
    {
        $this->financepurchase = new \app\admin\model\financepurchase\FinancePurchase;
        $this->payorder = new \app\admin\model\financepurchase\FinancePayorder;
        $this->payorder_item = new \app\admin\model\financepurchase\FinancePayorderItem;
        $this->supplier = new \app\admin\model\purchase\Supplier;
        return parent::_initialize();

    }
    /*
     * 付款单列表
     * */
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
            if($filter['pay_number']){
                //付款申请单号
                $map['p.pay_number'] = $filter['pay_number'];
            }
            if($filter['supplier_name']){
                //供应商名称
                $map['s.supplier_name'] = ['like','%'.$filter['supplier_name'].'%'];
            }
            if($filter['status']){
                //状态
                $map['p.status'] = $filter['status'];
            }
            if($filter['create_user']){
                //审核人
                $map['p.create_user'] = $filter['create_user'];
            }
            if($filter['check_user']){
                //创建人
                $map['p.check_user'] = $filter['check_user'];
            }
            //创建时间
            if($filter['create_time']){
                $createat = explode(' ', $filter['create_time']);
                $start = strtotime($createat[0].' '.$createat[1]);
                $end = strtotime($createat[3].' '.$createat[4]);
                $map['p.create_time'] = ['between', [$start,$end]];
            }
            unset($filter['pay_number']);
            unset($filter['status']);
            unset($filter['create_user']);
            unset($filter['check_user']);
            unset($filter['create_time']);
            unset($filter['one_time-operate']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'p.id';
            $total = $this->payorder
                ->alias('p')
                ->join('fa_supplier s','p.supply_id=s.id','left')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->payorder
                ->alias('p')
                ->join('fa_supplier s','p.supply_id=s.id','left')
                ->field('p.id,s.supplier_name,p.pay_number,p.status,p.create_user,p.check_user,FROM_UNIXTIME(p.create_time) create_time')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $now_user = session('admin.nickname');
        $this->assignconfig('now_user', $now_user);
        return $this->view->fetch();
    }
    /*
     * 创建付款单
     * */
    public function add(){
        //付款单号生成
        $pay_number = 'FK' . date('YmdHis') . rand(100000, 999999);
        $params = $this->request->param();
        $ids = $params['ids'];
        $data = $this->getPayinfo($ids);
        $supplier = $data['supplier'];
        $settle = $data['settle'];
        $total1 = $data['total1'];
        $count1 = $data['count1'];
        $prepay = $data['prepay'];
        $total2 = $data['total2'];
        $count2 = $data['count2'];
        $total = $data['total'];
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $ids = $params['ids'];
            $data = $this->getPayinfo($ids);
            $settle = $data['settle'];
            $prepay = $data['prepay'];
            unset($params['ids']);
            //添加付款单主表数据
            $params['create_user'] = session('admin.nickname'); //创建人
            $params['create_time'] = time();   //创建时间
            $pay_id = Db::name('finance_payorder')->insertGetId($params);
            //更改付款申请单中显示状态
            $this->financepurchase->where('id','in',$ids)->update(['is_show'=>2]);
            //添加付款单子表数据
            if($settle){
                //结算数据处理
                foreach ($settle as $kk1=>$vv1){
                    $arr1 = [];
                    $arr1['pay_id'] = $pay_id;
                    $arr1['pay_type'] = 3;
                    $arr1['purchase_id'] = $vv1['id'];
                    $arr1['purchase_order'] = $vv1['purchase_number'];
                    $arr1['purchase_batch_id'] = $vv1['purchase_batch_id'];
                    $arr1['now_before_total'] = $vv1['now_before_total'];
                    $arr1['instock_total'] = $vv1['instock_total'];
                    $arr1['deduction_total'] = $vv1['deduction_total'];
                    $arr1['deduction_reason'] = $vv1['deduction_reason'];
                    $arr1['wait_statement_total'] = $vv1['wait_statement_total'];
                    Db::name('finance_payorder_item')->insert($arr1);
                }
            }
            if($prepay){
                //预付数据处理
                foreach ($prepay as $kk2=>$vv2){
                    $arr2 = [];
                    $arr2['pay_id'] = $pay_id;
                    $arr2['pay_type'] = $vv2['pay_type'];
                    $arr2['purchase_id'] = $vv2['id'];
                    $arr2['purchase_order'] = $vv2['purchase_number'];
                    $arr2['purchase_total'] = $vv2['purchase_total'];
                    $arr2['pay_rate'] = $vv2['pay_rate'];
                    $arr2['pay_grand_total'] = $vv2['pay_grand_total'];
                    Db::name('finance_payorder_item')->insert($arr2);
                }
            }
            $this->success('添加成功！！', '',url('index'));
        }
        $this->view->assign(compact('pay_number','supplier', 'settle', 'prepay','total1','total2','total','count1','count2','ids'));
        return $this->view->fetch();
    }
    /*
     * 详情
     * */
    public function detail(){
        $id = input('ids');
        $supplier = $this->supplier->where('id',$id)->field('id,supplier_name,currency,period,opening_bank,bank_account,recipient_name')->select();
        //获取付款单信息
        $pay_order = $this->payorder->where('id',$id)->find();
        //获取付款单子单结算信息
        $settle = $this->payorder_item->where(['pay_id'=>$id,'pay_type'=>3])->select();
        $total1= 0;
        $count1 = 0;
        foreach ($settle as $k=>$v){
            $total1 += $v['wait_statement_total'];
            $count1++;
        }
        //获取付款单子单预付信息
        $prepay = $this->payorder_item->where(['pay_id'=>$id])->where('pay_type','<>',3)->select();
        $total2= 0;
        $count2 = 0;
        foreach ($prepay as $k1=>$v1){
            $total2 += $v1['pay_grand_total'];
            $count2++;
        }
        $total = $total1+$total2;
        $this->view->assign(compact('pay_order','supplier', 'settle', 'prepay','total1','total2','total','count1','count2'));
        return $this->view->fetch();
    }
    /*
     * 编辑
     * */
    public function edit($ids = ''){
        $id = input('ids');
        //获取付款单信息
        $pay_order = $this->payorder->where('id',$id)->find();
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $ids = $params['ids'];
            unset($params['ids']);
            unset($params['currency']);
            Db::name('finance_payorder')->where('id',$ids)->update($params);
            $this->success('编辑成功！！', '','');
        }
        $this->view->assign(compact('pay_order','now_user'));
        return $this->view->fetch();
    }
    /*
     * 更改状态
     * */
    public function setStatus($ids = ''){
        $status = input('status');
        $row = $this->payorder->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (request()->isAjax()) {
            $params['status'] = $status;
            $result = $row->allowField(true)->save($params);
            if($status == 6 || $status == 7){
                //在待付款单中显示
                $purchase_id = $this->payorder_item->where('pay_id',$ids)->column('purchase_id');
                $purchase_id = implode(',',$purchase_id);
                $this->financepurchase->where('id','in',$purchase_id)->update(['is_show'=>1]);
            }
            if (false !== $result) {
                $this->success('操作成功！！');
            } else {
                $this->error('操作失败！！');
            }
        }
        $this->error('404 not found');
    }
    /**
     * 上传
     */
    public function upload()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $this->success('', '', $params);
        }
        return $this->view->fetch();
    }
    public function getItemData()
    {
        if ($this->request->isAjax()) {
            $id = input('id');
            $batch = new \app\admin\model\purchase\PurchaseBatch();
            $item = $batch->alias('a')->where('a.id', $id)
                ->field('b.sku,b.arrival_num,c.supplier_sku,c.purchase_num,a.purchase_id,d.supplier_id,d.replenish_id')
                ->join(['fa_purchase_batch_item' => 'b'], 'a.id=b.purchase_batch_id')
                ->join(['fa_purchase_order_item' => 'c'], 'c.purchase_id=a.purchase_id and b.sku=c.sku')
                ->join(['fa_purchase_order' => 'd'], 'd.id=a.purchase_id')
                ->select();
            //查询质检数量
            $skus = array_column($item, 'sku');
            //查询质检信息
            $check_map['Check.purchase_id'] = $id;
            $check_map['type'] = 1;
            $check = new \app\admin\model\warehouse\Check;
            $list = $check->hasWhere('checkItem', ['sku' => ['in', $skus]])
                ->where($check_map)
                ->field('sku,sum(arrivals_num) as check_num')
                ->group('sku')
                ->select();
            $list = collection($list)->toArray();
            //重组数组
            $check_item = [];
            foreach ($list as $k => $v) {
                @$check_item[$v['sku']]['check_num'] = $v['check_num'];
            }

            foreach ($item as $k => $v) {
                $item[$k]['check_num'] = @$check_item[$v['sku']]['check_num'] ?? 0;
            }

            if ($item) {
                $this->success('', '', $item);
            } else {
                $this->error();
            }
        }
    }
    //获取付款申请单信息
    public function getPayinfo($ids = 0){
        //供应商id
        $supplier_id = $this->financepurchase->where('id','in',$ids)->value('supplier_id');
        //供应商信息
        $supplier = $this->supplier->where('id',$supplier_id)->field('id,supplier_name,currency,period,opening_bank,bank_account,recipient_name')->select();
        //结算信息
        $settle = $this->financepurchase->alias('p')->join('fa_purchase_order o','o.id=p.purchase_id','left')->join('fa_finance_statement s','s.finance_purcahse_id=p.id')->field('p.id,o.purchase_number,p.purchase_batch_id,s.now_before_total,s.instock_total,s.deduction_total,s.deduction_reason,s.wait_statement_total')->where('p.pay_type',3)->where('p.supplier_id',$supplier_id)->select();
        $total1 = 0;  //结算待结算金额合计
        $count1 = 0;
        foreach ($settle as $val){
            $total1 += $val['wait_statement_total'];
            $count1++;
        }
        //预付信息
        $prepay = $this->financepurchase->alias('p')->join('fa_purchase_order o','o.id=p.purchase_id','left')->field('p.id,o.purchase_number,o.purchase_total,p.pay_rate,p.pay_grand_total,p.pay_type')->where('p.pay_type','<>',3)->where('p.supplier_id',$supplier_id)->select();
        $total2 = 0;  //预付预付款金额合计
        $count2 = 0;
        foreach ($prepay as $v){
            $total2 += $v['pay_grand_total'];
            $count2++;
        }
        $total = $total1 + $total2;  //总金额合计
        $arr['supplier'] = $supplier;
        $arr['settle'] = $settle;
        $arr['total1'] = $total1;
        $arr['count1'] = $count1;
        $arr['prepay'] = $prepay;
        $arr['total2'] = $total2;
        $arr['count2'] = $count2;
        $arr['total'] = $total;
        return $arr;
    }
}
