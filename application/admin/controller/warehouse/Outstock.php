<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
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
                        if ($out_stock_num[$k] > $sku_platform['stock']){
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
                        if ($out_stock_num[$k] > $sku_platform['stock']){
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
        foreach ($item as $k=>$v){
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
            $info = $itemplatform->where(['sku'=>$item[$k]['sku'],'platform_type'=>$row['platform_id']])->field('stock')->find();
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
            ->join('fa_out_stock o','a.out_stock_id = o.id')
            ->where($where)
            ->select();
        $data['status'] = input('status');
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        if ($data['status'] == 2) {
            //批量审核出库 扣减sku的总数量不能大于当前sku的虚拟仓库存量
            foreach ($list as $k => $v) {
                if (!$arr[$v['sku']]) {
                    $arr[$v['sku']]['num'] = $v['out_stock_num'];
                    $arr[$v['sku']]['platform_type'] = $v['platform_id'];
                } else {
                    $arr[$v['sku']]['num'] = $v['out_stock_num'] + $arr[$v['sku']];
                }
            }
            // dump(collection($list)->toArray());
            // dump($arr);die;
            foreach ($arr as $k => $v) {
                $item_platform_sku = $platform->where(['sku'=>$k,'platform_type'=>$v['platform_id']])->field('stock')->find();
                if ($v['num'] > $item_platform_sku['stock']) {
                    $this->error('出库的数量大于sku:' . $k . '的虚拟仓库存，请检查后重试');
                }
            }
        }
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

        if ($res != false) {
            /**
             * @todo 审核通过扣减库存逻辑 
             */

            if ($data['status'] == 2) {
//                dump(collection($list)->toArray());die;

                //出库扣减库存
                foreach ($list as $v) {
                    //扣除商品表商品总库存
                    //总库存
                    $item = new \app\admin\model\itemmanage\Item;
                    $item_map['sku'] = $v['sku'];
                    $item->where($item_map)->dec('stock', $v['out_stock_num'])->dec('available_stock', $v['out_stock_num'])->update();

                    //直接扣减此平台sku的库存
                    $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->dec('stock', $v['out_stock_num'])->update();

                    // //同事配镜 厂家质量问题 带回办公室 扣减库存最大的那个站
                    // if (in_array($v['type_id'], [3, 5, 9,23])) {
                    //     $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock desc')->field('platform_type,stock')->find();
                    //     $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->dec('stock', $v['out_stock_num'])->update();
                    // }else{
                    //     //盘点的时候盘盈入库 盘亏出库 的同时要对虚拟库存进行一定的操作
                    //     //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                    //     $item_platform_sku = $platform->where('sku',$v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                    //     $all_num = count($item_platform_sku);
                    //     $whole_num = $platform->where('sku',$v['sku'])->sum('stock');
                    //     //盘盈或者盘亏的数量 根据此数量对平台sku虚拟库存进行操作
                    //     $stock_num = $v['out_stock_num'];
                    //     foreach ($item_platform_sku as $key => $val) {
                    //         //最后一个站点 剩余数量分给最后一个站
                    //         if (($all_num - $key) == 1) {
                    //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->dec('stock', $stock_num)->update();
                    //         } else {
                    //             $num = round($v['out_stock_num'] * $val['stock']/$whole_num);
                    //             $stock_num -= $num;
                    //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->dec('stock', $num)->update();
                    //         }
                    //     }
                    // }
                }

                //插入日志表
                (new StockLog())->setData([
                    'type'                      => 2,
                    'two_type'                  => 4,
                    'sku'                       => $v['sku'],
                    'public_id'                 => $v['out_stock_id'],
                    'stock_change'              => -$v['out_stock_num'],
                    'available_stock_change'    => -$v['out_stock_num'],
                    'create_person'             => session('admin.nickname'),
                    'create_time'               => date('Y-m-d H:i:s'),
                    'remark'                    => '出库单减少总库存,减少可用库存'
                ]);

                //先入先出逻辑
                $this->item->setPurchaseOrder($list);
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
                ->where(['status'=>2])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['outstocktype'])
                ->where(['status'=>2])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();    
            $list = collection($list)->toArray();
            //总共的
            $totalId = $this->model
            ->with(['outstocktype'])
            ->where(['status'=>2])
            ->where($where)
            ->column('outstock.id');
            $totalPriceInfo = (new OutStockLog())->calculateMoneyAccordOutStock($totalId);
            // echo '<pre>';
            // var_dump($totalPriceInfo);
            //本页的
            $thisPageId = $this->model
            ->with(['outstocktype'])
            ->where(['status'=>2])
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->column('outstock.id');
            $thisPagePriceInfo = (new OutStockLog())->calculateMoneyAccordThisPageId($thisPageId);
            if(0 != $thisPagePriceInfo){
                foreach($list as $keys => $vals){
                    if(array_key_exists($vals['id'],$thisPagePriceInfo)){
                         $list[$keys]['total_money'] = round($thisPagePriceInfo[$vals['id']],2);
                    }
                }
            }
            $total_money = round($totalPriceInfo['total_money'],2);
            $result = array("total" => $total, "rows" => $list,"totalPriceInfo"=>$total_money);

            return json($result);
        }
        return $this->view->fetch();
    }
    /****
     * 出库单成本核算详情 create@lsw
     */
    public function out_stock_order_detail($ids=null)
    {
        $row = $this->model->get($ids,['outstocktype']);
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
        if($item){
            $this->assign('item', $item);
        }
            $this->assign("row", $row);
        return $this->view->fetch();
    }
}
