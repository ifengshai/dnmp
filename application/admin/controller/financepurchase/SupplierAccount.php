<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\purchase\PurchaseOrder;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class SupplierAccount extends Backend
{
    protected $noNeedRight = [];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\financepurchase\Statement();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
    }
    /**
     * 供应商结算列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/15
     * Time: 13:49:14
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
            $filter = json_decode($this->request->get('filter'), true);
            $map = [];
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->supplier
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
}
