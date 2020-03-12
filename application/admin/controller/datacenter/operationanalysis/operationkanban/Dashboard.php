<?php

namespace app\admin\controller\datacenter\operationanalysis\operationkanban;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\admin\model\platformmanage\MagentoPlatform;

class Dashboard extends Backend
{
	protected $model = null;
	public function _initialize()
	{
		parent::_initialize();
		$this->model = new \app\admin\model\OperationAnalysis;
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
	 * 仪表盘首页
	 */
	public function index()
	{
		//上边部分数据 默认zeelool站数据
		$platform = (new MagentoPlatform())->getOrderPlatformList();
		$zeelool_data = $this->model->getList(1);
		//z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
		//z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
		$zeelool_data = collection($zeelool_data)->toArray();		
		//中间部分数据
		$orderStatistics = new OrderStatistics();
		$list = $orderStatistics->getAllData();
		$zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = [];
		foreach ($list as $v) {
			$zeeloolSalesNumList[$v['create_date']]  			 = $v['zeelool_sales_num'];
			$vooguemeSalesNumList[$v['create_date']] 			 = $v['voogueme_sales_num'];
			$nihaoSalesNumList[$v['create_date']]    			 = $v['nihao_sales_num'];
			$zeeloolSalesMoneyList[$v['create_date']] 			 = $v['zeelool_sales_money'];
			$vooguemeSalesMoneyList[$v['create_date']]			 = $v['voogueme_sales_money'];
			$nihaoSalesMoneyList[$v['create_date']]				 = $v['nihao_sales_money'];
			$zeeloolUnitPriceList[$v['create_date']]			 = $v['zeelool_unit_price'];
			$vooguemeUnitPriceList[$v['create_date']]			 = $v['voogueme_unit_price'];
			$nihaoUnitPriceList[$v['create_date']]				 = $v['nihao_unit_price'];
			$zeeloolShoppingcartTotal[$v['create_date']]		 = $v['zeelool_shoppingcart_total'];
			$vooguemeShoppingcartTotal[$v['create_date']]		 = $v['voogueme_shoppingcart_total'];
			$nihaoShoppingcartTotal[$v['create_date']]			 = $v['nihao_shoppingcart_total'];
			$zeeloolShoppingcartConversion[$v['create_date']]	 = $v['zeelool_shoppingcart_conversion'];
			$vooguemeShoppingcartConversion[$v['create_date']]   = $v['voogueme_shoppingcart_conversion'];
			$nihaoShoppingcartConversion[$v['create_date']]	     = $v['nihao_shoppingcart_conversion'];
			$zeeloolRegisterCustomer[$v['create_date']]		     = $v['zeelool_register_customer'];
			$vooguemeRegisterCustomer[$v['create_date']]		 = $v['voogueme_register_customer'];
			$nihaoRegisterCustomer[$v['create_date']]			 = $v['nihao_register_customer'];
		}
		//下边部分数据 默认30天数据
		$bottom_data = $this->get_platform_data(1);
		$this->view->assign([
			'orderPlatformList'					=> $platform,
			'zeelool_data'						=> $zeelool_data,
			'date'								=> $this->date(),
			'zeeloolSalesNumList'       		=> $zeeloolSalesNumList, //折线图数据
			'vooguemeSalesNumList'      		=> $vooguemeSalesNumList,
			'nihaoSalesNumList'         		=> $nihaoSalesNumList,
			'zeeloolSalesMoneyList'				=> $zeeloolSalesMoneyList,
			'vooguemeSalesMoneyList'			=> $vooguemeSalesMoneyList,
			'nihaoSalesMoneyList'				=> $nihaoSalesMoneyList,
			'zeeloolUnitPriceList'				=> $zeeloolUnitPriceList,
			'vooguemeUnitPriceList'				=> $vooguemeUnitPriceList,
			'nihaoUnitPriceList'				=> $nihaoUnitPriceList,
			'zeeloolShoppingcartTotal'			=> $zeeloolShoppingcartTotal,
			'vooguemeShoppingcartTotal' 		=> $vooguemeShoppingcartTotal,
			'nihaoShoppingcartTotal'			=> $nihaoShoppingcartTotal,
			'zeeloolShoppingcartConversion'	 	=> $zeeloolShoppingcartConversion,
			'vooguemeShoppingcartConversion'	=> $vooguemeShoppingcartConversion,
			'nihaoShoppingcartConversion'	 	=> $nihaoShoppingcartConversion,
			'zeeloolRegisterCustomer'			=> $zeeloolRegisterCustomer,
			'vooguemeRegisterCustomer'			=> $vooguemeRegisterCustomer,
			'nihaoRegisterCustomer'				=> $nihaoRegisterCustomer,
			'bottom_data'						=> $bottom_data
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
			if (10 != $order_platform) {
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
	public function async_bottom_data($id=null)
	{
		if($this->request->isAjax()){
			if(!$id){
				return $this->error('参数不存在，请重新尝试');
			}
			$data = $this->get_platform_data($id);
			if(false == $data){
				return $this->error('没有对应的时间数据，请重新尝试');
			}
				return $this->error('','',$data,0);
		}
	}	
	/**
	 * 获取平台数据来源
	 * @param $id  date 中的ID
	 * @Description created by lsw
	 * @author lsw
	 * @since 2020/03/12 14:04:02 
	 * @return void
	 */
	public function get_platform_data($id)
	{
		switch($id){
			case 1:
				$where = 'DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate()';
			break;
			case 2:
				$where = 'DATE_SUB(CURDATE(),INTERVAL 14 DAY) <= date(created_at) and created_at< curdate()';
			break;
			case 3:
				$where = 'DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate()';
			break;
			case 4:
				$where = 'DATEDIFF(created_at,NOW())=-1';
			break;
			default:
				$where = 'TO_DAYS(created_at) = TO_DAYS(NOW())';
			break;
		}
		$zeelool_model 	= Db::connect('database.db_zeelool');
		$voogueme_model = Db::connect('database.db_voogueme');
		$nihao_model	= Db::connect('database.db_nihao');
		$zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");
		$voogueme_model->table('sales_flat_order')->query("set time_zone='+8:00'");
		$nihao_model->table('sales_flat_order')->query("set time_zone='+8:00'");
		$status['status']  = ['in', ['processing', 'complete', 'creditcard_proccessing']];
		$pc['store_id']    = 1;
		$wap['store_id']   = ['in',[2,4]];
		$app['store_id']   = 5;
		//zeelool中pc销售额
		$zeelool_pc_sales_money  	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->sum('base_grand_total');
		//zeelool中wap销售额
		$zeelool_wap_sales_money 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->sum('base_grand_total');
		//zeelool中app销售额
		$zeelool_app_sales_money 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($where)->sum('base_grand_total');
		//zeelool中pc支付成功数
		$zeelool_pc_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->count('*');
		//zeelool中wap支付成功数
		$zeelool_wap_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->count('*');
		//zeelool中pc支付成功数
		$zeelool_app_sales_num	 	= $zeelool_model->table('sales_flat_order')->where($app)->where($status)->where($where)->count('*');
		//zeelool pc端客单价
		$zeelool_pc_unit_price   	= @round(($zeelool_pc_sales_money/$zeelool_pc_sales_num),2);
		//zeelool wap客单价
		$zeelool_wap_unit_price  	= @round(($zeelool_wap_sales_money/$zeelool_wap_sales_num),2);
		//zeelool app端客单价
		$zeelool_app_unit_price 	= @round(($zeelool_app_sales_money/$zeelool_app_sales_num),2);
		//voogueme中pc销售额
		$voogueme_pc_sales_money 	= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->sum('base_grand_total');
		//voogueme中wap销售额
		$voogueme_wap_sales_money	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->sum('base_grand_total');
		//voogueme中pc支付成功数
		$voogueme_pc_sales_num		= $voogueme_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->count('*');
		//voogueme中wap支付成功数
		$voogueme_wap_sales_num	 	= $voogueme_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->count('*');
		//voogueme pc端客单价
		$voogueme_pc_unit_price   	= @round(($voogueme_pc_sales_money/$voogueme_pc_sales_num),2);
		//voogueme wap客单价
		$voogueme_wap_unit_price  	= @round(($voogueme_wap_sales_money/$voogueme_wap_sales_num),2);
		//nihao中pc销售额
		$nihao_pc_sales_money 		= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->sum('base_grand_total');
		//nihao中wap销售额
		$nihao_wap_sales_money		= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->sum('base_grand_total');
		//nihao中pc支付成功数
		$nihao_pc_sales_num			= $nihao_model->table('sales_flat_order')->where($pc)->where($status)->where($where)->count('*');
		//nihao中wap支付成功数
		$nihao_wap_sales_num	 	= $nihao_model->table('sales_flat_order')->where($wap)->where($status)->where($where)->count('*');
		//nihao pc端客单价
		$nihao_pc_unit_price   		= @round(($nihao_pc_sales_money/$nihao_pc_sales_num),2);
		//nihao wap客单价
		$nihao_wap_unit_price  		= @round(($nihao_wap_sales_money/$nihao_wap_sales_num),2);
		return [
			'zeelool_pc_sales_money' 	=> $zeelool_pc_sales_money,
			'zeelool_wap_sales_money' 	=> $zeelool_wap_sales_money,
			'zeelool_app_sales_money' 	=> $zeelool_app_sales_money,
			'zeelool_pc_sales_num' 		=> $zeelool_pc_sales_num,
			'zeelool_wap_sales_num'		=> $zeelool_wap_sales_num,
			'zeelool_app_sales_num' 	=> $zeelool_app_sales_num,
			'zeelool_pc_unit_price' 	=> $zeelool_pc_unit_price,
			'zeelool_wap_unit_price' 	=> $zeelool_wap_unit_price,
			'zeelool_app_unit_price' 	=> $zeelool_app_unit_price,
			'voogueme_pc_sales_money' 	=> $voogueme_pc_sales_money,
			'voogueme_wap_sales_money' 	=> $voogueme_wap_sales_money,
			'voogueme_pc_sales_num' 	=> $voogueme_pc_sales_num,
			'voogueme_wap_sales_num' 	=> $voogueme_wap_sales_num,
			'voogueme_pc_unit_price' 	=> $voogueme_pc_unit_price,
			'voogueme_wap_unit_price' 	=> $voogueme_wap_unit_price,
			'nihao_pc_sales_money' 		=> $nihao_pc_sales_money,
			'nihao_wap_sales_money' 	=> $nihao_wap_sales_money,
			'nihao_pc_sales_num' 		=> $nihao_pc_sales_num,
			'nihao_wap_sales_num' 		=> $nihao_wap_sales_num,
			'nihao_pc_unit_price' 		=> $nihao_pc_unit_price,
			'nihao_wap_unit_price' 		=> $nihao_wap_unit_price
		];		

	}
}
