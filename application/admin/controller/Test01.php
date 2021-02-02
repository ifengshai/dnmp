<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\purchase\Supplier;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\common\controller\Backend;
use fast\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

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
            $timediff = $this->timediff(1610093513, $value['payment_time']);
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

    public function import()
    {
        $this->model = new Supplier();

        //校验参数空值
        $file = $this->request->request('file');
        !$file && $this->error(__('Parameter %s can not be empty', 'file'));

        //校验文件路径
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        !is_file($filePath) && $this->error(__('No results were found'));

        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        !in_array($ext, ['csv', 'xls', 'xlsx']) && $this->error(__('Unknown data format'));
        if ('csv' === $ext) {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if (0 == $n || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ('xls' === $ext) {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        $this->model->startTrans();
        //模板文件列名
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= 11; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    if (!empty($val)) {
                        $fields[] = $val;
                    }
                }
            }

            //校验模板文件格式
            // $listName = ['供应商', '供应商', '账期', '币种', '收款方名称', '收款方账户', '收款方开户行'];
            // $listName !== $fields && $this->error(__('模板文件格式错误！'));

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
            empty($data) && $this->error('表格数据为空！');
            foreach ($data as $k => $v) {
                if ($k < 45){
                    $this->model->where('supplier_name', $v[0])->update(['period' => $v[2], 'recipient_name' => trim($v[4])]);
                }

            }
            $this->model->commit();
        } catch (ValidateException $e) {
            $this->model->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $this->model->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $this->model->rollback();
            $this->error($e->getMessage(), [], 444);
        }
        $this->success('导入成功！');
    }

}
