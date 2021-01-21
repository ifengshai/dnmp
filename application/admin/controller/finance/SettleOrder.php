<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;

class SettleOrder extends Backend
{
    /*
    * 结算单列表
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
            $map['sku'] = ['<>', ''];
            $map['library_status'] = 1;
            if ($filter['sku']) {
                $map['sku'] = $filter['sku'];
            }
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->item
                ->where($where)
                ->where($map)
                ->group('sku')
                ->order($sort, $order)
                ->count();
            $list = $this->item
                ->where($where)
                ->where($map)
                ->group('sku')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('sku')
                ->select();
            $list = collection($list)->toArray();
            $i = 0;
            foreach ($list as $key => $item) {
                $i++;
                $list[$key]['id'] = $i;
                $prices = $this->item->alias('i')->join('fa_purchase_order_item p', 'p.purchase_id=i.purchase_id')->where('i.sku', $item['sku'])->where('i.library_status', 1)->field('i.id,purchase_price,actual_purchase_price')->select();
                $amount = 0;
                foreach ($prices as $price) {
                    $amount += $price['actual_purchase_price'] != 0 ? $price['actual_purchase_price'] : $price['purchase_price'];
                }
                $list[$key]['total'] = $amount;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
    }
    /*
     * 详情
     * */
    public function detail()
    {
        return $this->view->fetch();
    }
}
