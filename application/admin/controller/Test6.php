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
        $this->work = new \app\admin\model\saleaftermanage\WorkOrderList();
        $this->process = new \app\admin\model\order\order\NewOrderProcess;
    }

    public function test()
    {
        Api::init($this->app_id, $this->app_secret, $this->access_token);
        $all_facebook_spend = 0;


        $campaign = new Campaign('act_439802446536567');
        $params = [
            'time_range' => ['since' => '2020-08-14', 'until' => '2020-08-14'],
        ];
        $cursor = $campaign->getInsights([], $params);
        die;
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = [
                'time_range' => ['since' => '2020-08-14', 'until' => '2020-08-14'],
            ];
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
        $res = Db::name('new_product_replenish_order')->where(['replenish_id' => 129, 'replenishment_num' => 1])->delete();
        $res1 = Db::name('new_product_replenish_list')->where(['replenish_id' => 129, 'distribute_num' => 1])->delete();
    }

    protected function initializeAnalytics()
    {
        // Use the developers console and download your service account
        // credentials in JSON format. Place them in this directory or
        // change the key file location if necessary.
        $KEY_FILE_LOCATION = __DIR__.'/oauth-credentials.json';

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
        $request->setMetrics([$adCostMetric]);
        $request->setDimensions([$sessionDayDimension]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

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
        $request->setMetrics([$adCostMetric]);
        $request->setDimensions([$sessionDayDimension]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $analytics->reports->batchGet($body);

    }

    /**
     * Parses and prints the Analytics Reporting API V4 response.
     *
     * @param  An Analytics Reporting API V4 response.
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

    //运营数据中心
    public function zeelool_operate_data_center()
    {

        $connect = Db::connect('database.db_zeelool');
        //查询时间
        $date_time = $this->zeelool->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date_time FROM `sales_flat_order` where created_at between '2018-01-01' and '2018-12-31' GROUP BY DATE_FORMAT(created_at, '%Y%m%d') order by DATE_FORMAT(created_at, '%Y%m%d') asc");
        foreach ($date_time as $val) {
            $arr['site'] = 1;
            $arr['day_date'] = $val['date_time'];
            //活跃用户数
            $arr['active_user_num'] = $this->google_active_user(1, $val['date_time']);
            //注册用户数
            $register_where[] = ['exp', Db::raw("DATE_FORMAT(created, '%Y-%m-%d') = '".$val['date_time']."'")];
            $arr['register_num'] = $connect->table('admin_user')->where($register_where)->count();
            //新增vip用户数
            $vip_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '".$val['date_time']."'")];
            $vip_where['order_status'] = 'Success';
            $arr['vip_user_num'] = $connect->table('oc_vip_order')->where($vip_where)->count();
            //订单数
            $order_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '".$val['date_time']."'")];
            $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $arr['order_num'] = $this->zeelool->where($order_where)->count();
            //销售额
            $arr['sales_total_money'] = $this->zeelool->where($order_where)->sum('base_grand_total');
            //邮费
            $arr['shipping_total_money'] = $this->zeelool->where($order_where)->sum('base_shipping_amount');
            //购买人数
            $order_user = $this->zeelool->where($order_where)->group('customer_id')->count();
            //客单价
            $arr['order_unit_price'] = $arr['order_num'] ? round($arr['sales_total_money'] / $order_user, 2) : 0;
            //会话
            $arr['sessions'] = $this->google_session(1, $val['date_time']);
            //新建购物车数量
            $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '".$val['date_time']."'")];
            $arr['new_cart_num'] = $connect->table('sales_flat_quote')->where($cart_where1)->count();
            //更新购物车数量
            $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '".$val['date_time']."'")];
            $arr['update_cart_num'] = $connect->table('sales_flat_quote')->where($cart_where2)->count();
            //新增加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($arr['new_cart_num'] / $arr['sessions'], 2) : 0;
            //更新加购率
            $arr['update_add_cart_rate'] = $arr['sessions'] ? round($arr['update_cart_num'] / $arr['sessions'], 2) : 0;
            //新增购物车转化率
            $arr['cart_rate'] = $arr['new_cart_num'] ? round($arr['order_num'] / $arr['new_cart_num'], 2) : 0;
            //更新购物车转化率
            $arr['update_cart_cart'] = $arr['update_cart_num'] ? round($arr['order_num'] / $arr['update_cart_num'], 2) : 0;
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
            ->alias('s')->join('fa_check_order c', 'c.id=s.check_id')
            ->join('fa_logistics_info l', 'c.logistics_id=l.id')->field('s.warehouse_id,s.id,l.sign_warehouse')->select();
        foreach ($info as $val) {
            Db::name('in_stock')->where(['id' => $val['id']])->update(['warehouse_id' => $val['sign_warehouse']]);
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
            ->join('fa_purchase_order op', 'temp.purchase_number_temp=op.purchase_number')
            ->join('fa_purchase_order_item item', 'temp.purchase_number_temp=item.purchase_order_number')
            ->select();
        if (!$list) {
            return false;
        }
        $this->purchase_order = new \app\admin\model\purchase\PurchaseOrder;
        $this->purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
        foreach ($list as $v) {
            $data = $item_data = [];
            $data['purchase_number'] = $purchase_order = 'PO'.date('YmdHis').rand(100, 999).rand(100, 999);
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
            $data['product_total'] = $v['purchase_price_temp'] * ($v['purchase_num_temp'] - $v['checked_num_temp']);
            $data['purchase_freight'] = 0;
            $data['purchase_total'] = $v['purchase_price_temp'] * ($v['purchase_num_temp'] - $v['checked_num_temp']);
            $data['settlement_method'] = 2;
            $data['deposit_ratio'] = $v['deposit_ratio'];
            $data['deposit_amount'] = $v['deposit_amount'];
            $data['final_amount'] = $v['final_amount'];
            $data['logistics_number'] = $v['temp_logistics_number'];
            $data['logistics_company_no'] = 'ECZJ';
            $data['logistics_company_name'] = 'ECZJ';
            $data['create_person'] = $v['create_person'];
            $data['createtime'] = date('Y-m-d H:i:s');
            $data['is_add_logistics'] = 1;
            $data['receiving_warehouse'] = $v['receiving_warehouse'] ?: 2;
            $result = $this->purchase_order->allowField(true)->isUpdate(false)->data($data, true)->save();
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
                $this->purchase_order_item->allowField(true)->isUpdate(false)->data($item_data, true)->save();
                echo 'ok</br>';
            }
        }
    }

    public function updateStock()
    {
        $where['m.stock_id'] = 1;
        $where['distribution_status'] = 1;
        $where['o.status'] = 'processing';
        $where['m.site'] = ['neq', 12];
        $where['o.created_at'] = ['between', [strtotime('-3 month'), time()]];
        $result = Db::connect('database.db_mojing_order')->table('fa_order_item_process')
            ->alias('m')
            ->join('fa_order o', 'o.id=m.order_id')
            ->where($where)
            ->field('m.*')
            ->select();
        if (!$result) {
            return '无数据';
        }
        foreach ($result as $v) {
            Db::connect('database.db_mojing_order')->table('fa_order_item_process')->where(['id' => $v['id']])->update(['stock_id' => 2]);
            Db::connect('database.db_mojing_order')->table('fa_order')->where(['id' => $v['order_id']])->update(['stock_id' => 2]);

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
    public function intime_data()
    {
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
        $firstdaystr = date("Y-m-01", $start);
        $end = strtotime(date("Y-m-d 23:59:59", strtotime("$firstdaystr +1 month -1 day")));
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->ordernode = new \app\admin\model\OrderNode();
        $where['o.payment_time'] = ['between', [$start, $end]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered', 'delivery']];
        $arr['order_num'] = $this->order->alias('o')->where($where)->count();
        $this->process = new \app\admin\model\order\order\NewOrderProcess;
        $sql1 = $this->process->alias('p')->join('fa_order o', 'p.increment_id = o.increment_id')->field('(p.complete_time-o.payment_time)/3600 AS total')->where($where)->group('p.order_id')->buildSql();
        $arr['send_num'] = $this->process->table([$sql1 => 't2'])->value('sum( IF ( total <= 48, 1, 0) ) AS a');
        $arr['send_rate'] = $arr['order_num'] ? round($arr['send_num'] / $arr['order_num'] * 100, 2) : 0;

        return $arr;
    }

    public function getIntimeOrderTwo($date)
    {
        $arr = [];
        //订单数
        $start = strtotime($date);
        $firstdaystr = date("Y-m-01", $start);
        $end = strtotime(date("Y-m-d 23:59:59", strtotime("$firstdaystr +1 month -1 day")));
        $where['o.payment_time'] = ['between', [$start, $end]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered', 'delivery']];
        //求出所有的工单中订单
        $work_order_list = $this->work->column('platform_order');
        //当月所有的订单
        $arr_order_list = $this->order->alias('o')->where($where)->column('increment_id');
        $result_list = array_diff($arr_order_list, $work_order_list);
        $arr['order_num'] = count($result_list);

        $sql1 = $this->process->alias('p')->join('fa_order o', 'p.increment_id = o.increment_id')->field('p.increment_id,(p.complete_time-o.payment_time)/3600 AS total')->where($where)->group('p.order_id')->buildSql();
        $arr_send_list = $this->process->table([$sql1 => 't2'])->column("IF ( total <= 48,increment_id,'')");
        $arr['send_num'] = count(array_diff(array_filter($arr_send_list), $work_order_list));
        $arr['send_rate'] = $arr['order_num'] ? round($arr['send_num'] / $arr['order_num'] * 100, 2) : 0;

        return $arr;
    }

    /**
     * 获取物流数据
     * @author liushiwei
     * @date   2021/11/19 9:11
     */
    public function fetch_logistics_data()
    {
        $start = input('start');
        $end = input('end');
        $map['delivery_time'] = ['between', [$start, $end]];
        $result = $this->logistics_data(10, $map);
        dump($result);

    }

    /**计算妥投率
     *
     * @param $site
     * @param $map
     *
     * @return array
     * @author liushiwei
     * @date   2021/11/19 9:07
     */
    public function logistics_data($site, $map)
    {
        if ($site != 10) {
            $where['site'] = $whereSite['site'] = $site;
        }
        $where['node_type'] = 40;
        //7天妥投时间
        $serven_time_out = 86400 * 7;
        $eight_time_out = 86400 * 8;
        $nine_time_out = 86400 * 9;
        $ten_time_out = 86400 * 10;
        $eleven_time_out = 86400 * 11;
        $twelve_time_out = 86400 * 12;
        $thirteen_time_out = 86400 * 13;
        $fourteen_time_out = 86400 * 14;
        $fifteen_time_out = 86400 * 15;
        $sixteen_time_out = 86400 * 16;
        $seventeen_time_out = 86400 * 17;
        $eighteen_time_out = 86400 * 18;
        $nineteen_time_out = 86400 * 19;
        $twenty_time_out = 86400 * 20;
        $this->orderNode = new \app\admin\model\OrderNode();
        $all_shipment_type = $this->orderNode->where($map)->where('track_number is not null')->field('shipment_data_type')->group('shipment_data_type')->select();
        if ($all_shipment_type) {
            $arr = $rs = $rate = [];
            $all_shipment_type = collection($all_shipment_type)->toArray();
            //总共的妥投数量,妥投时间
            $all_total_num = $all_total_wait_time = 0;
            //循环所有的物流渠道
            foreach ($all_shipment_type as $k => $v) {
                //物流渠道
                $arr['shipment_data_type'][$k] = $v['shipment_data_type'];
                //发货订单号->where($orderNode)
                $delievered_order = $this->orderNode->where(['shipment_data_type' => $v['shipment_data_type']])->where($whereSite)->where($map)->field('order_number,delivery_time,signing_time')->select();
                $delievered_order = collection($delievered_order)->toArray();
                if (!$delievered_order) {
                    $arr['send_order_num'][$k] = 0;
                    $arr['deliverd_order_num'][$k] = 0;
                    $arr['serven_deliverd_rate'][$k] = 0;
                    $arr['eight_deliverd_rate'][$k] = 0;
                    $arr['nine_deliverd_rate'][$k] = 0;
                    $arr['ten_deliverd_rate'][$k] = 0;
                    $arr['eleven_deliverd_rate'][$k] = 0;
                    $arr['twelve_deliverd_rate'][$k] = 0;
                    $arr['thirteen_deliverd_rate'][$k] = 0;
                    $arr['fourteen_deliverd_rate'][$k] = 0;
                    $arr['fifteen_deliverd_rate'][$k] = 0;
                    $arr['sixteen_deliverd_rate'][$k] = 0;
                    $arr['seventeen_deliverd_rate'][$k] = 0;
                    $arr['eighteen_deliverd_rate'][$k] = 0;
                    $arr['nineteen_deliverd_rate'][$k] = 0;
                    $arr['twenty_deliverd_rate'][$k] = 0;
                    $arr['gtTwenty_deliverd_rate'][$k] = 0;
                    $arr['avg_deliverd_rate'][$k] = 0;
                    $rs[$v['shipment_data_type']] = 0;
                    continue;
                }
                //发货数量
                $send_order_num = count(array_column($delievered_order, 'order_number'));

                $serven_num = $eight_num = $nine_num = $ten_num = $eleven_num = $twelve_num = $thirteen_num = $fourteen_num = $fifteen_num = $wait_time = 0;
                $sixteen_num = $seventeen_num = $eighteen_num = $nineteen_num = $twenty_num  = $gtTwenty_num = 0;
                foreach ($delievered_order as $key => $val) {
                    /**
                     * 判断有签收时间，并且签收时间大于发货时间，并且签收时间大于发货时间两天 则计算正常发货数量  否则不计算在内
                     */
                    if (!empty($val['signing_time']) && $val['signing_time'] > $val['delivery_time'] && ((strtotime($val['signing_time']) - strtotime($val['delivery_time'])) / 86400) > 2) {
                        $distance_time = strtotime($val['signing_time']) - strtotime($val['delivery_time']);
                        $wait_time += $distance_time;
                        //时间小于7天的
                        if ($serven_time_out >= $distance_time) { //7天数量
                            $serven_num++;
                        } elseif (($serven_time_out < $distance_time) && ($distance_time <= $eight_time_out)) { //8天数量
                            $eight_num++;
                        } elseif (($eight_time_out < $distance_time) && ($distance_time <= $nine_time_out)) { //9天数量
                            $nine_num++;
                        } elseif (($nine_time_out < $distance_time) && ($distance_time <= $ten_time_out)) { //10天数量
                            $ten_num++;
                        }elseif (($ten_time_out < $distance_time) && ($distance_time <= $eleven_time_out)){ //11天
                            $eleven_num++;
                        }elseif (($eleven_time_out < $distance_time) && ($distance_time <= $twelve_time_out)){ //12
                            $twelve_num++;
                        }elseif (($twelve_time_out < $distance_time) && ($distance_time <= $thirteen_time_out)){ //13
                            $thirteen_num++;
                        }elseif (($thirteen_time_out < $distance_time) && ($distance_time <= $fourteen_time_out)){
                            $fourteen_num++;
                        }elseif (($fourteen_time_out < $distance_time) && ($distance_time <= $fifteen_time_out)){
                            $fifteen_num++;
                        }elseif (($fifteen_time_out < $distance_time) && ($distance_time <= $sixteen_time_out)){
                            $sixteen_num++;
                        }elseif (($sixteen_time_out < $distance_time) && ($distance_time <= $seventeen_time_out)){
                            $seventeen_num++;
                        }elseif (($seventeen_time_out < $distance_time) && ($distance_time <= $eighteen_time_out)){
                            $eighteen_num++;
                        }elseif (($eighteen_time_out < $distance_time) && ($distance_time <= $nineteen_time_out)){
                            $nineteen_num++;
                        }elseif (($nineteen_time_out < $distance_time) && ($distance_time <= $twenty_time_out)){
                            $twenty_num++;
                        }
                        else {
                            $gtTwenty_num++;
                        }
                    }
                }

                $arr['send_order_num'][$k] = $rs[$v['shipment_data_type']] = $send_order_num;

                //妥投单数
                $arr['deliverd_order_num'][$k] = $deliverd_order_num = $serven_num + $eight_num + $nine_num + $ten_num + $eleven_num +
                    $twelve_num + $thirteen_num + $fourteen_num + $fifteen_num + $sixteen_num + $seventeen_num + $eighteen_num + $nineteen_num + $twenty_num + $gtTwenty_num;
                //7天妥投单数
                $arr['serven_deliverd_order_num'][$k] = $serven_num;
                $arr['eight_deliverd_order_num'][$k] = $eight_num;
                $arr['nine_deliverd_order_num'][$k] = $nine_num;
                $arr['ten_deliverd_order_num'][$k] = $ten_num;
                $arr['eleven_deliverd_order_num'][$k] = $eleven_num;
                $arr['twelve_deliverd_order_num'][$k] = $twelve_num;
                $arr['thirteen_deliverd_order_num'][$k] = $thirteen_num;
                $arr['fourteen_deliverd_order_num'][$k] = $fourteen_num;
                $arr['fifteen_deliverd_order_num'][$k] = $fifteen_num;
                $arr['sixteen_deliverd_order_num'][$k] = $sixteen_num;
                $arr['seventeen_deliverd_order_num'][$k] = $seventeen_num;
                $arr['eighteen_deliverd_order_num'][$k] = $eighteen_num;
                $arr['nineteen_deliverd_order_num'][$k] = $nineteen_num;
                $arr['twenty_deliverd_order_num'][$k] = $twenty_num;
                //20天以上妥投单数
                $arr['gtTwenty_deliverd_order_num'][$k] = $gtTwenty_num;
                //妥投率
                if ($deliverd_order_num > 0) {
                    //7天妥投率
                    $arr['serven_deliverd_rate'][$k] = round(($serven_num / $deliverd_order_num) * 100, 2);
                    $arr['eight_deliverd_rate'][$k] = round(($eight_num / $deliverd_order_num) * 100, 2);
                    $arr['nine_deliverd_rate'][$k] = round(($nine_num / $deliverd_order_num) * 100, 2);
                    $arr['ten_deliverd_rate'][$k] = round(($ten_num / $deliverd_order_num) * 100, 2);
                    $arr['eleven_deliverd_rate'][$k] = round(($eleven_num / $deliverd_order_num) * 100, 2);
                    $arr['twelve_deliverd_rate'][$k] = round(($twelve_num / $deliverd_order_num) * 100, 2);
                    $arr['thirteen_deliverd_rate'][$k] = round(($thirteen_num / $deliverd_order_num) * 100, 2);
                    $arr['fourteen_deliverd_rate'][$k] = round(($fourteen_num / $deliverd_order_num) * 100, 2);
                    $arr['fifteen_deliverd_rate'][$k] = round(($fifteen_num / $deliverd_order_num) * 100, 2);
                    $arr['sixteen_deliverd_rate'][$k] = round(($sixteen_num / $deliverd_order_num) * 100, 2);
                    $arr['seventeen_deliverd_rate'][$k] = round(($seventeen_num / $deliverd_order_num) * 100, 2);
                    $arr['eighteen_deliverd_rate'][$k] = round(($eighteen_num / $deliverd_order_num) * 100, 2);
                    $arr['nineteen_deliverd_rate'][$k] = round(($nineteen_num / $deliverd_order_num) * 100, 2);
                    $arr['twenty_deliverd_rate'][$k] = round(($twenty_num / $deliverd_order_num) * 100, 2);
                    $arr['gtTwenty_deliverd_rate'][$k] = round(($gtTwenty_num / $deliverd_order_num) * 100, 2);
                } else {

                    $arr['serven_deliverd_rate'][$k] = 0;
                    $arr['eight_deliverd_rate'][$k] = 0;
                    $arr['nine_deliverd_rate'][$k] = 0;
                    $arr['ten_deliverd_rate'][$k] = 0;
                    $arr['eleven_deliverd_rate'][$k] = 0;
                    $arr['twelve_deliverd_rate'][$k] = 0;
                    $arr['thirteen_deliverd_rate'][$k] = 0;
                    $arr['fourteen_deliverd_rate'][$k] = 0;
                    $arr['fifteen_deliverd_rate'][$k] = 0;
                    $arr['sixteen_deliverd_rate'][$k] = 0;
                    $arr['seventeen_deliverd_rate'][$k] = 0;
                    $arr['eighteen_deliverd_rate'][$k] = 0;
                    $arr['nineteen_deliverd_rate'][$k] = 0;
                    $arr['twenty_deliverd_rate'][$k] = 0;
                    $arr['gtTwenty_deliverd_rate'][$k] = 0;
                }
//                //总共妥投数量
//                $rate[0] += $serven_num;
//                $rate[1] += $ten_num;
//                $rate[2] += $fourteen_num;
//                $rate[3] += $twenty_num;
//                $rate[4] += $gtTwenty_num;
//                $rate['total_num'] += $deliverd_order_num;
//                //平均妥投时效
//                if ($deliverd_order_num > 0) {
//                    $arr['avg_deliverd_rate'][$k] = round(($wait_time / $deliverd_order_num / 86400), 2);
//                } else {
//                    $arr['avg_deliverd_rate'][$k] = 0;
//                }
//                $all_total_num += $deliverd_order_num;
//                $all_total_wait_time += $wait_time;
            }
            //设置发货总数量 妥投订单总数量数为0
            $total_send_order_num = $total_deliverd_order_num = 0;
            $info = [];
            foreach ($arr['shipment_data_type'] as $ak => $av) {
                $info[$ak]['shipment_data_type'] = $av;
                $info[$ak]['send_order_num'] = $arr['send_order_num'][$ak];
                $info[$ak]['deliverd_order_num'] = $arr['deliverd_order_num'][$ak];
                $info[$ak]['serven_deliverd_rate'] = $arr['serven_deliverd_rate'][$ak];
                $info[$ak]['eight_deliverd_rate'] = $arr['eight_deliverd_rate'][$ak];
                $info[$ak]['nine_deliverd_rate'] = $arr['nine_deliverd_rate'][$ak];
                $info[$ak]['ten_deliverd_rate'] = $arr['ten_deliverd_rate'][$ak];
                $info[$ak]['eleven_deliverd_rate'] = $arr['eleven_deliverd_rate'][$ak];
                $info[$ak]['twelve_deliverd_rate'] = $arr['twelve_deliverd_rate'][$ak];
                $info[$ak]['thirteen_deliverd_rate'] = $arr['thirteen_deliverd_rate'][$ak];
                $info[$ak]['fourteen_deliverd_rate'] = $arr['fourteen_deliverd_rate'][$ak];
                $info[$ak]['fifteen_deliverd_rate'] = $arr['fifteen_deliverd_rate'][$ak];
                $info[$ak]['sixteen_deliverd_rate'] = $arr['sixteen_deliverd_rate'][$ak];
                $info[$ak]['seventeen_deliverd_rate'] = $arr['seventeen_deliverd_rate'][$ak];
                $info[$ak]['eighteen_deliverd_rate'] = $arr['eighteen_deliverd_rate'][$ak];
                $info[$ak]['nineteen_deliverd_rate'] = $arr['nineteen_deliverd_rate'][$ak];
                $info[$ak]['twenty_deliverd_rate'] = $arr['twenty_deliverd_rate'][$ak];
                $info[$ak]['gtTwenty_deliverd_rate'] = $arr['gtTwenty_deliverd_rate'][$ak];
                //$info[$ak]['avg_deliverd_rate'] = $arr['avg_deliverd_rate'][$ak];
                //计算总妥投率
//                if ($arr['send_order_num'][$ak] > 0) {
//                    $info[$ak]['total_deliverd_rate'] = round($arr['deliverd_order_num'][$ak] / $arr['send_order_num'][$ak] * 100, 2);
//                } else {
//                    $info[$ak]['total_deliverd_rate'] = 0;
//                }
//                $total_send_order_num += $arr['send_order_num'][$ak];
//                $total_deliverd_order_num += $arr['deliverd_order_num'][$ak];
            }
            //求出合计的数据
//            $info[$ak + 1]['shipment_data_type'] = '合计';
//            $info[$ak + 1]['send_order_num'] = $total_send_order_num;
//            $info[$ak + 1]['deliverd_order_num'] = $total_deliverd_order_num;
//            //总妥投率
//            if (0 < $total_send_order_num) {
//                $info[$ak + 1]['total_deliverd_rate'] = round($total_deliverd_order_num / $total_send_order_num * 100, 2);
//            } else {
//                $info[$ak + 1]['total_deliverd_rate'] = 0;
//            }

            //7天妥投率、14天妥投率、20天妥投率、21天妥投率总和
//            if ($all_total_num > 0) {
//                $info[$ak + 1]['serven_deliverd_rate'] = round($rate[0] / $all_total_num * 100, 2);
//                $info[$ak + 1]['ten_deliverd_rate'] = round($rate[1] / $all_total_num * 100, 2);
//                $info[$ak + 1]['fourteen_deliverd_rate'] = round($rate[2] / $all_total_num * 100, 2);
//                $info[$ak + 1]['twenty_deliverd_rate'] = round($rate[3] / $all_total_num * 100, 2);
//                $info[$ak + 1]['gtTwenty_deliverd_rate'] = round($rate[4] / $all_total_num * 100, 2);
//            } else {
//                $info[$ak + 1]['serven_deliverd_rate'] = 0;
//                $info[$ak + 1]['ten_deliverd_rate'] = 0;
//                $info[$ak + 1]['fourteen_deliverd_rate'] = 0;
//                $info[$ak + 1]['twenty_deliverd_rate'] = 0;
//                $info[$ak + 1]['gtTwenty_deliverd_rate'] = 0;
//            }
//            //总共妥投时效
//            if ($all_total_num > 0) {
//                $info[$ak + 1]['avg_deliverd_rate'] = round($all_total_wait_time / $all_total_num / 86400, 2);
//            } else {
//                $info[$ak + 1]['avg_deliverd_rate'] = 0;
//            }
            $info['deliverd_order_num_all'] = $rs;
            $info['rate'] = $rate;
        } else {
            $info['shipment_data_type'] = 0;
            // $info['order_num'] = 0;
            $info['send_order_num'] = 0;
            $info['deliverd_order_num'] = 0;
            $info['serven_deliverd_rate'] = 0;
            $info['eight_deliverd_rate'] = 0;
            $info['nine_deliverd_rate'] = 0;
            $info['ten_deliverd_rate'] = 0;
            $info['eleven_deliverd_rate'] = 0;
            $info['twelve_deliverd_rate'] = 0;
            $info['thirteen_deliverd_rate'] = 0;
            $info['fourteen_deliverd_rate'] = 0;
            $info['fifteen_deliverd_rate'] = 0;
            $info['sixteen_deliverd_rate'] = 0;
            $info['seventeen_deliverd_rate'] = 0;
            $info['eighteen_deliverd_rate'] = 0;
            $info['nineteen_deliverd_rate'] = 0;
            $info['twenty_deliverd_rate'] = 0;
            $info['gtTwenty_deliverd_rate'] = 0;
            $info['total_deliverd_rate'] = 0;
            $info['deliverd_order_num_all'] = 0;
            $info['rate'] = 0;
        }

        return $info;
    }

}
