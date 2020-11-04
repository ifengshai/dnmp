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
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }

        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            // dump($filter);

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['time_str']) {
                //时间段总和
                $createat = explode(' ', $filter['time_str']);
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else{
                $createat = explode(' ', $seven_days);
            }
            if($filter['create_time-operate']){
                unset($filter['create_time-operate']);
            }
            if($filter['order_platform']){
                $order_platform = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            }else{
                $order_platform = 1;
            }

            $map['site'] = $order_platform;
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('datacenter_sku_day')
                ->where($where)
                ->where($map)
                ->group('sku')
                // ->order($sort, $order)
                ->order('day_date','desc')
                ->count();
            $sku_data_day = Db::name('datacenter_sku_day')
                ->where($where)
                ->where($map)
                ->group('sku')
                ->field('id,sku,sum(cart_num) as cart_num,now_pricce,max(day_date) as day_date,single_price,day_stock,day_onway_stock,sum(sales_num) as sales_num,sum(order_num) as order_num,sum(glass_num) as glass_num,sum(sku_row_total) as sku_row_total,sum(sku_grand_total) as sku_grand_total,sum(sku_grand_total) as sku_grand_total')
                // ->order($sort, $order)
                ->order('day_date','desc')
                ->limit($offset, $limit)
                ->select();
            foreach ($sku_data_day as $k => $v) {
                $sku_detail = $_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $order_platform])->field('platform_sku,stock,plat_on_way_stock,outer_sku_status')->find();
                //sku转换
                $sku_data_day[$k]['sku_change'] = $sku_detail['platform_sku'];
                //上下架状态
                $sku_data_day[$k]['status'] = $sku_detail['outer_sku_status'];
                $sku_data_day[$k]['stock'] = $sku_detail['stock'];
                $sku_data_day[$k]['on_way_stock'] = $sku_detail['plat_on_way_stock'];
                $sku_data_day[$k]['cart_change'] = $sku_data_day[$k]['cart_num'] == 0 ? '0%' : round($sku_data_day[$k]['order_num'] / $sku_data_day[$k]['cart_num'] * 100, 2) . '%';
            }
            $result = array("total" => $total, "rows" => $sku_data_day);
            return json($result);
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    // 商品转化率分析 产品等级转化情况
    public function sku_grade_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
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
            $sku_data_days = Db::name('datacenter_sku_day')->where('site', $order_platform)->where($where)->field('')->select();

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
            $a_plus['a_plus_sku_total'] = round($arrs['A+']['sku_grand_total'],2);

            $aa['a_num'] = $arr['A'];
            $aa['a_session_num'] = $arrs['A']['unique_pageviews'];
            $aa['a_cart_num'] = $arrs['A']['cart_num'];
            $aa['a_session_change'] = $arrs['A']['unique_pageviews'] == 0 ? 0 : round($arrs['A']['cart_num'] / $arrs['A']['unique_pageviews'] * 100, 2) . '%';
            $aa['a_order_num'] = $arrs['A']['order_num'];
            $aa['a_cart_change'] = $arrs['A']['cart_num'] == 0 ? 0 : round($arrs['A']['order_num'] / $arrs['A']['cart_num'] * 100, 2) . '%';
            $aa['a_sku_total'] = round($arrs['A']['sku_grand_total'],2);

            $bb['b_num'] = $arr['B'];
            $bb['b_session_num'] = $arrs['B']['unique_pageviews'];
            $bb['b_cart_num'] = $arrs['B']['cart_num'];
            $bb['b_session_change'] = $arrs['B']['unique_pageviews'] == 0 ? 0 : round($arrs['B']['cart_num'] / $arrs['B']['unique_pageviews'] * 100, 2) . '%';
            $bb['b_order_num'] = $arrs['B']['order_num'];
            $bb['b_cart_change'] = $arrs['B']['cart_num'] == 0 ? 0 : round($arrs['B']['order_num'] / $arrs['B']['cart_num'] * 100, 2) . '%';
            $bb['b_sku_total'] = round($arrs['B']['sku_grand_total'],2);

            $cc['c_num'] = $arr['C'];
            $cc['c_session_num'] = $arrs['C']['unique_pageviews'];
            $cc['c_cart_num'] = $arrs['C']['cart_num'];
            $cc['c_session_change'] = $arrs['C']['unique_pageviews'] == 0 ? 0 : round($arrs['C']['cart_num'] / $arrs['C']['unique_pageviews'] * 100, 2) . '%';
            $cc['c_order_num'] = $arrs['C']['order_num'];
            $cc['c_cart_change'] = $arrs['C']['cart_num'] == 0 ? 0 : round($arrs['C']['order_num'] / $arrs['C']['cart_num'] * 100, 2) . '%';
            $cc['c_sku_total'] = round($arrs['C']['sku_grand_total'],2);

            $c_plus['c_plus_num'] = $arr['C+'];
            $c_plus['c_plus_session_num'] = $arrs['C+']['unique_pageviews'];
            $c_plus['c_plus_cart_num'] = $arrs['C+']['cart_num'];
            $c_plus['c_plus_session_change'] = $arrs['C+']['unique_pageviews'] == 0 ? 0 : round($arrs['C+']['cart_num'] / $arrs['C+']['unique_pageviews'] * 100, 2) . '%';
            $c_plus['c_plus_order_num'] = $arrs['C+']['order_num'];
            $c_plus['c_plus_cart_change'] = $arrs['C+']['cart_num'] == 0 ? 0 : round($arrs['C+']['order_num'] / $arrs['C+']['cart_num'] * 100, 2) . '%';
            $c_plus['c_plus_sku_total'] = round($arrs['C+']['sku_grand_total'],2);

            $ddd['d_num'] = $arr['D'];
            $ddd['d_session_num'] = $arrs['D']['unique_pageviews'];
            $ddd['d_cart_num'] = $arrs['D']['cart_num'];
            $ddd['d_session_change'] = $arrs['D']['unique_pageviews'] == 0 ? 0 : round($arrs['D']['cart_num'] / $arrs['D']['unique_pageviews'] * 100, 2);
            $ddd['d_order_num'] = $arrs['D']['order_num'];
            $ddd['d_cart_change'] = $arrs['D']['cart_num'] == 0 ? 0 : round($arrs['D']['order_num'] / $arrs['D']['cart_num'] * 100, 2);
            $ddd['d_sku_total'] = round($arrs['D']['sku_grand_total'],2);

            $ee['e_num'] = $arr['E'];
            $ee['e_session_num'] = $arrs['E']['unique_pageviews'];
            $ee['e_cart_num'] = $arrs['E']['cart_num'];
            $ee['e_session_change'] = $arrs['E']['unique_pageviews'] == 0 ? 0 : round($arrs['E']['cart_num'] / $arrs['E']['unique_pageviews'] * 100, 2) . '%';
            $ee['e_order_num'] = $arrs['E']['order_num'];
            $ee['e_cart_change'] = $arrs['E']['cart_num'] == 0 ? 0 : round($arrs['E']['order_num'] / $arrs['E']['cart_num'] * 100, 2) . '%';
            $ee['e_sku_total'] = round($arrs['E']['sku_grand_total'],2);
            $data = compact('a_plus', 'aa', 'bb', 'cc', 'c_plus', 'ddd', 'ee');

            $this->success('', '', $data);
        }
    }

    //跑sku每天的数据
    public function sku_day_data()
    {
        set_time_limit(0);
        $data = date('Y-m-d');
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $sku_data = $_item_platform_sku
            ->field('sku,grade,platform_sku,outer_sku_status')
            ->where(['platform_type' => 1])
            // ->where(['platform_type' => 1,'outer_sku_status'=>1])
            ->select();
        //当前站点的所有sku映射关系
        $sku_data = collection($sku_data)->toArray();
        //ga所有的sku唯一身份浏览量的数据
        $ga_skus = $this->zeeloolOperate->google_sku_detail(1,$data);
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
                ->where('sku', 'like', '%' . $value['sku'] . '%')
                // ->where($time_where)
                ->distinct('order_id')
                ->field('order_id,created_at')
                ->count();
            $map['b.sku'] = ['=', $value['sku']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_zeelool')->table('sales_flat_order')
                ->where($map)
                // ->where($time_where1)
                ->alias('a')
                ->field('grand_total,entity_id,row_total,b.sku,a.created_at,c.goods_type')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->join(['sales_flat_order_item_prescription' => 'c'], 'a.entity_id=c.order_id')
                ->select();
            // dump($sku_order_data);
            //统计某个sku某一天的销售额 实际支付的金额
            foreach ($sku_order_data as $kk=>$vv){
                if ($arr[$key]['sku_grand_total']){
                    $arr[$key]['sku_grand_total'] += $vv['grand_total'];
                }else{
                    $arr[$key]['sku_grand_total'] = $vv['grand_total'];
                }
                if ($arr[$key]['sku_row_total']){
                    $arr[$key]['sku_row_total'] += $vv['row_total'];
                }else{
                    $arr[$key]['sku_row_total'] += $vv['row_total'];
                }
                //找到商品的现价
                if (!$arr[$key]['now_pricce']){
                    $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool')->table('catalog_product_index_price')->where('entity_id',$vv['entity_id'])->value('final_price');
                }
                //商品的类型
                if (!$arr[$key]['goods_type']){
                    $arr[$key]['goods_type'] = $vv['goods_type'];
                }
            }
            //销售副数
            $arr[$key]['glass_num'] = Db::connect('database.db_zeelool')->table('sales_flat_order_item')
                ->where('sku', 'like', '%' . $value['sku'] . '%')
                // ->where($time_where)
                ->count();
            //副单价
            $arr[$key]['single_price'] = $arr[$key]['glass_num'] == 0 ?  0 : round($arr[$key]['sku_row_total']/$arr[$key]['glass_num'],0);
            // dump($sku_order_data);
            //日期
            $arr[$key]['day_date'] = $data;
            //站点
            $arr[$key]['site'] = 1;
            //购物车数量
            $zeelool_model = Db::connect('database.db_zeelool');
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
            //插入数据
            Db::name('datacenter_sku_day')->insert($arr[$key]);
            echo $key . "\n";
            usleep(100000);
        }
        // die;
        // dump($arr);
    }
    public function sku_day_data_ga()
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
                // ->where($time_where)
                ->distinct('order_id')
                ->field('order_id,created_at')
                ->count();
            $map['b.sku'] = ['=', $value['sku']];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
            //获取这个sku所有的订单情况
            $sku_order_data = Db::connect('database.db_zeelool')
                ->table('sales_flat_order')
                ->where($map)
                // ->where($time_where1)
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
                //找到商品的现价
                if (!$arr[$key]['now_pricce']){
                    // $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool_online')
                    $arr[$key]['now_pricce'] = Db::connect('database.db_zeelool')
                        ->table('catalog_product_index_price')
                        ->where('entity_id',$vv['entity_id'])
                        ->value('final_price');
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
                    ->update(['order_num'=>$arr[$key]['order_num'],'sku_grand_total'=>$arr[$key]['sku_grand_total'],'sku_row_total'=>$arr[$key]['sku_row_total'],'now_pricce'=>$arr[$key]['now_pricce'],'goods_type'=>$arr[$key]['goods_type']]);
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
                    ->update(['glass_num'=>$arr[$key]['glass_num'],'single_price'=>$arr[$key]['single_price'],'cart_num'=>$arr[$key]['cart_num']]);
                echo $arr[$key]['sku'] . "\n";
                usleep(100000);
            }

        }
        dump($arr);
    }
}
