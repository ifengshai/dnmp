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
			3 => '过去7天'
		];
		return $date;
	}
	/**
	 * 仪表盘首页
	 */
	public function index()
	{
		//查询三个站数据
		$orderStatistics = new OrderStatistics();
		$list = $orderStatistics->getAllData();
		$zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = [];
		foreach ($list as $k => $v) {
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
		//默认zeelool站数据
		$platform = (new MagentoPlatform())->getOrderPlatformList();
		$zeelool_data = $this->model->getList(1);
		//z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
		//z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
		$zeelool_data = collection($zeelool_data)->toArray();
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
			'nihaoRegisterCustomer'				=> $nihaoRegisterCustomer
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
}
