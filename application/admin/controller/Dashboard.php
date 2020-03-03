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
        $str = '奥美康
        660733826770802669
        586703426057802669
        527052770232802669
        驰豪眼镜
        驰豪
        665415043003802669
        654698272559802669
        568564640214802669
        514699522949802669
        尚为
        669314434017802669
        628074626531802669
        611738048965802669
        606171296938802669
        676730880129802669
        659120419701802669
        640839362167802669
        629442976026802669
        616933217429802669
        595817570324802669
        561510403699802669
        554720385971802669
        533602114825802669
        516993216896802669
        嘉明
        569778881686802669
        657629249078802669
        睛典定做
        睛典仓库定做
        睛典仓库采购
        561874146031802669
        553617697868802669
        684440704604802669
        643518371432802669
        615336161909802669
        586794208651802669
        553623073534802669
        睛典
        606198371669802669
        625280130786802669
        572621731065802669
        567318368500802669
        561852579760802669
        564695714794802669
        573992448384802669
        696178018965802669
        677426627380802669
        654568801884802669
        578990307399802669
        572618339381802669
        695093441381802669
        596050177747802669
        620044803810802669
        591757536753802669
        608101315116802669
        641342307261802669
        617310561042802669
        708547105195802669
        674203009877802669
        635679457553802669
        772172096724802669
        767965763676802669
        641351043100802669
        747358147578802669
        728001155925802669
        632624802023802669
        657975266498802669
        639851265912802669
        638376352659802669
        767689600750802669
        746841249624802669
        737007169742802669
        521544962907802669
        629762946024802669
        580156641109802669
        549805443991802669
        581626403783802669
        694458848016802669
        675418528234802669
        669510113229802669
        669356544845802669
        643547106917802669
        626914624655802669
        606585921283802669
        590543873273802669
        570952035213802669
        681777281934802669
        660796290770802669
        532545730778802669
        605176003074802669
        544566210670802669
        564527234091802669  
        681686689392802669
        656402114923802669
        561823264113802669
        563434498006802669
        553371651690802669
        567181186579802669
        597091232886802669
        567308322893802669
        552308258413802669
        527893792474802669
        678744995987802669
        527435395266802669
        515583459470802669
        573637216778802669
        530868769424802669
        525921186083802669
        567239107703802669
        531125602281802669
        635772675940802669
        660254144937802669
        534144291427802669
        525743651785802669
        523116355366802669
        640901603880802669
        674036803092802669
        685764737225802669
        618731169748802669
        531353184296802669
        749753057272802669
        743648480785802669
        730927521711802669
        720108514155802669
        678331746781802669
        656031043835802669
        615901827026802669
        598684098110802669
        735470912386802669
        743799778998802669
        730794624938802669
        726383680499802669
        723201024704802669
        638137984910802669
        618785475470802669
        595863522918802669
        563293888864802669
        535554432562802669
        515250912354802669
        513085378570802669
        626986883071802669
        617453537954802669
        597254499342802669
        553407075518802669
        533419809476802669
        580111968544802669
        628124450176802669
        619176034986802669
        581430369198802669
        746957986851802669
        660509570269802669
        723471043250802669
        640933697978802669
        609482177211802669
        594424162099802669
        572254849287802669
        524825315963802669
        521177441627802669
        752606850271802669
        596307619125802669
        745217248708802669
        727784192312802669
        596052576160802669
        578968290036802669
        550696835945802669
        534816642687802669
        544216514573802669
        533477986121802669
        515975938690802669
        590298689088802669
        521177569304802669
        594412865191802669
        723915745606802669
        557357377262802669
        522095392140802669
         慧眼定做
        慧眼定做
        746940001045802669
        690129026685802669
        625145633589802669
        570785312446802669
        533801410759802669
        629917443429802669
        629584547595802669
        595839841785802669
        636822723246802669
        雅致
        潮时尚
        泽宇
        557367714403802669
        778003010776802669
        745285024134802669
        773416128978802669
        773642531838802669
        625294753973802669
        654598305745802669
        626974530325802669
        625081088711802669
        591646081585802669
        579026723662802669
        534093955890802669
        黄岩海威定做
        639771777998802669
        黄岩海威
        625485601252802669
        599205699550802669
        548137794200802669
        535451171440802669
        533675106702802669
        514351873554802669
        654323456494802669
        570867331217802669
        638466786407802669
        684773858299802669
        578942209425802669
        567040833031802669
        638163330064802669
        576750338569802669
        635115104306802669
        581318113786802669
        748276866844802669
        742195009172802669
        735463680535802669
        595700161772802669
        570760000021802669
        561499392364802669
        553412194993802669
        535541472665802669
        530566817833802669
        615378432874802669
        615645698326802669
        572326081739802669
        553378755167802669
        576792577217802669
        561795235415802669
        690214242696802669
        591834016263802669
        600340611474802669
        594418370059802669
        555015584027802669
        525901859473802669
        737137026565802669
        731196578191802669
        635719779244802669
        619970755512802669
        605114945643802669
        591681602485802669
        697735489584802669
        亮典
        灿鸿眼镜
        灿鸿
        灿鸿定做
        PO20200116115727712748
        PO20200104111802588380
        德顺
        德顺定做
        684385891299802669
        雅诗诺定做
        雅诗诺
        兴亮
        兴亮眼镜
        641192544281802669
        635513442829802669
        610595106709802669
        580395264573802669
        577790307824802669
        559052515883802669
        615401313989802669
        796640803626802669
        787910464508802669
        769609761895802669
        777921056079802669
        589232961746802669
        595765857879802669
        580104896830802669
        569382146325802669
        558622241896802669
        548081409108802669
        517126049274802669
        609223425988802669
        誉博定做
        581385762440802669
        532118978985802669
        515926880261802669        
        ';
        $str = explode('
        ', $str);

        if ($str) {
            $map['check_order_number'] = ['in', $str];
            $check = new \app\admin\model\warehouse\Check();
            $res = $check->save(['is_return' => 1], $map);
        }

        echo $res;
       
    }
}
