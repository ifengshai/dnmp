<?php

namespace app\admin\controller;

use app\admin\model\order\order\Zeelool;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;

class Test01 extends Backend
{

    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
    }

    public function test01()
    {
        set_time_limit(0);
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku')
            ->where(['platform_type' => 2])
            ->select();
        $sku_data = collection($sku_data)->toArray();
        echo "sku_data:success\n";

        $sku_arr = array_column($sku_data, 'sku');
        $platform = [];
        $grade = [];
        foreach ($sku_data as $value) {
            $grade[$value['sku']] = $value['grade'];
            $platform[$value['sku']] = $value['platform_sku'];
        }

        $_new_product = new \app\admin\model\NewProduct();
        $list = $_new_product
            ->alias('a')
            ->field('sku,frame_color,frame_texture,shape,frame_shape,price')
            ->where(['item_status' => 2, 'is_del' => 1, 'sku' => ['in', $sku_arr]])
            ->join(['fa_new_product_attribute' => 'b'], 'a.id=b.item_id')
            ->select();
        $list = collection($list)->toArray();
        echo "list:success\n";

        /*//从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "SKU")
            ->setCellValue("B1", "产品评级")
            ->setCellValue("C1", "材质")
            ->setCellValue("D1", "框型")
            ->setCellValue("E1", "形状")
            ->setCellValue("F1", "颜色")
            ->setCellValue("G1", "进价")
            ->setCellValue("H1", "平均月销量")
            ->setCellValue("I1", "平均售价")
            ->setCellValue("J1", "最大月销量")
            ->setCellValue("K1", "最大月销量月份")
            ->setCellValue("L1", "201910~202009总销量")
            ->setCellValue("M1", "配镜率")
        ;   //利用setCellValues()填充数据*/

        $frame_texture = [1 => '塑料', 2 => '板材', 3 => 'TR90', 4 => '金属', 5 => '钛', 6 => '尼龙', 7 => '木质', 8 => '混合材质', 9 => '合金', 10 => '其他材质'];
        $frame_shape = [1 => '长方形', 2 => '正方形', 3 => '猫眼', 4 => '圆形', 5 => '飞行款', 6 => '多边形', 7 => '蝴蝶款'];
        $shape = [1 => '全框', 2 => '半框', 3 => '无框'];

        $file_content = '';
        foreach ($list as $key => $value) {
            $statistics = $this->voogueme
                ->alias('a')
                ->field("COUNT(b.item_id) AS num,sum(base_price) as price,DATE_FORMAT(b.created_at, '%Y-%m') AS time")
                ->where(['a.status' => ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing']]])
                ->where(['b.created_at' => ['>=', '2019-10-01 00:00:00']])
                ->where(['b.created_at' => ['<=', '2020-09-30 23:59:59']])
                ->where(['b.sku' => $platform[$value['sku']]])
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id', 'LEFT')
                ->group("time")
                ->select();
            $statistics = collection($statistics)->toArray();
            $ages = array_column($statistics, 'num');
            array_multisort($ages, SORT_DESC, $statistics);

            $all_count = 0;
            foreach ($statistics as $item) {
                $all_count += $item['num'];
            }

            $prescription = $this->voogueme
                ->alias('a')
                ->field("COUNT(b.item_id) AS num")
                ->where(['a.status' => ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing']]])
                ->where(['b.created_at' => ['>=', '2019-10-01 00:00:00']])
                ->where(['b.created_at' => ['<=', '2020-09-30 23:59:59']])
                ->where(['b.product_options' => ['not like', '%frameonly%']])
                ->where(['b.product_options' => ['not like', '%nonprescription%']])
                ->where(['b.sku' => $platform[$value['sku']]])
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id', 'LEFT')
                ->select();
            $prescription = collection($prescription)->toArray();

            $monthly_sales = $all_count > 0 ? $all_count / 12 : 0;
            $average_price = $statistics[0]['price'] > 0 && $statistics[0]['num'] > 0 ? $statistics[0]['price'] / $statistics[0]['num'] : 0;
            $proportion = $all_count > 0 && $statistics[0]['num'] > 0 ? $prescription[0]['num'] / $all_count : 0;

            /*$num = $key + 2;
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A{$num}", $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B{$num}", $grade[$value['sku']]);
            $spreadsheet->getActiveSheet()->setCellValue("C{$num}", $frame_texture[$value['frame_texture']]);
            $spreadsheet->getActiveSheet()->setCellValue("D{$num}", $frame_shape[$value['frame_shape']]);
            $spreadsheet->getActiveSheet()->setCellValue("E{$num}", $shape[$value['shape']]);
            $spreadsheet->getActiveSheet()->setCellValue("F{$num}", $value['frame_color']);
            $spreadsheet->getActiveSheet()->setCellValue("G{$num}", $value['price']);
            $spreadsheet->getActiveSheet()->setCellValue("H{$num}", $monthly_sales);
            $spreadsheet->getActiveSheet()->setCellValue("I{$num}", $average_price);
            $spreadsheet->getActiveSheet()->setCellValue("J{$num}", $statistics[0]['num']);
            $spreadsheet->getActiveSheet()->setCellValue("K{$num}", $statistics[0]['time']);
            $spreadsheet->getActiveSheet()->setCellValue("L{$num}", $all_count);
            $spreadsheet->getActiveSheet()->setCellValue("M{$num}", $proportion);*/

            $arr = [
                $value['sku'],
                $grade[$value['sku']],
                $frame_texture[$value['frame_texture']],
                $frame_shape[$value['frame_shape']],
                $shape[$value['shape']],
                $value['frame_color'],
                $value['price'],
                $monthly_sales,
                $average_price,
                $statistics[0]['num'],
                $statistics[0]['time'],
                $all_count,
                $proportion
            ];
            $file_content = $file_content . implode(',', $arr) . "\n";
            echo "{$value['sku']}:success\n";
        }

        $export_str = ['SKU', '产品评级', '材质', '框型', '形状', '颜色', '进价', '平均月销量', '平均售价', '最大月销量', '最大月销量月份', '201910~202009总销量', '配镜率'];
        $file_title = implode(',', $export_str) . " \n";
        $file = $file_title . $file_content;
        file_put_contents('/www/wwwroot/mojing/runtime/log/analysis.csv', $file);
        exit;

        //设置宽度
        //        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:I' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $save_name = '产品结构分析';

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
        header('Content-Disposition: attachment;filename="' . $save_name . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }

    public function test02()
    {
        Db::name('datacenter_day')->where(['day_date'=>'2020-10-21','site'=>1])->setField('order_unit_price',65.95);
        Db::name('datacenter_day')->where(['day_date'=>'2020-10-21','site'=>2])->setField('order_unit_price',57.51);
        Db::name('datacenter_day')->where(['day_date'=>'2020-10-21','site'=>3])->setField('order_unit_price',63.31);
    }

    //跑sku每天的数据
    public function sku_day_data()
    {
        $zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        set_time_limit(0);
        $data = date('Y-m-d');
        $data = '2020-10-10';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1])
            // ->where(['platform_type' => 1,'outer_sku_status'=>1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(1, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');

        //匹配sku映射关系 和ga的唯一身份浏览量的数据 循环嵌套
        $arr = [];
        foreach ($sku_data as $k => $v) {
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    if ($arr[$v['sku']]) {
                        $arr[$v['sku']]['unique_pageviews'] += $vv;
                    } else {
                        $arr[$v['sku']]['unique_pageviews'] = $vv;
                        $arr[$v['sku']]['goods_grade'] = $v['grade'];
                        $arr[$v['sku']]['sku'] = $v['sku'];
                        $arr[$v['sku']]['platform_sku'] = $v['platform_sku'];
                    }

                }
            }
        }
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //统计某个sku某一天的销量
        $zeelool_order = new Zeelool();
        foreach ($arr as $key => $value) {
            $arr[$key]['order_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
                ->where('sku', 'like', $value['sku'] . '%')
                ->where($time_where)
                ->distinct('order_id')
                ->field('order_id,created_at')
                ->count();
            $map['b.sku'] = ['=', $value['sku']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                ->where($time_where1)
                ->alias('a')
                ->field('base_grand_total,entity_id,base_row_total,b.sku,a.created_at,c.goods_type')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->join(['sales_flat_order_item_prescription' => 'c'], 'a.entity_id=c.order_id')
                ->select();
            // dump($sku_order_data);
            //统计某个sku某一天的销售额 实际支付的金额
            foreach ($sku_order_data as $kk => $vv) {
                if ($arr[$key]['sku_grand_total']) {
                    $arr[$key]['sku_grand_total'] += $vv['base_grand_total'];
                } else {
                    $arr[$key]['sku_grand_total'] = $vv['base_grand_total'];
                }
                if ($arr[$key]['sku_row_total']) {
                    $arr[$key]['sku_row_total'] += $vv['base_row_total'];
                } else {
                    $arr[$key]['sku_row_total'] += $vv['base_row_total'];
                }
                //找到商品的现价
                if (!$arr[$key]['now_pricce']) {
                    $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool_online')->table('catalog_product_index_price')->where('entity_id', $vv['entity_id'])->value('final_price');
                }
                //商品的类型
                if (!$arr[$key]['goods_type']) {
                    $arr[$key]['goods_type'] = $vv['goods_type'];
                }
            }
            //销售副数
            $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
                ->where('sku', 'like', $value['sku'] . '%')
                ->where($time_where)
                ->sum('qty_ordered');
            //副单价
            $arr[$key]['single_price'] = $arr[$key]['glass_num'] == 0 ? 0 : round($arr[$key]['sku_row_total'] / $arr[$key]['glass_num'], 0);
            // dump($sku_order_data);
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            //购物车数量
            $zeelool_model = Db::connect('database.db_zeelool_online');
            $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['=', $value['sku']];
            $arr[$key]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            //插入数据
            // Db::name('datacenter_sku_day')->insert($arr[$key]);
            // echo $key . "\n";
            // usleep(100000);
        }
        dump($arr);
    }

    public function sku_day_data_ga()
    {
        $zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        set_time_limit(0);
        $data = date('Y-m-d');
        $data = '2020-10-20';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1])
            // ->where(['platform_type' => 1,'outer_sku_status'=>1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(1,$data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');

        //匹配sku映射关系 和ga的唯一身份浏览量的数据 循环嵌套
        $arr = [];
        foreach ($sku_data as $k => $v) {
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    if ($arr[$v['sku']]) {
                        $arr[$v['sku']]['unique_pageviews'] += $vv;
                    } else {
                        $arr[$v['sku']]['unique_pageviews'] = $vv;
                        $arr[$v['sku']]['goods_grade'] = $v['grade'];
                        $arr[$v['sku']]['sku'] = $v['sku'];
                        $arr[$v['sku']]['platform_sku'] = $v['platform_sku'];
                        $arr[$v['sku']]['site'] = 1;
                        $arr[$v['sku']]['day_date'] = $data;
                    }

                }
            }
            // dump($arr[$v['sku']]);
            if (!empty($arr[$v['sku']])){
                Db::name('datacenter_sku_day')->insert($arr[$v['sku']]);
                echo $v['sku'] . "\n";
                echo '<br>';
                usleep(100000);
            }
        }
        // dump($arr);
    }
    //sku某一天的订单数量 销售额 实际支付的金额 现价 商品类型
    public function sku_day_data_order()
    {
        set_time_limit(0);
        $data = date('Y-m-d');
        $data = '2020-10-20';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 1,'outer_sku_status'=>1])
            // ->limit(10)
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        // dump($sku_data);die;
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //统计某个sku某一天的数据
        foreach ($sku_data as $key => $value) {
            //sku某一天的订单数量
            $arr[$key]['order_num'] = Db::connect('database.db_zeelool')
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->distinct('order_id')
                ->field('order_id,created_at')
                ->count();
            $map['b.sku'] = ['=', $value['sku']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_zeelool')
                ->table('sales_flat_order')
                ->where($map)
                ->where($time_where1)
                ->alias('a')
                ->field('base_grand_total,entity_id,base_row_total,b.sku,a.created_at,c.goods_type')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->join(['sales_flat_order_item_prescription' => 'c'], 'a.entity_id=c.order_id')
                ->select();
            // dump($sku_order_data);
            //统计某个sku某一天的销售额 实际支付的金额
            foreach ($sku_order_data as $kk=>$vv){
                if ($arr[$key]['sku_grand_total']){
                    $arr[$key]['sku_grand_total'] += $vv['base_grand_total'];
                }else{
                    $arr[$key]['sku_grand_total'] = $vv['base_grand_total'];
                }
                if ($arr[$key]['sku_row_total']){
                    $arr[$key]['sku_row_total'] += $vv['base_row_total'];
                }else{
                    $arr[$key]['sku_row_total'] += $vv['base_row_total'];
                }

                //商品的类型
                if (!$arr[$key]['goods_type']){
                    $arr[$key]['goods_type'] = $vv['goods_type'];
                }
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']){
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']){
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']){
                $arr[$key]['now_pricce'] = 0;
            }
            if (!$arr[$key]['goods_type']){
                $arr[$key]['goods_type'] = 1;
            }
            if (!empty($arr[$key])){
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku'=>$arr[$key]['sku'],'day_date'=>$arr[$key]['day_date'],'site'=>$arr[$key]['site']])
                    ->update(['order_num'=>$arr[$key]['order_num'],'sku_grand_total'=>$arr[$key]['sku_grand_total'],'sku_row_total'=>$arr[$key]['sku_row_total'],'goods_type'=>$arr[$key]['goods_type']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }

        }
        dump($arr);
    }
    //销售副数 副单价 购物车数量
    public function sku_day_data_other()
    {
        set_time_limit(0);
        $data = date('Y-m-d');
        $data = '2020-10-20';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1,'outer_sku_status'=>1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //统计某个sku某一天的数据
        foreach ($sku_data as $key => $value) {
            //销售副数
            $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->count();
            //副单价
            $arr[$key]['single_price'] = $arr[$key]['glass_num'] == 0 ?  0 : round($arr[$key]['sku_row_total']/$arr[$key]['glass_num'],0);
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            //购物车数量
            $zeelool_model = Db::connect('database.db_zeelool_online');
            $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['=', $value['sku']];
            $arr[$key]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total','gt',0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            //找到商品的现价
            if (!$arr[$key]['now_pricce']){
                $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool_online')
                    // $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool')
                    ->table('catalog_product_index_price')//为了获取现价找的表
                    ->alias('a')
                    ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id')//商品主表
                    ->where('a.sku','like',$value['sku'])
                    ->value('a.final_price');
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']){
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']){
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']){
                $arr[$key]['now_pricce'] = 0;
            }
            if (!$arr[$key]['goods_type']){
                $arr[$key]['goods_type'] = 1;
            }
            if (!empty($arr[$key])){
                //更新数据
                // Db::name('datacenter_sku_day')
                //     ->where(['sku'=>$arr[$key]['sku'],'day_date'=>$arr[$key]['day_date'],'site'=>$arr[$key]['site']])
                //     ->update(['glass_num'=>$arr[$key]['glass_num'],'single_price'=>$arr[$key]['single_price'],'cart_num'=>$arr[$key]['cart_num'],'now_pricce'=>$arr[$key]['now_pricce']]);
                // echo $arr[$key]['sku'] . "\n";
                // usleep(100000);
            }

        }
        dump($arr);
    }
}
