<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Cache;
use think\Db;

class PayOrder extends Backend
{
    public function _initialize()
    {
        $this->financepurchase = new \app\admin\model\financepurchase\FinancePurchase;
        $this->supplier = new \app\admin\model\purchase\Supplier;
        return parent::_initialize();

    }
    /*
     * 付款单列表
     * */
    public function index()
    {
        
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
