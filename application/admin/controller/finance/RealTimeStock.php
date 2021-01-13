<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class RealTimeStock extends Backend
{
    public function index()
    {
        return $this->view->fetch();
    }
}
