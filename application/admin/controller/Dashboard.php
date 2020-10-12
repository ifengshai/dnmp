<?php

namespace app\admin\controller;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Config;
use think\Db;
use think\Cache;


/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        //查询三个站数据
        $orderStatistics = new OrderStatistics();
        $list = $orderStatistics->getAllData();
        $zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = $meeloogSalesNumList = $zeeloolEsSalesNumList = $zeeloolDeSalesNumList = $zeeloolJpSalesNumList =  [];
        foreach ($list as $k => $v) {
            $zeeloolSalesNumList[$v['create_date']] = $v['zeelool_sales_num'];
            $vooguemeSalesNumList[$v['create_date']] = $v['voogueme_sales_num'];
            $nihaoSalesNumList[$v['create_date']] = $v['nihao_sales_num'];
            $meeloogSalesNumList[$v['create_date']] = $v['meeloog_sales_num'];
            $zeeloolEsSalesNumList[$v['create_date']] = $v['zeelool_es_sales_num'];
            $zeeloolDeSalesNumList[$v['create_date']] = $v['zeelool_de_sales_num'];
            $zeeloolJpSalesNumList[$v['create_date']] = $v['zeelool_jp_sales_num'];
        }

        //查询昨日数据
        $time = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = $time;
        $yestoday = $orderStatistics->where($map)->find();

        //查询最近7天
        $stime = date("Y-m-d", strtotime("-7 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        $last7days = $orderStatistics->where($map)->field('sum(all_sales_money) as all_sales_money,sum(all_sales_num) as all_sales_num')->find();


        //查询实时订单数
        //计算当天的销量
        $stime = date("Y-m-d 00:00:00", time());
        $etime = date("Y-m-d H:i:s", time());
        $where['created_at'] = ['between', [$stime, $etime]];
        $zelool = new \app\admin\model\order\order\Zeelool;
        $where['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'creditcard_proccessing', 'complete']];
        $where['order_type'] = ['not in', [4, 5]];
        $zeelool_count = $zelool->where($where)->count(1);
        $zeelool_total = $zelool->where($where)->sum('base_grand_total');

        $voogume = new \app\admin\model\order\order\Voogueme;
        $voogueme_count = $voogume->where($where)->count(1);
        $voogueme_total = $voogume->where($where)->sum('base_grand_total');

        $nihao = new \app\admin\model\order\order\Nihao;
        $nihao_count = $nihao->where($where)->count(1);
        $nihao_total = $nihao->where($where)->sum('base_grand_total');

        $meeloog = new \app\admin\model\order\order\Meeloog;
        $meeloog_count = $meeloog->where($where)->count(1);
        $meeloog_total = $meeloog->where($where)->sum('base_grand_total');

        $zeelool_es = new \app\admin\model\order\order\ZeeloolEs();
        $zeelool_es_count = $zeelool_es->where($where)->count(1);
        $zeelool_es_total = $zeelool_es->where($where)->sum('base_grand_total');

        $zeelool_de = new \app\admin\model\order\order\ZeeloolDe;
        $zeelool_de_count = $zeelool_de->where($where)->count(1);
        $zeelool_de_total = $zeelool_de->where($where)->sum('base_grand_total');

        $zeelool_jp = new \app\admin\model\order\order\ZeeloolJp();
        $zeelool_jp_count = $zeelool_jp->where($where)->count(1);
        $zeelool_jp_total = $zeelool_jp->where($where)->sum('base_grand_total');

        //实时查询当天购物车数量
        $total_quote_count = Cache::get('dashboard_total_quote_count');
        if (!$total_quote_count) {
            $stime = date("Y-m-d 00:00:00", time());
            $etime = date("Y-m-d H:i:s", time());
            $swhere['created_at'] = ['between', [$stime, $etime]];
            $zeelool_quote_count = Db::connect('database.db_zeelool')->table('sales_flat_quote')->where($swhere)->count(1);
            $voogueme_quote_count = Db::connect('database.db_voogueme')->table('sales_flat_quote')->where($swhere)->count(1);
            $nihao_quote_count = Db::connect('database.db_nihao')->table('sales_flat_quote')->where($swhere)->count(1);
            $meeloog_quote_count = Db::connect('database.db_meeloog')->table('sales_flat_quote')->where($swhere)->count(1);
            $zeelool_es_quote_count = Db::connect('database.db_zeelool_es')->table('sales_flat_quote')->where($swhere)->count(1);
            $zeelool_de_quote_count = Db::connect('database.db_zeelool_de')->table('sales_flat_quote')->where($swhere)->count(1);
            $zeelool_jp_quote_count = Db::connect('database.db_zeelool_jp')->table('sales_flat_quote')->where($swhere)->count(1);
            $total_quote_count = $zeelool_quote_count + $voogueme_quote_count + $nihao_quote_count + $meeloog_quote_count;
            Cache::set('dashboard_total_quote_count', $total_quote_count, 3600);
        }

        //实时用户数量
        $total_customer_count = Cache::get('dashboard_total_customer_count');
        if (!$total_customer_count) {
            $stime = date("Y-m-d 00:00:00", time());
            $etime = date("Y-m-d H:i:s", time());
            $swhere['created_at'] = ['between', [$stime, $etime]];
            $total_zeelool_customer_count = Db::connect('database.db_zeelool')->table('customer_entity')->where($swhere)->count(1);
            $total_voogueme_customer_count = Db::connect('database.db_voogueme')->table('customer_entity')->where($swhere)->count(1);
            $total_nihao_customer_count = Db::connect('database.db_nihao')->table('customer_entity')->where($swhere)->count(1);
            $total_meeloog_customer_count = Db::connect('database.db_meeloog')->table('customer_entity')->where($swhere)->count(1);
            $total_zeelool_es_customer_count = Db::connect('database.db_zeelool_es')->table('customer_entity')->where($swhere)->count(1);
            $total_zeelool_de_customer_count = Db::connect('database.db_zeelool_de')->table('customer_entity')->where($swhere)->count(1);
            $total_zeelool_jp_customer_count = Db::connect('database.db_zeelool_jp')->table('customer_entity')->where($swhere)->count(1);
            $total_customer_count = $total_zeelool_customer_count + $total_voogueme_customer_count + $total_nihao_customer_count + $total_meeloog_customer_count;
            Cache::set('dashboard_total_customer_count', $total_customer_count, 3600);
        }

        $operation = new \app\admin\model\OperationAnalysis;
        //总会员数
        $totaluser = $operation->sum('total_sign_customer');

        //总订单数
        $totalorder = $operation->sum('total_order_num');

        //总金额
        $totalorderamount = $operation->sum('total_sales_money');

        $this->view->assign([
            'order_num'                 => $zeelool_count + $voogueme_count + $nihao_count + $meeloog_count, //实时订单总数
            'order_sales_money'         => $zeelool_total + $voogueme_total + $nihao_total + $meeloog_total, //实时销售额
            'zeelool_count'             => $zeelool_count, //Z站实时订单数
            'voogueme_count'            => $voogueme_count, //V站实时订单数
            'nihao_count'               => $nihao_count, //nihao站实时订单数
            'meeloog_count'             => $meeloog_count, //meeloog站实时订单数
            'zeelool_es_count'          => $zeelool_es_count, //西语站实时订单数
            'zeelool_de_count'          => $zeelool_de_count, //德语站实时订单数
            'zeelool_jp_count'          => $zeelool_jp_count, //日语站实时订单数
            'zeelool_total'             => $zeelool_total, //Z站实时销售额
            'voogueme_total'            => $voogueme_total, //V站实时销售额
            'nihao_total'               => $nihao_total, //nihao站实时销售额
            'meeloog_total'             => $meeloog_total, //meeloog站实时销售额
            'zeelool_es_total'          => $zeelool_es_total, //西语站实时销售额
            'zeelool_de_total'          => $zeelool_de_total, //德语站实时销售额
            'zeelool_jp_total'          => $zeelool_jp_total, //日语站实时销售额
            'totalorder'                => $totalorder,
            'totalorderamount'          => $totalorderamount,
            'totaluser'                 => $totaluser,
            'zeeloolSalesNumList'       => $zeeloolSalesNumList, //折线图数据
            'vooguemeSalesNumList'      => $vooguemeSalesNumList,
            'nihaoSalesNumList'         => $nihaoSalesNumList,
            'meeloogSalesNumList'       => $meeloogSalesNumList, //折线图数据
            'zeeloolEsSalesNumList'     => $zeeloolEsSalesNumList,
            'zeeloolDeSalesNumList'     => $zeeloolDeSalesNumList,
            'zeeloolJpSalesNumList'     => $zeeloolJpSalesNumList,
            'yestoday'                  => $yestoday, //昨天的销量
            'last7days'                 => $last7days, //最近7天
            'yestoday_date'             => date("Y-m-d", strtotime("-1 day")),
            'today_date'                => date("Y-m-d"),
            'total_quote_count'         => $total_quote_count,
            'total_customer_count'      => $total_customer_count,

        ]);



        return $this->view->fetch();
    }

    public function zeelool()
    {
    }
    public function voogueme()
    {
    }
    public function nihao()
    {
    }
    public function meeloog()
    {
    }
    public function wesee()
    {
    }
    public function all()
    {
    }
    public function zeelool_es()
    {
    }
    public function zeelool_de()
    {
    }
    public function zeelool_jp()
    {
    }
}
