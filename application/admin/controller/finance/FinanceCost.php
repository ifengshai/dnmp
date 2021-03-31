<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class FinanceCost extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['income', 'cost', 'batch_export_xls'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\finance\FinanceCost();
    }

    /*
     * 成本核算
     * */
    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 收入
     *
     * @Description
     * @author gyh
     * @since 2021/01/21 15:24:14 
     * @return void
     */
    public function income()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type=1')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type=1')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }


    /**
     * 成本
     *
     * @Description
     * @author gyh
     * @since 2021/01/21 15:24:14 
     * @return void
     */
    public function cost()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type=2')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type=2')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch('index');
    }

    //导出数据

    public function batch_export_xls()

    {

        //设置过滤方法

        $ids = input('ids');

        $type = json_decode($this->request->get('type'), true);

        !empty($type) && $where['type'] = $type;

        if (!empty($ids)) {

            $where['id'] = ['in', $ids];

        } else {

            $filter = json_decode($this->request->get('filter'), true);

            !empty($filter['bill_type']) && $where['bill_type'] = ['in', $filter['bill_type']];

            !empty($filter['order_number']) && $where['order_number'] = ['like', '%'.$filter['order_number'].'%'];

            !empty($filter['site']) && $where['site'] = ['in', $filter['site']];

            !empty($filter['order_type']) && $where['order_type'] = $filter['order_type'];

            !empty($filter['order_currency_code']) && $where['order_currency_code'] = $filter['order_currency_code'];

            !empty($filter['action_type']) && $where['action_type'] = $filter['action_type'];

            !empty($filter['is_carry_forward']) && $where['is_carry_forward'] = $filter['is_carry_forward'];

            if ($filter['createtime']) {
                $createtime = explode(' - ', $filter['createtime']);
                $where['createtime'] = ['between', [strtotime($createtime[0]), strtotime($createtime[1])]];
            }

        }

        $list = $this->model
            ->where($where)
            ->order('id desc')
            ->select();
        $list = collection($list)->toArray();

        //站点列表

        $site_list = [

            1 => 'Zeelool',

            2 => 'Voogueme',

            3 => 'Nihao',

            4 => 'Meeloog',

            5 => 'Wesee',

            8 => 'Amazon',

            9 => 'Zeelool_es',

            10 => 'Zeelool_de',

            11 => 'Zeelool_jp',

        ];

        $type_document = [

            1 => '订单收入',

            2 => 'Vip订单',

            3 => '工单补差价',

            4 => '工单退货退款',

            5 => '工单取消',

            6 => '工单部分退款',

            7 => 'Vip退款',

            8 => '订单出库',

            9 => '出库单出库',

            10 => '冲减暂估',

        ];

        $order_type = [

            1 => '普通订单',

            2 => '批发',

            3 => '网红单',

            4 => '补发',

            5 => '补差价',

            9 => 'vip订单',


        ];

        //从数据库查询需要的数据

        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据

        if ($type == 1) {

            $spreadsheet
                ->setActiveSheetIndex(0)->setCellValue("A1", "ID")
                ->setCellValue("B1", "关联单据类型")
                ->setCellValue("C1", "订单号");

            $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "站点")
                ->setCellValue("E1", "订单类型")
                ->setCellValue("F1", "订单金额")
                ->setCellValue("G1", "收入金额")
                ->setCellValue("H1", "币种")
                ->setCellValue("I1", "是否结转")
                ->setCellValue("J1", "增加/冲减")
                ->setCellValue("K1", "订单支付时间")
                ->setCellValue("L1", "支付方式")
                ->setCellValue("M1", "创建时间");

            foreach ($list as $key => $value) {

                if ($value['action_type'] == 1) {

                    $value['action_type'] = '增加';

                } else {

                    $value['action_type'] = '减少';

                }

                if ($value['payment_time']) {

                    $value['payment_time'] = date('Y-m-d H:i:s', $value['payment_time']);

                } else {

                    $value['payment_time'] = '无';

                }

                if ($value['createtime']) {

                    $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);

                }

                if ($value['is_carry_forward'] == 1) {

                    $value['is_carry_forward'] = '是';

                } else {

                    $value['is_carry_forward'] = '否';

                }

                $spreadsheet->getActiveSheet()->setCellValueExplicit("A".($key * 1 + 2), $value['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $spreadsheet->getActiveSheet()->setCellValue("B".($key * 1 + 2), $type_document[$value['bill_type']]);

                $spreadsheet->getActiveSheet()->setCellValue("C".($key * 1 + 2), $value['order_number']);

                $spreadsheet->getActiveSheet()->setCellValue("D".($key * 1 + 2), $site_list[$value['site']]);

                $spreadsheet->getActiveSheet()->setCellValue("E".($key * 1 + 2), $order_type[$value['order_type']]);

                $spreadsheet->getActiveSheet()->setCellValue("F".($key * 1 + 2), $value['order_money']);

                $spreadsheet->getActiveSheet()->setCellValue("G".($key * 1 + 2), $value['income_amount']);

                $spreadsheet->getActiveSheet()->setCellValue("H".($key * 1 + 2), $value['order_currency_code']);

                $spreadsheet->getActiveSheet()->setCellValue("I".($key * 1 + 2), $value['is_carry_forward']);

                $spreadsheet->getActiveSheet()->setCellValue("J".($key * 1 + 2), $value['action_type']);

                $spreadsheet->getActiveSheet()->setCellValue("K".($key * 1 + 2), $value['payment_time']);

                $spreadsheet->getActiveSheet()->setCellValue("L".($key * 1 + 2), $value['payment_method']);

                $spreadsheet->getActiveSheet()->setCellValue("M".($key * 1 + 2), $value['createtime']);

            }

        } else {

            $spreadsheet
                ->setActiveSheetIndex(0)->setCellValue("A1", "ID")
                ->setCellValue("B1", "关联单据类型")
                ->setCellValue("C1", "关联单号");

            $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "镜架成本")
                ->setCellValue("E1", "镜片成本")
                ->setCellValue("F1", "是否结转")
                ->setCellValue("G1", "创建时间")
                ->setCellValue("H1", "币种")
                ->setCellValue("I1", "站点");

            foreach ($list as $key => $value) {

                if ($value['is_carry_forward'] == 1) {

                    $value['is_carry_forward'] = '是';

                } else {

                    $value['is_carry_forward'] = '否';

                }

                if ($value['createtime']) {

                    $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);

                }

                $spreadsheet->getActiveSheet()->setCellValueExplicit("A".($key * 1 + 2), $value['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $spreadsheet->getActiveSheet()->setCellValue("B".($key * 1 + 2), $type_document[$value['bill_type']]);

                $spreadsheet->getActiveSheet()->setCellValue("C".($key * 1 + 2), $value['order_number']);

                $spreadsheet->getActiveSheet()->setCellValue("D".($key * 1 + 2), $value['frame_cost']);

                $spreadsheet->getActiveSheet()->setCellValue("E".($key * 1 + 2), $value['lens_cost']);

                $spreadsheet->getActiveSheet()->setCellValue("F".($key * 1 + 2), $value['is_carry_forward']);

                $spreadsheet->getActiveSheet()->setCellValue("G".($key * 1 + 2), $value['createtime']);

                $spreadsheet->getActiveSheet()->setCellValue("H".($key * 1 + 2), $value['order_currency_code']);

                $spreadsheet->getActiveSheet()->setCellValue("I".($key * 1 + 2), $site_list[$value['type']]);

            }

        }


        //设置宽度

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(40);

        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);

        if ($type == 1) {

            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);

            $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(20);

            $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(20);

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


        $spreadsheet->getActiveSheet()->getStyle('A1:M'.$spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0);


        $format = 'xlsx';

        if ($type == 1) {

            $savename = '订单成本明细-收入'.date("YmdHis", time());

        } else {

            $savename = '订单成本明细-成本'.date("YmdHis", time());

        }

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

}
