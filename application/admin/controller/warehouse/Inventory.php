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
            $total = $this->product
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->product
                ->where($where)
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
                        $list = [];
                        foreach ($data as $k => $v) {
                            //查询是否已添加
                            if (in_array($v->sku, $skus)) {
                                unset($data[$k]);
                            }
                        }
                        if ($data) {
                            //创建盘点单
                            $arr['number'] = 'IS' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                            $arr['create_person'] = session('admin.username');
                            $arr['createtime'] = date('Y-m-d H:i:s', time());
                            $res = $this->model->allowField(true)->save($arr);

                            $list = [];
                            foreach ($data as $k => $v) {
                                $list[$k]['inventory_id'] = $this->model->id;
                                $list[$k]['sku'] = $v->sku;
                                $list[$k]['name'] = $v->name;
                                $list[$k]['real_time_qty'] = $v->stock;
                                $list[$k]['occupy_stock'] = $v->occupy_stock;
                                $list[$k]['available_stock'] = $v->available_stock;
                            }
                        } else {
                            $this->error('存在正在盘点的SKU！！');
                        }

                        if (@$res) {
                            $result = $this->item->allowField(true)->saveAll($list);
                            //如果添加完成 删除临时表数据
                            if ($result) {
                                $sku_arr = array_column($list, 'sku');
                                $temp = new \app\admin\model\warehouse\TempProduct;
                                $temp->where('sku', 'in', $sku_arr)->delete();
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
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
}
