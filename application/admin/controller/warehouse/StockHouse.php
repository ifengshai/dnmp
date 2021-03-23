<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Loader;

/**
 * 库位管理
 *
 * @icon fa fa-circle-o
 */
class StockHouse extends Backend
{

    /**
     * StockHouse模型对象
     * @var \app\admin\model\warehouse\StockHouse
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['print_label', 'stock_print_label'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\StockHouse;

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 库位列表
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $map = [];
            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['area_coding']) {
                $area_id = Db::name('warehouse_area')->where('coding', $filter['area_coding'])->value('id');
                $all_store_id = Db::name('store_house')->where('area_id', $area_id)->column('id');
                $map['id'] = ['in', $all_store_id];
                unset($filter['area_coding']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where(['type' => 1])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where(['type' => 1])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            //所有库区编码id
            $area_coding = Db::name('warehouse_area')->column('coding', 'id');
            //获得库位所属库区编码
            foreach ($list as $k => $v) {
                $list[$k]['area_coding'] = $area_coding[$v['area_id']];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $type = input('type');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                empty($params['coding']) && $this->error('库位编码不能为空！');
                $warehouse_area = Db::name('warehouse_area')->where('id', $params['area_id'])->find();
                if ($warehouse_area['status'] == 2) {
                    $this->error('当前库区已禁用！');
                }
                //判断选择的库位是否已存在
                if (2 == $type) {
                    $params['location'] = $params['coding'];
                    $params['coding'] = $params['subarea'] . '-' . $params['location'];
                }
                $map['type'] = $type;
                $map['coding'] = $params['coding'];
                $map['area_id'] = $params['area_id'];
                $count = $this->model->where($map)->count();
                $count > 0 && $this->error('当前库区已存在此编码！');

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    // dump($params);die;
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("type", $type);
        //        $arr = [];
        //        $kuweihao = $this->shelf_number1();
        //        foreach ($kuweihao as $k=>$v){
        //            $arr[$v] = $v;
        //        }
        $arr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $this->assign('shelf_number', $arr);
        //所有库区编码id
        $area_coding = Db::name('warehouse_area')->column('coding', 'id');
        $this->assign('area_coding', $area_coding);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $type = input('type');
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $warehouse_area = Db::name('warehouse_area')->where('id', $params['area_id'])->find();
                if ($warehouse_area['status'] == 2) {
                    $this->error('当前库区已禁用！');
                }
                empty($params['coding']) && $this->error('库位编码不能为空！');
                //判断选择的库位是否已存在
                if (2 == $type) {
                    $params['location'] = $params['coding'];
                    $params['coding'] = $params['subarea'] . '-' . $params['location'];
                }
                $map['type'] = $type;
                $map['coding'] = $params['coding'];
                $map['id'] = ['<>', $row->id];
                $map['area_id'] = $params['area_id'];
                $count = $this->model->where($map)->count();
                $count > 0 && $this->error('当前库区已存在此编码！');

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //        $arr = [];
        //        $kuweihao = $this->shelf_number1();
        //        foreach ($kuweihao as $k=>$v){
        //            $arr[$v] = $v;
        //        }
        $arr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $this->assign('shelf_number', $arr);
        $this->view->assign("type", $type);
        $this->view->assign("row", $row);
        //所有库区编码id
        $area_coding = Db::name('warehouse_area')->column('coding', 'id');
        $this->assign('area_coding', $area_coding);
        return $this->view->fetch();
    }

    /**
     * 启用、禁用
     */
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $this->success();
        } else {
            $this->error('修改失败！！');
        }
    }

    /**
     * 合单架库位列表
     */
    public function merge_shelf()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where(['type' => 2])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where(['type' => 2])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 暂存架库位列表
     */
    public function temporary_shelf()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where(['type' => 3])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where(['type' => 3])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 异常架库位列表
     */
    public function abnormal_shelf()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where(['type' => 4])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where(['type' => 4])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 库位打印
     */
    public function stock_print_label($ids = null)
    {

        $stock_house_info = $this->model
            ->where(['id' => ['in', $ids]])
            ->field('status,subarea,coding')
            ->select();
        $stock_house_info = collection($stock_house_info)->toArray();

        $status_arr = array_column($stock_house_info, 'status');
        if (in_array(2, $status_arr)) {
            $this->error('禁用状态无法打印！');
        }
        ob_start();
        $file_header =
            <<<EOF
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ margin:0; padding:0}
.single_box{margin:0 auto;}
table.addpro {clear: both;table-layout: fixed; margin-top:6px; border-top:1px solid #000;border-left:1px solid #000; font-size:12px;}
table.addpro .title {background: none repeat scroll 0 0 #f5f5f5; }
table.addpro .title  td {border-collapse: collapse;color: #000;text-align: center; font-weight:normal; }
table.addpro tbody td {word-break: break-all; text-align: center;border-bottom:1px solid #000;border-right:1px solid #000;}
table.addpro.re tbody td{ position:relative}
</style>
EOF;

        $file_content = '';
        foreach ($stock_house_info as $key => $value) {
            //检测文件夹
            $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "stock_house" . DS . "all";
            !file_exists($dir) && mkdir($dir, 0777, true);

            //生成条形码
            $fileName = $dir . DS . $value['coding'] . ".png";
            $this->generate_barcode($value['coding'], $fileName);

            //拼接条形码
            $img_url = "/uploads/stock_house/all/{$value['coding']}.png";
            $file_content .= "
<div style='display:list-item;margin: 0mm auto;padding-top:4mm;padding-right:2mm;text-align:center;'>
<p>库位条形码</p>
<img src='" . $img_url . "' style='width:36mm'>
</div>";
        }

        echo $file_header . $file_content;
    }


    /**
     * 打印
     */
    public function print_label($ids = null)
    {

        $stock_house_info = $this->model
            ->where(['id' => ['in', $ids]])
            ->field('status,subarea,coding')
            ->select();
        $stock_house_info = collection($stock_house_info)->toArray();

        $status_arr = array_column($stock_house_info, 'status');
        if (in_array(2, $status_arr)) {
            $this->error('禁用状态无法打印！');
        }
        ob_start();
        $file_header =
            <<<EOF
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ margin:0; padding:0}
.single_box{margin:0 auto;}
table.addpro {clear: both;table-layout: fixed; margin-top:6px; border-top:1px solid #000;border-left:1px solid #000; font-size:12px;}
table.addpro .title {background: none repeat scroll 0 0 #f5f5f5; }
table.addpro .title  td {border-collapse: collapse;color: #000;text-align: center; font-weight:normal; }
table.addpro tbody td {word-break: break-all; text-align: center;border-bottom:1px solid #000;border-right:1px solid #000;}
table.addpro.re tbody td{ position:relative}
</style>
EOF;


        foreach ($stock_house_info as $key => $value) {
            //检测文件夹
            $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "stock_house" . DS . "merge_shelf";
            !file_exists($dir) && mkdir($dir, 0777, true);

            //生成条形码
            $fileName = $dir . DS . $value['coding'] . ".png";
            $this->generate_barcode($value['coding'], $fileName);

            //拼接条形码
            $img_url = "/uploads/stock_house/merge_shelf/{$value['coding']}.png";
            $file_content .= "
<div style='display:list-item;margin: 0mm auto;padding-top:4mm;padding-right:2mm;text-align:center;'>
<p>合单架库位条形码</p>
<img src='" . $img_url . "' style='width:36mm'>
</div>";
        }

        echo $file_header . $file_content;
    }

    /**
     * 生成条形码
     */
    protected function generate_barcode($text, $fileName)
    {
        // $text = '1007000000030';
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile', EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing', EXTEND_PATH);
        // 条形码的编码格式
        // Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        Loader::import('BCode.BCGcode128', EXTEND_PATH, '.barcode.php');

        // $code = '';
        // 加载字体大小
        $font = new \BCGFontFile(EXTEND_PATH . '/BCode/font/Arial.ttf', 20);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(2);
            $code->setThickness(60); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont($font); //设置字体
            // $code->setOffsetX(10); //设置字体
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
     * 导入库位数据
     */
    public function import1()
    {
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
        //        $listName = ['折射率', '镜片类型', 'SPH', 'CYL', '库存数量', '镜片价格'];
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
            //            if ($listName !== $fields) {
            //                throw new Exception("模板文件不正确！！");
            //            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$data) {
            $this->error('未导入任何数据！！');
        }
        dump($data);
        die;
        //检测库存编码是否有重复
        $list = array_column($data, '0');
        if (count($list) != count(array_unique($list))) {
            $this->error('库存编码有重复！！请仔细核对库存编码');
        }

        //批量添加产品
        foreach ($data as $k => $v) {
            //检测库存编码是否已入库
            $findDetection = $this->model->where('coding', $v[0])->find();
            if ($findDetection) {
                $result = $this->model->save(['coding' => $v[0], 'library_name' => $v[1], 'remark' => $v[2], 'create_person' => $this->auth->username, 'createtime' => date('y-m-d h:i:s', time())], ['id' => $findDetection['id']]);
            } else {
                $result = $this->model->insert(['coding' => $v[0], 'library_name' => $v[1], 'remark' => $v[2], 'createtime' => date('y-m-d h:i:s', time()), 'create_person' => $this->auth->username]);
            }
        }
        if ($result) {
            $this->success('导入成功！！');
        } else {
            $this->error('导入失败！！');
        }
    }

    /**
     * 库位列表批量导入
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/23
     * Time: 13:49:49
     */
    public function import()
    {
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
        $listName = ['货架号', '库区编码', '库位编码', '库容', '库位名称', '备注','拣货顺序'];
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
            // 模板文件不正确
            if ($listName !== $fields) {
                throw new Exception("模板文件不正确！！");
            }
            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$data) {
            $this->error('未导入任何数据！！');
        }
        //检测库存编码是否有重复
        $list = array_column($data, '2');
        if (count($list) != count(array_unique($list))) {
            $this->error('库位编码有重复！！请仔细核对库位编码');
        }
        foreach ($data as $k => $v) {
            if (empty($v[2]) || empty($v[1])){
                $this->error('库位编码不能为空，请检查！！');
            }
            $area_id = Db::name('warehouse_area')->where('coding', $v[1])->value('id');
            if (empty($area_id)){
                $this->error('库区编码错误，请检查！！');
            }
            $is_exist_coding = $this->model->where('coding',$v[2])->where('area_id',$area_id)->find();
            if (!empty($is_exist_coding)){
                $this->error('当前库区已存在此库位编码，请检查！！');
            }
        }
        foreach ($data as $k => $v) {
            $area_id = Db::name('warehouse_area')->where('coding', $v[1])->value('id');
            $result = $this->model->insert(['coding' => $v[2], 'library_name' => $v[4], 'remark' => $v[5], 'createtime' => date('y-m-d h:i:s', time()), 'create_person' => $this->auth->username, 'shelf_number' => $v[0], 'area_id' => $area_id, 'volume' => $v[3], 'picking_sort' => $v[6]]);
        }
        if ($result) {
            $this->success('导入成功！！');
        } else {
            $this->error('导入失败！！');
        }
    }


    function getRepeat($arr)
    {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique($arr);
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc($arr, $unique_arr);
        return $repeat_arr;
    }

    //跑老的库位编码添加货架号字段
    public function shelf_number()
    {
        $shelf_number = $this->model->where('status', 1)->where('type', 1)->field('id,coding')->select();
        $shelf_number = collection($shelf_number)->toArray();
        foreach ($shelf_number as $k => $v) {
            $shelf_number[$k]['shelf_number'] = preg_replace("/\\d+/", '', (explode('-', $v['coding']))[0]);
            unset($shelf_number[$k]['coding']);
        }
        $this->model->isUpdate()->saveAll($shelf_number);
    }

    public function shelf_number1()
    {
        $data = (new \app\admin\model\warehouse\StockHouse())->get_shelf_number();
        return $data;
    }

}
