<?php

namespace app\admin\controller\supplydatacenter;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class DataMarket extends Backend
{
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
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->distributionLog = new \app\admin\model\DistributionLog;
        $this->orderNode = new \app\admin\model\OrderNode;
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $params = $this->request->param();
        if(!$params['time_str']){
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start.' - '.$end;
        }else{
            $time_str = $params['time_str'];
        }


        //库存总览
        $stock_overview = $this->stock_overview();
        //仓库指标总览
        $stock_measure_overview = $this->stock_measure_overview($time_str);
        //库存分级概况
        $stock_level_overview = $this->stock_level_overview($time_str);
        //采购概况
        $purchase_overview = $this->purchase_overview($time_str);


        //物流妥投概况
        //$logistics_completed_overview = $this->logistics_completed_overview($time_str);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        $this->view->assign(compact('stock_overview','stock_measure_overview','stock_level_overview','purchase_overview','logistics_completed_overview','magentoplatformarr'));
        return $this->view->fetch();
    }
    //库存总览
    public function stock_overview(){
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
        return $arr;
    }
    //仓库指标总览
    public function stock_measure_overview($time_str){
        /*
         * 库存周转率：所选时间内库存消耗数量/[（期初实时库存+期末实时库存）/2];
         * 库存消耗数量: 订单销售数量+出库单出库数量
         * */
        $createat = explode(' ', $time_str);
        $where['createtime'] = ['between', [$createat[0], $createat[3]]];
        $where['status'] = 2;
        $start = strtotime($createat[0]);
        $end = strtotime($createat[3]);
        $order_time_where['created_at'] = ['between', [$start, $end]];  //修改
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
        $start_stock = Db::table('fa_product_allstock_log')->where($start_stock_where)->value('allnum');
        //期末实时库存
        $end_stock_where = [];
        $end_stock_where[] = ['exp', Db::raw("DATE_FORMAT(createtime, '%Y-%m-%d') = '" . $createat[3] . "'")];
        $end_stock = Db::table('fa_product_allstock_log')->where($start_stock_where)->value('allnum');
        $sum = $start_stock+$end_stock;
        //库存周转率
        $arr['turnover_rate'] = $sum ? round($stock_consume_num/$sum/2,2) : 0;
        /*
         * 库存精度
         * */


        /*
         * 库销比：实时库存数量/所选时间段内销售数量
         * 实时库存 = 总库存-配货占用
         * */
        //实时库存
        $real_time_stock = $this->model->where('category_id','<>',43)->value('sum(stock)-sum(distribution_occupy_stock) as result');
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
        $days = round(($createat[3] - $createat[0]) / 3600 / 24);
        $arr['turnover_days_rate'] = $arr['turnover_rate'] ? round($days/$arr['turnover_rate']) : 0;
        /*
         * 月进销比:（所选时间包含的月份整月）月度已审核采购单采购的数量/月度销售数量（订单、批发出库、亚马逊出库）
         * */
        $month_start=date('Y-m-01',$start);
        $month_end_first = date('Y-m-01', $end);
        $month_end=date('Y-m-d 23:59:59',strtotime("$month_end_first +1 month -1 day"));
        $time_where['createtime'] = $order_time_where['created_at'] = ['between', [$month_start, $month_end]];
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
            $order_where['created_at'] = ['between', [$start, $end]];  //修改
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
            $end_stock = Db::table('fa_datacenter_day')->where($start_stock_where)->where('site',$order_platform)->value('virtual_stock');
            $sum = $start_stock+$end_stock;
            //虚拟仓库存周转率
            $arr['virtual_turnover_rate'] = $sum ? round($stock_consume_num/$sum/2,2) : 0;
            /*
             * 虚拟仓库存周转天数：所选时间段的天数/库存周转率
             * */
            //库存周转天数
            $days = round(($createat[3] - $createat[0]) / 3600 / 24);
            $arr['virtual_turnover_days_rate'] = $arr['virtual_turnover_rate'] ? round($days/$arr['virtual_turnover_rate']) : 0;
            /*
             * 虚拟仓月度进销比：（所选时间包含的月份整月）所选站点月度虚拟仓入库数量/站点虚拟仓月度销售数量（订单、出库）
             * */
            $month_start=date('Y-m-01',$start);
            $month_end_first = date('Y-m-01', $end);
            $month_end=date('Y-m-d 23:59:59',strtotime("$month_end_first +1 month -1 day"));
            $time_where['createtime'] = $order_where['created_at'] = ['between', [$month_start, $month_end]];
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
            $this->success('', '', $arr);
        }
    }
    //库存分级概况
    public function stock_level_overview($time_str){
        $createat = explode(' ', $time_str);
        $start = strtotime($createat[0]);
        $end = strtotime($createat[3]);
        $gradeSkuStock = $this->productGrade->getSkuStock();
        //计算产品等级的数量
        $arr = array(
            array(
                'grade'=>'A+',
                'count'=>$this->productGrade->where('grade','A+')->count(),
                'stock_num'=>$gradeSkuStock['aa_stock_num'],
                'stock_price'=>$gradeSkuStock['aa_stock_price'],
            ),
            array(
                'grade'=>'A',
                'count'=>$this->productGrade->where('grade','A')->count(),
                'stock_num'=>$gradeSkuStock['a_stock_num'],
                'stock_price'=>$gradeSkuStock['a_stock_price'],
            ),
            array(
                'grade'=>'B',
                'count'=>$this->productGrade->where('grade','B')->count(),
                'stock_num'=>$gradeSkuStock['b_stock_num'],
                'stock_price'=>$gradeSkuStock['b_stock_price'],
            ),
            array(
                'grade'=>'C+',
                'count'=>$this->productGrade->where('grade','C+')->count(),
                'stock_num'=>$gradeSkuStock['ca_stock_num'],
                'stock_price'=>$gradeSkuStock['ca_stock_price'],
            ),
            array(
                'grade'=>'C',
                'count'=>$this->productGrade->where('grade','C')->count(),
                'stock_num'=>$gradeSkuStock['c_stock_num'],
                'stock_price'=>$gradeSkuStock['c_stock_price'],
            ),
            array(
                'grade'=>'D',
                'count'=>$this->productGrade->where('grade','D')->count(),
                'stock_num'=>$gradeSkuStock['d_stock_num'],
                'stock_price'=>$gradeSkuStock['d_stock_price'],
            ),
            array(
                'grade'=>'E',
                'count'=>$this->productGrade->where('grade','E')->count(),
                'stock_num'=>$gradeSkuStock['e_stock_num'],
                'stock_price'=>$gradeSkuStock['e_stock_price'],
            ),
            array(
                'grade'=>'F',
                'count'=>$this->productGrade->where('grade','F')->count(),
                'stock_num'=>$gradeSkuStock['f_stock_num'],
                'stock_price'=>$gradeSkuStock['f_stock_price'],
            ),
        );
        $all_num = 0;
        $all_stock_num = 0;
        foreach ($arr as $value){
            //总数
            $all_num += $value['count'];
            //总库存
            $all_stock_num += $value['stock_num'];
        }
        foreach ($arr as $key=>$val){
            $arr[$key]['percent'] = $all_num ? round($val['count']/$all_num,2).'%':0;
            $arr[$key]['stock_percent'] = $all_stock_num ? round($val['stock_num']/$all_stock_num,2).'%':0;
            /*//库销比
            $where['grade'] = $val['grade'];
            $where['is_del'] = 1;
            $where['category_id'] = ['<>',43];
            $skus = $this->productGrade->where($where)->column('true_sku');
            $where['sku'] = ['in', $skus];
            //实时库存
            $data['aa_stock_num'] = $this->model->where($where)->value('sum(stock)-sum(distribution_occupy_stock) as result');

            //订单销售数量
            $order_time_where['created_at'] = ['between', [$start, $end]];  //修改
            $order_where['order_type'] = ['<>', 5];
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
            $skus = $this->productGrade->where($where)->column('true_sku');
            $order_sales_num = $this->order->alias('o')->join('fa_order_item_option i','o.entity_id=i.order_id')->where($order_where)->where($order_time_where)->sum('i.qty');*/
        }
        return $arr;
    }
    //库龄概况
    public function stock_age_overview(){
        
    }
    //采购总览
    public function purchase_overview($time_str){
        $createat = explode(' ', $time_str);
        $where['p.createtime'] = ['between', [$createat[0], $createat[3]]];
        $where['p.is_del'] = 1;
        $status_where['p.purchase_status'] = ['in', [2, 5, 6, 7]];
        $arrive_where['p.purchase_status'] = 7;
        //采购总数
        $arr['purchase_num'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('b.purchase_num');
        //采购总金额
        $arr['purchase_amount'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->sum('purchase_num*purchase_price');
        //采购总SKU数
        $arr['purchase_sku_num'] = $this->purchase->alias('p')->where($where)->where($status_where)->join(['fa_purchase_order_item' => 'b'], 'p.id=b.purchase_id')->group('sku')->count(1);
        //所选时间短内到货总批次
        $sum_batch = $this->purchase->alias('p')->join('fa_purchase_batch b','p.id=b.purchase_id','left')->where($where)->where($arrive_where)->count();
        //所选时间内到货的采购单延迟的批次
        $delay_batch = $this->purchase->alias('p')->join('fa_purchase_batch b','p.id=b.purchase_id','left')->join('fa_logistics_info l','p.id=l.purchase_id','left')->where($where)->where($arrive_where)->where('p.arrival_time<l.sign_time')->count();
        //采购批次到货延时率
        $arr['purchase_delay_rate'] = $sum_batch ? round($delay_batch/$sum_batch,2).'%' : 0;
        //所选时间内到货的采购单合格率90%以上的批次
        $qualified_num = $this->purchase->alias('p')->join('fa_check_order o','p.id = o.purchase_id','left')->join('fa_check_order_item i','o.id = i.check_id','left')->where($where)->where($arrive_where)->group('p.id')->having('sum( quantity_num )/ sum( arrivals_num )>= 0.9')->count();
        //采购批次到货合格率
        $arr['purchase_qualified_rate'] = $sum_batch ? round($qualified_num/$sum_batch,2).'%' : 0;
        //采购单价
        $arr['purchase_price'] = $arr['purchase_num'] ? round($arr['purchase_amount']/$arr['purchase_num'],2) : 0;
        return $arr;
    }
    //采购概况中的折线图柱状图
    public function purchase_histogram_line(){
        if ($this->request->isAjax()) {
            $time_str = input('time_str');
            if (!$time_str) {
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' - ' . $end;
            }
            $createat = explode(' ', $time_str);
            $where['create_time'] = ['between', [$createat[0], $createat[3]]];
            $list = $this->warehouse_model->where($where)
                ->field('all_purchase_num,create_date,all_purchase_price')
                ->order('create_date asc')
                ->select();
            $warehouse_data = collection($list)->toArray();
            //全部采购单
            $barcloumndata = array_column($warehouse_data, 'all_purchase_num');
            $linecloumndata = array_column($warehouse_data, 'all_purchase_price');

            $json['xColumnName'] = array_column($warehouse_data, 'create_date');
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'data' => $barcloumndata,
                    'name' => '采购数量'
                ],
                [
                    'type' => 'line',
                    'data' => $linecloumndata,
                    'name' => '采购金额',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
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
    //订单发出总览
    public function order_send_overview(){
        if ($this->request->isAjax()) {
            $time_str = input('time_str');
            if (!$time_str) {
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' - ' . $end;
            }
            $createat = explode(' ', $time_str);
            $date = $this->getDateFromRange($createat[0],$createat[3]);
            $arr = array();
            foreach ($date as $key=>$value){
                $arr[$key]['day'] = $value;
                //查询该时间段的订单
                $start = strtotime($value);
                $end = strtotime($value.' 23:59:59');

                $where['p.complete_time'] = ['between',[$start,$end]];
                $map1['p.order_prescription_type'] = 1;
                $map2['p.order_prescription_type'] = 2;
                $map3['p.order_prescription_type'] = 3;
                $sql1 = $this->process->alias('p')->join('fa_order o','p.order_id=o.entity_id')->field('p.complete_time - o.payment_time AS total')->where($where)->where($map1)->group('p.order_id')->buildSql();
                $arr1 = $this->process->table([$sql1=>'t2'])->field('sum( IF ( total > 24, 1, 0) ) AS a,sum( IF ( total <= 24, 1, 0) ) AS b')->select();

                $sql2 = $this->process->alias('p')->join('fa_order o','p.order_id=o.entity_id')->field('p.complete_time - o.payment_time AS total')->where($where)->where($map2)->group('p.order_id')->buildSql();
                $arr2 = $this->process->table([$sql2=>'t2'])->field('sum( IF ( total > 72, 1, 0) ) AS a,sum( IF ( total <= 72, 1, 0) ) AS b')->select();

                $sql3 = $this->process->alias('p')->join('fa_order o','p.order_id=o.entity_id')->field('p.complete_time - o.payment_time AS total')->where($where)->where($map3)->group('p.order_id')->buildSql();
                $arr3 = $this->process->table([$sql3=>'t2'])->field('sum( IF ( total > 168, 1, 0) ) AS a,sum( IF ( total <= 168, 1, 0) ) AS b')->select();
                $timeout_count = $arr1[0]['a'] + $arr2[0]['a'] + $arr3[0]['a'];
                $untimeout_count = $arr1[0]['b'] + $arr2[0]['b'] + $arr3[0]['b'];
                $arr[$key]['timeout_count'] = $timeout_count;
                $arr[$key]['untimeout_count'] = $untimeout_count;
            }
            $json['xColumnName'] = array_column($arr,'day');
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'data' => array_column($arr,'timeout_count'),
                    'name' => '超时订单',
                    'stack'=>'订单'
                ],
                [
                    'type' => 'bar',
                    'data' => array_column($arr,'untimeout_count'),
                    'name' => '未超时订单',
                    'stack'=>'订单'
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    //加工概况
    public function process_overview(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $time_str = $params['time_str'];
            $cache_data = Cache::get('Supplydatacenter_userdata'.$time_str.md5(serialize('process_overview')));
            if(!$cache_data){
                if (!$time_str) {
                    $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                    $end = date('Y-m-d 23:59:59');
                    $time_str = $start . ' - ' . $end;
                }
                $createat = explode(' ', $time_str);

                $start_time = strtotime($createat[0]);
                $end_time = strtotime($createat[3]);
                $data1 = $this->getProcess(1,$start_time,$end_time); //打印标签
                $data2 = $this->getProcess(2,$start_time,$end_time); //配货
                $data3 = $this->getProcess(3,$start_time,$end_time); //配镜片
                $data4 = $this->getProcess(4,$start_time,$end_time); //加工
                $data5 = $this->getProcess(5,$start_time,$end_time); //印logo
                $data6 = $this->getProcess(7,$start_time,$end_time); //合单

                $check_where['check_time'] = $combine_where['combine_time'] = ['between',[$start_time,$end_time]];
                $check_where['check_status'] = 1;
                $combine_where['combine_status'] = 1;
                $data7 = $this->process->where($check_where)->count();     //审单
                $data8 = $this->process->where($combine_where)->count();    //合单

                $arr = array(
                    $data8, $data7, $data6, $data5, $data4, $data3, $data2, $data1
                );
                Cache::set('Supplydatacenter_userdata' . $time_str . md5(serialize('process_overview')), $arr, 36000);
            }else{
                $arr = $cache_data;
            }
            $data = $arr;
            $json['firtColumnName'] = ['发货', '审单', '合单', '印logo', '加工', '配镜片', '配货', '打印标签'];
            $json['columnData'] = [[
                'type' => 'bar',
                'barWidth' => '40%',
                'data' => $data,
                'name' => '加工概况',
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'right',
                            'formatter'=>"{c}"."个",
                            'textStyle'=>[
                                'color'=> 'black'
                            ],
                        ],
                    ]
                ]
            ]];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    //统计子单加工流程数量
    public function getProcess($type,$start,$end){
        $where['create_time'] = ['between',[$start,$end]];
        $where['distribution_node'] = $type;
        return $this->distributionLog->where($where)->count();
    }
    //物流妥投概况
    public function logistics_completed_overview($time_str){
        if (!$time_str) {
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' - ' . $end;
        }
        $createat = explode(' ', $time_str);

        $start_time = strtotime($createat[0]);
        $end_time = strtotime($createat[3]);
        $where['check_status'] = 1;
        $where['check_time'] = ['between',[$start_time,$end_time]];
        $arr['delivery_count'] = $this->process->where($where)->count();  //发货数量
        $completed_where['is_tracking'] = 5;
        $arr['completed_count'] = $this->process->where($where)->where($completed_where)->count();  //总妥投数量
        $uncompleted_where['is_tracking'] = ['<>',5];
        $arr['uncompleted_count'] = $this->process->where($where)->where($uncompleted_where)->count();  //未妥投数量
        $map = [];
        $map[] = ['exp', Db::raw("DATE_ADD(check_time, INTERVAL 15 DAY)<now()")];
        $arr['timeout_uncompleted_count'] = $this->process->where($where)->where($uncompleted_where)->where($map)->count();  //超时未妥投数量
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
            $createat = explode(' ', $time_str);
            $where['delivery_time'] = ['between',[$createat[0],$createat[3]]];
            $where['node_type'] = 40;
            //总的妥投订单数
            $count = $this->orderNode->where($where)->count();

            $sql2 = $this->orderNode->alias('t1')->field('TIMESTAMPDIFF(DAY,delivery_time,signing_time) AS total')->where($where)->group('order_number')->buildSql();

            $sign_count = $this->orderNode->table([$sql2=>'t2'])->field('sum( IF ( total >= 10 and total<15, 1, 0 ) ) AS c,sum( IF ( total >= 7 and total<10, 1, 0 ) ) AS b,sum( IF ( total >= 0 and total<7, 1, 0 ) ) AS a')->select();

            $data1 = $sign_count[0]['a'];
            $data2 = $sign_count[0]['b'];
            $data3 = $sign_count[0]['c'];
            $data4 = $count - $data1 - $data2 - $data3;

            $json['column'] = ['7天妥投率', '10天妥投率','15天妥投率','15天以上妥投率'];
            $json['columnData'] = [
                [
                    'name' => '7天妥投率',
                    'value' => $data1,
                ],
                [
                    'name' => '10天妥投率',
                    'value' => $data2,
                ],
                [
                    'name' => '15天妥投率',
                    'value' => $data3,
                ],
                [
                    'name' => '15天以上妥投率',
                    'value' => $data4,
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
}
