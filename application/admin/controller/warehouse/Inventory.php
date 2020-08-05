<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use app\admin\model\StockLog;

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

            //查询临时表数据
            $temp = new \app\admin\model\warehouse\TempProduct;
            $skus = $temp->column('sku');

            $inventoryItem = new \app\admin\model\warehouse\InventoryItem;
            $itemSkus = $inventoryItem->where('is_add', 0)->column('sku');

            $skus = array_unique(array_merge($skus, $itemSkus));
            $map['is_open'] = ['in', [1, 2]];
            $map['sku'] = ['not in', $skus];
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
                            $list[$k]['stock'] = $v->stock;
                            $list[$k]['distribution_occupy_stock'] = $v->distribution_occupy_stock;
                            $list[$k]['available_stock'] = $v->available_stock;
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
                        $skus = $this->item->where('is_add', 0)->column('sku');
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
                            $arr['create_person'] = session('admin.nickname');
                            $arr['createtime'] = date('Y-m-d H:i:s', time());
                            $this->model->allowField(true)->save($arr);

                            $list = [];
                            foreach ($data as $k => $v) {
                                $list[$k]['inventory_id'] = $this->model->id;
                                $list[$k]['sku'] = $v->sku;
                                $list[$k]['name'] = $v->name;
                                $real_time_qty = ($v->stock * 1 - $v->distribution_occupy_stock * 1);
                                $list[$k]['real_time_qty'] = $real_time_qty ?? 0;
                                $list[$k]['distribution_occupy_stock'] = $v->distribution_occupy_stock;
                                $list[$k]['available_stock'] = $v->available_stock;
                                $list[$k]['error_qty'] = (0 - $real_time_qty);
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
            $this->error(__('此状态不能编辑！！'), url('index'));
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

        $this->model = new \app\admin\model\warehouse\InventoryItem;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
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
        return $this->view->fetch('edit');
    }

    /**
     * 添加盘点数据
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
                        $skus = $this->item->where('is_add', 0)->column('sku');
                        $list = [];
                        foreach ($data as $k => $v) {
                            //查询是否已添加
                            if (in_array($v->sku, $skus)) {
                                continue;
                            }
                            $list[$k]['inventory_id'] = $ids;
                            $list[$k]['sku'] = $v->sku;
                            $list[$k]['name'] = $v->name;
                            $real_time_qty = ($v->stock * 1 - $v->distribution_occupy_stock * 1);
                            $list[$k]['real_time_qty'] = $real_time_qty ?? 0;
                            $list[$k]['distribution_occupy_stock'] = $v->distribution_occupy_stock;
                            $list[$k]['available_stock'] = $v->available_stock;
                            $list[$k]['error_qty'] = (0 - $real_time_qty);
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
            if ($v['check_status'] != 1 || $v['status'] != 2) {
                $this->error('只有待审核已完成状态才能操作！！');
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

        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        //回滚
        Db::startTrans();
        try {
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            //审核通过 生成入库单 并同步库存
            if ($data['check_status'] == 2) {
                $infos = $this->item->where(['inventory_id' => ['in', $ids]])
                    ->field('sku,error_qty,inventory_id')
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
                        $stock = $item->where($item_map)->inc('stock', $v['error_qty'])->inc('available_stock', $v['error_qty'])->update();


                        //盘点的时候盘盈入库 盘亏出库 的同时要对虚拟库存进行一定的操作
                        //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                        $item_platform_sku = $platform->where('sku',$v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);
                        $whole_num = $platform->where('sku',$v['sku'])->sum('stock');
                        //盘盈或者盘亏的数量 根据此数量对平台sku虚拟库存进行操作
                        $stock_num = $v['error_qty'];
                        foreach ($item_platform_sku as $key => $val) {
                            //最后一个站点 剩余数量分给最后一个站
                            if (($all_num - $key) == 1) {
                                $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                            } else {
                                $num = round($stock_num * $val['stock']/$whole_num);
                                $stock_num -= $num;
                                $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
                            }
                        }


                    }

                    //修改库存结果为真
                    if ($stock === false) {
                        throw new Exception('同步库存失败,请检查SKU=>' . $v['sku']);
                        break;
                    }

                    //插入日志表
                    (new StockLog())->setData([
                        'type'                      => 2,
                        'two_type'                  => 5,
                        'sku'                       => $v['sku'],
                        'public_id'                 => $v['inventory_id'],
                        'stock_change'              => $v['error_qty'],
                        'available_stock_change'    => $v['error_qty'],
                        'create_person'             => session('admin.nickname'),
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '出库单减少总库存,减少可用库存'
                    ]);

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
                    $params['type_id'] = 1;
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
        list($where) = $this->buildparams();
        //查询SKU库存
        $item = new \app\admin\model\itemmanage\Item();
        $map['is_del'] = 1;
        $map['is_open'] = 1;
        $list = $item->where($where)->where($map)->field('sku,stock,available_stock,distribution_occupy_stock')->select();
        $list = collection($list)->toArray();

        //查询产品货位号
        $store_sku = new \app\admin\model\warehouse\StockHouse;
        $cargo_number = $store_sku->alias('a')->where(['status' => 1, 'b.is_del' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "SKU")
            ->setCellValue("B1", "货位号")
            ->setCellValue("C1", "实时库存")
            ->setCellValue("D1", "盘点数");
        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $cargo_number[$value['sku']]);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), ($value['stock'] - $value['distribution_occupy_stock']));
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:D' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xls';
        $savename = 'SKU数据' . date("YmdHis", time());;

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
     * 批量导入
     */
    public function import()
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
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        $map['is_del'] = 1;
        $map['is_open'] = 1;
        $list = $this->product->where($map)->column('stock,available_stock,distribution_occupy_stock', 'sku');
        $this->model = new \app\admin\model\warehouse\TempProduct;
        $params = [];
        foreach ($data as $k => $v) {
            $params[$k]['sku'] = $v[0];
            $params[$k]['stock'] = $list[$v[0]]['stock'];
            $params[$k]['available_stock'] = $list[$v[0]]['available_stock'];
            $params[$k]['distribution_occupy_stock'] = $list[$v[0]]['distribution_occupy_stock'];
        }
        if ($params) {
            $this->model->saveAll($params);
        }

        return json(['code' => 1, 'msg' => '导入成功！！']);
    }

    /**
     * 批量导入盘点数据
     */
    public function importXls()
    {
        $file = $this->request->request('file');
        $inventory_id = $this->request->request('inventory_id');
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
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        $map['is_del'] = 1;
        $map['is_open'] = 1;
        $list = $this->product->where($map)->column('stock,available_stock,distribution_occupy_stock', 'sku');
        $this->model = new \app\admin\model\warehouse\TempProduct;
        foreach ($data as $k => $v) {
            $map = [];
            $map['inventory_id'] = $inventory_id;
            $map['sku'] = $v[0];
            $params['error_qty'] = $v[3] - ($list[$v[0]]['stock'] - $list[$v[0]]['distribution_occupy_stock']);
            $params['inventory_qty'] = $v[3];

            $this->item->where($map)->update($params);
        }

        return json(['code' => 1, 'msg' => '导入成功！！']);
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
        // $instock = new \app\admin\model\warehouse\Instock;
        // $instockItem = new \app\admin\model\warehouse\InstockItem;
        // $outstock = new \app\admin\model\warehouse\Outstock;
        // $outstockItem = new \app\admin\model\warehouse\OutStockItem;
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
            //判断条件 如果原始的数量和变更之后的数量都不存在，则忽略
            if ((!$original_number) && (!$change_number)) {
                continue;
            }
            //原先sku对应的仓库sku
            $whereOriginSku['platform_sku'] = $original_sku;
            $whereOriginSku['platform_type'] = $order_platform;
            $warehouse_original_sku = $platformSku->where($whereOriginSku)->value('sku');
            //改变sku对应的仓库sku
            $whereChangeSku['platform_sku'] = $change_sku;
            $whereChangeSku['platform_type'] = $order_platform;
            $warehouse_change_sku = $platformSku->where($whereChangeSku)->value('sku');
            //求出订单对应的order_id
            $order = Db::connect($db)->table('sales_flat_order')->where(['increment_id' => $increment_id])->field('entity_id,custom_is_match_frame_new')->find();
            //回滚
            Db::startTrans();
            try {
                //更改sales_flat_order_item表中的sku字段
                if ($original_sku && $original_number) { //如果存在原始sku和原始的数量
                    $whereChange['order_id'] = $order['entity_id'];
                    $whereChange['sku']      = $original_sku;
                    $changeData['is_change_frame'] = 2;
                    $updateInfo = Db::connect($db)->table('sales_flat_order_item')->where($whereChange)->update($changeData);
                    if (false != $updateInfo) {
                        if (1 == $order['custom_is_match_frame_new']) { //如果已经配过镜架需要把原先的配货占用库存扣减，更新的配货占用库存增加
                            //原先sku增加可用库存,减少占用库存
                            if ($warehouse_original_sku && $original_number) {
                                $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('distribution_occupy_stock', $original_number)->dec('occupy_stock', $original_number)->update();
                            }
                            //更新之后的sku减少可用库存,增加占用库存
                            if ($warehouse_change_sku && $change_number) {
                                $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('distribution_occupy_stock', $change_number)->inc('occupy_stock', $change_number)->update();
                            }
                        } else { //否则走原先的流程
                            //原先sku增加可用库存,减少占用库存
                            if ($warehouse_original_sku && $original_number) {
                                $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('occupy_stock', $original_number)->update();
                            }
                            //更新之后的sku减少可用库存,增加占用库存
                            if ($warehouse_change_sku && $change_number) {
                                $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('occupy_stock', $change_number)->update();
                            }
                        }
                    }
                } else { //如果不存在原始sku和原始的数量
                    if (1 == $order['custom_is_match_frame_new']) { //如果已经配过镜架需要把原先的配货占用库存扣减，更新的配货占用库存增加
                        //原先sku增加可用库存,减少占用库存
                        //$item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('distribution_occupy_stock',$original_number)->dec('occupy_stock', $original_number)->update();
                        //更新之后的sku减少可用库存,增加占用库存
                        if ($warehouse_change_sku && $change_number) {
                            $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('distribution_occupy_stock', $change_number)->inc('occupy_stock', $change_number)->update();
                        }
                    } else { //否则走原先的流程
                        //原先sku增加可用库存,减少占用库存
                        //$item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('occupy_stock', $original_number)->update();
                        //更新之后的sku减少可用库存,增加占用库存
                        if ($warehouse_change_sku && $change_number) {
                            $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('occupy_stock', $change_number)->update();
                        }
                    }
                }

                //不需要添加出入库逻辑(主要针对总库存)
                //修改库存结果为真
                // if (($changeSku === false) || ($original_stock === false) || ($change_stock === false)) {
                //     throw new Exception('更改镜架失败,请检查SKU');
                //     continue;
                // } else {
                //     //入库记录
                //     $paramsIn = [];
                //     $paramsIn['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                //     $paramsIn['order_number']  = $increment_id;
                //     $paramsIn['create_person'] = session('admin.nickname');
                //     $paramsIn['createtime'] = date('Y-m-d H:i:s', time());
                //     $paramsIn['type_id'] = 5;
                //     $paramsIn['status'] = 2;
                //     $paramsIn['remark'] = '更改镜架入库';
                //     $paramsIn['check_time'] = date('Y-m-d H:i:s', time());
                //     $paramsIn['check_person'] = session('admin.nickname');
                //     $instorck_res = $instock->isUpdate(false)->allowField(true)->data($paramsIn, true)->save();
                //     //添加入库信息
                //     if ($instorck_res !== false) {
                //         $instockItemList['sku'] = $warehouse_original_sku;
                //         $instockItemList['in_stock_num'] = $original_number;
                //         $instockItemList['in_stock_id']  = $instock->id;
                //         //添加入库商品sku信息
                //         $instockItem->isUpdate(false)->allowField(true)->data($instockItemList, true)->save();
                //     }
                //     //出库记录
                //     $paramsOut = [];
                //     $paramsOut['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                //     $paramsOut['create_person'] = session('admin.nickname');
                //     $paramsOut['createtime'] = date('Y-m-d H:i:s', time());
                //     $paramsOut['type_id'] = 14;
                //     $paramsOut['status'] = 2;
                //     $paramsOut['remark'] = '更改镜架出库';
                //     $paramsOut['check_time'] = date('Y-m-d H:i:s', time());
                //     $paramsOut['check_person'] = session('admin.nickname');
                //     $outstock_res = $outstock->isUpdate(false)->allowField(true)->data($paramsOut, true)->save();
                //     //添加出库信息
                //     if ($outstock_res !== false) {
                //         $outstockItemList['sku'] = $warehouse_change_sku;
                //         $outstockItemList['out_stock_num']  = $change_number;
                //         $outstockItemList['out_stock_id'] = $outstock->id;
                //         //批量添加
                //         $outstockItem->isUpdate(false)->allowField(true)->data($outstockItemList, true)->save();
                //     }
                // }
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
    /***
     * 取消订单的逻辑
     * @param id 协同任务ID
     * @param order_platform 订单平台
     * @param increment_id 订单号
     */
    public function cancelOrder($id, $order_platform, $increment_id)
    {
        if (!$id || !$order_platform || !$increment_id) {
            return false;
        }
        $item = new \app\admin\model\itemmanage\Item;
        // $instock = new \app\admin\model\warehouse\Instock;
        // $instockItem = new \app\admin\model\warehouse\InstockItem;
        $taskChangeSku = new \app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
        $platformSku   = new \app\admin\model\itemmanage\ItemPlatformSku;
        $changeRow = $taskChangeSku->where(['tid' => $id])->field('original_sku,original_number')->select();
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
            //原先sku对应的仓库sku
            $whereOriginSku['platform_sku'] = $original_sku;
            $whereOriginSku['platform_type'] = $order_platform;
            $warehouse_original_sku = $platformSku->where($whereOriginSku)->value('sku');
            //求出订单对应的order_id
            $order = Db::connect($db)->table('sales_flat_order')->where(['increment_id' => $increment_id])->field('entity_id,custom_is_match_frame_new')->find();
            if (!$original_sku || !$original_number) {
                continue;
            }
            //回滚
            Db::startTrans();
            try {
                //更改sales_flat_order_item表中的sku字段
                $whereChange['order_id'] = $order['entity_id'];
                $whereChange['sku']      = $original_sku;
                $changeData['is_change_frame'] = 3;
                $updateInfo = Db::connect($db)->table('sales_flat_order_item')->where($whereChange)->update($changeData);
                if (false != $updateInfo) {
                    if (1 == $order['custom_is_match_frame_new']) { //如果已经配过镜架需要把原先的配货占用库存扣减
                        //原先sku增加可用库存,减少占用库存
                        $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('distribution_occupy_stock', $original_number)->dec('occupy_stock', $original_number)->update();
                    } else {
                        $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('occupy_stock', $original_number)->update();
                    }
                }
                //不需要添加出入库逻辑(主要针对总库存) 
                //修改库存结果为真
                // if (($changeSku === false) || ($original_stock === false)) {
                //     throw new Exception('更改镜架失败,请检查SKU');
                //     continue;
                // } else {
                //     //入库记录
                //     $paramsIn = [];
                //     $paramsIn['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                //     $paramsIn['order_number']  = $increment_id;
                //     $paramsIn['create_person'] = session('admin.nickname');
                //     $paramsIn['createtime'] = date('Y-m-d H:i:s', time());
                //     $paramsIn['type_id'] = 7;
                //     $paramsIn['status'] = 2;
                //     $paramsIn['remark'] = '取消订单入库';
                //     $paramsIn['check_time'] = date('Y-m-d H:i:s', time());
                //     $paramsIn['check_person'] = session('admin.nickname');
                //     $instorck_res = $instock->isUpdate(false)->allowField(true)->data($paramsIn, true)->save();
                //     //添加入库信息
                //     if ($instorck_res !== false) {
                //         $instockItemList['sku'] = $warehouse_original_sku;
                //         $instockItemList['in_stock_num'] = $original_number;
                //         $instockItemList['in_stock_id']  = $instock->id;
                //         //添加入库商品sku信息
                //         $instockItem->isUpdate(false)->allowField(true)->data($instockItemList, true)->save();
                //     }
                // }
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
    /*** lsw
     * 更改镜架逻辑
     * @param id 协同任务ID
     * @param order_platform 订单平台
     * @param increment_id 订单号
     * @param original_sku 原sku
     * @param original_number 原sku数量
     * @param change_sku   改变之后的sKu
     * @param change_number 改变之后的sku数量
     */
    public function workChangeFrame($id, $order_platform, $increment_id, $changeRow, $type)
    {
        if (!$id || !$order_platform || !$increment_id || !$changeRow) {
            return false;
        }
        $item = new \app\admin\model\itemmanage\Item;
        $platformSku   = new \app\admin\model\itemmanage\ItemPlatformSku;
        if (1 == $order_platform) {
            $db = 'database.db_zeelool';
        } elseif (2 == $order_platform) {
            $db = 'database.db_voogueme';
        } elseif (3 == $order_platform) {
            $db = 'database.db_nihao';
        }
        foreach ($changeRow as $v) {
            //原先sku
            $original_sku    = trim($v['original_sku']);
            //原先sku数量
            $original_number = $v['original_number'];
            //改变之后的sku
            $change_sku      = trim($v['change_sku']);
            //改变之后的sku数量
            $change_number   = $v['change_number'];
            //判断条件 如果原始的数量和变更之后的数量都不存在，则忽略
            if ((!$original_number) && (!$change_number)) {
                continue;
            }
            //原先sku对应的仓库sku
            $whereOriginSku['platform_sku'] = $original_sku;
            $whereOriginSku['platform_type'] = $order_platform;
            $warehouse_original_sku = $platformSku->where($whereOriginSku)->value('sku');
            //改变sku对应的仓库sku
            $whereChangeSku['platform_sku'] = $change_sku;
            $whereChangeSku['platform_type'] = $order_platform;
            $warehouse_change_sku = $platformSku->where($whereChangeSku)->value('sku');
            //求出订单对应的order_id
            $order = Db::connect($db)->table('sales_flat_order')->where(['increment_id' => $increment_id])->field('entity_id,custom_is_match_frame_new')->find();
            //回滚
            Db::startTrans();
            try {
                //更改sales_flat_order_item表中的sku字段
                if ($original_sku && $original_number) {     //如果存在原始sku和原始的数量
                    $whereChange['order_id'] = $order['entity_id'];
                    $whereChange['sku']      = $original_sku;
                    $changeData['is_change_frame'] = 2;
                    $updateInfo = Db::connect($db)->table('sales_flat_order_item')->where($whereChange)->update($changeData);
                    if (false !== $updateInfo) {
                        if (1 == $order['custom_is_match_frame_new']) { //如果已经配过镜架需要把原先的配货占用库存扣减，更新的配货占用库存增加
                            //原先sku增加可用库存,减少占用库存
                            if ($warehouse_original_sku && $original_number) {
                                $original_sku_log['distribution_change_num'] = -$original_number;
                                $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('distribution_occupy_stock', $original_number)->dec('occupy_stock', $original_number)->update();

                                //追加对应站点虚拟库存
                                $platformSku->where(['sku' => $warehouse_original_sku, 'platform_type' => $order_platform])->setInc('stock', $original_number);

                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                      => 2,
                                    'two_type'                  => 6,
                                    'sku'                       => $warehouse_original_sku,
                                    'order_number'              => $increment_id,
                                    'public_id'                 => $id,
                                    'distribution_stock_change' => -$original_number,
                                    'available_stock_change'    => $original_number,
                                    'occupy_stock_change'       => -$original_number,
                                    'create_person'             => session('admin.nickname'),
                                    'create_time'               => date('Y-m-d H:i:s'),
                                    'remark'                    => '工单更换镜框-订单已配镜架,原SKU增加可用库存,减少配货占用,减少订单占用'
                                ]);
                            }
                            //更新之后的sku减少可用库存,增加占用库存
                            if ($warehouse_change_sku && $change_number) {
                                $change_sku_log['distribution_change_num'] = $change_number;
                                $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('distribution_occupy_stock', $change_number)->inc('occupy_stock', $change_number)->update();

                                //扣减对应站点虚拟库存
                                $platformSku->where(['sku' => $warehouse_change_sku, 'platform_type' => $order_platform])->setDec('stock', $change_number);

                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                      => 2,
                                    'two_type'                  => 6,
                                    'sku'                       => $warehouse_change_sku,
                                    'order_number'              => $increment_id,
                                    'public_id'                 => $id,
                                    'distribution_stock_change' => $change_number,
                                    'available_stock_change'    => -$change_number,
                                    'occupy_stock_change'       => $change_number,
                                    'create_person'             => session('admin.nickname'),
                                    'create_time'               => date('Y-m-d H:i:s'),
                                    'remark'                    => '工单更换镜框-订单已配镜架,新SKU减少可用库存,增加配货占用,增加订单占用'
                                ]);
                            }
                        } else { //否则走原先的流程
                            //原先sku增加可用库存,减少占用库存
                            if ($warehouse_original_sku && $original_number) {
                                $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('occupy_stock', $original_number)->update();

                                //追加对应站点虚拟库存
                                $platformSku->where(['sku' => $warehouse_original_sku, 'platform_type' => $order_platform])->setInc('stock', $original_number);

                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                      => 2,
                                    'two_type'                  => 6,
                                    'sku'                       => $warehouse_original_sku,
                                    'order_number'              => $increment_id,
                                    'public_id'                 => $id,
                                    'available_stock_change'    => $original_number,
                                    'occupy_stock_change'       => -$original_number,
                                    'create_person'             => session('admin.nickname'),
                                    'create_time'               => date('Y-m-d H:i:s'),
                                    'remark'                    => '工单更换镜框-订单未配镜架,原SKU增加可用库存,减少配货占用,减少订单占用'
                                ]);
                            }
                            //更新之后的sku减少可用库存,增加占用库存
                            if ($warehouse_change_sku && $change_number) {
                                $item->where(['sku' => $warehouse_change_sku])->dec('available_stock', $change_number)->inc('occupy_stock', $change_number)->update();

                                //扣减对应站点虚拟库存
                                $platformSku->where(['sku' => $warehouse_change_sku, 'platform_type' => $order_platform])->setDec('stock', $change_number);

                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                      => 2,
                                    'two_type'                  => 6,
                                    'sku'                       => $warehouse_change_sku,
                                    'order_number'              => $increment_id,
                                    'public_id'                 => $id,
                                    'available_stock_change'    => -$change_number,
                                    'occupy_stock_change'       => $change_number,
                                    'create_person'             => session('admin.nickname'),
                                    'create_time'               => date('Y-m-d H:i:s'),
                                    'remark'                    => '工单更换镜框-订单未配镜架,新SKU减少可用库存,增加订单占用'
                                ]);
                            }
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
        }
    }
    /***lsw
     * 取消订单的逻辑
     * @param id 协同任务ID
     * @param order_platform 订单平台
     * @param increment_id 订单号
     */
    public function workCancelOrder($id, $order_platform, $increment_id, $changeRow, $type)
    {
        if (!$id || !$order_platform || !$increment_id || !$changeRow) {
            return false;
        }
        $item = new \app\admin\model\itemmanage\Item;
        $platformSku   = new \app\admin\model\itemmanage\ItemPlatformSku;
        if (1 == $order_platform) {
            $db = 'database.db_zeelool';
        } elseif (2 == $order_platform) {
            $db = 'database.db_voogueme';
        } elseif (3 == $order_platform) {
            $db = 'database.db_nihao';
        }
        foreach ($changeRow as $v) {
            //原先sku
            $original_sku    = trim($v['original_sku']);
            //原先sku数量
            $original_number = $v['original_number'];
            //原先sku对应的仓库sku
            $whereOriginSku['platform_sku'] = $original_sku;
            $whereOriginSku['platform_type'] = $order_platform;
            $warehouse_original_sku = $platformSku->where($whereOriginSku)->value('sku');
            //求出订单对应的order_id
            $order = Db::connect($db)->table('sales_flat_order')->where(['increment_id' => $increment_id])->field('entity_id,custom_is_match_frame_new')->find();
            if (!$original_sku || !$original_number) {
                continue;
            }
            //回滚
            Db::startTrans();
            try {
                //更改sales_flat_order_item表中的sku字段
                $whereChange['order_id'] = $order['entity_id'];
                $whereChange['sku']      = $original_sku;
                $changeData['is_change_frame'] = 3;
                $updateInfo = Db::connect($db)->table('sales_flat_order_item')->where($whereChange)->update($changeData);
                if (false !== $updateInfo) {
                    if (1 == $order['custom_is_match_frame_new']) { //如果已经配过镜架需要把原先的配货占用库存扣减
                        //原先sku增加可用库存,减少占用库存
                        $original_sku_log['distribution_change_num'] = -$original_number;
                        $res = $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('distribution_occupy_stock', $original_number)->dec('occupy_stock', $original_number)->update();

                        //追加对应站点虚拟库存
                        $platformSku->where(['sku' => $warehouse_original_sku, 'platform_type' => $order_platform])->setInc('stock', $original_number);

                        if (false !== $res) {
                            //插入日志表
                            (new StockLog())->setData([
                                'type'                      => 2,
                                'two_type'                  => 7,
                                'sku'                       => $warehouse_original_sku,
                                'order_number'              => $increment_id,
                                'public_id'                 => $id,
                                'distribution_stock_change' => -$original_number,
                                'available_stock_change'    => $original_number,
                                'occupy_stock_change'       => -$original_number,
                                'create_person'             => session('admin.nickname'),
                                'create_time'               => date('Y-m-d H:i:s'),
                                'remark'                    => '工单取消订单-订单已配镜架,SKU增加可用库存,减少配货占用,减少订单占用'
                            ]);
                        }
                    } else {
                        $res = $item->where(['sku' => $warehouse_original_sku])->inc('available_stock', $original_number)->dec('occupy_stock', $original_number)->update();

                        //追加对应站点虚拟库存
                        $platformSku->where(['sku' => $warehouse_original_sku, 'platform_type' => $order_platform])->setInc('stock', $original_number);

                        if (false !== $res) {
                            //插入日志表
                            (new StockLog())->setData([
                                'type'                      => 2,
                                'two_type'                  => 6,
                                'sku'                       => $warehouse_original_sku,
                                'order_number'              => $increment_id,
                                'public_id'                 => $id,
                                'available_stock_change'    => $original_number,
                                'occupy_stock_change'       => -$original_number,
                                'create_person'             => session('admin.nickname'),
                                'create_time'               => date('Y-m-d H:i:s'),
                                'remark'                    => '工单取消订单-订单未配镜架,SKU增加可用库存,减少订单占用'
                            ]);
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
        }
    }
    /***lsw
     * 赠品和补发的逻辑逻辑
     * @param id 协同任务ID
     * @param order_platform 订单平台
     * @param increment_id 订单号
     */
    public function workPresent($id, $order_platform, $increment_id, $changeRow, $type)
    {
        if (!$id || !$order_platform || !$increment_id || !$changeRow) {
            return false;
        }
        $item = new \app\admin\model\itemmanage\Item;
        $platformSku   = new \app\admin\model\itemmanage\ItemPlatformSku;
        foreach ($changeRow as $v) {
            $arr = explode('-', $v['original_sku']);
            if(!empty($arr[1])){
                $original_sku = $arr[0] . '-' . $arr[1];
            }else{
                $original_sku = trim($v['original_sku']);
            }
            //原先sku
            //$original_sku    = trim($v['original_sku']);
            //原先sku数量
            $original_number = $v['original_number'];
            //原先sku对应的仓库sku
            $whereOriginSku['platform_sku'] = $original_sku;
            $whereOriginSku['platform_type'] = $order_platform;
            $warehouse_original_sku = $platformSku->where($whereOriginSku)->value('sku');
            //回滚
            Db::startTrans();
            try {
                $res = $item->where(['sku' => $warehouse_original_sku])->dec('available_stock', $original_number)->inc('occupy_stock', $original_number)->update();

                //扣减对应站点虚拟库存
                $platformSku->where(['sku' => $warehouse_original_sku, 'platform_type' => $order_platform])->setDec('stock', $original_number);

                if (false !== $res) {
                    if ($type == 3) {
                        $two_type = 9;
                    } elseif ($type == 4) {
                        $two_type = 8;
                    }

                    //插入日志表
                    (new StockLog())->setData([
                        'type'                      => 2,
                        'two_type'                  => $two_type ?: 0,
                        'sku'                       => $warehouse_original_sku,
                        'order_number'              => $increment_id,
                        'public_id'                 => $id,
                        'available_stock_change'    => -$original_number,
                        'occupy_stock_change'       => $original_number,
                        'create_person'             => session('admin.nickname'),
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '工单补发、赠品-SKU减少可用库存,增加订单占用'
                    ]);
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
