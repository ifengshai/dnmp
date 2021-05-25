<?php
/**
 * Class ConversionRate.php
 * @package app\admin\controller\elasticsearch\customer\order
 * @author  crasphb
 * @date    2021/5/8 17:25
 */

namespace app\admin\controller\elasticsearch\customer\order;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;
use app\service\elasticsearch\customer\OrderEsFormat;
use think\Cache;

class ConversionRate extends BaseElasticsearch
{
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
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao', 'zeelool_de', 'zeelool_jp', 'wesee'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('web_site', 'time_str', 'magentoplatformarr'));

        return $this->view->fetch();
    }

    /**
     * ajax获取信息
     * @return \think\response\Json
     * @author crasphb
     * @date   2021/5/14 9:18
     */
    public function ajaxGetConversionRate()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $timeStr = $params['time_str'];
            $type = $params['type'] ?? 3;
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if (!$timeStr) {
                $start = date('Ymd', strtotime('-6 days'));
                $end = date('Ymd');
            } else {
                $createat = explode(' ', $timeStr);
                $start = date('Ymd', strtotime($createat[0]));
                $end = date('Ymd', strtotime($createat[3]));
            }
            $cacheStr = 'conversion_rate' . $site . $timeStr;
            $cacheData = Cache::get($cacheStr);
            if (!$cacheData) {
                $result = $this->buildConversionRateSearch($site, $start, $end);
                $allData = $this->esFormat->formatConversionRateData($result);
                Cache::set($cacheStr, $allData, 600);
            } else {
                $allData = $cacheData;
            }
            switch ($type) {
                case 1:
                    $data = $allData['echartsSessionSale'];

                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $allData['echartsCartOrder'];

                    return json(['code' => 1, 'data' => $data]);
                default:
                    $this->success('', '', $allData);
            }
            $this->success('', '', $allData);
        }
    }

    /**
     * 图标查询
     *
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/21 11:41
     */
    public function buildConversionRateSearch($site, $start, $end)
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
                    //总数聚合
                    'allRegisterNum'     => [
                        'sum' => [
                            'field' => 'register_num',
                        ],
                    ],
                    'allLoginNum'        => [
                        'sum' => [
                            'field' => 'login_user_num',
                        ],
                    ],
                    'allAvgPrice'        => [
                        "avg" => [
                            'field' => 'order_unit_price',
                        ],
                    ],
                    'allSalesTotalMoney' => [
                        "sum" => [
                            'field' => 'sales_total_money',
                        ],
                    ],

                    'allAddToCartNum'  => [
                        "sum" => [
                            'field' => 'cart_num',
                        ],
                    ],
                    'allOrderNum'      => [
                        "sum" => [
                            'field' => 'order_num',
                        ],
                    ],
                    'allNewCartNum'    => [
                        "sum" => [
                            'field' => 'new_cart_num',
                        ],
                    ],
                    'allUpdateCartNum' => [
                        "sum" => [
                            'field' => 'update_cart_num',
                        ],
                    ],
                    'allSessions'      => [
                        "sum" => [
                            'field' => 'sessions',
                        ],
                    ],
                    //天聚合
                    "daySale"          => [
                        "terms" => [
                            "field" => 'day_date',
                            'size'  => '10000',
                            'order' => [
                                '_key' => 'asc',
                            ],
                        ],
                        "aggs"  => [
                            //总数聚合
                            'registerNum'     => [
                                'sum' => [
                                    'field' => 'register_num',
                                ],
                            ],
                            'LoginNum'        => [
                                'sum' => [
                                    'field' => 'login_user_num',
                                ],
                            ],
                            'sessions'        => [
                                'sum' => [
                                    'field' => 'sessions',
                                ],
                            ],
                            'avgPrice'        => [
                                "avg" => [
                                    'field' => 'order_unit_price',
                                ],
                            ],
                            'salesTotalMoney' => [
                                "sum" => [
                                    'field' => 'sales_total_money',
                                ],
                            ],

                            'addToCartNum'  => [
                                "sum" => [
                                    'field' => 'cart_num',
                                ],
                            ],
                            'orderNum'      => [
                                "sum" => [
                                    'field' => 'order_num',
                                ],
                            ],
                            'newCartNum'    => [
                                "sum" => [
                                    'field' => 'new_cart_num',
                                ],
                            ],
                            'updateCartNum' => [
                                "sum" => [
                                    'field' => 'update_cart_num',
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