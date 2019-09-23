<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 入库单管理
 *
 * @icon fa fa-circle-o
 */
class Instock extends Backend
{

    /**
     * Instock模型对象
     * @var \app\admin\model\warehouse\Instock
     */
    protected $model = null;

    //当前是否为关联查询
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\Instock;
        $this->type = new \app\admin\model\warehouse\InstockType;
        $this->instockItem = new \app\admin\model\warehouse\InstockItem;
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
                ->with(['purchaseorder', 'instocktype'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['purchaseorder', 'instocktype'])
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

                    $params['create_person'] = session('admin.username');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {
                        $sku = $this->request->post("sku/a");
                        $in_stock_num = $this->request->post("in_stock_num/a");
                        $data = [];
                        foreach ($sku as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['in_stock_num'] = $in_stock_num[$k];
                            $data[$k]['no_stock_num'] = $in_stock_num[$k];
                            $data[$k]['in_stock_id'] = $this->model->id;
                        }
                        //批量添加
                        $this->instockItem->allowField(true)->saveAll($data);
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
        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseData();
        $this->assign('purchase_data', $purchase_data);

        //质检单
        $instock_number = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('instock_number', $instock_number);
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
        $check_map['purchase_id'] = $id;
        $check_map['type'] = 1;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', $skus]])
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num,sum(sample_num) as sample_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'];
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'];
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'];
            $check_item[$v['sku']]['sample_num'] = $v['sample_num'];
        }
        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'] ?? 0;
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'] ?? 0;
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'] ?? 0;
            $item[$k]['sample_num'] = $check_item[$v['sku']]['sample_num'] ?? 0;
        }

        $data->item = $item;
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
                    $result = $row->allowField(true)->save($params);

                    //修改产品
                    if ($result !== false) {
                        $sku = $this->request->post("sku/a");
                        $item_id = $this->request->post("item_id/a");
                        $in_stock_num = $this->request->post("in_stock_num/a");
                        $data = [];
                        foreach ($sku as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['in_stock_num'] = $in_stock_num[$k];
                            $data[$k]['no_stock_num'] = $in_stock_num[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['in_stock_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->instockItem->allowField(true)->saveAll($data);
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

        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseData();
        $this->assign('purchase_data', $purchase_data);


        /***********查询入库商品信息***************/
        //查询入库单商品信息
        $item_map['in_stock_id'] = $ids;
        $return_arr = $this->instockItem->where($item_map)->column('in_stock_num,id', 'sku');

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
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num,sum(sample_num) as sample_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'];
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'];
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'];
            $check_item[$v['sku']]['sample_num'] = $v['sample_num'];
        }

        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'];
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'];
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'];
            $item[$k]['sample_num'] = $check_item[$v['sku']]['sample_num'];
            $item[$k]['in_stock_num'] = $return_arr[$v['sku']]['in_stock_num'];
            $item[$k]['item_id'] = $return_arr[$v['sku']]['id'];
        }
        /***********end***************/
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 编辑
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

        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseData();
        $this->assign('purchase_data', $purchase_data);


        /***********查询入库商品信息***************/
        //查询入库单商品信息
        $item_map['in_stock_id'] = $ids;
        $return_arr = $this->instockItem->where($item_map)->column('in_stock_num,id', 'sku');

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
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num,sum(sample_num) as sample_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'];
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'];
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'];
            $check_item[$v['sku']]['sample_num'] = $v['sample_num'];
        }

        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'];
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'];
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'];
            $item[$k]['sample_num'] = $check_item[$v['sku']]['sample_num'];
            $item[$k]['in_stock_num'] = $return_arr[$v['sku']]['in_stock_num'];
            $item[$k]['item_id'] = $return_arr[$v['sku']]['id'];
        }
        /***********end***************/
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //删除入库单里的商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->instockItem->destroy($id);
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
        if ($data['status'] == 2) {
            $data['check_time'] = date('Y-m-d H:i:s', time());
        }
        $this->model->startTrans();
        $item = new \app\admin\model\itemmanage\Item;
        $item->startTrans();
        $purchase = new \app\admin\model\purchase\PurchaseOrderItem;
        $purchase->startTrans();
        try {
            $data['create_person'] = session('admin.username');
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            /**
             * @todo 审核通过增加库存 并添加入库单入库数量
             */

            //查询入库明细数据
            $list = $this->model->hasWhere('instockItem', ['in_stock_id' => ['in', $ids]])->field('sku,in_stock_num')->select();
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                //更新商品表商品总库存
                //总库存
                $item_map['sku'] = $v['sku'];
                $item->where($item_map)->setInc('stock', $v['in_stock_num']);
                //可用库存
                $item->where($item_map)->setInc('available_stock', $v['in_stock_num']);

                //更新采购商品表 入库数量 如果为真则为采购入库
                if ($v['purchase_id']) {
                    $purchase_map['sku'] = $v['sku'];
                    $purchase_map['purchase_id'] = $v['purchase_id'];
                    $purchase->where($purchase_map)->setInc('instock_num', $v['in_stock_num']);
                }
            }
            /**
             * @todo:更新采购入库状态 已入库 部分入库
             */
            


            $this->model->commit();
            $item->commit();
            $purchase->commit();
        } catch (ValidateException $e) {
            $this->model->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            $this->model->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->model->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->error($e->getMessage());
        }
        if ($res !== false) {
            $this->success();
        } else {
            $this->error();
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
}
