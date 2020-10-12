<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\common\controller\Backend;
use think\Request;

class DashBoard extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        //每日的数据
        $this->datacenterday = new \app\admin\model\DatacenterDay();
    }


    public function index()
    {
        $res = $this->datacenterday->where('id',1)->find();
        return $this->view->fetch();
    }

}
