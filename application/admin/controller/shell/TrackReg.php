<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;

class TrackReg extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';


    public function _initialize()
    {
        parent::_initialize();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
    }

    public function site_reg()
    {
        $this->reg_shipment('database.db_zeelool', 1);
        $this->reg_shipment('database.db_voogueme', 2);
        $this->reg_shipment('database.db_nihao', 3);
        $this->reg_shipment('database.db_meeloog', 4);
    }

    /**
     * 批量 注册物流
     * 每天跑一次，查找遗漏注册的物流单号，进行注册操作
     */
    public function reg_shipment($site_str, $site_type)
    {
        $order_shipment = Db::connect($site_str)
            ->table('sales_flat_shipment_track')->alias('a')
            ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
            ->field('a.entity_id,a.order_id,a.track_number,a.title,a.updated_at,a.created_at,b.increment_id')
            ->where('a.created_at', '>=', '2020-03-31 00:00:00')
            ->where('a.handle', '=', '0')
            ->group('a.order_id')
            ->select();
        foreach ($order_shipment as $k => $v) {
            $title = strtolower(str_replace(' ', '-', $v['title']));
            //区分usps运营商
            if (strtolower($title) == 'usps') {
                $track_num1 = substr($v['track_number'], 0, 4);
                if ($track_num1 == '9200' || $track_num1 == '9205') {
                    //郭伟峰
                    $shipment_data_type = 'USPS_1';
                } else {
                    $track_num2 = substr($v['track_number'], 0, 4);
                    if ($track_num2 == '9400') {
                        //加诺
                        $shipment_data_type = 'USPS_2';
                    } else {
                        //杜明明
                        $shipment_data_type = 'USPS_3';
                    }
                }
            } else {
                $shipment_data_type = $title;
            }
            $carrier = $this->getCarrier($title);
            $shipment_reg[$k]['number'] =  $v['track_number'];
            $shipment_reg[$k]['carrier'] =  $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] =  $v['order_id'];


            $list[$k]['order_node'] = 2;
            $list[$k]['node_type'] = 7; //出库
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = $site_type;
            $list[$k]['order_id'] = $v['order_id'];
            $list[$k]['order_number'] = $v['increment_id'];
            $list[$k]['shipment_type'] = $v['title'];
            $list[$k]['shipment_data_type'] = $shipment_data_type;
            $list[$k]['track_number'] = $v['track_number'];
            $list[$k]['content'] = 'Leave warehouse, Waiting for being picked up.';

            $data['order_node'] = 2;
            $data['node_type'] = 7;
            $data['update_time'] = $v['created_at'];
            $data['shipment_type'] = $v['title'];
            $data['shipment_data_type'] = $shipment_data_type;
            $data['track_number'] = $v['track_number'];
            $data['delivery_time'] = $v['created_at'];
            Db::name('order_node')->where(['order_id' => $v['order_id'], 'site' => $site_type])->update($data);
        }
        if ($list) {
            $this->ordernodedetail->saveAll($list);
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        $order_ids = array();
        foreach ($order_group as $key => $val) {
            $aa = $trackingConnector->registerMulti($val);

            //请求接口更改物流表状态
            $order_ids = implode(',', array_column($val, 'order_id'));
            $params['ids'] = $order_ids;
            $params['site'] = $site_type;
            $res = $this->setLogisticsStatus($params);
            if ($res->status !== 200) {
                echo $site_str . '更新失败:' . $order_ids . "\n";
            }
            $order_ids = array();

            usleep(500000);
        }
        echo $site_str . ' is ok' . "\n";
    }

    /**
     * 获取快递号
     * @param $title
     * @return mixed|string
     */
    public function getCarrier($title)
    {
        $carrierId = '';
        if (stripos($title, 'post') !== false) {
            $carrierId = 'chinapost';
            $title = 'China Post';
        } elseif (stripos($title, 'ems') !== false) {
            $carrierId = 'chinaems';
            $title = 'China Ems';
        } elseif (stripos($title, 'dhl') !== false) {
            $carrierId = 'dhl';
            $title = 'DHL';
        } elseif (stripos($title, 'fede') !== false) {
            $carrierId = 'fedex';
            $title = 'Fedex';
        } elseif (stripos($title, 'usps') !== false) {
            $carrierId = 'usps';
            $title = 'Usps';
        } elseif (stripos($title, 'yanwen') !== false) {
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        } elseif (stripos($title, 'cpc') !== false) {
            $carrierId = 'cpc';
            $title = 'Canada Post';
        }
        $carrier = [
            'dhl' => '100001',
            'chinapost' => '03011',
            'chinaems' => '03013',
            'cpc' =>  '03041',
            'fedex' => '100003',
            'usps' => '21051',
            'yanwen' => '190012'
        ];
        if ($carrierId) {
            return ['title' => $title, 'carrierId' => $carrier[$carrierId]];
        }
        return ['title' => $title, 'carrierId' => $carrierId];
    }

    /**
     * 更新物流表状态 handle 改为1
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 18:16:48 
     * @return void
     */
    protected function setLogisticsStatus($params)
    {
        switch ($params['site']) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            case 4:
                $url = config('url.meeloog_url');
                break;
            default:
                return false;
                break;
        }

        if ($params['site'] == 4) {
            $url = $url . 'rest/mj/update_order_handle';
        } else {
            $url = $url . 'magic/order/logistics';
        }
        unset($params['site']);
        $client = new Client(['verify' => false]);
        //请求URL
        $response = $client->request('POST', $url, array('form_params' => $params));
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody);
        return $res;
    }
    /**
     * zendesk10分钟更新前20分钟的数据
     * @return [type] [description]
     */
    public function zeelool_zendesk()
    {
        $this->zendeskUpateData('zeelool', 1);
        echo 'all ok';
        exit;
    }
    public function voogueme_zendesk()
    {
        $this->zendeskUpateData('voogueme', 2);
        echo 'all ok';
        exit;
    }
    public function nihao_zendesk()
    {
        $this->zendeskUpateData('nihaooptical', 3);
        echo 'all ok';
        exit;
    }
    /**
     * zendesk10分钟更新前20分钟的数据方法
     * @return [type] [description]
     */
    public function zendeskUpateData($siteType, $type)
    {
        // file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.log', 'starttime:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);

        $this->model = new \app\admin\model\zendesk\Zendesk;
        $ticketIds = (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType]))->autoAsyncUpdate($siteType);

        //判断是否存在
        $nowTicketsIds = $this->model->where("type", $type)->column('ticket_id');

        //求交集的更新
        $intersects = array_intersect($ticketIds, $nowTicketsIds);
        //求差集新增
        $diffs = array_diff($ticketIds, $nowTicketsIds);
        //更新
        foreach ($intersects as $intersect) {
            (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $intersect]))->auto_update();
            echo $intersect . 'is ok' . "\n";
        }
        //新增
        foreach ($diffs as $diff) {
            (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $diff]))->auto_create();
            echo $diff . 'ok' . "\n";
        }
        echo 'all ok';
        // file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.log', 'endtime:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
        exit;
    }

    /**
     * 获取前一天有效SKU销量
     * 记录当天有效SKU
     *
     * @Description
     * @author wpl
     * @since 2020/07/31 16:52:46 
     * @return void
     */
    public function get_sku_sales_num()
    {
        //记录当天上架的SKU 
        $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $order = new \app\admin\model\order\order\Order();
        $list = $itemPlatformSku->field('sku,platform_sku,platform_type as site')->where(['outer_sku_status' => 1])->select();
        $list = collection($list)->toArray();
        //批量插入当天各站点上架sku
        $skuSalesNum->saveAll($list);

        //查询昨天上架SKU 并统计当天销量
        $data = $skuSalesNum->whereTime('createtime', 'yesterday')->select();
        $data = collection($data)->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                $where['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-1 day")), date("Y-m-d 23:59:59", strtotime("-1 day"))]];
                if ($v['platform_sku']) {
                    $params[$k]['sales_num'] = $order->getSkuSalesNum($v['platform_sku'], $where, $v['site']);
                    $params[$k]['id'] = $v['id'];
                }
            }
            if ($params) {
                $skuSalesNum->saveAll($params);
            }
           
        }

        echo "ok";
    }

    /**
     * 统计有效天数日均销量 并按30天预估销量分级
     *
     * @Description
     * @author wpl
     * @since 2020/08/01 15:29:23 
     * @return void
     */
    public function get_days_sales_num()
    {
        $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $date = date('Y-m-d 00:00:00');
        $list = $itemPlatformSku->field('id,sku,platform_type as site')->where(['outer_sku_status' => 1])->select();
        $list = collection($list)->toArray();
        
        foreach ($list as $k => $v) {
            //15天日均销量
            $days15_data = $skuSalesNum->where(['sku' => $v['sku'], 'site' => $v['site'], 'createtime' => ['<', $date]])->field("sum(sales_num) as sales_num,count(*) as num")->limit(15)->order('createtime desc')->select();
            $params['sales_num_15days'] = $days15_data[0]->num > 0 ? round($days15_data[0]->sales_num / $days15_data[0]->num) : 0;

            $days90_data = $skuSalesNum->where(['sku' => $v['sku'], 'site' => $v['site'], 'createtime' => ['<', $date]])->field("sum(sales_num) as sales_num,count(*) as num")->limit(90)->order('createtime desc')->select();
            //90天总销量
            $params['sales_num_90days'] = $days90_data[0]->sales_num;
            //90天日均销量
            $sales_num_90days = $days90_data[0]->num > 0 ? round($days90_data[0]->sales_num / $days90_data[0]->num) : 0;
            //90天日均销量
            $params['average_90days_sales_num'] = $sales_num_90days;
            //计算等级 30天预估销量
            $num = round($sales_num_90days * 1 * 30);
            if ($num >= 300) {
                $params['grade'] = 'A+';
            } elseif ($num >= 150 && $num < 300) {
                $params['grade'] = 'A';
            } elseif ($num >= 90 && $num < 150) {
                $params['grade'] = 'B';
            } elseif ($num >= 60 && $num < 90) {
                $params['grade'] = 'C+';
            } elseif ($num >= 30 && $num < 60) {
                $params['grade'] = 'C';
            } elseif ($num >= 15 && $num < 30) {
                $params['grade'] = 'D';
            } elseif ($num >= 1 && $num < 15) {
                $params['grade'] = 'E';
            } else {
                $params['grade'] = 'F';
            }
            $itemPlatformSku->where('id', $v['id'])->update($params);
        }
       
        echo "ok";
    }


    /**
     * 计划任务 计划补货 每月7号执行一次 汇总各个平台原始sku相同的品的补货需求数量 加入补货需求单以供采购分配处理 汇总过后更新字段 is_show 的值 列表不显示
     * 2020.09.07 改为每月9号执行一次
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/16
     * Time: 15:46
     */
    public function plan_replenishment()
    {
        //补货需求清单表
        $this->model = new \app\admin\model\NewProductMapping();
        //补货需求单子表
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //补货需求单主表
        $this->replenish = new \app\admin\model\purchase\NewProductReplenish();
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");
        if (empty($list)) {
            echo ('暂时没有紧急补货单需要处理');
            die;
        }
        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $sku_list = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $sku_list = $this->array_group_by($sku_list, 'sku');

        //首先插入主表 获取主表id new_product_replenish
        $data['type'] = 1;
        $data['create_person'] = 'Admin';
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = $this->replenish->insertGetId($data);

        //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
        $int = 0;
        foreach ($sku_list as $k => $v) {
            //求出此sku在此补货单中的总数量
            $sku_whole_num = array_sum(array_map(function ($val) {
                return $val['replenish_num'];
            }, $v));
            //求出比例赋予新数组
            foreach ($v as $ko => $vo) {
                $date[$int]['id'] = $vo['id'];
                $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
                $date[$int]['replenish_id'] = $res;
                $int += 1;
            }
        }
        //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
        $res1 = $this->model->allowField(true)->saveAll($date);

        $number = 0;
        foreach ($list as $k => $v) {
            $arr[$number]['sku'] = $k;
            $arr[$number]['replenishment_num'] = $v;
            $arr[$number]['create_person'] = 'Admin';
            $arr[$number]['create_time'] = date('Y-m-d H:i:s');
            $arr[$number]['type'] = 1;
            $arr[$number]['replenish_id'] = $res;
            $number += 1;
        }
        //插入补货需求单子表 关联主表 new_product_replenish_order 关联字段replenish_id
        $result = $this->order->allowField(true)->saveAll($arr);
        //更新计划补货列表
        $ids = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->setField('is_show', 0);
    }

    /**
     * *@param  [type] $arr [二维数组]
     * @param  [type] $key [键名]
     * @return [type]      [新的二维数组]
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/22
     * Time: 11:37
     */
    function array_group_by($arr, $key)
    {
        $grouped = array();
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge($value, array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }
        return $grouped;
    }
    /**
     * 紧急补货  2020.09.07改为计划任务 周计划执行时间为每周三的24点，汇总各站提报的SKU及数量
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/17
     * Time: 9:22
     */
    public function emergency_replenishment()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //紧急补货分站点
        $platform_type = input('label');
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 2])
            // ->where(['is_show' => 1, 'type' => 2,'website_type'=>$platform_type]) //分站点统计补货需求 2020.9.4改为计划补货 不分站点

            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");

        if (empty($list)) {
            echo ('暂时没有紧急补货单需要处理');
            die;
        }

        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $sku_list = $this->model
            ->where(['is_show' => 1, 'type' => 2])

            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $sku_list = $this->array_group_by($sku_list, 'sku');

        $result = false;
            //首先插入主表 获取主表id new_product_replenish
            $data['type'] = 2;
            $data['create_person'] = 'Admin';
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = Db::name('new_product_replenish')->insertGetId($data);

            //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
            $int = 0;
            foreach ($sku_list as $k => $v) {
                //求出此sku在此补货单中的总数量
                $sku_whole_num = array_sum(array_map(function ($val) {
                    return $val['replenish_num'];
                }, $v));
                //求出比例赋予新数组
                foreach ($v as $ko => $vo) {
                    $date[$int]['id'] = $vo['id'];
                    $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
                    $date[$int]['replenish_id'] = $res;
                    $int += 1;
                }
            }
            //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
            $res1 = $this->model->allowField(true)->saveAll($date);

            $number = 0;
            foreach ($list as $k => $v) {
                $arr[$number]['sku'] = $k;
                $arr[$number]['replenishment_num'] = $v;
                $arr[$number]['create_person'] = 'Admin';
                // $arr[$number]['create_person'] = session('admin.nickname');
                $arr[$number]['create_time'] = date('Y-m-d H:i:s');
                $arr[$number]['type'] = 2;
                $arr[$number]['replenish_id'] = $res;
                $number += 1;
            }
            //插入补货需求单表
            $result = $this->order->allowField(true)->saveAll($arr);
            //更新计划补货列表
            $ids = $this->model
                ->where(['is_show' => 1, 'type' => 2])
                ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
                ->setField('is_show', 0);

    }
    //活跃用户数
    public function google_active_user($site,$start_time)
    {
        // dump();die;
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_active_user($site,$analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        return $result[0]['ga:1dayUsers'] ? round($result[0]['ga:1dayUsers'],2): 0;
    }
    protected function getReport_active_user($site,$analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if($site == 1){
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 2){
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 3){
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:1dayUsers");
        $adCostMetric->setAlias("ga:1dayUsers");
        // $adCostMetric->setExpression("ga:adCost");
        // $adCostMetric->setAlias("ga:adCost");

        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);

    }
    //session
    public function google_session($site,$start_time)
    {
        // dump();die;
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_session($site,$analytics, $start_time, $end_time);

        // dump($response);die;

        // Print the response.
        $result = $this->printResults($response);

        return $result[0]['ga:sessions'] ? round($result[0]['ga:sessions'],2): 0;
    }
    protected function getReport_session($site,$analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if($site == 1){
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 2){
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 3){
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:sessions");
        $adCostMetric->setAlias("ga:sessions");
        // $adCostMetric->setExpression("ga:adCost");
        // $adCostMetric->setAlias("ga:adCost");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);

    }
    /**
     * Parses and prints the Analytics Reporting API V4 response.
     *
     * @param An Analytics Reporting API V4 response.
     */
    protected function printResults($reports)
    {
        $finalResult = array();
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $finalResult[$rowIndex][$dimensionHeaders[$i]] = $dimensions[$i];
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $finalResult[$rowIndex][$entry->getName()] = $values[$k];
                    }
                }
            }
            return $finalResult;
        }
    }

    //运营数据中心
    public function zeelool_day_data()
    {
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $zeelool_model = Db::connect('database.db_zeelool_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");


        $date_time = date('Y-m-d',strtotime("-1 day"));

        //查询时间
        $arr = [];
        $arr['site'] = 1;
        $arr['day_date'] = $date_time;
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(1, $date_time);
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();
        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $zeelool_model->table('oc_vip_order')->where($vip_where)->count();
        //订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $arr['order_num'] = $this->zeelool->where($order_where)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->sum('base_shipping_amount');
        //购买人数
        $order_user = $this->zeelool->where($order_where)->count('distinct customer_id');
        //客单价
        // $arr['order_unit_price'] = $order_user ? round($arr['sales_total_money'] / $order_user, 2) : 0;

        $order_where1 = [];
        $order_where1[] = ['exp', Db::raw("customer_id is not null and customer_id != 0")];
        $order_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where1['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $sales_total_money1 = $this->zeelool->where($order_where1)->sum('base_grand_total');
        $order_user1 = $this->zeelool->where($order_where1)->count('distinct customer_id');
        //客单价
        $arr['order_unit_price'] = $order_user1 ? round($sales_total_money1 / $order_user1, 2) : 0;

        //会话
        $arr['sessions'] = $this->google_session(1, $date_time);
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->count();
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'], 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'], 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'], 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'], 2) : 0;
        //插入数据
        Db::name('datacenter_day')->insert($arr);
        echo $date_time . "\n";
        usleep(100000);

    }
    //运营数据中心
    public function voogueme_day_data()
    {
        $this->zeelool = new \app\admin\model\order\order\Voogueme();
        $zeelool_model = Db::connect('database.db_voogueme_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");


        $date_time = date('Y-m-d',strtotime("-1 day"));

        //查询时间
        $arr = [];
        $arr['site'] = 2;
        $arr['day_date'] = $date_time;
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(2, $date_time);
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();
        //新增vip用户数
        $vip_where = [];
        $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        $vip_where['order_status'] = 'Success';
        $arr['vip_user_num'] = $zeelool_model->table('oc_vip_order')->where($vip_where)->count();
        //订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $arr['order_num'] = $this->zeelool->where($order_where)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->sum('base_shipping_amount');
        //购买人数
        $order_user = $this->zeelool->where($order_where)->count('distinct customer_id');
        //客单价
        // $arr['order_unit_price'] = $order_user ? round($arr['sales_total_money'] / $order_user, 2) : 0;



        $order_where1 = [];
        $order_where1[] = ['exp', Db::raw("customer_id is not null and customer_id != 0")];
        $order_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where1['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $sales_total_money1 = $this->zeelool->where($order_where1)->sum('base_grand_total');
        $order_user1 = $this->zeelool->where($order_where1)->count('distinct customer_id');
        //客单价
        $arr['order_unit_price'] = $order_user1 ? round($sales_total_money1 / $order_user1, 2) : 0;



        //会话
        $arr['sessions'] = $this->google_session(2, $date_time);
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->count();
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'], 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'], 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'], 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'], 2) : 0;
        //插入数据
        Db::name('datacenter_day')->insert($arr);
        echo $date_time . "\n";
        usleep(100000);

    }
    //运营数据中心
    public function niaho_day_data()
    {
        $this->zeelool = new \app\admin\model\order\order\Nihao();
        $zeelool_model = Db::connect('database.db_nihao_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");


        $date_time = date('Y-m-d',strtotime("-1 day"));

        //查询时间
        $arr = [];
        $arr['site'] = 3;
        $arr['day_date'] = $date_time;
        //活跃用户数
        $arr['active_user_num'] = $this->google_active_user(3, $date_time);
        //注册用户数
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();
        //新增vip用户数
        // $vip_where = [];
        // $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $date_time . "'")];
        // $vip_where['order_status'] = 'Success';
        // $arr['vip_user_num'] = $zeelool_model->table('oc_vip_order')->where($vip_where)->count();
        //订单数
        $order_where = [];
        $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $arr['order_num'] = $this->zeelool->where($order_where)->count();
        //销售额
        $arr['sales_total_money'] = $this->zeelool->where($order_where)->sum('base_grand_total');
        //邮费
        $arr['shipping_total_money'] = $this->zeelool->where($order_where)->sum('base_shipping_amount');
        //购买人数
        // $order_user = $this->zeelool->where($order_where)->count('distinct customer_id');
        //客单价
        // $arr['order_unit_price'] = $order_user ? round($arr['sales_total_money'] / $order_user, 2) : 0;


        $order_where1 = [];
        $order_where1[] = ['exp', Db::raw("customer_id is not null and customer_id != 0")];
        $order_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $order_where1['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $sales_total_money1 = $this->zeelool->where($order_where1)->sum('base_grand_total');
        $order_user1 = $this->zeelool->where($order_where1)->count('distinct customer_id');
        //客单价
        $arr['order_unit_price'] = $order_user1 ? round($sales_total_money1 / $order_user1, 2) : 0;


        //会话
        $arr['sessions'] = $this->google_session(3, $date_time);
        //新建购物车数量
        $cart_where1 = [];
        $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->count();
        //更新购物车数量
        $cart_where2 = [];
        $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $date_time . "'")];
        $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->count();
        //新增加购率
        $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'], 2) : 0;
        //更新加购率
        $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'], 2) : 0;
        //新增购物车转化率
        $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'], 2) : 0;
        //更新购物车转化率
        $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'], 2) : 0;
        //插入数据
        Db::name('datacenter_day')->insert($arr);
        echo $date_time . "\n";
        usleep(100000);

    }

}
