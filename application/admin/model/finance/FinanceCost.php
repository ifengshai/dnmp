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
        $params['frame_cost'] = $this->order_frame_cost($order_id, $order_detail['increment_id']);
        $params['lens_cost'] = $this->order_lens_cost($order_id);
        $params['action_type'] = 1;
        $params['createtime'] = time();
        return $this->allowField(true)->save($params);
    }

    /**
     * 订单镜架成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 18:20:45 
     * @param [type] $order_id     订单id
     * @param [type] $order_number 订单号
     * @return void
     */
    protected function order_frame_cost($order_id = null, $order_number = null)
    {
        $product_barcode_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        $order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        //查询订单子单号
        $item_order_number = $order_item_process->where(['order_id' => $order_id])->column('item_order_number');

        //判断是否有工单
        $worklist = new \app\admin\model\saleaftermanage\WorkOrderList();

        //查询更改类型为赠品
        $goods_number = $worklist->alias('a')
            ->join(['fa_work_order_change_sku' => 'b', 'a.id=b.work_id'])
            ->where(['platform_order' => $order_number, 'work_status' => 7, 'change_type' => 4])
            ->column('goods_number');
        $workcost = 0;
        if ($goods_number) {
            //计算成本
            $workdata = $product_barcode_item->field('purchase_price,actual_purchase_price')
                ->where(['code' => ['in', $goods_number]])
                ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
                ->select();
            foreach ($workdata as $k => $v) {
                $workcost += $v['actual_purchase_price'] > 0 ?: $v['purchase_price'];
            }
        }

        //根据子单号查询条形码绑定关系
        $list = $product_barcode_item->field('purchase_price,actual_purchase_price')
            ->where(['item_order_number' => ['in', $item_order_number]])
            ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
            ->select();
        $list = collection($list)->toArray();
        $allcost = 0;
        foreach ($list as $k => $v) {
            $allcost += $v['actual_purchase_price'] > 0 ?: $v['purchase_price'];
        }
        return $allcost + $workcost;
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

    /**
     * 出库单镜框成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 16:31:21 
     * @return void
     */
    public function outstock_cost($out_stock_id = null, $out_stock_number = null)
    {
        $params['type'] = 2;
        $params['bill_type'] = 8;
        $params['order_number'] = $out_stock_number;
        $params['frame_cost'] = $this->outstock_frame_cost($out_stock_id);
        $params['action_type'] = 1;
        $params['createtime'] = time();
        return $this->allowField(true)->save($params);
    }

    /**
     * 出库单镜架成本计算
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 18:20:45 
     * @param [type] $order_id     订单id
     * @param [type] $order_number 订单号
     * @return void
     */
    protected function outstock_frame_cost($out_stock_id = null)
    {
        $product_barcode_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        //根据子单号查询条形码绑定关系
        $list = $product_barcode_item->field('purchase_price,actual_purchase_price')
            ->where(['out_stock_id' => $out_stock_id])
            ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
            ->select();
        $list = collection($list)->toArray();
        $allcost = 0;
        foreach ($list as $k => $v) {
            $allcost += $v['actual_purchase_price'] > 0 ?: $v['purchase_price'];
        }
        return $allcost;
    }
}
