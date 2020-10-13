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
        //补发单订单数
        $replacement_order_num = $this->zeeloolOperate->getReplacementOrderNum();
        //补发单销售额
        $replacement_order_total = $this->zeeloolOperate->getReplacementOrderTotal();
        //网红单订单数
        $online_celebrity_order_num = $this->zeeloolOperate->getOnlineCelebrityOrderNum();
        //网红单销售额
        $online_celebrity_order_total = $this->zeeloolOperate->getOnlineCelebrityOrderTotal();
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money','replacement_order_num','replacement_order_total','online_celebrity_order_num','online_celebrity_order_total'));
        return $this->view->fetch();
    }


}
