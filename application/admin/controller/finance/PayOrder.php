<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;

class PayOrder extends Backend
{
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
        return $this->view->fetch();
    }
}
