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
    /*
     * 17track物流查询webhook访问方法
     * */
    public function track_return(){
        $track_info = file_get_contents("php://input");
        file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt',$track_info."\r\n",FILE_APPEND);
    }
}
