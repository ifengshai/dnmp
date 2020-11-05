<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Controller;
use think\Request;
use think\Db;
use think\Cache;
use app\admin\model\OrderItemInfo;
use app\admin\model\platformmanage\MagentoPlatform;


class GoodsSaleDetail extends Backend
{
    //订单类型数据统计
    protected $item = null;
    protected $itemPlatformSku = null;
    protected $noNeedRight = ['ceshi'];

    public function _initialize()
    {
        parent::_initialize();

        $this->item_platform = new ItemPlatformSku();
        $this->item = new Item();
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;

    }

    /*
 * 销量榜单列表
 */
    public function top_sale_list()
    {
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        if (empty($orderPlatform)) {
            $this->error('您没有权限访问', 'general/profile?ref=addtabs');
        }
        $create_time = input('create_time');
        $platform = input('order_platform', current($orderPlatform));
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //默认7天数据
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['created_at'] = $itemMap['m.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['created_at'] = $itemMap['m.created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $order_platform = $params['platform'];
            if (100 <= $order_platform) {
                return $this->error('该平台暂时没有数据');
            }
            //缓存图标数据
            $create_date = $frame_sales_num = $frame_in_print_num = $decoration_sales_num = $decoration_in_print_num = [];
            $top_data = Cache::get('Operationalreport_index_top' . $order_platform . md5(serialize($map)));
            if ($top_data) {
                $list = $top_data;
            } else {
                $orderItemInfo = new OrderItemInfo();
                $list = $orderItemInfo->getAllData($order_platform);
                Cache::set('Operationalreport_index_top' . $order_platform . md5(serialize($map)), $list, 7200);
            }
            if ($list) {
                foreach ($list as $v) {
                    $frame_sales_num[] = $v['frame_sales_num'];
                    $frame_in_print_num[] = $v['frame_in_print_num'];
                    $decoration_sales_num[] = $v['decoration_sales_num'];
                    $decoration_in_print_num[] = $v['decoration_in_print_num'];
                    $create_date[] = $v['create_date'];
                }
            }
            $json['xColumnName'] = $json2['xColumnName'] = $create_date ? $create_date : [];
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => $frame_sales_num ? $frame_sales_num : [],
                    'name' => '眼镜销售副数'
                ],
                [
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => $frame_in_print_num ? $frame_in_print_num : [],
                    'name' => '眼镜动销数'
                ]

            ];
            $json2['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => $decoration_sales_num ? $decoration_sales_num : [],
                    'name' => '配饰销售副数'
                ],
                [
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => $decoration_in_print_num ? $decoration_in_print_num : [],
                    'name' => '配饰动销数'
                ]
            ];
            if ($params['key'] == 'frame_sales_num') {
                return json(['code' => 1, 'data' => $json]);

            } elseif ($params['key'] == 'decoration_sales_num') {
                return json(['code' => 1, 'data' => $json2]);
            } else {
                $result = $this->platformOrderInfo($order_platform, $map, $itemMap);
                if (!$result) {
                    return $this->error('暂无数据');
                }
                return json(['code' => 1, 'rows' => $result]);
            }


        }
        $this->view->assign(
            [
                'orderPlatformList' => $orderPlatform,
                'create_time' => $create_time,
                'platform' => $platform,

            ]
        );
        $this->assign('create_time', $create_time);
        $this->assign('label', $platform);
        $this->assignconfig('create_time', $create_time);
        $this->assignconfig('label', $platform);
        return $this->view->fetch();
    }

    /*
     * 眼镜关键指标 饰品关键指标
     */

    public function platformOrderInfo($platform, $map, $itemMap)
    {
        $arr = Cache::get('Operationalreport_platformOrderInfo' . $platform . md5(serialize($map)));
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
            default:
                $model = false;
                break;
        }
        if (false == $model) {
            return false;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $where = " status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";


        //求出眼镜所有sku
        $frame_sku = $this->itemPlatformSku->getDifferencePlatformSku(1, $platform);
        //求出饰品的所有sku
        $decoration_sku = $this->itemPlatformSku->getDifferencePlatformSku(3, $platform);
        //求出眼镜的销售额 base_price  base_discount_amount
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
            ->count('*');
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
            ->count('*');
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
            $frame_in_print_rate = round(($frame_in_print_num / $frame_onsales_num) * 100, 2);
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
            $decoration_in_print_rate = round(($decoration_in_print_num / $decoration_onsales_num) * 100, 2);
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
            $frame_new_in_print_rate = round(($frame_new_in_print_num / $frame_new_num) * 100, 2);
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
            $decoration_new_in_print_rate = round(($decoration_new_in_print_num / $decoration_new_num) * 100, 2);
        } else {
            $decoration_new_in_print_rate = 0;
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
        Cache::set('Operationalreport_platformOrderInfo' . $platform . md5(serialize($map)), $arr, 7200);
        return $arr;
    }

    public function index1()
    {
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        if (empty($orderPlatform)) {
            $this->error('您没有权限访问', 'general/profile?ref=addtabs');
        }
        $create_time = input('create_time');
        $label = input('order_platform', 1);
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //默认当天
            if ($params['create_time']) {
                $time = explode(' ', $params['create_time']);
                $map['a.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['a.created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }
            $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $itemPlatformSku
                // ->where($where)
                // ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $itemPlatformSku
                // ->where($where)
                // ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            return json(["total" => $total, 'rows' => $list]);
        }
        $this->assign('create_time', $create_time);
        $this->assign('label', $label);
        $this->assign('orderPlatformList', $orderPlatform);
        $this->assignconfig('create_time', $create_time);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /*
     * 商品销售情况首页数据
     */
    public function index()
    {
        // $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        // if (empty($orderPlatform)) {
        //     $this->error('您没有权限访问', 'general/profile?ref=addtabs');
        // }
        //查询对应平台权限
        $magentoplatformarr = (new MagentoPlatform())->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $create_time = input('create_time');
        $label = input('order_platform', 1);
        if ($this->request->isAjax()) {
            $params = $this->request->param();


            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['create_time-operate']) {
                unset($filter['create_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['create_time']) {
                $time = explode(' ', $filter['create_time']);
                // $map['a.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
                $map['day_date'] = ['between', [$time[0], $time[3]]];
                unset($filter['create_time']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                // $map['a.created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
                $map['day_date'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
                unset($filter['create_time']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['order_platform']) {
                $site = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $site = 1;
            }
            $map['site'] = $site;
            $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('datacenter_sku_day')
                ->where($where)
                ->where($map)
                ->group('sku')
                ->order('day_date', 'desc')
                ->count();
            $sku_data_day = Db::name('datacenter_sku_day')
                ->where($where)
                ->where($map)
                ->group('sku')
                ->field('id,sku,sum(cart_num) as cart_num,now_pricce,max(day_date) as day_date,single_price,day_stock,day_onway_stock,sum(sales_num) as sales_num,sum(order_num) as order_num,sum(glass_num) as glass_num,sum(sku_row_total) as sku_row_total,sum(sku_grand_total) as sku_grand_total,sum(sku_grand_total) as sku_grand_total')
                ->order('day_date', 'desc')
                ->limit($offset, $limit)
                ->select();
            foreach ($sku_data_day as $k => $v) {
                $sku_detail = $itemPlatformSku
                    ->where(['sku' => $v['sku'], 'platform_type' => $site])
                    ->field('platform_sku,stock,plat_on_way_stock,outer_sku_status,grade')
                    ->find();
                //sku转换
                $sku_data_day[$k]['sku_change'] = $sku_detail['platform_sku'];
                //上下架状态
                $sku_data_day[$k]['status'] = $sku_detail['outer_sku_status'];
                $sku_data_day[$k]['available_stock'] = $sku_detail['stock'];
                $sku_data_day[$k]['on_way_stock'] = $sku_detail['plat_on_way_stock'];
                $sku_data_day[$k]['grade'] = $sku_detail['grade'];
                $sku_data_day[$k]['is_up'] = $sku_detail['outer_sku_status'];
            }
            if (array_filter($sku_data_day) > 0) {
                $sortField = array_column($sku_data_day, 'available_stock');
                //可用库存倒叙排列
                if (($params['sort'] == 'available_stock') && ($params['order'] == 'desc')) {
                    array_multisort($sortField, SORT_DESC, $sku_data_day);
                    //可用库存正序排列
                } elseif (($params['sort'] == 'available_stock') && ($params['order'] == 'asc')) {
                    array_multisort($sortField, SORT_ASC, $sku_data_day);
                }

            }
            return json(['code' => 1, 'rows' => $sku_data_day, 'total' => $total]);
        }
        $this->view->assign(
            [
                'orderPlatformList' => $magentoplatformarr,
                'create_time' => $create_time,
            ]
        );
        $this->assign('create_time', $create_time);
        $this->assign('label', $label);
        $this->assignconfig('create_time', $create_time);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    //其他关键指标
    public function mid_data()
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
            $map['created_at'] = $itemMap['m.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
        } else {
            $map['created_at'] = $itemMap['m.created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
        }
        $platform = $platform;
        $arr = Cache::get('Operationalreport_platformOrderInfo1' . $platform . md5(serialize($map)));
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
        $where = " status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";


        //求出眼镜所有sku
        $frame_sku = $this->itemPlatformSku->getDifferencePlatformSku(1, $platform);

        //求出眼镜的销售额 base_price  base_discount_amount 太阳镜
        $frame_money_price = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_sku)
            ->sum('m.base_price');
        //眼镜的折扣价格
        $frame_money_discount = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_sku)
            ->sum('m.base_discount_amount');
        //眼镜的实际销售额
        $frame_money = round(($frame_money_price - $frame_money_discount), 2);
        //眼镜的销售副数
        $frame_sales_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_sku)
            ->count('*');
        //眼镜平均副金额
        if (0 < $frame_sales_num) {
            $frame_avg_money = round(($frame_money / $frame_sales_num), 2);
        } else {
            $frame_avg_money = 0;
        }

        //眼镜正常售卖数
        $frame_onsales_num = $this->itemPlatformSku->putawayDifferenceSku(1, $platform);
        //正常售卖的某个品类的眼镜的数量
        $frame_onsales_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where('m.sku', 'in', $frame_sku)
            ->count('distinct m.sku');

        //眼镜动销数
        $frame_in_print_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_sku)
            ->count('distinct m.sku');
        //眼镜总共的数量
        //$frame_num                 = $this->item->getDifferenceSkuNUm(1);
        //眼镜动销率
        if (0 < $frame_onsales_num) {
            $frame_in_print_rate = round(($frame_in_print_num / $frame_onsales_num) * 100, 2);
        } else {
            $frame_in_print_rate = 0;
        }

        //求出所有新品眼镜sku
        $frame_new_sku = $this->itemPlatformSku->getDifferencePlatformNewSku(1, $platform);

        //求出新品眼镜的销售额 base_price  base_discount_amount
        $frame_new_money_price = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_new_sku)
            ->sum('m.base_price');
        //新品眼镜的折扣价格
        $frame_new_money_discount = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_new_sku)
            ->sum('m.base_discount_amount');
        //新品眼镜的实际销售额
        $frame_new_money = round(($frame_new_money_price - $frame_new_money_discount), 2);

        //眼镜下单客户数
        $frame_order_customer = $model->table('sales_flat_order o')
            ->join('sales_flat_order_item m', 'o.entity_id=m.order_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where('m.sku', 'in', $frame_sku)
            ->where($itemMap)
            ->count('distinct o.customer_email');
        //眼镜客户平均副数
        if (0 < $frame_order_customer) {
            $frame_avg_customer = round(($frame_sales_num / $frame_order_customer), 2);
        }

        //新品眼镜数量
        $frame_new_num = $this->item->getDifferenceNewSkuNum(1);
        //新品眼镜某个品类的数量
        $frame_new_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where('m.sku', 'in', $frame_new_sku)
            ->count('distinct m.sku');

        //新品眼镜动销数
        $frame_new_in_print_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->where('m.sku', 'in', $frame_new_sku)
            ->count('distinct m.sku');
        //新品眼镜动销率
        if (0 < $frame_new_num) {
            $frame_new_in_print_rate = round(($frame_new_in_print_num / $frame_new_num) * 100, 2);
        } else {
            $frame_new_in_print_rate = 0;
        }

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
        Cache::set('Operationalreport_platformOrderInfo1' . $platform . $goods_type . md5(serialize($map)), $arr, 7200);
        return $arr;
    }

    public function ceshi()
    {
        $arr = $this->other_key_plat(1, 1, '2020-10-21 00:00:00 - 2020-10-27 23:59:59');
        dump($arr);
        die;
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        var_dump($orderPlatform);
        var_dump($orderPlatform[1]);
    }
}