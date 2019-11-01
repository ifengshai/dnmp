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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['checkorder', 'instocktype'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['checkorder', 'instocktype'])
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
                        $sample_num = $this->request->post("sample_num/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['in_stock_num'] = $in_stock_num[$k];
                            $data[$k]['sample_num'] = $sample_num[$k];
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

        //查询质检单
        $check = new \app\admin\model\warehouse\Check;
        $purchase_data = $check->where('status', 2)->column('check_order_number', 'id');
        $this->assign('purchase_data', $purchase_data);

        //质检单
        $instock_number = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('instock_number', $instock_number);
        return $this->view->fetch();
    }


    /**
     * 获取质检单商品信息
     */
    public function getCheckData()
    {
        $id = input('id');
        //查询质检信息
        $check_map['Check.id'] = $id;
        $check_map['Check.status'] = 2;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem')
            ->where($check_map)
            ->field('sku,supplier_sku,purchase_num,check_num,arrivals_num,quantity_num,sample_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();

        if ($list) {
            $this->success('', '', $list);
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
                        foreach (array_filter($sku) as $k => $v) {
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

        //查询质检单
        $check = new \app\admin\model\warehouse\Check;
        $purchase_data = $check->where('status', 2)->column('check_order_number', 'id');
        $this->assign('purchase_data', $purchase_data);


        /***********查询入库商品信息***************/
        //查询入库单商品信息
        $item_map['in_stock_id'] = $ids;
        $item = $this->instockItem->where($item_map)->select();

        //查询对应质检数据
        $checkItem = new \app\admin\model\warehouse\CheckItem;
        $check_data = $checkItem->where('check_id', $row['check_id'])->column('*', 'sku');
        /***********end***************/
        $this->assign('item', $item);
        $this->assign('check_data', $check_data);
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

        //查询质检单
        $check = new \app\admin\model\warehouse\Check;
        $purchase_data = $check->where('status', 2)->column('check_order_number', 'id');
        $this->assign('purchase_data', $purchase_data);


        /***********查询入库商品信息***************/
        //查询入库单商品信息
        $item_map['in_stock_id'] = $ids;
        $item = $this->instockItem->where($item_map)->select();

        //查询对应质检数据
        $checkItem = new \app\admin\model\warehouse\CheckItem;
        $check_data = $checkItem->where('check_id', $row['check_id'])->column('*', 'sku');
        /***********end***************/
        $this->assign('item', $item);
        $this->assign('check_data', $check_data);
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

        //查询入库明细数据
        $list = $this->model->alias('a')
            ->join(['fa_in_stock_item' => 'b'], 'a.id=b.in_stock_id')
            ->where(['b.in_stock_id' => ['in', $ids]])
            ->select();
        $list = collection($list)->toArray();
        $skus = array_column($list, 'sku');
        //查询存在产品库的sku
        $item = new \app\admin\model\itemmanage\Item;
        $skus = $item->where(['sku' => ['in', $skus]])->column('sku');
        foreach ($list as $v) {
            if (!in_array($v['sku'], $skus)) {
                $this->error('此sku:' . $v['sku'] . '不存在！！');
            }
        }

        $this->model->startTrans();
        $item = new \app\admin\model\itemmanage\Item;
        $item->startTrans();
        $purchase = new \app\admin\model\purchase\PurchaseOrderItem;
        $purchase->startTrans();
        $this->purchase->startTrans();
        try {
            $data['create_person'] = session('admin.nickname');
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

            if ($data['status'] == 2) {
                /**
                 * @todo 审核通过增加库存 并添加入库单入库数量
                 */
                foreach ($list as $k => $v) {
                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    if ($v['sku']) {
                        $stock_res = $item->where($item_map)->setInc('stock', $v['in_stock_num']);
                        //可用库存
                        $available_stock_res = $item->where($item_map)->setInc('available_stock', $v['in_stock_num']);

                        $sample_num_res = $item->where($item_map)->setInc('sample_num', $v['sample_num']);
                    }

                    if ($stock_res === false || $available_stock_res === false || $sample_num_res === false) {
                        $error_num[] = $k;
                    }

                    //根据质检id 查询采购单id 
                    $check = new \app\admin\model\warehouse\Check;
                    $check_res = $check->where('id', $v['check_id'])->find();
                    //更新采购商品表 入库数量 如果为真则为采购入库
                    if ($check_res['purchase_id']) {
                        $purchase_map['sku'] = $v['sku'];
                        $purchase_map['purchase_id'] = $check_res['purchase_id'];
                        $purchase->where($purchase_map)->setInc('instock_num', $v['in_stock_num']);

                        //更新采购单状态 已入库 或 部分入库
                        //查询采购单商品总到货数量 以及采购数量
                        //查询质检信息
                        $check_map['purchase_id'] = $check_res['purchase_id'];
                        $check_map['type'] = 1;
                        $check = new \app\admin\model\warehouse\Check;
                        //总到货数量
                        $all_arrivals_num = $check->hasWhere('checkItem')->where($check_map)->sum('arrivals_num');

                        $all_purchase_num = $purchase->where('purchase_id', $check_res['purchase_id'])->sum('purchase_num');
                        //总入库数量 小于 采购单采购数量 则为部分入库 
                        if ($all_arrivals_num < $all_purchase_num) {
                            $stock_status = 1;
                        } else {
                            $stock_status = 2;
                        }
                        //修改采购单质检状态
                        $purchase_data['stock_status'] = $stock_status;
                        $this->purchase->allowField(true)->save($purchase_data, ['id' => $check_res['purchase_id']]);
                    }

                    //如果为退货单 修改退货单状态为入库
                    if ($check_res['order_return_id']) {
                        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
                        $orderReturn->allowField(true)->save(['in_stock_status' => 1], ['id' => $v['order_return_id']]);
                    }
                }

                //有错误 则回滚数据
                if (count($error_num) > 0) {
                    throw new Exception("入库失败！！请检查SKU");
                }
            }


            $this->model->commit();
            $item->commit();
            $purchase->commit();
            $this->purchase->commit();
        } catch (ValidateException $e) {
            $this->model->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->purchase->rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            $this->model->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->purchase->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->model->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->purchase->rollback();
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


    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['status'] != 0) {
                $this->error('此状态不能提交审核');
            }

            //查询入库明细数据
            $list = $this->instockItem
                ->where(['in_stock_id' => ['in', $id]])
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');

            //查询存在产品库的sku
            $item = new \app\admin\model\itemmanage\Item;
            $skus = $item->where(['sku' => ['in', $skus]])->column('sku');

            foreach ($list as $v) {
                if (!in_array($v['sku'], $skus)) {
                    $this->error('此sku:' . $v['sku'] . '不存在！！');
                }
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
}
