<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;

class WaitPay extends Backend
{
    protected $noNeedRight = ['supplier', 'getCategoryName'];
    public function _initialize()
    {
        $this->financepurchase = new \app\admin\model\financepurchase\FinancePurchase;
        return parent::_initialize();
    }
    /*
     * 待付款列表
     * */
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
            //创建时间
            if ($filter['create_time']) {
                $createat = explode(' ', $filter['create_time']);
                $start = strtotime($createat[0] . ' ' . $createat[1]);
                $end = strtotime($createat[3] . ' ' . $createat[4]);
                $map['p.create_time'] = ['between', [$start, $end]];
            }
            //商品分类筛选
            $purchase = new \app\admin\model\purchase\PurchaseOrder();
            if ($filter['category_id']) {
                //查询此商品分类下的SKU 采购单
                $item = new \app\admin\model\itemmanage\Item();
                $category = new \app\admin\model\itemmanage\ItemCategory();
                $ids = $category->getList($filter['category_id']);
                $skus = $item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['in', $ids]])->column('sku');
                $purchase_id = $purchase->alias('a')->where(['purchase_status' => 2, 'sku' => ['in', $skus]])->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->column('purchase_id');
                $map['purchase_id'] = ['in', $purchase_id];
                unset($filter['category_id']);
            }

            $map['p.status'] = 2;
            $map['p.is_show'] = 1;
            unset($filter['create_time']);
            unset($filter['one_time-operate']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'p.id';
            $total = $this->financepurchase
                ->alias('p')
                ->join('fa_supplier s', 's.id=p.supplier_id', 'left')
                ->field('p.id,s.supplier_name,p.order_number,p.create_time,p.create_person')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->financepurchase
                ->alias('p')
                ->join('fa_supplier s', 's.id=p.supplier_id', 'left')
                ->field('p.id,s.supplier_name,p.order_number,FROM_UNIXTIME(p.create_time) create_time,p.create_person,p.purchase_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['1688_number'] = $purchase->where(['id' => $v['purchase_id']])->value('1688_number');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 获取商品分类
     *
     * @Description
     * @author wpl
     * @since 2021/03/12 11:04:19 
     * @return void
     */
    public function getCategoryName()
    {
        if ($this->request->isAjax()) {
            $category = new \app\admin\model\itemmanage\ItemCategory();
            $list = $category->getPidCategoryName();
            return json($list);
        }
    }

    /*
     * 判断创建付款单时是否为同一个供应商
     * */
    public function supplier()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $ids = $params['ids'];
            //判断供应商是否一致
            $supplier_ids = $this->financepurchase->where('id', 'in', $ids)->column('supplier_id');
            if (count(array_unique($supplier_ids)) != 1) {
                $status = 1;
            } else {
                $status = 0;
            }
            return json(['code' => 1, 'data' => $status]);
        }
    }
}
