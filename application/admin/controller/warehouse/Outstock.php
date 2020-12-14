<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\warehouse\OutStockLog;
use app\admin\model\StockLog;

/**
 * 出库单管理
 *
 * @icon fa fa-circle-o
 */
class Outstock extends Backend
{

    /**
     * Outstock模型对象
     * @var \app\admin\model\warehouse\Outstock
     */
    protected $model = null;

    //当前是否为关联查询
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\Outstock;
        $this->type = new \app\admin\model\warehouse\OutstockType;
        $this->item = new \app\admin\model\warehouse\OutStockItem;
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
                $ids = $this->item->where($smap)->column('out_stock_id');
                $map['outstock.id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }


            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['outstocktype'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['outstocktype'])
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
                // dump($params);die;

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
                    $out_stock_num = $this->request->post("out_stock_num/a");
                    // dump($sku);dump($out_stock_num);die;
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }
                    if (count($sku) != count(array_unique($sku))) {
                        $this->error('请不要填写相同的sku');
                    }
                    foreach (array_filter($sku) as $k => $v) {
                        $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                        $sku_platform = $item_platform_sku->where(['sku' => $v, 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v . '没有同步至此平台，请先同步后重试');
                        }
                        if ($out_stock_num[$k] > $sku_platform['stock']) {
                            $this->error('sku：' . $v . '出库数量不能大于当前站点虚拟仓库存');
                        }
                    }

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['out_stock_num'] = $out_stock_num[$k];
                            $data[$k]['out_stock_id'] = $this->model->id;
                        }
                        //批量添加
                        $this->item->allowField(true)->saveAll($data);
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
        //查询出库分类
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);


        //质检单
        $outstock_number = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('outstock_number', $outstock_number);
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

        //判断状态是否为新建
        if ($row['status'] > 0) {
            $this->error('只有新建状态才能编辑！！', url('index'));
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
                    $out_stock_num = $this->request->post("out_stock_num/a");

                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }
                    if (count($sku) != count(array_unique($sku))) {
                        $this->error('请不要填写相同的sku');
                    }
                    foreach (array_filter($sku) as $k => $v) {
                        $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                        $sku_platform = $item_platform_sku->where(['sku' => $v, 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v . '没有同步至此平台，请先同步后重试');
                        }
                        if ($out_stock_num[$k] > $sku_platform['stock']) {
                            $this->error('sku：' . $v . '出库数量不能大于当前站点虚拟仓库存');
                        }
                    }
                    $result = $row->allowField(true)->save($params);

                    //修改产品
                    if ($result !== false) {
                        $item_id = $this->request->post("item_id/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['out_stock_num'] = $out_stock_num[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['out_stock_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->item->allowField(true)->saveAll($data);
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
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);


        /***********查询出库商品信息***************/
        //查询入库单商品信息
        $item_map['out_stock_id'] = $ids;
        $item = $this->item->where($item_map)->select();
        $this->iitem = new \app\admin\model\itemmanage\Item;
        $itemplatform = new \app\admin\model\itemmanage\ItemPlatformSku();
        //查询数据以显示在出库单编辑界面
        foreach ($item as $k => $v) {
            $res = $this->iitem->getGoodsInfo($item[$k]['sku']);
            $item[$k]['stock'] = $res['stock'];
            //名字
            $item[$k]['name'] = $res['name'];
            //实时库存
            $item[$k]['now_stock'] = $res['stock'] - $res['distribution_occupy_stock'];
            //可用库存
            $item[$k]['available_stock'] = $res['available_stock'];
            //占用库存
            $item[$k]['occupy_stock'] = $res['occupy_stock'];
            $info = $itemplatform->where(['sku' => $item[$k]['sku'], 'platform_type' => $row['platform_id']])->field('stock')->find();
            //虚拟仓库存
            $item[$k]['platform_stock'] = $info['stock'];
        }
        //
        //         dump(collection($row)->toArray());
        // dump(collection($item)->toArray());die;
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
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);


        /***********查询出库商品信息***************/
        //查询入库单商品信息
        $item_map['out_stock_id'] = $ids;
        $item = $this->item->where($item_map)->select();

        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //删除入库单里的商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->item->destroy($id);
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

        //查询入库单商品信息
        $where['out_stock_id'] = ['in', $ids];
        $list = $this->item
            ->alias('a')
            ->join('fa_out_stock o', 'a.out_stock_id = o.id')
            ->where($where)
            ->select();
        $data['status'] = input('status');
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        if ($data['status'] == 2) {
            //批量审核出库 扣减sku的总数量不能大于当前sku的虚拟仓库存量
            $arr = [];
            foreach ($list as $k => $v) {
                if (!$arr[$v['sku']]) {
                    $arr[$v['sku']]['num'] = $v['out_stock_num'];
                    $arr[$v['sku']]['platform_type'] = $v['platform_id'];
                } else {
                    $arr[$v['sku']]['num'] = $v['out_stock_num'] + $arr[$v['sku']]['num'];
                }
            }
            foreach ($arr as $k => $v) {
                $item_platform_sku = $platform->where(['sku' => $k, 'platform_type' => $v['platform_type']])->find();
                if ($v['num'] > $item_platform_sku['stock']) {
                    $this->error('出库的数量大于sku:' . $k . '的虚拟仓库存，请检查后重试');
                }
            }
        }
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        $item = new \app\admin\model\itemmanage\Item;
        if ($res != false) {
            /**
             * @todo 审核通过扣减库存逻辑
             */
            (new StockLog())->startTrans();
            $platform->startTrans();
            $item->startTrans();
            Db::startTrans();
            try {
                if ($data['status'] == 2) {
                    //                dump(collection($list)->toArray());die;

                    //出库扣减库存
                    $stock_data = [];
                    foreach ($list as $v) {
                        //扣除商品表商品总库存
                        //总库存

                        $item_map['sku'] = $v['sku'];
                        $sku_item = $item->where($item_map)->find();
                        $item_platform_sku = $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->find();

                        $item->where($item_map)->dec('stock', $v['out_stock_num'])->dec('available_stock', $v['out_stock_num'])->update();
                        //直接扣减此平台sku的库存
                        $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->dec('stock', $v['out_stock_num'])->update();

                        //插入日志表
                        (new StockLog())->setData([
                            //'大站点类型：1网站 2魔晶',
                            'type' => 2,
                            //'站点类型：1Zeelool  2Voogueme 3Nihao 4Meeloog 5Wesee 8Amazon 9Zeelool_es 10Zeelool_de 11Zeelool_jp'
                            'site' => $v['platform_id'],
                            //'模块：1普通订单 2配货 3质检 4审单 5异常处理 6更改镜架 7取消订单 8补发 9赠品 10采购入库 11出入库 12盘点 13调拨'
                            'modular' => 11,
                            //'变动类型：1非预售下单 2预售下单-虚拟仓>0 3预售下单-虚拟仓<0 4配货 5质检拒绝-镜架报损 6审单-成功 7审单-配错镜框
                            // 8加工异常打回待配货 9印logo异常打回待配货 10更改镜架-配镜架前 11更改镜架-配镜架后 12取消订单-配镜架前 13取消订单-配镜架后
                            // 14补发 15赠品 16采购-有比例入库 17采购-没有比例入库 18手动入库 19手动出库 20盘盈入库 21盘亏出库 22调拨 23调拨 24库存调拨'
                            'change_type' => 19,
                            // '关联sku'
                            'sku' => $v['sku'],
                            //'关联订单号或子单号'
                            'order_number' => $v['out_stock_number'],
                            //'关联变化的ID'
                            'public_id' => 0,
                            //'操作端：1PC端 2PDA'
                            'source' => 1,
                            //'总库存变动前'
                            'stock_before' => $sku_item['stock'],
                            //'总库存变化量：正数为加，负数为减'
                            'stock_change' => -$v['out_stock_num'],
                            //'可用库存变动前'
                            'available_stock_before' => $sku_item['available_stock'],
                            //'可用库存变化量：正数为加，负数为减'
                            'available_stock_change' => -$v['out_stock_num'],
                            // '虚拟仓库存变动前'
                            'fictitious_before' => $item_platform_sku['stock'],
                            // '虚拟仓库存变化量：正数为加，负数为减'
                            'fictitious_change' => -$v['out_stock_num'],
                            //'订单占用变动前'
                            'occupy_stock_before' => $sku_item['occupy_stock'],
                            //'订单占用变化量：正数为加，负数为减'
                            'occupy_stock_change' => 0,
                            //'配货占用变动前'
                            'distribution_stock_before' => $sku_item['distribution_occupy_stock'],
                            //'配货占用变化量：正数为加，负数为减
                            'distribution_stock_change' => 0,
                            //'预售变动前'
                            'presell_num_before' => $sku_item['presell_num'],
                            //'预售变化量：正数为加，负数为减'
                            'presell_num_change' => 0,
                            //'留样库存变动前'
                            'sample_num_before' => $sku_item['sample_num'],
                            //'留样库存变化量：正数为加，负数为减'
                            'sample_num_change' => 0,
                            //'在途库存变动前'
                            'on_way_stock_before' => $sku_item['on_way_stock'],
                            //'在途库存变化量：正数为加，负数为减'
                            'on_way_stock_change' => 0,
                            //'待入库变动前'
                            'wait_instock_num_before' => $sku_item['wait_instock_num'],
                            //'待入库变化量：正数为加，负数为减'
                            'wait_instock_num_change' => 0,
                            'create_person' => session('admin.nickname'),
                            'create_time' => time(),
                            //'关联单号类型：1订单号 2子订单号 3入库单 4出库单 5盘点单 6调拨单'
                            'number_type' => 4,
                        ]);
                    }
                    //先入先出逻辑
                    //                $this->item->setPurchaseOrder($list);
                }
                Db::commit();
                (new StockLog())->commit();
                $platform->commit();
                $item->commit();
            } catch (ValidateException $e) {
                Db::rollback();
                (new StockLog())->rollback();
                $platform->rollback();
                $item->rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                (new StockLog())->rollback();
                $platform->rollback();
                $item->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                (new StockLog())->rollback();
                $platform->rollback();
                $item->rollback();
                $this->error($e->getMessage());
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
                $this->error('此状态不能提交审核');
            }

            //查询入库明细数据
            $list = $this->item
                ->where(['out_stock_id' => ['in', $id]])
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

    /***
     * 出库单成本核算 create@lsw
     */
    public function out_stock_order()
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
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            //总共的
            $totalId = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->column('outstock.id');
            $totalPriceInfo = (new OutStockLog())->calculateMoneyAccordOutStock($totalId);
            // echo '<pre>';
            // var_dump($totalPriceInfo);
            //本页的
            $thisPageId = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->column('outstock.id');
            $thisPagePriceInfo = (new OutStockLog())->calculateMoneyAccordThisPageId($thisPageId);
            if (0 != $thisPagePriceInfo) {
                foreach ($list as $keys => $vals) {
                    if (array_key_exists($vals['id'], $thisPagePriceInfo)) {
                        $list[$keys]['total_money'] = round($thisPagePriceInfo[$vals['id']], 2);
                    }
                }
            }
            $total_money = round($totalPriceInfo['total_money'], 2);
            $result = array("total" => $total, "rows" => $list, "totalPriceInfo" => $total_money);

            return json($result);
        }
        return $this->view->fetch();
    }

    /****
     * 出库单成本核算详情 create@lsw
     */
    public function out_stock_order_detail($ids = null)
    {
        $row = $this->model->get($ids, ['outstocktype']);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $item = (new OutStockLog())->getPurchaseItemInfo($ids);
        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);
        if ($item) {
            $this->assign('item', $item);
        }
        $this->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 出库单批量导入
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/9/24
     * Time: 15:11:52
     */
    public function import()
    {
        $this->model = new \app\admin\model\warehouse\Outstock();
        $_item = new \app\admin\model\warehouse\OutStockItem();
        $_platform = new \app\admin\model\itemmanage\ItemPlatformSku();

        //校验参数空值
        $file = $this->request->request('file');
        !$file && $this->error(__('Parameter %s can not be empty', 'file'));

        //校验文件路径
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        !is_file($filePath) && $this->error(__('No results were found'));

        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        !in_array($ext, ['csv', 'xls', 'xlsx']) && $this->error(__('Unknown data format'));
        if ('csv' === $ext) {
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
                if (0 == $n || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ('xls' === $ext) {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

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
                for ($currentColumn = 1; $currentColumn <= 11; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    if (!empty($val)) {
                        $fields[] = $val;
                    }
                }
            }

            //校验模板文件格式
            // $listName = ['商品SKU', '类型', '补货需求数量'];
            $listName = ['出库分类', '平台', 'SKU', '出库数量'];

            $listName !== $fields && $this->error(__('模板文件格式错误！'));

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
            empty($data) && $this->error('表格数据为空！');

            //获取表格中sku集合
            $sku_arr = [];
            foreach ($data as $k => $v) {
                //获取sku
                $sku = trim($v[2]);
                empty($sku) && $this->error(__('导入失败,第 ' . ($k + 1) . ' 行SKU为空！'));
                $sku_arr[] = $sku;
            }
            //获取出库平台
            $out_plat = $data[0][1];
            switch (trim($out_plat)) {
                case 'zeelool':
                    $out_label = 1;
                    break;
                case 'voogueme':
                    $out_label = 2;
                    break;
                case 'nihao':
                    $out_label = 3;
                    break;
                case 'meeloog':
                    $out_label = 4;
                    break;
                case 'wesee':
                    $out_label = 5;
                    break;
                case 'amazon':
                    $out_label = 8;
                    break;
                case 'zeelool_es':
                    $out_label = 9;
                    break;
                // case 'zeelool_jp':
                //     $label = 1;
                case 'zeelool_de':
                    $out_label = 10;
                    break;
                default:
                    $this->error(__('请检查表格中调出仓的名称'));
            };
            $instock_type = Db::name('out_stock_type')->where('is_del', 1)->field('id,name')->select();
            $instock_type = array_column(collection($instock_type)->toArray(), 'id', 'name');

            //插入一条数据到入库单主表
            $transfer_order['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $transfer_order['type_id'] = $instock_type[$data[0][0]];
            $transfer_order['status'] = 0;
            $transfer_order['platform_id'] = $out_label;
            $transfer_order['createtime'] = date('Y-m-d H:i:s');
            $transfer_order['create_person'] = session('admin.nickname');
            $transfer_order_id = $this->model->insertGetId($transfer_order);

            //批量导入
            $params = [];
            foreach ($data as $v) {
                //获取sku
                $sku = trim($v[2]);

                $sku_plat = $_platform->where(['platform_type' => $out_label, 'sku' => $sku])->find();
                //校验当前平台是否存在此sku映射关系
                if (empty($sku_plat)) {
                    $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . '在' . $out_plat . ' 平台没有映射关系！'));
                }

                //校验sku是否重复
                isset($params[$sku]) && $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 重复！'));

                //获取出库数量
                $replenish_num = (int)$v[3];
                empty($replenish_num) && $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 出库库数量不能为空！'));


                //校验出库数量是否大于当前虚拟仓库存量
                if ($replenish_num > $sku_plat['stock']) {
                    $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 出库数量大于当前虚拟仓库库存！'));
                }

                //拼接参数 插入出库单详情表中
                $params[$sku] = [
                    'out_stock_num' => $replenish_num,
                    'sku' => $sku,
                    'out_stock_id' => $transfer_order_id,
                ];
            }

            $_item->allowField(true)->saveAll($params) ? $this->success('导入成功！') : $this->error('导入失败！');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

}
