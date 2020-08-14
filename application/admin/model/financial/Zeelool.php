<?php

namespace app\admin\model\financial;

use think\Model;
use think\Db;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use app\admin\model\financial\Fackbook;
//use app\admin\model\itemmanage\ItemPlatformSku;
class Zeelool extends Model
{
    // protected $app_id = "438689069966204";
    // protected $app_secret = "1480382aa32283c6c13692908f7738a7";
    // protected $access_token = "EAAGOZCEIuo3wBAMkIOgCGaUjUmgvY4CqtvXWQ2Jf8o2GkuyOls67R1kk04CDWD7BKSqwzQLTMBeaaeTJaRNyqHI5tihJVFoc6qsNvgJZCpf4mgxCHjZC99iZCu63fmPctNRpAyWyAJcdBq4x4eva0IU6Q7N8lk6vgq1yOLOF4hEqNWt8E5ie";
    protected $app_id = '';
    protected $app_secret = '';
    protected $access_token = '';
    protected $accounts = '';
    protected $facebook = '';
    public function __construct()
    {
        parent::__construct();
        $this->facebook = Fackbook::where('platform',1)->find();
        $this->app_id = $this->facebook->app_id;
        $this->app_secret = $this->facebook->app_secret;
        $this->access_token = $this->facebook->access_token;
        $this->accounts   = $this->facebook->accounts;
    }    

    /**
     * 计算facebook费用
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-14 15:25:33
     * @return void
     */
    public function facebook_cost($start_time,$end_time)
    {
        Api::init($this->app_id,$this->app_secret,$this->access_token);
        $all_facebook_spend = 0;
        $accounts = explode(",",$this->accounts);
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = array(
            'time_range' => array('since'=>$start_time,'until'=>$end_time),
            );
            $cursor = $campaign->getInsights([],$params);
            foreach ($cursor->getObjects() as $key => $value) {
               if($value){
                 $all_facebook_spend += $cursor->getObjects()[0]->getData()['spend'];
                }
            }
        }
        return $all_facebook_spend ?: 0;

    }
    /**
     * 计算谷歌费用
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-14 15:27:40
     * @return void
     */
    public function goole_cost($start_time,$end_time)
    {
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // Call the Analytics Reporting API V4.
        $response = $this->getReport($analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);

       return $result[0]['ga:adCost'] ?: 0; 
    }
    protected function getReport($analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        $VIEW_ID = config('GOOGLE_ANALYTICS_VIEW_ID');


        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);   

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:adCost");
        $adCostMetric->setAlias("ga:adCost");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        // $request->setDimensions(array($sessionDayDimension));

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
     * 计算镜架成本、镜片成本、运输成本、总价格
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-14 17:13:20
     * @return void
     */
    public function frame_cost($start_time,$end_time)
    {
        //查询所有镜架成本
        $this->itemPlatform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $where['is_visable'] = 1;
        $sku_bid_price_list = Db::connect('db_zeelool_online')->table('zeelool_product')->where($where)->field('magento_sku,bid_price')->select();
        $whereFrame['o.status'] = ['in',['complete','processing','creditcard_proccessing','free_proccessing']];
        $whereFrame['o.created_at'] = ['between',[$start_time,$end_time]];
        $all_frame_result = Db::connect('db_zeelool')->table('sales_flat_order_item m')->join('sales_flat_order o on m.order_id=o.entity_id')
        ->where($whereFrame)->field('m.sku,round(sum(m.qty_ordered),0) counter')->group('m.sku')->select();
       //转换sku
        foreach ($all_frame_result as $key => $value) {
            $all_frame_result[$key]['true_sku'] = $this->itemPlatform->getTrueSku($value['sku'],1);
        }

        $all_frame_price=0;
        foreach ($all_frame_result as  $frame_value) {
            foreach ($sku_bid_price_list as  $bid_price_value) {
                if(trim($frame_value['true_sku']) == trim($bid_price_value['magento_sku'])){
                    $all_frame_price += $frame_value['counter']*$bid_price_value['bid_price'];
                }
            }
        }

        return $all_frame_price;
    }

}