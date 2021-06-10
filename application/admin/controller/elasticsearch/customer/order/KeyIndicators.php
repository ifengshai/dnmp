<?php
/**
 * Class KeyIndicators.php
 * @package app\admin\controller\elasticsearch\customer\order
 * @author  crasphb
 * @date    2021/5/8 15:07
 */

namespace app\admin\controller\elasticsearch\customer\order;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;
use app\service\elasticsearch\customer\OrderEsFormat;
use think\Cache;

/**
 * 订单仪表 -- 关键指标
 * Class KeyIndicators
 * @package app\admin\controller\elasticsearch\customer\order
 * @author  crasphb
 * @date    2021/5/8 15:08
 */
class KeyIndicators extends BaseElasticsearch
{
    protected $noNeedRight = ['*'];
    public $esFormat = null;

    public function _initialize()
    {
        $this->esFormat = new OrderEsFormat();

        return parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 列表页渲染
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/5/8 15:08
     */
    public function index()
    {
        $magentoplatformarr = new MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $magentoplatformarr->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'meeloog', 'zeelool_de', 'zeelool_jp', 'wesee','zeelool_fr'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact( 'magentoplatformarr'));

        return $this->view->fetch();
    }

    /**
     * 获取订单的数据
     * @author crasphb
     * @date   2021/5/8 15:52
     */
    public function ajaxGetPurchase()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
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
            $cacheStr = 'key_indicators_' . $site. '-' . $timeStr . $compareTimeStr;
            $cacheData = Cache::get($cacheStr);
            if (!$cacheData) {
                $compareData = [];
                if ($compareTimeStr) {
                    $compareTime = explode(' ', $compareTimeStr);
                    $compareStart = date('Ymd', strtotime($compareTime[0]));
                    $compareEnd = date('Ymd', strtotime($compareTime[3]));
                    $compareData = $this->buildPurchaseSearch($site, $compareStart, $compareEnd);
                }
                $result = $this->buildPurchaseSearch($site, $start, $end);
                $allData = $this->esFormat->formatPurchaseData($site, $result, $compareData);
                Cache::set($cacheStr, $allData, 600);
            } else {
                $allData = $cacheData;
            }
            $this->success('', '', $allData);
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
                    //订单类型聚合
                    "store"             => [
                        "terms" => [
                            "field" => 'store_id',
                        ],
                        'aggs'  => [
                            'daySalesAmount' => [
                                "sum" => [
                                    'field' => 'base_grand_total',
                                ],
                            ],
                            'avgPrice'       => [
                                "avg" => [
                                    'field' => 'base_grand_total',
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
                            "buckets_path" => "store>_count",
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }

    /**
     * ajax获取图标数据
     * @author crasphb
     * @date   2021/5/8 16:04
     */
    public function ajaxGetEcharts()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $timeStr = $params['time_str'];
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if (!$timeStr) {
                $start = date('Ymd', strtotime('-6 days'));
                $end = date('Ymd');
            } else {
                $createat = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($createat[0]));
                $end = date('Ymd', strtotime($createat[3]));
            }
            $cacheStr = 'key_indicators_user_' . $site . $timeStr;
            $cacheData = Cache::get($cacheStr);
            if (!$cacheData) {
                $result = $this->buildActiveOrderSearch($site, $start, $end);
                $allData = $this->esFormat->formatActiveOrderData($result);
                Cache::set($cacheStr, $allData, 600);
            } else {
                $allData = $cacheData;
            }

            return json(['code' => 1, 'data' => $allData['echarts']]);
        }
    }

    /**
     * 获取订单活跃用户趋势
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/13 15:02
     */
    public function buildActiveOrderSearch($site, $start, $end)
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
                'aggs'  => [
                    'daySale' => [
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
                ],
            ],
        ];


        return $this->esService->search($params);
    }


}