<?php

namespace app\admin\controller\warehouse;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\StockLog;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 物流单汇总管理
 *
 * @icon fa fa-circle-o
 */
class LogisticsInfo extends Backend
{

    /**
     * LogisticsInfo模型对象
     * @var \app\admin\model\warehouse\LogisticsInfo
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['signin', 'batch_signin', 'is_wrong_sign', 'is_wrong_sign_batch'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\LogisticsInfo;
        $this->purchase = new \app\admin\model\purchase\PurchaseOrder();
        $this->purchase_item = new \app\admin\model\purchase\PurchaseOrderItem();
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
            if ($filter['supplier_sku']) {
                $smap['supplier_sku'] = ['like', $filter['supplier_sku'] . '%'];
                $ids = $this->purchase_item->where($smap)->column('purchase_id');
                $map['purchase_id'] = ['in', $ids];
                unset($filter['supplier_sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['factory_type'] == 0 || $filter['factory_type'] == 1) {
                $factory_type = $filter['factory_type'];
                unset($filter['factory_type']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
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
            foreach ($list as $k => $v) {
                if ($v['purchase_id']) {
                    $res = $this->purchase->where(['id' => $v['purchase_id']])->field('purchase_name,is_new_product,factory_type,supplier_id')->find();
                    $list[$k]['purchase_name'] = $res->purchase_name;
                    $list[$k]['is_new_product'] = $res->is_new_product;
                    $list[$k]['factory_type'] = $res->factory_type;
                    //获取供应商SKU 采购数量字段
                    $supplier_sku = $this->purchase_item->where(['purchase_id' => $v['purchase_id']])->column('supplier_sku');
                    $purchase_num = $this->purchase_item->where(['purchase_id' => $v['purchase_id']])->column('purchase_num');
                    $sku = $this->purchase_item->where(['purchase_id' => $v['purchase_id']])->column('sku');
                    $list[$k]['supplier_sku'] = implode(',', $supplier_sku);
                    $list[$k]['purchase_num'] = implode(',', $purchase_num);
                    $list[$k]['sku'] = implode(',', $sku);
                    $list[$k]['supplier_name'] = Db::name('supplier')->where('id', $res->supplier_id)->value('supplier_name');
                } else {
                    $list[$k]['purchase_name'] = '';
                    $list[$k]['is_new_product'] = 0;
                    $list[$k]['supplier_sku'] = '';
                    $list[$k]['purchase_num'] = 0;
                    $list[$k]['factory_type'] = '';
                }
                if ($list[$k]['sign_warehouse'] == 1) {
                    $list[$k]['sign_warehouse'] = '郑州仓';
                } else {
                    if ($list[$k]['sign_warehouse'] == 2) {
                        $list[$k]['sign_warehouse'] = '丹阳仓';
                    } else {
                        $list[$k]['sign_warehouse'] = $list[$k]['sign_warehouse'] ?? '/';
                    }
                }
                if ($list[$k]['receiving_warehouse'] == 1) {
                    $list[$k]['receiving_warehouse'] = '郑州仓';
                } else {
                    if ($list[$k]['receiving_warehouse'] == 2) {
                        $list[$k]['receiving_warehouse'] = '丹阳仓';
                    } else {
                        $list[$k]['receiving_warehouse'] = $list[$k]['receiving_warehouse'] ?? '/';
                    }
                }
                $list[$k]['sign_time'] = $list[$k]['sign_time'] ?? '/';
                $list[$k]['sign_person'] = empty($list[$k]['sign_person']) ? '/' : $list[$k]['sign_person'];
            }
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 判断是否错仓签收
     * @throws \think\exception\DbException
     * @author jianghaohui
     * @date   2021/8/31 14:00:38
     */
    public function is_wrong_sign()
    {
        $ids = input('ids');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->get($ids);
        $adminId = session('admin.id');
        $stockPerson = config('workorder.stock_person');
        if (!$stockPerson[$adminId]) {
            //$this->error('获取不到当前仓库人员所属仓库，无法签收，请联系产品');
            $stockPerson[$adminId] = 1;
        }
        //相等 没错仓
        if ($row['receiving_warehouse'] == $stockPerson[$adminId]) {
            $this->success(0, '', $ids);
        } else {
            $this->success(1, '', $ids);
        }
    }

    public function is_wrong_sign_batch()
    {
        $ids = input('ids/a');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->where('id', 'in', $ids)->select();
        $adminId = session('admin.id');
        $stockPerson = config('workorder.stock_person');
        if (empty($stockPerson[$adminId])) {
            //$this->error('获取不到当前仓库人员所属仓库，无法签收，请联系产品');
            $stockPerson[$adminId] = 1;
        }
        $arr = [];
        foreach ($row as $v) {
            if ($v['receiving_warehouse'] !== $stockPerson[$adminId]) {
                array_push($arr, $v['logistics_number']);
            }
        }
        if (empty($arr)) {
            //物流单号没有错仓签收的
            $this->success(0, '', $arr);
        } else {
            $this->success(1, '', implode(",", $arr));
        }


    }

    /**
     * 签收
     *
     * @Description
     * @author wpl
     * @since 2020/05/27 15:45:28 
     * @return void
     */
    public function signin($ids = null)
    {
        $ids = input('id');
        $item_platform = new ItemPlatformSku();
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if (!$ids) {
            $this->error('缺少参数！！');
        }

        if ($this->request->isAjax()) {
            $adminId = session('admin.id');
            $stockPerson = config('workorder.stock_person');
            $params['sign_person'] = session('admin.nickname');
            $params['sign_warehouse'] = $stockPerson[$adminId];
            $params['sign_time'] = date('Y-m-d H:i:s');
            $params['status'] = 1;
            $res = $this->model->save($params, ['id' => $ids]);
            if (false !== $res) {
                //签收扣减在途库存
                $batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
                $item = new \app\admin\model\itemmanage\Item();
                $item->startTrans();
                $item_platform->startTrans();
                $this->purchase->startTrans();
                (new StockLog())->startTrans();
                try {
                    //签收成功时更改采购单签收状态
                    $count = $this->model->where(['purchase_id' => $row['purchase_id'], 'status' => 0])->count();
                    if ($count > 0) {
                        $data['purchase_status'] = 9;
                    } else {
                        $data['purchase_status'] = 7;
                    }
                    $data['receiving_time'] = date('Y-m-d H:i:s');
                    $this->purchase->save($data, ['id' => $row['purchase_id']]);


                    if ($row['batch_id']) {
                        $list = $batch_item->where(['purchase_batch_id' => $row['batch_id']])->select();
                        //根据采购单id获取补货单id再获取最初提报的比例
                        $replenish_id = Db::name('purchase_order')->where('id', $row['purchase_id'])->value('replenish_id');

                        foreach ($list as $v) {
                            //比例
                            $rate_arr = Db::name('new_product_mapping')
                                ->where(['sku' => $v['sku'], 'replenish_id' => $replenish_id])
                                ->field('website_type,rate')
                                ->select();
                            //数量
                            $all_num = count($rate_arr);
                            //在途库存数量
                            $stock_num = $v['arrival_num'];
                            //在途库存分站 更新映射关系表
                            foreach ($rate_arr as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                $on_way_stock_before = $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('plat_on_way_stock');
                                $wait_instock_num_before = $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('wait_instock_num');
                                if (($all_num - $key) == 1) {

                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type'                    => 2,
                                        'site'                    => $val['website_type'],
                                        'modular'                 => 10,
                                        //采购单签收
                                        'change_type'             => 24,
                                        'sku'                     => $v['sku'],
                                        'public_id'               => $row['purchase_id'],
                                        'source'                  => 1,
                                        'on_way_stock_before'     => $on_way_stock_before ?: 0,
                                        'on_way_stock_change'     => -$stock_num,
                                        'wait_instock_num_before' => $wait_instock_num_before ?: 0,
                                        'wait_instock_num_change' => $stock_num,
                                        'create_person'           => session('admin.nickname'),
                                        'create_time'             => time(),
                                        //关联采购单
                                        'number_type'             => 7,
                                    ]);
                                    //根据sku站点类型进行在途库存的分配 签收完成之后在途库存就变成了待入库的数量
                                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('plat_on_way_stock', $stock_num);
                                    //更新待入库数量
                                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('wait_instock_num', $stock_num);
                                } else {
                                    $num = round($v['arrival_num'] * $val['rate']);
                                    $stock_num -= $num;
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type'                    => 2,
                                        'site'                    => $val['website_type'],
                                        'modular'                 => 10,
                                        //采购单签收
                                        'change_type'             => 24,
                                        'sku'                     => $v['sku'],
                                        'public_id'               => $row['purchase_id'],
                                        'source'                  => 1,
                                        'on_way_stock_before'     => $on_way_stock_before ?: 0,
                                        'on_way_stock_change'     => -$num,
                                        'wait_instock_num_before' => $wait_instock_num_before ?: 0,
                                        'wait_instock_num_change' => -$num,
                                        'create_person'           => session('admin.nickname'),
                                        'create_time'             => time(),
                                        //关联采购单
                                        'number_type'             => 7,
                                    ]);
                                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('plat_on_way_stock', $num);
                                    //更新待入库数量
                                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('wait_instock_num', $num);
                                }
                            }
                            //插入日志表
                            (new StockLog())->setData([
                                'type'                    => 2,
                                'site'                    => 0,
                                'modular'                 => 10,
                                //采购单签收
                                'change_type'             => 24,
                                'sku'                     => $v['sku'],
                                'public_id'               => $row['purchase_id'],
                                'source'                  => 1,
                                'on_way_stock_before'     => ($item->where(['sku' => $v['sku']])->value('on_way_stock')) ?: 0,
                                'on_way_stock_change'     => -$v['arrival_num'],
                                'wait_instock_num_before' => ($item->where(['sku' => $v['sku']])->value('wait_instock_num')) ?: 0,
                                'wait_instock_num_change' => $v['arrival_num'],
                                'create_person'           => session('admin.nickname'),
                                'create_time'             => time(),
                                //关联采购单
                                'number_type'             => 7,
                            ]);
                            //减总的在途库存也就是商品表里的在途库存
                            $item->where(['sku' => $v['sku']])->setDec('on_way_stock', $v['arrival_num']);
                            //减在途加待入库数量
                            $item->where(['sku' => $v['sku']])->setInc('wait_instock_num', $v['arrival_num']);
                        }
                    } else {
                        if ($row['purchase_id']) {
                            $list = $this->purchase_item->where(['purchase_id' => $row['purchase_id']])->select();
                            //根据采购单id获取补货单id再获取最初提报的比例
                            $replenish_id = Db::name('purchase_order')->where('id', $row['purchase_id'])->value('replenish_id');
                            foreach ($list as $v) {
                                //比例
                                $rate_arr = Db::name('new_product_mapping')
                                    ->where(['sku' => $v['sku'], 'replenish_id' => $replenish_id])
                                    ->field('website_type,rate')
                                    ->select();
                                //数量
                                $all_num = count($rate_arr);
                                //在途库存数量
                                $stock_num = $v['purchase_num'];
                                //在途库存分站 更新映射关系表
                                foreach ($rate_arr as $key => $val) {
                                    //最后一个站点 剩余数量分给最后一个站
                                    $on_way_stock_before = $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('plat_on_way_stock');
                                    $wait_instock_num_before = $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->value('wait_instock_num');
                                    if (($all_num - $key) == 1) {
                                        //插入日志表
                                        (new StockLog())->setData([
                                            'type'                    => 2,
                                            'site'                    => $val['website_type'],
                                            'modular'                 => 10,
                                            //采购单签收
                                            'change_type'             => 24,
                                            'sku'                     => $v['sku'],
                                            'public_id'               => $row['purchase_id'],
                                            'source'                  => 1,
                                            'on_way_stock_before'     => $on_way_stock_before ?: 0,
                                            'on_way_stock_change'     => -$stock_num,
                                            'wait_instock_num_before' => $wait_instock_num_before ?: 0,
                                            'wait_instock_num_change' => $stock_num,
                                            'create_person'           => session('admin.nickname'),
                                            'create_time'             => time(),
                                            //关联采购单
                                            'number_type'             => 7,
                                        ]);
                                        //根据sku站点类型进行在途库存的分配
                                        $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('plat_on_way_stock', $stock_num);
                                        //更新待入库数量
                                        $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('wait_instock_num', $stock_num);
                                    } else {
                                        $num = round($v['purchase_num'] * $val['rate']);
                                        $stock_num -= $num;
                                        //插入日志表
                                        (new StockLog())->setData([
                                            'type'                    => 2,
                                            'site'                    => $val['website_type'],
                                            'modular'                 => 10,
                                            //采购单签收
                                            'change_type'             => 24,
                                            'sku'                     => $v['sku'],
                                            'public_id'               => $row['purchase_id'],
                                            'source'                  => 1,
                                            'on_way_stock_before'     => $on_way_stock_before ?: 0,
                                            'on_way_stock_change'     => -$num,
                                            'wait_instock_num_before' => $wait_instock_num_before ?: 0,
                                            'wait_instock_num_change' => $num,
                                            'create_person'           => session('admin.nickname'),
                                            'create_time'             => time(),
                                            //关联采购单
                                            'number_type'             => 7,
                                        ]);
                                        $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('plat_on_way_stock', $num);
                                        //更新待入库数量
                                        $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('wait_instock_num', $num);
                                    }
                                }

                                $on_way_stock_before = $item->where(['sku' => $v['sku']])->value('on_way_stock');
                                $wait_instock_num_before = $item->where(['sku' => $v['sku']])->value('wait_instock_num');
                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                    => 2,
                                    'site'                    => 0,
                                    'modular'                 => 10,
                                    //采购单签收
                                    'change_type'             => 24,
                                    'sku'                     => $v['sku'],
                                    'public_id'               => $row['purchase_id'],
                                    'source'                  => 1,
                                    'on_way_stock_before'     => $on_way_stock_before ?: 0,
                                    'on_way_stock_change'     => -$v['purchase_num'],
                                    'wait_instock_num_before' => $wait_instock_num_before ?: 0,
                                    'wait_instock_num_change' => $v['purchase_num'],
                                    'create_person'           => session('admin.nickname'),
                                    'create_time'             => time(),
                                    //关联采购单
                                    'number_type'             => 7,
                                ]);
                                //减总的在途库存也就是商品表里的在途库存
                                $item->where(['sku' => $v['sku']])->setDec('on_way_stock', $v['purchase_num']);
                                //减在途加待入库数量
                                $item->where(['sku' => $v['sku']])->setInc('wait_instock_num', $v['purchase_num']);
                            }
                        }
                    }
                    $item->commit();
                    $item_platform->commit();
                    $this->purchase->commit();
                    (new StockLog())->commit();
                } catch (ValidateException $e) {
                    $item->rollback();
                    $item_platform->rollback();
                    $this->purchase->rollback();
                    (new StockLog())->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $item->rollback();
                    $item_platform->rollback();
                    $this->purchase->rollback();
                    (new StockLog())->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $item->rollback();
                    $item_platform->rollback();
                    $this->purchase->rollback();
                    (new StockLog())->rollback();
                    $this->error($e->getMessage());
                }

                $this->success('签收成功');
            } else {
                $this->error('签收失败');
            }
        }
    }

    /**
     * 批量签收
     *
     * @Description
     * @author wpl
     * @since 2020/05/27 15:45:28 
     * @return void
     */
    public function batch_signin()
    {
        $item_platform = new ItemPlatformSku();
        $ids = input('ids/a');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->where('id', 'in', $ids)->select();
        foreach ($row as $v) {
            if ($v['status'] == 1) {
                $this->error('物流单号'.$v['logistics_number'].'已签收，请重新选择！！');
            }
        }
        if ($this->request->isAjax()) {
            $params['sign_person'] = session('admin.nickname');
            $params['sign_time'] = date('Y-m-d H:i:s');
            $params['status'] = 1;
            $res = $this->model->save($params, ['id' => ['in', $ids]]);
            if (false !== $res) {
                $batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
                $item = new \app\admin\model\itemmanage\Item();
                $row = $this->model->where(['id' => ['in', $ids]])->select();
                $item->startTrans();
                $item_platform->startTrans();
                $this->purchase->startTrans();
                (new StockLog())->startTrans();
                try {
                    foreach ($row as $k => $v) {
                        $data = [];
                        //签收成功时更改采购单签收状态
                        $count = $this->model->where(['purchase_id' => $v['purchase_id'], 'status' => 0])->count();
                        if ($count > 0) {
                            $data['purchase_status'] = 9;
                        } else {
                            $data['purchase_status'] = 7;
                        }
                        $data['receiving_time'] = date('Y-m-d H:i:s');
                        $this->purchase->where(['id' => $v['purchase_id']])->update($data);

                        //签收扣减在途库存

                        if ($v['batch_id']) {
                            $list = $batch_item->where(['purchase_batch_id' => $v['batch_id']])->select();
                            //根据采购单id获取补货单id再获取最初提报的比例
                            $replenish_id = Db::name('purchase_order')->where('id', $v['purchase_id'])->value('replenish_id');

                            foreach ($list as $val) {
                                //比例
                                $rate_arr = Db::name('new_product_mapping')
                                    ->where(['sku' => $val['sku'], 'replenish_id' => $replenish_id])
                                    ->field('website_type,rate')
                                    ->select();
                                //数量
                                $all_num = count($rate_arr);
                                //在途库存数量
                                $stock_num = $val['arrival_num'];
                                //在途库存分站 更新映射关系表
                                foreach ($rate_arr as $key => $vall) {
                                    //最后一个站点 剩余数量分给最后一个站
                                    if (($all_num - $key) == 1) {
                                        //插入日志表
                                        (new StockLog())->setData([
                                            'type'                    => 2,
                                            'site'                    => $vall['website_type'],
                                            'modular'                 => 10,
                                            //采购单签收
                                            'change_type'             => 24,
                                            'sku'                     => $val['sku'],
                                            'public_id'               => $v['purchase_id'],
                                            'source'                  => 1,
                                            'on_way_stock_before'     => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('plat_on_way_stock')) ?: 0,
                                            'on_way_stock_change'     => -$stock_num,
                                            'wait_instock_num_before' => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('wait_instock_num')) ?: 0,
                                            'wait_instock_num_change' => $stock_num,
                                            'create_person'           => session('admin.nickname'),
                                            'create_time'             => time(),
                                            //关联采购单
                                            'number_type'             => 7,
                                        ]);
                                        //根据sku站点类型进行在途库存的分配 签收完成之后在途库存就变成了待入库的数量
                                        $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $stock_num);
                                        //更新待入库数量
                                        $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setInc('wait_instock_num', $stock_num);
                                    } else {
                                        $num = round($val['arrival_num'] * $vall['rate']);
                                        $stock_num -= $num;
                                        //插入日志表
                                        (new StockLog())->setData([
                                            'type'                    => 2,
                                            'site'                    => $vall['website_type'],
                                            'modular'                 => 10,
                                            //采购单签收
                                            'change_type'             => 24,
                                            'sku'                     => $val['sku'],
                                            'public_id'               => $v['purchase_id'],
                                            'source'                  => 1,
                                            'on_way_stock_before'     => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('plat_on_way_stock')) ?: 0,
                                            'on_way_stock_change'     => -$num,
                                            'wait_instock_num_before' => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('wait_instock_num')) ?: 0,
                                            'wait_instock_num_change' => -$num,
                                            'create_person'           => session('admin.nickname'),
                                            'create_time'             => time(),
                                            //关联采购单
                                            'number_type'             => 7,
                                        ]);
                                        $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $num);
                                        //更新待入库数量
                                        $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setInc('wait_instock_num', $num);
                                    }
                                }
                                //插入日志表
                                (new StockLog())->setData([
                                    'type'                    => 2,
                                    'site'                    => 0,
                                    'modular'                 => 10,
                                    //采购单签收
                                    'change_type'             => 24,
                                    'sku'                     => $val['sku'],
                                    'public_id'               => $v['purchase_id'],
                                    'source'                  => 1,
                                    'on_way_stock_before'     => ($item->where(['sku' => $val['sku']])->value('on_way_stock')) ?: 0,
                                    'on_way_stock_change'     => -$val['arrival_num'],
                                    'wait_instock_num_before' => ($item->where(['sku' => $val['sku']])->value('wait_instock_num')) ?: 0,
                                    'wait_instock_num_change' => $val['arrival_num'],
                                    'create_person'           => session('admin.nickname'),
                                    'create_time'             => time(),
                                    //关联采购单
                                    'number_type'             => 7,
                                ]);
                                //减总的在途库存也就是商品表里的在途库存
                                $item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['arrival_num']);
                                //减在途加待入库数量
                                $item->where(['sku' => $val['sku']])->setInc('wait_instock_num', $val['arrival_num']);
                            }
                        } else {
                            if ($v['purchase_id']) {
                                $list = $this->purchase_item->where(['purchase_id' => $v['purchase_id']])->select();
                                //根据采购单id获取补货单id再获取最初提报的比例
                                $replenish_id = Db::name('purchase_order')->where('id', $v['purchase_id'])->value('replenish_id');
                                foreach ($list as $val) {
                                    //比例
                                    $rate_arr = Db::name('new_product_mapping')
                                        ->where(['sku' => $val['sku'], 'replenish_id' => $replenish_id])
                                        ->field('website_type,rate')
                                        ->select();
                                    //数量
                                    $all_num = count($rate_arr);
                                    //在途库存数量
                                    $stock_num = $val['purchase_num'];
                                    //在途库存分站 更新映射关系表
                                    foreach ($rate_arr as $key => $vall) {
                                        //最后一个站点 剩余数量分给最后一个站
                                        if (($all_num - $key) == 1) {
                                            //插入日志表
                                            (new StockLog())->setData([
                                                'type'                    => 2,
                                                'site'                    => $vall['website_type'],
                                                'modular'                 => 10,
                                                //采购单签收
                                                'change_type'             => 24,
                                                'sku'                     => $val['sku'],
                                                'public_id'               => $v['purchase_id'],
                                                'source'                  => 1,
                                                'on_way_stock_before'     => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('plat_on_way_stock')) ?: 0,
                                                'on_way_stock_change'     => -$stock_num,
                                                'wait_instock_num_before' => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('wait_instock_num')) ?: 0,
                                                'wait_instock_num_change' => $stock_num,
                                                'create_person'           => session('admin.nickname'),
                                                'create_time'             => time(),
                                                //关联采购单
                                                'number_type'             => 7,
                                            ]);
                                            //根据sku站点类型进行在途库存的分配 签收完成之后在途库存就变成了待入库的数量
                                            $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $stock_num);
                                            //更新待入库数量
                                            $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setInc('wait_instock_num', $stock_num);
                                        } else {
                                            $num = round($val['purchase_num'] * $vall['rate']);
                                            $stock_num -= $num;
                                            //插入日志表
                                            (new StockLog())->setData([
                                                'type'                    => 2,
                                                'site'                    => $vall['website_type'],
                                                'modular'                 => 10,
                                                //采购单签收
                                                'change_type'             => 24,
                                                'sku'                     => $val['sku'],
                                                'public_id'               => $v['purchase_id'],
                                                'source'                  => 1,
                                                'on_way_stock_before'     => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('plat_on_way_stock')) ?: 0,
                                                'on_way_stock_change'     => -$num,
                                                'wait_instock_num_before' => ($item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->value('wait_instock_num')) ?: 0,
                                                'wait_instock_num_change' => $num,
                                                'create_person'           => session('admin.nickname'),
                                                'create_time'             => time(),
                                                //关联采购单
                                                'number_type'             => 7,
                                            ]);
                                            $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $num);
                                            //更新待入库数量
                                            $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setInc('wait_instock_num', $num);
                                        }
                                    }
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type'                    => 2,
                                        'site'                    => 0,
                                        'modular'                 => 10,
                                        //采购单签收
                                        'change_type'             => 24,
                                        'sku'                     => $val['sku'],
                                        'public_id'               => $v['purchase_id'],
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
                            }
                        }
                    }
                    $item->commit();
                    $item_platform->commit();
                    $this->purchase->commit();
                    (new StockLog())->commit();
                } catch (ValidateException $e) {
                    $item->rollback();
                    $item_platform->rollback();
                    $this->purchase->rollback();
                    (new StockLog())->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $item->rollback();
                    $item_platform->rollback();
                    $this->purchase->rollback();
                    (new StockLog())->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $item->rollback();
                    $item_platform->rollback();
                    $this->purchase->rollback();
                    (new StockLog())->rollback();
                    $this->error($e->getMessage());
                }

                $this->success('签收成功');
            } else {
                $this->error('签收失败');
            }
        }
    }
}
