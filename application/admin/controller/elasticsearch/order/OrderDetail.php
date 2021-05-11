<?php
/**
 * Class OrderDetail.php
 * @package app\admin\controller\elasticsearch\order
 * @author  crasphb
 * @date    2021/4/16 14:20
 */

namespace app\admin\controller\elasticsearch\order;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;
use think\Cache;
use think\Db;

class OrderDetail extends BaseElasticsearch
{
    /**
     * 数据获取首页
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/4/24 14:10
     */
    public function index()
    {
        $magentoplatformarr = new MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $magentoplatformarr->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao', 'zeelool_de', 'zeelool_jp'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('web_site', 'time_str', 'magentoplatformarr'));

        return $this->view->fetch();
    }

    /**
     * 获取数据
     *
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function ajaxGetPurchase()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $type = $params['type'];
            $timeStr = $params['time_str'];
            $compareTimeStr = $params['compare_time_str'];
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if (!$timeStr) {
                $start = date('Ymd', strtotime('-6 days'));
                $end = date('Ymd');
            } else {
                $createat = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($createat[0]));
                $end = date('Ymd', strtotime($createat[3]));
            }
            $cacheStr = 'dash_board_' . $site . $timeStr . $compareTimeStr;
            $cacheData = Cache::get($cacheStr);
            if(!$cacheData) {
                $compareData = [];
                if ($compareTimeStr) {
                    $compareTime = explode(' ', $compareTimeStr);
                    $compareStart = date('Ymd', strtotime($compareTime[0]));
                    $compareEnd = date('Ymd', strtotime($compareTime[3]));
                    $compareData = $this->buildPurchaseSearch($site, $compareStart, $compareEnd);
                }
                $result = $this->buildPurchaseSearch($site, $start, $end);
                $allData = $this->esFormatData->formatPurchaseData($site, $result, $compareData);
                Cache::set($cacheStr, $allData, 600);
            }else{
                $allData = $cacheData;
            }

            switch ($type) {
                case 0:
                    $data = $allData['daySalesAmountEcharts'];

                    return json(['code' => 1, 'data' => $data]);
                case 1:
                    $data = $allData['dayOrderNumEcharts'];

                    return json(['code' => 1, 'data' => $data]);
                default:
                    $this->success('', '', $allData);
            }
        }

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
    public function buildPurchaseSearch($site, $start, $end)
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
                            ]
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
                    'allShippingAmount' => [
                        'sum' => [
                            'field' => 'base_shipping_amount',
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
                    'priceRanges'       => [
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
                            "daySalesAmount" => [
                                "sum" => [
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
                            "field" => 'shipping_method_type',
                        ],
                        'aggs'  => [
                            'allShippingAmount' => [
                                'sum' => [
                                    'field' => 'base_shipping_amount',
                                ],
                            ],
                        ],
                    ],
                    "sumOrder"          => [
                        "sum_bucket" => [
                            "buckets_path" => "daySale>_count",
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/5/11 13:04
     */
    public function ajaxGetPurchaseAna()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            //查询时间段内每天的客单价,中位数，标准差
            $timeStr = $params['time_str'];
            if (!$timeStr) {
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $timeStr = $start . ' - ' . $end;
            }
            $createat = explode(' ', $timeStr);
            $where['day_date'] = ['between', [$createat[0], $createat[3] . ' 23:59:59']];
            $where['site'] = $order_platform;
            $orderInfo = Db::name('datacenter_day')
                ->where($where)
                ->field('day_date,order_unit_price,order_total_midnum,order_total_standard')
                ->select();
            $orderInfo = collection($orderInfo)->toArray();
            $json['xColumnName'] = array_column($orderInfo, 'day_date') ? array_column($orderInfo, 'day_date') : [];
            $json['columnData'] = [
                [
                    'type'     => 'bar',
                    'barWidth' => '20%',
                    'data'     => array_column($orderInfo, 'order_unit_price') ? array_column($orderInfo, 'order_unit_price') : [],
                    'name'     => '客单价',
                ],
                [
                    'type'     => 'bar',
                    'barWidth' => '20%',
                    'data'     => array_column($orderInfo, 'order_total_midnum') ? array_column($orderInfo, 'order_total_midnum') : [],
                    'name'     => '中位数',
                ],
                [
                    'type'       => 'line',
                    'yAxisIndex' => 1,
                    'data'       => array_column($orderInfo, 'order_total_standard') ? array_column($orderInfo, 'order_total_standard') : [],
                    'name'       => '标准差',
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}