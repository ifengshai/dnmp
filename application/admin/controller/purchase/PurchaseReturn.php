<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 退销单管理
 *
 * @icon fa fa-circle-o
 */
class PurchaseReturn extends Backend
{

    /**
     * PurchaseReturn模型对象
     * @var \app\admin\model\purchase\PurchaseReturn
     */
    protected $model = null;

    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\PurchaseReturn;
        $this->purchase_return_item = new \app\admin\model\purchase\PurchaseReturnItem;
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
                ->with(['purchaseorder', 'supplier'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['purchaseorder', 'supplier'])
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

                    $sku = $this->request->post("sku/a");
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);
                    //添加产品信息
                    if ($result !== false) {
                        $return_num = $this->request->post("return_num/a");
                        $item_id = $this->request->post("item_id/a");

                        $data = [];
                        if ($sku) {
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['return_num'] = $return_num[$k];
                                $data[$k]['return_id'] = $this->model->id;
                                $data[$k]['check_item_id'] = $item_id[$k];
                            }
                            //批量添加
                            $this->purchase_return_item->allowField(true)->saveAll($data);
                        }

                        //标记质检单明细表为已处理
                        if ($item_id) {
                            $check = new \app\admin\model\warehouse\CheckItem;
                            $check->allowField(true)->save(['is_process' => 1], ['id' => ['in', $item_id]]);
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
                    $this->error(__('No rows were inserted'));
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
        $purchase_data = $purchase->getPurchaseReturnData([1, 2], '', [0, 1, 2]);
        $this->assign('purchase_data', $purchase_data);


        //质检单
        $return_number = 'RO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('return_number', $return_number);


        $id = input('ids');
        $this->assign('id', $id);
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

        //判断状态是否为新建
        if ($row['status'] > 0) {
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
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $result = $row->allowField(true)->save($params);

                    //添加合同产品
                    if ($result !== false) {
                        $return_num = $this->request->post("return_num/a");
                        $item_id = $this->request->post("item_id/a");


                        $data = [];
                        if ($sku) {
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['return_num'] = $return_num[$k];
                                if (@$item_id[$k]) {
                                    $data[$k]['id'] = $item_id[$k];
                                }
                            }
                        }
                        //批量添加
                        $this->purchase_return_item->allowField(true)->saveAll($data);
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
        $purchase_data = $purchase->getPurchaseReturnData([1, 2], []);
        $this->assign('purchase_data', $purchase_data);


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = $ids;
        $return_arr = $this->purchase_return_item->where($return_item_map)->select();
        $return_arr = collection($return_arr)->toArray();

        $check_item_id = array_column($return_arr, 'check_item_id');
        $skus = array_column($return_arr, 'sku');
        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $row['purchase_id'];
        $map['sku'] = ['in', $skus];
        $item = $purchase_item->where($map)->column('*', 'sku');

        //查询质检信息
        $check_map['id'] = ['in', $check_item_id];
        $check = new \app\admin\model\warehouse\CheckItem;
        $list = $check
            ->where($check_map)
            ->column('*', 'id');


        // //查询已退数量
        // $return_map['purchase_id'] = $row['purchase_id'];
        // $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', array_keys($return_arr)]])
        //     ->where($return_map)
        //     ->group('sku')
        //     ->column('sum(return_num) as return_all_num', 'sku');

        foreach ($return_arr as $k => $v) {
            $return_arr[$k]['purchase_price'] = $item[$v['sku']]['purchase_price'];
            $return_arr[$k]['supplier_sku'] = $item[$v['sku']]['supplier_sku'];
            $return_arr[$k]['purchase_num'] = $item[$v['sku']]['purchase_num'];
            $return_arr[$k]['arrivals_num'] = $list[$v['check_item_id']]['arrivals_num'];
            $return_arr[$k]['quantity_num'] = $list[$v['check_item_id']]['quantity_num'];
            $return_arr[$k]['unqualified_num'] = $list[$v['check_item_id']]['unqualified_num'];
        }

        /***********end***************/
        $this->assign('item', $return_arr);
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
        $purchase_data = $purchase->getPurchaseReturnData([1, 2], []);
        $this->assign('purchase_data', $purchase_data);


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = $ids;
        $return_arr = $this->purchase_return_item->where($return_item_map)->select();
        $return_arr = collection($return_arr)->toArray();

        $check_item_id = array_column($return_arr, 'check_item_id');
        $skus = array_column($return_arr, 'sku');
        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $row['purchase_id'];
        $map['sku'] = ['in', $skus];
        $item = $purchase_item->where($map)->column('*', 'sku');

        //查询质检信息
        $check_map['id'] = ['in', $check_item_id];
        $check = new \app\admin\model\warehouse\CheckItem;
        $list = $check
            ->where($check_map)
            ->column('*', 'id');


        // //查询已退数量
        // $return_map['purchase_id'] = $row['purchase_id'];
        // $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', array_keys($return_arr)]])
        //     ->where($return_map)
        //     ->group('sku')
        //     ->column('sum(return_num) as return_all_num', 'sku');

        foreach ($return_arr as $k => $v) {
            $return_arr[$k]['purchase_price'] = $item[$v['sku']]['purchase_price'];
            $return_arr[$k]['supplier_sku'] = $item[$v['sku']]['supplier_sku'];
            $return_arr[$k]['purchase_num'] = $item[$v['sku']]['purchase_num'];
            $return_arr[$k]['arrivals_num'] = $list[$v['check_item_id']]['arrivals_num'];
            $return_arr[$k]['quantity_num'] = $list[$v['check_item_id']]['quantity_num'];
            $return_arr[$k]['unqualified_num'] = $list[$v['check_item_id']]['unqualified_num'];
        }

        /***********end***************/
        $this->assign('item', $return_arr);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 异步获取采购退销信息
     */
    public function getPurchaseData()
    {
        $id = input('id');
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $data = $purchase->get($id);

        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $id;
        $item = $purchase_item->where($map)->column('*', 'sku');

        $skus = array_column($item, 'sku');
        //查询质检信息 
        //不合格数量不等于0 并且 未处理过退销 审核通过的质检单信息
        $check_map['purchase_id'] = $id;
        $check_map['type'] = 1;
        $check_map['status'] = 2; //已审核
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', $skus], 'unqualified_num' => ['<>', 0], 'is_process' => 0])
            ->where($check_map)
            ->field('sku,arrivals_num,quantity_num,unqualified_num,CheckItem.id as ids')
            ->group('CheckItem.id')
            ->select();
        $list = collection($list)->toArray();

        foreach ($list as $k => $v) {

            $list[$k]['purchase_price'] = $item[$v['sku']]['purchase_price'];
            $list[$k]['supplier_sku'] = $item[$v['sku']]['supplier_sku'];
            $list[$k]['purchase_num'] = $item[$v['sku']]['purchase_num'];
        }

        $data->item = $list;
        if ($list) {
            $this->success('', '', $data);
        } else {
            $this->error('未查询到此采购单需要退销的数据');
        }
    }

    //删除合同里商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->purchase_return_item->destroy($id);
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
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        foreach ($row as $v) {
            if ($status == 1 || $status == 5) {
                if ($v['status'] !== 0) {
                    $this->error('只有新建状态才能操作！！');
                }
            } elseif ($status == 3) {
                if ($v['status'] != 2) {
                    $this->error('只有已发货状态才能操作！！');
                }
            }
        }

        $data['status'] = $status;
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res !== false) {

            foreach ($row as $v) {
                //求和不合格数量
                if ($v['purchase_id']) {
                    //查询不合格数量
                    $check_map['purchase_id'] = $v['purchase_id'];
                    $check_map['type'] = 1;
                    $check = new \app\admin\model\warehouse\Check;
                    $all_unqualified_num = $check->hasWhere('checkItem')
                        ->where($check_map)
                        ->group('Check.purchase_id')
                        ->sum('unqualified_num');

                    //查询退销总数量
                    $return_map['purchase_id'] = $v['purchase_id'];
                    $all_return_num = $this->model->hasWhere('purchaseReturnItem')
                        ->where($return_map)
                        ->group('PurchaseReturn.purchase_id')
                        ->sum('return_num');

                    //已退销数量+退销数量 小于 采购单不合格数量 则为部分退销
                    if ($all_return_num < $all_unqualified_num) {
                        $return_status = 1;
                    } else {
                        $return_status = 2;
                    }


                    //查询采购单质检状态 如果为部分质检 则采购单必定为部分退销
                    $purchase = new \app\admin\model\purchase\PurchaseOrder;
                    $purchase_res = $purchase->get($v['purchase_id']);
                    if ($purchase_res['check_status'] == 1) {
                        $return_status = 1;
                    }

                    //修改采购单退销状态
                    $purchase_data['return_status'] = $return_status;
                    //查询采购单
                    $purchase = new \app\admin\model\purchase\PurchaseOrder;
                    $purchase->allowField(true)->save($purchase_data, ['id' => $v['purchase_id']]);
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
        if ($res !== false) {
            //查询处理的质检单明细表id
            $where['return_id'] = ['in', $ids];
            $check_item_ids = $this->purchase_return_item->where($where)->column('check_item_id');

            //取消退销单时 修改质检单明细表对应状态为未处理
            $checkItem = new \app\admin\model\warehouse\CheckItem;
            $checkItem->save(['is_process' => 0], ['id' => ['in', $check_item_ids]]);

            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }
}
