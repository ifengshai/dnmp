<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;


class WaitPay extends Backend
{
    /*
     * 待付款列表
     * */
    public function index()
    {

        return $this->view->fetch();
    }
}
