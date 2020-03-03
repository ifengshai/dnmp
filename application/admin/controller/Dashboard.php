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

        //实时查询当天购物车数量
        $total_quote_count = Cache::get('dashboard_total_quote_count');
        if (!$total_quote_count) {
            $stime = date("Y-m-d 00:00:00", time());
            $etime = date("Y-m-d H:i:s", time());
            $swhere['created_at'] = ['between', [$stime, $etime]];
            $zeelool_quote_count = Db::connect('database.db_zeelool')->table('sales_flat_quote')->where($swhere)->count(1);
            $voogueme_quote_count = Db::connect('database.db_voogueme')->table('sales_flat_quote')->where($swhere)->count(1);
            $nihao_quote_count = Db::connect('database.db_nihao')->table('sales_flat_quote')->where($swhere)->count(1);
            $total_quote_count = $zeelool_quote_count + $voogueme_quote_count + $nihao_quote_count;
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
            $total_customer_count = $total_zeelool_customer_count + $total_voogueme_customer_count + $total_nihao_customer_count;
            Cache::set('dashboard_total_customer_count', $total_customer_count, 3600);
        }

        //总会员数
        $totaluser = Cache::get('dashboard_totaluser');
        if (!$totaluser) {
            $zeelool_customer_count = Db::connect('database.db_zeelool')->table('customer_entity')->count(1);
            $voogueme_customer_count = Db::connect('database.db_voogueme')->table('customer_entity')->count(1);
            $nihao_customer_count = Db::connect('database.db_nihao')->table('customer_entity')->count(1);
            $totaluser = $zeelool_customer_count + $voogueme_customer_count + $nihao_customer_count;
            Cache::set('dashboard_totaluser', $totaluser, 3600);
        }

        $where = [];
        $where['status'] = ['in', ['processing', 'complete', 'creditcard_proccessing']];

        //总订单数
        $totalorder = Cache::get('dashboard_totalorder');
        if (!$totalorder) {
            $zeelool_order_count = Db::connect('database.db_zeelool')->where($where)->table('sales_flat_order')->count(1);
            $voogueme_order_count = Db::connect('database.db_voogueme')->where($where)->table('sales_flat_order')->count(1);
            $nihao_order_count = Db::connect('database.db_nihao')->where($where)->table('sales_flat_order')->count(1);
            $totalorder = $zeelool_order_count + $voogueme_order_count + $nihao_order_count;
            Cache::set('dashboard_totalorder', $totalorder, 3600);
        }

        //总金额
        $totalorderamount = Cache::get('dashboard_totalorderamount');
        if (!$totalorderamount) {
            $zeelool_order_money = Db::connect('database.db_zeelool')->where($where)->table('sales_flat_order')->sum('base_grand_total');
            $voogueme_order_money = Db::connect('database.db_voogueme')->where($where)->table('sales_flat_order')->sum('base_grand_total');
            $nihao_order_money = Db::connect('database.db_nihao')->where($where)->table('sales_flat_order')->sum('base_grand_total');
            $totalorderamount = $zeelool_order_money + $voogueme_order_money + $nihao_order_money;
            Cache::set('dashboard_totalorderamount', $totalorderamount, 3600);
        }




        $this->view->assign([
            'order_num'                 => $zeelool_count + $voogueme_count + $nihao_count, //实时订单总数
            'order_sales_money'         => $zeelool_total + $voogueme_total + $nihao_total, //实时销售额
            'zeelool_count'             => $zeelool_count, //Z站实时订单数
            'voogueme_count'            => $voogueme_count, //V站实时订单数
            'nihao_count'               => $nihao_count, //nihao站实时订单数
            'zeelool_total'             => $zeelool_total, //Z站实时销售额
            'voogueme_total'            => $voogueme_total, //V站实时销售额
            'nihao_total'               => $nihao_total, //nihao站实时销售额
            'totalorder'                => $totalorder,
            'totalorderamount'          => $totalorderamount,
            'totaluser'                 => $totaluser,
            'zeeloolSalesNumList'       => $zeeloolSalesNumList, //折线图数据
            'vooguemeSalesNumList'      => $vooguemeSalesNumList,
            'nihaoSalesNumList'         => $nihaoSalesNumList,
            'yestoday'                  => $yestoday, //昨天的销量
            'last7days'                 => $last7days, //最近7天
            'yestoday_date'             => date("Y-m-d", strtotime("-1 day")),
            'today_date'                => date("Y-m-d"),
            'total_quote_count'         => $total_quote_count,
            'total_customer_count'      => $total_customer_count,

        ]);



        return $this->view->fetch();
    }

    //获取
    public function tempTest()
    {
        $map['is_visable'] = 1;
        $res = Db::table('zeelool_product')->where($map)->select();
        $list = Db::table('fa_store_house')->column('id', 'coding');
        $i = 0;
        foreach ($res as $k => $v) {
            if ($v['cargo_location_number']) {
                $data[$i]['sku'] = $v['magento_sku'];
                $data[$i]['store_id'] = $list[$v['cargo_location_number']];
                $data[$i]['createtime'] = date('Y-m-d H:i:s');
                $data[$i]['create_person'] = session('admin.nickname');
                $i++;
            }
        }
        Db::table('fa_store_sku')->insertAll($data);
    }


    //测试 zeelool 
    public function get_info_test()
    {
        set_time_limit(0);
        $where['a.is_visable'] = 1;
        $where['a.is_show'] = 0;

        $data = Db::table('zeelool_service_collaboration')->alias('a')->field('a.*,b.name')
            ->join(['zeelool_service_collaboration_category' => 'b'], 'a.cate_id=b.id')
            ->where($where)
            ->limit(2000)
            ->select();
        //查询用户角色
        $user = Db::table('fa_admin')->alias('a')
            ->join(['fa_auth_group_access' => 'b'], 'a.id=b.uid')
            ->join(['fa_auth_group' => 'c'], 'c.id=b.group_id')
            ->column('b.group_id,a.id', 'a.nickname');

        //任务分类
        $task = Db::table('fa_info_synergy_task_category')->where('is_del', 1)->column('id', 'name');
        $ids = [];
        foreach ($data as $v) {
            //处理完成
            if ($v['complate'] == 1) {
                $list['synergy_status'] = 2;
            } elseif ($v['feedback'] == 2 && $v['reply'] == 2 && $v['processing'] == 2 && $v['complate'] == 2) {
                $list['synergy_status'] = 0;
            } else {
                $list['synergy_status'] = 1;
            }
            $list['synergy_number'] = 'WO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $list['synergy_order_id'] = 2;
            $list['synergy_order_number'] = $v['increment_id'];
            $list['order_platform'] = 1;
            $list['refund_money'] = $v['refund_amount'] ?? 0;
            $list['refund_mode'] = $v['refund_mode'];
            $task_operator = explode(',', $v['task_operator']);
            $task_user = '';
            $task_dept = '';
            foreach ($task_operator as $val) {
                $task_user .= $user[$val]['id'] . '+';
                $task_dept .= $user[$val]['group_id'] . '+';
            }
            $list['dept_id'] = rtrim($task_dept, '+');
            $list['rep_id'] = rtrim($task_user, '+');
            $list['synergy_task_id'] = $task[$v['name']];
            $list['problem_desc'] = $v['description'];
            $list['create_person'] = $v['created_operator'];
            $list['create_time'] = $v['created_at'];
            $list['prty_id'] = 2;
            $tid = Db::table('fa_info_synergy_task')->insertGetId($list);
            $info = [];
            if ($v['feedback'] == 1 && $v['feedback_remark']) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['feedback_remark'];
                $info['create_person'] = $v['feedback_operator'] ?? '';
                $info['create_time'] = $v['feedback_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['reply'] == 1 && $v['reply_remark']) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['reply_remark'];
                $info['create_person'] = $v['reply_operator'] ?? '';
                $info['create_time'] = $v['reply_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['processing'] == 1) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['processing_remark'];
                $info['create_person'] = $v['processing_operator'] ?? '';
                $info['create_time'] = $v['processing_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['complate'] == 1) {
                $info['tid'] = $tid;
                $info['remark_record'] = '处理完成';
                $info['create_person'] = $v['complate_operator'] ?? '';
                $info['create_time'] = $v['complate_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            //查询是否存在日志
            $logs = Db::table('zeelool_service_collaboration_log')->where(['increment_id' => $v['increment_id'], 'is_visable' => 1])->select();
            $linfo = [];
            foreach ($logs as $ka => $va) {
                $linfo[$ka]['tid'] = $tid;
                $linfo[$ka]['remark_record'] = $va['remark'];
                $linfo[$ka]['create_person'] = $va['created_operater'] ?? '';
                $linfo[$ka]['create_time'] = $va['created_at'];
            }

            if ($linfo) {
                Db::table('fa_info_synergy_task_remark')->insertAll($linfo);
            }


            //是否存在更换镜架
            $lchange_skus = Db::table('zeelool_service_collaboration_change_sku')->where(['collaboration_id' => $v['id'], 'is_visable' => 1])->select();
            $leinfo = [];
            foreach ($lchange_skus as $kal => $vae) {
                $leinfo[$kal]['tid'] = $tid;
                $leinfo[$kal]['increment_id'] = $v['increment_id'];
                $leinfo[$kal]['platform_type'] = 1;
                $leinfo[$kal]['original_sku'] = $vae['origin_sku'];
                $leinfo[$kal]['original_number'] = $vae['origin_sku_qty'];
                $leinfo[$kal]['change_type'] = 1;
                $leinfo[$kal]['change_sku'] = $vae['new_sku'];
                $leinfo[$kal]['change_number'] = $vae['new_sku_qty'];
                $leinfo[$kal]['create_person'] = $vae['created_operater'];
                $leinfo[$kal]['create_time'] = $vae['created_at'];
            }
            if ($leinfo) {
                Db::table('fa_info_synergy_task_change_sku')->insertAll($leinfo);
            }

            $ids[] = $v['id'];
        }

        $map['id'] = ['in', $ids];

        Db::table('zeelool_service_collaboration')->where($map)->update(['is_show' => 1]);

        echo 'ok';
    }





    //测试 voogueme
    public function get_info_test_voogueme()
    {
        set_time_limit(0);
        $where['a.is_visable'] = 1;
        $where['a.is_show'] = 0;

        $data = Db::table('voogueme_service_collaboration')->alias('a')->field('a.*,b.name')
            ->join(['voogueme_service_collaboration_category' => 'b'], 'a.cate_id=b.id')
            ->where($where)
            ->limit(2000)
            ->select();
        //查询用户角色
        $user = Db::table('fa_admin')->alias('a')
            ->join(['fa_auth_group_access' => 'b'], 'a.id=b.uid')
            ->join(['fa_auth_group' => 'c'], 'c.id=b.group_id')
            ->column('b.group_id,a.id', 'a.nickname');

        //任务分类
        $task = Db::table('fa_info_synergy_task_category')->where('is_del', 1)->column('id', 'name');
        $ids = [];
        foreach ($data as $v) {
            //处理完成
            if ($v['complate'] == 1) {
                $list['synergy_status'] = 2;
            } elseif ($v['feedback'] == 2 && $v['reply'] == 2 && $v['processing'] == 2 && $v['complate'] == 2) {
                $list['synergy_status'] = 0;
            } else {
                $list['synergy_status'] = 1;
            }
            $list['synergy_number'] = 'WO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $list['synergy_order_id'] = 2;
            $list['synergy_order_number'] = $v['increment_id'];
            $list['order_platform'] = 2;
            $list['refund_money'] = $v['refund_amount'] ?? 0;
            $list['refund_mode'] = $v['refund_mode'];
            $task_operator = explode(',', $v['task_operator']);
            $task_user = '';
            $task_dept = '';
            foreach ($task_operator as $val) {
                $task_user .= $user[$val]['id'] . '+';
                $task_dept .= $user[$val]['group_id'] . '+';
            }
            $list['dept_id'] = rtrim($task_dept, '+');
            $list['rep_id'] = rtrim($task_user, '+');
            $list['synergy_task_id'] = $task[$v['name']] ?? '';
            $list['problem_desc'] = $v['description'];
            $list['create_person'] = $v['created_operator'];
            $list['create_time'] = $v['created_at'];
            $list['prty_id'] = 2;
            $tid = Db::table('fa_info_synergy_task')->insertGetId($list);
            $info = [];
            if ($v['feedback'] == 1 && $v['feedback_remark']) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['feedback_remark'];
                $info['create_person'] = $v['feedback_operator'] ?? '';
                $info['create_time'] = $v['feedback_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['reply'] == 1 && $v['reply_remark']) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['reply_remark'];
                $info['create_person'] = $v['reply_operator'] ?? '';
                $info['create_time'] = $v['reply_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['processing'] == 1) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['processing_remark'];
                $info['create_person'] = $v['processing_operator'] ?? '';
                $info['create_time'] = $v['processing_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['complate'] == 1) {
                $info['tid'] = $tid;
                $info['remark_record'] = '处理完成';
                $info['create_person'] = $v['complate_operator'] ?? '';
                $info['create_time'] = $v['complate_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            //查询是否存在日志
            $logs = Db::table('voogueme_service_collaboration_log')->where(['increment_id' => $v['increment_id'], 'is_visable' => 1])->select();
            $linfo = [];
            foreach ($logs as $ka => $va) {
                $linfo[$ka]['tid'] = $tid;
                $linfo[$ka]['remark_record'] = $va['remark'];
                $linfo[$ka]['create_person'] = $va['created_operater'] ?? '';
                $linfo[$ka]['create_time'] = $va['created_at'];
            }

            if ($linfo) {
                Db::table('fa_info_synergy_task_remark')->insertAll($linfo);
            }


            //是否存在更换镜架
            $lchange_skus = Db::table('voogueme_service_collaboration_change_sku')->where(['collaboration_id' => $v['id'], 'is_visable' => 1])->select();
            $leinfo = [];
            foreach ($lchange_skus as $kal => $vae) {
                $leinfo[$kal]['tid'] = $tid;
                $leinfo[$kal]['increment_id'] = $v['increment_id'];
                $leinfo[$kal]['platform_type'] = 2;
                $leinfo[$kal]['original_sku'] = $vae['origin_sku'];
                $leinfo[$kal]['original_number'] = $vae['origin_sku_qty'];
                $leinfo[$kal]['change_type'] = 1;
                $leinfo[$kal]['change_sku'] = $vae['new_sku'];
                $leinfo[$kal]['change_number'] = $vae['new_sku_qty'];
                $leinfo[$kal]['create_person'] = $vae['created_operater'];
                $leinfo[$kal]['create_time'] = $vae['created_at'];
            }
            if ($leinfo) {
                Db::table('fa_info_synergy_task_change_sku')->insertAll($leinfo);
            }

            $ids[] = $v['id'];
        }

        $map['id'] = ['in', $ids];

        Db::table('voogueme_service_collaboration')->where($map)->update(['is_show' => 1]);

        echo 'ok';
    }


    //测试 nihao
    public function get_info_test_nihao()
    {
        set_time_limit(0);
        $where['a.is_visable'] = 1;
        $where['a.is_show'] = 0;

        $data = Db::table('nihao_service_collaboration')->alias('a')->field('a.*,b.name')
            ->join(['nihao_service_collaboration_category' => 'b'], 'a.cate_id=b.id')
            ->where($where)
            ->limit(1000)
            ->select();
        //查询用户角色
        $user = Db::table('fa_admin')->alias('a')
            ->join(['fa_auth_group_access' => 'b'], 'a.id=b.uid')
            ->join(['fa_auth_group' => 'c'], 'c.id=b.group_id')
            ->column('b.group_id,a.id', 'a.nickname');

        //任务分类
        $task = Db::table('fa_info_synergy_task_category')->where('is_del', 1)->column('id', 'name');
        $ids = [];
        foreach ($data as $v) {
            //处理完成
            if ($v['complate'] == 1) {
                $list['synergy_status'] = 2;
            } elseif ($v['feedback'] == 2 && $v['reply'] == 2 && $v['processing'] == 2 && $v['complate'] == 2) {
                $list['synergy_status'] = 0;
            } else {
                $list['synergy_status'] = 1;
            }
            $list['synergy_number'] = 'WO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $list['synergy_order_id'] = 2;
            $list['synergy_order_number'] = $v['increment_id'];
            $list['order_platform'] = 3;
            $list['refund_money'] = $v['refund_amount'] ?? 0;
            $list['refund_mode'] = $v['refund_mode'];
            $task_operator = explode(',', $v['task_operator']);
            $task_user = '';
            $task_dept = '';
            foreach ($task_operator as $val) {
                $task_user .= $user[$val]['id'] . '+';
                $task_dept .= $user[$val]['group_id'] . '+';
            }
            $list['dept_id'] = rtrim($task_dept, '+');
            $list['rep_id'] = rtrim($task_user, '+');
            $list['synergy_task_id'] = $task[$v['name']] ?? '';
            $list['problem_desc'] = $v['description'];
            $list['create_person'] = $v['created_operator'];
            $list['create_time'] = $v['created_at'];
            $list['prty_id'] = 2;
            $tid = Db::table('fa_info_synergy_task')->insertGetId($list);
            $info = [];
            if ($v['feedback'] == 1 && $v['feedback_remark']) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['feedback_remark'];
                $info['create_person'] = $v['feedback_operator'] ?? '';
                $info['create_time'] = $v['feedback_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['reply'] == 1 && $v['reply_remark']) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['reply_remark'];
                $info['create_person'] = $v['reply_operator'] ?? '';
                $info['create_time'] = $v['reply_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['processing'] == 1) {
                $info['tid'] = $tid;
                $info['remark_record'] = $v['processing_remark'];
                $info['create_person'] = $v['processing_operator'] ?? '';
                $info['create_time'] = $v['processing_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }

            if ($v['complate'] == 1) {
                $info['tid'] = $tid;
                $info['remark_record'] = '处理完成';
                $info['create_person'] = $v['complate_operator'] ?? '';
                $info['create_time'] = $v['complate_at'];
                Db::table('fa_info_synergy_task_remark')->insertGetId($info);
            }



            //是否存在更换镜架
            $lchange_skus = Db::table('nihao_service_collaboration_change_sku')->where(['collaboration_id' => $v['id'], 'is_visable' => 1])->select();
            $leinfo = [];
            foreach ($lchange_skus as $kal => $vae) {
                $leinfo[$kal]['tid'] = $tid;
                $leinfo[$kal]['increment_id'] = $v['increment_id'];
                $leinfo[$kal]['platform_type'] = 3;
                $leinfo[$kal]['original_sku'] = $vae['origin_sku'];
                $leinfo[$kal]['original_number'] = $vae['origin_sku_qty'];
                $leinfo[$kal]['change_type'] = 1;
                $leinfo[$kal]['change_sku'] = $vae['new_sku'];
                $leinfo[$kal]['change_number'] = $vae['new_sku_qty'];
                $leinfo[$kal]['create_person'] = $vae['created_operater'];
                $leinfo[$kal]['create_time'] = $vae['created_at'];
            }
            if ($leinfo) {
                Db::table('fa_info_synergy_task_change_sku')->insertAll($leinfo);
            }

            $ids[] = $v['id'];
        }

        $map['id'] = ['in', $ids];

        Db::table('nihao_service_collaboration')->where($map)->update(['is_show' => 1]);

        echo 'ok';
    }


    /**
     * 处理采购单旧数据
     */
    protected function purchase_test()
    {
        set_time_limit(0);

        $map['a.is_visable'] = 1;
        $map['a.id'] = ['between', [10485, 10803]];
        $res = Db::table('zeelool_purchase')
            ->alias('a')
            ->where($map)->cache(3600)->select();

        //查询供应商
        $supplier = Db::table('fa_supplier')->column('id', 'supplier_name');

        foreach ($res as $v) {
            $list = [];
            $list['purchase_number'] = $v['purchase_order_id'];
            $list['purchase_name'] = $v['purchase_name'];
            $list['purchase_remark'] = $v['purchase_remark'];
            if (is_numeric($v['purchase_order_id'])) {
                $list['purchase_type'] = 2;
            } else {
                $list['purchase_type'] = 1;
            }

            $count = Db::table('fa_purchase_order')->where('old_purchase_id', $v['id'])->count();
            if ($count > 0) {
                continue;
            }

            $list['purchase_remark'] = $v['purchase_remark'];
            $list['supplier_type'] = 2;
            $list['create_person'] = $v['create_person'];
            $list['createtime'] = $v['created_at'];
            $list['product_total'] = $v['purchase_total'] - $v['purchase_freight'];
            $list['purchase_freight'] = $v['purchase_freight'];
            $list['purchase_total'] = $v['purchase_total'];
            $list['old_purchase_id'] = $v['id'];
            $list['check_remark'] = $v['check_remark'];

            if ($v['status']  == 1) {
                $list['purchase_status'] = 6;
            } else {
                $list['purchase_status'] = 7;
            }


            if ($v['status'] == 5) {
                $list['check_status'] = 2;
                $list['stock_status'] = 2;
            } elseif ($v['status'] == 6) {
                $list['check_status'] = 1;
                $list['stock_status'] = 1;
            }


            //查询明细表
            $item = Db::table('zeelool_purchase_item')
                ->alias('a')
                ->where(['a.purchase_id' => $v['id']])
                ->field('a.*,b.name,b.address')
                ->join(['zeelool_supplier' => 'b'], 'a.supplier_id=b.id', 'left')
                ->select();

            foreach ($item as $val) {
                if ($val['name']) {
                    $list['supplier_id'] = $supplier[$val['name']] ?? '';
                    $list['supplier_address'] = $val['address'];
                }

                if ($val['deposit_ratio']) {
                    $list['deposit_ratio'] = $val['deposit_ratio'];
                    $list['deposit_amount'] = $list['product_total'] * $val['deposit_ratio'] / 100;
                    $list['final_amount'] = $list['product_total'] - ($list['product_total'] * $val['deposit_ratio'] / 100);
                    $list['settlement_method'] = 3;
                } else {
                    $list['settlement_method'] = 1;
                }
            }
            $tid = Db::table('fa_purchase_order')->insertGetId($list);

            $info = [];
            foreach ($item as $key => $val) {
                $info[$key]['purchase_id'] = $tid;
                $info[$key]['purchase_order_number'] = $v['purchase_order_id'];
                $info[$key]['sku'] = $val['product_sku'];
                $info[$key]['supplier_sku'] = $val['supplier_sku'];
                $info[$key]['purchase_num'] = $val['purchase_qty'];
                $info[$key]['purchase_price'] = $val['purchase_unit_price'];
                $info[$key]['purchase_total'] = $val['purchase_row_total'];
                $info[$key]['instock_num'] = $val['check_qty'];
                $info[$key]['old_purchase_id'] = $v['id'];
                $info[$key]['old_item_id'] = $val['id'];
            }
            if ($info) {
                Db::table('fa_purchase_order_item')->insertAll($info);
            }
        }
        echo 'ok';
    }


    public function check_test()
    {
        set_time_limit(0);
        //查询采购单数据
        $where['is_process'] = 0;
        $res = Db::table('fa_purchase_order')->where($where)->limit(500)->select();

        foreach ($res as $value) {
            if (!$value['old_purchase_id']) {
                continue;
            }
            //查询明细表
            $item = Db::table('zeelool_hander_input_stock')
                ->alias('a')
                ->where(['b.purchase_id' => $value['old_purchase_id'], 'a.is_visible' => 1])
                ->join(['zeelool_hander_input_stock_item' => 'b'], 'a.id=b.input_stock_id')
                ->select();
            if (!$item) {
                continue;
            }

            $list = [];
            foreach ($item as $kl => $v) {
                $list[$v['input_stock_id']]['status'] = $v['status'];
                $list[$v['input_stock_id']]['remark'] = $v['remark'];
                $list[$v['input_stock_id']]['stock_operator'] = $v['stock_operator'];
                $list[$v['input_stock_id']]['stock_created_at'] = $v['stock_created_at'];
                $list[$v['input_stock_id']]['created_operator'] = $v['created_operator'];
                $list[$v['input_stock_id']]['created_at'] = $v['created_at'];
                $list[$v['input_stock_id']]['purchase_order_id'] = $v['purchase_order_id'];
                $list[$v['input_stock_id']]['item'][$kl] = $v;
            }


            $params = [];
            $instock = [];
            foreach ($list as $k => $val) {
                $params['check_order_number'] = 'QC' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $params['type'] = 1;
                $params['purchase_id'] = $value['id'];
                $params['supplier_id'] = $value['supplier_id'];
                $params['remark'] = $value['check_remark'];
                $params['createtime'] = $val['created_at'];
                $params['create_person'] = $val['created_operator'];
                $params['is_add_stock'] = 1;
                $params['status'] = 2;
                $params['is_stock'] = 1;
                $id = Db::table('fa_check_order')->insertGetId($params);

                $instock['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $instock['type_id'] = 1;
                $instock['check_id'] = $id;
                $instock['remark'] = $value['check_remark'];
                if ($val['status'] == 'new') {
                    $instock['status'] = 0;
                } elseif ($val['status'] == 'stock') {
                    $instock['status'] = 2;
                }

                $instock['createtime'] = $val['created_at'];
                $instock['create_person'] = $val['created_operator'];
                $instock['check_time'] = $val['stock_created_at'];
                $instock['check_person'] = $val['stock_operator'];
                $in_id = Db::table('fa_in_stock')->insertGetId($instock);

                $info = [];
                $instocks = [];
                foreach ($val['item'] as $key => $va) {
                    $info[$key]['check_id'] = $id;
                    $info[$key]['sku'] = $va['input_sku'];
                    $info[$key]['supplier_sku'] = $va['supplier_sku'] ?? '';
                    $info[$key]['purchase_id'] = $va['purchase_id'];
                    $info[$key]['purchase_num'] = $va['purchase_qty'] ?? 0;
                    $info[$key]['arrivals_num'] = $va['arrival_qty'] ?? 0;
                    $info[$key]['quantity_num'] = $va['check_qty'] ?? 0;
                    $info[$key]['sample_num'] = $va['sample_qty'] ?? 0;
                    $info[$key]['unqualified_num'] = $va['arrival_qty'] - $va['check_qty'];
                    if ($va['arrival_qty']) {
                        $info[$key]['quantity_rate'] = round($va['check_qty'] / $va['arrival_qty'] * 100, 2);
                    } else {
                        $info[$key]['quantity_rate'] = 0;
                    }

                    $info[$key]['remark'] = $va['return_remark'];



                    $instocks[$key]['in_stock_id'] = $in_id;
                    $instocks[$key]['sku'] = $va['input_sku'];
                    $instocks[$key]['in_stock_num'] = $va['input_sku_qty'];
                    $instocks[$key]['purchase_id'] = $value['id'];
                    $instocks[$key]['sample_num'] = $va['sample_qty'] ?? 0;
                }

                if ($info) {
                    Db::table('fa_check_order_item')->insertAll($info);
                }

                if ($instocks) {
                    Db::table('fa_in_stock_item')->insertAll($instocks);
                }
            }
        }

        $ids = array_column($res, 'id');
        $map['id'] = ['in', $ids];
        Db::table('fa_purchase_order')->where($map)->update(['is_process' => 1]);

        echo 'ok';
    }


    //出库记录
    public function outstock()
    {
        set_time_limit(0);
        //查询采购单数据
        $res = Db::table('zeelool_hander_output_stock')->cache(3600)->select();
        foreach ($res as $k => $v) {
            $list['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $list['type_id'] = $v['cate_id'];
            $list['order_number'] = $v['increment_id'];
            $list['status'] = 2;
            $list['remark'] = $v['remark'];
            $list['createtime'] = $v['created_at'];
            $list['create_person'] = $v['created_operator'];
            $outid = Db::table('fa_out_stock')->insertGetId($list);
            $info = Db::table('zeelool_hander_output_stock_item')->where('output_stock_id', $v['id'])->select();
            $params = [];
            foreach ($info as $key => $val) {
                $params[$key]['sku'] = $val['output_sku'];
                $params[$key]['out_stock_num'] = $val['output_sku_qty'];
                $params[$key]['out_stock_id'] = $outid;
            }
            if ($params) {
                Db::table('fa_out_stock_item')->insertAll($params);
            }
        }
        echo 'ok';
    }


    public function test()
    {
        $str = 'QC20200107175740204281
        QC20200107175740204281
        QC20200107175740204281
        QC20200107175740204281
        QC20200107175740204281
        QC20200107175740204281
        QC20200107175740204281
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107180236628428
        QC20200107175604385611
        QC20200107175557825937
        QC20200107175551331888
        QC20200107175547880310
        QC20200107175547674477
        QC20200107175535707447
        QC20200107175430981486
        QC20200107175529917676
        QC20200107175449711889
        QC20200107175454539339
        QC20200107175521499899
        QC20200107175435158375
        QC20200107180235133406
        QC20200107180151815640
        QC20200107175558709947
        QC20200107175551195929
        QC20200107175547880494
        QC20200107175547618284
        QC20200107175537811208
        QC20200107175529917676
        QC20200107175521499899
        QC20200107175454297890
        QC20200107175430403514
        QC20200107175435158375
        QC20200107180235133406
        QC20200107180151815640
        QC20200107175728348859
        QC20200107175718449682
        QC20200107175705355694
        QC20200107175550386811
        QC20200107175544385924
        QC20200107175542109244
        QC20200107175539831271
        QC20200107175529859872
        QC20200107175529917676
        QC20200107175522664796
        QC20200107175454672223
        QC20200107175453348881
        QC20200107175449283114
        QC20200107175443264724
        QC20200107175435158375
        QC20200107175432983943
        QC20200107175430354613
        QC20200107175417922544
        QC20200107175417508944
        QC20200107180235133406
        QC20200107180151815640
        QC20200107180034893494
        QC20200107175925594462
        QC20200107175728348859
        QC20200107175718449682
        QC20200107175900866134
        QC20200107175428582975
        QC20200107180004791799
        QC20200107180112576963
        QC20200107175748586418
        QC20200107180112564787
        QC20200107180112576963
        QC20200107175446462956
        QC20200107175423899875
        QC20200107175849152612
        QC20200107175835787328
        QC20200107175835917181
        QC20200107175456827159
        QC20200107175453404914
        QC20200107180245978546
        QC20200107180111548632
        QC20200107175947637596
        QC20200107175901832540
        QC20200107175835770747
        QC20200107175742205657
        QC20200107175701417707
        QC20200107180048421433
        QC20200107180048421433
        QC20200107180144408735
        QC20200107175920977680
        QC20200107175908244813
        QC20200107175855841971
        QC20200107175848209212
        QC20200107175852347821
        QC20200107175848209212
        QC20200107175852347821
        QC20200107175910818932
        QC20200107175502480687
        QC20200107175445542433
        QC20200107175421368368
        QC20200107175929908545
        QC20200107175908530613
        QC20200107175501567881
        QC20200107175908530613
        QC20200107180027601921
        QC20200107180118307411
        QC20200107180004561422
        QC20200107180004642605
        QC20200107180003578709
        QC20200107180003792458
        QC20200107180003351461
        QC20200107180003798901
        QC20200107180003572243
        QC20200107180002320675
        QC20200105151440582812
        QC20200104120551758851
        QC20200107180003992750
        QC20200107180003459577
        QC20200107180003575742
        QC20200107175959988999
        QC20200105151440582812
        QC20200104120551758851
        QC20200107180003229449
        QC20200107180003249451
        QC20200107180003224754
        QC20200107180003414208
        QC20200107180002736837
        QC20200107180054810310
        QC20200107180242112296
        QC20200107180114232829
        QC20200107175524724703
        QC20200107175441583551
        QC20200107180227187139
        QC20200107175602740691
        QC20200107175558860769
        QC20200107180241146287
        QC20200107175549695849
        QC20200107175537778230
        QC20200107180144747717
        QC20200107180221886877
        QC20200107180144631455
        QC20200107175429455565
        QC20200107180234240331
        QC20200107180233349767
        QC20200107180232691394
        QC20200107175558707901
        QC20200107175549881128
        QC20200107175541822936
        QC20200107175703566716
        QC20200107180157725635
        QC20200107175933291395
        QC20200107175750981872
        QC20200107175936361714
        QC20200107175501802354
        QC20200107175444112689
        QC20200107175439167830
        QC20200107175439274540
        QC20200107180244186238
        QC20200107180149783879
        QC20200107180050580688
        QC20200107175956593784
        QC20200107175904135467
        QC20200107175450520791
        QC20200107175432819632
        QC20200107175432918532
        QC20200107180240188775
        QC20200107175731179655
        QC20200107175429645142
        QC20200107180241358713
        QC20200107180044168316
        QC20200107175743657819
        QC20200107180244200330
        QC20200107175851770408
        QC20200107175449249606
        QC20200107175425716555
        QC20200107175848256716
        QC20200107175851356668
        QC20200107175834885837
        QC20200107175854383300
        QC20200107180032805178
        QC20200107175906312326
        QC20200107175906941151
        QC20200107175906891343
        QC20200107175855216653
        QC20200107175831847403
        QC20200107175726839541
        QC20200107175446906655
        QC20200107175723401121
        QC20200107175658435485
        QC20200107175909963508
        QC20200107175729285463
        QC20200107175728576936
        QC20200107175855594122
        QC20200107175730588605
        QC20200107180228979443
        QC20200107180227627743
        QC20200107180227746661
        QC20200107175431367861
        QC20200107175735965897
        QC20200107175718569616
        QC20200107175705656854
        QC20200107180240294515
        QC20200107180239187872
        QC20200107175442758251
        QC20200107175453186393
        QC20200107175442940393
        QC20200107180239446901
        QC20200107180117624186
        QC20200107175730629539
        QC20200107175551407260
        QC20200107175544476412
        QC20200107175538578220
        QC20200107175531956122
        QC20200107175446181721
        QC20200107175424593626
        QC20200107180113923851
        QC20200107180033520645
        QC20200107175540501317
        QC20200107175544695856
        QC20200107175538616113
        QC20200107175535729520
        QC20200107175533985578
        QC20200107180231129156
        QC20200107180117743424
        QC20200107180025104271
        QC20200107175850749180
        QC20200107175741184418
        QC20200107175658701259
        QC20200107180231129156
        QC20200107180117743424
        QC20200107180025104271
        QC20200107175850749180
        QC20200107175654699545
        QC20200107180149106270
        QC20200107180114999425
        QC20200107180114423698
        QC20200107180100946175
        QC20200107180149106270
        QC20200107180114343270
        QC20200107180114423698
        QC20200107180100946175
        QC20200107175834335444
        QC20200107175734108472
        QC20200107175932827652
        QC20200107180152584170
        QC20200107180118250891
        QC20200107175936555531
        QC20200107175549424289
        QC20200107175431743779
        QC20200107175534715841
        QC20200107180240916432
        QC20200107180058924575
        QC20200107180009707447
        QC20200107175906966782
        QC20200107175716177372
        QC20200107175703983615
        QC20200107175552486179
        QC20200107180029761530
        QC20200107175548186641
        QC20200107175537538749
        QC20200107180029398615
        QC20200107180029896204
        QC20200107180028205649
        QC20200107175929931450
        QC20200107175750878183
        QC20200107175737128742
        QC20200107175742432959
        QC20200107175731306166
        QC20200107175716177372
        QC20200107175658257325
        QC20200107180240263722
        QC20200107180240916432
        QC20200107175958674506
        QC20200107175703916747
        QC20200107180006590500
        QC20200107175534216958
        QC20200107175840294589
        QC20200107175705338981
        QC20200107175753242703
        QC20200107175717617845
        QC20200107175549642629
        QC20200107175500998430
        QC20200107180119728599
        QC20200107175903604775
        QC20200107175734195627
        QC20200107180157555813
        QC20200107180157418743
        QC20200107180025872879
        QC20200107180229990979
        QC20200107175430760805
        QC20200107180044972317
        QC20200107175723799167
        QC20200107175430760805
        QC20200107175723799167
        QC20200107175924999557
        QC20200107180051305282
        QC20200107175840944633
        QC20200107175651355733
        QC20200107175548856398
        QC20200107175651355733
        QC20200107175602593621
        QC20200107175548856398
        QC20200107175651355733
        QC20200107175602361449
        QC20200107175548856398
        QC20200107180141926464
        QC20200107175419845590
        QC20200107180149619992
        QC20200107180120770393
        QC20200107180000437566
        QC20200107175928705634
        QC20200107175734298617
        QC20200107175456603856
        QC20200107175455813481
        QC20200107175455283324
        QC20200107180235625441
        QC20200107175415382650
        QC20200107175415621823
        QC20200107180235509688
        QC20200107180112457542
        QC20200107180112705234
        QC20200107180145393883
        QC20200107180036737585
        QC20200107175832932491
        QC20200107175832976391
        QC20200107175746733510
        QC20200107175738145498
        QC20200107175734676939
        QC20200107175658526825
        QC20200107175420742449
        QC20200107175903266510
        QC20200107180232110204
        QC20200107175452971288
        QC20200107175929605340
        QC20200107175853157163
        QC20200107180232167864
        QC20200107175923310585
        QC20200107180223918922
        QC20200107175936898952
        QC20200107175550461515
        QC20200107175543116159
        QC20200107175540318264
        QC20200107180024875577
        QC20200107175902696570
        QC20200107175847794924
        QC20200107175834822406
        QC20200107175741771717
        QC20200107175729638705
        QC20200107180110284983
        QC20200107180109321185
        QC20200107175907175505
        QC20200107175907167743
        QC20200107175834774490
        QC20200107175922517424
        QC20200107175848163312
        QC20200107175501461177
        QC20200107175500672310
        QC20200107180109771561
        QC20200107180000193983
        QC20200107180149135154
        QC20200107180039873922
        QC20200107180005688707
        QC20200107175837379595
        QC20200107175719895964
        QC20200107175541627501
        QC20200107175538603702
        QC20200107180227265177
        QC20200107180118882687
        QC20200107180109966485
        QC20200107180044426417
        QC20200107180000432775
        QC20200107175831324119
        QC20200107175848736223
        QC20200107175521728857
        QC20200107175439109612
        QC20200107175429533425
        QC20200107180156380841
        QC20200107175726632267
        QC20200107175556260332
        QC20200107175529863446
        QC20200107175524442309
        QC20200107175452775799
        QC20200107180235139553
        QC20200107180101818426
        QC20200107180048643522
        QC20200107175452775799
        QC20200107180235139553
        QC20200107180101818426
        QC20200107180048643522
        QC20200107175839414801
        QC20200107175739320418
        QC20200107180235139553
        QC20200107175839356537
        QC20200107175550212779
        QC20200107175452775799
        QC20200107180235139553
        QC20200107180054290396
        QC20200107175736334782
        QC20200107175457812777
        QC20200107175457289112
        QC20200107175456105487
        QC20200107180106468378
        QC20200107175856204605
        QC20200107175653771868
        QC20200107175456795251
        QC20200107180106468378
        QC20200107175856172574
        QC20200107175653771868
        QC20200107175454518765
        QC20200107180040876329
        QC20200107180040876329
        QC20200107175443320499
        QC20200107175550518192
        QC20200107175548802132
        QC20200107175548853156
        QC20200107180233608168
        QC20200107180233367970
        QC20200107180233964649
        QC20200107180233356212
        QC20200107180054316295
        QC20200107175912962186
        QC20200107175532401236
        QC20200107175532530152
        QC20200107180223168566
        QC20200107180223952846
        QC20200107180223181618
        QC20200107180223210504
        QC20200107180223205250
        QC20200107180223129647
        QC20200107180054290257
        QC20200107175912962186
        QC20200107175538495899
        QC20200107175502554996
        QC20200107175443740304
        QC20200107175932541101
        QC20200107175448482199
        QC20200107175949973545
        QC20200107175932541101
        QC20200119134811686996
        QC20200107175604542516
        QC20200107180150545754
        QC20200107175949393177
        QC20200107175912962186
        QC20200107175443850647
        QC20200107175537666480
        QC20200107175443237340
        QC20200107175533833179
        QC20200107175533833179
        QC20200106171408398873
        QC20200107180148705728
        QC20200107180116764868
        QC20200107180040459177
        QC20200107175951527290
        QC20200107180230386648
        QC20200107180157238284
        QC20200107175841716972
        QC20200107180056960972
        QC20200107175841716972
        QC20200107180023835819
        QC20200107175443974501
        QC20200107175435308871
        QC20200107175443974501
        QC20200107175927149568
        QC20200107175443974501
        QC20200107180235389125
        QC20200107175927149568
        QC20200107175443450298
        QC20200107180116915150
        QC20200107180101501626
        QC20200107175846228775
        QC20200107175436926298
        QC20200107175855633241
        QC20200107180031372101
        QC20200107180031372101
        QC20200107180031372101
        QC20200107175939652458
        QC20200107180100823276
        QC20200107175939398263
        QC20200107180040926674
        QC20200107175927583743
        QC20200107180107624277
        QC20200107175705698563
        QC20200107175705859658
        QC20200107175705142302
        QC20200107180007996934
        QC20200107180242408933
        QC20200107175949132556
        QC20200107180242397747
        QC20200107180242408933
        QC20200107175949132556
        QC20200107175859668393
        QC20200107175838607142
        QC20200107180242408933
        QC20200107175949132556
        QC20200107175846696494
        QC20200107175949132556
        QC20200107175859668393
        QC20200107175451183958
        QC20200107180032943246
        QC20200107175753746856
        QC20200107175753606595
        QC20200107180154572918
        QC20200107180154923528
        QC20200107175901962609
        QC20200107175901268213
        QC20200107175936176162
        QC20200107175936481647
        QC20200107175937696758
        QC20200107180030427756
        QC20200107175937367237
        QC20200107175455268355
        QC20200109163144596667
        QC20200107175455572689
        QC20200109163144596667
        QC20200107175455572689
        QC20200107175937114918
        QC20200107175718111922
        QC20200107175717348170
        QC20200107175717466229
        QC20200107175717348170
        QC20200107175717261243
        QC20200107175717485430
        QC20200107175717338620
        QC20200107175717338620
        QC20200107175851918877
        QC20200107180049645309
        QC20200107180049853606
        QC20200107180049126922
        QC20200109173631920225
        QC20200107180049243261
        QC20200107175920951267
        QC20200107175920107873
        QC20200109170143124960
        QC20200109164721434183
        QC20200107180153140420
        QC20200107180154677705
        QC20200107180153140420
        QC20200109164721434183
        QC20200107180154502560
        QC20200106092048362186
        QC20200106091635738456
        QC20200105154347548287
        QC20200105115423269593
        QC20200106092048362186
        QC20200106091635738456
        QC20200105154347548287
        QC20200105154347548287
        QC20200105115423269593
        QC20200106092048362186
        QC20200106091635738456
        QC20200107175445304855
        QC20200107175753521253
        QC20200107175753546285
        QC20200107175753613272
        QC20200107175753494353
        QC20200107175836759154
        QC20200107175705757203
        QC20200107175705898491
        QC20200107175924864640
        QC20200105093326273782
        QC20200107175705443401
        QC20200105093326273782
        QC20200107175705443401
        QC20200107175705443401
        QC20200107180059951422
        QC20200107180059611978
        QC20200107175738473100
        QC20200107180059542786
        QC20200107175950903934
        QC20200107175653248953
        QC20200107180047535932
        QC20200107175906443822
        QC20200107175704431751
        QC20200107180153918191
        QC20200107180242584580
        QC20200107180227792859
        QC20200107180105350973
        QC20200107180242584580
        QC20200107180227792859
        QC20200107180105350973
        QC20200107180242584580
        QC20200107180227792859
        QC20200107180105350973
        QC20200107175933480718
        QC20200107175925366772
        QC20200107175843838330
        QC20200107180109411707
        QC20200107175843838330
        QC20200108152131256210
        QC20200104134828971586
        QC20200101145749585305
        QC20200104134828971586
        QC20200101145749585305
        QC20200107175603796422
        QC20200107175650281423
        QC20200107175954555669
        QC20200107180025893489
        QC20200107175932430733
        QC20200107175858913696
        QC20200107175842939432
        QC20200107175745671531
        QC20200107175701592670
        QC20200107180057179391
        QC20200107175521506353
        QC20200107175957585110
        QC20200107175957716405
        QC20200107175957375423
        QC20200107175936205728
        QC20200107175730894995
        QC20200107175659584443
        ';
        $str = explode('
        ', $str);

        if ($str) {
            $map['check_order_number'] = ['in', $str];
            $check = new \app\admin\model\warehouse\Check();
            $ids = $check->where($map)->column('id');
        }
        
        $checkItem = new \app\admin\model\warehouse\CheckItem();
        if ($ids) {
            $where['check_id'] = ['in', $ids];
            $checkItem->save(['is_process' => 1], $where);
        }
    }
}
