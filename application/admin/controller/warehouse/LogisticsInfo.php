<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;

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
    protected $noNeedRight = ['signin', 'batch_signin'];

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

            $purchase = new \app\admin\model\purchase\PurchaseOrder();
            foreach ($list as $k => $v) {
                if ($v['purchase_id']) {
                    $res = $purchase->where(['id' => $v['purchase_id']])->field('purchase_name,is_new_product')->find();
                    $list[$k]['purchase_name'] = $res->purchase_name;
                    $list[$k]['is_new_product'] = $res->is_new_product;
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
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
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if (!$ids) {
            $this->error('缺少参数！！');
        }

        if ($this->request->isAjax()) {
            $res = $this->model->save(['status' => 1], ['id' => $ids]);
            if (false !== $res) {
                //签收成功时更改采购单签收状态
                $count = $this->model->where(['purchase_id' => $row['purchase_id'], 'status' => 0])->count();
                if ($count > 0) {
                    $data['purchase_status'] = 9;
                } else {
                    $data['purchase_status'] = 7;
                }
                $data['receiving_time'] = date('Y-m-d H:i:s');
                $this->purchase->save($data, ['id' => $row['purchase_id']]);

                //签收扣减在途库存
                $batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
                $item = new \app\admin\model\itemmanage\Item();
                if ($row['batch_id']) {
                    $list = $batch_item->where(['purchase_batch_id' => $row['batch_id']])->select();
                    foreach ($list as $v) {
                        $item->where(['sku' => $v['sku']])->setDec('on_way_stock', $v['arrival_num']);
                    }
                } else {
                    if ($row['purchase_id']) {
                        $list = $this->purchase_item->where(['purchase_id' => $row['purchase_id']])->select();
                        foreach ($list as $v) {
                            $item->where(['sku' => $v['sku']])->setDec('on_way_stock', $v['purchase_num']);
                        }
                    }
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
        $ids = input('ids/a');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        if ($this->request->isAjax()) {
            $res = $this->model->save(['status' => 1], ['id' => ['in', $ids]]);
            if (false !== $res) {

                $row = $this->model->where(['id' => ['in', $ids]])->select();
                foreach ($row as $k => $v) {
                    //签收成功时更改采购单签收状态
                    $count = $this->model->where(['purchase_id' => $v['purchase_id'], 'status' => 0])->count();
                    if ($count > 0) {
                        $data['purchase_status'] = 9;
                    } else {
                        $data['purchase_status'] = 7;
                    }
                    $data['arrival_time'] = date('Y-m-d H:i:s');
                    $this->purchase->save($data, ['id' => $v['purchase_id']]);

                    //签收扣减在途库存
                    $batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
                    $item = new \app\admin\model\itemmanage\Item();
                    if ($v['batch_id']) {
                        $list = $batch_item->where(['purchase_batch_id' => $v['batch_id']])->select();
                        foreach ($list as $val) {
                            $item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['arrival_num']);
                        }
                    } else {
                        if ($v['purchase_id']) {
                            $list = $this->purchase_item->where(['purchase_id' => $v['purchase_id']])->select();
                            foreach ($list as $val) {
                                $item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['purchase_num']);
                            }
                        }
                    }
                }



                $this->success('签收成功');
            } else {
                $this->error('签收失败');
            }
        }
    }
}
