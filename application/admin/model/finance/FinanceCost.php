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

    /**
     * 订单成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 16:31:21 
     * @return void
     */
    public function order_cost($order_id = null, $type = 0)
    {
        $order = new \app\admin\model\order\order\NewOrder();
        $order_detail = $order->get($order_id);
        if (!$order_detail) {
            return [];
        }
        $params['type'] = 2;
        $params['bill_type'] = $type;
        $params['order_number'] = $order_detail['increment_id'];
        $params['site'] = $order_detail['site'];
        $params['order_type'] = $order_detail['order_type'];
        $params['order_money'] = $order_detail['base_grand_total'];
        $params['income_amount'] = $order_detail['base_grand_total'];
        $params['order_currency_code'] = $order_detail['order_currency_code'];
        $params['payment_time'] = $order_detail['payment_time'];
        $params['payment_method'] = $order_detail['payment_method'];
        $params['frame_cost'] = $this->order_frame_cost($order_id);
        $params['lens_cost'] = $this->order_lens_cost($order_id);
        $params['action_type'] = $order_detail['payment_method'];
        $params['createtime'] = time();
        return $this->allowField(true)->save($params);
    }

    /**
     * 订单镜架成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 16:31:21 
     * @return void
     */
    protected function order_frame_cost($order_id = null)
    {

        $order = new \app\admin\model\order\order\NewOrder();
        $order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        //查询订单子单号
        $item_order_number = $order_item_process->where(['order_id' => $order_id])->column('item_order_number');

        //根据子单号查询条形码绑定关系
        $product_barcode_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        $list = $product_barcode_item->where(['item_order_number' => ['in', $item_order_number]])->select();
        $list = collection($list)->toArray();
        $purchase_id = array_column($list, 'purchase_id');

        //查询SKU采购成本及实际成本
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem();
        $item_list = $purchase_item->where(['purchase_id' => ['in', $purchase_id]])->select();
        $cost = [];
        foreach($item_list as $k => $v) {
            //采购单价
            $cost[$v['purchase_id']][$v['sku']]['purchase_price'] = $v['purchase_price'];
            //实际采购成本
            $cost[$v['purchase_id']][$v['sku']]['actual_purchase_price'] = $v['actual_purchase_price'];
        }
        foreach ($list as $k => $v) {
            
        }


        return $num;
    }

    /**
     * 镜片成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 16:31:21 
     * @return void
     */
    protected function order_lens_cost($order_id = null)
    {
        return $num;
    }
}
