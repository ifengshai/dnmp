<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;

class SettleOrder extends Backend
{
    public function _initialize()
    {
        $this->statementitem = new \app\admin\model\financepurchase\StatementItem;
        $this->statement = new \app\admin\model\financepurchase\Statement;
        $this->supplier = new \app\admin\model\purchase\Supplier;
        return parent::_initialize();
    }
    /*
    * 结算单列表
    * */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            $map['wait_statement_total'] = ['<', 0];
            $map['status'] = ['in',[4,6]];
            if ($filter['supplier_name']) {
                 //供应商名称
                $supplyId = Db::name('supplier')->where('supplier_name',$filter['supplier_name'])->value('id');
                $map['supplier_id'] = $supplyId ? $supplyId : 0;
            }
            if ($filter['purchase_person']) {
                //采购负责人
                $supplyId = Db::name('supplier')
                    ->where('purchase_person',$filter['purchase_person'])
                    ->column('id');
                $map['supplier_id'] = ['in',$supplyId];
            }
            unset($filter['supplier_name']);
            unset($filter['purchase_person']);
            $this->request->get(['filter' => json_encode($filter)]);

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();

            $total = $this->statement
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->statement
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('id,statement_number,supplier_id,wait_statement_total,account_statement,status,pay_type')
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k=>$v){
                $supply = $this->supplier->where('id',$v['supplier_id'])->field('supplier_name,purchase_person')->find();
                $list[$k]['supplier_name'] = $supply['supplier_name'];
                $list[$k]['purchase_person'] = $supply['purchase_person'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /*
     * 详情
     * */
    public function detail($ids = null)
    {
        $ids = input('ids');
        if (!$ids) {
            $this->error(__('No Results were found'));
        }
        //主表数据
        $statement = $this->statement->where('id',$ids)->find();
        $supply = $this->supplier->where('id',$statement['supplier_id'])->field('supplier_name,recipient_name,opening_bank,bank_account,currency,period')->find();
        $items = $this->statementitem->where('statement_id',$ids)->select();
        $this->view->assign(compact('statement', 'supply', 'items'));
        return $this->view->fetch();
    }
    /*
     * 财务确认
     * */
    public function confirm(){
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $financeStatement = $this->statement->where($map)->select();
        foreach ($financeStatement as $k=>$v){
            if ($v['status'] != 4){
                $this->error($v['statement_number'].'已确认！！请勿重复操作');
            }
        }
        $row = $this->statement->where($map)->update(['status'=>6]);
        if ($row !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }


    /**
     * @param null $ids
     * @throws \Mpdf\MpdfException
     */
    public function settleprint($ids = null)
    {
        //获取付款单信息
        $ids = input('ids');
        if (!$ids) {
            $this->error(__('No Results were found'));
        }
        //主表数据
        $statement = $this->statement->where('id',$ids)->find();
        $supply = $this->supplier->where('id',$statement['supplier_id'])->field('supplier_name,recipient_name,opening_bank,bank_account,currency,period')->find();
        $items = $this->statementitem->where('statement_id',$ids)->select();
        $this->view->assign(compact('statement', 'supply', 'items'));
        /***********end***************/

        //去掉控制台
        $this->view->engine->layout(false);

        $dir = './pdftmp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L'
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->autoLangToFont = true;
        $html =  $this->fetch('settleprint');

        $mpdf->WriteHTML($html);

        $mpdf->Output('pdf.pdf', 'I'); //D是下载
        die;
    }

    public function export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        if ($ids) {
            $map['a.statement_id'] = ['in', $ids];
        }else{

        }
        $items = $this->statementitem
            ->alias('a')
            ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id')
            ->join(['fa_finance_statement' => 'c'], 'a.statement_id=c.id')
            ->where($map)
            ->field('a.*,b.sku,b.purchase_price,c.statement_number')
            ->select();
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "采购单号")
            ->setCellValue("B1", "SKU")
            ->setCellValue("C1", "单价");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "批次数量")
            ->setCellValue("E1", "预付金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "入库数量")
            ->setCellValue("G1", "入库金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "退货数量")
            ->setCellValue("I1", "退货金额")
            ->setCellValue("J1", "结算单号");

        foreach ($items as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['purchase_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['purchase_name']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['purchase_price']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['instock_num'] + $value['return_num']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['before_total']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['instock_num']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['instock_total']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['return_num']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['return_total']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['statement_number']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(40);



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

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '结算单导出数据库' . date("YmdHis", time());;

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
