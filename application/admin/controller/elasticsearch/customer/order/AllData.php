<?php
/**
 * Class AllData.php
 * @package app\admin\controller\elasticsearch\customer\order
 * @author  crasphb
 * @date    2021/5/14 10:22
 */

namespace app\admin\controller\elasticsearch\customer\order;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\operatedatacenter\Datacenter;
use app\admin\model\operatedatacenter\Nihao;
use app\admin\model\operatedatacenter\Voogueme;
use app\admin\model\operatedatacenter\Weseeoptical;
use app\admin\model\operatedatacenter\Zeelool;
use app\admin\model\operatedatacenter\ZeeloolDe;
use app\admin\model\operatedatacenter\ZeeloolJp;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\web\WebUsers;
use app\enum\Site;
use app\service\elasticsearch\customer\OrderEsFormat;
use think\Cache;
use think\Db;

class AllData extends BaseElasticsearch
{
    public $esFormat = null;

    public function _initialize()
    {
        $this->esFormat = new OrderEsFormat();

        return parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function ajaxGetCustomerOrderData()
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
                     $compareData = $this->buildDashBoardSearch($site, $compareStart, $compareEnd);
                 }

                 $result = $this->buildDashBoardSearch($site, $start, $end);
                 $allData = $this->esFormat->getAllOrderDataFormatData($result, $compareData);
                 Cache::set($cacheStr, $allData, 600);
             } else {
                 $allData = $cacheData;
             }
            switch ($type) {
                case 1:
                    $data = $allData['echartsUser'];

                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $allData['echartsOrderSale'];

                    return json(['code' => 1, 'data' => $data]);
                case 3:
                    return json(['code' => 1, 'data' => $allData]);
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
    public function buildDashBoardSearch($site, $start, $end)
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
                    'activeUserNum'   => [
                        "sum" => [
                            'field' => 'active_user_num',
                        ],
                    ],
                    'registerNum'     => [
                        'sum' => [
                            'field' => 'register_num',
                        ],
                    ],
                    'orderNum'        => [
                        "sum" => [
                            'field' => 'order_num',
                        ],
                    ],
                    'salesTotalMoney' => [
                        "sum" => [
                            'field' => 'sales_total_money',
                        ],
                    ],
                    'daySale'         => [
                        'terms' => [
                            "field" => 'day_date',
                            'order' => [
                                '_key' => 'asc',
                            ],
                            'size'  => '1000',
                        ],
                        'aggs'  => [
                            'orderNum'        => [
                                'sum' => [
                                    'field' => 'order_num',
                                ],
                            ],
                            'activeUserNum'   => [
                                'sum' => [
                                    'field' => 'active_user_num',
                                ],
                            ],
                            "salesTotalMoney" => [
                                "sum" => [
                                    "field" => "sales_total_money",
                                ],
                            ],
                            "sessions"        => [
                                "avg" => [
                                    "field" => "sessions",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->esService->search($params);
    }
    /**
     * 复购用户数
     * @author crasphb
     * @date   2021/5/11 10:55
     */
    public function getReBuyNum()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $timeStr = $params['time_str'];
            $compareTimeStr = $params['compare_time_str'] ?: '';
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if (!$timeStr) {
                $nowDay = date('Y-m-d', strtotime('-6 days')) . ' ' . '00:00:00' . ' - ' . date('Y-m-d'). ' ' . '23:59:59';
            }
            switch ($site) {
                case Site::ZEELOOL:
                    $model = new Zeelool();
                    break;
                case Site::VOOGUEME:
                    $model = new Voogueme();
                    break;
                case Site::NIHAO:
                    $model = new Nihao();
                    break;
                case Site::ZEELOOL_DE:
                    $model = new ZeeloolDe();
                    break;
                case Site::ZEELOOL_JP:
                    $model = new ZeeloolJp();
                    break;
                case Site::WESEEOPTICAL:
                    $model = new Weseeoptical();
                    break;
            }
            //时间
            $timeStr = $timeStr ? $timeStr : $nowDay;
            $cacheStr = 'alldata_dash_board_rebuy_' . $site . $timeStr . $compareTimeStr;
            $cacheData = Cache::get($cacheStr);
            if(!$cacheData) {
                //复购用户数
                $againUserNum = $model->getAgainUser($timeStr, $compareTimeStr);
                Cache::set($cacheStr,$againUserNum,600);
            }else{
                $againUserNum = $cacheData;
            }

            $this->success('', '', $againUserNum);
        }
    }

    public function getCustomer()
    {
        echo date('Y-m-d H:i:s').PHP_EOL;
        echo date('Y-m-d H:i:s',time() - 8*3600);DIE;
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $timeStr = $params['time_str'];
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if (!$timeStr) {
                $nowDay = date('Y-m-d') . ' ' . '00:00:00' . ' - ' . date('Y-m-d'). ' ' . '23:59:59';
            }
            switch ($site) {
                case Site::ZEELOOL:
                    $model = new Zeelool();
                    break;
                case Site::VOOGUEME:
                    $model = new Voogueme();
                    break;
                case Site::NIHAO:
                    $model = new Nihao();
                    break;
                case Site::ZEELOOL_DE:
                    $model = new ZeeloolDe();
                    break;
                case Site::ZEELOOL_JP:
                    $model = new ZeeloolJp();
                    break;
                case Site::WESEEOPTICAL:
                    $model = new Weseeoptical();
                    break;
            }
            //时间
            $timeStr = $timeStr ? $timeStr : $nowDay;
            $cacheStr = 'dash_board_alldata_' . $site . $timeStr;
            $cacheData = Cache::get($cacheStr);
            if(!$cacheData) {
                //用户数据
                $userNum = $model->getUserOrderData($timeStr);
                Cache::set($cacheStr,$userNum,600);
            }else{
                $userNum = $cacheData;
            }
            $str = '';
            foreach($userNum as $key => $val){
                    $str .= '<tr><td>' . $val['name'] . '</td><td>' . $val['count'] . '</td><td>' . $val['customerRate'] . '</td><td>' . $val['num'] . '</td><td>' . $val['rate'] . '</td></tr>';
            }
            $this->success('', '', $str);
        }
    }
}