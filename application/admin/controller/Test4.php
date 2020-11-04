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
            $is_exist = Db::name('datacenter_day')->where('day_date', $val['date_time'])->where('site',1)->value('id');
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
                $arr['order_num'] = $this->zeelool->where($order_where)->where('order_type',1)->count();
                //销售额
                $arr['sales_total_money'] = $this->zeelool->where($order_where)->where('order_type',1)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->zeelool->where($order_where)->where('order_type',1)->sum('base_shipping_amount');
                //客单价
                $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
                //中位数
                $sales_total_money = $this->zeelool->where($order_where)->where('order_type',1)->column('base_grand_total');
                $arr['order_total_midnum'] = $this->median($sales_total_money);
                //标准差
                $arr['order_total_standard'] = $this->getVariance($sales_total_money);
                //补发订单数
                $arr['replacement_order_num'] = $this->zeelool->where($order_where)->where('order_type',4)->count();
                //补发销售额
                $arr['replacement_order_total'] = $this->zeelool->where($order_where)->where('order_type',4)->sum('base_grand_total');
                //网红订单数
                $arr['online_celebrity_order_num'] = $this->zeelool->where($order_where)->where('order_type',3)->count();
                //补发销售额
                $arr['online_celebrity_order_total'] = $this->zeelool->where($order_where)->where('order_type',3)->sum('base_grand_total');
                //会话
                $arr['sessions'] = $this->google_session(1, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total','gt',0)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $zeelool_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total','gt',0)->count();
                //新增加购率
                $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions']*100, 2) : 0;
                //更新加购率
                $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions']*100, 2) : 0;
                //新增购物车转化率
                $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num']*100, 2) : 0;
                //更新购物车转化率
                $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num']*100, 2) : 0;
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
                $arr['order_num'] = $this->voogueme->where($order_where)->where('order_type',1)->count();
                //销售额
                $arr['sales_total_money'] = $this->voogueme->where($order_where)->where('order_type',1)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->voogueme->where($order_where)->where('order_type',1)->sum('base_shipping_amount');
                //客单价
                $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
                //中位数
                $sales_total_money = $this->voogueme->where($order_where)->where('order_type',1)->column('base_grand_total');
                $arr['order_total_midnum'] = $this->median($sales_total_money);
                //标准差
                $arr['order_total_standard'] = $this->getVariance($sales_total_money);
                //补发订单数
                $arr['replacement_order_num'] = $this->voogueme->where($order_where)->where('order_type',4)->count();
                //补发销售额
                $arr['replacement_order_total'] = $this->voogueme->where($order_where)->where('order_type',4)->sum('base_grand_total');
                //网红订单数
                $arr['online_celebrity_order_num'] = $this->voogueme->where($order_where)->where('order_type',3)->count();
                //补发销售额
                $arr['online_celebrity_order_total'] = $this->voogueme->where($order_where)->where('order_type',3)->sum('base_grand_total');
                //会话
                $arr['sessions'] = $this->google_session(2, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $voogueme_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total','gt',0)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $voogueme_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total','gt',0)->count();
                //新增加购率
                $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions']*100, 2) : 0;
                //更新加购率
                $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions']*100, 2) : 0;
                //新增购物车转化率
                $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num']*100, 2) : 0;
                //更新购物车转化率
                $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num']*100, 2) : 0;
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
                $arr['order_num'] = $this->nihao->where($order_where)->where('order_type',1)->count();
                //销售额
                $arr['sales_total_money'] = $this->nihao->where($order_where)->where('order_type',1)->sum('base_grand_total');
                //邮费
                $arr['shipping_total_money'] = $this->nihao->where($order_where)->where('order_type',1)->sum('base_shipping_amount');
                //客单价
                $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $arr['order_num'], 2) : 0;
                //中位数
                $sales_total_money = $this->nihao->where($order_where)->where('order_type',1)->column('base_grand_total');
                $arr['order_total_midnum'] = $this->median($sales_total_money);
                //标准差
                $arr['order_total_standard'] = $this->getVariance($sales_total_money);
                //补发订单数
                $arr['replacement_order_num'] = $this->nihao->where($order_where)->where('order_type',4)->count();
                //补发销售额
                $arr['replacement_order_total'] = $this->nihao->where($order_where)->where('order_type',4)->sum('base_grand_total');
                //网红订单数
                $arr['online_celebrity_order_num'] = $this->nihao->where($order_where)->where('order_type',3)->count();
                //补发销售额
                $arr['online_celebrity_order_total'] = $this->nihao->where($order_where)->where('order_type',3)->sum('base_grand_total');
                //会话
                $arr['sessions'] = $this->google_session(3, $val['date_time']);
                //新建购物车数量
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['new_cart_num'] = $nihao_model->table('sales_flat_quote')->where($cart_where1)->where('base_grand_total','gt',0)->count();
                //更新购物车数量
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['date_time'] . "'")];
                $arr['update_cart_num'] = $nihao_model->table('sales_flat_quote')->where($cart_where2)->where('base_grand_total','gt',0)->count();
                //新增加购率
                $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions']*100, 2) : 0;
                //更新加购率
                $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions']*100, 2) : 0;
                //新增购物车转化率
                $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num']*100, 2) : 0;
                //更新购物车转化率
                $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num']*100, 2) : 0;
                //插入数据
                Db::name('datacenter_day')->insert($arr);
                echo $val['date_time'] . "\n";
                usleep(100000);
            }
        }
    }
    //更新运营数据中心
    public function zeelool_operate_data_center_update()
    {
        $model = Db::connect('database.db_zeelool_online');
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $date_time = Db::name('datacenter_day')->where('site',1)->field('id,day_date,sessions,order_num,new_cart_num,update_cart_num')->order('id asc')->select();
        foreach ($date_time as $val) {
            $arr = [];
            $order_where = [];
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $arr['sum_order_num'] = $model->table('sales_flat_order')->where($order_where)->where('order_type',1)->count();
            $customer_where = [];
            $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $arr['login_user_num'] = $model->table('customer_entity')->where($customer_where)->count();
            //更新数据
            Db::name('datacenter_day')->where('id',$val['id'])->update($arr);
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
        $date_time = Db::name('datacenter_day')->where('site',2)->field('id,day_date,sessions,order_num,new_cart_num,update_cart_num')->order('id asc')->select();
        foreach ($date_time as $val) {
            $arr = [];
            $order_where = [];
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $arr['sum_order_num'] = $model->table('sales_flat_order')->where($order_where)->where('order_type',1)->count();
            $customer_where = [];
            $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $arr['login_user_num'] = $model->table('customer_entity')->where($customer_where)->count();

            //更新数据
            Db::name('datacenter_day')->where('id',$val['id'])->update($arr);
            echo $val['day_date'] . "\n";
            usleep(100000);
        }
    }
    //更新运营数据中心
    public function nihao_operate_data_center_update()
    {
        $model = Db::connect('database.db_nihao_online');
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $date_time = Db::name('datacenter_day')->where('site',3)->field('id,day_date,sessions,order_num,new_cart_num,update_cart_num')->order('id asc')->select();
        foreach ($date_time as $val) {
            $arr = [];
            $order_where = [];
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $arr['sum_order_num'] = $model->table('sales_flat_order')->where($order_where)->where('order_type',1)->count();
            $customer_where = [];
            $customer_where[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $val['day_date'] . "'")];
            $arr['login_user_num'] = $model->table('customer_entity')->where($customer_where)->count();
            //更新数据
            Db::name('datacenter_day')->where('id',$val['id'])->update($arr);
            echo $val['day_date'] . "\n";
            usleep(100000);
        }
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
    function getVariance($arr) {
        $length = count($arr);
        if ($length == 0) {
            return 0;
        }
        $average = array_sum($arr)/$length;
        $count = 0;
        foreach ($arr as $v) {
            $count += pow($average-$v, 2);
        }
        $variance = $count/$length;
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
        $purchase_id = $logistics->where(['status' => 1])->column('purchase_id');
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
        echo  $res;
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
                $zeelool_stock = $data[$v['sku']]['zeelool']  > 0 ? ceil($v['available_stock'] * $data[$v['sku']]['zeelool'] / 100) : 0;
                if (($v['available_stock'] - $zeelool_stock) > 0) {
                    $voogueme_stock = ($v['available_stock'] - $zeelool_stock)  > ceil($v['available_stock'] * $data[$v['sku']]['voogueme'] / 100) ? ceil($v['available_stock'] * $data[$v['sku']]['voogueme'] / 100) : ($v['available_stock'] - $zeelool_stock);
                }

                if (($v['available_stock'] - $zeelool_stock - $voogueme_stock) > 0) {
                    $nihao_stock = ($v['available_stock'] - $zeelool_stock - $voogueme_stock)  > ceil($v['available_stock'] * $data[$v['sku']]['nihao'] / 100) ? ceil($v['available_stock'] * $data[$v['sku']]['nihao'] / 100) : ($v['available_stock'] - $zeelool_stock - $voogueme_stock);
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

    /************************跑库存数据用START**********************************/
    //导入实时库存 第一步
    public function set_product_relstock()
    {
        // $skus = [

        // ];
        $list = Db::table('fa_zz_temp2')->select();

        foreach ($list as $k => $v) {
            $p_map['sku'] = $v['sku'];
            $data['real_time_qty'] = $v['stock'];
            $res = $this->item->where($p_map)->update($data);
        }
        echo 'ok';
        die;
    }

    /**
     * 统计配货占用 第二步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_process()
    {
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;

        $skus = Db::table('fa_zz_temp2')->column('sku');

        foreach ($skus as $k => $v) {
            $map = [];
            $zeelool_sku = $this->itemplatformsku->getWebSku($v, 1);
            $voogueme_sku = $this->itemplatformsku->getWebSku($v, 2);
            $nihao_sku = $this->itemplatformsku->getWebSku($v, 3);
            $wesee_sku = $this->itemplatformsku->getWebSku($v, 5);
            $meeloog_sku = $this->itemplatformsku->getWebSku($v, 4);

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['custom_is_delivery_new'] = 0; //是否提货
            $map['custom_is_match_frame_new'] = 1; //是否配镜架
            $map['a.created_at'] = ['between', ['2020-01-01 00:00:00', date('Y-m-d H:i:s')]]; //时间节点
            $map['sku'] = $zeelool_sku;
            $zeelool_qty = $this->zeelool->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $voogueme_sku;
            $voogueme_qty = $this->voogueme->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $nihao_sku;
            $nihao_qty = $this->nihao->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $wesee_sku;
            $weseeoptical_qty = $this->weseeoptical->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $meeloog_sku;
            $map['custom_is_delivery'] = 0; //是否提货
            $map['custom_is_match_frame'] = 1; //是否配镜架
            unset($map['custom_is_delivery_new']);
            unset($map['custom_is_match_frame_new']);
            $meeloog_qty = $this->meeloog->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $p_map['sku'] = $v;
            $data['distribution_occupy_stock'] = $zeelool_qty + $voogueme_qty + $nihao_qty + $weseeoptical_qty + $meeloog_qty;

            $res = $this->item->where($p_map)->update($data);

            echo $k . "\n";
            usleep(200000);
        }

        echo 'ok';
        die;
    }


    /************************跑库存数据用END**********************************/

    /**
     * 订单占用 第三步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_process_order()
    {
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $skus = Db::table('fa_zz_temp2')->column('sku');
        // $skus = [

        // ];
        foreach ($skus as $k => $v) {
            $map = [];
            $zeelool_sku = $this->itemplatformsku->getWebSku($v, 1);
            $voogueme_sku = $this->itemplatformsku->getWebSku($v, 2);
            $nihao_sku = $this->itemplatformsku->getWebSku($v, 3);
            $wesee_sku = $this->itemplatformsku->getWebSku($v, 5);
            $meeloog_sku = $this->itemplatformsku->getWebSku($v, 4);

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['custom_is_delivery_new'] = 0; //是否提货
            $map['a.created_at'] = ['between', ['2020-01-01 00:00:00', date('Y-m-d H:i:s')]]; //时间节点
            $map['sku'] = $zeelool_sku;
            $zeelool_qty = $this->zeelool->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $voogueme_sku;
            $voogueme_qty = $this->voogueme->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $nihao_sku;
            $nihao_qty = $this->nihao->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $wesee_sku;
            $weseeoptical_qty = $this->weseeoptical->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $meeloog_sku;
            $map['custom_is_delivery'] = 0; //是否提货
            unset($map['custom_is_delivery_new']);
            $meeloog_qty = $this->meeloog->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $p_map['sku'] = $v;
            $data['occupy_stock'] = $zeelool_qty + $voogueme_qty + $nihao_qty + $weseeoptical_qty + $meeloog_qty;
            $res = $this->item->where($p_map)->update($data);

            echo $k . "\n";
            usleep(200000);
        }
        echo 'ok';
        die;
    }

    /**
     * 订单占用 第四步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_sotck()
    {

        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;

        $skus = Db::table('fa_zz_temp2')->column('sku');
        $list = $this->item->field('sku,stock,occupy_stock,available_stock,real_time_qty,distribution_occupy_stock')->where(['sku' => ['in', $skus]])->select();
        foreach ($list as $k => $v) {
            $data['stock'] = $v['real_time_qty'] + $v['distribution_occupy_stock'];
            $data['available_stock'] = ($v['real_time_qty'] + $v['distribution_occupy_stock']) - $v['occupy_stock'];
            $p_map['sku'] = $v['sku'];
            $res = $this->item->where($p_map)->update($data);

            echo $k . "\n";
            usleep(200000);
        }
        echo 'ok';
        die;
    }

    /************************跑库存数据用END**********************************/


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
}
