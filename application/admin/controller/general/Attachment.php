<?php

namespace app\admin\controller\general;

use app\common\controller\Backend;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * 附件管理
 *
 * @icon fa fa-circle-o
 * @remark 主要用于管理上传到又拍云的数据或上传至本服务的上传数据
 */
class Attachment extends Backend
{

    /**
     * @var \app\common\model\Attachment
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Attachment');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $mimetypeQuery = [];
            $filter = $this->request->request('filter');
            $filterArr = (array)json_decode($filter, TRUE);
            if (isset($filterArr['mimetype']) && stripos($filterArr['mimetype'], ',') !== false) {
                $this->request->get(['filter' => json_encode(array_merge($filterArr, ['mimetype' => '']))]);
                $mimetypeQuery = function ($query) use ($filterArr) {
                    $mimetypeArr = explode(',', $filterArr['mimetype']);
                    foreach ($mimetypeArr as $index => $item) {
                        $query->whereOr('mimetype', 'like', '%' . $item . '%');
                    }
                };
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($mimetypeQuery)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($mimetypeQuery)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $cdnurl = preg_replace("/\/(\w+)\.php$/i", '', $this->request->root());
            foreach ($list as $k => &$v) {
                $v['fullurl'] = ($v['storage'] == 'local' ? $cdnurl : $this->view->config['upload']['cdnurl']) . $v['url'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 选择附件
     */
    public function select()
    {
        if ($this->request->isAjax()) {
            return $this->index();
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $this->error();
        }
        return $this->view->fetch();
    }

    /**
     * 删除附件
     * @param array $ids
     */
    public function del($ids = "")
    {
        if ($ids) {
            \think\Hook::add('upload_delete', function ($params) {
                $attachmentFile = ROOT_PATH . '/public' . $params['url'];
                if (is_file($attachmentFile)) {
                    @unlink($attachmentFile);
                }
            });
            $attachmentlist = $this->model->where('id', 'in', $ids)->select();
            foreach ($attachmentlist as $attachment) {
                \think\Hook::listen("upload_delete", $attachment);
                $attachment->delete();
            }
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    
    /**
     * 批量导入出库单
     */
    public function import_xls_order()
    {
        set_time_limit(0);
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        //$importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';
        //模板文件列名
        $listName = ['序号', '仓库SKU', 'Zeelool_SKU', 'Zeelool销量', 'Voogueme_SKU', 'Voogueme销量', 'Nihao_SKU', 'Nihao销量', '汇总销量'];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            //模板文件不正确
            if ($listName !== array_filter($fields)) {
                throw new Exception("模板文件不正确！！");
            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null(trim($val)) ? 0 : trim($val);
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        $spreadsheet = new Spreadsheet();
       
        
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "仓库SKU")
            ->setCellValue("B1", "实时库存")
            ->setCellValue("C1", "Zeelool_SKU");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "Zeelool销量")
            ->setCellValue("E1", "Voogueme_SKU");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "Voogueme销量")
            ->setCellValue("G1", "Nihao_SKU");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "Nihao销量")
            ->setCellValue("I1", "汇总销量");
        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0)->setTitle('仓库SKU');

        foreach ($data as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 2 + 2), $value['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 2 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $value['sku']);
           
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(32);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(12);

        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(18);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(14);

        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(16);

       
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

    
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        // $spreadsheet->getActiveSheet()->getStyle('A1:Z'.$key)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:Z' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:Z' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
       
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '订单打印处方' . date("YmdHis", time());;
        // dump($spreadsheet);

        // if (!$spreadsheet) return false;
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
