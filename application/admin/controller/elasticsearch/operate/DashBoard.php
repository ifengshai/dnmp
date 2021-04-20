<?php
/**
 * Class DashBoard.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/13 16:09
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;

class DashBoard extends BaseElasticsearch
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

        return $this->view->fetch('operatedatacenter/dataview/dash_board/index');
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
    public function ajaxGetDashBoard()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $type = $params['type'];
            $timeStr = $params['time_str'];
            $compareTimeStr = $params['compare_time_str'];
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $siteAll = $site == 4;
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
                $compareData = $this->buildDashBoardSearch($site, $compareStart, $compareEnd, $siteAll);
            }
            $result = $this->buildDashBoardSearch($site, $start, $end, $siteAll);
            $allData = $this->esFormatData->formatDashBoardData($site, $result, $compareData, $siteAll);
            switch ($type) {
                case 1:
                    $data = $allData['dayChart'];

                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $allData['funnel'];

                    return json(['code' => 1, 'data' => $data]);
                default:
                    $this->success('', '', $allData);
            }
        }

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
    public function buildDashBoardSearch($site, $start, $end, $siteAll = false)
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
                    "cartNum"  => [
                        "sum" => [
                            "field" => "new_cart_num",
                        ],
                    ],
                    "salesTotalMoney" => [
                        "sum" => [
                            "field" => "sales_total_money",
                        ],
                    ],
                    "avgPrice"        => [
                        "avg" => [
                            "field" => "order_unit_price",
                        ],
                    ],
                    "addCartRate"        => [
                        "avg" => [
                            "field" => "cart_rate",
                        ],
                    ],
                    "registerNum"        => [
                        "avg" => [
                            "field" => "register_num",
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
            //删除site查询
            unset($params['body']['query']['bool']['must'][1]);
        }

        return $this->esService->search($params);
    }
}