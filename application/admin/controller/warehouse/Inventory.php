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
            $this->temp = new \app\admin\model\warehouse\TempProduct;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->temp
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->temp
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
                    if (@$params['inventory_qty']) {
                        $params['error_qty'] = $params['inventory_qty'] - $row['available_stock'];
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
            $res = $this->model->save(['status' => 2], ['id' => ['in', $ids]]);
            if ($res !== false) {
                //修改明细表
                $this->item->save(['is_add' => 1], ['inventory_id' => ['in', $ids]]);
                $this->success('操作成功！！');
            } else {
                $this->error('操作失败！！');
            }
        }
    }
}
