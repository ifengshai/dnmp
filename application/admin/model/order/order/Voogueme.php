<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class Voogueme extends Model
{
    //数据库
    // protected $connection = 'database';
    protected $connection = 'database.db_voogueme';

    
    // 表名
    protected $table = 'sales_flat_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
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
    public function getOrderCostInfo($totalId,$thisPageId)
    {

        $arr = [];
        if(!$totalId || !$thisPageId){
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
        $totalMap['entity_id'] = ['in',$totalId];
        $totalMap['status']    = ['in',['processing','complete','creditcard_proccessing','free_processing']];
        $payInfo = $this->where($totalMap)->field('entity_id,base_total_paid,base_total_due,postage_money')->select();
        if($payInfo){
            foreach($payInfo as $v){
                $arr['totalPayInfo'] +=round($v['base_total_paid']+$v['base_total_due'],2);
                $arr['totalPostageMoney'] += round($v['postage_money'],2);
            }
        }

        //求出镜架成本start
        //1.求出所有的订单号
        $frameTotalMap['entity_id'] = ['in',$totalId];
        $frameThisPageMap['entity_id'] = ['in',$thisPageId];
        $order['increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameTotalMap)->column('increment_id');
        if(!$order['increment_id']){
            return $arr;
        }
        //2.求出本页面的订单号
        $order['this_increment_id'] = Db::connect($this->connection)->table('sales_flat_order')->where($frameThisPageMap)->column('increment_id');
        if(!$order['this_increment_id']){
            return $arr;
        }
        //求出镜架成本start
        $arr['totalFramePrice'] = $arr['totalLensPrice'] = 0;
        $outStockMap['order_number'] = ['in',$order['increment_id']];
        $frameInfo = Db::table('fa_outstock_log')->alias('g')->where($outStockMap)->join('purchase_order_item m','g.purchase_id=m.purchase_id and g.sku=m.sku')
        ->field('g.sku,g.order_number,g.out_stock_num,g.purchase_id,m.purchase_price')->select(); 
        if($frameInfo){
            foreach($frameInfo as $fv){
                 $arr['totalFramePrice'] +=round($fv['out_stock_num']*$fv['purchase_price'],2);
                if(in_array($fv['order_number'],$order['this_increment_id'])){
                    $arr['thispageFramePrice'][$fv['order_number']] = round($fv['out_stock_num']*$fv['purchase_price'],2);
                }
            }
        }

        //求出镜架成本end
        //求出镜片成本start
        $lensInfo = Db::table('fa_lens_outorder')->where($outStockMap)->field('order_number,num,price')->select();
        if($lensInfo){
            foreach($lensInfo as  $lv){
                $arr['totalLensPrice'] += round($lv['num']*$lv['price'],2);
                if(in_array($lv['order_number'],$order['this_increment_id'])){
                    $arr['thispageLensPrice'][$lv['order_number']] = round($lv['num']*$lv['price'],2);
                }
            }
        }
        //求出镜片成本end
        //求出退款金额和补差价金额start
        $saleMap['order_number'] = ['in',$order['increment_id']];
        $saleMap['task_status']  = 2;
        $synergyMap['synergy_status'] = 2;
        $synergyMap['synergy_order_number'] = ['in',$order['increment_id']];
        $synergyMap['synergy_order_id'] = 2;
        $arr['totalRefundMoney'] = $arr['totalFullPostMoney'] = 0;
        $saleAfterInfo = Db::name('sale_after_task')->where($saleMap)->field('order_number,refund_money,make_up_price_order')->select(); 
        $infoSynergyInfo = Db::name('info_synergy_task')->where($synergyMap)->field('synergy_order_number,refund_money,make_up_price_order')->select();
        //求出退款金额
        //把补差价订单号存起来
        $fullPostOrderTask = $fullPostOrderSynergy = [];
        if($saleAfterInfo){
            foreach($saleAfterInfo as $sv){
                $arr['totalRefundMoney'] += round($sv['refund_money'],2);
                if(in_array($sv['order_number'],$order['this_increment_id'])){
                    $arr['thispageRefundMoney'][$sv['order_number']] = round($sv['refund_money'],2);
                }
                //如果补差价订单存在的话,把补差价订单存起来
                if($sv['make_up_price_order']){
                    $fullPostOrderTask[$sv['order_number']] = $sv['make_up_price_order'];
                }
            }
        }
        if($infoSynergyInfo){
            foreach($infoSynergyInfo as $vs){
                $arr['totalRefundMoney'] += round($vs['refund_money'],2);
                if(in_array($vs['synergy_order_number'],$order['this_increment_id'])){
                    if(isset($arr['thispageRefundMoney'][$vs['synergy_order_number']])){
                        $arr['thispageRefundMoney'][$vs['synergy_order_number']] += round($vs['refund_money'],2);
                    }else{
                        $arr['thispageRefundMoney'][$vs['synergy_order_number']] = round($vs['refund_money'],2);
                    }
                }
                if($vs['make_up_price_order']){
                    $fullPostOrderSynergy[$vs['synergy_order_number']] = $vs['make_up_price_order'];
                }
            }
        }
        //求出退款金额成本end
        //求出补差价订单(售后的补差价订单)
        //去掉重复的补差价订单号
        //$fullPostOrder = array_unique($fullPostOrder);
        //搜索订单条件
        $fullPostMap['status']       = ['in',['processing','complete','creditcard_proccessing','free_processing']];
        $fullPostMap['increment_id'] = ['in',$fullPostOrderTask];
        $fullPostResult = $this->where($fullPostMap)->field('increment_id,base_total_paid,base_total_due')->select();
        if($fullPostResult){
            foreach($fullPostResult as $vf){
                $arr['totalFullPostMoney'] +=round($vf['base_total_paid']+$vf['base_total_due'],2);
                //求出订单号
                $originOrder = array_search($vf['increment_id'],$fullPostOrderTask);
                if(in_array($originOrder,$order['this_increment_id'])){
                    $arr['thispageFullPostMoney'][$originOrder] = round($vf['base_total_paid']+$vf['base_total_due'],2);
                }

            }
            
        }
        //求出补差价订单(信息协同补差价订单)
        $synergyFullPostMap['status']       = ['in',['processing','complete','creditcard_proccessing','free_processing']];
        $synergyFullPostMap['increment_id'] = ['in',$fullPostOrderSynergy];
        $synergyPostResult = $this->where($synergyFullPostMap)->field('increment_id,base_total_paid,base_total_due')->select();
        if($synergyPostResult){
            foreach($synergyPostResult as $svf){
                $arr['totalFullPostMoney'] +=round($svf['base_total_paid']+$svf['base_total_due'],2);
                //求出订单号
                $originOrder = array_search($svf['increment_id'],$fullPostOrderSynergy);
                if(in_array($originOrder,$order['this_increment_id'])){
                    $arr['thispageFullPostMoney'][$originOrder] += round($svf['base_total_paid']+$svf['base_total_due'],2);
                }

            }
            
        }


        //求出加工费
        $arr['totalProcessCost'] = 0;
        $totalprocessMap['order_id'] = ['in',$totalId];
        $processResult = Db::connect($this->connection)->table('sales_flat_order_item_prescription')->where($totalprocessMap)->field('order_id,sku,prescription_type,index_type,frame_type_is_rimless,qty_ordered')->select();
        if($processResult){
            foreach($processResult as $pv){
                //1.处方类型为渐进镜,或者镜架是无框的都是8 元
                if(('Progressive' == $pv['prescription_type']) || ('Bifocal' == $pv['prescription_type']) ||((2 ==  $pv['frame_type_is_rimless']))){
                    $process_price = 8;
                //2.处方类型为单光并且折射率比较高的话是8元    
                }elseif((false !== strpos($pv['index_type'],'1.67')) || (false !== strpos($pv['index_type'],'1.71') || (false !== strpos($pv['index_type'],'1.74')))){
                    $process_price = 8;
                //其他的不是Plastic Lens的类型 5元   
                }elseif((!empty($pv['index_type']) && ('Plastic Lens' !=$pv['index_type']))){
                    $process_price = 5;
                }else{
                    $process_price = 0;
                }
                $arr['totalProcessCost'] += round($pv['qty_ordered']*$process_price,2);
                if(in_array($pv['order_id'],$thisPageId)){
                    $arr['thisPageProcessCost'][$pv['order_id']] += round($pv['qty_ordered']*$process_price,2);
                }
            } 
        }
            return $arr;
    }
    







}
