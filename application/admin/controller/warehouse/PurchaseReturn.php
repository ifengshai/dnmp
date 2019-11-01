<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 退销单管理
 *
 * @icon fa fa-circle-o
 */
class PurchaseReturn extends Backend
{

    /**
     * PurchaseReturn模型对象
     * @var \app\admin\model\warehouse\PurchaseReturn
     */
    protected $model = null;

    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\PurchaseReturn;
        $this->purchase_return_item = new \app\admin\model\purchase\PurchaseReturnItem;
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
                ->with(['purchaseOrder', 'supplier'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['purchaseOrder', 'supplier'])
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
     * 详情
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseReturnData([1, 2], []);
        $this->assign('purchase_data', $purchase_data);


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = $ids;
        $return_arr = $this->purchase_return_item->where($return_item_map)->column('return_num,id', 'sku');

        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $row['purchase_id'];
        $map['sku'] = ['in', array_keys($return_arr)];
        $item = $purchase_item->where($map)->select();

        //查询质检信息
        $check_map['purchase_id'] = $row['purchase_id'];
        $check_map['type'] = 1;
        $check = new \app\admin\model\warehouse\Check;
        $list = $check->hasWhere('checkItem', ['sku' => ['in', array_keys($return_arr)]])
            ->where($check_map)
            ->field('sku,sum(arrivals_num) as arrivals_num,sum(quantity_num) as quantity_num,sum(unqualified_num) as unqualified_num')
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        //重组数组
        $check_item = [];
        foreach ($list as $k => $v) {
            $check_item[$v['sku']]['arrivals_num'] = $v['arrivals_num'];
            $check_item[$v['sku']]['quantity_num'] = $v['quantity_num'];
            $check_item[$v['sku']]['unqualified_num'] = $v['unqualified_num'];
        }

        //查询已退数量
        $return_map['purchase_id'] = $row['purchase_id'];
        $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', array_keys($return_arr)]])
            ->where($return_map)
            ->group('sku')
            ->column('sum(return_num) as return_all_num', 'sku');

        foreach ($item as $k => $v) {
            $item[$k]['arrivals_num'] = $check_item[$v['sku']]['arrivals_num'];
            $item[$k]['quantity_num'] = $check_item[$v['sku']]['quantity_num'];
            $item[$k]['unqualified_num'] = $check_item[$v['sku']]['unqualified_num'];
            $item[$k]['return_all_num'] = @$return_item[$v['sku']] ? @$return_item[$v['sku']] : 0;
            $item[$k]['return_num'] = $return_arr[$v['sku']]['return_num'];
            $item[$k]['item_id'] = $return_arr[$v['sku']]['id'];
        }

        /***********end***************/
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //添加物流单号
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
                    $params['status'] = 2;
                    $result = $row->allowField(true)->save($params);

                    if ($params['logistics_number']) {
                        //添加物流汇总表
                        $logistics = new \app\admin\model\LogisticsInfo();
                        $list['logistics_number'] = $params['logistics_number'];
                        $list['type'] = 2;
                        $list['order_number'] = $row['return_number'];
                        $logistics->addLogisticsInfo($list);
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
