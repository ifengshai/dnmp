<?php
/**
 * Class session.php
 * @package app\service\google
 * @author  crasphb
 * @date    2021/4/14 10:59
 */

namespace app\service\google;

use app\enum\GoogleId;
use app\enum\Site;


class session
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
     * 获取分时数据
     *
     * @param string $site
     * @param        $start_time
     * @param        $end_time
     *
     * @return array
     * @throws \Google_Exception
     * @author crasphb
     * @date   2021/4/14 11:35
     */
    public function gaHourData($start_time, $end_time)
    {
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);

        $analytics = new \Google_Service_AnalyticsReporting($client);

        $response = $this->getReportSession($analytics, $start_time, $end_time);

        return $this->printResults($response);
    }

    /**
     * 获取session数据
     *
     * @param $analytics
     * @param $startDate
     * @param $endDate
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/14 11:34
     */
    protected function getReportSession($analytics, $startDate, $endDate)
    {
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:sessions");
        $adCostMetric->setAlias("ga:sessions");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:dateHour");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$adCostMetric]);
        $request->setDimensions([$sessionDayDimension]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $analytics->reports->batchGet($body);
    }

    /**
     * 格式化数据
     *
     * @param $reports
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 11:34
     */
    protected function printResults($reports)
    {
        $finalResult = [];
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
}