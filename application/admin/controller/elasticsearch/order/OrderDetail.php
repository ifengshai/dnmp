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

class OrderDetail extends BaseElasticsearch
{
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

        return $this->view->fetch('operatedatacenter/orderdata/order_data_view/index');
    }
    /**
     * 获取数据
     *
     * @param        $site
     * @param string $time
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
            $compareData = [];
            if($compareTimeStr){
                $compareTime = explode(' ', $compareTimeStr);
                $compareStart = date('Ymd', strtotime($compareTime[0]));
                $compareEnd = date('Ymd', strtotime($compareTime[3]));
                $compareData = $this->buildPurchaseSearch($site, $compareStart, $compareEnd);
            }
            $result = $this->buildPurchaseSearch($site, $start, $end);
            $allData = $this->esFormatData->formatPurchaseData($site, $result, $compareData);
            switch ($type) {
                case 0:
                    $data = $allData['saleChart'];

                    return json(['code' => 1, 'data' => $data]);
                case 1:
                    $data = $allData['dayChart'];

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
                    'priceRanges'      => [
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
}