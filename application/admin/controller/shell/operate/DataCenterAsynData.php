<?php
/**
 * 运营统计--仪表盘折线图脚本
 */

namespace app\admin\controller\shell\operate;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class DataCenterAsynData extends Command
{
    protected function configure()
    {
        $this->setName('data_center')
            ->setDescription('data_center run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getData(10);
        $this->getData(11);
        $output->writeln("All is ok");
    }

    /**
     * 获取每日数据
     * @author mjj
     * @date   2021/4/15 09:24:50
     */
    public function getData($site)
    {
        //获取datacenter表中德语站和日本站的数据
        $data = Db::name('datacenter_day')
            ->where('site',$site)
            ->where('sessions',0)
            ->select();
        foreach ($data as $value){
            //会话
            $arr['sessions'] = $this->google_session($site, $value['day_date']);
            //计算加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($value['new_cart_num']/$arr['sessions']*100,2) : 0;
            //计算会话转化率
            $arr['session_rate'] = $arr['sessions'] ? round($value['order_num']/$arr['sessions']*100,2) : 0;
            Db::name('datacenter_day')
                ->where('id',$value['id'])
                ->update($arr);
            echo $value['id']."---".$value['day_date']." is ok";
            usleep(10000);
        }
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
        $request->setMetrics([$adCostMetric]);
        $request->setDimensions([$sessionDayDimension]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $analytics->reports->batchGet($body);
    }
}