<?php
/**
 * Class Hour.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/14 10:35
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;
use app\service\google\Session;
use think\Cache;

class Hour extends BaseElasticsearch
{
    /**
     * 分时销量首页
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/4/24 14:08
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
     * ajax获取销量数据
     * @return \think\response\Json
     * @throws \Google_Exception
     * @author crasphb
     * @date   2021/4/24 14:09
     */
    public function ajaxGetResult()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $type = $params['type'];
            $timeStr = $params['time_str'];
            $nowDate = date('Ymd') . '00';
            $today = false;
            if (!$timeStr) {
                $start = $end = $timeStr = $nowDate;
                $gaStart = $gaEnd = date('Y-m-d');
                $today = true;
            } else {
                $createat = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($createat[0])) . '00';
                $end = date('Ymd', strtotime($createat[3])) . '99';
                $gaStart = date('Y-m-d', strtotime($createat[0]));
                $gaEnd = date('Y-m-d', strtotime($createat[3]));
            }
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $time = $start . '-' . $end;
            $cacheStr = 'day_hour_order_quote_' . $site . $time;
            $cacheData = Cache::get($cacheStr);
            if (!$cacheData) {
                $hourOrderData = $this->buildHourOrderSearch($site, $start, $end);
                $hourCartData = $this->buildHourCartSearch($site, $start, $end);
                $sessionService = new Session($site);
                $gaData = $sessionService->gaHourData($gaStart, $gaEnd);

                $allData = $this->esFormatData->formatHourData($hourOrderData, $hourCartData, $gaData, $today);
                Cache::set($cacheStr, $allData, 600);
            } else {
                $allData = $cacheData;
            }

            switch ($type) {
                case 1:
                    $data = $allData['orderitemCounter'];

                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $allData['saleAmount'];

                    return json(['code' => 1, 'data' => $data]);
                case 3:
                    $data = $allData['orderCounter'];

                    return json(['code' => 1, 'data' => $data]);
                case 4:
                    $data = $allData['grandTotalOrderConversion'];

                    return json(['code' => 1, 'data' => $data]);
                default:
                    $str = '';
                    foreach ($allData['finalLists'] as $key => $val) {
                        $num = $key + 1;
                        $str .= '<tr><td>' . $num . '</td><td>' . $val['hour_created'] . '</td><td>' . $val['orderCounter'] . '</td><td>' . $val['totalQtyOrdered'] . '</td><td>' . round($val['daySalesAmount'], 2) . '</td><td>' . $val['avgPrice'] . '</td><td>' . $val['sessions'] . '</td><td>' . $val['addCartRate'] . '</td><td>' . $val['sessionRate'] . '</td><td>' . $val['cartCount'] . '</td><td>' . $val['cartRate'] . '</td></tr>';
                    }
                    $str .= '<tr><td>' . count($allData['finalLists']) . '</td><td>合计</td><td>' . $allData['allOrderCount'] . '</td><td>' . $allData['allQtyOrdered'] . '</td><td>' . $allData['allDaySalesAmount'] . '</td><td>' . $allData['allAvgPrice'] . '</td><td>' . $allData['allSession'] . '</td><td>' . $allData['addCartRate'] . '</td><td>' . $allData['sessionRate'] . '</td><td>' . $allData['allCartAmount'] . '</td><td>' . $allData['cartRate'] . '</td></tr>';
                    $data = compact('time_str', 'order_platform', 'str');
                    $this->success('', '', $data);
            }
        }
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
                            [
                                'terms' => [
                                    'status' => $this->status,
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