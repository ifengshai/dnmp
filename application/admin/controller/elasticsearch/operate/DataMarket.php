<?php
/**
 * Class DataMarket.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/16 11:33
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\OperationAnalysis;
use app\admin\model\platformmanage\MagentoPlatform;
use think\Cache;

class DataMarket extends BaseElasticsearch
{

    /**
     * 仪表盘首页
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/4/24 14:09
     */
    public function index()
    {
        $platform = (new MagentoPlatform())->getNewAuthSite();
        foreach ($platform as $k => $v) {
            if (in_array($k, [5, 8, 13, 14])) {
                unset($platform[$k]);
            }
        }
        if (empty($platform)) {
            $this->error('您没有权限访问', 'general/profile?ref=addtabs');
        }
        $result = $this->getCharts();
        $xData = $result['xData'];
        $yData = $result['yData'];
        $this->view->assign(compact('web_site', 'time_str', 'platform', 'yData', 'xData'));

        return $this->view->fetch();
    }

    /**
     * 获取图标数据
     * @return array
     * @author crasphb
     * @date   2021/4/21 15:23
     */
    public function getCharts()
    {
        $start = date('Ymd', strtotime('-30 days'));
        $end = date('Ymd');
        $cacheStr = 'data_market_echart_' . $start . '-' . $end;
        $cacheData = Cache::get($cacheStr);
        if(!$cacheData) {
            $echartsData = $this->esFormatData->formatDataMarketEcharts($this->buildDataMarketEchartsSearch($this->site, $start, $end));
            Cache::set($cacheStr,$echartsData,600);
        }else{
            $echartsData = $cacheData;
        }

        return $echartsData;
    }

    /**
     * 图标查询
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 11:41
     */
    public function buildDataMarketEchartsSearch($site, $start, $end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_datacenterday',
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
                        ],
                    ],
                ],
                'aggs'  => [
                    'site' => [
                        'terms' => [
                            'field' => 'site',
                        ],
                        'aggs'  => [
                            //天聚合
                            "daySale" => [
                                "terms" => [
                                    "field" => 'day_date',
                                    'size'  => '10000',
                                    'order' => [
                                        '_key' => 'asc',
                                    ],
                                ],
                                "aggs"  => [
                                    //总数聚合
                                    'registerNum'     => [
                                        'sum' => [
                                            'field' => 'register_num',
                                        ],
                                    ],
                                    'avgPrice'        => [
                                        "sum" => [
                                            'field' => 'order_unit_price',
                                        ],
                                    ],
                                    'salesTotalMoney' => [
                                        "sum" => [
                                            'field' => 'sales_total_money',
                                        ],
                                    ],

                                    'cartNum'  => [
                                        "sum" => [
                                            'field' => 'cart_num',
                                        ],
                                    ],
                                    'orderNum' => [
                                        "sum" => [
                                            'field' => 'order_num',
                                        ],
                                    ],
                                ],
                            ],

                        ],
                    ],
                ],
            ],
        ];


        return $this->esService->search($params);
    }

    /**
     * 获取顶部数据
     *
     * @param null $order_platform
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/4/23 11:50
     */
    public function async_data($order_platform = null)
    {
        if ($this->request->isAjax()) {
            if (!$order_platform) {
                return $this->error('参数不存在，请重新尝试');
            }
            $begin = $end = date('Ymd');
            $site = $order_platform == 100 ? [1, 2, 3, 4] : $order_platform;
            $siteAll = $order_platform == 100 ? true : false;
            $cacheStr = 'data_market_async_data_' . $site . '-' . $begin;
            $cacheData = Cache::get($cacheStr);
            //if(!$cacheData) {
                $topOrder = $this->buildDataMarketTopOrderSearch($site, $begin, $begin);
                $topCart = $this->buildDataMarketTopCartSearch($site, $begin, $begin);
                $topCustomer = $this->buildDataMarketTopCustomerSearch($site, $begin, $begin);
                $topCartCreateIds = $this->esFormatData->formatGetCartIds($this->DataMarketTopCreateCartSearch($site, $begin, $begin));
                $topCartToOrder = $this->buildDataMarketTopCartToOrderSearch($site, $begin, $begin,$topCartCreateIds);
                $operationData = (new OperationAnalysis())->getSiteAnalysis($site);
                $data = $this->esFormatData->formatDataMarketTop($site, $operationData, $topOrder, $topCart, $topCustomer,$topCartToOrder, $begin, $this->status, $siteAll);
                Cache::set($cacheStr,$data,600);
//            }else{
//                $data = $cacheData;
//            }

            if (false == $data) {
                return $this->error('没有该平台数据,请重新选择');
            }

            return $this->success('', '', $data, 0);
        }
    }

    /**
     * 顶部数据 = 今天的销售额，订单，订单支付成功，客单价
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 15:40
     */
    public function buildDataMarketTopOrderSearch($site, $start, $end)
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
                        ],
                    ],
                ],
                "aggs"  => [
                    'status' => [
                        'terms' => [
                            'field' => 'status',
                        ],
                        "aggs"  => [
                            //总数聚合
                            'allDaySalesAmount' => [
                                "sum" => [
                                    'field' => 'base_grand_total',
                                ],
                            ],
                            'allAvgPrice'       => [
                                "avg" => [
                                    'field' => 'base_grand_total',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }
    public function buildDataMarketTopCartToOrderSearch($site, $start, $end, $ids)
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
                            [
                                'terms' => [
                                    'entity_id' => $ids,
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    "cartToOrder" => [
                        "terms" => [
                            "field" => 'day_date'
                        ],
                    ],

                ],
            ],
        ];

        return $this->esService->search($params);
    }

    /**
     * 顶部数据 = 今天的购物车数据
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 15:40
     */
    public function buildDataMarketTopCartSearch($site, $start, $end)
    {
        return $this->DataMarketTopCommonSearch('mojing_cart', $site, $start, $end);
    }

    /**
     * 公共的搜索方法
     *
     * @param $index
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 15:57
     */
    public function DataMarketTopCommonSearch($index, $site, $start, $end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => $index,
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            //in查询
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                        ],
                        'should' => [
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
                                    'update_time_day' => [
                                        'gte' => $start,
                                        'lte' => $end,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    "dayCreate" => [
                        "terms" => [
                            "field" => 'day_date',
                            "order" => [
                                "_key" => "desc"
                            ],
                        ],
                    ],
                    "dayUpdate" => [
                        "terms" => [
                            "field" => 'update_time_day',
                            "order" => [
                                "_key" => "desc"
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }
    public function DataMarketTopCreateCartSearch($site, $start,$end)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_cart',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must'   => [
                            //in查询
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                            [
                                'match' => [
                                    'day_date' => $start
                                ],
                            ]
                        ],
                    ],
                ],
                "aggs"  => [
                    "dayCreate" => [
                        "terms" => [
                            "field" => 'id',
                            "size" => 10000
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }
    /**
     * 顶部数据 = 今天的用户数据
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 15:40
     */
    public function buildDataMarketTopCustomerSearch($site, $start, $end)
    {
        return $this->DataMarketTopCommonSearch('mojing_customer', $site, $start, $end);
    }

    /**
     * 数据概况 -- 底部数据获取
     *
     * @author crasphb
     * @date   2021/4/20 11:48
     */
    public function ajaxGetBottom()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $timeStr = $params['create_time'];
            if (!$timeStr) {
                $start = date('Ymd', strtotime('-30 days'));
                $end = date('Ymd');
            } else {
                $timeStr = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($timeStr[0]));
                $end = date('Ymd', strtotime($timeStr[3]));
            }
            $cacheStr = 'data_market_async_get_bottom_' . $start . '-' . $end;
            $cacheData = Cache::get($cacheStr);
            if(!$cacheData) {
                $bottomData = $this->esFormatData->formatDataMarketBottom($this->buildDataMarketBottomSearch($this->site, $start, $end));
                Cache::set($cacheStr,$bottomData,600);
            }else{
                $bottomData = $cacheData;
            }

            if (!$bottomData) {
                return $this->error('没有对应的时间数据，请重新尝试');
            }

            return $this->success('', '', $bottomData, 0);
        }


    }

    /**
     * 底部三十天销量查询
     *
     * @param $site
     * @param $status
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 11:41
     */
    public function buildDataMarketBottomSearch($site, $start, $end)
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
                    'site' => [
                        'terms' => [
                            'field' => 'site',
                        ],
                        'aggs'  => [
                            'store' => [
                                "terms" => [
                                    "field" => 'store_id',
                                ],
                                "aggs"  => [
                                    //总数聚合
                                    'allDaySalesAmount' => [
                                        "sum" => [
                                            'field' => 'base_grand_total',
                                        ],
                                    ],
                                    'allAvgPrice'       => [
                                        "avg" => [
                                            'field' => 'base_grand_total',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }

}