<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use fast\Http;
use fast\Alibaba;


/**
 * 采购单管理
 *
 * @icon fa fa-circle-o
 */
class PurchaseOrder extends Backend
{

    /**
     * PurchaseOrder模型对象
     * @var \app\admin\model\purchase\PurchaseOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\PurchaseOrder;
        $this->purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index123()
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
                ->where('online_status', 'not in', ['success', 'cancel', 'waitbuyerpay'])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('online_status', 'not in', ['success', 'cancel', 'waitbuyerpay', 'confirm_goods_but_not_fund', 'send_goods_but_not_fund'])
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

                    //添加采购单商品信息
                    if ($result !== false) {
                        $sku = $this->request->post("sku/a");
                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");
                        $num = $this->request->post("purchase_num/a");
                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");

                        $data = [];
                        foreach ($sku as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = $supplier_sku[$k];
                            $data[$k]['product_name'] = $product_name[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            $data[$k]['purchase_id'] = $this->model->id;
                            $data[$k]['purchase_order_number'] = $params['purchase_number'];
                        }
                        //批量添加
                        $this->purchase_order_item->allowField(true)->saveAll($data);
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        //生成采购编号
        $purchase_number = 'PO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('purchase_number', $purchase_number);
        return $this->view->fetch();
    }

    /**
     * 异步获取合同数据
     */
    public function getContractData()
    {
        $id = input('id');
        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $data = $contract->get($id);
        //查询合同商品信息
        $contract_item = new \app\admin\model\purchase\ContractItem;
        $map['contract_id'] = $id;
        $item = $contract_item->where($map)->select();
        if ($item) {
            $data->item = $item;
        }
        if ($data) {
            $this->success('', '', $data);
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
        if ($row['purchase_status'] > 0) {
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

                    //添加合同产品
                    if ($result !== false) {
                        $sku = $this->request->post("sku/a");
                        $product_name = $this->request->post("product_name/a");
                        $supplier_sku = $this->request->post("supplier_sku/a");
                        $num = $this->request->post("purchase_num/a");
                        $price = $this->request->post("purchase_price/a");
                        $total = $this->request->post("purchase_total/a");
                        $item_id = $this->request->post("item_id/a");

                        $data = [];
                        foreach ($sku as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['supplier_sku'] = $supplier_sku[$k];
                            $data[$k]['product_name'] = $product_name[$k];
                            $data[$k]['purchase_num'] = $num[$k];
                            $data[$k]['purchase_price'] = $price[$k];
                            $data[$k]['purchase_total'] = $total[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['purchase_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->purchase_order_item->allowField(true)->saveAll($data);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $supplier = $supplier->getSupplierData();
        $this->assign('supplier', $supplier);

        //查询产品信息
        $map['purchase_id'] = $ids;
        $item = $this->purchase_order_item->where($map)->select();
        $this->assign('item', $item);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $ids = $ids ? $ids : input('id');
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $supplier = $supplier->getSupplierData();
        $this->assign('supplier', $supplier);

        //查询产品信息
        $map['purchase_id'] = $ids;
        $item = $this->purchase_order_item->where($map)->select();
        $this->assign('item', $item);

        //查询合同
        $contract = new \app\admin\model\purchase\Contract;
        $contract_data = $contract->getContractData();
        $this->assign('contract_data', $contract_data);

        $getTabList = ['采购单信息', '质检信息', '物流信息', '付款信息'];
        $this->assign('getTabList', $getTabList);

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function logistics($ids = null)
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
                    $params['is_add_logistics'] = 1;
                    $result = $row->allowField(true)->save($params);

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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    //删除合同里商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->purchase_order_item->destroy($id);
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
            if ($v['purchase_status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }

        $data['purchase_status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
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
        if ($row['purchase_status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['purchase_status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }

    //物流详情
    public function logisticsDetail()
    {
        $id = input('id');
        //采购单供应商物流信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $arr = explode(',', $row['logistics_number']);
        $data = [];
        foreach ($arr as $k => $v) {
            try {
                $param = ['express_id' => trim($v)];
                $data[$k] = Hook::listen('express_query', $param)[0];
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        //采购单退销物流信息
        $purchaseReturn = new \app\admin\model\purchase\PurchaseReturn;
        $res = $purchaseReturn->where('purchase_id', $id)->column('logistics_number');
        $number = implode(',', $res);
        $arr = array_filter(explode(',', $number));
        $return_data = [];
        foreach ($arr as $k => $v) {
            try {
                $param = ['express_id' => trim($v)];
                $return_data[$k] = Hook::listen('express_query', $param)[0];
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $this->assign('id', $id);
        $this->assign('data', $data);
        $this->assign('return_data', $return_data);
        return $this->view->fetch();
    }

    public function test()
    {
        // $orderId = '531349795862802669';
        // $data = Alibaba::getOrderDetail($orderId);

        // waitbuyerpay	等待买家付款	 
        // waitsellersend	等待卖家发货	 
        // waitbuyerreceive 等待买家确认收货	 
        // success 交易成功	 
        // cancel 交易关闭	 
        // paid_but_not_fund 已支付，未到账	 
        // confirm_goods 已收货	 
        // waitsellerconfirm 等待卖家确认订单	 
        // waitbuyerconfirm 等待买家确认订单	 
        // confirm_goods_but_not_fund 已收货，未到账	 
        // confirm_goods_and_has_subsidy 已收货，已贴现	 
        // send_goods_but_not_fund 已发货，未到账	 
        // waitlogisticstakein 等待物流揽件	 
        // waitbuyersign 等待买家签收	 
        // signinsuccess 买家已签收	 
        // signinfailed 签收失败	 
        // waitselleract 等待卖家操作	 
        // waitbuyerconfirmaction 等待买家确认操作	 
        // waitsellerpush 等待卖家推进	


        //根据不同的状态取订单数据
        $success_data = Alibaba::getOrderList(1);

        //转为数组
        $success_data = collection($success_data)->toArray();
        set_time_limit(0);
        $data = [];
        for ($i = 1; $i <= round($success_data['totalRecord'] / 50); $i++) {
            //根据不同的状态取订单数据
            $data[] = Alibaba::getOrderList($i)->result;
        }
        //设置缓存
        cache('success_data', $data, 86400);
        dump($data);
        die;

        foreach ($success_data['result'] as $k => $v) {

            $list = [];
            $map['purchase_number'] = $v->baseInfo->idOfStr;
            $res = $this->model->where($map)->find();
            if ($res !== false) {
                $list['online_status'] = $v->baseInfo->status;
                $result = $this->model->allowField(true)->save($list, ['id' => $res['id']]);
            } else {

                $list['purchase_number'] = $v->baseInfo->idOfStr;
                $list['create_person'] = $v->baseInfo->buyerContact->name;
                $jsonDate = $v->baseInfo->createTime;
                preg_match('/\d{14}/', $jsonDate, $matches);
                $list['createtime'] = date('Y-m-d H:i:s', strtotime($matches[0]));

                $list['product_total'] = ($v->baseInfo->totalAmount) * 1 - ($v->baseInfo->shippingFee) * 1;
                $list['purchase_freight'] = $v->baseInfo->shippingFee;
                $list['purchase_total'] = $v->baseInfo->totalAmount;
                $jsonDate = $v->baseInfo->allDeliveredTime;
                preg_match('/\d{14}/', $jsonDate, $matches);
                $list['delivery_stime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                $list['delivery_etime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                $list['purchase_status'] = 2;
                $list['delivery_address'] = $v->baseInfo->receiverInfo->toArea;
                $list['online_status'] = $v->baseInfo->status;

                $result = $this->model->allowField(true)->create($list);

                $params = [];
                foreach ($v->productItems as  $key => $val) {
                    //添加商品数据
                    $params[$key]['purchase_id'] = $result->id;
                    $params[$key]['purchase_order_number'] = $v->baseInfo->idOfStr;
                    $params[$key]['product_name'] = $val->name;
                    $params[$key]['supplier_sku'] = @$val->cargoNumber;
                    $params[$key]['purchase_num'] = $val->quantity;
                    $params[$key]['purchase_price'] = $val->price;
                    $params[$key]['purchase_total'] = $val->itemAmount;
                }
                $this->purchase_order_item->allowField(true)->saveAll($params);
            }
        }
        
        $success_data = Alibaba::getOrderList(2);
        dump($success_data);
        die;




        die;
    }


    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $offset = input('offset');
            $page = ($offset + 50) / 50;

            $params = [
                'createStartTime' => date('YmdHis', time() - 7200) . '000+0800',
                'createEndTime' => date('YmdHis') . '000+0800',
            ];
            //根据不同的状态取订单数据
            $success_data = cache('success_data_' . $page . '_' . md5(serialize($params)));
            dump($success_data);
            die;
            if (!$success_data) {
                $success_data = Alibaba::getOrderList($page, $params);
                //转为数组
                $success_data = collection($success_data)->toArray();

                //设置缓存
                cache('success_data_' . $page . '_' . md5(serialize($params)), $success_data, 86400);
            }

            foreach ($success_data['result'] as $k => $v) {

                $list = [];
                $map['purchase_number'] = $v->baseInfo->idOfStr;
                $res = $this->model->where($map)->find();
                if ($res) {
                    $list['online_status'] = $v->baseInfo->status;
                    $result = $this->model->allowField(true)->save($list, ['id' => $res['id']]);
                } else {
                    $list['purchase_number'] = $v->baseInfo->idOfStr;
                    $list['create_person'] = $v->baseInfo->buyerContact->name;
                    $jsonDate = $v->baseInfo->createTime;
                    preg_match('/\d{14}/', $jsonDate, $matches);
                    $list['createtime'] = date('Y-m-d H:i:s', strtotime($matches[0]));

                    $list['product_total'] = ($v->baseInfo->totalAmount) * 1 - ($v->baseInfo->shippingFee) * 1;
                    $list['purchase_freight'] = $v->baseInfo->shippingFee;
                    $list['purchase_total'] = $v->baseInfo->totalAmount;
                    $jsonDate = @$v->baseInfo->allDeliveredTime;
                    if ($jsonDate) {
                        preg_match('/\d{14}/', $jsonDate, $matches);
                        $list['delivery_stime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                        $list['delivery_etime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    $list['purchase_status'] = 2;
                    $list['delivery_address'] = $v->baseInfo->receiverInfo->toArea;
                    $list['online_status'] = $v->baseInfo->status;
                    $result = $this->model->allowField(true)->create($list);

                    $params = [];
                    foreach ($v->productItems as  $key => $val) {
                        //添加商品数据
                        $params[$key]['purchase_id'] = $result->id;
                        $params[$key]['purchase_order_number'] = $v->baseInfo->idOfStr;
                        $params[$key]['product_name'] = $val->name;
                        $params[$key]['supplier_sku'] = @$val->cargoNumber;
                        $params[$key]['purchase_num'] = $val->quantity;
                        $params[$key]['purchase_price'] = $val->price;
                        $params[$key]['purchase_total'] = $val->itemAmount;
                    }
                    $this->purchase_order_item->allowField(true)->saveAll($params);
                }

                $data[$k]['id'] = $v->baseInfo->idOfStr;
                $data[$k]['purchase_number'] = $v->baseInfo->idOfStr;
                $data[$k]['purchase_name'] = $v->baseInfo->idOfStr;
                $data[$k]['product_total'] = $v->baseInfo->idOfStr;
                $data[$k]['purchase_freight'] = $v->baseInfo->idOfStr;
                $data[$k]['purchase_total'] = $v->baseInfo->idOfStr;
                $data[$k]['purchase_status'] = 1;
                $data[$k]['payment_status'] = 1;
                $data[$k]['check_status'] = 1;
                $data[$k]['stock_status'] = 1;
                $data[$k]['return_status'] = 1;
                $data[$k]['is_add_logistics'] = 1;
                $data[$k]['is_new_product'] = 1;
                $data[$k]['create_person'] = $v->baseInfo->idOfStr;
                $data[$k]['createtime'] = $v->baseInfo->idOfStr;
            }
            $result = array("total" => $success_data['totalRecord'], "rows" => $data);

            return json($result);
        }
        return $this->view->fetch();
    }
}
