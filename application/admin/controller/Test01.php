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
    }

    public function test02()
    {
        $list = Db::connect('database.db_nihao_online')->table('sales_flat_order')->where('entity_id > 44154 and entity_id < 44312')->select();
        foreach ($list as $k => $v) {
            $count = Db::connect('database.db_nihao')->table('sales_flat_order')->where(['entity_id' => $v['entity_id']])->count();
            if ($count > 0) {
                $data = [];
                $data['created_at'] = $v['created_at'];
                $data['updated_at'] = $v['updated_at'];
                Db::connect('database.db_nihao')->table('sales_flat_order')->where(['entity_id' => $v['entity_id']])->update($data);

                echo Db::connect('database.db_nihao')->table('sales_flat_order')->getLastSql();
                echo ";" . "\n";
                continue;
            }

            // Db::connect('database.db_nihao')->table('sales_flat_order')->insert($v);
            echo $k . "\n";
        }

        echo 'ok';
    }


    public function test03()
    {
        $list = Db::connect('database.db_zeelool_online')->table('sales_flat_shipment_track')->where('order_id > 520028 and order_id < 521028')->select();
        foreach ($list as $k => $v) {
            $count = Db::connect('database.db_zeelool')->table('sales_flat_shipment_track')->where(['order_id' => $v['order_id']])->count();
            if ($count > 0) {
                continue;
            }

            Db::connect('database.db_zeelool')->table('sales_flat_shipment_track')->insert($v);
            echo $k . "\n";
        }

        echo 'ok';
    }


    public function sku_day_data_ga()
    {
        $zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        set_time_limit(0);
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => 1, 'outer_sku_status' => 1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(1, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');
        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $sku_data[$k]['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = 1;
            $sku_data[$k]['day_stock'] = $sku_data[$k]['stock'];
            $sku_data[$k]['day_onway_stock'] = $sku_data[$k]['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    $sku_data[$k]['unique_pageviews'] += $vv;
                }
            }
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }


        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => 2, 'outer_sku_status' => 1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(2, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');

        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $sku_data[$k]['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = 2;
            $sku_data[$k]['day_stock'] = $sku_data[$k]['stock'];
            $sku_data[$k]['day_onway_stock'] = $sku_data[$k]['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    $sku_data[$k]['unique_pageviews'] += $vv;
                }
            }
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }

        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
            ->where(['platform_type' => 3, 'outer_sku_status' => 1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(3, $data);
        $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');

        foreach ($sku_data as $k => $v) {
            $sku_data[$k]['unique_pageviews'] = 0;
            $sku_data[$k]['goods_grade'] = $sku_data[$k]['grade'];
            $sku_data[$k]['day_date'] = $data;
            $sku_data[$k]['site'] = 3;
            $sku_data[$k]['day_stock'] = $sku_data[$k]['stock'];
            $sku_data[$k]['day_onway_stock'] = $sku_data[$k]['plat_on_way_stock'];
            unset($sku_data[$k]['stock']);
            unset($sku_data[$k]['grade']);
            unset($sku_data[$k]['plat_on_way_stock']);
            foreach ($ga_skus as $kk => $vv) {
                if (strpos($kk, $v['sku']) != false) {
                    $sku_data[$k]['unique_pageviews'] += $vv;
                }
            }
            Db::name('datacenter_sku_day')->insert($sku_data[$k]);
        }
    }

    public function sku_day_data_order()
    {
        set_time_limit(0);
        Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_zeelool')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_zeelool')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 1])->select();
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //某个sku当天的订单数
            $z_sku_list[$k]['order_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();
            //sku销售总副数
            $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
            $z_sku_list[$k]['glass_num'] = Db::connect('database.db_zeelool')
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $v['platform_sku'] . '%')
                ->where($time_where1)
                ->sum('qty_ordered');
            $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal')";
            $whereItem1 = " o.order_type = 1";
            $itemMap[] = ['exp', Db::raw("DATE_FORMAT(m.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //求出眼镜的销售额 base_price  base_discount_amount
            $frame_money_price = Db::connect('database.db_zeelool')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = Db::connect('database.db_zeelool')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            $z_sku_list[$k]['sku_grand_total'] = $frame_money_price;
            $z_sku_list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }

        //v站
        Db::connect('database.db_voogueme')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_voogueme')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_voogueme')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 2])->select();
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //某个sku当天的订单数
            $z_sku_list[$k]['order_num'] = Db::connect('database.db_voogueme')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();
            //sku销售总副数
            $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
            $z_sku_list[$k]['glass_num'] = Db::connect('database.db_voogueme')
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $v['platform_sku'] . '%')
                ->where($time_where1)
                ->sum('qty_ordered');
            $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal')";
            $whereItem1 = " o.order_type = 1";
            $itemMap[] = ['exp', Db::raw("DATE_FORMAT(m.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //求出眼镜的销售额 base_price  base_discount_amount
            $frame_money_price = Db::connect('database.db_voogueme')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = Db::connect('database.db_voogueme')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            $z_sku_list[$k]['sku_grand_total'] = $frame_money_price;
            $z_sku_list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }

        //nihao站
        Db::connect('database.db_nihao')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 3])->select();
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //某个sku当天的订单数
            $z_sku_list[$k]['order_num'] = Db::connect('database.db_nihao')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();
            //sku销售总副数
            $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
            $z_sku_list[$k]['glass_num'] = Db::connect('database.db_nihao')
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $v['platform_sku'] . '%')
                ->where($time_where1)
                ->sum('qty_ordered');
            $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal')";
            $whereItem1 = " o.order_type = 1";
            $itemMap[] = ['exp', Db::raw("DATE_FORMAT(m.created_at, '%Y-%m-%d') = '" . $data . "'")];
            //求出眼镜的销售额 base_price  base_discount_amount
            $frame_money_price = Db::connect('database.db_nihao')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = Db::connect('database.db_nihao')->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where($whereItem)
                ->where($whereItem1)
                ->where($itemMap)
                ->where('p.sku', 'like', $v['platform_sku'] . '%')
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            $z_sku_list[$k]['sku_grand_total'] = $frame_money_price;
            $z_sku_list[$k]['sku_row_total'] = $frame_money;
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
    }

    public function sku_day_data_other()
    {
        //z站
        set_time_limit(0);
        //购物车数量
        $zeelool_model = Db::connect('database.db_zeelool_online');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 1])->select();
        foreach ($z_sku_list as $k => $v) {
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $z_sku_list[$k]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            $z_sku_list[$k]['now_pricce'] = Db::connect('database.db_zeelool_online')
                ->table('catalog_product_index_price') //为了获取现价找的表
                ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
        //v站
        //购物车数量
        $zeelool_model = Db::connect('database.db_voogueme_online');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 2])->select();
        foreach ($z_sku_list as $k => $v) {
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $z_sku_list[$k]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            $z_sku_list[$k]['now_pricce'] = Db::connect('database.db_voogueme_online')
                ->table('catalog_product_index_price') //为了获取现价找的表
                ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
        //nihao站
        //购物车数量
        $zeelool_model = Db::connect('database.db_nihao_online');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 3])->select();
        foreach ($z_sku_list as $k => $v) {
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $v['platform_sku'] . '%'];
            $z_sku_list[$k]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            $z_sku_list[$k]['now_pricce'] = Db::connect('database.db_nihao_online')
                ->table('catalog_product_index_price') //为了获取现价找的表
                ->alias('a')
                ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                ->where('b.sku', 'like', $v['platform_sku'] . '%')
                ->value('a.final_price');
            Db::name('datacenter_sku_day')->update($z_sku_list[$k]);
            echo $z_sku_list[$k]['sku'] . "\n";
            echo '<br>';
        }
    }

    public function update_11_3_stock()
    {
        set_time_limit(0);
        $data = '2020-11-05';
        // Db::name('datacenter_sku_day')
        //     ->where(['day_date'=>$data,'site'=>1,'goods_type'=>0])
        //     ->update(['goods_type'=>1]);
        // Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        // Db::connect('database.db_zeelool')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        // Db::connect('database.db_zeelool')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //
        // $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date'=>$data,'site'=>1])->field('sku,platform_sku,site,goods_grade,glass_num')->select();
        // $itemMap[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        // foreach ($z_sku_list as $k =>$v){
        //     // dump($v);
        //     //获取这个sku所有的订单情况
        //     $sku_order_data = Db::connect('database.db_zeelool')->table('sales_flat_order')
        //         ->where('c.sku','like',$v['platform_sku'] . '%')
        //         ->where('a.status','in',['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete'])
        //         ->where('a.order_type','=',1)
        //         ->where($itemMap)
        //         ->alias('a')
        //         ->field('c.sku,a.created_at,c.goods_type')
        //         ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
        //         ->join(['sales_flat_order_item_prescription' => 'c'], 'a.entity_id=c.order_id')
        //         ->find();
        //
        //     if (!empty($sku_order_data)){
        //         Db::name('datacenter_sku_day')
        //             ->where(['day_date'=>$data,'site'=>1,'sku'=>$v['sku']])
        //             ->update(['goods_type'=>$sku_order_data['goods_type']]);
        //     }
        // }
        // Db::connect('database.db_voogueme')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        // Db::connect('database.db_voogueme')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        // Db::connect('database.db_voogueme')->table('sales_flat_order')->query("set time_zone='+8:00'");
        //
        // $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date'=>$data,'site'=>2])->field('sku,platform_sku,site,goods_grade,glass_num')->select();
        // $itemMap[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        // foreach ($z_sku_list as $k =>$v){
        //     // dump($v);
        //     //获取这个sku所有的订单情况
        //     $sku_order_data = Db::connect('database.db_voogueme')->table('sales_flat_order')
        //         ->where('c.sku','like',$v['platform_sku'] . '%')
        //         ->where('a.status','in',['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete'])
        //         ->where('a.order_type','=',1)
        //         ->where($itemMap)
        //         ->alias('a')
        //         ->field('c.sku,a.created_at,c.goods_type')
        //         ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
        //         ->join(['sales_flat_order_item_prescription' => 'c'], 'a.entity_id=c.order_id')
        //         ->find();
        //
        //     if (!empty($sku_order_data)){
        //         Db::name('datacenter_sku_day')
        //             ->where(['day_date'=>$data,'site'=>2,'sku'=>$v['sku']])
        //             ->update(['goods_type'=>$sku_order_data['goods_type']]);
        //     }
        // }
        Db::connect('database.db_nihao')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order')->query("set time_zone='+8:00'");

        $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date' => $data, 'site' => 3])->field('sku,platform_sku,site,goods_grade,glass_num')->select();
        $itemMap[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        foreach ($z_sku_list as $k => $v) {
            // dump($v);
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_voogueme')->table('sales_flat_order')
                ->where('c.sku', 'like', $v['platform_sku'] . '%')
                ->where('a.status', 'in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete'])
                ->where('a.order_type', '=', 1)
                ->where($itemMap)
                ->alias('a')
                ->field('c.sku,a.created_at,c.goods_type')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->join(['sales_flat_order_item_prescription' => 'c'], 'a.entity_id=c.order_id')
                ->find();

            if (!empty($sku_order_data)) {
                Db::name('datacenter_sku_day')
                    ->where(['day_date' => $data, 'site' => 3, 'sku' => $v['sku']])
                    ->update(['goods_type' => $sku_order_data['goods_type']]);
            }
        }
        die;
        //
        // $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        // $sku_data_stock = $_item_platform_sku
        //     ->field('sku,platform_sku,stock,plat_on_way_stock')
        //     ->where(['platform_type' => 1, 'outer_sku_status' => 1])
        //     ->column('stock','sku');
        // $sku_data_plat_stock = $_item_platform_sku
        //     ->field('sku,platform_sku,stock,plat_on_way_stock')
        //     ->where(['platform_type' => 1, 'outer_sku_status' => 1])
        //     ->column('plat_on_way_stock','sku');
        //
        // $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date'=>'2020-11-03','site'=>1])->field('sku,site')->select();
        // foreach ($z_sku_list as $k =>$v){
        //     Db::name('datacenter_sku_day')
        //         ->where(['day_date'=>'2020-11-03','site'=>1,'sku'=>$v['sku']])
        //         ->update(['day_stock'=>$sku_data_stock[$v['sku']],'day_onway_stock'=>$sku_data_plat_stock[$v['sku']]]);
        // }
        //
        // $sku_data_stock = $_item_platform_sku
        //     ->field('sku,platform_sku,stock,plat_on_way_stock')
        //     ->where(['platform_type' => 2, 'outer_sku_status' => 1])
        //     ->column('stock','sku');
        // $sku_data_plat_stock = $_item_platform_sku
        //     ->field('sku,platform_sku,stock,plat_on_way_stock')
        //     ->where(['platform_type' => 2, 'outer_sku_status' => 1])
        //     ->column('plat_on_way_stock','sku');
        //
        // $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date'=>'2020-11-03','site'=>2])->field('sku,site')->select();
        // foreach ($z_sku_list as $k =>$v){
        //     Db::name('datacenter_sku_day')
        //         ->where(['day_date'=>'2020-11-03','site'=>2,'sku'=>$v['sku']])
        //         ->update(['day_stock'=>$sku_data_stock[$v['sku']],'day_onway_stock'=>$sku_data_plat_stock[$v['sku']]]);
        // }
        //
        //
        // $sku_data_stock = $_item_platform_sku
        //     ->field('sku,platform_sku,stock,plat_on_way_stock')
        //     ->where(['platform_type' => 3, 'outer_sku_status' => 1])
        //     ->column('stock','sku');
        // $sku_data_plat_stock = $_item_platform_sku
        //     ->field('sku,platform_sku,stock,plat_on_way_stock')
        //     ->where(['platform_type' => 3, 'outer_sku_status' => 1])
        //     ->column('plat_on_way_stock','sku');
        //
        // $z_sku_list = Db::name('datacenter_sku_day')->where(['day_date'=>'2020-11-03','site'=>3])->field('sku,site')->select();
        // foreach ($z_sku_list as $k =>$v){
        //     Db::name('datacenter_sku_day')
        //         ->where(['day_date'=>'2020-11-03','site'=>3,'sku'=>$v['sku']])
        //         ->update(['day_stock'=>$sku_data_stock[$v['sku']],'day_onway_stock'=>$sku_data_plat_stock[$v['sku']]]);
        // }
    }
}
