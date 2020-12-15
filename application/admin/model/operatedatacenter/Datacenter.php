<?php

namespace app\admin\model\operatedatacenter;

use think\Db;
use think\Model;


class Datacenter extends Model
{

    // 表名
    protected $name = 'datacenter_day';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];

    public function __construct()
    {
        $this->zeelool = new \app\admin\model\operatedatacenter\Zeelool();
        $this->voogueme = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihao = new \app\admin\model\operatedatacenter\Nihao();
        $this->zeelool_order = new \app\admin\model\order\order\Zeelool();
        $this->voogueme_order = new \app\admin\model\order\order\Voogueme();
        $this->nihao_order = new \app\admin\model\order\order\Nihao();
    }

    //获取着陆页数据
    public function getLanding($time_str = '', $type = 0)
    {
        $z = $this->zeelool->getLanding($time_str, 1);
        $v = $this->voogueme->getLanding($time_str, 1);
        $n = $this->nihao->getLanding($time_str, 1);
        $num['landing_num'] = $z['landing_num'] + $v['landing_num'] + $n['landing_num'];
        return $num;
    }

    //产品详情页
    public function getDetail($time_str = '', $type = 0)
    {
        $z = $this->zeelool->getDetail($time_str, 1);
        $v = $this->voogueme->getDetail($time_str, 1);
        $n = $this->nihao->getDetail($time_str, 1);
        $num['detail_num'] = $z['detail_num'] + $v['detail_num'] + $n['detail_num'];
        return $num;
    }

    //加购
    public function getCart($time_str = '', $type = 0)
    {
        $z = $this->zeelool->getCart($time_str, 1);
        $v = $this->voogueme->getCart($time_str, 1);
        $n = $this->nihao->getCart($time_str, 1);
        $num['cart_num'] = $z['cart_num'] + $v['cart_num'] + $n['cart_num'];
        return $num;
    }

    //交易次数
    public function getComplete($time_str = '', $type = 0)
    {
        $z = $this->zeelool->getComplete($time_str, 1);
        $v = $this->voogueme->getComplete($time_str, 1);
        $n = $this->nihao->getComplete($time_str, 1);
        $num['complete_num'] = $z['complete_num'] + $v['complete_num'] + $n['complete_num'];
        return $num;
    }

    //活跃用户数 调用此方法
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

    //着陆页数据 调用此方法
    public function google_landing($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_landing1($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        return $result;
        // return $result[0]['ga:secondPagePath'] ? round($result[0]['ga:secondPagePath'], 2) : 0;
    }

    //着陆页会话数
    protected function getReport_landing1($site, $analytics, $startDate, $endDate)
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
        //着陆页的数量
        $adCostMetric->setExpression("ga:landingPagePath");
        $adCostMetric->setAlias("ga:landingPagePath");
        $adCostMetric->setExpression("ga:sessions");
        $adCostMetric->setAlias("ga:sessions");

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

    //目标13会话数 调用此方法 产品详情页
    public function google_target13($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_target13($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:goal13Starts'] ? round($result[0]['ga:goal13Starts'], 2) : 0;
    }

    //目标13会话数 产品详情页数据
    protected function getReport_target13($site, $analytics, $startDate, $endDate)
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
        //着陆页的数量
        // $adCostMetric->setExpression("ga:landingPagePath");
        // $adCostMetric->setAlias("ga:landingPagePath");
        // $adCostMetric->setExpression("ga:sessions");
        // $adCostMetric->setAlias("ga:sessions");
        //目标4的数量
        $adCostMetric->setExpression("ga:goal13Starts");
        $adCostMetric->setAlias("ga:goal13Starts");
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

    //目标1会话数 调用此方法 购物车页面
    public function google_target1($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_target1($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:goal1Starts'] ? round($result[0]['ga:goal1Starts'], 2) : 0;
    }

    //目标1会话数 购物车页面数据
    protected function getReport_target1($site, $analytics, $startDate, $endDate)
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
        //着陆页的数量
        // $adCostMetric->setExpression("ga:landingPagePath");
        // $adCostMetric->setAlias("ga:landingPagePath");
        // $adCostMetric->setExpression("ga:sessions");
        // $adCostMetric->setAlias("ga:sessions");
        //目标4的数量
        $adCostMetric->setExpression("ga:goal1Starts");
        $adCostMetric->setAlias("ga:goal1Starts");
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

    //最终电子商务页面交易次数数据
    public function google_target_end($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_target_end($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:transactions'] ? round($result[0]['ga:transactions'], 2) : 0;
    }

    //最终电子商务页面交易次数数据
    protected function getReport_target_end($site, $analytics, $startDate, $endDate)
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
        //着陆页的数量
        // $adCostMetric->setExpression("ga:landingPagePath");
        // $adCostMetric->setAlias("ga:landingPagePath");
        $adCostMetric->setExpression("ga:Ecommerce");
        $adCostMetric->setAlias("ga:Ecommerce");
        //目标4的数量
        $adCostMetric->setExpression("ga:transactions");
        $adCostMetric->setAlias("ga:transactions");
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

    //着陆页（弃用）
    protected function getReport_landing($site, $analytics, $startDate, $endDate)
    {

        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $transactions = new \Google_Service_AnalyticsReporting_Metric();
        $transactions->setExpression("ga:transactions");
        $transactions->setAlias("transactions");

        $pageviews = new \Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");

        $uniquePageviews = new \Google_Service_AnalyticsReporting_Metric();
        $uniquePageviews->setExpression("ga:uniquePageviews");
        $uniquePageviews->setAlias("uniquePageviews");

        $avgTimeOnPage = new \Google_Service_AnalyticsReporting_Metric();
        $avgTimeOnPage->setExpression("ga:avgTimeOnPage");
        $avgTimeOnPage->setAlias("avgTimeOnPage");

        $entrances = new \Google_Service_AnalyticsReporting_Metric();
        $entrances->setExpression("ga:entrances");
        $entrances->setAlias("entrances");

        $entranceRate = new \Google_Service_AnalyticsReporting_Metric();
        $entranceRate->setExpression("ga:entranceRate");
        $entranceRate->setAlias("entranceRate");


        $exits = new \Google_Service_AnalyticsReporting_Metric();
        $exits->setExpression("ga:exits");
        $exits->setAlias("exits");

        $exitRate = new \Google_Service_AnalyticsReporting_Metric();
        $exitRate->setExpression("ga:exitRate");
        $exitRate->setAlias("exitRate");

        $pageValue = new \Google_Service_AnalyticsReporting_Metric();
        $pageValue->setExpression("ga:pageValue");
        $pageValue->setAlias("pageValue");


        $pagePathDimension = new \Google_Service_AnalyticsReporting_Dimension();
        // $browser->setName("ga:browser");
        // $browser->setName("ga:country");
        $pagePathDimension->setName("ga:pagePath");

        $sourceMediumDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sourceMediumDimension->setName("ga:sourceMedium");
        // $sourceMediumDimension->setName("ga:source");
        // $sourceMediumDimension->setName("ga:medium");


        // $sourceMediumDimension->setName("ga:acquisitionSourceMedium");

        // ga:acquisitionSourceMedium

        $ordering = new \Google_Service_AnalyticsReporting_OrderBy();
        $ordering->setFieldName("ga:pageviews");
        $ordering->setOrderType("VALUE");
        $ordering->setSortOrder("DESCENDING");

        // Create the DimensionFilter.
        $dimensionFilter = new \Google_Service_AnalyticsReporting_DimensionFilter();
        $dimensionFilter->setDimensionName('ga:pagePath');
        $dimensionFilter->setOperator('PARTIAL');
        $dimensionFilter->setExpressions(array('-'));

        // Create the DimensionFilterClauses
        $dimensionFilterClause = new \Google_Service_AnalyticsReporting_DimensionFilterClause();
        $dimensionFilterClause->setFilters(array($dimensionFilter));

        // echo '<br>$dateRange<br>';
        // var_dump($dateRange);
        // echo '<br>$sessions<br>';
        // var_dump($sessions);
        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($transactions, $pageviews, $uniquePageviews, $avgTimeOnPage, $entrances, $entranceRate, $exits, $exitRate, $pageValue));
        $request->setDimensions(array($pagePathDimension, $sourceMediumDimension));
        $request->setOrderBys($ordering); // note this one!
        $request->setPageSize(20000);


        $request->setDimensionFilterClauses(array($dimensionFilterClause));


        // echo '<br>$request<br>';
        // var_dump($request);
        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        // echo '<br>$body<br>';
        // var_dump($body);
        // echo '<br>$batchGet<br>';
        // var_dump($analytics->reports->batchGet($body));
        return $analytics->reports->batchGet($body);
    }

    //session 调用此方法
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

    /**
     * 活跃用户数
     *
     * @type 0:计算某天的数据1：计算总的数据
     * 当type == 0时，$time_str传某天时间；当type == 1时，$time_str传时间段
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:39:38
     */
    public function getActiveUser($time_str = '', $time_str2 = '')
    {
        //默认查询7天的数据
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        //时间段总和
        $createat = explode(' ', $time_str);
        $where['day_date'] = ['between', [$createat[0], $createat[3]]];
        $arr['active_user_num'] = $this->where($where)->sum('active_user_num');

        if ($time_str2) {
            $createat2 = explode(' ', $time_str2);
            $contrast_where['day_date'] = ['between', [$createat2[0], $createat2[3]]];
            $contrast_active_user_num = $this->where($contrast_where)->sum('active_user_num');
            $arr['contrast_active_user_num'] = $contrast_active_user_num ? round(($arr['active_user_num'] - $contrast_active_user_num) / $contrast_active_user_num * 100, 2) : '0';
        }


        return $arr;
    }

    /**
     * 注册用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:39:50
     */
    public function getRegisterUser($time_str = '', $time_str2 = '')
    {
        //默认查询7天的数据
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        //时间段总和
        $createat = explode(' ', $time_str);
        $where['day_date'] = ['between', [$createat[0], $createat[3]]];
        $arr['register_user_num'] = $this->where($where)->sum('register_num');

        if ($time_str2) {
            $createat2 = explode(' ', $time_str2);
            $contrast_where['day_date'] = ['between', [$createat2[0], $createat2[3]]];
            $contrast_register_user_num = $this->where($contrast_where)->sum('register_num');
            $arr['contrast_register_user_num'] = $contrast_register_user_num ? round(($arr['register_user_num'] - $contrast_register_user_num) / $contrast_register_user_num * 100, 2) : '0';
        }

        return $arr;
    }

    /**
     * 复购用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:04
     */

    public function getAgainUser($time_str = '', $time_str2 = '')
    {
        $createat = explode(' ', $time_str);
        $again_user_numzeelool = $this->zeelool->get_again_user($createat);
        $again_user_numvoogueme = $this->voogueme->get_again_user($createat);
        $again_user_numnihao = $this->nihao->get_again_user($createat);
        //三个站所有的复购用户数
        $arrs['again_user_num'] = $again_user_numzeelool + $again_user_numvoogueme + $again_user_numnihao;

        if ($time_str2) {
            $createat = explode(' ', $time_str);
            $again_user_numzeelool = $this->zeelool->get_again_user($createat);
            $again_user_numvoogueme = $this->voogueme->get_again_user($createat);
            $again_user_numnihao = $this->nihao->get_again_user($createat);
            //三个站所有的复购用户数
            $arrs['contrast_again_user_num'] = $again_user_numzeelool + $again_user_numvoogueme + $again_user_numnihao;
            $arrs['contrast_again_user_num'] = $arrs['again_user_num'] == 0 ? '100' : round(($arrs['contrast_again_user_num'] - $arrs['again_user_num']) / $arrs['again_user_num'] * 100, 2);
        }
        return $arrs;
    }


    /**
     * vip用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:15
     */
    public function getVipUser($time_str = '', $time_str2 = '')
    {
        //默认查询7天的数据
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        //时间段总和
        $createat = explode(' ', $time_str);
        $where['day_date'] = ['between', [$createat[0], $createat[3]]];
        $arr['vip_user_num'] = $this->where($where)->sum('vip_user_num');

        //对比数据
        if ($time_str2) {
            $createat2 = explode(' ', $time_str2);
            $contrast_where['day_date'] = ['between', [$createat2[0], $createat2[3]]];
            $contrast_vip_user_num = $this->where($contrast_where)->sum('vip_user_num');
            $arr['contrast_vip_user_num'] = $contrast_vip_user_num == 0 ? '100' : round(($arr['vip_user_num'] - $contrast_vip_user_num) / $contrast_vip_user_num * 100, 2);
        }
        return $arr;
    }

    /**
     * 统计订单All
     * 0:计算某天的数据1：计算总的数据
     * 当type == 0时，$time_str传某天时间；当type == 1时，$time_str传时间段
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 10:44:38
     */
    public function getOrderNum($time_str = '', $time_str2 = '')
    {
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        //时间段总和
        $createat = explode(' ', $time_str);
        $where['day_date'] = ['between', [$createat[0], $createat[3]]];
        $arr['order_num'] = $this->where($where)->sum('order_num');
        if ($time_str2) {
            $createat2 = explode(' ', $time_str2);
            $huan_where['day_date'] = ['between', [$createat2[0], $createat2[3]]];
            $contrast_order_num = $this->where($huan_where)->sum('order_num');
            $arr['contrast_order_num'] = $contrast_order_num ? round(($arr['order_num'] - $contrast_order_num) / $contrast_order_num * 100, 2) : 0;
        }
        return $arr;
    }

    /*
     * 统计客单价
     */
    public function getOrderUnitPrice($time_str = '', $time_str2 = '')
    {
        $z = $this->zeelool->getOrderUnitPrice($time_str, $time_str2);
        $v = $this->voogueme->getOrderUnitPrice($time_str, $time_str2);
        $n = $this->nihao->getOrderUnitPrice($time_str, $time_str2);
        $num['order_unit_price'] = round($z['order_unit_price'] + $v['order_unit_price'] + $n['order_unit_price'], 2);
        $num['contrast_order_unit_price'] = round(($z['contrast_order_unit_price'] + $v['contrast_order_unit_price'] + $n['contrast_order_unit_price']) / 3, 2);
        return $num;
    }

    /*
     * 统计销售额
     */
    public function getSalesTotalMoney($time_str = '', $time_str2 = '')
    {
        $z = $this->zeelool->getSalesTotalMoney($time_str, $time_str2);
        $v = $this->voogueme->getSalesTotalMoney($time_str, $time_str2);
        $n = $this->nihao->getSalesTotalMoney($time_str, $time_str2);
        $num['sales_total_money'] = round($z['sales_total_money'] + $v['sales_total_money'] + $n['sales_total_money'], 2);
        $num['contrast_sales_total_num'] = round(($z['contrast_sales_total_num'] + $v['contrast_sales_total_num'] + $n['contrast_sales_total_num']) / 3, 2);
        return $num;
    }

    /*
     * 统计邮费
     * */
    public function getShippingTotalMoney($time_str = '', $time_str2 = '')
    {
        $z = $this->zeelool->getShippingTotalMoney($time_str, $time_str2);
        $v = $this->voogueme->getShippingTotalMoney($time_str, $time_str2);
        $n = $this->nihao->getShippingTotalMoney($time_str, $time_str2);
        $num['shipping_total_money'] = round($z['shipping_total_money'] + $v['shipping_total_money'] + $n['shipping_total_money'], 2);
        $num['contrast_shipping_total_money'] = round(($z['contrast_shipping_total_money'] + $v['contrast_shipping_total_money'] + $n['contrast_shipping_total_money']) / 3, 2);
        return $num;
    }


}
