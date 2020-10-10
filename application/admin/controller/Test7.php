<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;
use GuzzleHttp\Client;
use think\Db;

class Test7 extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
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

    public function test001()
    {
        echo 111;die;
    }


    //运营数据中心
    public function zeelool_operate_data_center()
    {
        $zeelool_model = Db::connect('database.db_zeelool_online');
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $zeelool_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->zeelool->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2019-09-01' and '2020-10-11' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->value('id');
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
                $arr['order_num'] = $this->zeelool->where($order_where)->count();
                //销售额
                $arr['sales_total_money'] = $this->zeelool->where($order_where)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->zeelool->where($order_where)->sum('base_shipping_amount');
                //购买人数
                $order_user = $this->zeelool->where($order_where)->count('distinct customer_id');
                //客单价
                $arr['order_unit_price'] = $order_user ? round($arr['sales_total_money'] / $order_user, 2) : 0;
                //会话
                $arr['sessions'] = $this->google_session(1, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
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
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }
    //运营数据中心
    public function voogueme01()
    {
        $voogueme_model = Db::connect('database.db_voogueme_online');
        $voogueme_model->table('customer_entity')->query("set time_zone='+8:00'");
        $voogueme_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->voogueme->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2018-01-01' and '2020-10-11' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->value('id');
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
                $arr['order_num'] = $this->voogueme->where($order_where)->count();
                //销售额
                $arr['sales_total_money'] = $this->voogueme->where($order_where)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->voogueme->where($order_where)->sum('base_shipping_amount');
                //购买人数
                $order_user = $this->voogueme->where($order_where)->count('distinct customer_id');
                //客单价
                $arr['order_unit_price'] = $order_user ? round($arr['sales_total_money'] / $order_user, 2) : 0;
                //会话
                $arr['sessions'] = $this->google_session(2, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $voogueme_model->table('sales_flat_quote')->where($cart_where1)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $voogueme_model->table('sales_flat_quote')->where($cart_where2)->count();
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
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }
    //运营数据中心
    public function nihao01()
    {
        $nihao_model = Db::connect('database.db_nihao_online');
        $nihao_model->table('customer_entity')->query("set time_zone='+8:00'");
        $nihao_model->table('oc_vip_order')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_quote')->query("set time_zone='+8:00'");

        //查询时间
        $date_time = $this->nihao->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2018-01-01' and '2020-10-11' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->value('id');
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
                //新增vip用户数
                $vip_where = [];
                $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $vip_where['order_status'] = 'Success';
                $arr['vip_user_num'] = $nihao_model->table('oc_vip_order')->where($vip_where)->count();
                //订单数
                $order_where = [];
                $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $arr['order_num'] = $this->nihao->where($order_where)->count();
                //销售额
                $arr['sales_total_money'] = $this->nihao->where($order_where)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->nihao->where($order_where)->sum('base_shipping_amount');
                //购买人数
                $order_user = $this->nihao->where($order_where)->count('distinct customer_id');
                //客单价
                $arr['order_unit_price'] = $order_user ? round($arr['sales_total_money'] / $order_user, 2) : 0;
                //会话
                $arr['sessions'] = $this->google_session(3, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $nihao_model->table('sales_flat_quote')->where($cart_where1)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $nihao_model->table('sales_flat_quote')->where($cart_where2)->count();
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
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }
}
