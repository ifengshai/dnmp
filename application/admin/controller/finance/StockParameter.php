<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class StockParameter extends Backend
{
    public function index()
    {
        return $this->view->fetch();
    }
    public function detail()
    {
        return $this->view->fetch();
    }
}
