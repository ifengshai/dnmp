<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrder;
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
        $this->order = new NewOrder();
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
        $order = new NewOrder();
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
            $order = new NewOrder();
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
        ini_set('memory_limit', '1000M');

        $skus = [
            'DA091078-01','DA165838-01','DP383759-01','DP784598-01','DP784598-02','DP784598-03','DP923190-01','FM0088-01','HM0238-01','OA02123-03','OA02123-04','OA02123-06','OM446692-01','OM469870-01','OM777679-09','OM935234-01','OP006413-02','OP006413-03','OP006896-02','OP006896-04','OP006896-05','OP014066-08','OP01958-04','OP02080-01','OP025962-02','OP054962-02','OP054962-03','OP054962-05','OP066822-05','OP073046-02','OP073046-03','OP158495-02','OP158495-03','OP166621-04','OP228837-03','OP235563-02','OP302058-01','OP315841-01','OP315841-02','OP368647-01','OP374116-01','OP441484-02','OP441484-03','OP441484-04','OP449452-01','OP449452-02','OP449452-03','OP679529-01','OP679529-02','OP752631-01','OP752631-02','OP752631-03','OP791781-01','OP791781-03','OP791781-04','OP791781-05','OP797619-02','OP797619-04','OP797619-05','OP797619-06','OP797619-07','OP797619-08','OP825396-01','OP825396-02','OP889378-01','OP889378-02','OP919529-02','OP944539-01','OT126616-01','OT126616-02','OT126616-03','OT126930-02','OT126930-03','OT126930-06','OT235163-02','OT235163-03','OT235163-04','OT235163-06','OT235163-07','OT324858-01','OT352772-01','OT352772-02','OT632399-01','OT632399-02','OT632399-03','OT688777-01','OT688777-02','OT766472-01','OT766472-02','OT766472-03','OT776862-01','OT776862-02','OX009644-01','OX009644-02','OX082674-02','OX123188-03','OX488895-01','OX488895-02','OX488895-03','OX509159-01','OX695975-01','OX695975-02','OX737634-01','OX737634-02','OX737634-03','OX907171-01','OX907171-02','WA064442-01','WA224994-01','WA224994-02','WP007672-01','WP007672-02','OP313158-03','FP0150-01','FP0511-01','FP0511-02','FP0511-03','FP0511-04','FT0105-01','FT0229-01','OM072875-01','OM158185-01','OM158185-02','OM414288-03','OM414288-04','OM496867-01','OM496867-02','OM496867-03','OM496867-04','OM607627-01','OM607627-02','OM648787-01','OM679419-02','OM689661-01','OM986034-01','OM986034-02','OM986034-03','OM986034-04','OO075562-01','OP01884-01','OP01884-02','OP01884-03','OP01889-01','OP01889-02','OP01892-03','OP02006-02','OP025962-01','OP054962-04','OP064023-01','OP075937-01','OP075937-02','OP077474-01','OP080978-04','OP153479-06','OP162712-01','OP239668-02','OP239668-03','OP239668-04','OP279834-01','OP313158-01','OP313158-02','OP429312-02','OP429312-03','OP715916-01','OP725733-02','OP966117-05','OT006813-04','OT008735-01','OT008735-02','OT008735-03','OT008735-04','OT008735-05','OT010228-01','OT010228-02','OT010228-03','OT010228-04','OT010228-05','OT011656-02','OT01911-01','OT01911-02','OT02019-02','OT020785-01','OT02145-03','OT026952-03','OT053568-03','OT053568-05','OT092185-01','OT092341-01','OT092341-02','OT092341-03','OT092341-04','OT092341-05','OT092341-06','OT093437-01','OT108597-02','OT131247-01','OT131247-02','OT323463-05','OT389022-01','OT389022-03','OT389022-04','OT414973-02','OT414973-03','OT414973-05','OT501749-02','OT518836-03','OT518836-05','OT518836-06','OT569852-01','OT587952-01','OT587952-02','OT587952-03','OT587952-04','OT615924-01','OT615924-02','OT615924-03','OT615924-04','OT615924-05','OT615924-06','OT615924-07','OT615924-08','OT615924-09','OT629735-01','OT736650-01','OT736650-02','OT736650-03','OT736650-04','OT736935-01','OT736935-02','OT736935-03','OT744319-01','OT744319-02','OT768723-01','OT793997-02','OT838932-01','OT921139-01','OT921139-04','OX006447-02','OX006447-03','OX011570-01','OX011570-02','OX011570-03','OX011570-04','OX01560-02','OX02009-02','OX02009-03','OX02143-02','OX024581-01','OX024581-02','OX024581-03','OX024581-04','OX024581-05','OX043050-04','OX045890-01','OX056924-01','OX056924-02','OX059712-01','OX059712-02','OX059712-03','OX059712-04','OX063349-03','OX063349-05','OX065861-01','OX065861-02','OX065861-03','OX082799-01','OX082799-02','OX082799-03','OX128869-02','OX177371-01','OX177371-03','OX177371-05','OX215012-01','OX215012-03','OX219056-01','OX219056-02','OX219056-03','OX219056-04','OX368382-04','OX395215-01','OX481166-01','OX481166-02','OX521044-01','OX562741-01','OX562741-02','OX562741-04','OX567524-02','OX582697-02','OX582697-03','OX582697-04','OX582697-05','OX582697-06','OX602628-01','OX602628-02','OX616570-01','OX616570-03','OX616570-04','OX616570-05','OX671622-02','OX691711-01','OX691711-02','OX691711-03','OX691711-06','OX725959-01','OX867962-01','OX908172-01','OX908172-02','OX908172-03','OX921163-01','OX921163-02','OX974334-01', 'OX974334-02','OX984868-02','SM052031-01','SM052031-02','SM052031-03','SM901394-01','TP716864-02','WA01720-02','WA036297-01','WA063596-01','WA269250-01','WA724842-01','WP002878-01','WP002878-02','WP002878-03','WP675087-01','WP675087-02','WP984982-01','WT007860-01','WT007860-02','WT267259-01','XT134925-01','ZX0923-03','FP0326-01','OP010193-06','OP02006-01','OP02010-01','OP025470-03','OP166621-05','OP421241-02','OP797619-01','OP797619-03','OP985641-02','OT01910-01','OT01910-02','OT038647-01','OT038647-03','OT038647-04','OT050046-02','OT050046-03','OT050046-04','OT126930-01','OT126930-05','OT235163-01','OT414973-04','OT414973-06','OT964573-01','OX017894-04','OX043050-05','OX123188-02','OX425928-02','OX425928-03','OX425928-04','OX855461-01','DX026928-02','DX064287-01','FA0321-01','FA0321-02','FA0407-02','FA0535-02','FA0742-01','FA0742-05','FA0742-06','FM0161-01','FM0161-02','FP0049-01','FP0087-03','FP0153-01','FP0424-03','FP0662-01','FP0662-02','FP0662-03','FP0662-04','FP0662-06','FT0232-01','FX0195-02','GA077726-01','GA077726-02','GT659590-01','OA01451-01','OA01451-02','OA01451-03','OA01968-01','OA01968-02','OA02040-01','OA02040-02','OA02040-04','OA02058-01','OA02061-01','OA02063-03','OM01872-01','OM326875-01','OM326875-02','OM418130-02','OM506216-01','OM506216-02','OM989266-01','OP01884-04','OP01899-03','OP01934-03','OP01934-04','OP01934-06','OP01956-01','OP01956-02','OP01956-03','OP02052-01','OP02098-01','OP02098-02','OP02098-04','OP02098-07','OP02126-01','OP02126-02','OP02126-03','OP02126-04','OP02128-01','OP02128-03','OP049594-01','OP049594-02','OP071873-01','OP071873-02','OP154992-01','OP168655-01','OP168655-02','OP168655-03','OP291258-04','OP358317-01','OP358317-04','OP373340-01','OP525244-01','OP652540-01','OP652540-02','OP765682-02','OP791268-01','OP791268-02','OP791268-03','OP928770-01','OP928770-02','OT02019-06','OT02116-02','OT026172-01','OT026172-02','OT068084-01','OT068084-02','OT099571-01','OT099571-02','OT207217-01','OT207217-02','OT359236-01','OT359236-02','OT524812-02','OT714213-01','OT768017-02','OT854223-01','OT854223-02','OT854223-03','OT854223-04','OT865063-01','OT917236-01','OX002546-02','OX035056-01','OX035056-04','OX052215-01','OX058541-01','OX081854-01','OX126479-02','OX126479-03','OX126479-04','OX366077-02','OX485851-01','OX485851-03','OX684861-01','OX684861-04','OX929851-03','OX992564-02','RM0136-01','RM0136-02','TT082679-01','TT082679-03','TT475156-04','TT598617-01','TT598617-03','TT598617-05','TT598617-07','VFP0158-01','VFP0236-01','VFP0236-02','VFP0236-04','VFP0236-05','WA01654-01','WA01654-03','WA01666-01','WA033656-01','WA033656-02','WA034649-01','WA108054-02','WA233799-01','WA233799-02','WA233799-06','WA244876-01','WA298281-02','WA298281-03','WA495436-02','WA495436-04','WA514578-01','WA514578-02','WA514578-03','WA514578-04','WA514578-05','WA729538-01','WP054838-01','WP054838-02','WP054838-03','WP342432-01','WP342432-02','WP342432-03','WP457763-01','WP943992-01','WP943992-02','WT069479-01','WT751392-01','WT897856-01','WT897856-02','WT899767-01','WT899767-02','WT899767-03','ZX0923-04','FM0479-01','FP0563-01','FT0017-01','FT0127-01','FT0127-02','OM02033-02','OP008768-03','OP008768-04','OP01892-02','OP01892-05','OP01892-06','OP02086-02','OP025962-03','OP038454-02','OP054962-01','OP054962-06','OP080978-01','OP080978-02','OP080978-03','OP725733-01','OP725733-03','OP928638-01','OP966117-03','OT501032-02','OT921139-05','OX872988-01','DA024554-01','DA798761-01','DA798761-02','FA0176-01','FA0176-02','FA0176-03','FA0249-01','FA0481-01','FP0424-01','FP0424-02','FP0662-07','FP0662-08','FP0662-09','FP0662-10','FP0662-11','FP0668-01','OA01862-01','OA01866-01','OA01866-02','OA01874-01','OA01973-01','OA02054-02','OA02055-01','OA02062-02','OA02106-02','OA02109-01','OA02109-02','OA02117-03','OM02024-03','OM024576-01','OM068620-02','OM068620-03','OM268544-02','OM441540-01','OP010193-01','OP010193-02','OP01860-03','OP01892-04','OP01899-01','OP01899-02','OP02010-03','OP02010-04','OP025470-02','OP025470-04','OP037821-03','OP037821-05','OP037821-06','OP037821-07','OP166621-03','OP235563-01','OP394420-01','OP394420-04','OP432631-01','OP606881-01','OP606881-02','OP911170-02','OP911170-03','OP911170-04','OT005008-04','OT006813-01','OT006813-02','OT02018-01','OT02018-02','OT02018-03','OT038647-05','OT049254-01','OT050046-05','OT066274-01','OT066274-02','OT126930-04','OT157359-01','OT157359-02','OT331145-02','OT331145-03','OT652438-01','OT652438-02','OT652438-04','OT652438-05','OT964573-02','OT964573-03','OT964573-04','OX017894-01','OX035445-01', 'OX123188-01','OX354990-01','OX354990-02','OX354990-03','OX406441-01','OX406441-02','OX406441-06','OX468320-01','OX682137-01','OX723043-03','OX915680-03','OX965149-01','OX965149-03','RM0545-01','SM103749-01','SM103749-02','SM103749-03','SM534670-01','SM534670-02','SM534670-03','TT070731-01','TT221590-02','TT221590-03','TT221590-04','TT386629-01','TT386629-02','TT386629-03','TT386629-04','TT457283-03','TT457283-04','TT499571-01','TT499571-02','TT499571-03','TT637015-01','TT637015-03','TT637015-04','TT802724-01','TT805294-01','TT805294-02','TT805294-03','TT805294-04','TT874013-01','VFP0183-01','VFP0183-02','VFP0183-03','VFP0306-02','WA007391-01','WA007391-02','WA007391-03','WA01626-04','WA01647-03','WA178431-01','WA178431-02','WA178431-03','WA222727-01','WA222727-02','WA233799-03','WA238966-01','WA438876-01','WA438876-02','WA438876-04','WA579252-01','WA579252-02','WA579252-03','WA583669-01','WA906730-01','WA906730-02','WM012934-02','WT085260-02','WT085260-03','WT944467-01','WT944467-02','WT944467-03','WT976245-01','WT976245-02','WX016813-01','FP0227-01','FT0642-01','FX0480-01','OA01776-06','OA02140-01','OA047287-01','OA817636-01','OM02127-01','OM094579-01','OM094579-02','OM178828-01','OM178828-02','OM178828-03','OM268544-01','OM448178-01','OM796599-01','OM944324-01','OM944324-02','OM944324-03','OM944324-04','OP019117-02','OP019117-03','OP02085-01','OP02097-02','OP02097-03','OP099710-01','OP099710-02','OP099710-03','OP099710-04','OP099710-05','OP099710-06','OP225077-01','OP225077-02','OP269513-01','OP291258-01','OP291258-02','OP291258-03','OT003317-01','OT003317-02','OT003317-03','OT02019-04','OT02019-05','OT02116-03','OT02116-04','OT02144-01','OT02144-02','OT02144-03','OT02145-01','OT092477-01','OT092477-02','OT092477-03','OT361266-01','OT361266-02','OT518836-02','OT518836-04','OT714213-02','OT854223-05','OT912399-01','OT912399-02','OT912399-03','OX002546-01','OX006465-01','OX006465-03','OX011792-02','OX017439-02','OX035445-02','OX035445-03','OX035445-04','OX035445-05','OX035445-06','OX078897-01','OX459485-01','OX459485-02','OX459485-03','OX459485-04','OX459485-05','OX671622-03','OX671622-04','OX671622-05','OX671622-06','OX802941-02','OX978145-01','OX978145-02','TT418088-01','TT418088-03','TT475156-01','TT475156-03','TT598617-02','WA01654-02','WA438876-03','FT0105-02','OA02117-02','OO075562-02','OP01934-01','OP413531-01','OT01906-01','OT176792-01','OT176792-02','OT176792-03','OX245026-01','OX245026-02','OX245026-03','OX521044-04','OX616570-02','TT055326-01','TT055326-02','WA01638-03','DA041413-01','DA041413-03','DA148988-01','DA403394-01','DA403394-02','DA452547-01','DA452547-02','DA519297-01','DA519297-02','DA519297-04','DA656343-02','DA656343-03','DA656343-04','DA656546-01','DA656546-02','DA656546-03','DA707924-01','DA707924-02','DA738035-01','DA775673-01','DM568185-01','DM568185-02','DM997471-01','FA0178-02','FA0451-03','FA0457-01','FA0457-02','FA0457-03','FA0457-04','FA0457-05','FA0457-06','FA0457-07','FA0602-02','FA0602-03','FA0726-01','FA0726-02','FA0726-03','FA0726-04','FA0892-01','FA0892-02','FM0088-02','FM0088-03','FM0125-01','FM0125-02','FM0125-03','FM0125-06','FM0173-01','FM0361-01','FP0044-01','FP0044-02','FP0044-03','FP0044-05','FP0044-06','FP0044-07','FP0044-10','FP0044-11','FP0044-12','FP0044-13','FP0045-01','FP0099-01','FP0099-02','FP0101-01','FP0207-01','FP0327-02','FP0341-01','FP0351-01','FP0427-01','FP0432-01','FP0580-03','FP0877-01','FP0877-02','FP0877-03','FP0885-01','FP0885-02','FP0886-01','FP0886-02','FP0886-03','FP0886-04','FT0230-01','FT0230-02','FX0052-01','FX0199-01','FX0536-01','FX0547-01','FX0548-01','FX0548-02','FX0552-02','FX0757-05','GA006265-01','GA032471-01','GA035141-01','GA075836-01','GA234832-01','GA531639-02','GA709736-01','GA709736-02','GA832826-01','GA933076-02','GA933076-03','GM095515-01','GM256830-01','GM256830-02','GM256830-03','GM282046-01','GM282046-03','GM282046-04','GM362645-01','GM947526-01','GM947526-02','GM998194-01','GM998194-02','GP0321-02','GX089313-01','GX089313-03','GX089313-04','HP0181-01','HP0181-02','OA002110-02','OA01534-01','OA01762-01','OA01776-04','OA01776-05','OA01815-02','OA01815-03','OA01873-01','OA01873-02','OA01885-01','OA01900-01','OA01953-02','OA01968-03','OA01968-04','OA01968-05','OA02007-01','OA02007-02','OA02007-03','OA02040-03','OA02130-01','OA02133-01','OA029562-01','OA047287-02','OA076014-01','OA076014-02','OA082477-01','OA453588-01','OA511723-03','OA511723-04','OA511723-05','OA822539-01','OA877655-01','OA919468-01','OM01935-01','OM02024-01','OM02024-02','OM02025-01', 'OM02025-02','OM02100-01','OM02100-02','OM035279-01','OM654870-01','OM666564-01','OM666564-02','OM749624-02','OM843870-01','OM919595-01','OM919595-02','OP01860-04','OP01860-05','OP01860-06','OP01860-07','OP01860-08','OP01860-09','OP01863-03','OP01863-04','OP01863-05','OP01887-02','OP01887-04','OP01887-05','OP01887-06','OP01887-07','OP01892-01','OP01892-07','OP01892-09','OP01912-04','OP01934-02','OP02085-02','OP02129-01','OP02131-01','OP025451-01','OP038454-03','OP068304-02','OP068304-05','OP068304-06','OP073046-04','OP078345-05','OP153479-02','OP153479-05','OP235358-01','OP525244-03','OP525244-04','OP675215-01','OP675215-03','OP971516-01','OP971516-02','OP971516-03','OP971516-05','OP971516-06','OT006813-03','OT006813-05','OT006813-06','OT01914-02','OT01952-01','OT01952-02','OT01978-01','OT01978-02','OT01978-03','OT02084-01','OT057997-04','OT108597-04','OT108597-05','OT146796-01','OT146796-02','OT157359-03','OT157359-04','OT202996-03','OT222092-02','OT253030-02','OT253030-03','OT253030-05','OT331145-01','OT465170-01','OT465170-02','OT465170-03','OT465170-04','OT518836-01','OT921139-03','OX002546-03','OX006465-04','OX011792-01','OX011792-04','OX017439-01','OX043050-01','OX043050-02','OX043050-03','OX043050-06','OX043050-07','OX043050-08','OX049063-01','OX049063-02','OX049063-03','OX264142-01','OX264142-02','OX264142-04','OX425928-01','OX519935-01','OX521044-02','OX521044-03','OX723043-02','OX727451-02','OX727451-03','OX739865-01','OX739865-02','OX739865-03','OX739865-07','OX845435-02','OX845435-03','TI0286-02','TM428727-01','TM428727-02','TT013417-01','TT177089-02','TT177089-04','TT177089-06','TT598617-04','TX607768-03','VFM0176-01','VFM0176-02','VFM0176-03','VFM0176-04','VFM0176-05','VFM0176-06','VFM0176-08','VFM0176-09','VFM0176-10','VFM0176-12','VFM0176-13','VFM0176-14','VFM0176-15','VFP0116-01','VFP0158-02','VFP0163-07','VFP0164-01','VFP0179-01','VFP0227-01','VFP0270-01','VFP0270-02','VFP0270-04','VFP0270-05','VFP0270-06','VFP0270-07','VFP0290-01','VFP0290-06','VFP0290-07','VFP0290-08','VFP0290-10','VFP0306-01','VFP0306-03','VFP0306-04','VFP0306-06','VFP0306-07','VFP0306-08','VFP0306-11','VFP0306-12','VFP0307-02','VFT0271-01','VFT0271-05','VFT0271-06','VFT0271-07','VFX0060-01','VFX0060-02','VFX0060-03','VFX0060-08','VFX0060-09','VHP0189-01','VHP0189-02','VHP0189-03','VHP0189-06','VHP0189-08','VHP0189-11','WA000781-01','WA000781-02','WA001215-01','WA006389-01','WA006880-01','WA006880-02','WA012457-03','WA012457-05','WA014534-01','WA014534-02','WA01606-02','WA01626-01','WA016415-01','WA01647-02','WA01688-01','WA01712-01','WA01713-02','WA01753-01','WA01753-02','WA024259-02','WA025283-01','WA029114-03','WA031277-01','WA031277-02','WA031277-03','WA035804-03','WA035804-04','WA035804-05','WA048251-01','WA048516-01','WA068079-02','WA069535-02','WA069535-03','WA069535-04','WA069535-06','WA069535-07','WA078127-02','WA078127-03','WA079045-01','WA079045-02','WA079045-03','WA081494-01','WA104195-01','WA105764-01','WA105764-02','WA105764-03','WA105764-04','WA105764-05','WA158859-01','WA158859-02','WA158859-04','WA158859-05','WA161419-02','WA184290-01','WA184290-02','WA187785-02','WA224527-02','WA224527-03','WA233265-01','WA233265-02','WA233265-04','WA233265-05','WA233799-04','WA233799-05','WA245023-05','WA253742-01','WA253742-02','WA259684-01','WA259684-02','WA311167-02','WA311167-04','WA311167-05','WA348535-01','WA422086-01','WA422086-02','WA424665-01','WA424665-02','WA432511-01','WA432511-02','WA432511-03','WA432511-04','WA432511-05','WA434629-01','WA434629-02','WA438665-01','WA438665-03','WA438665-05','WA438665-08','WA456693-01','WA456693-02','WA456693-04','WA482221-01','WA482221-02','WA482221-03','WA482221-04','WA501431-01','WA512068-01','WA524290-01','WA524290-02','WA524290-03','WA528461-01','WA545514-01','WA545514-02','WA545514-03','WA556718-01','WA557253-01','WA575716-01','WA581464-01','WA581464-02','WA581464-03','WA581464-04','WA581464-05','WA581464-06','WA646635-01','WA654982-01','WA654982-02','WA654982-03','WA666238-01','WA688393-01','WA688393-02','WA688393-03','WA712947-01','WA712947-02','WA712947-03','WA712947-04','WA712947-05','WA723599-01','WA723599-02','WA723599-03','WA732119-01','WA732119-02','WA754531-01','WA767153-01','WA768816-01','WA768816-02','WA768816-03','WA768816-04','WA768816-05','WA768816-06','WA777329-04','WA777329-08','WA822781-01','WA822781-02','WA822781-03','WA822781-04','WA829049-01','WA829049-03','WA829049-04','WA829049-05','WA829049-06','WA845144-01','WA845144-03','WA845144-04',
            'WA849536-03','WA861059-03','WA861059-04','WA868739-01','WA868739-02','WA887911-01','WA891389-01','WA891389-02','WA891389-04','WA947322-01','WA947322-02','WA981434-01','WM012934-01','WM01680-02','WM01682-02','WM01682-03','WM162166-01','WM242542-01','WM242542-02','WM446047-01','WM457246-01','WM579033-01','WM579033-02','WO626691-01','WO626691-02','WO626691-03','WO626691-04','WT055532-02','WT061797-01','WT061812-01','WT089447-02','WT089447-03','WT116311-01','WT116311-02','WT116311-03','WT117238-01','WT117238-03','WX009553-01','WX009553-02','WX035111-01','WX035111-02','WX035111-03','WX053371-01','WX053371-02','WX075228-01','WX091891-01','WX091891-02','WX091891-03','WX093432-01','WX093432-02','WX337618-01','WX337618-02','WX354847-01','WX354847-02','WX354847-03','WX545741-01','WX552343-01','WX616995-01','WX701721-01','WX701721-02','WX701721-03','WX701721-05','WX977822-01','WX977822-02','WX997265-01','WX997265-02','ZM0957-01','ZM0980-04','ZM0982-03','DA334240-04','FP0563-02','FP0634-02','GM119026-01','GM119026-02','GM119026-03','OA01776-03','OA01938-01','OM268544-03','OP004716-01','OP004716-02','OP235563-03','OT665155-04','OT665155-06','OX01987-02','OX519935-02','OX802941-01','TT089380-01','TT475156-02','VFP0158-03','VFP0158-04','VFP0177-01','WA012457-01','WA012457-02','WA012457-04','WA981434-02','WA981434-03','DA438237-01','DA883067-02','FP0668-03','FX0236-01','FX0819-03','GM031715-01','GM049355-03','GM137973-01','GM137973-02','GP0321-01','OA01817-01','OA01817-02','OA01823-02','OA01877-01','OA01879-01','OA01895-01','OA01918-01','OA01923-01','OA01992-01','OA01993-01','OA01999-01','OA02002-01','OA02005-01','OA02035-01','OA02039-02','OA02099-02','OA02113-01','OA02135-02','OA391157-02','OA391157-04','OA391157-05','OA511723-01','OA511723-02','OA511723-06','OM01454-01','OM01454-02','OM01975-02','OM02102-02','OP01971-01','OP01990-03','OP02048-01','OP02048-02','OP02048-03','OP02048-04','OP153479-01','OP153479-03','OP153479-04','OP499012-01','OP772243-01','OT02077-01','OT02078-01','OT524812-01','OX264142-03','TX414178-01','VFP0168-01','VFP0168-02','VFP0168-03','VFP0236-03','WA01753-03','WA01753-05','WA292841-01','WA351257-01','WA457920-01','WA457920-02','WA671325-01','DA022366-01','DA869112-01','DA869112-02','DA883067-01','DA934054-02','DA934054-03','DA934054-04','DI023962-01','DI121062-01','DI508611-01','DI944768-01','DM473233-02','DM473233-04','DM473233-05','DM473233-06','DM626083-01','DM626083-03','DM626083-04','DX057124-01','DX057124-02','DX064287-02','DX064287-03','FA0434-02','FA0654-02','FA0761-01','FA0831-01','FA0831-02','FP0124-01','FP0124-02','FP0180-01','FP0180-02','FP0205-01','FP0207-02','FP0266-01','FP0266-02','FP0266-03','FP0266-04','FP0327-01','FP0434-01','FP0639-01','FP0665-01','FP0665-02','FP0668-02','FP0668-08','FP0668-09','FP0669-01','FP0669-02','FP0669-03','FP0669-04','FT0139-01','FT0667-01','FT0690-01','FT0690-02','FX0047-03','FX0170-01','FX0170-02','FX0206-01','FX0206-02','FX0206-03','FX0206-04','FX0206-06','FX0206-07','FX0231-01','FX0306-02','FX0325-01','FX0353-01','FX0552-01','FX0552-03','FX0757-07','FX0757-08','FX0758-01','FX0819-01','FX0819-02','GA023816-01','GA047488-02','GA047488-04','GA082118-02','GA083224-02','GA083224-03','GA083224-04','GA236825-02','GA301368-01','GA329222-01','GA381699-01','GA427035-01','GA427035-02','GA427035-03','GA427035-04','GA453260-01','GA453260-02','GA453260-03','GA551348-01','GA551348-02','GM031715-02','GM031715-03','GM049355-01','GM049355-02','GM308171-01','GM308171-02','GM372191-01','GM372191-02','GM372191-03','GM372191-04','GM559668-01','GM559668-02','GM563824-01','GM817681-02','GP0314-01','GX606338-01','GX606338-02','GX682784-01','GX773615-01','HP0223-01','OA002110-01','OA01504-01','OA01544-01','OA015508-01','OA01590-01','OA01767-01','OA01767-02','OA01798-01','OA01798-02','OA01798-03','OA01835-01','OA01838-01','OA01858-02','OA01858-03','OA01858-04','OA01858-05','OA01858-06','OA01858-07','OA01858-08','OA01858-09','OA01858-10','OA01868-01','OA01870-02','OA01870-03','OA01880-01','OA01901-01','OA01901-02','OA01901-03','OA01901-04','OA01901-05','OA01901-07','OA01901-08','OA01901-12','OA01901-13','OA01901-14','OA01901-15','OA01901-17','OA01901-18','OA01909-01','OA01909-02','OA01920-01','OA01927-01','OA01941-01','OA01941-02','OA01994-01','OA01995-01','OA01996-02','OA02034-02','OA02036-02','OA02041-01','OA02041-02','OA02041-03','OA02043-04','OA02083-01','OA02083-02','OA02090-02','OA02099-01','OA02104-01','OA02141-01','OA208120-01','OA261692-01','OA391157-03','OA766129-01','OA766129-02','OA766129-03','OA907598-01','OA907598-02', 'OI02107-02','OM01849-01','OM01949-01','OM01949-02','OM01975-01','OM252016-03','OM293664-01','OP006896-03','OP010193-03','OP010193-05','OP02126-10','OP02129-02','OP239668-07','OP269513-02','OP579084-01','OP579084-02','OP928638-02','OT026952-01','OT026952-02','OT115181-01','OT115181-02','OT115181-03','OT115181-04','OT188487-01','OT188487-02','OT576184-01','OT799337-01','OX01969-01','OX01969-02','OX02101-01','OX02101-02','OX044379-01','OX059051-01','OX059051-02','OX084225-01','OX331639-01','OX331639-03','OX558648-01','OX558648-02','OX558648-03','OX685324-01','OX763099-01','OX915680-01','OX963140-01','OX963140-02','OX984868-01','TA914125-01','TA914125-02','TA914125-03','TA914125-04','TM017879-02','TM042458-01','TM042458-02','TM042458-03','TM042526-01','TM073073-01','TM080384-01','TM080384-02','TM080384-03','TM216434-01','TM216434-02','TM329140-01','TM329140-02','TM329140-03','TM425072-01','TM425072-02','TM425072-03','TM432413-01','TM432413-03','TM481377-01','TM505544-01','TM505544-02','TM559041-01','TM625894-01','TM625894-02','TM625894-03','TM863291-02','TM863291-03','TM865428-01','TM865428-02','TO555713-01','TO916426-01','TO916426-03','TP135820-03','TP135820-04','TP459998-01','TP459998-02','TT009941-01','TT009941-02','TT009941-03','TT009941-04','TT029458-01','TT029458-02','TT051935-01','TT078112-01','TT078112-02','TT102894-02','TT102894-03','TT123484-01','TT123484-02','TT125691-01','TT125691-02','TT125691-03','TT125691-05','TT145767-01','TT145767-02','TT145767-06','TT162834-01','TT162834-03','TT203922-03','TT217878-01','TT217878-03','TT217878-04','TT217878-05','TT217878-06','TT256753-02','TT265225-02','TT531729-01','TT531729-02','TT531729-03','TT531729-04','TT579425-01','TT579425-03','TT633559-01','TT753613-01','TT802724-02','TU796833-01','TU796833-02','TU796833-03','TU796833-04','TX016856-01','TX016856-02','TX019334-01','TX019334-02','TX022098-01','TX022098-02','TX026556-01','TX049218-01','TX049218-02','TX055276-01','TX055276-02','TX075313-01','TX075313-02','TX149552-03','TX149552-04','TX314798-01','TX357178-01','TX357178-02','TX357178-03','TX448843-01','TX452142-02','TX452142-03','TX457614-01','TX457614-02','TX491611-01','TX512293-01','TX512713-03','TX531583-02','TX531583-04','TX548130-01','TX548130-02','TX548130-03','TX578858-01','TX579630-01','TX579630-02','TX579630-03','TX579630-04','TX579630-05','TX687442-01','TX687442-02','TX687442-03','TX723249-01','TX784042-01','TX784042-02','TX784042-03','TX784042-04','TX816080-01','TX816080-02','TX816080-03','TX816080-04','TX823437-03','TX827070-01','TX828043-01','TX828043-02','TX893639-01','TX893639-02','TX893639-03','TX893639-04','TX893639-05','TX893639-06','TX898397-01','TX898397-02','TX898397-03','TX898397-04','TX898397-05','VFP0165-02','VFP0165-03','VFP0290-03','VFP0290-05','VFP0306-13','VFP0306-14','VFP0306-15','VFT0269-02','VFT0269-03','WA009975-01','WA009975-03','WA009975-04','WA011292-02','WA011292-03','WA011292-04','WA012308-01','WA012308-02','WA012308-03','WA012308-04','WA027698-02','WA027698-04','WA028961-01','WA028961-02','WA032234-02','WA032234-03','WA032234-04','WA032313-01','WA034265-03','WA034265-04','WA034265-05','WA043668-01','WA043668-02','WA043668-03','WA043790-02','WA043790-03','WA044485-01','WA052962-01','WA053654-01','WA053654-02','WA053654-03','WA054245-01','WA054245-02','WA055182-03','WA055182-04','WA055921-01','WA055921-02','WA062782-03','WA071469-01','WA071469-02','WA071469-03','WA072828-01','WA072828-02','WA077858-01','WA081343-01','WA081343-02','WA081343-03','WA081832-01','WA082394-01','WA082846-02','WA096553-04','WA144030-06','WA245023-01','WA247145-01','WA247145-02','WA255833-02','WA263231-02','WA263231-03','WA283939-01','WA283939-03','WA331129-01','WA331129-02','WA349127-03','WA349127-07','WA351969-01','WA351969-03','WA351969-04','WA351969-06','WA376026-01','WA376026-02','WA376026-03','WA377494-01','WA377494-03','WA387390-01','WA395229-01','WA435611-02','WA454518-02','WA454518-04','WA454518-05','WA454518-06','WA457528-02','WA457528-03','WA457528-04','WA501932-01','WA501932-02','WA501932-03','WA501932-04','WA501932-05','WA501932-06','WA515812-01','WA515812-02','WA515812-03','WA582748-01','WA585777-01','WA609979-01','WA624495-01','WA624495-02','WA649172-01','WA649172-02','WA649172-03','WA649172-04','WA681769-01','WA681769-02','WA684477-01','WA688337-04','WA688337-05','WA696761-02','WA701741-01','WA719457-01','WA719457-02','WA732786-01','WA732786-03','WA732786-05','WA737113-01','WA757532-01','WA757532-02','WA784562-01','WA784562-02','WA847069-01','WA847069-02','WA859798-01','WA864696-01','WA864696-02','WA864696-03','WA881835-01','WA881835-03','WA885863-01','WA885863-02','WA899452-02','WA905785-01','WA905785-02','WA905785-03','WA924811-01','WA924811-02','WA953018-01','WA953018-02','WA953018-03','WA953018-04','WA953018-05','WA959659-01','WA959659-02','WA973195-01','WA974038-01','WA981358-01','WA991077-01','WA991077-03','WI294825-01','WI294825-03','WM088648-01','WM456857-01','WM456857-02','WM456857-03','WM702211-01','WM702211-02','WM702211-03','WM707434-05','WO483822-01','WO483822-02','ZM0980-01','ZM0980-02','ZM0986-01','ZM0986-03','ZP0944-01','ZX0926-01','ZX0926-02','DA319718-01','DX041919-01','DX338853-01','DX338853-02','FA0654-01','FA0654-03','FA0654-04','FA0654-05','FA0841-01','FP0186-03','FP0300-01','FP0334-01','FP0472-01','FP0663-02','FP0664-01','FP0668-07','FX0118-01','FX0159-01','FX0160-01','FX0382-02','FX0721-01','FX0721-02','FX0774-01','HP0223-02','HP0661-01','OA01451-04','OA01767-03','OA01767-10','OA01787-01','OA01803-01','OA01805-01','OA01815-01','OA01825-02','OA01830-01','OA01840-01','OA01881-01','OA01936-02','OA01942-02','OA02001-01','OA02001-02','OA02037-01','OA02038-01','OA02043-05','OA02110-01','OA02110-02','OA02137-01','OA02137-03','OA094582-01','OA094582-02','OA094582-03','OA094582-04','OM202513-01','OM202513-02','OX723043-01','ST01241-01','ST01241-02','VFP0261-02','VFP0273-01','VFP0306-09','WA01647-01','WM012934-03','ZM0957-02','ZM0957-03','ZM0957-04','ZM0978-01','ZM0978-02','ZM0979-01','ZM0979-02','ZM0979-03','ZM0979-04','ZM0981-01','ZM0981-02','ZM0981-03','ZM0982-02','ZP0940-01','ZX0923-05','FM0395-01','DA162085-01','DA164431-02','DA164431-03','DA164431-04','DA385610-04','DI014088-02','DI054752-01','DI054752-03','DI067388-01','DI067388-02','DI302433-01','DI488365-01','DX619533-02','FA0346-05','FA0407-01','FA0431-01','FA0431-02','FA0431-03','FA0754-01','FA0761-02','FA0761-03','FA0764-01','FA0809-01','FA0835-01','FA0846-01','FM0125-04','FM0197-01','FP0174-01','FP0669-05','FX0239-03','FX0419-01','GA166956-01','GA385449-01','GA435020-01','GA435020-02','GA541040-01','GA734892-01','GX005686-01','GX005686-02','GX059139-01','GX322474-01','GX322474-02','GX322474-03','OA01499-03','OA01499-04','OA01517-02','OA01542-05','OA01581-01','OA01589-01','OA01592-01','OA01767-09','OA01767-11','OA01767-12','OA01824-02','OA01828-02','OA01829-01','OA01829-02','OA01833-02','OA01834-01','OA01834-03','OA01838-02','OA01870-01','OA01904-01','OA01904-03','OA01922-01','OA01923-02','OA01942-01','OA01946-01','OA01946-02','OA01989-01','OA01997-02','OA02007-04','OA02007-06','OA02007-09','OA02070-02','OA02076-01','OA02134-01','OA348676-01','OA458289-01','OA718726-01','OA853169-01','OA853169-02','OI004710-01','OI016748-01','OI016748-02','OI432591-01','OI611693-01','OI611693-02','OI611693-03','OI694780-01','OI716520-01','OI731923-01','OI815956-02','OI946147-01','OI986882-01','OI986882-02','OI986882-03','OI998123-01','OI998123-02','OI998123-03','OM01836-02','OM858834-01','OM858834-02','OP000774-01','OP01957-02','OP01957-04','OP01977-01','OP01983-01','OP01983-02','OP01983-03','OP01983-04','OP432631-03','OT01972-01','OX01970-01','OX02022-02','OX02091-01','SA004168-01','SA010588-02','SA010588-03','SA145262-03','SA145262-04','SA271795-01','SA271795-02','SA271795-03','SA299973-01','SA299973-02','SA323438-01','SA323438-02','SA323438-03','SA397725-01','SA647319-01','SA647319-02','SA647319-04','TX225717-01','TX225717-02','TX225717-03','VFP0169-01','VFP0171-02','VFP0261-01','VFP0280-01','VFP0280-02','WA000487-03','WA000487-04','WA000487-05','WA01604-02','WA01635-01','WA022959-01','WA022959-02','WA022959-03','WA022959-04','WA035255-02','WA035255-03','WA053689-01','WA053689-02','WA053689-03','WA067826-01','WA067826-02','WA067826-03','WA099544-01','WA104084-05','WA142377-01','WA142377-04','WA159790-01','WA159790-02','WA163519-02','WA163519-03','WA163519-04','WA174020-01','WA192071-02','WA192071-04','WA198431-01','WA198431-02','WA198431-03','WA236697-03','WA238643-01','WA238643-02','WA245023-02','WA258752-04','WA258752-05','WA314324-01','WA314324-02','WA314324-03','WA336474-04','WA397898-01','WA397898-02','WA397898-03','WA432768-01','WA432768-02','WA495923-01','WA558432-01','WA558432-02','WA558432-03','WA558432-04','WA639441-01','WA643927-01','WA643927-02','WA643927-03','WA645483-01','WA645483-02','WA685033-01','WA716339-01','WA716339-02','WA716339-04','WA716339-06','WA738386-01','WA738386-02','WA769247-01','WA769247-03','WA769247-04','WA801627-01','WA801627-02','WA801627-03', 'WA801627-04','WA814760-01','WA814760-02','WA921017-01','WA921017-03','WA927534-01','WA927534-02','WA942416-01','WA942416-03','WA942416-04','WA942416-05','WI958754-01','WM213083-01','WM213083-02','WM213083-03','WM243594-01','WM243594-02','WM243594-03','WM243594-04','WM525286-01','WM525286-02','WM525286-03','WM818539-01','WM818539-02','WX123073-01','WX123073-02','WX123073-03','WX256410-01','WX256410-02','WX344250-01','WX344250-02','WX553661-01','WX553661-02','WX553661-03','WX553661-04','WX709297-03','WX709297-04','WX709297-05','WX872036-01','WX872036-03','WX975249-01','ZX0925-01','ZX0925-02','FA0179-01','FA0213-01','FA0225-01','FP0124-03','FP0124-04','FP0200-01','FT0139-02','FX0206-05','FX0306-01','FX0757-02','FX0757-06','OA01875-01','OA01875-02','OA01991-01','OX01878-02','OX01950-01','OX01950-02','SM01319-01','WA703642-01','WX505995-01','DA028918-02','DA028918-03','DA028918-04','FA0838-01','FA0843-02','FA0852-01','FX0239-01','FX0239-02','FX0823-01','FX0823-02','GA009073-02','GA009073-03','GA017734-01','GA055539-01','GA491860-01','GA569445-01','GA569445-02','GA569445-03','GA913285-01','GA913285-03','GX027455-01','GX066079-01','GX965450-01','OA01522-02','OA01876-02','OA01896-01','OA01896-02','OA337084-01','OM01932-01','OX01841-01','OX925716-01','OX925716-02','SA028713-01','SA595052-01','SA595052-02','SA595052-03','SA595052-04','WA002947-01','WA002947-02','WA002947-03','WA002947-04','WA01701-01','WA036751-01','WA036751-02','WA097200-01','WA097200-04','WA097200-05','WA528512-01','WA528512-02','WA528512-03','WA528512-04','WA528512-05','WA528512-06','WA557588-01','WA558115-01','WA558115-03','WA666832-01','WA948061-01','WA958512-01','WA969028-01','WA969028-02','WA969028-03','WX186553-01','WX186553-02','WX226282-01','WX868198-02','FP0373-03','OA01947-01','OA01948-01','SM01320-01','ZP0955-01','FA0749-01','FA0802-04','FA0889-02','FP0044-04','FP0445-01','FX0122-01','FX0808-01','FX0872-01','OA01854-01','OA01854-02','OA01864-01','OA01944-01','OA01963-01','OA02125-01','OO159042-01','SN01328-03','VFT0269-01','WA01742-03','WA01746-01','WA948372-01','ZA0971-02','ZP0937-03','DA062140-01','DA062140-02','DA062140-03','DA062140-04','DA098740-02','DA098740-03','DA275259-01','DA275259-04','DA281863-02','DA281863-04','DA424185-04','DA586983-01','DA586983-02','DA654589-02','DA654589-04','DX002816-02','DX002816-03','DX549954-01','DX549954-02','DX595884-02','DX595884-03','DX595884-04','FX0752-01','FX0788-01','GA017474-01','GA042726-01','GA055723-01','GA065427-01','GA301763-04','GA301763-05','GA427263-01','GA455778-01','GA532373-01','GA693471-01','GX060286-01','OA01776-08','OA01856-01','OA01856-02','OA01856-03','OA01857-04','OA01897-01','OA01897-02','OA02008-01','OA02008-02','OA02008-04','OA02026-01','OA02123-01','OA02123-02','OA02124-01','OA023526-01','OA124856-01','OA124856-02','OA124856-03','OA407086-02','OA407086-03','OA984626-01','OI072635-01','OI072635-02','OI076236-01','OI076236-04','OO836688-01','OX003768-02','OX038829-01','OX038829-02','OX864240-01','OX864240-02','OX876699-01','SA137325-01','SA137325-02','SA137325-03','SA198221-01','SA198221-02','SA223928-01','SA223928-02','SA268647-01','SA268647-02','SA268647-03','SA268647-04','SA292882-01','SA292882-02','SA941431-01','SA941431-02','SA941431-03','SA941431-04','SA941431-05','WA018352-01','WA018352-02','WA065451-01','WA065451-02','WA066872-01','WA104084-01','WA135389-01','WA135389-02','WA414262-04','WA414262-05','WA414262-06','WA414262-08','WA573696-02','WA631945-01','WA631945-02','WA631945-03','WA631945-04','WA631945-05','WA631945-06','WA687214-01','WA687214-02','WA697015-01','WA697015-02','WA893234-01','WM004930-01','WM004930-02','WM048545-01','WM048545-02','WM366128-01','WM366128-02','WM692931-01','WM692931-02','WM706044-01','WM706044-02','WM829722-01','WM829722-02','WM928151-01','WX057723-02','WX057723-03','WX057723-04','WX221934-02','WX221934-03','WX221934-05','WX435742-01','WX435742-02','WX435742-03','WX435742-04','WX435742-06','WX573789-01','WX573789-02','WX573789-03','ZA0999-01','FA0806-01','FM0878-01','FX0732-01','FX0732-02','FX0772-01','FX0772-02','FX0772-03','OA01776-01','OX02111-01','VFX0191-01','ZI0995-02','FA0065-01','FA0602-01','FT0182-02','FX0416-01','FX0689-01','DI151559-02','DI151559-03','FA0100-01','FA0100-02','FA0178-01','FX0324-01','OI017548-01','OI093443-01','OI485618-01','OI883076-01','OI883076-02','WA503375-03','WA503375-04','OA01822-01','OA01822-02','OA01822-04','ZI0994-01','ZI0994-02','FA0283-01','FA0453-01','FA0453-02','FX0772-04','OA01776-02','OA206621-01','OI01924-01','OA01769-01', 'OA02014-01','OM01490-02','DI014919-01','DI014919-02','DI014919-03','DI038215-01','DI046658-02','DI046658-03','DI048277-01','DI048277-02','DI151785-01','DI216878-01','DI216878-02','DI645990-01','DI645990-02','DI707471-01','DI707471-02','DI743899-01','GI761422-01','OI02115-01','OW02011-01','OX02096-01','OA01800-01','OX02093-01','TI0815-01','FA0924-01','FA0924-04','OA715116-01','OA715116-02','OA715116-03','OA715116-04','FX0540-03','FX0868-02','GI023870-02','GI023870-04','GI095968-02','GI095968-03','GI149379-01','GI149379-02','GI149379-03','GI162062-01','GI162062-02','GI337287-01','GI366610-01','GI366610-04','GI494595-01','GI686597-01','GI889892-01','OA01565-01','OA01822-03','OI016350-01','OI224692-01','OI224692-02','OI224692-03','OI318124-01','OI716123-01','OI716123-02','OI842498-01','OW01844-01','TI0673-03','TI0905-02','ZI0945-01','ZI0945-02','TI0533-01','TI0533-02','OA953522-01','FX0880-01','FX0880-02','ZA0989-02'
        ];


        $items = [];
        $all = count($skus);
        $count = 0;
        foreach ($skus as $sku) {
            echo $count++ . '/' . $all . PHP_EOL;
            $items[$sku] = [
                'sku' => $sku,
                '0' => 0,
                '30' => 0,
                '60' => 0,
                '90' => 0,
                '120' => 0,
                '150' => 0,
                '180' => 0,
            ];
            $data = Db::name('datacenter_sku_day')
                ->where('site',1)
                ->where('sku',$sku)
                ->field('day_date,glass_num')
                ->order('day_date', 'asc')
                ->select();
            $data = array_chunk($data, 30);

            foreach ($data as $i => $datum) {
                $items[$sku][min($i, 6) * 30] += array_sum(array_column($datum, 'glass_num'));
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
            ->setCellValue("F1", "120-150")
            ->setCellValue("G1", "150-180")
            ->setCellValue("H1", "180+");   //利用setCellValues()填充数据

        foreach ($items as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2),  $value['0']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['30']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['60']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['90']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['120']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['150']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['180']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);

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
//            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
//            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
//        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
//        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

//        $writer->save('php://output');
        $writer->save('/tmp/export.xlsx');
    }
    //跑你好站 08-06-08-09的品类数据
    public function goods_type_day_center1($plat, $goods_type,$time)
    {
        $this->order = new NewOrder();
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
        $arr['day_date'] = $start;
        $arr['site'] = $plat;
        $arr['goods_type'] = $goods_type;
        $arr['glass_num'] = $frame_sales_num;
        $arr['sales_total_money'] = $frame_money;

        return $arr;
    }

    public function run_nihao_goods_type_data()
    {
        $time = input('time');
        Db::name('datacenter_goods_type_data')->where('day_date',$time)->where('site',3)->delete();
        $res10 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center1(3, 1,$time));
        if ($res10) {
            echo 'nihao站平光镜ok';
        } else {
            echo 'nihao站平光镜不ok';
        }
        $res11 = Db::name('datacenter_goods_type_data')->insert($this->goods_type_day_center1(3, 2,$time));
        if ($res11) {
            echo 'nihao站配饰ok';
        } else {
            echo 'nihao站配饰不ok';
        }
    }

    public function export_user_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $site = input('site');
        $startDate1 = input('start');
        $endDate1 = input('end');
        $startTime1 = strtotime($startDate1);
        $endTime1 = strtotime($endDate1);
        $this->order = new NewOrder();
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
            ]
        ];


        $where2['payment_time'] = ['<', $startTime1];
        $oldAllUser = $this->order
            ->where($where)
            ->where($where2)
            ->field('customer_email')
            ->group('customer_email')
            ->column('customer_email');
        $where1['payment_time'] = ['between', [$startTime1, $endTime1]];
        $timeUser = $this->order
            ->where($where)
            ->where($where1)
            ->field('customer_email')
            ->group('customer_email')
            ->column('customer_email');



        $newUser = array_diff($timeUser, $oldAllUser);
        dump('新客数');
        dump(count($newUser));
        dump('下单客户数');
        dump(count($timeUser));
        dump('-------------------------------------------');
//        dump($newUser);
//        dump('-------------------------------------------');
//        dump($timeUser);
//        dump('-------------------------------------------');
//        dump($oldAllUser);

        die;
    }
    /**
     * 当月
     * @author liushiwei
     * @date   2021/9/28 17:42
     */
    public function export_user_data_two()
    {
        $site = input('site');
        $startDate1 = input('start');
        $endDate1 = input('end');
        $startTime1 = strtotime($startDate1);
        $endTime1 = strtotime($endDate1);
        $useEndDate = date('Y-m-d H:i:s',strtotime("$startDate1+1 month"));
        //往后三年的开始时间
        $startDateTwo = $startDateThree = $startDateFour =  strtotime("$startDate1+1 month");
        //第一年的结束时间
        $endDateTwo = strtotime("$useEndDate+1 year");
        $endDateThree = strtotime("$useEndDate+2 year");
        $endDateFour  = strtotime("$useEndDate+3 year");
        $this->order = new NewOrder();
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
            ]
        ];


        $where2['payment_time'] = ['<', $startTime1];
        $oldAllUser = $this->order
            ->where($where)
            ->where($where2)
            ->field('customer_email')
            ->group('customer_email')
            ->column('customer_email');
        $where1['payment_time'] = ['between', [$startTime1, $endTime1]];
        $timeUser = $this->order
            ->where($where)
            ->where($where1)
            ->field('customer_email')
            ->group('customer_email')
            ->column('customer_email');



        $newUser = array_diff($timeUser, $oldAllUser);
        $twoData = $this->old_user_data($site,$startDateTwo,$endDateTwo);
        $threeData = $this->old_user_data($site,$startDateThree,$endDateThree);
        $fourData  = $this->old_user_data($site,$startDateFour,$endDateFour);
        dump('当月新客数');
        dump(count($newUser));
        dump('第2-13月时间');
        dump($startDateTwo);
        dump($endDateTwo);
        dump('第2-25月时间');
        dump($startDateTwo);
        dump($endDateThree);
        dump('第2-37月时间');
        dump($startDateTwo);
        dump($endDateFour);
        dump('第2-13月复购数');
        dump($twoData);
        dump('第2-25月复购数');
        dump($threeData);
        dump('第2-37月复购数');
        dump($fourData);
        die;
    }
    public function old_user_data($site,$startDate1,$endDate1)
    {

        $this->order = new NewOrder();
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
            ]
        ];


        $where2['payment_time'] = ['<', $startDate1];
        $oldAllUser = $this->order
            ->where($where)
            ->where($where2)
            ->field('customer_email')
            ->group('customer_email')
            ->column('customer_email');
        $where1['payment_time'] = ['between', [$startDate1, $endDate1]];
        $timeUser = $this->order
            ->where($where)
            ->where($where1)
            ->field('customer_email')
            ->group('customer_email')
            ->column('customer_email');



        $newUser = array_diff($timeUser, $oldAllUser);
        return count($timeUser)-count($newUser);
    }
}
