<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\purchase\SampleWorkorder;
use app\admin\model\purchase\SampleWorkorderItem;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\enum\OrderType;
use fast\Excel;
use fast\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use app\Common\model\Auth;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use fast\Trackingmore;
use think\Log;
use think\Model;
use think\Request;

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
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
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
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
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
        $info = Db::name('datacenter_sku_day')->where('site','in','2,10,11')->where('id','>',27326)->field('id,platform_sku,day_date,site')->select();
        foreach ($info as $key => $value) {
            $data = $value['day_date'];
            $ga_skus = $zeeloolOperate->google_sku_detail($value['site'], $data);
            $ga_skus = array_column($ga_skus, 'uniquePageviews', 'ga:pagePath');
            if ($value['site'] == 2) {
                $model = Db::connect('database.db_voogueme_online');
            } elseif ($value['site'] == 10) {
                $model = Db::connect('database.db_zeelool_de_online');
            } else {
                $model = Db::connect('database.db_zeelool_jp_online');
            }
            $sku_id = $model->table('catalog_product_entity')->where('sku',$value['platform_sku'])->value('entity_id');
            $unique_pageviews = 0;
            foreach ($ga_skus as $kk => $vv) {
                if ($kk == '/goods-detail/'.$sku_id) {
                    $unique_pageviews += $vv;
                }
            }
            Db::name('datacenter_sku_day')->where('id',$value['id'])->update(['unique_pageviews' =>$unique_pageviews]);
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
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery')";
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
        $trackInfo = $trackingConnector->getTrackInfoMulti([
            [
                'number'  => '92001902551561000101621623',
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

            $trackInfo = $trackingConnector->getTrackInfoMulti([
                [
                    'number'  => $v['track_number'],
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
            'dhl'       => '100001',
            'chinapost' => '03011',
            'chinaems'  => '03013',
            'cpc'       => '03041',
            'fedex'     => '100003',
            'usps'      => '21051',
            'yanwen'    => '190012',
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
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
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
        $data = [
            'count' => $count,
            'days'  => $days,
        ];

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
                $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
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
                $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
                $sales_num = $order->where($where)->sum('total_qty_ordered');
                $arr2['purchase_num'] = $purchase_num;
                $arr2['sales_num'] = $sales_num;
                $arr2['purchase_sales_rate'] = $sales_num != 0 ? round($purchase_num / $sales_num * 100, 2) : 0;
                Db::name('datacenter_supply_month')->where('day_date', $start)->update($arr2);
            } else {
                //上个月总的采购数量（副数）
                $purchase_num = Db::name('warehouse_data')->where($map)->sum('all_purchase_num');
                //上个月总的销售数量（副数）
                $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered','delivery']];
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

    /**
     * 跑条形码库位库区绑定关系
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/23
     * Time: 15:05:16
     */
    public function product_bar_code_warehouse()
    {
        $store_sku = Db::name('store_sku')
            ->alias('a')
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
            ->where('a.is_del', 1)
            ->where('a.id', 'between', [9672, 9850])//178
            ->whereOr('a.id', 'between', [9590, 9604])//67
            ->whereOr('a.id', 'between', [9606, 9657])//67
            ->whereOr('a.id', 'between', [3280, 3287])//8
            ->whereOr('a.id', 'between', [9549, 9578])//30
            ->whereOr('a.id', 'between', [9852, 9876])//25
            ->field('a.sku,a.store_id,a.id,b.coding,b.area_id')
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
        foreach ($skus as $k => $v) {
            $store_sku = Db::name('store_sku')
                ->alias('a')
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->where('a.is_del', 1)
                ->where('a.sku', $v)
                ->field('a.sku,a.store_id,b.id,b.coding,b.area_id')
                ->find();
            $res = Db::name('product_barcode_item')->where('sku', $store_sku['sku'])->update(['location_code' => $store_sku['coding'], 'location_id' => $store_sku['area_id'], 'location_code_id' => $store_sku['store_id']]);
            echo $store_sku['sku'].'更新'.$res.'条数据 is ok'."\n";
            usleep(10000);
        }
    }

    public function update_bar_code_data()
    {
        $res = Db::connect('database.db_mojing_order')->table('fa_order_item_process')->where('magento_order_id', 390286)->where('site', 2)->update(['abnormal_house_id' => 0]);
        dump($res);
    }

    public function update_lens_data()
    {
        // Db::name('lens_price')->where(['lens_number'=>23100000,'price'=>10])->update(['sph_start'=>'-7.00','sph_end'=>'0.00','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['lens_number'=>23100000,'price'=>15])->update(['sph_start'=>'-10.00','sph_end'=>'-7.25','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['lens_number'=>23100000,'price'=>11])->update(['sph_start'=>'0.00','sph_end'=>'4.00','cyl_start'=>'-2.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['lens_number'=>23100000,'price'=>50])->update(['sph_start'=>'4.25','sph_end'=>'6.00','cyl_start'=>'-6.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['lens_number'=>23100001,'price'=>10])->update(['sph_start'=>'-7.00','sph_end'=>'0.00','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['lens_number'=>23100001,'price'=>15])->update(['sph_start'=>'-10.00','sph_end'=>'-7.25','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['lens_number'=>23100001,'price'=>11])->update(['sph_start'=>'0.00','sph_end'=>'4.00','cyl_start'=>'-2.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['lens_number'=>23100001,'price'=>50])->update(['sph_start'=>'4.25','sph_end'=>'6.00','cyl_start'=>'-6.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>31])->update(['sph_start'=>'-12.00','sph_end'=>'-3.00','cyl_start'=>'-2.00','cyl_end'=>'-0.50','type'=>1]);
        // Db::name('lens_price')->where(['id'=>36])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>39])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>45])->update(['sph_start'=>'-7.00','sph_end'=>'0.00','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['id'=>46])->update(['sph_start'=>'-10.00','sph_end'=>'-7.25','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>48])->update(['sph_start'=>'0.00','sph_end'=>'4.00','cyl_start'=>'-2.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['id'=>49])->update(['sph_start'=>'4.25','sph_end'=>'6.00','cyl_start'=>'-6.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>50])->update(['sph_start'=>'-7.00','sph_end'=>'0.00','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['id'=>51])->update(['sph_start'=>'-10.00','sph_end'=>'-7.25','cyl_start'=>'-4.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>53])->update(['sph_start'=>'0.00','sph_end'=>'4.00','cyl_start'=>'-2.00','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['id'=>54])->update(['sph_start'=>'4.25','sph_end'=>'6.00','cyl_start'=>'-6.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>55])->update(['sph_start'=>'-12.00','sph_end'=>'-3.00','cyl_start'=>'-2.00','cyl_end'=>'-0.50','type'=>1]);
        // Db::name('lens_price')->where(['id'=>56])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>60])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>67])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>69])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>71])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>73])->update(['sph_start'=>'-5.00','sph_end'=>'0.00','cyl_start'=>'-1.50','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['id'=>74])->update(['sph_start'=>'-8.00','sph_end'=>'-5.25','cyl_start'=>'-6.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>76])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>79])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>82])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>85])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>88])->update(['sph_start'=>'-5.00','sph_end'=>'0.00','cyl_start'=>'-1.50','cyl_end'=>'0.00','type'=>1]);
        // Db::name('lens_price')->where(['id'=>89])->update(['sph_start'=>'-8.00','sph_end'=>'-5.25','cyl_start'=>'-6.00','cyl_end'=>'0.00','type'=>2]);
        // Db::name('lens_price')->where(['id'=>91])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>98])->update(['type'=>2]);
        // Db::name('lens_price')->where(['id'=>101])->update(['price'=>400]);
        Db::name('lens_price')->where(['id' => 28])->update(['sph_start' => '-8.00', 'sph_end' => '0.00', 'cyl_start' => '-6.00', 'cyl_end' => '-4.25', 'type' => 2]);
    }

    /**
     * 跑入库单老数据 条形码与库位号绑定关系
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/25
     * Time: 14:18:26
     */
    public function update_bar_code_datas()
    {
        $data = Db::query('SELECT a.id, a.check_id, b.sku, a.in_stock_number, c.store_id, d.coding, d.area_id FROM `fa_in_stock` `a` LEFT JOIN `fa_in_stock_item` `b` ON `a`.`id` = `b`.`in_stock_id` JOIN `fa_store_sku` `c` ON `b`.`sku` = `c`.`sku` LEFT JOIN `fa_store_house` `d` ON `c`.`store_id` = `d`.`id` WHERE in_stock_number IN ( \'IN20210302090825772230\', \'IN20210302090829480510\', \'IN20210302090834209386\', \'IN20210302090840913439\', \'IN20210302090845487953\', \'IN20210302090849562833\', \'IN20210302090853861218\', \'IN20210302090857353890\', \'IN20210302090902864240\', \'IN20210302090907101523\', \'IN20210302090911359925\', \'IN20210302090915328422\', \'IN20210302090923837238\', \'IN20210302090929532973\', \'IN20210302090934608811\', \'IN20210302090954633838\', \'IN20210302091016827115\', \'IN20210302091042660609\', \'IN20210302091046585208\', \'IN20210302091051652988\', \'IN20210302091055273368\', \'IN20210311160016883690\', \'IN20210313131741682819\', \'IN20210316112712383364\', \'IN20210316112718558198\', \'IN20210316112722422461\', \'IN20210316112727225594\', \'IN20210316112731775127\', \'IN20210316130758346411\', \'IN20210316130803593610\', \'IN20210316130807203669\', \'IN20210316130819991528\', \'IN20210316130823863185\', \'IN20210316130843263882\', \'IN20210316130853784318\', \'IN20210316130858704453\', \'IN20210316130902938350\', \'IN20210316130908161545\', \'IN20210316130912626138\', \'IN20210316133717246103\', \'IN20210318182425735576\' ) AND d.area_id = 3 AND c.is_del = 1 GROUP BY b.sku ORDER BY c.store_id');
        $product_bar_code_item = new ProductBarCodeItem();
        foreach ($data as $k => $v) {
            $res = $product_bar_code_item->where(['sku' => $v['sku'], 'check_id' => $v['check_id'], 'is_sample' => 0])->update(['location_code' => $v['coding'], 'location_code_id' => $v['store_id'], 'location_id' => $v['area_id'], 'in_stock_id' => $v['id']]);
            echo $v['sku'].'更新'.$res.'条数据 is ok'."\n";
        }

    }

    /**
     * 阿里巴巴国际站
     * Interface upload_third_sku
     * @package app\admin\controller
     * @author  jhh
     * @date    2021/4/16 18:21:42
     */
    public function upload_third_sku()
    {
        $skus = Db::name('temp_sku')->select();
        foreach ($skus as $k=>$v){
            $platform = new ItemPlatformSku();
            $platSku =$platform->where('sku',$v['sku'])->where('platform_type',14)->value('platform_sku');
            if ($platSku) {
                $params['sku_info'] = $platSku;
                $params['platform_type'] = 2;
                $thirdRes = Http::post(config('url.api_zeelool_cn_url'), $params);
                $thirdRes = json_decode($thirdRes, true);
                if ($thirdRes['code'] == 1) {
                    $platform->where('sku',$v['sku'])->where('platform_type',14)->update(['is_upload' => 1]);
                    echo $platSku.'is ok'."\n";
                }else{
                    echo $platSku.'上传失败'."\n";
                }
            }
            else{
                echo $platSku.'没有映射关系'."\n";
            }

        }
    }

    /**
     * 抖音
     * Interface upload_third_sku_douyin
     * @package app\admin\controller
     * @author  jhh
     * @date    2021/4/16 18:21:33
     */
    public function upload_third_sku_douyin()
    {
        $skus = Db::name('temp_sku')->select();
        foreach ($skus as $k=>$v){
            $platform = new ItemPlatformSku();
            $platSku =$platform->where('sku',$v['sku'])->where('platform_type',13)->value('platform_sku');
            if ($platSku) {
                $params['sku_info'] = $platSku;
                $params['platform_type'] = 1;
                $thirdRes = Http::post(config('url.api_zeelool_cn_url'), $params);
                $thirdRes = json_decode($thirdRes, true);
                if ($thirdRes['code'] == 1) {
                    $platform->where('sku',$v['sku'])->where('platform_type',13)->update(['is_upload' => 1]);
                    echo $platSku.'is ok'."\n";
                }else{
                    echo $platSku.'上传失败'."\n";
                }
            }
            else{
                echo $v['sku'].'没有映射关系'."\n";
            }

        }
    }

    /**
     * 跑出映射关系 alibaba国际站
     * Interface
     * @package app\admin\controller
     * @author  jhh
     * @date    2021/4/19 9:19:57
     */
    public function run_plat_sku()
    {
        $this->model = new \app\admin\model\NewProduct;
        $this->attribute = new \app\admin\model\NewProductAttribute;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->platformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $skus = Db::name('temp_sku')->select();
        foreach ($skus as $k=>$v) {
            $newProductId = $this->model->where('sku',$v['sku'])->value('id');
            $skus[$k]['new_product_id'] = $newProductId;
            $where['new_product.id'] = $newProductId;
            $row = $this->model->where($where)->with(['newproductattribute'])->find();
            if (!$row) {
                echo '未查询到数据'."\n";
            }
            $row = $row->toArray();
            if ($row['item_status'] != 1 && $row['item_status'] != 2) {
                echo '此状态不能同步'."\n";
            }
            $map['id'] = $newProductId;
            $map['item_status'] = 1;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $params = $row;
                $params['create_person'] = session('admin.nickname');
                $params['create_time'] = date('Y-m-d H:i:s', time());
                $params['item_status'] = 1;
                unset($params['id']);
                unset($params['newproductattribute']);
                //查询商品表SKU是否存在
                $tWhere['sku'] = $params['sku'];
                $tWhere['is_del'] = 1;
                $count = $this->item->where($tWhere)->count();
                //此SKU已存在 跳过
                if ($count < 1) {
                    //添加商品主表信息
                    $this->item->allowField(true)->save($params);
                    $attributeParams = $row['newproductattribute'];
                    unset($attributeParams['id']);
                    unset($attributeParams['frame_images']);
                    unset($attributeParams['frame_color']);
                    $attributeParams['item_id'] = $this->item->id;
                    //添加商品属性表信息
                    $this->itemAttribute->allowField(true)->save($attributeParams);
                }

                //添加对应平台映射关系
                $skuParams['site'] = 14;
                $skuParams['sku'] = $params['sku'];
                $skuParams['frame_is_rimless'] = $row['frame_is_rimless'];
                $skuParams['name'] = $row['name'];
                $skuParams['category_id'] = $row['category_id'];

                $result = (new \app\admin\model\itemmanage\ItemPlatformSku())->addPlatformSku($skuParams);
                echo $v['sku'].'同步成功'."\n";
            } else {
                echo $v['sku'].'同步失败'."\n";
            }
        }
    }

    /**
     * 跑出映射关系 抖音
     * Interface run_plat_sku_douyin
     * @package app\admin\controller
     * @author  jhh
     * @date    2021/4/19 10:03:40
     */
    public function run_plat_sku_douyin()
    {
        $this->model = new \app\admin\model\NewProduct;
        $this->attribute = new \app\admin\model\NewProductAttribute;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->platformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $skus = Db::name('temp_sku')->select();
        foreach ($skus as $k=>$v) {
            $newProductId = $this->model->where('sku',$v['sku'])->value('id');
            $skus[$k]['new_product_id'] = $newProductId;
            $where['new_product.id'] = $newProductId;
            $row = $this->model->where($where)->with(['newproductattribute'])->find();
            if (!$row) {
                echo '未查询到数据'."\n";
            }
            $row = $row->toArray();
            if ($row['item_status'] != 1 && $row['item_status'] != 2) {
                echo '此状态不能同步'."\n";
            }
            $map['id'] = $newProductId;
            $map['item_status'] = 1;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $params = $row;
                $params['create_person'] = session('admin.nickname');
                $params['create_time'] = date('Y-m-d H:i:s', time());
                $params['item_status'] = 1;
                unset($params['id']);
                unset($params['newproductattribute']);
                //查询商品表SKU是否存在
                $tWhere['sku'] = $params['sku'];
                $tWhere['is_del'] = 1;
                $count = $this->item->where($tWhere)->count();
                //此SKU已存在 跳过
                if ($count < 1) {
                    //添加商品主表信息
                    $this->item->allowField(true)->save($params);
                    $attributeParams = $row['newproductattribute'];
                    unset($attributeParams['id']);
                    unset($attributeParams['frame_images']);
                    unset($attributeParams['frame_color']);
                    $attributeParams['item_id'] = $this->item->id;
                    //添加商品属性表信息
                    $this->itemAttribute->allowField(true)->save($attributeParams);
                }

                //添加对应平台映射关系
                $skuParams['site'] = 13;
                $skuParams['sku'] = $params['sku'];
                $skuParams['frame_is_rimless'] = $row['frame_is_rimless'];
                $skuParams['name'] = $row['name'];
                $skuParams['category_id'] = $row['category_id'];

                $result = (new \app\admin\model\itemmanage\ItemPlatformSku())->addPlatformSku($skuParams);
                echo $v['sku'].'同步成功'."\n";
            } else {
                echo $v['sku'].'同步失败'."\n";
            }
        }
    }

    /**
     * 跑sku的大货现货属性
     * Interface run_item_is_spot
     * @package app\admin\controller
     * @author  jhh
     * @date    2021/4/20 10:24:37
     */
    public function run_item_is_spot()
    {
        $item = new Item();
        $skuSpot = Db::name('zzzz_temp_is_spot')->select();
        foreach ($skuSpot as $k=>$v){
            $res =$item->where('sku',$v['sku'])->update(['is_spot'=>$v['is_spot']]);
            if ($res > 0){
                echo $v['sku'].'更新为'.$v['is_spot']."\n";
            }else{
                echo $v['sku'].'更新失败'."\n";
            }
        }
    }

    public function batch_export_xls_account2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->model = new NewOrderItemProcess();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        //默认展示3个月内的数据
        //二月
        $map['a.created_at'] = ['between', [1619798400,1622476800]];//128662
//        $map['a.created_at'] = ['between', [1614528000,1617206399]];//173915
//        $map['a.created_at'] = ['between', [1617206400,1619798399]];//162822
//        $map['a.created_at'] = ['between', [strtotime('-3 month'), time()]];
        //站点列表
        $siteList = [
            1  => 'Zeelool',
            2  => 'Voogueme',
            3  => 'Nihao',
            4  => 'Meeloog',
            5  => 'Wesee',
            8  => 'Amazon',
            9  => 'Zeelool_es',
            10 => 'Zeelool_de',
            11 => 'Zeelool_jp',
        ];
        $headList = [
            '子单号',
            '站点',
            '商品SKU',
            '加工类型',
            '订单状态',
            '支付时间',
            '创建时间'
        ];
        $itemSku = new ItemPlatformSku();
        $skus =$itemSku->column('sku','platform_sku');
        $path = '/uploads/';
        $fileName = '用来分析丹阳仓库需要存储的商品SKU和数量'.time();
        $count = $this->model
            ->alias('a')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->where($map)
            ->where('a.order_prescription_type','in',[2,3])
            ->count();

        for ($i = 0; $i < ceil($count / 50000); $i++) {
            $list = $this->model
                ->alias('a')
                ->field('a.id as aid,a.item_order_number,a.sku,a.order_prescription_type,b.created_at,b.status,b.site,a.created_at,b.payment_time')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($map)
                ->where('a.order_prescription_type','in',[2,3])
                ->page($i + 1, 50000)
                ->order('a.created_at desc')
                ->select();

            $list = collection($list)->toArray();

            //获取更改镜框最新信息
            $changeSku = $this->_work_order_change_sku
                ->alias('f')
                ->join(['fa_work_order_measure' => 'g'], 'f.measure_id=g.id')
                ->where([
                    'f.change_type'       => 1,
                    'f.item_order_number' => ['in', array_column($list, 'item_order_number')],
                    'g.operation_type'    => 1,
                ])
                ->column('f.change_sku', 'f.item_order_number');
            foreach ($list as $key => &$value) {

                switch ($value['order_prescription_type']) {
                    case 1:
                        $value['order_prescription_type'] = '仅镜架';
                        break;
                    case 2:
                        $value['order_prescription_type'] = '现货处方镜';
                        break;
                    case 3:
                        $value['order_prescription_type'] = '定制处方镜';
                        break;
                }
                $data[$key]['item_order_number'] = $value['item_order_number'];//子单号
                $data[$key]['site'] = $siteList[$value['site']];//站点
                $data[$key]['sku'] =$skus[$changeSku[$value['item_order_number']] ?: $value['sku']];//sku
                $data[$key]['order_prescription_type'] = $value['order_prescription_type'];//加工类型
                $data[$key]['status'] = $value['status'];//订单状态
                if (empty($value['payment_time'])) {
                    $value['payment_time'] = '暂无';
                } else {
                    $value['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);
                }
                $data[$key]['payment_time'] = $value['payment_time'];//支付时间
                if (empty($value['created_at'])) {
                    $value['created_at'] = '暂无';
                } else {
                    $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
                }
                $data[$key]['created_at'] = $value['created_at'];//订单创建时间
            }
            if ($i > 0) {
                $headList = [];
            }
            Excel::writeCsv($data,$headList,$path.$fileName);
        }
        //获取当前域名
        $request = Request::instance();
        $domain = $request->domain();
        header('Location: '.$domain.$path.$fileName.'.csv');
        die;
    }
    public function batch_export_xls_account3()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->model = new NewOrderItemProcess();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        //默认展示3个月内的数据
        //3月
        $map['a.created_at'] = ['between', [1614528000,1617206399]];//173915

        //站点列表
        $siteList = [
            1  => 'Zeelool',
            2  => 'Voogueme',
            3  => 'Nihao',
            4  => 'Meeloog',
            5  => 'Wesee',
            8  => 'Amazon',
            9  => 'Zeelool_es',
            10 => 'Zeelool_de',
            11 => 'Zeelool_jp',
        ];
        $headList = [
            '子单号',
            '站点',
            '商品SKU',
            '加工类型',
            '订单状态',
            '支付时间',
            '创建时间'
        ];
        $itemSku = new ItemPlatformSku();
        $skus =$itemSku->column('sku','platform_sku');
        $path = '/uploads/';
        $fileName = '用来分析丹阳仓库需要存储的商品SKU和数量'.time();
        $count = $this->model
            ->alias('a')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->where($map)
            ->where('a.order_prescription_type','in',[2,3])
            ->count();

        for ($i = 0; $i < ceil($count / 50000); $i++) {
            $list = $this->model
                ->alias('a')
                ->field('a.id as aid,a.item_order_number,a.sku,a.order_prescription_type,b.created_at,b.status,b.site,a.created_at,b.payment_time')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($map)
                ->where('a.order_prescription_type','in',[2,3])
                ->page($i + 1, 50000)
                ->order('a.created_at desc')
                ->select();

            $list = collection($list)->toArray();

            //获取更改镜框最新信息
            $changeSku = $this->_work_order_change_sku
                ->alias('f')
                ->join(['fa_work_order_measure' => 'g'], 'f.measure_id=g.id')
                ->where([
                    'f.change_type'       => 1,
                    'f.item_order_number' => ['in', array_column($list, 'item_order_number')],
                    'g.operation_type'    => 1,
                ])
                ->column('f.change_sku', 'f.item_order_number');
            foreach ($list as $key => &$value) {

                switch ($value['order_prescription_type']) {
                    case 1:
                        $value['order_prescription_type'] = '仅镜架';
                        break;
                    case 2:
                        $value['order_prescription_type'] = '现货处方镜';
                        break;
                    case 3:
                        $value['order_prescription_type'] = '定制处方镜';
                        break;
                }
                $data[$key]['item_order_number'] = $value['item_order_number'];//子单号
                $data[$key]['site'] = $siteList[$value['site']];//站点
                $data[$key]['sku'] =$skus[$changeSku[$value['item_order_number']] ?: $value['sku']];//sku
                $data[$key]['order_prescription_type'] = $value['order_prescription_type'];//加工类型
                $data[$key]['status'] = $value['status'];//订单状态
                if (empty($value['payment_time'])) {
                    $value['payment_time'] = '暂无';
                } else {
                    $value['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);
                }
                $data[$key]['payment_time'] = $value['payment_time'];//支付时间
                if (empty($value['created_at'])) {
                    $value['created_at'] = '暂无';
                } else {
                    $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
                }
                $data[$key]['created_at'] = $value['created_at'];//订单创建时间
            }
            if ($i > 0) {
                $headList = [];
            }
            Excel::writeCsv($data,$headList,$path.$fileName);
        }
        //获取当前域名
        $request = Request::instance();
        $domain = $request->domain();
        header('Location: '.$domain.$path.$fileName.'.csv');
        die;
    }
    public function batch_export_xls_account4()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->model = new NewOrderItemProcess();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        //默认展示3个月内的数据
        //4月
        $map['a.created_at'] = ['between', [1617206400,1619798399]];//162822
        //站点列表
        $siteList = [
            1  => 'Zeelool',
            2  => 'Voogueme',
            3  => 'Nihao',
            4  => 'Meeloog',
            5  => 'Wesee',
            8  => 'Amazon',
            9  => 'Zeelool_es',
            10 => 'Zeelool_de',
            11 => 'Zeelool_jp',
        ];
        $headList = [
            '子单号',
            '站点',
            '商品SKU',
            '加工类型',
            '订单状态',
            '支付时间',
            '创建时间'
        ];
        $itemSku = new ItemPlatformSku();
        $skus =$itemSku->column('sku','platform_sku');
        $path = '/uploads/';
        $fileName = '用来分析丹阳仓库需要存储的商品SKU和数量'.time();
        $count = $this->model
            ->alias('a')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->where($map)
            ->where('a.order_prescription_type','in',[2,3])
            ->count();

        for ($i = 0; $i < ceil($count / 50000); $i++) {
            $list = $this->model
                ->alias('a')
                ->field('a.id as aid,a.item_order_number,a.sku,a.order_prescription_type,b.created_at,b.status,b.site,a.created_at,b.payment_time')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($map)
                ->where('a.order_prescription_type','in',[2,3])
                ->page($i + 1, 50000)
                ->order('a.created_at desc')
                ->select();

            $list = collection($list)->toArray();

            //获取更改镜框最新信息
            $changeSku = $this->_work_order_change_sku
                ->alias('f')
                ->join(['fa_work_order_measure' => 'g'], 'f.measure_id=g.id')
                ->where([
                    'f.change_type'       => 1,
                    'f.item_order_number' => ['in', array_column($list, 'item_order_number')],
                    'g.operation_type'    => 1,
                ])
                ->column('f.change_sku', 'f.item_order_number');
            foreach ($list as $key => &$value) {

                switch ($value['order_prescription_type']) {
                    case 1:
                        $value['order_prescription_type'] = '仅镜架';
                        break;
                    case 2:
                        $value['order_prescription_type'] = '现货处方镜';
                        break;
                    case 3:
                        $value['order_prescription_type'] = '定制处方镜';
                        break;
                }
                $data[$key]['item_order_number'] = $value['item_order_number'];//子单号
                $data[$key]['site'] = $siteList[$value['site']];//站点
                $data[$key]['sku'] =$skus[$changeSku[$value['item_order_number']] ?: $value['sku']];//sku
                $data[$key]['order_prescription_type'] = $value['order_prescription_type'];//加工类型
                $data[$key]['status'] = $value['status'];//订单状态
                if (empty($value['payment_time'])) {
                    $value['payment_time'] = '暂无';
                } else {
                    $value['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);
                }
                $data[$key]['payment_time'] = $value['payment_time'];//支付时间
                if (empty($value['created_at'])) {
                    $value['created_at'] = '暂无';
                } else {
                    $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
                }
                $data[$key]['created_at'] = $value['created_at'];//订单创建时间
            }
            if ($i > 0) {
                $headList = [];
            }
            Excel::writeCsv($data,$headList,$path.$fileName);
        }
        //获取当前域名
        $request = Request::instance();
        $domain = $request->domain();
        header('Location: '.$domain.$path.$fileName.'.csv');
        die;
    }

    public function get_same_skus()
    {
        $itemPlatform = new ItemPlatformSku();
        $list = $itemPlatform->field('platform_sku,sku,platform_type')->select();
        $list = collection($list)->toArray();
        $arr = [];
        foreach ($list as $k=>$v){
            if ($arr[$v['sku']]){
                array_push($arr[$v['sku']],$v['platform_type']);
            }else{
                $arr[$v['sku']] = [$v['platform_type']];
            }
        }
        $data = [];
        foreach ($arr as $k1=>$v1){
            if (count($v1) != count(array_unique($v1))) {
                array_push($data,$k1);
            }
        }
        $skuData = [];
        foreach ($data as $k2=>$v2){
            $skuData[$k2]['sku'] = $v2;
        }
        $path = '/uploads/';
        $fileName = 'sku存在重复映射关系的sku'.time();
        $headList = [
            'sku'
        ];
        Excel::writeCsv($skuData,$headList,$path.$fileName);
        //获取当前域名
        $request = Request::instance();
        $domain = $request->domain();
        header('Location: '.$domain.$path.$fileName.'.csv');
        die;
    }
    public function run_purchase_sample_data()
    {
        $skus = Db::name('zzz_purchase_sample_sku')->column('sku');
        $purchaseSample = Db::name('purchase_sample')->where('sku','in',$skus)->select();
        $purchaseSampleIds = array_column($purchaseSample,'id');
        $Sample = new \app\admin\model\purchase\Sample();
        $purchaseSampleWorkOrder = new SampleWorkorder();
        $purchaseSampleWorkOrderItem = new SampleWorkorderItem();
        $data =$skus;//对应sku
        $data = array_unique($data);
        $Sample->startTrans();
        $purchaseSampleWorkOrder->startTrans();
        $purchaseSampleWorkOrderItem->startTrans();
        foreach ($data as $key => $val) {
            try {
                //将样品间信息假删除
                $map['sku'] = $val;
                $value['is_del'] = 2;
                $out_stock_num = $Sample->where(['sku' => $val])->value('stock');
                $res = $Sample->where($map)->update($value);
                if ($res) {
                    //添加出库单
                    $addValue['location_number'] = 'OUT'.date('YmdHis').rand(100, 999).rand(100, 999);;
                    $addValue['createtime'] = date('Y-m-d H:i:s', time());
                    $addValue['create_user'] = session('admin.nickname');
                    $addValue['description'] = '样品间清理库存,商品批量出库,并将绑定关系绑定关系解除';
                    $addValue['status'] = 3;
                    $addValue['type'] = 2;
                    $outStockId = $purchaseSampleWorkOrder->insertGetId($addValue);
                    if ($outStockId) {
                        //出库商品信息表对应信息
                        $outStockItemValue['sku'] = $val;
                        $outStockItemValue['stock'] = $out_stock_num;
                        $outStockItemValue['parent_id'] = $outStockId;
                        $purchaseSampleWorkOrderItem->insert($outStockItemValue);
                    }
                }
                $a[] = $val;
                $Sample->commit();
                $purchaseSampleWorkOrder->commit();
                $purchaseSampleWorkOrderItem->commit();
            } catch (ValidateException $e) {
                $Sample->rollback();
                $purchaseSampleWorkOrder->rollback();
                $purchaseSampleWorkOrderItem->rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                $Sample->rollback();
                $purchaseSampleWorkOrder->rollback();
                $purchaseSampleWorkOrderItem->rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                $Sample->rollback();
                $purchaseSampleWorkOrder->rollback();
                $purchaseSampleWorkOrderItem->rollback();
                $this->error($e->getMessage(), [], 408);
            }
        }
    }
    public function export_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $skus = Db::name('zzzzzzzz_temps')->column('sku');
        $map['a.sku'] = ['in',$skus];
        $items = Db::name('check_order_item')
            ->alias('a')
            ->join(['fa_check_order' => 'b'], 'a.check_id=b.id')
            ->join(['fa_purchase_order' => 'c'], 'b.purchase_id=c.id')
            ->join(['fa_purchase_order_item' => 'd'], 'c.id=d.purchase_id')
            ->join(['fa_supplier' => 'e'], 'c.supplier_id=e.id')
            ->where($map)
            ->where('c.createtime','between',['2020.06.01','2021.07.08'])
            ->field('a.sku,a.quantity_rate,b.check_order_number,e.supplier_name,d.purchase_num,d.purchase_price,c.purchase_number,c.purchase_status,c.check_status,c.stock_status,c.factory_type,c.createtime,c.type')
            ->select();
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "SKU")
            ->setCellValue("B1", "供应商")
            ->setCellValue("C1", "采购数量");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "采购单价")
            ->setCellValue("E1", "采购单号");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "质检合格率")
            ->setCellValue("G1", "采购状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "质检状态")
            ->setCellValue("I1", "入库状态")
            ->setCellValue("J1", "工厂类型")
            ->setCellValue("K1", "大货/现货")
            ->setCellValue("L1", "采购单创建时间")
            ->setCellValue("M1", "质检单号");

        foreach ($items as $key => $value) {
            switch ($value['purchase_status']) {
                case 0:
                    $purchaseStatus = '新建';
                    break;
                case 1:
                    $purchaseStatus = '审核中';
                    break;
                case 2:
                    $purchaseStatus = '已审核';
                    break;
                case 3:
                    $purchaseStatus = '已拒绝';
                    break;
                case 4:
                    $purchaseStatus = '已取消';
                    break;
                case 5:
                    $purchaseStatus = '待发货';
                    break;
                case 6:
                    $purchaseStatus = '待收货';
                    break;
                case 7:
                    $purchaseStatus = '已签收';
                    break;
                case 8:
                    $purchaseStatus = '已退款';
                    break;
                case 9:
                    $purchaseStatus = '部分签收';
                    break;
                case 10:
                    $purchaseStatus = '已完成';
                    break;
                default:
                    $purchaseStatus = '异常';
                    break;
            }
            switch ($value['check_status']) {
                case 0:
                    $checkStatus = '未质检';
                    break;
                case 1:
                    $checkStatus = '部分质检';
                    break;
                case 2:
                    $checkStatus = '已质检';
                    break;
                default:
                    $checkStatus = '异常';
                    break;
            }
            switch ($value['stock_status']) {
                case 0:
                    $stockStatus = '未入库';
                    break;
                case 1:
                    $stockStatus = '部分入库';
                    break;
                case 2:
                    $stockStatus = '已入库';
                    break;
                default:
                    $stockStatus = '异常';
                    break;
            }
            switch ($value['factory_type']) {
                case 0:
                    $factoryType = '工厂';
                    break;
                case 1:
                    $factoryType = '贸易';
                    break;
            }
            switch ($value['type']) {
                case 1:
                    $type = '现货';
                    break;
                case 2:
                    $type = '大货';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['supplier_name']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['purchase_num']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['purchase_price']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['purchase_number']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['quantity_rate']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $purchaseStatus);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $checkStatus);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $stockStatus);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $factoryType);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $type);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['createtime']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['check_order_number']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(40);



        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '采购单数据2020.6.1-2021.7.8';

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }
    public function export_work_order_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $skus = Db::name('zzzzzzzz_temps')->column('sku');
        $map['a.platform_order'] = ['in',$skus];
        $items = Db::name('work_order_list')
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.id=b.work_id')
            ->join(['fa_work_order_problem_type' => 'c'], 'c.id=a.problem_type_id')
            ->where($map)
            ->field('c.type,c.problem_name,b.measure_content,a.replacement_order,a.refund_money,a.platform_order,b.operation_type')
            ->select();
//        dump($items);die;
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "订单号")
            ->setCellValue("B1", "工单问题类型")
            ->setCellValue("C1", "退款金额");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "措施")
            ->setCellValue("E1", "措施状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "补发订单号")
            ->setCellValue("G1", "补发订单状态");

        foreach ($items as $key => $value) {
            switch ($value['type']) {
                case 1:
                    $type = '客服';
                    break;
                case 2:
                    $type = '仓库';
                    break;
            }
            switch ($value['operation_type']) {
                case 0:
                    $operationType = '未处理';
                    break;
                case 1:
                    $operationType = '处理完成';
                    break;
                case 2:
                    $operationType= '处理失败';
                    break;
            }
            $orderStatus = Db::connect('database.db_mojing_order')->table('fa_order')->where('increment_id', $value['replacement_order'])->value('status');
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['platform_order'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $type);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['refund_money']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['measure_content']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $operationType);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['replacement_order']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $orderStatus);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '导出数据-排查加诺拦截订单的补发情况';

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }

    public function run_goods_supply_data()
    {
        $item = new Item();
        $newProduct = new \app\admin\model\NewProduct();
        $goodsSupply = Db::name('zzzzzzz_temp_goods_supply')->select();
        foreach ($goodsSupply as $k=>$v){
            if ($v['goods_supply'] == 1 || $v['goods_supply'] == 2){
                $isSpot = 1;
            }else{
                $isSpot = 2;
            }
            $res = $newProduct->where('sku',$v['sku'])->update(['goods_supply'=>$v['goods_supply']]);
            if ($res){
                $res1 = $item->where('sku',$v['sku'])->update(['goods_supply'=>$v['goods_supply'],'is_spot'=>$isSpot]);
                if ($res1){
                    echo $v['sku'].'更新成功'."\n";
                }else{
                    echo $v['sku'].'更新选品成功，更新商品失败'."\n";
                }
            }else{
                echo $v['sku'].'更新失败'."\n";
            }
        }
    }
    //导出所有网站在售SKU数据
    public function export_on_sale_sku_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $platformSku = new ItemPlatformSku();
        $items = $platformSku
            ->where(['outer_sku_status'=>1])
            ->field('sku,platform_type')
            ->select();
        $platArr = [
            1  => 'Zeelool',
            2  => 'Voogueme',
            3  => 'Nihao',
            4  => 'Meeloog',
            5  => 'Wesee',
            8  => 'Amazon',
            9  => 'Zeelool_es',
            10 => 'Zeelool_de',
            11 => 'Zeelool_jp',
            12 => 'voogmechic',
            13 => 'zeelool_cn',
            14 => 'alibaba',
            15 => 'Zeelool_fr',
        ];
        $skuArr = [];
        foreach ($items as $key => $value) {
            $items[$key]['site'] = $platArr[$value['platform_type']];
            if (isset($skuArr[$value['sku']])){
                $skuArr[$value['sku']] = $skuArr[$value['sku']].','.$items[$key]['site'];
            }else{
                $skuArr[$value['sku']] = $items[$key]['site'];
            }

        }
        $keys = 0;
        $skuArrs = [];
        foreach ($skuArr as $k => $v) {
            $skuArrs[$keys]['sku'] = $k;
            $skuArrs[$keys]['plat'] = $v;
            $keys = $keys + 1;
        }

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "sku")
            ->setCellValue("B1", "站点");

        foreach ($skuArrs as $key => $value) {
            $keys = 0;
            $keys ++;
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['plat']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(150);


        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '导出所有网站在售SKU数据';

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }
    // 导出绑定拣货区库位SKU数据
    public function export_store_sku_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $items = Db::name('store_sku')
            ->alias('a')
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
            ->where('b.area_id','in',[3,6])
            ->where('a.is_del',1)
            ->field('a.sku,a.stock_id,b.area_id,b.coding')
            ->select();
        $stockArr = [1=>'郑州仓',2=>'丹阳仓'];
        $stockAreaArr = [3=>'拣货区',6=>'丹阳拣货区'];
        foreach ($items as $key => $value) {
            $items[$key]['area'] = $stockAreaArr[$value['area_id']];
            $items[$key]['stock'] = $stockArr[$value['stock_id']];
            unset($items[$key]['stock_id']);
            unset($items[$key]['area_id']);
        }
        $cmf_arr = array_column($items, 'sku');
        array_multisort($cmf_arr, SORT_ASC, $items);
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "sku")
            ->setCellValue("B1", "实体仓")
            ->setCellValue("C1", "库区");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "库位");

        foreach ($items as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2),  $value['stock']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['area']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['coding']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '导出数据-导出绑定拣货区库位SKU数据';

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }
    //导出郑州仓或者丹阳仓在货架上sku及sku对应的库存数量
    public function export_store_sku_all_data()
    {
        $stockId = input('stock_id');
        $startId = input('start_id');
        $endId = input('end_id');
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $list = Db::name('store_sku')
            ->alias('a')
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
            ->where('a.is_del',1)
            ->where('a.stock_id',$stockId)
            ->where('a.store_id','between',[$startId,$endId])
            ->field('a.sku,a.stock_id,b.area_id,b.coding')
            ->select();

        $productbarcodeitem = new ProductBarCodeItem();
        $stockNameArr = [1=>'郑州仓',2=>'丹阳仓'];
        $stockAreaArr = [1=>'大货区',2=>'货架区',4=>'丹阳大货区',5=>'丹阳货架区',3=>'拣货区',6=>'丹阳拣货区'];
        foreach ($list as $k => $row) {
            //在库 子单号为空 库位号 库区id都一致的库存作为此库位的库存
            $list[$k]['stock_name'] = $stockNameArr[$row['stock_id']];
            $list[$k]['area_name'] = $stockAreaArr[$row['area_id']];
            $coding = $row['coding'];
            unset($list[$k]['coding']);
            $list[$k]['coding'] = $coding;
            unset($list[$k]['stock_id']);
            unset($list[$k]['area_id']);
            $list[$k]['stock'] = $productbarcodeitem
                ->where(['location_id'=>$row['area_id'],'location_code'=>$row['coding'],'library_status'=>1,'item_order_number'=>'','sku'=>$row['sku'],'stock_id' => $row['stock_id']])
                ->count();
        }
        $header = ['SKU', '仓库', '库区','库位','库位库存'];
//        $filename = $stockNameArr[$stockId].'当前库位库存';
        $filename = 'zhengzhoukuweikucun';
        Excel::writeCsv($list, $header, $filename,true);
    }
    //导出郑州仓或者丹阳仓在配货加工流程中且未出库的sku及sku对应的库存数量
    public function export_store_sku_all_run_data()
    {
        $stockId = input('stock_id');
        $startId = input('start_id');
        $endId = input('end_id');
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $item = new Item();
        $list = $item
            ->where('distribution_occupy_stock','>',0)
            ->field('sku,distribution_occupy_stock')
            ->where('id','between',[$startId,$endId])
            ->select();

        $productbarcodeitem = new ProductBarCodeItem();
        $stockNameArr = [1=>'郑州仓',2=>'丹阳仓'];
        $skuArr = [];
        foreach ($list as $k => $row) {
            $skuArr[$k]['sku'] = $row['sku'];
            $skuArr[$k]['stock'] = $productbarcodeitem
                ->where(['library_status'=>1,'sku'=>$row['sku'],'stock_id' => $stockId,'is_loss_report_out'=>0])
                ->where('item_order_number','<>','')
                ->where('in_stock_time','>','2020-12-30 14:49:34')
                ->count();
        }
        $header = ['SKU','库存数量'];
        $filename = $stockNameArr[$stockId].'在配货加工流程中且未出库的sku及sku对应的库存数量';
        Excel::writeCsv($skuArr, $header, $filename,true);
    }

    // 按上架时间导出SKU销售数据
    public function export_sku_sales_month()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $skus = [
            'WA032313-01','OT003144-02','OT003144-01','SM534670-03','SM534670-01','WA575716-01','WA643927-01','WA666832-01','TP716864-02','OP791781-04','TT386629-04','TT386629-03','TT386629-02','TT386629-01','OX024581-05','OX024581-04','OX024581-03','OX024581-02','OX024581-01','VFP0163-07','WA035255-03','WA035255-02','TT125691-05','TT125691-03','TT125691-02','TT125691-01','WA097200-05','WA097200-04','WA097200-01','WA524290-03','WA524290-02','WA524290-01','WX057723-04','WX093432-01','WA757532-02','WA757532-01','WM456857-03','WM456857-02','WM456857-01','WX256410-02','WX256410-01','WA022959-04','WA022959-03','WA022959-02','WA022959-01','WA032234-04','WA032234-03','WA032234-02','WX053371-02','OA261692-01','WX053371-01','OM252016-03','OM648787-01','WA942416-03','WA942416-01','OM496867-04','OM496867-03','OM496867-02','OM496867-01','OM607627-01','OX567524-02','WA432768-01','OM654870-01','GX005686-02','GX005686-01','GA569445-03','OX685324-01','OM919595-02','OM919595-01','GX606338-02','GX606338-01','GA083224-04','GA083224-03','GA083224-02','OX558648-02','OX558648-01','GA569445-02','GA569445-01','TX022098-02','TX022098-01','OX056924-02','OX056924-01','GM998194-02','GM998194-01','GT659590-01','GI686597-01','OX582697-06','OX582697-05','OX582697-04','OX582697-03','OX582697-02','GI337287-01','GI889892-01','WX354847-03','WX354847-02','WX354847-01','WX093432-02','WA081343-03','WA081343-02','WA081343-01','WA558432-04','WA558432-03','WA558432-02','WA558432-01','WX123073-03','WX123073-02','WX123073-01','WA645483-02','WA645483-01','WA331129-02','WA331129-01','OP154992-01','WP943992-02','WP943992-01','WA432768-02','WA643927-03','WA643927-02','WA067826-03','WA067826-02','WA067826-01','TM425072-03','TT029458-02','TT029458-01','TT051935-01','TT102894-03','WA024259-02','WA025283-01','WA048251-01','VFP0179-01','TT145767-06','TT145767-02','TT145767-01','WA053689-03','WA054245-02','WA054245-01','WA078127-03','WA187785-02','WA495436-04','WA495436-02','WA512068-01','WA754531-01','WA847069-01','WM446047-01','WP984982-01','WX226282-01','WA238643-02','WA238643-01','WX344250-02','WX344250-01','OT010228-05','OT010228-04','OT010228-03','OT010228-02','OT010228-01','OT008735-02','OT008735-01','WA981434-03','OI883076-02','OI017548-01','OI093443-01','TM559041-01','TM425072-02','TM425072-01','TT805294-04','TT805294-03','TT805294-02','TT805294-01','OM858834-02','OM858834-01','OT736935-03','OT736935-02','WA688337-05','WA688337-04','WT267259-01','WA528461-01','WA899452-02','OX602628-02','OX602628-01','WA784562-02','WA784562-01','WA053654-03','WA053654-02','WA053654-01','WA055182-04','WA055182-03','OI076236-04','TT102894-02','GI366610-01','GI366610-04','WA078127-02','WA719457-02','WA719457-01','WA712947-05','WA712947-04','WA712947-03','WA712947-02','WA712947-01','OA01858-10','WA585777-01','OI883076-01','OI485618-01','OT501749-02','DI707471-02','DI707471-01','SM052031-03','SM052031-02','SM052031-01','OA984626-01','TP459998-02','TP459998-01','OI076236-01','DI743899-01','WA161419-02','DT119358-01','OP791781-05','OP791781-03','OP791781-01','OT793997-02','WA973195-01','WP002878-03','WI958754-01','OX081854-01','DI014919-03','DI014919-02','DI014919-01','OX929851-03','DU060356-01','DU030357-03','OI004710-01','WA666238-01','WM579033-02','WM579033-01','WA893234-01','TU796833-04','DT555870-01','DT303557-01','OI716123-02','OI716123-01','TT009941-04','TT009941-03','TT009941-02','TT009941-01','TT531729-03','TT531729-04','TT531729-02','TT531729-01','OI842498-01','OA907598-02','OA907598-01','OT744319-01','WA283939-03','DU030357-02','DU030357-01','WA283939-01','OA453588-01','DI645990-02','DI645990-01','DU779473-02','DU779473-01','OA076014-02','OA076014-01','WA349127-07','WA349127-03','OI072635-02','TA914125-04','TA914125-03','TP135820-04','TP135820-03','WA142377-04','WA142377-01','WA947322-02','WP002878-02','WP002878-01','SA271795-03','SA271795-02','SA271795-01','WA847069-02','WA921017-03','WA921017-01','DA062140-03','OT008735-05','OT008735-04','OT008735-03','GA055723-01','WA014534-02','WA014534-01','WA503375-04','WA503375-03','GI023870-04','GI023870-02','GI162062-02','GI162062-01','DA062140-04','DA062140-02','DA062140-01','WA654982-03','WA654982-02','WA654982-01','WI294825-03','WI294825-01','DI054752-03','DI054752-01','OT465170-04','OP279834-01','WA377494-03','WA377494-01','WA029114-03','TX607768-03','GX027455-01','SM901394-01','SA397725-01','OP441484-04','OP441484-03','OP441484-02','OX043050-08','OX043050-07','OX043050-06','FX0772-04','WA053689-02','WA053689-01','TM505544-02','TM505544-01','GA709736-02','GA709736-01','TU796833-03','TU796833-02','TU796833-01','TA914125-02','TA914125-01','WX221934-05','WX221934-03','WX221934-02','WA027698-04','WA027698-02','SA595052-04','SA595052-03','SA595052-02','SA595052-01','WA501431-01','WT061797-01','VFP0290-10','WA557253-01','WA269250-01','TT221590-03','TT221590-04','TT221590-02','OT126616-01','WA238966-01','WA104084-01','WA687214-02','WA001215-01','WA457528-04','WA457528-03','WA457528-02','WA006880-02','WA006880-01','WA849536-03','TT457283-04','WA336474-04','WA991077-01','WA991077-03','OT359236-02','OT057997-04','WA687214-01','GA301368-01','WA881835-03','WA881835-01','WX435742-06','WX435742-02','OA01565-01','OM749624-02','WA573696-02','GA453260-03','WX435742-03','FX0540-03','OT736650-03','GA032471-01','WX016813-01','WA099544-01','GA453260-02','GA453260-01','WA861059-04','WA861059-03','OT126616-03','OA01798-03','GA006265-01','DA934054-02','WX435742-04','WX435742-01','GX965450-01','WX057723-03','WX057723-02','OT736650-04','OT736650-01','DA028918-04','DA028918-03','DA028918-02','GA541040-01','OI318124-01','OT359236-01','WT089447-03','WT089447-02','WT055532-02','TT203922-03','OA01522-02','OP01958-04','OP02080-01','OA01953-02','OP228837-03','OX02009-02','OX02009-03','OT279230-03','TX823437-03','GM362645-01','OM035279-01','OT615924-02','OP971516-06','DA869112-01','DA281863-02','DI151559-03','DI151559-02','OM777679-09','ER921420-02','ER543569-01','ER726284-01','ACC085213-04','ACC085213-03','ACC085213-01','TT457283-03','OT066274-02','OT066274-01','OX004559-04','DX057124-01','DX057124-02','DA281863-04','OI016350-01','WA224994-01','WA224994-02','WA064442-01','WA414262-08','DA654589-04','DA654589-02','SA292882-02','SA292882-01','OI224692-03','OI224692-02','OI224692-01','OX354990-03','TX149552-03','OT126616-02','TX149552-04','WA501932-06','WA501932-05','WA501932-04','WA501932-02','WA501932-01','WA009975-04','WA009975-03','WA501932-03','WA009975-01','TT078112-02','TT078112-01','OM414288-04','OX177371-05','WA801627-03','WA414262-05','OP01887-07','OP01887-06','OP01887-05','B30008-2','B30008-1','B30010-1','B30011-1','B30013-1','B30014-1','B30015-1','B30016-1','B30012-1','B30018-1','B30019-1','E20020-1','E20021-1','E20022-1','E20023-1','E20024-1','E20025-1','E20026-1','E20027-1','E20029-1','E20030-1','E20031-1','E20034-1','E20037-1','E20038-1','E20040-1','E40010-2','E20041-3','E20041-2','E20041-1','E40001-1','E40002-1','E40003-1','E40004-1','E40005-1','E40006-1','E40007-1','E40009-2','E40010-1','E40011-1','E40012-1','E40013-1','E40014-1','E40015-1','E40016-1','E40017-1','E40018-1','E40019-1','E40020-1','E40021-1','E40022-1','E40023-1','E40024-1','E40025-1','E40026-1','E40027-1','E40028-1','E40029-1','E40030-1','E50001-1','E50002-1','E40031-1','E50003-1','E50004-1','E50007-1','E50008-3','E50008-2','E50008-1','E50011-3','E50011-2','E50011-1','E60001-1','E50009-1','E60002-1','N10003-1','N10008-1','N10026-1','N10011-1','N10016-1','N10027-1','N10028-1','N10029-1','N10030-1','N10031-1','N10032-1','N10033-1','N10034-1','N10035-1','N10036-1','N10037-1','N10039-1','NBY002-1','NBY003-1','NXP005-1','ER6042-01','ER149179-01','WA953018-02','SM534670-02','WM01682-03','WM366128-01','DA424185-04','WM048545-02','OX004559-02','OP876213-03','WA016415-01','TT753613-01','SM103749-02','OX354990-02','OX354990-01','OX02143-02','OM679419-02','DX002816-03','DP383759-01','WM706044-01','WA414262-06','WA414262-04','DA707924-02','DA707924-01','DA798761-02','DA798761-01','WA958512-01','WA065451-01','WA006389-01','WA801627-04','WA801627-02','WA801627-01','WM706044-02','WA953018-04','WM829722-02','WM829722-01','WA953018-03','WM004930-02','DX595884-02','DA275259-04','OP449452-03','OP449452-02','OP449452-01','OM989266-01','OX177371-03','OX177371-01','WA000487-05','DP923190-01','WA018352-01','WA018352-02','WA108054-02','SM103749-03','SM103749-01','WA065451-02','ER039871-01','OP302058-01','OT011656-02','WM004930-01','WM048545-01','WM366128-02','TM017879-02','DA275259-01','GA435020-01','GA832826-01','OM414288-03','VFT0271-01','TM428727-02','TM428727-01','TM432413-03','TM432413-01','TT162834-03','TX314798-01','DI038215-01','OP679529-02','OX063349-03','WA953018-05','WA953018-01','OT108597-02','WA081494-01','WA737113-01','WT061812-01','WM928151-01','OX197768-02','OX197768-01','OX845435-02','WA454518-05','WA043668-03','WA043668-02','WA043668-01','DI046658-03','DI046658-02','WA891389-04','WA891389-02','WA891389-01','DA519297-04','DA519297-02','DA519297-01','TM481377-01','WA071469-03','WX997265-02','WX997265-01','WA974038-01','WA104084-05','DX595884-04','DX595884-03','TX026556-01','GX089313-04','GX089313-03','GX089313-01','WA071469-02','WA071469-01','OP525244-03','OP525244-04','OP01892-09','OP01892-07','WA035804-03','WA000487-04','WA000487-03','GX682784-01','WA258752-05','WA258752-04','WA011292-04','WA011292-03','WA011292-02','TX512293-01','DT666623-02','WM692931-02','WM692931-01','NBY001-1','BBY001-1','E50005-1','WA012308-04','WA012308-03','WA012308-02','WA012308-01','WA035804-05','WA035804-04','DP784598-03','DP784598-02','DP784598-01','WA681769-02','WA681769-01','OT092185-01','OX011570-01','WA528512-06','WA528512-04','OX011570-04','OX011570-03','OX011570-02','WA036297-01','WA422086-02','WA422086-01','OX671622-06','OT776862-02','OT776862-01','OI432591-01','DA656343-02','WM162166-01','TX452142-02','TX452142-03','OT768723-01','OT068084-02','OT068084-01','TX687442-03','TX687442-02','TX687442-01','WX553661-03','WX553661-04','WX553661-02','WX553661-01','GA427263-01','TT013417-01','DA452547-02','DA452547-01','WA079045-03','WA079045-02','WA079045-01','GM256830-03','GM256830-02','GM256830-01','TX225717-01','TX225717-02','TX225717-03','WA259684-02','WA259684-01','WA192071-04','WA192071-02','OT688777-02','OT688777-01','DA656343-04','DA656343-03','OP02126-10','WA233799-04','DT666623-01','OT917236-01','TT162834-01','WT007860-01','OX084225-01','TT633559-01','XT134925-01','OT632399-03','OX331639-03','OX331639-01','FX0206-07','WA351969-04','WX977822-01','DX002816-02','TM042526-01','WM242542-02','WM242542-01','OP971516-05','OP971516-03','OP971516-02','OP971516-01','VHP0189-06','OX739865-02','WA397898-02','WA397898-03','WA397898-01','DI151785-01','DI048277-01','DI048277-02','WX977822-02','OX965149-03','DA022366-01','TM073073-01','WA351969-06','GA933076-03','DI216878-02','WA829049-06','WA351969-01','WA351969-03','WX545741-01','OP679529-01','WM457246-01','OX739865-03','SA137325-03','SA137325-02','SA137325-01','DI216878-01','WP457763-01','WA528512-05','WA528512-03','WA528512-02','WA528512-01','DA934054-04','GA166956-01','DA385610-04','OT615924-06','TX828043-02','TX828043-01','OP01860-09','OP01860-08','OP01860-07','TT579425-03','TT579425-01','ER138272-01','VHP0189-11','WA01701-01','TM329140-03','OT632399-02','DM473233-06','DM473233-05','DM473233-04','DM473233-02','SA323438-03','SA323438-02','SA323438-01','OA01858-07','WP675087-02','WP675087-01','WA135389-02','WA135389-01','SA647319-04','SA647319-02','SA647319-01','WA311167-05','WA311167-04','WA311167-02','OA02007-06','OA02007-09','OX063349-05','OP01860-06','B10015-1','B10001-1','E10045-1','CH633891-01','CH141466-01','ACC451837-02','ACC451837-01','ER081175-01','ER387673-02','ER387673-01','ER475067-02','ER475067-01','ER189826-04','ER189826-03','ER189826-02','OT632399-01','ER203634-05','ER203634-04','ER203634-03','ER203634-02','ER203634-01','WA582748-01','GA435020-02','SA145262-04','SA145262-03','OA01858-09','WA233265-05','WA233265-04','WA233265-02','WA233265-01','OP006413-03','OP006413-02','DA162085-01','WA434629-02','WA434629-01','WT007860-02','OX727451-03','OX727451-02','OT629735-01','OA01858-08','OM506216-01','OM506216-02','DM997471-01','OX921163-02','OX921163-01','TX548130-03','TX548130-02','TX548130-01','WA082862-01','OT413672-05','OT413672-11','OT413672-08','OT413672-10','GA082118-02','OT615924-01','WA454518-06','WA454518-04','WA454518-02','WA069535-07','WA069535-06','WA069535-04','WA069535-03','WA069535-02','WA034265-03','WA034265-05','WA034265-04','OT324858-01','VFP0116-01','TM042458-03','TM865428-02','TM865428-01','WA948061-01','OX065861-03','WA104195-01','GA035141-01','GX059139-01','GX066079-01','GA455778-01','GA491860-01','GA017734-01','GA065427-01','OT465170-03','WA927534-01','GA301763-05','GA301763-04','OM446692-01','WX552343-01','WX975249-01','GA933076-02','GA075836-01','WA515812-03','WA515812-02','WA515812-01','TX512713-03','WA887911-01','DA869112-02','OP02131-01','TM863291-03','TM863291-02','OI946147-01','TM329140-02','TM329140-01','TM042458-02','TM042458-01','OT569852-01','TT123484-02','TT123484-01','OX737634-03','OX737634-02','OX737634-01','WA077858-01','FA0431-03','OP889378-02','OP889378-01','OX065861-02','OX065861-01','DA934054-03','TT256753-02','WA263231-03','WA263231-02','OX965149-01','OO836688-01','OX009644-02','OX009644-01','WA082846-02','OT099571-02','OT099571-01','WA767153-01','OT253030-05','OT253030-03','OT253030-02','GA329222-01','OP000774-01','TX357178-03','TX579630-01','OT092341-06','OT092341-05','OT092341-04','OI998123-03','OI998123-02','OI998123-01','TM625894-03','TM625894-02','TM625894-01','GX322474-03','GX322474-02','GX322474-01','OT207217-02','OT207217-01','WA044485-01','WA062782-03','TX816080-04','WA906730-02','WA906730-01','WA579252-03','WA579252-02','WA222727-02','WA222727-01','WA178431-03','WA178431-02','WA178431-01','OP01863-04','OP01863-03','OP01863-05','VFP0164-01','WA822781-04','WA822781-03','WA822781-02','WA822781-01','TX827070-01','WA424665-02','WA424665-01','OP525244-01','VFP0183-03','OP239668-04','OP239668-03','OP239668-02','WA456693-01','WA456693-04','WA043790-03','WA723599-03','WA723599-02','WA723599-01','WA062247-05','WA062247-04','WA106882-03','WA106882-01','OX684861-04','OX684861-01','GM095515-01','WA01753-03','OA01858-06','OT615924-09','OT615924-08','OT615924-07','OT615924-05','OT615924-04','OT615924-03','OT146796-02','OT146796-01','OT865063-01','VHP0189-08','OI716520-01','OI731923-01','OM935234-01','GA009073-02','GA009073-03','OI986882-03','OI986882-02','OI986882-01','OI694780-01','WX709297-05','WX709297-04','WX709297-03','OM944324-01','TX579630-05','TX579630-03','TX579630-04','OI611693-03','OI611693-02','OI611693-01','OT838932-01','DM568185-01','DM568185-02','OX992564-02','OT093437-01','TX531583-04','TX531583-02','TX784042-04','TX784042-03','TX784042-02','TX784042-01','WA768816-06','WA768816-05','WA768816-04','WA768816-03','WA768816-02','WA768816-01','OP014066-08','DX619533-02','GA023816-01','WA043790-02','WA829049-05','WA829049-04','WA829049-03','WA829049-01','TX816080-03','TX816080-01','TX816080-02','OT389022-04','OT389022-03','OT389022-01','WA244876-01','WA639441-01','WA729538-01','OT092341-01','WA646635-01','WA034649-01','SA299973-02','SA299973-01','FA0742-06','SA010588-03','SA010588-02','VFX0060-08','VFX0060-09','VFT0271-05','DM626083-04','DM626083-03','DM626083-01','SA223928-02','SA223928-01','OA01858-03','VFM0176-08','VFM0176-09','OA01901-08','OA01858-04','OP01884-01','OP066822-05','DA041413-03','DA041413-01','WA885863-01','WA885863-02','WA624495-02','WA624495-01','OA01901-14','OA01901-18','FA0742-05','WA438665-05','WA438665-01','WA438665-08','WA438665-03'];


        $items = [];
        foreach ($skus as $sku) {
            $items[$sku] = [
                'sku' => $sku,
                '0' => 0,
                '30' => 0,
                '60' => 0,
                '90' => 0,
                '120' => 0,
            ];
            $data = Db::name('datacenter_sku_day')
                ->where('site',1)
                ->where('sku',$sku)
                ->field('day_date,glass_num')
                ->order('day_date', 'asc')
                ->select();
            $data = array_chunk($data, 30);

            foreach ($data as $i => $datum) {
                $items[$sku][min($i, 4) * 30] += array_sum(array_column($datum, 'glass_num'));
            }
        }

        $items = array_values($items);

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "sku")
            ->setCellValue("B1", "0-30")
            ->setCellValue("C1", "30-60")
            ->setCellValue("D1", "60-90")
            ->setCellValue("E1", "90-120")
            ->setCellValue("F1", "120+");   //利用setCellValues()填充数据

        foreach ($items as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2),  $value['0']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['30']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['60']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['90']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['120']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '导出数据-SKU销量';

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }
    //跑你好站 08-06-08-09的品类数据
    public function goods_type_day_center1($plat, $goods_type,$time)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $start = $time;
        $seven_days = $start . ' 00:00:00 - ' . $start . ' 23:59:59';
        $createat = explode(' ', $seven_days);
        $where['o.payment_time'] = ['between', [strtotime($createat[0] . ' ' . $createat[1]), strtotime($createat[3] . ' ' . $createat[4])]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered', 'delivery','shipped']];
        $where['order_type'] = 1;
        $where['o.site'] = $plat;
        $where['goods_type'] = $goods_type;

        //某个品类眼镜的销售副数
        $frame_sales_num = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
            ->where($where)
            ->sum('i.qty');
        //眼镜的折扣价格
        $frame_money = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
            ->where($where)
            ->value('sum(base_original_price-i.base_discount_amount) as price');
        $frame_money = $frame_money ? round($frame_money, 2) : 0;
        $arr['goods_type'] = $goods_type;
        $arr['glass_num'] = $frame_sales_num;
        $arr['sales_total_money'] = $frame_money;

        return $arr;
    }

    public function run_nihao_goods_type_data()
    {
        $time = input('time');
        $res10 = Db::name('datacenter_goods_type_data')->where(['site'=>3,'day_date'=>$time])->update($this->goods_type_day_center1(3, 1,$time));
        if ($res10) {
            echo 'nihao站平光镜ok';
        } else {
            echo 'nihao站平光镜不ok';
        }
        $res11 = Db::name('datacenter_goods_type_data')->where(['site'=>3,'day_date'=>$time])->update($this->goods_type_day_center1(3, 2,$time));
        if ($res11) {
            echo 'nihao站配饰ok';
        } else {
            echo 'nihao站配饰不ok';
        }
    }
}
