<?php

namespace app\admin\model\financial;

use think\Model;
use think\Db;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use app\admin\model\financial\Fackbook;
//use app\admin\model\itemmanage\ItemPlatformSku;
class ZeeloolDe extends Model
{

    //数据库
    protected $connection = 'database.db_zeelool_de';


    // 表名
    protected $table = 'sales_flat_order';
    protected $app_id = '';
    protected $app_secret = '';
    protected $access_token = '';
    protected $accounts = '';
    protected $facebook = '';
    public function __construct()
    {
        parent::__construct();
        $this->facebook = Fackbook::where('platform', 10)->find();
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
    public function facebook_cost($start_time, $end_time)
    {
        Api::init($this->app_id, $this->app_secret, $this->access_token);
        $all_facebook_spend = 0;
        $accounts = explode(",", $this->accounts);
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = array(
                'time_range' => array('since' => $start_time, 'until' => $end_time),
            );
            $cursor = $campaign->getInsights([], $params);
            foreach ($cursor->getObjects() as $key => $value) {
                if ($value) {
                    $all_facebook_spend += $cursor->getObjects()[0]->getData()['spend'];
                }
            }
        }
        return $all_facebook_spend ? round($all_facebook_spend, 2) : 0;
    }
    /**
     * 计算谷歌费用
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-14 15:27:40
     * @return void
     */
    public function goole_cost($start_time, $end_time)
    {
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // Call the Analytics Reporting API V4.
        $response = $this->getReport($analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);

        return $result[0]['ga:adCost'] ? round($result[0]['ga:adCost'], 2) : 0;
    }
    protected function getReport($analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        $VIEW_ID = config('MEELOOG_GOOGLE_ANALYTICS_VIEW_ID');

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
     * 计算镜架成本、镜片成本、运输成本、销售额
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-14 17:13:20
     * @return void
     */
    public function all_cost($start_time, $end_time)
    {
        //查询所有镜架成本

        $this->itemPlatform = new \app\admin\model\itemmanage\ItemPlatformSku;
        $start_time = $start_time . ' 00:00:00';
        $end_time   = $end_time . ' 23:59:59';
        $this->item = new \app\admin\model\itemmanage\Item();
        //查询产品库镜框采购单价
        $sku_list = $this->item->where(['is_open' => 1, 'is_del' => 1])->column('purchase_price', 'sku');

        $whereFrame['o.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing', 'free_proccessing']];
        $whereFrame['o.created_at'] = ['between', [$start_time, $end_time]];
        //Db::connect('database.db_zeelool')->query("SET time_zone = '+8:00'");
        $all_frame_result = Db::connect('database.db_meeloog')->table('sales_flat_order_item m')->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->where($whereFrame)->field('m.sku,round(sum(m.qty_ordered),0) counter')->group('m.sku')->select();
        //镜架成本
        $all_frame_price = 0;
        foreach ($all_frame_result as $key => $value) {
            $true_sku = $this->itemPlatform->getTrueSku($value['sku'], 4);
            $all_frame_price += $value['counter'] * $sku_list[trim($true_sku)];
        }
        //求镜片成本价格
        // $all_lens_result = Db::connect('database.db_zeelool_online')->table('sales_flat_order_item m')->join('sales_flat_order o', 'o.entity_id=m.order_id','left')
        // ->join('sales_lens sl', 'sl.lens_id=m.lens_id','left')->where($whereFrame)
        // ->field('round(sum(m.qty_ordered*sl.left_lens_cost_price),2) left_lens_cost_price,round(sum(m.qty_ordered*sl.right_lens_cost_price),2) right_lens_cost_price')
        // ->select();
        //镜片成本
        // $all_lens_price = round($all_lens_result[0]['left_lens_cost_price'] + $all_lens_result[0]['right_lens_cost_price'], 2);

        $all_lens_price = 0;

        //求销售额、运费、毛利润
        $base_grand_total_result = Db::connect('database.db_meeloog')->table('sales_flat_order o')->where($whereFrame)
            ->field('sum(o.base_grand_total) base_grand_total,sum(o.shipping_amount) shipping_amount')->select();
        //销售额
        $all_base_grand_total = round($base_grand_total_result[0]['base_grand_total'], 2);
        //运费
        $all_shipping_amount  = round($base_grand_total_result[0]['shipping_amount'], 2);
        return [
            'all_frame_price'       => $all_frame_price ? round($all_frame_price, 2) : 0,
            'all_lens_price'        => $all_lens_price ? round($all_lens_price, 2) : 0,
            'all_base_grand_total'  => $all_base_grand_total ? round($all_base_grand_total, 2) : 0,
            'all_shipping_amount'   => $all_shipping_amount ? round($all_shipping_amount, 2) : 0
        ];
    }
    /**
     * 计算成本控制器
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-15 09:58:16
     * @return void
     */
    public function index_cost($rate, $start_time, $end_time)
    {
        //google金额
        $google_money   = $this->goole_cost($start_time, $end_time);
        //facebook金额
        $facebook_money = $this->facebook_cost($start_time, $end_time);
        
        //镜框等价格
        $all_money      = $this->all_cost($start_time, $end_time);
        //销售额
        $all_base_grand_total       = $all_money['all_base_grand_total'];
        //运费
        $all_shipping_amount        = $all_money['all_shipping_amount'];
        //镜架成本
        $all_frame_price            = round($all_money['all_frame_price'] / $rate, 2);
        //镜片成本
        $all_lens_price             = $all_money['all_lens_price'];
        //利润
        $all_profit     = round(($all_base_grand_total - $all_shipping_amount - $all_frame_price - $all_lens_price - $google_money - $facebook_money), 2);

        //求出人民币成本和比率
        $all_base_grand_total_rate = round($all_base_grand_total * $rate, 2);
        $all_shipping_amount_rate  = round($all_shipping_amount * $rate, 2);
        $all_frame_price_rate      = round($all_frame_price * $rate, 2);
        $all_lens_price_rate       = round($all_lens_price * $rate, 2);
        $all_profit_rate           = round($all_profit * $rate, 2);
        $facebook_money_rate       = round($facebook_money * $rate, 2);
        $google_money_rate         = round($google_money * $rate, 2);
        if (0 < $all_base_grand_total) {
            $shipping_percent      = round($all_shipping_amount / $all_base_grand_total * 100, 2);
            $frame_percent         = round($all_frame_price / $all_base_grand_total * 100, 2);
            $lens_percent          = round($all_lens_price / $all_base_grand_total * 100, 2);
            $google_percent        = round($google_money / $all_base_grand_total * 100, 2);
            $facebook_percent      = round($facebook_money / $all_base_grand_total * 100, 2);
            $profit_percent        = round($all_profit / $all_base_grand_total * 100, 2);
        } else {
            $shipping_percent = $frame_percent = $lens_percent = $google_percent = $facebook_percent = $profit_percent = 0;
        }

        $arr = [
            ['type' => '运费', 'money_us' => $all_shipping_amount, 'money_cn' => $all_shipping_amount_rate, 'percent' => $shipping_percent],
            ['type' => '镜架成本', 'money_us' => $all_frame_price, 'money_cn' => $all_frame_price_rate, 'percent' => $frame_percent],
            ['type' => '镜片成本', 'money_us' => $all_lens_price, 'money_cn' => $all_lens_price_rate, 'percent' => $lens_percent],
            ['type' => 'Google Adwords', 'money_us' => $google_money, 'money_cn' => $google_money_rate, 'percent' => $google_percent],
            ['type' => 'Facebook', 'money_us' => $facebook_money, 'money_cn' => $facebook_money_rate, 'percent' => $facebook_percent],
            ['type' => '毛利润', 'money_us' => $all_profit, 'money_cn' => $all_profit_rate, 'percent' => $profit_percent],
            ['type' => '销售额', 'money_us' => $all_base_grand_total, 'money_cn' => $all_base_grand_total_rate, 'percent' => ''],
        ];
        return $arr;
    }
}
