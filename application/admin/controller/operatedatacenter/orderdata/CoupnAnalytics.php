<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class CoupnAnalytics extends Backend
{
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
                $map['created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                if (isset($filter['time_str'])) {
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['created_at'] = ['between', [$start, $end]];
            }

            if ($filter['order_platform']) {
                $site = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $site = 1;
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
                    $salesrule = Db::connect('database.db_niaho_online');
                    break;
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $salesrule->table('salesrule')
                ->where('channel', '>', 0)
                ->field('name,rule_id,channel')
                ->where($where)
                ->count();
            //所有的优惠券
            $list = $salesrule->table('salesrule')
                ->where('channel', '>', 0)
                ->field('name,rule_id,channel')
                ->where($where)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            // dump($list);die;
            //判断订单的某些条件
            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['order_type'] = ['=', 1];
            $whole_order = $model->table('sales_flat_order')
                ->where($map)
                ->count();
            $whole_order_price = $model->table('sales_flat_order')
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
                $list[$k]['use_order_num_rate'] = $whole_order != 0 ? round($list[$k]['use_order_num'] / $whole_order, 4) * 100 .'%' : 0;
                //应用订单金额
                $list[$k]['use_order_total_price'] = $model->table('sales_flat_order')
                    ->where($map)
                    ->where($andWhere)
                    ->sum('base_grand_total');
                //应用订单金额占比
                $list[$k]['use_order_total_price_rate'] = $whole_order_price != 0 ? round($list[$k]['use_order_total_price'] / $whole_order_price, 4) * 100 .'%' : 0;
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 优惠券应用订单占比
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/24
     * Time: 10:32:4
     */
    public function user_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
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
                    $salesrule = Db::connect('database.db_niaho_online');
                    break;
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");

            //判断订单的某些条件
            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['order_type'] = ['=', 1];
            //coupon_code不能为空
            $map['coupon_code'] = ['neq', 'not null'];

            //coupon_code为空 目的是为了查到未使用优惠券的订单的数量
            $maps = $map;
            $maps['coupon_code'] = null;

            //时间段内所有的订单使用的优惠券的ids
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->field('entity_id,created_at,applied_rule_ids')
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
                //某个优惠券所对应的订单的数量
                if (!$arr[$total[$k]['applied_rule_ids']]) {
                    $arr[$total[$k]['applied_rule_ids']] = 1;
                } else {
                    $arr[$total[$k]['applied_rule_ids']] += 1;
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
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 处方类型占比饼图
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function lens_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
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
                    $salesrule = Db::connect('database.db_niaho_online');
                    break;
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");

            //判断订单的某些条件
            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['order_type'] = ['=', 1];
            //coupon_code不能为空
            $map['coupon_code'] = ['neq', 'not null'];

            //coupon_code为空 目的是为了查到未使用优惠券的订单的金额的数量
            $maps = $map;
            $maps['coupon_code'] = null;

            //时间段内所有的订单使用的优惠券的ids
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->field('entity_id,created_at,applied_rule_ids,base_grand_total')
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
                    'value' => $model->table('sales_flat_order')->where($maps)->sum('base_grand_total'),
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
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
            $map['p.created_at'] = ['between', [$createat[0], $createat[3] . ' 23:59:59']];
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
        } else {
            $order_model = Db::connect('database.db_zeelool');
        }
        $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
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
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($k * 1 + 2), $v['number']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($k * 1 + 2), $v['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($k * 1 + 2), $v['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($k * 1 + 2), $v['customer_email']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . ($k * 1 + 2), $v['prescription_type']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . ($k * 1 + 2), $v['coatiing_name']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . ($k * 1 + 2), $v['price']);
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
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '订单数据' . date("YmdHis", time());;

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
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);
        $writer->save('php://output');
    }
}










