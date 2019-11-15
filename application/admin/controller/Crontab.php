<?php
namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Validate;

/**
 * 定时任务
 * @internal
 */
class Crontab extends Backend
{
    
    protected $noNeedLogin = ['setPurchaseStatus'];

    /**
     * 获取采购到货状态、到货时间
     */
    public function setPurchaseStatus()
    {
        

    
        
    }

    /**
     * 回调函数
     */
    public function callback()
    {
        $arr = 'a:4:{s:6:"status";s:7:"polling";s:10:"billstatus";s:6:"change";s:7:"message";s:6:"变化";s:10:"lastResult";a:8:{s:7:"message";s:2:"ok";s:2:"nu";s:14:"75311528565205";s:7:"ischeck";s:1:"0";s:3:"com";s:9:"zhongtong";s:6:"status";s:3:"200";s:4:"data";a:4:{i:0;a:6:{s:4:"time";s:19:"2019-11-14 18:18:41";s:7:"context";s:76:"【太原市】 快件离开 【太原中转】 已发往 【郑州中转】";s:5:"ftime";s:19:"2019-11-14 18:18:41";s:8:"areaCode";s:14:"CN140100000000";s:8:"areaName";s:16:"山西,太原市";s:6:"status";s:6:"在途";}i:1;a:6:{s:4:"time";s:19:"2019-11-14 18:11:19";s:7:"context";s:53:"【太原市】 快件已经到达 【太原中转】";s:5:"ftime";s:19:"2019-11-14 18:11:19";s:8:"areaCode";s:14:"CN140100000000";s:8:"areaName";s:16:"山西,太原市";s:6:"status";s:6:"在途";}i:2;a:6:{s:4:"time";s:19:"2019-11-14 09:25:07";s:7:"context";s:70:"【吕梁市】 快件离开 【吕梁】 已发往 【太原中转】";s:5:"ftime";s:19:"2019-11-14 09:25:07";s:8:"areaCode";s:14:"CN141100000000";s:8:"areaName";s:16:"山西,吕梁市";s:6:"status";s:6:"在途";}i:3;a:6:{s:4:"time";s:19:"2019-11-13 13:52:37";s:7:"context";s:97:"【吕梁市】 【吕梁】（0358-3375311） 的 中吉-三晋御品（17635454634） 已揽收";s:5:"ftime";s:19:"2019-11-13 13:52:37";s:8:"areaCode";s:14:"CN141100000000";s:8:"areaName";s:16:"山西,吕梁市";s:6:"status";s:6:"揽收";}}s:5:"state";s:1:"0";s:9:"condition";s:3:"F00";}}';
        dump(unserialize($arr));die;
    }




}
