<?php
/**
 * Class Hour.php
 * @package app\admin\controller\elasticsearch
 * @author  crasphb
 * @date    2021/4/1 15:59
 */

namespace app\admin\controller\elasticsearch;


class Hour extends BaseElasticsearch
{

    public function test()
    {
        $start = '2018020500';
        $end = '2021020531';
        $order = $this->getPurchaseSearch(1, $start, $end);
    }

    public function getPurchaseSearch($site = 1, $start, $end)
    {
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
                        ],
                    ],
                ],
                "aggs"  => [
                    'allDaySalesAmount' => [
                        "sum" => [
                            'field' => 'base_grand_total',
                        ]
                    ],
                    'allQtyOrdered' => [
                        "sum" => [
                            'field' => 'total_qty_ordered',
                        ]
                    ],
                    'allAvgPrice' => [
                        "avg" => [
                            'field' => 'base_grand_total',
                        ]
                    ],
                    "hourSale" => [
                        "terms" => [
                            "field" => 'hour',
                            'size'  => '24',
                            'order' => [
                                '_key' => 'asc'
                            ]
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
                ],

            ],
        ];
        $results = $this->esClient->search($params);
        return $results['aggregations']['hourSale']['buckets'];
    }

}