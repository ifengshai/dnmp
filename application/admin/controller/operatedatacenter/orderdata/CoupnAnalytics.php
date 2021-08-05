<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use fast\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Request;

class CoupnAnalytics extends Backend
{
    protected $noNeedRight = ['*'];
    /**
     * 优惠券明细列表
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/24
     * Time: 13:53:21
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            // dump($filter);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['create_time-operate']) {
                unset($filter['create_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                if (isset($filter['time_str'])) {
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['payment_time'] = ['between', [$start, $end]];
            }

            if ($filter['order_platform']) {
                $site = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $site = 1;
            }

            if ($filter['channel']) {
                if($site == 3 || $site == 5){
                    $map1['department'] = $filter['channel'];
                }else{
                    $map1['channel'] = $filter['channel'];
                }
            } else {
                $map1 = [];
            }
            if ($filter['name']) {
                $map2['name'] = ['like', '%'.$filter['name'].'%'];
            } else {
                $map2 = [];
            }
            switch ($site) {
                case 1:
                    $model = Db::connect('database.db_zeelool');
                    $salesrule = Db::connect('database.db_zeelool_online');
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme');
                    $salesrule = Db::connect('database.db_voogueme_online');
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao');
                    $salesrule = Db::connect('database.db_nihao_online');
                    break;
                case 5:
                    $model = Db::connect('database.db_weseeoptical');
                    $salesrule = Db::connect('database.db_weseeoptical_online');
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de');
                    $salesrule = Db::connect('database.db_zeelool_de_online');
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp');
                    $salesrule = Db::connect('database.db_zeelool_jp_online');
                    break;
                case 14:
                    $model = Db::connect('database.db_voogueme_acc');
                    $salesrule = Db::connect('database.db_voogueme_acc_online');
                    break;
                case 15:
                    $model = Db::connect('database.db_zeelool_fr');
                    $salesrule = Db::connect('database.db_zeelool_fr');
                    break;
            }
            if($site == 3){
                $model->table('orders')->query("set time_zone='+8:00'");
            }else{
                $model->table('sales_flat_order')->query("set time_zone='+8:00'");
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if($site == 3 || $site == 5){
                $total = $salesrule->table('discount_codes')
                    ->where('department', '>', 0)
                    ->field('name,id,department')
                    ->where($where)
                    ->where($map1)
                    ->where($map2)
                    ->count();
                //所有的优惠券
                $list = $salesrule->table('discount_codes')
                    ->where('department', '>', 0)
                    ->field('name,id,department')
                     ->where($where)
                    ->where($map1)
                    ->where($map2)
                    ->limit($offset, $limit)
                    ->select();
            }else{
                $total = $salesrule->table('salesrule')
                    ->where('channel', '>', 0)
                    ->field('name,rule_id,channel')
                     ->where($where)
                    ->where($map1)
                    ->where($map2)
                    ->count();
                //所有的优惠券
                $list = $salesrule->table('salesrule')
                    ->where('channel', '>', 0)
                    ->field('name,rule_id,channel')
                     ->where($where)
                    ->where($map1)
                    ->where($map2)
                    ->limit($offset, $limit)
                    ->select();
            }
            $list = collection($list)->toArray();
            //判断订单的某些条件
            $map['status'] = ['in',
                [
                    'free_processing',
                    'processing',
                    'complete',
                    'paypal_reversed',
                    'payment_review',
                    'paypal_canceled_reversal',
                    'delivered',
                    'delivery',
                    'shipped'
                ]
            ];
            $map['order_type'] = 1;
            if($site == 3 || $site == 5){
                $whole_order = $model->table('orders')
                    ->where($map)
                    ->count();
                $whole_order_price = $model->table('orders')
                    ->where($map)
                    ->sum('base_actual_payment');
            }else{
                $whole_order = $model->table('sales_flat_order')
                    ->where($map)
                    ->count();
                $whole_order_price = $model->table('sales_flat_order')
                    ->where($map)
                    ->sum('base_grand_total');
            }
            foreach ($list as $k => $v) {
                if($site == 3 || $site == 5){
                    $sql = $model->table('discount_coupon_tickets')
                        ->where('discount_coupon_id',$v['id'])
                        ->field('id')
                        ->buildSql();
                    $andWhere = [];
                    if($site == 3){
                        $andWhere[] = ['exp', Db::raw("coupon_id in " . $sql)];
                    }else{
                        $andWhere[] = ['exp', Db::raw("discount_coupon_id in " . $sql)];
                    }
                    //应用订单数量
                    $list[$k]['use_order_num'] = $model->table('orders')
                        ->where($map)
                        ->where($andWhere)
                        ->count();
                    //应用订单数量占比
                    $list[$k]['use_order_num_rate'] = $whole_order != 0 ? round($list[$k]['use_order_num'] / $whole_order* 100,2)  .'%' : 0;
                    //应用订单金额
                    $list[$k]['use_order_total_price'] = $model->table('orders')
                        ->where($map)
                        ->where($andWhere)
                        ->sum('base_actual_payment');
                    //应用订单金额占比
                    $list[$k]['use_order_total_price_rate'] = $whole_order_price != 0 ? round($list[$k]['use_order_total_price'] / $whole_order_price* 100,2)  .'%' : 0;
                }else{
                    $andWhere = "FIND_IN_SET({$v['rule_id']},applied_rule_ids)";
                    //应用订单数量
                    $list[$k]['use_order_num'] = $model->table('sales_flat_order')
                        ->where($map)
                        ->where($andWhere)
                        ->count();
                    //应用订单数量占比
                    $list[$k]['use_order_num_rate'] = $whole_order != 0 ? round($list[$k]['use_order_num'] / $whole_order,
                            4) * 100 .'%' : 0;
                    //应用订单金额
                    $list[$k]['use_order_total_price'] = $model->table('sales_flat_order')
                        ->where($map)
                        ->where($andWhere)
                        ->sum('base_grand_total');
                    //应用订单金额占比
                    $list[$k]['use_order_total_price_rate'] = $whole_order_price != 0 ? round($list[$k]['use_order_total_price'] / $whole_order_price,
                            4) * 100 .'%' : 0;
                }
            }
            if (array_filter($list) > 0) {
                //应用订单数量倒叙排列
                if ((input('sort') == 'use_order_num') && input('order') == 'desc') {
                    $sortField = array_column($list, 'use_order_num');
                    array_multisort($sortField, SORT_DESC, $list);
                    //应用订单数量正序排列
                } elseif ((input('sort') == 'use_order_num') && (input('order') == 'asc')) {
                    $sortField = array_column($list, 'use_order_num');
                    array_multisort($sortField, SORT_ASC, $list);
                } //订单金额倒叙排列
                elseif ((input('sort') == 'use_order_total_price') && input('order') == 'desc') {
                    $sortField = array_column($list, 'use_order_total_price');
                    array_multisort($sortField, SORT_DESC, $list);
                    //订单金额正序排列
                } elseif ((input('sort') == 'use_order_total_price') && (input('order') == 'asc')) {
                    $sortField = array_column($list, 'use_order_total_price');
                    array_multisort($sortField, SORT_ASC, $list);
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'meeloog', 'zeelool_de', 'zeelool_jp','zeelool_fr'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    public function user_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $seven_days = $params['time_str'];
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start.' 00:00:00 - '.$end.' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            switch ($site) {
                case 1:
                    $model = Db::connect('database.db_zeelool');
                    $salesrule = Db::connect('database.db_zeelool_online');
                    $plat = new \app\admin\model\operatedatacenter\Zeelool;
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme');
                    $salesrule = Db::connect('database.db_voogueme_online');
                    $plat = new \app\admin\model\operatedatacenter\Voogueme();
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao');
                    $salesrule = Db::connect('database.db_nihao_online');
                    $plat = new \app\admin\model\operatedatacenter\Nihao();
                    break;
                case 5:
                    $model = Db::connect('database.db_weseeoptical');
                    $salesrule = Db::connect('database.db_weseeoptical_online');
                    $plat = new \app\admin\model\operatedatacenter\Weseeoptical();
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de');
                    $salesrule = Db::connect('database.db_zeelool_de_online');
                    $plat = new \app\admin\model\operatedatacenter\ZeeloolDe();
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp');
                    $salesrule = Db::connect('database.db_zeelool_jp_online');
                    $plat = new \app\admin\model\operatedatacenter\ZeeloolJp();
                    break;
                case 14:
                    $model = Db::connect('database.db_voogueme_acc');
                    $salesrule = Db::connect('database.db_voogueme_acc_online');
                    $plat = new \app\admin\model\operatedatacenter\VooguemeAcc();
                    break;
                case 15:
                    $model = Db::connect('database.db_zeelool_fr');
                    $salesrule = Db::connect('database.db_zeelool_fr');
                    $plat = new \app\admin\model\operatedatacenter\ZeeloolFr();
                    break;
            }

            //判断订单的某些条件
            $map['sfo.status'] = ['in',
                [
                    'free_processing',
                    'processing',
                    'complete',
                    'paypal_reversed',
                    'payment_review',
                    'paypal_canceled_reversal',
                    'delivered',
                    'delivery',
                    'shipped'
                ]
            ];
            $map['sfo.payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            $map['sfo.order_type'] = ['=', 1];
            if($site == 3 || $site == 5){
                $model->table('orders')->query("set time_zone='+8:00'");
                if($site == 3){
                    //coupon_code不能为空
                    $map['sfo.coupon_id'] = ['neq', 'not null'];
                    //coupon_code为空 目的是为了查到未使用优惠券的订单的数量
                    $maps = $map;
                    $maps['sfo.coupon_id'] = null;
                    $total = $model->table('orders')
                        ->alias('sfo')
                        ->join('discount_coupon_tickets t','t.id=sfo.coupon_id')
                        ->join('discount_coupons c','c.id=t.discount_coupon_id')
                        ->where($map)
                        ->group('department')
                        ->column('count(*) count','department');
                    $json['column'] = ['运营优惠券', '渠道优惠券', '用户优惠券', '红人优惠券','主页优惠券', '客服优惠券', '网站优惠券','未使用优惠券'];
                    $json['columnData'] = [
                        [
                            'name' => '运营优惠券',
                            'value' => $total[1],
                        ],
                        [
                            'name' => '渠道优惠券',
                            'value' => $total[2],
                        ],
                        [
                            'name' => '用户优惠券',
                            'value' => $total[3],
                        ],
                        [
                            'name' => '红人优惠券',
                            'value' => $total[4],
                        ],
                        [
                            'name' => '主页优惠券',
                            'value' => $total[5],
                        ],
                        [
                            'name' => '客服优惠券',
                            'value' => $total[6],
                        ],
                        [
                            'name' => '网站优惠券',
                            'value' => $total[7],
                        ],
                        [
                            'name' => '未使用优惠券',
                            'value' => $model->table('orders')->alias('sfo')->where($maps)->count(),
                        ],
                    ];
                }else{
                    //coupon_code不能为空
                    $map['sfo.discount_coupon_id'] = ['neq', 'not null'];
                    //coupon_code为空 目的是为了查到未使用优惠券的订单的数量
                    $maps = $map;
                    $maps['sfo.discount_coupon_id'] = null;
                    $total = $model->table('orders')
                        ->alias('sfo')
                        ->join('discount_coupon_tickets t','t.id=sfo.discount_coupon_id')
                        ->join('discount_coupons c','c.id=t.discount_coupon_id')
                        ->where($map)
                        ->group('department')
                        ->column('count(*) count','department');
                    $json['column'] = ['运营优惠券', '客服优惠券', '整站优惠券','未使用优惠券'];
                    $json['columnData'] = [
                        [
                            'name' => '运营优惠券',
                            'value' => $total[1],
                        ],
                        [
                            'name' => '客服优惠券',
                            'value' => $total[2],
                        ],
                        [
                            'name' => '整站优惠券',
                            'value' => $total[3],
                        ],
                        [
                            'name' => '未使用优惠券',
                            'value' => $model->table('orders')->alias('sfo')->where($maps)->count(),
                        ],
                    ];
                }

                $json['total'] = ($plat->getOrderNum($seven_days, ''))['order_num'];
                return json(['code' => 1, 'data' => $json]);
            }else{
                //coupon_code不能为空
                $map['coupon_code'] = ['neq', 'not null'];
                //coupon_code为空 目的是为了查到未使用优惠券的订单的数量
                $maps = $map;
                $maps['coupon_code'] = null;
                $model->table('sales_flat_order')->query("set time_zone='+8:00'");
                $order_coupon_List = $model->table('sales_flat_order')
                    ->alias('sfo')
                    ->where($map)
                    ->field('sfo.coupon_rule_name,sfo.applied_rule_ids,count(*) counter,round(sum(sfo.base_grand_total),2) base_grand_total,round(sum(sfo.base_discount_amount),2) base_discount_amount')
                    ->group('sfo.coupon_rule_name')
                    ->order('counter desc')
                    ->select();
                //所有的优惠券
                $all_coupon = $salesrule->table('salesrule')
                    ->where('channel', '>', 0)
                    ->field('name,rule_id,channel')
                    ->select();
                $arr = [];
                $total = $order_coupon_List;
                foreach ($total as $k => $v) {
                    if (empty($v['coupon_rule_name'])) {
                        unset($total[$k]);
                    } else {
                        $total[$k]['applied_rule_ids'] = explode(',', $total[$k]['applied_rule_ids']);
                        foreach ($total[$k]['applied_rule_ids'] as $kk => $vv) {
                            //去除订单中多余的网站的固定优惠规则 只保留使用优惠券的优惠券的id
                            if ($vv == 56 || $vv == 359) {
                                unset($total[$k]['applied_rule_ids'][$kk]);
                            }
                        }
                        foreach ($total[$k]['applied_rule_ids'] as $kk => $vv) {
                            $total[$k]['applied_rule_ids'] = $vv;
                        }
                        //某个优惠券所对应的订单的数量
                        if (!$arr[$total[$k]['applied_rule_ids']]) {
                            $arr[$total[$k]['applied_rule_ids']] = $total[$k]['counter'];
                        } else {
                            $arr[$total[$k]['applied_rule_ids']] += $total[$k]['counter'];
                        }
                    }

                }
                //根据优惠券所属的分组 计算某个分组的订单的数量
                $num = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
                foreach ($all_coupon as $k => $v) {
                    $num[$v['channel']] += $arr[$v['rule_id']];
                }

                $json['column'] = ['网站优惠券', '主页优惠券', '用户优惠券', '渠道优惠券', '客服优惠券', '未使用优惠券',];
                $json['columnData'] = [
                    [
                        'name' => '网站优惠券',
                        'value' => $num[1],
                    ],
                    [
                        'name' => '主页优惠券',
                        'value' => $num[2],
                    ],
                    [
                        'name' => '用户优惠券',
                        'value' => $num[3],
                    ],
                    [
                        'name' => '渠道优惠券',
                        'value' => $num[4],
                    ],
                    [
                        'name' => '客服优惠券',
                        'value' => $num[5],
                    ],
                    [
                        'name' => '未使用优惠券',
                        'value' => $model->table('sales_flat_order')->where($maps)->count(),
                    ],
                ];

                $json['total'] = ($plat->getOrderNum($seven_days, ''))['order_num'];
                return json(['code' => 1, 'data' => $json]);
            }
        }
    }

    /**
     * 优惠券金额占比
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/2
     * Time: 9:53:36
     */
    public function lens_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                $seven_days = $params['time_str'];
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start.' 00:00:00 - '.$end.' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            switch ($site) {
                case 1:
                    $model = Db::connect('database.db_zeelool');
                    $salesrule = Db::connect('database.db_zeelool_online');
                    $plat = new \app\admin\model\operatedatacenter\Zeelool;
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme');
                    $salesrule = Db::connect('database.db_voogueme_online');
                    $plat = new \app\admin\model\operatedatacenter\Voogueme;
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao');
                    $salesrule = Db::connect('database.db_nihao_online');
                    $plat = new \app\admin\model\operatedatacenter\Nihao();
                    break;
                case 5:
                    $model = Db::connect('database.db_weseeoptical');
                    $salesrule = Db::connect('database.db_weseeoptical_online');
                    $plat = new \app\admin\model\operatedatacenter\Weseeoptical();
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de');
                    $salesrule = Db::connect('database.db_zeelool_de_online');
                    $plat = new \app\admin\model\operatedatacenter\ZeeloolDe();
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp');
                    $salesrule = Db::connect('database.db_zeelool_jp_online');
                    $plat = new \app\admin\model\operatedatacenter\ZeeloolJp();
                    break;
                case 14:
                    $model = Db::connect('database.db_voogueme_acc');
                    $salesrule = Db::connect('database.db_voogueme_acc_online');
                    $plat = new \app\admin\model\operatedatacenter\VooguemeAcc();
                    break;
                case 15:
                    $model = Db::connect('database.db_zeelool_fr');
                    $salesrule = Db::connect('database.db_zeelool_fr');
                    $plat = new \app\admin\model\operatedatacenter\ZeeloolFr();
                    break;
            }
            //判断订单的某些条件
            $map['o.status'] = ['in',
                [
                    'free_processing',
                    'processing',
                    'complete',
                    'paypal_reversed',
                    'payment_review',
                    'paypal_canceled_reversal',
                    'delivered',
                    'delivery',
                    'shipped'
                ]
            ];
            $map['o.payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            $map['o.order_type'] = ['=', 1];
            if($site == 3 || $site == 5){
                $model->table('orders')->query("set time_zone='+8:00'");
                if($site == 3){
                    //coupon_code不能为空
                    $map['o.coupon_id'] = ['neq', 'not null'];

                    //coupon_code为空 目的是为了查到未使用优惠券的订单的金额的数量
                    $maps = $map;
                    $maps['o.coupon_id'] = null;
                    //优惠券对应的金额
                    $total = $model->table('orders')
                        ->alias('o')
                        ->join('discount_coupon_tickets t','t.id=o.coupon_id')
                        ->join('discount_coupons c','c.id=t.discount_coupon_id')
                        ->where($map)
                        ->group('department')
                        ->column('sum(base_actual_payment) base_actual_payment','department');
                    $json['column'] = ['运营优惠券', '渠道优惠券', '用户优惠券', '红人优惠券','主页优惠券', '客服优惠券', '网站优惠券','未使用优惠券'];
                    $json['columnData'] = [
                        [
                            'name' => '运营优惠券',
                            'value' => $total[1],
                        ],
                        [
                            'name' => '渠道优惠券',
                            'value' => $total[2],
                        ],
                        [
                            'name' => '用户优惠券',
                            'value' => $total[3],
                        ],
                        [
                            'name' => '红人优惠券',
                            'value' => $total[4],
                        ],
                        [
                            'name' => '主页优惠券',
                            'value' => $total[5],
                        ],
                        [
                            'name' => '客服优惠券',
                            'value' => $total[6],
                        ],
                        [
                            'name' => '网站优惠券',
                            'value' => $total[7],
                        ],
                        [
                            'name' => '未使用优惠券',
                            'value' => $model->table('orders')->alias('o')->where($maps)->sum('base_actual_payment'),
                        ],
                    ];
                }else{
                    //coupon_code不能为空
                    $map['o.discount_coupon_id'] = ['neq', 'not null'];

                    //coupon_code为空 目的是为了查到未使用优惠券的订单的金额的数量
                    $maps = $map;
                    $maps['o.discount_coupon_id'] = null;
                    //优惠券对应的金额
                    $total = $model->table('orders')
                        ->alias('o')
                        ->join('discount_coupon_tickets t','t.id=o.discount_coupon_id')
                        ->join('discount_coupons c','c.id=t.discount_coupon_id')
                        ->where($map)
                        ->group('department')
                        ->column('sum(base_actual_payment) base_actual_payment','department');
                    $json['column'] = ['运营优惠券', '客服优惠券', '整站优惠券','未使用优惠券'];
                    $json['columnData'] = [
                        [
                            'name' => '运营优惠券',
                            'value' => $total[1],
                        ],
                        [
                            'name' => '客服优惠券',
                            'value' => $total[2],
                        ],
                        [
                            'name' => '整站优惠券',
                            'value' => $total[3],
                        ],
                        [
                            'name' => '未使用优惠券',
                            'value' => $model->table('orders')->alias('o')->where($maps)->sum('base_actual_amount_paid'),
                        ],
                    ];
                }
                $json['total'] = ($plat->getSalesTotalMoney($seven_days, ''))['sales_total_money'];
                return json(['code' => 1, 'data' => $json]);
            }else{
                $model->table('sales_flat_order')->query("set time_zone='+8:00'");
                //coupon_code不能为空
                $map['o.coupon_code'] = ['neq', 'not null'];

                //coupon_code为空 目的是为了查到未使用优惠券的订单的金额的数量
                $maps = $map;
                $maps['o.coupon_code'] = null;
                //时间段内所有的订单使用的优惠券的ids
                $total = $model->table('sales_flat_order')
                    ->alias('o')
                    ->where($map)
                    ->field('o.entity_id,o.created_at,o.applied_rule_ids,o.base_grand_total')
                    ->select();
                //所有的优惠券
                $all_coupon = $salesrule->table('salesrule')
                    ->where('channel', '>', 0)
                    ->field('name,rule_id,channel')
                    ->select();
                $arr = [];
                foreach ($total as $k => $v) {
                    $total[$k]['applied_rule_ids'] = explode(',', $total[$k]['applied_rule_ids']);
                    foreach ($total[$k]['applied_rule_ids'] as $kk => $vv) {
                        //去除订单中多余的网站的固定优惠规则 只保留使用优惠券的优惠券的id
                        if ($vv == 56 || $vv == 359) {
                            unset($total[$k]['applied_rule_ids'][$kk]);
                        }
                    }
                    foreach ($total[$k]['applied_rule_ids'] as $kk => $vv) {
                        $total[$k]['applied_rule_ids'] = $vv;
                    }
                    //某个优惠券所对应的订单的 总金额
                    if (!$arr[$total[$k]['applied_rule_ids']]) {
                        $arr[$total[$k]['applied_rule_ids']] = $total[$k]['base_grand_total'];
                    } else {
                        $arr[$total[$k]['applied_rule_ids']] += $total[$k]['base_grand_total'];
                    }
                }

                //根据优惠券所属的分组 计算某个分组的订单的金额
                $num = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
                foreach ($all_coupon as $k => $v) {
                    $num[$v['channel']] += $arr[$v['rule_id']];
                }

                $json['column'] = ['网站优惠券', '主页优惠券', '用户优惠券', '渠道优惠券', '客服优惠券', '未使用优惠券',];
                $json['columnData'] = [
                    [
                        'name' => '网站优惠券',
                        'value' => round($num[1], 2),
                    ],
                    [
                        'name' => '主页优惠券',
                        'value' => round($num[2], 2),
                    ],
                    [
                        'name' => '用户优惠券',
                        'value' => round($num[3], 2),
                    ],
                    [
                        'name' => '渠道优惠券',
                        'value' => round($num[4], 2),
                    ],
                    [
                        'name' => '客服优惠券',
                        'value' => round($num[5], 2),
                    ],
                    [
                        'name' => '未使用优惠券',
                        'value' => $model->table('sales_flat_order')->where($maps)->sum('base_grand_total'),
                    ],
                ];
                $json['total'] = ($plat->getSalesTotalMoney($seven_days, ''))['sales_total_money'];
                return json(['code' => 1, 'data' => $json]);
            }
        }
    }

    public function export()
    {
        $this->model = new \app\admin\model\warehouse\Check;
        $this->check_item = new \app\admin\model\warehouse\CheckItem;
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $sku = input('sku');

        if ($time_str) {
            $createat = explode(' ', $time_str);
            $map['p.created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
        }
        if ($sku) {
            $map['p.sku'] = $sku;
        }
        $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.coatiing_name,p.frame_price,p.index_price';
        if ($order_platform == 2) {
            $order_model = Db::connect('database.db_voogueme');
        } elseif ($order_platform == 3) {
            $order_model = Db::connect('database.db_nihao');
            $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.frame_price,p.index_price';
        } elseif ($order_platform == 10) {
            $order_model = Db::connect('database.db_zeelool_de');
        } elseif ($order_platform == 11) {
            $order_model = Db::connect('database.db_zeelool_jp');
        } elseif ($order_platform == 14) {
            $order_model = Db::connect('database.db_voogueme_acc');
        } elseif ($order_platform == 15) {
            $order_model = Db::connect('database.db_zeelool_fr');
        } else {
            $order_model = Db::connect('database.db_zeelool');
        }
        $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        $map['o.status'] = ['in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery'
            ]
        ];
        $map['o.order_type'] = 1;

        $list = $order_model->table('sales_flat_order_item_prescription')
            ->alias('p')
            ->join('sales_flat_order o', 'p.order_id=o.entity_id')
            ->field($field)
            ->where($map)
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $key => $value) {
            $list[$key]['number'] = $key + 1;
            $list[$key]['price'] = round($value['frame_price'] + $value['index_price'], 2);
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "序号");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "订单号");
        $spreadsheet->getActiveSheet()->setCellValue("C1", "订单时间");
        $spreadsheet->getActiveSheet()->setCellValue("D1", "支付邮箱");
        $spreadsheet->getActiveSheet()->setCellValue("E1", "处方类型");
        $spreadsheet->getActiveSheet()->setCellValue("F1", "镀膜类型");
        $spreadsheet->getActiveSheet()->setCellValue("G1", "价格（镜框+镜片）");


        $spreadsheet->setActiveSheetIndex(0)->setTitle('SKU明细');
        $spreadsheet->setActiveSheetIndex(0);
        foreach ($list as $k => $v) {
            $spreadsheet->getActiveSheet()->setCellValue('A'.($k * 1 + 2), $v['number']);
            $spreadsheet->getActiveSheet()->setCellValue('B'.($k * 1 + 2), $v['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue('C'.($k * 1 + 2), $v['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue('D'.($k * 1 + 2), $v['customer_email']);
            $spreadsheet->getActiveSheet()->setCellValue('E'.($k * 1 + 2), $v['prescription_type']);
            $spreadsheet->getActiveSheet()->setCellValue('F'.($k * 1 + 2), $v['coatiing_name']);
            $spreadsheet->getActiveSheet()->setCellValue('G'.($k * 1 + 2), $v['price']);
        }
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:'.$spreadsheet->getActiveSheet()->getHighestColumn().$spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:Q'.$spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '订单数据'.date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }
        //输出名称
        header('Content-Disposition: attachment;filename="'.$savename.'.'.$format.'"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * 导出优惠券数据
     * Interface export_coupon_analytics
     * @package app\admin\controller\operatedatacenter\orderdata
     * @author jhh
     * @date   2021/4/7 16:54:05
     */
    public function export_coupon_analytics()
    {
        $map['created_at'] = ['between', ['2021-01-01 00:00:00', '2021-03-31 23:59:59']];
        $site = 1;
        switch ($site) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                $salesrule = Db::connect('database.db_zeelool_online');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                $salesrule = Db::connect('database.db_voogueme_online');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                $salesrule = Db::connect('database.db_nihao_online');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                $salesrule = Db::connect('database.db_zeelool_de_online');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                $salesrule = Db::connect('database.db_zeelool_jp_online');
                break;
            case 15:
                $model = Db::connect('database.db_zeelool_fr');
                $salesrule = Db::connect('database.db_zeelool_fr');
                break;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        //所有的优惠券
        $list = $salesrule->table('salesrule')
            ->where('channel', '>', 0)
            ->field('name,rule_id,channel')
            ->select();
        $list = collection($list)->toArray();
        //判断订单的某些条件
        $map['status'] = ['in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered',
                'delivery'
            ]
        ];
        $map['order_type'] = ['=', 1];
        $wholeOrder = $model->table('sales_flat_order')
            ->where($map)
            ->count();
        $wholeOrderPrice= $model->table('sales_flat_order')
            ->where($map)
            ->sum('base_grand_total');
        foreach ($list as $k => $v) {
            $andWhere = "FIND_IN_SET({$v['rule_id']},applied_rule_ids)";
            //应用订单数量
            $list[$k]['use_order_num'] = $model->table('sales_flat_order')
                ->where($map)
                ->where($andWhere)
                ->count();
            //应用订单数量占比
            $list[$k]['use_order_num_rate'] = $wholeOrder != 0 ? round($list[$k]['use_order_num'] / $wholeOrder,
                    4) * 100 .'%' : 0;
            //应用订单金额
            $list[$k]['use_order_total_price'] = $model->table('sales_flat_order')
                ->where($map)
                ->where($andWhere)
                ->sum('base_grand_total');
            //应用订单金额占比
            $list[$k]['use_order_total_price_rate'] = $wholeOrderPrice != 0 ? round($list[$k]['use_order_total_price'] / $wholeOrderPrice,
                    4) * 100 .'%' : 0;
        }
        $headlist = [
            '优惠券类型',  '优惠券名称', '应用订单数量',
            '订单数量占比',  '订单金额', '订单金额占比'
        ];
        $path = "/uploads/";
        $fileName = '优惠券分析1-3月份数据-T';
        Excel::writeCsv($list, $headlist, $path . $fileName);
        //获取当前域名
        $request = Request::instance();
        $domain = $request->domain();
        header('Location: '.$domain.$path.$fileName.'.csv');
    }
}










