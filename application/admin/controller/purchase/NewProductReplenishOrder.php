<?php

namespace app\admin\controller\purchase;

use app\admin\model\itemmanage\Item;
use app\admin\model\NewProduct;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;

/**
 * 补货需求单
 *
 * @icon fa fa-circle-o
 */
class NewProductReplenishOrder extends Backend
{

    /**
     * NewProductReplenishOrder模型对象
     * @var \app\admin\model\purchase\NewProductReplenishOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\NewProductReplenishOrder;
        $this->list = new \app\admin\model\purchase\NewProductReplenishList;
        $this->supplier = new \app\admin\model\purchase\Supplier;
        $this->replenish = new \app\admin\model\purchase\NewProductReplenish;

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 补货需求单
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/21
     * Time: 13:46
     */
    public function replenish()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);

            //如果筛选条件有sku的话 查询这个补货需求单中有这个sku的单子
            if ($filter['sku']) {
                $ids = $this->model->where('sku', 'like', '%' . $filter['sku'] . '%')->group('replenish_id')->field('id')->column('replenish_id');
                //                dump(collection($ids)->toArray());die;
                $map['id'] = ['in', $ids];
                unset($filter['sku']);
            } else {
                $map = array();
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->replenish
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->replenish
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
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/21
     * Time: 14:35
     */
    public function index($ids = null)
    {
        $replenish_id = input('replenish_id');
        $this->assignConfig('id', $ids);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where('replenish_id', $replenish_id)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where('replenish_id', $replenish_id)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                $new_product_replenish_list = Db::name('new_product_replenish_list')->where('replenish_order_id', $v['id'])->field('supplier_name,distribute_num')->select();
                $list[$k]['supplier'] = $new_product_replenish_list;
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 补货需求单分配
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/13
     * Time: 11:22
     */
    public function distribution($ids = null)
    {
        $replenish_id = input('replenish_id');
        $this->assignConfig('id', $ids);
        //根据补货需求单id查出子表数据 同时需要去添加子子表数据为系统默认带出SKU的主供货商和补货需求数量
        $replenish_order = $this->model->where('replenish_id',$ids)->select();
        // dump(collection($replenish_order)->toArray());
        foreach ($replenish_order as $k => $v){
            $replenish_order_list = $this->list->where(['replenish_order_id'=>$v['id'],'replenish_id'=>$v['replenish_id']])->find();
            if (!$replenish_order_list){
                $big_supplier =  Db::name('supplier_sku')->where(['sku'=>$v['sku'],'label'=>1])->find();
                $arrr['replenish_id'] = $v['replenish_id'];
                $arrr['replenish_order_id'] = $v['id'];
                $arrr['sku'] = $v['sku'];
                $arrr['supplier_id'] = $big_supplier['supplier_id'];
                $arrr['supplier_name'] = Db::name('supplier')->where('id',$big_supplier['supplier_id'])->value('supplier_name');
                $arrr['distribute_num'] = $v['replenishment_num'];
                $arrr['create_time'] = date("Y-m-d H:i:s", time());
                $arrr['purchase_person'] = Db::name('supplier')->where('id',$big_supplier['supplier_id'])->value('purchase_person');
                $arrr['type'] = $v['type'];
                $result = Db::name('new_product_replenish_list')->insert($arrr);
            }
        }
        $item = new Item();
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where(['is_del' => 1, 'is_verify' => 1, 'replenish_id' => $replenish_id])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where(['is_del' => 1, 'is_verify' => 1, 'replenish_id' => $replenish_id])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                $list[$k]['new_old'] =$item->where('sku',$list[$k]['sku'])->value('is_new');
                $new_product_replenish_list = Db::name('new_product_replenish_list')->where('replenish_order_id', $v['id'])->field('supplier_name,distribute_num')->select();
                $list[$k]['supplier'] = $new_product_replenish_list;
            }
            // dump($list);die;
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('distribution');
    }

    //补货需求单-分配，增加提交功能，提交后补货需求单状态变为待处理
    public function submit($ids = null)
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->replenish->get($id);
            if ($row['status'] != 1) {
                $this->error('此状态不能提交审核');
            }

            $res = $this->replenish->where('id', $ids)->setField('status', 2);
            if ($res) {
                $this->success('提交审核成功');
            } else {
                $this->error('提交审核失败');
            }
        } else {
            $this->error('404 Not found');
        }

    }

    //补货需求单-处理，增加备注字段和SKU拒绝功能；SKU拒绝时以下拉列表的方式（厂家缺货/断货、厂家不处理售后问题）添加拒绝原因；拒绝原因回写到备注字段
    public function refused($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a", [], 'strip_tags');
            $res = $this->list->where('id',$ids)->setField(['status'=>3,'remarks'=>$params['type']]);
            Db::startTrans();
            try {
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
            $this->success();
        }

        return $this->view->fetch();
    }
    /**
     * 补货需求单确认分配弹出框
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/13
     * Time: 17:22
     */
    public function distribute_detail()
    {

        $id = input('ids');
        $num = $this->model->where('id', $id)->field('id,replenishment_num,sku,replenish_id,type')->find();
        $replenish_order_list = $this->list->where('replenish_order_id',$id)->select();
        // dump(collection($replenish_order_list)->toArray());
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params = $this->request->post();

            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                $whole_num = 0;
                //判断各个供应商分配数量之和是否等于总需求数量 相等的话 写入处理表
                foreach ($params['supplier_num'] as $k => $v) {
                    if (preg_match("/^[1-9][0-9]*$/", (int)$v)) {
                        $whole_num += (int)$v;
                    } else {
                        $this->error('供应商分配数量必须大于0');
                    }
                }
                if ($num['replenishment_num'] > $whole_num) {
                    $this->error('已分配数量小于总需求量，请核对已分配数量');
                } elseif ($num['replenishment_num'] < $whole_num) {
                    $this->error('已分配数量大于总需求量，请核对已分配数量');
                }
                if ($params['supplier_id'] != array_unique($params['supplier_id'])) {
                    $this->error('请不要选择两个相同的供应商');
                }
                //编辑的话先删掉之前的分配数据 然后重新生成数据
                $replenish_order_list_arr = $this->list->where(['replenish_id'=>$num['replenish_id'],'replenish_order_id'=>$num['id']])->delete();
                Db::startTrans();
                try {
                    //根据这条补货需求单的某一个sku进行分配 分配根据选择的供应商以及供应商对应的数量插入处理表 进入创建采购单步骤
                    foreach ($params['supplier_id'] as $k => $v) {

                            $supplier = $this->supplier->where('id', $v)->field('id,purchase_person,supplier_name')->find();
                            //关联补货单id
                            $data['replenish_id'] = $num['replenish_id'];
                            $data['replenish_order_id'] = $num['id'];
                            $data['type'] = $num['type'];
                            $data['sku'] = $num['sku'];
                            $data['supplier_id'] = $supplier['id'];
                            $data['supplier_name'] = $supplier['supplier_name'];
                            $data['distribute_num'] = $params['supplier_num'][$k];
                            $data['purchase_person'] = $supplier['purchase_person'];
                            //插入补货单处理表 同时更新补货单分配表状态为待处理
                            $result = Db::name('new_product_replenish_list')->insert($data);
                            $update = $this->model->where('id', $id)->setField('status', 2);

                    }
                    //每次对补货需求单进行分配的时候 查询这个补货需求单是否还有未分配的sku 如果没有就更新补货需求单状态为待处理 （弃用）现在改为手动提交 -> public function submit
                    // $replenish_order = $this->model->where(['replenish_id' => $num['replenish_id'], 'status' => 1])->find();
                    // if (empty($replenish_order)) {
                    //     $res = $this->replenish->where('id', $num['replenish_id'])->setField('status', 2);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $supplier = $this->supplier->getSupplierData();
        $this->assign('supplier', $supplier);
        $this->assign('num', $num);
        $this->assign('replenish_order_list', $replenish_order_list);
        return $this->view->fetch();
    }

    /**
     * 补货需求单处理
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/14
     * Time: 15:38
     */
    public function handle($ids = null)
    {

        $this->assignConfig('id', $ids);
        $replenish_id = input('replenish_id');
        if (!$ids) {
            $id = $replenish_id;
        } else {
            $id = $ids;
        }
        //        if (!$replenish_id){
        //            $order_ids = $this->model->where('replenish_id',$ids)->column('id');
        //        }else{
        //            $order_ids = $this->model->where('replenish_id',$replenish_id)->column('id');
        //        }
        //        $map['replenish_id'] = ['in', $order_ids];
        $map['replenish_id'] = ['=', $id];
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->list
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->list
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                $new_product_replenish_order = Db::name('new_product_replenish_order')->where('id', $v['replenish_order_id'])->value('replenishment_num');
                $list[$k]['num'] = $new_product_replenish_order;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('handle');
    }

    /**
     * 补货需求单处理
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/14
     * Time: 15:38
     */
    public function handle_detail($ids = null)
    {

        $this->assignConfig('id', $ids);
        $replenish_id = input('replenish_id');
        if (!$ids) {
            $id = $replenish_id;
        } else {
            $id = $ids;
        }
        $replenish = $this->replenish->where('id', $id)->find();
        switch ($replenish['status']) {
            case 1:
                $replenish['name'] = '待分配';
                break;
            case 2:
                $replenish['name'] = '待处理';
                break;
            case 3:
                $replenish['name'] = '部分处理';
                break;
            case 4:
                $replenish['name'] = '已处理';
                break;
        }
        $this->assign('replenish', $replenish);
        //        dump(collection($replenish)->toArray());die;
        //        if (!$replenish_id){
        //            $order_ids = $this->model->where('replenish_id',$ids)->column('id');
        //        }else{
        //            $order_ids = $this->model->where('replenish_id',$replenish_id)->column('id');
        //        }
        //        $map['replenish_id'] = ['in', $order_ids];
        $map['replenish_id'] = ['=', $id];
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->list
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->list
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                $new_product_replenish_order = Db::name('new_product_replenish_order')->where('id', $v['replenish_order_id'])->value('replenishment_num');
                $list[$k]['num'] = $new_product_replenish_order;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('handle_detail');
    }

    /**
     * 创建采购单
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/15
     * Time: 8:58
     */
    public function purchase_order()
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
                    $num = $this->request->post("purchase_num/a");
                    //执行过滤空值
                    array_walk($sku, 'trim_value');
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());

                    $batch_sku = $this->request->post("batch_sku/a");
                    $arrival_num = $this->request->post("arrival_num/a");
                    if ($arrival_num) {
                        //现在分批到货数量必须等于采购数量
                        $arr = [];
                        foreach ($arrival_num as $k => $v) {
                            foreach ($v as $key => $val) {
                                $arr[$key] += $val;
                            }
                        }
                        foreach ($num as $k => $v) {
                            if ($arr[$k] != $v) {
                                $this->error('分批到货数量必须等于采购数量');
                            }
                        }
                    }

                    $result = $this->model->allowField(true)->save($params);

                    //添加采购单商品信息
                    if ($result !== false) {
                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");

                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = trim($supplier_sku[$k]);
                            $data[$k]['product_name'] = $product_name[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            $data[$k]['purchase_id'] = $this->model->id;
                            $data[$k]['purchase_order_number'] = $params['purchase_number'];
                        }
                        //批量添加
                        $this->purchase_order_item->saveAll($data);

                        //添加分批数据
                        $batch_arrival_time = $this->request->post("batch_arrival_time/a");
                        $batch_sku = $this->request->post("batch_sku/a");
                        $arrival_num = $this->request->post("arrival_num/a");

                        //判断是否有分批数据
                        if ($batch_arrival_time && count($batch_arrival_time) > 0) {
                            $i = 0;
                            foreach (array_filter($batch_arrival_time) as $k => $v) {
                                $batch_data['purchase_id'] = $this->model->id;
                                $batch_data['arrival_time'] = $v;
                                $batch_data['batch'] = $i + 1;
                                $batch_data['create_person'] = session('admin.nickname');
                                $batch_data['create_time'] = date('Y-m-d H:i:s');
                                $batch_id = $this->batch->insertGetId($batch_data);
                                $i++;
                                $list = [];
                                foreach ($batch_sku[$k] as $key => $val) {
                                    if (!$val || !$arrival_num[$k][$key]) {
                                        continue;
                                    }
                                    $list[$key]['sku'] = $val;
                                    $list[$key]['arrival_num'] = $arrival_num[$k][$key];
                                    $list[$key]['purchase_batch_id'] = $batch_id;
                                }

                                $this->batch_item->saveAll($list);
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
                    $this->success('添加成功！！', url('PurchaseOrder/index'));
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //查询新品数据
        $new_product_ids = $this->request->get('new_product_ids');
        if ($new_product_ids) {
            //查询所选择的数据
            $where['new_product.id'] = ['in', $new_product_ids];
            $row = (new NewProduct())->where($where)->with(['newproductattribute'])->select();
            $row = collection($row)->toArray();
            dump($row);
            die;
            foreach ($row as $v) {
                if ($v['item_status'] != 1) {
                    $this->error(__('只有待选品状态能够创建！！'), url('new_product/index'));
                }
            }

            //提取供应商id
            $supplier = array_unique(array_column($row, 'supplier_id'));
            if (count($supplier) > 1) {
                $this->error(('必须选择相同的供应商！！'), url('new_product/index'));
            }
            $this->assign('row', $row);
            $this->assign('is_new_product', 1);
        }
        //
        //
        //        //查询供应商
        //        $supplier = new \app\admin\model\purchase\Supplier;
        //        $data = $supplier->getSupplierData();
        //        $this->assign('supplier', $data);


        //查询新品数据
        $new_product_ids = $this->request->get('new_product_ids');

        if ($new_product_ids) {
            //查询所选择的数据 批量生成
            $where['id'] = ['in', $new_product_ids];
            $row = $this->list
                ->where($where)
                ->select();
            $row = collection($row)->toArray();

            //提取供应商id
            $supplier = array_unique(array_column($row, 'supplier_id'));
            if (count($supplier) > 1) {
                $this->error(__('必须选择相同的供应商！！'), url('purchase/new_product_replenish_order/handle'));
            }
            $supplier_info = $this->supplier->where('id', $supplier[0])->field('supplier_name,address,id,purchase_person')->find();
            //            dump($supplier_info);die;

            $this->assign('row', $row);
            $this->assign('supplier', $supplier_info);
            $this->assign('is_new_product', 1);
        } else {
            //查询补货需求单处理表 单条生成采购单
            $new_product_ids = $this->request->get('ids');
            $new_product_ids = input('ids');
            $detail = $this->list->where('id', $new_product_ids)->find();

            //当前信息对应的供应商信息
            $supplier = $this->supplier->where('id', $detail['supplier_id'])->field('supplier_name,address,id,purchase_person')->find();
            $this->assign('supplier', $supplier);
        }

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        //生成采购编号
        $purchase_number = 'PO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('purchase_number', $purchase_number);
        $this->assignconfig('newdatetime', date('Y-m-d H:i:s'));
        return $this->view->fetch();
    }

    /**
     * 审核通过补货需求单
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/15
     * Time: 9:14
     */
    public function morePassAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,status,is_verify')->select();
            foreach ($row as $v) {
                if ($v['status'] != 1) {
                    $this->error('只有待分配状态才能操作！！');
                }
                if ($v['is_verify'] != 0) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['is_verify'] = 1;
            $data['check_time'] = date("Y-m-d H:i:s", time());

            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

            if ($res !== false) {
                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /**
     * 审核拒绝补货需求单
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/15
     * Time: 11:15
     */
    public function moreAuditRefused($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,status,is_verify')->select();
            foreach ($row as $v) {
                if ($v['status'] != 1) {
                    $this->error('只有待分配状态才能操作！！');
                }
                if ($v['is_verify'] != 0) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['is_verify'] = 2;
            $data['check_time'] = date("Y-m-d H:i:s", time());

            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('审核拒绝成功');
            } else {
                $this->error('审核拒绝失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /**
     * 确认分配
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/13
     * Time: 15:23
     */
    public function distribution_confirm()
    {
        $ids = $this->request->post("ids/a");
        dump($ids);
        die;
        if (!$ids) {
            $this->error('缺少参数！！');
        }

    }

    public function export_replenish_order()
    {
        $list = Db::name('new_product_mapping')
            ->where('create_time','>','2020-08-28 00:00:00')
            ->select();
        $arr = [];
        foreach ($list as $k =>$v){
            if ($v['website_type'] == 1){
                $v['site_name'] = 'zeelool';
                $fenpei = Db::name('new_product_replenish_list')->where(['sku'=>$v['sku'],'replenish_id'=>$v['replenish_id']])->find();
                if ($fenpei['real_dis_num'] > 0){
                    // dump('sku:'.$fenpei['sku'].'已采购');
                }else{
                    $arr['zeelool'][$k] = $v;
                }
            }
            if ($v['website_type'] == 2){
                $v['site_name'] = 'voogueme';

                $fenpei = Db::name('new_product_replenish_list')->where(['sku'=>$v['sku'],'replenish_id'=>$v['replenish_id']])->find();
                if ($fenpei['real_dis_num'] > 0){
                    // dump('sku:'.$fenpei['sku'].'已采购');
                }else{
                    $arr['voogueme'][$k] = $v;
                }
            }
            if ($v['website_type'] == 3){
                $v['site_name'] = 'nihao';

                $fenpei = Db::name('new_product_replenish_list')->where(['sku'=>$v['sku'],'replenish_id'=>$v['replenish_id']])->find();
                if ($fenpei['real_dis_num'] > 0){
                    // dump('sku:'.$fenpei['sku'].'已采购');
                }else{
                    $arr['nihao'][$k] = $v;
                }
            }
            if ($v['website_type'] == 4){
                $v['site_name'] = 'meeloog';

                $fenpei = Db::name('new_product_replenish_list')->where(['sku'=>$v['sku'],'replenish_id'=>$v['replenish_id']])->find();
                if ($fenpei['real_dis_num'] > 0){
                    // dump('sku:'.$fenpei['sku'].'已采购');
                }else{
                    $arr['meeloog'][$k] = $v;
                }
            }
            if ($v['website_type'] == 5){
                $v['site_name'] = 'wesee';

                $fenpei = Db::name('new_product_replenish_list')->where(['sku'=>$v['sku'],'replenish_id'=>$v['replenish_id']])->find();
                if ($fenpei['real_dis_num'] > 0){
                    // dump('sku:'.$fenpei['sku'].'已采购');
                }else{
                    $arr['wesee'][$k] = $v;
                }
            }
            if ($v['website_type'] == 8){
                $v['site_name'] = 'amazon';

                $fenpei = Db::name('new_product_replenish_list')->where(['sku'=>$v['sku'],'replenish_id'=>$v['replenish_id']])->find();
                if ($fenpei['real_dis_num'] > 0){
                    // dump('sku:'.$fenpei['sku'].'已采购');
                }else{
                    $arr['amazon'][$k] = $v;
                }
            }

        }
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "站点")
            ->setCellValue("B1", "SKU")
            ->setCellValue("C1", "补货提报数量");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "创建人")
            ->setCellValue("E1", "创建时间");


        $num = 0;
        foreach ($arr as $key => $value) {
            foreach ($value as $kk =>$vv){
                $spreadsheet->getActiveSheet()->setCellValue("A" . ($num * 1 + 2), $vv['site_name']);
                $spreadsheet->getActiveSheet()->setCellValue("B" . ($num * 1 + 2), $vv['sku']);
                $spreadsheet->getActiveSheet()->setCellValue("C" . ($num * 1 + 2), $vv['replenish_num']);
                $spreadsheet->getActiveSheet()->setCellValue("D" . ($num * 1 + 2), $vv['create_person']);
                $spreadsheet->getActiveSheet()->setCellValue("E" . ($num * 1 + 2), $vv['create_time']);

                $num += 1;
            }
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(40);


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
        $savename = '8.28号之后补货需求单数据' . date("YmdHis", time());;

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


}
