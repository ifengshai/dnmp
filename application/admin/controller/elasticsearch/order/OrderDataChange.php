<?php
/**
 * Class DashBoard.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/13 16:09
 */

namespace app\admin\controller\elasticsearch\order;

use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\platformmanage\MagentoPlatform;
use think\Db;

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
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $pages = array(
                'order'=>$order,
                'offset'=>$offset,
                'limit'=>$limit,
            );
            $data = $this->getData($filter,$pages);
            //dump($data);exit;
            $list = $data['data']['list'];
            $total = $data['count'];

            //合计
            $sessions = $data['sum']['sessions'];
            $order_num = $data['sum']['orderNum'];
            $new_cart_num = $data['sum']['cartNum'];
            $update_cart_num = $data['sum']['updateCartNum'];
            $sales_total_money = round($data['sum']['salesTotalMoney'],2);
            $register_num = $data['sum']['registerNum'];
            $add_cart_rate = $sessions ? round($new_cart_num / $sessions * 100, 2) : 0;
            $session_rate = $sessions ? round($order_num / $sessions * 100, 2) : 0;
            $order_unit_price = $order_num ? round($sales_total_money / $order_num, 2) : 0;

            $data =array(
                'sessions'=>$sessions,
                'add_cart_rate'=>$add_cart_rate,
                'session_rate'=>$session_rate,
                'order_num'=>$order_num,
                'order_unit_price'=>$order_unit_price,
                'new_cart_num'=>$new_cart_num,
                'update_cart_num'=>$update_cart_num,
                'sales_total_money'=>$sales_total_money,
                'register_num'=>$register_num,
            );
            $result = array("total" => $total, "rows" => $list,'data'=>$data);

            return json($result);
        }
        //查询对应平台权限
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao','zeelool_de','zeelool_jp'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch('operatedatacenter/orderdata/order_data_change/index');
    }

    /**
     * 获取数据
     * @param $params：包含站点和所选时间
     * @param $pages:分页数据
     * @return array  返回数组
     * @author mjj
     * @date   2021/5/6 16:59:19
     */
    public function getData($params,$pages= [])
    {
        $timeStr = $params['time_str'];
        $site = $params['order_platform'] ? $params['order_platform'] : 1;
        $siteAll = true;
        if (!$timeStr) {
            $start = date('Ymd', strtotime('-6 days'));
            $end = date('Ymd');
        } else {
            $createat = explode(' ', $timeStr);
            $start = date('Ymd', strtotime($createat[0]));
            $end = date('Ymd', strtotime($createat[3]));
        }
        $compareData = [];
        $sum = $this->buildOrderChangeSearch($site, $start, $end);  //总数
        $sumAllData = $this->esFormatData->formatDashBoardData($site, $sum['data'], $compareData, $siteAll);
        $result = $this->buildOrderChangeSearchPage($site, $start, $end,$pages);  //分页数据
        $allData = $this->esFormatData->formatDashBoardData($site, $result, $compareData, $siteAll);
        $data = array(
            'count'=>$sum['count'],
            'sum'=>$sumAllData,
            'data'=>$allData,
        );
        return $data;
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
            $arr['time_str'] = $params['time_str'];
            $arr['order_platform'] = $params['order_platform'] ? $params['order_platform'] : 1;
            //获取数据
            $allData = $this->getData($arr);
            $allData = $allData['data'];
            switch ($type) {
                case 1:
                    $data = $allData['dayChart1'];
                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $allData['dayChart2'];
                    return json(['code' => 1, 'data' => $data]);
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
            'orderNum'           => [
                "sum" => [
                    'field' => 'order_num',
                ],
            ],
            'cartNum'         => [
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
        ];
        $params['body']['aggs'] = $aggs;

        $data = $this->esClient->search($params);
        $results = array(
            'count'=>$data['hits']['total']['value'],
            'data'=>$data['aggregations']
        );
        return $results;
    }

    public function buildOrderChangeSearchPage($site, $start, $end,$pages= [])
    {
        if (!is_array($site)) {
            $site = [$site];
        }
        if(empty($pages)){
            $pages = array(
                'order'=>'asc',
                'offset'=>0,
                'limit'=>1000,
            );
        }
        $params = [
            'index' => 'mojing_datacenterday',
            'body'  => [
                'size'=>$pages['limit'],
                'from'=>$pages['offset'],
                "sort"=>[
                    [
                        "day_date"=>$pages['order']
                    ]
                ],
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
        //echo json_encode($this->esClient->search($params));die;
        return $this->esService->search($params);
    }
}