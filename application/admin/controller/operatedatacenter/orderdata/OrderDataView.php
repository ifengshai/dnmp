<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class OrderDataView extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\Zeelool;
    }

    /**
     * 订单数据概况
     *
     * @return \think\Response
     */
    public function index()
    {

        //订单数
        $order_num = $this->zeeloolOperate->getOrderNum();
        //客单价
        $order_unit_price = $this->zeeloolOperate->getOrderUnitPrice();
        //销售额
        $sales_total_money = $this->zeeloolOperate->getSalesTotalMoney();
        //邮费
        $shipping_total_money = $this->zeeloolOperate->getShippingTotalMoney();
        dump($order_num);
        dump($order_unit_price);
        dump($sales_total_money);
        dump($shipping_total_money);
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money'));
        return $this->view->fetch();
    }


}
