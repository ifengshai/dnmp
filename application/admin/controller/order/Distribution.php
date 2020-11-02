<?php

namespace app\admin\controller\order;

use app\admin\model\DistributionLog;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\exception\PDOException;
use think\Exception;
use think\Loader;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * 配货列表
 */
class Distribution extends Backend
{
    protected $noNeedRight = ['orderDetail', 'batch_print_label_new', 'batch_export_xls', 'account_order_batch_export_xls'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\order\NewOrderItemProcess();
    }

    /**
     * 列表
     */
    public function index()
    {
        $label = $this->request->get('label', 0);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $map = [];
            //普通状态剔除跟单数据
            if (!in_array($label, [0, 8])) {
                if (7 == $label) {
                    $map['a.distribution_status'] = [['>', 6], ['<', 9]];
                } else {
                    $map['a.distribution_status'] = $label;
                }
                $map['a.temporary_house_id|a.abnormal_house_id|b.store_house_id'] = 0;
            }

            $_stock_house = new \app\admin\model\warehouse\StockHouse;
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['abnormal'] || $filter['stock_house_num']) {
                //筛选异常
                if ($filter['abnormal']) {
                    $_distribution_abnormal = new \app\admin\model\DistributionAbnormal();
                    $abnormal_where['type'] = $filter['abnormal'];
                    if (8 == $label) {
                        $abnormal_where['status'] = 1;
                    }
                    $item_process_id = $_distribution_abnormal
                        ->where($abnormal_where)
                        ->column('item_process_id');
                    $map['a.id'] = ['in', $item_process_id];
                }

                //筛选库位号
                if ($filter['stock_house_num']) {
                    $stock_house_where['coding'] = ['like', $filter['stock_house_num'] . '%'];
                    if (8 == $label) {
                        $stock_house_where['type'] = ['>', 2];
                    } else {
                        $stock_house_where['type'] = 2;
                    }
                    $stock_house_id = $_stock_house
                        ->where($stock_house_where)
                        ->column('id');
                    $map['a.temporary_house_id|a.abnormal_house_id|b.store_house_id'] = ['in', $stock_house_id];
                }
                unset($filter['abnormal']);
                unset($filter['stock_house_num']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            //TODO::是否有工单
            $list = $this->model
                ->alias('a')
                ->field('a.id,a.item_order_number,a.sku,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,order_type,a.created_at,b.store_house_id,b.order_prescription_type')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $stock_house_data = $_stock_house
                ->where(['status' => 1, 'type' => ['>', 1], 'occupy' => ['>', 0]])
                ->column('coding', 'id');

            foreach ($list as $key => $value) {
                if (!empty($value['temporary_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['temporary_house_id']]['coding'];
                } elseif (!empty($value['temporary_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['abnormal_house_id']]['coding'];
                } elseif (!empty($value['store_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['store_house_id']]['coding'];
                }
                $list[$key]['stock_house_num'] = $stock_house_num ?? '-';
                $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);

        $label_list = ['全部', '待打印标签', '待配货', '待配镜片', '待加工', '待印logo', '待成品质检', '待合单', '跟单'];
        $this->assign('label_list', $label_list);

        return $this->view->fetch();
    }

    /**
     * 批量导出xls
     *
     * @Description
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 0);

        $ids = input('ids');
        $map = [];
        if ($ids) {
            $map['id'] = ['in', $ids];
            $where = [];
            $sort = 'a.created_at';
            $order = 'desc';
        } else {
            //普通状态剔除跟单数据
            if (!in_array($label, [0, 8])) {
                if (7 == $label) {
                    $map['a.status'] = [['>', 6], ['<', 9]];
                } else {
                    $map['a.status'] = $label;
                }
                $map['a.temporary_house_id|a.abnormal_house_id|b.store_house_id'] = 0;
            }

            $_stock_house = new \app\admin\model\warehouse\StockHouse;
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['abnormal'] || $filter['stock_house_num']) {
                //筛选异常
                if ($filter['abnormal']) {
                    $_distribution_abnormal = new \app\admin\model\DistributionAbnormal();
                    $abnormal_where['type'] = $filter['abnormal'];
                    if (8 == $label) {
                        $abnormal_where['status'] = 1;
                    }
                    $item_process_id = $_distribution_abnormal
                        ->where($abnormal_where)
                        ->column('item_process_id');
                    $map['a.id'] = ['in', $item_process_id];
                }

                //筛选库位号
                if ($filter['stock_house_num']) {
                    $stock_house_where['coding'] = ['like', $filter['stock_house_num'] . '%'];
                    if (8 == $label) {
                        $stock_house_where['type'] = ['>', 2];
                    } else {
                        $stock_house_where['type'] = 2;
                    }
                    $stock_house_id = $_stock_house
                        ->where($stock_house_where)
                        ->column('id');
                    $map['a.temporary_house_id|a.abnormal_house_id|b.store_house_id'] = ['in', $stock_house_id];
                }
                unset($filter['abnormal']);
                unset($filter['stock_house_num']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order) = $this->buildparams();
        }

        $list = $this->model
            ->alias('a')
            ->field('a.id,a.item_order_number,a.sku,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,order_type,a.created_at,b.store_house_id,b.order_prescription_type')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->where($where)
            ->where($map)
            ->order($sort, $order)
            ->select();
        $list = collection($list)->toArray();

        $_stock_house = new \app\admin\model\warehouse\StockHouse;
        $stock_house_data = $_stock_house
            ->where(['status' => 1, 'type' => ['>', 1], 'occupy' => ['>', 0]])
            ->column('coding', 'id');

        foreach ($list as $key => $value) {
            if (!empty($value['temporary_house_id'])) {
                $stock_house_num = $stock_house_data[$value['temporary_house_id']]['coding'];
            } elseif (!empty($value['temporary_house_id'])) {
                $stock_house_num = $stock_house_data[$value['abnormal_house_id']]['coding'];
            } elseif (!empty($value['store_house_id'])) {
                $stock_house_num = $stock_house_data[$value['store_house_id']]['coding'];
            }
            $list[$key]['stock_house_num'] = $stock_house_num ?? '-';
            $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
        }

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "订单号")
            ->setCellValue("B1", "子单号")
            ->setCellValue("C1", "SKU")
            ->setCellValue("D1", "订单副数")
//            ->setCellValue("E1", "工单")
            ->setCellValue("F1", "站点")
            ->setCellValue("G1", "加工类型")
            ->setCellValue("H1", "订单类型")
            ->setCellValue("I1", "订单状态")
            ->setCellValue("J1", "子单号状态")
            ->setCellValue("K1", "库位号")
            ->setCellValue("L1", "创建时间");

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
            11 => 'Zeelool_jp'
        ];

        //加工类型
        $prescription_type_list = [
            1 => '仅镜架',
            2 => '现货处方镜',
            3 => '定制处方镜',
            4 => '镜架+现货',
            5 => '镜架+定制',
            6 => '现片+定制片'
        ];

        //订单类型
        $order_type_list = [
            1 => '普通订单',
            2 => '批发单',
            3 => '网红单',
            4 => '补发单',
            5 => '补差价',
            6 => '一件代发'
        ];

        //子单号状态
        $distribution_status_list = [
            1 => '待打印标签',
            2 => '待配货',
            3 => '待配镜片',
            4 => '待加工',
            5 => '待印logo',
            6 => '待成品质检',
            7 => '待合单',
            8 => '合单中',
            9 => '合单完成'
        ];

        foreach ($list as $key => $value) {
            $line = $key + 2;
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A{$line}", $value['increment_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B{$line}", $value['item_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("C{$line}", $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("D{$line}", $value['total_qty_ordered']);
//            $spreadsheet->getActiveSheet()->setCellValue("E{$line}", $value['base_grand_total']);
            $spreadsheet->getActiveSheet()->setCellValue("F{$line}", $site_list[$value['site']]);
            $spreadsheet->getActiveSheet()->setCellValue("G{$line}", $prescription_type_list[$value['order_prescription_type']]);
            $spreadsheet->getActiveSheet()->setCellValue("H{$line}", $order_type_list[$value['order_type']]);
            $spreadsheet->getActiveSheet()->setCellValue("I{$line}", $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("J{$line}", $distribution_status_list[$value['distribution_status']]);
            $spreadsheet->getActiveSheet()->setCellValue("K{$line}", $value['stock_house_num']);
            $spreadsheet->getActiveSheet()->setCellValue("L{$line}", $value['created_at']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
//        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);

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
        $spreadsheet->getActiveSheet()->getStyle('A1:I' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $save_name = '配货列表' . date("YmdHis", time());
        //输出07Excel版本
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //输出名称
        header('Content-Disposition: attachment;filename="' . $save_name . '.xlsx"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * 批量打印
     *
     * @Description
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
     */
    public function batch_print_label()
    {
        ob_start();
        $ids = input('ids');
        if ($ids) {
            //检测配货状态
            $where = [
                'id' => ['in', $ids],
                'distribution_status' => ['neq', 1]
            ];
            $count = $this->model
                ->where($where)
                ->count();
            if ($count > 0) {
                return $this->error('存在非当前节点的子订单', url('index?ref=addtabs'));
            }

            $file_header = <<<EOF
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ margin:0; padding:0}
.single_box{margin:0 auto;width: 400px;padding:1mm;margin-bottom:2mm;}
table.addpro {clear: both;table-layout: fixed; margin-top:6px; border-top:1px solid #000;border-left:1px solid #000; font-size:12px;}
table.addpro .title {background: none repeat scroll 0 0 #f5f5f5; }
table.addpro .title  td {border-collapse: collapse;color: #000;text-align: center; font-weight:normal; }
table.addpro tbody td {word-break: break-all; text-align: center;border-bottom:1px solid #000;border-right:1px solid #000;}
table.addpro.re tbody td{ position:relative}
</style>
EOF;

            $list = $this->model
                ->field('item_order_number')
                ->where(['id' => ['in', $ids]])
                ->select();

            $file_content = '';
            foreach ($list as $value) {
                $item_order_number = $value['item_order_number'];
                $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new" . DS . "$item_order_number.png";
                $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new";
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                $img_url = "/uploads/printOrder/distribution/new/$item_order_number.png";
                //生成条形码
                $this->generate_barcode_new($item_order_number, $fileName);
                $file_content .= "<div  class = 'single_box'>
                <table width='420mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;padding:0px;'>
                <tr>
                <td rowspan='5' colspan='3' style='padding:10px;'><img src='" . $img_url . "' height='80%'><br></td></tr>                
                </table></div>";
            }
            echo $file_header . $file_content;
        }
    }

    /**
     * 生成新的条形码
     */
    protected function generate_barcode_new($text, $fileName)
    {
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile', EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing', EXTEND_PATH);
        // 条形码的编码格式
        // Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        Loader::import('BCode.BCGcode128', EXTEND_PATH, '.barcode.php');

        // $code = '';
        // 加载字体大小
        $font = new \BCGFontFile(EXTEND_PATH . '/BCode/font/Arial.ttf', 18);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $label = new \BCGLabel();
        $label->setPosition(\BCGLabel::POSITION_TOP);
        $label->setText('Made In China');
        $label->setFont($font);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(4);
            $code->setThickness(18); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont($font); //设置字体
            $code->addLabel($label); //设置字体
            $code->parse($text); // 条形码需要的数据内容
        } catch (\Exception $exception) {
            $drawException = $exception;
        }
        //根据以上条件绘制条形码
        $drawing = new \BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            if ($fileName) {
                // echo 'setFilename<br>';
                $drawing->setFilename($fileName);
            }
            $drawing->draw();
        }
        // 生成PNG格式的图片
        header('Content-Type: image/png');
        // header('Content-Disposition:attachment; filename="barcode.png"'); //自动下载
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
    }

    /**
     * 批量标记已打印
     *
     * @Description
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
     */
    public function tag_printed()
    {
        $ids = input('id_params/a');
        if ($ids) {
            //检测配货状态
            $where = [
                'id' => ['in', $ids],
                'distribution_status' => ['neq', 1]
            ];
            $count = $this->model
                ->where($where)
                ->count();
            if ($count > 0) {
                return $this->error('存在非当前节点的子订单', url('index?ref=addtabs'));
            }

            Db::startTrans();
            try {
                //标记状态
                $this->model
                    ->allowField(true)
                    ->isUpdate(true, ['id'=>['in', $ids]])
                    ->save(['distribution_status'=>2])
                ;

                //记录配货日志
                $admin = (object)session('admin');
                DistributionLog::record($admin,$ids,'标记打印完成');

                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
    }

}
