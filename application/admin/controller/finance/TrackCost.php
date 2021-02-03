<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;

class TrackCost extends Backend
{
    public function _initialize()
    {
        return parent::_initialize();
    }
    /*
     * 列表
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
            if($filter['increment_id']){
                //订单号
                $map['increment_id'] = $filter['increment_id'];
            }
            if($filter['platform_shop_name']){
                //平台
                $map['platform_shop_name'] = $filter['platform_shop_name'];
            }
            if($filter['created_at']){
                //创建时间
                $createat = explode(' ', $filter['created_at']);
                $map['created_at'] = ['between', [$createat[0].' '.$createat[1],$createat[3].' '.$createat[4]]];
            }
            $map['fi_review_status'] = ['in','0,10'];
            $map['platform_shop_name'] = ['<',20];
            $map['increment_id'] = ['<>',''];
            $model = Db::connect('database.db_delivery');
            $model->table('ld_delivery_order_finance')->query("set time_zone='+8:00'");
            unset($filter['increment_id']);
            unset($filter['platform_shop_name']);
            unset($filter['created_at']);
            unset($filter['one_time-operate']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $model->table('ld_delivery_order_finance')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $model->table('ld_delivery_order_finance')
                ->where($where)
                ->where($map)
                ->field('id,increment_id,track_number,platform_shop_name,fi_actual_payment_fee,created_at')
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
