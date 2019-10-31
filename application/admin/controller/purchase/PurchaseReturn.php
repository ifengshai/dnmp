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
                ->with(['purchaseOrder', 'supplier'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['purchaseOrder', 'supplier'])
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

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);
                    //添加产品信息
                    if ($result !== false) {

                        $sku = $this->request->post("sku/a");
                        $return_num = $this->request->post("return_num/a");

                        //求和不合格数量
                        if ($params['purchase_id']) {
                            //查询不合格数量
                            $check_map['purchase_id'] = $params['purchase_id'];
                            $check_map['type'] = 1;
                            $check = new \app\admin\model\warehouse\Check;
                            $all_unqualified_num = $check->hasWhere('checkItem')
                                ->where($check_map)
                                ->sum('unqualified_num');

                            //查询退销总数量
                            $return_map['purchase_id'] = $params['purchase_id'];
                            $all_return_num = $this->model->hasWhere('purchaseReturnItem')
                                ->where($return_map)
                                ->sum('return_num');

                            $all_return_num = $all_return_num + array_sum($return_num);
                            //已退销数量+退销数量 小于 采购单不合格数量 则为部分退销
                            if ($all_return_num < $all_unqualified_num) {
                                $return_status = 1;
                            } else {
                                $return_status = 2;
                            }
                            //修改采购单退销状态
                            $purchase_data['return_status'] = $return_status;
                            //查询采购单
                            $purchase = new \app\admin\model\purchase\PurchaseOrder;
                            $purchase->allowField(true)->save($purchase_data, ['id' => $params['purchase_id']]);
                        }

                        $data = [];
                        if ($sku) {
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['return_num'] = $return_num[$k];
                                $data[$k]['return_id'] = $this->model->id;
                            }
                            //批量添加
                            $this->purchase_return_item->allowField(true)->saveAll($data);
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
        $purchase_data = $purchase->getPurchaseReturnData([1, 2],'');
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

                    //添加合同产品
                    if ($result !== false) {
                        $sku = $this->request->post("sku/a");
                        $return_num = $this->request->post("return_num/a");
                        $item_id = $this->request->post("item_id/a");

                        //求和不合格数量
                        if ($params['purchase_id']) {
                            //查询不合格数量
                            $check_map['purchase_id'] = $params['purchase_id'];
                            $check_map['type'] = 1;
                            $check = new \app\admin\model\warehouse\Check;
                            $all_unqualified_num = $check->hasWhere('checkItem')
                                ->where($check_map)
                                ->sum('unqualified_num');

                            //查询退销总数量
                            $return_map['purchase_id'] = $params['purchase_id'];
                            $all_return_num = $this->model->hasWhere('purchaseReturnItem')
                                ->where($return_map)
                                ->sum('return_num');

                            $all_return_num = $all_return_num + array_sum($return_num);
                            //已退销数量+退销数量 小于 采购单不合格数量 则为部分退销
                            if ($all_return_num < $all_unqualified_num) {
                                $return_status = 1;
                            } else {
                                $return_status = 2;
                            }
                            //修改采购单退销状态
                            $purchase_data['return_status'] = $return_status;
                            //查询采购单
                            $purchase = new \app\admin\model\purchase\PurchaseOrder;
                            $purchase->allowField(true)->save($purchase_data, ['id' => $params['purchase_id']]);
                        }

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
        $purchase_data = $purchase->getPurchaseReturnData([2], [1, 2]);
        $this->assign('purchase_data', $purchase_data);


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = $ids;
        $return_arr = $this->purchase_return_item->where($return_item_map)->column('return_num,id', 'sku');

        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $row['purchase_id'];
        $map['sku'] = ['in', array_keys($return_arr)];
        $item = $purchase_item->where($map)->select();

        //查询质检信息
        $check_map['purchase_id'] = $row['purchase_id'];
        $check_map['type'] = 1;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', array_keys($return_arr)]])
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'];
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'];
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'];
        }

        //查询已退数量
        $return_map['purchase_id'] = $row['purchase_id'];
        $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', array_keys($return_arr)]])
            ->where($return_map)
            ->group('sku')
            ->column('sum(return_num) as return_all_num', 'sku');

        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'];
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'];
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'];
            $item[$k]['return_all_num'] = @$return_item[$v['sku']] ? @$return_item[$v['sku']] : 0;
            $item[$k]['return_num'] = $return_arr[$v['sku']]['return_num'];
            $item[$k]['item_id'] = $return_arr[$v['sku']]['id'];
        }

        /***********end***************/
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
        $purchase_data = $purchase->getPurchaseReturnData([2], [1, 2]);
        $this->assign('purchase_data', $purchase_data);


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = $ids;
        $return_arr = $this->purchase_return_item->where($return_item_map)->column('return_num,id', 'sku');

        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $row['purchase_id'];
        $map['sku'] = ['in', array_keys($return_arr)];
        $item = $purchase_item->where($map)->select();

        //查询质检信息
        $check_map['purchase_id'] = $row['purchase_id'];
        $check_map['type'] = 1;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', array_keys($return_arr)]])
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'];
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'];
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'];
        }

        //查询已退数量
        $return_map['purchase_id'] = $row['purchase_id'];
        $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', array_keys($return_arr)]])
            ->where($return_map)
            ->group('sku')
            ->column('sum(return_num) as return_all_num', 'sku');

        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'];
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'];
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'];
            $item[$k]['return_all_num'] = @$return_item[$v['sku']] ? @$return_item[$v['sku']] : 0;
            $item[$k]['return_num'] = $return_arr[$v['sku']]['return_num'];
            $item[$k]['item_id'] = $return_arr[$v['sku']]['id'];
        }

        /***********end***************/
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 异步获取采购信息
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

        $skus = array_column($item, 'sku');

        //查询质检信息
        $check_map['purchase_id'] = $id;
        $check_map['type'] = 1;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', $skus]])
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'] ?? 0;
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'] ?? 0;
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'] ?? 0;
        }

        //查询已退数量
        $return_map['purchase_id'] = $id;
        $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', $skus]])
            ->where($return_map)
            ->group('sku')
            ->column('sum(return_num) as return_num', 'sku');

        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'];
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'];
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'];
            $item[$k]['return_num'] = @$return_item[$v['sku']] ? @$return_item[$v['sku']] : 0;
        }

        // $purchase_all_num = array_sum(array_column($item, 'purchase_num'));
        // $arrivals_all_num = array_sum(array_column($item, 'arrivals_num'));
        // if ($arrivals_all_num < $purchase_all_num) {
        //     $this->error('产品未到齐！！不能退销');
        // }

        $data->item = $item;
        if ($data) {
            $this->success('', '', $data);
        } else {
            $this->error();
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
}
