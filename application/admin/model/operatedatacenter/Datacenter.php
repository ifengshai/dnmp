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
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
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
    //着陆页数据
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
    //目标1会话数
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
        $response = $this->getReport_landing1($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:goal4Starts'] ? round($result[0]['ga:goal4Starts'], 2) : 0;
    }

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
    //目标1会话数
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
        $adCostMetric->setExpression("ga:goal4Starts");
        $adCostMetric->setAlias("ga:goal4Starts");
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
    public function getActiveUser($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');
        //今天的实时活跃用户数
        $today_active_user = ($this->google_active_user(1, $start)) + ($this->google_active_user(2, $start)) + ($this->google_active_user(3, $start));

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间的数据
            $active_user_num = $this->where($where)->sum('active_user_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['active_user_num'] = $active_user_num + $today_active_user;
            } else {
                $arr['active_user_num'] = $active_user_num;
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($same_where)->sum('active_user_num');
            $arr['same_active_user_num'] = $same_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($huan_where)->sum('active_user_num');
            $arr['huan_active_user_num'] = $huan_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['active_user_num'] = $today_active_user;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['active_user_num'] = $this->where($where)->sum('active_user_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $where['day_date'] = ['between', [$time_str, $time_str]];
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];

            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($same_where)->sum('active_user_num');
            $arr['same_active_user_num'] = $same_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($huan_where)->sum('active_user_num');
            $arr['huan_active_user_num'] = $huan_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

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
    public function getRegisterUser($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        //今天的实时注册用户数
        $today_register_user_num = ($this->zeelool->table('customer_entity')->where($register_where)->count()) + ($this->voogueme->table('customer_entity')->where($register_where)->count()) + ($this->nihao->table('customer_entity')->where($register_where)->count());

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间内的数据
            $register_num = $this->where($where)->sum('register_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['register_user_num'] = $register_num + $today_register_user_num;
            } else {
                $arr['register_user_num'] = $register_num;
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($same_where)->sum('register_num');
            $arr['same_register_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($same_where)->sum('register_num');
            $arr['huan_register_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        } else {
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['register_user_num'] = $today_register_user_num;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['register_user_num'] = $this->where($where)->sum('register_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
            $where['day_date'] = ['between', [$start, $time_str]];
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($same_where)->sum('register_num');
            $arr['same_register_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($same_where)->sum('register_num');
            $arr['huan_register_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';


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
    public function getAgainUser($time_str = '', $type = 0)
    {
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
        } else {
            if ($time_str) {
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            } else {
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
                $where['day_date'] = ['between', [$start, $end]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            }
        }
        $arr['order_unit_price'] = $this->where($where)->sum('order_unit_price');
        $same_order_unit_price = $this->where($same_where)->sum('order_unit_price');
        $huan_order_unit_price = $this->where($huan_where)->sum('order_unit_price');

        $arr['same_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100' . '%' : round(($same_order_unit_price - $arr['order_unit_price']) / $arr['order_unit_price'] * 100, 2) . '%';
        $arr['huan_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100' . '%' : round(($huan_order_unit_price - $arr['order_unit_price']) / $arr['order_unit_price'] * 100, 2) . '%';

        return $arr;
    }

    /**
     * vip用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:15
     */
    public function getVipUser1($time_str = '', $type = 0)
    {
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
        } else {
            if ($time_str) {
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            } else {
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
                $where['day_date'] = ['between', [$start, $end]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            }
        }
        $arr['vip_user_num'] = $this->where($where)->sum('vip_user_num');
        $same_order_unit_price = $this->where($same_where)->sum('vip_user_num');
        $huan_order_unit_price = $this->where($huan_where)->sum('vip_user_num');

        $arr['same_vip_user_num'] = $arr['vip_user_num'] == 0 ? '100' . '%' : round(($same_order_unit_price - $arr['vip_user_num']) / $arr['vip_user_num'] * 100, 2) . '%';
        $arr['huan_vip_user_num'] = $arr['vip_user_num'] == 0 ? '100' . '%' : round(($huan_order_unit_price - $arr['vip_user_num']) / $arr['vip_user_num'] * 100, 2) . '%';

        return $arr;
    }

    public function getVipUser($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $start . "'")];
        $vip_where['order_status'] = 'Success';
        //今天的实时vip用户数 三个站相加
        $today_register_user_num = ($this->zeelool->table('oc_vip_order')->where($vip_where)->where($register_where)->count()
            + ($this->voogueme->table('oc_vip_order')->where($vip_where)->where($register_where)->count())
            + ($this->nihao->table('oc_vip_order')->where($vip_where)->where($register_where)->count()));

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间内的数据
            $register_num = $this->where($where)->sum('vip_user_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['vip_user_num'] = $register_num + $today_register_user_num;
            } else {
                $arr['vip_user_num'] = $register_num;
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($same_where)->sum('vip_user_num');
            $arr['same_vip_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($same_where)->sum('register_num');
            $arr['huan_vip_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        } else {
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['vip_user_num'] = $today_register_user_num;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['vip_user_num'] = $this->where($where)->sum('vip_user_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
            $where['day_date'] = ['between', [$start, $time_str]];
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($same_where)->sum('vip_user_num');
            $arr['same_vip_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($same_where)->sum('vip_user_num');
            $arr['huan_vip_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';
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
    public function getOrderNum1($time_str = '', $type = 0)
    {
        if ($type == 1) {
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
        } else {
            //查询某天的数据
            if ($time_str) {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            } else {
                //查询当天的数据
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
                $where['day_date'] = ['between', [$start, $end]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            }
        }
        // dump($where);
        // dump($same_where);
        // dump($huan_where);
        $arr['order_num'] = $this->where($where)->sum('order_num');
        $same_order_num = $this->where($same_where)->sum('order_num');
        $huan_order_num = $this->where($huan_where)->sum('order_num');
        $arr['same_order_num'] = $arr['order_num'] == 0 ? '100' . '%' : round(($same_order_num - $arr['order_num']) / $arr['order_num'] * 100, 2) . '%';
        $arr['huan_order_num'] = $arr['order_num'] == 0 ? '100' . '%' : round(($huan_order_num - $arr['order_num']) / $arr['order_num'] * 100, 2) . '%';
        // dump($same_order_num);
        // dump($huan_order_num);
        // dump($arr);
        return $arr;
    }

    public function getOrderNum($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->zeelool->where($map_where)->where($arr_where)->count();
        if ($type == 1) {
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $order_num = $this->where($map)->where($where)->sum('order_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['order_num'] = $order_num + $today_order_num;
            } else {
                $arr['order_num'] = $order_num;
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_order_num = $this->where($map)->where($same_where)->sum('order_num');
            $arr['same_order_num'] = $same_order_num != 0 ? round(($arr['order_num'] - $same_order_num) / $same_order_num * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_order_num = $this->where($map)->where($huan_where)->sum('order_num');
            $arr['huan_order_num'] = $huan_order_num != 0 ? round(($arr['order_num'] - $huan_order_num) / $huan_order_num * 100, 2) . '%' : 0;
        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if ($time_str == $start) {
                $arr['order_num'] = $today_order_num;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['order_num'] = $this->where($map)->where($where)->sum('order_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_order_num = $this->where($map)->where($same_where)->sum('order_num');
            $arr['same_order_num'] = $same_order_num != 0 ? round(($arr['order_num'] - $same_order_num) / $same_order_num * 100, 2) . '%' : 0;

            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_order_num = $this->where($map)->where($huan_where)->sum('order_num');
            $arr['huan_order_num'] = $huan_order_num != 0 ? round(($arr['order_num'] - $huan_order_num) / $huan_order_num * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:38:39
     */
    public function getOrderUnitPrice1($time_str = '', $type = 0)
    {
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
        } else {
            if ($time_str) {
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            } else {
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
                $where['day_date'] = ['between', [$start, $end]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            }
        }
        $arr['order_unit_price'] = $this->where($where)->sum('order_unit_price');
        $same_order_unit_price = $this->where($same_where)->sum('order_unit_price');
        $huan_order_unit_price = $this->where($huan_where)->sum('order_unit_price');

        $arr['same_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100' . '%' : round(($same_order_unit_price - $arr['order_unit_price']) / $arr['order_unit_price'] * 100, 2) . '%';
        $arr['huan_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100' . '%' : round(($huan_order_unit_price - $arr['order_unit_price']) / $arr['order_unit_price'] * 100, 2) . '%';

        return $arr;
    }

    /*
     * 统计销售额
     * */
    public function getSalesTotalMoney1($time_str = '', $type = 0)
    {
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
        } else {
            if ($time_str) {
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            } else {
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
                $where['day_date'] = ['between', [$start, $end]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            }
        }

        $arr['sales_total_money'] = $this->where($where)->sum('sales_total_money');
        $same_sales_total_money = $this->where($same_where)->sum('sales_total_money');
        $huan_sales_total_money = $this->where($huan_where)->sum('sales_total_money');

        $arr['same_sales_total_money'] = $arr['sales_total_money'] == 0 ? '100' . '%' : round(($same_sales_total_money - $arr['sales_total_money']) / $arr['sales_total_money'] * 100, 2) . '%';
        $arr['huan_sales_total_money'] = $arr['sales_total_money'] == 0 ? '100' . '%' : round(($huan_sales_total_money - $arr['sales_total_money']) / $arr['sales_total_money'] * 100, 2) . '%';

        return $arr;
    }


    /*
     * 统计邮费
     * */
    public function getShippingTotalMoney1($time_str = '', $type = 0)
    {
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
        } else {
            if ($time_str) {
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            } else {
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
                $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
                $where['day_date'] = ['between', [$start, $end]];
                $same_where['day_date'] = ['between', [$same_start, $same_end]];
                $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            }
        }
        $arr['shipping_total_money'] = $this->where($where)->sum('shipping_total_money');
        $same_shipping_total_money = $this->where($same_where)->sum('shipping_total_money');
        $huan_shipping_total_money = $this->where($huan_where)->sum('shipping_total_money');

        $arr['same_shipping_total_money'] = $arr['shipping_total_money'] == 0 ? '100' . '%' : round(($same_shipping_total_money - $arr['shipping_total_money']) / $arr['shipping_total_money'] * 100, 2) . '%';
        $arr['huan_shipping_total_money'] = $arr['shipping_total_money'] == 0 ? '100' . '%' : round(($huan_shipping_total_money - $arr['shipping_total_money']) / $arr['shipping_total_money'] * 100, 2) . '%';

        return $arr;
    }

    /*
     * 统计客单价
     */
    public function getOrderUnitPrice($time_str = '', $type = 0)
    {
        $map[] = ['exp', Db::raw("customer_id is not null and customer_id != 0")];
        $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        if ($type == 1) {
            //时间段统计客单价
            $createat = explode(' ', $time_str);
            $where['created_at'] = ['between', [$createat[0], $createat[3]]];
            $order_total = $this->zeelool->where($map)->where($where)->sum('base_grand_total');
            $order_user = $this->zeelool->where($map)->where($where)->count('distinct customer_id');
            $arr['order_unit_price'] = $order_user != 0 ? round($order_total / $order_user, 2) : 0;
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['created_at'] = ['between', [$same_start, $same_end]];
            $same_order_total = $this->zeelool->where($map)->where($same_where)->sum('base_grand_total');
            $same_order_user = $this->zeelool->where($map)->where($same_where)->count('distinct customer_id');
            $same_order_unit_price = round($same_order_total / $same_order_user, 2);
            $arr['same_order_unit_price'] = $same_order_unit_price != 0 ? round(($arr['order_unit_price'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['created_at'] = ['between', [$huan_start, $huan_end]];
            $huan_order_total = $this->zeelool->where($map)->where($huan_where)->sum('base_grand_total');
            $huan_order_user = $this->zeelool->where($map)->where($huan_where)->count('distinct customer_id');
            $huan_order_unit_price = round($huan_order_total / $huan_order_user, 2);
            $arr['huan_order_unit_price'] = $huan_order_unit_price != 0 ? round(($arr['order_unit_price'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%' : 0;
        } else {
            if (!$time_str) {
                $time_str = date('Y-m-d');
            }
            $where['created_at'] = ['between', [$time_str, $time_str]];
            $order_total = $this->zeelool->where($map)->where($where)->sum('base_grand_total');
            $order_user = $this->zeelool->where($map)->where($where)->count('distinct customer_id');
            $arr['order_unit_price'] = round($order_total / $order_user, 2);
            //同比
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where['created_at'] = ['between', [$same_start, $same_end]];
            $same_order_total = $this->zeelool->where($map)->where($same_where)->sum('base_grand_total');
            $same_order_user = $this->zeelool->where($map)->where($same_where)->count('distinct customer_id');
            $same_order_unit_price = round($same_order_total / $same_order_user, 2);
            $arr['same_order_unit_price'] = $same_order_unit_price != 0 ? round(($arr['order_unit_price'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%' : 0;
            //环比
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where['created_at'] = ['between', [$huan_start, $huan_end]];
            $huan_order_total = $this->zeelool->where($map)->where($huan_where)->sum('base_grand_total');
            $huan_order_user = $this->zeelool->where($map)->where($huan_where)->count('distinct customer_id');
            $huan_order_unit_price = round($huan_order_total / $huan_order_user, 2);
            $arr['huan_order_unit_price'] = $huan_order_unit_price != 0 ? round(($arr['order_unit_price'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /*
     * 统计销售额
     */
    public function getSalesTotalMoney($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_sales_total_money = $this->zeelool->where($map_where)->where($arr_where)->sum('base_grand_total');
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $sales_total_money = $this->where($map)->where($where)->sum('sales_total_money');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['sales_total_money'] = $sales_total_money + $today_sales_total_money;
            } else {
                $arr['sales_total_money'] = $sales_total_money;
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_sales_total_money = $this->where($map)->where($same_where)->sum('sales_total_money');
            $arr['same_sales_total_money'] = $same_sales_total_money != 0 ? round(($arr['sales_total_money'] - $same_sales_total_money) / $same_sales_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_sales_total_money = $this->where($map)->where($huan_where)->sum('sales_total_money');
            $arr['huan_sales_total_money'] = $huan_sales_total_money != 0 ? round(($arr['sales_total_money'] - $huan_sales_total_money) / $huan_sales_total_money * 100, 2) . '%' : 0;
        } else {
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['sales_total_money'] = $today_sales_total_money;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['sales_total_money'] = $this->where($map)->where($where)->sum('sales_total_money');
            }
            //同比
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_sales_total_money = $this->where($map)->where($same_where)->sum('sales_total_money');
            $arr['same_sales_total_money'] = $same_sales_total_money != 0 ? round(($arr['sales_total_money'] - $same_sales_total_money) / $same_sales_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_sales_total_money = $this->where($map)->where($huan_where)->sum('sales_total_money');
            $arr['huan_sales_total_money'] = $huan_sales_total_money != 0 ? round(($arr['sales_total_money'] - $huan_sales_total_money) / $huan_sales_total_money * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /*
     * 统计邮费
     * */
    public function getShippingTotalMoney($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_shipping_total_money = $this->zeelool->where($map_where)->where($arr_where)->sum('base_shipping_amount');
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $shipping_total_money = $this->where($map)->where($where)->sum('shipping_total_money');
            if ($start <= $createat[3]) {
                $arr['shipping_total_money'] = $shipping_total_money + $today_shipping_total_money;
            } else {
                $arr['shipping_total_money'] = $shipping_total_money;
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_shipping_total_money = $this->where($map)->where($same_where)->sum('shipping_total_money');
            $arr['same_shipping_total_money'] = $same_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $same_shipping_total_money) / $same_shipping_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_shipping_total_money = $this->where($map)->where($huan_where)->sum('shipping_total_money');
            $arr['huan_shipping_total_money'] = $huan_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $huan_shipping_total_money) / $huan_shipping_total_money * 100, 2) . '%' : 0;
        } else {
            if (!$time_str) {
                $time_str = $start;
            }
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if ($time_str == $start) {
                $arr['shipping_total_money'] = $today_shipping_total_money;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['shipping_total_money'] = $this->where($map)->where($where)->sum('shipping_total_money');
            }
            //同比
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_shipping_total_money = $this->where($map)->where($same_where)->sum('shipping_total_money');
            $arr['same_shipping_total_money'] = $same_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $same_shipping_total_money) / $same_shipping_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_shipping_total_money = $this->where($map)->where($huan_where)->sum('shipping_total_money');
            $arr['huan_shipping_total_money'] = $huan_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $huan_shipping_total_money) / $huan_shipping_total_money * 100, 2) . '%' : 0;
        }
        return $arr;
    }


}
