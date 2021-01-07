<?php

namespace app\admin\controller\itemmanage;

use app\common\controller\Backend;

/**
 * 商品库存变化管理
 *
 * @icon fa fa-circle-o
 */
class GoodsStockLog extends Backend
{

    /**
     * GoodsStockAllocated模型对象
     * @var \app\admin\model\itemmanage\GoodsStockAllocated
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\GoodsStockLog;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     *
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            if ($filter['order_number']) {
                $map['order_number'] = ['like',$filter['order_number'].'%'];
                unset($filter['order_number']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $k=>$v){
                if ($v['stock_change'] == 0 && $v['stock_before'] == 0){
                    $list[$k]['stock_change'] = '-';
                    $list[$k]['stock_before'] = '-';
                }
                if ($v['available_stock_before'] == 0 && $v['available_stock_change'] == 0){
                    $list[$k]['available_stock_before'] = '-';
                    $list[$k]['available_stock_change'] = '-';
                }
                if ($v['fictitious_before'] == 0 && $v['fictitious_change'] == 0){
                    $list[$k]['fictitious_before'] = '-';
                    $list[$k]['fictitious_change'] = '-';
                }
                if ($v['occupy_stock_before'] == 0 && $v['occupy_stock_change'] == 0){
                    $list[$k]['occupy_stock_before'] = '-';
                    $list[$k]['occupy_stock_change'] = '-';
                }
                if ($v['distribution_stock_before'] == 0 && $v['distribution_stock_change'] == 0){
                    $list[$k]['distribution_stock_before'] = '-';
                    $list[$k]['distribution_stock_change'] = '-';
                }
                if ($v['presell_num_before'] == 0 && $v['presell_num_change'] == 0){
                    $list[$k]['presell_num_before'] = '-';
                    $list[$k]['presell_num_change'] = '-';
                }
                if ($v['on_way_stock_before'] == 0 && $v['on_way_stock_change'] == 0){
                    $list[$k]['on_way_stock_before'] = '-';
                    $list[$k]['on_way_stock_change'] = '-';
                }
                if ($v['wait_instock_num_before'] == 0 && $v['wait_instock_num_change'] == 0){
                    $list[$k]['wait_instock_num_before'] = '-';
                    $list[$k]['wait_instock_num_change'] = '-';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
