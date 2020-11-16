<?php

namespace app\admin\controller\order;
use app\common\controller\Backend;

class Testinfo extends Backend {

    public function index(){
        echo  phpinfo();die();
    }

}