<?php

namespace app\admin\controller\purchase;

use app\admin\model\NewProductDesign;
use app\admin\model\purchase\SampleWorkorder;
use app\admin\model\purchase\SampleWorkorderItem;
use app\admin\model\warehouse\Outstock;
use app\admin\model\warehouse\OutStockItem;
use app\common\controller\Backend;
use Aws\S3\S3Client;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Sample extends Backend
{

    /**
     * Sample模型对象
     * @var \app\admin\model\purchase\Sample
     */
    protected $model = null;
    protected $noNeedRight = ['sample_import_xls_copy'];

    public function _initialize()
    {
        parent::_initialize();
        $this->sample = new \app\admin\model\purchase\Sample;
        $this->samplelocation = new \app\admin\model\purchase\SampleLocation;
        $this->sampleworkorder = new \app\admin\model\purchase\SampleWorkorder;
        $this->samplelendlog = new \app\admin\model\purchase\SampleLendlog;
        $this->item = new \app\admin\model\itemmanage\Item;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 样品间列表
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:04:06
     * @author mjj
     */
    public function sample_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['location']) {
                $smap['location'] = ['like', '%' . $filter['location'] . '%'];
                $ids = Db::name('purchase_sample_location')->where($smap)->column('id');
                $map['location_id'] = ['in', $ids];
                unset($filter['location']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            $where_arr['is_del'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->sample
                ->where($where)
                ->where($where_arr)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->sample
                ->where($where)
                ->where($where_arr)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['location_id'] = $this->samplelocation->getLocationName($value['location_id']);
                $list[$key]['is_lend'] = $value['is_lend'] == 1 ? '是' : '否';
                $list[$key]['product_name'] = $this->item->where('sku', $value['sku'])->value('name');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 导入样品入库单
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:08:22
     * @author mjj
     */
    public function sample_import_xls_copy()
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
        $listName = ['SKU', '数量'];
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
            if ($allRow > 3500) {
                throw new Exception("表格行数过大");
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


        /*********************样品入库逻辑***********************/
        $sku = array();
        $no_location = array();
        $result_index = 0;
        //入库单号
        $location_number = 'IN2' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $workorder['location_number'] = $location_number;
        $workorder['status'] = 1;
        $workorder['create_user'] = session('admin.nickname');
        $workorder['createtime'] = date('Y-m-d H:i:s', time());
        $workorder['type'] = 1;

        $location_id = Db::name('purchase_sample_workorder')->insertGetId($workorder);
        foreach ($data as $k => $v) {
            $sku[$k]['parent_id'] = $location_id;
            $sku[$k]['sku'] = $v[0];
            $sku[$k]['stock'] = $v[1];

        }
        $result_index = Db::table('fa_purchase_sample_workorder_item')->insertAll($sku);
        if (!$result_index) {
            $this->error('导入失败！！');
        } else {
            $this->success('导入成功');
        }


    }


    /**
     * sku和商品的绑定关系
     *
     * @Description
     * @return void
     * @since 2020/05/23 14:59:04
     * @author mjj
     */
    public function sample_add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                //判断sku是否重复
                $sku_arr = $this->sample->where('is_del', 1)->column('sku');
                if (in_array($params['sku'], $sku_arr)) {
                    $this->error(__('sku不能重复'));
                }
                Db::startTrans();
                try {
                    $sample['sku'] = $params['sku'];
                    $sample['location_id'] = $params['location_id'];
                    $result = $this->sample->insert($sample);
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
        //获取库位数据
        $location_data = $this->samplelocation->getPurchaseLocationData();
        $this->assign('location_data', $location_data);

        return $this->view->fetch();
    }

    /**
     * sku，库位绑定修改
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/06/05 13:53:33
     */
    public function sample_edit($ids = null)
    {
        $row = $this->sample->get($ids);
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
                Db::startTrans();
                try {
                    //是否采用模型验证
                    $result = $this->sample->where('id', $ids)->update(['location_id' => $params['location_id']]);
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
        //获取库位数据
        $location_data = $this->samplelocation->getPurchaseLocationData();
        $this->assign('location_data', $location_data);

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * sku，库位绑定删除
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/06/05 14:05:10
     */
    public function sample_del($ids = null)
    {
        $result = $this->sample->where('id', $ids)->update(['is_del' => 2]);
        if ($result) {
            $this->success();
        } else {
            $this->error(__('删除失败'));
        }
    }

    /**
     * 库位批量导入
     *
     * @Description
     * @return void
     * @since 2020/06/05 13:52:57
     * @author mjj
     */
    public function sample_import_xls()
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
        $listName = ['SKU', '库位号'];
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
            if ($allRow > 3500) {
                throw new Exception("表格行数过大");
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

        /*********************样品入库逻辑***********************/
        $sku = array();
        $no_location = array();
        $result_index = 0;
        foreach ($data as $k => $v) {
            //查询样品间是否存在该商品
            $sample = $this->sample->where('sku', $v[0])->value('id');
            if ($sample) {
                $location_id = $this->samplelocation->where('location', trim($v[1]))->value('id');
                if ($location_id) {
                    $result = $this->sample->where('sku', $v[0])->update(['location_id' => $location_id]);
                    if ($result) {
                        $result_index = 1;
                    }
                } else {
                    $no_location[] = $v[0];
                }
            } else {
                $sku['sku'] = $v[0];
                $location_id = $this->samplelocation->where('location', trim($v[1]))->value('id');
                if ($location_id) {
                    $sku['location_id'] = $location_id;
                    $result = $this->sample->insert($sku);
                    if ($result) {
                        $result_index = 1;
                    }
                } else {
                    $no_location[] = $v[0];
                }
            }
        }
        if (count($no_location) != 0) {
            $str = ' SKU:' . implode(',', $no_location) . '这些sku库位号有误';
        }
        if ($result_index == 1) {
            $this->success('导入成功！！' . $str);
        } else {
            $this->error('导入失败！！' . $str);
        }
        /*********************end***********************/
    }

    /**
     * 库位列表
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:03:40
     * @author mjj
     */
    public function sample_location_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where_arr['is_del'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->samplelocation
                ->where($where)
                ->where($where_arr)
                ->order($sort, $order)
                ->count();

            $list = $this->samplelocation
                ->where($where)
                ->where($where_arr)
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
     * 库位增加
     *
     * @Description
     * @return void
     * @since 2020/05/23 14:59:04
     * @author mjj
     */
    public function sample_location_add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                //判断库位号是否重复
                $location_repeat = Db::name('purchase_sample_location')
                    ->where(['location' => $params['location'], 'is_del' => 1])
                    ->find();
                if ($location_repeat) {
                    $this->error(__('库位号不能重复'));
                }
                $params['createtime'] = date('Y-m-d H:i:s', time());
                $params['create_user'] = session('admin.nickname');
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->samplelocation));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->samplelocation->validateFailException(true)->validate($validate);
                    }
                    $result = $this->samplelocation->allowField(true)->save($params);
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
     * 库位编辑
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/05/23 15:05:29
     */
    public function sample_location_edit($ids = null)
    {
        $row = $this->samplelocation->get($ids);
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
                //判断库位号是否重复
                $location_repeat = Db::name('purchase_sample_location')
                    ->where(['location' => $params['location'], 'is_del' => 1])
                    ->find();
                if ($location_repeat) {
                    $this->error(__('库位号不能重复'));
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->samplelocation));
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
     * 库位删除
     *
     * @Description
     * @param string $ids
     * @return void
     * @author mjj
     * @since 2020/05/23 15:05:41
     */
    public function sample_location_del($ids = "")
    {
        if (!$ids) {
            $this->error(__('无效参数'));
        }
        $this->samplelocation->where('id', $ids)->update(['is_del' => 2]);
        $this->success();
    }

    /**
     * 入库列表
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:08:11
     * @author mjj
     */
    public function sample_workorder_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                $ids = Db::name('purchase_sample_workorder_item')->where($smap)->column('parent_id');
                $map['id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            $where_arr['type'] = 1;
            $where_arr['is_del'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->sampleworkorder
                ->where($where)
                ->where($where_arr)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->sampleworkorder
                ->where($where)
                ->where($where_arr)
                ->where($map)
                ->order('id desc')
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['status_id'] = $value['status'];
                if ($value['status'] == 1) {
                    $list[$key]['status'] = '新建';
                } elseif ($value['status'] == 2) {
                    $list[$key]['status'] = '待审核';
                } elseif ($value['status'] == 3) {
                    $list[$key]['status'] = '已审核';
                } elseif ($value['status'] == 4) {
                    $list[$key]['status'] = '已拒绝';
                } elseif ($value['status'] == 5) {
                    $list[$key]['status'] = '已取消';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 入库添加
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:08:22
     * @author mjj
     */
    public function sample_workorder_add()
    {
        $location_number = 'IN2' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if (!$params['goods']) {
                    $this->error(__('提交信息不能为空', ''));
                }
                //判断数据中是否有空值
                $sku_arr = array_column($params['goods'], 'sku');
                $stock_arr = array_column($params['goods'], 'stock');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) {
                    $this->error(__('sku不能重复', ''));
                }
                if (in_array('', $sku_arr)) {
                    $this->error(__('商品信息不能为空', ''));
                }
                if (in_array('', $stock_arr)) {
                    $this->error(__('库存不能为空', ''));
                }
                //生成入库主表数据
                $workorder['location_number'] = $location_number;
                $workorder['status'] = $params['status'];
                $workorder['create_user'] = session('admin.nickname');
                $workorder['createtime'] = date('Y-m-d H:i:s', time());
                $workorder['type'] = 1;
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder);
                $parent_id = $this->sampleworkorder->id;
                foreach ($params['goods'] as $value) {
                    $workorder_item['parent_id'] = $parent_id;
                    $workorder_item['sku'] = $value['sku'];
                    $workorder_item['stock'] = $value['stock'];
                    Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取样品间数据
        $sample_list = $this->sample->where('is_del', 1)->select();
        $sample_list = collection($sample_list)->toArray();
        foreach ($sample_list as $k => $v) {
            $sample_list[$k]['location'] = $this->samplelocation->where('id', $v['location_id'])->value('location');
        }
        $this->assign('sample_list', $sample_list);

        $this->assign('location_number', $location_number);

        return $this->view->fetch();
    }

    /**
     * 入库编辑
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/05/23 15:08:32
     */
    public function sample_workorder_edit($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                if (!$params['goods']) {
                    $this->error(__('提交信息不能为空', ''));
                }
                //判断数据中是否有空值
                $sku_arr = array_column($params['goods'], 'sku');
                $stock_arr = array_column($params['goods'], 'stock');
                $warehouseHouse = array_column($params['goods'], 'warehouse');
                if (in_array('', $sku_arr)) {
                    $this->error(__('商品信息不能为空', ''));
                }
                if (in_array('', $stock_arr)) {
                    $this->error(__('库存不能为空', ''));
                }
                if (in_array('', $warehouseHouse)) {
                    $this->error(__('库位号不能为空', ''));
                }
                //获取该入库单下的商品sku，并将不在该列表的数据进行删除
                $save_sku_arr = Db('purchase_sample_workorder_item')->where(['parent_id' => $ids])->column('sku');
                $diff_sku_arr = array_diff($save_sku_arr, $sku_arr);
                Db('purchase_sample_workorder_item')->where('sku', 'in', $diff_sku_arr)->where('parent_id', $ids)->delete();
                //处理商品
                foreach ($params['goods'] as $value) {
                    //判断入库表中是否有该商品
                    $is_exist = Db('purchase_sample_workorder_item')->where(['sku' => $value['sku'], 'parent_id' => $ids])->value('id');
                    $workorder_item = array();
                    if ($is_exist) {
                        //更新
                        $workorder_item['stock'] = $value['stock'];
                        Db::name('purchase_sample_workorder_item')->where(['sku' => $value['sku'], 'parent_id' => $ids])->update($workorder_item);
                    } else {
                        //插入
                        $workorder_item['parent_id'] = $ids;
                        $workorder_item['sku'] = $value['sku'];
                        $workorder_item['stock'] = $value['stock'];
                        Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                    }
                }
                //更新备注
                $workorder['description'] = $params['description'];
                $workorder['status'] = $params['status'];
                $this->sampleworkorder->save($workorder, ['id' => input('ids')]);
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        //获取样品间数据
        $sample_list = $this->sample->where('is_del', 1)->select();
        $sample_list = collection($sample_list)->toArray();
        foreach ($sample_list as $k => $v) {
            $sample_list[$k]['location'] = $this->samplelocation->where('id', $v['location_id'])->value('location');
        }
        $this->assign('sample_list', $sample_list);
        //获取入库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id', $ids)->order('id asc')->select();
        foreach ($product_list as $key => $value) {
            $product_list[$key]['location'] = $this->sample->getlocation($value['sku']);
        }
        $this->assign('product_list', $product_list);
        return $this->view->fetch();
    }

    /**
     * 入库详情
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/05/23 15:38:27
     */
    public function sample_workorder_detail($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        $this->view->assign("row", $row);

        //获取入库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id', $ids)->order('id asc')->select();
        foreach ($product_list as $key => $value) {
            $product_list[$key]['location'] = $this->sample->getlocation($value['sku']);
        }
        $this->assign('product_list', $product_list);

        return $this->view->fetch();
    }

    /**
     * 入库批量审核
     *
     * @Description
     * @return void
     * @since 2020/05/23 17:26:57
     * @author mjj
     */
    public function sample_workorder_setstatus($ids = null)
    {
        $ids = $this->request->post("ids/a");
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $is_update = 0;
        $where['id'] = ['in', $ids];
        $row = $this->sampleworkorder->where($where)->select();
        foreach ($row as $v) {
            if ($status == 3 || $status == 4) {
                if ($v['status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                    $is_update = 0;
                    break;
                } else {
                    $is_update = 1;
                }
            }
            if ($status == 5) {
                if ($v['status'] != 1) {
                    $this->error('只有新建状态才能操作！！');
                    $is_update = 0;
                    break;
                } else {
                    $is_update = 1;
                }
            }
        }
        $workorder_item = Db::name('purchase_sample_workorder_item')->where('parent_id', 'in', $ids)->select();
        $location_error_sku = array();
        foreach ($workorder_item as $val) {
            $location = $this->sample->getlocation($val['sku']);
            if (!$location) {
                $location_error_sku[] = $val['sku'];
            }
        }
        if (count($location_error_sku) != 0) {
            $this->error('SKU:' . implode(',', array_unique($location_error_sku)) . '库位号不存在，无法审核！！');
        }
        $newProductDesign = new NewProductDesign();
        $this->sampleworkorder->startTrans();
        $newProductDesign->startTrans();
        $this->sample->startTrans();
        $this->samplelendlog->startTrans();
        try {
            if ($is_update == 1) {
                $this->sampleworkorder->where($where)->update(['status' => $status]);
                if ($status == 3) {
                    //审核通过后将商品信息添加到样品间列表
                    foreach ($ids as $id) {
                        $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id', $id)->order('id asc')->select();
                        foreach ($product_arr as $item) {
                            $is_exist = $this->sample->where('sku', $item['sku'])->value('id');
                            if ($is_exist) {
                                $this->sample->where('sku', $item['sku'])->inc('stock', $item['stock'])->inc('lend_num', 1)->update(['is_lend' => 1]);
                                //自动生成一条样品借出记录
                                $lendlog['status'] = 2;
                                $lendlog['create_user'] = session('admin.nickname');
                                $lendlog['createtime'] = date('Y-m-d H:i:s', time());
                                $lendlog['sku'] = $item['sku'];
                                $lendlog['lend_num'] = 1;
                                $this->samplelendlog->insert($lendlog);
                                $newProductDesign->insert(['sku'=>$item['sku'],'status'=>1,'create_time'=>date('Y-m-d H:i:s', time()),'update_time'=>date('Y-m-d H:i:s', time())]);
                            } else {
                                $sample['sku'] = $item['sku'];
                                $sample['location_id'] = $this->sample->getlocation($item['sku']);
                                $sample['stock'] = $item['stock'];
                                $sample['is_lend'] = 1;//是否借出：是
                                $sample['lend_num'] = 1;//借出数量1
                                $this->sample->insert($sample);
                                //自动生成一条样品借出记录
                                $lendlog['status'] = 2;
                                $lendlog['create_user'] = session('admin.nickname');
                                $lendlog['createtime'] = date('Y-m-d H:i:s', time());
                                $lendlog['sku'] = $item['sku'];
                                $lendlog['lend_num'] = 1;
                                $this->samplelendlog->insert($lendlog);
                                $newProductDesign->insert(['sku'=>$item['sku'],'status'=>1,'create_time'=>date('Y-m-d H:i:s', time()),'update_time'=>date('Y-m-d H:i:s', time())]);
                            }
                        }
                    }
                }
            }
            $this->sampleworkorder->commit();
            $newProductDesign->commit();
            $this->sample->commit();
            $this->samplelendlog->commit();
        } catch (ValidateException $e) {
            $this->sampleworkorder->rollback();
            $newProductDesign->rollback();
            $this->sample->rollback();
            $this->samplelendlog->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->sampleworkorder->rollback();
            $newProductDesign->rollback();
            $this->sample->rollback();
            $this->samplelendlog->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->sampleworkorder->rollback();
            $newProductDesign->rollback();
            $this->sample->rollback();
            $this->samplelendlog->rollback();
            $this->error($e->getMessage(), [], 408);
        }
        $this->success();
    }


    /**
     * 出库列表
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:08:11
     * @author mjj
     */
    public function sample_workorder_out_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where_arr['type'] = 2;
            $where_arr['is_del'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->sampleworkorder
                ->alias('a')
                ->join(['fa_purchase_sample_workorder_item' => 'b'], 'a.id = b.parent_id', 'LEFT')
                ->where($where)
                ->where($where_arr)
                ->group('a.id')
                ->order('a.' . $sort, $order)
                ->count();

            $list = $this->sampleworkorder
                ->alias('a')
                ->field('a.id,a.location_number,a.status,a.create_user,a.createtime')
                ->join(['fa_purchase_sample_workorder_item' => 'b'], 'a.id = b.parent_id', 'LEFT')
                ->where($where)
                ->where($where_arr)
                ->group('a.id')
                ->order('a.' . $sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['status_id'] = $value['status'];
                if ($value['status'] == 1) {
                    $list[$key]['status'] = '新建';
                } elseif ($value['status'] == 2) {
                    $list[$key]['status'] = '待审核';
                } elseif ($value['status'] == 3) {
                    $list[$key]['status'] = '已审核';
                } elseif ($value['status'] == 4) {
                    $list[$key]['status'] = '已拒绝';
                } elseif ($value['status'] == 5) {
                    $list[$key]['status'] = '已取消';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 出库添加
     *
     * @Description
     * @return void
     * @since 2020/05/23 15:08:22
     * @author mjj
     */
    public function sample_workorder_out_add()
    {
        $location_number = 'OUT2' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if (!$params['goods']) {
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'], 'sku');
                $stock_arr = array_column($params['goods'], 'stock');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) {
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if (in_array('', $sku_arr)) {
                    $this->error(__('商品信息不能为空', ''));
                }
                if (in_array('', $stock_arr)) {
                    $this->error(__('出库数量不能为空', ''));
                }
                //生成出库主表数据
                $workorder['location_number'] = $location_number;
                $workorder['status'] = $params['status'];
                $workorder['create_user'] = session('admin.nickname');
                $workorder['createtime'] = date('Y-m-d H:i:s', time());
                $workorder['type'] = 2;
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder);
                $parent_id = $this->sampleworkorder->id;
                foreach ($params['goods'] as $key => $value) {
                    $workorder_item['parent_id'] = $parent_id;
                    $workorder_item['sku'] = $value['sku'];
                    $workorder_item['stock'] = $value['stock'];
                    Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        $this->assign('location_number', $location_number);

        return $this->view->fetch();
    }

    /**
     * 出库编辑
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/05/23 15:08:32
     */
    public function sample_workorder_out_edit($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                if (!$params['goods']) {
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'], 'sku');
                $stock_arr = array_column($params['goods'], 'stock');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) {
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if (in_array('', $sku_arr)) {
                    $this->error(__('商品信息不能为空', ''));
                }
                if (in_array('', $stock_arr)) {
                    $this->error(__('出库数量不能为空', ''));
                }
                //获取该入库单下的商品sku，并将不在该列表的数据进行删除
                $save_sku_arr = Db('purchase_sample_workorder_item')->where(['parent_id' => $ids])->column('sku');
                $diff_sku_arr = array_diff($save_sku_arr, $sku_arr);
                Db('purchase_sample_workorder_item')->where('sku', 'in', $diff_sku_arr)->where('parent_id', $ids)->delete();
                //处理商品
                foreach ($params['goods'] as $key => $value) {
                    $is_exist = Db::name('purchase_sample_workorder_item')->where(['sku' => $value['sku'], 'parent_id' => $ids])->value('id');
                    if ($is_exist) {
                        //更新
                        Db::name('purchase_sample_workorder_item')->where(['sku' => $value['sku'], 'parent_id' => $ids])->update(['stock' => $value['stock']]);
                    } else {
                        //插入
                        $workorder_item = array();
                        $workorder_item['parent_id'] = $ids;
                        $workorder_item['sku'] = $value['sku'];
                        $workorder_item['stock'] = $value['stock'];
                        Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                    }
                }
                $workorder['description'] = $params['description'];
                $workorder['status'] = $params['status'];
                $this->sampleworkorder->save($workorder, ['id' => input('ids')]);

                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        //获取出库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id', $ids)->order('id asc')->select();
        foreach ($product_list as $key => $value) {
            $product_list[$key]['location'] = $this->sample->getlocation($value['sku']);
        }
        $this->assign('product_list', $product_list);


        return $this->view->fetch();
    }

    /**
     * 出库详情/审核
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/05/23 15:38:27
     */
    public function sample_workorder_out_detail($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        $this->view->assign("row", $row);

        //获取出库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id', $ids)->order('id asc')->select();
        foreach ($product_list as $key => $value) {
            $product_list[$key]['location'] = $this->sample->getlocation($value['sku']);
        }
        $this->assign('product_list', $product_list);

        return $this->view->fetch();
    }

    /**
     * 出库批量审核
     *
     * @Description
     * @return void
     * @since 2020/05/23 17:26:57
     * @author mjj
     */
    public function sample_workorder_out_setstatus($ids = null)
    {
        $ids = $this->request->post("ids/a");
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $is_update = 0;
        $where['id'] = ['in', $ids];
        $row = $this->sampleworkorder->where($where)->select();
        foreach ($row as $v) {
            if ($status == 3 || $status == 4) {
                if ($v['status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                    $is_update = 0;
                    break;
                } else {
                    $is_update = 1;
                }
            }
            if ($status == 5) {
                if ($v['status'] != 1) {
                    $this->error('只有新建状态才能操作！！');
                    $is_update = 0;
                    break;
                } else {
                    $is_update = 1;
                }
            }
        }
        if ($is_update == 1) {
            if ($status == 3) {
                $is_check = 0;
                $check_arr = array();
                //审核通过后将商品信息添加到样品间列表
                foreach ($ids as $id) {
                    $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id', $id)->order('id asc')->select();
                    foreach ($product_arr as $item) {
                        $sample = $this->sample->where('sku', $item['sku'])->find();
                        $rest_stock = $sample['stock'] - $sample['lend_num'];
                        if ($rest_stock >= $item['stock']) {
                            $check_arr[] = array(
                                'sku' => $item['sku'],
                                'stock' => $item['stock'],
                            );
                        } else {
                            $is_check++;
                            break;
                        }
                    }
                }
                if ($is_check == 0) {
                    //审核通过
                    if (count($check_arr) > 0) {
                        foreach ($check_arr as $value) {
                            $this->sample->where('sku', $value['sku'])->dec('stock', $value['stock'])->update();
                        }
                        $this->sampleworkorder->where($where)->update(['status' => $status]);
                    }
                } else {
                    $this->error(__('样品间商品不足', ''));
                }
            } else {
                $this->sampleworkorder->where($where)->update(['status' => $status]);
            }
            $this->success();
        }
    }

    /**
     * 借出记录列表
     *
     * @Description
     * @return void
     * @since 2020/05/25 09:49:12
     * @author mjj
     */
    public function sample_lendlog_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->samplelendlog
                ->where($where)
                ->count();
            $list = $this->samplelendlog
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['status_id'] = $value['status'];
                if ($value['status'] == 1) {
                    $list[$key]['status'] = '待审核';
                } elseif ($value['status'] == 2) {
                    $list[$key]['status'] = '已借出';
                } elseif ($value['status'] == 3) {
                    $list[$key]['status'] = '已拒绝';
                } elseif ($value['status'] == 4) {
                    $list[$key]['status'] = '已归还';
                } elseif ($value['status'] == 5) {
                    $list[$key]['status'] = '已取消';
                }
                $location_id = $this->sample->where('sku', $value['sku'])->value('location_id');
                $list[$key]['location'] = $this->samplelocation->where('id', $location_id)->value('location');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 借出记录申请
     *
     * @Description
     * @return void
     * @since 2020/05/25 17:02:44
     * @author mjj
     */
    public function sample_lendlog_add($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if (!$params['goods']) {
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'], 'sku');
                $lend_num_arr = array_column($params['goods'], 'lend_num');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) {
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if (in_array('', $sku_arr)) {
                    $this->error(__('商品信息不能为空', ''));
                }
                if (in_array('', $lend_num_arr)) {
                    $this->error(__('借出数量不能为空', ''));
                }
                // dump($params);
                // exit;
                //判断库存是否足够
                $info = $this->check_stock_enough($params['goods'], $sku_arr);
                if ($info && (1 != $info)) {
                    $this->error("sku{$info}借出库存不足,请重新尝试");
                } elseif (1 == $info) {
                    $this->error("无法找到相关库存不足,请重新尝试");
                }
                //生成入库数据
                foreach ($params['goods'] as $value) {
                    $lendlog['status'] = 1;
                    $lendlog['create_user'] = session('admin.nickname');
                    $lendlog['createtime'] = date('Y-m-d H:i:s', time());
                    $lendlog['sku'] = $value['sku'];
                    $lendlog['lend_num'] = $value['lend_num'];
                    $this->samplelendlog->insert($lendlog);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        } else {
            if (!empty($ids)) {
                $idArr = explode(',', $ids);
                $list = $this->sample->where('id', 'in', $idArr)->select();
                $list = collection($list)->toArray();
                foreach ($list as $key => $value) {
                    $list[$key]['location_id'] = $this->samplelocation->getLocationName($value['location_id']);
                    $list[$key]['is_lend'] = $value['is_lend'] == 1 ? '是' : '否';
                    $list[$key]['product_name'] = $this->item->where('sku', $value['sku'])->value('name');
                }
                $this->assign('info', $list);
            }
        }

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        return $this->view->fetch();
    }

    /**
     * 借出记录编辑
     *
     * @Description
     * @param [type] $ids
     * @return void
     * @author mjj
     * @since 2020/05/25 17:23:40
     */
    public function sample_lendlog_edit($ids = null)
    {
        $row = $this->samplelendlog->get($ids);
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
                //判断数据中是否有空值
                if (!$params['sku']) {
                    $this->error(__('sku不能为空', ''));
                }
                if (!$params['lend_num']) {
                    $this->error(__('借出数量不能为空', ''));
                }
                //更新
                Db::name('purchase_sample_lendlog')->where(['id' => $ids])->update(['lend_num' => $params['lend_num'], 'sku' => $params['sku']]);

                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        //获取样品借出商品信息
        $location_id = $this->sample->where('sku', $row->sku)->value('location_id');
        $row->location = $this->samplelocation->where('id', $location_id)->value('location');
        $this->assign('row', $row);

        return $this->view->fetch();
    }

    /**
     * 借出记录批量审核
     *
     * @Description
     * @return void
     * @since 2020/05/26 11:52:38
     * @author mjj
     */
    public function sample_lendlog_setstatus($ids = null)
    {
        $ids = $this->request->post("ids/a");
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $is_update = 0;
        $where['id'] = ['in', $ids];
        $row = $this->samplelendlog->where($where)->select();
        foreach ($row as $v) {
            if ($status == 2 || $status == 3) {
                if ($v['status'] > 1) {
                    $this->error('只有待审核状态才能操作！！');
                    $is_update = 0;
                    break;
                } else {
                    $is_update = 1;
                }
            }
        }
        if ($is_update == 1) {
            if ($status == 2) {
                $skus = Db::name('purchase_sample_lendlog')->field('sum(lend_num) lend_num,sku')->where('id', 'in', $ids)->group('sku')->select();
                //批量审核通过
                $is_check = array();
                $lend_arr = array();
                foreach ($skus as $sku) {
                    $sample = $this->sample->where('sku', $sku['sku'])->find();
                    $rest_stock = $sample->stock - $sample->lend_num;
                    if ($rest_stock >= $sku['lend_num']) {
                        //借出商品并更新状态
                        $lend_arr[] = array(
                            'sku' => $sku['sku'],
                            'lend_num' => $sku['lend_num']
                        );
                    } else {
                        //借出单中存在商品数量不足，无法借出
                        $is_check[] = $sku['sku'];
                        break;
                    }
                }
                if (count($is_check) == 0) {
                    //审核通过
                    foreach ($lend_arr as $value) {
                        $this->sample->where('sku', $value['sku'])->inc('lend_num', $value['lend_num'])->update();
                        $this->sample->where('sku', $value['sku'])->update(['is_lend' => 1]);
                    }
                    $this->samplelendlog->where($where)->update(['status' => $status]);
                } else {
                    $sku_str = implode(',', $is_check);
                    $this->error('sku：' . $sku_str . '商品数量不足，无法借出');
                }
            } else {
                //批量审核拒绝
                $this->samplelendlog->where($where)->update(['status' => $status]);
            }
            $this->success();
        }
    }

    /**
     * 借出记录归还
     *
     * @Description
     * @return void
     * @since 2020/05/26 10:25:09
     * @author mjj
     */
    public function sample_lendlog_check($ids = null)
    {
        if ($this->request->isAjax()) {
            $params = input();
            if (!$params['ids']) {
                $this->error('缺少参数！！');
            }
            $where['id'] = $params['ids'];
            //判断是否是本人归还，如果是本人，才允许归还
            //$admin_user = $this->samplelendlog->where($where)->value('create_user');
            //if($admin_user == session('admin.nickname')){
            //归还
            $lendlog = Db::name('purchase_sample_lendlog')->where('id', $ids)->find();
            $this->sample->where('sku', $lendlog['sku'])->dec('lend_num', $lendlog['lend_num'])->update();
            //判断是否没有借出数量，如果没有修改样品间列表的状态
            $already_lend_num = $this->sample->where('sku', $lendlog['sku'])->value('lend_num');
            if ($already_lend_num == 0) {
                $this->sample->where('sku', $lendlog['sku'])->update(['is_lend' => 0]);
            }
            $this->samplelendlog->where($where)->update(['status' => $params['status']]);
            $this->success();
            // }else{
            //     $this->error('只有本人才能归还');
            // }
        }
    }

    /**
     * 判断库存是否足够
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-07-22 14:08:00
     * @return void
     */
    public function check_stock_enough($goods, $sku_arr)
    {
        //求出所有的sku的留样库存和借出的数量
        $info = $this->sample->where('sku', 'in', $sku_arr)->field('sku,stock-lend_num as new_stock')->select();
        if (!$info) {
            return 1;
        }
        $info = collection($info)->toArray();
        //return $info;
        //组装传入的sku信息
        $arr = [];
        foreach ($goods as $v) {
            $arr[$v['sku']] = $v['lend_num'];
        }
        $not_enough = 0;
        foreach ($info as $vv) {
            if (array_key_exists($vv['sku'], $arr)) {
                if ($arr[$vv['sku']] > $vv['new_stock']) {
                    $not_enough = 1;
                    return $vv['sku'];
                }
            }
        }
        return $not_enough ? true : false;
    }


    /**
     * @author zjw
     * @date   2021/4/27 10:08
     * 样品间商品批量出库
     */
    public function batchDelivery(){
        $Sample = new \app\admin\model\purchase\Sample();
        $purchaseSampleWorkOrder = new SampleWorkorder();
        $purchaseSampleWorkOrderItem = new SampleWorkorderItem();

        $data = array('FT0064-01',
            'FX0067-01',
            'FT0069-02',
            'FX0070-01',
            'FT0071-01',
            'FX0080-01',
            'FT0078-01',
            'FX0093-01',
            'FT0103-01',
            'FT0117-01',
            'FX0146-01',
            'HP0147-01',
            'HP0147-02',
            'FP0150-02',
            'FX0151-02',
            'FT0152-01',
            'FT0155-02',
            'FT0158-01',
            'FT0158-02',
            'FX0172-01',
            'FP0175-01',
            'FP0175-02',
            'FX0159-02',
            'FX0156-01',
            'FX0154-01',
            'FP0174-02',
            'FM0161',
            'FA0166-01',
            'FT0182-01',
            'FP0187-01',
            'FP0187-02',
            'FX0201-02',
            'FP0204-01',
            'FT0215-01',
            'FX0214-02',
            'FP0216-02',
            'FX0217-01',
            'FX0217-02',
            'FA0218-02',
            'FX0221-02',
            'FX0219-01',
            'FA0222-01',
            'FX0236-01',
            'FX0228-01',
            'FX0228-02',
            'FM0224-01',
            'FM0224-02',
            'FX0235-02',
            'FP0240-01',
            'FP0242-02',
            'FA0234-02',
            'FX0241-01',
            'FP0247-01',
            'FX0248-01',
            'FA0251-02',
            'FT0253-01',
            'FX0245-02',
            'FX0258-01',
            'FX0258-02',
            'FX0255-01',
            'FX0255-02',
            'TI0263-02',
            'FA0264-01',
            'HM0267-01',
            'FX0269-01',
            'FA0272-01',
            'FA0272-03',
            'FM0271-01',
            'FX0273-01',
            'FX0268-02',
            'FX0248-02',
            'FX0274-01',
            'FX0274-02',
            'FX0281-02',
            'FP0284-01',
            'FP0294-01',
            'FP0287-01',
            'FA0297-01',
            'FA0297-03',
            'TI0286-01',
            'FA0293-01',
            'FM0254-02',
            'FP0312-01',
            'FA0293-02',
            'FP0312-02',
            'FP0313-01',
            'RM0282-01',
            'FP0313-02',
            'FP0299-01',
            'FT0316-01',
            'TI0288-01',
            'FX0306-02',
            'FM0317-01',
            'FM0317-02',
            'FX0324-02',
            'FP0303-01',
            'FM0337-01',
            'FM0305-01',
            'FM0305-02',
            'TI0307-01',
            'FP0308-01',
            'FM0309-01',
            'FP0310-01',
            'TI0314-01',
            'FX0319-03',
            'FX0315-01',
            'FT0320-01',
            'FM0311-01',
            'FX0322-02',
            'FX0323-01',
            'FX0322-01',
            'FP0343-02',
            'FP0343-01',
            'FX0333-02',
            'FP0338-02',
            'FP0358-02',
            'FA0363-01',
            'FM0349-01',
            'FM0349-02',
            'FX0329-01',
            'FA0360-02',
            'FP0340-01',
            'FM0365-01',
            'FM0365-02',
            'FA0366-01',
            'FA0366-02',
            'FP0347-01',
            'FX0367-01',
            'FP0347-02',
            'FA0369-01',
            'FP0345-02',
            'FA0369-02',
            'FP0348-02',
            'FA0370-01',
            'FM0350-01',
            'FX0371-01',
            'FP0373-01',
            'FP0373-02',
            'FA0372-01',
            'FX0374-01',
            'FM0352-02',
            'FM0354-01',
            'FM0354-02',
            'FM0375-01',
            'FM0376-01',
            'FT0377-01',
            'FT0377-02',
            'FA0355-01',
            'FA0355-02',
            'FT0357-01',
            'FX0379-01',
            'FX0379-02',
            'FP0383-02',
            'FX0381-02',
            'FA0385-01',
            'FA0386-01',
            'FX0399-01',
            'FX0387-01',
            'FA0388-01',
            'FA0388-02',
            'FA0397-01',
            'FA0397-04',
            'FM0389-01',
            'FX0398-01',
            'FT0400-01',
            'FX0401-01',
            'FX0411-01',
            'FT0403-01',
            'FP0412-01',
            'FP0405-01',
            'FA0414-02',
            'FA0415-02',
            'FP0408-01',
            'FX0416-02',
            'FP0409-01',
            'FA0410-01',
            'FP0417-01',
            'FP0421-01',
            'FP0421-02',
            'FP0422-01',
            'FP0422-02',
            'FX0423-01',
            'FM0425-01',
            'FX0426-01',
            'FX0426-02',
            'FT0420-01',
            'FP0412-02',
            'FT0429-01',
            'FT0429-02',
            'FM0428-01',
            'FP0440-02',
            'FM0430-01',
            'FP0442-01',
            'FM0436-01',
            'FM0444-01',
            'FM0444-02',
            'FP0443-01',
            'FP0443-02',
            'FM0438-01',
            'FP0446-01',
            'FX0448-01',
            'FP0450-02',
            'FP0450-01',
            'FX0454-01',
            'FA0452-01',
            'FX0454-02',
            'FX0455-02',
            'FP0456-02',
            'FP0458-01',
            'FP0458-02',
            'FX0461-01',
            'FX0461-02',
            'FP0464-01',
            'FP0463-01',
            'FP0463-02',
            'FP0462-01',
            'FP0466-01',
            'FP0459-01',
            'FP0459-02',
            'FP0466-02',
            'FX0476-01',
            'FX0476-02',
            'FX0480-02',
            'FP0467-01',
            'FP0467-02',
            'FP0469-01',
            'FP0475-01',
            'FP0465-01',
            'FP0465-02',
            'FP0468-01',
            'FP0470-02',
            'FP0474-01',
            'FA0484-01',
            'FX0477-01',
            'FX0477-02',
            'FX0473-01',
            'FX0478-01',
            'FX0478-02',
            'TI0490-01',
            'FX0447-02',
            'FX0447-03',
            'FA0485-01',
            'FA0485-02',
            'TI0495-01',
            'FA0486-01',
            'TI0497-01',
            'TI0487-01',
            'TI0498-01',
            'TI0487-02',
            'TI0500-01',
            'FA0488-01',
            'TI0502-01',
            'TI0502-02',
            'TI0503-02',
            'TI0504-01',
            'TI0504-02',
            'TI0492-01',
            'FA0508-01',
            'TI0493-01',
            'TI0494-01',
            'FA0505-01',
            'FA0505-02',
            'FA0506-01',
            'FA0506-03',
            'FP0507-01',
            'FP0507-02',
            'FA0512-01',
            'FT0526-01',
            'FA0513-01',
            'FA0516-01',
            'FA0514-01',
            'FA0521-01',
            'FA0517-01',
            'FA0517-02',
            'FA0522-01',
            'FA0518-01',
            'FA0518-02',
            'FA0523-01',
            'FA0519-02',
            'FT0527-01',
            'FT0527-02',
            'RM0510-01',
            'FA0520-01',
            'FA0520-02',
            'FA0524-01',
            'FA0525-01',
            'FA0525-02',
            'TI0534-01',
            'TI0534-02',
            'FA0535-01',
            'FA0535-02',
            'FT0529-02',
            'FT0543-01',
            'FX0537-01',
            'FT0543-02',
            'FP0538-01',
            'FX0540-01',
            'FA0532-01',
            'FA0539-01',
            'FA0539-02',
            'FA0539-03',
            'HP0531-02',
            'FX0541-01',
            'FX0541-02',
            'FX0542-01',
            'FX0544-01',
            'FX0548-01',
            'FM0549-03',
            'FM0549-02',
            'FM0554-01',
            'FM0554-02',
            'FP0555-01',
            'FP0555-02',
            'FP0556-01',
            'FP0556-02',
            'FX0553-01',
            'FX0553-02',
            'FX0551-01',
            'HP0561-02',
            'FM0562-01',
            'FM0562-02',
            'FM0562-03',
            'FP0557-02',
            'FX0564-02',
            'FP0559-01',
            'FP0559-02',
            'FP0588-01',
            'FP0588-02',
            'FX0573-01',
            'FX0573-02',
            'FX0565-01',
            'FT0570-02',
            'HM0572-01',
            'HM0572-02',
            'FM0575-02',
            'FT0577-01',
            'FT0571-01',
            'FT0571-02',
            'FT0571-03',
            'FT0579-02',
            'FM0568-01',
            'FM0576-01',
            'FM0576-02',
            'FM0576-03',
            'FX0595-01',
            'FX0595-02',
            'FX0600-01',
            'FX0600-02',
            'FM0581-01',
            'FP0580-03',
            'HM0566-01',
            'FP0591-01',
            'FX0597-01',
            'FX0597-02',
            'FP0592-01',
            'FP0563-03',
            'FT0058-01',
            'FT0058-02',
            'FA0016-03',
            'FA0037-01',
            'FA0037-03',
            'FX0565-02',
            'FT0567-01',
            'HM0569-01',
            'HM0569-02',
            'HM0578-01',
            'HM0578-02',
            'HM0582-01',
            'FM0583-01',
            'FT0584-02',
            'FM0585-01',
            'FM0585-02',
            'FM0586-01',
            'FM0586-02',
            'FM0587-01',
            'FM0596-01',
            'FM0587-02',
            'FM0596-02',
            'FP0589-01',
            'FP0598-02',
            'FP0589-02',
            'FX0608-01',
            'FT0590-02',
            'FX0608-02',
            'FX0608-03',
            'FM0593-01',
            'FM0594-01',
            'FM0594-02',
            'FP0599-02',
            'FX0601-01',
            'FA0603-01',
            'FX0601-02',
            'FX0604-01',
            'FX0604-02',
            'FP0613-02',
            'FA0605-01',
            'FP0616-01',
            'FP0616-02',
            'FP0614-01',
            'FP0634-01',
            'FP0634-02',
            'FA0610-01',
            'FA0610-02',
            'FA0610-03',
            'FX0612-02',
            'FP0560-01',
            'FP0617-01',
            'FX0574-02',
            'FP0618-01',
            'FM0611-02',
            'FA0619-01',
            'FM0633-01',
            'FM0633-02',
            'FM0633-03',
            'FX0622-01',
            'FX0623-01',
            'FM0627-01',
            'FM0627-02',
            'FX0624-01',
            'FM0628-01',
            'FM0628-02',
            'FX0630-01',
            'FX0630-02',
            'FA0629-01',
            'FA0629-02',
            'FT0638-01',
            'FA0640-01',
            'FA0641-01',
            'FP0637-01',
            'FM0620-02',
            'FA0636-01',
            'FM0643-01',
            'VFA0072-02',
            'FP0649-01',
            'FP0634-03',
            'FA0644-02',
            'FA0645-01',
            'FA0647-01',
            'FA0647-02',
            'FA0039-01',
            'FA0039-03',
            'FT0085-02',
            'FT0086-02',
            'FT0086-03',
            'FA0040-01',
            'FA0040-02',
            'FA0065-01',
            'FT0103-03',
            'FT0117-02',
            'FT0139-02',
            'FA0648-02',
            'FM0650-01',
            'FM0650-02',
            'FM0650-03',
            'FA0651-01',
            'FA0651-02',
            'FA0106-02',
            'FA0106-03',
            'FM0653-01',
            'FM0653-02',
            'FA0126-01',
            'FA0126-02',
            'FA0655-01',
            'FA0655-02',
            'FA0656-01',
            'FM0010-01',
            'FX0012-01',
            'FM0053-01',
            'FX0012-03',
            'FM0053-03',
            'FX0030-02',
            'FM0056-01',
            'FM0056-02',
            'FX0033-01',
            'FX0036-01',
            'FX0036-02',
            'FX0036-03',
            'FX0038-01',
            'FX0038-02',
            'FX0043-01',
            'FX0047-01',
            'FX0047-02',
            'FX0047-04',
            'FM0113-02',
            'FX0050-01',
            'FX0050-02',
            'FM0109-01',
            'FM0109-02',
            'FX0062-01',
            'FX0062-02',
            'FA0657-01',
            'FX0073-01',
            'FX0073-02',
            'FA0657-03',
            'FX0075-01',
            'FX0075-02',
            'FX0001-01',
            'FX0082-01',
            'FX0097-01',
            'FX0097-02',
            'FP0140-01',
            'FP0140-02',
            'FP0091-01',
            'FP0091-03',
            'FP0091-04',
            'FP0183-01',
            'FP0186-03',
            'FT0013-01',
            'FA0658-01',
            'FT0017-03',
            'FP0087-02',
            'FP0087-03',
            'FP0087-04',
            'FA0652-02',
            'FX0132-01',
            'FX0122-01',
            'FX0122-02',
            'FX0122-03',
            'FX0202-01',
            'FX0202-02',
            'FA0659-01',
            'FA0659-02',
            'FX0112-01',
            'FX0112-02',
            'FM0193-01',
            'FM0193-02',
            'FM0194-01',
            'FM0194-02',
            'FX0118-02',
            'FM0197-01',
            'FM0210-01',
            'FM0210-02',
            'FX0129-01',
            'FX0129-02',
            'HP0661-01',
            'FX0137-01',
            'FX0137-02',
            'FP0133-01',
            'FP0133-02',
            'FP0141-01',
            'FP0141-02',
            'FP0148-01',
            'FP0153-01',
            'FX0192-01',
            'FP0157-01',
            'FP0664-01',
            'FP0190-01',
            'FP0663-01',
            'FP0203-01',
            'FX0220-01',
            'FX0220-02',
            'FM0666-01',
            'FP0205-01',
            'FM0666-02',
            'FP0660-02',
            'FP0660-03',
            'HM0110-01',
            'FM0670-01',
            'FM0670-02',
            'HM0114-02',
            'FP0671-01',
            'FT0014-03',
            'HM0115-02',
            'TI0673-02',
            'FT0020-02',
            'HM0142-01',
            'HM0142-02',
            'FA0677-01',
            'FT0022-03',
            'FA0677-02',
            'FA0672-01',
            'FA0675-01',
            'FA0675-02',
            'FM0682-01',
            'FA0679-01',
            'FT0031-03',
            'FA0679-02',
            'FT0032-01',
            'FP0676-01',
            'FP0676-02',
            'TI0054-01',
            'FT0055-01',
            'TI0054-03',
            'FT0055-02',
            'FT0055-03',
            'FA0680-01',
            'FA0680-02',
            'FT0015-02',
            'FA0674-01',
            'FA0674-02',
            'FA0681-01',
            'FA0018-02',
            'FA0681-02',
            'FM0683-01',
            'FX0046-01',
            'FP0684-02',
            'FP0144-01',
            'FP0144-02',
            'FX0685-01',
            'FX0685-02',
            'FX0685-03',
            'FP0687-02',
            'FX0686-01',
            'FT0688-01',
            'FT0688-02',
            'FT0642-02',
            'FX0061-01',
            'FT0690-02',
            'FT0690-01',
            'FP0691-01',
            'FP0692-01',
            'FX0693-01',
            'FA0694-01',
            'FP0671-02',
            'FX0696-01',
            'FP0704-01',
            'FX0696-02',
            'FA0698-01',
            'FX0699-01',
            'FA0694-02',
            'FP0708-01',
            'FX0697-01',
            'TI0695-01',
            'FX0697-02',
            'FA0700-01',
            'VFA0170-02',
            'FX0701-01',
            'FA0705-01',
            'FP0709-01',
            'FP0709-03',
            'FP0710-01',
            'FL01-01',
            'FL01-02',
            'FL01-03',
            'FL02-01',
            'FL02-02',
            'FL02-03',
            'FA0715-02',
            'FA0715-03',
            'FA0716-01',
            'FA0711-02',
            'FA0713-01',
            'FA0712-01',
            'FA0712-02',
            'FA0714-01',
            'FP0087-05',
            'FM0718-01',
            'FX0721-02',
            'FP0724-02',
            'FP0724-03',
            'Acc-04',
            'Acc-06',
            'FX0720-01',
            'FP0723-01',
            'FP0723-02',
            'FP0722-01',
            'FP0722-02',
            'FA0730-01',
            'FA0731-01',
            'FX0727-01',
            'FT0728-01',
            'FT0729-01',
            'FA0735-01',
            'FA0736-01',
            'FA0738-01',
            'FA0738-02',
            'FA0733-01',
            'FA0734-01',
            'FA0741-01',
            'TI0751-01',
            'TI0751-02',
            'FA0755-01',
            'FX0737-01',
            'FA0739-01',
            'FA0739-02',
            'FA0753-01',
            'FA0753-02',
            'FA0744-01',
            'FA0756-01',
            'FA0756-02',
            'FA0748-01',
            'FA0748-02',
            'FX0743-01',
            'FA0745-01',
            'FA0760-01',
            'FA0760-02',
            'FM0763-01',
            'FM0763-02',
            'HM0747-01',
            'FA0750-01',
            'FX0758-01',
            'FX0762-01',
            'FA0768-01',
            'FA0768-02',
            'FT0057-02',
            'HM0114-01',
            'FM0766-02',
            'FA0769-01',
            'VFX0198-01',
            'FT0031-04',
            'FX0092-02',
            'FA0629-03',
            'FX0774-02',
            'FA0775-01',
            'FT0776-01',
            'FT0776-02',
            'FM0782-01',
            'FX0777-01',
            'FX0777-02',
            'FA0780-01',
            'FA0780-02',
            'FA0783-01',
            'FA0784-01',
            'FX0785-01',
            'FX0770-01',
            'FX0770-02',
            'FP0708-03',
            'FT0773-01',
            'FP0778-01',
            'FA0779-01',
            'FA0781-01',
            'FA0786-01',
            'FM0787-02',
            'FA0789-01',
            'FA0789-02',
            'FA0791-01',
            'FA0792-02',
            'FA0793-01',
            'VFA0209-01',
            'VFX0135-01',
            'FA0794-01',
            'FA0795-01',
            'FA0796-01',
            'FA0800-01',
            'FA0800-02',
            'FA0755-02',
            'FX0685-04',
            'FA0797-01',
            'FA0797-02',
            'FA0799-01',
            'FA0802-01',
            'FA0802-02',
            'FA0802-03',
            'FA0804-01',
            'FA0717-03',
            'FA0801-01',
            'FA0801-02',
            'FA0801-03',
            'FP0189-02',
            'FA0803-01',
            'FA0803-02',
            'FX0808-01',
            'FX0807-01',
            'TI0813-01',
            'TI0813-02',
            'FA0816-01',
            'FA0809-01',
            'FA0810-01',
            'FA0817-01',
            'FA0817-02',
            'TI0814-02',
            'TI0815-01',
            'TI0815-02',
            'FP0664-03',
            'FA0818-01',
            'FA0820-01',
            'FA0821-01',
            'FA0821-02',
            'FA0822-01',
            'FA0822-02',
            'FA0822-03',
            'VFP0226-01',
            'FA0820-02',
            'VFX0229-01',
            'VFX0229-02',
            'FP0824-01',
            'VFA0207-03',
            'FA0826-03',
            'FA0827-01',
            'FA0827-02',
            'FA0828-01',
            'FA0829-01',
            'FA0829-02',
            'FA0832-01',
            'FA0832-02',
            'FA0833-01',
            'FA0834-01',
            'VFP0243-02',
            'VFA0245-02',
            'VFA0245-03',
            'FP0140-04',
            'FA0830-01',
            'FA0836-01',
            'FA0837-01',
            'FA0837-02',
            'FA0838-01',
            'FA0838-02',
            'FA0839-01',
            'FA0839-02',
            'FA0840-01',
            'FX0842-02',
            'FX0842-01',
            'VFP0243-01',
            'VFA0246-01',
            'VFP0250-01',
            'FA0715-04',
            'VFP0228-01',
            'VFP0177-02',
            'VFP0253-01',
            'VFP0253-02',
            'FA0843-01',
            'FA0843-02',
            'FA0844-01',
            'FA0846-01',
            'FA0846-02',
            'VFA0213-01',
            'VFA0213-02',
            'FA0453-03',
            'FA0798-02',
            'FA0838-03',
            'VFA0247-01',
            'VFA0239-01',
            'VFA0239-02',
            'VFA0239-03',
            'VFP0250-02',
            'FA0853-01',
            'FA0893-01',
            'VFX0229-03',
            'VFA0251-01',
            'VFA0251-02',
            'FA0845-01',
            'FA0849-01',
            'FA0847-01',
            'FA0848-01',
            'VFX0234-01',
            'FA0855-02',
            'VFP0257-01',
            'VFP0257-02',
            'FA0850-01',
            'FA0863-01',
            'FA0863-02',
            'FA0873-01',
            'FA0851-01',
            'FA0854-01',
            'FA0857-01',
            'FA0857-02',
            'FA0859-01',
            'FA0860-01',
            'FA0883-01',
            'FA0862-02',
            'FA0864-01',
            'FA0864-02',
            'VFP0193-01',
            'VFP0193-02',
            'FX0093-02',
            'FX0093-03',
            'FX0093-04',
            'FA0310-03',
            'FA0310-04',
            'FX0861-01',
            'FM0865-01',
            'FM0865-02',
            'VFP0264-01',
            'VFP0264-02',
            'FA0866-02',
            'SX0005-01',
            'SX0006-01',
            'SX0008-01',
            'FA0867-01',
            'FA0867-02',
            'FA0867-03',
            'FX0861-02',
            'FX0868-01',
            'FA0870-02',
            'SX0001-02',
            'SA0011-01',
            'SA0011-02',
            'SP0012-01',
            'SP0012-02',
            'SP0012-03',
            'FX0881-01',
            'SA0010-01',
            'SA0010-02',
            'SP0013-01',
            'SM0017-01',
            'FX0872-01',
            'FX0874-01',
            'SX0014-01',
            'SX0016-01',
            'SX0016-03',
            'FA0875-01',
            'TI0876-01',
            'SX0015-01',
            'FX0759-01',
            'SX0020-01',
            'SP0021-01',
            'SP0023-01',
            'SP0024-01',
            'VFP0274-01',
            'VFP0274-02',
            'VFP0275-01',
            'VFP0275-02',
            'ST0022-01',
            'ST0022-02',
            'VFP0276-01',
            'VFP0276-02',
            'SX0026-02',
            'SP0027-02',
            'VHP0281-01',
            'VHP0281-02',
            'VFW0279-01',
            'VFP0283-01',
            'SP0028-02',
            'FA0879-01',
            'FA0879-02',
            'SX0029-01',
            'FA0264-02',
            'FA0756-03',
            'FA0866-03',
            'SX0030-02',
            'SX0031-01',
            'FA0882-02',
            'FA0884-01',
            'FA0884-02',
            'FM0878-01',
            'FM0878-02',
            'FA0887-01',
            'FA0887-02',
            'VFP0294-01',
            'FA0888-01',
            'FA0888-02',
            'FA0891-02',
            'FA0891-03',
            'SW0009-02',
            'SW0003-01',
            'SW0003-02',
            'FA0889-02',
            'FA0890-01',
            'FA0890-02',
            'SW0032-02',
            'FA0894-01',
            'FA0894-02',
            'FA0894-03',
            'SW0033-01',
            'SW0034-01',
            'FA0897-01',
            'FA0897-02',
            'FA0899-01',
            'FA0899-02',
            'SA0035-01',
            'SA0037-01',
            'FA0895-01',
            'FA0895-02',
            'FA0895-03',
            'SA0036-01',
            'FT0898-01',
            'FX0900-01',
            'FX0900-02',
            'FP0668-04',
            'FP0668-05',
            'FP0668-06',
            'FX0901-01',
            'FX0901-03',
            'FA0902-01',
            'TI0903-02',
            'TI0909-01',
            'TI0909-02',
            'TI0908-01',
            'TI0911-02',
            'FX0732-02',
            'TI0905-01',
            'TI0906-01',
            'TI0904-01',
            'FP0896-01',
            'FP0896-02',
            'FX0907-01',
            'FA0912-01',
            'FA0912-02',
            'FX0913-01',
            'FX0913-02',
            'FA0915-01',
            'FA0915-02',
            'FX0914-01',
            'FX0916-01',
            'FX0916-02',
            'FX0917-01',
            'FX0917-02',
            'FA0918-01',
            'FA0918-02',
            'FX0919-01',
            'HM0920-01',
            'HM0920-02',
            'FX0921-01',
            'FX0921-02',
            'FX0922-02',
            'FA0924-02',
            'FA0924-03',
            'VFM0194-01',
            'ZX0926-03',
            'ZA0930-01',
            'ZA0930-02',
            'ZA0930-03',
            'ZX0948-01',
            'ZX0948-02',
            'ZX0948-03',
            'ZX0947-01',
            'ZX0947-02',
            'ZA0927-01',
            'ZA0928-01',
            'ZA0928-02',
            'ZA0932-01',
            'ZP0939-01',
            'ZP0941-01',
            'ZP0941-02',
            'ZA0933-01',
            'ZA0933-02',
            'ZA0935-01',
            'ZA0935-02',
            'ZP0937-01',
            'ZP0937-02',
            'ZP0936-02',
            'ZP0946-01',
            'ZT0951-01',
            'ZT0951-02',
            'SM0038-01',
            'SM0038-03',
            'ZP0943-01',
            'ZP0943-02',
            'ZP0950-01',
            'ZP0956-01',
            'ZP0956-02',
            'FA0453-04',
            'ZI0949-02',
            'ZP0952-01',
            'ZP0952-02',
            'ST0041-01',
            'ZP0953-01',
            'ZP0953-02',
            'ZP0953-03',
            'ZP0954-01',
            'ZP0954-02',
            'ZP0955-02',
            'ST0042-02',
            'ZA0963-02',
            'ZA0963-01',
            'ZA0965-01',
            'ZA0965-02',
            'ZA0970-01',
            'ZA0971-01',
            'ZA0972-01',
            'ZA0972-02',
            'ZA0966-01',
            'ZA0974-02',
            'ZA0975-01',
            'ZA0960-01',
            'ZA0967-01',
            'ZM0978-01',
            'ZA0962-01',
            'ZA0973-02',
            'ZA0973-01',
            'ZA0959-01',
            'ZA0959-02',
            'ZA0959-03',
            'FM0167-05',
            'FM0167-06',
            'ZX0977-01',
            'ZX0977-02',
            'ZM0979-01',
            'ZX0983-01',
            'ZX0983-02',
            'ZM0980-01',
            'ZM0980-02',
            'ZM0980-04',
            'ZM0980-03',
            'ZA0964-01',
            'ZA0964-02',
            'ZA0976-01',
            'ZA0969-01',
            'ZA0961-01',
            'ZA0961-02',
            'ZM0982-01',
            'ZM0982-04',
            'ZM0986-02',
            'ZM0986-03',
            'ZM0986-05',
            'ZA0965-03',
            'ZA0985-01',
            'ZA0985-02',
            'ZA0984-02',
            'ZX0988-01',
            'ER5018-01',
            'ER5006-01',
            'ER5002-02',
            'ZA0990-01',
            'ZA0990-02',
            'ZA0989-01',
            'ZA0989-02',
            'ZA0991-01',
            'ZA0991-02',
            'SA0044-01',
            'SA0044-02',
            'ER5015-01',
            'ER5005-01',
            'ER5003-01',
            'ER5016-01',
            'ER5006-03',
            'FA0742-03',
            'ZA0992-01',
            'ER5007-02',
            'ER5027-01',
            'ER5024-01',
            'ER5010-02',
            'ER5029-02',
            'ER5029-03',
            'VFP0167-01',
            'SA0047-01',
            'SA01117-01',
            'SA01117-03',
            'SA01118-03',
            'SX01129-01',
            'SP01127-01',
            'SX01274-01',
            'SX01275-01',
            'SX01271-01',
            'SX01272-01',
            'SX01273-01',
            'SA01216-01',
            'SA01218-01',
            'SA01206-01',
            'SA01208-01',
            'SA01220-01',
            'SA01212-01',
            'SN01328-01',
            'SN01382-01',
            'ST01335-01',
            'ST01341-01',
            'ST01338-01',
            'ST01338-02',
            'ST01246-01',
            'ST01247-01',
            'SX01359-01',
            'SX01359-02',
            'SA01210-02',
            'SX01360-01',
            'SX01379-01',
            'SX01251-01',
            'SX01251-02',
            'SX01380-01',
            'SX01381-01',
            'SX01367-01',
            'SX01365-03',
            'SX01371-01',
            'SX01374-01',
            'SX01375-01',
            'SX01368-01',
            'SX01369-01',
            'SX01422-01',
            'SX01354-02',
            'SX01377-01',
            'SM01319-02',
            'ST01337-01',
            'SX01352-02',
            'SX01123-01',
            'SX01124-01',
            'SX01425-01',
            'SA01205-03',
            'ZI0997-01',
            'FX0004-05',
            'ZA1001-01',
            'ZA1001-02',
            'ZA1000-02',
            'ER5022-01',
            'ER5029-01',
            'SM0045-02',
            'SA01458-01',
            'SA01458-02',
            'ZI0994-01',
            'ZI0994-02',
            'SA01460-01',
            'ZI0995-01',
            'ZI0996-01',
            'ZI0996-02',
            'ZA0999-01',
            'ZI0998-01',
            'SA01457-02',
            'SA01461-01',
            'FA0792-03',
            'SA01459-01',
            'ER5025-01',
            'ER5028-01',
            'ER5030-01',
            'OX01463-01',
            'OX01492-01',
            'OX01493-01',
            'OA01502-02',
            'OA01503-01',
            'OA01503-02',
            'WA01602-01',
            'WA01602-02',
            'WA01604-01',
            'WA01604-03',
            'WA01606-01',
            'WA01608-01',
            'WA01608-02',
            'WA01609-01',
            'WA01609-02',
            'WX01612-01',
            'WA01615-01',
            'WA01666-01',
            'WA01667-01',
            'WA01618-01',
            'WA01616-01',
            'FX0353-03',
            'WA01662-01',
            'WA01660-01',
            'WA01660-02',
            'WM01680-02',
            'WA01617-01',
            'WA01658-01',
            'WA01658-02',
            'WA01658-03',
            'WA01622-01',
            'WA01621-01',
            'WX01643-01',
            'WA01654-02',
            'WA01648-03',
            'WA01650-01',
            'WA01650-02',
            'WA01652-01',
            'WM01684-01',
            'WX01642-01',
            'WX01642-02',
            'WA01692-01',
            'WA01692-02',
            'WA01693-01',
            'WA01693-02',
            'WA01703-01',
            'WA01704-02',
            'WA01712-01',
            'WA01712-02',
            'WA01712-03',
            'WA01721-01',
            'WA01722-01',
            'WA01713-02',
            'WA01711-01',
            'WA01711-02',
            'WA01699-01',
            'WA01702-01',
            'WA01719-02',
            'WA01720-01',
            'WA01727-02',
            'WA01728-01',
            'WA01729-01',
            'WN01730-01',
            'WN01730-02',
            'OX01493-02',
            'OX01492-02',
            'OX01491-01',
            'OX01491-02',
            'OX01491-03',
            'OA01505-02',
            'OA01504-01',
            'FA0781-03',
            'FA0781-05',
            'OA01506-01',
            'OA01506-02',
            'OA01535-01',
            'OA01535-02',
            'FA0346-04',
            'OA01536-01',
            'OA01524-01',
            'OA01524-02',
            'OA01531-01',
            'OA01539-01',
            'OA01545-01',
            'OA01545-02',
            'OA01545-03',
            'OA01545-04',
            'OA01544-03',
            'OA01544-04',
            'OA01544-05',
            'OA01500-03',
            'OA01500-04',
            'OA01518-02',
            'OA01518-04',
            'OA01518-05',
            'OA01517-03',
            'OA01517-04',
            'WA01746-01',
            'WA01746-02',
            'OA01534-01',
            'WA01743-01',
            'WA01743-02',
            'WP01751-02',
            'WP01751-03',
            'WA01744-01',
            'WA01744-02',
            'WA01741-01',
            'WA01741-02',
            'WA01741-03',
            'WA01742-03',
            'WX01749-01',
            'WX01749-02',
            'WA01715-01',
            'WA01745-01',
            'WA01745-02',
            'WA01745-03',
            'WA01747-02',
            'WA01747-03',
            'WA01740-01',
            'WA01700-01',
            'WA01700-02',
            'WA01748-01',
            'WM01752-01',
            'WA01733-01',
            'OA01573-01',
            'OA01573-02',
            'OA01576-01',
            'WA01670-02',
            'WA01670-01',
            'WA01674-01',
            'OA01575-01',
            'OA01575-02',
            'OA01569-01',
            'OA01572-01',
            'OA01571-01',
            'OA01481-01',
            'OA01529-01',
            'OA01528-01',
            'OA01532-01',
            'OA01760-01',
            'OA01519-01',
            'OA01762-02',
            'WM01673-01',
            'OA01589-02',
            'OA01533-01',
            'OA01590-01',
            'OP01598-01',
            'OP01756-01',
            'OP01595-01',
            'OP01599-01',
            'OP01597-01',
            'OP01596-01',
            'OA01513-01',
            'OM01764-01',
            'OM01764-02',
            'OA01484-02',
            'OA01785-01',
            'OA01591-01',
            'OA01592-01',
            'OA01592-02',
            'OA01792-02',
            'OA01794-01',
            'OA01794-02',
            'OA01793-02',
            'OA01793-03',
            'OA01515-01',
            'OM01490-01',
            'OA01485-02',
            'OA01485-03',
            'OA01538-01',
            'OA01777-01',
            'OA01777-02',
            'OA01810-01',
            'OT01594-01',
            'OA01593-01',
            'OA01509-01',
            'OA01509-02',
            'OA01486-01',
            'OA01486-02',
            'OA01805-02',
            'OA01784-01',
            'OA01775-01',
            'OA01773-01',
            'OA01773-02',
            'OM01761-01',
            'OA01520-01',
            'OA01496-01',
            'OA01495-01',
            'OA01537-01',
            'WA01753-04',
            'OA01806-01',
            'OA01806-02',
            'OA01821-01',
            'OA01795-02',
            'OA01795-03',
            'OM01783-01',
            'OA01789-01',
            'OA01800-01',
            'OA01803-01',
            'OA01803-02',
            'OA01774-02',
            'OA01782-01',
            'OA01781-01',
            'OA01814-01',
            'OA01804-01',
            'OA01804-02',
            'OA01820-01',
            'OA01820-02',
            'OA01820-03',
            'OA01809-01',
            'OA01808-01',
            'OA01817-01',
            'OA01817-02',
            'OA01823-01',
            'OA01823-02',
            'OA01822-02',
            'OA01824-01',
            'OA01824-02',
            'OA01827-01',
            'OA01827-02',
            'OA01828-01',
            'OA01828-02',
            'OA01832-01',
            'OA01832-02',
            'OW01845-01',
            'OW01846-01',
            'OM01836-01',
            'OM01836-02',
            'OA01829-01',
            'OA01829-02',
            'OA01829-03',
            'OA01829-04',
            'OA01830-01',
            'OA01834-02',
            'OA01834-03',
            'OA01825-01',
            'OA01825-02',
            'OA01838-01',
            'ER5035-01',
            'OA01856-01',
            'OA01856-02',
            'OA01856-03',
            'OA01859-01',
            'OA01859-02',
            'OA01855-01',
            'OA01855-02',
            'OA01857-01',
            'OA01857-02',
            'OA01857-03',
            'OA01857-04',
            'OX01878-01',
            'OA01871-01',
            'OM01848-03',
            'OA01877-01',
            'OA01877-02',
            'OM01849-01',
            'OM01849-02',
            'OM01853-02',
            'OA01851-03',
            'FX0052-04',
            'OA01879-01',
            'OA01876-01',
            'OA01876-02',
            'OA01882-01',
            'OA01882-02',
            'OM01883-01',
            'OA01880-01',
            'OA01880-02',
            'OM01872-02',
            'OA01864-01',
            'OA01881-01',
            'OA01862-02',
            'OA01868-01',
            'OA01868-02',
            'OA01865-01',
            'OA01865-02',
            'OA01894-01',
            'OA01867-01',
            'OA01867-02',
            'OA01869-01',
            'OA01897-01',
            'OA01897-02',
            'OA01895-02',
            'OA01769-01',
            'OA01896-01',
            'OA01896-02',
            'OA01904-02',
            'OT01911-01',
            'OT01911-02',
            'OA01909-01',
            'OA01909-02',
            'OA01920-02',
            'OA01905-01',
            'OA01907-01',
            'OA01907-02',
            'WA01603-02',
            'WA01603-03',
            'OM01913-01',
            'OI01916-01',
            'OA01940-02',
            'OT01914-01',
            'OT01915-01',
            'OT01915-02',
            'OA01923-02',
            'OA01926-01',
            'OX01928-01',
            'OX01921-01',
            'OA01941-01',
            'OM01937-01',
            'OM01937-02',
            'OM01935-01',
            'OA01936-01',
            'OA01936-02',
            'OM01932-01',
            'OT01952-01',
            'OT01952-02',
            'OA01938-01',
            'OX01950-01',
            'OX01950-02',
            'OA01943-01',
            'OA01939-01',
            'OM01951-01',
            'OA01945-01',
            'OA01944-01',
            'OA01959-01',
            'OX01961-02',
            'OA01962-01',
            'OT01972-01',
            'OM01975-02',
            'OA01964-01',
            'OA01964-02',
            'OM01974-01',
            'OA01965-01',
            'OP01990-02',
            'OA01966-01',
            'ER6016-01',
            'OP01986-01',
            'OA01989-01',
            'OA01996-01',
            'OA01996-02',
            'OA01993-01',
            'OX01987-01',
            'OA01994-01',
            'OA01994-02',
            'OA01998-01',
            'OA01999-01',
            'OA02003-01',
            'OA01997-02',
            'OA02000-01',
            'OA02002-01',
            'OA02004-01',
            'OM02024-04',
            'OP02017-01',
            'OP02017-02',
            'OA02013-01',
            'OA02013-02',
            'OA02014-02',
            'OM02016-01',
            'OM02021-02',
            'OM02021-03',
            'OX02022-01',
            'OA02020-01',
            'OA02020-02',
            'OA02020-03',
            'OM02025-02',
            'OA02035-03',
            'OX02023-01',
            'OA02037-02',
            'OA02034-01',
            'OA02036-02',
            'ER06045-01',
            'OA02041-01',
            'OA02041-05',
            'OA02041-03',
            'ACC06046-01',
            'ACC06046-02',
            'OA02042-01',
            'OA02042-03',
            'OA02042-05',
            'OA02043-01',
            'OA02043-04',
            'OA02043-02',
            'OA02043-03',
            'OA02044-01',
            'OA02044-04',
            'OA02044-05',
            'OA02044-02',
            'OA02057-01',
            'OA02057-02',
            'OA02053-01',
            'OA02062-01',
            'OA02062-02',
            'OA02062-03',
            'OA02064-03',
            'OA02070-01',
            'OA02070-02',
            'OA02059-01',
            'OA02069-01',
            'OA02069-03',
            'OA02071-01',
            'OA02058-01',
            'OA02063-01',
            'OA02063-02',
            'OA02063-03',
            'OA02075-01',
            'OA02054-01',
            'OA02054-02',
            'OP02085-01',
            'OA02066-01',
            'OA02060-01',
            'OA02060-02',
            'OA02083-01',
            'OA02088-01',
            'OA02065-01',
            'OA02056-01',
            'OX02091-02',
            'OA02090-01',
            'OP02047-01',
            'OX02095-01',
            'OM02102-02',
            'OX02111-01',
            'OI02114-02',
            'OP02048-01',
            'OA02104-02',
            'OA02108-01',
            'OA02106-01',
            'OA02130-02',
            'OA02125-01',
            'OM02122-02',
            'OM02122-03',
            'OA02133-03',
            'OA02137-03',
            'OP02128-02',
            'OA02134-01',
            'OA02136-01',
            'GI497414-01',
            'OP456725-01',
            'OP077474-01',
            'OP077474-02',
            'OM662855-02',
            'OP029333-01',
            'DA488986-01',
            'OA015508-01',
            'OP225077-01',
            'OP225077-02',
            'OX019285-01',
            'OX045565-02',
            'OX395215-02',
            'DX096236-01',
            'OP532467-01',
            'OP532467-02',
            'OM796599-01',
            'OX468320-01',
            'OM441540-01',
            'OP008768-01',
            'OP008768-02',
            'OP675215-03',
            'OX855461-01',
            'ER431942-01',
            'ER134040-01',
            'OP456725-02',
            'WA158859-01',
            'OX002546-01',
            'OT050046-03',
            'OT050046-04',
            'OP099710-05',
            'OA124856-01',
            'OT049254-01',
            'OA817636-01',
            'OP652540-01',
            'OP652540-02',
            'FP0886-03',
            'OX965149-01',
            'OM446692-01',
            'GA301763-04',
            'GA301763-05',
            'OX921163-01',
            'OX921163-02',
            'SX0001-01',
            'SX0007-01',
            'SM0045-01',
            'OM01456-02',
            'WM01672-01',
            'WM01672-02',
            'WA01704-03',
            'OA01576-02',
            'OX462594-01',
            'OX001364-03',
            'OP996844-02',
            'FP0563-01',
            'FP0563-02',
            'VFP0165-01',
            'VFA0207-01',
            'FA0806-01',
            'SP0027-01',
            'SX0015-02',
            'VFP0277-01',
            'VFP0292-02',
            'VFP0277-03',
            'GM0352-01',
            'GSM0026-01',
            'AFPB0001-01',
            'WA01611-01',
            'OA01795-01',
            'OA01774-01',
            'OA01833-01',
            'OW01843-01',
            'OW01847-01',
            'OA01858-01',
            'OA01851-01',
            'OP01863-01',
            'ER6004-01',
            'OA01895-01',
            'OA01900-02',
            'OT01914-02',
            'OT01906-01',
            'VFP0288-03',
            'OM01949-01',
            'ACC6030-01',
            'ER6044-01',
            'OW01844-02',
            'VFP0165-03',
            'OA02041-04',
            'OA02076-01',
            'OA02008-03',
            'OP02082-04',
            'OA02117-01',
            'OM02122-04',
            'OP02128-01',
            'NL021678-03',
            'OA01901-06',
            'OA094582-04',
            'OP376847-01',
            'OM115822-01',
            'OM115822-02',
            'DA438237-01',
            'OP000523-01',
            'OM389865-02',
            'OP119687-02',
            'VFP0292-03',
            'OT652438-03',
            'OX978145-02',
            'OM202513-03',
            'OX339464-01',
            'OP675215-01',
            'OA936753-05',
            'OM728946-01',
            'OP186723-03',
            'OA244274-02',
            'TX827070-03',
            'OP005860-03',
            'OP005860-06',
            'FA0407-03',
            'OX123188-01',
            'OP261490-03',
            'OT049280-05',
            'OT049280-08',
            'OA715116-01',
            'OX976871-04',
            'OM555522-01',
            'OM555522-02',
            'OM555522-03',
            'OT375172-01',
            'OT375172-02',
            'OT375172-03',
            'OT375172-04',
            'OT375172-06',
            'OT108597-03',
            'OX338259-01',
            'OX338259-02',
            'OX338259-03',
            'OM425586-01',
            'OP315193-02',
            'OP315193-04',
            'OP315193-01',
            'OP315193-03',
            'OM349860-01',
            'OM349860-02',
            'OT101726-02',
            'OT101726-04',
            'OT501032-01',
            'OA005234-01',
            'OA005234-02',
            'OA005234-03',
            'OM721750-02',
            'FM0053-01',
            'FX0038-01',
            'FX0062-01',
            'FX0072-01',
            'FT0086-01',
            'VFA0018-01',
            'VFM0031-01',
            'VFM0040-01',
            'FT0086-02',
            'FM0056-01',
            'FM0088-01',
            'FP0044-04',
            'FA0016-03',
            'FX0004-02',
            'FP0087-04',
            'FT0014-03',
            'FX0050-01',
            'FX0036-03',
            'VFX0010-01',
            'VFP0012-01',
            'FP0140-02',
            'RM0136-01',
            'FT0098-01',
            'FX0112-01',
            'FA0100-01',
            'VFT0002-01',
            'VFX0004-01',
            'VFM0025-01',
            'VFA0001-02',
            'VFA0047-01',
            'VFA0058-01',
            'VFX0053-01',
            'FT0226-01',
            'VFX0061-01',
            'VFX0069-01',
            'VFA0074-01',
            'VFM0068-02',
            'VFM0068-01',
            'VFT0028-01',
            'VFX0022-01',
            'VFM0034-01',
            'FX0235-01',
            'FX0137-01',
            'VFP0079-01',
            'VFA0072-02',
            'VFA0075-01',
            'VFX0056-01',
            'VFM0083-01',
            'VFA0085-01',
            'VFM0078-02',
            'VFM0091-01',
            'FP0304-01',
            'VFP0080-02',
            'FP0247-02',
            'VFP0094-01',
            'FP0336-01',
            'FA0363-02',
            'FP0339-02',
            'FT0368-01',
            'VFM0103-01',
            'FP0383-01',
            'FX0399-02',
            'FX0381-01',
            'FP0233-01',
            'VFM0105-01',
            'VFM0101-01',
            'FA0397-03',
            'FX0392-01',
            'FM0390-01',
            'VFA0113-01',
            'VFM0114-01',
            'FP0289-01',
            'VFW0115-01',
            'VFP0032-02',
            'VFP0120-01',
            'VFM0121-01',
            'VFT0118-01',
            'FP0483-01',
            'FA0513-01',
            'VFP0124-01',
            'FP0474-01',
            'FA0522-01',
            'FX0172-02',
            'FA0488-01',
            'VFX0133-01',
            'VFP0132-01',
            'VFX0131-01',
            'VFT0130-01',
            'VFX0134-01',
            'VFX0135-01',
            'FP0101-02',
            'FA0277-01',
            'FP0538-01',
            'HP0531-02',
            'VFA0138-01',
            'VFX0137-01',
            'FX0278-01',
            'HP0561-02',
            'FX0573-01',
            'FX0573-02',
            'VFP0147-01',
            'VFA0148-02',
            'VFA0149-01',
            'VFP0146-01',
            'VFX0143-01',
            'VFX0144-01',
            'VFX0152-01',
            'FP0588-01',
            'FP0087-02',
            'VFP0160-01',
            'VFP0157-01',
            'VFP0156-01',
            'FX0245-04',
            'VFX0161-01',
            'FX0353-02',
            'FP0556-01',
            'FP0556-02',
            'FP0599-02',
            'FP0563-03',
            'FP0227-01',
            'VFP0167-01',
            'FP0634-03',
            'VFT0159-01',
            'FP0634-02',
            'FP0634-01',
            'FX0170-03',
            'VFP0158-04',
            'VFP0154-02',
            'FX0245-03',
            'VFP0172-01',
            'VFA0173-01',
            'FP0383-03',
            'VFA0174-01',
            'VFP0032-03',
            'FX0608-02',
            'VFM0035-02',
            'VFP0182-01',
            'FP0671-01',
            'FP0671-02',
            'FP0664-01',
            'VFA0170-03',
            'VFA0170-02',
            'FP0580-03',
            'VFP0164-02',
            'FP0709-01',
            'FP0709-03',
            'VFP0186-01',
            'FP0692-01',
            'FP0724-03',
            'VFP0190-01',
            'VFP0192-01',
            'VFP0193-01',
            'VFP0195-01',
            'VFM0194-01',
            'VFP0196-01',
            'VFP0197-01',
            'VFX0198-01',
            'FA0739-01',
            'FA0640-01',
            'FA0753-01',
            'FA0753-02',
            'TI0751-02',
            'FA0739-02',
            'FA0629-03',
            'VFA0199-02',
            'VFA0202-01',
            'VFA0204-01',
            'VFA0203-01',
            'VFA0205-01',
            'VFA0208-01',
            'VFA0209-01',
            'VFA0201-01',
            'VFA0215-01',
            'VFA0210-01',
            'VFA0220-01',
            'VFA0212-01',
            'VFA0217-01',
            'FA0733-02',
            'VFA0219-01',
            'VFA0211-01',
            'VFA0221-01',
            'VFA0210-02',
            'VFA0213-01',
            'VFA0213-02',
            'VFA0214-01',
            'VFA0216-01',
            'FA0640-02',
            'VFA0214-02',
            'VFA0218-01',
            'FX0737-01',
            'VFP0160-02',
            'VFP0222-01',
            'VFA0221-02',
            'FA0675-01',
            'FA0675-02',
            'FA0792-02',
            'FA0797-02',
            'VFA0200-02',
            'FA0793-01',
            'FA0768-01',
            'TI0673-02',
            'VFP0223-01',
            'VFP0223-02',
            'FP0663-01',
            'FP0778-01',
            'FX0685-04',
            'FA0801-01',
            'FA0801-02',
            'FA0802-02',
            'FA0802-03',
            'VFT0224-01',
            'VFT0224-02',
            'FA0803-02',
            'FA0804-01',
            'FX0808-01',
            'FA0810-01',
            'VFP0230-01',
            'VFP0177-02',
            'VHX0225-01',
            'VFP0226-01',
            'VHX0225-02',
            'VFP0228-01',
            'FA0799-01',
            'VHX0225-03',
            'VFX0234-01',
            'VFP0235-01',
            'VFP0235-02',
            'VFP0193-02',
            'VFP0237-02',
            'VFP0237-01',
            'VFP0231-01',
            'VFP0240-01',
            'VFA0207-03',
            'VFX0229-01',
            'FA0715-04',
            'VFP0238-01',
            'VFA0245-02',
            'VFP0243-01',
            'VFA0246-01',
            'VFP0243-02',
            'VFA0245-03',
            'VFX0229-02',
            'VFP0241-01',
            'FP0708-01',
            'FP0660-02',
            'VFP0250-01',
            'FM0620-02',
            'Necklace-01',
            'Mask-02',
            'Mask-03',
            'FX0842-01',
            'FX0842-02',
            'VFP0253-01',
            'VFP0253-02',
            'FX0214-01',
            'VFP0252-01',
            'FX0447-03',
            'Chain-V01',
            'VFP0250-02',
            'FA0715-03',
            'FP0676-01',
            'FP0676-02',
            'VFA0247-01',
            'FX0447-02',
            'VFA0239-01',
            'VFA0251-01',
            'VFA0251-02',
            'VFX0229-03',
            'VFP0254-01',
            'FA0847-01',
            'VFP0257-01',
            'VFP0257-02',
            'FA0851-01',
            'FA0849-01',
            'FX0122-01',
            'VFP0259-01',
            'VFP0259-02',
            'VFP0233-02',
            'VFP0116-02',
            'VFP0116-03',
            'VFA0239-02',
            'VFA0262-01',
            'VFA0262-02',
            'VFA0239-03',
            'VFP0264-01',
            'FX0093-02',
            'VFP0264-02',
            'FX0093-03',
            'FA0310-03',
            'FX0093-04',
            'FA0310-04',
            'FP0162-01',
            'VFP0265-01',
            'FA0179-01',
            'FP0087-03',
            'FP0649-01',
            'VFP0267-01',
            'VFP0267-02',
            'VFP0268-01',
            'VFM0266-01',
            'VFX0229-04',
            'FA0789-01',
            'FA0672-01',
            'FX0685-01',
            'FX0685-02',
            'VFP0278-01',
            'VFP0278-02',
            'VFP0274-01',
            'VFP0274-02',
            'VFP0275-01',
            'VFP0276-01',
            'VFP0275-02',
            'VFP0272-01',
            'VFW0279-01',
            'VFP0276-02',
            'VFP0283-01',
            'VHP0281-01',
            'VHP0281-02',
            'VFM0282-01',
            'VFP0277-02',
            'VFW0285-01',
            'VFP0289-01',
            'VFP0293-01',
            'VFP0294-01',
            'VFP0292-01',
            'VFP0289-02',
            'VFP0291-01',
            'VFP0295-01',
            'FA0891-03',
            'VFW0284-01',
            'VFA0300-01',
            'VFA0302-01',
            'VFP0298-02',
            'FA0899-01',
            'FA0899-02',
            'VFP0304-03',
            'FP0668-04',
            'FP0668-05',
            'VFA0301-01',
            'VFP0304-01',
            'VFP0305-01',
            'FP0896-01',
            'FP0896-02',
            'FA0393-01',
            'FA0884-01',
            'FA0884-02',
            'VFP0297-03',
            'VFP0303-01',
            'VFP0303-02',
            'FX0921-01',
            'FX0921-02',
            'VFA0308-01',
            'VFP0307-03',
            'VFP0286-02',
            'VFP0286-03',
            'VFP0311-01',
            'FA0924-02',
            'VFP0315-01',
            'VFP0315-02',
            'VFP0312-01',
            'VFP0316-01',
            'VFP0286-01',
            'GP0324-01',
            'GT0319-01',
            'GP0320-01',
            'GP0320-02',
            'GP0322-02',
            'GP0323-01',
            'GP0323-02',
            'FP0896-04',
            'GX0325-01',
            'GX0318-01',
            'GP0327-01',
            'GX0318-02',
            'GX0318-03',
            'VFT0271-02',
            'GP0329-01',
            'VFA0208-02',
            'GX0332-01',
            'GX0328-02',
            'GM0330-01',
            'GM0331-01',
            'GZX109481-01',
            'GA0335-01',
            'GA0335-03',
            'GP0338-01',
            'GP0339-01',
            'GZX109481-02',
            'GZX109481-03',
            'GZM109792-01',
            'GA0334-01',
            'GZM109862-02',
            'GZM109862-03',
            'GZM109862-05',
            'GZX109772-01',
            'GZX109772-02',
            'GZA109632-01',
            'GZA109632-02',
            'GZA109642-01',
            'GZA109642-02',
            'GZA109602-01',
            'GP0342-01',
            'GX0336-01',
            'GX0328-01',
            'GT0343-01',
            'GT0343-02',
            'GP0341-02',
            'GM0333-01',
            'GP0341-01',
            'GT0346-01',
            'GP0345-01',
            'GZA109762-01',
            'GP0350-01',
            'GM0349-01',
            'GSA100351-01',
            'GSP100131-01',
            'GSA300363-01',
            'GSP100121-01',
            'GSP100121-02',
            'GSP100121-03',
            'GP0359-01',
            'GP0361-01',
            'GP0362-01',
            'GZA509915-01',
            'GX0360-01',
            'GZA609908-02',
            'GZA809909-01',
            'GP0361-03',
            'GP0351-01',
            'GSX100161-01',
            'GSX100161-03',
            'GSX300296-01',
            'GSX600208-01',
            'GSX900308-02',
            'GSP200233-01',
            'GSP300245-01',
            'GSP600276-01',
            'GSP600276-02',
            'GST200226-01',
            'GST200226-02',
            'GSX100012-01',
            'GSX100012-02',
            'GSX800089-01',
            'GX0369-01',
            'GX0369-02',
            'GX0336-02',
            'GX0336-03',
            'GP0320-03',
            'GP0320-04',
            'GT0367-01',
            'GM0366-02',
            'GM0366-01',
            'GM0352-03',
            'GM0363-01',
            'GT0368-02',
            'GSP0003-02',
            'GT0368-01',
            'GP0372-01',
            'GP0342-03',
            'GM0365-01',
            'GM0365-02',
            'GX0370-03',
            'GP0359-02',
            'GX0373-01',
            'GX0357-01',
            'GX0370-01',
            'GX0370-02',
            'GSX0012-02',
            'GSP0005-01',
            'GSP0011-01',
            'GX0360-02',
            'GSP0004-02',
            'GSA100471-01',
            'GSP0014-01',
            'GZM109821-01',
            'GZM109821-04',
            'GSP0015-01',
            'GM0356-02',
            'GSP0017-02',
            'GSP0017-03',
            'GSP0017-01',
            'GSM0001-01',
            'GSP0014-02',
            'GSP0006-01',
            'GUSA01113-01',
            'GSX01137-01',
            'GSA01259-01',
            'GSA01262-02',
            'GUSA01261-01',
            'GSA01196-01',
            'GSX01139-01',
            'GSX01139-02',
            'GSA01263-01',
            'GUSA01264-01',
            'GSA01305-01',
            'GSX01251-01',
            'GM0354-01',
            'GSX01251-02',
            'GSI01311-01',
            'GSI01310-01',
            'GSI01312-01',
            'GSI01313-01',
            'GSM01315-01',
            'GUSA01279-01',
            'GSM01318-01',
            'GSM01318-02',
            'GSX0039-01',
            'GSM0008-01',
            'GSP0003-01',
            'GSP0035-02',
            'GSP0035-01',
            'GSX0010-01',
            'GSX0019-01',
            'GSM0028-01',
            'GER2020-01',
            'GER2021-01',
            'GNL2023-01',
            'GER2018-01',
            'GER2017-01',
            'GSP0040-01',
            'GST0043-01',
            'GSM0020-01',
            'GSM0023-02',
            'GSP0004-01',
            'GSX0007-01',
            'GSM0038-01',
            'GSM0038-02',
            'GSX0025-01',
            'GSP0016-01',
            'GUOM01456-01',
            'GSX0021-01',
            'GUOX01452-02',
            'GSP0002-01',
            'GST0031-01',
            'GSP0014-03',
            'GSX0021-02',
            'GSX0029-01',
            'GST0032-02',
            'GSP0006-02',
            'GT0374-01',
            'GX0375-01',
            'GSM0008-02',
            'GM0358-01',
            'GSX0009-01',
            'GM0353-01',
            'GSX0030-01',
            'GSW0033-01',
            'GSM0024-01',
            'GSM0024-02',
            'GUOM01456-02',
            'GA0376-01',
            'GSX0025-02',
            'GSM0034-02',
            'GSM0034-01',
            'GOT01462-01',
            'GUOP01472-01',
            'GOT01475-01',
            'GUOP01470-01',
            'GUOP01465-02',
            'GUOP01465-01',
            'GUOT01476-01',
            'GUOM01467-01',
            'GUOM01471-01',
            'GOX01474-01',
            'GOT01453-01',
            'GUOM01464-01',
            'GOA01481-01',
            'GER2007-01',
            'GER2006-01',
            'GER2004-01',
            'GUOA01466-02',
            'FA0792-03',
            'GOA01480-01',
            'GOA01479-01',
            'GOA01477-01',
            'GOA01482-01',
            'GOA01483-01',
            'GOA01478-01',
            'GOT01501-01',
            'GWA01605-01',
            'GWA01605-02',
            'GWA01610-02',
            'GWA01613-01',
            'GWA01613-02',
            'GWA01613-03',
            'GWA01614-02',
            'GWA01619-01',
            'GWA01620-01',
            'GWA01624-01',
            'GWA01624-03',
            'GWA01656-01',
            'GWA01659-01',
            'GWA01659-02',
            'GWA01662-01',
            'GWA01669-02',
            'GWA01675-01',
            'GUWM01671-01',
            'GWA01610-01',
            'GWM01683-01',
            'GWM01683-02',
            'GWA01651-01',
            'GWA01651-02',
            'GWA01689-01',
            'GWA01689-02',
            'GWA01691-01',
            'GUWA01700-01',
            'GUWA01700-02',
            'GWA01706-01',
            'GWA01715-01',
            'GWA01724-01',
            'GWA01726-01',
            'GUWN01731-01',
            'GUWN01732-01',
            'GUWN01732-02',
            'GUWX01717-02',
            'GWA01707-01',
            'GWA01708-01',
            'GWA01708-02',
            'GWA01709-01',
            'GWA01709-02',
            'GWA01710-01',
            'GWA01710-02',
            'GWA01714-01',
            'GWA01676-01',
            'GUWM01678-02',
            'GUOX01468-01',
            'GUOX01468-02',
            'GOM01523-02',
            'GOA01525-01',
            'GOA01525-02',
            'GOA01524-01',
            'GOA01524-02',
            'GFP103265-01',
            'GFP100915-01',
            'GUOX01491-01',
            'GUOX01491-02',
            'GUOX01491-03',
            'GOA01526-01',
            'GOA01527-01',
            'GOA01528-01',
            'GOA01529-01',
            'GOP01541-01',
            'GOA01546-01',
            'GOA01546-02',
            'GOA01547-01',
            'GWA01740-01',
            'GWA01741-01',
            'GWA01741-02',
            'GWA01741-03',
            'GWX01749-01',
            'GWX01749-02',
            'GOA01548-01',
            'GOA01549-01',
            'GOA01550-01',
            'GOA01551-01',
            'GOA01552-01',
            'GOA01552-02',
            'GOA01552-03',
            'GOA01553-01',
            'GOA01554-01',
            'Chain-G04',
            'GER2010-01',
            'GER2012-01',
            'GER2022-01',
            'GWA01746-01',
            'GWA01746-02',
            'GOT01555-02',
            'GOT01555-01',
            'GOX01557-02',
            'GOX01558-01',
            'GOX01558-02',
            'GOX01559-01',
            'GOA01562-01',
            'GOA01563-01',
            'GWA01677-02',
            'GWA01742-03',
            'GOX01567-01',
            'GOA01568-01',
            'GWA01748-01',
            'GWP01736-02',
            'GUWM01737-01',
            'GUWM01738-01',
            'GOA01573-01',
            'GOA01573-02',
            'GOM01582-01',
            'GOM01582-02',
            'GOX01583-02',
            'GOA01569-01',
            'GOA01571-01',
            'GOA01572-01',
            'GOA01758-01',
            'GOM01586-01',
            'GOX01766-02',
            'GOA01759-01',
            'GOA01759-02',
            'GOA01765-01',
            'GOA01769-01',
            'GOA01770-01',
            'GOA01770-02',
            'GOA01760-01',
            'GOA01587-01',
            'GOX01771-01',
            'GOX01771-02',
            'GOP01772-01',
            'GOP01772-02',
            'GWA01655-02',
            'GOA01773-01',
            'GOA01773-02',
            'GOA01775-01',
            'GOA01810-01',
            'GOA01777-02',
            'GOA01777-01',
            'GOA01788-01',
            'GOA01486-01',
            'GOA01509-01',
            'GOA01509-02',
            'GOA01486-02',
            'GUOA01484-02',
            'GUOA01513-01',
            'GUOA01485-02',
            'GUOA01485-03',
            'GOA01785-01',
            'GOM01490-01',
            'GOA01515-01',
            'GOA01588-02',
            'GOA01792-02',
            'GOA01793-02',
            'GOA01793-03',
            'GOA01794-01',
            'GOA01779-01',
            'GOT01791-02',
            'GT0374-02',
            'GOA01789-01',
            'GOT01790-01',
            'GOA01801-01',
            'GOA01802-01',
            'GOA01495-01',
            'GOA01803-01',
            'GOA01803-02',
            'GOA01809-01',
            'GOA01806-01',
            'GOA01806-02',
            'GOA01821-01',
            'GOA01774-02',
            'GOA01781-01',
            'GOA01782-01',
            'GOA01811-01',
            'GOA01811-02',
            'GOT01812-01',
            'GOM01813-01',
            'GOA01818-01',
            'GOA01819-01',
            'GOA01817-01',
            'GOA01817-02',
            'GOA01820-01',
            'GOA01820-02',
            'GOA01820-03',
            'GOA01823-01',
            'GOA01823-02',
            'GOA01815-01',
            'GOA01829-03',
            'GOA01829-04',
            'GOA01838-01',
            'GOA01822-02',
            'GOA01824-01',
            'GOA01824-02',
            'GOA01827-01',
            'GOA01828-01',
            'VFT0271-04',
            'GOA01842-02',
            'GOA01816-01',
            'GOA01825-01',
            'GOA01825-02',
            'GOA01826-01',
            'GOA01831-01',
            'GOA01829-01',
            'GOA01829-02',
            'GOM01837-01',
            'GOM01839-01',
            'GOM01839-02',
            'GOA01855-01',
            'GOA01855-02',
            'GOM01849-01',
            'GOM01849-02',
            'GOA01850-02',
            'GOA01852-01',
            'GOA01852-02',
            'GOP01861-01',
            'GOP01861-02',
            'GOA01857-01',
            'GOA01857-02',
            'GOA01857-03',
            'GOA01857-04',
            'GOP01886-01',
            'GOP01888-01',
            'GOP01888-02',
            'GOX01893-01',
            'GOX01893-02',
            'GOP01891-02',
            'GOP01891-03',
            'GOP01891-05',
            'GER6004-01',
            'GER6007-01',
            'GOP01898-01',
            'GOP01929-01',
            'GOP01917-01',
            'GOP01917-02',
            'GOP01931-03',
            'GOP01954-02',
            'VFP0288-03',
            'GOP01979-02',
            'GOP01957-01',
            'GOP01980-01',
            'GOP01981-01',
            'GOP01981-02',
            'GOP01933-03',
            'GOP01984-01',
            'GOP01984-02',
            'GOP01984-03',
            'GOP01985-03',
            'GACC6032-03',
            'GOP01988-01',
            'FP0563-04',
            'GACC6033-01',
            'GOP02012-02',
            'GACC6035-01',
            'GACC6035-02',
            'GACC6029-01',
            'GACC6031-01',
            'GER6021-01',
            'GNL6026-01',
            'GACC6037-01',
            'GOX02031-01',
            'GOP02028-02',
            'VFP0165-03',
            'GOX02045-01',
            'GOX02045-02',
            'GOX02046-01',
            'GOP02048-01',
            'GOP02047-01',
            'GOM02050-01',
            'GOA02051-01',
            'GOP02049-01',
            'GOP02049-02',
            'GOT02067-01',
            'GOP02073-02',
            'GOP02074-01',
            'GOA02070-01',
            'GOA02070-02',
            'GOP02082-01',
            'GOP02082-03',
            'GOA02071-01',
            'GOA02008-03',
            'GOA02008-04',
            'GOX02089-02',
            'GOX02089-03',
            'GOX02089-04',
            'GOP02081-01',
            'GOP02094-01',
            'GOT02116-02',
            'GOM02122-02',
            'GOM02122-03',
            'GOM02118-01',
            'GOT02138-01',
            'GOM02122-01',
            'GOT02138-02',
            'GOX02143-01',
            'GOP02139-01',
            'GACC06053-01',
            'GACC06054-01',
            'HP0661-01',
            'GTI0534-01',
            'GNL374660-01',
            'GJS074792-01',
            'GDI023962-02',
            'GGI497414-01',
            'GZI0994-01',
            'GZI0994-02',
            'GER083286-01',
            'GER007444-01',
            'GBL149951-01',
            'GER521681-01',
            'GNL696787-01',
            'GOP903993-04',
            'GOA094582-01',
            'GOM989266-02',
            'GOM676044-01',
            'GER126827-01',
            'GOM439217-01',
            'GOP029333-01',
            'GOP006101-01',
            'GOP776759-01',
            'GOP806061-01',
            'GOP806061-02',
            'GOM389865-01',
            'GOM389865-03',
            'GOX992237-02',
            'GOP903993-05',
            'GOT652438-03',
            'GOA163927-01',
            'GOX019285-01',
            'GOX019285-02',
            'GOT716334-01',
            'GOP249168-01',
            'GOP249168-02',
            'GOP598864-02',
            'GOP327448-02',
            'GOP249168-04',
            'GOP249168-05',
            'GOP233677-01',
            'GOP233677-02',
            'GOA034393-01',
            'GOA034393-02',
            'GOA034393-03',
            'GOP01958-03',
            'GOM202513-01',
            'GOP675215-03',
            'GOP008768-01',
            'GOP008768-02',
            'GOP707959-02',
            'GOA244274-01',
            'GOA244274-03',
            'GOA199298-01',
            'GOP687951-02',
            'GOI095135-01',
            'GOX687713-01',
            'GOP005860-01',
            'GOX123188-01',
            'GOX976871-04',
            'GOP008160-01',
            'GOP099710-01',
            'GOP099710-04',
            'GOP099710-05',
            'GWA454518-06',
            'GOP006413-02',
            'GSA137325-02',
            'GOP075866-04');
        $data = array_unique($data);
        foreach ($data as $key=>$val){
            $Sample->startTrans();
            $purchaseSampleWorkOrder->startTrans();
            $purchaseSampleWorkOrderItem->startTrans();
            try {
                //将样品间信息假删除
                $map['sku'] = $val;
                $value['is_del'] =2;
                $out_stock_num = $Sample->where(['sku'=>$val])->value('stock');
                $res = $Sample->allowField(true)->isUpdate(true, $map)->save($value);
                if ($res){
                    //添加出库单
                    $addValue['location_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);;
                    $addValue['createtime'] = date('Y-m-d H:i:s',time());
                    $addValue['create_user'] = session('admin.nickname');
                    $addValue['description'] = '样品间商品批量出库';
                    $addValue['status'] = 3;
                    $outStockId = $purchaseSampleWorkOrder->insertGetId($addValue);
                    if ($outStockId){
                        //出库商品信息表对应信息
                        $outStockItemValue['sku'] = $val;
                        $outStockItemValue['stock'] = $out_stock_num;
                        $outStockItemValue['parent_id'] = $outStockId;
                        $purchaseSampleWorkOrderItem ->insert($outStockItemValue);
                    }
                }
                $Sample->commit();
                $purchaseSampleWorkOrder->commit();
                $purchaseSampleWorkOrderItem->commit();
            }catch (ValidateException $e) {
                $Sample->rollback();
                $purchaseSampleWorkOrder->rollback();
                $purchaseSampleWorkOrderItem->rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                $Sample->rollback();
                $purchaseSampleWorkOrder->rollback();
                $purchaseSampleWorkOrderItem->rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                $Sample->rollback();
                $purchaseSampleWorkOrder->rollback();
                $purchaseSampleWorkOrderItem->rollback();
                $this->error($e->getMessage(), [], 408);
            }
        }
    }

}

