<?php

namespace app\admin\controller\purchase;

use app\admin\controller\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\purchase\PurchaseBatch;
use app\admin\model\StockLog;
use app\common\controller\Backend;
use fast\Excel;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\response\Json;


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
    protected $noNeedLogin = ['getAlibabaPurchaseOrder', 'callback', 'export_sku_many_type'];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['batch_export_xls', 'deleteLogisticsItem', 'process_export_xls'];

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
            $map['purchase_order.is_in_stock'] = 0;
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                $ids = $this->purchase_order_item->where($smap)->column('purchase_id');
                $map['purchase_order.id'] = ['in', $ids];
                unset($filter['sku']);
            }
            //列表默认不显示是退货入库的采购单
            if ($filter['is_in_stock']) {
                $map['purchase_order.is_in_stock'] = $filter['is_in_stock'];
                unset($filter['is_in_stock']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
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
            //判断能否创建付款申请单 要先看有没有批次
            foreach ($list as $k => $v) {
                //创建过付款申请单 并且不是已取消或者审核拒绝状态的 不能在继续创建付款申请单
                $purchase_pay = Db::name('finance_purchase')->where('purchase_id', $v['id'])->where('status', 'in', [0, 1, 2, 4])->find();
                if (!empty($purchase_pay)) {
                    //不能创建
                    $list[$k]['can_create_pay'] = 0;
                } else {
                    if (in_array($v['purchase_status'], [2, 5, 6, 7, 9, 10])) //能创建
                    {
                        $list[$k]['can_create_pay'] = 1;
                    }
                }


            }
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add($ids = null)
    {
        if ($ids) {
            $replenishListDetail = Db::name('new_product_replenish_list')
                ->where('id', $ids)
                ->find();
            $supplierDetail = Db::name('supplier')
                ->where('id', $replenishListDetail['supplier_id'])
                ->find();
            $this->assign('supplier_detail', $supplierDetail);
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if (empty($params['supplier_id']) || empty($params['supplier_address'])) {
                    $this->error('必须选择供应商信息');
                }
                if (empty($params['pay_type'])) {
                    $this->error('必须选择采购单付款类型');
                } else {
                    if ($params['pay_type'] == 1) {
                        if (empty($params['pay_rate'])) {
                            $this->error('选择预付款必须选择预付款比例');
                        }
                    } else {
                        if (!empty($params['pay_rate'])) {
                            $this->error('不选择预付款不能选择预付款比例');
                        }
                    }
                }
                if ($params['product_total'] == 0) {
                    $this->error('商品总额不能为0');
                }

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                $this->model->startTrans();
                $this->purchase_order_item->startTrans();
                $this->batch_item->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    $sku = $this->request->post("sku/a");
                    foreach (array_filter($sku) as $k => $v) {
                        $item = new \app\admin\model\itemmanage\Item();
                        $itemDetail = $item->where('sku', $v)->find();
                        if (!$itemDetail) {
                            $this->error('提报的商品不存在');
                        }
                    }
                    $num = $this->request->post("purchase_num/a");
                    //执行过滤空值
                    array_walk($sku, 'trim_value');
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $params['purchase_name'] = trim($params['purchase_name']);

                    $batchSku = $this->request->post("batch_sku/a");
                    $arrivalNum = $this->request->post("arrival_num/a");
                    if ($arrivalNum) {
                        //现在分批到货数量必须等于采购数量
                        $arr = [];
                        foreach ($arrivalNum as $k => $v) {
                            foreach ($v as $key => $val) {
                                if (empty($val) || !is_numeric($val)) {
                                    $this->error('分批到货数量不能为空且不能为非数字');
                                }
                                $arr[$key] += $val;
                            }
                        }
                        foreach ($num as $k => $v) {
                            if ($arr[$k] != $v) {
                                $this->error('分批到货数量必须等于采购数量');
                            }
                        }
                    }
                    $result = $this->model->allowField(true)->save($params);

                    //添加采购单商品信息
                    if ($result !== false) {
                        $productName = $this->request->post("product_name/a");
                        $supplierSku = $this->request->post("supplier_sku/a");

                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");
                        $replenishListId = $this->request->post("replenish_list_id/a");

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = trim($supplierSku[$k]);
                            $data[$k]['product_name'] = $productName[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            $data[$k]['purchase_id'] = $this->model->id;
                            $data[$k]['replenish_list_id'] = $replenishListId[$k];
                            $data[$k]['purchase_order_number'] = $params['purchase_number'];
                        }
                        //批量添加
                        $this->purchase_order_item->saveAll($data);

                        //添加分批数据
                        $batchArrivalTime = $this->request->post("batch_arrival_time/a");
                        $batchSku = $this->request->post("batch_sku/a");
                        $arrivalNum = $this->request->post("arrival_num/a");
                        //判断是否有分批数据
                        if ($batchArrivalTime && count($batchArrivalTime) > 0) {
                            $i = 0;
                            foreach (array_filter($batchArrivalTime) as $k => $v) {
                                $batchData['purchase_id'] = $this->model->id;
                                $batchData['arrival_time'] = $v;
                                $batchData['batch'] = $i + 1;
                                $batchData['create_person'] = session('admin.nickname');
                                $batchData['create_time'] = date('Y-m-d H:i:s');
                                $batchId = $this->batch->insertGetId($batchData);
                                $i++;
                                $list = [];
                                foreach ($batchSku[$k] as $key => $val) {
                                    if (!$val) {
                                        continue;
                                    }
                                    $list[$key]['sku'] = $val;
                                    $list[$key]['arrival_num'] = $arrivalNum[$k][$key];
                                    $list[$key]['purchase_batch_id'] = $batchId;
                                }
                                $this->batch_item->saveAll($list);
                            }
                        }
                    }
                    $this->model->commit();
                    $this->purchase_order_item->commit();
                    $this->batch_item->commit();
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $this->purchase_order_item->rollback();
                    $this->batch_item->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $this->purchase_order_item->rollback();
                    $this->batch_item->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $this->purchase_order_item->rollback();
                    $this->batch_item->rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('添加成功！！', url('PurchaseOrder/index'));
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $label = input('label');
        //从补货需求单处理页面进来的采购单
        if ($label == 'replenish') {
            $ids = $ids ? $ids : input('ids');
            $this->list = new \app\admin\model\purchase\NewProductReplenishList;
            $list = $this->list->where('id', 'in', $ids)->select();
            $item = new \app\admin\model\itemmanage\Item;
            $supplier = new \app\admin\model\purchase\SupplierSku;
            foreach ($list as &$v) {
                if ($v['status'] == 3) {
                    $this->error('存在已经拒绝的补货需求，请重新选择！！');
                }
                //查询sku 商品名称
                $data = $item->getGoodsInfo($v['sku']);
                $v['product_name'] = $data->name;
                //查询供应商SKU
                $v['supplier_sku'] = $supplier->getSupplierSkuData($v['sku'], $v['supplier_id']);
            }
            $newOld = $item->where('sku', $list[0]['sku'])->value('is_new');
            if (count(array_unique(array_column($list, 'supplier_id'))) > 1) {
                $this->error('必须选择相同的供应商！！');
            }
            $this->assign('new_old', $newOld);
            $this->assign('list', $list);
        }

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contractData = $contract->getContractData();
        $this->assign('contract_data', $contractData);

        //生成采购编号
        $purchaseNumber = 'PO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('purchase_number', $purchaseNumber);
        $this->assignconfig('newdatetime', date('Y-m-d H:i:s'));

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
        if (!in_array($row['purchase_status'], [0])) {
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
                if (empty($params['pay_type'])) {
                    $this->error('必须选择采购单付款类型');
                } else {
                    if ($params['pay_type'] == 1) {
                        if (empty($params['pay_rate'])) {
                            $this->error('选择预付款必须选择预付款比例');
                        }
                    } else {
                        if (!empty($params['pay_rate'])) {
                            $this->error('不选择预付款不能选择预付款比例');
                        }
                    }
                }
                $result = false;
                Db::startTrans();
                $this->model->startTrans();
                $this->purchase_order_item->startTrans();
                $this->batch_item->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $sku = $this->request->post("sku/a");
                    foreach (array_filter($sku) as $k => $v) {
                        $item = new \app\admin\model\itemmanage\Item();
                        $itemDetail = $item->where('sku', $v)->find();
                        if (!$itemDetail) {
                            $this->error('提报的商品不存在');
                        }
                    }
                    $num = $this->request->post("purchase_num/a");
                    //执行过滤空值
                    array_walk($sku, 'trim_value');
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $arrivalNum = $this->request->post("arrival_num/a");
                    if ($arrivalNum) {
                        //现在分批到货数量必须等于采购数量
                        $arr = [];
                        foreach ($arrivalNum as $k => $v) {
                            foreach ($v as $key => $val) {
                                $arr[$key] += $val;
                            }
                        }
                        foreach ($num as $k => $v) {
                            if ($arr[$k] != $v) {
                                $this->error('分批到货数量必须等于采购数量');
                            }
                        }
                    }
                    $params['purchase_name'] = trim($params['purchase_name']);
                    $result = $row->allowField(true)->save($params);

                    //添加合同产品
                    if ($result !== false) {
                        $productName = $this->request->post("product_name/a");
                        $supplierSku = $this->request->post("supplier_sku/a");

                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");
                        $itemId = $this->request->post("item_id/a");

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = trim($supplierSku[$k]);
                            $data[$k]['product_name'] = $productName[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            if (@$itemId[$k]) {
                                $data[$k]['id'] = $itemId[$k];
                            } else {
                                $data[$k]['purchase_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->purchase_order_item->allowField(true)->saveAll($data);

                        //添加分批数据
                        $batchArrivalTime = $this->request->post("batch_arrival_time/a");

                        $batchId = $this->request->post("batch_id/a");
                        $batchSku = $this->request->post("batch_sku/a") ?: [];
                        $batchItemId = $this->request->post("batch_item_id/a");

                        //判断是否有分批数据
                        if ($batchArrivalTime && count($batchArrivalTime) > 0) {
                            $batchSku = $batchSku ? array_values($batchSku) : [];
                            $i = 0;
                            foreach (array_filter($batchArrivalTime) as $k => $v) {
                                //判断是否存在id 如果存在则为编辑
                                $batchData = [];
                                if ($batchId[$k]) {
                                    $batchData['arrival_time'] = $v;
                                    $this->batch->where(['id' => $batchId[$k]])->update($batchData);
                                } else {
                                    $batchData['purchase_id'] = $ids;
                                    $batchData['arrival_time'] = $v;
                                    $batchData['batch'] = $i + 1;
                                    $batchData['create_person'] = session('admin.nickname');
                                    $batchData['create_time'] = date('Y-m-d H:i:s');

                                    $batchGetId = $this->batch->insertGetId($batchData);
                                }
                                $i++;
                                $list = [];
                                $arrivalNum = $arrivalNum ? array_values($arrivalNum) : []; //数组默认首位下标不是0 需要转一下
                                foreach ($batchSku[$k] as $key => $val) {
                                    if (!$val || !$arrivalNum[$k][$key]) {
                                        continue;
                                    }
                                    if ($batchItemId[$k][$key]) {
                                        $list[$key]['id'] = $batchItemId[$k][$key];
                                    } else {
                                        $list[$key]['purchase_batch_id'] = $batchGetId;
                                        $list[$key]['sku'] = $val;
                                    }
                                    $list[$key]['arrival_num'] = $arrivalNum[$k][$key];
                                }
                                $this->batch_item->allowField(true)->saveAll($list);
                            }
                        }
                    }

                    Db::commit();
                    $this->model->commit();
                    $this->purchase_order_item->commit();
                    $this->batch_item->commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $this->purchase_order_item->rollback();
                    $this->batch_item->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $this->purchase_order_item->rollback();
                    $this->batch_item->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $this->purchase_order_item->rollback();
                    $this->batch_item->rollback();
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
        $logistics = new \app\admin\model\LogisticsInfo();
        $ids = $ids ?? input($ids);
        $ids = explode(',', $ids);
        if (count($ids) > 1) {
            $row = $this->model->where(['id' => ['in', $ids]])->select();
            foreach ($row as $v) {
                if ($v['is_batch'] == 1) {
                    $this->error(__('分批到货采购单只能单选'), url('index'));
                }

                if (!in_array($v['purchase_status'], [2, 5, 6, 7, 9])) {
                    $this->error(__('此状态不能录入物流单号'), url('index'));
                }
            }
        } else {
            $row = $this->model->get($ids);
            if (!$row) {
                $this->error(__('No Results were found'), url('index'));
            }

            if (!in_array($row['purchase_status'], [2, 5, 6, 7, 9])) {
                $this->error(__('此状态不能录入物流单号'), url('index'));
            }

            $logistics = new \app\admin\model\LogisticsInfo();
            $logistics_data = $logistics->where('purchase_id', 'in', $ids)->select();
            $logistics_data = collection($logistics_data)->toArray();
            $this->view->assign("logistics_data", $logistics_data);
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = input('post.');

            $logistics_company_no = $params['logistics_company_no'];
            $logistics_number = $params['logistics_number'];
            $logistics_ids = $params['logistics_ids'];
            $stock_ids = $params['stock_id'];

            if ($params) {

                if (!$params['logistics_number']) {
                    $this->error(__('物流单号不能为空'));
                }

                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                $item = new \app\admin\model\itemmanage\Item();
                $this->model->startTrans();
                $item->startTrans();
                $logistics->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params['is_add_logistics'] = 1;
                    $params['purchase_status'] = 6; //待收货
                    $params['logistics_number'] = is_array($logistics_number) ? implode(',', $logistics_number) : $logistics_number;
                    $params['logistics_company_no'] = is_array($logistics_company_no) ? implode(',', $logistics_company_no) : $logistics_company_no;
                    $params['receiving_warehouse'] = is_array($stock_ids) ? 0 : $stock_ids;
                    $result = $this->model->allowField(true)->isUpdate(true, ['id' => ['in', $ids], 'purchase_status' => ['in', [2, 5, 6]]])->save($params);
                    //添加物流汇总表
                    $logistics = new \app\admin\model\LogisticsInfo();
                    if (count($logistics_number) == count($logistics_number, 1)) {
                        $result = $logistics_number;
                    } else {
                        //所有的物流单号
                        $result = array_reduce($logistics_number, function ($result, $value) {
                            return array_merge($result, array_values($value));
                        }, []);
                    }
                    $have_logistics = $logistics->whereIn('logistics_number', $result)->where('status', 1)->count();
                    $count_result = count($result);
                    if ($have_logistics == 0) {
                        $purchase_status = 6;
                    } else {
                        if ($have_logistics > $count_result || $have_logistics == $count_result) {
                            $purchase_status = 7;
                        } else {
                            $purchase_status = 9;
                        }
                    }
                    //添加物流单明细表
                    if ($params['batch_id']) {
                        $i = 0;
                        $batch = new PurchaseBatch();
                        foreach ($logistics_company_no as $k => $v) {
                            foreach ($v as $key => $val) {
                                $list = [];
                                if (!$val) {
                                    continue;
                                }
                                if ($logistics_ids[$k][$key]) {
                                    $list['id'] = $logistics_ids[$k][$key];
                                } else {
                                    $list['order_number'] = $row['purchase_number'];
                                    $list['purchase_id'] = $row['id'];
                                    $list['type'] = 1;
                                    $list['batch_id'] = $k;
                                }
                                $list['logistics_number'] = $logistics_number[$k][$key];
                                $list['logistics_company_no'] = $val;
                                $list['receiving_warehouse'] = $stock_ids[$i];

                                $batch->where(['id' => $k])->update(['receiving_warehouse' => $stock_ids[$i]]);
                                //若物流单号已经签收的话直接更改采购单的状态为已签收
                                $have_logistics = $logistics->where(['logistics_number' => $logistics_number[$k][$key], 'status' => 1])->find();
                                if (!empty($have_logistics)) {
                                    $this->model->where(['id' => $row['id']])->update(['purchase_status' => $purchase_status]);
                                    //物流单已签收要减少在途增加待入库 这里是录入已经签收的物流单号要进行的操作
                                    $lists = Db::name('purchase_order_item')->where(['purchase_id' => $row['id']])->select();
                                    $batch_arrival_num = Db::name('purchase_batch_item')->where(['purchase_batch_id' => $k])->value('arrival_num');
                                    $item = new \app\admin\model\itemmanage\Item();

                                    //:todo 此扣减方案有Bug 已反馈 未给新的扣减方案
                                    /*foreach ($lists as $val) {
                                        //插入日志表
                                        (new StockLog())->setData([
                                            'type'                    => 2,
                                            'site'                    => 0,
                                            'modular'                 => 10,
                                            //采购单签收
                                            'change_type'             => 24,
                                            'sku'                     => $val['sku'],
                                            'public_id'               => $row['id'],
                                            'source'                  => 1,
                                            'on_way_stock_before'     => ($item->where(['sku' => $val['sku']])->value('on_way_stock')) ?: 0,
                                            'on_way_stock_change'     => -$batch_arrival_num,
                                            'wait_instock_num_before' => ($item->where(['sku' => $val['sku']])->value('wait_instock_num')) ?: 0,
                                            'wait_instock_num_change' => $batch_arrival_num,
                                            'create_person'           => session('admin.nickname'),
                                            'create_time'             => time(),
                                            //关联采购单
                                            'number_type'             => 7,
                                        ]);
                                        //减总的在途库存也就是商品表里的在途库存 :todo 此扣减方案有Bug 已反馈 未给新的扣减方案
                                        //$item->where(['sku' => $val['sku']])->setDec('on_way_stock', $batch_arrival_num);
                                        //减在途加待入库数量
                                        //$item->where(['sku' => $val['sku']])->setInc('wait_instock_num', $batch_arrival_num);
                                    }*/
                                    $list['status'] = 1;
                                    $list['sign_number'] = $have_logistics['sign_number'];
                                }
                                $logistics->addLogisticsInfo($list);

                            }
                            $i++;
                        }
                    } else {
                        if (count($ids) > 1) {
                            foreach ($row as $k => $v) {
                                foreach ($logistics_company_no as $key => $val) {
                                    $list = [];
                                    if (!$val) {
                                        continue;
                                    }
                                    $list['logistics_number'] = $logistics_number[$key];
                                    $list['logistics_company_no'] = $val;
                                    $list['type'] = 1;
                                    $list['order_number'] = $v['purchase_number'];
                                    $list['purchase_id'] = $v['id'];
                                    $list['receiving_warehouse'] = is_array($stock_ids) ? 0 : $stock_ids;

                                    $this->model->where(['id' => $k])->update(['receiving_warehouse' => is_array($stock_ids) ? 0 : $stock_ids]);
                                    //若物流单号已经签收的话直接更改采购单的状态为已签收
                                    $have_logistics = $logistics->where(['logistics_number' => $logistics_number[$k], 'status' => 1])->find();
                                    if (!empty($have_logistics)) {
                                        $this->model->where(['id' => $v['id']])->update(['purchase_status' => $purchase_status]);
                                        //物流单已签收要减少在途增加待入库 这里是录入已经签收的物流单号要进行的操作
                                        $list = Db::name('purchase_order_item')->where(['purchase_id' => $v['id']])->select();
                                        //:todo 未给扣减方案
                                        /* foreach ($list as $val) {
                                             //插入日志表
                                             (new StockLog())->setData([
                                                 'type'                    => 2,
                                                 'site'                    => 0,
                                                 'modular'                 => 10,
                                                 //采购单签收
                                                 'change_type'             => 24,
                                                 'sku'                     => $val['sku'],
                                                 'public_id'               => $v['id'],
                                                 'source'                  => 1,
                                                 'on_way_stock_before'     => ($item->where(['sku' => $val['sku']])->value('on_way_stock')) ?: 0,
                                                 'on_way_stock_change'     => -$val['purchase_num'],
                                                 'wait_instock_num_before' => ($item->where(['sku' => $val['sku']])->value('wait_instock_num')) ?: 0,
                                                 'wait_instock_num_change' => $val['purchase_num'],
                                                 'create_person'           => session('admin.nickname'),
                                                 'create_time'             => time(),
                                                 //关联采购单
                                                 'number_type'             => 7,
                                             ]);
                                             //减总的在途库存也就是商品表里的在途库存  :todo 未给扣减方案
                                             //$item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['purchase_num']);
                                             //减在途加待入库数量
                                             //$item->where(['sku' => $val['sku']])->setInc('wait_instock_num', $val['purchase_num']);
                                         }*/
                                        $list['status'] = 1;
                                        $list['sign_number'] = $have_logistics['sign_number'];
                                    }
                                    $logistics->addLogisticsInfo($list);
                                }
                            }
                        } else {
                            foreach ($logistics_company_no as $k => $v) {
                                if (!$v) {
                                    continue;
                                }
                                $list = [];
                                if ($logistics_ids[$k]) {
                                    $list['id'] = $logistics_ids[$k];
                                } else {
                                    $list['order_number'] = $row['purchase_number'];
                                    $list['purchase_id'] = $row['id'];
                                    $list['type'] = 1;
                                }
                                $list['logistics_number'] = $logistics_number[$k];
                                $list['logistics_company_no'] = $v;
                                $list['stock_id'] = is_array($stock_ids) ? 0 : $stock_ids;
                                //若物流单号已经签收的话直接更改采购单的状态为已签收
                                $have_logistics = $logistics->where(['logistics_number' => $logistics_number[$k], 'status' => 1])->find();
                                if (!empty($have_logistics)) {
                                    $this->model->where(['id' => $row['id']])->update(['purchase_status' => $purchase_status]);
                                    //物流单已签收要减少在途增加待入库 这里是录入已经签收的物流单号要进行的操作
                                    $lists = Db::name('purchase_order_item')->where(['purchase_id' => $row['id']])->select();
                                    //根据采购单id获取补货单id再获取最初提报的比例
                                    $replenish_id = Db::name('purchase_order')->where('id', $row['id'])->value('replenish_id');
                                    $item_platform = new ItemPlatformSku();
                                    foreach ($lists as $val) {
                                        //比例
                                        $rate_arr = Db::name('new_product_mapping')
                                            ->where(['sku' => $val['sku'], 'replenish_id' => $replenish_id])
                                            ->field('website_type,rate')
                                            ->select();

                                        //插入日志表
                                        (new StockLog())->setData([
                                            'type'                    => 2,
                                            'site'                    => 0,
                                            'modular'                 => 10,
                                            //采购单签收
                                            'change_type'             => 24,
                                            'sku'                     => $val['sku'],
                                            'public_id'               => $row['id'],
                                            'source'                  => 1,
                                            'on_way_stock_before'     => ($item->where(['sku' => $val['sku']])->value('on_way_stock')) ?: 0,
                                            'on_way_stock_change'     => -$val['purchase_num'],
                                            'wait_instock_num_before' => ($item->where(['sku' => $val['sku']])->value('wait_instock_num')) ?: 0,
                                            'wait_instock_num_change' => $val['purchase_num'],
                                            'create_person'           => session('admin.nickname'),
                                            'create_time'             => time(),
                                            //关联采购单
                                            'number_type'             => 7,
                                        ]);
                                        //减总的在途库存也就是商品表里的在途库存
                                        $item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['purchase_num']);
                                        //减在途加待入库数量
                                        $item->where(['sku' => $val['sku']])->setInc('wait_instock_num', $val['purchase_num']);
                                    }
                                    $list['status'] = 1;
                                    $list['sign_number'] = $have_logistics['sign_number'];
                                }
                                $logistics->addLogisticsInfo($list);
                            }
                        }
                    }
                    Db::commit();
                    $this->model->commit();
                    $item->commit();
                    $logistics->commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $item->rollback();
                    $logistics->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $item->rollback();
                    $logistics->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->model->rollback();
                    $item->rollback();
                    $logistics->rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {

                    //添加成功订阅物流单推送
                    $this->model->logisticsSubscription($logistics_number, $logistics_company_no);

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
     * 删除采购单物流数据
     *
     * @Description
     * @author wpl
     * @since 2020/06/04 13:55:22 
     * @return void
     */
    public function deleteLogisticsItem()
    {
        if ($this->request->isAjax()) {
            $id = input('id');
            $logistics = new \app\admin\model\LogisticsInfo();
            $res = $logistics->destroy($id);
            if ($res) {
                $this->success();
            } else {
                $this->error();
            }
        }
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
     * 采购单审核 操作1：更改采购单状态 2：item添加在途库存 存库存日志 3：有补货单的需要将在途分站 存映射关系分站点库存日志 4：更新补货需求单状态为部分处理 5：更改sku为老品
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/22
     * Time: 10:46:53
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
        $status = input('status');
        $data['purchase_status'] = $status;

        $item = new \app\admin\model\itemmanage\Item();
        $item_platform = new ItemPlatformSku();
        $this->list = new \app\admin\model\purchase\NewProductReplenishList;
        $this->replenish = new \app\admin\model\purchase\NewProductReplenish;


        $item->startTrans();
        $item_platform->startTrans();
        $this->list->startTrans();
        $this->model->startTrans();
        $this->replenish->startTrans();
        (new StockLog())->startTrans();
        try {
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            //在途库存新逻辑
            if ($status == 2) {
                //审核通过添加在途库存
                $list = $this->purchase_order_item->alias('a')
                    ->join(['fa_purchase_order' => 'b'], 'a.purchase_id=b.id')
                    ->field('supplier_id,sku,replenish_list_id,purchase_num,purchase_number')
                    ->where(['purchase_id' => ['in', $ids]])->select();
                $skus = array_column($list, 'sku');

                //查询供应商
                $supplier = new \app\admin\model\purchase\Supplier();
                $supplier_list = $supplier->column('supplier_name', 'id');
                foreach ($list as $v) {
                    //插入日志表
                    (new StockLog())->setData([
                        'type'                => 2,
                        'site'                => 0,
                        'modular'             => 10,
                        'change_type'         => 23,
                        'sku'                 => $v['sku'],
                        'order_number'        => $v['purchase_number'],
                        'source'              => 1,
                        'on_way_stock_before' => $item->where(['sku' => $v['sku']])->value('on_way_stock') ? $item->where(['sku' => $v['sku']])->value('on_way_stock') : 0,
                        'on_way_stock_change' => $v['purchase_num'],
                        'create_person'       => session('admin.nickname'),
                        'create_time'         => time(),
                        //关联采购单
                        'number_type'         => 7,
                    ]);
                    $item->where(['sku' => $v['sku']])->setInc('on_way_stock', $v['purchase_num']);

                    //判断是否关联补货需求单 如果有回写实际采购数量 已采购状态 供应商
                    if ($v['replenish_list_id']) {
                        $this->list
                            ->where('id', $v['replenish_list_id'])
                            ->update(['supplier_id' => $v['supplier_id'], 'supplier_name' => $supplier_list[$v['supplier_id']], 'real_dis_num' => $v['purchase_num'], 'status' => 2]);
                        //当对补货需求单对应的子子表 对应的采购单进行审核的时候 判断对应的补货需求单 是否还有未采购的单 如果没有 就更新主表状态为已处理
                        $replenish_id = $this->list
                            ->where('id', $v['replenish_list_id'])
                            ->field('replenish_id,status')->find();
                        //补货需求单id $replenish_id['replenish_id'] 新逻辑在途库存分站 在更新商品表的在途库存的时候 查找补货需求单中的原始比例 进行在途库存的分站点分配
                        //比例
                        $rate_arr = Db::name('new_product_mapping')
                            ->where(['sku' => $v['sku'], 'replenish_id' => $replenish_id['replenish_id']])
                            ->field('website_type,rate')
                            ->select();
                        //数量
                        $all_num = count($rate_arr);
                        //在途库存数量
                        $stock_num = $v['purchase_num'];
                        //在途库存分站 更新映射关系表
                        foreach ($rate_arr as $key => $val) {
                            //最后一个站点 剩余数量分给最后一个站
                            if (($all_num - $key) == 1) {
                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                => 2,
                                    'site'                => $val['website_type'],
                                    'modular'             => 10,
                                    'change_type'         => 23,
                                    'sku'                 => $v['sku'],
                                    'order_number'        => $v['purchase_number'],
                                    'source'              => 1,
                                    'on_way_stock_before' => $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('plat_on_way_stock') ? $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('plat_on_way_stock') : 0,
                                    'on_way_stock_change' => $stock_num,
                                    'create_person'       => session('admin.nickname'),
                                    'create_time'         => time(),
                                    //关联采购单
                                    'number_type'         => 7,
                                ]);
                                //根据sku站点类型进行在途库存的分配
                                $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('plat_on_way_stock', $stock_num);

                            } else {
                                $num = round($v['purchase_num'] * $val['rate']);
                                $stock_num -= $num;
                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                => 2,
                                    'site'                => $val['website_type'],
                                    'modular'             => 10,
                                    'change_type'         => 23,
                                    'sku'                 => $v['sku'],
                                    'order_number'        => $v['purchase_number'],
                                    'source'              => 1,
                                    'on_way_stock_before' => $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('plat_on_way_stock') ? $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('plat_on_way_stock') : 0,
                                    'on_way_stock_change' => $num,
                                    'create_person'       => session('admin.nickname'),
                                    'create_time'         => time(),
                                    //关联采购单
                                    'number_type'         => 7,
                                ]);
                                $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('plat_on_way_stock', $num);

                            }
                        }

                        $replenish_order = $this->list
                            ->where(['replenish_id' => $replenish_id['replenish_id'], 'status' => 1])
                            ->find();
                        //当前补货单状态为待处理 有审核通过的采购单 立刻更新补货需求单状态为部分处理
                        if ($replenish_id['status'] == 2) {
                            $res = $this->replenish->where('id', $replenish_id['replenish_id'])->setField('status', 3);
                        }
                        if (empty($replenish_order)) {
                            $res = $this->replenish->where('id', $replenish_id['replenish_id'])->setField('status', 4);
                        }
                    }


                }
                //采购单审核通过 sku 改为老品
                $item->where(['sku' => ['in', $skus]])->update(['is_new' => 2]);

                createNewProductProcessLog($skus, 3, session('admin.id'));
            }
            $item->commit();
            $item_platform->commit();
            $this->list->commit();
            $this->model->commit();
            $this->replenish->commit();
            (new StockLog())->commit();
        } catch (ValidateException $e) {
            $item->rollback();
            $item_platform->rollback();
            $this->list->rollback();
            $this->model->rollback();
            $this->replenish->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            $item->rollback();
            $item_platform->rollback();
            $this->list->rollback();
            $this->model->rollback();
            $this->replenish->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $item->rollback();
            $item_platform->rollback();
            $this->list->rollback();
            $this->model->rollback();
            $this->replenish->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage());
        }

        $this->success();


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

        //查询物流信息表快递数据
        $logistics = new \app\admin\model\LogisticsInfo();
        $list = $logistics->where(['purchase_id' => $id])->select();
        $list = collection($list)->toArray();
        if (!$list) {
            $this->error(__('No Results were found'));
        }

        $cacheIndex = 'logisticsDetail_purchase_number' . $row['purchase_number'];
        $data = Cache::get($cacheIndex);
        if (!$data) {
            foreach ($list as $k => $v) {
                //来源快递100
                if ($v['source'] == 1) {
                    $param['express_id'] = $v['logistics_number'];
                    $param['code'] = $v['logistics_company_no'];
                    $data[$k] = Hook::listen('express_query', $param)[0];
                } elseif ($v['source'] == 2) { //来源1688
                    $data[$k] = Alibaba::getLogisticsMsg($v['order_number']);
                }
            }
            // 记录缓存, 时效1小时
            Cache::set($cacheIndex, $data, 3600);
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
        $data = $this->purchase_order_item->alias('a')->field('a.id,a.purchase_num,a.skuid,supplier_id')->join(['fa_purchase_order' => 'b'], 'a.purchase_id=b.id')->whereExp('', 'LENGTH(trim(sku))=0')->whereOr('sku', 'exp', 'is null')->select();
        $data = collection($data)->toArray();
        $new_product = new \app\admin\model\NewProduct();
        $item = new \app\admin\model\itemmanage\Item();
        foreach ($data as $k => $v) {
            //匹配SKU
            if ($v['skuid']) {
                $params['sku'] = (new SupplierSku())->getSkuData($v['skuid'], $v['supplier_id']);

                $params['supplier_sku'] = (new SupplierSku())->getSupplierData($v['skuid'], $v['supplier_id']);
            }

            if ($params['sku']) {
                $this->purchase_order_item->allowField(true)->isUpdate(true, ['id' => $v['id']])->data($params)->save();

                $item->where(['sku' => $params['sku']])->setInc('on_way_stock', $v['purchase_num']);
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
     * 定时获取1688采购单 每天9点更新一次 已弃用1688
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
            'createStartTime' => date('YmdHis', strtotime("-10 day")) . '000+0800',
            'createEndTime'   => date('YmdHis') . '000+0800',
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
        $item = new \app\admin\model\itemmanage\Item();
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
                    if (in_array($res->purchase_status, [6, 7, 8, 9, 10])) {
                        continue;
                    }

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
                        $list['delivery_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    //待发货
                    if (in_array($v['baseInfo']['status'], ['waitsellersend', 'waitsellerconfirm', 'waitbuyerconfirm', 'waitselleract', 'waitsellerpush', 'waitbuyerconfirmaction'])) {
                        $list['purchase_status'] = 5;
                    } elseif (in_array($v['baseInfo']['status'], ['waitbuyerreceive', 'send_goods_but_not_fund', 'waitlogisticstakein', 'waitbuyersign', 'signinfailed'])) {
                        $list['purchase_status'] = 6; //待收货
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
                    foreach ($v['productItems'] as $key => $val) {
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
                            $params[$key]['sku'] = (new SupplierSku())->getSkuData($val['skuID'], $list['supplier_id']);
                            $params[$key]['supplier_sku'] = (new SupplierSku())->getSupplierData($val['skuID'], $list['supplier_id']);
                        }

                        //判断sku是否为选品库SKU
                        $count = $new_product->where(['sku' => $params[$key]['sku'], 'item_status' => 1, 'is_del' => 1])->count();
                        if ($count > 0) {
                            $kval = 1;
                        }

                        if ($params[$key]['sku']) {
                            $item->where(['sku' => $params[$key]['sku']])->setInc('on_way_stock', $params[$key]['purchase_num']);
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
     * @Description
     * @return Json
     * @since : 2021/4/1 9:54
     * @author: wpl
     */
    public function callback(): Json
    {
        $purchase_id = input('purchase_id');
        if (!$purchase_id) {
            return json(['result' => false, 'returnCode' => 302, 'message' => '采购单未获取到']);
        }
        $params = $this->request->post('param');
        $params = json_decode($params, true);
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
     * 产品补货列表 在途库存新
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

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
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

            $skus = array_column($list, 'true_sku');
            //查询所有产品库存
            $map['is_del'] = 1;
            $map['is_open'] = ['lt', 3];
            $map['sku'] = ['in', $skus];
            $item = new \app\admin\model\itemmanage\Item;
            $product = $item->where($map)->column('available_stock,on_way_stock', 'sku');

            /**
             * 日均销量：A+ 和 A等级，日均销量变动较大，按照2天日均销量补；
             * B和C，C+等级按照5天的日均销量来补货;
             * D和E等级按照30天日均销量补货，生产入库周期按照7天；
             *
             * 计划售卖周期    计划售卖周期至少是生产入库周期的1倍
             * A+ 按照计划售卖周期的1.5倍来补
             * A和 B,C+等级按照计划售卖周期的1.3/1.2/1.1倍来补
             * C和D和E等级按照计划售卖周期的1倍来补
             * 补货量=日均销量*生产入库周期+日均销量*计划售卖周期-实时库存-库存在途
             */
            foreach ($list as &$v) {
                $onway_stock = $product[$v['true_sku']]['on_way_stock'] ?? 0;
                $v['stock'] = $product[$v['true_sku']]['available_stock'];
                $v['purchase_qty'] = $onway_stock > 0 ? $onway_stock : 0;
                $v['replenish_days'] = $v['days_sales_num'] > 0 ? floor($v['stock'] / $v['days_sales_num']) : 0;
            }

            unset($v);

            $result = ["total" => $total, "rows" => $list];

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
        if ($all_num) {
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
        }

        $this->assign('res', $res);

        return $this->view->fetch();
    }


    /**
     * 查看
     */
    public function process()
    {
        $this->relationSearch = true;
        $this->model = new \app\admin\model\warehouse\Check;
        $this->check_item = new \app\admin\model\warehouse\CheckItem;
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
                $ids = $this->check_item->where($smap)->column('check_id');
                $map['check.id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            //是否存在需要退回产品
            $smap['unqualified_num'] = ['>', 0];
            $ids = $this->check_item->where($smap)->column('check_id');
            $map['check.id'] = ['in', $ids];
            $map['check.is_return'] = 0;
            $map['check.status'] = 2;

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();

            $total = $this->model
                ->with(['purchaseorder', 'supplier'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['purchaseorder', 'supplier'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 批量导入1688物流单号
     */
    public function logistics_info_import()
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
        $listName = ['订单编号', '物流公司运单号'];
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
                for ($currentColumn = 1; $currentColumn <= 11; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    if (!$val) {
                        continue;
                    }
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

        //批量导入物流单号
        $logistics = new \app\admin\model\LogisticsInfo();
        foreach ($data as $k => $v) {

            if (empty($v[0])) {
                $this->error('导入失败！！,1688单号不能为空');
            }
            if (empty($v[1])) {
                $this->error('导入失败！！,物流单号不能为空');
            }

            $row = $this->model->where(['1688_number' => $v[0]])->find();
            if (!$row) {
                $this->error('导入失败！！,1688单号' . $v[0] . '未查询到记录');
            }
            if ($row->purchase_status != 2) {
                $this->error('导入失败！！,1688单号' . $v[0] . '订单状态必须是已审核');
            }
            //拆分物流单号和物流公司
            $logistics_data = explode(':', $v[1]);
            $result = $this->model->where(['1688_number' => $v[0]])->update(['purchase_status' => 6, 'logistics_number' => $logistics_data[1], 'logistics_company_name' => $logistics_data[0], 'logistics_company_no' => $logistics_data[0], 'is_add_logistics' => 1]);

            $list = [];
            $list['order_number'] = $row->purchase_number;
            $list['purchase_id'] = $row->id;
            $list['type'] = 1;
            $list['logistics_number'] = $logistics_data[1];
            $list['logistics_company_no'] = $logistics_data[0];
            $logistics->addLogisticsInfo($list);
        }

        if ($result) {
            $this->success('导入成功！！');
        } else {
            $this->error('导入失败！！');
        }
    }


    /**
     * 导入镜片库存
     */
    public function import()
    {
    }

    //批量导出xls
    public function process_export_xls()
    {
        $this->model = new \app\admin\model\warehouse\Check;
        $this->check_item = new \app\admin\model\warehouse\CheckItem;
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids_all = input('ids');
        //自定义sku搜索
        $filter = json_decode($this->request->get('filter'), true);
        if ($filter['sku']) {
            $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
            $ids = $this->check_item->where($smap)->column('check_id');
            $map['check.id'] = ['in', $ids];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['createtime']) {
            $arr = explode(' ', $filter['createtime']);
            $map['check.createtime'] = ['between', [$arr[0] . ' ' . $arr[1], $arr[3] . ' ' . $arr[4]]];
            unset($filter['createtime']);
            $this->request->get(['filter' => json_encode($filter)]);
        }
        //添加供货商名称搜索
        if ($filter['supplier.supplier_name']) {
            $map['s.supplier_name'] = ['like', '%' . $filter['supplier.supplier_name'] . '%'];
            unset($filter['supplier.supplier_name']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        //是否存在需要退回产品
        $check_map['unqualified_num'] = ['>', 0];
        $ids = $this->check_item->where($check_map)->column('check_id');
        $map['check.id'] = ['in', $ids];
        $map['check.is_return'] = 0;
        $map['check.status'] = 2;

        if ($ids_all) {
            $map['check.id'] = ['in', $ids_all];
        }

        [$where] = $this->buildparams();
        $list = $this->model->alias('check')
            ->join(['fa_purchase_order' => 'd'], 'check.purchase_id=d.id')
            ->join(['fa_supplier' => 's'], 's.id=d.supplier_id')
            ->join(['fa_check_order_item' => 'b'], 'b.check_id=check.id')
            ->join(['fa_purchase_order_item' => 'c'], 'b.purchase_id=c.purchase_id and c.sku=b.sku')
            ->field('check.*,b.*,c.purchase_price,d.purchase_number,d.create_person as person,d.purchase_remark')
            ->where($where)
            ->where($map)
            ->order('check.id desc')
            ->select();
        $list = collection($list)->toArray();


        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier();
        $supplier_data = $supplier->getSupplierData();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "质检单号")
            ->setCellValue("B1", "质检单类型")
            ->setCellValue("C1", "采购单号");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "采购创建人")
            ->setCellValue("E1", "退货单号");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "供应商")
            ->setCellValue("G1", "采购备注");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "质检备注")
            ->setCellValue("I1", "SKU")
            ->setCellValue("J1", "供应商SKU")
            ->setCellValue("K1", "单价");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("L1", "采购数量")
            ->setCellValue("M1", "到货数量")
            ->setCellValue("N1", "合格数量")
            ->setCellValue("O1", "不合格数量")
            ->setCellValue("P1", "创建人")
            ->setCellValue("Q1", "创建时间");

        $spreadsheet->setActiveSheetIndex(0)->setTitle('质检单数据');

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['check_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['type'] == 1 ? '采购质检' : '退货质检');
            $spreadsheet->getActiveSheet()->setCellValueExplicit("C" . ($key * 1 + 2), $value['purchase_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['person']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), '');
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $supplier_data[$value['supplier_id']]);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['purchase_remark']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['remark']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['supplier_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['purchase_price']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['purchase_num']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['arrivals_num']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['quantity_num']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['unqualified_num']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['createtime']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);

        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);


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

        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '质检单数据' . date("YmdHis", time());;
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


    /**
     * 批量导出xls
     *
     * @Description
     * @return void
     * @since  2020/02/28 14:45:39
     * @author wpl
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

        //添加供货商名称搜索
        if ($filter['supplier.supplier_name']) {
            $map['c.supplier_name'] = ['like', '%' . $filter['supplier.supplier_name'] . '%'];
            unset($filter['supplier.supplier_name']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        [$where] = $this->buildparams();
        $list = $this->model->alias('purchase_order')
            ->field('purchase_order.id,effect_time,type,logistics_info,receiving_time,purchase_number,purchase_name,supplier_name,sku,supplier_sku,is_new_product,is_sample,product_total,purchase_freight,purchase_num,purchase_price,purchase_remark,b.purchase_total,purchase_order.create_person,purchase_order.createtime,arrival_time,receiving_time,1688_number,replenish_id,purchase_order.purchase_type,purchase_order.factory_type,purchase_order.pay_type')
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
            'label'  => 1,
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
            ->setCellValue("I1", "采购运费")
            ->setCellValue("J1", "创建人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "创建时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("L1", "生产周期");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("M1", "预计出货时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("N1", "实际到货时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("O1", "是否新品采购");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("P1", "是否留样采购");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("Q1", "总计");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("R1", "1688单号");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("S1", "是否大货");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("T1", "揽件时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("U1", "订单生效时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("V1", "工厂类型");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("W1", "采购单类型");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("X1", "采购单付款类型");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("Y1", "需求提出时间");

        $logistic = new \app\admin\model\warehouse\LogisticsInfo();
        foreach ($list as $key => $value) {
            $logistics_info = [];
            $readly_logistics_info = [];
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['purchase_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['purchase_name']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['supplier_name']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['supplier_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['purchase_num']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['purchase_price']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['purchase_remark']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['purchase_freight']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['createtime']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $info[$value['sku']] ?: 7);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['arrival_time']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['receiving_time']);
            if ($value['is_new_product'] == 1) {
                $is_new_product = '是';
            } else {
                $is_new_product = '否';
            }
            if ($value['type'] == 1) {
                $value['type'] = '否';
            } else {
                $value['type'] = '是';
            }
            if ($value['is_sample'] == 1) {
                $is_sample = '是';
            } else {
                $is_sample = '否';
            }
            $payTypeName = '未知';
            switch ($value['pay_type']) {
                case 1:
                    $payTypeName = '预付款';
                    break;
                case 2:
                    $payTypeName = '全款预付';
                    break;
                case 3:
                    $payTypeName = '货到付款';
                    break;
            }

            if ($value['factory_type'] == 1) {
                $factoryTypeName = '贸易';
            } else {
                $factoryTypeName = '工厂';
            }

            if ($value['purchase_type'] == 1) {
                $purchaseTypeName = '线下采购单';
            } else {
                $purchaseTypeName = '线上采购单';
            }


            //查询揽收时间
            $collect_time = $logistic->where(['purchase_id' => $value['id']])->value('collect_time');
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $is_new_product);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $is_sample);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['purchase_total']);
            $spreadsheet->getActiveSheet()->setCellValueExplicit("R" . ($key * 1 + 2), $value['1688_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['type']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 1 + 2), $collect_time);
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 1 + 2), $value['effect_time']);
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 1 + 2), $factoryTypeName);
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 1 + 2), $purchaseTypeName);
            $spreadsheet->getActiveSheet()->setCellValue("X" . ($key * 1 + 2), $payTypeName);
            $replenishAddTime = Db::name('new_product_replenish')->where('id', $value['replenish_id'])->value('create_time');
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($key * 1 + 2), $replenishAddTime);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('W')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('X')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Y')->setWidth(30);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:X' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
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
     * 批量导出xls
     *
     * @Description
     * @return void
     * @since  2020/02/28 14:45:39
     * @author wpl
     */
    public function batch_export_test()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map['a.createtime'] = ['between', ['2020-02-01 00:00:00', '2020-08-15 00:00:00']];
        $map['stock_status'] = ['in', [1, 2]];
        [$where] = $this->buildparams();
        $list = $this->model->alias('a')
            ->field('purchase_number,b.sku,purchase_num,in_stock_num,d.check_time,purchase_remark')
            ->join(['fa_purchase_order_item' => 'b'], 'b.purchase_id=a.id')
            ->join(['fa_in_stock_item' => 'c'], 'c.purchase_id=a.id and c.sku=b.sku')
            ->join(['fa_in_stock' => 'd'], 'd.id=c.in_stock_id')
            ->where($where)
            ->where($map)
            ->select();
        $list = collection($list)->toArray();
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "采购单号")
            ->setCellValue("B1", "SKU")
            ->setCellValue("C1", "采购数量");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "入库数量")
            ->setCellValue("E1", "入库时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "备注");

        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['purchase_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['purchase_num']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['in_stock_num']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['check_time']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['purchase_remark']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(35);


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

        $spreadsheet->getActiveSheet()->getStyle('A1:F' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
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
        $this->relationSearch = true;
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $whereCondition['purchase_status'] = ['egt', 2];
            $whereCondition['is_in_stock'] = ['eq', 0];
            $whereConditionOr = [];
            $rep = $this->request->get('filter');
            //如果没有搜索条件
            if ($rep != '{}') {
                $whereTotalId = '1=1';
                $filter = json_decode($rep, true);
                //付款人
                if ($filter['pay_person']) {
                    $workIds = Purchase_order_pay::where(['create_person' => $filter['pay_person']])->column('purchase_id');
                    $whereCondition['purchase_order.id'] = ['in', $workIds];
                    unset($filter['pay_person']);
                }
                if ($filter['pay_time']) {
                    $time = explode(' ', $filter['pay_time']);
                    $mapTime['create_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
                    $measuerWorkIds = Purchase_order_pay::where($mapTime)->column('purchase_id');
                    if (!empty($whereCondition['id'])) {
                        $newWorkIds = array_intersect($workIds, $measuerWorkIds);
                        $whereCondition['purchase_order.id'] = ['in', $newWorkIds];
                    } else {
                        $whereCondition['purchase_order.id'] = ['in', $measuerWorkIds];
                    }
                    $whereConditionOr['purchase_order.payment_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
                    unset($filter['pay_time']);
                }
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $whereTotalId['purchase_order.createtime'] = ['between', [date('Y-m-d 00:00:00', strtotime('-6 day')), date('Y-m-d H:i:s', time())]];
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->whereor($whereConditionOr)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->whereor($whereConditionOr)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //查询总共的ID    
            $totalId = $this->model
                ->with(['supplier'])
                ->where($whereCondition)
                ->where($whereTotalId)
                ->where($where)
                ->whereor($whereConditionOr)
                ->column('purchase_order.id');
            //这个页面的ID    
            $thisPageId = $this->model
                ->with(['supplier'])
                ->where($whereCondition)
                ->where($where)
                ->whereor($whereConditionOr)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->column('purchase_order.id');
            $list = collection($list)->toArray();
            //求出所有的总共的实际采购总额和本页面的实际采购金额
            $purchaseMoney = $this->model->calculatePurchaseOrderMoney($totalId, $thisPageId);
            // echo '<pre>';
            // var_dump($purchaseMoney);
            // exit;
            //求出退款金额信息
            $returnMoney = $this->model->calculatePurchaseReturnMoney($totalId, $thisPageId);
            if (is_array($returnMoney['thisPageArr'])) {
                foreach ($list as $keys => $vals) {
                    if (array_key_exists($vals['id'], $returnMoney['thisPageArr'])) {
                        //采购单的退款金额 
                        $list[$keys]['refund_amount'] = round($returnMoney['thisPageArr'][$vals['id']], 2) ?: 0;
                    }
                }
            }
            if (is_array($purchaseMoney['thisPageArr'])) {
                foreach ($list as $key => $val) {
                    if (array_key_exists($val['id'], $purchaseMoney['thisPageArr'])) {
                        //采购单的实际采购金额 
                        $list[$key]['purchase_virtual_total'] = round($purchaseMoney['thisPageArr'][$val['id']] + $val['purchase_freight'], 2) ?: 0;
                        //采购单实际结算金额(如果存在实际采购金额要从实际采购金额扣减)
                        $list[$key]['purchase_settle_money'] = round($list[$key]['purchase_virtual_total'] - $list[$key]['refund_amount'], 2) ?: 0;
                    } else {
                        //采购单实际结算金额(如果不存在实际采购金额要从采购金额中扣减) 
                        $list[$key]['purchase_settle_money'] = round(($list[$key]['purchase_total'] - $list[$key]['refund_amount']), 2) ?: 0;
                    }
                }
            }
            $result = ["total" => $total, "rows" => $list, "total_money" => $purchaseMoney['total_money'] ?: 0, "return_money" => $returnMoney['return_money'] ?: 0];

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
        $this->view->assign("refund_amount", $refund_amount ?: 0);
        $this->view->assign("purchase_settle_money", $purchase_settle_money ?: 0);
        $this->view->assign("purchase_virtual_total", $purchase_virtual_total ?: 0);

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
                $params['purchase_id'] = $row['id'];
                $params['create_person'] = session('admin.nickname');
                $params['create_time'] = date('Y-m-d H:i:s', time());
                $result = (new purchase_order_pay())->allowField(true)->save($params);
                if ($result) {
                    return $this->success('添加成功');
                }
            } else {
                return $this->error('添加失败');
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


    //采购单数据导出
    public function export_sku_many()
    {
        $purchase_order_item = Db::name('purchase_order_item')->order('id desc')->column('purchase_id,sku');
        $purchase_order_item = array_unique($purchase_order_item);
        $data = [];
        foreach ($purchase_order_item as $key => $item) {
            $where['id'] = ['eq', $key];
            $whe['purchase_id'] = ['eq', $key];

            $val = Db::name('purchase_order')->where($where)->field('type,arrival_time')->find();
            $check_order = Db::name('check_order')->where($whe)->order('id desc')->value('id');
            $check_time = Db::name('in_stock')->where('check_id', $check_order)->order('id desc')->value('check_time');
            $data[$key]['id'] = $key;
            if ($val['type'] == 1) {
                $data[$key]['type'] = '现货';
            } else {
                $data[$key]['type'] = '大货';
            }
            $data[$key]['createtime'] = $val['arrival_time'];
            $data[$key]['check_time'] = $check_time;
            $data[$key]['sku'] = $item;
        }
        $data = array_values($data);
        $headlist = ['id', '类型', '预计出货时间', '入库单审核完成时间', 'SKU'];
        $path = "/uploads/";
        $fileName = '导出所有SKU数据';

        Excel::writeCsv($data, $headlist, $path . $fileName);
    }

    //所有的 仓库SKU、最近一次采购单中对应的大货/现货
    public function export_sku_many_type()
    {
        $purchase_order_item = Db::name('purchase_order_item')->order('id desc')->column('purchase_id,sku');
        $purchase_order_item = array_unique($purchase_order_item);
        $data = [];
        foreach ($purchase_order_item as $key => $item) {
            $where['b.sku'] = ['eq', $item];
            $where['a.type'] = 2;
            $count = Db::name('purchase_order')->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->where($where)->count();
            $data[$key]['id'] = $key;
            if ($count > 0) {
                $data[$key]['type'] = '大货';
            } else {
                $data[$key]['type'] = '现货';
            }
            $data[$key]['sku'] = $item;
        }
        $data = array_values($data);
        $headlist = ['id', '类型', 'SKU'];
        $path = "/uploads/";
        $fileName = '导出所有SKU对应的大货现货数据';

        Excel::writeCsv($data, $headlist, $path . $fileName);
    }


}
