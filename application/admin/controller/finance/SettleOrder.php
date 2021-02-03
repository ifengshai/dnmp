<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;

class SettleOrder extends Backend
{
    public function _initialize()
    {
        $this->statementitem = new \app\admin\model\financepurchase\StatementItem;
        $this->statement = new \app\admin\model\financepurchase\Statement;
        $this->supplier = new \app\admin\model\purchase\Supplier;
        return parent::_initialize();
    }
    /*
    * 结算单列表
    * */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            $map['wait_statement_total'] = ['<', 0];
            $map['status'] = 4;
            if ($filter['supplier_name']) {
                 //供应商名称
                $supply_id = Db::name('supplier')->where('supplier_name',$filter['supplier_name'])->value('id');
                $map['supplier_id'] = $supply_id ? $supply_id : 0;
            }
            if ($filter['purchase_person']) {
                //采购负责人
                $supply_id = Db::name('supplier')->where('purchase_person',$filter['purchase_person'])->value('id');
                $map['supplier_id'] = $supply_id ? $supply_id : 0;
            }
            unset($filter['supplier_name']);
            unset($filter['purchase_person']);
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->statement
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->statement
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('id,statement_number,supplier_id,wait_statement_total,account_statement,status,pay_type')
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k=>$v){
                $supply = $this->supplier->where('id',$v['supplier_id'])->field('supplier_name,purchase_person')->find();
                $list[$k]['supplier_name'] = $supply['supplier_name'];
                $list[$k]['purchase_person'] = $supply['purchase_person'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /*
     * 详情
     * */
    public function detail($ids = null)
    {
        $ids = input('ids');
        if (!$ids) {
            $this->error(__('No Results were found'));
        }
        //主表数据
        $statement = $this->statement->where('id',$ids)->find();
        $supply = $this->supplier->where('id',$statement['supplier_id'])->field('supplier_name,recipient_name,opening_bank,bank_account,currency,period')->find();
        $items = $this->statementitem->where('statement_id',$ids)->select();
        $this->view->assign(compact('statement', 'supply', 'items'));
        return $this->view->fetch();
    }
    /*
     * 财务确认
     * */
    public function confirm(){
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->statement->where($map)->update(['status'=>6]);
        if ($row !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }
}
