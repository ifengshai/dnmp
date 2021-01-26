<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use think\Db;


class FinanceOrder extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->finance_cost = new \app\admin\model\finance\FinanceCost();
    }
    public function index()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $model = Db::connect('database.db_delivery');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq',9]])
                ->where(['bill_type' => ['neq',11]])
                ->order($sort, $order)
                ->group('order_number')
                ->count();

            $list = $this->finance_cost
                ->where($where)
                ->where(['bill_type' => ['neq',9]])
                ->where(['bill_type' => ['neq',11]])
                ->order($sort, $order)
                ->group('order_number')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key => $value) {
                //查询成本
                $list_z = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 1,'type' => 2])
                ->select();
                $list_j = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 2,'type' => 2])
                ->select();
                $list_z_frame = array_sum(array_column($list_z, 'frame_cost'));
                $list_z_lens = array_sum(array_column($list_z, 'lens_cost'));
                $list_j_frame = array_sum(array_column($list_j, 'frame_cost'));
                $list_j_lens = array_sum(array_column($list_j, 'lens_cost'));
                $list[$key]['frame_cost'] = $list_z_frame-$list_j_frame;
                $list[$key]['lens_cost'] = $list_z_lens-$list_j_lens;
                //查询收入
                $list_zs = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 1,'type' => 1])
                ->select();
                $list_js = $this->finance_cost
                ->where(['order_number' => $value['order_number'],'action_type' => 2,'type' => 1])
                ->select();
                $list_zs_income_amount = array_sum(array_column($list_zs, 'income_amount'));
                $list_js_income_amount = array_sum(array_column($list_js, 'income_amount'));
                $list[$key]['income_amount'] = $list_zs_income_amount-$list_js_income_amount;
                //物流成本
                $list[$key]['fi_actual_payment_fee'] = $model->table('ld_delivery_order_finance')->where(['increment_id' => $value['order_number']])->value('fi_actual_payment_fee');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
    }
}
