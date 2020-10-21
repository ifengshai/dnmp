<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\admin\model\order\order\Zeelool;
use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class GoodsChange extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao();
    }

    public function index()
    {
        $start = date('Y-m-d', strtotime('-6 day'));
        $end = date('Y-m-d 23:59:59');
        $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        $order_platform = input('order_platform') ? input('order_platform') : 1;
        //默认七天的数据
        $create_time = input('time_str') ? input('time_str') : $seven_days;

        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        if (empty($orderPlatform)) {
            $this->error('您没有权限访问', 'general/profile?ref=addtabs');
        }
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = input('order_platform') ? input('order_platform') : 1;
            //时间
            $time_str = input('time_str') ? input('time_str') : $seven_days;

            //时间段总和
            $createat = explode(' ', $time_str);
            $where['site'] = $order_platform;
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $sku_data_day = Db::name('datacenter_sku_day')
                ->where($where)
                ->field('')
                ->limit(10)
                ->select();

            $total = Db::name('datacenter_sku_day')
                ->where($where)
                ->field('')
                ->count();
            foreach ($sku_data_day as $k => $v) {
                $sku_detail = $_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $order_platform])->field('platform_sku,stock,plat_on_way_stock,outer_sku_status')->find();

                //sku转换
                $sku_data_day[$k]['sku_change'] = $sku_detail['platform_sku'];
                //上下架状态
                $sku_data_day[$k]['status'] = $sku_detail['outer_sku_status'];
                $sku_data_day[$k]['stock'] = $sku_detail['stock'];
                $sku_data_day[$k]['on_way_stock'] = $sku_detail['plat_on_way_stock'];
                $sku_data_day[$k]['cart_change'] = $sku_data_day[$k]['cart_num'] == 0 ? 0 : round($sku_data_day[$k]['order_num'] / $sku_data_day[$k]['cart_num'] * 100, 2) . '%';
            }
            $result = array("total" => $total, "rows" => $sku_data_day);
            $this->assignconfig('label', $order_platform);
            $this->assignconfig('create_time', $create_time);
            return json($result);
        }
        $this->assign('orderPlatformList', $orderPlatform);
        $this->assignconfig('label', $order_platform);
        $this->assignconfig('create_time', $create_time);
        return $this->view->fetch();
    }

    // 商品转化率分析 产品等级转化情况
    public function sku_grade_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            // dump($params);
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            //时间
            $time_str = $params['time_str'];
            if (!$time_str) {
                //默认查询z站七天的数据
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
            }
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['site'] = $order_platform;
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $sku_data_day = Db::name('datacenter_sku_day')->where($where)->field('')->select();
            $sku_data_days = Db::name('datacenter_sku_day')->where('site', $order_platform)->field('')->select();

            //各个等级产品数量
            $arr = array_column($sku_data_days, 'goods_grade');
            $arr = array_count_values($arr);

            $arrs = [];
            foreach ($sku_data_day as $k => $v) {
                if ($arrs[$v['goods_grade']]) {
                    $arrs[$v['goods_grade']]['unique_pageviews'] += $v['unique_pageviews'];
                    $arrs[$v['goods_grade']]['cart_num'] += $v['cart_num'];
                    $arrs[$v['goods_grade']]['order_num'] += $v['order_num'];
                    $arrs[$v['goods_grade']]['sku_grand_total'] += $v['sku_grand_total'];
                } else {
                    $arrs[$v['goods_grade']]['unique_pageviews'] = $v['unique_pageviews'];
                    $arrs[$v['goods_grade']]['cart_num'] = $v['cart_num'];
                    $arrs[$v['goods_grade']]['order_num'] = $v['order_num'];
                    $arrs[$v['goods_grade']]['sku_grand_total'] = $v['sku_grand_total'];
                }

            }

            $a_plus['a_plus_num'] = $arr['A+'];
            $a_plus['a_plus_session_num'] = $arrs['A+']['unique_pageviews'];
            $a_plus['a_plus_cart_num'] = $arrs['A+']['cart_num'];
            $a_plus['a_plus_session_change'] = $arrs['A+']['unique_pageviews'] == 0 ? 0 : round($arrs['A+']['cart_num'] / $arrs['A+']['unique_pageviews'] * 100, 2) . '%';
            $a_plus['a_plus_order_num'] = $arrs['A+']['order_num'];
            $a_plus['a_plus_cart_change'] = $arrs['A+']['cart_num'] == 0 ? 0 : round($arrs['A+']['order_num'] / $arrs['A+']['cart_num'] * 100, 2) . '%';
            $a_plus['a_plus_sku_total'] = $arrs['A+']['sku_grand_total'];

            $aa['a_num'] = $arr['A'];
            $aa['a_session_num'] = $arrs['A']['unique_pageviews'];
            $aa['a_cart_num'] = $arrs['A']['cart_num'];
            $aa['a_session_change'] = $arrs['A']['unique_pageviews'] == 0 ? 0 : round($arrs['A']['cart_num'] / $arrs['A']['unique_pageviews'] * 100, 2) . '%';
            $aa['a_order_num'] = $arrs['A']['order_num'];
            $aa['a_cart_change'] = $arrs['A']['cart_num'] == 0 ? 0 : round($arrs['A']['order_num'] / $arrs['A']['cart_num'] * 100, 2) . '%';
            $aa['a_sku_total'] = $arrs['A']['sku_grand_total'];

            $bb['b_num'] = $arr['B'];
            $bb['b_session_num'] = $arrs['B']['unique_pageviews'];
            $bb['b_cart_num'] = $arrs['B']['cart_num'];
            $bb['b_session_change'] = $arrs['B']['unique_pageviews'] == 0 ? 0 : round($arrs['B']['cart_num'] / $arrs['B']['unique_pageviews'] * 100, 2) . '%';
            $bb['b_order_num'] = $arrs['B']['order_num'];
            $bb['b_cart_change'] = $arrs['B']['cart_num'] == 0 ? 0 : round($arrs['B']['order_num'] / $arrs['B']['cart_num'] * 100, 2) . '%';
            $bb['b_sku_total'] = $arrs['B']['sku_grand_total'];

            $cc['c_num'] = $arr['C'];
            $cc['c_session_num'] = $arrs['C']['unique_pageviews'];
            $cc['c_cart_num'] = $arrs['C']['cart_num'];
            $cc['c_session_change'] = $arrs['C']['unique_pageviews'] == 0 ? 0 : round($arrs['C']['cart_num'] / $arrs['C']['unique_pageviews'] * 100, 2) . '%';
            $cc['c_order_num'] = $arrs['C']['order_num'];
            $cc['c_cart_change'] = $arrs['C']['cart_num'] == 0 ? 0 : round($arrs['C']['order_num'] / $arrs['C']['cart_num'] * 100, 2) . '%';
            $cc['c_sku_total'] = $arrs['C']['sku_grand_total'];

            $c_plus['c_plus_num'] = $arr['C+'];
            $c_plus['c_plus_session_num'] = $arrs['C+']['unique_pageviews'];
            $c_plus['c_plus_cart_num'] = $arrs['C+']['cart_num'];
            $c_plus['c_plus_session_change'] = $arrs['C+']['unique_pageviews'] == 0 ? 0 : round($arrs['C+']['cart_num'] / $arrs['C+']['unique_pageviews'] * 100, 2) . '%';
            $c_plus['c_plus_order_num'] = $arrs['C+']['order_num'];
            $c_plus['c_plus_cart_change'] = $arrs['C+']['cart_num'] == 0 ? 0 : round($arrs['C+']['order_num'] / $arrs['C+']['cart_num'] * 100, 2) . '%';
            $c_plus['c_plus_sku_total'] = $arrs['C+']['sku_grand_total'];

            $ddd['d_num'] = $arr['D'];
            $ddd['d_session_num'] = $arrs['D']['unique_pageviews'];
            $ddd['d_cart_num'] = $arrs['D']['cart_num'];
            $ddd['d_session_change'] = $arrs['D']['unique_pageviews'] == 0 ? 0 : round($arrs['D']['cart_num'] / $arrs['D']['unique_pageviews'] * 100, 2);
            $ddd['d_order_num'] = $arrs['D']['order_num'];
            $ddd['d_cart_change'] = $arrs['D']['cart_num'] == 0 ? 0 : round($arrs['D']['order_num'] / $arrs['D']['cart_num'] * 100, 2);
            $ddd['d_sku_total'] = $arrs['D']['sku_grand_total'];

            $ee['e_num'] = $arr['E'];
            $ee['e_session_num'] = $arrs['E']['unique_pageviews'];
            $ee['e_cart_num'] = $arrs['E']['cart_num'];
            $ee['e_session_change'] = $arrs['E']['unique_pageviews'] == 0 ? 0 : round($arrs['E']['cart_num'] / $arrs['E']['unique_pageviews'] * 100, 2) . '%';
            $ee['e_order_num'] = $arrs['E']['order_num'];
            $ee['e_cart_change'] = $arrs['E']['cart_num'] == 0 ? 0 : round($arrs['E']['order_num'] / $arrs['E']['cart_num'] * 100, 2) . '%';
            $ee['e_sku_total'] = $arrs['E']['sku_grand_total'];
            $data = compact('a_plus', 'aa', 'bb', 'cc', 'c_plus', 'ddd', 'ee');

            $this->success('', '', $data);
        }
    }

    //跑sku每天的数据
    public function sku_day_data()
    {
        set_time_limit(0);
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1])
            // ->where(['platform_type' => 1,'outer_sku_status'=>1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $this->zeeloolOperate->google_sku_detail(1, '2020-10-13');
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
                        $arr[$v['sku']]['grade'] = $v['grade'];
                        $arr[$v['sku']]['sku'] = $v['sku'];
                        $arr[$v['sku']]['platform_sku'] = $v['platform_sku'];
                    }

                }
            }
        }

        //统计某个sku某一天的销量
        $zeelool_order = new Zeelool();
        foreach ($arr as $key => $value) {
            $arr[$key]['order_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
                ->where('sku', 'like', '%' . $value['sku'] . '%')
                ->distinct('order_id')
                ->field('order_id')
                ->count();
            $map['sku'] = ['=', $value['sku']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            $sku_order_data = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->field('base_grand_total,entity_id,row_total,sku')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->select();
            dump($sku_order_data);

        }
        die;
        dump($arr);
    }

}
