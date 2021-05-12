<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemProcess;
use app\common\controller\Backend;
use fast\Excel;
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
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->order = new \app\admin\model\order\order\NewOrder();
    }

    public function test0001()
    {
        sleep(50);
        echo "ok";

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
        //查询未生成子单号的数据
        $list = $this->orderprocess->where('LENGTH(trim(item_order_number))=0')->order('id desc')->limit(10000)->select();
        $list = collection($list)->toArray();
        foreach ($list as $v) {
            $res = $this->order->where(['entity_id' => $v['magento_order_id'], 'site' => $v['site']])->field('id,increment_id')->find();
            $data = $this->orderitemprocess->where(['magento_order_id' => $v['magento_order_id'], 'site' => $v['site']])->select();
            $item_params = [];
            foreach ($data as $key => $val) {
                $item_params[$key]['id'] = $val['id'];
                $str = '';
                if ($key < 9) {
                    $str = '0' . ($key + 1);
                } else {
                    $str = $key + 1;
                }

                $item_params[$key]['item_order_number'] = $res->increment_id . '-' . $str;
                $item_params[$key]['order_id'] = $res->id ?? 0;
            }
            //更新数据
            if ($item_params) $this->orderitemprocess->saveAll($item_params);

            echo $v['id'] . "\n";
            usleep(10000);
        }

        echo "ok";
    }

    public function test101()
    {
        $item_platform_sku = new ItemPlatformSku();
        $item_skuy = $item_platform_sku->where('id', '>', 0)->where('platform_type', 1)->column('grade', 'sku');
        foreach ($item_skuy as $k => $v) {
            $update = Db::name('datacenter_sku_day')->where('day_date', '2020-12-02')->where('site', 1)->where('sku', $k)->update(['goods_grade' => $v]);
            if ($update) {
                echo $k;
            }
        }

    }

    public function test102()
    {
        $data_center_sku_day = Db::name('datacenter_sku_day')->where('day_date', '2020-11-11')->field('sku,site,now_pricce')->select();
        foreach ($data_center_sku_day as $k => $v) {
            $update = Db::name('datacenter_sku_day')->where('day_date', '2020-11-12')->where('sku', $v['sku'])->where('site', $v['site'])->update(['now_pricce' => $v['now_pricce']]);
            if ($update) {
                echo $v['sku'];
            }
        }
    }

    public function test200()
    {
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        dump(Db::connect('database.db_zeelool')->table('customer_entity')->where($yestime_where1)->count());
        dump(Db::connect('database.db_zeelool')->getLastSql());

        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        dump(Db::connect('database.db_zeelool')->table('customer_entity')->where($sev_where1)->count());
        dump(Db::connect('database.db_zeelool')->getLastSql());

    }

    public function test201()
    {

        $model = Db::connect('database.db_zeelool');

        $createat = '2020-12-09 00:00:00 - 2020-12-09 23:59:59';
        $createat = explode(' ', $createat);
        $sku = 'ZVFP102705-04';
        $map['sku'] = ['like', $sku . '%'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        $map['a.order_type'] = ['=', 1];
        $total = $model->table('sales_flat_order')
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->group('order_id')
            ->field('entity_id,sku,a.created_at,a.order_type,a.status')
            ->count();
        $nopay_jingpian_glass = $model
            ->table('sales_flat_order')
            ->alias('a')
            ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
            ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
            ->where('sku', 'like', $sku . '%')
            ->where('a.order_type', '=', 1)
            ->where('b.coatiing_price', '=', 0)
            ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
            ->where('b.index_price', '=', 0)
            ->group('order_id')
            ->count();
        $only_one_glass_order_list = $model->table('sales_flat_order')
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->group('order_id')
            ->field('entity_id,sku,a.created_at,a.order_type,a.status,order_id,sum(qty_ordered) as all_qty_ordered')
            ->select();
        dump($only_one_glass_order_list);
        dump($total);
        dump($nopay_jingpian_glass);
        dump($model->getLastSql());
    }

    //商品转化率的销售副数 销量统计的销量
    public function test300()
    {
        $createat = '2020-12-09 00:00:00 - 2020-12-09 23:59:59';
        $createat = explode(' ', $createat);
        $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        $map['sku'] = ['in', ['VHP0189-01']];
        $model = Db::connect('database.db_zeelool');
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $res = $model->table('sales_flat_order')
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->group('sku')
            ->order('num desc')
            ->column('round(sum(b.qty_ordered)) as num', 'trim(sku)');
        dump($model->getLastSql());
        $data = '2020-12-09';
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $z_sku_list = $model
            ->table('sales_flat_order_item')
            ->where('sku', 'like', 'VHP0189-01' . '%')
            ->where($time_where1)
            ->sum('qty_ordered');
        dump($model->getLastSql());
        dump($res);
        dump($z_sku_list);
    }

    public function export_v_data()
    {
        $sku_list = Db::name('datacenter_sku_import_test')->where('id', '>=', 1)->where('id', '<=', 99)->select();
        // dump($sku_list);die;
        foreach ($sku_list as $k => $v) {
            //站点
            $order_platform = 2;
            //时间
            $time_str = '2020-11-21 00:00:00 - 2020-12-20 23:59:59';
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $order_platform];
            $sku = $v['sku'];
            $item_platform = new ItemPlatformSku();
            $sku = $item_platform->where('sku', $sku)->where('platform_type', $order_platform)->value('platform_sku') ? $item_platform->where('sku', $sku)->where('platform_type', $order_platform)->value('platform_sku') : $sku;
            $model = Db::connect('database.db_voogueme');
            $coatiing_price['b.coatiing_price'] = ['=', 0];

            $model->table('sales_flat_order')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            //此sku的总订单量
            $map['sku'] = ['like', $sku . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['a.order_type'] = ['=', 1];
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();

            //整站订单量
            $whole_platform_order_num = Db::name('datacenter_day')->where($same_where)->sum('order_num');
            //订单占比
            $order_rate = $whole_platform_order_num == 0 ? 0 : round($total / $whole_platform_order_num * 100, 2) . '%';
            //平均订单副数
            $whole_glass = $model
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->sum('qty_ordered');//sku总副数
            $avg_order_glass = $total == 0 ? 0 : round($whole_glass / $total, 2);
            if ($order_platform != 3) {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('a.order_type', '=', 1)
                    ->where($coatiing_price)
                    ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            } else {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('a.order_type', '=', 1)
                    // ->where('b.coatiing_price', '=', 0)
                    ->where($coatiing_price)
                    ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            }
            $pay_jingpian_glass = $total - $nopay_jingpian_glass;
            //付费镜片订单数占比
            $pay_jingpian_glass_rate = $total == 0 ? 0 : round($pay_jingpian_glass / $total * 100, 2) . '%';
            //只买一副的订单
            $only_one_glass_order_list = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status,order_id,sum(qty_ordered) as all_qty_ordered')
                ->select();
            $only_one_glass_num = 0;
            foreach ($only_one_glass_order_list as $kk => $v) {
                $one = $model->table('sales_flat_order_item')->where('order_id', $v['order_id'])->sum('qty_ordered');
                if ($one == 1) {
                    $only_one_glass_num += 1;
                }
            }
            //只买一副的订单占比
            $only_one_glass_rate = $total == 0 ? 0 : round($only_one_glass_num / $total * 100, 2) . '%';
            //订单总金额
            $whole_price = $model
                ->table('sales_flat_order')
                ->where($map)
                ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->field('base_grand_total')
                ->sum('base_grand_total');
            //订单客单价
            $every_price = $total == 0 ? 0 : round($whole_price / $total, 2);
            $arr[$k]['sku'] = $sku;
            $arr[$k]['total'] = $total;
            $arr[$k]['whole_platform_order_num'] = $whole_platform_order_num;
            $arr[$k]['order_rate'] = $order_rate;
            $arr[$k]['avg_order_glass'] = $avg_order_glass;
            $arr[$k]['pay_jingpian_glass'] = $pay_jingpian_glass;
            $arr[$k]['pay_jingpian_glass_rate'] = $pay_jingpian_glass_rate;
            $arr[$k]['only_one_glass_num'] = $only_one_glass_num;
            $arr[$k]['only_one_glass_rate'] = $only_one_glass_rate;
            $arr[$k]['whole_price'] = $whole_price;
            $arr[$k]['every_price'] = $every_price;
        }
        // dump($arr);die;
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "sku");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "sku订单量");
        $spreadsheet->getActiveSheet()->setCellValue("C1", "整站订单量");
        $spreadsheet->getActiveSheet()->setCellValue("D1", "订单占比");
        $spreadsheet->getActiveSheet()->setCellValue("E1", "平均订单副数");
        $spreadsheet->getActiveSheet()->setCellValue("F1", "付费镜片订单数");
        $spreadsheet->getActiveSheet()->setCellValue("G1", "付费镜片订单数占比");
        $spreadsheet->getActiveSheet()->setCellValue("H1", "只买一副的订单量");
        $spreadsheet->getActiveSheet()->setCellValue("I1", "只买一副订单占比");
        $spreadsheet->getActiveSheet()->setCellValue("J1", "订单客单价");
        $spreadsheet->getActiveSheet()->setCellValue("K1", "订单金额");
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(60);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(12);
        $spreadsheet->setActiveSheetIndex(0)->setTitle('SKU明细');
        $spreadsheet->setActiveSheetIndex(0);
        $num = 0;
        foreach ($arr as $k => $v) {
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['total']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['whole_platform_order_num']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['order_rate']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['avg_order_glass']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['pay_jingpian_glass']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . ($num * 1 + 2), $v['pay_jingpian_glass_rate']);
            $spreadsheet->getActiveSheet()->setCellValue('H' . ($num * 1 + 2), $v['only_one_glass_num']);
            $spreadsheet->getActiveSheet()->setCellValue('I' . ($num * 1 + 2), $v['only_one_glass_rate']);
            $spreadsheet->getActiveSheet()->setCellValue('J' . ($num * 1 + 2), $v['whole_price']);
            $spreadsheet->getActiveSheet()->setCellValue('K' . ($num * 1 + 2), $v['every_price']);
            $num += 1;
        }
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
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = 'voogueme站' . $createat[0] . '至' . $createat[3] . 'SKU销售情况';
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

    public function export_n_data()
    {
        $sku_list = Db::name('datacenter_sku_import_test')->where('id', '>=', 100)->where('id', '<=', 199)->select();
        foreach ($sku_list as $k => $v) {
            //站点
            $order_platform = 3;
            //时间
            $time_str = '2020-11-21 00:00:00 - 2020-12-20 23:59:59';
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $order_platform];
            $sku = $v['sku'];
            $item_platform = new ItemPlatformSku();
            $sku = $item_platform->where('sku', $sku)->where('platform_type', $order_platform)->value('platform_sku') ? $item_platform->where('sku', $sku)->where('platform_type', $order_platform)->value('platform_sku') : $sku;

            $model = Db::connect('database.db_nihao');
            $coatiing_price = [];

            $model->table('sales_flat_order')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            //此sku的总订单量
            $map['sku'] = ['like', $sku . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['a.order_type'] = ['=', 1];
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();

            //整站订单量
            $whole_platform_order_num = Db::name('datacenter_day')->where($same_where)->sum('order_num');
            //订单占比
            $order_rate = $whole_platform_order_num == 0 ? 0 : round($total / $whole_platform_order_num * 100, 2) . '%';
            //平均订单副数
            $whole_glass = $model
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->sum('qty_ordered');//sku总副数
            $avg_order_glass = $total == 0 ? 0 : round($whole_glass / $total, 2);
            if ($order_platform != 3) {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('a.order_type', '=', 1)
                    ->where($coatiing_price)
                    ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            } else {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('a.order_type', '=', 1)
                    ->where($coatiing_price)
                    ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            }
            $pay_jingpian_glass = $total - $nopay_jingpian_glass;
            //付费镜片订单数占比
            $pay_jingpian_glass_rate = $total == 0 ? 0 : round($pay_jingpian_glass / $total * 100, 2) . '%';
            //只买一副的订单
            $only_one_glass_order_list = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status,order_id,sum(qty_ordered) as all_qty_ordered')
                ->select();
            $only_one_glass_num = 0;
            foreach ($only_one_glass_order_list as $kk => $v) {
                $one = $model->table('sales_flat_order_item')->where('order_id', $v['order_id'])->sum('qty_ordered');
                if ($one == 1) {
                    $only_one_glass_num += 1;
                }
            }
            //只买一副的订单占比
            $only_one_glass_rate = $total == 0 ? 0 : round($only_one_glass_num / $total * 100, 2) . '%';
            //订单总金额
            $whole_price = $model
                ->table('sales_flat_order')
                ->where($map)
                ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->field('base_grand_total')
                ->sum('base_grand_total');
            //订单客单价
            $every_price = $total == 0 ? 0 : round($whole_price / $total, 2);
            $arr[$k]['sku'] = $sku;
            $arr[$k]['total'] = $total;
            $arr[$k]['whole_platform_order_num'] = $whole_platform_order_num;
            $arr[$k]['order_rate'] = $order_rate;
            $arr[$k]['avg_order_glass'] = $avg_order_glass;
            $arr[$k]['pay_jingpian_glass'] = $pay_jingpian_glass;
            $arr[$k]['pay_jingpian_glass_rate'] = $pay_jingpian_glass_rate;
            $arr[$k]['only_one_glass_num'] = $only_one_glass_num;
            $arr[$k]['only_one_glass_rate'] = $only_one_glass_rate;
            $arr[$k]['whole_price'] = $whole_price;
            $arr[$k]['every_price'] = $every_price;
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "sku");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "sku订单量");
        $spreadsheet->getActiveSheet()->setCellValue("C1", "整站订单量");
        $spreadsheet->getActiveSheet()->setCellValue("D1", "订单占比");
        $spreadsheet->getActiveSheet()->setCellValue("E1", "平均订单副数");
        $spreadsheet->getActiveSheet()->setCellValue("F1", "付费镜片订单数");
        $spreadsheet->getActiveSheet()->setCellValue("G1", "付费镜片订单数占比");
        $spreadsheet->getActiveSheet()->setCellValue("H1", "只买一副的订单量");
        $spreadsheet->getActiveSheet()->setCellValue("I1", "只买一副订单占比");
        $spreadsheet->getActiveSheet()->setCellValue("J1", "订单客单价");
        $spreadsheet->getActiveSheet()->setCellValue("K1", "订单金额");
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(60);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(12);
        $spreadsheet->setActiveSheetIndex(0)->setTitle('SKU明细');
        $spreadsheet->setActiveSheetIndex(0);
        $num = 0;
        foreach ($arr as $k => $v) {
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['total']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['whole_platform_order_num']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['order_rate']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['avg_order_glass']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['pay_jingpian_glass']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . ($num * 1 + 2), $v['pay_jingpian_glass_rate']);
            $spreadsheet->getActiveSheet()->setCellValue('H' . ($num * 1 + 2), $v['only_one_glass_num']);
            $spreadsheet->getActiveSheet()->setCellValue('I' . ($num * 1 + 2), $v['only_one_glass_rate']);
            $spreadsheet->getActiveSheet()->setCellValue('J' . ($num * 1 + 2), $v['whole_price']);
            $spreadsheet->getActiveSheet()->setCellValue('K' . ($num * 1 + 2), $v['every_price']);
            $num += 1;
        }
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
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = 'nihao站' . $createat[0] . '至' . $createat[3] . 'SKU销售情况';
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

    public function hedankuwei()
    {
        $res = Db::name('work_order_list')->where('id', 'in', ['54838'])->setField('assign_user_id', 117);
        die;
        $list = Db::name('hedan_kuwei')->where('id', '>', 0)->select();
        foreach ($list as $k => $v) {
            $list[$k]['type'] = 2;
            $list[$k]['createtime'] = '2020-12-22 20:03:31';
            $list[$k]['create_person'] = 'Admin';
            $list[$k]['shelf_number'] = '';
            // Db::name('store_house')->insert($list[$k]);
            unset($list[$k]['id']);
        }
        Db::name('store_house')->insertAll($list);
        dump($list);
    }

    public function xiaoliangpaihangbang()
    {
        $data = date('Y-m-d', strtotime('-1 day'));
        //sku销售总副数
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $z_sku_list = Db::connect('database.db_zeelool')
            ->table('sales_flat_order_item')
            ->where('sku', 'like', 'ZOP049594-01' . '%')
            ->where($time_where1)
            ->sum('qty_ordered');


        $map['sku'] = ['like', 'ZOP049594-01' . '%'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $map['a.order_type'] = ['=', 1];
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //某个sku当天的订单数
        $z_sku_list = Db::connect('database.db_zeelool')->table('sales_flat_order')
            ->where($map)
            ->where($time_where)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->sum('qty_ordered');
        dump(Db::connect('database.db_zeelool')->getLastSql());
        die;

        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $createat = explode(' ', '2020-12-01 00:00:00 - 2020-12-31 23:59:59');
        $map['sku'] = ['=', 'ZOP049594-01'];
        $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $res = $this->zeelool
            ->where($map)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->group('sku')
            ->order('num desc')
            ->column('round(sum(b.qty_ordered)) as num', 'trim(sku)');
        dump($this->zeelool->getLastSql());
    }

    public function update_month_12_data()
    {
        Db::connect('database.db_zeelool')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_zeelool')->table('sales_flat_order')->query("set time_zone='+8:00'");
        $z_sku_list = Db::name('datacenter_sku_day')
            ->where(['site' => 1])
            ->where('day_date', 'between', ['2020-12-06', '2020-12-31'])
            // ->where('glass_num','>',0)
            ->field('id,day_date,platform_sku')
            // ->limit(2000)
            ->select();
        // dump($z_sku_list);die;
        // dump($z_sku_list);
        foreach ($z_sku_list as $k => $v) {
            $map['sku'] = ['like', $v['platform_sku'] . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
            $map['a.order_type'] = ['=', 1];
            $time_where[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $v['day_date'] . "'")];
            //sku销售总副数
            $glass_num = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                ->where($time_where)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->sum('qty_ordered');
            // dump($v['platform_sku'].':'.$v['day_date'].':'.$z_sku_list[$k]['glass_num']);
            // dump($glass_num);
            // dump(Db::connect('database.db_zeelool')->getLastSql());
            // $z_sku_list[$k]['glass_num'] = 1099;
            // $glass_num = 1099;
            $res = Db::name('datacenter_sku_day')->where('id', $v['id'])->setField('glass_num', $glass_num);
            $map = [];
            $time_where = [];
            // dump(Db::name('datacenter_sku_day')->getLastSql());
            // ->where('platform_sku',$v['platform_sku'])
            // ->where('day_date',$v['day_date'])
            // ->update(['glass_num'=>$glass_num]);
            if ($res) {
                echo 'sku:' . $v['platform_sku'] . $v['day_date'] . '更新成功' . "\n";
                echo '<br>';
            } else {
                echo 'sku:' . $v['platform_sku'] . $v['day_date'] . '更新失败' . "\n";
                echo '<br>';
            }
        }

        // dump($z_sku_list);die;
    }

    public function export_8_month_not_complete_son_order()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $new_order_item = new NewOrderItemProcess();
        $list = $new_order_item->alias('a')
            ->join(['fa_order_process' => 'b'], 'a.order_id=b.order_id')
            ->join(['fa_order' => 'c'], 'b.order_id=c.id')
            ->where('c.status', '=', 'processing')
            ->where('c.created_at', '>', 1596211200)
            ->where('b.delivery_time', 'EXP', 'IS NULL')
            ->field('a.item_order_number,a.order_prescription_type,c.payment_time')
            // ->limit(500)
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $key => $value) {
            $csv[$key]['item_order_number'] = $value['item_order_number'];
            $timediff = $this->timediff(1610093513,$value['payment_time']);
            //状态
            switch ($value['order_prescription_type']) {
                case 1:
                    $work_status = '仅镜架';
                    if ($timediff > 24) {
                        $csv[$key]['is_time_out'] = '超时';
                    } else {
                        $csv[$key]['is_time_out'] = '未超时';
                    }
                    break;
                case 2:
                    $work_status = '现货处方镜';
                    if ($timediff > 72) {
                        $csv[$key]['is_time_out'] = '超时';
                    } else {
                        $csv[$key]['is_time_out'] = '未超时';
                    }
                    break;
                case 3:
                    $work_status = '定制处方镜';
                    if ($timediff > 168) {
                        $csv[$key]['is_time_out'] = '超时';
                    } else {
                        $csv[$key]['is_time_out'] = '未超时';
                    }
                    break;
                case 4:
                    $work_status = '其他';
                    $csv[$key]['is_time_out'] = '';
                    break;
                default:
                    $work_status = '0待处理';
                    $csv[$key]['is_time_out'] = '';
                    break;
            }
            $csv[$key]['order_prescription_type'] = $work_status;
        }
        // dump($csv);
        // die();
        $headlist = [
            '子订单号', '子单加工类型', '是否超时'
        ];
        $path = "/uploads/ship_uploads/";
        $fileName = '导出订单加工数据1 2021-01-08';
        Excel::writeCsv($csv, $headlist, $path . $fileName);
    }

    /**
     * 计算两个时间戳之间相差的日时分秒
     * @param $begin_time 开始时间戳
     * @param $end_time 结束时间戳
     * @return array
     */
    function timediff($start_time, $end_time)
    {
        if (strtotime($start_time) > strtotime($end_time)) list($start_time, $end_time) = array($end_time, $start_time);

        $sec = $start_time - $end_time;
        $sec = round($sec / 60);
        $min = str_pad($sec % 60, 2, 0, STR_PAD_LEFT);
        $hours_min = floor($sec / 60);
        $min != 0 && $hours_min .= '.' . $min;
        return $hours_min;
    }

    /**
     * 财务数据 -- 库存台账导出
     * @author mjj
     * @date   2021/5/7 17:15:44
     */
    public function financeExport()
    {
        set_time_limit(0);
        $this->instock = new \app\admin\model\warehouse\Instock;
        $this->outstock = new \app\admin\model\warehouse\Outstock;
        $this->stockparameter = new \app\admin\model\financepurchase\StockParameter;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $this->model = new \app\admin\model\itemmanage\Item;
        //时间
        $arr = array();
        for($t=1617206400;$t<=1619712000;$t+=24*3600){
            $start = date('Y-m-d',$t);
            $end = $start.' 23:59:59';
            $startTime = strtotime($start);
            $endTime = strtotime($end);
            /*************入库单出库start**************/
            //入库数据
            $instockWhere['s.status'] = 2;
            $instockWhere['s.check_time'] = ['between', [$start, $end]];
            $inSkus = $this->instock
                ->alias('s')
                ->join('fa_in_stock_item i','i.in_stock_id=s.id')
                ->where($instockWhere)
                ->group('sku')
                ->column('sku');
            $arr1 = [];
            $i = 0;
            foreach ($inSkus as $inSku){
                //获取入库sku类别
                $category = $this->model
                    ->alias('i')
                    ->join('fa_item_category c','i.category_id=c.id')
                    ->where('sku',$inSku)
                    ->value('c.name');
                $instocks = $this->instock
                    ->alias('s')
                    ->join('fa_in_stock_item i','i.in_stock_id=s.id')
                    ->join('fa_purchase_order_item oi', 'i.purchase_id=oi.purchase_id')
                    ->join('fa_purchase_order o', 'oi.purchase_id=o.id')
                    ->where($instockWhere)
                    ->where('i.sku',$inSku)
                    ->field('o.id,o.purchase_number,round(o.purchase_total/oi.purchase_num,2) purchase_total,sum(in_stock_num) in_stock_num,s.type_id')
                    ->group('o.purchase_number')
                    ->select();
                foreach($instocks as $instock){
                    $arr1[$i]['day_date'] = $start;
                    $arr1[$i]['category'] = $category;
                    $arr1[$i]['sku'] = $inSku;
                    $typeName = Db::name('in_stock_type')
                        ->where('id',$instock['type_id'])
                        ->value('name');
                    $arr1[$i]['inOutFlag'] = $typeName;//入库
                    $arr1[$i]['purchase_number'] = $instock['purchase_number'];//采购单号
                    $arr1[$i]['instockPrice'] = $instock['purchase_total'];
                    $arr1[$i]['instock_num'] = $instock['in_stock_num'];
                    $arr1[$i]['outstockPrice'] = '-';
                    $arr1[$i]['outstock_num'] = '-';
                    $i++;
                }
            }
            /*************入库单出库end**************/
            /*************出库单出库start**************/
            $barWhere['out_stock_time'] = ['between', [$start, $end]];
            $barWhere['out_stock_id'] = ['<>', 0];
            $barWhere['library_status'] = 2;
            $bars = $this->item
                ->where($barWhere)
                ->group('sku')
                ->column('sku');
            $arr2 = [];
            $j = 0;
            foreach ($bars as $bar) {
                //获取出库sku类别
                $category = $this->model
                    ->alias('i')
                    ->join('fa_item_category c','i.category_id=c.id')
                    ->where('sku',$bar)
                    ->value('c.name');
                $barItems = $this->item
                    ->alias('i')
                    ->join('fa_purchase_order_item p', 'i.purchase_id=p.purchase_id and i.sku=p.sku')
                    ->join('fa_purchase_order o', 'p.purchase_id=o.id')
                    ->join('fa_out_stock s', 'i.out_stock_id=s.id')
                    ->join('fa_out_stock_type t', 't.id=s.type_id')
                    ->field('round(o.purchase_total/p.purchase_num,2) purchase_total,count(*) purchase_num,o.purchase_number,t.name')
                    ->where($barWhere)
                    ->where('i.sku', $bar)
                    ->group('o.purchase_number,s.type_id')
                    ->select();
                foreach ($barItems as $barItem){
                    $arr2[$j]['day_date'] = $start;
                    $arr2[$j]['category'] = $category;
                    $arr2[$j]['sku'] = $bar;
                    $arr2[$j]['inOutFlag'] = $barItem['name'];//出库类型
                    $arr2[$j]['purchase_number'] = $barItem['purchase_number'];//采购单单号
                    $arr2[$j]['instockPrice'] = '-';//入库单价
                    $arr2[$j]['instock_num'] = '-';//入库数量
                    $arr2[$j]['outstockPrice'] = $barItem['purchase_total'];//出库单价
                    $arr2[$j]['outstock_num'] = $barItem['purchase_num'];//出库数量
                    $j++;
                }
            }
            /*************出库单出库end**************/
            /*************订单出库start**************/
            $barWhere1['out_stock_time'] = ['between', [$start, $end]];
            $barWhere1['out_stock_id'] = 0;
            $barWhere1['item_order_number'] = ['<>', ''];
            $barWhere1['library_status'] = 2;
            $bars1 = $this->item
                ->where($barWhere1)
                ->group('sku')
                ->column('sku');
            $arr3 = [];
            $l = 0;
            foreach ($bars1 as $bar1){
                //获取出库sku类别
                $category = $this->model
                    ->alias('i')
                    ->join('fa_item_category c','i.category_id=c.id')
                    ->where('sku',$bar1)
                    ->value('c.name');
                $barItems1 = $this->item
                    ->alias('i')
                    ->join('fa_purchase_order_item p', 'i.purchase_id=p.purchase_id and i.sku=p.sku')
                    ->join('fa_purchase_order o', 'p.purchase_id=o.id')
                    ->where($barWhere1)
                    ->where('i.sku', $bar1)
                    ->field('round(o.purchase_total/p.purchase_num,2) purchase_total,count(*) purchase_num,o.purchase_number')
                    ->group('o.purchase_number')
                    ->select();
                foreach ($barItems1 as $barItem1){
                    $arr3[$l]['day_date'] = $start;
                    $arr3[$l]['category'] = $category;
                    $arr3[$l]['sku'] = $bar1;
                    $arr3[$l]['inOutFlag'] = '订单出库';//订单出库
                    $arr3[$l]['purchase_number'] = $barItem1['purchase_number'];//采购单单号
                    $arr3[$l]['instockPrice'] = '-';//采购单价
                    $arr3[$l]['instock_num'] = '-';//入库数量
                    $arr3[$l]['outstockPrice'] = $barItem1['purchase_total'];//采购单价
                    $arr3[$l]['outstock_num'] = $barItem1['purchase_num'];//出库数量
                    $l++;
                }
            }
            /*************订单出库end**************/
            /*************入库单冲减start**************/
            //当天是否有冲减记录
            $writeDownWhere['create_time'] = ['between',[$startTime,$endTime]];
            $writeDownDatas = Db::name('finance_cost_error')
                ->alias('r')
                ->join('fa_purchase_order_item p', 'r.purchase_id=p.purchase_id')
                ->join('fa_purchase_order o', 'p.purchase_id=o.id')
                ->where($writeDownWhere)
                ->field('r.purchase_id,r.create_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_total,p.sku,o.purchase_number')
                ->select();
            $arr4 = [];
            $m = 0;
            foreach ($writeDownDatas as $writeDownData){
                $category = $this->model
                    ->alias('i')
                    ->join('fa_item_category c','i.category_id=c.id')
                    ->where('sku',$writeDownData['sku'])
                    ->value('c.name');
                //冲减入库记录
                $inTimeWhere = [];
                $inTimeWhere[] = ['exp', Db::raw("UNIX_TIMESTAMP(s.check_time)<".$writeDownData['create_time']."")];
                $inStockCount = $this->instock
                    ->alias('s')
                    ->join('fa_in_stock_item i','i.in_stock_id=s.id')
                    ->where('s.status',2)
                    ->where($inTimeWhere)
                    ->where('i.purchase_id',$writeDownData['purchase_id'])
                    ->sum('in_stock_num');
                if($inStockCount>0){
                    $arr4[$m]['day_date'] = $start;
                    $arr4[$m]['category'] = $category;
                    $arr4[$m]['sku'] = $writeDownData['sku'];
                    $arr4[$m]['inOutFlag'] = '采购结算入库冲减';//订单出库
                    $arr4[$m]['purchase_number'] = $writeDownData['purchase_number'];//采购单单号
                    $arr4[$m]['instockPrice'] = '-'.$writeDownData['purchase_total'];//采购单价
                    $arr4[$m]['instock_num'] = $inStockCount;//入库数量
                    $arr4[$m]['outstockPrice'] = '-';//采购单价
                    $arr4[$m]['outstock_num'] = '-';//出库数量
                    $m++;
                    $arr4[$m]['day_date'] = $start;
                    $arr4[$m]['category'] = $category;
                    $arr4[$m]['sku'] = $writeDownData['sku'];
                    $arr4[$m]['inOutFlag'] = '采购结算入库冲减';//订单出库
                    $arr4[$m]['purchase_number'] = $writeDownData['purchase_number'];//采购单单号
                    $arr4[$m]['instockPrice'] = $writeDownData['actual_purchase_price'];//入库单价
                    $arr4[$m]['instock_num'] = $inStockCount;//入库数量
                    $arr4[$m]['outstockPrice'] = '-';//出库单价
                    $arr4[$m]['outstock_num'] = '-';//出库数量
                    $m++;
                }
                //判断冲减之前是否有出库单出库记录
                $outTimeWhere = [];
                $outTimeWhere[] = ['exp', Db::raw("UNIX_TIMESTAMP(out_stock_time)<".$writeDownData['create_time']."")];
                $outCount1 = $this->item
                    ->where($barWhere)
                    ->where('purchase_id',$writeDownData['purchase_id'])
                    ->where($outTimeWhere)
                    ->count();
                //判断冲减之前是否有订单出库记录
                $outCount2 = $this->item
                    ->where($barWhere1)
                    ->where('purchase_id',$writeDownData['purchase_id'])
                    ->where($outTimeWhere)
                    ->count();
                $outCount = $outCount1+$outCount2;
                if($outCount>0){
                    //增加冲减记录
                    $arr4[$m]['day_date'] = $start;
                    $arr4[$m]['category'] = $category;
                    $arr4[$m]['sku'] = $writeDownData['sku'];
                    $arr4[$m]['inOutFlag'] = '采购结算出库冲减';//订单出库
                    $arr4[$m]['purchase_number'] = $writeDownData['purchase_number'];//采购单单号
                    $arr4[$m]['instockPrice'] = '-';//采购单价
                    $arr4[$m]['instock_num'] = '-';//入库数量
                    $arr4[$m]['outstockPrice'] = '-'.$writeDownData['purchase_total'];//采购单价
                    $arr4[$m]['outstock_num'] = $outCount;//出库数量
                    $m++;
                    //增加冲减记录
                    $arr4[$m]['day_date'] = $start;
                    $arr4[$m]['category'] = $category;
                    $arr4[$m]['sku'] = $writeDownData['sku'];
                    $arr4[$m]['inOutFlag'] = '采购结算出库冲减';//订单出库
                    $arr4[$m]['purchase_number'] = $writeDownData['purchase_number'];//采购单单号
                    $arr4[$m]['instockPrice'] = '-';//采购单价
                    $arr4[$m]['instock_num'] = '-';//入库数量
                    $arr4[$m]['outstockPrice'] = $writeDownData['actual_purchase_price'];//采购单价
                    $arr4[$m]['outstock_num'] = $outCount;//出库数量
                    $m++;
                }
            }
            /*************入库单冲减end**************/
            $arr = array_merge($arr,array_merge($arr1,$arr2,$arr3,$arr4));
        }
        $file_content = '';
        foreach ($arr as $key => $value) {
            $file_content = $file_content . implode(',', $value) . "\n";
            echo "{$value['sku']}:success\n";
        }
        $export_str = ['日期', '商品分类', '商品sku', '出入库类型', '采购单号','入库单价（元）','入库数量','出库单价','出库数量'];
        $file_title = implode(',', $export_str) . " \n";
        $file = $file_title . $file_content;
        file_put_contents('/var/www/mojing/runtime/log/finance2.csv', $file);
        exit;
    }
}
