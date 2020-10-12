<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class OrderDataView extends Backend
{
    public function _initialize()
    {
        return parent::_initialize();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\zeelool;
    }

    /**
     * 订单数据概况
     *
     * @return \think\Response
     */
    public function index()
    {
        //订单数
        $deal_num = $this->zeeloolOperate->dealnum_statistical(1);
        //未达标天数
        $no_up_to_day = $this->zendeskTasks->not_up_to_standard_day(1);
        //人效
        $positive_effect_num = $this->zendeskTasks->positive_effect_num(1);
        //获取表格内容
        $customer_data = $this->get_worknum_table(1);
        $this->view->assign(compact('deal_num', 'no_up_to_day', 'positive_effect_num', 'customer_data'));
        return $this->view->fetch();
    }


}
