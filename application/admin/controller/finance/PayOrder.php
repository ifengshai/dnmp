<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;

class PayOrder extends Backend
{
    public function _initialize()
    {
        $this->financepurchase = new \app\admin\model\financepurchase\FinancePurchase;
        $this->statementitem = new \app\admin\model\financepurchase\StatementItem;
        $this->statement = new \app\admin\model\financepurchase\Statement;
        $this->payorder = new \app\admin\model\financepurchase\FinancePayorder;
        $this->payorder_item = new \app\admin\model\financepurchase\FinancePayorderItem;
        $this->supplier = new \app\admin\model\purchase\Supplier;
        $this->batch = new \app\admin\model\purchase\PurchaseBatch();
        $this->batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
        $this->purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $this->outstockItem = new \app\admin\model\warehouse\OutStockItem;
        $this->instockItem = new \app\admin\model\warehouse\InstockItem;
        $this->financecost = new \app\admin\model\finance\FinanceCost();
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
        $params = $this->request->param();
        $ids = $params['ids'];
        //付款单号生成
        $pay_number = 'FK' . date('YmdHis') . rand(100000, 999999);
        $data = $this->getPayinfo($ids);
        $supplier = $data['supplier'];
        $settle = $data['settle'];
        $total1 = $data['total1'];
        $count1 = $data['count1'];
        $prepay = $data['prepay'];
        foreach ($prepay as $k=>$v){
            $prepay[$k]['pay_rate'] = $v['pay_rate']*100;
        }
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
            $params['supply_id'] = $supplier['id'];   //供应商id
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
                    $arr1['purchase_id'] = $vv1['finance_purcahse_id'];
                    $arr1['purchase_order_id'] = $vv1['purchase_id'];
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
                    $arr2['purchase_order_id'] = $vv2['purchase_id'];
                    $arr2['purchase_order'] = $vv2['purchase_number'];
                    $arr2['purchase_total'] = $vv2['purchase_total'];
                    $arr2['pay_rate'] = $vv2['pay_rate'];
                    $arr2['pay_grand_total'] = $vv2['pay_grand_total'];
                    Db::name('finance_payorder_item')->insert($arr2);
                }
            }
            $this->success('添加成功！！');
        }
        $this->view->assign(compact('pay_number','supplier', 'settle', 'prepay','total1','total2','total','count1','count2','ids'));
        return $this->view->fetch();
    }
    /*
     * 详情
     * */
    public function detail(){
        $id = input('ids');
        //获取付款单信息
        $pay_order = $this->payorder->where('id',$id)->find();
        $imgs = array_filter(explode(',',$pay_order['invoice']));
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
            $pay_rate = $v1['pay_rate']*100;
            $prepay[$k1]['pay_rate'] = $pay_rate.'%';
            $total2 += $v1['pay_grand_total'];
            $count2++;
        }
        $total = $total1+$total2;
        $supplier = $this->supplier->where('id',$pay_order['supply_id'])->field('id,supplier_name,currency,period,opening_bank,bank_account,recipient_name')->find();
        $this->view->assign(compact('pay_order','supplier', 'settle', 'prepay','total1','total2','total','count1','count2','imgs'));
        return $this->view->fetch();
    }
    /*
     * 编辑
     * */
    public function edit($ids = ''){
        $id = input('ids');
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
            $pay_rate = $v1['pay_rate']*100;
            $prepay[$k1]['pay_rate'] = $pay_rate.'%';
            $total2 += $v1['pay_grand_total'];
            $count2++;
        }
        $total = $total1+$total2;
        $supplier = $this->supplier->where('id',$pay_order['supply_id'])->field('id,supplier_name,currency,period,opening_bank,bank_account,recipient_name')->find();
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $ids = $params['ids'];
            $data['desc'] = $params['desc'];
            $data['status'] = $params['status'];
            Db::name('finance_payorder')->where('id',$ids)->update($data);
            $this->success('编辑成功！！', '','');
        }
        $this->view->assign(compact('pay_order','supplier', 'settle', 'prepay','total1','total2','total','count1','count2'));
        return $this->view->fetch();
    }
    /*
     * 付款
     * */
    public function pay($ids = ''){
        $id = input('ids');
        //更改状态
        $this->payorder->where('id',$id)->update(['status'=>4]);
        //获取付款单下所有的采购单id
        $pay_order_item = $this->payorder_item->where('pay_id',$id)->where('pay_type','in','1,2')->field('purchase_order_id,pay_type')->select();
        foreach ($pay_order_item as $key=>$value){
            //判断预付款:修改采购单状态为部分付款
            if($value['pay_type'] == 1){
                Db::name('purchase_order')->where('id',$value['purchase_order_id'])->update(['payment_status'=>2]);
            }
            //判断全款预付:修改采购单状态为已经付款
            if($value['pay_type'] == 2){
                Db::name('purchase_order')->where('id',$value['purchase_order_id'])->update(['payment_status'=>3]);
            }
        }
        /**************************************计算采购成本start**********************************/
        //判断采购单id
        $purchase_order_ids = $this->payorder_item->where('pay_type',3)->where('pay_id',$id)->group('purchase_order_id')->column('purchase_order_id');
        foreach ($purchase_order_ids as $v){
            //采购单总批次
            $batch_count = $this->batch->where('purchase_id',$v)->count();
            $batch_count = $batch_count == 0 ? 1 : $batch_count;
            //付款完成总批次
            $where['i.purchase_order_id'] = $v;
            $where['p.status'] = ['in','4,5'];
            $where['i.pay_type'] = 3;
            $pay_batch_count = $this->payorder_item->alias('i')->join('fa_finance_payorder p','i.pay_id=p.id','left')->where($where)->count();
            if($batch_count == $pay_batch_count){
                //判断尾款：判断批次若全部完成，修改采购单状态为已经付款
                Db::name('purchase_order')->where('id',$v)->update(['payment_status'=>3]);
                //判断结算尾款的采购单是否结算完成，如果完成计算采购成本单价
                $map['i.purchase_order_id'] = $v;
                $map['p.status'] = ['in','4,5'];
                $total1 = $this->payorder_item->alias('i')->join('fa_finance_payorder p','i.pay_id=p.id','left')->where($map)->where('i.pay_type',1)->value('pay_grand_total');  //首付金额
                $total2 = $this->payorder_item->alias('i')->join('fa_finance_payorder p','i.pay_id=p.id','left')->where($map)->where('i.pay_type',3)->sum('wait_statement_total');
                $total = $total1 + $total2;
                //入库总数量
                $count = $this->instockItem->alias('i')->join('fa_in_stock s','i.in_stock_id=s.id')->join('fa_check_order c','s.check_id=c.id')->where('c.purchase_id',$v)->sum('i.in_stock_num');
                $data['actual_purchase_price'] = $count ? round($total/$count,2) : 0;
                $this->purchase_item->where('purchase_id',$v)->update($data);
                /**************************************计算采购成本end**********************************/
                /**************************************计算成本冲减start****************************************/
                $result = array();
                $purchase_order = $this->purchase_item->where('purchase_id',$v)->find();
                //实际采购成本和预估成本不一致，冲减差值
                if($purchase_order['purchase_price'] != $purchase_order['actual_purchase_price']){
                    //计算订单出库数量
                    $out_count1 = $this->item->where('purchase_id',$v)->where('item_order_number','<>','')->where('sku',$purchase_order['sku'])->where('library_status',2)->count();
                    //计算出库数量
                    $out_count2 = $this->outstockItem->alias('i')->join('fa_out_stock s','s.id=i.out_stock_id','left')->where('s.purchase_id',$v)->where('status',2)->where('i.sku',$purchase_order['sku'])->sum('out_stock_num');
                    $out_count = $out_count1+$out_count2;
                    $result['purchase_id'] = $v;
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
                        $order = $this->item->where('purchase_id',$v)->where('item_order_number','<>','')->where('sku',$purchase_order['sku'])->where('library_status',2)->select();
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
                            $cost_order_info = $this->financecost->where(['order_number' => $rr1, 'type' => 2])->find();
                            //如果有出库数据，需要添加冲减暂估结算金额和增加成本核算数据
                            $arr1['type'] = 2;   //类型：成本
                            $arr1['bill_type'] = 10;    //单据类型：暂估结算金额
                            $arr1['frame_cost'] = $cost_order_info['frame_cost'];    //镜架成本：剩余预估单价*剩余数量
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
                        $outorder = $this->outstockItem->alias('i')->join('fa_out_stock s','s.id=i.out_stock_id','left')->where('s.purchase_id',$v)->where('status',2)->where('i.sku',$purchase_order['sku'])->group('s.out_stock_number')->field('s.id,s.out_stock_number,sum(i.out_stock_num) count')->select();
                        foreach ($outorder as $rr2=>$ss2){
                            //获取成本核算中的订单数据
                            $cost_order_info1 = $this->financecost->where(['out_stock_id' => $ss2['id'], 'type' => 2])->find();
                            //如果有出库数据，需要添加冲减暂估结算金额和增加成本核算数据
                            $arr3['type'] = 2;   //类型：成本
                            $arr3['bill_type'] = 11;    //单据类型：暂估结算金额
                            $arr3['frame_cost'] = $cost_order_info1['frame_cost'];    //镜架成本：剩余预估单价*剩余数量
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
        $this->success();
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
            if($status == 3){
                //审核通过
                $params['check_user'] = session('admin.nickname');
            }
            $result = $row->allowField(true)->save($params);
            if($status == 6 || $status == 7) {
                //在待付款单中显示
                $purchase_id = $this->payorder_item->where('pay_id', $ids)->column('purchase_id');
                $purchase_id = implode(',', $purchase_id);
                $this->financepurchase->where('id', 'in', $purchase_id)->update(['is_show' => 1]);
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
        $id = input('ids');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $pay_id = $params['id'];
            $arr['invoice'] = $params['unqualified_images'];
            $arr['status'] = 5;
            $result = $this->payorder->where('id',$pay_id)->update($arr);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('No rows were updated'));
            }
        }
        $this->view->assign('id',$id);
        return $this->view->fetch();
    }
    //获取付款申请单信息
    public function getPayinfo($ids = 0){
        //供应商id
        $supplier_id = $this->financepurchase->where('id','in',$ids)->value('supplier_id');
        //供应商信息
        $supplier = $this->supplier->where('id',$supplier_id)->field('id,supplier_name,currency,period,opening_bank,bank_account,recipient_name')->find();
        $settle_where['s.pay_type'] = 3;
        $settle_where['s.finance_purcahse_id'] = ['in',$ids];
        $settle_where['s.supplier_id'] = $supplier_id;
        //结算信息
        $settle = $this->statement->alias('s')->join('fa_finance_statement_item i','i.statement_id=s.id','left')->field('s.finance_purcahse_id,i.id,i.purchase_number,i.purchase_batch_id,i.now_before_total,i.instock_total,i.deduction_total,i.deduction_total,i.deduction_reason,i.wait_statement_total,i.purchase_id')->where($settle_where)->select();
        $total1 = 0;  //结算待结算金额合计
        $count1 = 0;
        foreach ($settle as $val){
            $total1 += $val['wait_statement_total'];
            $count1++;
        }
        //预付信息
        $prepay = $this->financepurchase->alias('p')->join('fa_purchase_order o','o.id=p.purchase_id','left')->field('p.id,o.purchase_number,o.purchase_total,p.pay_rate,p.pay_grand_total,p.pay_type,p.purchase_id')->where('p.pay_type','in','1,2')->where('p.id','in',$ids)->where('p.supplier_id',$supplier_id)->select();
        $total2 = 0;  //预付预付款金额合计
        $count2 = 0;
        foreach ($prepay as $k=>$v){
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
