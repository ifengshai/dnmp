<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use think\Db;

class Temp extends Backend
{
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        
    }


}