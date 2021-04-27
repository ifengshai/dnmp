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
                $smap['location'] = ['like', '%'.$filter['location'].'%'];
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
        $filePath = ROOT_PATH.DS.'public'.DS.$file;
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
                    fwrite($fp, $line."\n");
                } else {
                    fwrite($fp, '"'.str_replace(['"', ','], ['""', '","'], $line)."\"\n");
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
        $location_number = 'IN2'.date('YmdHis').rand(100, 999).rand(100, 999);
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
        $filePath = ROOT_PATH.DS.'public'.DS.$file;
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
                    fwrite($fp, $line."\n");
                } else {
                    fwrite($fp, '"'.str_replace(['"', ','], ['""', '","'], $line)."\"\n");
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
            $str = ' SKU:'.implode(',', $no_location).'这些sku库位号有误';
        }
        if ($result_index == 1) {
            $this->success('导入成功！！'.$str);
        } else {
            $this->error('导入失败！！'.$str);
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
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name.'.add' : $name) : $this->modelValidate;
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
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name.'.edit' : $name) : $this->modelValidate;
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
     * @param  string  $ids
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
                $smap['sku'] = ['like', '%'.$filter['sku'].'%'];
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
        $location_number = 'IN2'.date('YmdHis').rand(100, 999).rand(100, 999);
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
                Db('purchase_sample_workorder_item')->where('sku', 'in', $diff_sku_arr)->where('parent_id',
                    $ids)->delete();
                //处理商品
                foreach ($params['goods'] as $value) {
                    //判断入库表中是否有该商品
                    $is_exist = Db('purchase_sample_workorder_item')->where([
                        'sku' => $value['sku'], 'parent_id' => $ids
                    ])->value('id');
                    $workorder_item = array();
                    if ($is_exist) {
                        //更新
                        $workorder_item['stock'] = $value['stock'];
                        Db::name('purchase_sample_workorder_item')->where([
                            'sku' => $value['sku'], 'parent_id' => $ids
                        ])->update($workorder_item);
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
            $this->error('SKU:'.implode(',', array_unique($location_error_sku)).'库位号不存在，无法审核！！');
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
                        $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id',
                            $id)->order('id asc')->select();
                        foreach ($product_arr as $item) {
                            $is_exist = $this->sample->where('sku', $item['sku'])->value('id');
                            if ($is_exist) {
                                $this->sample->where('sku', $item['sku'])->inc('stock', $item['stock'])->inc('lend_num',
                                    1)->update(['is_lend' => 1]);
                                //自动生成一条样品借出记录
                                $lendlog['status'] = 2;
                                $lendlog['create_user'] = session('admin.nickname');
                                $lendlog['createtime'] = date('Y-m-d H:i:s', time());
                                $lendlog['sku'] = $item['sku'];
                                $lendlog['lend_num'] = 1;
                                $this->samplelendlog->insert($lendlog);
                                $newProductDesign->insert([
                                    'sku' => $item['sku'], 'status' => 1, 'create_time' => date('Y-m-d H:i:s', time()),
                                    'update_time' => date('Y-m-d H:i:s', time())
                                ]);
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
                                $newProductDesign->insert([
                                    'sku' => $item['sku'], 'status' => 1, 'create_time' => date('Y-m-d H:i:s', time()),
                                    'update_time' => date('Y-m-d H:i:s', time())
                                ]);
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
                ->order('a.'.$sort, $order)
                ->count();

            $list = $this->sampleworkorder
                ->alias('a')
                ->field('a.id,a.location_number,a.status,a.create_user,a.createtime')
                ->join(['fa_purchase_sample_workorder_item' => 'b'], 'a.id = b.parent_id', 'LEFT')
                ->where($where)
                ->where($where_arr)
                ->group('a.id')
                ->order('a.'.$sort, $order)
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
        $location_number = 'OUT2'.date('YmdHis').rand(100, 999).rand(100, 999);
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
                Db('purchase_sample_workorder_item')->where('sku', 'in', $diff_sku_arr)->where('parent_id',
                    $ids)->delete();
                //处理商品
                foreach ($params['goods'] as $key => $value) {
                    $is_exist = Db::name('purchase_sample_workorder_item')->where([
                        'sku' => $value['sku'], 'parent_id' => $ids
                    ])->value('id');
                    if ($is_exist) {
                        //更新
                        Db::name('purchase_sample_workorder_item')->where([
                            'sku' => $value['sku'], 'parent_id' => $ids
                        ])->update(['stock' => $value['stock']]);
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
                    $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id',
                        $id)->order('id asc')->select();
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
                Db::name('purchase_sample_lendlog')->where(['id' => $ids])->update([
                    'lend_num' => $params['lend_num'], 'sku' => $params['sku']
                ]);

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
                $skus = Db::name('purchase_sample_lendlog')->field('sum(lend_num) lend_num,sku')->where('id', 'in',
                    $ids)->group('sku')->select();
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
                    $this->error('sku：'.$sku_str.'商品数量不足，无法借出');
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
    public function batchDelivery()
    {
        $Sample = new \app\admin\model\purchase\Sample();
        $purchaseSampleWorkOrder = new SampleWorkorder();
        $purchaseSampleWorkOrderItem = new SampleWorkorderItem();
        $data = array('FX0067-01', 'FT0069-02', 'FX0070-01');
        $data = array_unique($data);
        $Sample->startTrans();
        $purchaseSampleWorkOrder->startTrans();
        $purchaseSampleWorkOrderItem->startTrans();
        foreach ($data as $key => $val) {
            try {
                //将样品间信息假删除
                $map['sku'] = $val;
                $value['is_del'] = 2;
                $out_stock_num = $Sample->where(['sku' => $val])->value('stock');
                $res = $Sample->where($map)->update($value);
                if ($res) {
                    //添加出库单
                    $addValue['location_number'] = 'OUT'.date('YmdHis').rand(100, 999).rand(100, 999);;
                    $addValue['createtime'] = date('Y-m-d H:i:s', time());
                    $addValue['create_user'] = session('admin.nickname');
                    $addValue['description'] = '样品间商品批量出库';
                    $addValue['status'] = 3;
                    $outStockId = $purchaseSampleWorkOrder->insertGetId($addValue);
                    if ($outStockId) {
                        //出库商品信息表对应信息
                        $outStockItemValue['sku'] = $val;
                        $outStockItemValue['stock'] = $out_stock_num;
                        $outStockItemValue['parent_id'] = $outStockId;
                        $purchaseSampleWorkOrderItem->insert($outStockItemValue);
                    }
                }
                $a[] = $val;
                $Sample->commit();
                $purchaseSampleWorkOrder->commit();
                $purchaseSampleWorkOrderItem->commit();
            } catch (ValidateException $e) {
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

