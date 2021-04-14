<?php
/**
 * Class Hour.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/14 10:35
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\service\google\session;

class Hour extends BaseElasticsearch
{
    public function index()
    {
        $start = '2018020500';
        $end = '2021020500';
        $site = 1;
        $hourOrderData = $this->buildHourOrderSearch($site, $start, $end);
        $hourCartData = $this->buildHourCartSearch($site, $start, $end);

        $sessionService = new session($site);
        $gaData = $sessionService->gaHourData('2018-02-05', '2021-02-05');
        $res = $this->esFormatData->formatHourData($hourOrderData, $hourCartData, $gaData);
        echo json_encode($res);
        die;
        file_put_contents('./a.json', json_encode($res));
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
                                    'hour_date' => [
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
     * 获取购物车数据
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function buildHourCartSearch($site, $start, $end)
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
                                    'hour_date' => [
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
                    "hourCart" => [
                        "terms" => [
                            "field" => 'hour',
                            'size'  => '24',
                            'order' => [
                                '_key' => 'asc',
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