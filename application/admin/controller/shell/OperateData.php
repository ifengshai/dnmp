<?php

/**
 * 执行对象：运营数据
 */

namespace app\admin\controller\shell;

use app\admin\model\operatedatacenter\Zeelool;
use app\common\controller\Backend;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use think\Hook;

class OperateData extends Backend
{
    protected $noNeedLogin = ['*'];


    public function _initialize()
    {
        parent::_initialize();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $this->ordernode = new \app\admin\model\OrderNode();
    }
    /**
     * 更新zeelool站仪表盘数据
     *
     * z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/02 17:39:31
     * @return void
     */
    public function update_ashboard_data_one1()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function update_ashboard_data_one2()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function update_ashboard_data_one3()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function update_ashboard_data_one4()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function update_ashboard_data_one5()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function update_ashboard_data_one6()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function update_ashboard_data_one7()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //昨天销售额
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_sales_money = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $yesterday_sales_money_data           = round($yesterday_sales_money['base_grand_total'], 2);
        //过去7天销售额
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_sales_money = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastsevenday_sales_money_data        = round($pastsevenday_sales_money['base_grand_total'], 2);
        //过去30天销售额
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_sales_money = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $pastthirtyday_sales_money_data       = round($pastthirtyday_sales_money['base_grand_total'], 2);
        //当月销售额
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_sales_money = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thismonth_sales_money_data           = round($thismonth_sales_money['base_grand_total'], 2);
        //上月销售额
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_sales_money = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastmonth_sales_money_data           = round($lastmonth_sales_money['base_grand_total'], 2);
        //今年销售额
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_sales_money = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $thisyear_sales_money_data            = round($thisyear_sales_money['base_grand_total'], 2);
        //上年销售额
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_sales_money = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $lastyear_sales_money_data            = round($lastyear_sales_money['base_grand_total'], 2);
        //总共销售额
        $total_sales_money = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->field('sum(base_grand_total) base_grand_total,count(entity_id) order_num')->find();
        $total_sales_money_data               = round($total_sales_money['base_grand_total'], 2);
        //昨天订单数
        $yesterday_order_num_data             = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->count();
        //过去7天订单数
        $pastsevenday_order_num_data          = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->count();
        //过去30天订单数
        $pastthirtyday_order_num_data         = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->count();
        //当月订单数
        $thismonth_order_num_data             = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->count();
        //上月订单数
        $lastmonth_order_num_data             = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->count();
        //今年订单数
        $thisyear_order_num_data              = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->count();
        //去年订单数
        $lastyear_order_num_data              = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->count();
        //总共订单数
        $total_order_num_data                 = $model->table('sales_flat_order')->where($order_where)->count();
        //昨天支付成功数
        $yesterday_order_success_data         = $yesterday_sales_money['order_num'];
        //过去7天支付成功数
        $pastsevenday_order_success_data      = $pastsevenday_sales_money['order_num'];
        //过去30天支付成功数
        $pastthirtyday_order_success_data     = $pastthirtyday_sales_money['order_num'];
        //当月支付成功数
        $thismonth_order_success_data         = $thismonth_sales_money['order_num'];
        //上月支付成功数
        $lastmonth_order_success_data         = $lastmonth_sales_money['order_num'];
        //今年支付成功数
        $thisyear_order_success_data          = $thisyear_sales_money['order_num'];
        //上年支付成功数
        $lastyear_order_success_data          = $lastyear_sales_money['order_num'];
        //总共支付成功数
        $total_order_success_data             = $total_sales_money['order_num'];
        //昨天新增注册人数
        //昨天新增注册用户数sql
        $yesterday_register_customer_data     = $model->table('customer_entity')->where($yestime_where)->count();
        //过去7天新增注册人数
        $pastsevenday_register_customer_data  = $model->table('customer_entity')->where($sev_where)->count();
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data = $model->table('customer_entity')->where($thirty_where)->count();
        //当月新增注册人数
        $thismonth_register_customer_data     = $model->table('customer_entity')->where($thismonth_where)->count();
        //上月新增注册人数
        $lastmonth_register_customer_data     = $model->table('customer_entity')->where($lastmonth_where)->count();
        //今年新增注册人数
        $thisyear_register_customer_data      = $model->table('customer_entity')->where($thisyear_where)->count();
        //去年新增注册人数
        $lastyear_register_customer_data      = $model->table('customer_entity')->where($lastyear_where)->count();
        //总共新增注册人数
        $total_register_customer_data         = $model->table('customer_entity')->count();
        //昨天新增登录人数
        //昨天新增登录用户数sql
        $yesterday_sign_customer_data         = $model->table('customer_entity')->where($yestime_where1)->count();
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data      = $model->table('customer_entity')->where($sev_where1)->count();
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data     = $model->table('customer_entity')->where($thirty_where1)->count();
        //当月新增登录人数
        $thismonth_sign_customer_data         = $model->table('customer_entity')->where($thismonth_where1)->count();
        //上月新增登录人数
        $lastmonth_sign_customer_data         = $model->table('customer_entity')->where($lastmonth_where1)->count();
        //今年新增登录人数
        $thisyear_sign_customer_data          = $model->table('customer_entity')->where($thisyear_where1)->count();
        //去年新增登录人数
        $lastyear_sign_customer_data          = $model->table('customer_entity')->where($lastyear_where1)->count();
        //总共新增登录人数
        $total_sign_customer_data             = $total_register_customer_data;

        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);

        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    /**
     * 更新zeelool站仪表盘数据
     *
     * z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/02 17:39:31
     * @return void
     */
    public function update_ashboard_data_two()
    {
        ini_set('memory_limit', '1512M');
        set_time_limit(0);
        //求出平台
        $platform = $this->request->get('platform', 1);


        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }


        if (false === $model) {
            return false;
        }

        $today = date('Y-m-d 23:59:59');
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //昨天支付成功数
        if($platform == 11){
            $order_where['order_type'] = ['in',[1,10]];
        }else{
            $order_where['order_type'] = 1;
        }
        $order_success_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where1 = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_where1[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_order_success_data = $model->table('sales_flat_order')->where($yestime_where)->where($order_where)->where($order_success_where)->count();
        //过去7天支付成功数
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where['created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_order_success_data = $model->table('sales_flat_order')->where($sev_where)->where($order_where)->where($order_success_where)->count();
        //过去30天支付成功数
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where['created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_order_success_data = $model->table('sales_flat_order')->where($thirty_where)->where($order_where)->where($order_success_where)->count();
        //当月支付成功数
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where['created_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_order_success_data = $model->table('sales_flat_order')->where($thismonth_where)->where($order_where)->where($order_success_where)->count();
        //上月支付成功数
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where['created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_order_success_data = $model->table('sales_flat_order')->where($lastmonth_where)->where($order_where)->where($order_success_where)->count();
        //今年支付成功数
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where['created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_order_success_data = $model->table('sales_flat_order')->where($thisyear_where)->where($order_where)->where($order_success_where)->count();
        //上年支付成功数
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where['created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_order_success_data = $model->table('sales_flat_order')->where($lastyear_where)->where($order_where)->where($order_success_where)->count();
        //总共支付成功数
        $total_order_success_data = $model->table('sales_flat_order')->where($order_where)->where($order_success_where)->count();

        //昨天购物车总数
        $quote_where['base_grand_total'] = ['>', 0];
        $yesterday_shoppingcart_total_data = $model->table('sales_flat_quote')->where($yestime_where)->where($quote_where)->count();
        //过去7天购物车总数
        $pastsevenday_shoppingcart_total_data = $model->table('sales_flat_quote')->where($sev_where)->where($quote_where)->count();
        //过去30天购物车总数
        $pastthirtyday_shoppingcart_total_data = $model->table('sales_flat_quote')->where($thirty_where)->where($quote_where)->count();
        //当月购物车总数
        $thismonth_shoppingcart_total_data = $model->table('sales_flat_quote')->where($thismonth_where)->where($quote_where)->count();
        //上月购物车总数
        $lastmonth_shoppingcart_total_data = $model->table('sales_flat_quote')->where($lastmonth_where)->where($quote_where)->count();
        //今年购物车总数
        $thisyear_shoppingcart_total_data = $model->table('sales_flat_quote')->where($thisyear_where)->where($quote_where)->count();
        //上年购物车总数
        $lastyear_shoppingcart_total_data = $model->table('sales_flat_quote')->where($lastyear_where)->where($quote_where)->count();
        //总共购物车总数
        $total_shoppingcart_total_data = $model->table('sales_flat_quote')->where($quote_where)->count();
        //昨天新增购物车总数
        $yesterday_shoppingcart_new_data = $model->table('sales_flat_quote')->where($yestime_where1)->where($quote_where)->count();
        //过去7天新增购物车总数
        $pastsevenday_shoppingcart_new_data = $model->table('sales_flat_quote')->where($sev_where1)->where($quote_where)->count();
        //过去30天新增购物车总数
        $pastthirtyday_shoppingcart_new_data = $model->table('sales_flat_quote')->where($thirty_where1)->where($quote_where)->count();
        //当月新增购物车总数
        $thismonth_shoppingcart_new_data = $model->table('sales_flat_quote')->where($thismonth_where1)->where($quote_where)->count();
        //上月新增购物车总数
        $lastmonth_shoppingcart_new_data = $model->table('sales_flat_quote')->where($lastmonth_where1)->where($quote_where)->count();
        //今年新增购物车总数
        $thisyear_shoppingcart_new_data = $model->table('sales_flat_quote')->where($thisyear_where1)->where($quote_where)->count();
        //上年新增购物车总数
        $lastyear_shoppingcart_new_data = $model->table('sales_flat_quote')->where($lastyear_where1)->where($quote_where)->count();
        //总共新增购物车总数
        $total_shoppingcart_new_data  = $total_shoppingcart_total_data;
        //2020-11-25 更换仪表盘页面新增购物车转化率(%)的计算方法 start
        //昨天支付成功数 从新增购物车中成功支付数
        $order_where = [];
        $order_where['o.order_type'] = 1;
        $order_success_where = [];
        $order_success_where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $yes_date = date("Y-m-d", strtotime("-1 day"));
        $yestime_where = [];
        $yestime_where[] = ['exp', Db::raw("DATE_FORMAT(o.created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yestime_wheres[] = ['exp', Db::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d') = '" . $yes_date . "'")];
        $yesterday_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($yestime_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($yestime_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();

        //过去7天从新增购物车中成功支付数
        $seven_start = date("Y-m-d", strtotime("-7 day"));
        $seven_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $sev_where = [];
        $sev_where['o.created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $sev_wheres['p.created_at'] = $sev_where1['updated_at'] = ['between', [$seven_start, $seven_end]];
        $pastsevenday_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($sev_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($sev_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();

        //过去30天从新增购物车中成功支付数
        $thirty_start = date("Y-m-d", strtotime("-30 day"));
        $thirty_end = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $thirty_where = [];
        $thirty_where['o.created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $thirty_wheres['p.created_at'] = $thirty_where1['updated_at'] = ['between', [$thirty_start, $thirty_end]];
        $pastthirtyday_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($thirty_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($thirty_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();
        //当月从新增购物车中成功支付数
        $thismonth_start = date('Y-m-01', strtotime($today));
        $thismonth_end =  $today;
        $thismonth_where = [];
        $thismonth_where['o.created_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_wheres['p.created_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_where1['updated_at'] = ['between', [$thismonth_start, $thismonth_end]];
        $thismonth_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($thismonth_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($thismonth_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();
        //上月从新增购物车中成功支付数
        $lastmonth_start = date('Y-m-01', strtotime("$today -1 month"));
        $lastmonth_end = date('Y-m-t 23:59:59', strtotime("$today -1 month"));
        $lastmonth_where = [];
        $lastmonth_where['o.created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_wheres['p.created_at'] = $lastmonth_where1['updated_at'] = ['between', [$lastmonth_start, $lastmonth_end]];
        $lastmonth_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($lastmonth_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($lastmonth_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();
        //今年从新增购物车中成功支付数
        $thisyear_start = date("Y", time()) . "-1" . "-1"; //本年开始
        $thisyear_end = $today;
        $thisyear_where = [];
        $thisyear_where['o.created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_wheres['p.created_at'] = $thisyear_where1['updated_at'] = ['between', [$thisyear_start, $thisyear_end]];
        $thisyear_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($thisyear_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($thisyear_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();


        //上年从新增购物车中成功支付数
        $lastyear_start = date('Y-01-01 00:00:00', strtotime('last year'));
        $lastyear_end = date('Y-12-31 23:59:59', strtotime('last year'));
        $lastyear_where = [];
        $lastyear_where['o.created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_wheres['p.created_at'] = $lastyear_where1['updated_at'] = ['between', [$lastyear_start, $lastyear_end]];
        $lastyear_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where($lastyear_wheres)
            ->where('p.base_grand_total','>',0)
            ->where($lastyear_where)
            ->where($order_where)
            ->where($order_success_where)
            ->count();


        //总共从新增购物车中成功支付数
        $total_order_success_data1 = $model->table('sales_flat_order')
            ->alias('o')
            ->join('sales_flat_quote p', 'o.quote_id=p.entity_id')
            ->where('p.base_grand_total','>',0)
            ->where($order_where)
            ->where($order_success_where)
            ->count();
        //2020-11-25 更换仪表盘页面新增购物车转化率(%)的计算方法 end

        //昨天购物车转化率data
        $yesterday_shoppingcart_conversion_data     = @round(($yesterday_order_success_data1 / $yesterday_shoppingcart_total_data), 4) * 100;
        //过去7天购物车转化率data
        $pastsevenday_shoppingcart_conversion_data  = @round(($pastsevenday_order_success_data1 / $pastsevenday_shoppingcart_total_data), 4) * 100;
        //过去30天购物车转化率data
        $pastthirtyday_shoppingcart_conversion_data = @round(($pastthirtyday_order_success_data1 / $pastthirtyday_shoppingcart_total_data), 4) * 100;
        //当月购物车转化率data
        $thismonth_shoppingcart_conversion_data     = @round(($thismonth_order_success_data1 / $thismonth_shoppingcart_total_data), 4) * 100;
        //上月购物车转化率data
        $lastmonth_shoppingcart_conversion_data     = @round(($lastmonth_order_success_data1 / $lastmonth_shoppingcart_total_data), 4) * 100;
        //今年购物车转化率
        $thisyear_shoppingcart_conversion_data      = @round(($thisyear_order_success_data1 / $thisyear_shoppingcart_total_data), 4) * 100;
        //上年购物车总数sql
        $lastyear_shoppingcart_conversion_data      = @round(($lastyear_order_success_data1 / $lastyear_shoppingcart_total_data), 4) * 100;
        //总共购物车转化率
        $total_shoppingcart_conversion_data         = @round(($total_order_success_data1 / $total_shoppingcart_total_data), 4) * 100;

        //昨天新增购物车转化率
        $yesterday_shoppingcart_newconversion_data  = @round(($yesterday_order_success_data / $yesterday_shoppingcart_new_data), 4) * 100;
        //过去7天新增购物车转化率
        $pastsevenday_shoppingcart_newconversion_data = @round(($pastsevenday_order_success_data / $pastsevenday_shoppingcart_new_data), 4) * 100;
        //过去30天新增购物车转化率
        $pastthirtyday_shoppingcart_newconversion_data = @round(($pastthirtyday_order_success_data / $pastthirtyday_shoppingcart_new_data), 4) * 100;
        //当月新增购物车转化率
        $thismonth_shoppingcart_newconversion_data = @round(($thismonth_order_success_data / $thismonth_shoppingcart_new_data), 4) * 100;
        //上月新增购物车转化率
        $lastmonth_shoppingcart_newconversion_data = @round(($lastmonth_order_success_data / $lastmonth_shoppingcart_new_data), 4) * 100;
        //今年新增购物车转化率
        $thisyear_shoppingcart_newconversion_data  = @round(($thisyear_order_success_data / $thisyear_shoppingcart_new_data), 4) * 100;
        //上年新增购物车总数sql
        $lastyear_shoppingcart_newconversion_data  = @round(($lastyear_order_success_data / $lastyear_shoppingcart_new_data), 4) * 100;
        //总共新增购物车转化率
        $total_shoppingcart_newconversion_data     = @round(($total_order_success_data / $total_shoppingcart_new_data), 4) * 100;

        $updateData['yesterday_shoppingcart_total']        = $yesterday_shoppingcart_total_data ?? 0;
        $updateData['pastsevenday_shoppingcart_total']     = $pastsevenday_shoppingcart_total_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_total']    = $pastthirtyday_shoppingcart_total_data ?? 0;
        $updateData['thismonth_shoppingcart_total']        = $thismonth_shoppingcart_total_data ?? 0;
        $updateData['lastmonth_shoppingcart_total']        = $lastmonth_shoppingcart_total_data ?? 0;
        $updateData['thisyear_shoppingcart_total']         = $thisyear_shoppingcart_total_data ?? 0;
        $updateData['lastyear_shoppingcart_total']         = $lastyear_shoppingcart_total_data ?? 0;
        $updateData['total_shoppingcart_total']            = $total_shoppingcart_total_data ?? 0;

        $updateData['yesterday_shoppingcart_conversion']         = $yesterday_shoppingcart_conversion_data ?? 0;
        $updateData['pastsevenday_shoppingcart_conversion']      = $pastsevenday_shoppingcart_conversion_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_conversion']     = $pastthirtyday_shoppingcart_conversion_data ?? 0;
        $updateData['thismonth_shoppingcart_conversion']         = $thismonth_shoppingcart_conversion_data ?? 0;
        $updateData['lastmonth_shoppingcart_conversion']         = $lastmonth_shoppingcart_conversion_data ?? 0;
        $updateData['thisyear_shoppingcart_conversion']          = $thisyear_shoppingcart_conversion_data ?? 0;
        $updateData['lastyear_shoppingcart_conversion']          = $lastyear_shoppingcart_conversion_data ?? 0;
        $updateData['total_shoppingcart_conversion']             = $total_shoppingcart_conversion_data ?? 0;

        $updateData['yesterday_shoppingcart_new']         = $yesterday_shoppingcart_new_data ?? 0;
        $updateData['pastsevenday_shoppingcart_new']      = $pastsevenday_shoppingcart_new_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_new']     = $pastthirtyday_shoppingcart_new_data ?? 0;
        $updateData['thismonth_shoppingcart_new']         = $thismonth_shoppingcart_new_data ?? 0;
        $updateData['lastmonth_shoppingcart_new']         = $lastmonth_shoppingcart_new_data ?? 0;
        $updateData['thisyear_shoppingcart_new']          = $thisyear_shoppingcart_new_data ?? 0;
        $updateData['lastyear_shoppingcart_new']          = $lastyear_shoppingcart_new_data ?? 0;
        $updateData['total_shoppingcart_new']             = $total_shoppingcart_new_data ?? 0;

        $updateData['yesterday_shoppingcart_newconversion']      = $yesterday_shoppingcart_newconversion_data ?? 0;
        $updateData['pastsevenday_shoppingcart_newconversion']   = $pastsevenday_shoppingcart_newconversion_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_newconversion']  = $pastthirtyday_shoppingcart_newconversion_data ?? 0;
        $updateData['thismonth_shoppingcart_newconversion']      = $thismonth_shoppingcart_newconversion_data ?? 0;
        $updateData['lastmonth_shoppingcart_newconversion']      = $lastmonth_shoppingcart_newconversion_data ?? 0;
        $updateData['thisyear_shoppingcart_newconversion']       = $thisyear_shoppingcart_newconversion_data ?? 0;
        $updateData['lastyear_shoppingcart_newconversion']       = $lastyear_shoppingcart_newconversion_data ?? 0;
        $updateData['total_shoppingcart_newconversion']          = $total_shoppingcart_newconversion_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
}
