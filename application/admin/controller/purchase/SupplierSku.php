<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use fast\Alibaba;
use think\Loader;
use fast\Auth;

/**
 * 供应商sku管理
 *
 * @icon fa fa-circle-o
 */
class SupplierSku extends Backend
{

    /**
     * SupplierSku模型对象
     * @var \app\admin\model\purchase\SupplierSku
     */
    protected $model = null;

    protected $relationSearch = true;

    protected $searchFields = 'id,sku,supplier_sku,create_person,supplier.supplier_name';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\SupplierSku;
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
                ->with(['supplier'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['supplier'])
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
                    $skus = $this->request->post("skus/a");
                    //执行过滤空值
                    array_walk($skus, 'trim_value');
                    if (count(array_filter($skus)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $supplier_skus = $this->request->post("supplier_sku/a");
                    array_walk($supplier_skus, 'trim_value');
                    if (count(array_filter($supplier_skus)) < 1) {
                        $this->error('供应商sku不能为空！！');
                    }  

                    //是否为大货
                    if ($params['is_big_goods'] == 1 && !$params['product_cycle']) {
                        $this->error('生产周期不能为空');
                    } elseif ($params['is_big_goods'] == 0 && !$params['product_cycle']) {
                        $params['product_cycle'] = 7;
                    }

                    $link = $this->request->post("link/a");

                    $data = [];
                    foreach (array_filter($skus) as $k => $v) {
                        //判断是否重复
                        $where['sku'] = $v;
                        $where['supplier_id'] = $params['supplier_id'];
                        $count = $this->model->where($where)->count();
                        if ($count > 0) {
                            $this->error('记录已存在！！SKU:' . $v);
                        }

                        //供应商sku  和产品sku 必须为真 否则自动过滤
                        if ($v && $supplier_skus[$k]) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = $supplier_skus[$k];
                            $data[$k]['link'] = $link[$k];
                            $data[$k]['supplier_id'] = $params['supplier_id'];
                            $data[$k]['label'] = $params['label'];
                            $data[$k]['is_big_goods'] = $params['is_big_goods'];
                            $data[$k]['product_cycle'] = $params['product_cycle'];
                            $data[$k]['create_person'] = session('admin.nickname');
                            $data[$k]['createtime'] = date('Y-m-d H:i:s', time());
                        }

                        //如果选择主供应商 则同SKU下 其他记录设置为辅供应商
                        if ($params['label'] == 1) {
                            $map['sku'] = $v;
                            $this->model->where($map)->update(['label' => 0]);
                        } else {
                            //查询此sku 是否有主供应商 如果没有 则当前的默认为主供货商
                            $map['sku'] = $v;
                            $map['label'] = 1;
                            $count = $this->model->where($map)->count();
                            if ($count < 1) {
                                $data[$k]['label'] = 1;
                            }
                        }
                    }
                    $result = $this->model->allowField(true)->saveAll($data);
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $supplier = $supplier->getSupplierData();
        $this->assign('supplier', $supplier);
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
//            dump($params);die;
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

                    //判断是否重复
                    $where['sku'] = $params['sku'];
                    $where['supplier_id'] = $params['supplier_id'];
                    $count = $this->model->where($where)->count();
                    if ($count > 1) {
                        $this->error('记录已存在！！SKU:' . $params['sku']);
                    }
                    
                    //是否为大货
                    if ($params['is_big_goods'] == 1 && !$params['product_cycle']) {
                        $this->error('生产周期不能为空');
                    } elseif ($params['is_big_goods'] == 0 && !$params['product_cycle']) {
                        $params['product_cycle'] = 7;
                    }

                    //如果选择主供应商 则同SKU下 其他记录设置为辅供应商
                    if ($params['label'] == 1) {
                        $map['sku'] = $params['sku'];
                        $this->model->allowField(true)->isUpdate(true, $map)->save(['label' => 0]);
                    } else {
                        //查询此sku 是否有主供应商 如果没有 则当前的默认为主供货商
                        $map['sku'] = $params['sku'];
                        $map['label'] = 1;
                        $map['id'] = ['<>', $ids];
                        $count = $this->model->where($map)->count();
                        if ($count < 1) {
                            $params['label'] = 1;
                        }
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
        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $supplier = $supplier->getSupplierData();
        $this->assign('supplier', $supplier);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 修改供应商sku状态
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
     * 导入
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
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }
        //加载文件
        $insert = [];

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $supplier = $supplier->getSupplierData();

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

            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);

                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                    if ($k == '供应商名称' && $v) {
                        if (array_search($v, $supplier)) {
                            $row['supplier_id'] = array_search($v, $supplier);
                        } else {
                            $this->error($v . '供应商未匹配到！！');
                        }
                    }
                }
                if ($row) {
                    $insert[] = array_filter($row);
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }


        try {
            //是否包含admin_id字段
            $has_admin_id = false;
            foreach ($fieldArr as $name => $key) {
                if ($key == 'admin_id') {
                    $has_admin_id = true;
                    break;
                }
            }
            if ($has_admin_id) {
                $auth = Auth::instance();
                foreach ($insert as &$val) {
                    if (!isset($val['admin_id']) || empty($val['admin_id'])) {
                        $val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
                    }
                    $val['create_person'] = session('admin.nickname');
                    $val['createtime'] = date('Y-m-d H:i:s', time());
                }
            } else {
                foreach ($insert as &$val) {
                    $val['create_person'] = session('admin.nickname');
                    $val['createtime'] = date('Y-m-d H:i:s', time());
                }
            }
            unset($val);
            $this->model->saveAll($insert);
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    //匹配1688 SKUid
    public function matching($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (!$row['link']) {
            $this->error('请先补充1688对应商品链接！！', url('index'));
        }
        if ($this->request->isAjax()) {
            $ids = $this->request->get('ids');
            $row = $this->model->get($ids);
            //获取缓存名称
            $controllername = Loader::parseName($this->request->controller());
            $actionname = strtolower($this->request->action());
            $path = str_replace('.', '1', $controllername) . '_' . $actionname . '_' . md5($row['link']);
            //是否存在缓存
            $result = cache($path);
            if ($row['link'] && !$result) {
                //截取出商品id
                $name = parse_url($row['link']);
                preg_match('/\d+/', $name['path'], $goodsId);
                //先添加到铺货列表
                Alibaba::getGoodsPush([$goodsId[0]]);
                //获取商品详情
                $result = Alibaba::getGoodsDetail($goodsId[0]);

                cache($path, $result, 3600);
            }

            $list = [];
            if (!$result->productInfo->skuInfos) {
                $result = array("total" => 0, "rows" => $list);
                return json($result);
            }

            foreach ($result->productInfo->skuInfos as $k => $v) {
                $list[$k]['id'] = $k + 1;
                $list[$k]['title'] = $result->productInfo->subject;
                if (count($v->attributes) > 1) {
                    $list[$k]['color'] = $v->attributes[0]->attributeValue . ':' . $v->attributes[1]->attributeValue;
                } else {
                    $list[$k]['color'] = $v->attributes[0]->attributeValue;
                }

                $list[$k]['cargoNumber'] = $v->cargoNumber;
                $list[$k]['price'] = @$v->price ? @$v->price : @$v->consignPrice;
                $list[$k]['skuId'] = $v->skuId;
                $list[$k]['parent_id'] = $ids;
            }

            $result = array("total" => count($list), "rows" => $list);

            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }

    //绑定skuid
    public function matchingSkuId()
    {
        $ids = $this->request->get('parent_id');
        $skuId = $this->request->get('skuId');
        $res = $this->model->save(['skuid' => $skuId, 'is_matching' => 1], ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error(__('No rows were updated'));
        }
    }
}
