<?php

namespace app\admin\controller\example;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use fast\Excel;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use think\Cache;

use think\Exception;


/**
 * 表格完整示例
 *
 * @icon fa fa-table
 * @remark 在使用Bootstrap-table中的常用方式,更多使用方式可查看:http://bootstrap-table.wenzhixin.net.cn/zh-cn/
 */
class Bootstraptable extends Backend
{
    protected $model = null;

    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';
    /**
     * 无需鉴权的方法(需登录)
     * @var array
     */
    protected $noNeedRight = ['start', 'pause', 'change', 'detail', 'cxselect', 'searchlist'];
    /**
     * 快捷搜索的字段
     * @var string
     */
    protected $searchFields = 'id,title,url';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('AdminLog');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams(null);
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list, "extend" => ['money' => mt_rand(100000, 999999), 'price' => 200]);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isAjax()) {
            $this->success("Ajax请求成功", null, ['id' => $ids]);
        }
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }

    /**
     * 启用
     */
    public function start($ids = '')
    {
        $this->success("模拟启动成功");
    }

    /**
     * 暂停
     */
    public function pause($ids = '')
    {
        $this->success("模拟暂停成功");
    }

    /**
     * 切换
     */
    public function change($ids = '')
    {
        //你需要在此做具体的操作逻辑

        $this->success("模拟切换成功");
    }

    /**
     * 联动搜索
     */
    public function cxselect()
    {
        $type = $this->request->get('type');
        $group_id = $this->request->get('group_id');
        $list = null;
        if ($group_id !== '') {
            if ($type == 'group') {
                $groupIds = $this->auth->getChildrenGroupIds(true);
                $list = \app\admin\model\AuthGroup::where('id', 'in', $groupIds)->field('id as value, name')->select();
            } else {
                $adminIds = \app\admin\model\AuthGroupAccess::where('group_id', 'in', $group_id)->column('uid');
                $list = \app\admin\model\Admin::where('id', 'in', $adminIds)->field('id as value, username AS name')->select();
            }
        }
        $this->success('', null, $list);
    }

    /**
     * 搜索下拉列表
     */
    public function searchlist()
    {
        $result = $this->model->limit(10)->select();
        $searchlist = [];
        foreach ($result as $key => $value) {
            $searchlist[] = ['id' => $value['url'], 'name' => $value['url']];
        }
        $data = ['searchlist' => $searchlist];
        $this->success('', null, $data);
    }

    /**
     * 批量导入
     */
    public function import()
    {
        ini_set('memory_limit', '1512M');
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

        $data =  Cache::get('data_excel');
        if (!$data) {
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
    
    
                $data = [];
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                        $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                        $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                    }
                }
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
    
        }
        
        // Cache::set('data_excel', $data, 86400);

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach ($data as &$value) {

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $value[0],
                'carrier' => '03011'
            ]]);
            $value[1] = $trackInfo['data']['accepted'][0]['track']['e'];
            usleep(300000);
        }
        unset($value);
        Cache::set('data_excel_001', serialize($data), 86400);
        dump(serialize($data));
        die;
    }

    public function derive()
    {
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "运单号")
            ->setCellValue("B1", "状态");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setTitle('工单数据');

        $data = Cache::get('data_excel_001');
        foreach ($data as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value[0]);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value[1]);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);


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

        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '工单数据' . date("YmdHis", time());;

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
