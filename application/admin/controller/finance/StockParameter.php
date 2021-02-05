<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class StockParameter extends Backend
{
    public function _initialize()
    {
        $this->stockparameter = new \app\admin\model\financepurchase\StockParameter();
        $this->stockparameteritem = new \app\admin\model\financepurchase\StockParameterItem();
        return parent::_initialize();

    }
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            //时间
            if($filter['day_date']){
                $createat = explode(' ', $filter['day_date']);
                $map['day_date'] = ['between', [$createat[0],$createat[3]]];
            }
            unset($filter['day_date']);
            unset($filter['one_time-operate']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->stockparameter
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->stockparameter
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    public function detail($ids = null)
    {
        if (!$ids) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isAjax()) {
            $ids = $this->request->get('ids');
            $start = $this->stockparameter->where('id',$ids)->value('day_date');
            $list = $this->stockparameteritem->where('stock_id',$ids)->select();
            $list = collection($list)->toArray();
            //查询冲减数据
            $start_time = strtotime($start);
            $end_time = strtotime($start.' 23:59:59');
            $exist_where['create_time'] = ['between', [$start_time, $end_time]];
            $is_exist = Db::name('finance_cost_error')->where($exist_where)->select();
            if($is_exist){
                foreach ($is_exist as $item){
                    $data = array();
                    $data['type'] = 4;
                    $data['instock_num'] = $item['count'];
                    $data['instock_total'] = $item['total'];
                    $list[] = $data;
                }
            }
            foreach ($list as $key=>$value){
                $list[$key]['id'] = $key+1;
            }
            $result = array("total" => count($list), "rows" => $list);
            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }
}
