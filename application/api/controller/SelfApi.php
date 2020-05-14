<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 会员接口
 */
class SelfApi extends Api
{
    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function create_order()
    {
        dump(1);exit;
    }

    public function order_delivery()
    {
        dump(1);exit;
    }
}
