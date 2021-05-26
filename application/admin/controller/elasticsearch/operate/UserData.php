<?php

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\operatedatacenter\Datacenter;
use app\admin\model\operatedatacenter\Nihao;
use app\admin\model\operatedatacenter\Voogueme;
use app\admin\model\operatedatacenter\Weseeoptical;
use app\admin\model\operatedatacenter\Zeelool;
use app\admin\model\operatedatacenter\ZeeloolDe;
use app\admin\model\operatedatacenter\ZeeloolJp;
use app\enum\Site;
use think\Cache;
use think\Db;

class UserData extends BaseElasticsearch
{


    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 15:02:03
     */
    public function index()
    {
        //查询对应平台权限
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform;
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getNewAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val, ['zeelool', 'voogueme', 'nihao', 'zeelool_de', 'zeelool_jp','wesee'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $data = compact(  'active_user_num', 'register_user_num', 'again_user_num',  'magentoplatformarr');
        $this->view->assign($data);
        return $this->view->fetch();
    }

    /**
     * ajax获取上半部分数据
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 13:42:57
     */
    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            //时间
            $timeStr = $params['time_str'];
            $compareTimeStr = $params['time_str2'];
            if (!$timeStr){
                $timeStr = date('Y-m-d', strtotime('-6 days')) . ' ' . '00:00:00' . ' - ' . date('Y-m-d');
            }
            $arr = $this->getReBuyNum($site, $timeStr, $compareTimeStr);

            $table=$this->getUserTypeNum($site, $timeStr);
            $countryStr="";
            foreach ($table as $tb=>$value){
                $countryStr .= '<tr><td>' . $value['userType'] . '</td><td>' . $value['userNumber'] . '</td><td>' . $value['userNumberRatio'] . '</td><td>' . $value['userSale'] . '</td><td>' . $value['userSaleRatio'] . '</td></tr>';
            }


            $type = $params['type'];
            $createat = explode(' ', $timeStr);
            $start = date('Ymd', strtotime($createat[0]));
            $end = date('Ymd', strtotime($createat[3]));
            //基础数据
            $commontData = $this->getUserDataEsSearch($site, $start, $end);

            //比较数据
            $compareData = [];
            if ($compareTimeStr) {  //对比时间
                $compareTime = explode(' ', $compareTimeStr);
                $compareStart = date('Ymd', strtotime($compareTime[0]));
                $compareEnd = date('Ymd', strtotime($compareTime[3]));
                $compareData = $this->getUserDataEsSearch($site, $compareStart, $compareEnd);
            }

            $result = $this->getDataFormatEs($commontData, $compareData, $start, $end, $site);
            $result['again_user_num']=$arr;//添加复购数据
            $result['countryStr']=$countryStr;
            switch ($type) {
                case 1:
                    $data = $result['active_trend'];
                    return json(['code' => 1, 'data' => $data]);
                case 2:
                    $data = $result['conversion_rate'];
                    return json(['code' => 1, 'data' => $data]);
                default:
                    $this->success('', '', $result);
            }
            $this->success('', '', $data);
        }
    }

    /**
     * 将ES的结果处理
     * Interface getDataFormatEs
     * @package app\admin\controller\elasticsearch\operate
     * @author fzg
     * @date   2021/5/13 9:16
     */
    public function getDataFormatEs($commontData, $compareData, $start, $end, $site)
    {
        //内部拼装数据  拼装图表数据  增加复购数据与用户类型数据
        //基础数据
        $result = [];//拼装数据返回
        if ($commontData) {
            $result['active_user_num'] = $commontData['activeUserNum']['value'];//活跃用户数
            $result['register_user_num'] = $commontData['registerNum']['value'];//注册用户数
            $result['again_user_num'] = $commontData[''];//复购用户数  -读取数据库数据
            //处理活跃用户趋势图
            $xcolumnData = [];
            $dateArr = [];
            $newArr = [];
            $activeArr = [];
            foreach ($commontData['daySale']['buckets'] as $bucketsK => $bucketsV) {

                array_push($xcolumnData, date('Y-m-d', strtotime($bucketsV['key'])));
                array_push($dateArr, $bucketsV['activeUserNum']['value']);

                array_push($newArr, $bucketsV['createUserChangeRate']['value'] ? round($bucketsV['createUserChangeRate']['value'], 2) : '无');
                array_push($activeArr, $bucketsV['updateUserChangeRate']['value'] ? round($bucketsV['updateUserChangeRate']['value'], 2) : "无");
            }
            if (empty($newArr)){
                array_push($newArr,  '无');
            }
            if (empty($activeArr)){
                array_push($activeArr,  '无');
            }
            $name = '活跃用户数';
            $json['xcolumnData'] = $xcolumnData;
            $json['column'] = [$name];
            $json['columnData'] = [
                [
                    'name'   => $name,
                    'type'   => 'line',
                    'smooth' => true,
                    'data'   => array_values($dateArr),
                ],

            ];
            $result['active_trend'] = $json;//活跃用户趋势图

            //新老用户转化率对比
            $conversionJson['xColumnName'] = $xcolumnData;
            $conversionJson['columnData'] = [
                [
                    'type'   => 'line',
                    'data'   => $newArr,
                    'name'   => '新用户',
                    'smooth' => true //平滑曲线
                ],
                [
                    'type'   => 'line',
                    'data'   => $activeArr,
                    'name'   => '活跃用户',
                    'smooth' => true //平滑曲线
                ],
            ];
            $result['conversion_rate'] = $conversionJson;//新老用户购买转化率对比
            //用户类型分布
        }

        if ($compareData) {
            $result['compare_active_user_num'] = $compareData['activeUserNum']['value'] ? round(($commontData['activeUserNum']['value'] - $compareData['activeUserNum']['value']) / $compareData['activeUserNum']['value'] * 100, 2) : '0';
            $result['compare_register_user_num'] = $compareData['registerNum']['value'] ? round(($commontData['registerNum']['value'] - $compareData['registerNum']['value']) / $compareData['registerNum']['value'] * 100, 2) : '0';
            $result['compare_again_user_num'] = $compareData['again_user_num']['value'] ? round(($commontData['again_user_num']['value'] - $compareData['again_user_num']['value']) / $compareData['again_user_num']['value'] * 100, 2) : '0';
        }

        return $result;

    }

    public function getUserTypeNum($site,$timeStr){
        if (!$timeStr) {
            $timeStr = date('Y-m-d', strtotime('-6 days')) . ' ' . '00:00:00' . ' - ' . date('Y-m-d');
        }
        $createat = explode(' ', $timeStr);
        $map_where['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];


        // 用户概述表格数据
        $map_where['o.order_type'] = 1;

        if ($site == 2) {
            $model = Db::connect('database.db_voogueme');
        } elseif ($site == 3) {
            $model = Db::connect('database.db_nihao');
        } elseif ($site == 5) {
            $model = Db::connect('database.db_weseeoptical');
        } elseif ($site == 10) {
            $model =  Db::connect('database.db_zeelool_de');
        } elseif ($site == 11) {
            $model =Db::connect('database.db_zeelool_jp');
        } else {
            $model =  Db::connect('database.db_zeelool');
        }

        if ($site==5){
            $customerWhere['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            $orderWhere['status'] = ['in', [2,3,4,9,10]];
            $orderWhere['order_type'] = 1;
            //用户统计
            $customerCount = Db::connect('database.db_weseeoptical')
                ->table('users')
                ->where('name','<>','visitor')
                ->where($customerWhere)
                ->count();
            $orderCount = Db::connect('database.db_weseeoptical')
                ->table('orders')
                ->where($customerWhere)
                ->where($orderWhere)
                ->sum('base_actual_amount_paid');
            $resultData=array();
            //拼装数据
            $resultData[0]['userType']="普通用户";//用户类型
            $resultData[0]['userNumber']=$customerCount;//用户数
            $resultData[0]['userNumberRatio']="100%";//用户数占比
            $resultData[0]['userSale']=$orderCount;//销售额
            $resultData[0]['userSaleRatio']="100%";//销售额占比

            return $resultData;
        }

        $customerWhere['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
        $orderWhere['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $orderWhere['order_type'] = 1;
        //用户统计
        $customer = $model
            ->table('customer_entity')
            ->field('count(*) as count,group_id')
            ->where($customerWhere)
            ->group('group_id')
            ->select();
        $customerCount = $model
            ->table('customer_entity')
            ->where($customerWhere)
            ->count();
        $order = $model
            ->table('sales_flat_order')
            ->field('customer_group_id,sum(base_grand_total) as total')
            ->group('customer_group_id')
            ->where('customer_group_id','>',0)
            ->where($customerWhere)
            ->where($orderWhere)
            ->select();
        $orderCount = $model
            ->table('sales_flat_order')
            ->where($customerWhere)
            ->where($orderWhere)
            ->sum('base_grand_total');
        $resultData = [];
        foreach($customer as $key => $val) {
            foreach($order as $k => $v){
                if($val['group_id'] == $v['customer_group_id']) {
                    switch($v['customer_group_id']) {
                        case 1:
                            $name = '普通用户';
                            break;
                        case 2:
                            $name = '批发用户';
                            break;
                        case 4:
                            $name = 'VIP';
                            break;
                        default:
                            $name = "其余非游客用户";
                    }
                    $resultData[$key] = [
                        'userNumber' => $val['count'],
                        'userType' => $name,
                        'userSale'  => round($v['total'],2),
                        'userSaleRatio' => bcmul(bcdiv($v['total'], $orderCount,4),100,2).'%',
                        'userNumberRatio' => bcmul(bcdiv($val['count'], $customerCount,4),100,2).'%',
                    ];
                }
            }
        }

        return $resultData;

    }

    public function getReBuyNum($site, $timeStr, $compareTimeStr)
    {
        if (!$timeStr) {
            $nowDay = date('Y-m-d', strtotime('-6 days')) . ' ' . '00:00:00' . ' - ' . date('Y-m-d');
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
        $cacheStr = 'dash_board_rebuy_'.$site.$timeStr.$compareTimeStr;
        $cacheData = Cache::get($cacheStr);
        if (!$cacheData) {
            //复购用户数
            $againUserNum = $model->getAgainUser($timeStr, $compareTimeStr);
            Cache::set($cacheStr, $againUserNum, 600);
        } else {
            $againUserNum = $cacheData;
        }
        return $againUserNum;

    }


    /**
     * 根据站点和时间范围查询数据
     * Interface getUserDataEsSearch
     * @package app\admin\controller\operatedatacenter\userdata
     * @author fzg
     * @date   2021/5/12 14:43
     */
    public function getUserDataEsSearch($site, $start, $end)
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
                    'size'  => '10000',
                ],
                'aggs'  => [
                    'orderNum'             => [
                        'sum' => [
                            'field' => 'order_num',
                        ],
                    ],
                    'activeUserNum'        => [
                        'sum' => [
                            'field' => 'active_user_num',
                        ],
                    ],
                    "cartNum"              => [
                        "sum" => [
                            "field" => "new_cart_num",
                        ],
                    ],
                    "createUserChangeRate" => [
                        "sum" => [
                            "field" => "create_user_change_rate",
                        ],
                    ],
                    "updateUserChangeRate" => [
                        "sum" => [
                            "field" => "update_user_change_rate",
                        ],
                    ],
                    "salesTotalMoney"      => [
                        "sum" => [
                            "field" => "sales_total_money",
                        ],
                    ],
                    "avgPrice"             => [
                        "avg" => [
                            "field" => "order_unit_price",
                        ],
                    ],
                    "addCartRate"          => [
                        "avg" => [
                            "field" => "cart_rate",
                        ],
                    ],
                    "registerNum"          => [
                        "avg" => [
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
