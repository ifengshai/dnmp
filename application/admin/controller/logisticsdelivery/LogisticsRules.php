<?php
use Admin\Util\DeliverOrderCountryName;
use app\common\controller\Backend;
use app\common\model\Auth;
use think\Db;
use think\Model;

/**
 * 发货模块
 *
 */
class LogisticsRules extends Backend
{
	protected $model = null;
	protected $magentoPlatform = null;
	public function _initialize()
	{
	  parent::_initialize();
	  $this->model = new \app\admin\model\logisticsdelivery\DeliveryOrder;
	}

	 public function index(){
	 	echo "嘿嘿";
	 }

}












?>