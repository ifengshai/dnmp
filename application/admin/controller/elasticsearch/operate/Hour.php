<?php
/**
 * Class Hour.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/14 10:35
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;

class Hour extends BaseElasticsearch
{
    public function index()
    {
        $start = '20180205';
        $end = '20210205';
        $site = 1;
        $hourOrderData = $this->buildHourOrderSearch($site,$start,$end);
        $hourCartData = $this->buildHourCartSearch($site,$start,$end);
        $this->formatHourData($hourOrderData,$hourCartData);
    }
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
                    ]
                ],
            ],
        ];

        return $this->esService->search($params);
    }
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
                ]
            ],
        ];

        return $this->esService->search($params);
    }
}