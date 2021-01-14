<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;


class WaitPay extends Backend
{
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
            if($filter['order_number']){
                //付款申请单号
                $map['p.order_number'] = $filter['order_number'];
            }
            if($filter['supplier_name']){
                //供应商名称
                $map['s.supplier_name'] = $filter['supplier_name'];
            }
            if($filter['userid']){
                //审核人
                $map['l.userid'] = $filter['userid'];
            }
            if($filter['create_person']){
                //创建人
                $map['p.create_person'] = $filter['create_person'];
            }
            //创建时间
            if($filter['create_time']){
                $createat = explode(' ', $filter['create_time']);
                $start = strtotime($createat[0].' '.$createat[1]);
                $end = strtotime($createat[3].' '.$createat[4]);
                $map['p.create_time'] = ['between', [$start,$end]];
            }

            $map['p.status'] = 4;
            $map['p.is_show'] = 1;
            unset($filter['order_number']);
            unset($filter['supplier_name']);
            unset($filter['userid']);
            unset($filter['create_person']);
            unset($filter['create_time']);
            unset($filter['one_time-operate']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'p.id';
            $total = $this->financepurchase
                ->alias('p')
                ->join('fa_supplier s','s.id=p.supplier_id','left')
                ->join('fa_finance_purchase_log l','p.process_instance_id=l.process_instance_id','left')
                ->field('p.id,s.supplier_name,p.order_number,p.create_time,l.userid,p.create_person')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->financepurchase
                ->alias('p')
                ->join('fa_supplier s','s.id=p.supplier_id','left')
                ->join('fa_finance_purchase_log l','p.process_instance_id=l.process_instance_id','left')
                ->field('p.id,s.supplier_name,p.order_number,FROM_UNIXTIME(p.create_time) create_time,l.userid,p.create_person')
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
