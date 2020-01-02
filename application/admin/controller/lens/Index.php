<?php

namespace app\admin\controller\lens;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * 镜片管理管理
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['add', 'edit', 'lens_edit', 'import', 'import_xls_order'];


    /**
     * Index模型对象
     * @var \app\admin\model\lens\Index
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\lens\Index;
        $this->outorder = new \app\admin\model\lens\LensOutorder;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    //查询是否已存在记录
                    $map['refractive_index'] = $params['refractive_index'];
                    $map['lens_type'] = $params['refractive_index'] . ' ' . $params['lens_type'];
                    $map['sph'] = $params['sph'];
                    $map['cyl'] = $params['cyl'];
                    $count =  $this->model->where($map)->count();
                    if ($count > 0) {
                        $this->error('已存在此记录！！');
                    }
                    $params['lens_type'] = $params['refractive_index'] . ' ' . $params['lens_type'];
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
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
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 镜片库存
     */
    public function lens()
    {
        $data['CYL'] = config('CYL');

        $refractive_index = input('refractive_index', '1.57');
        $lens_type = input('lens_type', 'Mid-Index');

        if ($refractive_index) {
            $map['refractive_index'] = $refractive_index;
            $this->assign('refractive_index', $refractive_index);
        }

        if ($lens_type) {
            $map['lens_type'] = $refractive_index . ' ' . $lens_type;
            $this->assign('lens_type', $lens_type);
        }

        //显示类型 1是库存  2是价格
        $type = input('type', 1);
        if ($type == 2) {
            $res = $this->model->field('id,price as data,cyl,sph,stock_num as value')->where($map)->select();
        } else {
            $res = $this->model->field('id,stock_num as data,cyl,sph,price as value')->where($map)->select();
        }

        $res = collection($res)->toArray();

        $list = [];
        foreach ($res as  $v) {
            $list[$v['sph']][$v['cyl']] = $v;
        }

        unset($res);
        //度数范围
        $label = input('label', 1);
        if ($label == 1) {
            $data['SPH'] = config('SPH');
            $data['FSPH'] = config('FSPH');
        } elseif ($label == 2) {
            $data['SPH'] = config('SPH_1');
            $data['FSPH'] = config('FSPH_1');
        } else {
            $data['SPH'] = config('SPH_all');
            $data['FSPH'] = config('FSPH_all');
        }

        $this->assign('data', $data);
        $this->assign('label', $label);
        $this->assign('list', $list);
        $this->assign('type', $type);

        return $this->fetch();
    }

    /**
     * 修改镜片数据
     */
    public function lens_edit()
    {
        if ($this->request->isAjax()) {
            $id = input('id');
            $data['stock_num'] = input('stock_num');
            $data['price'] = input('price');
            if (!$id) {
                $data['sph'] = input('sph');
                $data['cyl'] = input('cyl');
                $data['refractive_index'] = input('refractive_index');
                $data['lens_type'] = input('refractive_index') . ' ' . input('lens_type');
                $data['createtime'] = date('Y-m-d H:i:s', time());
                $data['create_person'] = session('admin.nickname');
                $res = $this->model->save($data);
            } else {
                $res = $this->model->save($data, ['id' => $id]);
            }
            if ($res) {
                return json(['code' => 1, 'msg' => '修改成功！！']);
            } else {
                return json(['code' => 1, 'msg' => '修改失败！！']);
            }
        }
    }

    /**
     * 镜片出库单
     */
    public function lens_out_order()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->outorder
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->outorder
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
     * 导入镜片库存
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
        $listName = ['折射率', '镜片类型', 'SPH', 'CYL', '库存数量', '镜片价格'];
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
        //批量添加产品
        foreach ($data as $k => $v) {
            $map['refractive_index'] = trim($v[0]);
            $map['lens_type'] = trim($v[1]);
            $sph = $v[2] * 1;
            if ($sph >= 0) {
                $sph = '+' . number_format($sph, 2);
            } else {
                $sph = number_format($sph, 2);
            }

            $cyl = $v[3] * 1;

            if ($cyl > 0 || $cyl < -4) {
                $this->error('数据异常！！CYL不能大于0并且小于-4！！');
            }
            if ($cyl < 0) {
                $cyl = number_format($cyl, 2);
            } else {
                $cyl = '+' . number_format($cyl, 2);
            }

            $map['sph'] = $sph;
            $map['cyl'] = $cyl;
            $res = $this->model->where($map)->find();

            if ($res) {
                $params[$k]['id'] = $res->id;
                $params[$k]['stock_num'] = $v[4] * 1;
                $params[$k]['price'] = $v[5];
            } else {
                $params[$k]['refractive_index'] = trim($v[0]);
                $params[$k]['lens_type'] = trim($v[1]);
                $params[$k]['sph'] = $sph;
                $params[$k]['cyl'] = $cyl;
                $params[$k]['stock_num'] = $v[4];
                $params[$k]['price'] = $v[5];
                $params[$k]['createtime'] = date('Y-m-d H:i:s', time());
                $params[$k]['create_person'] = session('admin.nickname');
            }
        }

        $result = $this->model->allowField(true)->saveAll($params);
        if ($result) {
            $this->success('导入成功！！');
        } else {
            $this->error('导入失败！！');
        }
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
        $listName = ['日期', '订单号', 'SKU', '眼球', 'SPH', 'CYL', 'AXI', 'ADD', '镜片', '处方类型'];
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

        /*********************镜片出库计算逻辑***********************/
        /**
         * 镜片扣减逻辑
         * SHP,CYL都是“-” 直接带入扣减库存
         * 若SPH为+，CYL为- 直接带入扣减库存，若SPH为“-”CYL为“+”则 sph=SPH+CYL,cyl变正负号，用新得到的sph,cyl扣减库存
         * 若带有ADD，sph=SPH+ADD,用新sph带入上面正负号判断里判断
         */
        //补充第二列订单号
        foreach ($data as $k => $v) {
            if (!$v[1]) {
                $data[$k][0] = $data[$k - 1][0];
                $data[$k][1] = $data[$k - 1][1];
                $data[$k][2] = $data[$k - 1][2];
                if (!$v[7]) {
                    $data[$k][7] = $data[$k - 1][7];
                }
                $data[$k][8] = $data[$k - 1][8];
                $data[$k][9] = $data[$k - 1][9];
            }
        }

        foreach ($data as $k => $v) {
            $lens_type = trim($v[8]);
            //如果ADD为真  sph = sph + ADD;
            $sph = $v[4];
            $cyl = $v[5];
            if ($sph) {
                $sph = $sph * 1;
                if ($v[7]) {
                    $sph = $sph + $v[7] * 1;
                }
                
                //如果cyl 为+;则sph = sph + cyl;cyl 正号变为负号
                if ($cyl && $cyl * 1 > 0) {
                    $sph = $sph + $cyl * 1;
                    $cyl = '-' . number_format($cyl * 1, 2);
                } else {
                    if ($cyl) {
                        $cyl = number_format($cyl * 1, 2);
                    } 
                }
                
                if ($sph > 0) {
                    $sph = '+' . number_format($sph, 2);
                } else {
                    $sph = number_format($sph, 2);
                }
            }

            if (!$cyl || $cyl * 1 == 0) {
                $cyl = '+0.00';
            }
            if (!$sph || $sph * 1 == 0) {
                $sph = '+0.00';
            }

            if ($lens_type) {

                //扣减库存
                $map['sph'] = trim($sph);
                $map['cyl'] = trim($cyl);
                $map['lens_type'] = ['like', '%' . $lens_type . '%'];
                $res = $this->model->where($map)->setDec('stock_num');

                //生成出库单
                if ($res) {
                    $params[$k]['num'] = 1;
                } else {
                    $params[$k]['num'] = 0;
                }
                //查询镜片单价
                $price = $this->model->where($map)->value('price');
                $params[$k]['lens_type'] = $lens_type;
                $params[$k]['sph'] = trim($sph);
                $params[$k]['cyl'] = trim($cyl);
                $params[$k]['createtime'] = date('Y-m-d H:i:s', time());
                $params[$k]['create_person'] = session('admin.nickname');
                $params[$k]['price'] = $price * $params[$k]['num'];
                $params[$k]['order_number'] = $v[1];
                $params[$k]['sku'] = trim($v[2]);
                $params[$k]['eye_type'] = $v[3];
                $params[$k]['order_sph'] = trim($v[4]);
                $params[$k]['order_cyl'] = trim($v[5]);
                $params[$k]['order_date'] = $v[0];
                $params[$k]['axi'] = $v[6];
                $params[$k]['add'] = trim($v[7]);
                $params[$k]['order_lens_type'] = $v[8];
                $params[$k]['prescription_type'] = $v[9];
            }
        }

        $this->outorder->saveAll($params);
        /*********************end***********************/
        $this->success('导入成功！！');
    }
}
