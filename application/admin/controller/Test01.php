<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\common\controller\Backend;
use fast\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\admin\model\AuthGroup;
use think\Db;
use fast\Tree;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

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
       // $count = 9781;
        //$page = 100;
        for($i = 1;$i<=51;$i++) {
            $offset = ( $i - 1 ) * 200;
            $list = $_new_product
                ->alias('a')
                ->field('sku,frame_color,frame_texture,shape,frame_shape,price')
                ->where(['item_status' => 2, 'is_del' => 1, 'sku' => ['in', $sku_arr]])
                ->join(['fa_new_product_attribute' => 'b'], 'a.id=b.item_id', 'left')
                ->limit($offset,200)
                ->select();
            $list = collection($list)->toArray();
            echo "list:success{$i}\n";

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
                    ->where(['a.createtime' => ['>=', '2020-07-01 00:00:00']])
                    ->where(['a.createtime' => ['<=', '2021-06-30 23:59:59']])
                    ->select();

                $statistics = $this->zeelool
                    ->alias('a')
                    ->field("sum(b.qty_ordered) AS num,sum(base_price) as price,DATE_FORMAT(b.created_at, '%Y-%m') AS time")
                    ->where(['a.status' => ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','delivered']]])
                    ->where(['b.created_at' => ['>=', '2020-07-01 00:00:00']])
                    ->where(['b.created_at' => ['<=', '2021-06-30 23:59:59']])
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
                    ->where(['a.status' => ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','delivered']]])
                    ->where(['b.created_at' => ['>=', '2020-07-01 00:00:00']])
                    ->where(['b.created_at' => ['<=', '2021-06-30 23:59:59']])
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

            if($i == 1) {
                $export_str = ['SKU', '产品评级', '平均采购价CNY', '材质', '框型', '形状', '颜色', '进价', '平均月销量', '平均售价', '最大月销量', '最大月销量月份', '202007~202106总销量', '20年7月~21年6月总销售额', '配镜率'];
                $file_title = implode(',', $export_str) . " \n";
                $file = $file_title . $file_content;
            }else{
                $file = $file_content;
            }

            file_put_contents('/var/www/mojing/runtime/log/test01.csv', $file,FILE_APPEND);
        }
        exit;
    }

    public function test01v()
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
        // $count = 9781;
        //$page = 100;
        for($i = 1;$i<=51;$i++) {
            $offset = ( $i - 1 ) * 200;
            $list = $_new_product
                ->alias('a')
                ->field('sku,frame_color,frame_texture,shape,frame_shape,price')
                ->where(['item_status' => 2, 'is_del' => 1, 'sku' => ['in', $sku_arr]])
                ->join(['fa_new_product_attribute' => 'b'], 'a.id=b.item_id', 'left')
                ->limit($offset,200)
                ->select();
            $list = collection($list)->toArray();
            echo "list:success{$i}\n";

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
                    ->where(['a.createtime' => ['>=', '2020-07-01 00:00:00']])
                    ->where(['a.createtime' => ['<=', '2021-06-30 23:59:59']])
                    ->select();

                $statistics = $this->voogueme
                    ->alias('a')
                    ->field("sum(b.qty_ordered) AS num,sum(base_price) as price,DATE_FORMAT(b.created_at, '%Y-%m') AS time")
                    ->where(['a.status' => ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','delivered']]])
                    ->where(['b.created_at' => ['>=', '2020-07-01 00:00:00']])
                    ->where(['b.created_at' => ['<=', '2021-06-30 23:59:59']])
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

                $prescription = $this->voogueme
                    ->alias('a')
                    ->field("sum(b.qty_ordered) AS num")
                    ->where(['a.status' => ['in', ['processing', 'complete', 'creditcard_proccessing', 'free_processing','delivered']]])
                    ->where(['b.created_at' => ['>=', '2020-07-01 00:00:00']])
                    ->where(['b.created_at' => ['<=', '2021-06-30 23:59:59']])
                    ->where(['b.product_options' => ['not like', '%frameonly%']])
                    ->where(['b.product_options' => ['not like', '%nonprescription%']])
                    ->where(['b.sku' => $platform[$value['sku']]])
                    ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id', 'LEFT')
                    ->select();
                $prescription = collection($prescription)->toArray();

                $monthly_sales = $all_count > 0 ? $all_count / 12 : 0;
                $average_price = $statistics[0]['price'] > 0 && $statistics[0]['num'] > 0 ? $statistics[0]['price'] / $statistics[0]['num'] : 0;
                $proportion = $all_count > 0 && $statistics[0]['num'] > 0 ? $prescription[0]['num'] / $all_count : 0;



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

            if($i == 1) {
                $export_str = ['SKU', '产品评级', '平均采购价CNY', '材质', '框型', '形状', '颜色', '进价', '平均月销量', '平均售价', '最大月销量', '最大月销量月份', '202007~202106总销量', '20年7月~21年6月总销售额', '配镜率'];
                $file_title = implode(',', $export_str) . " \n";
                $file = $file_title . $file_content;
            }else{
                $file = $file_content;
            }

            file_put_contents('/var/www/mojing/runtime/log/test01v.csv', $file,FILE_APPEND);
        }
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
        $sku_list = Db::name('datacenter_sku_import_new')->select();
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
        $item_platform = new ItemPlatformSku();
        $sku_list = $item_platform->field('sku')->where('platform_type', 2)->select();
        foreach ($sku_list as $k => $v) {
            //站点
            $order_platform = 2;
            //时间
            $time_str = '2020-07-01 00:00:00 - 2021-06-31 23:59:59';
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $order_platform];
            $sku = $v['sku'];
            $item_platform = new ItemPlatformSku();
            $sku = $item_platform->where('sku', $sku)->where('platform_type', $order_platform)->value('platform_sku') ? $item_platform->where('sku', $sku)->where('platform_type', $order_platform)->value('platform_sku') : $sku;

            $model = Db::connect('database.db_voogueme');
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
        $list = Db::name('hedan_kuwei_2')->where('id', '>', 0)->select();
        foreach ($list as $k => $v) {
            $list[$k]['stock_id'] = 2;
            $list[$k]['area_id'] = 6;
            $list[$k]['type'] = 2;
            $list[$k]['createtime'] = '2021-05-28 15:03:31';
            $list[$k]['create_person'] = 'Admin';
            $list[$k]['shelf_number'] = '';
            unset($list[$k]['id']);
        }
        Db::name('store_house')->insertAll($list);
        dump($list);
    }
    public function hedankuwei2()
    {
        $list = Db::name('hedan_kuwei_2')->where('id', '>', 0)->select();
        foreach ($list as $k => $v) {
            $subarea = explode('-',$v['coding'])[0];
            $location = explode('-',$v['coding'])[1].'-'.explode('-',$v['coding'])[2].'-'.explode('-',$v['coding'])[3];
            Db::name('hedan_kuwei_2')->where('id',$v['id'])->update(['subarea'=>$subarea,'location'=>$location]);
        }
    }
    public function kuweiliebiao()
    {
        $list = Db::name('hedan_house')->select();
        $arr = [];
        foreach ($list as $k => $v) {
            $arr[$k]['stock_id'] = 2;
            $arr[$k]['area_id'] = 6;
            $arr[$k]['shelf_number'] = explode('-',$v['coding'])[0];
            $arr[$k]['coding'] = $v['coding'];
            $arr[$k]['type'] = 1;
            $arr[$k]['createtime'] = '2021-05-28 15:03:31';
            $arr[$k]['create_person'] = 'Admin';
        }
        // dump($arr);die;
        Db::name('store_house')->insertAll($arr);

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
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
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
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
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
        if (strtotime($start_time) > strtotime($end_time)) [$start_time, $end_time] = array($end_time, $start_time);

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

    /**
     * 角色权限导出
     * @author gyh
     * @date   2021/10/15 10:15:44
     */
    public function roleExport(){
        $page = input('page',0);
        $AuthGroupmodel = model('AuthGroup');
        $AuthRulemodel = model('AuthRule');

        $childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = collection(AuthGroup::where('id', 'in', $childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $result = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        } else {
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $result = array_merge($result, Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['pid'])));
            }
        }
        $groupName = [];
        foreach ($result as $k => $v) {
            $groupName[$v['id']] = $v['name'];
        }

        

        $list = AuthGroup::all(array_keys($groupName));
        $list = collection($list)->toArray();
        $groupList = [];
        foreach ($list as $k => $v) {
            $groupList[$v['id']] = $v;
        }
        $list = [];
        foreach ($groupName as $k => $v) {
            if (isset($groupList[$k])) {
                $groupList[$k]['name'] = $v;
                $list[] = $groupList[$k];
            }
        }

        $exp_data = [];
        $count = count($list);//总条数
        $start=($page-1)*10;//偏移量，当前页-1乘以每页显示条数
        $list = array_slice($list,$start,10);

        foreach ($list as $key => $value) {
                if ($value['rules']) {
                $ruleList = collection($AuthRulemodel->field('title,pid')->order('weigh', 'desc')->order('id', 'asc')->select())->toArray();
                }else{
                    $ruleList = collection($AuthRulemodel->field('title,pid')->order('weigh', 'desc')->order('id', 'asc')->where('id','in',$value['rules'])->select())->toArray();
                }
                
                $data = [];
                foreach ($ruleList as $ke => $valu) {
                    if ($valu['pid']!= 0) {
                        $data[$valu['pid']][] = $valu;
                    }
                    
                }
                $info = [];
                foreach ($data as $k => $val) {
                    $title = $AuthRulemodel->where('id',$k)->value('title');
                    $arr = array_column($val, 'title');
                    $info[] = '['.$title.']:('.implode(',', $arr).')';
                }
                $rules = implode(';', $info);
                $name = str_replace("&nbsp;"," ",$value['name']);
                $exp_data[] = ['id'=>$value['id'],'pid'=>$value['pid'],'name'=>$name,'rules'=>$rules];
            
        }

        $headlist = ['id', '父级', '名称', '权限'];
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "id");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "父级");
        $spreadsheet->getActiveSheet()->setCellValue("C1", "名称");
        $spreadsheet->getActiveSheet()->setCellValue("D1", "权限");

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(60);

        $spreadsheet->setActiveSheetIndex(0);
        $num = 0;
        foreach ($exp_data as $k => $v) {
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['id']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['pid']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['name']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['rules']);
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
        $savename = '角色权限导出';
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

    /**
     * 账号权限导出
     * @author gyh
     * @date   2021/10/15 10:15:44
     */
    public function userroleExport(){
        $page = input('page',0);
        $count = input('count',10);
        $usermodel = model('Admin');
        $start = ($page-1)*$count;//偏移量，当前页-1乘以每页显示条数
        $user = collection($usermodel->field('id,username,nickname')->limit($start,$count)->select())->toArray();
        foreach ($user as $key => $value) {
            $group = Db::name('auth_group_access')->field('b.rules,b.name')->join(['fa_auth_group' => 'b'], 'group_id=b.id', 'left')->where('uid', $value['id'])->select();
            $rulesarr = array_column($group, 'rules');
            $name = array_column($group, 'name');
            $user[$key]['group_text'] = implode(',', $name);
            $rules = implode(',', $rulesarr);
            $arr = explode(',', $rules);
            if (in_array('*', $rulesarr)) {
                $user[$key]['rules'] = '*';
            }else{
                $user[$key]['rules'] = implode(',', array_unique($arr));
            }
        }

        $AuthRulemodel = model('AuthRule');
        foreach ($user as $key => $value) {
            if ($value['rules'] == '*') {
                $rules = '*';
            }else{
               if ($value['rules']) {
                $ruleList = collection($AuthRulemodel->field('title,pid')->order('weigh', 'desc')->order('id', 'asc')->select())->toArray();
                }else{
                    $ruleList = collection($AuthRulemodel->field('title,pid')->order('weigh', 'desc')->order('id', 'asc')->where('id','in',$value['rules'])->select())->toArray();
                }
                
                $data = [];
                foreach ($ruleList as $ke => $valu) {
                    if ($valu['pid']!= 0) {
                        $data[$valu['pid']][] = $valu;
                    }
                    
                }
                $info = [];
                foreach ($data as $k => $val) {
                    $title = $AuthRulemodel->where('id',$k)->value('title');
                    $arr = array_column($val, 'title');
                    $info[] = '['.$title.']:('.implode(',', $arr).')';
                }
                $rules = implode(';', $info); 
            }
                
                $exp_data[] = ['id'=>$value['id'],'username'=>$value['username'],'nickname'=>$value['nickname'],'name'=>$value['group_text'],'rules'=>$rules];
            
        }

        $headlist = ['id', '用户名', '昵称', '角色', '权限'];
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "id");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "用户名");
        $spreadsheet->getActiveSheet()->setCellValue("C1", "昵称");
        $spreadsheet->getActiveSheet()->setCellValue("D1", "角色");
        $spreadsheet->getActiveSheet()->setCellValue("E1", "权限");

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(60);

        $spreadsheet->setActiveSheetIndex(0);
        $num = 0;
        foreach ($exp_data as $k => $v) {
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['id']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['username']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['nickname']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['name']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['rules']);
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
        $savename = '角色权限导出';
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

    public function return_department_name($department_id,$name = ''){   
        $departmentmodel = model('Department');   
        $pid = $departmentmodel->where(['department_id'=>$department_id])->value('pid');
        
        if(!$pid){
            return $name;
        } 
        
        if($pid == 1){
            
            if($name == null){
                $name = $departmentmodel->where(['department_id'=>$department_id])->value('name');
            }else{
                $name = $departmentmodel->where(['department_id'=>$department_id])->value('name').'-'.$name;
            }
            return $name;   
        }else{
            
            if($name == null){
                $name = $departmentmodel->where(['department_id'=>$department_id])->value('name');
            }else{
                $name = $departmentmodel->where(['department_id'=>$department_id])->value('name').'-'.$name;
            }
            $f_department_id = $departmentmodel->where(['id'=>$pid])->value('department_id');
            return self::return_department_name($f_department_id,$name);
        }
        
    }

    public function export_runwangtao_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $where['library_status'] = 1;
        $time = date("Y-m-d H:i:s");
        $time3 = date("Y-m-d H:i:s", strtotime("-3 month"));
        $time6 = date("Y-m-d H:i:s", strtotime("-6 month"));
        $time9 = date("Y-m-d H:i:s", strtotime("-9 month"));
        $time12 = date("Y-m-d H:i:s", strtotime("-12 month"));

        //9-12个月
        $data12 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time12,$time9])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //6-9
        $data9 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time9,$time6])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //3-6
        $data6 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time6,$time3])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //0-3
        $data3 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time3,$time])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();

        //12以上
        $data13 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','<',$time12)
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();


        $spreadsheet = new Spreadsheet();
        $pIndex = 0;
        if (!empty($data3)){
            //从数据库查询需要的数据
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "库存");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('0-3个月');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($data3 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['stock']);
                $num += 1;
            }
            $pIndex += 1;
        }
        if (!empty($data6)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "库存");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('3-6个月');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($data6 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['stock']);
                $num += 1;
            }
            $pIndex += 1;
        }

        if (!empty($data9)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "库存");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('6-9个月');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($data9 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['stock']);
                $num += 1;
            }
            $pIndex += 1;
        }

        if (!empty($data12)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "库存");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('9-12个月');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($data12 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['stock']);
                $num += 1;
            }
            $pIndex += 1;
        }

        if (!empty($data13)){
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($pIndex);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "SKU");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "库存");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->setActiveSheetIndex($pIndex)->setTitle('12个月以上');
            $spreadsheet->setActiveSheetIndex($pIndex);
            $num = 0;
            foreach ($data13 as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['stock']);
                $num += 1;
            }
            $pIndex += 1;
        }

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '导出现在库SKU库存、库龄、数量数据';
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

    /**
     * 跑库龄概况
     *
     * @return void
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @author jianghaohui
     * @date   2021/11/18 13:37:29
     */
    public function stock_age_overview()
    {
        $this->item = new ProductBarCodeItem;
        $where['library_status'] = 1;
        $time = date("Y-m-d H:i:s");
        $time3 = date("Y-m-d H:i:s", strtotime("-3 month"));
        $time6 = date("Y-m-d H:i:s", strtotime("-6 month"));
        $time9 = date("Y-m-d H:i:s", strtotime("-9 month"));
        $time12 = date("Y-m-d H:i:s", strtotime("-12 month"));

        //9-12个月
        $data12 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time12,$time9])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //6-9
        $data9 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time9,$time6])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //3-6
        $data6 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time6,$time3])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //0-3
        $data33 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','between',[$time3,$time])
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();

        //12以上
        $data13 = $this->item
            ->field('sku,in_stock_time,count(*) as stock')
            ->where($where)
            ->where('in_stock_time','<',$time12)
            ->where('in_stock_time is not null')
            ->group('sku')
            ->select();
        //sku数量
        $data1 = count($data33);
        $data2 = count($data6);
        $data3 = count($data9);
        $data4 = count($data12);
        $data5 = count($data13);
        $count = $data1 + $data2 + $data3 + $data4 + $data5;
        //库存
        $stock1 = array_sum(array_column($data33, 'stock'));
        $stock2 = array_sum(array_column($data6, 'stock'));
        $stock3 = array_sum(array_column($data9, 'stock'));
        $stock4 = array_sum(array_column($data12, 'stock'));
        $stock5 = array_sum(array_column($data13, 'stock'));
        $stock = $stock1 + $stock2 + $stock3 + $stock4 + $stock5;

        $total = $this->item->alias('i')->join('fa_purchase_order_item oi',
            'i.purchase_id=oi.purchase_id and i.sku=oi.sku')->join('fa_purchase_order o',
            'o.id=i.purchase_id')->where($where)->where('in_stock_time is not null')->value('SUM(IF(actual_purchase_price,actual_purchase_price,o.purchase_total/purchase_num)) price');

        $sql5 = $this->item->where($where)->where('in_stock_time is not null')->field('distinct sku')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("i.sku in " . $sql5)];

        $sql6 = $this->item->alias('i')->join('fa_purchase_order_item oi',
            'i.purchase_id=oi.purchase_id and i.sku=oi.sku')->join('fa_purchase_order o',
            'o.id=i.purchase_id')->field('TIMESTAMPDIFF( MONTH, min(in_stock_time), now()) AS total,SUM(IF(actual_purchase_price,actual_purchase_price,o.purchase_total/purchase_num)) price')->where($where)->where($arr_where)->where('in_stock_time is not null')->group('i.sku')->buildSql();

        $total_info = $this->item->table([$sql6 => 't2'])->field('sum(IF( total>= 0 AND total< 4, price, 0 )) AS a,sum(IF( total>= 4 AND total< 7, price, 0 )) AS b,sum(IF( total>= 7 AND total< 10, price, 0 )) AS c,sum(IF( total>= 10 AND total< 13, price, 0 )) AS d')->select();
        $total1 = round($total_info[0]['a'], 2);
        $total2 = round($total_info[0]['b'], 2);
        $total3 = round($total_info[0]['c'], 2);
        $total4 = round($total_info[0]['d'], 2);

        $total5 = round(($total - $total1 - $total2 - $total3 - $total4), 2);

        $percent1 = $count ? round($data1 / $count * 100, 2) : 0;
        $percent2 = $count ? round($data2 / $count * 100, 2) : 0;
        $percent3 = $count ? round($data3 / $count * 100, 2) : 0;
        $percent4 = $count ? round($data4 / $count * 100, 2) : 0;
        $percent5 = $count ? round($data5 / $count * 100, 2) : 0;

        $stock_percent1 = $stock ? round($stock1 / $stock * 100, 2) : 0;
        $stock_percent2 = $stock ? round($stock2 / $stock * 100, 2) : 0;
        $stock_percent3 = $stock ? round($stock3 / $stock * 100, 2) : 0;
        $stock_percent4 = $stock ? round($stock4 / $stock * 100, 2) : 0;
        $stock_percent5 = $stock ? round($stock5 / $stock * 100, 2) : 0;

        $arr = [
            [
                'age' => '0~3月',
                'sku_num' => $data1,
                'sku_percent' => $percent1,
                'stock' => $stock1,
                'stock_percent' => $stock_percent1,
                'date' => date("Y-m-d", strtotime("-1 day")),
                'money' => $total1
            ],
            [
                'age' => '4~6月',
                'sku_num' => $data2,
                'sku_percent' => $percent2,
                'stock' => $stock2,
                'stock_percent' => $stock_percent2,
                'date' => date("Y-m-d", strtotime("-1 day")),
                'money' => $total2
            ],
            [
                'age' => '7~9月',
                'sku_num' => $data3,
                'sku_percent' => $percent3,
                'stock' => $stock3,
                'stock_percent' => $stock_percent3,
                'date' => date("Y-m-d", strtotime("-1 day")),
                'money' => $total3
            ],
            [
                'age' => '10~12月',
                'sku_num' => $data4,
                'sku_percent' => $percent4,
                'stock' => $stock4,
                'stock_percent' => $stock_percent4,
                'date' => date("Y-m-d", strtotime("-1 day")),
                'money' => $total4
            ],
            [
                'age' => '12个月以上',
                'sku_num' => $data5,
                'sku_percent' => $percent5,
                'stock' => $stock5,
                'stock_percent' => $stock_percent5,
                'date' => date("Y-m-d", strtotime("-1 day")),
                'money' => $total5
            ],
            [
                'age' => '总计',
                'sku_num' => $count,
                'sku_percent' => '100%',
                'stock' => $stock,
                'stock_percent' => '100%',
                'date' => date("Y-m-d", strtotime("-1 day")),
                'money' => round($total, 2),
            ]
        ];
        $res = Db::name('stock_age_day_data')->insertAll($arr);
        if ($res){
            echo 'ok';
        }else{
            echo 'failed';
        }

    }

    public function export_caiwu_data()
    {
        $startTime = input('start').' 00:00:00';
        $endTime = input('end').' 23:59:59';
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $item = new Item();
//        $startTime = $month.'-01 00:00:00';
//        $endTime = $month.'-31 23:59:59';
        $writeDownWhere['a.check_time'] = ['between',[$startTime,$endTime]];

        $instockArr = Db::name('in_stock')
            ->alias('a')
            ->join(['fa_in_stock_item' => 'b'], 'a.id=b.in_stock_id', 'left')
            ->join(['fa_check_order' => 'c'], 'a.check_id=c.id')
            ->join(['fa_purchase_order' => 'd'], 'c.purchase_id=d.id')
            ->join(['fa_purchase_order_item' => 'e'], 'e.purchase_id = d.id')
            ->where($writeDownWhere)
            ->field('a.check_time,a.in_stock_number,b.sku,d.supplier_id,b.in_stock_num,e.purchase_price,e.actual_purchase_price,a.warehouse_id,d.purchase_number,d.1688_number')
            ->select();
        foreach ($instockArr as $k => $v) {
            if ($v['supplier_id']){
                $instockArr[$k]['suppplier_name'] = Db::name('supplier')->where('id',$v['supplier_id'])->value('supplier_name');
            }
            if ($v['actual_purchase_price'] > 0){
                $instockArr[$k]['total'] =$v['in_stock_num'] * $v['actual_purchase_price'];
            }else{
                $instockArr[$k]['total'] =$v['in_stock_num'] * $v['purchase_price'];
            }
            $instockArr[$k]['warehouse_name'] = $v['warehouse_id'] == 1 ?'郑州仓':'丹阳仓';
            $instockArr[$k]['sku_name'] = $item->where('sku',$v['sku'])->value('name');
        }

        $spreadsheet = new Spreadsheet();
        $pIndex = 0;
        if (!empty($instockArr)){
            //从数据库查询需要的数据
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "入库时间");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "仓库");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "供应商");
            $spreadsheet->getActiveSheet()->setCellValue("D1", "商品名称");
            $spreadsheet->getActiveSheet()->setCellValue("E1", "sku");
            $spreadsheet->getActiveSheet()->setCellValue("F1", "数量（个）");
            $spreadsheet->getActiveSheet()->setCellValue("G1", "金额");
            $spreadsheet->getActiveSheet()->setCellValue("H1", "入库单号");
            $spreadsheet->getActiveSheet()->setCellValue("I1", "采购单号");
            $spreadsheet->getActiveSheet()->setCellValue("J1", "1688单号");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(22);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('数据');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($instockArr as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['check_time']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['warehouse_name']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['suppplier_name']);
                $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['sku_name']);
                $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['in_stock_num']);
                $spreadsheet->getActiveSheet()->setCellValue('G' . ($num * 1 + 2), $v['total']);
                $spreadsheet->getActiveSheet()->setCellValue('H' . ($num * 1 + 2), $v['in_stock_number']);
                $spreadsheet->getActiveSheet()->setCellValue('I' . ($num * 1 + 2), $v['purchase_number']);
                $spreadsheet->getActiveSheet()->setCellValue('J' . ($num * 1 + 2), $v['1688_number']);
                $num += 1;
            }
        }

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '财务数据'.$month;
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
    public function export_warehouse_data()
    {
        $stock_id = input('stock_id');
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $item = new Item();

        $arr = Db::name('product_barcode_item')
            ->where('library_status',1)
            ->where('sku','<>','')
            ->where('purchase_id','<>','')
            ->where('stock_id',$stock_id)
            ->field('sku,purchase_id,count(*) as count')
            ->group('sku,purchase_id')
            ->select();
        //查询商品分类
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $category = $this->category->where('is_del', 1)->column('name', 'id');
        foreach ($arr as $k => $v) {
            $v['category_id'] =$item->where('sku',$v['sku'])->value('category_id');
            $arr[$k]['category_name'] = $category[$v['category_id']];
            $purchase = Db::name('purchase_order_item')->where('purchase_id',$v['purchase_id'])->find();
            $arr[$k]['purchase_number'] = $purchase['purchase_order_number'];
            $arr[$k]['price'] = $purchase['actual_purchase_price'] > 0 ? $purchase['actual_purchase_price'] : $purchase['purchase_price'];
        }

        $spreadsheet = new Spreadsheet();
        $pIndex = 0;
        if (!empty($arr)){
            //从数据库查询需要的数据
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "sku");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "商品分类");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "在库数量");
            $spreadsheet->getActiveSheet()->setCellValue("D1", "成本价/采购价");
            $spreadsheet->getActiveSheet()->setCellValue("E1", "采购单号");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(22);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('数据');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($arr as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['category_name']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['count']);
                $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['price']);
                $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['purchase_number']);
                $num += 1;
            }
        }

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '仓库数据';
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
    public function export_supplier_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $instockArr = Db::name('supplier')
            ->field('id,supplier_name,supplier_type_pattern,recipient_name,bank_account,opening_bank,remark,supplier_type,period')
            ->select();

        $spreadsheet = new Spreadsheet();
        $pIndex = 0;
        if (!empty($instockArr)){
            //从数据库查询需要的数据
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "ID");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "供应商名称");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "供应商类型");
            $spreadsheet->getActiveSheet()->setCellValue("D1", "收款人名称");
            $spreadsheet->getActiveSheet()->setCellValue("E1", "账号");
            $spreadsheet->getActiveSheet()->setCellValue("F1", "开户行");
            $spreadsheet->getActiveSheet()->setCellValue("G1", "供应商备注");
            $spreadsheet->getActiveSheet()->setCellValue("H1", "主营类目");
            $spreadsheet->getActiveSheet()->setCellValue("I1", "账期");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(22);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('数据');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($instockArr as $k=>$v){
                if ($v['supplier_type'] == 1){
                    $name = '镜片';
                }else if ($v['supplier_type'] == 2){
                    $name = '镜架';
                }else if ($v['supplier_type'] == 3){
                    $name = '眼镜盒';
                }else{
                    $name = '镜布';
                }
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['id']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['supplier_name']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['supplier_type_pattern'] == 1 ? '工厂':'贸易');
                $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['recipient_name']);
                $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['bank_account']);
                $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['opening_bank']);
                $spreadsheet->getActiveSheet()->setCellValue('G' . ($num * 1 + 2), $v['remark']);
                $spreadsheet->getActiveSheet()->setCellValue('H' . ($num * 1 + 2), $name);
                $spreadsheet->getActiveSheet()->setCellValue('I' . ($num * 1 + 2), $v['period']);
                $num += 1;
            }
        }

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '财务数据';
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
    public function export_z_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $params['site'] = 1;
        $start = strtotime('2021-11-14 00:00:00');
        $end = strtotime('2021-12-13 23:59:59');
        $map['payment_time'] = ['between', [$start, $end]];

        //列表
        $result = [];
        //查询对应平台销量
        $info = $this->getOrderSalesNum($params['site'],$map);
        $list = $info['data'];
        //查询对应平台商品SKU
        $skus = $this->itemPlatformSku->getWebSkuAll($params['site']);
        $productInfo = $this->item->getSkuInfo();
        $list = $list ?? [];
        $i = 0;
        $nowDate = date('Y-m-d H:i:s');
        foreach ($list as $k => $v) {
            $result[$i]['platformsku'] = $k;
            $result[$i]['sku'] = $skus[trim($k)]['sku'];
            //上架时间
            $shelvesTime = Db::name('sku_shelves_time')
                ->where(['site'=>$params['site'],'platform_sku'=>$k])
                ->value('shelves_time');
            $result[$i]['shelves_date'] = date('Y-m-d H:i:s',$shelvesTime);
            $result[$i]['type_name'] = $productInfo[$skus[trim($k)]['sku']]['type_name'];
            $result[$i]['available_stock'] = $skus[trim($k)]['stock'];  //虚拟仓库存
            $result[$i]['sales_num'] = $v;

            //在线状态（实时）
            $stockInfo = $this->itemPlatformSku
                ->where(['platform_type'=>$params['site'],'platform_sku'=>$k])
                ->field('stock,outer_sku_status,presell_status,presell_start_time,presell_end_time,presell_num')
                ->find();
            if($stockInfo['outer_sku_status'] == 1){
                if($stockInfo['stock'] > 0){
                    $result[$i]['online_status'] = '在线';  //在线
                }else{
                    if($stockInfo['presell_status'] == 1 && $nowDate >= $stockInfo['presell_start_time'] && $nowDate <= $stockInfo['presell_end_time']){
                        if($stockInfo['presell_num'] > 0){
                            $result[$i]['online_status'] = '在线';  //在线
                        }else{
                            $result[$i]['online_status'] = '售罄';  //售罄
                        }
                    }else{
                        $result[$i]['online_status'] = '售罄';  //售罄
                    }
                }
            }else{
                $result[$i]['online_status'] = '下架';  //下架
            }
            $i++;
        }
        $spreadsheet = new Spreadsheet();
        $pIndex = 0;
        if (!empty($result)){
            //从数据库查询需要的数据
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "平台sku");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "sku");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "上架时间");
            $spreadsheet->getActiveSheet()->setCellValue("D1", "分类");
            $spreadsheet->getActiveSheet()->setCellValue("E1", "虚拟仓库存");
            $spreadsheet->getActiveSheet()->setCellValue("F1", "销量");
            $spreadsheet->getActiveSheet()->setCellValue("G1", "在售状态（实时）");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(22);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(22);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('数据');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($result as $k=>$v){
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['platformsku']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['sku']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['shelves_date']);
                $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['type_name']);
                $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['available_stock']);
                $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['sales_num']);
                $spreadsheet->getActiveSheet()->setCellValue('G' . ($num * 1 + 2), $v['online_status']);
                $num += 1;
            }
        }

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = 'SKU销售数据zeelool';
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

    //新的信息都从mojing_base获取
    public function getOrderSalesNum($site,$timeWhere,$pages=[],$sku = '')
    {
        if($sku){
            $map['p.sku'] = $sku;
        }else{
            $map['p.sku'] = ['not like', '%Price%'];
        }
        $map['o.site'] = $site;
        $map['o.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered','delivery','shipped']];
        if($pages['limit']){
            if(isset($pages['offset'])){
                $res['data'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->group('sku')
                    ->order('num desc')
                    ->limit($pages['offset'],$pages['limit'])
                    ->column('sum(p.qty) as num', 'p.sku');
                $res['count'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->count('distinct sku');
            }else{
                $res['data'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->group('sku')
                    ->order('num desc')
                    ->limit($pages['limit'])
                    ->column('sum(p.qty) as num', 'p.sku');
                $res['count'] = $this->order
                    ->alias('o')
                    ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                    ->where($map)
                    ->where($timeWhere)
                    ->count('distinct sku');
            }
        }else{
            $res['data'] = $this->order
                ->alias('o')
                ->join('fa_order_item_option p','o.entity_id=p.magento_order_id and o.site=p.site')
                ->where($map)
                ->where($timeWhere)
                ->group('sku')
                ->order('num desc')
                ->column('sum(p.qty) as num', 'p.sku');
        }
        return $res;
    }
}
