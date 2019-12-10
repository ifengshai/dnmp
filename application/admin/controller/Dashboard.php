<?php

namespace app\admin\controller;

use app\admin\model\OrderStatistics;
use app\common\controller\Backend;
use think\Config;
use think\Db;

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
        $zeeloolSalesNumList = $vooguemeSalesNumList = $nihaoSalesNumList = [];
        foreach ($list as $k => $v) {
            $zeeloolSalesNumList[$v['create_date']] = $v['zeelool_sales_num'];
            $vooguemeSalesNumList[$v['create_date']] = $v['voogueme_sales_num'];
            $nihaoSalesNumList[$v['create_date']] = $v['nihao_sales_num'];
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
        $where['status'] = ['in', ['processing', 'complete', 'creditcard_proccessing']];
        $zeelool_count = $zelool->where($where)->count(1);
        $zeelool_total = $zelool->where($where)->sum('base_grand_total');

        $voogume = new \app\admin\model\order\order\Voogueme;
        $voogueme_count = $voogume->where($where)->count(1);
        $voogueme_total = $voogume->where($where)->sum('base_grand_total');

        $nihao = new \app\admin\model\order\order\Nihao;
        $nihao_count = $nihao->where($where)->count(1);
        $nihao_total = $nihao->where($where)->sum('base_grand_total');

        $this->view->assign([
            'order_num'                 => $zeelool_count + $voogueme_count + $nihao_count,//实时订单总数
            'order_sales_money'         => $zeelool_total + $voogueme_total + $nihao_total,//实时销售额
            'zeelool_count'             => $zeelool_count,//Z站实时订单数
            'voogueme_count'            => $voogueme_count,//V站实时订单数
            'nihao_count'               => $nihao_count,//nihao站实时订单数
            'zeelool_total'             => $zeelool_total,//Z站实时销售额
            'voogueme_total'            => $voogueme_total,//V站实时销售额
            'nihao_total'               => $nihao_total,//nihao站实时销售额
            'totalorder'                => 32143,
            'totalorderamount'          => 174800,
            'zeeloolSalesNumList'       => $zeeloolSalesNumList,//折线图数据
            'vooguemeSalesNumList'      => $vooguemeSalesNumList,
            'nihaoSalesNumList'         => $nihaoSalesNumList,
            'yestoday'                  => $yestoday,//昨天的销量
            'last7days'                 => $last7days,//最近7天
            'yestoday_date'             => date("Y-m-d", strtotime("-1 day")),
            'today_date'                => date("Y-m-d"),
        ]);



        return $this->view->fetch();
    }
}
