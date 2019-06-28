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
        $str = 'a:2:{s:15:"info_buyRequest";a:10:{s:7:"product";s:3:"255";s:4:"uenc";s:88:"aHR0cHM6Ly93d3cudm9vZ3VlbWUuY29tL2V5ZWdsYXNzZXMvZnAwMDQ1Lmh0bWw_X19fc3RvcmU9ZGVmYXVsdA,,";s:8:"form_key";s:16:"BsM6kOlmC7sGXd4p";s:15:"related_product";s:0:"";s:15:"validate_rating";N;s:3:"qty";i:0;s:7:"options";a:1:{i:151;s:3:"352";}s:7:"tmplens";a:30:{s:15:"super_attribute";N;s:13:"product_color";N;s:10:"frame_type";i:0;s:10:"glass_type";s:33:"eyeglasses, sunglasses, sunshades";s:19:"frame_regural_price";d:12.949999999999999;s:11:"frame_price";d:12.949999999999999;s:8:"lenskind";s:0:"";s:6:"usefor";s:0:"";s:11:"useforprice";s:0:"";s:5:"prism";s:0:"";s:10:"prismprice";s:0:"";s:5:"extra";s:0:"";s:10:"extraprice";s:0:"";s:12:"prescription";s:0:"";s:10:"index_type";s:0:"";s:11:"index_price";s:0:"";s:9:"lens_type";s:0:"";s:15:"lens_type_price";s:0:"";s:9:"lens_tint";s:0:"";s:14:"lens_coating_1";s:0:"";s:20:"lens_coating_1_price";s:0:"";s:14:"lens_coating_2";s:0:"";s:20:"lens_coating_2_price";s:0:"";s:14:"lens_coating_3";s:0:"";s:20:"lens_coating_3_price";s:0:"";s:14:"lens_coating_4";s:0:"";s:20:"lens_coating_4_price";s:0:"";s:3:"rid";s:1:"0";s:4:"lens";s:0:"";s:5:"total";d:12.949999999999999;}s:12:"original_qty";s:1:"1";s:13:"cart_currency";s:3:"USD";}s:7:"options";a:1:{i:0;a:6:{s:5:"label";s:5:"Color";s:5:"value";s:5:"Black";s:9:"option_id";s:3:"151";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:3:"352";s:11:"custom_view";b:0;}}}';
        $arr = unserialize($str);
        dump($arr);
    }



}
