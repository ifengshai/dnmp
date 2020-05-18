<?php

namespace app\api\controller;

use app\common\controller\Api;


/**
 * 会员接口
 */
class ThirdApi extends Api
{
    protected $noNeedLogin = '*';
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';

    public function _initialize()
    {
        parent::_initialize();
    }
    /*
     * 17track物流查询webhook访问方法
     * */
    public function track_return(){
        $track_info = file_get_contents("php://input");
        $track_arr = json_decode($track_info,true);
        $verify_sign = $track_arr['event'].'/'.json_encode($track_arr['data']).'/'.$this->apiKey;
        $verify_sign = hash("sha256",$verify_sign);
        if($verify_sign == $track_arr['sign']){
            file_put_contents('/www/wwwroot/mojing/runtime/log/track.txt',$track_info."\r\n",FILE_APPEND);
        }
    }
}
