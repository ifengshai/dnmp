<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class ZeeloolFr extends Model
{
    //数据库
    protected $connection = 'database.db_zeelool_fr';


    // 表名
    protected $table = 'sales_flat_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //名称获取器
    public function getCustomerFirstnameAttr($value, $data)
    {
        return $data['customer_firstname'] . ' ' . $data['customer_lastname'];
    }

    /***
     * 获取nihao订单的成本信息  create@lsw
     * @param totalId 所有的
     * @param thisPageId 当前页面的ID 
     */
    public function getOrderCostInfo($totalId, $thisPageId)
    {

        $arr = [];
        if (!$totalId || !$thisPageId) {
            return $arr;
        }
        //原先逻辑已经废弃(总付款金额)
        // $totalMap['parent_id'] = ['in',$totalId];
        // //总付款金额
        // $payInfo = Db::connect($this->connection)->table('sales_flat_order_payment')->where($totalMap)->sum('base_amount_paid');
        // $arr['totalPayInfo'] = $payInfo;
        // $thisPageIdMap['parent_id'] = ['in',$thisPageId];
        // $thisPageInfo = Db::connect($this->connection)->table('sales_flat_order_payment')->where($thisPageIdMap)->field('parent_id,base_amount_paid')->select();
        // if(!$thisPageInfo){
        //     return $arr;
        // }
        // $thisPageInfo = collection($thisPageInfo)->toArray($thisPageInfo);
        // foreach($thisPageInfo as  $v){
        //         $arr['thisPagePayPrice'][$v['parent_id']] = round($v['base_amount_paid'],2);
        // }
        //求出总付款金额
        $totalMap['entity_id'] = ['in', $totalId];
        $totalMap['status']    = ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $payInfo = $this->where($totalMap)->field('entity_id,base_total_paid,base_total_due,postage_money')->select();
        if ($payInfo) {
            foreach ($payInfo as $v) {
                $arr['totalPayInfo'] += round($v['base_total_paid'] + $v['base_total_due'], 2);
                $arr['totalPostageMoney'] += round($v['postage_money'], 2);
            }
        }

        //求出镜架成本start
        //1.求出所有的订单号
        $frameTotalMap['entity_id'] = ['in', $totalId];
        $frameThisPageMap['entity_id'] = ['in', $thisPageId];
        $order['increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameTotalMap)->column('increment_id');
        if (!$order['increment_id']) {
            return $arr;
        }
        //2.求出本页面的订单号
        $order['this_increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameThisPageMap)->column('increment_id');
        if (!$order['this_increment_id']) {
            return $arr;
        }
        //求出镜架成本start
        $arr['totalFramePrice'] = $arr['totalLensPrice'] = 0;
        $outStockMap['order_number'] = ['in', $order['increment_id']];
        $frameInfo = Db::table('fa_outstock_log')->alias('g')->where($outStockMap)->join('purchase_order_item m', 'g.purchase_id=m.purchase_id and g.sku=m.sku')
            ->field('g.sku,g.order_number,g.out_stock_num,g.purchase_id,m.purchase_price')->select();
        if ($frameInfo) {
            foreach ($frameInfo as $fv) {
                $arr['totalFramePrice'] += round($fv['out_stock_num'] * $fv['purchase_price'], 2);
                if (in_array($fv['order_number'], $order['this_increment_id'])) {
                    $arr['thispageFramePrice'][$fv['order_number']] = round($fv['out_stock_num'] * $fv['purchase_price'], 2);
                }
            }
        }

        //求出镜架成本end
        //求出镜片成本start
        $lensInfo = Db::table('fa_lens_outorder')->where($outStockMap)->field('order_number,num,price')->select();
        if ($lensInfo) {
            foreach ($lensInfo as  $lv) {
                $arr['totalLensPrice'] += round($lv['num'] * $lv['price'], 2);
                if (in_array($lv['order_number'], $order['this_increment_id'])) {
                    $arr['thispageLensPrice'][$lv['order_number']] = round($lv['num'] * $lv['price'], 2);
                }
            }
        }
        //求出镜片成本end
        //求出退款金额和补差价金额start
        $saleMap['order_number'] = ['in', $order['increment_id']];
        $saleMap['task_status']  = 2;
        $synergyMap['synergy_status'] = 2;
        $synergyMap['synergy_order_number'] = ['in', $order['increment_id']];
        $synergyMap['synergy_order_id'] = 2;
        $arr['totalRefundMoney'] = $arr['totalFullPostMoney'] = 0;
        $saleAfterInfo = Db::name('sale_after_task')->where($saleMap)->field('order_number,refund_money,make_up_price_order')->select();
        $infoSynergyInfo = Db::name('info_synergy_task')->where($synergyMap)->field('synergy_order_number,refund_money,make_up_price_order')->select();
        //求出退款金额
        //把补差价订单号存起来
        $fullPostOrderTask = $fullPostOrderSynergy = [];
        if ($saleAfterInfo) {
            foreach ($saleAfterInfo as $sv) {
                $arr['totalRefundMoney'] += round($sv['refund_money'], 2);
                if (in_array($sv['order_number'], $order['this_increment_id'])) {
                    $arr['thispageRefundMoney'][$sv['order_number']] = round($sv['refund_money'], 2);
                }
                //如果补差价订单存在的话,把补差价订单存起来
                if ($sv['make_up_price_order']) {
                    $fullPostOrderTask[$sv['order_number']] = $sv['make_up_price_order'];
                }
            }
        }
        if ($infoSynergyInfo) {
            foreach ($infoSynergyInfo as $vs) {
                $arr['totalRefundMoney'] += round($vs['refund_money'], 2);
                if (in_array($vs['synergy_order_number'], $order['this_increment_id'])) {
                    if (isset($arr['thispageRefundMoney'][$vs['synergy_order_number']])) {
                        $arr['thispageRefundMoney'][$vs['synergy_order_number']] += round($vs['refund_money'], 2);
                    } else {
                        $arr['thispageRefundMoney'][$vs['synergy_order_number']] = round($vs['refund_money'], 2);
                    }
                }
                if ($vs['make_up_price_order']) {
                    $fullPostOrderSynergy[$vs['synergy_order_number']] = $vs['make_up_price_order'];
                }
            }
        }
        //求出退款金额成本end
        //求出补差价订单(售后的补差价订单)
        //去掉重复的补差价订单号
        //$fullPostOrder = array_unique($fullPostOrder);
        //搜索订单条件
        $fullPostMap['status']       = ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $fullPostMap['increment_id'] = ['in', $fullPostOrderTask];
        $fullPostResult = $this->where($fullPostMap)->field('increment_id,base_total_paid,base_total_due')->select();
        if ($fullPostResult) {
            foreach ($fullPostResult as $vf) {
                $arr['totalFullPostMoney'] += round($vf['base_total_paid'] + $vf['base_total_due'], 2);
                //求出订单号
                $originOrder = array_search($vf['increment_id'], $fullPostOrderTask);
                if (in_array($originOrder, $order['this_increment_id'])) {
                    $arr['thispageFullPostMoney'][$originOrder] = round($vf['base_total_paid'] + $vf['base_total_due'], 2);
                }
            }
        }
        //求出补差价订单(信息协同补差价订单)
        $synergyFullPostMap['status']       = ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $synergyFullPostMap['increment_id'] = ['in', $fullPostOrderSynergy];
        $synergyPostResult = $this->where($synergyFullPostMap)->field('increment_id,base_total_paid,base_total_due')->select();
        if ($synergyPostResult) {
            foreach ($synergyPostResult as $svf) {
                $arr['totalFullPostMoney'] += round($svf['base_total_paid'] + $svf['base_total_due'], 2);
                //求出订单号
                $originOrder = array_search($svf['increment_id'], $fullPostOrderSynergy);
                if (in_array($originOrder, $order['this_increment_id'])) {
                    $arr['thispageFullPostMoney'][$originOrder] += round($svf['base_total_paid'] + $svf['base_total_due'], 2);
                }
            }
        }


        //求出加工费
        $arr['totalProcessCost'] = 0;
        $totalprocessMap['order_id'] = ['in', $totalId];
        $processResult = Db::connect($this->connection)->table('sales_flat_order_item_prescription')->where($totalprocessMap)->field('order_id,sku,prescription_type,index_type,frame_type_is_rimless,qty_ordered')->select();
        if ($processResult) {
            foreach ($processResult as $pv) {
                //1.处方类型为渐进镜,或者镜架是无框的都是8 元
                if (('Progressive' == $pv['prescription_type']) || ('Bifocal' == $pv['prescription_type']) || ((2 ==  $pv['frame_type_is_rimless']))) {
                    $process_price = 8;
                    //2.处方类型为单光并且折射率比较高的话是8元    
                } elseif ((false !== strpos($pv['index_type'], '1.67')) || (false !== strpos($pv['index_type'], '1.71') || (false !== strpos($pv['index_type'], '1.74')))) {
                    $process_price = 8;
                    //其他的不是Plastic Lens的类型 5元   
                } elseif ((!empty($pv['index_type']) && ('Plastic Lens' != $pv['index_type']))) {
                    $process_price = 5;
                } else {
                    $process_price = 0;
                }
                $arr['totalProcessCost'] += round($pv['qty_ordered'] * $process_price, 2);
                if (in_array($pv['order_id'], $thisPageId)) {
                    $arr['thisPageProcessCost'][$pv['order_id']] += round($pv['qty_ordered'] * $process_price, 2);
                }
            }
        }
        return $arr;
    }

    /**
     * 获取zeelool订单的成本信息 用于导出excel(按照条件导出)
     * @param $totalId
     * @param $thisPageId
     * @return array
     * @return void
     * @Description
     * @author jhh
     * @since 2020/6/12 16:49
     */
    public function getOrderCostInfoExcel($totalId, $thisPageId)
    {

        $arr = [];
        if (!$totalId || !$thisPageId) {
            return $arr;
        }
        //求出总付款金额
        $totalMap['entity_id'] = ['in', $totalId];
        $totalMap['status']    = ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $payInfo = $this->where($totalMap)->field('entity_id,base_total_paid,base_total_due,postage_money')->select();
        if ($payInfo) {
            foreach ($payInfo as $v) {
                $arr['totalPayInfo'] += round($v['base_total_paid'] + $v['base_total_due'], 2);
                $arr['totalPostageMoney'] += round($v['postage_money'], 2);
            }
        }

        //求出镜架成本start
        //1.求出所有的订单号
        $frameTotalMap['entity_id'] = ['in', $totalId];
        $frameThisPageMap['entity_id'] = ['in', $thisPageId];
        $order['increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameTotalMap)->column('increment_id');
        if (!$order['increment_id']) {
            return $arr;
        }
        //2.求出本页面的订单号
        $order['this_increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameThisPageMap)->column('increment_id');
        if (!$order['this_increment_id']) {
            return $arr;
        }
        //求出镜架成本start
        $arr['totalFramePrice'] = $arr['totalLensPrice'] = 0;
        $outStockMap['order_number'] = ['in', $order['increment_id']];
        $frameInfo = Db::table('fa_outstock_log')->alias('g')->where($outStockMap)->join('purchase_order_item m', 'g.purchase_id=m.purchase_id and g.sku=m.sku')
            ->field('g.sku,g.order_number,g.out_stock_num,g.purchase_id,m.purchase_price')->select();
        if ($frameInfo) {
            foreach ($frameInfo as $fv) {
                $arr['totalFramePrice'] += round($fv['out_stock_num'] * $fv['purchase_price'], 2);
                if (in_array($fv['order_number'], $order['this_increment_id'])) {
                    $arr['thispageFramePrice'][$fv['order_number']] = round($fv['out_stock_num'] * $fv['purchase_price'], 2);
                }
            }
        }

        //求出镜架成本end
        //求出镜片成本start
        $lensInfo = Db::table('fa_lens_outorder')->where($outStockMap)->field('order_number,num,price')->select();
        if ($lensInfo) {
            foreach ($lensInfo as  $lv) {
                $arr['totalLensPrice'] += round($lv['num'] * $lv['price'], 2);
                if (in_array($lv['order_number'], $order['this_increment_id'])) {
                    $arr['thispageLensPrice'][$lv['order_number']] = round($lv['num'] * $lv['price'], 2);
                }
            }
        }
        //求出镜片成本end
        //求出退款金额和补差价金额start
        $saleMap['order_number'] = ['in', $order['increment_id']];
        $saleMap['task_status']  = 2;
        $synergyMap['synergy_status'] = 2;
        $synergyMap['synergy_order_number'] = ['in', $order['increment_id']];
        $synergyMap['synergy_order_id'] = 2;
        $arr['totalRefundMoney'] = $arr['totalFullPostMoney'] = 0;
        $saleAfterInfo = Db::name('sale_after_task')->where($saleMap)->field('order_number,refund_money,make_up_price_order')->select();
        $infoSynergyInfo = Db::name('info_synergy_task')->where($synergyMap)->field('synergy_order_number,refund_money,make_up_price_order')->select();
        //求出退款金额
        //把补差价订单号存起来
        $fullPostOrderTask = $fullPostOrderSynergy = [];
        if ($saleAfterInfo) {
            foreach ($saleAfterInfo as $sv) {
                $arr['totalRefundMoney'] += round($sv['refund_money'], 2);
                if (in_array($sv['order_number'], $order['this_increment_id'])) {
                    $arr['thispageRefundMoney'][$sv['order_number']] = round($sv['refund_money'], 2);
                }
                //如果补差价订单存在的话,把补差价订单存起来
                if ($sv['make_up_price_order']) {
                    $fullPostOrderTask[$sv['order_number']] = $sv['make_up_price_order'];
                }
            }
        }
        if ($infoSynergyInfo) {
            foreach ($infoSynergyInfo as $vs) {
                $arr['totalRefundMoney'] += round($vs['refund_money'], 2);
                if (in_array($vs['synergy_order_number'], $order['this_increment_id'])) {
                    if (isset($arr['thispageRefundMoney'][$vs['synergy_order_number']])) {
                        $arr['thispageRefundMoney'][$vs['synergy_order_number']] += round($vs['refund_money'], 2);
                    } else {
                        $arr['thispageRefundMoney'][$vs['synergy_order_number']] = round($vs['refund_money'], 2);
                    }
                }
                if ($vs['make_up_price_order']) {
                    $fullPostOrderSynergy[$vs['synergy_order_number']] = $vs['make_up_price_order'];
                }
            }
        }
        //求出退款金额成本end
        //求出补差价订单(售后的补差价订单)
        //去掉重复的补差价订单号
        //$fullPostOrder = array_unique($fullPostOrder);
        //搜索订单条件
        $fullPostMap['status']       = ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $fullPostMap['increment_id'] = ['in', $fullPostOrderTask];
        $fullPostResult = $this->where($fullPostMap)->field('increment_id,base_total_paid,base_total_due')->select();
        if ($fullPostResult) {
            foreach ($fullPostResult as $vf) {
                $arr['totalFullPostMoney'] += round($vf['base_total_paid'] + $vf['base_total_due'], 2);
                //求出订单号
                $originOrder = array_search($vf['increment_id'], $fullPostOrderTask);
                if (in_array($originOrder, $order['this_increment_id'])) {
                    $arr['thispageFullPostMoney'][$originOrder] = round($vf['base_total_paid'] + $vf['base_total_due'], 2);
                }
            }
        }
        //求出补差价订单(信息协同补差价订单)
        $synergyFullPostMap['status']       = ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','paypal_canceled_reversal','paypal_reversed']];
        $synergyFullPostMap['increment_id'] = ['in', $fullPostOrderSynergy];
        $synergyPostResult = $this->where($synergyFullPostMap)->field('increment_id,base_total_paid,base_total_due')->select();
        if ($synergyPostResult) {
            foreach ($synergyPostResult as $svf) {
                $arr['totalFullPostMoney'] += round($svf['base_total_paid'] + $svf['base_total_due'], 2);
                //求出订单号
                $originOrder = array_search($svf['increment_id'], $fullPostOrderSynergy);
                if (in_array($originOrder, $order['this_increment_id'])) {
                    $arr['thispageFullPostMoney'][$originOrder] += round($svf['base_total_paid'] + $svf['base_total_due'], 2);
                }
            }
        }


        //求出加工费
        $arr['totalProcessCost'] = 0;
        $totalprocessMap['order_id'] = ['in', $totalId];
        $processResult = Db::connect($this->connection)->table('sales_flat_order_item_prescription')->where($totalprocessMap)->field('order_id,sku,prescription_type,index_type,frame_type_is_rimless,qty_ordered')->select();
        if ($processResult) {
            foreach ($processResult as $pv) {
                //1.处方类型为渐进镜,或者镜架是无框的都是8 元
                if (('Progressive' == $pv['prescription_type']) || ('Bifocal' == $pv['prescription_type']) || ((2 ==  $pv['frame_type_is_rimless']))) {
                    $process_price = 8;
                    //2.处方类型为单光并且折射率比较高的话是8元
                } elseif ((false !== strpos($pv['index_type'], '1.67')) || (false !== strpos($pv['index_type'], '1.71') || (false !== strpos($pv['index_type'], '1.74')))) {
                    $process_price = 8;
                    //其他的不是Plastic Lens的类型 5元
                } elseif ((!empty($pv['index_type']) && ('Plastic Lens' != $pv['index_type']))) {
                    $process_price = 5;
                } else {
                    $process_price = 0;
                }
                $arr['totalProcessCost'] += round($pv['qty_ordered'] * $process_price, 2);
                if (in_array($pv['order_id'], $thisPageId)) {
                    $arr['thisPageProcessCost'][$pv['order_id']] += round($pv['qty_ordered'] * $process_price, 2);
                }
            }
        }
        return $arr;
    }

    /**
     * 统计订单SKU销量
     *
     * @Description
     * @author wpl
     * @since 2020/02/06 16:42:25 
     * @param [type] $sku 筛选条件
     * @return object
     */
    public function getOrderSalesNum($sku, $where)
    {
        if ($sku) {
            $map['sku'] = ['in', $sku];
        } else {
            $map['sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $res = $this
            ->where($map)
            ->where($where)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->group('sku')
            ->order('num desc')
            ->column('round(sum(b.qty_ordered)) as num', 'sku');
        return $res;
    }


    /**
     * 统计未发货订单
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function undeliveredOrder($map = [])
    {
        $map['custom_is_delivery_new'] = 0;
        //过滤补差价单
        $map['order_type'] = ['<>', 5];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        return $this->alias('a')->where($map)->count(1);
    }

    /**
     * 统计未发货订单SKU副数
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function undeliveredOrderNum($map)
    {
        if ($map) {
            $map['custom_is_delivery_new'] = 0;
            //过滤补差价单
            $map['order_type'] = ['<>', 5];
            $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            return $this->alias('a')->where($map)->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id = b.order_id')->sum('b.qty_ordered');
        }
    }

    /**
     * 统计仅镜架订单
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function frameOrder($map)
    {
        if ($map) {
            $map['custom_is_delivery_new'] = 0;
            //过滤补差价单
            $map['order_type'] = ['<>', 5];
            $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['custom_order_prescription_type'] = 1;
            return $this->alias('a')->where($map)->count(1);
        }
    }

    /**
     * 统计处方镜副数
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function getOrderPrescriptionNum($map)
    {
        if ($map) {
            $map['custom_is_delivery_new'] = 0;
            //过滤补差价单
            $map['order_type'] = ['<>', 5];
            $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['custom_order_prescription_type'] = ['in', [2, 3, 4, 5, 6]];
            $map[] = ['exp', Db::raw("index_type NOT IN ( 'Plastic Lenses', 'FRAME ONLY' ) 
            AND index_type IS NOT NULL 
            AND index_type != ''")];

            return $this->alias('a')
                ->where($map)
                ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id = b.order_id')
                ->sum('b.qty_ordered');
        }
    }

    /**
     * 统计现货处方镜副数
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function getSpotOrderPrescriptionNum($map)
    {
        if ($map) {
            $map['custom_is_delivery_new'] = 0;
            //过滤补差价单
            $map['order_type'] = ['<>', 5];
            $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['custom_order_prescription_type'] = ['in', [2, 4, 6]];
            $map[] = ['exp', Db::raw("index_type NOT IN ( 'Plastic Lenses', 'FRAME ONLY' ) 
            AND index_type IS NOT NULL 
            AND index_type != ''")];
            $map['is_custom_lens'] = 0;

            return $this->alias('a')
                ->where($map)
                ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id = b.order_id')
                ->sum('b.qty_ordered');
        }
    }

    /**
     * 统计定制处方镜副数
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function getCustomOrderPrescriptionNum($map)
    {
        if ($map) {
            $map['custom_is_delivery_new'] = 0;
            //过滤补差价单
            $map['order_type'] = ['<>', 5];
            $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['custom_order_prescription_type'] = ['in', [3, 5, 6]];
            $map[] = ['exp', Db::raw("index_type NOT IN ( 'Plastic Lenses', 'FRAME ONLY' ) 
            AND index_type IS NOT NULL 
            AND index_type != ''")];
            $map['is_custom_lens'] = 1;

            return $this->alias('a')
                ->where($map)
                ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id = b.order_id')
                ->sum('b.qty_ordered');
        }
    }

    /**
     * 统计待处理订单 即未打印标签的订单
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 14:57:47 
     * @return void
     */
    public function getPendingOrderNum()
    {
        $where['custom_print_label_new'] = 0;
        //过滤补差价单
        $where['order_type'] = ['<>', 5];
        $where['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        return $this->where($where)->count(1);
    }

    /**
     * 统计当月销售总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/06 16:08:44 
     * @return void
     */
    public function getOrderSkuNum()
    {
        $where['a.created_at'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        return $this->alias('a')
            ->where($where)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->sum('b.qty_ordered');
    }

    /**
     * 统计当月销售总成本
     *
     * @Description
     * @author wpl
     * @since 2020/03/06 16:08:44 
     * @return void
     */
    public function getOrderSalesCost()
    {
        $where['a.created_at'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $data = $this->alias('a')
            ->where($where)
            ->field("sum(qty_ordered) as num,sku")
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->group('sku')
            ->select();

        //SKU实时进价
        $sku_pirce = new \app\admin\model\SkuPrice;
        $arr = $sku_pirce->getAllData();
        $itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        //SKU参考进价
        $item = new \app\admin\model\itemmanage\Item();
        $item_price = $item->getSkuPrice();
        $all_price = 0;
        foreach ($data as $k => $v) {
            //sku转换
            $sku = $itemplatformsku->getWebSku($v['sku'], 2);
            if ($arr[$sku]) {
                $all_price += $arr[$sku] * $v['num'];
            } else {
                $all_price += $item_price[$sku] * $v['num'];
            }
        }
        return $all_price;
    }

    /**
     * 统计订单SKU销量
     *
     * @Description
     * @author wpl
     * @since 2020/02/06 16:42:25 
     * @param [type] $sku 筛选条件
     * @return object
     */
    public function getOrderSalesNumTop30($sku, $where)
    {
        if ($sku) {
            $map['sku'] = ['in', $sku];
        }
        $map['sku'] = ['not like', '%Price%'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $res = $this
            ->where($map)
            ->where($where)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->group('sku')
            ->order('num desc')
            ->limit(15)
            ->column('round(sum(b.qty_ordered)) as num', 'sku');

        return $res;
    }

    /**
     * 根据SKU查询订单号ID
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 14:51:20 
     * @return void
     */
    public function getOrderId($map)
    {
        if ($map) {
            $result = Db::connect('database.db_zeelool_fr')
                ->table('sales_flat_order_item')
                ->alias('a')
                ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
                ->where($map)
                ->column('order_id');
            return $result;
        }
        return false;
    }

    /**
     * 统计加工时效
     *
     * @Description
     * @author wpl
     * @since 2020/03/14 14:20:45 
     * @return void
     */
    public function getProcessingAging()
    {
        //最近30天
        $created_at = ['between', [date('Y-m-d 00:00:00', strtotime('-30 day')), date('Y-m-d H:i:s', time())]];

        /**************未超时未处理******************/
        //打标签(24h)
        $map[] = ['exp', Db::raw("created_at >= (NOW() - interval 24 hour) and custom_print_label_new = 0")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['labelNotOvertime'] = $this->where($map)->cache(7200)->count(1);

        //配镜架（24h）
        $map = [];
        $map[] = ['exp', Db::raw("custom_print_label_created_at_new >= (NOW() - interval 24 hour) and custom_is_match_frame_new = 0 and custom_print_label_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['frameNotOvertime'] = $this->where($map)->cache(7200)->count(1);

        //配镜片
        //现片时效 24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [2, 4]];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new >= (NOW() - interval 24 hour) and custom_is_match_lens_new = 0 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $nowLensNotOvertime = $this->where($map)->cache(7200)->count(1);

        //定制片时效 5*24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [3, 5, 6]];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new >= (NOW() - interval 5*24 hour) and custom_is_match_lens_new = 0 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $customLensNotOvertime = $this->where($map)->cache(7200)->count(1);
        $data['lensNotOvertime'] = $nowLensNotOvertime + $customLensNotOvertime;

        //加工(24h）
        $map = [];
        $map[] = ['exp', Db::raw("custom_match_lens_created_at_new >= (NOW() - interval 24 hour) and custom_is_send_factory_new = 0 and custom_is_match_lens_new = 1")];
        $map['created_at'] = $created_at;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $data['machiningNotOvertime'] = $this->where($map)->cache(7200)->count(1);

        //成品质检(24h）两种情况 1 仅镜架不许要点击加工  2 含处方需要点击加工
        //仅镜架情况 以配镜架为时间节点
        $map = [];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new >= (NOW() - interval 24 hour) and custom_is_delivery_new = 0 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = 1;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkNotOvertime01 = $this->where($map)->cache(7200)->count(1);

        $map = [];
        $map[] = ['exp', Db::raw("custom_match_factory_created_at_new >= (NOW() - interval 24 hour) and custom_is_delivery_new = 0 and custom_is_send_factory_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = ['in', [2, 3, 4, 5, 6]];;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkNotOvertime02 = $this->where($map)->cache(7200)->count(1);
        $data['checkNotOvertime'] =  $checkNotOvertime01 +  $checkNotOvertime02;

        /**************超时未处理******************/
        //打标签(24h)
        $map = [];
        $map[] = ['exp', Db::raw("created_at < (NOW() - interval 24 hour) and custom_print_label_new = 0")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['labelOvertime'] = $this->where($map)->cache(3600)->count(1);

        //配镜架（24h）
        $map = [];
        $map[] = ['exp', Db::raw("custom_print_label_created_at_new < (NOW() - interval 24 hour) and custom_is_match_frame_new = 0 and custom_print_label_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['frameOvertime'] = $this->where($map)->cache(3600)->count(1);

        //配镜片
        //现片时效 24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [2, 4]];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new < (NOW() - interval 24 hour) and custom_is_match_lens_new = 0 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $nowLensOvertime = $this->where($map)->cache(3600)->count(1);

        //定制片时效 5*24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [3, 5, 6]];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new < (NOW() - interval 5*24 hour) and custom_is_match_lens_new = 0 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $customLensOvertime = $this->where($map)->cache(3600)->count(1);
        $data['lensOvertime'] = $nowLensOvertime + $customLensOvertime;

        //加工(24h）
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map[] = ['exp', Db::raw("custom_match_lens_created_at_new < (NOW() - interval 24 hour) and custom_is_send_factory_new = 0 and custom_is_match_lens_new = 1")];
        $map['created_at'] = $created_at;
        $data['machiningOvertime'] = $this->where($map)->cache(3600)->count(1);

        //成品质检(24h）两种情况 1 仅镜架不许要点击加工  2 含处方需要点击加工
        //仅镜架情况 以配镜架为时间节点
        $map = [];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new < (NOW() - interval 24 hour) and custom_is_delivery_new = 0 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = 1;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkOvertime01 = $this->where($map)->cache(7200)->count(1);

        $map = [];
        $map[] = ['exp', Db::raw("custom_match_factory_created_at_new < (NOW() - interval 24 hour) and custom_is_delivery_new = 0 and custom_is_send_factory_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = ['in', [2, 3, 4, 5, 6]];;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkOvertime02 = $this->where($map)->cache(7200)->count(1);
        $data['checkOvertime'] =  $checkOvertime01 +  $checkOvertime02;


        /**************未超时已处理******************/
        //打标签(24h)
        $map = [];
        $map[] = ['exp', Db::raw("created_at >= (NOW() - interval 24 hour) and custom_print_label_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['labelNotOvertimeProcess'] = $this->where($map)->cache(10800)->count(1);

        //配镜架（24h）
        $map = [];
        $map[] = ['exp', Db::raw("custom_print_label_created_at_new >= (custom_match_frame_created_at_new - interval 24 hour) and custom_is_match_frame_new = 1 and custom_print_label_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['frameNotOvertimeProcess'] = $this->where($map)->cache(10800)->count(1);

        //配镜片
        //现片时效 24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [2, 4]];
        $map['created_at'] = $created_at;
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new >= (custom_match_lens_created_at_new- interval 24 hour) and custom_is_match_lens_new = 1 and custom_is_match_frame_new = 1")];
        $nowLensNotOvertimeProcess = $this->where($map)->cache(10800)->count(1);

        //定制片时效 5*24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [3, 5, 6]];
        $map['created_at'] = $created_at;
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new >= (custom_match_lens_created_at_new - interval 5*24 hour) and custom_is_match_lens_new = 1 and custom_is_match_frame_new = 1")];
        $customLensNotOvertimeProcess = $this->where($map)->cache(10800)->count(1);
        $data['lensNotOvertimeProcess'] = $nowLensNotOvertimeProcess + $customLensNotOvertimeProcess;

        //加工(24h）
        $map = [];
        $map[] = ['exp', Db::raw("custom_match_lens_created_at_new >= (custom_match_factory_created_at_new - interval 24 hour) and custom_is_send_factory_new = 1 and custom_is_match_lens_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['machiningNotOvertimeProcess'] = $this->where($map)->cache(10800)->count(1);

        //成品质检(24h）两种情况 1 仅镜架不许要点击加工  2 含处方需要点击加工
        //仅镜架情况 以配镜架为时间节点
        $map = [];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new >= (custom_match_delivery_created_at_new - interval 24 hour) and custom_is_delivery_new = 1 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = 1;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkNotOvertimeProcess01 = $this->where($map)->cache(7200)->count(1);

        $map = [];
        $map[] = ['exp', Db::raw("custom_match_factory_created_at_new >= (custom_match_delivery_created_at_new - interval 24 hour) and custom_is_delivery_new = 1 and custom_is_send_factory_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = ['in', [2, 3, 4, 5, 6]];;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkNotOvertimeProcess02 = $this->where($map)->cache(7200)->count(1);
        $data['checkNotOvertimeProcess'] =  $checkNotOvertimeProcess01 +  $checkNotOvertimeProcess02;

        /**************超时已处理******************/
        //打标签(24h)
        $map = [];
        $map[] = ['exp', Db::raw("created_at < (custom_print_label_created_at_new - interval 24 hour) and custom_print_label_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['labelOvertimeProcess'] = $this->where($map)->cache(10800)->count(1);

        //配镜架（24h）
        $map = [];
        $map[] = ['exp', Db::raw("custom_print_label_created_at_new < (custom_match_frame_created_at_new - interval 24 hour) and custom_is_match_frame_new = 1 and custom_print_label_new = 1")];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['created_at'] = $created_at;
        $data['frameOvertimeProcess'] = $this->where($map)->cache(10800)->count(1);

        //配镜片
        //现片时效 24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [2, 4]];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new < (custom_match_lens_created_at_new - interval 24 hour) and custom_is_match_lens_new = 1 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $nowLensOvertimeProcess = $this->where($map)->cache(10800)->count(1);

        //定制片时效 5*24h
        $map = [];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['custom_order_prescription_type'] = ['in', [3, 5, 6]];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new < (custom_match_lens_created_at_new - interval 5*24 hour) and custom_is_match_lens_new = 1 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $customLensOvertimeProcess = $this->where($map)->cache(10800)->count(1);
        $data['lensOvertimeProcess'] = $nowLensOvertimeProcess + $customLensOvertimeProcess;

        //加工(24h）
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map[] = ['exp', Db::raw("custom_match_lens_created_at_new < (custom_match_factory_created_at_new - interval 24 hour) and custom_is_send_factory_new = 1 and custom_is_match_lens_new = 1")];
        $map['created_at'] = $created_at;
        $data['machiningOvertimeProcess'] = $this->where($map)->cache(10800)->count(1);

        //成品质检(24h）两种情况 1 仅镜架不许要点击加工  2 含处方需要点击加工
        //仅镜架情况 以配镜架为时间节点
        $map = [];
        $map[] = ['exp', Db::raw("custom_match_frame_created_at_new < (custom_match_delivery_created_at_new - interval 24 hour) and custom_is_delivery_new = 1 and custom_is_match_frame_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = 1;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkOvertimeProcess01 = $this->where($map)->cache(7200)->count(1);

        $map = [];
        $map[] = ['exp', Db::raw("custom_match_factory_created_at_new < (custom_match_delivery_created_at_new - interval 24 hour) and custom_is_delivery_new = 1 and custom_is_send_factory_new = 1")];
        $map['created_at'] = $created_at;
        $map['custom_order_prescription_type'] = ['in', [2, 3, 4, 5, 6]];;
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $checkOvertimeProcess02 = $this->where($map)->cache(10800)->count(1);
        $data['checkOvertimeProcess'] =  $checkOvertimeProcess01 +  $checkOvertimeProcess02;
        return $data;
    }

    /**
     * 打标签数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 09:52:30 
     * @return void
     */
    public function printLabelNum($time = [])
    {
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        if ($time) {
            $where['a.custom_print_label_created_at_new'] = ['between', $time];
        } else {
            $where['a.custom_print_label_created_at_new'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }

        $where['custom_print_label_new'] = 1;
        return $this->alias('a')
            ->where($where)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->sum('b.qty_ordered');
    }


    /**
     * 配镜架数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 09:52:30 
     * @return void
     */
    public function frameNum($time = [])
    {
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        if ($time) {
            $where['a.custom_match_frame_created_at_new'] = ['between', $time];
        } else {
            $where['a.custom_match_frame_created_at_new'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }
        $where['custom_is_match_frame_new'] = 1;
        return $this->alias('a')
            ->where($where)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->sum('b.qty_ordered');
    }


    /**
     * 配镜片数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 09:52:30 
     * @return void
     */
    public function lensNum($time = [])
    {
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        if ($time) {
            $where['a.custom_match_lens_created_at_new'] = ['between', $time];
        } else {
            $where['a.custom_match_lens_created_at_new'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }

        $where['custom_is_match_lens_new'] = 1;
        return $this->alias('a')
            ->where($where)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->sum('b.qty_ordered');
    }

    /**
     * 加工数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 09:52:30 
     * @return void
     */
    public function factoryNum($time = [])
    {
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        if ($time) {
            $where['a.custom_match_factory_created_at_new'] = ['between', $time];
        } else {
            $where['a.custom_match_factory_created_at_new'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }

        $where['custom_is_send_factory_new'] = 1;
        return $this->alias('a')
            ->where($where)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->sum('b.qty_ordered');
    }

    /**
     * 成品质检数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/20 09:52:30 
     * @return void
     */
    public function checkNum($time = [])
    {
        $where['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        if ($time) {
            $where['a.custom_match_delivery_created_at_new'] = ['between', $time];
        } else {
            $where['a.custom_match_delivery_created_at_new'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }

        $where['custom_is_delivery_new'] = 1;
        return $this->alias('a')
            ->where($where)
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')
            ->sum('b.qty_ordered');
    }
    /**
     * 复购用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:04
     */
    public function getAgainUser($time_str = '', $time_str2 = '')
    {
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' 00:00:00 - ' .$end;
        }
        $createat = explode(' ', $time_str);
        $again_num = $this->get_again_user($createat);
        $all_order_user = $this->get_all_order_user($createat);
        $all_order_user_rate = $all_order_user ? round(($again_num) / $all_order_user * 100, 2) : 0;
        $arrs['again_user_num'] = $again_num;
        $arrs['again_user_num_rate'] = $all_order_user_rate;
        if($time_str2){
            $createat2 = explode(' ', $time_str2);
            $contrast_again_num = $this->get_again_user($createat2);
            $contrast_all_order_user = $this->get_all_order_user($createat2);
            $arrs['contrast_again_user_num'] = $contrast_again_num ? round(($arrs['again_user_num'] - $contrast_again_num) / $contrast_again_num * 100, 2) : 0;
            $contrast_all_order_user_rate = $contrast_all_order_user ? round(($contrast_again_num) / $contrast_all_order_user * 100, 2) : 0;

            $arrs['all_contrast_again_user_num'] = $contrast_all_order_user_rate ? round(($all_order_user_rate - $contrast_all_order_user_rate) / $contrast_all_order_user_rate * 100, 2) : 0;
        }
        return $arrs;
    }
    //获取某一段时间内的复购用户数 new
    public function get_again_user($createat){
        $map_where['payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        $order_where['payment_time'] = ['lt',$createat[0]];

        $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
        $map['order_type'] = 1;
        $map1['customer_id'] = ['>',0];

        $order_model = new \app\admin\model\order\order\ZeeloolFr();
        //复购用户数
        //查询时间段内的订单 根据customer_id先计算出此事件段内的复购用户数
        $again_buy_num1 = $order_model
            ->where($map_where)
            ->where($map)
            ->where($map1)
            ->group('customer_id')
            ->having('count(customer_id)>1')
            ->count('customer_id');

        $again_buy_data2 = $order_model
            ->where($map_where)
            ->where($map)
            ->where($map1)
            ->group('customer_id')
            ->having('count(customer_id)<=1')
            ->column('customer_id');
        $again_buy_num2 = 0;
        foreach ($again_buy_data2 as $v){
            //查询时间段内是否进行购买行为
            $order_where_arr['customer_id'] = $v;
            $is_buy = $order_model->where($order_where)->where($order_where_arr)->where($map)->value('entity_id');
            if($is_buy){
                $again_buy_num2++;
            }
        }

        $again_buy_num = $again_buy_num1+$again_buy_num2;
        return $again_buy_num;
    }
    /**
     * 获取某一个时间购买的用户总数
     * @param $createat
     *
     * @return int|string
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/5/14 12:53
     */
    public function get_all_order_user($createat)
    {
        $map_where['payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        $order_where['payment_time'] = ['lt',$createat[0]];

        $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map['order_type'] = 1;
        $map1['customer_id'] = ['>',0];

        $order_model = new \app\admin\model\order\order\ZeeloolFr();
        return $order_model
            ->where($map_where)
            ->where($map)
            ->where($map1)
            ->group('customer_id')
            ->count('customer_id');
    }
    /**
     * 用户统计
     * @param $time_str
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/5/14 17:33
     */
    public function getUserOrderData($time_str)
    {
        if(!$time_str){
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start . ' - '. $end;
        }
        $createat = explode(' ', $time_str);
        $customerWhere['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
        $orderWhere['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
        $orderWhere['order_type'] = 1;
        $order = Db::connect('database.db_zeelool_fr')
            ->table('sales_flat_order')
            ->field('customer_group_id,sum(base_grand_total) as total,count(*) as count')
            ->group('customer_group_id')
            //->where('customer_group_id','>',0)
            ->where($customerWhere)
            ->where($orderWhere)
            ->select();
        $orderCount = Db::connect('database.db_zeelool_fr')
            ->table('sales_flat_order')
            ->where($customerWhere)
            ->where($orderWhere)
            //->where('customer_group_id','>',0)
            ->sum('base_grand_total');
        $result = [];
        $customerCount = 0;
        foreach($order as $k => $v){
            $customerCount += $v['count'];
        }
        foreach($order as $k => $v){
            switch($v['customer_group_id']) {
                case 0:
                    $name = '游客';
                    break;
                case 1:
                    $name = '普通用户';
                    break;
                case 2:
                    $name = '批发用户';
                    break;
                case 4:
                    $name = 'VIP';
                    break;
                default:
                    $name = "其余非游客用户";
            }
            $result[$k] = [
                'count' => $v['count'],
                'name' => $name,
                'num'  => round($v['total'],2),
                'rate' => bcmul(bcdiv($v['total'], $orderCount,4),100,2).'%',
                'customerRate' => bcmul(bcdiv($v['count'], $customerCount,4),100,2).'%',
            ];
        }
        return $result;
    }
}
