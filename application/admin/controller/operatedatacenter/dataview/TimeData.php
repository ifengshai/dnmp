<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class TimeData extends Backend
{
    /**
     * 分时数据
     *
     * @return \think\Response
     */
    public function index()
    {
        $time_str = input('time_str');
        if(!$time_str){
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d');
            $time_between = $start.' 00:00:00 - '.$end.' 23:59:59';
            $time_show = '';
        }else{
            $createat = explode(' ', $time_str);
            $start = $createat[0];
            $end = $createat[3];
            $time_between = $time_str;
            $time_show = $time_str;
        }
        $web_site = input('order_platform') ? input('order_platform') : 1;
        //获取session
        $ga_result = $this->initializeAnalytics($web_site,$start,$end);
        $finalList = array();
        for ($i = 0; $i < 24; $i++) {
            $finalList[$i]['hour'] = $i;
            $finalList[$i]['hour_created'] = "$i:00 - $i:59";
        }
        foreach ($finalList as $final_key => $final_value) {
            foreach ($ga_result as $ga_key => $ga_value) {
                if ((int)$final_value['hour'] == (int)substr($ga_value['ga:dateHour'], 8)) {
                    $finalList[$final_key]['sessions'] += $ga_value['ga:sessions'];
                }
            }
        }
        dump($finalList);exit;

        //查询对应平台权限
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('data', 'total', 'coating_arr','coating_count','web_site','time_show','magentoplatformarr'));
        return $this->view->fetch();
    }
    protected function initializeAnalytics($site,$start_time,$end_time)
    {
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

        return $result;
    }
    protected function getReport_session($site, $analytics, $startDate, $endDate)
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

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:sessions");
        $adCostMetric->setAlias("ga:sessions");
        // $adCostMetric->setExpression("ga:adCost");
        // $adCostMetric->setAlias("ga:adCost");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:dateHour");

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
     * 销售量
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:09:27 
     * @return void
     */
    public function sales_num_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['销售量'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '销售量',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 销售额
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:49 
     * @return void
     */
    public function sales_money_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['销售额'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '销售额',
                    'smooth' => true //平滑曲线
                ]

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 订单数
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:28 
     * @return void
     */
    public function order_num_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['订单数量'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '订单数量',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 客单价
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:04 
     * @return void
     */
    public function unit_price_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['客单价'];
            $json['columnData'] = [

                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '客单价',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
