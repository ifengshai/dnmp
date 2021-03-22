<?php

namespace app\admin\controller;

use think\Controller;
use app\Common\model\Auth;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use fast\Trackingmore;

class Test4 extends Controller
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';
    protected $str1 = 'Arrived Shipping Partner Facility, Awaiting Item.';
    protected $str2 = 'Delivered to Air Transport.';
    protected $str3 = 'In Transit to Next Facility.';
    protected $str4 = 'Arrived in the Final Destination Country.';

    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolde = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelooljp = new \app\admin\model\order\order\ZeeloolJp();
        $this->user = new \app\admin\model\Admin();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $this->ordernode = new \app\admin\model\OrderNode();
    }


    protected function initializeAnalytics()
    {
        // Use the developers console and download your service account
        // credentials in JSON format. Place them in this directory or
        // change the key file location if necessary.
        $KEY_FILE_LOCATION = __DIR__ . '/oauth-credentials.json';

        // Create and configure a new client object.
        $client = new \Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new \Google_Service_AnalyticsReporting($client);

        return $analytics;
    }

    //活跃用户数
    public function google_active_user($site, $start_time)
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
        $response = $this->getReport_active_user($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        return $result[0]['ga:1dayUsers'] ? round($result[0]['ga:1dayUsers'], 2) : 0;
    }

    protected function getReport_active_user($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 10) {
            $VIEW_ID = config('ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 11) {
            $VIEW_ID = config('ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID');
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
    public function google_session($site, $start_time)
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
        $response = $this->getReport_session($site, $analytics, $start_time, $end_time);

        // dump($response);die;

        // Print the response.
        $result = $this->printResults($response);

        return $result[0]['ga:sessions'] ? round($result[0]['ga:sessions'], 2) : 0;
    }

    protected function getReport_session($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 10) {
            $VIEW_ID = config('ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 11) {
            $VIEW_ID = config('ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID');
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
    //运营数据ga数据脚本
    public function only_ga_data()
    {
        $where['site'] = ['in',[10,11]];
        $days = Db::name('datacenter_day')->where($where)->field('id,site,day_date,order_num,sessions')->select();
        foreach ($days as $data) {
            $arr = [];
            //会话转化率
            $arr['session_rate'] = $data['sessions'] != 0 ? round($data['order_num'] / $data['sessions'] * 100, 2) : 0;
            if ($data['site'] == 10) {
                $model = new \app\admin\model\operatedatacenter\ZeeloolDe();
            } else {
                $model = new \app\admin\model\operatedatacenter\ZeeloolJp();
            }
            //着陆页数据
            $arr['landing_num'] = $model->google_landing($data['site'], $data['day_date']);
            //产品详情页
            $arr['detail_num'] = $model->google_target13($data['site'], $data['day_date']);
            //加购
            $arr['cart_num'] = $model->google_target1($data['site'], $data['day_date']);
            //交易次数
            $arr['complete_num'] = $model->google_target_end($data['site'], $data['day_date']);
            Db::name('datacenter_day')->where('id', $data['id'])->update($arr);
            echo $data['id'].' is ok'."\n";
            usleep(100000);
        }
    }
    //新增运营数据中心
    public function zeelool_operate_data_center()
    {
        $zeelool_model = Db::connect('database.db_zeelool_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->zeelool->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2020-10-01' and '2020-10-21' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->where('site', 1)->value('id');
            if (!$is_exist) {
                $arr = [];
                $arr['site'] = 1;
                $arr['day_date'] = $val['date_time'];
                //活跃用户数
                $arr['active_user_num'] = $this->google_active_user(1, $val['date_time']);
                //注册用户数
                $register_where = [];
                $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['register_num'] = $zeelool_model->table('customer_entity')->where($register_where)->count();
                //新增vip用户数
                $vip_where = [];
                $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $vip_where['order_status'] = 'Success';
                $arr['vip_user_num'] = $zeelool_model->table('oc_vip_order')->where($vip_where)->count();
                //订单数
                $order_where = [];
                $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type', 1)->count();
                //销售额
                $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
                //客单价
                $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
                //中位数
                $sales_total_money = $this->zeelool->where($order_where)->where('order_type', 1)->column('base_grand_total');
                $arr['order_total_midnum'] = $this->median($sales_total_money);
                //标准差
                $arr['order_total_standard'] = $this->getVariance($sales_total_money);
                //补发订单数
                $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type', 4)->count();
                //补发销售额
                $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type', 4)->sum('base_grand_total');
                //网红订单数
                $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type', 3)->count();
                //补发销售额
                $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type', 3)->sum('base_grand_total');
                //会话
                $arr['sessions'] = $this->google_session(1, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
                //新增加购率
                $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
                //更新加购率
                $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
                //新增购物车转化率
                $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
                //更新购物车转化率
                $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
                //插入数据
                Db::name('datacenter_day')->insert($arr);
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }

    //新增运营数据中心
    public function voogueme_operate_data_center()
    {
        $voogueme_model = Db::connect('database.db_voogueme_online');
        $voogueme_model->table('customer_entity')->query("set time_zone='+8:00'");
        $voogueme_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->voogueme->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2020-10-01' and '2020-10-21' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where(['day_date' => $val['date_time'], 'site' => 2])->value('id');
            if (!$is_exist) {
                $arr = [];
                $arr['site'] = 2;
                $arr['day_date'] = $val['date_time'];
                //活跃用户数
                $arr['active_user_num'] = $this->google_active_user(2, $val['date_time']);
                //注册用户数
                $register_where = [];
                $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['register_num'] = $voogueme_model->table('customer_entity')->where($register_where)->count();
                //新增vip用户数
                $vip_where = [];
                $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $vip_where['order_status'] = 'Success';
                $arr['vip_user_num'] = $voogueme_model->table('oc_vip_order')->where($vip_where)->count();
                //订单数
                $order_where = [];
                $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $arr['order_num'] = $this->voogueme->where($order_where)->where('order_type', 1)->count();
                //销售额
                $arr['sales_total_money'] = $this->voogueme->where($order_where)->where('order_type', 1)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->voogueme->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
                //客单价
                $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
                //中位数
                $sales_total_money = $this->voogueme->where($order_where)->where('order_type', 1)->column('base_grand_total');
                $arr['order_total_midnum'] = $this->median($sales_total_money);
                //标准差
                $arr['order_total_standard'] = $this->getVariance($sales_total_money);
                //补发订单数
                $arr['replacement_order_num'] = $this->voogueme->where($order_where)->where('order_type', 4)->count();
                //补发销售额
                $arr['replacement_order_total'] = $this->voogueme->where($order_where)->where('order_type', 4)->sum('base_grand_total');
                //网红订单数
                $arr['online_celebrity_order_num'] = $this->voogueme->where($order_where)->where('order_type', 3)->count();
                //补发销售额
                $arr['online_celebrity_order_total'] = $this->voogueme->where($order_where)->where('order_type', 3)->sum('base_grand_total');
                //会话
                $arr['sessions'] = $this->google_session(2, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $voogueme_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $voogueme_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
                //新增加购率
                $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
                //更新加购率
                $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
                //新增购物车转化率
                $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
                //更新购物车转化率
                $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
                //插入数据
                Db::name('datacenter_day')->insert($arr);
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }

    //新增运营数据中心
    public function nihao_operate_data_center()
    {
        $nihao_model = Db::connect('database.db_nihao_online');
        $nihao_model->table('customer_entity')->query("set time_zone='+8:00'");
        $nihao_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->nihao->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2020-10-01' and '2020-10-21' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where(['day_date' => $val['date_time'], 'site' => 3])->value('id');
            if (!$is_exist) {
                $arr = [];
                $arr['site'] = 3;
                $arr['day_date'] = $val['date_time'];
                //活跃用户数
                $arr['active_user_num'] = $this->google_active_user(3, $val['date_time']);
                //注册用户数
                $register_where = [];
                $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['register_num'] = $nihao_model->table('customer_entity')->where($register_where)->count();
                //订单数
                $order_where = [];
                $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $arr['order_num'] = $this->nihao->where($order_where)->where('order_type', 1)->count();
                //销售额
                $arr['sales_total_money'] = $this->nihao->where($order_where)->where('order_type', 1)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->nihao->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
                //客单价
                $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
                //中位数
                $sales_total_money = $this->nihao->where($order_where)->where('order_type', 1)->column('base_grand_total');
                $arr['order_total_midnum'] = $this->median($sales_total_money);
                //标准差
                $arr['order_total_standard'] = $this->getVariance($sales_total_money);
                //补发订单数
                $arr['replacement_order_num'] = $this->nihao->where($order_where)->where('order_type', 4)->count();
                //补发销售额
                $arr['replacement_order_total'] = $this->nihao->where($order_where)->where('order_type', 4)->sum('base_grand_total');
                //网红订单数
                $arr['online_celebrity_order_num'] = $this->nihao->where($order_where)->where('order_type', 3)->count();
                //补发销售额
                $arr['online_celebrity_order_total'] = $this->nihao->where($order_where)->where('order_type', 3)->sum('base_grand_total');
                //会话
                $arr['sessions'] = $this->google_session(3, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $nihao_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $nihao_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
                //新增加购率
                $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
                //更新加购率
                $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
                //新增购物车转化率
                $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
                //更新购物车转化率
                $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
                //插入数据
                Db::name('datacenter_day')->insert($arr);
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }

    //运营数据中心--德语站
    public function zeelool_de_operate_data_center()
    {
        $model = Db::connect('database.db_zeelool_de');
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->zeeloolde->query("SELECT DATE_FORMAT(payment_time, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where payment_time between '2021-03-17' and '2021-03-18' GROUP BY DATE_FORMAT(payment_time, '%Y%m%d') order by DATE_FORMAT(payment_time, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->where('site', 10)->value('id');
            $arr = [];
            //活跃用户数
            $arr['active_user_num'] = $this->google_active_user(10, $val['date_time']);
            //注册用户数
            $register_where = [];
            $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $arr['register_num'] = $model->table('customer_entity')->where($register_where)->count();
            //新增vip用户数
            $vip_where = [];
            $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $vip_where['order_status'] = 'Success';
            $arr['vip_user_num'] = $model->table('oc_vip_order')->where($vip_where)->count();
            //订单数
            $order_where = [];
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
            $arr['order_num'] = $this->zeeloolde->where($order_where)->where('order_type', 1)->count();
            //销售额
            $arr['sales_total_money'] = $this->zeeloolde->where($order_where)->where('order_type', 1)->sum('base_grand_total');
            //邮费
            $arr['shipping_total_money'] = $this->zeeloolde->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
            //客单价
            $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
            //中位数
            $sales_total_money = $this->zeeloolde->where($order_where)->where('order_type', 1)->column('base_grand_total');
            $arr['order_total_midnum'] = $this->median($sales_total_money);
            //标准差
            $arr['order_total_standard'] = $this->getVariance($sales_total_money);
            //补发订单数
            $arr['replacement_order_num'] = $this->zeeloolde->where($order_where)->where('order_type', 4)->count();
            //补发销售额
            $arr['replacement_order_total'] = $this->zeeloolde->where($order_where)->where('order_type', 4)->sum('base_grand_total');
            //网红订单数
            $arr['online_celebrity_order_num'] = $this->zeeloolde->where($order_where)->where('order_type', 3)->count();
            //补发销售额
            $arr['online_celebrity_order_total'] = $this->zeeloolde->where($order_where)->where('order_type', 3)->sum('base_grand_total');
            //会话
            $arr['sessions'] = $this->google_session(10, $val['date_time']);
            //新建购物车数量
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $arr['new_cart_num'] = $model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
            //更新购物车数量
            $cart_where2 = [];
            $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $arr['update_cart_num'] = $model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
            //新增加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
            //更新加购率
            $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
            //新增购物车转化率
            $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
            //更新购物车转化率
            $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
            if (!$is_exist) {
                $arr['site'] = 10;
                $arr['day_date'] = $val['date_time'];
                //插入数据
                Db::name('datacenter_day')->insert($arr);
                echo $val['date_time'] .' ok'. "\n";
                usleep(100000);
            } else{
                //更新数据
                Db::name('datacenter_day')->where('id',$is_exist)->update($arr);
                echo $val['date_time'] .' is ok'. "\n";
                usleep(100000);
            }
        }
    }
    //运营数据中心--日本站
    public function zeelool_jp_operate_data_center()
    {
        $model = Db::connect('database.db_zeelool_jp');
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->zeelooljp->query("SELECT DATE_FORMAT(payment_time, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where payment_time between '2021-03-17' and '2021-03-18' GROUP BY DATE_FORMAT(payment_time, '%Y%m%d') order by DATE_FORMAT(payment_time, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->where('site', 11)->value('id');
            $arr = [];
            //活跃用户数
            $arr['active_user_num'] = $this->google_active_user(11, $val['date_time']);
            //注册用户数
            $register_where = [];
            $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $arr['register_num'] = $model->table('customer_entity')->where($register_where)->count();
            //新增vip用户数
            $vip_where = [];
            $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $vip_where['order_status'] = 'Success';
            $arr['vip_user_num'] = $model->table('oc_vip_order')->where($vip_where)->count();
            //订单数
            $order_where = [];
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(payment_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
            $arr['order_num'] = $this->zeelooljp->where($order_where)->where('order_type', 1)->count();
            //销售额
            $arr['sales_total_money'] = $this->zeelooljp->where($order_where)->where('order_type', 1)->sum('base_grand_total');
            //邮费
            $arr['shipping_total_money'] = $this->zeelooljp->where($order_where)->where('order_type', 1)->sum('base_shipping_amount');
            //客单价
            $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
            //中位数
            $sales_total_money = $this->zeelooljp->where($order_where)->where('order_type', 1)->column('base_grand_total');
            $arr['order_total_midnum'] = $this->median($sales_total_money);
            //标准差
            $arr['order_total_standard'] = $this->getVariance($sales_total_money);
            //补发订单数
            $arr['replacement_order_num'] = $this->zeelooljp->where($order_where)->where('order_type', 4)->count();
            //补发销售额
            $arr['replacement_order_total'] = $this->zeelooljp->where($order_where)->where('order_type', 4)->sum('base_grand_total');
            //网红订单数
            $arr['online_celebrity_order_num'] = $this->zeelooljp->where($order_where)->where('order_type', 3)->count();
            //补发销售额
            $arr['online_celebrity_order_total'] = $this->zeelooljp->where($order_where)->where('order_type', 3)->sum('base_grand_total');
            //会话
            $arr['sessions'] = $this->google_session(11, $val['date_time']);
            //新建购物车数量
            $cart_where1 = [];
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $arr['new_cart_num'] = $model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total', 'gt', 0)->count();
            //更新购物车数量
            $cart_where2 = [];
            $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
            $arr['update_cart_num'] = $model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total', 'gt', 0)->count();
            //新增加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'] * 100, 2) : 0;
            //更新加购率
            $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'] * 100, 2) : 0;
            //新增购物车转化率
            $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'] * 100, 2) : 0;
            //更新购物车转化率
            $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'] * 100, 2) : 0;
            if (!$is_exist) {
                $arr['site'] = 11;
                $arr['day_date'] = $val['date_time'];
                //插入数据
                Db::name('datacenter_day')->insert($arr);
                echo $val['date_time'] .' ok'. "\n";
                usleep(100000);
            } else{
                //更新数据
                Db::name('datacenter_day')->where('id',$is_exist)->update($arr);
                echo $val['date_time'] .' is ok'. "\n";
                usleep(100000);
            }
        }
    }

    //更新运营数据中心
    public function zeelool_operate_data_center_update()
    {
        $model = Db::connect('database.db_zeelool_online');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $date_time = Db::name('datacenter_day')->where('site', 1)->field('id,day_date,sessions,order_num,new_cart_num,update_cart_num')->order('id asc')->select();
        foreach ($date_time as $val) {
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $order_where['order_type'] = 1;

            $create_where = $update_where = [];
            $create_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $update_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            //当天注册用户数
            $register_userids = $model->table('customer_entity')->where($create_where)->column('entity_id');
            $register_num = count($register_userids);
            //当天注册用户在当天下单的用户数
            $order_user_count1 = 0;
            foreach ($register_userids as $register_userid) {
                //判断当前用户在当天是否下单
                $order = $model->table('sales_flat_order')->where($create_where)->where($order_where)->where('customer_id', $register_userid)->value('entity_id');
                if ($order) {
                    $order_user_count1++;
                }
            }
            $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;

            //当天更新用户数
            $update_userids = $model->table('customer_entity')->where($update_where)->column('entity_id');
            $update_num = count($update_userids);
            //当天活跃更新用户数在当天是否下单
            $order_user_count2 = 0;
            foreach ($update_userids as $update_userid) {
                //判断活跃用户在当天下单的用户数
                $order = $model->table('sales_flat_order')->where($create_where)->where($order_where)->where('customer_id', $update_userid)->value('entity_id');
                if ($order) {
                    $order_user_count2++;
                }
            }
            $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 2) : 0;
            //更新数据
            Db::name('datacenter_day')->where('id', $val['id'])->update($arr);
            echo $val['day_date'] . "\n";
            usleep(100000);
        }
    }

    //更新运营数据中心
    public function voogueme_operate_data_center_update()
    {
        $model = Db::connect('database.db_voogueme_online');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $date_time = Db::name('datacenter_day')->where('site', 2)->field('id,day_date,sessions,order_num,new_cart_num,update_cart_num')->order('id asc')->select();
        foreach ($date_time as $val) {
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $order_where['order_type'] = 1;

            $create_where = $update_where = [];
            $create_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $update_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            //当天注册用户数
            $register_userids = $model->table('customer_entity')->where($create_where)->column('entity_id');
            $register_num = count($register_userids);
            //当天注册用户在当天下单的用户数
            $order_user_count1 = 0;
            foreach ($register_userids as $register_userid) {
                //判断当前用户在当天是否下单
                $order = $model->table('sales_flat_order')->where($create_where)->where($order_where)->where('customer_id', $register_userid)->value('entity_id');
                if ($order) {
                    $order_user_count1++;
                }
            }
            $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;

            //当天更新用户数
            $update_userids = $model->table('customer_entity')->where($update_where)->column('entity_id');
            $update_num = count($update_userids);
            //当天活跃更新用户数在当天是否下单
            $order_user_count2 = 0;
            foreach ($update_userids as $update_userid) {
                //判断活跃用户在当天下单的用户数
                $order = $model->table('sales_flat_order')->where($create_where)->where($order_where)->where('customer_id', $update_userid)->value('entity_id');
                if ($order) {
                    $order_user_count2++;
                }
            }
            $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 2) : 0;

            //更新数据
            Db::name('datacenter_day')->where('id', $val['id'])->update($arr);
            echo $val['day_date'] . "\n";
            usleep(100000);
        }
    }

    //更新运营数据中心
    public function nihao_operate_data_center_update()
    {
        $model = Db::connect('database.db_nihao_online');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $date_time = Db::name('datacenter_day')->where('site', 3)->field('id,day_date,sessions,order_num,new_cart_num,update_cart_num')->order('id asc')->select();
        foreach ($date_time as $val) {
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $order_where['order_type'] = 1;

            $create_where = $update_where = [];
            $create_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $update_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            //当天注册用户数
            $register_userids = $model->table('customer_entity')->where($create_where)->column('entity_id');
            $register_num = count($register_userids);
            //当天注册用户在当天下单的用户数
            $order_user_count1 = 0;
            foreach ($register_userids as $register_userid) {
                //判断当前用户在当天是否下单
                $order = $model->table('sales_flat_order')->where($create_where)->where($order_where)->where('customer_id', $register_userid)->value('entity_id');
                if ($order) {
                    $order_user_count1++;
                }
            }
            $arr['create_user_change_rate'] = $register_num ? round($order_user_count1 / $register_num * 100, 2) : 0;

            //当天更新用户数
            $update_userids = $model->table('customer_entity')->where($update_where)->column('entity_id');
            $update_num = count($update_userids);
            //当天活跃更新用户数在当天是否下单
            $order_user_count2 = 0;
            foreach ($update_userids as $update_userid) {
                //判断活跃用户在当天下单的用户数
                $order = $model->table('sales_flat_order')->where($create_where)->where($order_where)->where('customer_id', $update_userid)->value('entity_id');
                if ($order) {
                    $order_user_count2++;
                }
            }
            $arr['update_user_change_rate'] = $update_num ? round($order_user_count2 / $update_num * 100, 2) : 0;
            //更新数据
            Db::name('datacenter_day')->where('id', $val['id'])->update($arr);
            echo $val['day_date'] . "\n";
            usleep(100000);
        }
    }
    public function sku_day_data_ga()
    {
        $zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        set_time_limit(0);
        //统计昨天的数据
        $info = Db::name('datacenter_sku_day')->where('site','in','2,10,11')->field('id,platform_sku,day_date,site')->select();
        foreach($info as $key=>$value){
            $data = $value['day_date'];
            $ga_skus = $zeeloolOperate->google_sku_detail($value['site'], $data);
            $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');
            if($value['site'] == 2){
                $model = Db::connect('database.db_voogueme_online');
            }elseif($value['site'] == 10){
                $model = Db::connect('database.db_zeelool_de_online');
            }else{
                $model = Db::connect('database.db_zeelool_jp_online');
            }
            $sku_id = $model->table('catalog_product_entity')->where('sku',$value['platform_sku'])->value('entity_id');
            $unique_pageviews = 0;
            foreach ($ga_skus as $kk => $vv) {
                preg_match('/\d+/',$kk,$str_arr);
                if ($str_arr[0] == $sku_id) {
                    $unique_pageviews += $vv;
                }
            }
            Db::name('datacenter_sku_day')->where('id',$value['id'])->update(['unique_pageviews'=>$unique_pageviews]);
            echo $value['id'].' is ok'."\n";
            usleep(10000);
        }
    }
    //计划任务跑每天的分类销量的数据
    public function day_data_goods_type()
    {
        $where['day_date'] = ['between',['2020-10-08','2021-03-20']];
        $date_time = Db::name('datacenter_day')->where('site', 1)->where($where)->column('day_date');
        foreach($date_time as $v){
            $res12 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(10, 1,$v));
            if ($res12) {
                echo 'de站平光镜ok';
            } else {
                echo 'de站平光镜不ok';
            }
            $res13 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(10, 2,$v));
            if ($res13) {
                echo 'de站太阳镜ok';
            } else {
                echo 'de站太阳镜不ok';
            }
            $res14 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(10, 6,$v));
            if ($res14) {
                echo 'de站配饰ok';
            } else {
                echo 'de站配饰不ok';
            }
            $res15 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(11, 1,$v));
            if ($res15) {
                echo 'jp站平光镜ok';
            } else {
                echo 'jp站平光镜不ok';
            }
            $res16 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(11, 2,$v));
            if ($res16) {
                echo 'jp站太阳镜ok';
            } else {
                echo 'jp站太阳镜不ok';
            }
            $res17 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center(11, 6,$v));
            if ($res17) {
                echo 'jp站配饰ok';
            } else {
                echo 'jp站配饰不ok';
            }

        }

    }
    //统计昨天各品类镜框的销量
    public function goods_type_day_center($plat, $goods_type,$time)
    {
        $start = $time;
        $seven_days = $start . ' 00:00:00 - ' . $start . ' 23:59:59';
        $createat = explode(' ', $seven_days);
        $itemMap['m.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
        //判断站点
        switch ($plat) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        //$whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered')";
        //某个品类眼镜的销售副数
        $frame_sales_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            ->sum('m.qty_ordered');
        //求出眼镜的销售额 base_price  base_discount_amount
        $frame_money_price = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where($whereItem)
            ->where('p.goods_type', '=', $goods_type)
            ->where($itemMap)
            ->sum('m.base_price');
        //眼镜的折扣价格
        $frame_money_discount = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where($whereItem)
            ->where('p.goods_type', '=', $goods_type)
            ->where($itemMap)
            ->sum('m.base_discount_amount');
        //眼镜的实际销售额
        $frame_money = round(($frame_money_price - $frame_money_discount), 2);

        $arr['day_date'] = $start;
        $arr['site'] = $plat;
        $arr['goods_type'] = $goods_type;
        $arr['glass_num'] = $frame_sales_num;
        $arr['sales_total_money'] = $frame_money;
        return $arr;
    }

    /**
     *计算中位数 中位数：是指一组数据从小到大排列，位于中间的那个数。可以是一个（数据为奇数），也可以是2个的平均（数据为偶数）
     */
    function median($numbers)
    {
        sort($numbers);
        $totalNumbers = count($numbers);
        $mid = floor($totalNumbers / 2);

        return ($totalNumbers % 2) === 0 ? ($numbers[$mid - 1] + $numbers[$mid]) / 2 : $numbers[$mid];
    }

    /**
     * 得到数组的标准差
     * @param unknown type $avg
     * @param Array $list
     * @param Boolen $isSwatch
     * @return unknown type
     */
    function getVariance($arr)
    {
        $length = count($arr);
        if ($length == 0) {
            return 0;
        }
        $average = array_sum($arr) / $length;
        $count = 0;
        foreach ($arr as $v) {
            $count += pow($average - $v, 2);
        }
        $variance = $count / $length;
        return sqrt($variance);
    }

    public function test006()
    {

        $carrier = $this->getCarrier('usps');
        $trackingConnector = new TrackingConnector($this->apiKey);
        $trackInfo = $trackingConnector->getTrackInfoMulti([[
            'number' => '92001902551561000101621623',
            'carrier' => $carrier['carrierId']
            /*'number' => 'LO546092713CN',//E邮宝
            'carrier' => '03011'*/
            /* 'number' => '3616952791',//DHL
            'carrier' => '100001' */
            /* 'number' => '74890988318620573173', //Fedex
            'carrier' => '100003' */
            /* 'number' => '92001902551559000101352584', //usps郭伟峰
            'carrier' => '21051' */
        ]]);
        dump($trackInfo['data']['accepted'][0]['track']['z1']);
        die;
    }


    //数据已跑完 2020 08.25 14:47
    public function amazon_sku()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $skus = Db::name('zzzzzzz_temp')->select();
        foreach ($skus as $k => $v) {
            $params = [];
            if (!empty($v['true_sku'])) {
                $item_detail = $item->where('sku', $v['true_sku'])->find();
                $params['sku'] = $v['true_sku'];
                $params['platform_sku'] = $v['sku'];
                if (empty($item_detail['name'])) {
                    $params['name'] = '';
                } else {
                    $params['name'] = $item_detail['name'];
                }
                $params['platform_type'] = 11;
                $params['create_person'] = 'Admin';
                $params['create_time'] = date("Y-m-d H:i:s");
                $params['is_upload'] = 1;
                $params['outer_sku_status'] = $v['status'];
                $res = $item_platform_sku->insert($params);
            }
            echo $k . "\n";
        }
        echo "ok";
        die;
    }

    public function test01()
    {
        $str = "%2B";
        echo urldecode($str);
    }


    public function zendesk_test()
    {
        $comments = new \app\admin\model\zendesk\ZendeskComments();
        $list = $comments->field('id,author_id')->where(['create_time' => ['between', ['2020-07-01 00:00:00', '2020-07-19 00:00:00']], 'is_admin' => 0])->select();

        $account = new \app\admin\model\zendesk\ZendeskAccount();
        $account_id = $account->column('account_id');
        foreach ($list as $k => $v) {
            if (in_array($v['author_id'], $account_id)) {
                $comments->where('id', $v['id'])->update(['is_admin' => 1]);
            }
            echo $v['id'] . "\n";
        }
        echo "is ok";
        die;
    }

    /**
     * 处理在途库存
     *
     * @Description
     * @author wpl
     * @since 2020/06/09 10:08:03 
     * @return void
     */
    public function proccess_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $result = $item->where(['is_open' => 1, 'is_del' => 1, 'on_way_stock' => ['<', 0]])->field('sku,id')->select();
        $result = collection($result)->toArray();
        $skus = array_column($result, 'sku');


        //查询签收的采购单
        $logistics = new \app\admin\model\LogisticsInfo();
        $purchase_id = $logistics->where(['status' => 1, 'purchase_id' => ['>', 0]])->column('purchase_id');
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        // $res = $purchase->where(['id' => ['in', $purchase_id], 'purchase_status' => 6])->update(['purchase_status' => 7]);
        //计算SKU总采购数量
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $hasWhere['sku'] = ['in', $skus];
        $purchase_map['purchase_status'] = ['in', [2, 5, 6]];
        $purchase_map['is_del'] = 1;
        $purchase_map['PurchaseOrder.id'] = ['not in', $purchase_id];
        $purchase_list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->group('sku')
            ->column('sum(purchase_num) as purchase_num', 'sku');

        foreach ($result as &$v) {
            $v['on_way_stock'] = $purchase_list[$v['sku']] ?? 0;
            unset($v['sku']);
        }
        unset($v);
        $res = $item->saveAll($result);
        echo $res;
        die;
    }


    /**
     * 处理各站虚拟仓库存
     *
     * @Description
     * @author wpl
     * @since 2020/08/14 09:30:39 
     * @return void
     */
    public function proccess_sku_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $itemPlatformSKU = new \app\admin\model\itemmanage\ItemPlatformSku();
        $list = $item->where(['is_del' => 1, 'is_open' => 1, 'available_stock' => ['>', 0]])->select();

        //查询临时表比例数据
        $data = Db::name('zzz_temp')->column('*', 'sku');
        foreach ($list as $k => $v) {
            $zeelool_stock = 0;
            $voogueme_stock = 0;
            $nihao_stock = 0;
            $meeloog_stock = 0;
            $wesee_stock = 0;
            //如果存在比例
            if ($data[$v['sku']]) {
                $zeelool_stock = $data[$v['sku']]['zeelool'] > 0 ? ceil($v['available_stock'] * $data[$v['sku']]['zeelool'] / 100) : 0;
                if (($v['available_stock'] - $zeelool_stock) > 0) {
                    $voogueme_stock = ($v['available_stock'] - $zeelool_stock) > ceil($v['available_stock'] * $data[$v['sku']]['voogueme'] / 100) ? ceil($v['available_stock'] * $data[$v['sku']]['voogueme'] / 100) : ($v['available_stock'] - $zeelool_stock);
                }

                if (($v['available_stock'] - $zeelool_stock - $voogueme_stock) > 0) {
                    $nihao_stock = ($v['available_stock'] - $zeelool_stock - $voogueme_stock) > ceil($v['available_stock'] * $data[$v['sku']]['nihao'] / 100) ? ceil($v['available_stock'] * $data[$v['sku']]['nihao'] / 100) : ($v['available_stock'] - $zeelool_stock - $voogueme_stock);
                }


                if (($v['available_stock'] - $zeelool_stock - $voogueme_stock - $nihao_stock) > 0) {
                    $meeloog_stock = ($v['available_stock'] - $zeelool_stock - $voogueme_stock - $nihao_stock) > ceil($v['available_stock'] * $data[$v['sku']]['meeloog'] / 100) ? ceil($v['available_stock'] * $data[$v['sku']]['meeloog'] / 100) : ($v['available_stock'] - $zeelool_stock - $voogueme_stock - $nihao_stock);
                }

                $stock = $v['available_stock'] - $zeelool_stock - $voogueme_stock - $nihao_stock - $meeloog_stock;
                $wesee_stock = $stock > 0 ? $stock : 0;
            } else {
                $zeelool_stock = $v['available_stock'];
            }

            if ($zeelool_stock > 0) {
                $itemPlatformSKU->where(['sku' => $v['sku'], 'platform_type' => 1])->update(['stock' => $zeelool_stock]);
            }

            if ($voogueme_stock > 0) {
                $itemPlatformSKU->where(['sku' => $v['sku'], 'platform_type' => 2])->update(['stock' => $voogueme_stock]);
            }

            if ($nihao_stock > 0) {
                $itemPlatformSKU->where(['sku' => $v['sku'], 'platform_type' => 3])->update(['stock' => $nihao_stock]);
            }

            if ($meeloog_stock > 0) {
                $itemPlatformSKU->where(['sku' => $v['sku'], 'platform_type' => 4])->update(['stock' => $meeloog_stock]);
            }

            if ($wesee_stock > 0) {
                $itemPlatformSKU->where(['sku' => $v['sku'], 'platform_type' => 5])->update(['stock' => $wesee_stock]);
            }
            echo $k . "\n";
            usleep(50000);
        }
        echo 'ok';
    }

    /**
     * 修改sku 上下架
     *
     * @Description
     * @author wpl
     * @since 2020/08/19 10:30:16 
     * @return void
     */
    public function proccess_sku_status()
    {
        $itemPlatformSKU = new \app\admin\model\itemmanage\ItemPlatformSku();
        //查询临时表比例数据
        $data = Db::name('zzzzaaa_temp')->select();
        foreach ($data as $k => $v) {
            $itemPlatformSKU->where(['platform_type' => 1, 'sku' => trim($v['sku'])])->update(['outer_sku_status' => $v['status']]);
            echo $k . "\n";
            usleep(50000);
        }
        echo 'ok';
    }

    public function new_track_test()
    {

        $order_shipment = Db::name('order_node')->where(['node_type' => 10, 'order_node' => 3, 'shipment_type' => 'USPS'])->select();
        $order_shipment = collection($order_shipment)->toArray();

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach ($order_shipment as $k => $v) {
            //先把主表状态更新为2-7
            // $update['order_node'] = 2;
            // $update['node_type'] = 7;
            // Db::name('order_node')->where('id', $v['id'])->update($update); //更新主表状态

            $title = strtolower(str_replace(' ', '-', $v['title']));

            $carrier = $this->getCarrier($title);

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $v['track_number'],
                'carrier' => $carrier['carrierId']
                /*'number' => 'LO546092713CN',//E邮宝
                'carrier' => '03011'*/
                /* 'number' => '3616952791',//DHL
                'carrier' => '100001' */
                /* 'number' => '74890988318620573173', //Fedex
                'carrier' => '100003' */
                /* 'number' => '92001902551559000101352584', //usps郭伟峰
                'carrier' => '21051' */
            ]]);

            $add['site'] = $v['site'];
            $add['order_id'] = $v['order_id'];
            $add['order_number'] = $v['order_number'];
            $add['shipment_type'] = $v['shipment_type'];
            $add['shipment_data_type'] = $v['shipment_data_type'];
            $add['track_number'] = $v['track_number'];

            if ($trackInfo['code'] == 0 && $trackInfo['data']['accepted']) {
                $trackdata = $trackInfo['data']['accepted'][0]['track'];

                if (stripos($v['shipment_type'], 'USPS') !== false) {
                    if ($v['shipment_data_type'] == 'USPS_1') {
                        //郭伟峰
                        $this->usps_1_data($trackdata, $add);
                    }
                    if ($v['shipment_data_type'] == 'USPS_2') {
                        //加诺
                        $this->usps_2_data($trackdata, $add);
                    }

                    if ($v['shipment_data_type'] == 'USPS_3') {
                        //临时杜明明
                        $this->usps_3_data($trackdata, $add);
                    }
                }

                if (stripos($v['shipment_type'], 'DHL') !== false) {
                    $this->new_dhl_data($trackdata, $add);
                }

                if (stripos($v['shipment_type'], 'fede') !== false) {
                    $this->new_fedex_data($trackdata, $add);
                }
            }
            echo 'site:' . $v['site'] . ';key:' . $k . ';order_id' . $v['order_id'] . "\n";
            usleep(200000);
        }
        echo 'ok';
    }

    //fedex
    public function new_fedex_data($data, $add)
    {
        $sel_num = 1; //抓取第二条
        $trackdetail = array_reverse($data['z1']);
        $all_num = count($trackdetail);

        $order_node_detail['order_node'] = 3;
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];

        if ($data['e'] != 0) {
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                if ($count < 1) {
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }

                //到达目的国
                if (stripos($v['z'], 'International shipment release - Import') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if ($all_num - 1 == $k) {
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间 
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //DHL
    public function new_dhl_data($data, $add)
    {
        $sel_num = 1; //抓取第二条
        $trackdetail = array_reverse($data['z1']);
        $all_num = count($trackdetail);

        $order_node_detail['order_node'] = 3;
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];

        if ($data['e'] != 0) {
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                if ($count < 1) {
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }


                //到达目的国
                if (stripos($v['z'], 'Customs status updated') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if ($all_num - 1 == $k) {
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间 
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //usps_1  郭伟峰
    public function usps_1_data($data, $add)
    {
        $sel_num = 1; //抓取第二条
        $trackdetail = array_reverse($data['z1']);
        $all_num = count($trackdetail);

        $order_node_detail['order_node'] = 3;
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];

        if ($data['e'] != 0) {
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                if ($count < 1) {
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }

                //到达目的国
                if (stripos($v['z'], 'Accepted at USPS Origin Facility') !== false || stripos($v['z'], 'Accepted at USPS Regional Origin Facility') !== false || stripos($v['z'], 'Arrived at USPS Regional Destination Facility') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if ($all_num - 1 == $k) {
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();


                        //因为没有匹配上到达目的国，所以根据签收时间-1天就是到达目的国
                        if ($data['e'] == 40 && ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)) {
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " -1 day")));
                            $update_order_node['order_node'] = 3;
                            $update_order_node['node_type'] = 11;
                            $update_order_node['update_time'] = $time;
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['node_type'] = 11;
                            $order_node_detail['content'] = $this->str4;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            $time = '';
                            $order_node_date['order_node'] = 3;
                            $order_node_date['node_type'] = 11;
                        }


                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间 
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //usps_2  加诺
    public function usps_2_data($data, $add)
    {
        //根据出库时间，+1天后就是上网，再+1天就是运输中
        $where['track_number'] = $add['track_number'];
        $where['order_node'] = 2;
        $where['node_type'] = 7;
        $order_node_detail_time = Db::name('order_node_detail')->where($where)->field('create_time')->find();
        $time = date('Y-m-d H:i', strtotime(($order_node_detail_time['create_time'] . " +1 day")));

        $trackdetail = array_reverse($data['z1']);
        $all_num = count($trackdetail);

        $order_node_detail['order_node'] = 3;
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];

        if ($all_num > 0 && $data['e'] != 0) {
            $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();

            //到达目的国
            foreach ($trackdetail as $k => $v) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
                if ($count < 1) {
                    Db::name('order_node_courier')->insert($add); //插入物流日志表
                }

                //到达目的国
                if (stripos($v['z'], 'Accepted at USPS Origin Facility') !== false) {
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 11;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 11;
                        $order_node_detail['content'] = $this->str4;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if ($all_num - 1 == $k) {
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();


                        //因为没有匹配上到达目的国，所以根据签收时间-1天就是到达目的国
                        if ($data['e'] == 40 && ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)) {
                            $time = date('Y-m-d H:i', strtotime(($v['a'] . " -1 day")));
                            $update_order_node['order_node'] = 3;
                            $update_order_node['node_type'] = 11;
                            $update_order_node['update_time'] = $time;
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['node_type'] = 11;
                            $order_node_detail['content'] = $this->str4;
                            $order_node_detail['create_time'] = $time;
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            $time = '';
                            $order_node_date['order_node'] = 3;
                            $order_node_date['node_type'] = 11;
                        }

                        if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间 
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                            $order_node_detail['order_node'] = 4;
                            $order_node_detail['node_type'] = $data['e'];
                            switch ($data['e']) {
                                case 30:
                                    $order_node_detail['content'] = $this->str30;
                                    break;
                                case 35:
                                    $order_node_detail['content'] = $this->str35;
                                    break;
                                case 40:
                                    $order_node_detail['content'] = $this->str40;
                                    break;
                                case 50:
                                    $order_node_detail['content'] = $this->str50;
                                    break;
                            }

                            $order_node_detail['create_time'] = $v['a'];
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    //usps_2  加诺
    public function usps_3_data($data, $add)
    {
        //根据出库时间，+1天后就是上网，再+1天就是运输中
        $where['track_number'] = $add['track_number'];
        $where['order_node'] = 2;
        $where['node_type'] = 7;
        $order_node_detail_time = Db::name('order_node_detail')->where($where)->field('create_time')->find();
        $time = date('Y-m-d H:i', strtotime(($order_node_detail_time['create_time'] . " +1 day")));

        $order_node_detail['order_node'] = 3;
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];


        $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();

        if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40) {
            $where['track_number'] = $add['track_number'];
            $where['order_node'] = 3;
            $where['node_type'] = 10;
            $order_node_detail_time = Db::name('order_node_detail')->where($where)->field('create_time')->find();
            $time = date('Y-m-d H:i', strtotime(($order_node_detail_time['create_time'] . " +1 day")));
            $update_order_node['order_node'] = 3;
            $update_order_node['node_type'] = 11;
            $update_order_node['update_time'] = $time;
            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

            $order_node_detail['node_type'] = 11;
            $order_node_detail['content'] = $this->str4;
            $order_node_detail['create_time'] = $time;
            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

            $update_order_node['order_node'] = 4;
            $update_order_node['node_type'] = $data['e'];
            $update_order_node['update_time'] = $data['z0']['a'];
            if ($data['e'] == 40) {
                $update_order_node['signing_time'] = $data['z0']['a']; //更新签收时间 
            }
            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

            $order_node_detail['order_node'] = 4;
            $order_node_detail['node_type'] = $data['e'];
            switch ($data['e']) {
                case 30:
                    $order_node_detail['content'] = $this->str30;
                    break;
                case 35:
                    $order_node_detail['content'] = $this->str35;
                    break;
                case 40:
                    $order_node_detail['content'] = $this->str40;
                    break;
                case 50:
                    $order_node_detail['content'] = $this->str50;
                    break;
            }

            $order_node_detail['create_time'] = $data['z0']['a'];
            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

        }
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
            'cpc' => '03041',
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
     * 跑需求数据
     *
     * @Description
     * @author wpl
     * @since 2020/08/07 09:18:21 
     * @return void
     */
    public function test()
    {
        //查询
        $list = db('it_web_old_demand')->where('status', 7)->select();
        $data = [];
        foreach ($list as $k => $v) {
            if ($v['type'] == 1) {
                $data[$k]['type'] = $v['type'];
            } else {
                $data[$k]['type'] = 2;
            }
            $data[$k]['site'] = $v['site_type'];

            $str = '';
            if ($v['web_designer_group'] == 1 || $v['phper_group'] == 1) {
                $str .= '1,2';
            } elseif ($v['app_group'] == 1) {
                $str .= ',3';
            }
            $data[$k]['site_type'] = $str;
            $data[$k]['status'] = 4;
            $data[$k]['create_time'] = $v['create_time'];
            $data[$k]['entry_user_id'] = $v['entry_user_id'];
            $data[$k]['entry_user_confirm'] = $v['entry_user_confirm'];
            $data[$k]['entry_user_confirm_time'] = $v['entry_user_confirm_time'];
            $data[$k]['copy_to_user_id'] = $v['copy_to_user_id'];
            $data[$k]['title'] = $v['title'];
            $data[$k]['content'] = $v['content'];
            $data[$k]['priority'] = 1;
            //计算周期
            $time = ceil((strtotime($v['all_finish_time']) - strtotime($v['create_time'])) / 86400);
            $data[$k]['node_time'] = $time;
            $data[$k]['start_time'] = $v['create_time'];
            $data[$k]['end_time'] = date('Y-m-d H:i:s', strtotime($v['all_finish_time']) + 7200);
            $data[$k]['pm_audit_status'] = 3;
            $data[$k]['pm_audit_status_time'] = date('Y-m-d H:i:s', strtotime($v['create_time']) + 3600);;
            $data[$k]['pm_confirm'] = 1;
            $data[$k]['pm_confirm_time'] = $v['entry_user_confirm_time'];
            $data[$k]['web_designer_group'] = $v['web_designer_group'];
            $data[$k]['web_designer_complexity'] = $v['web_designer_complexity'];
            $data[$k]['web_designer_user_id'] = $v['web_designer_user_id'];
            $data[$k]['web_designer_expect_time'] = $v['web_designer_expect_time'];
            $data[$k]['web_designer_is_finish'] = $v['web_designer_is_finish'];
            $data[$k]['web_designer_finish_time'] = $v['web_designer_finish_time'];
            $data[$k]['phper_group'] = $v['phper_group'];
            $data[$k]['phper_complexity'] = $v['phper_complexity'];
            $data[$k]['phper_user_id'] = $v['phper_user_id'];
            $data[$k]['phper_expect_time'] = $v['phper_expect_time'];
            $data[$k]['phper_is_finish'] = $v['phper_is_finish'];
            $data[$k]['phper_finish_time'] = $v['phper_finish_time'];
            $data[$k]['app_group'] = $v['app_group'];
            $data[$k]['app_complexity'] = $v['app_complexity'];
            $data[$k]['app_user_id'] = $v['app_user_id'];
            $data[$k]['app_expect_time'] = $v['app_expect_time'];
            $data[$k]['app_is_finish'] = $v['app_is_finish'];
            $data[$k]['app_finish_time'] = $v['app_finish_time'];
            $data[$k]['test_group'] = $v['test_group'];
            $data[$k]['test_confirm_time'] = $v['test_confirm_time'];
            $data[$k]['test_user_id'] = $v['test_user_id'];
            $data[$k]['test_is_finish'] = $v['test_is_finish'];
            $data[$k]['test_finish_time'] = $v['test_finish_time'];
            $data[$k]['test_status'] = 5;
            $data[$k]['develop_finish_status'] = 3;
            $finish_time = max(array($v['web_designer_finish_time'], $v['phper_finish_time'], $v['app_finish_time']));
            $data[$k]['develop_finish_time'] = $finish_time;
            $data[$k]['all_finish_time'] = $v['all_finish_time'];

            $data[$k]['is_small_probability'] = $v['is_small_probability'];
            if ($v['type'] == 3) {
                $data[$k]['is_difficult'] = 1;
            } else {
                $data[$k]['is_difficult'] = 0;
            }

            $data[$k]['is_del'] = $v['is_del'];
        }
        db('it_web_demand')->insertAll($data);
    }

    /*
     * 同步工单支付时间
     * */
    public function work_list_time()
    {
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderList;
        $list = $this->model->field('id,work_platform,platform_order')->where('payment_time', '0000-00-00 00:00:00')->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $value) {
            $info = $this->model->getSkuList($value['work_platform'], $value['platform_order']);
            $payment_time = $info['payment_time'];
            $this->model->where('id', $value['id'])->update(['payment_time' => $payment_time]);
            echo $value['id'] . ' is ok' . "\n";
        }
    }

    /*
     * 同步借出数量
     * */
    public function lendlog_info()
    {
        $list = Db::name('purchase_sample_lendlog_item')->select();
        foreach ($list as $k => $v) {
            $lendinfo = Db::name('purchase_sample_lendlog')->where('id', $v['log_id'])->find();
            $data['status'] = $lendinfo['status'];
            $data['create_user'] = $lendinfo['create_user'];
            $data['createtime'] = $lendinfo['createtime'];
            Db::name('purchase_sample_lendlog_item')->where('id', $v['id'])->update($data);
            echo $v['id'] . "\n";
        }
    }

    //同步track签收数据到fa_order_process
    public function track_process()
    {
        $orderNode = new \app\admin\model\OrderNode;
        $process = new \app\admin\model\order\order\NewOrderProcess;
        //查询order_node表中签收的订单数据
        $where['node_type'] = 40;
        $all_order = $orderNode->where($where)->column('order_number');
        foreach ($all_order as $key => $value) {
            $is_tracking = $process->where('increment_id', $value)->value('is_tracking');
            if ($is_tracking != 5) {
                //更新process表中数据
                $process->where('increment_id', $value)->update(['is_tracking' => 5]);
                echo $value . " is ok" . "\n";
                usleep(10000);
            }
        }
    }

    //产品等级销量数据脚本
    public function product_level_salesnum()
    {
        //查询时间
        $date_time = $this->zeelool->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2021-01-22' and '2021-01-23' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day_supply')->where('day_date', $val['date_time'])->value('id');
            if (!$is_exist) {
                $arr['day_date'] = $val['date_time'];
                $zeelool = $this->getSalesnum(1, $val['date_time']);
                $voogueme = $this->getSalesnum(2, $val['date_time']);
                $nihao = $this->getSalesnum(3, $val['date_time']);
                $arr['sales_num_a1'] = $zeelool[0] + $voogueme[0] + $nihao[0];
                $arr['sales_num_a'] = $zeelool[1] + $voogueme[1] + $nihao[1];
                $arr['sales_num_b'] = $zeelool[2] + $voogueme[2] + $nihao[2];
                $arr['sales_num_c1'] = $zeelool[3] + $voogueme[3] + $nihao[3];
                $arr['sales_num_c'] = $zeelool[4] + $voogueme[4] + $nihao[4];
                $arr['sales_num_d'] = $zeelool[5] + $voogueme[5] + $nihao[5];
                $arr['sales_num_e'] = $zeelool[6] + $voogueme[6] + $nihao[6];
                $arr['sales_num_f'] = $zeelool[7] + $voogueme[7] + $nihao[7];
                Db::name('datacenter_day_supply')->insert($arr);
                echo $val['date_time'] . " is ok" . "\n";
                usleep(10000);
            }
        }
    }

    public function getSalesnum($site, $date)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->productGrade = new \app\admin\model\ProductGrade();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        //所选时间段内有销量的平台sku
        $start = $date;
        $end = $date . ' 23:59:59';
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        $where['o.payment_time'] = ['between', [$start_time, $end_time]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $where['o.site'] = $site;
        $order = $this->order->alias('o')->join('fa_order_item_option i', 'o.entity_id=i.order_id')->field('i.sku,count(*) as count')->where($where)->group('i.sku')->select();
        $grade1 = 0;
        $grade2 = 0;
        $grade3 = 0;
        $grade4 = 0;
        $grade5 = 0;
        $grade6 = 0;
        $grade7 = 0;
        $grade8 = 0;
        foreach ($order as $key => $value) {
            $sku = $this->itemplatformsku->getTrueSku($value['sku'], $site);
            //查询该品的等级
            $grade = $this->productGrade->where('true_sku', $sku)->value('grade');
            switch ($grade) {
                case 'A+':
                    $grade1 += $value['count'];
                    break;
                case 'A':
                    $grade2 += $value['count'];
                    break;
                case 'B':
                    $grade3 += $value['count'];
                    break;
                case 'C+':
                    $grade4 += $value['count'];
                    break;
                case 'C':
                    $grade5 += $value['count'];
                    break;
                case 'D':
                    $grade6 += $value['count'];
                    $grade6 += $value['count'];
                    break;
                case 'E':
                    $grade7 += $value['count'];
                    break;
                case 'F':
                    $grade8 += $value['count'];
                    break;
                default:
                    break;
            }
        }
        $arr = array(
            $grade1, $grade2, $grade3, $grade4, $grade5, $grade6, $grade7, $grade8
        );
        return $arr;

    }

    //订单发出时间脚本
    public function order_send_time()
    {
        $process = new \app\admin\model\order\order\NewOrderProcess;
        $orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        //查询所有订单
        $order = $process->where('order_prescription_type', 0)->column('order_id');
        foreach ($order as $key => $value) {
            $order_type = $orderitemprocess->where('order_id', $value)->column('order_prescription_type');
            if (in_array(3, $order_type)) {
                $type = 3;
            } elseif (in_array(2, $order_type)) {
                $type = 2;
            } else {
                $type = 1;
            }
            $process->where('order_id', $value)->update(['order_prescription_type' => $type]);
            echo $value . ' is ok' . "\n";
            usleep(100000);
        }
    }

    /**
     * 呆滞数据
     */
    public function dull_stock()
    {
        $count = 0;   //总数量
        $count1 = 0;   //低
        $count2 = 0;   //中
        $count3 = 0;    //高
        $total = 0;     //总金额
        $total1 = 0;     //低
        $total2 = 0;     //中
        $total3 = 0;     //高
        $arr1 = array();   //A+
        $arr2 = array();   //A
        $arr3 = array();
        $arr4 = array();
        $arr5 = array();
        $arr6 = array();
        $arr7 = array();
        $arr8 = array();
        $grades = Db::name('product_grade')->field('true_sku,grade')->select();
        foreach ($grades as $key => $value) {
            $this->model = new \app\admin\model\itemmanage\Item;
            $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
            //该品实时库存
            $real_time_stock = $this->model->where('sku', $value['true_sku'])->where('is_del', 1)->where('is_open', 1)->value('sum(stock)-sum(distribution_occupy_stock) as result');
            //该品库存金额
            $sku_amount = $this->item->alias('i')->join('fa_purchase_order_item o', 'i.purchase_id=o.purchase_id and i.sku=o.sku')->where('i.sku', $value['true_sku'])->where('i.library_status', 1)->value('SUM(IF(o.actual_purchase_price != 0,o.actual_purchase_price,o.purchase_price)) as result');
            //实际周转天数
            $sku_info = $this->getSkuSales($value['true_sku']);
            $actual_day = $sku_info['days'] != 0 && $sku_info['count'] != 0 ? round($real_time_stock / ($sku_info['count'] / $sku_info['days']), 2) : 0;
            if ($actual_day > 120 && $actual_day <= 144) {
                $count += $real_time_stock;
                $total += $sku_amount;
                $count1 += $real_time_stock;
                $total1 += $sku_amount;
                if ($value['grade'] == 'A+') {
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['low_stock'] += $real_time_stock;
                    $arr1['low_total'] += $sku_amount;
                } elseif ($value['grade'] == 'A') {
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['low_stock'] += $real_time_stock;
                    $arr2['low_total'] += $sku_amount;
                } elseif ($value['grade'] == 'B') {
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['low_stock'] += $real_time_stock;
                    $arr3['low_total'] += $sku_amount;
                } elseif ($value['grade'] == 'C+') {
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['low_stock'] += $real_time_stock;
                    $arr4['low_total'] += $sku_amount;
                } elseif ($value['grade'] == 'C') {
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['low_stock'] += $real_time_stock;
                    $arr5['low_total'] += $sku_amount;
                } elseif ($value['grade'] == 'D') {
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['low_stock'] += $real_time_stock;
                    $arr6['low_total'] += $sku_amount;
                } elseif ($value['grade'] == 'E') {
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['low_stock'] += $real_time_stock;
                    $arr7['low_total'] += $sku_amount;
                } else {
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['low_stock'] += $real_time_stock;
                    $arr8['low_total'] += $sku_amount;
                }
            } elseif ($actual_day > 144 && $actual_day <= 168) {
                $count += $real_time_stock;
                $total += $sku_amount;
                $count2 += $real_time_stock;
                $total2 += $sku_amount;
                if ($value['grade'] == 'A+') {
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['center_stock'] += $real_time_stock;
                    $arr1['center_total'] += $sku_amount;
                } elseif ($value['grade'] == 'A') {
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['center_stock'] += $real_time_stock;
                    $arr2['center_total'] += $sku_amount;
                } elseif ($value['grade'] == 'B') {
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['center_stock'] += $real_time_stock;
                    $arr3['center_total'] += $sku_amount;
                } elseif ($value['grade'] == 'C+') {
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['center_stock'] += $real_time_stock;
                    $arr4['center_total'] += $sku_amount;
                } elseif ($value['grade'] == 'C') {
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['center_stock'] += $real_time_stock;
                    $arr5['center_total'] += $sku_amount;
                } elseif ($value['grade'] == 'D') {
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['center_stock'] += $real_time_stock;
                    $arr6['center_total'] += $sku_amount;
                } elseif ($value['grade'] == 'E') {
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['center_stock'] += $real_time_stock;
                    $arr7['center_total'] += $sku_amount;
                } else {
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['center_stock'] += $real_time_stock;
                    $arr8['center_total'] += $sku_amount;
                }
            } elseif ($actual_day > 168) {
                $count += $real_time_stock;
                $total += $sku_amount;
                $count3 += $real_time_stock;
                $total3 += $sku_amount;
                if ($value['grade'] == 'A+') {
                    $arr1['stock'] += $real_time_stock;
                    $arr1['total'] += $sku_amount;
                    $arr1['high_stock'] += $real_time_stock;
                    $arr1['high_total'] += $sku_amount;
                } elseif ($value['grade'] == 'A') {
                    $arr2['stock'] += $real_time_stock;
                    $arr2['total'] += $sku_amount;
                    $arr2['high_stock'] += $real_time_stock;
                    $arr2['high_total'] += $sku_amount;
                } elseif ($value['grade'] == 'B') {
                    $arr3['stock'] += $real_time_stock;
                    $arr3['total'] += $sku_amount;
                    $arr3['high_stock'] += $real_time_stock;
                    $arr3['high_total'] += $sku_amount;
                } elseif ($value['grade'] == 'C+') {
                    $arr4['stock'] += $real_time_stock;
                    $arr4['total'] += $sku_amount;
                    $arr4['high_stock'] += $real_time_stock;
                    $arr4['high_total'] += $sku_amount;
                } elseif ($value['grade'] == 'C') {
                    $arr5['stock'] += $real_time_stock;
                    $arr5['total'] += $sku_amount;
                    $arr5['high_stock'] += $real_time_stock;
                    $arr5['high_total'] += $sku_amount;
                } elseif ($value['grade'] == 'D') {
                    $arr6['stock'] += $real_time_stock;
                    $arr6['total'] += $sku_amount;
                    $arr6['high_stock'] += $real_time_stock;
                    $arr6['high_total'] += $sku_amount;
                } elseif ($value['grade'] == 'E') {
                    $arr7['stock'] += $real_time_stock;
                    $arr7['total'] += $sku_amount;
                    $arr7['high_stock'] += $real_time_stock;
                    $arr7['high_total'] += $sku_amount;
                } else {
                    $arr8['stock'] += $real_time_stock;
                    $arr8['total'] += $sku_amount;
                    $arr8['high_stock'] += $real_time_stock;
                    $arr8['high_total'] += $sku_amount;
                }
            }
        }
        $this->productGrade = new \app\admin\model\ProductGrade();
        $gradeSkuStock = $this->productGrade->getSkuStock();
        //计算产品等级的数量
        $a1_stock_num = $gradeSkuStock['aa_stock_num'];
        $a_stock_num = $gradeSkuStock['a_stock_num'];
        $b_stock_num = $gradeSkuStock['b_stock_num'];
        $c1_stock_num = $gradeSkuStock['ca_stock_num'];
        $c_stock_num = $gradeSkuStock['c_stock_num'];
        $d_stock_num = $gradeSkuStock['d_stock_num'];
        $e_stock_num = $gradeSkuStock['e_stock_num'];
        $f_stock_num = $gradeSkuStock['f_stock_num'];

        $date_time = date('Y-m-d', strtotime("-1 day"));
        $arr1['day_date'] = $arr2['day_date'] = $arr3['day_date'] = $arr4['day_date'] = $arr5['day_date'] = $arr6['day_date'] = $arr7['day_date'] = $arr8['day_date'] = $sum['day_date'] = $date_time;
        $arr1['grade'] = 'A+';
        $arr1['stock_rate'] = $a1_stock_num ? round($arr1['stock'] / $a1_stock_num * 100, 2) : 0;
        $arr2['grade'] = 'A';
        $arr2['stock_rate'] = $a_stock_num ? round($arr2['stock'] / $a_stock_num * 100, 2) : 0;
        $arr3['grade'] = 'B';
        $arr3['stock_rate'] = $b_stock_num ? round($arr3['stock'] / $b_stock_num * 100, 2) : 0;
        $arr4['grade'] = 'C+';
        $arr4['stock_rate'] = $c1_stock_num ? round($arr4['stock'] / $c1_stock_num * 100, 2) : 0;
        $arr5['grade'] = 'C';
        $arr5['stock_rate'] = $c_stock_num ? round($arr5['stock'] / $c_stock_num * 100, 2) : 0;
        $arr6['grade'] = 'D';
        $arr6['stock_rate'] = $d_stock_num ? round($arr6['stock'] / $d_stock_num * 100, 2) : 0;
        $arr7['grade'] = 'E';
        $arr7['stock_rate'] = $e_stock_num ? round($arr7['stock'] / $e_stock_num * 100, 2) : 0;
        $arr8['grade'] = 'F';
        $arr8['stock_rate'] = $f_stock_num ? round($arr8['stock'] / $f_stock_num * 100, 2) : 0;
        Db::name('supply_dull_stock')->insert($arr1);
        Db::name('supply_dull_stock')->insert($arr2);
        Db::name('supply_dull_stock')->insert($arr3);
        Db::name('supply_dull_stock')->insert($arr4);
        Db::name('supply_dull_stock')->insert($arr5);
        Db::name('supply_dull_stock')->insert($arr6);
        Db::name('supply_dull_stock')->insert($arr7);
        Db::name('supply_dull_stock')->insert($arr8);
        $sum['grade'] = 'Z';
        $sum['stock'] = $count;
        $sum['total'] = round($total, 2);
        $sum['low_stock'] = $count1;
        $sum['low_total'] = round($total1, 2);
        $sum['center_stock'] = $count2;
        $sum['center_total'] = round($total2, 2);
        $sum['high_stock'] = $count3;
        $sum['high_total'] = round($total3, 2);
        Db::name('supply_dull_stock')->insert($sum);
        echo 'ALL IS OK';
    }

    //获取sku总销量
    public function getSkuSales($sku)
    {
        $days = array();
        //zeelool
        $z_info = $this->getDullStock($sku, 1);
        $sales_num1 = $z_info['sales_num'];
        $days[] = $z_info['days'];
        //voogueme
        $v_info = $this->getDullStock($sku, 2);
        $sales_num2 = $v_info['sales_num'];
        $days[] = $v_info['days'];
        //nihao
        $n_info = $this->getDullStock($sku, 3);
        $sales_num3 = $n_info['sales_num'];
        $days[] = $n_info['days'];
        //meeloog
        $m_info = $this->getDullStock($sku, 4);
        $sales_num4 = $m_info['sales_num'];
        $days[] = $m_info['days'];
        //wesee
        $w_info = $this->getDullStock($sku, 5);
        $sales_num5 = $w_info['sales_num'];
        $days[] = $w_info['days'];
        //amazon
        $a_info = $this->getDullStock($sku, 8);
        $sales_num6 = $a_info['sales_num'];
        $days[] = $a_info['days'];
        //zeelool_es
        $e_sku = $this->getDullStock($sku, 9);
        $sales_num7 = $e_sku['sales_num'];
        $days[] = $e_sku['days'];
        //zeelool_de
        $d_info = $this->getDullStock($sku, 10);
        $sales_num8 = $d_info['sales_num'];
        $days[] = $d_info['days'];
        //zeelool_jp
        $j_info = $this->getDullStock($sku, 11);
        $sales_num9 = $j_info['sales_num'];
        $days[] = $j_info['days'];
        //voogmechic
        $c_info = $this->getDullStock($sku, 12);
        $sales_num10 = $c_info['sales_num'];
        $days[] = $j_info['days'];
        $count = $sales_num1 + $sales_num2 + $sales_num3 + $sales_num4 + $sales_num5 + $sales_num6 + $sales_num7 + $sales_num8 + $sales_num9 + $sales_num10;
        $days = max($days);
        $data = array(
            'count' => $count,
            'days' => $days,
        );
        return $data;
    }

    //查询sku的有效天数的销量和有效天数
    public function getDullStock($sku, $site)
    {
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $date = date('Y-m-d');
        $map['createtime'] = ['<', $date];
        $map['sku'] = $sku;
        $map['site'] = $site;
        $sql = $skuSalesNum->field('sales_num')->where($map)->limit(30)->order('createtime desc')->buildSql();
        $data['sales_num'] = Db::table($sql . ' a')->sum('a.sales_num');
        $days = Db::name('sku_sales_num')->where($map)->count();
        $data['days'] = $days > 30 ? 30 : $days;
        return $data;
    }

    /*
     * 库存台账
     * */
    public function stock_parameter()
    {
        $this->instock = new \app\admin\model\warehouse\Instock;
        $this->outstock = new \app\admin\model\warehouse\Outstock;
        $this->stockparameter = new \app\admin\model\financepurchase\StockParameter;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
        $stimestamp = 1608868800;
        $etimestamp = 1614916800;
        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 循环每天日期
        for ($i = 0; $i < $days; $i++) {
            $start = date('Y-m-d', $stimestamp + (86400 * $i));
            $end = $start . ' 23:59:59';
            //库存主表插入数据
            $stock_data['day_date'] = $start;
            $stockId = $this->stockparameter->insertGetId($stock_data);
            //采购入库数量
            $instock_where['s.status'] = 2;
            $instock_where['s.type_id'] = 1;
            $instock_where['s.check_time'] = ['between', [$start, $end]];
            $instocks = $this->instock->alias('s')->join('fa_check_order c', 'c.id=s.check_id')->join('fa_purchase_order_item oi', 'c.purchase_id=oi.purchase_id')->join('fa_purchase_order o', 'oi.purchase_id=o.id')->where($instock_where)->field('s.id,round(o.purchase_total/oi.purchase_num,2) purchase_price')->select();
            $instock_total = 0; //入库总金额
            foreach ($instocks as $key => $instock) {
                $arr = array();
                $arr['stock_id'] = $stockId;
                $arr['instock_id'] = $instock['id'];
                $arr['type'] = 1;
                $instock_num = Db::name('in_stock_item')->where('in_stock_id', $instock['id'])->sum('in_stock_num');
                $arr['instock_num'] = $instock_num;
                $arr['instock_total'] = round($instock['purchase_price'] * $instock_num, 2);
                $instock_total += $arr['instock_total'];
                Db::name('finance_stock_parameter_item')->insert($arr);
            }
            //判断今天是否有冲减数据
            $start_time = strtotime($start);
            $end_time = strtotime($end);
            $exist_where['create_time'] = ['between', [$start_time, $end_time]];
            $is_exist = Db::name('finance_cost_error')->where($exist_where)->field('id,create_time,purchase_id,total')->select();
            $outstock_total1 = 0;   //出库单出库
            $outstock_total2 = 0;   //订单出库
            /*************出库单出库start**************/
            $bar_where['out_stock_time'] = ['between', [$start, $end]];
            $bar_where['out_stock_id'] = ['<>', 0];
            $bar_where['library_status'] = 2;
            //判断冲减前的出库单出库数量和金额
            $bars = $this->item->where($bar_where)->group('barcode_id')->column('barcode_id');
            foreach ($bars as $bar) {
                $flag = [];
                $flag['stock_id'] = $stockId;
                $flag['bar_id'] = $bar;
                $flag['type'] = 2;
                $bar_items = $this->item->alias('i')->join('fa_purchase_order_item p', 'i.purchase_id=p.purchase_id and i.sku=p.sku')->join('fa_purchase_order o', 'p.purchase_id=o.id')->field('i.out_stock_id,i.purchase_id,i.out_stock_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_price')->where($bar_where)->where('barcode_id', $bar)->select();
                $sum_count = 0;
                $sum_total = 0;
                foreach ($bar_items as $item) {
                    if (count(array_unique($is_exist)) != 0) {
                        foreach ($is_exist as $value) {
                            if ($item['purchase_id'] == $value['purchase_id']) {
                                $end_date = date('Y-m-d H:i:s', $value['create_time']);
                                if ($item['out_stock_time'] >= $end_date) {
                                    //使用成本计算
                                    $total = $item['actual_purchase_price'];
                                } else {
                                    //使用预估计算
                                    $total = $item['purchase_price'];
                                }
                            } else {
                                //没有冲减数据，直接拿预估成本计算
                                if ($item['actual_purchase_price'] != 0) {
                                    $total = $item['actual_purchase_price'];   //有成本价拿成本价计算
                                } else {
                                    $total = $item['purchase_price'];   //没有成本价拿预估价计算
                                }
                            }
                        }
                    } else {
                        //没有冲减数据，直接拿预估成本计算
                        if ($item['actual_purchase_price'] != 0) {
                            $total = $item['actual_purchase_price'];   //有成本价拿成本价计算
                        } else {
                            $total = $item['purchase_price'];   //没有成本价拿预估价计算
                        }
                    }
                    $sum_total += $total;
                    $sum_count++;
                }
                $flag['outstock_count'] = $sum_count;
                $flag['outstock_total'] = $sum_total;
                $outstock_total1 += $sum_total;
                Db::name('finance_stock_parameter_item')->insert($flag);
            }
            /*************出库单出库end**************/
            /*************订单出库start**************/
            $bar_where1['out_stock_time'] = ['between', [$start, $end]];
            $bar_where1['out_stock_id'] = 0;
            $bar_where1['item_order_number'] = ['<>', ''];
            $bar_where1['library_status'] = 2;
            //判断冲减前的出库单出库数量和金额
            $bars1 = $this->item->alias('i')->join('fa_purchase_order_item p', 'i.purchase_id=p.purchase_id and i.sku=p.sku')->join('fa_purchase_order o', 'p.purchase_id=o.id')->where($bar_where1)->field('i.out_stock_id,i.purchase_id,i.out_stock_time,p.actual_purchase_price,round(o.purchase_total/p.purchase_num,2) purchase_price')->select();
            if (count($bars1) != 0) {
                $flag1 = [];
                $flag1['stock_id'] = $stockId;
                $flag1['type'] = 3;
                foreach ($bars1 as $bar1) {
                    if (count(array_unique($is_exist)) != 0) {
                        foreach ($is_exist as $value) {
                            if ($bar1['purchase_id'] == $value['purchase_id']) {
                                $end_date = date('Y-m-d H:i:s', $value['create_time']);
                                if ($bar1['out_stock_time'] >= $end_date) {
                                    //使用成本计算
                                    $total1 = $bar1['actual_purchase_price'];
                                } else {
                                    //使用预估计算
                                    $total1 = $bar1['purchase_price'];
                                }
                            } else {
                                //没有冲减数据，直接拿预估成本计算
                                if ($bar1['actual_purchase_price'] != 0) {
                                    $total1 = $bar1['actual_purchase_price'];   //有成本价拿成本价计算
                                } else {
                                    $total1 = $bar1['purchase_price'];   //没有成本价拿预估价计算
                                }
                            }
                        }
                    } else {
                        if ($bar1['actual_purchase_price'] != 0) {
                            $total1 = $bar1['actual_purchase_price'];   //有成本价拿成本价计算
                        } else {
                            $total1 = $bar1['purchase_price'];   //没有成本价拿预估价计算
                        }
                    }
                    $outstock_total2 += $total1;
                }
                $flag1['outstock_count'] = count($bars1);
                $flag1['outstock_total'] = $outstock_total2;
                Db::name('finance_stock_parameter_item')->insert($flag1);
            }
            /*************订单出库end**************/
            //查询最新一条的余额
            $rest_total = $this->stockparameter->order('id', 'desc')->field('rest_total')->limit(1, 1)->select();
            $cha_amount = 0;
            foreach ($is_exist as $k => $v) {
                $cha_amount += $v['total'];
            }
            $end_rest = round($rest_total[0]['rest_total'] + $instock_total - $outstock_total1 - $outstock_total2 + $cha_amount, 2);
            $info['instock_total'] = $instock_total;
            $info['outstock_total'] = round($outstock_total1 + $outstock_total2, 2);
            $info['rest_total'] = $end_rest;
            $this->stockparameter->where('id', $stockId)->update($info);
            echo $start . ' is ok' . "\n";
            usleep(100000);
        }
        echo "all is ok";
    }


    /**
     * 呆滞数据
     */
    public function dull_stock1()
    {
        $grades = Db::name('product_grade')->field('true_sku,grade')->select();
        foreach ($grades as $key => $value) {
            $this->model = new \app\admin\model\itemmanage\Item;
            $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
            //该品实时库存
            $real_time_stock = $this->model->where('sku', $value['true_sku'])->where('is_del', 1)->where('is_open', 1)->value('sum(stock)-sum(distribution_occupy_stock) as result');
            //该品库存金额
            $sku_amount = $this->item->alias('i')->join('fa_purchase_order_item o', 'i.purchase_id=o.purchase_id and i.sku=o.sku')->where('i.sku', $value['true_sku'])->where('i.library_status', 1)->value('SUM(IF(o.actual_purchase_price != 0,o.actual_purchase_price,o.purchase_price)) as result');
            //实际周转天数
            $sku_info = $this->getSkuSales($value['true_sku']);
            $actual_day = $sku_info['days'] != 0 && $sku_info['count'] != 0 ? round($real_time_stock / ($sku_info['count'] / $sku_info['days']), 2) : 0;
            $data['sku'] = $value['true_sku'];
            $data['grade'] = $value['grade'];
            $data['sales_num'] = $sku_info['count'];
            $data['day'] = $sku_info['days'];
            $data['stock'] = $real_time_stock;
            $data['total'] = $sku_amount ? $sku_amount : 0;
            $data['actual_day'] = $actual_day;
            Db::name('ceshi')->insert($data);
            echo $value['true_sku'] . ' is ok' . "\n";
            usleep(10000);
        }
    }

    /**
     * 邮件排查没有回复状态为关闭的邮件
     */
    public function zendesk_error_assign_email()
    {
        $this->zendesk = new \app\admin\model\zendesk\Zendesk;
        $where['channel'] = ['<>', 'voice'];
        $start = '2021-01-01';
        $end = '2021-01-31 23:59:59';
        $where['create_time'] = ['between', [$start, $end]];
        $email = $this->zendesk->where('assign_id  is null or assign_id=0')->where($where)->select();
        foreach ($email as $key => $value) {
            //判断是否只有用户发送的邮件
            $count = Db::name('zendesk_comments')->where('zid', $value['id'])->where('is_admin', 1)->count();
            if ($count == 0) {
                $data['zid'] = $value['id'];
                $data['ticket_id'] = $value['ticket_id'];
                $data['site'] = $value['type'];
                //判断是否有合并邮件
                $zemail = Db::name('zendesk_comments')->where('zid', $value['id'])->where('is_admin', 0)->field('html_body')->select();
                $i = 0;
                foreach ($zemail as $k => $v) {
                    $str = strtolower($v['html_body']);
                    if (strpos($str, 'merged') !== false) {
                        $i++;
                    }
                }
                if ($i > 0) {
                    $data['type'] = 1;
                } else {
                    $data['type'] = 0;
                }
                Db::name('ceshi')->insert($data);
                echo $value['ticket_id'] . ' is ok ' . "\n";
                usleep(10000);
            }

        }
    }

    //及时率中订单数
    public function order_num()
    {
        $order = new \app\admin\model\order\order\NewOrder();
        $this->process = new \app\admin\model\order\order\NewOrderProcess;
        $date_time = $order->query("SELECT FROM_UNIXTIME(created_at, '%Y-%m-%d') AS date_time FROM `fa_order` where payment_time between 1515520244 and 1612375200 GROUP BY FROM_UNIXTIME(created_at, '%Y-%m-%d') order by FROM_UNIXTIME(created_at, '%Y-%m-%d') asc");
        //查询时间
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day_order')->where('day_date', $val['date_time'])->value('id');
            if (!$is_exist) {
                $arr = [];
                $arr['day_date'] = $val['date_time'];
                //订单数
                $start = strtotime($val['date_time']);
                $end = strtotime($val['date_time'] . ' 23:59:59');
                $where['o.payment_time'] = ['between', [$start, $end]];
                $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
                $arr['order_num'] = $order->alias('o')->where($where)->count();

                $map1['p.order_prescription_type'] = 1;
                $map2['p.order_prescription_type'] = 2;
                $map3['p.order_prescription_type'] = 3;

                $sql1 = $this->process->alias('p')->join('fa_order o', 'p.increment_id = o.increment_id')->field('(p.delivery_time-o.payment_time)/3600 AS total')->where($where)->where($map1)->group('p.order_id')->buildSql();
                $count1 = $this->process->table([$sql1 => 't2'])->value('sum( IF ( total <= 24, 1, 0) ) AS a');

                $sql2 = $this->process->alias('p')->join('fa_order o', 'p.increment_id = o.increment_id')->field('(p.delivery_time-o.payment_time)/3600 AS total')->where($where)->where($map2)->group('p.order_id')->buildSql();
                $count2 = $this->process->table([$sql2 => 't2'])->value('sum( IF ( total <= 72, 1, 0) ) AS a');

                $sql3 = $this->process->alias('p')->join('fa_order o', 'p.increment_id = o.increment_id')->field('(p.delivery_time-o.payment_time)/3600 AS total')->where($where)->where($map3)->group('p.order_id')->buildSql();
                $count3 = $this->process->table([$sql3 => 't2'])->value('sum( IF ( total <= 168, 1, 0) ) AS a');
                $untimeout_count = $count1 + $count2 + $count3;
                $arr['intime_rate'] = $arr['order_num'] ? round($untimeout_count / $arr['order_num'] * 100, 2) : 0;
                Db::name('datacenter_day_order')->insert($arr);
                echo $val['date_time'] . ' is ok' . "\n";
                usleep(10000);
            }
        }
    }

    //物流概况每天发货数量
    public function send_logistics_num()
    {
        $ordernode = new \app\admin\model\OrderNode();
        $date_time = Db::name('datacenter_day_order')->where('day_date', '>', '2020-04-01')->order('day_date', 'asc')->select();
        //查询时间
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day_order')->where('day_date', $val['day_date'])->value('id');
            if ($is_exist) {
                $arr = [];
                $arr['day_date'] = $val['day_date'];
                //发送订单数
                $start = $val['day_date'];
                $end = $val['day_date'] . ' 23:59:59';
                $where['delivery_time'] = ['between', [$start, $end]];
                $arr['send_num'] = $ordernode->where($where)->count();

                $sql1 = $this->ordernode->field('(UNIX_TIMESTAMP(signing_time)-UNIX_TIMESTAMP(delivery_time))/3600/24 AS total')->where($where)->group('order_id')->buildSql();
                $count = $this->ordernode->table([$sql1 => 't2'])->value('sum( IF ( total <= 15, 1, 0) ) AS a');

                $arr['logistics_rate'] = $arr['send_num'] ? round($count / $arr['send_num'] * 100, 2) : 0;
                Db::name('datacenter_day_order')->where('day_date', $val['day_date'])->update($arr);
                echo $val['day_date'] . ' is ok' . "\n";
                usleep(10000);
            }
        }
    }

    //每月总库存、呆滞库存数据
    public function supply_month_data()
    {
        $this->productAllStockLog = new \app\admin\model\ProductAllStock();
        $this->dullstock = new \app\admin\model\supplydatacenter\DullStock();
        $start = date('Y-m', strtotime('-12 months'));
        $end = date('Y-m', strtotime('-2 months'));
        while ($end > $start) {
            $endmonth = $start;
            $start = date('Y-m', strtotime("$endmonth +1 month"));
            $startday = $start . '-01';
            $endday = $start . '-' . date('t', strtotime($startday));
            $start_stock = $this->productAllStockLog->where("DATE_FORMAT(createtime,'%Y-%m-%d')='$startday'")->field('id,allnum')->find();
            //判断是否有月初数据
            if ($start_stock['id']) {
                //判断是否有月末数据
                $end_stock = $this->productAllStockLog->where("DATE_FORMAT(createtime,'%Y-%m-%d')='$endday'")->field('id,allnum')->find();
                if ($end_stock['id']) {
                    //如果有月末数据，（月初数据+月末数据）/2
                    $stock = round(($start_stock['allnum'] + $end_stock['allnum']) / 2, 0);
                    $arr['day_date'] = $start;
                    $arr['avg_stock'] = $stock;
                    Db::name('datacenter_supply_month')->insert($arr);
                    echo $start . " is ok" . "\n";
                }
            }
            //获取当前上个月份的库存数据
            $stock_info = Db::name('datacenter_supply_month')->where('day_date', $start)->field('id,avg_stock')->find();
            $map['create_time'] = ['between', [$startday . ' 00:00:00', $endday . ' 23:59:59']];
            $where['payment_time'] = ['between', [strtotime($startday . ' 00:00:00'), strtotime($endday . ' 23:59:59')]];
            $order = new \app\admin\model\order\order\NewOrder();
            if ($stock_info['id']) {
                //上个月总的采购数量（副数）
                $purchase_num = Db::name('warehouse_data')->where($map)->sum('all_purchase_num');

                //上个月总的销售数量（副数）
                $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
                $sales_num = $order->where($where)->sum('total_qty_ordered');
                $arr2['purchase_num'] = $purchase_num;
                $arr2['sales_num'] = $sales_num;
                $arr2['purchase_sales_rate'] = $sales_num != 0 ? round($purchase_num / $sales_num * 100, 2) : 0;
                Db::name('datacenter_supply_month')->where('day_date', $start)->update($arr2);
            } else {
                //上个月总的采购数量（副数）
                $purchase_num = Db::name('warehouse_data')->where($map)->sum('all_purchase_num');
                //上个月总的销售数量（副数）
                $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
                $sales_num = $order->where($where)->sum('total_qty_ordered');
                $arr3['purchase_num'] = $purchase_num;
                $arr3['sales_num'] = $sales_num;
                $arr3['purchase_sales_rate'] = $sales_num != 0 ? round($purchase_num / $sales_num * 100, 2) : 0;
                $arr3['day_date'] = $start;
                Db::name('datacenter_supply_month')->insert($arr3);
                echo $start . " is ok" . "\n";
            }
            $start_dull_stock = $this->dullstock->where("DATE_FORMAT(day_date,'%Y-%m-%d')='$startday'")->where('grade', 'Z')->field('id,stock')->find();
            //判断是否有月初数据
            if ($start_dull_stock['id']) {
                //判断是否有月末数据
                $end_dull_stock = $this->dullstock->where("DATE_FORMAT(day_date,'%Y-%m-%d')='$endday'")->where('grade', 'Z')->field('id,stock')->find();
                if ($end_dull_stock['id']) {
                    $stock_info1 = Db::name('datacenter_supply_month')->where('day_date', $start)->field('id,avg_stock')->find();
                    //如果有月末数据，（月初数据+月末数据）/2
                    $dull_stock = round(($start_dull_stock['stock'] + $end_dull_stock['stock']) / 2, 2);
                    $arr1['avg_dull_stock'] = $dull_stock;
                    $arr1['avg_rate'] = $stock_info1['avg_stock'] ? round($arr1['avg_dull_stock'] / $stock_info1['avg_stock'] * 100, 2) : 0;
                    Db::name('datacenter_supply_month')->where('id', $stock_info1['id'])->update($arr1);
                }
            }
            usleep(10000);
        }
    }

    public function add_plat()
    {
        $res = Db::name('magento_platform')->where(['id' => 13])->update(['name' => 'zeelool_cn', 'prefix' => 'B', 'create_time' => time()]);
        $res = Db::name('magento_platform')->where(['id' => 14])->update(['name' => 'alibaba', 'prefix' => 'L', 'create_time' => time()]);
    }

    public function product_bar_code_warehouse()
    {
        $store_sku = Db::name('store_sku')
            ->alias('a')
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
            ->where('a.is_del', 1)
            ->field('a.sku,a.store_id,b.id,b.coding,b.area_id')
            ->select();
        foreach ($store_sku as $k => $v) {
            $res = Db::name('product_barcode_item')->where('sku', $v['sku'])->update(['location_code' => $v['coding'], 'location_id' => $v['area_id'], 'location_code_id' => $v['store_id']]);
            echo $v['sku'] . '更新' . $res . '条数据 is ok' . "\n";
            usleep(10000);
        }
    }

    public function store_sku()
    {
        $res = Db::name('store_sku')->where('id', '>', 4629)->delete();
        $data = Db::name('zzzz_temp')->where('id', '>', 0)->field('sku,product_number')->select();
        $store_house = Db::name('store_house')->column('id', 'coding');
        foreach ($data as $k => $v) {
            $data[$k]['store_id'] = $store_house[$v['product_number']];
            Db::name('store_sku')->insert(['sku' => $v['sku'], 'store_id' => $data[$k]['store_id'], 'create_person' => 'Admin', 'createtime' => date("Y-m-d H:i:s")]);
        }
    }

    public function update_purchase_order()
    {
        $arr = ['PO20210315162344924696', 'PO20210315162215645784', 'PO20210315161228347829', 'PO20210315161000139240', 'PO20210315165346719168', 'PO20210315165153941625', 'PO20210315165020644213', 'PO20210315164835238322',
            'PO20210315164722739902', 'PO20210315164147826371', 'PO20210315163755709146', 'PO20210315163110907985', 'PO20210315162728388129', 'PO20210315172414645839'];
        foreach ($arr as $k => $v) {
            $res = Db::name('purchase_order')->where('purchase_number', $v)->update(['type' => 1]);
            dump($res);
        }

    }

    public function sku_code_review()
    {
        $skus = ['E10005-2','E10009-2','E10019-1','E10045-1','E10055-1','E10060-1','E10061-1','E10061-2','E10085-1','E10087-1','E20008-1','E20014-1','E20026-1','E20034-1','E20041-1','E50002-1','E50003-1','E50005-1','N10003-1','N10008-1','N10011-1','N10016-1','NBY001-1','NBY003-1','Box'];
        foreach ($skus as $k=>$v){
            $store_sku = Db::name('store_sku')
                ->alias('a')
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->where('a.is_del', 1)
                ->where('a.sku', $v)
                ->field('a.sku,a.store_id,b.id,b.coding,b.area_id')
                ->find();
            $res = Db::name('product_barcode_item')->where('sku', $store_sku['sku'])->update(['location_code' => $store_sku['coding'], 'location_id' => $store_sku['area_id'], 'location_code_id' => $store_sku['store_id']]);
            echo $store_sku['sku'] . '更新' . $res . '条数据 is ok' . "\n";
            usleep(10000);
        }
    }

}
