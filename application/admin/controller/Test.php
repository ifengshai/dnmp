<?php

namespace app\admin\controller;

use app\common\controller\Backend;

class Test extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
    }

   
}
