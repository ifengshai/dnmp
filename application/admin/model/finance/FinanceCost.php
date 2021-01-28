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
     * 审单成功-核算订单收入
     *
     * @Description
     * @author gyh
     * @param $order_id 订单id
              $bill_type 单据类型(对应不同业务节点)
              $action_type 动作类型：1增加；2冲减；默认1增加
     * @since 2021/01/14 19:42:14 
     * @return void
     */
    public function order_income($order_id = null)
    {
        $order = new \app\admin\model\order\order\NewOrder();
        $order_detail = $order->get($order_id); //查询订单信息
        if (!$order_detail) {
            return 0;
        }
        $params['type'] = 1;
        $params['bill_type'] = 1;
        $params['order_number'] = $order_detail['increment_id'];
        $params['site'] = $order_detail['site'];
        $params['order_type'] = $order_detail['order_type'];
        $params['order_money'] = $order_detail['base_grand_total'];
        $params['income_amount'] = $order_detail['base_grand_total'];
        $params['order_currency_code'] = $order_detail['order_currency_code'];
        $params['payment_time'] = $order_detail['payment_time'];
        $params['payment_method'] = $order_detail['payment_method'];
        $params['action_type'] = 1;
        $params['createtime'] = time();
        //订单收入增加
        $res = $this->insert($params);
        $this->process_complete_workorder($order_detail); //处理存在补价，退件，退款工单的核算
    }

    /**
     * 处理当前订单已完成的工单包含的所有措施
     *
     * @Description
     * @author gyh
     * @param $order_id 订单id
     */
    public function process_complete_workorder($order_detail = null)
    {
        $work_order_list = new \app\admin\model\saleaftermanage\WorkOrderList();
        $change_sku = $work_order_list //查询补价，退件，退款的工单
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.id=b.work_id')
            ->where([
                'a.platform_order' => $order_detail['increment_id'],
                'b.measure_choose_id' => ['in', [2, 8]],
                'b.operation_type' => 1
            ])
            ->select();
        $change_sku = collection($change_sku)->toArray();
        if (!empty($change_sku)) { //如果有补价，退件，退款增加成本核算
            foreach ($change_sku as $key => $value) {
                $params = [];
                switch ($value['measure_choose_id']) {
                    case 2: //退款措施
                        if ($value['refund_money'] < $order_detail['base_grand_total']) { //判断是否是部分退款
                            $bill_type = 6; //部分退款
                            $action_type = 2; //冲减
                            $income_amount = $value['refund_money']; //收入金额(退款金额)
                        } else if ($value['refund_money'] == $order_detail['base_grand_total']) {
                            $bill_type = 4; //退货退款
                            $action_type = 2; //冲减
                            $income_amount = $order_detail['base_grand_total']; //收入金额(退件)
                        }
                        break;
                    case 8: //补差价措施
                        $bill_type = 3; //补差价工单收入单据类型
                        $action_type = 1; //增加
                        $income_amount = $value['replenish_money']; //收入金额(补差价的金额)
                        break;
                    /*case 15: //vip退款措施
                        $bill_type = 7; //vip退款单据类型
                        $action_type = 2; //冲减
                        $income_amount = $value['refund_money']; //收入金额(退款金额)
                        break;*/
                }
                if (!empty($bill_type)) { //有工单单据需要核算-增加核算数据
                    $params['type'] = 1;
                    $params['bill_type'] = $bill_type; //单据类型
                    $params['order_number'] = $order_detail['increment_id']; //订单号
                    $params['site'] = $order_detail['site']; //站点
                    $params['order_type'] = $order_detail['order_type']; //
                    $params['order_money'] = $order_detail['base_grand_total']; //订单金额
                    $params['income_amount'] = $income_amount; //收入金额
                    $params['order_currency_code'] = $order_detail['order_currency_code']; //币种
                    $params['payment_time'] = $order_detail['payment_time']; //支付时间
                    $params['payment_method'] = $order_detail['payment_method']; //支付方式
                    $params['action_type'] = $action_type; //动作类型：1增加；2冲减；
                    $params['work_id'] = $value['work_id']; //工单id
                    $params['createtime'] = time();
                    $this->insert($params);
                }
            }
        }
    }

    /**
     * 工单主单取消-冲减
     *
     * @Description
     * @author gyh
     * @param $work_id 订单id
     */
    public function cancel_order_subtract($work_id = null)
    {
        $WorkOrderList = new \app\admin\model\saleaftermanage\WorkOrderList;
        $work_order_info = $WorkOrderList->get($work_id); //获取工单信息
        $order = new \app\admin\model\order\order\NewOrder();
        $order_detail = $order->where(['increment_id' => $work_order_info['platform_order']])->find(); //获取订单信息
        $params['type'] = 1;
        $params['bill_type'] = 5; //单据类型
        $params['order_number'] = $order_detail['increment_id']; //订单号
        $params['site'] = $order_detail['site']; //站点
        $params['order_type'] = $order_detail['order_type']; //
        $params['order_money'] = $order_detail['base_grand_total']; //订单金额
        $params['income_amount'] = $order_detail['base_grand_total']; //收入金额
        $params['order_currency_code'] = $order_detail['order_currency_code']; //币种
        $params['payment_time'] = $order_detail['payment_time']; //支付时间
        $params['payment_method'] = $order_detail['payment_method']; //支付方式
        $params['action_type'] = 2; //动作类型：1增加；2冲减；
        $params['work_id'] = $work_id; //工单id
        $params['createtime'] = time();
        $this->insert($params); //主单取消冲减
        $params['action_type'] = 1;
        $this->insert($params); //主单取消增加
    }

    /**
     * vip退款-冲减
     *
     * @Description
     * @author gyh
     * @param $work_id 订单id
     */
    public function vip_order_subtract($work_id = null)
    {
        $WorkOrderList = new \app\admin\model\saleaftermanage\WorkOrderList;
        $work_order_info = $WorkOrderList->get($work_id); //获取工单信息
        $params['type'] = 1;
        $params['bill_type'] = 7; //单据类型
        $params['order_number'] = $work_order_info['platform_order']; //订单号
        $params['site'] = $work_order_info['work_platform']; //站点
        $params['order_type'] = 9; //vip
        $params['order_money'] = $work_order_info['refund_money']; //订单金额
        $params['income_amount'] = $work_order_info['refund_money']; //收入金额
        $params['order_currency_code'] = $work_order_info['order_pay_currency']; //币种
        $params['payment_time'] = $work_order_info['payment_time']; //支付时间
        $params['payment_method'] = $work_order_info['order_pay_method']; //支付方式
        $params['action_type'] = 2; //动作类型：1增加；2冲减；
        $params['work_id'] = $work_id; //工单id
        $params['createtime'] = time();
        $this->insert($params); //vip退款冲减
    }

    /**
     * 订单成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 16:31:21 
     * @return void
     */
    public function order_cost($order_id = null)
    {
        $order = new \app\admin\model\order\order\NewOrder();
        $order_detail = $order->get($order_id);
        if (!$order_detail) {
            return [];
        }
        $params['type'] = 2;
        $params['bill_type'] = 8;
        $params['order_number'] = $order_detail['increment_id'];
        $params['site'] = $order_detail['site'];
        $params['order_type'] = $order_detail['order_type'];
        $params['order_money'] = $order_detail['base_grand_total'];
        $params['income_amount'] = $order_detail['base_grand_total'];
        $params['order_currency_code'] = $order_detail['order_currency_code'];
        $params['payment_time'] = $order_detail['payment_time'];
        $params['payment_method'] = $order_detail['payment_method'];
        $params['frame_cost'] = $this->order_frame_cost($order_id, $order_detail['increment_id']);
        $params['lens_cost'] = $this->order_lens_cost($order_id, $order_detail['increment_id']);
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
            ->join(['fa_work_order_change_sku' => 'b'], 'a.id=b.work_id')
            ->where(['platform_order' => $order_number, 'work_status' => 7, 'change_type' => 4])
            ->column('b.goods_number');
        $workcost = 0;
        if ($goods_number) {
            //计算成本
            $workdata = $product_barcode_item->alias('a')->field('purchase_price,actual_purchase_price,c.purchase_total,purchase_num')
                ->where(['code' => ['in', $goods_number]])
                ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
                ->join(['fa_purchase_order' => 'c'], 'a.purchase_id=c.id')
                ->select();
            foreach ($workdata as $k => $v) {
                $workcost += $v['actual_purchase_price'] > 0 ? $v['actual_purchase_price'] : $v['purchase_total'] / $v['purchase_num'];
            }
        }

        //根据子单号查询条形码绑定关系
        $list = $product_barcode_item->alias('a')->field('purchase_price,actual_purchase_price,c.purchase_total,purchase_num')
            ->where(['item_order_number' => ['in', $item_order_number]])
            ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
            ->join(['fa_purchase_order' => 'c'], 'a.purchase_id=c.id')
            ->select();
        $list = collection($list)->toArray();
        $allcost = 0;
        foreach ($list as $k => $v) {
            $purchase_price = $v['actual_purchase_price'] > 0 ? $v['actual_purchase_price'] : ($v['purchase_total'] / $v['purchase_num']);
            $allcost += $purchase_price;
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
    protected function order_lens_cost($order_id = null, $order_number = null)
    {

        //判断是否有工单
        $worklist = new \app\admin\model\saleaftermanage\WorkOrderList();
        $workchangesku = new \app\admin\model\saleaftermanage\WorkOrderChangeSku();
        $work_id = $worklist->where(['platform_order' => $order_number, 'work_status' => 7])->order('id desc')->value('id');
        //查询更改类型为更改镜片
        $work_data = $workchangesku->where(['work_id' => $work_id, 'change_type' => 2])
            ->field('od_sph,os_sph,od_cyl,os_cyl,os_add,od_add,lens_number,item_order_number')
            ->select();
        $work_data = collection($work_data)->toArray();
        //工单计算镜片成本
        if ($work_data) {
            $where['item_order_number'] = ['not in', array_column($work_data, 'item_order_number')];
            $lens_number = array_column($work_data, 'lens_number');
            //查询镜片编码对应价格
            $lens_price = new \app\admin\model\lens\LensPrice();
            $lens_list = $lens_price->where(['lens_number' => ['in', $lens_number]])->order('price asc')->select();
            $work_cost = 0;
            foreach ($work_data as $k => $v) {
                $data = [];
                foreach ($lens_list as $key => $val) {
                    $temp_cost = 0;
                    //如果计算过一次成本 跳过
                    if (in_array($val['lens_number'], $data)) {
                        continue;
                    }
                    if ($v['od_cyl'] == '-0.25') {
                        //右眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float) $v['od_sph'] >= (float) $val['sph_start'] && (float) $v['od_sph'] <= (float) $val['sph_end']) && ((float) $v['od_cyl'] == (float) $val['cyl_end'] && (float) $v['od_cyl'] == (float) $val['cyl_end'])) {
                            $work_cost += $val['price'];
                            $temp_cost += $val['price'];
                        } elseif ($v['lens_number'] == $val['lens_number'] && ((float) $v['od_sph'] >= (float) $val['sph_start'] && (float) $v['od_sph'] <= (float) $val['sph_end']) && ((float) $v['od_cyl'] >= (float) $val['cyl_start'] && (float) $v['od_cyl'] <= (float) $val['cyl_end'])) {
                            $work_cost += $val['price'];
                            $temp_cost += $val['price'];
                        }
                    } else {
                        //右眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float) $v['od_sph'] >= (float) $val['sph_start'] && (float) $v['od_sph'] <= (float) $val['sph_end']) && ((float) $v['od_cyl'] >= (float) $val['cyl_start'] && (float) $v['od_cyl'] <= (float) $val['cyl_end'])) {
                            $work_cost += $val['price'];
                            $temp_cost += $val['price'];
                        }
                    }

                    if ($v['os_cyl'] == '-0.25') {
                        //左眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float) $v['os_sph'] >= (float) $val['sph_start'] && (float) $v['os_sph'] <= (float) $val['sph_end']) && ((float) $v['os_cyl'] == (float) $val['cyl_start'] && (float) $v['os_cyl'] == (float) $val['cyl_end'])) {
                            $work_cost += $val['price'];
                            $temp_cost += $val['price'];
                        } elseif ($v['lens_number'] == $val['lens_number'] && ((float) $v['os_sph'] >= (float) $val['sph_start'] && (float) $v['os_sph'] <= (float) $val['sph_end']) && ((float) $v['os_cyl'] >= (float) $val['cyl_start'] && (float) $v['os_cyl'] <= (float) $val['cyl_end'])) {
                            $work_cost += $val['price'];
                            $temp_cost += $val['price'];
                        }
                    } else {
                        //左眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float) $v['os_sph'] >= (float) $val['sph_start'] && (float) $v['os_sph'] <= (float) $val['sph_end']) && ((float) $v['os_cyl'] >= (float) $val['cyl_start'] && (float) $v['os_cyl'] <= (float) $val['cyl_end'])) {
                            $work_cost += $val['price'];
                            $temp_cost += $val['price'];
                        }
                    }

                    //如果计算过一次成本 记录下来 防止满足两个条件,计算两次
                    if ($temp_cost > 0) {
                        $data[] = $v['lens_number'];
                    }
                }
            }
        }

        //查询处方数据
        $order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $order_prescription = $order_item_process->alias('a')->field('b.od_sph,b.os_sph,b.od_cyl,b.os_cyl,b.os_add,b.od_add,b.lens_number')
            ->where(['a.order_id' => $order_id, 'distribution_status' => 9])
            ->where($where)
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->select();

        $order_prescription = collection($order_prescription)->toArray();
        $lens_number = array_column($order_prescription, 'lens_number');
        //查询镜片编码对应价格
        $lens_price = new \app\admin\model\lens\LensPrice();
        $lens_list = $lens_price->where(['lens_number' => ['in', $lens_number]])->order('price asc')->select();
        $cost = 0;

        foreach ($order_prescription as $k => $v) {
            $data = [];
            foreach ($lens_list as $key => $val) {
                 //如果计算过一次成本 跳过
                $temp_cost = 0;
                if (in_array($val['lens_number'], $data)) {
                    continue;
                }
                if ($v['od_cyl'] == '-0.25') {
                    //右眼
                    if ($v['lens_number'] == $val['lens_number'] && ((float) $v['od_sph'] >= (float) $val['sph_start'] && (float) $v['od_sph'] <= (float) $val['sph_end']) && ((float) $v['od_cyl'] == (float) $val['cyl_end'] && (float) $v['od_cyl'] == (float) $val['cyl_end'])) {
                        $cost += $val['price'];
                        $temp_cost += $val['price'];
                    } elseif ($v['lens_number'] == $val['lens_number'] && ((float) $v['od_sph'] >= (float) $val['sph_start'] && (float) $v['od_sph'] <= (float) $val['sph_end']) && ((float) $v['od_cyl'] >= (float) $val['cyl_start'] && (float) $v['od_cyl'] <= (float) $val['cyl_end'])) {
                        $cost += $val['price'];
                        $temp_cost += $val['price'];
                    }
                } else {
                    //右眼
                    if ($v['lens_number'] == $val['lens_number'] && ((float) $v['od_sph'] >= (float) $val['sph_start'] && (float) $v['od_sph'] <= (float) $val['sph_end']) && ((float) $v['od_cyl'] >= (float) $val['cyl_start'] && (float) $v['od_cyl'] <= (float) $val['cyl_end'])) {
                        $cost += $val['price'];
                        $temp_cost += $val['price'];
                    }
                }

                if ($v['os_cyl'] == '-0.25') {
                    //左眼
                    if ($v['lens_number'] == $val['lens_number'] && ((float) $v['os_sph'] >= (float) $val['sph_start'] && (float) $v['os_sph'] <= (float) $val['sph_end']) && ((float) $v['os_cyl'] == (float) $val['cyl_end'] && (float) $v['os_cyl'] == (float) $val['cyl_end'])) {
                        $cost += $val['price'];
                        $temp_cost += $val['price'];
                    } elseif ($v['lens_number'] == $val['lens_number'] && ((float) $v['os_sph'] >= (float) $val['sph_start'] && (float) $v['os_sph'] <= (float) $val['sph_end']) && ((float) $v['os_cyl'] >= (float) $val['cyl_start'] && (float) $v['os_cyl'] <= (float) $val['cyl_end'])) {
                        $cost += $val['price'];
                        $temp_cost += $val['price'];
                    }
                } else {
                    //左眼
                    if ($v['lens_number'] == $val['lens_number'] && ((float) $v['os_sph'] >= (float) $val['sph_start'] && (float) $v['os_sph'] <= (float) $val['sph_end']) && ((float) $v['os_cyl'] >= (float) $val['cyl_start'] && (float) $v['os_cyl'] <= (float) $val['cyl_end'])) {
                        $cost += $val['price'];
                        $temp_cost += $val['price'];
                    }
                }
                //如果计算过一次成本 记录下来 防止满足两个条件,计算两次
                if ($temp_cost > 0) {
                    $data[] = $v['lens_number'];
                }
            }
        }

        return $cost + $work_cost;
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
        $params['bill_type'] = 9;
        $params['order_number'] = $out_stock_number;
        $params['frame_cost'] = $this->outstock_frame_cost($out_stock_id);
        $params['action_type'] = 1;
        $params['order_currency_code'] = 'cny';
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
        $list = $product_barcode_item->alias('a')->field('purchase_price,actual_purchase_price,c.purchase_total,purchase_num')
            ->where(['out_stock_id' => $out_stock_id])
            ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
            ->join(['fa_purchase_order' => 'c'], 'a.purchase_id=c.id')
            ->select();
        $list = collection($list)->toArray();
        $allcost = 0;
        foreach ($list as $k => $v) {
            $allcost += $v['actual_purchase_price'] > 0 ? $v['actual_purchase_price'] : $v['purchase_total'] / $v['purchase_num'];
        }
        return $allcost;
    }
}
