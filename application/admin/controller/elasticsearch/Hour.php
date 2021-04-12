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
        $pruchaseData = $this->getPurchaseSearch([1, 2, 3], $start, $end);

        return $this->esFormatData->formatPurchaseData($pruchaseData);

    }

    /**
     * 订单数据统计（包含几乎所有的聚合和统计）
     *
     * @param     $start
     * @param     $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/2 15:14
     */
    public function getPurchaseSearch($site, $start, $end)
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
                    'site' => [
                        "terms" => [
                            "field" => 'site',
                        ],
                        "aggs"  => [
                            //总数聚合
                            'allDaySalesAmount' => [
                                "sum" => [
                                    'field' => 'base_grand_total',
                                ],
                            ],
                            'allShippingAmount' => [
                                'sum' => [
                                    'field' => 'base_shipping_amount',
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
                            //国家求订单
                            "countrySale"       => [
                                "terms" => [
                                    "field" => 'country_id',
                                    'size'  => '150',
                                    'order' => [
                                        '_count' => 'desc',
                                    ],
                                ],
                            ],

                            //价格区间聚合
                            'price_ranges'      => [
                                'range' => [
                                    'field'  => 'base_grand_total',
                                    'ranges' => [
                                        ['from' => '0', 'to' => '19.99'],
                                        ['from' => '20', 'to' => '29.99'],
                                        ['from' => '30', 'to' => '39.99'],
                                        ['from' => '40', 'to' => '49.99'],
                                        ['from' => '50', 'to' => '59.99'],
                                        ['from' => '60', 'to' => '79.99'],
                                        ['from' => '80', 'to' => '99.99'],
                                        ['from' => '100', 'to' => '199.99'],
                                        ['from' => '200', 'to' => '5000000'],
                                    ],
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
                            //天聚合
                            "daySale"           => [
                                "terms" => [
                                    "field" => 'day_date',
                                    'size'  => '10000',
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
                            //订单类型聚合
                            "orderType"         => [
                                "terms" => [
                                    "field" => 'order_type',
                                    'order' => [
                                        '_key' => 'asc',
                                    ],
                                ],
                                "aggs"  => [
                                    "salesAmount" => [
                                        "sum" => [
                                            "field" => "base_grand_total",
                                        ],
                                    ],
                                ],
                            ],
                            //运输方式聚合
                            "shipType"          => [
                                'terms' => [
                                    "field" => 'shipping_method',
                                ],
                                'aggs'  => [
                                    'allShippingAmount' => [
                                        'sum' => [
                                            'field' => 'base_shipping_amount',
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