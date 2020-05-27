<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use fast\Alibaba;
use app\admin\model\NewProduct;
use app\admin\model\purchase\Supplier;
use app\admin\model\purchase\SupplierSku;
use think\Cache;
use fast\Kuaidi100;
use app\admin\model\purchase\Purchase_order_pay;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


/**
 * 采购单管理
 *
 * @icon fa fa-circle-o
 */
class PurchaseOrder extends Backend
{

    /**
     * PurchaseOrder模型对象
     * @var \app\admin\model\purchase\PurchaseOrder
     */
    protected $model = null;

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = ['getAlibabaPurchaseOrder', 'callback', 'batch_export_xls'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\PurchaseOrder;
        $this->purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $this->batch = new \app\admin\model\purchase\PurchaseBatch();
        $this->batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
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
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                $ids = $this->purchase_order_item->where($smap)->column('purchase_id');
                $map['purchase_order.id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['supplier'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['supplier'])
                ->where($where)
                ->where($map)
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

                    $sku = $this->request->post("sku/a");
                    //执行过滤空值
                    array_walk($sku, 'trim_value');
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);

                    //添加采购单商品信息
                    if ($result !== false) {
                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");
                        $num = $this->request->post("purchase_num/a");
                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = trim($supplier_sku[$k]);
                            $data[$k]['product_name'] = $product_name[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            $data[$k]['purchase_id'] = $this->model->id;
                            $data[$k]['purchase_order_number'] = $params['purchase_number'];
                        }
                        //批量添加
                        $this->purchase_order_item->saveAll($data);

                        //添加分批数据
                        $batch_arrival_time = $this->request->post("batch_arrival_time/a");
                        $batch_sku = $this->request->post("batch_sku/a");
                        $arrival_num = $this->request->post("arrival_num/a");

                        //判断是否有分批数据
                        if ($batch_arrival_time && count($batch_arrival_time) > 0) {
                            $i = 0;
                            foreach (array_filter($batch_arrival_time) as $k => $v) {
                                $batch_data['purchase_id'] = $this->model->id;
                                $batch_data['arrival_time'] = $v;
                                $batch_data['batch'] = $i + 1;
                                $batch_data['create_person'] = session('admin.nickname');
                                $batch_data['create_time'] = date('Y-m-d H:i:s');
                                $batch_id = $this->batch->insertGetId($batch_data);
                                $i++;
                                $list = [];
                                foreach ($batch_sku[$k] as $key => $val) {
                                    if (!$val || !$arrival_num[$k][$key]) {
                                        continue;
                                    }
                                    $list[$key]['sku'] = $val;
                                    $list[$key]['arrival_num'] = $arrival_num[$k][$key];
                                    $list[$key]['purchase_batch_id'] = $batch_id;
                                }

                                $this->batch_item->saveAll($list);
                            }
                        }
                    }

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
                    $this->success('添加成功！！',  url('PurchaseOrder/index'));
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //查询新品数据
        $new_product_ids = $this->request->get('new_product_ids');
        if ($new_product_ids) {
            //查询所选择的数据
            $where['new_product.id'] = ['in', $new_product_ids];
            $row = (new NewProduct())->where($where)->with(['newproductattribute'])->select();
            $row = collection($row)->toArray();
            foreach ($row as $v) {
                if ($v['item_status'] != 1) {
                    $this->error(__('只有待选品状态能够创建！！'), url('new_product/index'));
                }
            }

            //提取供应商id
            $supplier = array_unique(array_column($row, 'supplier_id'));
            if (count($supplier) > 1) {
                $this->error(__('必须选择相同的供应商！！'), url('new_product/index'));
            }
            $this->assign('row', $row);
            $this->assign('is_new_product', 1);
        }


        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        //生成采购编号
        $purchase_number = 'PO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('purchase_number', $purchase_number);
        return $this->view->fetch();
    }

    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['purchase_status'] != 0) {
                $this->error('此商品状态不能提交审核');
            }

            //查询明细数据
            $list = $this->purchase_order_item
                ->where(['purchase_id' => ['in', $id]])
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');

            //查询存在产品库的sku
            $item = new \app\admin\model\itemmanage\Item;
            $skus = $item->where(['sku' => ['in', $skus]])->column('sku');

            if ($row['is_new_product'] == 0) {
                foreach ($list as $v) {
                    if (!in_array($v['sku'], $skus)) {
                        $this->error('此sku:' . $v['sku'] . '不存在！！');
                    }
                }
            }

            $map['id'] = $id;
            $data['purchase_status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('提交审核成功');
            } else {
                $this->error('提交审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /**
     * 异步获取合同数据
     */
    public function getContractData()
    {
        $id = input('id');
        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $data = $contract->get($id);
        //查询合同商品信息
        $contract_item = new \app\admin\model\purchase\ContractItem;
        $map['contract_id'] = $id;
        $item = $contract_item->where($map)->select();
        if ($item) {
            $data->item = $item;
        }
        if ($data) {
            $this->success('', '', $data);
        } else {
            $this->error();
        }
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
        //判断状态是否为新建
        if ($row['purchase_status'] > 0 && $row['purchase_type'] == 1) {
            $this->error('只有新建状态才能编辑！！', url('index'));
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

                    $sku = $this->request->post("sku/a");
                    //执行过滤空值
                    array_walk($sku, 'trim_value');
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $result = $row->allowField(true)->save($params);

                    //添加合同产品
                    if ($result !== false) {
                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");
                        $num = $this->request->post("purchase_num/a");
                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");
                        $item_id = $this->request->post("item_id/a");

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = trim($supplier_sku[$k]);
                            $data[$k]['product_name'] = $product_name[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['purchase_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->purchase_order_item->allowField(true)->saveAll($data);

                        //添加分批数据
                        $batch_arrival_time = $this->request->post("batch_arrival_time/a");
                        $batch_id = $this->request->post("batch_id/a");
                        $batch_sku = $this->request->post("batch_sku/a");
                        $arrival_num = $this->request->post("arrival_num/a");
                        $batch_item_id = $this->request->post("batch_item_id/a");
                        //判断是否有分批数据
                        if ($batch_arrival_time && count($batch_arrival_time) > 0) {
                            $i = 0;
                            foreach (array_filter($batch_arrival_time) as $k => $v) {
                                //判断是否存在id 如果存在则为编辑
                                $batch_data = [];
                                if ($batch_id[$k]) {
                                    $batch_data['arrival_time'] = $v;
                                    $this->batch->where(['id' => $batch_id[$k]])->update($batch_data);
                                } else {
                                    $batch_data['purchase_id'] = $ids;
                                    $batch_data['arrival_time'] = $v;
                                    $batch_data['batch'] = $i + 1;
                                    $batch_data['create_person'] = session('admin.nickname');
                                    $batch_data['create_time'] = date('Y-m-d H:i:s');
                                    $batch_id = $this->batch->insertGetId($batch_data);
                                }
                                $i++;
                                $list = [];
                                foreach ($batch_sku[$k] as $key => $val) {
                                    if (!$val || !$arrival_num[$k][$key]) {
                                        continue;
                                    }
                                    if ($batch_item_id[$k][$key]) {
                                        $list[$key]['id'] = $batch_item_id[$k][$key];
                                    } else {
                                        $list[$key]['purchase_batch_id'] = $batch_id;
                                        $list[$key]['sku'] = $val;
                                    }
                                    $list[$key]['arrival_num'] = $arrival_num[$k][$key];
                                }

                                $this->batch_item->allowField(true)->saveAll($list);
                            }
                        }
                    }

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
                    $this->success('添加成功！！', '', url('index'));
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

        //查询产品信息
        $map['purchase_id'] = $ids;
        $item = $this->purchase_order_item->where($map)->select();
        $this->assign('item', $item);

        //查询分批数据
        $batch = $this->batch->hasWhere('purchaseBatchItem')->where('purchase_id', $ids)->select();
        $this->assign('batch', $batch);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $ids = $ids ? $ids : input('id');
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $supplier = $supplier->getSupplierData();
        $this->assign('supplier', $supplier);

        //查询产品信息
        $map['purchase_id'] = $ids;
        $item = $this->purchase_order_item->where($map)->select();
        $this->assign('item', $item);

        //查询分批数据
        $batch = $this->batch->hasWhere('purchaseBatchItem')->where('purchase_id', $ids)->select();
        $this->assign('batch', $batch);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        $getTabList = ['采购单信息', '质检信息', '物流信息', '付款信息'];
        $this->assign('getTabList', $getTabList);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 录入物流单号
     */
    public function logistics($ids = null)
    {
        $ids = $ids ?? input($ids);
        $ids = explode(',', $ids);
        if (count($ids) > 1) {
            $row = $this->model->where(['id' => ['in', $ids]])->select();
            foreach ($row as $v) {
                if ($v['is_batch'] == 1) {
                    $this->error(__('分批到货采购单只能单选'));
                }
            }
        } else {
            $row = $this->model->get($ids);
            if (!$row) {
                $this->error(__('No Results were found'));
            }
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = input('post.');
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
                    $params['is_add_logistics'] = 1;
                    $params['purchase_status'] = 6; //待收货
                    $result = $this->model->allowField(true)->isUpdate(true, ['id' => ['in', $ids]])->save($params);
                    //添加物流汇总表
                    $logistics = new \app\admin\model\LogisticsInfo();
                    $logistics_company_no = $params['logistics_company_no'];
                    $logistics_number = $params['logistics_number'];
                    if ($params['batch_id']) {
                        foreach ($logistics_company_no as $k => $v) {
                            foreach($v as $key => $val) {
                                if (!$val) {
                                    continue;
                                }
                                $list['logistics_number'] = $logistics_number[$k][$key];
                                $list['logistics_company_no'] = $val;
                                $list['type'] = 1;
                                $list['order_number'] = $row['purchase_number'];
                                $list['purchase_id'] = $row['id'];
                                $list['batch_id'] = $k;
                                $logistics->addLogisticsInfo($list);
                            }
                        }
                    } else {
                        dump($logistics_company_no);
                        dump($logistics_number);die;
                        if (count($ids) > 1) {
                            foreach ($row as $k => $v) {
                                foreach ($logistics_company_no as $key => $val) {
                                    $list['logistics_number'] = $logistics_number[$key];
                                    $list['logistics_company_no'] = $val;
                                    $list['type'] = 1;
                                    $list['order_number'] = $row['purchase_number'];
                                    $list['purchase_id'] = $row['id'];
                                    $list['batch_id'] = $k;
                                    $logistics->addLogisticsInfo($list);
                                }

                                $list['logistics_number'] = $params['logistics_number'];
                                $list['logistics_company_no'] = $params['logistics_company_no'];
                                $list['type'] = 1;
                                $list['order_number'] = $v['purchase_number'];
                                $list['purchase_id'] = $v['id'];
                                $logistics->addLogisticsInfo($list);
                            }
                        } else {
                            foreach ($logistics_company_no as $k => $v) {
                                $list['logistics_number'] = $logistics_number[$k][$key];
                                $list['logistics_company_no'] = $val;
                                $list['type'] = 1;
                                $list['order_number'] = $row['purchase_number'];
                                $list['purchase_id'] = $row['id'];
                                $list['batch_id'] = $k;
                                $logistics->addLogisticsInfo($list);
                            }
                            $list['logistics_number'] = $params['logistics_number'];
                            $list['logistics_company_no'] = $params['logistics_company_no'];
                            $list['type'] = 1;
                            $list['order_number'] = $row['purchase_number'];
                            $list['purchase_id'] = $row['id'];
                            $logistics->addLogisticsInfo($list);
                        }
                    }

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
                    $this->success('添加成功！！', '', url('index'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        //判断是否为分批到货
        if ($row['is_batch'] == 1) {
            //查询分批数据
            $batch = new \app\admin\model\purchase\PurchaseBatch();
            $batch_data = $batch->where('purchase_id', $row['id'])->select();
            $this->view->assign("batch_data", $batch_data);
        }
        return $this->view->fetch();
    }

    /**
     * 备注
     * 
     */
    public function remark()
    {
        $ids = input('ids');
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
                    $this->success('添加成功！！', '', url('index'));
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
     * 删除合同里商品信息
     */
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->purchase_order_item->destroy($id);
        if ($res) {
            $this->success();
        } else {
            $this->error();
        }
    }


    /**
     * 审核
     */
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        foreach ($row as $v) {
            if ($v['purchase_status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }

        $data['purchase_status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res !== false) {
            $this->success();
        } else {
            $this->error('修改失败！！');
        }
    }

    /**
     * 取消
     */
    public function cancel($ids = null)
    {
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->get($ids);
        if ($row['purchase_status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['purchase_status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res !== false) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }

    /**
     * 物流详情
     */
    public function logisticsDetail()
    {
        $id = input('id');
        //采购单供应商物流信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $data = [];
        //判断采购单类型是否为线上采购单 1线下采购单=> 快递100api 2线上采购单 1688api
        if ($row['purchase_type'] == 2) {
            $cacheIndex = 'logisticsDetail_purchase_number' . $row['purchase_number'];
            $data = Cache::get($cacheIndex);
            if (!$data) {
                $data = Alibaba::getLogisticsMsg($row['purchase_number']);
                // 记录缓存, 时效1小时
                Cache::set($cacheIndex, $data, 3600);
            }
            $data = $data->logisticsTrace[0];
        } else {
            if ($row['logistics_number']) {
                $arr = explode(',', $row['logistics_number']);
                //物流公司编码
                $company = explode(',', $row['logistics_company_no']);
                foreach ($arr as $k => $v) {
                    try {
                        $param['express_id'] = trim($v);
                        $param['code'] = trim($company[$k]);
                        $data[$k] = Hook::listen('express_query', $param)[0];
                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                    }
                }
            }
        }

        //采购单退销物流信息
        $purchaseReturn = new \app\admin\model\purchase\PurchaseReturn;
        $res = $purchaseReturn->where('purchase_id', $id)->column('logistics_number');
        $logistics_company_no = $purchaseReturn->where('purchase_id', $id)->column('logistics_company_no');
        $return_data = [];
        if ($res) {
            $number = implode(',', $res);
            $arr = array_filter(explode(',', $number));

            $com_number = implode(',', $logistics_company_no);
            $com_arr = array_filter(explode(',', $com_number));
            foreach ($arr as $k => $v) {
                try {
                    $param['express_id'] = trim($v);
                    $param['code'] = trim($com_arr[$k]);
                    $return_data[$k] = Hook::listen('express_query', $param)[0];
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        }

        $this->assign('id', $id);
        $this->assign('data', $data);
        $this->assign('return_data', $return_data);
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    //质检信息
    public function checkDetail()
    {
        $id = input('id');
        //采购单信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        //查询产品信息
        $map['purchase_id'] = $id;
        $item = $this->purchase_order_item->where($map)->column('*', 'sku');
        $this->assign('item', $item);


        //查询质检信息
        $check_map['purchase_id'] = $id;
        $check_map['status'] = 2;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->with(['checkItem'])
            ->where($check_map)
            ->select();
        $list = collection($list)->toArray();
        $this->assign('list', $list);
        $this->assign('id', $id);

        //查询入库信息
        $check_id = array_column($list, 'id');
        if ($check_id) {
            $instock_map['check_id'] = ['in', $check_id];
            $Instock = new \app\admin\model\warehouse\Instock;
            $instock_list = $Instock->with(['instockItem'])
                ->where($instock_map)
                ->select();
            $instock_list = collection($instock_list)->toArray();
        }
        $this->assign('instock_list', $instock_list ?? []);

        //查询退销信息
        $PurchaseReturn_map['purchase_id'] = $id;
        $PurchaseReturn = new \app\admin\model\purchase\PurchaseReturn;
        $return_list = $PurchaseReturn->with(['purchaseReturnItem'])
            ->where($PurchaseReturn_map)
            ->select();
        $return_list = collection($return_list)->toArray();
        $this->assign('return_list', $return_list);


        return $this->view->fetch();
    }

    //确认差异
    public function confirmDiff()
    {
        $id = input('id');
        //采购单信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $data['check_status'] = 2;
        $data['stock_status'] = 2;
        $data['return_status'] = 2;
        $data['purchase_status'] = 7;
        $data['is_diff'] = 1;
        $res = $this->model->allowField(true)->save($data, ['id' => $id]);
        if ($res !== false) {
            $check = new \app\admin\model\warehouse\Check;
            $check->allowField(true)->save(['is_return' => 1], ['purchase_id' => $id]);
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }

    /**
     * 批量匹配sku
     */
    public function matching()
    {
        //查询SKU为空的采购单

        $data = $this->purchase_order_item->whereExp('', 'LENGTH(trim(sku))=0')->whereOr('sku', 'exp', 'is null')->select();
        $data = collection($data)->toArray();
        $new_product = new \app\admin\model\NewProduct();
        foreach ($data as $k => $v) {
            //匹配SKU
            if ($v['skuid']) {
                $params['sku'] = (new SupplierSku())->getSkuData($v['skuid']);

                $params['supplier_sku'] = (new SupplierSku())->getSupplierData($v['skuid']);
            }

            if ($params['sku']) {
                $this->purchase_order_item->allowField(true)->isUpdate(true, ['id' => $v['id']])->data($params)->save();
            }

            //判断sku是否为选品库SKU
            $count = $new_product->where(['sku' => $params['sku'], 'item_status' => 1, 'is_del' => 1])->count();
            if ($count > 0) {
                $this->model->where('id', $v['purchase_id'])->update(['is_new_product' => 1]);
            }
        }

        $this->success();
    }

    /**
     * 定时获取1688采购单 每天9点更新一次
     */
    public function getAlibabaPurchaseOrder()
    {
        //$orderId = '551171682534802669';
        //$data = Alibaba::getOrderDetail($orderId);
        // waitbuyerpay	等待买家付款	 
        // waitsellersend	等待卖家发货	 
        // waitbuyerreceive 等待买家确认收货	 
        // success 交易成功	 
        // cancel 交易关闭	 
        // paid_but_not_fund 已支付，未到账	 
        // confirm_goods 已收货	 
        // waitsellerconfirm 等待卖家确认订单	 
        // waitbuyerconfirm 等待买家确认订单	 
        // confirm_goods_but_not_fund 已收货，未到账	 
        // confirm_goods_and_has_subsidy 已收货，已贴现	 
        // send_goods_but_not_fund 已发货，未到账	 
        // waitlogisticstakein 等待物流揽件	 
        // waitbuyersign 等待买家签收	 
        // signinsuccess 买家已签收	 
        // signinfailed 签收失败	 
        // waitselleract 等待卖家操作	 
        // waitbuyerconfirmaction 等待买家确认操作	 
        // waitsellerpush 等待卖家推进
        //refundStatus = refundsuccess 退款成功

        /**
         * @todo 后面添加采集时间段
         */
        $params = [
            'createStartTime' => date('YmdHis', strtotime("-60 day")) . '000+0800',
            'createEndTime' => date('YmdHis') . '000+0800',
        ];

        set_time_limit(0);
        //根据不同的状态取订单数据
        $success_data = Alibaba::getOrderList(1, $params);
        $data = [];
        for ($i = 1; $i <= ceil($success_data['totalRecord'] / 50); $i++) {
            //根据不同的状态取订单数据
            $data[$i] = Alibaba::getOrderList($i, $params)['result'];
            sleep(1);
        }
        $new_product = new \app\admin\model\NewProduct();
        foreach ($data as $key => $val) {
            if (!$val) {
                continue;
            }
            foreach ($val as $k => $v) {
                $list = [];
                $map['purchase_number'] = $v['baseInfo']['idOfStr'];
                $map['is_del'] = 1;
                //根据采购单号查询采购单是否已存在
                $res = $this->model->where($map)->find();
                //如果采购单已存在 则更新采购单状态
                if ($res) {
                    //待发货
                    if (in_array($v['baseInfo']['status'], ['waitsellersend', 'waitsellerconfirm', 'waitbuyerconfirm', 'waitselleract', 'waitsellerpush', 'waitbuyerconfirmaction'])) {
                        $list['purchase_status'] = 5;
                    } elseif (in_array($v['baseInfo']['status'], ['waitbuyerreceive', 'send_goods_but_not_fund', 'waitlogisticstakein', 'waitbuyersign', 'signinfailed'])) {
                        $list['purchase_status'] = 6; //待收货
                    } else {
                        $list['purchase_status'] = 7; //已收货
                        $jsonDate = $v['baseInfo']['createTime'];
                        preg_match('/\d{14}/', $jsonDate, $matches);
                        $list['receiving_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    //售中退款
                    if (@$v['baseInfo']['refundStatus'] == 'refundsuccess') {
                        $list['purchase_status'] = 8; //已退款
                    }

                    $list['online_status'] = $v['baseInfo']['status'];

                    //匹配供应商
                    if (!$res['supplier_id']) {
                        $supplier = new Supplier;
                        $list['supplier_id'] = $supplier->getSupplierId($v['baseInfo']['sellerContact']['companyName']);
                    }

                    //更新采购单状态
                    $result = $res->save($list);
                } else {
                    //过滤待付款 和取消状态的订单
                    if (in_array($v['baseInfo']['status'], ['waitbuyerpay', 'cancel'])) {
                        continue;
                    }

                    $list['purchase_number'] = $v['baseInfo']['idOfStr'];
                    //1688用户配置id
                    $userIDs = config('1688user');
                    $list['create_person'] = $userIDs[$v['baseInfo']['buyerSubID']] ?? '任萍';
                    $jsonDate = $v['baseInfo']['createTime'];
                    preg_match('/\d{14}/', $jsonDate, $matches);
                    $list['createtime'] = date('Y-m-d H:i:s', strtotime($matches[0]));

                    $list['product_total'] = ($v['baseInfo']['totalAmount']) * 1 - ($v['baseInfo']['shippingFee']) * 1;
                    $list['purchase_freight'] = $v['baseInfo']['shippingFee'];
                    $list['purchase_total'] = $v['baseInfo']['totalAmount'];
                    $list['payment_money'] = $v['baseInfo']['totalAmount'];
                    $list['payment_status'] = 3;
                    $payTime = @$v['baseInfo']['payTime'];
                    if ($payTime) {
                        $matches = [];
                        preg_match('/\d{14}/', $payTime, $matches);
                        $list['payment_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    $allDeliveredTime = @$v['baseInfo']['allDeliveredTime'];
                    if ($allDeliveredTime) {
                        $matches = [];
                        preg_match('/\d{14}/', $allDeliveredTime, $matches);
                        $list['delivery_stime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                        $list['delivery_etime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    //待发货
                    if (in_array($v['baseInfo']['status'], ['waitsellersend', 'waitsellerconfirm', 'waitbuyerconfirm', 'waitselleract', 'waitsellerpush', 'waitbuyerconfirmaction'])) {
                        $list['purchase_status'] = 5;
                    } elseif (in_array($v['baseInfo']['status'], ['waitbuyerreceive', 'send_goods_but_not_fund', 'waitlogisticstakein', 'waitbuyersign', 'signinfailed'])) {
                        $list['purchase_status'] = 6; //待收货
                    } else {
                        $list['purchase_status'] = 7; //已收货
                    }
                    //收货地址
                    $list['delivery_address'] = $v['baseInfo']['receiverInfo']['toArea'];
                    $list['online_status'] = $v['baseInfo']['status'];
                    $receivingTime = @$v['baseInfo']['receivingTime'];
                    if ($receivingTime) {
                        $matches = [];
                        preg_match('/\d{14}/', $receivingTime, $matches);
                        $list['receiving_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }
                    $list['purchase_type'] = 2;

                    //匹配供应商
                    $supplier = new Supplier;
                    $list['supplier_id'] = $supplier->getSupplierId($v['baseInfo']['sellerContact']['companyName']);

                    //添加采购单
                    $result = $this->model->allowField(true)->create($list);

                    $params = [];
                    $kval = 0;
                    foreach ($v['productItems'] as  $key => $val) {
                        //添加商品数据
                        $params[$key]['purchase_id'] = $result->id;
                        $params[$key]['purchase_order_number'] = $v['baseInfo']['idOfStr'];
                        $params[$key]['product_name'] = $val['name'];
                        $params[$key]['purchase_num'] = $val['quantity'];
                        $params[$key]['purchase_price'] = $val['itemAmount'] / $val['quantity'];
                        $params[$key]['purchase_total'] = $val['itemAmount'];
                        $params[$key]['price'] = $val['price'];
                        $params[$key]['discount_money'] = $val['entryDiscount'] / 100;
                        $params[$key]['skuid'] = $val['skuID'];

                        //匹配SKU 供应商SKU
                        if ($val['skuID']) {
                            $params[$key]['sku'] = (new SupplierSku())->getSkuData($val['skuID']);
                            $params[$key]['supplier_sku'] = (new SupplierSku())->getSupplierData($val['skuID']);
                        }

                        //判断sku是否为选品库SKU
                        $count = $new_product->where(['sku' => $params[$key]['sku'], 'item_status' => 1, 'is_del' => 1])->count();
                        if ($count > 0) {
                            $kval = 1;
                        }
                    }
                    //修改为选品采购单
                    if ($kval == 1) {
                        $this->model->where('id', $result->id)->update(['is_new_product' => 1]);
                    }
                    $this->purchase_order_item->allowField(true)->saveAll($params);
                }
            }
        }
        unset($data);
        echo 'ok';
    }

    /**
     * 快递100回调地址
     */
    public function callback()
    {
        $purchase_id = input('purchase_id');
        if (!$purchase_id) {
            return json(['result' => false, 'returnCode' => 302, 'message' => '采购单未获取到']);
        }
        $params = $this->request->post('param');
        $params = json_decode($params, true);
        //此状态为已签收
        if ($params['lastResult']['state'] == 3) {
            //更改为已收货
            $data['purchase_status'] = 7;
            //收货时间
            $data['receiving_time'] = date('Y-m-d H:i:s', strtotime($params['lastResult']['data'][0]['ftime']));
        }
        $data['push_time'] = date('Y-m-d H:i:s'); //推送时间
        $data['logistics_info'] = serialize($params);
        $res = $this->model->allowField(true)->save($data, ['id' => ['in', $purchase_id]]);
        if ($res !== false) {
            return json(['result' => true, 'returnCode' => 200, 'message' => '接收成功']);
        } else {
            return json(['result' => false, 'returnCode' => 301, 'message' => '接收失败']);
        }
    }

    /**
     * 产品补货列表
     */
    public function product_grade_list()
    {
        $this->model = new \app\admin\model\ProductGrade;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('product_grade')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->alias('product_grade')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->order('counter desc')
                ->select();
            $list = collection($list)->toArray();


            //查询所有产品库存
            $map['is_del'] = 1;
            $item = new \app\admin\model\itemmanage\Item;
            $product = $item->where($map)->column('available_stock', 'sku');

            //计算在途数量
            $skus = array_column($list, 'true_sku');

            //计算SKU总采购数量
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $hasWhere['sku'] = ['in', $skus];
            $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
            $purchase_map['check_status'] = ['in', [0, 1]];
            $purchase_map['is_diff'] = 0;
            $purchase_map['is_del'] = 1;

            $purchase_list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
                ->where($purchase_map)
                ->group('sku')
                ->column('sum(purchase_num) as purchase_num', 'sku');

            //查询出满足条件的采购单号
            $ids = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
                ->where($purchase_map)
                ->group('PurchaseOrder.id')
                ->column('PurchaseOrder.id');

            //查询留样库存
            //查询实际采购信息 查询在途库存 = 采购数量 减去 到货数量
            $check_map['status'] = 2;
            $check_map['type'] = 1;
            $check_map['Check.purchase_id'] = ['in', $ids];
            $check = new \app\admin\model\warehouse\Check;
            $hasWhere['sku'] = ['in', $skus];
            $check_list = $check->hasWhere('checkItem', $hasWhere)
                ->where($check_map)
                ->group('sku')
                ->column('sum(arrivals_num) as arrivals_num', 'sku');

            //查询生产周期
            $supplier_sku = new \app\admin\model\purchase\SupplierSku;
            $supplier_where['sku'] = ['in', $skus];
            $supplier_where['status'] = 1;
            $supplier_where['label'] = 1;
            $supplier_res = $supplier_sku->where($supplier_where)->column('product_cycle', 'sku');

            /**
             * 日均销量：A+ 和 A等级，日均销量变动较大，按照2天日均销量补；
             * B和C，C+等级按照5天的日均销量来补货;
             * D和E等级按照30天日均销量补货，生产入库周期按照7天；
             * 
             * 计划售卖周期	计划售卖周期至少是生产入库周期的1倍
             * A+ 按照计划售卖周期的1.5倍来补
             * A和 B,C+等级按照计划售卖周期的1.3/1.2/1.1倍来补
             * C和D和E等级按照计划售卖周期的1倍来补
             * 补货量=日均销量*生产入库周期+日均销量*计划售卖周期-实时库存-库存在途
             */

            foreach ($list as &$v) {
                $product_cycle = $supplier_res[$v['true_sku']]['product_cycle'] ? $supplier_res[$v['true_sku']]['product_cycle'] : 7;
                $onway_stock = $purchase_list[$v['true_sku']] - ($check_list[$v['true_sku']] ?? 0);
                if ($v['grade'] == 'A+') {
                    $times = 1.5;
                } elseif ($v['grade'] == 'A') {
                    $times = 1.3;
                } elseif ($v['grade'] == 'B') {
                    $times = 1.2;
                } elseif ($v['grade'] == 'C+') {
                    $times = 1.1;
                } else {
                    $times = 1;
                }
                if ($v['grade'] == 'D' || $v['grade'] == 'E' || $v['grade'] == 'F') {
                    $product_cycle = 7;
                }

                //补货量
                $replenish_num = round(($v['days_sales_num'] * $product_cycle) + ($v['days_sales_num'] * $product_cycle * $times) - $product[$v['true_sku']] - $onway_stock);
                $v['replenish_num'] = $replenish_num > 0 ? $replenish_num : 0;
                $v['stock'] = $product[$v['true_sku']];
                $v['purchase_qty'] = $onway_stock > 0 ? $onway_stock : 0;
                //$res[$k]['out_of_stock_num'] = $sku_list[$v['true_sku']]['num'];
                $v['replenish_days'] = $v['days_sales_num'] > 0 ? floor($v['stock'] / $v['days_sales_num']) : 0;
            }

            unset($v);

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        //计算产品等级的数量
        $where = [];
        $where['grade'] = 'A+';
        $AA_num = $this->model->where($where)->count();
        $where['grade'] = 'A';
        $A_num = $this->model->where($where)->count();
        $where['grade'] = 'B';
        $B_num = $this->model->where($where)->count();
        $where['grade'] = 'C+';
        $CA_num = $this->model->where($where)->count();
        $where['grade'] = 'C';
        $C_num = $this->model->where($where)->count();
        $where['grade'] = 'D';
        $D_num = $this->model->where($where)->count();
        $where['grade'] = 'E';
        $E_num = $this->model->where($where)->count();
        $where['grade'] = 'F';
        $F_num = $this->model->where($where)->count();

        //总数
        $all_num = $AA_num + $A_num + $B_num + $CA_num + $C_num + $D_num + $E_num + $F_num;
        //A级数量即总占比
        $res['AA_num'] = $AA_num;
        $res['AA_percent'] = round($AA_num / $all_num * 100, 2);
        $res['A_num'] = $A_num;
        $res['A_percent'] = round($A_num / $all_num * 100, 2);
        $res['B_num'] = $B_num;
        $res['B_percent'] = round($B_num / $all_num * 100, 2);
        $res['CA_num'] = $CA_num;
        $res['CA_percent'] = round($CA_num / $all_num * 100, 2);
        $res['C_num'] = $C_num;
        $res['C_percent'] = round($C_num / $all_num * 100, 2);
        $res['D_num'] = $D_num;
        $res['D_percent'] = round($D_num / $all_num * 100, 2);
        $res['E_num'] = $E_num;
        $res['E_percent'] = round($E_num / $all_num * 100, 2);
        $res['F_num'] = $F_num;
        $res['F_percent'] = round($F_num / $all_num * 100, 2);

        $this->assign('res', $res);

        return $this->view->fetch();

        // //计算断货频次
        // $sku_where['sku'] = ['in', $sku_list];
        // $sku_data = M('product_sku_stock', 'zeelool_')->where($sku_where)->order('sku asc,createtime asc')->cache(true, 86400)->select();
        // $sku_list = [];
        // foreach ($sku_data as $k => $v) {
        //     //实时库存加上采购未入库库存
        //     if (($v['qty'] + $v['stock_num']) <= 0) {
        //         if (!$sku_list[$v['sku']]) {
        //             $sku_list[$v['sku']]['num'] = 1;
        //         } else {
        //             //实时库存加上采购未入库库存
        //             if (($sku_data[$k - 1]['qty'] + $sku_data[$k - 1]['stock_num']) > 0) {
        //                 $sku_list[$v['sku']]['num'] = $sku_list[$v['sku']]['num'] + 1;
        //             }
        //         }
        //     }
        // }



    }

    /**
     * 批量导出xls
     *
     * @Description
     * @author wpl
     * @since 2020/02/28 14:45:39 
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $this->relationSearch = true;

        if ($ids) {
            $map['purchase_order.id'] = ['in', $ids];
        }

        //自定义sku搜索
        $filter = json_decode($this->request->get('filter'), true);
        if ($filter['sku']) {
            $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
            $ids = $this->purchase_order_item->where($smap)->column('purchase_id');
            $map['purchase_order.id'] = ['in', $ids];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        list($where) = $this->buildparams();
        $list = $this->model->alias('purchase_order')
            ->field('receiving_time,purchase_number,purchase_name,supplier_name,sku,supplier_sku,purchase_num,purchase_price,purchase_remark,b.purchase_total,purchase_order.create_person,purchase_order.createtime')
            ->join(['fa_purchase_order_item' => 'b'], 'b.purchase_id=purchase_order.id')
            ->join(['fa_supplier' => 'c'], 'c.id=purchase_order.supplier_id')
            ->where($where)
            ->where($map)
            ->order('purchase_order.id desc')
            ->select();

        $list = collection($list)->toArray();

        //查询生产周期
        $supplier = new \app\admin\model\purchase\SupplierSku();
        $info = $supplier->where([
            'status' => 1,
            'label' => 1
        ])->column('product_cycle', 'sku');

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "采购单号")
            ->setCellValue("B1", "采购单名称")
            ->setCellValue("C1", "供应商名称");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "SKU")
            ->setCellValue("E1", "供应商SKU");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "采购数量")
            ->setCellValue("G1", "采购单价");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "采购备注")
            ->setCellValue("I1", "采购总价")
            ->setCellValue("J1", "创建人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "创建时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("L1", "生产周期");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("M1", "预计到货时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("N1", "实际到货时间");

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['purchase_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['purchase_name']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['supplier_name']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['supplier_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['purchase_num']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['purchase_price']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['purchase_remark']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['purchase_total']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['createtime']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $info[$value['sku']] ?: 7);
            $product_cycle = $info[$value['sku']] ?: 7;
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), date('Y-m-d H:i:s', strtotime('+' . $product_cycle . ' day', strtotime($value['createtime']))));
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['receiving_time']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(30);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '采购单数据' . date("YmdHis", time());;

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

    /**
     * 核算采购单成本 create@lsw
     */
    public function account_purchase_order()
    {
        //设置过滤方法
        //$this->relationSearch = true;
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $whereCondition['purchase_status'] = ['egt', 2];
            $total = $this->model
                //->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                //->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //查询总共的ID    
            $totalId = $this->model
                //->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->column('id');
            //这个页面的ID    
            $thisPageId = $this->model
                //->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->column('id');
            $list = collection($list)->toArray();
            //求出所有的总共的实际采购总额和本页面的实际采购金额
            $purchaseMoney = $this->model->calculatePurchaseOrderMoney($totalId, $thisPageId);
            // echo '<pre>';
            // var_dump($purchaseMoney);
            // exit;
            //求出退款金额信息
            $returnMoney   = $this->model->calculatePurchaseReturnMoney($totalId, $thisPageId);
            if (is_array($returnMoney['thisPageArr'])) {
                foreach ($list as $keys => $vals) {
                    if (array_key_exists($vals['id'], $returnMoney['thisPageArr'])) {
                        //采购单的退款金额 
                        $list[$keys]['refund_amount']  = round($returnMoney['thisPageArr'][$vals['id']], 2);
                    }
                }
            }
            if (is_array($purchaseMoney['thisPageArr'])) {
                foreach ($list as $key => $val) {
                    if (array_key_exists($val['id'], $purchaseMoney['thisPageArr'])) {
                        //采购单的实际采购金额 
                        $list[$key]['purchase_virtual_total'] = round($purchaseMoney['thisPageArr'][$val['id']] + $val['purchase_freight'], 2);
                        //采购单实际结算金额(如果存在实际采购金额要从实际采购金额扣减)
                        $list[$key]['purchase_settle_money']  = round($list[$key]['purchase_virtual_total'] - $list[$key]['refund_amount'], 2);
                    } else {
                        //采购单实际结算金额(如果不存在实际采购金额要从采购金额中扣减) 
                        $list[$key]['purchase_settle_money']  = round(($list[$key]['purchase_total'] - $list[$key]['refund_amount']), 2);
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list, "total_money" => $purchaseMoney['total_money'], "return_money" => $returnMoney['return_money']);

            return json($result);
        }
        return $this->view->fetch();
    }

    /***
     * 采购单成本核算详情 create@lsw 
     */
    public function account_purchase_order_detail($ids = null, $purchase_virtual_total = 0, $refund_amount = 0, $purchase_settle_money = 0)
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
        $info = $this->model->getPurchaseOrderItemInfo($row['id']);
        if ($info) {
            $this->view->assign("item", $info);
        }
        $this->view->assign("row", $row);
        $this->view->assign("refund_amount", $refund_amount);
        $this->view->assign("purchase_settle_money", $purchase_settle_money);
        $this->view->assign("purchase_virtual_total", $purchase_virtual_total);
        return $this->view->fetch();
    }

    /***
     * 核算采购单付款  create@lsw
     */
    public function purchase_order_pay($ids = null)
    {
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $row = $this->model->get($ids);
            if (1 == $row['purchase_type']) {
                $resultInfo = $this->model->where(['id' => $row['id']])->setInc('payment_money', $params['pay_money']);
            } else {
                $resultInfo = true;
            }
            if (false !== $resultInfo) {
                $this->model->save(['payment_status' => 3], ['id' => $row['id']]);
                $params['purchase_id']   = $row['id'];
                $params['create_person'] = session('admin.nickname');
                $params['create_time'] = date('Y-m-d H:i:s', time());
                $result = (new purchase_order_pay())->allowField(true)->save($params);
                if ($result) {
                    return    $this->success('添加成功');
                }
            } else {
                return    $this->error('添加失败');
            }
        }
        return $this->view->fetch();
    }
    /***
     * 核算采购单确认退款 create@lsw
     */
    public function purchase_order_affirm_refund($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (8 == $row['purchase_status']) {
                return $this->error('已经是退款状态,无须再次退款');
            }
            $data['purchase_status'] = 8;
            $result = $this->model->allowField(true)->save($data, ['id' => $ids]);
            if ($result) {
                return $this->success();
            }
            return $this->error();
        }
    }
}
