<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use app\admin\model\warehouse\StockHouse;
use app\admin\model\itemmanage;
use app\admin\model\itemmanage\Item;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * SKU库位绑定
 *
 * @icon fa fa-circle-o
 */
class StockSku extends Backend
{

    /**
     * StockSku模型对象
     * @var \app\admin\model\warehouse\StockSku
     */
    protected $model = null;

    protected $noNeedRight = ['import'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\StockSku;
        $this->assignconfig('warehourseStock', getStockHouse());
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
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['area_coding']) {
                $area_id = Db::name('warehouse_area')->where('coding', $filter['area_coding'])->value('id');
                $all_store_id = Db::name('store_house')->where('area_id', $area_id)->column('id');
                $map['storehouse.id'] = ['in', $all_store_id];
                unset($filter['area_coding']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['storehouse.status']) {
                $map['storehouse.status'] = ['=', $filter['storehouse.status']];
                unset($filter['storehouse.status']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->with(['storehouse', 'warehouseStock'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['storehouse', 'warehouseStock'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //查询商品SKU
            $item = new \app\admin\model\itemmanage\Item;
            $arr = $item->where('is_del', 1)->column('name,is_open', 'sku');
            //所有库区编码id
            $area_coding = Db::name('warehouse_area')->column('coding', 'id');
            foreach ($list as $k => $row) {
                $row->getRelation('storehouse')->visible(['coding', 'library_name', 'status']);
                $list[$k]['name'] = $arr[$row['sku']]['name'];
                $list[$k]['is_open'] = $arr[$row['sku']]['is_open'];
                $store_house = Db::name('store_house')->where('id', $row['store_id'])->find();
                //获得库位所属库区编码
                $list[$k]['area_coding'] = $area_coding[$store_house['area_id']];
                $list[$k]['area_status'] = Db::name('warehouse_area')->where('id', $store_house['area_id'])->value('status');
            }

            $list = collection($list)->toArray();

            $result = ["total" => $total, "rows" => $list];

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
                empty($params['sku']) && $this->error('sku不能为空！');

                $store_house = Db::name('store_house')->where('id', $params['store_id'])->where('stock_id', $params['stock_id'])->find();
                //查询拣货区ID
                $warehouse_area = Db::name('warehouse_area')->where('id', $params['area_id'])->find();
                //拣货货区一个库位号只能有一个sku
                //拣货区一个sku 只能有一个库位
                if ($warehouse_area['type'] != 2) {
                    //判断选择的库位是否已存在
                    $map['a.store_id'] = $params['store_id'];//库位id
                } else {
                    $map['b.area_id'] = $params['area_id'];
                }

                if (!$params['area_id']) {
                    $this->error('库区不能为空！！');
                }
                $map['a.sku'] = $params['sku'];
                $map['a.is_del'] = 1;
                $map['a.stock_id'] = $params['stock_id'];
                $count = $this->model->alias('a')->where($map)->join(['fa_store_house' => 'b'], 'a.store_id=b.id')->count();

                if ($count > 0) {
                    $this->error('库位已绑定！！');
                }
                if ($store_house['area_id'] != $params['area_id']) {
                    $this->error('库位不在当前选择库区！！');
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
        //查询库区数据
        $data1 = Db::name('warehouse_area')->column('coding', 'id');
        $this->assign('data1', $data1);
        //查询库位数据
        $data = (new StockHouse())->getStockHouseData();
        $this->assign('data', $data);

        //查询商品SKU数据
        $info = (new Item())->getItemSkuInfo();
        $this->assign('info', $info);

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $row['area_id'] = Db::name('store_house')->where('id', $row['store_id'])->value('area_id');
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
                empty($params['sku']) && $this->error('sku不能为空！');
                if (!$params['area_id']) {
                    $this->error('库区不能为空！！');
                }

                //查询库区
                $warehouse_area = Db::name('warehouse_area')->where('id', $params['area_id'])->find();
                //拣货货区一个库位号只能有一个sku
                //拣货区一个sku 只能有一个库位
                if ($warehouse_area['type'] != 2) {
                    //判断选择的库位是否已存在
                    $map['a.store_id'] = $params['store_id'];//库位id
                } else {
                    $map['b.area_id'] = $params['area_id'];
                }

                $map['a.sku'] = $params['sku'];
                $map['a.is_del'] = 1;
                $map['a.stock_id'] = $params['stock_id'];
                $map['a.id'] = ['<>', $row->id];
                $count = $this->model->alias('a')->where($map)->join(['fa_store_house' => 'b'], 'a.store_id=b.id')->count();
                if ($count > 0) {
                    $this->error('库位已绑定！！');
                }

                $store_house = Db::name('store_house')->where('id', $params['store_id'])->where('stock_id', $params['stock_id'])->find();
                if ($store_house['area_id'] != $params['area_id']) {
                    $this->error('库位不在当前选择库区！！');
                }
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
        //查询库区数据
        $data1 = Db::name('warehouse_area')->column('coding', 'id');
        $this->assign('data1', $data1);
        //查询库位数据
        $data = (new StockHouse())->getStockHouseData();
        $this->assign('data', $data);

        //查询商品SKU数据
        $info = (new Item())->getItemSkuInfo();
        $this->assign('info', $info);
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }

    /**
     * sku库位绑定批量导入
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
        $listName = ['库区编码', '库位编码', 'SKU', '实体仓id'];
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

        foreach ($data as $k => $v) {
            if (empty($v[3]) || empty($v[2]) || empty($v[1]) || empty($v[0])) {
                $this->error('参数不能为空，请检查！！');
            }
            $warehouse_area = Db::name('warehouse_area')->where('coding', $v[0])->where('stock_id', $v[3])->find();
            if (empty($warehouse_area)) {
                $this->error('sku:' . $v[2] . '库区编码错误，请检查！！');
            }
            $store_house = Db::name('store_house')->where('coding', $v[1])->where('area_id', $warehouse_area['id'])->where('stock_id', $v[3])->find();
            if (empty($store_house)) {
                $this->error('sku:' . $v[2] . '库位编码错误，请检查！！');
            }
            //拣货货区一个库位号只能有一个sku
            if ($warehouse_area['type'] !== 2) {
                $map['sku'] = $v[2];
            }
            //判断选择的库位是否已存在
            $map['store_id'] = $store_house['id'];//库位id
            $map['is_del'] = 1;
            $map['stock_id'] = $store_house['stock_id'];
            $count = Db::name('store_sku')->where($map)->count();
            if ($count > 0) {
                $this->error('sku:' . $v[2] . '库位已绑定！！');
            }
        }
        foreach ($data as $k => $v) {
            $area_id = Db::name('warehouse_area')->where('stock_id', $v[3])->where('coding', $v[0])->value('id');
            $store_house = Db::name('store_house')->where('stock_id', $v[3])->where('coding', $v[1])->where('area_id', $area_id)->find();
            $result = Db::name('store_sku')->insert(['sku' => $v[2], 'store_id' => $store_house['id'], 'stock_id' => $store_house['stock_id'], 'createtime' => date('y-m-d h:i:s', time()), 'create_person' => $this->auth->username]);
        }
        if ($result) {
            $this->success('导入成功！！');
        } else {
            $this->error('导入失败！！');
        }
    }
}
