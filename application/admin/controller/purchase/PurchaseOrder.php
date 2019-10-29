<?php

namespace app\admin\controller\purchase;

use app\admin\model\LogisticsInfo;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use fast\Http;
use fast\Alibaba;
use app\admin\model\NewProduct;
use app\admin\model\purchase\Supplier;
use app\admin\model\purchase\SupplierSku;
use think\Cache;


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

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = ['getAlibabaPurchaseOrder'];

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
                        foreach (array_filter($sku) as $k => $v) {
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
                    $this->success('添加成功！！',  url('PurchaseOrder/index'));
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

            //提取供应商id
            $supplier = array_unique(array_column($row, 'supplier_id'));
            if (count($supplier) > 1) {
                $this->error(__('必须选择相同的供应商！！', url('admin/new_product/index')));
            }
            $this->assign('row', $row);
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

    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['purchase_status'] != 0) {
                $this->error('此商品状态不能提交审核');
            }
            $map['id'] = $id;
            $data['purchase_status'] = 1;
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
                        foreach (array_filter($sku) as $k => $v) {
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

    /**
     * 录入物流单号
     */
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
                    $params['purchase_status'] = 6; //待收货
                    $result = $row->allowField(true)->save($params);

                    //添加物流汇总表
                    $logistics = new \app\admin\model\LogisticsInfo();
                    $list['logistics_number'] = $params['logistics_number'];
                    $list['type'] = 1;
                    $list['order_number'] = $row['purchase_number'];
                    $list['purchase_id'] = $ids;
                    $logistics->addLogisticsInfo($list);

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


    /**
     * 删除合同里商品信息
     */
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
        if ($res !== false) {
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
        if ($res !== false) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }

    /**
     * 物流详情
     */
    public function logisticsDetail()
    {
        $id = input('id');
        //采购单供应商物流信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $data = [];
        //判断采购单类型是否为线上采购单 1线下采购单=> 快递100api 2线上采购单 1688api
        if ($row['purchase_type'] == 2) {
            $cacheIndex = 'logisticsDetail_' . $row['purchase_number'];
            $data = Cache::get($cacheIndex);
            if (!$data) {
                $data = Alibaba::getLogisticsMsg($row['purchase_number']);
                // 记录缓存, 时效10分钟
                Cache::set($cacheIndex, $data, 3600);
            }
            $data = $data->logisticsTrace[0];
        } else {
            if ($row['logistics_number']) {
                $arr = explode(',', $row['logistics_number']);
                //物流公司编码
                $company = explode(',', $row['logistics_company_no']);
                foreach ($arr as $k => $v) {
                    try {
                        $param['express_id'] = trim($v);
                        $param['code'] = trim($company[$k]);
                        $data[$k] = Hook::listen('express_query', $param)[0];
                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                    }
                }
            }
        }

        //采购单退销物流信息
        $purchaseReturn = new \app\admin\model\purchase\PurchaseReturn;
        $res = $purchaseReturn->where('purchase_id', $id)->column('logistics_number');
        $return_data = [];
        if ($res) {
            $number = implode(',', $res);
            $arr = array_filter(explode(',', $number));
            foreach ($arr as $k => $v) {
                try {
                    $param = ['express_id' => trim($v)];
                    $return_data[$k] = Hook::listen('express_query', $param)[0];
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        }

        $this->assign('id', $id);
        $this->assign('data', $data);
        $this->assign('return_data', $return_data);
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    //质检信息
    public function checkDetail()
    {
        $id = input('id');
        //采购单信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        //查询产品信息
        $map['purchase_id'] = $id;
        $item = $this->purchase_order_item->where($map)->column('*', 'sku');
        $this->assign('item', $item);


        //查询质检信息
        $check_map['purchase_id'] = $id;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->with(['checkItem'])
            ->where($check_map)
            ->select();
        $list = collection($list)->toArray();
        $this->assign('list', $list);
        $this->assign('id', $id);

        //查询入库信息
        $check_id = array_column($list, 'id');
        if ($check_id) {
            $instock_map['check_id'] = ['in', $check_id];
            $Instock = new \app\admin\model\warehouse\Instock;
            $instock_list = $Instock->with(['instockItem'])
                ->where($instock_map)
                ->select();
            $instock_list = collection($instock_list)->toArray();
        }
        $this->assign('instock_list', $instock_list ?? []);

        //查询退销信息
        $PurchaseReturn_map['purchase_id'] = $id;
        $PurchaseReturn = new \app\admin\model\purchase\PurchaseReturn;
        $return_list = $PurchaseReturn->with(['purchaseReturnItem'])
            ->where($PurchaseReturn_map)
            ->select();
        $return_list = collection($return_list)->toArray();
        $this->assign('return_list', $return_list);


        return $this->view->fetch();
    }

    //确认差异
    public function confirmDiff()
    {
        $id = input('id');
        //采购单信息
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        //查询实际采购信息
        $purchase_map['purchase_id'] = $id;
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_list = $purchase->hasWhere('purchaseOrderItem')
            ->where($purchase_map)
            ->field('sku,purchase_num')
            ->column('*', 'sku');


        //查询质检信息
        $check_map['purchase_id'] = $id;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem')
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as arrivals_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        $data = [];
        $check_data = [];
        foreach ($list as $k => $v) {
            //到货数量小于采购数量 更新实际到货数量为采购数量
            if ($v['arrivals_num'] < @$purchase_list[$v['sku']]['purchase_num']) {
                $data[$k]['sku'] = $v['sku'];
                $data[$k]['id'] = @$purchase_list[$v['sku']]['id'];
                $data[$k]['purchase_num'] = $v['arrivals_num'];

                $check_data[$k]['sku'] = $v['sku'];
                $check_data[$k]['purchase_num'] = $v['arrivals_num'];
                $check_data[$k]['check_id'] = $v['id'];
            }
        }
        if ($data) {
            //批量修改
            $this->purchase_order_item->allowField(true)->saveAll($data);

            //更改质检单商品信息采购数量
            foreach ($check_data as $k => $v) {
                $checkItem = new \app\admin\model\warehouse\CheckItem;
                $where['sku'] = $v['sku'];
                $where['check_id'] = $v['check_id'];
                $checkItem->allowField(true)->save(['purchase_num' => $v['purchase_num']], $where);
            }
        }

        //同时判断采购单下所有商品是否全部质检
        $purchase_all_num =  array_sum(array_column($purchase_list, 'purchase_num'));

        //到货数量
        $arrivals_all_num = array_sum(array_column($list, 'arrivals_num'));
        //到货数量 大于或等于采购数量 更改采购状态为全部质检
        if ($arrivals_all_num >= $purchase_all_num) {
            $this->model->allowField(true)->save(['check_status' => 2], ['id' => $id]);
        }
        $this->success();
    }

    /**
     * 批量匹配sku
     */
    public function matching()
    {
        //查询SKU为空的采购单
        $data = $this->purchase_order_item->where('sku', 'exp', 'is null')->select();
        $data = collection($data)->toArray();
        foreach ($data as $k => $v) {
            //匹配SKU
            $params['sku'] = (new SupplierSku())->getSkuData($v['skuid']);
            $this->purchase_order_item->allowField(true)->save($params, ['id' => $v['id']]);
        }
        $this->success();
    }

    /**
     * 定时获取1688采购单 每天9点更新一次
     */
    public function getAlibabaPurchaseOrder()
    {
        //$orderId = '551171682534802669';
        //$data = Alibaba::getOrderDetail($orderId);
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

        //refundStatus = refundsuccess 退款成功

        /**
         * @todo 后面添加采集时间段
         */
        $params = [
            'createStartTime' => date('YmdHis', strtotime("-30 day")) . '000+0800',
            'createEndTime' => date('YmdHis') . '000+0800',
        ];
        //根据不同的状态取订单数据
        $success_data = Alibaba::getOrderList(1, $params);
        set_time_limit(0);
        $data = cache('Crontab_getAlibabaPurchaseOrder_' . date('YmdH') . md5(serialize($params)));
        if (!$data) {
            //根据不同的状态取订单数据
            $success_data = Alibaba::getOrderList(1, $params);
            //转为数组
            if ($success_data) {
                $success_data = collection($success_data)->toArray();
            }

            $data = [];
            for ($i = 1; $i <= round($success_data['totalRecord'] / 50); $i++) {

                //根据不同的状态取订单数据
                $data[$i] = Alibaba::getOrderList($i, $params)->result;
            }

            //设置缓存
            cache('Crontab_getAlibabaPurchaseOrder_' . date('YmdH') . md5(serialize($params)), $data, 3600);
        }
       
        foreach ($data as $key => $val) {
            if (!$val) {
                continue;
            }
            foreach ($val as $k => $v) {
                $list = [];
                $map['purchase_number'] = $v->baseInfo->idOfStr;
                $map['is_del'] = 1;
                //根据采购单号查询采购单是否已存在
                $res = $this->model->where($map)->find();
                //如果采购单已存在 则更新采购单状态
                if ($res) {
                    //待发货
                    if (in_array($v->baseInfo->status, ['waitsellersend', 'waitsellerconfirm', 'waitbuyerconfirm', 'waitselleract', 'waitsellerpush', 'waitbuyerconfirmaction'])) {
                        $list['purchase_status'] = 5;
                    } elseif (in_array($v->baseInfo->status, ['waitbuyerreceive', 'send_goods_but_not_fund', 'waitlogisticstakein', 'waitbuyersign', 'signinfailed'])) {
                        $list['purchase_status'] = 6; //待收货
                    } else {
                        $list['purchase_status'] = 7; //已收货
                        $jsonDate = $v->baseInfo->createTime;
                        preg_match('/\d{14}/', $jsonDate, $matches);
                        $list['receiving_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    //售中退款
                    if (@$v->baseInfo->refundStatus == 'refundsuccess') {
                        $list['purchase_status'] = 8; //已退款
                    }

                    $list['online_status'] = $v->baseInfo->status;

                    //匹配供应商
                    if (!$res['supplier_id']) {
                        $supplier = new Supplier;
                        $list['supplier_id'] = $supplier->getSupplierId($v->baseInfo->sellerContact->companyName);
                    }

                    //更新采购单状态
                    $result = $res->save($list);
                } else {
                    //过滤待付款 和取消状态的订单
                    if (in_array($v->baseInfo->status, ['waitbuyerpay', 'cancel'])) {
                        continue;
                    }

                    $list['purchase_number'] = $v->baseInfo->idOfStr;
                    $list['create_person'] = $v->baseInfo->buyerContact->name;
                    $jsonDate = $v->baseInfo->createTime;
                    preg_match('/\d{14}/', $jsonDate, $matches);
                    $list['createtime'] = date('Y-m-d H:i:s', strtotime($matches[0]));

                    $list['product_total'] = ($v->baseInfo->totalAmount) * 1 - ($v->baseInfo->shippingFee) * 1;
                    $list['purchase_freight'] = $v->baseInfo->shippingFee;
                    $list['purchase_total'] = $v->baseInfo->totalAmount;
                    $list['payment_money'] = $v->baseInfo->totalAmount;
                    $list['payment_status'] = 3;
                    $payTime = @$v->baseInfo->payTime;
                    if ($payTime) {
                        $matches = [];
                        preg_match('/\d{14}/', $payTime, $matches);
                        $list['payment_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    $allDeliveredTime = @$v->baseInfo->allDeliveredTime;
                    if ($allDeliveredTime) {
                        $matches = [];
                        preg_match('/\d{14}/', $allDeliveredTime, $matches);
                        $list['delivery_stime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                        $list['delivery_etime'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }

                    //待发货
                    if (in_array($v->baseInfo->status, ['waitsellersend', 'waitsellerconfirm', 'waitbuyerconfirm', 'waitselleract', 'waitsellerpush', 'waitbuyerconfirmaction'])) {
                        $list['purchase_status'] = 5;
                    } elseif (in_array($v->baseInfo->status, ['waitbuyerreceive', 'send_goods_but_not_fund', 'waitlogisticstakein', 'waitbuyersign', 'signinfailed'])) {
                        $list['purchase_status'] = 6; //待收货
                    } else {
                        $list['purchase_status'] = 7; //已收货
                    }
                    //收货地址
                    $list['delivery_address'] = $v->baseInfo->receiverInfo->toArea;
                    $list['online_status'] = $v->baseInfo->status;
                    $receivingTime = @$v->baseInfo->receivingTime;
                    if ($receivingTime) {
                        $matches = [];
                        preg_match('/\d{14}/', $receivingTime, $matches);
                        $list['receiving_time'] = date('Y-m-d H:i:s', strtotime($matches[0]));
                    }
                    $list['purchase_type'] = 2;

                    //匹配供应商
                    $supplier = new Supplier;
                    $list['supplier_id'] = $supplier->getSupplierId($v->baseInfo->sellerContact->companyName);

                    //添加采购单
                    $result = $this->model->allowField(true)->create($list);

                    $params = [];
                    foreach ($v->productItems as  $key => $val) {
                        //添加商品数据
                        $params[$key]['purchase_id'] = $result->id;
                        $params[$key]['purchase_order_number'] = $v->baseInfo->idOfStr;
                        $params[$key]['product_name'] = $val->name;
                        $params[$key]['supplier_sku'] = @$val->cargoNumber;
                        $params[$key]['purchase_num'] = $val->quantity;
                        $params[$key]['purchase_price'] = $val->itemAmount / $val->quantity;
                        $params[$key]['purchase_total'] = $val->itemAmount;
                        $params[$key]['price'] = $val->price;
                        $params[$key]['discount_money'] = $val->entryDiscount / 100;
                        $params[$key]['skuid'] = $val->skuID;

                        //匹配SKU
                        $params[$key]['sku'] = (new SupplierSku())->getSkuData($val->skuID);
                    }
                    $this->purchase_order_item->allowField(true)->saveAll($params);
                }
            }
        }
        echo 'ok';
    }
}
