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
     * 商品销售情况首页数据
     */
    public function index()
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

    /*
     * 销量榜单列表
     */
    public function top_sale_list()
    {
        $create_time = input('create_time');
        $label = input('label', 1);
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //默认当天默认7天数据
            if ($params['time']) {
                $time = explode(' ', $params['time']);
                $map['a.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
            } else {
                $map['a.created_at'] = ['between', [date('Y-m-d 00:00:00', strtotime('-7 day')), date('Y-m-d H:i:s', time())]];
            }

            //列表
            $result = [];
            if ($params['type'] == 'list') {
                $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
                if ($params['site'] == 1) {
                    //查询对应平台销量
                    $list = $this->zeelool->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(1);
                } elseif ($params['site'] == 2) {
                    //查询对应平台销量
                    $list = $this->voogueme->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(2);
                } elseif ($params['site'] == 3) {
                    //查询对应平台销量
                    $list = $this->nihao->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(3);
                } elseif ($params['site'] == 4) {
                    //查询对应平台销量
                    $list = $this->meeloog->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(4);
                }elseif ($params['site'] == 5){
                    //查询对应平台销量
                    $list = $this->wesee->getOrderSalesNum([], $map);
                    //查询对应平台商品SKU
                    $skus = $itemPlatformSku->getWebSkuAll(5);
                }
                $productInfo = $this->item->getSkuInfo();
                $list = $list ?? [];
                $i = 0;
                foreach ($list as $k => $v) {
                    $result[$i]['platformsku'] = $k;
                    $result[$i]['sales_num'] = $v;
                    $result[$i]['sku'] = $skus[trim($k)]['sku'];
                    $result[$i]['grade'] = $skus[trim($k)]['grade'];
                    $result[$i]['is_up'] = $skus[trim($k)]['outer_sku_status'];
                    $result[$i]['available_stock'] = $skus[trim($k)]['stock'];
                    $result[$i]['name'] = $productInfo[$skus[trim($k)]['sku']]['name'];
                    $result[$i]['type_name'] = $productInfo[$skus[trim($k)]['sku']]['type_name'];
                    $i++;
                }
            }
            if(array_filter($result)>0){
                $sortField = array_column($result,'available_stock');
                //可用库存倒叙排列
                if(($params['sort'] == 'available_stock') && ($params['order'] == 'desc')){
                    array_multisort($sortField,SORT_DESC,$result);
                    //可用库存正序排列
                }elseif(($params['sort'] == 'available_stock') && ($params['order'] == 'asc')){
                    array_multisort($sortField,SORT_ASC,$result);
                }

            }
            return json(['code' => 1,'rows' => $result]);
        }
        $this->assign('create_time', $create_time);
        $this->assign('label', $label);
        $this->assignconfig('create_time', $create_time);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }
    public function ceshi()
    {
        $orderPlatform = (new MagentoPlatform())->getNewAuthSite();
        var_dump($orderPlatform);
        var_dump($orderPlatform[1]);
    }
}