<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;


class FinanceOrder extends Backend
{
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
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq',9]])
                ->where(['bill_type' => ['neq',11]])
                ->order($sort, $order)
                ->group('order_number')
                ->count();

            $list = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq',9]])
                ->where(['bill_type' => ['neq',11]])
                ->order($sort, $order)
                ->group('order_number')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                //查询成本
                $list_z = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 1,'type' => 2])
                ->select();
                $list_j = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 2,'type' => 2])
                ->select();
                $list_z_frame = array_sum(array_column($list_z, 'frame_cost'));
                $list_z_lens = array_sum(array_column($list_z, 'lens_cost'));
                $list_j_frame = array_sum(array_column($list_j, 'frame_cost'));
                $list_j_lens = array_sum(array_column($list_j, 'lens_cost'));
                $list[$key]['frame_cost'] = $list_z_frame-$list_j_frame;
                $list[$key]['lens_cost'] = $list_z_lens-$list_j_lens;
                //查询收入
                $list_zs = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 1,'type' => 1])
                ->select();
                $list_js = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 2,'type' => 1])
                ->select();
                $list_zs_income_amount = array_sum(array_column($list_zs, 'income_amount'));
                $list_js_income_amount = array_sum(array_column($list_js, 'income_amount'));
                $list[$key]['income_amount'] = $list_zs_income_amount-$list_js_income_amount;
                //物流成本
                $list[$key]['fi_actual_payment_fee'] = $model->table('ld_delivery_order_finance')->where(['increment_id' => $value['order_number']])->value('fi_actual_payment_fee');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
    }

        //批量导出xls
    public function batch_export_xls()
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
        
        list($where) = $this->buildparams();
        $list = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq',9]])
                ->where(['bill_type' => ['neq',11]])
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
                ->where(['order_number' => $value['order_number'],'action_type' => 1,'type' => 2])
                ->select();
                $list_j = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 2,'type' => 2])
                ->select();
                $list_z_frame = array_sum(array_column($list_z, 'frame_cost'));
                $list_z_lens = array_sum(array_column($list_z, 'lens_cost'));
                $list_j_frame = array_sum(array_column($list_j, 'frame_cost'));
                $list_j_lens = array_sum(array_column($list_j, 'lens_cost'));
                $list[$key]['frame_cost'] = $list_z_frame-$list_j_frame;
                $list[$key]['lens_cost'] = $list_z_lens-$list_j_lens;
                //查询收入
                $list_zs = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 1,'type' => 1])
                ->select();
                $list_js = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 2,'type' => 1])
                ->select();
                $list_zs_income_amount = array_sum(array_column($list_zs, 'income_amount'));
                $list_js_income_amount = array_sum(array_column($list_js, 'income_amount'));
                $list[$key]['income_amount'] = $list_zs_income_amount-$list_js_income_amount;
                //物流成本
                $list[$key]['fi_actual_payment_fee'] = $model->table('ld_delivery_order_finance')->where(['increment_id' => $value['order_number']])->value('fi_actual_payment_fee');
            }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "退销单号")
            ->setCellValue("B1", "采购单号")
            ->setCellValue("C1", "供应商")  //利用setCellValues()填充数据
            ->setCellValue("D1", "SKU");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("E1", "供应商SKU")
            ->setCellValue("F1", "采购数量")
            ->setCellValue("G1", "到货数量")
            ->setCellValue("H1", "合格数量");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("I1", "退销数量")
            ->setCellValue("J1", "退销类型");
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("K1", "退销金额")
            ->setCellValue("L1", "退销备注")
            ->setCellValue("M1", "质检备注")
            ->setCellValue("N1", "联系人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("O1", "联系电话")
            ->setCellValue("P1", "收货地址")
            ->setCellValue("Q1", "创建时间")
            ->setCellValue("R1", "创建人");

        $spreadsheet->setActiveSheetIndex(0)->setTitle('退销单数据');

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['return_number']);
            $spreadsheet->getActiveSheet()->setCellValueExplicit("B" . ($key * 1 + 2), $value['purchaseorder']['purchase_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['supplier']['supplier_name']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['supplier_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['purchase_num']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['arrivals_num']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['quantity_num']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['return_num']);

            if ($value['return_type'] == 1) {
                $type = '仅退款';
            } elseif ($value['return_type'] == 2) {
                $type = '退货退款';
            } else {
                $type = '调换货';
            }
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $type);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['return_money']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['remark']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['check_remark']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['supplier_linkname']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['supplier_linkphone']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['supplier_address']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['createtime']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['create_person']);
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
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);



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
        $savename = '退销单数据' . date("YmdHis", time());;

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
