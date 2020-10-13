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
    }


    public function index()
    {
        return $this->view->fetch();
    }

}
