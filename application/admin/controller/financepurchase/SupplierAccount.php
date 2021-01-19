<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\purchase\PurchaseOrder;
use app\admin\model\warehouse\Instock;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class SupplierAccount extends Backend
{
    protected $noNeedRight = [];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
    }
    /**
     * 供应商结算列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/15
     * Time: 13:49:14
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
            $filter = json_decode($this->request->get('filter'), true);
            $map = [];
            $map['status'] = ['=',1];

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->supplier
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k=>$v){
                $list[$k]['now_wait_total'] = 1;
                $list[$k]['all_wait_total'] = 1;
                $list[$k]['statement_status'] = 1;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 某个供应商的本期待结算金额 总待结算金额 待结算明细
     * @params $suppiler_id 供应商id
     * @return $data['now_wait_statement'] 本期待结算金额
     * @return $data['all_wait_statement'] 全部待结算金额
     * @return $data['wait_statement_detail'] 待结算明细
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/18
     * Time: 13:44:10
     */
    public function supplier_wait_statement($supplier_id = null)
    {
        $instock = new Instock();
        $supplier_id = 22;
        //供应商详细信息
        $supplier = Db::name('supplier')->where('id',$supplier_id)->field('period,currency')->find();
        $all_purchase_order =$instock
            ->alias('a')
            ->join('check_order b','a.check_id = b.id','left')
            ->where('b.supplier_id',$supplier_id)
            ->where('a.status',2)//已审核通过的入库单
            ->field('a.id,a.in_stock_number,b.check_order_number,b.purchase_id,b.batch_id')
            ->select();
        dump(collection($supplier)->toArray());
        dump(collection($all_purchase_order)->toArray());
        $data['now_wait_statement'] = 1;
        $data['all_wait_statement'] = 1;
        $data['wait_statement_detail'] = 1;
        return $data;
    }
}
