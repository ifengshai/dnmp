<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\NewProductProcessLog;
use app\common\controller\Backend;
use think\Model;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class NewProductProcesses extends Backend
{
    /**
     * @inheritdoc
     */
    protected $noNeedRight = ['funnel'];

    /**
     * NewProductProcesses模型对象
     * @var \app\admin\model\NewProductProcess
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\NewProductProcess;

    }

    public function statistics()
    {
        return $this->view->fetch();
    }

    public function funnel()
    {
        list($start_time, $end_time) = explode(' - ', $this->request->post('time_str') ?: date('Y-m-d 00:00:00', time() - 30 * 86400) . ' - ' . date('Y-m-d 23:59:59'));
        return json(['code' => 1, 'data' => [
            'columnData' => $this->model->getCountByCreateTime($start_time, $end_time),
        ]]);
    }

    /**
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @author huangbinbin
     * @date   2021/7/28 16:29
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $platformType = ['0','Z','V','M','Vm','W','0','0','A','Es','De','Jp','Chic','Z_cn','Ali','Z_fr'];
            $itemPlatform = new ItemPlatformSku();
            foreach($list as $key => $val) {
                $goodsSupply = '';
                if(in_array($val['goods_supply'],[1,2])){
                    $goodsSupply = '大货';
                }elseif(in_array($val['goods_supply'],[3,4])) {
                    $goodsSupply = '现货';
                }
                $list[$key]['goods_supply'] = $goodsSupply;
                $list[$key]['platform'] =$itemPlatform->where('sku',$val['sku'])->order('platform_type asc')->column('platform_type');
                foreach ($list[$key]['platform'] as $k1=>$v1){
                    $list[$key]['platform'][$k1] = $platformType[$v1];
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //选项卡
        return $this->view->fetch();
    }

    /**
     * @param null $ids
     *
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author huangbinbin
     * @date   2021/7/28 16:29
     */
    public function operationLog($ids = null){
        $value = NewProductProcessLog::with('admin')->where(['new_product_process_id'=>$ids])->order('id asc')->select();
        $this->assign('list',$value);
        return $this->view->fetch();
    }

}
