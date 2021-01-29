<?php

namespace app\admin\controller\supplydatacenter;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use function GuzzleHttp\Psr7\str;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class DataMarket extends Backend
{
    protected $noNeedRight = ['stock_overview','stock_measure_overview','stock_measure_overview_platform','stock_level_overview','stock_level_sales_rate','stock_age_overview','purchase_overview','purchase_histogram_line','order_send_overview','process_overview','logistics_completed_overview','comleted_time_rate'];
    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->skuSalesNum = new \app\admin\model\SkuSalesNum();
        $this->outstock = new \app\admin\model\warehouse\Outstock;
        $this->instock = new \app\admin\model\warehouse\Instock;
        $this->productGrade = new \app\admin\model\ProductGrade();
        $this->purchase = new \app\admin\model\purchase\PurchaseOrder();
        $this->warehouse_model = new \app\admin\model\WarehouseData();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->worklist = new \app\admin\model\saleaftermanage\WorkOrderList;
        $this->process = new \app\admin\model\order\order\NewOrderProcess;
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->distributionLog = new \app\admin\model\DistributionLog;
        $this->orderNode = new \app\admin\model\OrderNode;
        $this->supply = new \app\admin\model\supplydatacenter\Supply();
        $this->inventory = new \app\admin\model\warehouse\Inventory;
        $this->inventoryitem = new \app\admin\model\warehouse\InventoryItem;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->productAllStockLog = new \app\admin\model\ProductAllStock();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if(!$params['time_str']){
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start.' - '.$end;
            }else{
                $time_str = $params['time_str'];
            }
            //仓库指标总览
            $stock_measure_overview = $this->stock_measure_overview($time_str);
            $this->success('', '', $stock_measure_overview);
        }
        //库存总览
        $stock_overview = $this->stock_overview();
        //仓库指标总览
        $stock_measure_overview = $this->stock_measure_overview();
        //库存分级概况
        $stock_level_overview = $this->stock_level_overview();
        $stock_level_overview2 = $this->stock_level_overview2();
        //库龄概况
        $stock_age_overview = $this->stock_age_overview();
        //采购概况
        $purchase_overview = $this->purchase_overview();
        //物流妥投概况
        $logistics_completed_overview = $this->logistics_completed_overview();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(in_array($val['name'],['meeloog'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('stock_overview','stock_measure_overview','stock_level_overview','stock_level_overview2','stock_level_sales_rate','purchase_overview','logistics_completed_overview','magentoplatformarr','stock_age_overview','time_str'));
        return $this->view->fetch();
    }
    //库存变化折线图
    public function stock_change_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $time_str = $params['time_str'];
            if ($time_str) {
                $createat = explode(' ', $time_str);
                $where['createtime'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $where['createtime'] = ['between', [$start, $end]];
            }
            $data = $this->productAllStockLog->where($where)->column("allnum","DATE_FORMAT(createtime,'%Y-%m-%d') day_date");
            $json['xcolumnData'] = array_keys($data);
            $json['column'] = ['库存'];
            $json['columnData'] = [
                [
                    'name' => '库存',
                    'type' => 'line',
                    'smooth' => true,
                    'data' => array_values($data)
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    //库存总览
    public function stock_overview(){
        $cache_data = Cache::get('Supplydatacenter_datamarket'  . md5(serialize('stock_overview')));
        if ($cache_data) {
           return $cache_data;
        }
        //呆滞库存数量/金额
        $dull_stock = $this->getProductTurnoverDays();
        $arr['dull_stock_count'] = $dull_stock['count'];
        $arr['dull_stock_count1'] = $dull_stock['count1'];  //低
        $arr['dull_stock_count2'] = $dull_stock['count2'];  //中
        $arr['dull_stock_count3'] = $dull_stock['count3'];  //高
        $arr['dull_stock_total'] = $dull_stock['total'];
        $arr['dull_stock_total1'] = $dull_stock['total1'];   //低
        $arr['dull_stock_total2'] = $dull_stock['total2'];   //中
        $arr['dull_stock_total3'] = $dull_stock['total3'];   //高
        $where['is_open'] = 1;
        $where['is_del'] = 1;
        $where['category_id'] = ['<>',43]; //排除补差价商品
        //库存总数量
        $arr['stock_num'] = $this->model->where($where)->sum('stock');
        //库存总金额
        $arr['stock_amount'] = $this->model->where($where)->sum('stock*purchase_price');
        //库存单价
        $arr['stock_price'] = $arr['stock_num'] ? round($arr['stock_amount']/$arr['stock_num'],2) : 0;
        //在途库存数量
        $arr['onway_stock_num'] = $this->model->where($where)->sum('on_way_stock');
        //在途库存总金额
        $arr['onway_stock_amount'] = $this->model->where($where)->sum('on_way_stock*purchase_price');
        //在途库存单价
        $arr['onway_stock_price'] = $arr['onway_stock_num'] ? round($arr['onway_stock_amount']/$arr['onway_stock_num'],2) : 0;
        //待入库数量
        $arr['wait_stock_num'] = $this->model->where($where)->sum('wait_instock_num');
        //待入库金额
        $arr['wait_stock_amount'] = $this->model->where($where)->sum('wait_instock_num*purchase_price');
        Cache::set('Supplydatacenter_datamarket'  . md5(serialize('stock_overview')), $arr, 7200);
        return $arr;
    }
    public function getProductTurnoverDays(){
        $count = 0;   //总数量
        $count1 = 0;   //低
        $count2 = 0;   //中
        $count3 = 0;    //高
        $total = 0;     //总金额
        $total1 = 0;     //低
        $total2 = 0;     //中
        $total3 = 0;     //高
        $arr1 = array();   //A+
        $arr2 = array();   //A
        $arr3 = array();
        $arr4 = array();
        $arr5 = array();
        $arr6 = array();
        $arr7 = array();
        $arr8 = array();
        $grades = Db::name('product_grade')->field('true_sku,grade')->select();
        foreach ($grades as $key=>$value){
            //该品实时库存
            $real_time_stock = $this->model->where('sku',$value['true_sku'])->where('is_del',1)->where('is_open',1)->value('sum(stock)-sum(distribution_occupy_stock) as result');
            //该品库存金额
            $sku_amount = $this->item->alias('i')->join('fa_purchase_order_item o','i.purchase_id=o.purchase_id and i.sku=o.sku')->where('i.sku',$value['true_sku'])->where('i.library_status',1)->value('SUM(IF(o.actual_purchase_price != 0,o.actual_purchase_price,o.purchase_price)) as result');
            //实际周转天数
            $sku_info  = $this->getSkuSales($value['true_sku']);
            $actual_day = $sku_info['days']!=0 && $sku_info['count']!=0 ? round($real_time_stock/$sku_info['count']/$sku_info['days'],2) : 0;
            if($actual_day >120 && $actual_day<=144){
                $count += $real_time_stock;
                $total += $sku_amount;
                $count1 += $real_time_stock;
                $total1 += $sku_amount;
                if($value['grade'] == 'A+'){
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['stock1'] += $real_time_stock;
                    $arr1['total1'] += $sku_amount;
                }elseif($value['grade'] == 'A'){
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['stock1'] += $real_time_stock;
                    $arr2['total1'] += $sku_amount;
                }elseif($value['grade'] == 'B'){
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['stock1'] += $real_time_stock;
                    $arr3['total1'] += $sku_amount;
                }elseif($value['grade'] == 'C+'){
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['stock1'] += $real_time_stock;
                    $arr4['total1'] += $sku_amount;
                }elseif($value['grade'] == 'C'){
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['stock1'] += $real_time_stock;
                    $arr5['total1'] += $sku_amount;
                }elseif($value['grade'] == 'D'){
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['stock1'] += $real_time_stock;
                    $arr6['total1'] += $sku_amount;
                }elseif($value['grade'] == 'E'){
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['stock1'] += $real_time_stock;
                    $arr7['total1'] += $sku_amount;
                }else{
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['stock1'] += $real_time_stock;
                    $arr8['total1'] += $sku_amount;
                }
            }elseif($actual_day > 144 && $actual_day<=168){
                $count += $real_time_stock;
                $total += $sku_amount;
                $count2 += $real_time_stock;
                $total2 += $sku_amount;
                if($value['grade'] == 'A+'){
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['stock2'] += $real_time_stock;
                    $arr1['total2'] += $sku_amount;
                }elseif($value['grade'] == 'A'){
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['stock2'] += $real_time_stock;
                    $arr2['total2'] += $sku_amount;
                }elseif($value['grade'] == 'B'){
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['stock2'] += $real_time_stock;
                    $arr3['total2'] += $sku_amount;
                }elseif($value['grade'] == 'C+'){
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['stock2'] += $real_time_stock;
                    $arr4['total2'] += $sku_amount;
                }elseif($value['grade'] == 'C'){
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['stock2'] += $real_time_stock;
                    $arr5['total2'] += $sku_amount;
                }elseif($value['grade'] == 'D'){
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['stock2'] += $real_time_stock;
                    $arr6['total2'] += $sku_amount;
                }elseif($value['grade'] == 'E'){
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['stock2'] += $real_time_stock;
                    $arr7['total2'] += $sku_amount;
                }else{
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['stock2'] += $real_time_stock;
                    $arr8['total2'] += $sku_amount;
                }
            }elseif($actual_day>168){
                $count += $real_time_stock;
                $total += $sku_amount;
                $count3 += $real_time_stock;
                $total3 += $sku_amount;
                if($value['grade'] == 'A+'){
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['stock3'] += $real_time_stock;
                    $arr1['total3'] += $sku_amount;
                }elseif($value['grade'] == 'A'){
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['stock3'] += $real_time_stock;
                    $arr2['total3'] += $sku_amount;
                }elseif($value['grade'] == 'B'){
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['stock3'] += $real_time_stock;
                    $arr3['total3'] += $sku_amount;
                }elseif($value['grade'] == 'C+'){
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['stock3'] += $real_time_stock;
                    $arr4['total3'] += $sku_amount;
                }elseif($value['grade'] == 'C'){
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['stock3'] += $real_time_stock;
                    $arr5['total3'] += $sku_amount;
                }elseif($value['grade'] == 'D'){
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['stock3'] += $real_time_stock;
                    $arr6['total3'] += $sku_amount;
                }elseif($value['grade'] == 'E'){
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['stock3'] += $real_time_stock;
                    $arr7['total3'] += $sku_amount;
                }else{
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['stock3'] += $real_time_stock;
                    $arr8['total3'] += $sku_amount;
                }
            }
        }
        $result = array(
            'count'=>$count,
            'count1'=>$count1,
            'count2'=>$count2,
            'count3'=>$count3,
            'total'=>$total,
            'total1'=>$total1,
            'total2'=>$total2,
            'total3'=>$total3,
            'A+'=>$arr1,
            'A'=>$arr2,
            'B'=>$arr3,
            'C+'=>$arr4,
            'C'=>$arr5,
            'D'=>$arr6,
            'E'=>$arr7,
            'F'=>$arr8,
        );
        return $result;
    }
    //获取sku总销量
    public function getSkuSales($sku)
    {
        $days = array();
        //zeelool
        $z_info = $this->getDullStock($sku,1);
        $sales_num1 = $z_info['sales_num'];
        $days[] = $z_info['days'];
        //voogueme
        $v_info = $this->getDullStock($sku,2);
        $sales_num2 = $v_info['sales_num'];
        $days[] = $v_info['days'];
        //nihao
        $n_info = $this->getDullStock($sku,3);
        $sales_num3 = $n_info['sales_num'];
        $days[] = $n_info['days'];
        //meeloog
        $m_info = $this->getDullStock($sku,4);
        $sales_num4 = $m_info['sales_num'];
        $days[] = $m_info['days'];
        //wesee
        $w_info = $this->getDullStock($sku, 5);
        $sales_num5 = $w_info['sales_num'];
        $days[] = $w_info['days'];
        //amazon
        $a_info = $this->getDullStock($sku, 8);
        $sales_num6 = $a_info['sales_num'];
        $days[] = $a_info['days'];
        //zeelool_es
        $e_sku = $this->getDullStock($sku, 9);
        $sales_num7 = $e_sku['sales_num'];
        $days[] = $e_sku['days'];
        //zeelool_de
        $d_info = $this->getDullStock($sku, 10);
        $sales_num8 = $d_info['sales_num'];
        $days[] = $d_info['days'];
        //zeelool_jp
        $j_info = $this->getDullStock($sku, 11);
        $sales_num9 = $j_info['sales_num'];
        $days[] = $j_info['days'];
        //voogmechic
        $c_info = $this->getDullStock($sku, 12);
        $sales_num10 = $c_info['sales_num'];
        $days[] = $j_info['days'];
        $count = $sales_num1+$sales_num2+$sales_num3+$sales_num4+$sales_num5+$sales_num6+$sales_num7+$sales_num8+$sales_num9+$sales_num10;
        $days = max($days);
        $data = array(
            'count'=>$count,
            'days'=>$days,
        );
        return $data;
    }
    //查询sku的有效天数的销量和有效天数
    public function getDullStock($sku,$site)
    {
        $date = date('Y-m-d');
        $map['createtime'] = ['<',$date];
        $map['sku'] = $sku;
        $data['sales_num'] = Db::name('sku_sales_num')->where($map)->where('site',$site)->order('createtime','desc')->limit(30)->sum('sales_num');
        $days = Db::name('sku_sales_num')->where($map)->count();
        $data['days'] = $days>30 ? 30 : $days;
        return $data;
    }

    //仓库指标总览
    public function stock_measure_overview($time_str = ''){
        if(!$time_str){
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' - '.$end;
        }
        $cache_data = Cache::get('Supplydatacenter_datamarket'  .$time_str. md5(serialize('stock_measure_overview')));
        if ($cache_data) {
            return $cache_data;
        }
        /*
         * 库存周转率：所选时间内库存消耗数量/[（期初实时库存+期末实时库存）/2];
         * 库存消耗数量: 订单销售数量+出库单出库数量
         * */
        $createat = explode(' ', $time_str);
        $where['createtime'] = ['between', [$createat[0], $createat[3]]];
        $where['status'] = 2;
        $start = strtotime($createat[0]);
        $end = strtotime($createat[3]);
        $order_time_where['payment_time'] = ['between', [$start, $end]];  //修改
        $order_where['order_type'] = ['<>', 5];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        //订单销售数量
        $order_sales_num = $this->order->alias('o')->join('fa_order_item_option i','o.entity_id=i.order_id')->where($order_where)->where($order_time_where)->sum('i.qty');
        //出库单出库数量
        $out_stock_num = $this->outstock->alias('o')->join('fa_out_stock_item i','o.id=i.out_stock_id')->where($where)->sum('out_stock_num');
        $stock_consume_num = $order_sales_num+$out_stock_num;
        //期初实时库存
        $start_stock_where = [];
        $start_stock_where[] = ['exp', Db::raw("DATE_FORMAT(createtime, '%Y-%m-%d') = '" . $createat[0] . "'")];
        $start_stock = Db::table('fa_product_allstock_log')->where($start_stock_where)->value('realtime_stock');
        //期末实时库存
        $end_stock_where = [];
        $end_stock_where[] = ['exp', Db::raw("DATE_FORMAT(createtime, '%Y-%m-%d') = '" . $createat[3] . "'")];
        $end_stock = Db::table('fa_product_allstock_log')->where($end_stock_where)->value('realtime_stock');
        $sum = $start_stock+$end_stock;
        //库存周转率
        $arr['turnover_rate'] = $sum ? round($stock_consume_num/$sum/2,2) : 0;
        /*
         * 库存精度
         * 库存精度：（最新的盘点单）（该盘点单内盘点的总数量-盘点误差数）/盘点的总数量
         * */
        $inv_where['is_del'] = 1;
        $inv_where['check_status'] = 2;
        $id = $this->inventory->where($inv_where)->order('id desc')->value('id');
        $inventory_count = $this->inventoryitem->where('inventory_id',$id)->sum('inventory_qty');
        $inventory_error_count = $this->inventoryitem->where('inventory_id',$id)->field('sum(ABS(error_qty)) as count')->select();
        $inventory_error_count = $inventory_error_count[0]['count'];
        $arr['stock_accuracy'] = $inventory_count ? round(($inventory_count-$inventory_error_count)/$inventory_count,2) : 0;
        /*
         * SKU库存精度：（最新的盘点单）该盘点单内有误差SKU/该盘点单总盘点的SKU
         * */
        $error_sku = $this->inventoryitem->where('inventory_id',$id)->where('error_qty','<>',0)->count();
        $sku_count = $this->inventoryitem->where('inventory_id',$id)->count();
        $arr['sku_accuracy'] = $sku_count ? round($error_sku/$sku_count,2) : 0;
        /*
         * 库销比：实时库存数量/所选时间段内销售数量
         * 实时库存 = 总库存-配货占用
         * */
        //实时库存
        $real_time_stock = $this->model->where('category_id','<>',43)->where('is_del',1)->where('is_open',1)->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库销比
        $arr['stock_sales_rate'] = $order_sales_num ? round($real_time_stock/$order_sales_num,2) : 0;
        /*
         * 缺货率：缺货次数（每仓库工单镜框缺货问题类型工单算一次）/订单总副数
         * */
        $work_order_where['problem_type_id'] = 26;
        $work_order_where['work_status'] = ['<>',0];
        //缺货次数
        $stockout_num = $this->worklist->where($work_order_where)->count();
        //订单总副数
        $order_sum_num = $this->order->alias('o')->join('fa_order_item_option i','o.entity_id=i.order_id')->where($order_where)->sum('i.qty');
        //缺货率
        $arr['stockout_rate'] = $order_sum_num ? round($stockout_num/$order_sum_num,2) : 0;

        /*
         * 库存周转天数：所选时间段的天数/库存周转率
         * */
        //库存周转天数
        $days = round(($end - $start) / 3600 / 24);
        $arr['turnover_days_rate'] = $arr['turnover_rate'] ? round($days/$arr['turnover_rate']) : 0;
        /*
         * 月进销比:（所选时间包含的月份整月）月度已审核采购单采购的数量/月度销售数量（订单、批发出库、亚马逊出库）
         * */
        $month_start=date('Y-m-01',$start);
        $month_end_first = date('Y-m-01', $end);
        $month_end=date('Y-m-d 23:59:59',strtotime("$month_end_first +1 month -1 day"));
        $month_start_time = strtotime($month_start);
        $month_end_time = strtotime($month_end);
        $time_where['createtime'] = ['between', [$month_start, $month_end]];
        $order_time_where['payment_time'] = ['between', [$month_start_time, $month_end_time]];
        $purchase_where['purchase_status'] = ['>=',2];
        $purchase_where['is_del'] = 1;
        //（所选时间包含的月份整月）月度已审核采购单采购的数量--暂时使用的是采购单创建时间
        $purchase_num = $this->purchase->where($purchase_where)->where($time_where)->count();
        //月度销售数量
        $month_sales_num1 = $this->order->alias('o')->join('fa_order_item_option i','o.entity_id=i.order_id')->where($order_where)->where($order_time_where)->sum('i.qty');
        $month_sales_num2 = $this->outstock->alias('o')->join('fa_out_stock_item i','o.id=i.out_stock_id','left')->where($time_where)->where('o.platform_id','in','5,8')->where('status',2)->sum('i.out_stock_num');
        $month_sales_num = $month_sales_num1+$month_sales_num2;
        //月进销比
        $arr['month_in_out_rate'] = $month_sales_num ? round($purchase_num/$month_sales_num,2) : 0;
        Cache::set('Supplydatacenter_datamarket'  .$time_str. md5(serialize('stock_measure_overview')), $arr, 7200);
        return $arr;
    }
    //仓库指标总览 -- 和站点有关指标
    public function stock_measure_overview_platform(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $time_str = $params['time_str'] ? $params['time_str'] : '';
            if(!$params['time_str']){
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start .' - '.$end;
            }
            $cache_data = Cache::get('Supplydatacenter_datamarket'  .$order_platform.$time_str. md5(serialize('stock_measure_overview_platform')));
            if (!$cache_data) {
                /*
             * 虚拟仓库存周转率：时间段内所选站点虚拟仓库存消耗数量/[（该站点虚拟仓期初实时库存+该站点虚拟仓期末实时库存）/2]；
             * 虚拟仓库存消耗数量指该站点订单销售数量、该站点出库单出库数量
             * */
                $createat = explode(' ', $time_str);
                $where['createtime'] = ['between', [$createat[0], $createat[3]]];
                $where['platform_id'] = $order_platform;
                $where['status'] = 2;
                $start = strtotime($createat[0]);
                $end = strtotime($createat[3]);
                $order_where['payment_time'] = ['between', [$start, $end]];  //修改
                $order_where['order_type'] = ['<>', 5];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
                $order_where['o.site'] = $order_platform;
                //站点订单销售数量
                $order_sales_num = $this->order->alias('o')->join('fa_order_item_option i','o.entity_id=i.order_id')->where($order_where)->sum('i.qty');
                //站点出库单出库数量
                $out_stock_num = $this->outstock->alias('o')->join('fa_out_stock_item i','o.id=i.out_stock_id')->where($where)->sum('out_stock_num');
                $stock_consume_num = $order_sales_num+$out_stock_num;
                //站点虚拟仓期初实时库存
                $start_stock_where = [];
                $start_stock_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $createat[0] . "'")];
                $start_stock = Db::table('fa_datacenter_day')->where($start_stock_where)->where('site',$order_platform)->value('virtual_stock');
                //站点虚拟仓期末实时库存
                $end_stock_where = [];
                $end_stock_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $createat[3] . "'")];
                $end_stock = Db::table('fa_datacenter_day')->where($end_stock_where)->where('site',$order_platform)->value('virtual_stock');
                $sum = $start_stock+$end_stock;
                //虚拟仓库存周转率
                $arr['virtual_turnover_rate'] = $sum ? round($stock_consume_num/$sum/2,2) : 0;
                /*
                 * 虚拟仓库存周转天数：所选时间段的天数/库存周转率
                 * */
                //库存周转天数
                $days = round(($end - $start) / 3600 / 24);
                $arr['virtual_turnover_days_rate'] = $arr['virtual_turnover_rate'] ? round($days/$arr['virtual_turnover_rate']) : 0;
                /*
                 * 虚拟仓月度进销比：（所选时间包含的月份整月）所选站点月度虚拟仓入库数量/站点虚拟仓月度销售数量（订单、出库）
                 * */
                $month_start=date('Y-m-01',$start);
                $month_end_first = date('Y-m-01', $end);
                $month_end=date('Y-m-d 23:59:59',strtotime("$month_end_first +1 month -1 day"));
                $start_time = strtotime($month_start);
                $end_time = strtotime($month_end);
                $time_where['createtime'] = ['between', [$month_start, $month_end]];
                $order_where['payment_time'] = ['between', [$start_time, $end_time]];
                $instock_where['platform_id'] = $order_platform;
                $instock_where['status'] = 2;
                //（所选时间包含的月份整月）所选站点月度虚拟仓入库数量
                $instock_num = $this->instock->alias('o')->join('fa_in_stock_item i','o.id=i.in_stock_id','left')->where($instock_where)->where($time_where)->sum('i.in_stock_num');
                //月度销售数量
                $month_sales_num1 = $this->order->alias('o')->join('fa_order_item_option i','o.entity_id=i.order_id')->where($order_where)->sum('i.qty');
                $month_sales_num2 = 0;
                if(in_array($order_platform,[5,8])){
                    $outstock_where['platform_id'] = $order_platform;
                    $outstock_where['status'] = 2;
                    $month_sales_num2 = $this->outstock->alias('o')->join('fa_out_stock_item i','o.id=i.out_stock_id','left')->where($time_where)->where($outstock_where)->sum('i.out_stock_num');
                }
                $month_sales_num = $month_sales_num1+$month_sales_num2;
                //虚拟仓月度进销比
                $arr['virtual_month_in_out_rate'] = $month_sales_num ? round($instock_num/$month_sales_num,2) : 0;
                Cache::set('Supplydatacenter_datamarket'  .$order_platform.$time_str. md5(serialize('stock_measure_overview_platform')), $arr, 7200);
            }else{
                $arr = $cache_data;
            }
            $this->success('', '', $arr);
        }
    }
    //库存分级概况
    public function stock_level_overview(){
        $cache_data = Cache::get('Supplydatacenter_datamarket'  . md5(serialize('stock_level_overview')));
        if ($cache_data) {
            return $cache_data;
        }
        $gradeSkuStock = $this->productGrade->getSkuStock();
        //计算产品等级的数量
        $arr = array(
            'a1_count'=>$this->productGrade->where('grade','A+')->count(),
            'a1_stock_num'=>$gradeSkuStock['aa_stock_num'],
            'a1_stock_price'=>$gradeSkuStock['aa_stock_price'],
            'a1_turnover_day'=>120,

            'a_count'=>$this->productGrade->where('grade','A')->count(),
            'a_stock_num'=>$gradeSkuStock['a_stock_num'],
            'a_stock_price'=>$gradeSkuStock['a_stock_price'],
            'a_turnover_day'=>120,

            'b_count'=>$this->productGrade->where('grade','B')->count(),
            'b_stock_num'=>$gradeSkuStock['b_stock_num'],
            'b_stock_price'=>$gradeSkuStock['b_stock_price'],
            'b_turnover_day'=>120,

            'c1_count'=>$this->productGrade->where('grade','C+')->count(),
            'c1_stock_num'=>$gradeSkuStock['ca_stock_num'],
            'c1_stock_price'=>$gradeSkuStock['ca_stock_price'],
            'c1_turnover_day'=>120,

            'c_count'=>$this->productGrade->where('grade','C')->count(),
            'c_stock_num'=>$gradeSkuStock['c_stock_num'],
            'c_stock_price'=>$gradeSkuStock['c_stock_price'],
            'c_turnover_day'=>120,

            'd_count'=>$this->productGrade->where('grade','D')->count(),
            'd_stock_num'=>$gradeSkuStock['d_stock_num'],
            'd_stock_price'=>$gradeSkuStock['d_stock_price'],
            'd_turnover_day'=>120,

            'e_count'=>$this->productGrade->where('grade','E')->count(),
            'e_stock_num'=>$gradeSkuStock['e_stock_num'],
            'e_stock_price'=>$gradeSkuStock['e_stock_price'],
            'e_turnover_day'=>120,

            'f_count'=>$this->productGrade->where('grade','F')->count(),
            'f_stock_num'=>$gradeSkuStock['f_stock_num'],
            'f_stock_price'=>$gradeSkuStock['f_stock_price'],
            'f_turnover_day'=>120,
        );
        //获取呆滞库存信息
        $dull_stock = $this->getProductTurnoverDays();

        $all_num = $arr['a1_count']+$arr['a_count']+$arr['b_count']+$arr['c1_count']+$arr['c_count']+$arr['d_count']+$arr['e_count']+$arr['f_count'];
        $all_stock_num = $arr['a1_stock_num']+$arr['a_stock_num']+$arr['b_stock_num']+$arr['c1_stock_num']+$arr['c_stock_num']+$arr['d_stock_num']+$arr['e_stock_num']+$arr['f_stock_num'];
        $arr['a1_percent'] = $all_num ? round($arr['a1_count']/$all_num*100,2).'%':0;
        $arr['a1_stock_percent'] = $all_stock_num ? round($arr['a1_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['a1_dull_stock'] = $dull_stock['A+']['stock'];   //呆滞库存
        $arr['a1_dull_stock_rate'] =$arr['a1_stock_num'] ? round($arr['a1_dull_stock']/$arr['a1_stock_num'],2).'%':0;
        $arr['a1_dull_total'] = $dull_stock['A+']['total'];   //呆滞金额

        $arr['a_percent'] = $all_num ? round($arr['a_count']/$all_num*100,2).'%':0;
        $arr['a_stock_percent'] = $all_stock_num ? round($arr['a_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['a_dull_stock'] = $dull_stock['A']['stock'];   //呆滞库存
        $arr['a_dull_stock_rate'] = $arr['a_stock_num'] ? round($arr['a_dull_stock']/$arr['a_stock_num'],2).'%':0;
        $arr['a_dull_total'] = $dull_stock['A']['total'];   //呆滞金额

        $arr['b_percent'] = $all_num ? round($arr['b_count']/$all_num*100,2).'%':0;
        $arr['b_stock_percent'] = $all_stock_num ? round($arr['b_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['b_dull_stock'] = $dull_stock['B']['stock'];   //呆滞库存
        $arr['b_dull_stock_rate'] = $arr['b_stock_num'] ? round($arr['b_dull_stock']/$arr['b_stock_num'],2).'%':0;
        $arr['b_dull_total'] = $dull_stock['B']['total'];   //呆滞金额

        $arr['c1_percent'] = $all_num ? round($arr['c1_count']/$all_num*100,2).'%':0;
        $arr['c1_stock_percent'] = $all_stock_num ? round($arr['c1_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['c1_dull_stock'] = $dull_stock['C+']['stock'];   //呆滞库存
        $arr['c1_dull_stock_rate'] = $arr['c1_stock_num'] ? round($arr['c1_dull_stock']/$arr['c1_stock_num'],2).'%':0;
        $arr['c1_dull_total'] = $dull_stock['C+']['total'];   //呆滞金额

        $arr['c_percent'] = $all_num ? round($arr['c_count']/$all_num*100,2).'%':0;
        $arr['c_stock_percent'] = $all_stock_num ? round($arr['c_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['c_dull_stock'] = $dull_stock['C']['stock'];   //呆滞库存
        $arr['c_dull_stock_rate'] = $arr['c_stock_num'] ? round($arr['c_dull_stock']/$arr['c_stock_num'],2).'%':0;
        $arr['c_dull_total'] = $dull_stock['C']['total'];   //呆滞金额

        $arr['d_percent'] = $all_num ? round($arr['d_count']/$all_num*100,2).'%':0;
        $arr['d_stock_percent'] = $all_stock_num ? round($arr['d_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['d_dull_stock'] = $dull_stock['D']['stock'];   //呆滞库存
        $arr['d_dull_stock_rate'] = $arr['d_stock_num'] ? round($arr['d_dull_stock']/$arr['d_stock_num'],2).'%':0;
        $arr['d_dull_total'] = $dull_stock['D']['total'];   //呆滞金额

        $arr['e_percent'] = $all_num ? round($arr['e_count']/$all_num*100,2).'%':0;
        $arr['e_stock_percent'] = $all_stock_num ? round($arr['e_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['e_dull_stock'] = $dull_stock['E']['stock'];   //呆滞库存
        $arr['e_dull_stock_rate'] = $arr['e_stock_num'] ? round($arr['e_dull_stock']/$arr['e_stock_num'],2).'%':0;
        $arr['e_dull_total'] = $dull_stock['E']['total'];   //呆滞金额

        $arr['f_percent'] = $all_num ? round($arr['f_count']/$all_num*100,2).'%':0;
        $arr['f_stock_percent'] = $all_stock_num ? round($arr['f_stock_num']/$all_stock_num*100,2).'%':0;
        $arr['f_dull_stock'] = $dull_stock['F']['stock'];   //呆滞库存
        $arr['f_dull_stock_rate'] = $arr['f_stock_num'] ? round($arr['f_dull_stock']/$arr['f_stock_num'],2).'%':0;
        $arr['f_dull_total'] = $dull_stock['F']['total'];   //呆滞金额
        Cache::set('Supplydatacenter_datamarket'.md5(serialize('stock_level_overview')),$arr,7200);
        return $arr;
    }
    //库存分级概况
    public function stock_level_overview2(){
        $cache_data = Cache::get('Supplydatacenter_datamarket'  . md5(serialize('stock_level_overview2')));
        if ($cache_data) {
            return $cache_data;
        }
        $gradeSkuStock = $this->productGrade->getSkuStock();
        //计算产品等级的数量
        $a1_stock_num = $gradeSkuStock['aa_stock_num'];
        $a_stock_num = $gradeSkuStock['a_stock_num'];
        $b_stock_num = $gradeSkuStock['b_stock_num'];
        $c1_stock_num = $gradeSkuStock['ca_stock_num'];
        $c_stock_num = $gradeSkuStock['c_stock_num'];
        $d_stock_num = $gradeSkuStock['d_stock_num'];
        $e_stock_num = $gradeSkuStock['e_stock_num'];
        $f_stock_num = $gradeSkuStock['f_stock_num'];

        //获取呆滞库存信息
        $dull_stock = $this->getProductTurnoverDays();
        $dull_stock_rate1 = $a1_stock_num ? round($dull_stock['A+']['stock']/$a1_stock_num,2).'%' : 0;
        $dull_stock_rate2 = $a_stock_num ? round($dull_stock['A']['stock']/$a_stock_num,2).'%' : 0;
        $dull_stock_rate3 = $b_stock_num ? round($dull_stock['B']['stock']/$b_stock_num,2).'%' : 0;
        $dull_stock_rate4 = $c1_stock_num ? round($dull_stock['C+']['stock']/$c1_stock_num,2).'%' : 0;
        $dull_stock_rate5 = $c_stock_num ? round($dull_stock['C']['stock']/$c_stock_num,2).'%' : 0;
        $dull_stock_rate6 = $d_stock_num ? round($dull_stock['D']['stock']/$d_stock_num,2).'%' : 0;
        $dull_stock_rate7 = $e_stock_num ? round($dull_stock['E']['stock']/$e_stock_num,2).'%' : 0;
        $dull_stock_rate8 = $f_stock_num ? round($dull_stock['F']['stock']/$f_stock_num,2).'%' : 0;
        //计算产品等级的数量
        $arr = array(
            array(
                'grade'=>'A+',
                'dull_stock'=>$dull_stock['A+']['stock'],
                'rate'=>$dull_stock_rate1,
                'total'=>$dull_stock['A+']['total'],
                'high_dull_stock'=>$dull_stock['A+']['stock3'],
                'high_dull_total'=>$dull_stock['A+']['total3'],
                'center_dull_stock'=>$dull_stock['A+']['stock2'],
                'center_dull_total'=>$dull_stock['A+']['total2'],
                'low_dull_stock'=>$dull_stock['A+']['stock1'],
                'low_dull_total'=>$dull_stock['A+']['total1'],
            ),
            array(
                'grade'=>'A',
                'dull_stock'=>$dull_stock['A']['stock'],
                'rate'=>$dull_stock_rate2,
                'total'=>$dull_stock['A']['total'],
                'high_dull_stock'=>$dull_stock['A']['stock3'],
                'high_dull_total'=>$dull_stock['A']['total3'],
                'center_dull_stock'=>$dull_stock['A']['stock2'],
                'center_dull_total'=>$dull_stock['A']['total2'],
                'low_dull_stock'=>$dull_stock['A']['stock1'],
                'low_dull_total'=>$dull_stock['A']['total1'],
            ),
            array(
                'grade'=>'B',
                'dull_stock'=>$dull_stock['B']['stock'],
                'rate'=>$dull_stock_rate3,
                'total'=>$dull_stock['B']['total'],
                'high_dull_stock'=>$dull_stock['B']['stock3'],
                'high_dull_total'=>$dull_stock['B']['total3'],
                'center_dull_stock'=>$dull_stock['B']['stock2'],
                'center_dull_total'=>$dull_stock['B']['total2'],
                'low_dull_stock'=>$dull_stock['B']['stock1'],
                'low_dull_total'=>$dull_stock['B']['total1'],
            ),
            array(
                'grade'=>'C+',
                'dull_stock'=>$dull_stock['C+']['stock'],
                'rate'=>$dull_stock_rate4,
                'total'=>$dull_stock['C+']['total'],
                'high_dull_stock'=>$dull_stock['C+']['stock3'],
                'high_dull_total'=>$dull_stock['C+']['total3'],
                'center_dull_stock'=>$dull_stock['C+']['stock2'],
                'center_dull_total'=>$dull_stock['C+']['total2'],
                'low_dull_stock'=>$dull_stock['C+']['stock1'],
                'low_dull_total'=>$dull_stock['C+']['total1'],
            ),
            array(
                'grade'=>'C',
                'dull_stock'=>$dull_stock['C']['stock'],
                'rate'=>$dull_stock_rate5,
                'total'=>$dull_stock['C']['total'],
                'high_dull_stock'=>$dull_stock['C']['stock3'],
                'high_dull_total'=>$dull_stock['C']['total3'],
                'center_dull_stock'=>$dull_stock['C']['stock2'],
                'center_dull_total'=>$dull_stock['C']['total2'],
                'low_dull_stock'=>$dull_stock['C']['stock1'],
                'low_dull_total'=>$dull_stock['C']['total1'],
            ),
            array(
                'grade'=>'D',
                'dull_stock'=>$dull_stock['D']['stock'],
                'rate'=>$dull_stock_rate6,
                'total'=>$dull_stock['D']['total'],
                'high_dull_stock'=>$dull_stock['D']['stock3'],
                'high_dull_total'=>$dull_stock['D']['total3'],
                'center_dull_stock'=>$dull_stock['D']['stock2'],
                'center_dull_total'=>$dull_stock['D']['total2'],
                'low_dull_stock'=>$dull_stock['D']['stock1'],
                'low_dull_total'=>$dull_stock['D']['total1'],
            ),
            array(
                'grade'=>'E',
                'dull_stock'=>$dull_stock['E']['stock'],
                'rate'=>$dull_stock_rate7,
                'total'=>$dull_stock['E']['total'],
                'high_dull_stock'=>$dull_stock['E']['stock3'],
                'high_dull_total'=>$dull_stock['E']['total3'],
                'center_dull_stock'=>$dull_stock['E']['stock2'],
                'center_dull_total'=>$dull_stock['E']['total2'],
                'low_dull_stock'=>$dull_stock['E']['stock1'],
                'low_dull_total'=>$dull_stock['E']['total1'],
            ),
            array(
                'grade'=>'F',
                'dull_stock'=>$dull_stock['F']['stock'],
                'rate'=>$dull_stock_rate8,
                'total'=>$dull_stock['F']['total'],
                'high_dull_stock'=>$dull_stock['F']['stock3'],
                'high_dull_total'=>$dull_stock['F']['total3'],
                'center_dull_stock'=>$dull_stock['F']['stock2'],
                'center_dull_total'=>$dull_stock['F']['total2'],
                'low_dull_stock'=>$dull_stock['F']['stock1'],
                'low_dull_total'=>$dull_stock['F']['total1'],
            ),
        );
        Cache::set('Supplydatacenter_datamarket'.md5(serialize('stock_level_overview2')),$arr,7200);
        return $arr;
    }
    //库龄概况
    public function stock_age_overview(){
        $cache_data = Cache::get('Supplydatacenter_datamarket' . md5(serialize('stock_age_overview')));
        if ($cache_data) {
            return $cache_data;
        }
        $where['library_status'] = 1;
        $stock = $this->item->where($where)->where('in_stock_time is not null')->count();
        $count = $this->item->where($where)->where('in_stock_time is not null')->count('distinct sku');
        //sku数量
        $sql1 = $this->item->where($where)->where('in_stock_time is not null')->field('distinct sku')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("sku in " . $sql1)];

        $sql2 = $this->item->alias('t1')->field('TIMESTAMPDIFF( MONTH, min(in_stock_time), now()) AS total')->where($where)->where($arr_where)->where('in_stock_time is not null')->group('sku')->buildSql();

        $count_info = $this->item->table([$sql2=>'t2'])->field('sum(IF( total>= 0 AND total< 4, 1, 0 )) AS a,sum(IF( total>= 4 AND total< 7, 1, 0 )) AS b,sum(IF( total>= 7 AND total< 10, 1, 0 )) AS c,sum(IF( total>= 10 AND total< 13, 1, 0 )) AS d')->select();

        $data1 = $count_info[0]['a'];
        $data2 = $count_info[0]['b'];
        $data3 = $count_info[0]['c'];
        $data4 = $count_info[0]['d'];
        $data5 = $count - $data1 - $data2 - $data3 - $data4;
        //库存
        $sql3 = $this->item->where($where)->where('in_stock_time is not null')->field('distinct sku')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("sku in " . $sql3)];

        $sql4 = $this->item->alias('t1')->field('TIMESTAMPDIFF( MONTH, min(in_stock_time), now()) AS total,count(*) count')->where($where)->where($arr_where)->where('in_stock_time is not null')->group('sku')->buildSql();

        $stock_info = $this->item->table([$sql4=>'t2'])->field('sum(IF( total>= 0 AND total< 4, count, 0 )) AS a,sum(IF( total>= 4 AND total< 7, count, 0 )) AS b,sum(IF( total>= 7 AND total< 10, count, 0 )) AS c,sum(IF( total>= 10 AND total< 13, count, 0 )) AS d')->select();
        $stock1 = $stock_info[0]['a'];
        $stock2 = $stock_info[0]['b'];
        $stock3 = $stock_info[0]['c'];
        $stock4 = $stock_info[0]['d'];
        $stock5 = $stock - $stock1 - $stock2 - $stock3 - $stock4;

        $total = $this->item->alias('i')->join('fa_purchase_order_item oi','i.purchase_id=oi.purchase_id and i.sku=oi.sku')->join('fa_purchase_order o','o.id=i.purchase_id')->where($where)->where('in_stock_time is not null')->value('SUM(IF(actual_purchase_price,actual_purchase_price,o.purchase_total/purchase_num)) price');

        $sql5 = $this->item->where($where)->where('in_stock_time is not null')->field('distinct sku')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("i.sku in " . $sql5)];

        $sql6 = $this->item->alias('i')->join('fa_purchase_order_item oi','i.purchase_id=oi.purchase_id and i.sku=oi.sku')->join('fa_purchase_order o','o.id=i.purchase_id')->field('TIMESTAMPDIFF( MONTH, min(in_stock_time), now()) AS total,SUM(IF(actual_purchase_price,actual_purchase_price,o.purchase_total/purchase_num)) price')->where($where)->where($arr_where)->where('in_stock_time is not null')->group('i.sku')->buildSql();

        $total_info = $this->item->table([$sql6=>'t2'])->field('sum(IF( total>= 0 AND total< 4, price, 0 )) AS a,sum(IF( total>= 4 AND total< 7, price, 0 )) AS b,sum(IF( total>= 7 AND total< 10, price, 0 )) AS c,sum(IF( total>= 10 AND total< 13, price, 0 )) AS d')->select();
        $total1 = round($total_info[0]['a'],2);
        $total2 = round($total_info[0]['b'],2);
        $total3 = round($total_info[0]['c'],2);
        $total4 = round($total_info[0]['d'],2);

        $total5 = round(($total - $total1 - $total2 - $total3 - $total4),2);

        $percent1 = $count ? round($data1/$count*100,2) : 0;
        $percent2 = $count ? round($data2/$count*100,2) : 0;
        $percent3 = $count ? round($data3/$count*100,2) : 0;
        $percent4 = $count ? round($data4/$count*100,2) : 0;
        $percent5 = $count ? round($data5/$count*100,2) : 0;

        $stock_percent1 = $stock ? round($stock1/$stock*100,2) : 0;
        $stock_percent2 = $stock ? round($stock2/$stock*100,2) : 0;
        $stock_percent3 = $stock ? round($stock3/$stock*100,2) : 0;
        $stock_percent4 = $stock ? round($stock4/$stock*100,2) : 0;
        $stock_percent5 = $stock ? round($stock5/$stock*100,2) : 0;

        $arr = array(
            array(
                'title'=>'0~3月',
                'count'=>$data1,
                'percent'=>$percent1,
                'stock'=>$stock1,
                'stock_percent' => $stock_percent1,
                'total'=>$total1
            ),
            array(
                'title'=>'4~6月',
                'count'=>$data2,
                'percent'=>$percent2,
                'stock'=>$stock2,
                'stock_percent' => $stock_percent2,
                'total'=>$total2
            ),
            array(
                'title'=>'7~9月',
                'count'=>$data3,
                'percent'=>$percent3,
                'stock'=>$stock3,
                'stock_percent' => $stock_percent3,
                'total'=>$total3
            ),
            array(
                'title'=>'10~12月',
                'count'=>$data4,
                'percent'=>$percent4,
                'stock'=>$stock4,
                'stock_percent' => $stock_percent4,
                'total'=>$total4
            ),
            array(
                'title'=>'12个月以上',
                'count'=>$data5,
                'percent'=>$percent5,
                'stock'=>$stock5,
                'stock_percent' => $stock_percent5,
                'total'=>$total5
            ),
        );
        Cache::set('Supplydatacenter_datamarket'.md5(serialize('stock_age_overview')),$arr,7200);
        return $arr;
    }
    //采购ajax
    public function purchase_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if(!$params['time_str']){
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start.' - '.$end;
            }else{
                $time_str = $params['time_str'];
            }
            //采购概况
            $purchase_overview = $this->purchase_overview($time_str);
            $this->success('', '', $purchase_overview);
        }
    }
    //采购总览
    public function purchase_overview($time_str = ''){
        if(!$time_str){
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' - '.$end;
        }
        $cache_data = Cache::get('Supplydatacenter_datamarket'  .$time_str. md5(serialize('purchase_overview')));
        if ($cache_data) {
            return $cache_data;
        }
        $createat = explode(' ', $time_str);
        $where['p.createtime'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        $where['p.is_del'] = 1;
        $status_where['p.purchase_status'] = ['in', [2, 5, 6, 7,8,9,10]];
        $arrive_where['p.purchase_status'] = 7;
        //采购总数
        $arr['purchase_num'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('b.purchase_num');
        $arr['purchase_num_now'] = $this->purchase->alias('p')->where($where)->where($status_where)->where('p.type',1)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('b.purchase_num');  //现货
        $arr['purchase_num_big'] = $this->purchase->alias('p')->where($where)->where($status_where)->where('p.type',2)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('b.purchase_num');  //大货
        //采购总金额
        $arr['purchase_amount'] = $this->purchase->alias('p')->where($where)->where($status_where)->sum('purchase_total');
        $arr['purchase_amount_now'] = $this->purchase->alias('p')->where($where)->where($status_where)->where('p.type',1)->sum('purchase_total');//现货
        $arr['purchase_amount_big'] = $this->purchase->alias('p')->where($where)->where($status_where)->where('p.type',2)->sum('purchase_total');//大货
        //采购总SKU数
        $arr['purchase_sku_num'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->group('sku')->count(1);
        $arr['purchase_sku_num_now'] = $this->purchase->alias('p')->where($where)->where($status_where)->where('p.type',1)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->group('sku')->count(1);  //现货
        $arr['purchase_sku_num_big'] = $this->purchase->alias('p')->where($where)->where($status_where)->where('p.type',2)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->group('sku')->count(1);  //大货
        //所选时间短内到货总批次
        $sum_batch = $this->purchase->alias('p')->join('fa_purchase_batch b','p.id=b.purchase_id','left')->where($where)->where($arrive_where)->count();
        //所选时间内到货的采购单延迟的批次
        $delay_batch = $this->purchase->alias('p')->join('fa_purchase_batch b','p.id=b.purchase_id','left')->join('fa_logistics_info l','p.id=l.purchase_id','left')->where($where)->where($arrive_where)->where('p.arrival_time<l.sign_time')->count();
        //采购批次到货延时率
        $arr['purchase_delay_rate'] = $sum_batch ? round($delay_batch/$sum_batch*100,2).'%' : 0;
        //所选时间内到货的采购单合格率90%以上的批次
        $qualified_num = $this->purchase->alias('p')->join('fa_check_order o','p.id = o.purchase_id','left')->join('fa_check_order_item i','o.id = i.check_id','left')->where($where)->where($arrive_where)->group('p.id')->having('sum( quantity_num )/ sum( arrivals_num )>= 0.9')->count();
        //采购批次到货合格率
        $arr['purchase_qualified_rate'] = $sum_batch ? round($qualified_num/$sum_batch*100,2).'%' : 0;
        //采购单价
        $arr['purchase_price'] = $arr['purchase_num'] ? round($arr['purchase_amount']/$arr['purchase_num'],2) : 0;
        $arr['purchase_price_now'] = $arr['purchase_num_now'] ? round($arr['purchase_amount_now']/$arr['purchase_num_now'],2) : 0;  //现货
        $arr['purchase_price_big'] = $arr['purchase_num_big'] ? round($arr['purchase_amount_big']/$arr['purchase_num_big'],2) : 0;//大货
        Cache::set('Supplydatacenter_datamarket'.$time_str.md5(serialize('purchase_overview')),$arr,7200);
        return $arr;
    }
    /**
     *  获取指定日期段内每一天的日期
     * @param Date $startdate 开始日期
     * @param Date $enddate 结束日期
     * @return Array
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 16:06:51
     */
    function getDateFromRange($startdate, $enddate)
    {
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 保存每天日期
        $date = array();
        for ($i = 0; $i < $days; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }
        return $date;
    }
    //订单发货及时率
    public function order_histogram_line(){
        if ($this->request->isAjax()) {
            $time_str = input('time_str');
            if (!$time_str) {
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' - ' . $end;
            }
            $cache_data = Cache::get('Supplydatacenter_datamarket'  .$time_str. md5(serialize('purchase_histogram_line')));
            if (!$cache_data) {
                $createat = explode(' ', $time_str);
                $date = $this->getDateFromRange($createat[0],$createat[3]);
                $arr = array();
                foreach ($date as $key=>$value){
                    $arr[$key]['day'] = $value;
                    //查询该时间段的订单
                    $start = strtotime($value);
                    $end = strtotime($value.' 23:59:59');

                    $where['p.delivery_time'] = $flag['payment_time'] = ['between',[$start,$end]];
                    $map1['p.order_prescription_type'] = 1;
                    $map2['p.order_prescription_type'] = 2;
                    $map3['p.order_prescription_type'] = 3;
                    $where['o.status'] = $flag['status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];

                    //订单数量
                    $arr[$key]['order_count'] = $this->order->where($flag)->count();
                    $sql1 = $this->process->alias('p')->join('fa_order o','p.increment_id = o.increment_id')->field('(p.delivery_time-o.payment_time)/3600 AS total')->where($where)->where($map1)->group('p.order_id')->buildSql();
                    $count1 = $this->process->table([$sql1=>'t2'])->value('sum( IF ( total <= 24, 1, 0) ) AS a');

                    $sql2 = $this->process->alias('p')->join('fa_order o','p.increment_id = o.increment_id')->field('(p.delivery_time-o.payment_time)/3600 AS total')->where($where)->where($map2)->group('p.order_id')->buildSql();
                    $count2 = $this->process->table([$sql2=>'t2'])->value('sum( IF ( total <= 72, 1, 0) ) AS a');

                    $sql3 = $this->process->alias('p')->join('fa_order o','p.increment_id = o.increment_id')->field('(p.delivery_time-o.payment_time)/3600 AS total')->where($where)->where($map3)->group('p.order_id')->buildSql();
                    $count3 = $this->process->table([$sql3=>'t2'])->value('sum( IF ( total <= 168, 1, 0) ) AS a');
                    $untimeout_count = $count1 + $count2 + $count3;
                    $arr[$key]['rate'] = $arr[$key]['order_count'] ? round($untimeout_count/$arr[$key]['order_count']*100,2) : 0;
                }
                Cache::set('Supplydatacenter_datamarket'.$time_str.md5(serialize('purchase_histogram_line')),$arr,7200);
            }else{
                $arr = $cache_data;
            }
            //全部采购单
            $barcloumndata = array_column($arr, 'order_count');
            $linecloumndata = array_column($arr, 'rate');

            $json['xColumnName'] = array_column($arr, 'day');
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'data' => $barcloumndata,
                    'name' => '订单数'
                ],
                [
                    'type' => 'line',
                    'data' => $linecloumndata,
                    'name' => '及时率',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    //物流ajax
    public function track_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if(!$params['time_str']){
                $start = date('Y-m-d 00:00:00', strtotime('-30 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start.' - '.$end;
            }else{
                $time_str = $params['time_str'];
            }
            //物流妥投概况
            $logistics_completed_overview = $this->logistics_completed_overview($time_str);
            $this->success('', '', $logistics_completed_overview);
        }
    }
    //物流妥投概况
    public function logistics_completed_overview($time_str = ''){
        if (!$time_str) {
            $start = date('Y-m-d 00:00:00', strtotime('-30 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' - ' . $end;
        }
        $cache_data = Cache::get('Supplydatacenter_userdata'.$time_str.md5(serialize('logistics_completed_overview')));
        if($cache_data){
            return $cache_data;
        }
        $createat = explode(' ', $time_str);

        $start_time = strtotime($createat[0].' '.$createat[1]);
        $end_time = strtotime($createat[3].' '.$createat[4]);
        $where['check_status'] = 1;
        $where['check_time'] = ['between',[$start_time,$end_time]];
        $arr['delivery_count'] = $this->process->where($where)->count();  //发货数量
        $completed_where['is_tracking'] = 5;
        $arr['completed_count'] = $this->process->where($where)->where($completed_where)->count();  //总妥投数量
        $uncompleted_where['is_tracking'] = ['<>',5];
        $arr['uncompleted_count'] = $this->process->where($where)->where($uncompleted_where)->count();  //未妥投数量
        $map = [];
        $map[] = ['exp', Db::raw("check_time+3600*24*15<unix_timestamp(now())")];
        $arr['timeout_uncompleted_count'] = $this->process->where($where)->where($uncompleted_where)->where($map)->count();  //超时未妥投数量
        Cache::set('Supplydatacenter_userdata' . $time_str . md5(serialize('logistics_completed_overview')), $arr, 7200);
        return $arr;
    }
    //妥投时效占比
    public function comleted_time_rate(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $time_str = $params['time_str'] ? $params['time_str'] : '';
            if (!$time_str) {
                $start = date('Y-m-d 00:00:00', strtotime('-30 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' - ' . $end;
            }
            $cache_data = Cache::get('Supplydatacenter_userdata'.$time_str.md5(serialize('comleted_time_rate')));
            if(!$cache_data){
                $createat = explode(' ', $time_str);
                $where['delivery_time'] = ['between',[$createat[0].' '.$createat[1],$createat[3].' '.$createat[4]]];
                $where['node_type'] = 40;
                //总的妥投订单数
                $count = $this->orderNode->where($where)->count();

                $sql2 = $this->orderNode->alias('t1')->field('TIMESTAMPDIFF(DAY,delivery_time,signing_time) AS total')->where($where)->group('order_number')->buildSql();

                $sign_count = $this->orderNode->table([$sql2=>'t2'])->field('sum( IF ( total >= 10 and total<15, 1, 0 ) ) AS c,sum( IF ( total >= 7 and total<10, 1, 0 ) ) AS b,sum( IF ( total >= 0 and total<7, 1, 0 ) ) AS a')->select();

                $data4 = $count - $sign_count[0]['a'] - $sign_count[0]['b'] - $sign_count[0]['c'];
                $data = array(
                    $sign_count[0]['a'],$sign_count[0]['b'],$sign_count[0]['c'],$data4
                );
                Cache::set('Supplydatacenter_userdata' . $time_str . md5(serialize('comleted_time_rate')), $data, 7200);
            }else{
                $data = $cache_data;
            }
            $json['column'] = ['7天妥投率', '10天妥投率','15天妥投率','15天以上妥投率'];
            $json['columnData'] = [
                [
                    'name' => '7天妥投率',
                    'value' => $data[0],
                ],
                [
                    'name' => '10天妥投率',
                    'value' => $data[1],
                ],
                [
                    'name' => '15天妥投率',
                    'value' => $data[2],
                ],
                [
                    'name' => '15天以上妥投率',
                    'value' => $data[3],
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    //导出超时未妥投数量
    public function export_not_shipped(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start = '1611158400';
        $end = '1611244799';

        $where['p.delivery_time'] = ['between',[$start,$end]];
        $where['p.site'] = ['<>',4];

        $where['o.status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $sql1 = $this->process->alias('p')
            ->join('fa_order o','p.increment_id = o.increment_id')
            ->field('p.delivery_time,p.order_prescription_type,o.payment_time,o.increment_id,o.status')
            ->where($where)->group('p.order_id')->select(false);
        dump($sql1);die();
        $list  = collection($sql1)->toArray();
        dump(count($list));die();
        foreach ($list as $key=>$item){
            $va = ($item['delivery_time'] - $item['payment_time'])/3600;
            if ($item['order_prescription_type'] ==1){
                if ($va < 24){
                    unset($key);
                }
            }
            if ($item['order_prescription_type'] ==2){
                if ($va < 72){
                    unset($key);
                }
            }
            if ($item['order_prescription_type'] ==3){
                if ($va < 168){
                    unset($key);
                }
            }
        }
        dump($list);
        dump(count($list));
        die();
//        $sql2 = $this->process->alias('p')
//            ->join('fa_order o','p.increment_id = o.increment_id')
//            ->field('p.delivery_time,o.payment_time,o.increment_id,o.status')
//            ->where($where)->where($map2)->group('p.order_id')->buildSql();
//        $arr2 = $this->process->table([$sql2=>'t2'])
////            ->field('sum( IF ( total > 72, 1, 0) ) AS a,sum( IF ( total <= 72, 1, 0) ) AS b')
//            ->select();
//        $arr2  = collection($arr2)->toArray();
//        $sql3 = $this->process->alias('p')
//            ->join('fa_order o','p.increment_id = o.increment_id')
//            ->field('p.delivery_time,o.payment_time,o.increment_id,o.status')
//            ->where($where)->where($map3)->group('p.order_id')->buildSql();
//        $arr3 = $this->process->table([$sql3=>'t2'])
////            ->field('sum( IF ( total > 168, 1, 0) ) AS a,sum( IF ( total <= 168, 1, 0) ) AS b')
//            ->select();
//        $arr3  = collection($arr3)->toArray();
//        foreach ($arr1 as $key=>$value){
//            $va = ($value['delivery_time'] - $value['payment_time'])/3600;
//            dump($va);die();
//            if ($va<24){
//                unset($key);
//            }
//        }
//        foreach ($arr2 as $key=>$value){
//            $va = ($value['delivery_time'] - $value['payment_time'])/3600;
//            if ($va<72){
//                unset($key);
//            }
//        }
//        foreach ($arr3 as $key=>$value){
//            $va = ($value['delivery_time'] - $value['payment_time'])/3600;
//            if ($va<168){
//                unset($key);
//            }
//        }
        $timeout_count = $arr1[0]['a'] + $arr2[0]['a'] + $arr3[0]['a'];

        dump(count($arr1));
        dump(count($arr2));
        dump(count($arr3));
        die();

        dump($timeout_count);die();

//        $map['b.created_at'] = ['between', [1606752000, 1609430399]];
        $neworderprocess = new \app\admin\model\order\order\NewOrderProcess();
        $undeliveredOrder = $neworderprocess->undeliveredOrder();
        dump($undeliveredOrder);die();
//        $undeliveredOrder = $neworderprocess->undeliveredOrderMessage($map);

        $list = collection($undeliveredOrder)->toArray();
        $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "订单号")
            ->setCellValue("B1", "订单状态")
            ->setCellValue("C1", "下单时间");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "站点")
            ->setCellValue("E1", "是否有工单")
            ->setCellValue("F1", "工单类型")
            ->setCellValue("G1", "创建人");
        foreach ($list as $key => $value) {

            $swhere['platform_order'] = $value['increment_id'];
            $swhere['work_platform'] = 1;
            $swhere['work_status'] = ['not in', [0, 4, 6]];
            $work_type = $workorder->where($swhere)->field('work_type,create_user_name')->find();
            if (!empty($work_type)){
                $value['work'] = '是';
                if ($work_type->work_type == 1){
                    $value['work_status'] = '客服工单';
                }else{
                    $value['work_status'] = '仓库工单';
                }
                $value['create_user_name'] =$work_type->create_user_name;
            }else{
                $value['work'] = '否';
                $value['work_status'] = '无';
                $value['create_user_name'] = '无';
            }
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), date('Y-m-d H:i:s',$value['created_at']));
            switch ($value['site']){
                case 1:
                    $value['site'] = 'zeelool';
                    break;
                case 2:
                    $value['site'] = 'voogueme';
                    break;
                case 3:
                    $value['site'] = 'nihao';
                    break;
                case 4:
                    $value['site'] = 'meeloog';
                    break;
                case 5:
                    $value['site'] = 'wesee';
                    break;
                case 9:
                    $value['site'] = 'zeelool_es';
                    break;
                case 10:
                    $value['site'] = 'zeelool_de';
                    break;
                case 11:
                    $value['site'] = 'zeelool_jp';
                    break;
                case 12:
                    $value['site'] = 'voogmechic';
                    break;
            }

            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['site']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['work']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['work_status']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['create_user_name']);

        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:H' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '物流未发货订单' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');

    }
}
