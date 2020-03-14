<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use app\common\controller\Backend;
use app\admin\model\OrderStatistics;
use app\admin\model\platformmanage\MagentoPlatform;
class Conversionrate extends Backend{
    /**
     * 转化率首页
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/13 16:53:17 
     * @return void
     */
    public function index ()
    {
        $platform = (new MagentoPlatform())->getOrderPlatformList();	
        //头部数据
        if($this->request->isAjax()){
            $orderStatistics = new OrderStatistics();
            $list = $orderStatistics->getAllData();
            foreach ($list as $v) {
                $zeeloolShoppingCartUpdateTotal[$v['create_date']]              = $v['zeelool_shoppingcart_update_total'];
                $zeeloolShoppingCartUpdateConversion[$v['create_date']] 	    = $v['zeelool_shoppingcart_update_conversion'];
            }
            $json['columnData'] = [
                'type' => 'line',
                'data' => $zeeloolShoppingCartUpdateTotal ?: [],
                'name' => '购物车数量、购物车转化率线图'
            ];
        }

        $this->view->assign(
            [

                'orderPlatformList'	=> $platform
            ]
        );
        return  $this->view->fetch();
    }
}