<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class DataMarket extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OperationAnalysis;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     *定义时间日志
     */
    public function date()
    {
        $date = [
            1 => '过去30天',
            2 => '过去14天',
            3 => '过去7天',
            4 => '昨天',
            5 => '今天'
        ];
        return $date;
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $platform = $this->magentoplatform->getNewAuthSite();
        if(empty($platform)){
            $this->error('您没有权限访问','general/profile?ref=addtabs');
        }
        $arr = [];
        foreach($platform as $pkey => $pv){
            $arr[] = $pkey;
        }
        $zeelool_data = $this->model->getList(key($platform));
        //z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
        //z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
        $zeelool_data = collection($zeelool_data)->toArray();
        //中间部分数据
        $orderStatistics = new OrderStatistics();
        $list = $orderStatistics->getAllData();
        $zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = $meeloogSalesNumList = $zeelool_esSalesNumList = $zeelool_deSalesNumList = [];
        foreach ($list as $v) {
            //如果有zeelool权限
            if(in_array(1,$arr)){
                $zeeloolSalesNumList[$v['create_date']]  			 = $v['zeelool_sales_num'];
                $zeeloolSalesMoneyList[$v['create_date']] 			 = $v['zeelool_sales_money'];
                $zeeloolUnitPriceList[$v['create_date']]			 = $v['zeelool_unit_price'];
                $zeeloolShoppingcartTotal[$v['create_date']]		 = $v['zeelool_shoppingcart_total'];
                $zeeloolShoppingcartConversion[$v['create_date']]	 = $v['zeelool_shoppingcart_conversion'];
                $zeeloolRegisterCustomer[$v['create_date']]		     = $v['zeelool_register_customer'];
            }
            //如果有voogueme权限
            if(in_array(2,$arr)){
                $vooguemeSalesNumList[$v['create_date']] 			 = $v['voogueme_sales_num'];
                $vooguemeSalesMoneyList[$v['create_date']]			 = $v['voogueme_sales_money'];
                $vooguemeUnitPriceList[$v['create_date']]			 = $v['voogueme_unit_price'];
                $vooguemeShoppingcartTotal[$v['create_date']]		 = $v['voogueme_shoppingcart_total'];
                $vooguemeShoppingcartConversion[$v['create_date']]   = $v['voogueme_shoppingcart_conversion'];
                $vooguemeRegisterCustomer[$v['create_date']]		 = $v['voogueme_register_customer'];
            }
            //如果有nihao权限
            if(in_array(3,$arr)){
                $nihaoSalesNumList[$v['create_date']]    			 = $v['nihao_sales_num'];
                $nihaoSalesMoneyList[$v['create_date']]				 = $v['nihao_sales_money'];
                $nihaoUnitPriceList[$v['create_date']]				 = $v['nihao_unit_price'];
                $nihaoShoppingcartTotal[$v['create_date']]			 = $v['nihao_shoppingcart_total'];
                $nihaoShoppingcartConversion[$v['create_date']]	     = $v['nihao_shoppingcart_conversion'];
                $nihaoRegisterCustomer[$v['create_date']]			 = $v['nihao_register_customer'];
            }
            //如果有meeloog权限
            if(in_array(4,$arr)){
                $meeloogSalesNumList[$v['create_date']]    			 = $v['meeloog_sales_num'];
                $meeloogSalesMoneyList[$v['create_date']]			 = $v['meeloog_sales_money'];
                $meeloogUnitPriceList[$v['create_date']]			 = $v['meeloog_unit_price'];
                $meeloogShoppingcartTotal[$v['create_date']]		 = $v['meeloog_shoppingcart_total'];
                $meeloogShoppingcartConversion[$v['create_date']]	 = $v['meeloog_shoppingcart_conversion'];
                $meeloogRegisterCustomer[$v['create_date']]			 = $v['meeloog_register_customer'];
            }
            //如果有zeelool_es权限
            if(in_array(9,$arr)){
                $zeelool_esSalesNumList[$v['create_date']]    	     = $v['zeelool_es_sales_num'];
                $zeelool_esSalesMoneyList[$v['create_date']]	     = $v['zeelool_es_sales_money'];
                $zeelool_esUnitPriceList[$v['create_date']]			 = $v['zeelool_es_unit_price'];
                $zeelool_esShoppingcartTotal[$v['create_date']]		 = $v['zeelool_es_shoppingcart_total'];
                $zeelool_esShoppingcartConversion[$v['create_date']] = $v['zeelool_es_shoppingcart_conversion'];
                $zeelool_esRegisterCustomer[$v['create_date']]	     = $v['zeelool_es_register_customer'];
            }
            //如果有zeelool_de权限
            if(in_array(10,$arr)){
                $zeelool_deSalesNumList[$v['create_date']]    	     = $v['zeelool_de_sales_num'];
                $zeelool_deSalesMoneyList[$v['create_date']]	     = $v['zeelool_de_sales_money'];
                $zeelool_deUnitPriceList[$v['create_date']]			 = $v['zeelool_de_unit_price'];
                $zeelool_deShoppingcartTotal[$v['create_date']]		 = $v['zeelool_de_shoppingcart_total'];
                $zeelool_deShoppingcartConversion[$v['create_date']] = $v['zeelool_de_shoppingcart_conversion'];
                $zeelool_deRegisterCustomer[$v['create_date']]	     = $v['zeelool_de_register_customer'];
            }
        }
        //下边部分数据 默认30天数据
        $bottom_data = $this->get_platform_data(1);
        $this->view->assign([
            'orderPlatformList'					=> $platform,
            'zeelool_data'						=> $zeelool_data,
            'date'								=> $this->date(),
            'zeeloolSalesNumList'       		=> $zeeloolSalesNumList ?:[], //折线图数据
            'vooguemeSalesNumList'      		=> $vooguemeSalesNumList ?:[],
            'nihaoSalesNumList'         		=> $nihaoSalesNumList ?:[],
            'meeloogSalesNumList'         		=> $meeloogSalesNumList ?:[],
            'zeelool_esSalesNumList'            => $zeelool_esSalesNumList ?:[],
            'zeelool_deSalesNumList'            => $zeelool_deSalesNumList ?:[],
            'zeeloolSalesMoneyList'				=> $zeeloolSalesMoneyList ?:[],
            'vooguemeSalesMoneyList'			=> $vooguemeSalesMoneyList ?:[],
            'nihaoSalesMoneyList'				=> $nihaoSalesMoneyList ?:[],
            'meeloogSalesMoneyList'				=> $meeloogSalesMoneyList ?:[],
            'zeelool_esSalesMoneyList'          => $zeelool_esSalesMoneyList ?:[],
            'zeelool_deSalesMoneyList'          => $zeelool_deSalesMoneyList ?:[],
            'zeeloolUnitPriceList'				=> $zeeloolUnitPriceList ?:[],
            'vooguemeUnitPriceList'				=> $vooguemeUnitPriceList ?:[],
            'nihaoUnitPriceList'				=> $nihaoUnitPriceList ?:[],
            'meeloogUnitPriceList'				=> $meeloogUnitPriceList ?:[],
            'zeelool_esUnitPriceList'		    => $zeelool_esUnitPriceList ?:[],
            'zeelool_deUnitPriceList'		    => $zeelool_deUnitPriceList ?:[],
            'zeeloolShoppingcartTotal'			=> $zeeloolShoppingcartTotal ?:[],
            'vooguemeShoppingcartTotal' 		=> $vooguemeShoppingcartTotal ?:[],
            'nihaoShoppingcartTotal'			=> $nihaoShoppingcartTotal ?:[],
            'meeloogShoppingcartTotal'			=> $meeloogShoppingcartTotal ?:[],
            'zeelool_esShoppingcartTotal'	    => $zeelool_esShoppingcartTotal ?:[],
            'zeelool_deShoppingcartTotal'		=> $zeelool_deShoppingcartTotal ?:[],
            'zeeloolShoppingcartConversion'	 	=> $zeeloolShoppingcartConversion ?:[],
            'vooguemeShoppingcartConversion'	=> $vooguemeShoppingcartConversion ?:[],
            'nihaoShoppingcartConversion'	 	=> $nihaoShoppingcartConversion ?:[],
            'meeloogShoppingcartConversion'	 	=> $meeloogShoppingcartConversion ?:[],
            'zeelool_esShoppingcartConversion'	=> $zeelool_esShoppingcartConversion ?:[],
            'zeelool_deShoppingcartConversion'	=> $zeelool_deShoppingcartConversion ?:[],
            'zeeloolRegisterCustomer'			=> $zeeloolRegisterCustomer ?:[],
            'vooguemeRegisterCustomer'			=> $vooguemeRegisterCustomer ?:[],
            'nihaoRegisterCustomer'				=> $nihaoRegisterCustomer ?:[],
            'meeloogRegisterCustomer'			=> $meeloogRegisterCustomer ?:[],
            'zeelool_esRegisterCustomer'		=> $zeelool_esRegisterCustomer ?:[],
            'zeelool_deRegisterCustomer'		=> $zeelool_deRegisterCustomer ?:[],
            'bottom_data'						=> $bottom_data,
            'result'                            => $arr,
            'arr'                               => $arr
        ]);
        // $this->view->assign("orderPlatformList", $platform);
        // $this->view->assign("zeelool_data",$zeelool_data);
        // $this->view->assign("date",$this->date());
        return $this->view->fetch();
    }
    /***
     * 异步获取仪表盘首页上部分数据
     */
    public function async_data($order_platform = null)
    {
        if ($this->request->isAjax()) {
            if (!$order_platform) {
                return   $this->error('参数不存在，请重新尝试');
            }
            if (100 != $order_platform) {
                $data = $this->model->getList($order_platform);
            } else {
                $data = $this->model->getAllList();
            }
            if (false == $data) {
                return $this->error('没有该平台数据,请重新选择');
            }

            return $this->success('', '', $data, 0);
        }
    }
    /**
     * 异步获取仪表盘首页下部分数据
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/12 15:37:42
     * @param [type] $id
     * @return void
     */
    public function async_bottom_data($create_time=null)
    {
        if ($this->request->isAjax()) {
            if ($create_time) {
                $time = explode(' ', $create_time);
                $map['created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $data = $this->get_platform_data($map);
            if (false == $data) {
                return $this->error('没有对应的时间数据，请重新尝试');
            }
            return $this->success('', '', $data, 0);
        }
    }
    public function get_platform_data($map)
    {
        $arr = Cache::get('Dashboard_get_platform_data_'.md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        $zeelool_model 	= Db::connect('database.db_zeelool');
        $voogueme_model = Db::connect('database.db_voogueme');
        $nihao_model	= Db::connect('database.db_nihao');
        $meeloog_model	= Db::connect('database.db_meeloog');
        $zeelool_es_model = Db::connect('database.db_zeelool_es');
        $zeelool_de_model = Db::connect('database.db_zeelool_de');
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $meeloog_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_es_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_de_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $status['status']  = ['in', ['processing', 'complete', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $status['order_type'] = ['not in',[4,5]];
        $pc['store_id']    = 1;
        $wap['store_id']   = ['in',[2,4]];
        $app['store_id']   = 5;
        $android['store_id']   = 6;
        //zeelool中pc销售额
        $zeelool_pc_sales_money  	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中wap销售额
        $zeelool_wap_sales_money 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中IOS销售额
        $zeelool_app_sales_money 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($map)->sum('base_grand_total');
        //zeelool中Android销售额
        $zeelool_android_sales_money 	= $zeelool_model->table('sales_flat_order')->where($android)->where($status)->where($map)->sum('base_grand_total');

        //zeelool中pc支付成功数
        $zeelool_pc_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool中wap支付成功数
        $zeelool_wap_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //zeelool中IOS支付成功数
        $zeelool_app_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($map)->count('*');
        //zeelool中Android支付成功数
        $zeelool_android_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($android)->where($status)->where($map)->count('*');

        if($zeelool_pc_sales_num>0){
            //zeelool pc端客单价
            $zeelool_pc_unit_price   	= round(($zeelool_pc_sales_money/$zeelool_pc_sales_num), 2);
        }else{
            $zeelool_pc_unit_price  	= 0;
        }
        //zeelool wap客单价
        if($zeelool_wap_sales_num>0){
            $zeelool_wap_unit_price  	= round(($zeelool_wap_sales_money/$zeelool_wap_sales_num), 2);
        }else{
            $zeelool_wap_unit_price     = 0;
        }
        //zeelool IOS端客单价
        if($zeelool_app_sales_num>0){
            $zeelool_app_unit_price 	= round(($zeelool_app_sales_money/$zeelool_app_sales_num), 2);
        }else{
            $zeelool_app_unit_price		= 0;
        }
        //zeelool Android端客单价
        if($zeelool_android_sales_num>0){
            $zeelool_android_unit_price 	= round(($zeelool_android_sales_money/$zeelool_android_sales_num), 2);
        }else{
            $zeelool_android_unit_price		= 0;
        }
        //voogueme中pc销售额
        $voogueme_pc_sales_money 	= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //voogueme中wap销售额
        $voogueme_wap_sales_money	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //voogueme中pc支付成功数
        $voogueme_pc_sales_num		= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //voogueme中wap支付成功数
        $voogueme_wap_sales_num	 	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //voogueme pc端客单价
        if($voogueme_pc_sales_num>0){
            $voogueme_pc_unit_price   	= round(($voogueme_pc_sales_money/$voogueme_pc_sales_num), 2);
        }else{
            $voogueme_pc_unit_price   	= 0;
        }
        //voogueme wap客单价
        if($voogueme_wap_sales_num>0){
            $voogueme_wap_unit_price  	= round(($voogueme_wap_sales_money/$voogueme_wap_sales_num), 2);
        }else{
            $voogueme_wap_unit_price  	= 0;
        }

        //nihao中pc销售额
        $nihao_pc_sales_money 		= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //nihao中wap销售额
        $nihao_wap_sales_money		= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //nihao中pc支付成功数
        $nihao_pc_sales_num			= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //nihao中wap支付成功数
        $nihao_wap_sales_num	 	= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //nihao pc端客单价
        if($nihao_pc_sales_num>0){
            $nihao_pc_unit_price   	= round(($nihao_pc_sales_money/$nihao_pc_sales_num), 2);
        }else{
            $nihao_pc_unit_price    = 0;
        }
        //nihao wap客单价
        if($nihao_wap_sales_num>0){
            $nihao_wap_unit_price   = round(($nihao_wap_sales_money/$nihao_wap_sales_num), 2);
        }else{
            $nihao_wap_unit_price   = 0;
        }


        //meeloog中pc销售额
        $meeloog_pc_sales_money 	= $meeloog_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //meeloog中wap销售额
        $meeloog_wap_sales_money	= $meeloog_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //meeloog中pc支付成功数
        $meeloog_pc_sales_num		= $meeloog_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //meeloog中wap支付成功数
        $meeloog_wap_sales_num	 	= $meeloog_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //meeloog pc端客单价
        if($meeloog_pc_sales_num>0){
            $meeloog_pc_unit_price  = round(($meeloog_pc_sales_money/$meeloog_pc_sales_num), 2);
        }else{
            $meeloog_pc_unit_price  = 0;
        }
        //meeloog wap客单价
        if($meeloog_wap_sales_num>0){
            $meeloog_wap_unit_price = round(($meeloog_wap_sales_money/$meeloog_wap_sales_num), 2);
        }else{
            $meeloog_wap_unit_price = 0;
        }

        //zeelool_es中pc销售额
        $zeelool_es_pc_sales_money 	= $zeelool_es_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_es中wap销售额
        $zeelool_es_wap_sales_money	= $zeelool_es_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_es中pc支付成功数
        $zeelool_es_pc_sales_num    = $zeelool_es_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool_es中wap支付成功数
        $zeelool_es_wap_sales_num	= $zeelool_es_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //meeloog pc端客单价
        if($zeelool_es_pc_sales_num>0){
            $zeelool_es_pc_unit_price  = round(($zeelool_es_pc_sales_money/$zeelool_es_pc_sales_num), 2);
        }else{
            $zeelool_es_pc_unit_price  = 0;
        }
        //zeelool_es wap客单价
        if($zeelool_es_wap_sales_num>0){
            $zeelool_es_wap_unit_price = round(($zeelool_es_wap_sales_money/$zeelool_es_wap_sales_num), 2);
        }else{
            $zeelool_es_wap_unit_price = 0;
        }

        //zeelool_de中pc销售额
        $zeelool_de_pc_sales_money 	= $zeelool_de_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_de中wap销售额
        $zeelool_de_wap_sales_money	= $zeelool_de_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->sum('base_grand_total');
        //zeelool_de中pc支付成功数
        $zeelool_de_pc_sales_num    = $zeelool_de_model->table('sales_flat_order')->where($pc)->where($status)->where($map)->count('*');
        //zeelool_de中wap支付成功数
        $zeelool_de_wap_sales_num	= $zeelool_de_model->table('sales_flat_order')->where($wap)->where($status)->where($map)->count('*');
        //meeloog pc端客单价
        if($zeelool_de_pc_sales_num>0){
            $zeelool_de_pc_unit_price  = round(($zeelool_de_pc_sales_money/$zeelool_de_pc_sales_num), 2);
        }else{
            $zeelool_de_pc_unit_price  = 0;
        }
        //zeelool_de wap客单价
        if($zeelool_de_wap_sales_num>0){
            $zeelool_de_wap_unit_price = round(($zeelool_de_wap_sales_money/$zeelool_de_wap_sales_num), 2);
        }else{
            $zeelool_de_wap_unit_price = 0;
        }
        $arr = [
            'zeelool_pc_sales_money' 	=> $zeelool_pc_sales_money ?:0,
            'zeelool_wap_sales_money' 	=> $zeelool_wap_sales_money ?:0,
            'zeelool_app_sales_money' 	=> $zeelool_app_sales_money ?:0,
            'zeelool_android_sales_money' 	=> $zeelool_android_sales_money ?:0,
            'zeelool_pc_sales_num' 		=> $zeelool_pc_sales_num ?:0,
            'zeelool_wap_sales_num'		=> $zeelool_wap_sales_num ?:0,
            'zeelool_app_sales_num' 	=> $zeelool_app_sales_num ?:0,
            'zeelool_android_sales_num' 	=> $zeelool_android_sales_num ?:0,
            'zeelool_pc_unit_price' 	=> $zeelool_pc_unit_price ?:0,
            'zeelool_wap_unit_price' 	=> $zeelool_wap_unit_price ?:0,
            'zeelool_app_unit_price' 	=> $zeelool_app_unit_price ?:0,
            'zeelool_android_unit_price' 	=> $zeelool_android_unit_price ?:0,
            'voogueme_pc_sales_money' 	=> $voogueme_pc_sales_money ?:0,
            'voogueme_wap_sales_money' 	=> $voogueme_wap_sales_money ?:0,
            'voogueme_pc_sales_num' 	=> $voogueme_pc_sales_num ?:0,
            'voogueme_wap_sales_num' 	=> $voogueme_wap_sales_num ?:0,
            'voogueme_pc_unit_price' 	=> $voogueme_pc_unit_price ?:0,
            'voogueme_wap_unit_price' 	=> $voogueme_wap_unit_price ?:0,
            'nihao_pc_sales_money' 		=> $nihao_pc_sales_money ?:0,
            'nihao_wap_sales_money' 	=> $nihao_wap_sales_money ?:0,
            'nihao_pc_sales_num' 		=> $nihao_pc_sales_num ?:0,
            'nihao_wap_sales_num' 		=> $nihao_wap_sales_num ?:0,
            'nihao_pc_unit_price' 		=> $nihao_pc_unit_price ?:0,
            'nihao_wap_unit_price' 		=> $nihao_wap_unit_price ?:0,
            'meeloog_pc_sales_money' 	=> $meeloog_pc_sales_money ?:0,
            'meeloog_wap_sales_money' 	=> $meeloog_wap_sales_money ?:0,
            'meeloog_pc_sales_num' 		=> $meeloog_pc_sales_num ?:0,
            'meeloog_wap_sales_num' 	=> $meeloog_wap_sales_num ?:0,
            'meeloog_pc_unit_price' 	=> $meeloog_pc_unit_price ?:0,
            'meeloog_wap_unit_price' 	=> $meeloog_wap_unit_price ?:0,
            'zeelool_es_pc_sales_money' => $zeelool_es_pc_sales_money ?:0,
            'zeelool_es_wap_sales_money' =>$zeelool_es_wap_sales_money ?:0,
            'zeelool_es_pc_sales_num' 	=> $zeelool_es_pc_sales_num ?:0,
            'zeelool_es_wap_sales_num' 	=> $zeelool_es_wap_sales_num ?:0,
            'zeelool_es_pc_unit_price' 	=> $zeelool_es_pc_unit_price ?:0,
            'zeelool_es_wap_unit_price' => $zeelool_es_wap_unit_price ?:0,
            'zeelool_de_pc_sales_money' => $zeelool_de_pc_sales_money ?:0,
            'zeelool_de_wap_sales_money' =>$zeelool_de_wap_sales_money ?:0,
            'zeelool_de_pc_sales_num' 	=> $zeelool_de_pc_sales_num ?:0,
            'zeelool_de_wap_sales_num' 	=> $zeelool_de_wap_sales_num ?:0,
            'zeelool_de_pc_unit_price' 	=> $zeelool_de_pc_unit_price ?:0,
            'zeelool_de_wap_unit_price' => $zeelool_de_wap_unit_price ?:0,
        ];
        Cache::set('Dashboard_get_platform_data_'.md5(serialize($map)), $arr, 7200);
        return $arr;
    }
}
