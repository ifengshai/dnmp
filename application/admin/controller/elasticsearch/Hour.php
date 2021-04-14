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
        $start = '20180205';
        $end = '20210205';
        $pruchaseData = $this->dashBoard([1, 2, 3], $start, $end,false);
        $data = array_combine(array_column($pruchaseData['site']['buckets'],'key'),$pruchaseData['site']['buckets']);
        file_put_contents('./b.json', json_encode($pruchaseData));
        die;

        return $this->esFormatData->formatPurchaseData($pruchaseData);

    }

    /**
     * @param      $site
     * @param      $start
     * @param      $end
     * @param bool $siteAll
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/13 15:02
     */
    public function dashBoard($site, $start, $end, $siteAll = false)
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
            ],
        ];
        $aggs = [
            //总数聚合
            'activeUserNum'      => [
                "sum" => [
                    'field' => 'active_user_num',
                ],
            ],
            'registerNum'        => [
                'sum' => [
                    'field' => 'register_num',
                ],
            ],
            'vipUserNum'         => [
                "sum" => [
                    'field' => 'vip_user_num',
                ],
            ],
            'orderNum'           => [
                "sum" => [
                    'field' => 'order_num',
                ],
            ],
            'orderUnitPrice'     => [
                "sum" => [
                    'field' => 'order_unit_price',
                ],
            ],
            'salesTotalMoney'    => [
                "sum" => [
                    'field' => 'sales_total_money',
                ],
            ],
            'shippingTotalMoney' => [
                "sum" => [
                    'field' => 'shipping_total_money',
                ],
            ],
            'landingNum'         => [
                "sum" => [
                    'field' => 'landing_num',
                ],
            ],
            'detailNum'          => [
                "sum" => [
                    'field' => 'detail_num',
                ],
            ],
            'cartNum'            => [
                "sum" => [
                    'field' => 'cart_num',
                ],
            ],
            'completeNum'        => [
                "sum" => [
                    'field' => 'complete_num',
                ],
            ],
            'daySale'            => [
                'terms' => [
                    "field" => 'day_date',
                    'order' => [
                        '_key' => 'asc',
                    ],
                ],
                'aggs'  => [
                    'orderNum'      => [
                        'sum' => [
                            'field' => 'order_num',
                        ],
                    ],
                    'activeUserNum' => [
                        'sum' => [
                            'field' => 'active_user_num',
                        ],
                    ],
                ],
            ],
        ];
        $params['body']['aggs'] = $aggs;

        if (!$siteAll) {
            $params['body']['aggs'] = [
                'site' => [
                    "terms" => [
                        "field" => 'site',
                    ],
                    "aggs"  => $aggs,
                ],
            ];
        }

        return $this->esService->search($params);
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