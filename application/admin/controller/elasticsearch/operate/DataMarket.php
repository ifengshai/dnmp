<?php
/**
 * Class DataMarket.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/16 11:33
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\enum\Site;

class DataMarket extends BaseElasticsearch
{
    public $site = [
        Site::ZEELOOL,
        Site::VOOGUEME,
        Site::NIHAO,
        Site::ZEELOOL_DE,
        Site::ZEELOOL_JP,
        Site::ZEELOOL_ES,
        Site::VOOGUEME_ACC
    ];
    public function ajaxGetCharts()
    {
        //if ($this->request->isAjax()) {

            $start = date('Ymd', strtotime('-6 days'));
            $end = date('Ymd');


            $result = $this->buildDataMarketEchartsSearch($this->site, $start, $end);

            echo json_encode($result);

        //}

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
                $start = date('Ymd', strtotime('-6 days'));
                $end = date('Ymd');
            } else {
                $timeStr = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($timeStr[0]));
                $end = date('Ymd', strtotime($timeStr[3]));
            }
            $status = [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered'
            ];
            $bottomData = $this->esFormatData->formatDataMarketBottom($this->buildDataMarketBottomSearch($this->site,$status,$start,$end));
            if (!$bottomData) {
                return $this->error('没有对应的时间数据，请重新尝试');
            }
            return $this->success('', '', $bottomData, 0);
        }


    }
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
                'aggs' => [
                    'site' => [
                            'terms' => [
                                'field' => 'site'
                            ],
                            'aggs' => [
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
                                        'avgPrice'           => [
                                            "sum" => [
                                                'field' => 'order_unit_price',
                                            ],
                                        ],
                                        'salesTotalMoney'    => [
                                            "sum" => [
                                                'field' => 'sales_total_money',
                                            ],
                                        ],

                                        'cartNum'            => [
                                            "sum" => [
                                                'field' => 'cart_num',
                                            ],
                                        ],
                                    ],
                                ],

                            ]
                        ]
                ]
            ],
        ];


        return $this->esService->search($params);
    }
    public function buildDataMarketBottomSearch($site, $status, $start, $end)
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
                                    'status' => $status
                                ],
                            ],
                        ],
                    ],
                ],
                "aggs"  => [
                    'site' => [
                        'terms' => [
                            'field' => 'site'
                        ],
                        'aggs' => [
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
                        ]
                    ]
                ],
            ],
        ];

        return $this->esService->search($params);
    }
}