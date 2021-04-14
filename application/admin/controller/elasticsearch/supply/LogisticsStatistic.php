<?php
/**
 * Class LogisticsStatistic.php
 * @author mjj
 * @date   2021/4/14 15:26:50
 */

namespace app\admin\controller\elasticsearch\supply;

use app\admin\controller\elasticsearch\BaseElasticsearch;

class LogisticsStatistic extends BaseElasticsearch
{
    public function index()
    {
        $start = '20180205';

        echo date('Ymd160000');
        exit;
        echo date('Ymd160000', strtotime('-30 days'));
        exit;
        $end = '20210205';
        $dashBoardData = $this->getDashBoard(1, '20180205 20210205');
        return json(['code' => 1, 'data' => $dashBoardData]);
    }

    /**
     * 获取数据
     * @param        $site
     * @param string $time
     *
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function getDashBoard($site, $time = '')
    {
        //获取时间
        if ($time) {
            $timeRange = explode(' ', $time);
        } else {
            $timeRange = [
                date('Ymd', strtotime('-30 days')),
                date('Ymd')
            ];
        }
        //判断是否为全部站点
        $siteAll = $site == 4;
        $result = $this->buildTrackSearch($site, $timeRange[0], $timeRange[1], $siteAll);
        $a = $this->esFormatData->formatDashBoardData($site, $result, $siteAll);
        file_put_contents('./t.json', $a);

    }

    /**
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/13 15:02
     */
    public function buildTrackSearch($site, $start, $end, $siteAll = false)
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        $params = [
            'index' => 'mojing_track',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    'delivery_time' => [
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
            'activeUserNum' => [
                "sum" => [
                    'field' => 'active_user_num',
                ],
            ],
            'registerNum' => [
                'sum' => [
                    'field' => 'register_num',
                ],
            ],
            'vipUserNum' => [
                "sum" => [
                    'field' => 'vip_user_num',
                ],
            ],
            'orderNum' => [
                "sum" => [
                    'field' => 'order_num',
                ],
            ],
            'salesTotalMoney' => [
                "sum" => [
                    'field' => 'sales_total_money',
                ],
            ],
            'shippingTotalMoney' => [
                "sum" => [
                    'field' => 'shipping_total_money',
                ],
            ],
            'landingNum' => [
                "sum" => [
                    'field' => 'landing_num',
                ],
            ],
            'detailNum' => [
                "sum" => [
                    'field' => 'detail_num',
                ],
            ],
            'cartNum' => [
                "sum" => [
                    'field' => 'cart_num',
                ],
            ],
            'completeNum' => [
                "sum" => [
                    'field' => 'complete_num',
                ],
            ],
            'daySale' => [
                'terms' => [
                    "field" => 'day_date',
                    'order' => [
                        '_key' => 'asc',
                    ],
                ],
                'aggs' => [
                    'orderNum' => [
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
                    "aggs" => $aggs,
                ],
            ];
        }

        return $this->esService->search($params);
    }
}