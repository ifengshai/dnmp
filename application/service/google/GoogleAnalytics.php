<?php
/**
 * GoogleAnalytics.php
 * @author huangbinbin
 * @date   2021/8/10 17:12
 */

namespace app\service\google;


use app\enum\GoogleId;
use app\enum\Site;

class GoogleAnalytics
{
    public $viewId = '';

    public function __construct($site)
    {
        $this->getConfig($site);
    }

    /**
     * 获取google的view id
     *
     * @param $site
     *
     * @author crasphb
     * @date   2021/4/14 11:32
     */
    public function getConfig($site)
    {
        switch ($site) {
            case Site::ZEELOOL:
                $this->viewId = GoogleId::ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::VOOGUEME:
                $this->viewId = GoogleId::VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::NIHAO:
                $this->viewId = GoogleId::NIHAO_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::MEELOOG:
                $this->viewId = GoogleId::MEELOOG_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::WESEEOPTICAL:
                $this->viewId = GoogleId::WESEEOPTICAL_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::ZEELOOL_ES:
                $this->viewId = GoogleId::ZEELOOLES_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::ZEELOOL_DE:
                $this->viewId = GoogleId::ZEELOOLDE_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            case Site::ZEELOOL_JP:
                $this->viewId = GoogleId::ZEELOOLJP_GOOGLE_ANALYTICS_VIEW_ID;
                break;
            default:
                $this->viewId = GoogleId::ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID;
        }
    }

    /**
     * 获取时间段内的ga数据
     * @param $start
     * @param $end
     *
     * @return array
     * @throws \Google_Exception
     * @author huangbinbin
     * @date   2021/8/10 17:34
     */
    public function getGaResult($start,$end)
    {
        $initializeAnalytics = $this->initializeAnalytics();
        $response = $this->getReport($initializeAnalytics,$start,$end);
        return $this->printResults($response);
    }

    /**
     * @return \Google_Service_AnalyticsReporting
     * @throws \Google_Exception
     * @author huangbinbin
     * @date   2021/8/10 17:15
     */
    public function initializeAnalytics(): \Google_Service_AnalyticsReporting
    {
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);

        return new \Google_Service_AnalyticsReporting($client);
    }

    /**
     * 获取统计信息
     * @param $analytics
     * @param $startDate
     * @param $endDate
     *
     * @return mixed
     * @author huangbinbin
     * @date   2021/8/10 17:28
     */
    protected function getReport($analytics, $startDate, $endDate)
    {
        $VIEW_ID = $this->viewId;
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $pageviews = new \Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");


        $uniquePageviews = new \Google_Service_AnalyticsReporting_Metric();

        $uniquePageviews->setExpression("ga:uniquePageviews");

        $uniquePageviews->setAlias("uniquePageviews");



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


        $pagePathDimension->setName("ga:pagePath");


        $ordering = new \Google_Service_AnalyticsReporting_OrderBy();

        $ordering->setFieldName("ga:pageviews");

        $ordering->setOrderType("VALUE");

        $ordering->setSortOrder("DESCENDING");


        $request = new \Google_Service_AnalyticsReporting_ReportRequest();

        $request->setViewId($VIEW_ID);

        $request->setDateRanges($dateRange);

        $request->setMetrics(array($pageviews, $uniquePageviews, $entrances, $entranceRate, $exits, $exitRate, $pageValue));

        $request->setDimensions(array($pagePathDimension));

        $request->setOrderBys($ordering); // note this one!

        $request->setPageSize(10000);


        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();

        $body->setReportRequests(array($request));

        return $analytics->reports->batchGet($body);
    }

    /**
     * 格式化数据
     * @param $reports
     *
     * @return array
     * @author huangbinbin
     * @date   2021/8/10 17:28
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


                    $finalResult[$rowIndex]['pagePath'] = $dimensions[$i];
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
}