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
        //获取采购单物流单号
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $map['purchase_type'] = 1;   //线下采购单
        $map['purchase_status'] = 5; //待收货
        $res = $purchase->field('logistics_company_no,logistics_company_name,id')->where($map)->select();
        
    
        
    }

    /**
     * 回调函数
     */
    public function callback()
    {
        
    }




}
