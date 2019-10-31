<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 库存盘点单
 *
 * @icon fa fa-circle-o
 */
class Inventory extends Backend
{

    /**
     * Inventory模型对象
     * @var \app\admin\model\warehouse\Inventory
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\Inventory;
        $this->item = new \app\admin\model\warehouse\InventoryItem;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->product = new \app\admin\model\itemmanage\Item;
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
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as &$v) {
                $map['inventory_id'] = $v['id'];
                //查询总数量
                $allCount = $this->item->where($map)->count();
                $smap['is_add'] = 1;
                $smap['inventory_id'] = $v['id'];
                //查询盘点数量
                $count = $this->item->where($smap)->count();
                $count = $count ?? '0';
                $v['num'] = $count . '/' . $allCount;
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 商品库存列表
     */
    public function add()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $map['is_open'] = ['in', [1, 2]];
            $total = $this->product
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->product
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $skus = array_column($list, 'sku');
            //查询留样库存
            //查询实际采购信息 查询在途库存
            $purchase_map['stock_status'] = ['in', [1, 2]];
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $hasWhere['sku'] = ['in', $skus];
            $purchase_list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
                ->where($purchase_map)
                ->column('sku,purchase_num,instock_num', 'sku');

            //查询样品数量
            $check = new \app\admin\model\warehouse\CheckItem;
            $check_list = $check->where($hasWhere)->column('sample_num', 'sku');

            foreach ($list as &$v) {
                $v['on_way_stock'] = @$purchase_list[$v['sku']]['purchase_num'] - @$purchase_list[$v['sku']]['instock_num'];
                $v['sample_stock'] = @$check_list[$v['sku']]['sample_num'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    //临时表数据
    public function tempProduct()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\admin\model\warehouse\TempProduct;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('add');
    }

    /**
     * 添加临时表数据
     */
    public function addTempProduct()
    {
        if ($this->request->isPost()) {
            $this->model = new \app\admin\model\warehouse\TempProduct;
            $params = $this->request->post("data/a");
            if ($params) {
                $data = json_decode($params[0]);
                $result = false;
                Db::startTrans();
                try {
                    if ($data) {
                        $skus = $this->model->column('sku');
                        $list = [];
                        foreach ($data as $k => $v) {
                            //查询是否已添加
                            if (in_array($v->sku, $skus)) {
                                continue;
                            }
                            $list[$k]['sku'] = $v->sku;
                            $list[$k]['name'] = $v->name;
                            $list[$k]['stock'] = $v->stock;
                            $list[$k]['occupy_stock'] = $v->occupy_stock;
                            $list[$k]['available_stock'] = $v->available_stock;
                            $list[$k]['on_way_stock'] = $v->on_way_stock;
                            $list[$k]['sample_stock'] = $v->sample_stock ?? 0;
                        }
                    }

                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->saveAll($list);
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
     * 删除临时表数据
     */
    public function tempdel($ids = null)
    {
        $this->model = new \app\admin\model\warehouse\TempProduct;
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    /**
     * 创建任务
     */
    public function createInventory()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("data");
            if ($params) {
                if ($params != 'all') {
                    $data = json_decode($params);
                } else {
                    $temp = new \app\admin\model\warehouse\TempProduct;
                    $data = $temp->select();
                }
                $result = false;
                Db::startTrans();
                try {
                    if ($data) {
                        $skus = $this->item->where('is_add', '=', 0)->column('sku');
                        $sku_arr = [];
                        foreach ($data as $k => $v) {
                            //查询是否已添加
                            if (in_array($v->sku, $skus)) {
                                unset($data[$k]);
                            }
                            $sku_arr[] = $v->sku;
                        }

                        //删除临时表
                        $temp = new \app\admin\model\warehouse\TempProduct;
                        $temp->where('sku', 'in', $sku_arr)->delete();

                        if ($data) {
                            //创建盘点单
                            $arr['number'] = 'IS' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                            $arr['create_person'] = session('admin.username');
                            $arr['createtime'] = date('Y-m-d H:i:s', time());
                            $this->model->allowField(true)->save($arr);

                            $list = [];
                            foreach ($data as $k => $v) {
                                $list[$k]['inventory_id'] = $this->model->id;
                                $list[$k]['sku'] = $v->sku;
                                $list[$k]['name'] = $v->name;
                                $list[$k]['real_time_qty'] = $v->stock ?? 0;
                                $list[$k]['occupy_stock'] = $v->occupy_stock;
                                $list[$k]['available_stock'] = $v->available_stock;
                            }
                            //添加明细表数据
                            $result = $this->item->allowField(true)->saveAll($list);
                        } else {
                            $this->error('存在正在盘点的SKU！！', url('index'));
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
                    $this->success('', url('index'));
                } else {
                    $this->error(__('No rows were inserted'), url('index'));
                }
            }
            $this->error(__('Parameter %s can not be empty'), url('index'));
        }
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
        if ($row['status'] > 0) {
            $this->error(__('此状态不能编辑！！'), '/admin/warehouse/Inventory/index');
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        $this->view->assign("row", $row);
        $this->assignconfig("id", $ids);
        return $this->view->fetch();
    }


    /**
     * 盘点明细表数据
     */
    public function inventoryEdit()
    {

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $map['inventory_id'] = input('inventory_id');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->item
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->item
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('edit');
    }

    /**
     * 添加临时表数据
     */
    public function addInventoryItem()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("data/a");
            $ids = $this->request->post("inventory_id");
            if ($params) {
                $data = json_decode($params[0]);
                $result = false;
                Db::startTrans();
                try {
                    if ($data) {
                        $skus = $this->item->where('is_add', '=', 0)->column('sku');
                        $list = [];
                        foreach ($data as $k => $v) {
                            //查询是否已添加
                            if (in_array($v->sku, $skus)) {
                                continue;
                            }
                            $list[$k]['inventory_id'] = $ids;
                            $list[$k]['sku'] = $v->sku;
                            $list[$k]['name'] = $v->name;
                            $list[$k]['real_time_qty'] = $v->stock ?? 0;
                            $list[$k]['occupy_stock'] = $v->occupy_stock;
                            $list[$k]['available_stock'] = $v->available_stock;
                        }
                    }
                    if (!$list) {
                        $this->error('已存在正在盘点的SKU！！');
                    }
                    $result = $this->item->allowField(true)->saveAll($list);
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
     * 移除盘点单明细表数据
     */
    public function delInventoryItem($ids = null)
    {

        if ($ids) {
            $pk = $this->item->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->item->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->item->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 开始盘点页面
     */
    public function start($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\admin\model\warehouse\InventoryItem;
            $map['inventory_id'] = input('inventory_id');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('inventory_id', $ids);
        return $this->view->fetch();
    }

    /**
     * 开始编辑
     */
    public function startEdit()
    {
        $ids = input('ids');
        $row = $this->item->get($ids);
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
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->item));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    //计算误差数量
                    if (@$params['inventory_qty'] || $params['inventory_qty'] == 0) {
                        $params['error_qty'] = $params['inventory_qty'] - $row['real_time_qty'];
                        $params['is_add'] = 1;
                    }

                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        //修改状态为盘点中
                        $this->model->save(['status' => 1], ['id' => $row['inventory_id']]);
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
                    $this->success('操作成功！！', '', ['error_qty' => @$params['error_qty'] ?? '']);
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    /**
     * 结束盘点
     */
    public function endInventory()
    {
        $ids = input('inventory_id/a');

        if ($this->request->isPost()) {
            //修改状态为盘点中
            $res = $this->model->save(['status' => 2, 'end_time' => date('Y-m-d H:i:s', time())], ['id' => ['in', $ids]]);
            if ($res !== false) {
                //修改明细表
                $this->item->save(['is_add' => 1], ['inventory_id' => ['in', $ids]]);
                $this->success('操作成功！！');
            } else {
                $this->error('操作失败！！');
            }
        }
    }


    /**
     * 审核 冲减库存生成入库单 出库单
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
            if ($v['check_status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }
        $data['check_status'] = input('status');
        $data['check_time'] = date('Y-m-d H:i:s', time());
        $data['check_person'] = session('admin.nickname');
        $item = new \app\admin\model\itemmanage\Item;
        $instock = new \app\admin\model\warehouse\Instock;
        $instockItem = new \app\admin\model\warehouse\InstockItem;
        $outstock = new \app\admin\model\warehouse\Outstock;
        $outstockItem = new \app\admin\model\warehouse\OutStockItem;

        //回滚
        Db::startTrans();
        try {
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            //审核通过 生成入库单 并同步库存
            if ($data['check_status'] == 2) {
                $infos = $this->model->hasWhere('InventoryItem', ['inventory_id' => ['in', $ids]])
                    ->field('sku,error_qty')
                    ->group('sku')
                    ->select();
                $infos = collection($infos)->toArray();
                foreach ($infos as $k => $v) {
                    //如果误差为0则跳过
                    if ($v['error_qty'] == 0) {
                        continue;
                    }
                    //同步对应SKU库存
                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    if ($v['sku']) {
                        $stock = $item->where($item_map)->setInc('stock', $v['error_qty']);
                        //可用库存
                        $available_stock = $item->where($item_map)->setInc('available_stock', $v['error_qty']);
                    }

                    //修改库存结果为真
                    if ($stock === false || $available_stock === false) {
                        throw new Exception('同步库存失败,请检查SKU=>' . $v['sku']);
                        break;
                    }

                    if ($v['error_qty'] > 0) {
                        //生成入库单
                        $info[$k]['sku'] = $v['sku'];
                        $info[$k]['in_stock_num'] = abs($v['error_qty']);
                        $info[$k]['no_stock_num'] = abs($v['error_qty']);
                    } else {
                        $list[$k]['sku'] = $v['sku'];
                        $list[$k]['out_stock_num'] = abs($v['error_qty']);
                    }
                }
                //入库记录
                if ($info) {
                    $params['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $params['type_id'] = 2;
                    $params['status'] = 2;
                    $params['remark'] = '盘盈入库';
                    $params['check_time'] = date('Y-m-d H:i:s', time());
                    $params['check_person'] = session('admin.nickname');
                    $instorck_res = $instock->isUpdate(false)->allowField(true)->data($params, true)->save();

                    //添加入库信息
                    if ($instorck_res !== false) {
                        $instockItemList = array_values($info);
                        unset($info);
                        foreach ($instockItemList as &$v) {
                            $v['in_stock_id'] = $instock->id;
                        }
                        unset($v);
                        //批量添加
                        $instockItem->allowField(true)->saveAll($instockItemList);
                    } else {
                        throw new Exception('生成入库记录失败！！数据回滚');
                    }
                }

                //出库记录
                if ($list) {
                    $params = [];
                    $params['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $params['type_id'] = 8;
                    $params['status'] = 2;
                    $params['remark'] = '盘亏出库';
                    $params['check_time'] = date('Y-m-d H:i:s', time());
                    $params['check_person'] = session('admin.nickname');
                    $outstock_res = $outstock->isUpdate(false)->allowField(true)->data($params, true)->save();


                    //添加入库信息
                    if ($outstock_res !== false) {
                        $outstockItemList = array_values($list);
                        foreach ($outstockItemList as $k => $v) {
                            $outstockItemList[$k]['out_stock_id'] = $outstock->id;
                        }
                        //批量添加
                        $outstockItem->allowField(true)->saveAll($outstockItemList);
                    } else {
                        throw new Exception('生成出库记录失败！！数据回滚');
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

        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
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
        if ($row['check_status'] !== 0 && $row['status'] == 2) {
            $this->error('只有未提交状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['check_status'] = input('status');
        $data['check_time'] = date('Y-m-d H:i:s', time());
        $data['check_person'] = session('admin.nickname');
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
            if ($row['check_status'] != 0 && $row['status'] == 2) {
                $this->error('盘点单状态必须为已完成并且未提交状态！！');
            }
            $map['id'] = $id;
            $data['check_status'] = 1;
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
     * 详情页面
     */
    public function detail($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\admin\model\warehouse\InventoryItem;
            $map['inventory_id'] = input('inventory_id');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('inventory_id', $ids);
        return $this->view->fetch();
    }


    /***
     * 更改镜架逻辑
     * @param id 协同任务ID
     * @param order_platform 订单平台
     * @param increment_id 订单号
     * @param original_sku 原sku
     * @param original_number 原sku数量
     * @param change_sku   改变之后的sKu
     * @param change_number 改变之后的sku数量
     */
    public function changeFrame($id, $order_platform, $increment_id)
    {
        if (!$id || !$order_platform || !$increment_id) {
            return false;
        }
        $item = new \app\admin\model\itemmanage\Item;
        $instock = new \app\admin\model\warehouse\Instock;
        $instockItem = new \app\admin\model\warehouse\InstockItem;
        $outstock = new \app\admin\model\warehouse\Outstock;
        $outstockItem = new \app\admin\model\warehouse\OutStockItem;
        $taskChangeSku = new \app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
        $platformSku   = new \app\admin\model\itemmanage\ItemPlatformSku;
        $changeRow = $taskChangeSku->where(['tid' => $id])->field('original_sku,original_number,change_sku,change_number')->select();
        if (!$changeRow) { //如果不存在改变的sku
            return false;
        }
        if (1 == $order_platform) {
            $db = 'database.db_zeelool';
        } elseif (2 == $order_platform) {
            $db = 'database.db_voogueme';
        } elseif (3 == $order_platform) {
            $db = 'database.db_nihao';
        }
        foreach ($changeRow as $v) {
            //原先sku
            $original_sku    = $v['original_sku'];
            //原先sku数量
            $original_number = $v['original_number'];
            //改变之后的sku
            $change_sku      = $v['change_sku'];
            //改变之后的sku数量
            $change_number   = $v['change_number'];
            //原先sku对应的仓库sku
            $whereOriginSku['platform_sku'] = $original_sku;
            $whereOriginSku['platform_type'] = $order_platform;
            $warehouse_original_sku = $platformSku->where($whereOriginSku)->value('sku');
            //改变sku对应的仓库sku
            $whereChangeSku['platform_sku'] = $change_sku;
            $whereChangeSku['platform_type'] = $order_platform;
            $warehouse_change_sku = $platformSku->where($whereChangeSku)->value('sku');
            //求出订单对应的order_id
            $order_id = Db::connect($db)->table('sales_flat_order')->where(['increment_id' => $increment_id])->value('entity_id');
            if (!$original_sku || !$original_number || !$change_sku || !$change_number) {
                return false;
            }
            //回滚
            Db::startTrans();
            try {
                //更改sales_flat_order_item表中的sku字段
                $whereChange['order_id'] = $order_id;
                $whereChange['sku']      = $original_sku;
                $changeData['is_change_frame'] = 2;
                $changeSku = Db::connect($db)->table('sales_flat_order_item')->where($whereChange)->update($changeData);
                //原先sku增加可用库存,减少占用库存
                $original_stock = $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('occupy_stock', $original_number)->update();
                //更新之后的sku减少可用库存,增加占用库存
                $change_stock = $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('occupy_stock', $change_number)->update();
                //修改库存结果为真
                if (($changeSku === false) || ($original_stock === false) || ($change_stock === false)) {
                    throw new Exception('更改镜架失败,请检查SKU');
                    continue;
                } else {
                    //入库记录
                    $paramsIn = [];
                    $paramsIn['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $paramsIn['order_number']  = $increment_id;
                    $paramsIn['create_person'] = session('admin.nickname');
                    $paramsIn['createtime'] = date('Y-m-d H:i:s', time());
                    $paramsIn['type_id'] = 5;
                    $paramsIn['status'] = 2;
                    $paramsIn['remark'] = '更改镜架入库';
                    $paramsIn['check_time'] = date('Y-m-d H:i:s', time());
                    $paramsIn['check_person'] = session('admin.nickname');
                    $instorck_res = $instock->isUpdate(false)->allowField(true)->data($paramsIn, true)->save();
                    //添加入库信息
                    if ($instorck_res !== false) {
                        $instockItemList['sku'] = $warehouse_original_sku;
                        $instockItemList['in_stock_num'] = $original_number;
                        $instockItemList['in_stock_id']  = $instock->id;
                        //添加入库商品sku信息
                        $instockItem->isUpdate(false)->allowField(true)->data($instockItemList, true)->save();
                    }
                    //出库记录
                    $paramsOut = [];
                    $paramsOut['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $paramsOut['create_person'] = session('admin.nickname');
                    $paramsOut['createtime'] = date('Y-m-d H:i:s', time());
                    $paramsOut['type_id'] = 14;
                    $paramsOut['status'] = 2;
                    $paramsOut['remark'] = '更改镜架出库';
                    $paramsOut['check_time'] = date('Y-m-d H:i:s', time());
                    $paramsOut['check_person'] = session('admin.nickname');
                    $outstock_res = $outstock->isUpdate(false)->allowField(true)->data($paramsOut, true)->save();
                    //添加出库信息
                    if ($outstock_res !== false) {
                        $outstockItemList['sku'] = $warehouse_change_sku;
                        $outstockItemList['out_stock_num']  = $change_number;
                        $outstockItemList['out_stock_id'] = $outstock->id;
                        //批量添加
                        $outstockItem->isUpdate(false)->allowField(true)->data($outstockItemList, true)->save();
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
        }
    }
}
