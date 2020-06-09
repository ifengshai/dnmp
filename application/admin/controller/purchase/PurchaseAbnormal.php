<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class PurchaseAbnormal extends Backend
{

    /**
     * PurchaseAbnormal模型对象
     * @var \app\admin\model\purchase\PurchaseAbnormal
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['detail'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\PurchaseAbnormal;
        $this->item = new \app\admin\model\purchase\PurchaseAbnormalItem();
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
            //查询供应商
            $supplier = new \app\admin\model\purchase\Supplier();
            $supplier_list = $supplier->where(['id' => ['in', array_column($list, 'supplier_id')]])->column('supplier_name', 'id');

            //查询采购单
            $purchase = new \app\admin\model\purchase\PurchaseOrder();
            $purchase_list = $purchase->where(['id' => ['in', array_column($list, 'purchase_id')]])->column('purchase_number', 'id');

            foreach ($list as $k => $v) {
                $list[$k]['supplier_name'] = $supplier_list[$v['supplier_id']];
                $list[$k]['purchase_number'] = $purchase_list[$v['purchase_id']];
            }

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

        //查询供应商名称
        $supplier = new \app\admin\model\purchase\Supplier();
        $supplier_name = $supplier->where(['id' => $row['supplier_id']])->value('supplier_name');

        //查询采购单单号
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        $purchase_number = $purchase->where(['id' => $row['purchase_id']])->value('purchase_number');

        //查询子表信息
        $map['abnormal_id'] = $ids;
        $item = $this->item->where($map)->select();
        $this->assign('item', $item);
        $this->assign('supplier_name', $supplier_name);
        $this->assign('purchase_number', $purchase_number);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
