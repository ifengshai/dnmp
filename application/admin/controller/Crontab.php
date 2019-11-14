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
        
    }




}
