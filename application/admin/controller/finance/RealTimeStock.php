<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;

class RealTimeStock extends Backend
{
    public function _initialize()
    {
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        return parent::_initialize(); // TODO: Change the autogenerated stub
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
            $map['sku'] = ['<>',''];
            $map['library_status'] = 1;
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
            foreach ($list as $key=>$item){
                $i++;
                $list[$key]['id'] = $i;
                $prices = $this->item->alias('i')->join('fa_purchase_order_item p','p.purchase_id=i.purchase_id')->where('i.sku',$item['sku'])->where('i.library_status',1)->field('i.id,purchase_price,actual_purchase_price')->select();
                $amount = 0;
                foreach ($prices as $price){
                    $amount += $price['actual_purchase_price'] != 0 ? $price['actual_purchase_price'] : $price['purchase_price'];
                }
                $list[$key]['total'] = round($amount,2);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        //库存总金额
        $purchase = $this->item->alias('i')->join('fa_purchase_order_item p','p.purchase_id=i.purchase_id')->where('i.sku','<>','')->where('i.library_status',1)->field('sum(purchase_price) purchase_price,sum(actual_purchase_price) actual_purchase_price')->group('p.purchase_order_number')->select();
        $amount = 0;
        foreach ($purchase as $vv){
            $amount += $vv['actual_purchase_price'] != 0 ? $vv['actual_purchase_price'] : $vv['purchase_price'];
        }
        $amount = round($amount,2);
        $this->view->assign('amount',$amount);
        return $this->view->fetch();
    }
    public function detail()
    {
        $sku = $this->request->get('sku');
        if (!$sku) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isAjax()) {
            $sku = $this->request->get('sku');
            $list = $this->item->alias('i')->join('fa_purchase_order_item p','p.purchase_id=i.purchase_id')->where('i.sku',$sku)->where('i.library_status',1)->field('p.purchase_order_number,i.sku,sum(purchase_price) purchase_price,sum(actual_purchase_price) actual_purchase_price,count(*) num')->group('p.purchase_order_number')->select();
            $list = collection($list)->toArray();
            $i = 0;
            foreach ($list as $k=>$val){
                $i++;
                $list[$k]['id'] = $i;
                $total = $val['actual_purchase_price'] != 0 ? $val['actual_purchase_price'] : $val['purchase_price'];
                $list[$k]['total'] = round($total,2);
            }
            $result = array("total" => count($list), "rows" => $list);
            return json($result);
        }
        $this->assignconfig('sku', $sku);
        $this->assign('sku', $sku);
        return $this->view->fetch();
    }
}
