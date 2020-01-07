<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 质检单
 *
 * @icon fa fa-circle-o
 */
class Check extends Backend
{

    /**
     * Check模型对象
     * @var \app\admin\model\warehouse\Check
     */
    protected $model = null;

    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\Check;
        $this->check_item = new \app\admin\model\warehouse\CheckItem;
        $this->purchase = new \app\admin\model\purchase\PurchaseOrder;
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
            if ($filter['is_process'] || $filter['is_process'] == '0') {

                $smap['unqualified_num'] = $filter['is_process'] == 1 ? ['>', 0] : ['=', 0];

                $ids = $this->check_item->where($smap)->column('check_id');
                $map['check.id'] = ['in', $ids];

                $map['check.is_return'] = $filter['is_process'] == 1 ? 0 : 1;

                unset($filter['is_process']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['purchaseorder', 'supplier', 'orderreturn'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['purchaseorder', 'supplier', 'orderreturn'])
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
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);

                    //添加质检产品
                    if ($result !== false) {

                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");
                        $purchase_num = $this->request->post("purchase_num/a");
                        $check_num = $this->request->post("check_num/a");
                        $arrivals_num = $this->request->post("arrivals_num/a");
                        $quantity_num = $this->request->post("quantity_num/a");
                        $sample_num = $this->request->post("sample_num/a");
                        $remark = $this->request->post("remark/a");
                        $unqualified_images = $this->request->post("unqualified_images/a");
                        $unqualified_num = $this->request->post("unqualified_num/a");
                        $quantity_rate = $this->request->post("quantity_rate/a");
                        //新增采购单ID create@lsw
                        $purchase_id = $this->request->post("purchase_id/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            if ($supplier_sku[$k]) {
                                $data[$k]['supplier_sku'] = $supplier_sku[$k];
                            }

                            if ($product_name[$k]) {
                                $data[$k]['product_name'] = $product_name[$k];
                            }
                            //新增采购单ID create@lsw
                            $data[$k]['purchase_id']  = $purchase_id[$k];
                            $data[$k]['purchase_num'] = $purchase_num[$k] ?? 0;
                            $data[$k]['check_num'] = $check_num[$k] ?? 0;
                            $data[$k]['arrivals_num'] = $arrivals_num[$k] ?? 0;
                            $data[$k]['quantity_num'] = $quantity_num[$k] ?? 0;
                            $data[$k]['sample_num'] = $sample_num[$k] ?? 0;
                            $data[$k]['remark'] = $remark[$k];
                            $data[$k]['unqualified_images'] = $unqualified_images[$k];
                            $data[$k]['unqualified_num'] = $unqualified_num[$k];
                            $data[$k]['quantity_rate'] = $quantity_rate[$k];
                            $data[$k]['check_id'] = $this->model->id;
                        }
                        //批量添加
                        $this->check_item->allowField(true)->saveAll($data);
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
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //采购单id
        $purchase_id = input('purchase_id');
        if ($purchase_id) {
            $this->assign('purchase_id', $purchase_id);
        }

        //查询物流单检索表数据
        $ids = input('ids');
        if ($ids && !$purchase_id) {
            $logisticsinfo = new \app\admin\model\warehouse\LogisticsInfo;
            $info = $logisticsinfo->get($ids);
            $this->assign('info', $info);
        }


        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseData();
        $this->assign('purchase_data', $purchase_data);

        //查询退货单
        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
        $orderReturnData = $orderReturn->getOrderReturnData();
        $this->assign('order_return_data', $orderReturnData);

        //质检单
        $check_order_number = 'QC' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('check_order_number', $check_order_number);
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

                    $sku = $this->request->post("sku/a");
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }


                    $result = $row->allowField(true)->save($params);

                    //添加产品
                    if ($result !== false) {
                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");
                        $purchase_num = $this->request->post("purchase_num/a");
                        $check_num = $this->request->post("check_num/a");
                        $arrivals_num = $this->request->post("arrivals_num/a");
                        $quantity_num = $this->request->post("quantity_num/a");
                        $sample_num = $this->request->post("sample_num/a");
                        $remark = $this->request->post("remark/a");
                        $unqualified_images = $this->request->post("unqualified_images/a");
                        $item_id = $this->request->post("item_id/a");
                        $unqualified_num = $this->request->post("unqualified_num/a");
                        $quantity_rate = $this->request->post("quantity_rate/a");

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = $supplier_sku[$k];
                            $data[$k]['product_name'] = $product_name[$k];
                            $data[$k]['purchase_num'] = $purchase_num[$k] ?? 0;
                            $data[$k]['check_num'] = $check_num[$k] ?? 0;
                            $data[$k]['arrivals_num'] = $arrivals_num[$k] ?? 0;
                            $data[$k]['quantity_num'] = $quantity_num[$k] ?? 0;
                            $data[$k]['sample_num'] = $sample_num[$k] ?? 0;
                            $data[$k]['remark'] = $remark[$k];
                            $data[$k]['unqualified_images'] = $unqualified_images[$k];
                            $data[$k]['unqualified_num'] = $unqualified_num[$k];
                            $data[$k]['quantity_rate'] = $quantity_rate[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['check_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->check_item->allowField(true)->saveAll($data);
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
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseData();
        $this->assign('purchase_data', $purchase_data);

        //查询退货单
        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
        $orderReturnData = $orderReturn->getOrderReturnData();
        $this->assign('order_return_data', $orderReturnData);

        //查询质检单商品信息
        $check_item = new \app\admin\model\warehouse\CheckItem;
        $map['check_id'] = $ids;
        $item = $check_item->where($map)->select();
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseData();
        $this->assign('purchase_data', $purchase_data);

        //查询退货单
        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
        $orderReturnData = $orderReturn->getOrderReturnData();
        $this->assign('order_return_data', $orderReturnData);

        //查询质检单商品信息
        $check_item = new \app\admin\model\warehouse\CheckItem;
        $map['check_id'] = $ids;
        $item = $check_item->where($map)->select();
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 上传
     */
    public function uploads()
    {

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $this->success('', '', $params);
        }
        $img_url = $this->request->get('img_url');
        $this->assign('img_url', $img_url);
        return $this->view->fetch();
    }

    /**
     * 获取采购单商品信息
     */
    public function getPurchaseData()
    {
        $id = input('id');
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $data = $purchase->get($id);

        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $id;
        $item = $purchase_item->where($map)->select();
        //查询质检数量
        $skus = array_column($item, 'sku');
        //查询质检信息
        $check_map['Check.purchase_id'] = $id;
        $check_map['type'] = 1;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', $skus]])
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as check_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            @$check_item[$v['sku']]['check_num'] = $v['check_num'];
        }

        foreach ($item as $k => $v) {
            $item[$k]['check_num'] = @$check_item[$v['sku']]['check_num'] ?? 0;
        }

        $data->item = $item;
        if ($data) {
            $this->success('', '', $data);
        } else {
            $this->error();
        }
    }

    /**
     * 获取退货单商品信息
     */
    public function getOrderReturnData()
    {
        $id = input('id');
        //查询退货单商品信息
        $orderReturnItem = new \app\admin\model\saleaftermanage\OrderReturnItem;
        $map['order_return_id'] = $id;
        $list = $orderReturnItem->where($map)->alias('a')->field('b.order_platform,a.*')->join(['fa_order_return' => 'b'], 'a.order_return_id = b.id')->select();
        $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        //平台SKU转商品SKU
        foreach ($list as $k => $v) {
            $return_sku = $ItemPlatformSku->getTrueSku($v['return_sku'], $v['order_platform']);
            $list[$k]['return_sku'] = $return_sku ?? '';
        }
        if ($list) {
            $this->success('', '', $list);
        } else {
            $this->error('未查询到数据！！');
        }
    }

    //删除质检单商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->check_item->destroy($id);
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
            if ($v['status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

        if ($res) {
            if ($data['status'] == 2) {

                //查询对应采购单总采购数量 以及总到货数量
                foreach ($row as $k => $v) {
                    //采购质检
                    if ($v['purchase_id']) {
                        //查询质检信息
                        $check_map['Check.purchase_id'] = $v['purchase_id'];
                        $check_map['type'] = 1;
                        $check = new \app\admin\model\warehouse\Check;
                        //总到货数量
                        $all_arrivals_num = $check->hasWhere('checkItem')->where($check_map)->group('Check.purchase_id')->sum('arrivals_num');

                        //查询总采购数量
                        $purchaseItem = new \app\admin\model\purchase\PurchaseOrderItem;
                        $all_purchase_num = $purchaseItem->where('purchase_id', $v['purchase_id'])->sum('purchase_num');

                        //已质检数量+到货数量 小于 采购单采购数量 则为部分质检
                        if ($all_arrivals_num < $all_purchase_num) {
                            $check_status = 1;
                        } else {
                            $check_status = 2;
                        }
                        $purchase = new \app\admin\model\purchase\PurchaseOrder;
                        //修改采购单质检状态
                        $purchase_data['check_status'] = $check_status;
                        $purchase->allowField(true)->save($purchase_data, ['id' => $v['purchase_id']]);
                    }

                    //退货质检
                    if ($v['order_return_id']) {
                        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
                        $orderReturn->allowField(true)->save(['quality_status' => 1], ['id' => $v['order_return_id']]);
                    }
                }
            }

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
        if ($row['status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }

    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['status'] != 0) {
                $this->error('此商品状态不能提交审核');
            }

            $map['id'] = $id;
            $data['status'] = 1;
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
     * 批量生成退销单
     */
    public function add_return_order()
    {
        $ids = input('ids');
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");

            //查询质检单
            $where['a.id'] = ['in', $ids];
            $where['b.unqualified_num'] = ['>', 0];
            $res = $this->model->alias('a')->field('b.check_id,b.id,a.purchase_id,c.purchase_num,c.purchase_price,c.purchase_total,b.supplier_sku,b.sku,b.unqualified_num,b.remark')->where($where)
                ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')
                ->join(['fa_purchase_order_item' => 'c'], 'a.purchase_id=c.purchase_id and b.sku=c.sku')
                ->select();
            $res = collection($res)->toArray();

            $list = [];
            foreach ($res as $k => $v) {
                $list[$v['purchase_id']][$k] = $v;
            }
            unset($res);
            $return_model = new \app\admin\model\purchase\PurchaseReturn;
            $return_model_item = new \app\admin\model\purchase\PurchaseReturnItem;

            $check_id = [];
            foreach ($list as $k => $v) {
                $params['return_number'] = 'RO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $params['purchase_id'] = $k;
                $params['create_person'] = session('admin.nickname');
                $params['createtime'] = date('Y-m-d H:i:s', time());
                $result = $return_model->allowField(true)->isUpdate(false)->data($params)->save();

                $i = 0;
                $info = [];
                $return_money = 0;
                $purchase_total = 0;
                $check_id_params = [];
                foreach ($v as $val) {
                    $info[$i]['sku'] = $val['sku'];
                    $info[$i]['return_num'] = $val['unqualified_num'];
                    $info[$i]['return_id'] = $return_model->id;
                    $info[$i]['check_item_id'] = $val['id'];
                    $i++;

                    $return_money  += round($val['unqualified_num'] * $val['purchase_price'], 2);
                    $purchase_total += $val['purchase_total'];

                    $check_id[] = $val['check_id'];

                    $check_id_params[] = $val['check_id'];
                }
                if ($info) {
                    $return_model_item->allowField(true)->saveAll($info);

                    if ($check_id_params) {
                        $check_ids = implode(',', array_unique($check_id_params));
                    }
                    //填充退货金额  采购总金额
                    $return_model->allowField(true)->isUpdate(true, ['id' => $return_model->id])->save(['return_money' => $return_money, 'purchase_total' => $purchase_total, 'check_ids' => $check_ids]);
                }
            }

            if ($result !== false) {

                //标记为已退
                $map['id'] = ['in', array_unique($check_id)];
                $this->model->allowField(true)->isUpdate(true, $map)->save(['is_return' => 1]);
                $this->success('操作成功');
            } else {
                $this->success('操作失败');
            }
        }

        $this->assign('ids', $ids);

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        return $this->fetch('add_return_order');
    }
}
