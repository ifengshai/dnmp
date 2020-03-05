<?php

namespace app\admin\controller;

use app\admin\model\Elaticsearch;
use app\common\controller\Backend;

class Test extends Backend{

    public function _initialize()
    {
        parent::_initialize();

       $this->es = new Elaticsearch();
    }

    public function test()
    {
        $this->es->addOne();
        echo $this->es->getOne();die;
    }

}