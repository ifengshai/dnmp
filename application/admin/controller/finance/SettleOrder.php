<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;

class SettleOrder extends Backend
{
    /*
    * 结算单列表
    * */
    public function index()
    {
        return $this->view->fetch();
    }
    /*
     * 详情
     * */
    public function detail()
    {
        return $this->view->fetch();
    }
}
