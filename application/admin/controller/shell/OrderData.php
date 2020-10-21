<?php

/**
 * 订单数据解析
 * 执行时间：
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use think\Db;

class OrderData extends Backend
{
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->order = new \app\admin\model\order\Order();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
    }



    /**
     * 处理订单数据
     *
     * @Description
     * @author wpl
     * @since 2020/10/21 14:55:50 
     * @return void
     */
    public function process_order_data()
    {
        //查询订单表最大id
        $id = $this->order->max('entity_id');

        $list = $this->zeelool->where(['entity_id' => ['>', $id]])
        ->field('entity_id,status,store_id,increment_id as order_number,base_grand_total,order_type,base_currency_code,customer_email,customer_firstname,customer_lastname,created_at,updated_at')
        ->limit(100)
        ->select();
        $list = collection($list)->toArray();

        $order_ids = array_column($list,'entity_id');
        //查询每个单号购买的商品数量
        $goods_data = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where(['order_id' => ['in',$order_ids]])->column("sum(qty_ordered)",'order_id');
        foreach($list as &$v) {
            $v['created_at'] = strtotime($v['created_at']);
            $v['updated_at'] = strtotime($v['updated_at']);
            $v['all_goods_num'] = $goods_data[$v['entity_id']] ?? 0;
        }
        dump($list);die;
        
    }
}
