<?php

namespace app\admin\controller\logistics;

use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\ValidateException;
class LogisticsStatistic extends Backend{
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderNodeDetail;
    }
    public function index()
    {
        return $this->view->fetch();
    }

}