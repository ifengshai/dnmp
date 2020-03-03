<?php
namespace app\admin\controller\datacenter\operationanalysis\operationkanban;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\admin\model\platformmanage\MagentoPlatform;
class Dashboard extends Backend{
	protected $model = null;
	public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OperationAnalysis;
    }
	/**
	 *定义时间日志
	 */
	public function date(){
		$date=[
			1=>'过去30天',
			2=>'过去14天',
			3=>'过去7天'
		];
		return $date;
	}
	/**
	 * 仪表盘首页
	 */
	public function index(){
		//默认zeelool站数据
		$platform = (new MagentoPlatform())->getOrderPlatformList();
		$zeelool_data = $this->model->getList(1);
		//z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
		//z站的历史数据  昨天、过去7天、过去30天、当月、上月、今年、总计
		$zeelool_data = collection($zeelool_data)->toArray();
		$this->view->assign("orderPlatformList", $platform);
		$this->view->assign("zeelool_data",$zeelool_data);
		$this->view->assign("date",$this->date());
		return $this->view->fetch();
	}
	/***
	 * 异步获取仪表盘首页上部分数据
	 */
	public function async_data($order_platform=null)
	{
		if($this->request->isAjax()){
			if(!$order_platform){
				return   $this->error('参数不存在，请重新尝试');
			}
			if(10 != $order_platform){
				$data = $this->model->getList($order_platform);
			}else{
				$data = $this->model->getAllList();
			}
			if(false == $data){
			  return $this->error('没有该平台数据,请重新选择');	
			}

			  return $this->success('', '', $data, 0);			
		}
	}
	
}
