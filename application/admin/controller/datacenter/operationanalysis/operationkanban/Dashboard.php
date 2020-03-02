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
	public function index(){
		//默认zeelool站数据
		$zeelool_data = $this->model->getList(1);
		$zeelool_data = collection($zeelool_data)->toArray();
		dump($zeelool_data);
		exit;
		$this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
		$this->view->assign("date",$this->date());
		return $this->view->fetch();
	}
}
