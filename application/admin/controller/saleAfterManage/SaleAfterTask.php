<?php

namespace app\admin\controller\saleAfterManage;

use app\common\controller\Backend;
use app\admin\model\saleAfterManage\SaleAfterIssue;
use think\Request;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class SaleAfterTask extends Backend
{
    
    /**
     * SaleAfterTask模型对象
     * @var \app\admin\model\saleAfterManage\SaleAfterTask
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleAfterManage\SaleAfterTask;
        $this->view->assign("orderPlatformList", $this->model->getOrderPlatformList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign('prtyIdList',$this->model->getPrtyIdList());
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(1,0));

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /***
     * 异步请求获取订单所在平台和订单号处理
     * @param
     */
    public function ajax( Request $request)
    {
        if($this->request->isAjax()){
            $ordertype = $request->post('ordertype');
            $order_number = $request->post('order_number');
            if($ordertype<1 || $ordertype>5){ //不在平台之内
               return  $this->error('选择平台错误，请重新选择','','error',0);
            }
            if(!$order_number){
               return  $this->error('订单号不存在，请重新选择','','error',0);
            }
            $result = $this->model->getOrderInfo($ordertype,$order_number);
            if(!$result){
                return $this->error('找不到这个订单，请重新尝试','','error',0);
            }
            return $this->success('','',$result,0);
        }else{
            $arr=[
                12=>'a',34=>'b',57=>'c',84=>'d',
            ];
            $json = json_encode($arr);
            return $this->success('ok','',$json);
        }


    }
    public function ceshi()
    {
        $ordertype = 1;
        $order_number = 12321;
        if($ordertype<1 || $ordertype>5){ //不在平台之内
            return  $this->error('选择平台错误，请重新选择','','error',0);
        }
        if(!$order_number){
            return  $this->error('订单号不存在，请重新选择','','error',0);
        }
//        switch ($ordertype){
//            case 1:
//                $result = Db::connect('db_config1')->table('sales_flat_order')->where('increment_id','=',$order_number)->find();
//                break;
//            case 2:
//                $result = Db::connect('db_config2')->table('sales_flat_order')->where('increment_id','=',$order_number)->find();
//                break;
//        }
        dump(Db::connect('database.db_config1'));
    }
    public function ceshi2()
    {
        echo THINK_VERSION;
    }



}
