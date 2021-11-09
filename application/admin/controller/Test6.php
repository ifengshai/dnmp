<?php

namespace app\admin\controller;

use app\admin\model\DistributionAbnormal;
use app\admin\model\order\order\LensData;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\saleaftermanage\WorkOrderList;
use app\admin\model\warehouse\StockHouse;
use app\admin\model\zendesk\Zendesk;
use app\admin\model\zendesk\ZendeskTasks;
use app\common\controller\Backend;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use think\Db;
use fast\Http;

class Test6 extends Backend
{
    protected $app_id = "623060648636265";
    protected $app_secret = "ad00911ec3120286be008c02bdd66a92";
    protected $access_token = "EAAI2q5yir2kBAMPlwaNqRmZCHPdBGLadq6FUAaIxz7BFbuS7uaNDUShEMhCVG7KZBHwQ8VivZBxChNEdTC14MnapJwPi4V9uJYnxriK5WggdbUUx4QlBELggA9QO1YHPCZCPGPJC6B6OPy9xUUceGT2qIMQ7JwM0F2rE8V4LbWstn84Rytnkizn5u7mQyXwxqZCYELcXH8HHsQUdZCS0wj";
    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->ordernode = new \app\admin\model\OrderNode();
        $this->work  = new \app\admin\model\saleaftermanage\WorkOrderList();
        $this->process = new \app\admin\model\order\order\NewOrderProcess;
    }
    
    public function test()
    {
        Api::init($this->app_id, $this->app_secret, $this->access_token);
        $all_facebook_spend = 0;


        $campaign = new Campaign('act_439802446536567');
        $params = array(
            'time_range' => array('since' => '2020-08-14', 'until' => '2020-08-14'),
        );
        $cursor = $campaign->getInsights([], $params);
        die;
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = array(
                'time_range' => array('since' => '2020-08-14', 'until' => '2020-08-14'),
            );
            $cursor = $campaign->getInsights([], $params);
            foreach ($cursor->getObjects() as $key => $value) {
                if ($value) {
                    $all_facebook_spend += $cursor->getObjects()[0]->getData()['spend'];
                }
            }
        }

        dump($all_facebook_spend);
        exit;
    }

    public function test01()
    {
        $url = "https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id={623060648636265}&client_secret={ad00911ec3120286be008c02bdd66a92}&fb_exchange_token={EAAI2q5yir2kBAMPlwaNqRmZCHPdBGLadq6FUAaIxz7BFbuS7uaNDUShEMhCVG7KZBHwQ8VivZBxChNEdTC14MnapJwPi4V9uJYnxriK5WggdbUUx4QlBELggA9QO1YHPCZCPGPJC6B6OPy9xUUceGT2qIMQ7JwM0F2rE8V4LbWstn84Rytnkizn5u7mQyXwxqZCYELcXH8HHsQUdZCS0wj}";
        $res = Http::get($url);

    }

    public function delete_num_eq_1()
    {
        $res  = Db::name('new_product_replenish_order')->where(['replenish_id'=>129,'replenishment_num'=>1])->delete();
        $res1 = Db::name('new_product_replenish_list')->where(['replenish_id'=>129,'distribute_num'=>1])->delete();
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
    public function google_active_user($site,$start_time)
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
        $response = $this->getReport_active_user($site,$analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        return $result[0]['ga:1dayUsers'] ? round($result[0]['ga:1dayUsers'],2): 0;
    }
    protected function getReport_active_user($site,$analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if($site == 1){
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 2){
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 3){
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
    //session
    public function google_session($site,$start_time)
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
        $response = $this->getReport_session($site,$analytics, $start_time, $end_time);

        // dump($response);die;

        // Print the response.
        $result = $this->printResults($response);

        return $result[0]['ga:sessions'] ? round($result[0]['ga:sessions'],2): 0;
    }
    protected function getReport_session($site,$analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if($site == 1){
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 2){
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        }elseif ($site == 3){
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
    //运营数据中心
    public function zeelool_operate_data_center(){

        $connect = Db::connect('database.db_zeelool');
        //查询时间
        $date_time = $this->zeelool->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2018-01-01' and '2018-12-31' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val){
            $arr['site'] = 1;
            $arr['day_date'] = $val['date_time'];
            //活跃用户数
            $arr['active_user_num'] = $this->google_active_user(1,$val['date_time']);
            //注册用户数
            $register_where[] = ['exp', Db::raw("DATE_FORMAT(created, '%Y-%m-%d') = '".$val['date_time']."'")];
            $arr['register_num'] = $connect->table('admin_user')->where($register_where)->count();
            //新增vip用户数
            $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '".$val['date_time']."'")];
            $vip_where['order_status'] = 'Success';
            $arr['vip_user_num'] = $connect->table('oc_vip_order')->where($vip_where)->count();
            //订单数
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '".$val['date_time']."'")];
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed','payment_review', 'paypal_canceled_reversal']];
            $arr['order_num'] = $this->zeelool->where($order_where)->count();
            //销售额
            $arr['sales_total_money'] = $this->zeelool->where($order_where)->sum('base_grand_total');
            //邮费
            $arr['shipping_total_money'] = $this->zeelool->where($order_where)->sum('base_shipping_amount');
            //购买人数
            $order_user = $this->zeelool->where($order_where)->group('customer_id')->count();
            //客单价
            $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money']/$order_user,2) : 0;
            //会话
            $arr['sessions'] = $this->google_session(1,$val['date_time']);
            //新建购物车数量
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '".$val['date_time']."'")];
            $arr['new_cart_num'] = $connect->table('sales_flat_quote')->where($cart_where1)->count();
            //更新购物车数量
            $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '".$val['date_time']."'")];
            $arr['update_cart_num'] = $connect->table('sales_flat_quote')->where($cart_where2)->count();
            //新增加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num']/$arr['sessions'],2) : 0;
            //更新加购率
            $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num']/$arr['sessions'],2) : 0;
            //新增购物车转化率
            $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num']/$arr['new_cart_num'],2) : 0;
            //更新购物车转化率
            $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num']/$arr['update_cart_num'],2) : 0;
            //插入数据
            Db::name('datacenter_day')->insert($arr);
            echo $val['date_time']."\n";
        }
    }

    public function test001()
    {
        echo 111;
    }

    /**
     * 更新入库单仓库
     * @author liushiwei
     * @date   2021/9/29 18:32
     */
    public function updateStockWarehouse()
    {
        $info = Db::name('in_stock')
            ->alias('s')->join('fa_check_order c','c.id=s.check_id')
            ->join('fa_logistics_info l','c.logistics_id=l.id')->field('s.warehouse_id,s.id,l.sign_warehouse')->select();
        foreach ($info as $val){
            Db::name('in_stock')->where(['id'=>$val['id']])->update(['warehouse_id'=>$val['sign_warehouse']]);
        }
        echo 'ok';
    }

    /**
     * 根据用户手机号获取用户userid
     * @author liushiwei
     * @date   2021/10/25 16:59
     */
    public function getUserId()
    {
        $mobile = '15737150715';
        $ding = new \app\api\controller\Ding;
        $info = $ding->getbymobile($mobile);
    }

    /**
     * 批量创建采购单
     * @author liushiwei
     * @date   2021/10/27 10:11
     */
    public function createPurchaseOrder()
    {
        $list = Db::name('zz_purchase')
            ->alias('temp')
            ->join('fa_purchase_order op','temp.purchase_number_temp=op.purchase_number')
            ->join('fa_purchase_order_item item','temp.purchase_number_temp=item.purchase_order_number')
            ->select();
        if(!$list){
            return false;
        }
        $this->purchase_order = new \app\admin\model\purchase\PurchaseOrder;
        $this->purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
        foreach($list as $v){
            $data =$item_data= [];
            $data['purchase_number'] = $purchase_order = 'PO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $data['1688_number'] = $v['1688_number'];
            $data['purchase_name'] = $v['purchase_name'];
            $data['factory_type'] = $v['factory_type'];
            $data['purchase_type'] = $v['purchase_type'];
            $data['is_sample'] = 0; //是否是留样采购单 0 不留样
            $data['type'] = $v['type']; //是否大货现货
            $data['is_new_product'] = 0;
            $data['pay_type'] = 3;
            $data['pay_rate'] = $v['pay_rate'];
            $data['arrival_time'] = $v['arrival_time'];
            $data['purchase_remark'] = $v['purchase_remark'];
            $data['contract_id'] = $v['contract_id'];
            $data['delivery_address'] = $v['delivery_address'];
            $data['delivery_time'] = $v['delivery_time'];
            $data['supplier_id'] = $v['supplier_id'];
            $data['supplier_type'] = $v['supplier_type'];
            $data['supplier_address'] = $v['supplier_address'];
            $data['product_total']    = $v['purchase_price_temp'] * ($v['purchase_num_temp'] - $v['checked_num_temp']);
            $data['purchase_freight']    = 0;
            $data['purchase_total']    = $v['purchase_price_temp'] * ($v['purchase_num_temp'] - $v['checked_num_temp']);
            $data['settlement_method'] = 2;
            $data['deposit_ratio']    = $v['deposit_ratio'];
            $data['deposit_amount']   = $v['deposit_amount'];
            $data['final_amount']     = $v['final_amount'];
            $data['logistics_number'] = $v['temp_logistics_number'];
            $data['logistics_company_no'] = 'ECZJ';
            $data['logistics_company_name'] = 'ECZJ';
            $data['create_person']    = $v['create_person'];
            $data['createtime']    = date('Y-m-d H:i:s');
            $data['is_add_logistics'] = 1;
            $data['receiving_warehouse'] = $v['receiving_warehouse'] ?:2;
            $result = $this->purchase_order->allowField(true)->isUpdate(false)->data($data,true)->save();
            if ($result !== false) {
                $item_data['sku'] = $v['sku'];
                $item_data['supplier_sku'] = $v['supplier_sku'];
                $item_data['product_name'] = $v['product_name'];
                $item_data['purchase_num'] = $v['purchase_num_temp'] - $v['checked_num_temp'];
                $item_data['purchase_price'] = $v['purchase_price_temp'];
                $item_data['purchase_total'] = $v['purchase_price_temp'] * ($v['purchase_num_temp'] - $v['checked_num_temp']);
                $item_data['purchase_id'] = $this->purchase_order->id;
                $item_data['replenish_list_id'] = $v['replenish_list_id'];
                $item_data['purchase_order_number'] = $purchase_order;
                $this->purchase_order_item->allowField(true)->isUpdate(false)->data($item_data,true)->save();
                echo 'ok</br>';
            }
        }
    }
    public function updateStock()
    {
        $where['m.stock_id'] = 1;
        $where['distribution_status'] = 1;
        $where['o.status'] = 'processing';
        $where['m.site'] = ['neq',12];
        $where['o.created_at'] = ['between', [strtotime('-3 month'), time()]];
        $result = Db::connect('database.db_mojing_order')->table('fa_order_item_process')
            ->alias('m')
            ->join('fa_order o','o.id=m.order_id')
            ->where($where)
            ->field('m.*')
            ->select();
        if(!$result){
            return '无数据';
        }
        foreach ($result as $v){
            Db::connect('database.db_mojing_order')->table('fa_order_item_process')->where(['id'=>$v['id']])->update(['stock_id'=>2]);
            Db::connect('database.db_mojing_order')->table('fa_order')->where(['id'=>$v['order_id']])->update(['stock_id'=>2]);

        }
        echo '<pre>';
        dump(count($result));

    }

    /**
     * 48小时发出的订单数量占总数的比例
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author liushiwei
     * @date   2021/11/9 14:08
     */
    public function intime_data(){
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $start_time = 1627747200;
        $end_time = 1636387199;
        $this->order = new \app\admin\model\order\order\NewOrder();
        $date_time = $this->order->query("SELECT FROM_UNIXTIME(payment_time, '%Y-%m') AS date_time FROM `fa_order` where payment_time between ".$start_time." and ".$end_time." GROUP BY FROM_UNIXTIME(payment_time, '%Y-%m') order by FROM_UNIXTIME(payment_time, '%Y-%m') asc");
        //查询时间
        foreach ($date_time as $val) {
            $info = $this->getIntimeOrderTwo($val['date_time']);
            dump($val['date_time']);
            dump($info);
        }
    }
    public function getIntimeOrder($date)
    {
        $arr = [];
        //订单数
        $start = strtotime($date);
        $firstdaystr = date("Y-m-01",$start);
        $end = strtotime(date("Y-m-d 23:59:59",strtotime("$firstdaystr +1 month -1 day")));
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->ordernode = new \app\admin\model\OrderNode();
        $where['o.payment_time'] = ['between',[$start,$end]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
        $arr['order_num'] = $this->order->alias('o')->where($where)->count();
        $this->process = new \app\admin\model\order\order\NewOrderProcess;
        $sql1 = $this->process->alias('p')->join('fa_order o','p.increment_id = o.increment_id')->field('(p.complete_time-o.payment_time)/3600 AS total')->where($where)->group('p.order_id')->buildSql();
        $arr['send_num'] = $this->process->table([$sql1=>'t2'])->value('sum( IF ( total <= 48, 1, 0) ) AS a');
        $arr['send_rate'] = $arr['order_num'] ? round($arr['send_num']/$arr['order_num']*100,2) : 0;
        return $arr;
    }
    public function getIntimeOrderTwo($date)
    {
        $arr = [];
        //订单数
        $start = strtotime($date);
        $firstdaystr = date("Y-m-01",$start);
        $end = strtotime(date("Y-m-d 23:59:59",strtotime("$firstdaystr +1 month -1 day")));
        $where['o.payment_time'] = ['between',[$start,$end]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered','delivery']];
        //求出所有的工单中订单
        $work_order_list = $this->work->column('platform_order');
        //当月所有的订单
        $arr_order_list = $this->order->alias('o')->where($where)->column('increment_id');
        $result_list = array_diff($arr_order_list,$work_order_list);
        $arr['order_num'] = count($result_list);

        $sql1 = $this->process->alias('p')->join('fa_order o','p.increment_id = o.increment_id')->field('(p.complete_time-o.payment_time)/3600 AS total')->where($where)->group('p.order_id')->buildSql();
        $arr_send_list = $this->process->table([$sql1=>'t2'])->column("IF ( total <= 48, increment_id,'')");
        $arr['send_num']  = count(array_diff(array_filter($arr_send_list),$work_order_list));
        $arr['send_rate'] = $arr['order_num'] ? round($arr['send_num']/$arr['order_num']*100,2) : 0;
        return $arr;
    }
    
}
