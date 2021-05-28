<?php

namespace app\admin\controller\operatedatacenter\NewGoodsData;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Cache;
use function GuzzleHttp\describe_type;
use think\Controller;
use think\Db;
use think\Request;

class GoodsDataView extends Backend
{
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();

        $this->item_platform = new ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $this->model = new \app\admin\model\itemmanage\Item;
    }

    /**
     * 商品数据-数据概览
     *
     */
    public function index()
    {
        $label = input('order_platform', 1);
        $goods_type = [1 => '光学镜', 2 => '太阳镜', 3 => '运动镜', 4 => '老花镜', 5 => '儿童镜', 6 => '配饰'];
        $this->assign('goods_type', $goods_type);
        if ($this->request->isAjax()) {
            $result = [];
            return json(['code' => 1, 'rows' => $result]);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao','wesee','zeelool_de','zeelool_jp'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }
    //根据网站类型展示商品类型
    public function site_goods_type(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            switch ($order_platform) {
                case 1:
                    $goods_type = [1 => '光学镜', 2 => '太阳镜', 3 => '运动镜', 4 => '老花镜', 5 => '儿童镜', 6 => '配饰'];
                    break;
                case 2:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜', 6 => '配饰'];
                    break;
                case 3:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜'];
                    break;
                case 5:
                    $goods_type = [1 => '光学镜', 2 => '太阳镜', 3 => '运动镜', 4 => '儿童镜', 5 => '老花镜', 6 => '配饰'];
                    break;
                case 10:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜', 6 => '配饰'];
                    break;
                case 11:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜', 6 => '配饰'];
                    break;
            }
            $str = '';
            $str .= '<option value="0">请选择</option>';
            foreach ($goods_type as $key=>$value){
                $str .= '<option value="'.$key.'">'.$value.'</option>';
            }
            $this->success('', '', $str);
        }
    }
    /**
     * 镜框销量/幅单价趋势
     *
     */
    public function goods_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                //时间段总和
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            $map['goods_type'] = ['<', 6];
            $dataCenterDay = Db::name('datacenter_goods_type_data')
                ->where(['site' => $order_platform])
                ->where($map)
                ->field('day_date,sum(sales_total_money) sales_total_money,sum(glass_num) glass_num')
                ->group('day_date')
                ->order('day_date', 'asc')
                ->select();

            //副单价
            foreach ($dataCenterDay as $key => $value) {
                $dataCenterDay[$key]['sing_price'] = $value['glass_num'] == 0 ? 0 : round($value['sales_total_money'] / $value['glass_num'], 2);
            }
            $json['xColumnName'] = array_column($dataCenterDay,'day_date');
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_column($dataCenterDay,'glass_num'),
                    'name' => '镜框销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => array_column($dataCenterDay,'sing_price'),
                    'name' => '副单价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }


    //商品销量概况
    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                //时间段总和
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            $itemMap['m.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            //判断站点
            switch ($params['order_platform']) {
                case 1:
                    //包含实时数据的
                    $glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 1)->where('site', 1)->sum('glass_num');
                    $sun_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 2)->where('site', 1)->sum('glass_num');
                    $run_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 5)->where('site', 1)->sum('glass_num');
                    $old_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 3)->where('site', 1)->sum('glass_num');
                    $son_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 4)->where('site', 1)->sum('glass_num');
                    $other_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 6)->where('site', 1)->sum('glass_num');
                    break;
                case 2:
                    $glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 1)->where('site', 2)->sum('glass_num');
                    $sun_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 2)->where('site', 2)->sum('glass_num');
                    $other_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 6)->where('site', 2)->sum('glass_num');
                    break;
                case 3:
                    $glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 1)->where('site', 3)->sum('glass_num');
                    $sun_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 2)->where('site', 3)->sum('glass_num');
                    break;
                case 5:
                    //包含实时数据的
                    $glass_num = Db::name('datacenter_goods_type_data')
                        ->where($map)
                        ->where('goods_type', 1)
                        ->where('site', 5)
                        ->sum('glass_num');
                    $sun_glass_num = Db::name('datacenter_goods_type_data')
                        ->where($map)
                        ->where('goods_type', 2)
                        ->where('site', 5)
                        ->sum('glass_num');
                    $old_glass_num = Db::name('datacenter_goods_type_data')
                        ->where($map)
                        ->where('goods_type', 3)
                        ->where('site', 5)
                        ->sum('glass_num');
                    $son_glass_num = Db::name('datacenter_goods_type_data')
                        ->where($map)
                        ->where('goods_type', 4)
                        ->where('site', 5)
                        ->sum('glass_num');
                    $run_glass_num = Db::name('datacenter_goods_type_data')
                        ->where($map)
                        ->where('goods_type', 5)
                        ->where('site', 5)
                        ->sum('glass_num');
                    $other_num = Db::name('datacenter_goods_type_data')
                        ->where($map)
                        ->where('goods_type', 6)
                        ->where('site', 5)
                        ->sum('glass_num');
                    break;
                case 10:
                    $glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 1)->where('site', 10)->sum('glass_num');
                    $sun_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 2)->where('site', 10)->sum('glass_num');
                    $other_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 6)->where('site', 10)->sum('glass_num');
                    break;
                case 11:
                    $glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 1)->where('site', 11)->sum('glass_num');
                    $sun_glass_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 2)->where('site', 11)->sum('glass_num');
                    $other_num = Db::name('datacenter_goods_type_data')->where($map)->where('goods_type', 6)->where('site', 11)->sum('glass_num');
                    break;
                default:
                    $model = false;
                    break;
            }

            // goods_type:1光学镜,2太阳镜,,3运动镜,4老花镜,5儿童镜,6配饰
            //goods_type:1光学镜,2太阳镜,,3老花镜,4儿童镜,5运动镜,6配饰 现在用的
            $glass_num = $glass_num ? $glass_num : 0;
            $sun_glass_num = $sun_glass_num ? $sun_glass_num : 0;
            $run_glass_num = $run_glass_num ? $run_glass_num : 0;
            $old_glass_num = $old_glass_num ? $old_glass_num : 0;
            $son_glass_num = $son_glass_num ? $son_glass_num : 0;
            $other_num = $other_num ? $other_num : 0;
            $total_num = $glass_num + $sun_glass_num + $run_glass_num + $old_glass_num + $son_glass_num + $other_num;
        }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'd_data', 'e_data', 'f_data', 'glass_num', 'sun_glass_num', 'run_glass_num', 'old_glass_num', 'son_glass_num', 'other_num', 'total_num');
        $this->success('', '', $data);
    }

    //产品等级分布表格
    public function ajax_dowm_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                //时间段总和
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $map['site'] = $params['order_platform'] ? $params['order_platform'] : 1;
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            if ($params['goods_type']) {
                $map['goods_type'] = $params['goods_type'];
            }
            //查询符合条件的商品sku
            $skusSum = Db::name('datacenter_sku_day')
                ->where($map)
                ->column('sku');
            $stockSum = $this->model
                ->where(['is_open'=>1,'is_del'=>1])
                ->where('category_id','neq',43)
                ->where('sku','in',$skusSum)
                ->field('sum(stock-distribution_occupy_stock) real_time_stock,sum(stock) stock')
                ->find();
            //根据产品等级统计总数据
            $sumData = Db::name('datacenter_sku_day')
                ->where($map)
                ->field('count(*) sku_num,sum(glass_num) sales_num,sum(sku_row_total) sales_total')
                ->find();
            //根据产品等级分组数据
            $dataCenterDay = Db::name('datacenter_sku_day')
                ->where($map)
                ->group('goods_grade')
                ->field('site,goods_grade,count(*) sku_num,sum(glass_num) sales_num,sum(sku_row_total) sales_total')
                ->select();
            $sort = ['A+' => 1, 'A' => 2, 'B' => 3, 'C+' => 4, 'C' => 5, 'D' => 6, 'E' => 7, 'F' => 8, 'Z' => 9];
            foreach($dataCenterDay as $key=>$value){
                $dataCenterDay[$key]['grade'] = $value['goods_grade'];
                $dataCenterDay[$key]['sort'] = $sort[$value['goods_grade']];
                $dataCenterDay[$key]['sku_num'] = $value['sku_num'];
                $dataCenterDay[$key]['sku_num_rate'] = $sumData['sku_num'] ? round($value['sku_num']/$sumData['sku_num']*100,2) : 0;
                $dataCenterDay[$key]['sales_num'] = $value['sales_num'];
                $dataCenterDay[$key]['sales_num_rate'] = $sumData['sales_num'] ? round($value['sales_num']/$sumData['sales_num']*100,2) : 0;
                $dataCenterDay[$key]['sales_total'] = $value['sales_total'];
                $dataCenterDay[$key]['sales_total_rate'] = $sumData['sales_total'] ? round($value['sales_total']/$sumData['sales_total']*100,2) : 0;
                //处于该等级的商品sku
                $skus = Db::name('datacenter_sku_day')
                    ->where($map)
                    ->where('goods_grade',$value['goods_grade'])
                    ->column('sku');
                $stockInfo = $this->model
                    ->where(['is_open'=>1,'is_del'=>1])
                    ->where('category_id','neq',43)
                    ->where('sku','in',$skus)
                    ->field('sum(stock-distribution_occupy_stock) real_time_stock,sum(stock) stock')
                    ->find();
                $dataCenterDay[$key]['real_time_stock'] = $stockInfo['real_time_stock'];
                $dataCenterDay[$key]['real_time_stock_rate'] = $stockSum['real_time_stock'] ? round($stockInfo['real_time_stock']/$stockSum['real_time_stock']*100,2) : 0;
                $dataCenterDay[$key]['stock'] = $stockInfo['stock'];
            }
            $dataCenterDay = array_column($dataCenterDay,null,'sort');
            ksort($dataCenterDay);
            $dataCenterDay[] = array(
                'grade' => '合计',
                'sku_num' => $sumData['sku_num'],
                'sku_num_rate' => '100',
                'sales_num' => $sumData['sales_num'],
                'sales_num_rate' => '100',
                'sales_total' => $sumData['sales_total'],
                'sales_total_rate' => '100',
                'real_time_stock' => $stockSum['real_time_stock'],
                'real_time_stock_rate' => '100',
                'stock' => $stockSum['stock']
            );
            $str = '';
            foreach ($dataCenterDay as $v){
                $str .= '<tr>';
                $str .= '<td>'.$v['grade'].'</td>';
                $str .= '<td>'.$v['sku_num'].'</td>';
                $str .= '<td>'.$v['sku_num_rate'].'%</td>';
                $str .= '<td>'.$v['sales_num'].'</td>';
                $str .= '<td>'.$v['sales_num_rate'].'%</td>';
                $str .= '<td>'.$v['sales_total'].'</td>';
                $str .= '<td>'.$v['sales_total_rate'].'%</td>';
                $str .= '<td>'.$v['real_time_stock'].'</td>';
                $str .= '<td>'.$v['real_time_stock_rate'].'%</td>';
                $str .= '<td>'.$v['stock'].'</td>';
            }
        }
        $this->success('', '', $str);
    }

    //眼镜关键指标 饰品关键指标
    public function glass_box_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = $params['platform'] ? $params['platform'] : 1;
            //时间
            $time_str = $params['time_str'];
            if (!$time_str) {
                //默认查询z站七天的数据
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
            }
            $time_str = explode(' ',$time_str);
            $start = strtotime($time_str[0].' '.$time_str[1]);
            $end = strtotime($time_str[3].' '.$time_str[4]);
            if($order_platform == 5){
                $where['payment_time'] = ['between',[$start,$end]];
            }else{
                $where['payment_time'] = ['between',[$time_str[0].' '.$time_str[1],$time_str[3].' '.$time_str[4]]];
            }
            $data = $this->platformOrderInfo($order_platform,$where);
            $this->success('', '', $data);
        }
    }
    /*
     * 眼镜关键指标 饰品关键指标方法
     */
    public function platformOrderInfo($platform, $itemMap)
    {
        $arr = Cache::get('newGoodsData_platformOrderInfo' . $platform . md5(serialize($itemMap)));
        if ($arr) {
            return $arr;
        }
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 5:
                $model = Db::connect('database.db_weseeoptical');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false == $model) {
            return false;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";

        //求出眼镜所有sku
        $frame_sku = $this->itemPlatformSku->getDifferencePlatformSku(1, $platform);
        //求出饰品的所有sku
        $decoration_sku = $this->itemPlatformSku->getDifferencePlatformSku(3, $platform);
        if($platform == 5){
            $itemMap['o.site'] = $platform;
            //求出眼镜的销售额
            $frame_money_discount = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where('sku', 'in', $frame_sku)
                ->where($itemMap)
                ->sum('base_original_price');
            //眼镜的实际销售额
            $frame_money = round($frame_money_discount, 2);
            //眼镜的销售副数
            $frame_sales_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where('sku', 'in', $frame_sku)
                ->where($whereItem)
                ->where($itemMap)
                ->sum('i.qty');
            //眼镜平均副金额
            $frame_avg_money = $frame_sales_num ? round(($frame_money / $frame_sales_num), 2) : 0;
            //求出配饰的销售额
            $decoration_money_discount = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where('sku', 'in', $decoration_sku)
                ->where($itemMap)
                ->sum('base_original_price');
            //配饰的实际销售额
            $decoration_money = round($decoration_money_discount, 2);
            //配饰的销售副数
            $decoration_sales_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where('sku', 'in', $decoration_sku)
                ->where($whereItem)
                ->where($itemMap)
                ->sum('i.qty');
            //配饰平均副金额
            $decoration_avg_money = $decoration_sales_num ? round(($decoration_money / $decoration_sales_num), 2):0;
            //眼镜正常售卖数
            $frame_onsales_num = $this->itemPlatformSku->putawayDifferenceSku(1, $platform);
            //配饰正常售卖数
            $decoration_onsales_num = $this->itemPlatformSku->putawayDifferenceSku(3, $platform);
            //眼镜动销数
            $frame_in_print_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where('sku', 'in', $frame_sku)
                ->where($whereItem)
                ->where($itemMap)
                ->count('distinct i.sku');
            //眼镜动销率
            $frame_in_print_rate = $frame_onsales_num ? round(($frame_in_print_num/$frame_onsales_num)*100,2).'%' : 0;
            //配饰动销数
            $decoration_in_print_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where('sku', 'in', $decoration_sku)
                ->where($whereItem)
                ->where($itemMap)
                ->count('distinct i.sku');
            //配饰动销率
            $decoration_in_print_rate = $decoration_onsales_num ? round(($decoration_in_print_num / $decoration_onsales_num) * 100, 2).'%' : 0;
            //求出所有新品眼镜sku
            $frame_new_sku = $this->itemPlatformSku->getDifferencePlatformNewSku(1, $platform);
            //求出所有新品饰品sku
            $decoration_new_sku = $this->itemPlatformSku->getDifferencePlatformNewSku(3, $platform);
            //求出新品眼镜的销售额
            $frame_new_money_price = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where('i.sku', 'in', $frame_new_sku)
                ->where($itemMap)
                ->sum('base_original_price');
            //新品眼镜的实际销售额
            $frame_new_money = round($frame_new_money_price, 2);
            //求出新品配饰的销售额
            $decoration_new_money_price = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where('i.sku', 'in', $decoration_new_sku)
                ->where($itemMap)
                ->sum('base_original_price');
            //求出新品配饰的实际销售额
            $decoration_new_money = round($decoration_new_money_price, 2);
            //眼镜下单客户数
            $frame_order_customer = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where('i.sku', 'in', $frame_sku)
                ->where($itemMap)
                ->count('distinct o.customer_email');
            //眼镜客户平均副数
            $frame_avg_customer = $frame_order_customer ? round(($frame_sales_num / $frame_order_customer), 2) : 0;
            //配饰下单客户数
            $decoration_order_customer = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where('i.sku', 'in', $decoration_sku)
                ->where($itemMap)
                ->count('distinct o.customer_email');
            $decoration_avg_customer = $decoration_order_customer ? round(($decoration_sales_num / $decoration_order_customer), 2) : 0;
            //新品眼镜数量
            $frame_new_num = $this->item->getDifferenceNewSkuNum(1);
            //新品饰品数量
            $decoration_new_num = $this->item->getDifferenceNewSkuNum(3);
            //新品眼镜动销数
            $frame_new_in_print_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.sku', 'in', $frame_new_sku)
                ->count('distinct i.sku');
            //新品眼镜动销率
            $frame_new_in_print_rate = $frame_new_num ? round(($frame_new_in_print_num / $frame_new_num) * 100, 2).'%' : 0;
            //新品饰品动销数
            $decoration_new_in_print_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.sku', 'in', $decoration_new_sku)
                ->count('distinct i.sku');
            //新品饰品动销率
            $decoration_new_in_print_rate = $decoration_new_num ? round(($decoration_new_in_print_num / $decoration_new_num) * 100, 2).'%' : 0;
        }else{
            //求出眼镜的销售额
            $frame_money_price = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->sum('m.base_price');
            //眼镜的折扣价格
            $frame_money_discount = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->sum('m.base_discount_amount');
            //眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            //眼镜的销售副数
            $frame_sales_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->sum('m.qty_ordered');
            //眼镜平均副金额
            if (0 < $frame_sales_num) {
                $frame_avg_money = round(($frame_money / $frame_sales_num), 2);
            } else {
                $frame_avg_money = 0;
            }
            //求出配饰的销售额
            $decoration_money_price = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_sku)
                ->sum('m.base_price');
            //配饰的折扣价格
            $decoration_money_discount = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_sku)
                ->sum('m.base_discount_amount');
            //配饰的实际销售额
            $decoration_money = round(($decoration_money_price - $decoration_money_discount), 2);
            //配饰的销售副数
            $decoration_sales_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_sku)
                ->sum('m.qty_ordered');
            //配饰平均副金额
            if (0 < $decoration_sales_num) {
                $decoration_avg_money = round(($decoration_money / $decoration_sales_num), 2);
            } else {
                $decoration_avg_money = 0;
            }
            //眼镜正常售卖数
            $frame_onsales_num = $this->itemPlatformSku->putawayDifferenceSku(1, $platform);
            //配饰正常售卖数
            $decoration_onsales_num = $this->itemPlatformSku->putawayDifferenceSku(3, $platform);
            //眼镜动销数
            $frame_in_print_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->count('distinct m.sku');
            //眼镜总共的数量
            //$frame_num                 = $this->item->getDifferenceSkuNUm(1);
            //眼镜动销率
            if (0 < $frame_onsales_num) {
                $frame_in_print_rate = round(($frame_in_print_num / $frame_onsales_num) * 100, 2).'%';
            } else {
                $frame_in_print_rate = 0;
            }
            //配饰动销数
            $decoration_in_print_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_sku)
                ->count('distinct m.sku');
            //配饰总共的数量
            //$decoration_num            = $this->item->getDifferenceSkuNUm(3);
            //配饰动销率
            if (0 < $decoration_onsales_num) {
                $decoration_in_print_rate = round(($decoration_in_print_num / $decoration_onsales_num) * 100, 2).'%';
            } else {
                $decoration_in_print_rate = 0;
            }
            //求出所有新品眼镜sku
            $frame_new_sku = $this->itemPlatformSku->getDifferencePlatformNewSku(1, $platform);
            //求出所有新品饰品sku
            $decoration_new_sku = $this->itemPlatformSku->getDifferencePlatformNewSku(3, $platform);
            //求出新品眼镜的销售额 base_price  base_discount_amount
            $frame_new_money_price = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->sum('m.base_price');
            //新品眼镜的折扣价格
            $frame_new_money_discount = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->sum('m.base_discount_amount');
            //新品眼镜的实际销售额
            $frame_new_money = round(($frame_new_money_price - $frame_new_money_discount), 2);
            //求出新品配饰的销售额
            $decoration_new_money_price = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_new_sku)
                ->sum('m.base_price');
            //求出新品配饰的折扣价格
            $decoration_new_money_discount = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_new_sku)
                ->sum('m.base_discount_amount');
            //求出新品配饰的实际销售额
            $decoration_new_money = round(($decoration_new_money_price - $decoration_new_money_discount), 2);
            //眼镜下单客户数
            $frame_order_customer = $model->table('sales_flat_order o')
                ->join('sales_flat_order_item m', 'o.entity_id=m.order_id', 'left')
                ->where($whereItem)
                ->where('m.sku', 'in', $frame_sku)
                ->where($itemMap)
                ->count('distinct o.customer_email');
            //眼镜客户平均副数
            if (0 < $frame_order_customer) {
                $frame_avg_customer = round(($frame_sales_num / $frame_order_customer), 2);
            }
            //配饰下单客户数
            $decoration_order_customer = $model->table('sales_flat_order o')
                ->join('sales_flat_order_item m', 'o.entity_id=m.order_id', 'left')
                ->where($whereItem)
                ->where('m.sku', 'in', $decoration_sku)
                ->where($itemMap)
                ->count('distinct o.customer_email');
            if (0 < $decoration_order_customer) {
                $decoration_avg_customer = round(($decoration_sales_num / $decoration_order_customer), 2);
            }
            //新品眼镜数量
            $frame_new_num = $this->item->getDifferenceNewSkuNum(1);
            //新品饰品数量
            $decoration_new_num = $this->item->getDifferenceNewSkuNum(3);
            //新品眼镜动销数
            $frame_new_in_print_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->count('distinct m.sku');
            //新品眼镜动销率
            if (0 < $frame_new_num) {
                $frame_new_in_print_rate = round(($frame_new_in_print_num / $frame_new_num) * 100, 2).'%';
            } else {
                $frame_new_in_print_rate = 0;
            }
            //新品饰品动销数
            $decoration_new_in_print_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $decoration_new_sku)
                ->count('distinct m.sku');
            //新品饰品动销率
            if (0 < $decoration_new_num) {
                $decoration_new_in_print_rate = round(($decoration_new_in_print_num / $decoration_new_num) * 100, 2).'%';
            } else {
                $decoration_new_in_print_rate = 0;
            }
        }
        $arr = [
            //眼镜的实际销售额
            'frame_money' => $frame_money,
            //眼镜的销售副数
            'frame_sales_num' => $frame_sales_num,
            //眼镜平均副金额
            'frame_avg_money' => $frame_avg_money,
            //配饰的实际销售额
            'decoration_money' => $decoration_money,
            //配饰的销售副数
            'decoration_sales_num' => $decoration_sales_num,
            //配饰平均副金额
            'decoration_avg_money' => $decoration_avg_money,
            //眼镜正常售卖数
            'frame_onsales_num' => $frame_onsales_num,
            //配饰正常售卖数
            'decoration_onsales_num' => $decoration_onsales_num,
            //眼镜动销数
            'frame_in_print_num' => $frame_in_print_num,
            //眼镜动销率
            'frame_in_print_rate' => $frame_in_print_rate,
            //配饰动销数
            'decoration_in_print_num' => $decoration_in_print_num,
            //配饰动销率
            'decoration_in_print_rate' => $decoration_in_print_rate,
            //新品眼镜的实际销售额
            'frame_new_money' => $frame_new_money,
            //求出新品配饰的实际销售额
            'decoration_new_money' => $decoration_new_money,
            //眼镜下单客户数
            'frame_order_customer' => $frame_order_customer,
            //眼镜客户平均副数
            'frame_avg_customer' => $frame_avg_customer,
            //配饰下单客户数
            'decoration_order_customer' => $decoration_order_customer,
            //配饰客户平均副数
            'decoration_avg_customer' => $decoration_avg_customer,
            //新品眼镜数量
            'frame_new_num' => $frame_new_num,
            //新品饰品数量
            'decoration_new_num' => $decoration_new_num,
            //新品眼镜动销数
            'frame_new_in_print_num' => $frame_new_in_print_num,
            //新品眼镜动销率
            'frame_new_in_print_rate' => $frame_new_in_print_rate,
            //新品饰品动销数
            'decoration_new_in_print_num' => $decoration_new_in_print_num,
            //新品饰品动销率
            'decoration_new_in_print_rate' => $decoration_new_in_print_rate
        ];
        Cache::set('newGoodsData_platformOrderInfo' . $platform . md5(serialize($itemMap)), $arr, 7200);
        return $arr;
    }
    //其他关键指标
    public function mid_data()
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
            switch ($order_platform) {
                case 1:
                    $glass = $this->other_key_plat($order_platform, 1, $time_str);
                    $sun_glass = $this->other_key_plat($order_platform, 2, $time_str);
                    $old_glass = $this->other_key_plat($order_platform, 3, $time_str);
                    $son_glass = $this->other_key_plat($order_platform, 4, $time_str);
                    $run_glass = $this->other_key_plat($order_platform, 5, $time_str);
                    break;
                case 2:
                    $glass = $this->other_key_plat($order_platform, 1, $time_str);
                    $sun_glass = $this->other_key_plat($order_platform, 2, $time_str);
                    break;
                case 3:
                    $glass = $this->other_key_plat($order_platform, 1, $time_str);
                    $sun_glass = $this->other_key_plat($order_platform, 2, $time_str);
                    break;
                case 5:
                    $glass = $this->other_key_plat($order_platform, 1, $time_str);
                    $sun_glass = $this->other_key_plat($order_platform, 2, $time_str);
                    $old_glass = $this->other_key_plat($order_platform, 3, $time_str);
                    $son_glass = $this->other_key_plat($order_platform, 4, $time_str);
                    $run_glass = $this->other_key_plat($order_platform, 5, $time_str);
                    break;
                case 10:
                    $glass = $this->other_key_plat($order_platform, 1, $time_str);
                    $sun_glass = $this->other_key_plat($order_platform, 2, $time_str);
                    break;
                case 11:
                    $glass = $this->other_key_plat($order_platform, 1, $time_str);
                    $sun_glass = $this->other_key_plat($order_platform, 2, $time_str);
                    break;
                default:
                    break;
            }
            $data = compact('glass', 'sun_glass', 'old_glass', 'son_glass', 'run_glass');
            $this->success('', '', $data);
        }
    }

    //其他关键指标 $platform站点,$goods_type产品类型,$time时间段
    public function other_key_plat($platform, $goods_type, $time)
    {
        //默认7天数据
        if ($time) {
            $time = explode(' ', $time);
            $map['payment_time'] = $itemMap['o.payment_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            $maps['day_date'] = ['between', [$time[0] , $time[3]]];
        } else {
            $map['payment_time'] = $itemMap['o.payment_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            $maps['day_date'] = ['between', [date('Y-m-d', strtotime('-7 day')), date('Y-m-d', time())]];
        }
        $arr = Cache::get('newGoodsData_platformOrderInfo1' . $platform . $goods_type . md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        if($platform != 5){
            switch ($platform) {
                case 1:
                    $model = Db::connect('database.db_zeelool');
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme');
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao');
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de');
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp');
                    break;
                default:
                    $model = false;
                    break;
            }
            if (false == $model) {
                return false;
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        }
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";
        //求出眼镜所有sku
        $frame_sku = $this->itemPlatformSku->getDifferencePlatformSku(1, $platform);
        if($platform == 5){
            //某个类型的求出眼镜的销售额 base_price  base_discount_amount 太阳镜
            $frame_money = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('sku', 'in', $frame_sku)
                ->where('i.goods_type', $goods_type)
                ->value('sum(base_original_price-i.base_discount_amount) as price');
            $frame_money = $frame_money ? round($frame_money, 2) : 0;
            //某个类型的眼镜的销售副数
            $frame_sales_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.goods_type', $goods_type)
                ->where('i.sku', 'in', $frame_sku)
                ->sum('i.qty');
            //某个类型的眼镜平均副金额
            $frame_avg_money = $frame_sales_num > 0 ? round(($frame_money / $frame_sales_num), 2) : 0;
        }else{
            //某个类型的求出眼镜的销售额 base_price  base_discount_amount 太阳镜
            $frame_money_price = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->sum('m.base_price');
            //某个类型的眼镜的折扣价格
            $frame_money_discount = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->sum('m.base_discount_amount');
            //某个类型的眼镜的实际销售额
            $frame_money = round(($frame_money_price - $frame_money_discount), 2);
            //某个类型的眼镜的销售副数
            $frame_sales_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->sum('m.qty_ordered');
            //某个类型的眼镜平均副金额
            $frame_avg_money = $frame_sales_num > 0 ? round(($frame_money / $frame_sales_num), 2) : 0;
        }

        //光学镜
        if ($goods_type == 1){
            $frame_onsales_lilist = Db::name('datacenter_sku_day')
                ->where(['site' =>$platform])
                ->where($maps)
                ->where('goods_type',$goods_type)
                ->distinct(true)
                ->field('sku')
                ->select();

            //求某个类型的眼镜的正常售卖数
            $item = new ItemPlatformSku();
            $frame_onsales_num = 0;
            foreach ($frame_onsales_lilist as $k=>$v){
                $is_new = $item->where('sku',$v['sku'])
                    ->where('outer_sku_status',1)
                    ->where('platform_type',$platform)
                    ->find();
                if (!empty($is_new)){
                    $frame_onsales_num += 1;
                }
            }
            if ($frame_onsales_num == 0){
                $frame_onsales_num = $this->itemPlatformSku->putawayDifferenceSku(1, $platform);
            }
        }else{
            //其他类型眼镜
            if($platform == 5){
                $frame_onsales_num = $this->orderitemoption
                    ->alias('i')
                    ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                    ->where($whereItem)
                    ->where('i.goods_type', $goods_type)
                    ->count('distinct i.sku');
            }else{
                $frame_onsales_num = $model->table('sales_flat_order_item m')
                    ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                    ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                    ->where('p.goods_type', '=', $goods_type)
                    ->where($whereItem)
                    ->count('distinct m.sku');
            }
        }
        //求出所有新品眼镜sku
        $frame_new_sku = $this->itemPlatformSku->getDifferencePlatformNewSku(1, $platform);
        if($platform == 5){
            //某个类型的眼镜动销数
            $frame_in_print_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.goods_type', $goods_type)
                ->where('i.sku', 'in', $frame_sku)
                ->count('distinct i.sku');
            //求出某个类型的新品眼镜的销售额 base_price  base_discount_amount
            $frame_new_money =$this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.goods_type', $goods_type)
                ->where('i.sku', 'in', $frame_new_sku)
                ->value('sum(base_original_price-i.base_discount_amount) as price');
            $frame_new_money = $frame_new_money ? round($frame_new_money, 2) : 0;
            //某个类型的眼镜下单客户数
            $frame_order_customer = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.goods_type', $goods_type)
                ->where('i.sku', 'in', $frame_sku)
                ->count('distinct o.customer_email');

            $frame_new_list = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.goods_type', $goods_type)
                ->where('i.sku', 'in', $frame_new_sku)
                ->group('i.sku')
                ->field('i.sku')
                ->select();
            //某个类型的新品眼镜动销数
            $frame_new_in_print_num = $this->orderitemoption
                ->alias('i')
                ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                ->where($whereItem)
                ->where($itemMap)
                ->where('i.goods_type', $goods_type)
                ->where('i.sku', 'in', $frame_new_sku)
                ->count('distinct i.sku');
        }else{
            //某个类型的眼镜动销数
            $frame_in_print_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_sku)
                ->count('distinct m.sku');
            //求出某个类型的新品眼镜的销售额 base_price  base_discount_amount
            $frame_new_money_price = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->sum('m.base_price');
            //某个类型的新品眼镜的折扣价格
            $frame_new_money_discount = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->sum('m.base_discount_amount');
            //某个类型的新品眼镜的实际销售额
            $frame_new_money = round(($frame_new_money_price - $frame_new_money_discount), 2);
            //某个类型的眼镜下单客户数
            $frame_order_customer = $model->table('sales_flat_order o')
                ->join('sales_flat_order_item m', 'o.entity_id=m.order_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where('m.sku', 'in', $frame_sku)
                ->where($itemMap)
                ->count('distinct o.customer_email');
            $frame_new_list = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                // ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->distinct(true)
                ->field('m.sku')
                ->select();
            //某个类型的新品眼镜动销数
            $frame_new_in_print_num = $model->table('sales_flat_order_item m')
                ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
                ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
                ->where('p.goods_type', '=', $goods_type)
                ->where($whereItem)
                ->where($itemMap)
                ->where('m.sku', 'in', $frame_new_sku)
                ->count('distinct m.sku');
        }
        $frame_in_print_rate = $frame_onsales_num ? round(($frame_in_print_num/$frame_onsales_num)*100, 2).'%' : 0;
        //某个类型的眼镜客户平均副数
        $frame_avg_customer = $frame_order_customer ? round(($frame_sales_num / $frame_order_customer), 2) : 0;

        //求某个类型的新品眼镜的数量
        $item = new Item();
        $item_platform = new ItemPlatformSku();
        $frame_new_num = 0;
        foreach ($frame_new_list as $k=>$v){
            $platform_sku =$item_platform
                ->where('platform_sku',$v['sku'])
                ->value('sku');
            $is_new = $item->where('sku',$platform_sku)
                ->where('is_new',1)
                ->find();
            if (!empty($is_new)){
                $frame_new_num += 1;
            }
        }
        //某个类型的新品眼镜动销率
        $frame_new_in_print_rate = $frame_new_num ? round(($frame_new_in_print_num / $frame_new_num) * 100, 2).'%' : 0;
        //光学镜
        $arr = [
            //眼镜的实际销售额
            'frame_money' => $frame_money,
            //眼镜动销数
            'frame_in_print_num' => $frame_in_print_num,
            //眼镜动销率
            'frame_in_print_rate' => $frame_in_print_rate,
            //新品眼镜的实际销售额
            'frame_new_money' => $frame_new_money,
            //眼镜平均副金额
            'frame_avg_money' => $frame_avg_money,
            //眼镜客户平均副数
            'frame_avg_customer' => $frame_avg_customer,
            //眼镜正常售卖数
            'frame_onsales_num' => $frame_onsales_num,
            //新品眼镜数量
            'frame_new_num' => $frame_new_num,
            //新品眼镜动销数
            'frame_new_in_print_num' => $frame_new_in_print_num,
            //新品眼镜动销率
            'frame_new_in_print_rate' => $frame_new_in_print_rate,

            //眼镜的销售副数
            // 'frame_sales_num' => $frame_sales_num,
            //眼镜下单客户数
            'frame_order_customer' => $frame_order_customer,
        ];
        Cache::set('newGoodsData_platformOrderInfo1' . $platform . $goods_type . md5(serialize($map)), $arr, 7200);
        return $arr;
    }
}
