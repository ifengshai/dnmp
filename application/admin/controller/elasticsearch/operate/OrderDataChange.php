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

class OrderDataChange extends BaseElasticsearch
{

    /**
     * 数据大盘
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
        $this->view->assign(compact( 'magentoplatformarr'));

        return $this->view->fetch('operatedatacenter/orderdata/order_data_change/index');
    }
    /**
     * 获取数据
     *
     * @return \think\response\Json
     * @author crasphb
     * @date   2021/4/14 13:56
     */
    public function ajaxGetOrderDataChange()
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
            if ($compareTimeStr) {
                $compareTime = explode(' ', $compareTimeStr);
                $compareStart = date('Ymd', strtotime($compareTime[0]));
                $compareEnd = date('Ymd', strtotime($compareTime[3]));
                $compareData = $this->buildOrderChangeSearch($site, $compareStart, $compareEnd, $siteAll);
            }
            $result = $this->buildOrderChangeSearch($site, $start, $end, $siteAll);
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
    public function buildOrderChangeSearch($site, $start, $end)
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
            'sessions'      => [
                "sum" => [
                    'field' => 'sessions',
                ],
            ],
            'salesTotalMoney'    => [
                "sum" => [
                    'field' => 'sales_total_money',
                ],
            ],
            'orderNum'           => [
                "sum" => [
                    'field' => 'order_num',
                ],
            ],
            'newCartNum'         => [
                "sum" => [
                    'field' => 'new_cart_num',
                ],
            ],
            'updateCartNum'          => [
                "sum" => [
                    'field' => 'update_cart_num',
                ],
            ],
            'registerNum'        => [
                "sum" => [
                    'field' => 'register_num',
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
                    'orderNum'        => [
                        'sum' => [
                            'field' => 'order_num',
                        ],
                    ],
                    'sessions'   => [
                        'sum' => [
                            'field' => 'sessions',
                        ],
                    ],
                    'session_rate'   => [
                        'avg' => [
                            'field' => 'session_rate',
                        ],
                    ],
                    "cartNum"         => [
                        "sum" => [
                            "field" => "new_cart_num",
                        ],
                    ],
                    "order_unit_price"         => [
                        "avg" => [
                            "field" => "order_unit_price",
                        ],
                    ],
                    "salesTotalMoney" => [
                        "sum" => [
                            "field" => "sales_total_money",
                        ],
                    ],
                    "update_cart_num" => [
                        "sum" => [
                            "field" => "update_cart_num",
                        ],
                    ],
                    "addCartRate"     => [
                        "avg" => [
                            "field" => "add_cart_rate",
                        ],
                    ],
                    "registerNum"     => [
                        "sum" => [
                            "field" => "register_num",
                        ],
                    ],
                ],
            ],
        ];
        $params['body']['aggs'] = $aggs;
        return $this->esService->search($params);
    }
}