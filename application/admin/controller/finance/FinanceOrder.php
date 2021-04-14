<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use fast\Excel;
use think\Request;


class FinanceOrder extends Backend
{
     /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['batch_export_xls'];

    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->finance_cost = new \app\admin\model\finance\FinanceCost();
    }
    public function index()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $model = Db::connect('database.db_delivery');
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq', 9]])
                ->where(['bill_type' => ['neq', 11]])
                ->order($sort, $order)
                ->group('order_number')
                ->count();

            $list = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq', 9]])
                ->where(['bill_type' => ['neq', 11]])
                ->order($sort, $order)
                ->group('order_number')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                //查询成本
                $list_z = $this->finance_cost
                    ->where(['order_number' => $value['order_number'], 'action_type' => 1, 'type' => 2])
                    ->select();
                $list_j = $this->finance_cost
                    ->where(['order_number' => $value['order_number'], 'action_type' => 2, 'type' => 2])
                    ->select();
                $list_z_frame = array_sum(array_column($list_z, 'frame_cost'));
                $list_z_lens = array_sum(array_column($list_z, 'lens_cost'));
                $list_j_frame = array_sum(array_column($list_j, 'frame_cost'));
                $list_j_lens = array_sum(array_column($list_j, 'lens_cost'));
                $list[$key]['frame_cost'] = $list_z_frame - $list_j_frame;
                $list[$key]['lens_cost'] = $list_z_lens - $list_j_lens;
                //查询收入
                $list_zs = $this->finance_cost
                    ->where(['order_number' => $value['order_number'], 'action_type' => 1, 'type' => 1])
                    ->select();
                $list_js = $this->finance_cost
                    ->where(['order_number' => $value['order_number'], 'action_type' => 2, 'type' => 1])
                    ->select();
                $list_zs_income_amount = array_sum(array_column($list_zs, 'income_amount'));
                $list_js_income_amount = array_sum(array_column($list_js, 'income_amount'));
                $list[$key]['income_amount'] = $list_zs_income_amount - $list_js_income_amount;
                //物流成本
                $list[$key]['fi_actual_payment_fee'] = $model->table('ld_delivery_order_finance')->where(['increment_id' => $value['order_number'], 'fi_review_status' => ['in', '0,10,100']])->value('fi_actual_payment_fee');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    //批量导出xls
    public function batch_export_xls_bak()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        if ($ids) {
            $ids = explode(',', $ids);
            $order_number = [];
            foreach ($ids as $key => $value) {
                $order_number[] = $this->finance_cost->where(['id' => $value])->value('order_number');
            }
            $map['order_number'] = ['in', $order_number];
        }

        [$where] = $this->buildparams();
        $list = $this->finance_cost
            ->where($where)
            ->where(['bill_type' => ['neq', 9]])
            ->where(['bill_type' => ['neq', 11]])
            ->where($map)
            ->order($sort, $order)
            ->group('order_number')
            ->limit($offset, $limit)
            ->select();

        $list = collection($list)->toArray();
        $model = Db::connect('database.db_delivery');
        foreach ($list as $key => $value) {
            //查询成本
            $list_z = $this->finance_cost
                ->where(['order_number' => $value['order_number'], 'action_type' => 1, 'type' => 2])
                ->select();
            $list_j = $this->finance_cost
                ->where(['order_number' => $value['order_number'], 'action_type' => 2, 'type' => 2])
                ->select();
            $list_z_frame = array_sum(array_column($list_z, 'frame_cost'));
            $list_z_lens = array_sum(array_column($list_z, 'lens_cost'));
            $list_j_frame = array_sum(array_column($list_j, 'frame_cost'));
            $list_j_lens = array_sum(array_column($list_j, 'lens_cost'));
            $list[$key]['frame_cost'] = $list_z_frame - $list_j_frame;
            $list[$key]['lens_cost'] = $list_z_lens - $list_j_lens;
            //查询收入
            $list_zs = $this->finance_cost
                ->where(['order_number' => $value['order_number'], 'action_type' => 1, 'type' => 1])
                ->select();
            $list_js = $this->finance_cost
                ->where(['order_number' => $value['order_number'], 'action_type' => 2, 'type' => 1])
                ->select();
            $list_zs_income_amount = array_sum(array_column($list_zs, 'income_amount'));
            $list_js_income_amount = array_sum(array_column($list_js, 'income_amount'));
            $list[$key]['income_amount'] = $list_zs_income_amount - $list_js_income_amount;
            //物流成本
            $list[$key]['fi_actual_payment_fee'] = $model->table('ld_delivery_order_finance')->where(['increment_id' => $value['order_number']])->value('fi_actual_payment_fee');

            $list[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
            $list[$key]['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "订单号")
            ->setCellValue("B1", "站点")
            ->setCellValue("C1", "订单类型")  //利用setCellValues()填充数据
            ->setCellValue("D1", "支付金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("E1", "订单总金额")
            ->setCellValue("F1", "镜架成本")
            ->setCellValue("G1", "镜片成本")
            ->setCellValue("H1", "物流成本");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("I1", "币种")
            ->setCellValue("J1", "支付时间");
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("K1", "创建时间");


        $spreadsheet->setActiveSheetIndex(0)->setTitle('财务订单报表');

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['order_number']);
            if ($value['site'] == 1) {
                $site = 'Zeelool';
            } elseif ($value['site'] == 2) {
                $site = 'Voogueme';
            } elseif ($value['site'] == 3) {
                $site = 'Nihao';
            } elseif ($value['site'] == 4) {
                $site = 'Meeloog';
            } elseif ($value['site'] == 5) {
                $site = 'Wesee';
            } elseif ($value['site'] == 8) {
                $site = 'Amazon';
            } elseif ($value['site'] == 9) {
                $site = 'Zeelool_es';
            } elseif ($value['site'] == 10) {
                $site = 'Zeelool_de';
            } elseif ($value['site'] == 11) {
                $site = 'Zeelool_jp';
            }
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $site);
            if ($value['order_type'] == 1) {
                $order_type = '普通订单';
            } elseif ($value['order_type'] == 2) {
                $order_type = '批发';
            } elseif ($value['order_type'] == 3) {
                $order_type = '网红单';
            } elseif ($value['order_type'] == 4) {
                $order_type = '补发单';
            } elseif ($value['order_type'] == 9) {
                $order_type = 'vip订单';
            }
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $order_type);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['order_money']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['income_amount']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['frame_cost']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['lens_cost']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['fi_actual_payment_fee']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['order_currency_code']);

            if ($value['return_type'] == 1) {
                $type = '仅退款';
            } elseif ($value['return_type'] == 2) {
                $type = '退货退款';
            } else {
                $type = '调换货';
            }
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['payment_time']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['createtime']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(12);

        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);



        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:R' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '财务订单报表' . date("YmdHis", time());;

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

    //批量导出xls
    public function batch_export_xls($ids = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $filter = json_decode(input('filter',''),true);
        if ($ids) {
            $ids = explode(',', $ids);
            $order_number = [];
            foreach ($ids as $key => $value) {
                $order_number[] = $this->finance_cost->where(['id' => $value])->value('order_number');
            }
            $map['a.order_number'] = ['in', $order_number];
        }
        $where = [];
        if(isset($filter['order_number'])) {
            $where['a.order_number'] = $order_number;
        }
        if(isset($filter['site'])) {
            $where['a.site'] = ['in', $filter['site']];
        }
        if(isset($filter['order_type'])) {
            $where['a.order_type'] = $filter['order_type'];
        }
        if(isset($filter['payment_time'])) {
            $payment_time = explode(' - ',$filter['payment_time']);
            $where['a.payment_time'] = ['between', [strtotime($payment_time[0]),strtotime($payment_time[1])]];
        }
        if(isset($filter['createtime'])) {
            $createtime = explode(' - ',$filter['createtime']);
            $where['a.createtime'] = ['between', [strtotime($createtime[0]),strtotime($createtime[1])]];
        }
        $headList = ['订单号', '站点', '订单类型', '支付金额', '订单总金额', '币种', '镜架成本', '镜片成本', '物流成本', '支付时间', '创建时间'];
        $saveName = '/uploads/财务订单报表' . date('YmdHis');
        $allCount = $this->finance_cost->alias('a')->field('sum(if ((action_type=1 and type=2),frame_cost,0)) as frame_cost_z,sum(if ((action_type=1 and type=2),lens_cost,0)) as lens_cost_z,sum(if ((action_type=2 and type=2),frame_cost,0)) as frame_cost_j,sum(if ((action_type=2 and type=2),lens_cost,0)) as lens_cost_j,sum(if ((action_type=1 and type=1),income_amount,0)) as income_amount_zs
,sum(if ((action_type=2 and type=1),lens_cost,0)) as lens_cost_js,a.id,a.order_number,a.site,a.order_type,a.order_money,a.order_currency_code,a.payment_time,a.createtime')
            ->where($where)
            ->where(['a.bill_type' => ['not in', '9,11']])
            ->where($map)
            ->with('DeliveryOrderFinance')
            ->group('a.order_number')
            ->count();
        $page = ceil($allCount / 25000);
        for($i = 0;$i<$page;$i++){
            $list = $this->finance_cost->alias('a')->field('sum(if ((action_type=1 and type=2),frame_cost,0)) as frame_cost_z,sum(if ((action_type=1 and type=2),lens_cost,0)) as lens_cost_z,sum(if ((action_type=2 and type=2),frame_cost,0)) as frame_cost_j,sum(if ((action_type=2 and type=2),lens_cost,0)) as lens_cost_j,sum(if ((action_type=1 and type=1),income_amount,0)) as income_amount_zs
,sum(if ((action_type=2 and type=1),lens_cost,0)) as lens_cost_js,a.id,a.order_number,a.site,a.order_type,a.order_money,a.order_currency_code,a.payment_time,a.createtime')
                ->where($where)
                ->where(['a.bill_type' => ['not in', '9,11']])
                ->where($map)
                ->with('DeliveryOrderFinance')
                ->group('a.order_number')
                ->select();
            $list = collection($list)->toArray();
            $params = [];
            foreach ($list as $key => $value) {
                $params[$key]['order_number'] = $value['order_number'];
                if ($value['site'] == 1) {
                    $site = 'Zeelool';
                } elseif ($value['site'] == 2) {
                    $site = 'Voogueme';
                } elseif ($value['site'] == 3) {
                    $site = 'Nihao';
                } elseif ($value['site'] == 4) {
                    $site = 'Meeloog';
                } elseif ($value['site'] == 5) {
                    $site = 'Wesee';
                } elseif ($value['site'] == 8) {
                    $site = 'Amazon';
                } elseif ($value['site'] == 9) {
                    $site = 'Zeelool_es';
                } elseif ($value['site'] == 10) {
                    $site = 'Zeelool_de';
                } elseif ($value['site'] == 11) {
                    $site = 'Zeelool_jp';
                }
                $params[$key]['site'] = $site;
                if ($value['order_type'] == 1) {
                    $order_type = '普通订单';
                } elseif ($value['order_type'] == 2) {
                    $order_type = '批发';
                } elseif ($value['order_type'] == 3) {
                    $order_type = '网红单';
                } elseif ($value['order_type'] == 4) {
                    $order_type = '补发单';
                } elseif ($value['order_type'] == 9) {
                    $order_type = 'vip订单';
                }
                $params[$key]['order_type'] = $order_type;
                $params[$key]['order_money'] = $value['order_money'];
                //收入
                $list_zs_income_amount = $value['income_amount_zs'];
                $list_js_income_amount = $value['lens_cost_js'];
                $params[$key]['income_amount'] = $list_zs_income_amount - $list_js_income_amount;
                $params[$key]['order_currency_code'] = $value['order_currency_code'];
                $list_z_frame = $value['frame_cost_z'];
                $list_z_lens = $value['lens_cost_z'];
                $list_j_frame = $value['frame_cost_j'];
                $list_j_lens = $value['lens_cost_j'];
                $params[$key]['frame_cost'] = $list_z_frame - $list_j_frame;
                $params[$key]['lens_cost'] = $list_z_lens - $list_j_lens;

                //物流成本
                $params[$key]['fi_actual_payment_fee'] = $value['fi_actual_payment_fee'];
                $params[$key]['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);
                $params[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
            }

            if ($i > 0) {
                $headList = [];
            }
            $i++;
            Excel::writeCsv($params, $headList, $saveName, false);
        }
        //获取当前域名
        $request = Request::instance();
        $domain = $request->domain();
        header('Location: '.$domain.$saveName.'.csv');
        die;
    }
}
