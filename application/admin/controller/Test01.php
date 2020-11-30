<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

class Test01 extends Backend
{

    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        // $this->voogueme = new \app\admin\model\order\order\Voogueme();
        // $this->nihao = new \app\admin\model\order\order\Nihao();
    }

    public function test01()
    {
        set_time_limit(0);
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku')
            ->where(['platform_type' => 1])
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
            ->join(['fa_new_product_attribute' => 'b'], 'a.id=b.item_id', 'left')
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
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        $file_content = '';
        foreach ($list as $key => $value) {
            //获取平均采购价
            $res = $purchase->alias('a')->field('sum(b.purchase_num) as purchase_num,sum(b.purchase_total) as purchase_total')
                ->where(['a.purchase_status' => ['in', [2, 5, 6, 7, 9, 10]]])
                ->where(['b.sku' => $value['sku']])
                ->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
                ->where(['a.createtime' => ['>=', '2019-10-01 00:00:00']])
                ->where(['a.createtime' => ['<=', '2020-09-30 23:59:59']])
                ->select();

            $statistics = $this->zeelool
                ->alias('a')
                ->field("sum(b.qty_ordered) AS num,sum(base_price) as price,DATE_FORMAT(b.created_at, '%Y-%m') AS time")
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
            $all_money = 0;
            foreach ($statistics as $item) {
                $all_count += $item['num'];
                $all_money += $item['price'];
            }

            $prescription = $this->zeelool
                ->alias('a')
                ->field("sum(b.qty_ordered) AS num")
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

            $ava_purchase_price = $res[0]['purchase_num'] > 0 && $res[0]['purchase_total'] > 0 ? $res[0]['purchase_total'] / $res[0]['purchase_num'] : 0;
            $arr = [
                $value['sku'],
                $grade[$value['sku']],
                $ava_purchase_price,
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
                $all_money,
                $proportion
            ];
            $file_content = $file_content . implode(',', $arr) . "\n";
            echo "{$value['sku']}:success\n";
        }

        $export_str = ['SKU', '产品评级', '平均采购价CNY', '材质', '框型', '形状', '颜色', '进价', '平均月销量', '平均售价', '最大月销量', '最大月销量月份', '201910~202009总销量', '19年10月~20年9月总销售额', '配镜率'];
        $file_title = implode(',', $export_str) . " \n";
        $file = $file_title . $file_content;
        file_put_contents('/www/wwwroot/mojing/runtime/log/analysis.csv', $file);
        exit;
    }

    public function test02()
    {
        $this->ordernode = new \app\admin\model\OrderNode();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $this->ordernodecourier = new \app\admin\model\OrderNodeCourier();
        $list = $this->ordernode
            ->where(['delivery_time' => ['between', ['2020-10-01 00:00:00', '2020-12-01 00:00:00']]])
            ->where('track_number is not null')
            // ->where(['shipment_data_type' => ['in', ['FEDEX', 'USPS_2', 'USPS_1', 'USPS_3', 'CHINA_EMS', 'DHL', 'CHINA_POST']]])
            ->select();
        foreach ($list as $k => $v) {
            //根据物流单号查询发货物流渠道
            $shipment_data_type = Db::connect('database.db_delivery')->table('ld_deliver_order')->where(['track_number' => $v['track_number'], 'increment_id' => $v['order_number']])->value('agent_way_title');
            if (!$shipment_data_type) continue;
            $this->ordernode->where('id', $v['id'])->update(['shipment_data_type' => $shipment_data_type]);
            $this->ordernodedetail->where('order_id', $v['order_id'])->where('site', $v['site'])->update(['shipment_data_type' => $shipment_data_type]);
            $this->ordernodecourier->where('order_id', $v['order_id'])->where('site', $v['site'])->update(['shipment_data_type' => $shipment_data_type]);
            echo $k . "\n";
            usleep(10000);
        }
        echo "ok";
    }

    public function test99()
    {
        // $yes_date = date("Y-m-d",strtotime("-1 day"));
        // $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        // $yesterday_shoppingcart_total_data = Db::connect('database.db_zeelool')
        //     ->table('sales_flat_quote')
        //     ->where($yestime_where)
        //     ->where('base_grand_total','>',0)
        //     ->count();
        // dump($yesterday_shoppingcart_total_data);
        // $yesterday_shoppingcart_total_data1 = Db::connect('database.db_zeelool')
        //     ->table('sales_flat_quote')
        //     ->where($yestime_where)
        //     ->where('base_grand_total','>',0)
        //     ->column('entity_id');
        // // dump($yesterday_shoppingcart_total_data1);
        // $quote_where1['quote_id'] = ['in',$yesterday_shoppingcart_total_data1];
        // $order_where['order_type'] = 1;
        // $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        // $yes_date = date("Y-m-d",strtotime("-1 day"));
        // $yestime_where = [];
        // $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        // $yesterday_order_success_data1 = Db::connect('database.db_zeelool')
        //     ->table('sales_flat_order')
        //     ->where($quote_where1)
        //     ->where($yestime_where)
        //     ->where($order_where)
        //     ->where($order_success_where)
        //     ->count();
        // dump($yesterday_order_success_data1);
        // //昨天购物车转化率data
        // $yesterday_shoppingcart_conversion_data     = @round(($yesterday_order_success_data1 / $yesterday_shoppingcart_total_data), 4) * 100;
        // dump($yesterday_shoppingcart_conversion_data);

        $order_where['o.order_type'] = 1;
        $order_success_where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(o.created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_wheres[] = ['exp', Db::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_order_success_data1 = Db::connect('database.db_zeelool')->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($yestime_wheres)
            ->where('p.base_grand_total', '>', 0)
            ->where($yestime_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();
        //过去7天从新增购物车中成功支付数
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['o.created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $sev_wheres['p.created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_order_success_data1 = Db::connect('database.db_zeelool')->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($sev_wheres)
            ->where('p.base_grand_total', '>', 0)
            ->where($sev_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();
        dump($yesterday_order_success_data1);
        dump($pastsevenday_order_success_data1);
    }

    public function test100()
    {
        $now_date = date('Y-m-d');
        $now_date = '2020-11-29';
        $start = $end = $time_str = $now_date;

        $model = new \app\admin\model\operatedatacenter\Zeelool;
        //获取session
        $ga_result = $model->ga_hour_data($start, $end);
        dump($ga_result);die;

    }
}
