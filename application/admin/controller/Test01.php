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
        $list = Db::connect('database.db_voogueme_online')->table('sales_flat_order')->where('entity_id > 272779 and entity_id < 273408')->select();
        foreach ($list as $k => $v) {
            $count = Db::connect('database.db_voogueme')->table('sales_flat_order')->where(['entity_id' => $v['entity_id']])->count();
            if ($count > 0) {
                $data = [];
                $data['created_at'] = $v['created_at'];
                $data['updated_at'] = $v['updated_at'];
                $res = Db::connect('database.db_voogueme')->table('sales_flat_order')->where(['entity_id' => $v['entity_id']])->update($data);
                echo Db::connect('database.db_voogueme')->table('sales_flat_order')->getLastSql();
                echo "\n";
            }

            // Db::connect('database.db_voogueme')->table('sales_flat_order')->insert($v);

        }

        echo 'ok';
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
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        // dump($data);die;
        // $data = '2020-10-23';
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status,stock,plat_on_way_stock')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 1, 'outer_sku_status' => 1])
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
                        $arr[$v['sku']]['site'] = 1;
                        $arr[$v['sku']]['day_date'] = $data;
                        $arr[$v['sku']]['day_stock'] = $v['stock'];
                        $arr[$v['sku']]['day_onway_stock'] = $v['plat_on_way_stock'];
                    }
                }
            }
            // dump($arr[$v['sku']]);
            if (!empty($arr[$v['sku']])) {
                Db::name('datacenter_sku_day')->insert($arr[$v['sku']]);
                echo $v['sku'] . "\n";
                echo '<br>';
                usleep(100000);
            }
        }

        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status,stock,plat_on_way_stock')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 2, 'outer_sku_status' => 1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(2, $data);
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
                        $arr[$v['sku']]['site'] = 2;
                        $arr[$v['sku']]['day_date'] = $data;
                        $arr[$v['sku']]['day_stock'] = $v['stock'];
                        $arr[$v['sku']]['day_onway_stock'] = $v['plat_on_way_stock'];
                    }
                }
            }
            // dump($arr[$v['sku']]);
            if (!empty($arr[$v['sku']])) {
                Db::name('datacenter_sku_day')->insert($arr[$v['sku']]);
                echo $v['sku'] . "\n";
                echo '<br>';
                usleep(100000);
            }
        }

        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status,stock,plat_on_way_stock')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 3, 'outer_sku_status' => 1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $zeeloolOperate->google_sku_detail(3, $data);
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
                        $arr[$v['sku']]['site'] = 3;
                        $arr[$v['sku']]['day_date'] = $data;
                        $arr[$v['sku']]['day_stock'] = $v['stock'];
                        $arr[$v['sku']]['day_onway_stock'] = $v['plat_on_way_stock'];
                    }
                }
            }
            // dump($arr[$v['sku']]);
            if (!empty($arr[$v['sku']])) {
                Db::name('datacenter_sku_day')->insert($arr[$v['sku']]);
                echo $v['sku'] . "\n";
                echo '<br>';
                usleep(100000);
            }
        }
        // dump($arr);
    }

    public function sku_day_data_order()
    {
        $this->sku_day_data_order_z();
        $this->sku_day_data_order_v();
        $this->sku_day_data_order_n();
    }

    public function sku_day_data_other()
    {
        $this->sku_day_data_other_z();
        $this->sku_day_data_other_v();
        $this->sku_day_data_other_n();
    }

    //sku某一天的订单数量 销售额 实际支付的金额 现价 商品类型 销量
    public function sku_day_data_order_z()
    {
        Db::connect('database.db_zeelool')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_zeelool')->table('sales_flat_order')->query("set time_zone='+8:00'");
        set_time_limit(0);
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 1, 'outer_sku_status' => 1])
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
            //销售副数
            $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->count('qty_ordered');
            $arr[$key]['sales_num'] = $arr[$key]['glass_num'];

            $map['b.sku'] = ['like', $value['platform_sku'] . '%'];
            // $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
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

                //商品的类型
                if (!$arr[$key]['goods_type']) {
                    $arr[$key]['goods_type'] = $vv['goods_type'];
                }
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']) {
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']) {
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = 0;
            }
            if (!$arr[$key]['goods_type']) {
                $arr[$key]['goods_type'] = 1;
            }
            //副单价
            $arr[$key]['single_price'] = $arr[$key]['glass_num'] == 0 ? 0 : round($arr[$key]['sku_row_total'] / $arr[$key]['glass_num'], 2);
            if (!empty($arr[$key])) {
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku' => $arr[$key]['sku'], 'day_date' => $arr[$key]['day_date'], 'site' => $arr[$key]['site']])
                    ->update(['glass_num' => $arr[$key]['glass_num'], 'sales_num' => $arr[$key]['sales_num'], 'order_num' => $arr[$key]['order_num'], 'sku_grand_total' => $arr[$key]['sku_grand_total'], 'single_price' => $arr[$key]['single_price'], 'sku_row_total' => $arr[$key]['sku_row_total'], 'goods_type' => $arr[$key]['goods_type']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }
        }
        // dump($arr);
    }

    //销售副数 副单价 购物车数量
    public function sku_day_data_other_z()
    {
        set_time_limit(0);
        Db::connect('database.db_zeelool')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1, 'outer_sku_status' => 1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //统计某个sku某一天的数据
        foreach ($sku_data as $key => $value) {
            // //销售副数
            // $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
            //     ->where('sku', 'like', $value['platform_sku'] . '%')
            //     ->where($time_where)
            //     ->count('qty_ordered');
            // $arr[$key]['sales_num'] = $arr[$key]['glass_num'];

            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            //购物车数量
            $zeelool_model = Db::connect('database.db_zeelool_online');
            $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $value['platform_sku'] . '%'];
            $arr[$key]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            //找到商品的现价
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool_online')
                    // $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool')
                    ->table('catalog_product_index_price')//为了获取现价找的表
                    ->alias('a')
                    ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id')//商品主表
                    ->where('b.sku', 'like', $value['platform_sku'] . '%')
                    ->value('a.final_price');
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']) {
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']) {
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = 0;
            }

            if (!empty($arr[$key])) {
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku' => $arr[$key]['sku'], 'day_date' => $arr[$key]['day_date'], 'site' => $arr[$key]['site']])
                    ->update(['cart_num' => $arr[$key]['cart_num'], 'now_pricce' => $arr[$key]['now_pricce']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }
        }
        // dump($arr);
    }

    //sku某一天的订单数量 销售额 实际支付的金额 现价 商品类型 销量
    public function sku_day_data_order_v()
    {
        Db::connect('database.db_voogueme')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_voogueme')->table('sales_flat_order')->query("set time_zone='+8:00'");
        set_time_limit(0);
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 2, 'outer_sku_status' => 1])
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
            $arr[$key]['order_num'] = Db::connect('database.db_voogueme')
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->distinct('order_id')
                ->field('order_id,created_at')
                ->count();
            //销售副数
            $arr[$key]['glass_num'] = Db::connect('database.db_voogueme')->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->count('qty_ordered');
            $arr[$key]['sales_num'] = $arr[$key]['glass_num'];

            $map['b.sku'] = ['like', $value['platform_sku'] . '%'];
            // $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_voogueme')
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

                //商品的类型
                if (!$arr[$key]['goods_type']) {
                    $arr[$key]['goods_type'] = $vv['goods_type'];
                }
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 2;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']) {
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']) {
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = 0;
            }
            if (!$arr[$key]['goods_type']) {
                $arr[$key]['goods_type'] = 1;
            }
            //副单价
            $arr[$key]['single_price'] = $arr[$key]['glass_num'] == 0 ? 0 : round($arr[$key]['sku_row_total'] / $arr[$key]['glass_num'], 2);
            if (!empty($arr[$key])) {
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku' => $arr[$key]['sku'], 'day_date' => $arr[$key]['day_date'], 'site' => $arr[$key]['site']])
                    ->update(['glass_num' => $arr[$key]['glass_num'], 'sales_num' => $arr[$key]['sales_num'], 'order_num' => $arr[$key]['order_num'], 'sku_grand_total' => $arr[$key]['sku_grand_total'], 'single_price' => $arr[$key]['single_price'], 'sku_row_total' => $arr[$key]['sku_row_total'], 'goods_type' => $arr[$key]['goods_type']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }
        }
        // dump($arr);
    }

    //销售副数 副单价 购物车数量
    public function sku_day_data_other_v()
    {
        set_time_limit(0);
        Db::connect('database.db_voogueme')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 2, 'outer_sku_status' => 1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //统计某个sku某一天的数据
        foreach ($sku_data as $key => $value) {
            // //销售副数
            // $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
            //     ->where('sku', 'like', $value['platform_sku'] . '%')
            //     ->where($time_where)
            //     ->count('qty_ordered');
            // $arr[$key]['sales_num'] = $arr[$key]['glass_num'];

            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 2;
            //购物车数量
            $zeelool_model = Db::connect('database.db_voogueme_online');
            $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $value['platform_sku'] . '%'];
            $arr[$key]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            //找到商品的现价
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = Db::connect('database.db_voogueme_online')
                    // $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool')
                    ->table('catalog_product_index_price')//为了获取现价找的表
                    ->alias('a')
                    ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id')//商品主表
                    ->where('b.sku', 'like', $value['platform_sku'] . '%')
                    ->value('a.final_price');
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 2;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']) {
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']) {
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = 0;
            }

            if (!empty($arr[$key])) {
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku' => $arr[$key]['sku'], 'day_date' => $arr[$key]['day_date'], 'site' => $arr[$key]['site']])
                    ->update(['cart_num' => $arr[$key]['cart_num'], 'now_pricce' => $arr[$key]['now_pricce']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }
        }
        // dump($arr);
    }

    //sku某一天的订单数量 销售额 实际支付的金额 现价 商品类型 销量
    public function sku_day_data_order_n()
    {
        Db::connect('database.db_nihao')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order')->query("set time_zone='+8:00'");
        set_time_limit(0);
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            // ->where(['platform_type' => 1])
            ->where(['platform_type' => 3, 'outer_sku_status' => 1])
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
            $arr[$key]['order_num'] = Db::connect('database.db_nihao')
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->distinct('order_id')
                ->field('order_id,created_at')
                ->count();
            //销售副数
            $arr[$key]['glass_num'] = Db::connect('database.db_nihao')->table('sales_flat_order_item')
                ->where('sku', 'like', $value['platform_sku'] . '%')
                ->where($time_where)
                ->count('qty_ordered');
            $arr[$key]['sales_num'] = $arr[$key]['glass_num'];

            $map['b.sku'] = ['like', $value['platform_sku'] . '%'];
            // $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_nihao')
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

                //商品的类型
                if (!$arr[$key]['goods_type']) {
                    $arr[$key]['goods_type'] = $vv['goods_type'];
                }
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 3;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']) {
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']) {
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = 0;
            }
            if (!$arr[$key]['goods_type']) {
                $arr[$key]['goods_type'] = 1;
            }
            //副单价
            $arr[$key]['single_price'] = $arr[$key]['glass_num'] == 0 ? 0 : round($arr[$key]['sku_row_total'] / $arr[$key]['glass_num'], 2);
            if (!empty($arr[$key])) {
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku' => $arr[$key]['sku'], 'day_date' => $arr[$key]['day_date'], 'site' => $arr[$key]['site']])
                    ->update(['glass_num' => $arr[$key]['glass_num'], 'sales_num' => $arr[$key]['sales_num'], 'order_num' => $arr[$key]['order_num'], 'sku_grand_total' => $arr[$key]['sku_grand_total'], 'single_price' => $arr[$key]['single_price'], 'sku_row_total' => $arr[$key]['sku_row_total'], 'goods_type' => $arr[$key]['goods_type']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }
        }
        // dump($arr);
    }

    //销售副数 副单价 购物车数量
    public function sku_day_data_other_n()
    {
        set_time_limit(0);
        Db::connect('database.db_nihao')->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        //统计昨天的数据
        $data = date('Y-m-d', strtotime('-1 day'));
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 3, 'outer_sku_status' => 1])
            ->select();

        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        $time_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
        //统计某个sku某一天的数据
        foreach ($sku_data as $key => $value) {
            // //销售副数
            // $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
            //     ->where('sku', 'like', $value['platform_sku'] . '%')
            //     ->where($time_where)
            //     ->count('qty_ordered');
            // $arr[$key]['sales_num'] = $arr[$key]['glass_num'];

            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 3;
            //购物车数量
            $zeelool_model = Db::connect('database.db_nihao_online');
            $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(a.created_at, '%Y-%m-%d') = '" . $data . "'")];
            $cart_where1['b.sku'] = ['like', $value['platform_sku'] . '%'];
            $arr[$key]['cart_num'] = $zeelool_model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($cart_where1)
                ->where('base_grand_total', 'gt', 0)
                ->field('b.sku,a.base_grand_total,a.created_at')
                ->count();
            //找到商品的现价
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = Db::connect('database.db_nihao_online')
                    // $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool')
                    ->table('catalog_product_index_price')//为了获取现价找的表
                    ->alias('a')
                    ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id')//商品主表
                    ->where('b.sku', 'like', $value['platform_sku'] . '%')
                    ->value('a.final_price');
            }
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 3;
            $arr[$key]['sku'] = $value['sku'];
            if (!$arr[$key]['sku_grand_total']) {
                $arr[$key]['sku_grand_total'] = 0;
            }
            if (!$arr[$key]['sku_row_total']) {
                $arr[$key]['sku_row_total'] = 0;
            }
            if (!$arr[$key]['now_pricce']) {
                $arr[$key]['now_pricce'] = 0;
            }

            if (!empty($arr[$key])) {
                //更新数据
                Db::name('datacenter_sku_day')
                    ->where(['sku' => $arr[$key]['sku'], 'day_date' => $arr[$key]['day_date'], 'site' => $arr[$key]['site']])
                    ->update(['cart_num' => $arr[$key]['cart_num'], 'now_pricce' => $arr[$key]['now_pricce']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }
        }
        // dump($arr);
    }

    //产品类型有点问题 跑一下数据
    public function test10()
    {
        set_time_limit(0);
        Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        $data = '2020-10-22';
        // $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        // $skus = Db::name('datacenter_sku_day')->field('sku,glass_num,sales_num,platform_sku')->select();
        // foreach ($skus as $k => $v) {
        //     // Db::name('datacenter_sku_day')->where(['sku'=>$v['sku']])->update(['sales_num'=>$v['glass_num']]);
        //     $map['sku'] = ['like', '%' . $v['platform_sku'] . '%'];
        //     // $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        //     //获取这个sku所有的订单情况
        //     $sku_order_data = Db::connect('database.db_zeelool')
        //         ->table('sales_flat_order_item_prescription')
        //         ->where($map)
        //         ->where($time_where1)
        //         ->field('sku,created_at,goods_type')
        //         ->select();
        //     dump($sku_order_data);
        //     $arr = [];
        //     //统计某个sku某一天的产品类型
        //     //     foreach ($sku_order_data as $kk => $vv) {
        //     //         // Db::name('datacenter_sku_day')->where(['sku'=>$v['sku']])->update(['goods_type'=>$vv['goods_type']]);
        //     //     }
        // }
        $time_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $data . "'")];
        $map['sku'] = ['=', 'ZOP012914-01'];
        $sku_order_data = Db::connect('database.db_zeelool')
            ->table('sales_flat_order_item_prescription')
            ->where($map)
            // ->where($time_where1)
            ->field('sku,created_at,goods_type')
            ->select();
        dump($sku_order_data);
        // dump($skus);
    }

    public function delete_purchase_data()
    {
        Db::name('purchase_order')->where('purchase_number', 'PO20201023090310717435')->update(['purchase_status' => 3]);
        // Db::name('purchase_order')->where('purchase_number','PO20201023090109313843')->update(['purchase_status'=>3]);
        // Db::name('purchase_order')->where('purchase_number','PO20201023090109313843')->update(['purchase_status'=>3]);
        // Db::name('purchase_order')->where('purchase_number','PO20201023090457598387')->update(['purchase_status'=>3]);
    }

    public function update_v_data()
    {
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-29'])->update(['detail_num'=>20520,'cart_num'=>4699]);
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-28'])->update(['detail_num'=>22869,'cart_num'=>5295]);
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-27'])->update(['detail_num'=>19959,'cart_num'=>4467]);
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-26'])->update(['detail_num'=>16710,'cart_num'=>3518]);
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-25'])->update(['detail_num'=>17248,'cart_num'=>3621]);
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-24'])->update(['detail_num'=>21029,'cart_num'=>4730]);
        // Db::name('datacenter_day')->where(['site'=>2,'day_date'=>'2020-10-23'])->update(['detail_num'=>22037,'cart_num'=>4948]);

    }

    //统计昨天各品类镜框的销量
    public function goods_type_day_center($plat, $goods_type)
    {
//        $plat = 1;
        $start = date('Y-m-d', strtotime('-1 day'));
        // $start = '2020-10-29';
        $seven_days = $start . ' 00:00:00 - ' . $start . ' 23:59:59';
        $createat = explode(' ', $seven_days);
        $itemMap['m.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        //判断站点
        switch ($plat) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            default:
                $model = false;
                break;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        //$whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal')";
        //某个品类眼镜的销售副数
        $frame_sales_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->sum('m.qty_ordered');
        //销售额
        // $this->zeelool = new \app\admin\model\order\order\Zeelool();
        // $order_where = [];
        // $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        // $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        // $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_grand_total');
        $sales_total_money = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->sum('o.base_grand_total');

        // $glass_avg_price = $frame_sales_num == 0 ? 0:round($sales_total_money / $frame_sales_num,2);
        $arr['day_date'] = $start;
        $arr['site'] = $plat;
        $arr['goods_type'] = $goods_type;
        $arr['glass_num'] = $frame_sales_num;
        $arr['sales_total_money'] = $sales_total_money;
        return $arr;
    }

    //计划任务跑每天的分类销量的数据
    public function day_data_goods_type()
    {
        // $arr = [
        //     $this->goods_type_day_center(1,1),
        //     $this->goods_type_day_center(1,2),
        //     $this->goods_type_day_center(1,3),
        //     $this->goods_type_day_center(1,4),
        //     $this->goods_type_day_center(1,5),
        //     $this->goods_type_day_center(1,6),
        //     $this->goods_type_day_center(2,1),
        //     $this->goods_type_day_center(2,2),
        //     $this->goods_type_day_center(2,6),
        //     $this->goods_type_day_center(3,1),
        //     $this->goods_type_day_center(3,2),
        // ];
        // dump($arr);die;
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 1));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 2));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 3));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 4));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 5));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(1, 6));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(2, 1));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(2, 2));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(2, 6));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(3, 1));
        Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(3, 2));
    }

    public function testtest()
    {
        Db::name('in_stock')->where('id',18574)->setField('status',1);
        // Db::name('in_stock')->where('id',18372)->setField('status',1);
    }

}
