<?php
/**
 * Class Hour.php
 * @package app\admin\controller\elasticsearch\customer\order
 * @author  crasphb
 * @date    2021/5/12 11:53
 */

namespace app\admin\controller\elasticsearch\customer\order;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;
use app\service\elasticsearch\customer\OrderEsFormat;
use app\service\google\Session;
use think\Cache;

class Hour extends BaseElasticsearch
{
    public $esFormat = null;

    public function _initialize()
    {
        $this->esFormat = new OrderEsFormat();

        return parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 列表页渲染
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/5/8 15:08
     */
    public function index()
    {
        $magentoplatformarr = new MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $magentoplatformarr->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao', 'zeelool_de', 'zeelool_jp', 'wesee'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('web_site', 'time_str', 'magentoplatformarr'));

        return $this->view->fetch();
    }

    /**
     * 分时图表获取
     * @return \think\response\Json
     * @author crasphb
     * @date   2021/5/14 9:17
     */
    public function ajaxGetChartsResult()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $type = $params['type'];
            $timeStr = $params['time_str'];
            $nowDate = date('Ymd');
            $compareTimeStr = $params['compare_time_str'];
            $today = false;
            if (!$timeStr) {
                $start = $end = $timeStr = $nowDate;
                $today = true;
            } else {
                $createat = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($createat[0]));
                $end = date('Ymd', strtotime($createat[3]));
                if($start == $end && $start == $nowDate) {
                    $today = true;
                }
            }
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $time = $start . '-' . $end;
            $cacheStr = 'day_hour_order_quote_charts_' . $site . $time . $compareTimeStr;;
            $cacheData = Cache::get($cacheStr);
            if (!$cacheData) {
                $compareData = [];
                if ($compareTimeStr) {
                    $compareTime = explode(' ', $compareTimeStr);
                    $compareStart = date('Ymd', strtotime($compareTime[0]));
                    $compareEnd = date('Ymd', strtotime($compareTime[3]));
                    $compareData = $this->buildHourOrderChatsSearch($site, $compareStart, $compareEnd);
                }
                $hourOrderData = $this->buildHourOrderChatsSearch($site, $start, $end);

                $allData = $this->esFormat->formatHourChartsData($hourOrderData, $compareData,$today);
                Cache::set($cacheStr, $allData, 600);
            } else {
                $allData = $cacheData;
            }
            switch ($type) {
                case 1:
                    $data = $allData['totalQtyOrderedEcharts'];

                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $allData['daySalesAmountEcharts'];

                    return json(['code' => 1, 'data' => $data]);
                case 3:
                    $data = $allData['orderCounterEcharts'];

                    return json(['code' => 1, 'data' => $data]);
                case 4:
                    $data = $allData['avgPriceEcharts'];

                    return json(['code' => 1, 'data' => $data]);
            }
        }
    }

    /**
     * 获取销量统计图表查询
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/5/14 9:17
     */
    public function buildHourOrderChatsSearch($site, $start, $end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_order',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    'day_date' => [
                                        'gte' => $start,
                                        'lte' => $end,
                                    ],
                                ],
                            ],
                            //in查询
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                            [
                                'terms' => [
                                    'status' => $this->status,
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    //小时聚合
                    "hourSale" => [
                        "terms" => [
                            "field" => 'hour',
                            'size'  => '24',
                            'order' => [
                                '_key' => 'asc',
                            ],
                        ],
                        "aggs"  => [
                            "daySalesAmount"  => [
                                "sum" => [
                                    "field" => "base_grand_total",
                                ],
                            ],
                            "totalQtyOrdered" => [
                                "sum" => [
                                    "field" => "total_qty_ordered",
                                ],
                            ],
                            "avgPrice"        => [
                                "avg" => [
                                    "field" => "base_grand_total",
                                ],
                            ],
                        ],
                    ],
                    "sumOrder" => [
                        "sum_bucket" => [
                            "buckets_path" => "hourSale>_count",
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }

    /**
     * ajax获取销量数据
     * @return \think\response\Json
     * @throws \Google_Exception
     * @author crasphb
     * @date   2021/4/24 14:09
     */
    public function ajaxGetResult()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $type = $params['type'];
            $timeStr = $params['time_str'];
            $nowDate = date('Ymd');
            $today = false;
            if (!$timeStr) {
                $start = $end = $timeStr = $nowDate;
                $gaStart = $gaEnd = date('Y-m-d');
                $today = true;
            } else {
                $createat = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($createat[0]));
                $end = date('Ymd', strtotime($createat[3]));
                $gaStart = date('Y-m-d', strtotime($createat[0]));
                $gaEnd = date('Y-m-d', strtotime($createat[3]));
            }
            if ($start == $end && $start == date('Ymd')) {
                $today = true;
            }

            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $time = $start . '-' . $end;
            $cacheStr = 'day_hour_order_quote_' . $site . $time;
            $cacheData = Cache::get($cacheStr);
            //if (!$cacheData) {
                $hourOrderData = $this->buildHourOrderSearch($site, $start, $end);
                $hourCreateCartData = $this->buildHourCreateCartSearch($site, $start, $end);
                $hourUpdateCartData = $this->buildHourUpdateCartSearch($site, $start, $end);
                $sessionService = new Session($site);
                $gaData = $sessionService->gaHourData($gaStart, $gaEnd);

                $allData = $this->esFormat->formatHourData($hourOrderData, $hourCreateCartData, $hourUpdateCartData, $gaData, $today);
                Cache::set($cacheStr, $allData, 600);
//            } else {
//                $allData = $cacheData;
//            }

            $str = '';
            foreach ($allData['arr'] as $key => $val) {
                $num = $key + 1;
                $str .= '<tr><td>' . $num . '</td><td>' . $val['hour_created'] . '</td><td>' . $val['orderCounter'] . '</td><td>' . $val['totalQtyOrdered'] . '</td><td>' . round($val['daySalesAmount'], 2) . '</td><td>' . $val['avgPrice'] . '</td><td>' . $val['sessions'] . '</td><td>' . $val['addCartRate'] . '</td><td>' . $val['sessionRate'] . '</td><td>' . $val['createCartCount'] . '</td><td>' . $val['createCartRate'] . '</td><td>' . $val['updateCartCount'] . '</td><td>' . $val['updateCartRate'] . '</td></tr>';
            }
            $str .= '<tr><td>' . count($allData['arr']) . '</td><td>合计</td><td>' . $allData['allOrderCount'] . '</td><td>' . $allData['allQtyOrdered'] . '</td><td>' . $allData['allDaySalesAmount'] . '</td><td>' . $allData['allAvgPrice'] . '</td><td>' . $allData['allSession'] . '</td><td>' . $allData['addCartRate'] . '</td><td>' . $allData['sessionRate'] . '</td><td>' . $allData['allHourCreateCart'] . '</td><td>' . $allData['createCartRate'] . '</td><td>' . $allData['allHourUpdateCart'] . '</td><td>' . $allData['updateCartRate'] . '</td></tr>';
            $data = compact('time_str', 'order_platform', 'str');
            $this->success('', '', $data);
        }
    }

    /**
     * 获取时段销量数据
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function buildHourOrderSearch($site, $start, $end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_order',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    'day_date' => [
                                        'gte' => $start,
                                        'lte' => $end,
                                    ],
                                ],
                            ],
                            //in查询
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                            [
                                'terms' => [
                                    'status' => $this->status,
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    //总数聚合
                    'allDaySalesAmount' => [
                        "sum" => [
                            'field' => 'base_grand_total',
                        ],
                    ],
                    'allQtyOrdered'     => [
                        "sum" => [
                            'field' => 'total_qty_ordered',
                        ],
                    ],
                    'allAvgPrice'       => [
                        "avg" => [
                            'field' => 'base_grand_total',
                        ],
                    ],
                    //小时聚合
                    "hourSale"          => [
                        "terms" => [
                            "field" => 'hour',
                            'size'  => '24',
                            'order' => [
                                '_key' => 'asc',
                            ],
                        ],
                        "aggs"  => [
                            "quoteIds"        => [
                                "terms" => [
                                    'field' => 'quote_id',
                                ],
                            ],
                            "daySalesAmount"  => [
                                "sum" => [
                                    "field" => "base_grand_total",
                                ],
                            ],
                            "totalQtyOrdered" => [
                                "sum" => [
                                    "field" => "total_qty_ordered",
                                ],
                            ],
                            "avgPrice"        => [
                                "avg" => [
                                    "field" => "base_grand_total",
                                ],
                            ],
                        ],
                    ],
                    "sumOrder"          => [
                        "sum_bucket" => [
                            "buckets_path" => "hourSale>_count",
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }

    /**
     * 获取新增购物车数据
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function buildHourCreateCartSearch($site, $start, $end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_cart',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    'day_date' => [
                                        'gte' => $start,
                                        'lte' => $end,
                                    ],
                                ],
                            ],
                            [
                                'range' => [
                                    'base_grand_total' => [
                                        'gt' => 0
                                    ],
                                ],
                            ],
                            //in查询
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    "hourCart" => [
                        "terms" => [
                            "field" => 'hour',
                            'size'  => '24',
                            'order' => [
                                '_key' => 'asc',
                            ],
                        ],
                        "aggs"  => [
                            "ids" => [
                                "terms" => [
                                    'field' => 'entity_id.keyword',
                                    'size'  => 1000000,
                                ],
                            ],
                        ],
                    ],
                    "sumCarts" => [
                        "sum_bucket" => [
                            "buckets_path" => "hourCart>_count",
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }


    /**
     * 更新购物车筛选
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/5/13 21:03
     */
    public function buildHourUpdateCartSearch($site, $start, $end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_cart',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    'update_time_day' => [
                                        'gte' => $start,
                                        'lte' => $end,
                                    ],
                                ],
                            ],
                            [
                                'range' => [
                                    'base_grand_total' => [
                                        'gt' => 0
                                    ],
                                ],
                            ],
                            //in查询
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    "hourCart" => [
                        "terms" => [
                            "field" => 'update_time_hour',
                            'size'  => '24',
                            'order' => [
                                '_key' => 'asc',
                            ],
                        ],
                        "aggs"  => [
                            "ids" => [
                                "terms" => [
                                    'field' => 'entity_id.keyword',
                                    'size'  => 1000000,
                                ],
                            ],
                        ],
                    ],
                    "sumCarts" => [
                        "sum_bucket" => [
                            "buckets_path" => "hourCart>_count",
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }
}