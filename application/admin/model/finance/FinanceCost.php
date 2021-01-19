<?php

namespace app\admin\model\finance;

use think\Db;
use think\Model;


class FinanceCost extends Model
{

    // 表名
    protected $name = 'finance_cost';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [];

    /**
     * 订单收入
     *
     * @Description
     * @author wpl
     * @param $order_id 订单id
     * @since 2021/01/14 19:42:14 
     * @return void
     */
    public function order_income($order_id = null, $type = 0)
    {
        $order = new \app\admin\model\order\order\NewOrder();
        $order_detail = $order->get($order_id);
        if (!$order_detail) {
            return [];
        }
        $params['type'] = 1;
        $params['bill_type'] = $type;
        $params['order_number'] = $order_detail['increment_id'];
        $params['site'] = $order_detail['site'];
        $params['order_type'] = $order_detail['order_type'];
        $params['order_money'] = $order_detail['base_grand_total'];
        $params['income_amount'] = $order_detail['base_grand_total'];
        $params['order_currency_code'] = $order_detail['order_currency_code'];
        $params['payment_time'] = $order_detail['payment_time'];
        $params['payment_method'] = $order_detail['payment_method'];
        $params['createtime'] = time();
        return $this->allowField(true)->save($params);
    }
}
