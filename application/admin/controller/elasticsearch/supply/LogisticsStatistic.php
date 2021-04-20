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
        $start = strtotime('2020-01-02');
        $end = strtotime('2020-04-15 23:59:59');
        $trackData = $this->getTrack(1,$start.' '.$end);
        return json(['code' => 1, 'data' => $trackData]);
    }

    /**
     * 获取数据
     * @param        $site
     * @param string $time
     *
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function getTrack($site, $time = '')
    {
        //获取时间
        if ($time) {
            $timeRange = explode(' ', $time);
        } else {
            $timeRange = [
                strtotime('-200 days'),
                time()
            ];
        }
        $siteAll = $site == 99;
        //判断是否为全部站点
        $trackResult = $this->buildTrackSearch($site, $timeRange[0], $timeRange[1],$siteAll);
        $trackDelievedResult = $this->buildTrackDelievedSearch($site, $timeRange[0], $timeRange[1],$siteAll);
        //$data = $this->esFormatData->formatTrackData($site, $trackResult,$trackDelievedResult, $siteAll);
        $trackResult = json_encode($trackResult);
        file_put_contents('./t.json', $trackResult);
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
    public function buildTrackSearch($site, $start, $end,$siteAll = false)
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
            "track_channel" => [
                "terms" => [
                    "field"=>'shipment_data_type'
                ],
            ],
        ];
        $params['body']['aggs'] = $aggs;
        if ($siteAll) {
            //删除site查询
            unset($params['body']['query']['bool']['must'][1]);
        }
        return $this->esService->search($params);
    }
    public function buildTrackDelievedSearch($site, $start, $end,$siteAll = false)
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
                            [
                                'terms' => [
                                    'site' => $site,
                                ],
                            ],
                            [
                                'term' => [
                                    'node_type' => 40,
                                ],
                            ],
                        ],
                    ],
                ],

            ],
        ];
        $aggs = [
            "track_channel" => [
                "terms" => [
                    "field"=>'shipment_data_type'
                ],
                "aggs" => [
                    'delieveredDays' => [
                        'range' => [
                            'field'  => 'delievered_days',
                            'ranges' => [
                                ['from' => '0', 'to' => '6.99'],
                                ['from' => '7', 'to' => '9.99'],
                                ['from' => '10', 'to' => '13.99'],
                                ['from' => '14', 'to' => '19.99'],
                                ['from' => '20', 'to' => '5000000'],
                            ],
                        ],
                    ],
                    "sumWaitTime"=>[
                        'sum'=>[
                            'field'=>'wait_time'
                        ]
                    ]
                ],
            ],
        ];
        $params['body']['aggs'] = $aggs;
        if ($siteAll) {
            //删除site查询
            unset($params['body']['query']['bool']['must'][1]);
        }
        return $this->esService->search($params);
    }
}