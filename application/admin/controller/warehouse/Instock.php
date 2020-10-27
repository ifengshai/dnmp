<?php

namespace app\admin\controller\warehouse;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\admin\model\StockLog;

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

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['deleteItem'];

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

            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                $ids = $this->instockItem->where($smap)->column('in_stock_id');
                $map['instock.id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }


            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['checkorder', 'instocktype'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['checkorder', 'instocktype'])
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
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    //存在平台id 代表把当前入库单的sku分给这个平台 首先做判断 判断入库单的sku是否都有此平台对应的映射关系
                    if ($params['platform_id']) {
                        foreach (array_filter($sku) as $k => $v) {
                            $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                            $sku_platform = $item_platform_sku->where(['sku' => $v, 'platform_type' => $params['platform_id']])->find();
                            if (!$sku_platform) {
                                $this->error('此sku：' . $v . '没有同步至此平台，请先同步后重试');
                            }
                        }
                        $params['create_person'] = session('admin.nickname');
                        $params['createtime'] = date('Y-m-d H:i:s', time());
                        $result = $this->model->allowField(true)->save($params);

                        //添加入库信息
                        if ($result !== false) {

                            $in_stock_num = $this->request->post("in_stock_num/a");
                            $sample_num = $this->request->post("sample_num/a");
                            $purchase_id = $this->request->post("purchase_id/a");
                            $data = [];
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['in_stock_num'] = $in_stock_num[$k];
                                $data[$k]['sample_num'] = $sample_num[$k];
                                $data[$k]['no_stock_num'] = $in_stock_num[$k];
                                $data[$k]['purchase_id'] = $purchase_id[$k];
                                $data[$k]['in_stock_id'] = $this->model->id;
                            }
                            //批量添加
                            $this->instockItem->allowField(true)->saveAll($data);
                        }
                    } else {
                        $params['create_person'] = session('admin.nickname');
                        $params['createtime'] = date('Y-m-d H:i:s', time());
                        $result = $this->model->allowField(true)->save($params);

                        //添加入库信息
                        if ($result !== false) {
                            //更改质检单为已创建入库单
                            $check = new \app\admin\model\warehouse\Check;
                            $check->allowField(true)->save(['is_stock' => 1], ['id' => $params['check_id']]);


                            $in_stock_num = $this->request->post("in_stock_num/a");
                            $sample_num = $this->request->post("sample_num/a");
                            $purchase_id = $this->request->post("purchase_id/a");
                            $data = [];
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['in_stock_num'] = $in_stock_num[$k];
                                $data[$k]['sample_num'] = $sample_num[$k];
                                $data[$k]['no_stock_num'] = $in_stock_num[$k];
                                $data[$k]['purchase_id'] = $purchase_id[$k];
                                $data[$k]['in_stock_id'] = $this->model->id;
                            }
                            //批量添加
                            $this->instockItem->allowField(true)->saveAll($data);
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

        //质检单id
        $ids = input('ids');
        //根据type值判断是从哪个入口进入的添加入库单 type值为1是从质检入口进入 type值为2是从入库单直接添加 直接添加的需要选择站点
        $types = input('type');
        if ($ids) {
            $this->assign('ids', $ids);
            $this->assign('instocktype', 1);
        }

        //查询入库分类
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);

        //查询质检单
        $check = new \app\admin\model\warehouse\Check;
        $map['status'] = 2;
        $map['is_stock'] = 0;
        $purchase_data = $check->where($map)->order('createtime desc')->column('check_order_number', 'id');
        $this->assign('purchase_data', $purchase_data);

        //质检单
        $instock_number = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('types', $types);
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
        $check_map['Check.is_stock'] = 0;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem')
            ->where($check_map)
            ->field('Check.purchase_id,Check.replenish_id,sku,supplier_sku,purchase_num,check_num,arrivals_num,quantity_num,sample_num')
            ->group('CheckItem.id')
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

                    $sku = $this->request->post("sku/a");
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }


                    $result = $row->allowField(true)->save($params);

                    //修改产品
                    if ($result !== false) {
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
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);

        //查询质检单
        $check = new \app\admin\model\warehouse\Check;
        $map['status'] = 2;
        $purchase_data = $check->where($map)->order('createtime desc')->column('check_order_number', 'id');
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
        if ($row['platform_id'] > 0){
            $this->assign('types',2);
        }
        if ($row['check_id'] > 0){
            $this->assign('types',1);
        }
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
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);

        //查询质检单
        $check = new \app\admin\model\warehouse\Check;
        $map['status'] = 2;
        $purchase_data = $check->where($map)->order('createtime desc')->column('check_order_number', 'id');
        $this->assign('purchase_data', $purchase_data);


        /***********查询入库商品信息***************/
        //查询入库单商品信息
        $item_map['in_stock_id'] = $ids;
        $item = $this->instockItem->where($item_map)->select();

        //查询对应质检数据
        $checkItem = new \app\admin\model\warehouse\CheckItem;
        $check_data = $checkItem->where('check_id', $row['check_id'])->column('*', 'sku');
        /***********end***************/
        if ($row['platform_id'] > 0){
            $this->assign('types',2);
        }
        if ($row['check_id'] > 0){
            $this->assign('types',1);
        }

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

        $new_product_mapp = new \app\admin\model\NewProductMapping();
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->model->startTrans();
        $item = new \app\admin\model\itemmanage\Item;
        $item->startTrans();
        $purchase = new \app\admin\model\purchase\PurchaseOrderItem;
        $allocated = new \app\admin\model\itemmanage\GoodsStockAllocated;
        $purchase->startTrans();
        $this->purchase->startTrans();

        try {
            $data['create_person'] = session('admin.nickname');
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

            if ($data['status'] == 2) {
                /**
                 * @todo 审核通过增加库存 并添加入库单入库数量
                 */
                $error_num = [];
                foreach ($list as $k => $v) {

                    //审核通过对虚拟库存的操作
                    //审核通过时按照补货需求比例 划分各站虚拟库存 如果未关联补货需求单则按照当前各站虚拟库存数量实时计算各站比例（弃用）
                    //采购过来的 有采购单的 1、有补货需求单的直接按比例分配 2、没有补货需求单的都给m站
                    if ($v['purchase_id']) {
                        if ($v['replenish_id']) {
                            //查询各站补货需求量占比
                            $rate_arr = $new_product_mapp
                                ->where(['replenish_id' => $v['replenish_id'], 'sku' => $v['sku'], 'is_show' => 0])
                                // ->order('rate asc')
                                ->field('rate,website_type')
                                ->select();
                            // dump(collection($rate_arr)->toArray());die;
                            //根据入库数量插入各站虚拟仓库存
                            $all_num = count($rate_arr);
                            $stock_num = $v['in_stock_num'];
                            foreach ($rate_arr as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    //增加站点虚拟仓库存
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $stock_num);
                                    //入库的时候减少待入库数量
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $stock_num);

                                } else {
                                    $num = round($v['in_stock_num'] * $val['rate']);
                                    $stock_num -= $num;
                                    //增加站点虚拟仓库存
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $num);
                                    //入库的时候减少待入库数量
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $num);
                                }
                            }
                        } else {
                            //记录没有采购比例直接入库的sku
                            $allocated->allowField(true)->save(['sku' => $v['sku'], 'change_num' => $v['in_stock_num'], 'create_time' => date('Y-m-d H:i:s')]);

                            $item_platform_sku = $platform->where(['sku' => $v['sku'], 'platform_type' => 4])->field('platform_type,stock')->find();
                            //sku没有同步meeloog站 无法添加虚拟库存 必须先同步
                            if (empty($item_platform_sku)) {
                                $this->error('sku：' . $v['sku'] . '没有同步meeloog站，请先同步');
                            }
                            $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $v['in_stock_num']);
                        }
                    } //不是采购过来的 如果有站点id 说明是指定增加此平台sku
                    elseif ($v['platform_id']) {
                        $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->setInc('stock', $v['in_stock_num']);
                    } //没有采购单也没有站点id 说明是盘点过来的
                    else {
                        //根据当前sku 和当前 各站的虚拟库存进行分配
                        $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);

                        $stock_num = $v['in_stock_num'];
                        //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照平均规则分配 例如五个站都有此品 那么比例就是20%
                        $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                        if ($stock_all_num == 0) {
                            $rate_rate = 1/$all_num;
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                } else {
                                    $num = round($v['in_stock_num'] * $rate_rate);
                                    $stock_num -= $num;
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                }
                            }
                        } else {
                            //某個平台這個sku存在庫存 就按照當前各站的虛擬庫存進行分配
                            $whole_num = $platform->where('sku', $v['sku'])->sum('stock');
                            $stock_num = $v['in_stock_num'];
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                } else {
                                    $num = round($v['in_stock_num'] * $val['stock'] / $whole_num);
                                    $stock_num -= $num;
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                }
                            }
                        }
                    }
                    // if ($v['replenish_id']) {
                    //     //查询各站补货需求量占比
                    //     $rate_arr = $new_product_mapp->where(['replenish_id' => $v['replenish_id'], 'sku' => $v['sku'], 'is_show' => 0])->order('rate asc')->field('rate,website_type')->select();
                    //     // dump(collection($rate_arr)->toArray());die;
                    //     //根据入库数量插入各站虚拟仓库存
                    //     $all_num = count($rate_arr);
                    //     $stock_num = $v['in_stock_num'];
                    //     foreach ($rate_arr as $key => $val) {
                    //         //最后一个站点 剩余数量分给最后一个站
                    //         if (($all_num - $key) == 1) {
                    //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $stock_num);
                    //         } else {
                    //             $num = round($v['in_stock_num'] * $val['rate']);
                    //             $stock_num -= $num;
                    //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $num);
                    //         }
                    //     }
                    // }
                    // else {
                    //     //样品入库单独逻辑给现在库存最大的那个站
                    //     if ($v['type_id'] == 6) {
                    //         $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock desc')->field('platform_type,stock')->find();
                    //         $stock_num = $v['in_stock_num'];
                    //         $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $stock_num);
                    //     }
                    //     //现在先使用此规则 没有关联到采购需求比例的入库单，默认分配到杭州站点的虚拟仓（meeloog）
                    //     else{
                    //         $item_platform_sku = $platform->where(['sku'=>$v['sku'],'platform_type'=>4])->field('platform_type,stock')->find();
                    //         //sku没有同步meeloog站 无法添加虚拟库存 必须先同步
                    //         if (empty($item_platform_sku)){
                    //             $this->error('sku：'.$v['sku'].'没有同步meeloog站，请先同步');
                    //         }
                    //         $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $v['in_stock_num']);
                    //     }
                    //     // else {
                    //     //     //没有补货需求单的入库单 根据当前sku 和当前 各站的虚拟库存进行分配
                    //     //     $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                    //     //     $all_num = count($item_platform_sku);
                    //     //
                    //     //     $stock_num = $v['in_stock_num'];
                    //     //     //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照‘发牌’规则进行分库存
                    //     //     $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                    //     //     if ($stock_all_num == 0) {
                    //     //         //当前入库数量有几个就循环几次
                    //     //         foreach ($item_platform_sku as $key => $val) {
                    //     //
                    //     //             //一直发直到$v['in_stock_num']为0
                    //     //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock');
                    //     //             $stock_num--;
                    //     //             if ($stock_num == 0) {
                    //     //                 break;
                    //     //             } else {
                    //     //                 if (($all_num - $key) == 1) {
                    //     //                     $this->send_stock($item_platform_sku, $stock_num, $v['sku'], $all_num);
                    //     //                 }
                    //     //             }
                    //     //         }
                    //     //     } else {
                    //     //         //某個平台這個sku存在庫存 就按照當前各站的虛擬庫存進行分配
                    //     //         $whole_num = $platform->where('sku', $v['sku'])->sum('stock');
                    //     //         //                                dump($whole_num);die;
                    //     //         $stock_num = $v['in_stock_num'];
                    //     //         foreach ($item_platform_sku as $key => $val) {
                    //     //             //最后一个站点 剩余数量分给最后一个站
                    //     //             if (($all_num - $key) == 1) {
                    //     //                 $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                    //     //             } else {
                    //     //                 $num = round($v['in_stock_num'] * $val['stock'] / $whole_num);
                    //     //                 $stock_num -= $num;
                    //     //                 $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                    //     //             }
                    //     //         }
                    //     //     }
                    //     // }
                    // }

                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    if ($v['sku']) {
                        //增加商品表里的商品库存、可用库存、留样库存
                        $stock_res = $item->where($item_map)->inc('stock', $v['in_stock_num'])->inc('available_stock', $v['in_stock_num'])->inc('sample_num', $v['sample_num'])->update();
                        //减少待入库数量
                        $stock_res1 = $item->where($item_map)->dec('wait_instock_num', $v['in_stock_num'])->update();
                    }

                    if ($stock_res === false) {
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
                        $check_map['Check.purchase_id'] = $check_res['purchase_id'];
                        $check_map['type'] = 1;
                        $check = new \app\admin\model\warehouse\Check;
                        //总到货数量
                        $all_arrivals_num = $check->hasWhere('checkItem')->where($check_map)->group('Check.purchase_id')->sum('arrivals_num');

                        $all_purchase_num = $purchase->where('purchase_id', $check_res['purchase_id'])->sum('purchase_num');
                        //总到货数量 小于 采购单采购数量 则为部分入库 
                        if ($all_arrivals_num < $all_purchase_num) {
                            $stock_status = 1;
                        } else {
                            $stock_status = 2;
                        }
                        //修改采购单入库状态
                        $purchase_data['stock_status'] = $stock_status;
                        $this->purchase->where(['id' => $check_res['purchase_id']])->update($purchase_data);
                    }
                    //如果为退货单 修改退货单状态为入库
                    if ($check_res['order_return_id']) {
                        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
                        $orderReturn->where(['id' => $check_res['order_return_id']])->update(['in_stock_status' => 1]);
                    }


                    //插入日志表
                    (new StockLog())->setData([
                        'type' => 2,
                        'two_type' => 3,
                        'sku' => $v['sku'],
                        'public_id' => $v['in_stock_id'],
                        'stock_change' => $v['in_stock_num'],
                        'available_stock_change' => $v['in_stock_num'],
                        'sample_num_change' => $v['sample_num'],
                        'create_person' => session('admin.nickname'),
                        'create_time' => date('Y-m-d H:i:s'),
                        'remark' => '入库单增加总库存,可用库存,样品库存'
                    ]);
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
     * @$item_platform_sku 平台映射关系列表
     * @$stock_all_num 当前剩余未分配的数量
     * @$sku 当前sku
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/8/3
     * Time: 14:52
     */
    function send_stock($item_platform_sku, $stock_all_num, $sku, $all_num)
    {
        //        dump(collection($item_platform_sku)->toArray());dump($stock_all_num);dump($sku);
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        foreach ($item_platform_sku as $key => $val) {
            //一直发直到$v['in_stock_num']为0
            $platform->where(['sku' => $sku, 'platform_type' => $val['platform_type']])->setInc('stock');
            $stock_all_num--;
            if ($stock_all_num == 0) {
                break;
            } else {
                if (($all_num - $key) == 1) {
                    $this->send_stock($item_platform_sku, $stock_all_num, $sku, $all_num);
                }
            }
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
            //如果取消入库单 则 去掉质检单已入库标记
            $check = new \app\admin\model\warehouse\Check;
            $check->allowField(true)->save(['is_stock' => 0], ['id' => $row['check_id']]);

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

    //入库单成本核算 create@lsw
    public function account_in_stock_order()
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
                ->where(['instock.status' => 2, 'type_id' => 1])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['checkorder', 'instocktype'])
                ->where(['instock.status' => 2, 'type_id' => 1])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $totalId = $this->model
                ->with(['checkorder', 'instocktype'])
                ->where(['instock.status' => 2, 'type_id' => 1])
                ->where($where)
                ->column('instock.id');
            $thisPageId = $this->model
                ->with(['checkorder', 'instocktype'])
                ->where(['instock.status' => 2, 'type_id' => 1])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->column('instock.id');
            $totalPriceInfo = $this->instockItem->calculateMoneyAccordInStock($totalId);
            $thisPagePriceInfo = $this->instockItem->calculateMoneyAccordInStockThisPageId($thisPageId);
            if (0 != $thisPagePriceInfo) {
                foreach ($list as $keys => $vals) {
                    if (array_key_exists($vals['id'], $thisPagePriceInfo)) {
                        $list[$keys]['total_money'] = $thisPagePriceInfo[$vals['id']];
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list, "totalPriceInfo" => $totalPriceInfo['total_money']);
            return json($result);
        }
        return $this->view->fetch();
    }

    //入库单成本核算详情 create@lsw
    public function account_in_stock_order_detail($ids = null)
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

        // //查询质检单
        // $check = new \app\admin\model\warehouse\Check;
        // $map['status'] = 2;
        // $purchase_data = $check->where($map)->order('createtime desc')->column('check_order_number', 'id');
        // $this->assign('purchase_data', $purchase_data);


        /***********查询入库商品信息***************/
        //查询入库单商品信息
        // $item_map['in_stock_id'] = $ids;
        // $item = $this->instockItem->where($item_map)->select();
        $item = $this->instockItem->getPurchaseItemInfo($ids);
        // var_dump($item);
        // exit;
        //查询对应质检数据
        // $checkItem = new \app\admin\model\warehouse\CheckItem;
        // $check_data = $checkItem->where('check_id', $row['check_id'])->column('*', 'sku');
        /***********end***************/
        if ($item) {
            $this->assign('item', $item);
        }
        // $this->assign('check_data', $check_data);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 入库单批量导出xls
     *
     * @Description
     * @author wpl
     * @since 2020/02/28 14:45:39
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        if ($ids) {
            $map['a.id'] = ['in', $ids];
        }

        //自定义sku搜索
        $filter = json_decode($this->request->get('filter'), true);
        if ($filter['sku']) {
            $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
            $ids = $this->instockItem->where($smap)->column('in_stock_id');
            $map['instock.id'] = ['in', $ids];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        list($where) = $this->buildparams();
        $list = $this->model->alias('a')
            ->field('in_stock_number,sku,in_stock_num,createtime,create_person')
            ->join(['fa_in_stock_item' => 'b'], 'b.in_stock_id=a.id')
            ->where($where)
            ->where($map)
            ->select();

        $list = collection($list)->toArray();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "入库单号")
            ->setCellValue("B1", "SKU")
            ->setCellValue("C1", "入库数量");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "创建人")
            ->setCellValue("E1", "创建时间");


        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['in_stock_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['in_stock_num']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['createtime']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);


        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:E' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '入库单数据' . date("YmdHis", time());;

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
     * 入库单批量导入
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/9/24
     * Time: 14:00:16
     */
    public function import()
    {
        $this->model = new \app\admin\model\warehouse\Instock();
        $_item = new \app\admin\model\warehouse\InstockItem();
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
            $listName = ['入库分类', '平台', 'SKU', '入库数量'];

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
            //获取入库平台
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
            $instock_type = Db::name('in_stock_type')->where('is_del',1)->field('id,name')->select();
            $instock_type = array_column(collection($instock_type)->toArray(), 'id', 'name');

            //插入一条数据到入库单主表
            $transfer_order['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $transfer_order['type_id'] = $instock_type[$data[0][0]];
            $transfer_order['status'] = 0;
            $transfer_order['platform_id'] =$out_label;
            $transfer_order['createtime'] = date('Y-m-d H:i:s');
            $transfer_order['create_person'] = session('admin.nickname');
            $transfer_order_id = $this->model->insertGetId($transfer_order);

            //批量导入
            $params = [];
            foreach ($data as $v) {
                //获取sku
                $sku = trim($v[2]);

                //校验当前平台是否存在此sku映射关系
                if (empty($_platform->where(['platform_type'=>$out_label,'sku'=>$sku])->find())){
                    $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku .'在'. $out_plat.' 平台没有映射关系！'));
                }

                //校验sku是否重复
                isset($params[$sku]) && $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 重复！'));

                //获取入库数量
                $replenish_num = (int)$v[3];
                empty($replenish_num) && $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 入库数量不能为空！'));

                //拼接参数 插入入库单详情表中
                $params[$sku] = [
                    'in_stock_num' => $replenish_num,
                    'sku' => $sku,
                    'in_stock_id' => $transfer_order_id,
                ];
            }

            $_item->allowField(true)->saveAll($params) ? $this->success('导入成功！') : $this->error('导入失败！');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

}
