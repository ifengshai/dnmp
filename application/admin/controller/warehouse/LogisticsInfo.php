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
                $data['arrival_time'] = date('Y-m-d H:i:s');
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
}
