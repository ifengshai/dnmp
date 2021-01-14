<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;

class PayOrder extends Backend
{
    public function _initialize()
    {
        $this->financepurchase = new \app\admin\model\financepurchase\FinancePurchase;
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
        //供应商id
        $supplier_id = $this->financepurchase->where('id','in',$ids)->value('supplier_id');
        //供应商信息
        $supplier = $this->financepurchase->where('supplier_id',$supplier_id)->field('supplier_name,currency,period,opening_bank,bank_account,recipient_name')->select();
        //结算信息
        $settle = $this->financepurchase->alias('p')->join('fa_purchase_order o','o.id=p.purchase_id','left')->join('fa_purchase_batch b','b.id=p.purchase_batch_id','left')->join('fa_finance_statement s','s.finance_purcahse_id=p.id')->field('p.purchase_number,b.batch,s.now_before_total')->select();
        return $this->view->fetch();
    }
}
