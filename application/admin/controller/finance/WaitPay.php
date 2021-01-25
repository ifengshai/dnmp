<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;

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
            if($filter['nickname']){
                //创建人
                $ids = Db::name('finance_purchase_log')->alias('l')->join('fa_admin a','a.id=l.userid')->where('a.nickname',$filter['nickname'])->column('l.process_instance_id');
                $map['p.process_instance_id'] = ['in',$ids];
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
            unset($filter['nickname']);
            unset($filter['create_time']);
            unset($filter['one_time-operate']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'p.id';
            $total = $this->financepurchase
                ->alias('p')
                ->join('fa_supplier s','s.id=p.supplier_id','left')
                ->field('p.id,s.supplier_name,p.order_number,p.create_time,l.userid,p.create_person')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->financepurchase
                ->alias('p')
                ->join('fa_supplier s','s.id=p.supplier_id','left')
                ->field('p.id,s.supplier_name,p.order_number,FROM_UNIXTIME(p.create_time) create_time,p.create_person')
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
    /*
     * 判断创建付款单时是否为同一个供应商
     * */
    public function supplier(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $ids = $params['ids'];
            //判断供应商是否一致
            $supplier_ids = $this->financepurchase->where('id','in',$ids)->column('supplier_id');
            if(count(array_unique($supplier_ids)) != 1){
                $status = 1;
            }else{
                $status = 0;
            }
            return json(['code' => 1, 'data' => $status]);
        }
    }
}
