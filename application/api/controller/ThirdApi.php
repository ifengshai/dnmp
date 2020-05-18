<?php

namespace app\api\controller;

use app\common\controller\Api;


/**
 * 会员接口
 */
class ThirdApi extends Api
{
    protected $noNeedLogin = '*';


    public function _initialize()
    {
        parent::_initialize();
    }



}
