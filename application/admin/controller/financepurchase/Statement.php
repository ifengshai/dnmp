<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\purchase\PurchaseOrder;
use app\api\controller\Ding;
use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Request;

class Statement extends Backend
{
    protected $noNeedRight = [];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\financepurchase\Statement();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
    }

    /**
     * 结算单列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/15
     * Time: 11:11:07
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

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k=>$v){
                $list[$k]['supplier_name'] = $this->supplier->where('id',$v['supplier_id'])->value('supplier_name');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加采购付款生清单
     * 有两个入口一个从采购列表过来 一个从当前页面添加
     * 当前页面添加需要手动输入采购单号
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:03:09
     */
    public function add($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $reason = $this->request->post("reason/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                // dump($params);die;
                Db::startTrans();
                try {
                    //校验是否存在未完成的付款申请单
                    if ($params['pay_type'] == 1 || $params['pay_type'] == 2){
                        $finance_pirchase = $this->model->where('purchase_id',$params['purchase_id'])->where('status','in',[0,1,2])->find();
                    }else{
                        $finance_pirchase = $this->model->where('order_number',$params['order_number'])->where('status','in',[0,1,2])->find();
                    }
                    if (!empty($finance_pirchase)){
                        $this->error('当前单号存在未完成的付款申请单，请检查后重试');
                    }
                    $insert['order_number'] = $params['order_number'];
                    $insert['pay_type'] = $params['pay_type'];
                    $insert['pay_rate'] = $params['pay_rate'];
                    switch ($insert['pay_type']) {
                        case 1:
                            $pay_type = '预付款';
                            break;
                        case 2:
                            $pay_type = '全款预付';
                            break;
                        case 3:
                            $pay_type = '尾款';
                            break;
                    }
                    $insert['status'] = $params['status'];
                    $insert['purchase_id'] = $params['purchase_id'];
                    $insert['supplier_id'] = $params['supplier_id'];
                    $insert['order_number'] = $params['order_number'];
                    $insert['pay_grand_total'] = $params['pay_grand_total'];
                    $insert['base_currency_code'] = $params['base_currency_code'];
                    $insert['create_time'] = time();
                    $insert['create_person'] = session('admin.nickname');
                    //采购单信息
                    $purchase_order = $this->purchase_order->where('id',$insert['purchase_id'])->find();
                    //提交审核 需要创建钉钉审批单
                    if ($insert['status'] == 1) {
                        $initiate_approval = new Ding();
                        //当前用户信息
                        $admin = Db::name('admin')->where('id',session('admin.id'))->find();
                        // $arr['originator_user_id'] = $admin['userid'];
                        // $arr['dept_id'] = $admin['department_id'];
                        // //任萍 王涛 王剑
                        // $arr['approvers'] = '1007304767660594,0221135665945008,0647044715938022';
                        // //抄送 屈金金
                        // $arr['cc_list'] = '204112301323897192';
                        $arr['originator_user_id'] = '071829462027950349';
                        $arr['dept_id'] = '143678442';
                        $arr['approvers'] = '285501046927507550,0550643549844645,056737345633028055';
                        $arr['cc_list'] = '071829462027950349';

                        $arr['form_component_values'] = [
                            ['name' => '采购方式', 'value' =>$purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购'],
                            ['name' => '采购产品类型', 'value' => '镜框'],
                            ['name' => '付款类型', 'value' => $pay_type],
                            ['name' => '供应商名称', 'value' => $params['supplier_name']],
                            ['name' => '币种', 'value' => $params['base_currency_code']],
                            ['name' => '付款比例', 'value' => $params['pay_rate'] * 100 .'%'],
                            ['name' => '采购事由', 'value' => [
                                [
                                    ['name' => '采购单号', 'value' => $params['purchase_number']],
                                    ['name' => '采购品名', 'value' => '镜架'],
                                    ['name' => '数量', 'value' => $reason['num']],
                                    ['name' => '金额（元）', 'value' => $reason['money']]
                                ]
                            ]],
                            ['name' => '付款总金额', 'value' => $params['pay_grand_total']],
                            ['name' => '收款方名称', 'value' => $params['linkname']],
                            ['name' => '收款方账户', 'value' => $params['bank_account']],
                            ['name' => '收款方开户行', 'value' => $params['opening_bank_address']],
                        ];
                        // dump($arr);die;
                        // $res = $initiate_approval->initiate_approval($arr);
                        if ($res['errcode'] != 0) {
                            throw new Exception('发起审批失败');
                        }
                    }
                    $insert['process_instance_id'] = $res['process_instance_id'];
                    // dump($insert);die;
                    Db::name('finance_purchase')->insertGetId($insert);
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
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            $this->success('添加成功！！', url('PurchasePay/index'));
        }
        //生成结算单号
        $order_number = 'JS' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('order_number', $order_number);
        return $this->view->fetch();
    }

    /**
     * 编辑采购付款申请单
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:04:17
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $update['status'] = $params['status'];
                    $update['pay_type'] = $params['pay_type'];
                    $update['pay_rate'] = $params['pay_rate'];
                    $update['pay_grand_total'] = $params['pay_grand_total'];
                    $result = Db::name('finance_purchase')->where('order_number',$params['order_number'])->update($update);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $purchase_order = $this->purchase_order->where('id', $row['purchase_id'])->find();
        $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
        $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
        //查询采购单对应的供应商信息
        $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
        switch ($data['period']) {
            case 1:
                $data['period'] = '1个月';
                break;
            case 2:
                $data['period'] = '2个月';
                break;
            case 3:
                $data['period'] = '3个月';
                break;
        }
        switch ($data['currency']) {
            case 1:
                $data['currency'] = '人民币';
                break;
            case 2:
                $data['currency'] = '美元';
                break;
        }
        $this->assign('purchase_order', $purchase_order);
        $this->assign('purchase_detail', $puchase_detail);
        $this->assign('order_number', $row['order_number']);
        $this->assign('supplier', $data);
        $this->assign('row', $row);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 采购付款申请单详情
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 19:04:37
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $purchase_order = $this->purchase_order->where('id', $row['purchase_id'])->find();
        $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
        $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
        //查询采购单对应的供应商信息
        $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
        switch ($data['period']) {
            case 1:
                $data['period'] = '1个月';
                break;
            case 2:
                $data['period'] = '2个月';
                break;
            case 3:
                $data['period'] = '3个月';
                break;
        }
        switch ($data['currency']) {
            case 1:
                $data['currency'] = '人民币';
                break;
            case 2:
                $data['currency'] = '美元';
                break;
        }
        $this->assign('purchase_order', $purchase_order);
        $this->assign('purchase_detail', $puchase_detail);
        $this->assign('order_number', $row['order_number']);
        $this->assign('supplier', $data);
        $this->assign('row', $row);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 添加页面获取采购单 供应商各种信息
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/14
     * Time: 17:24:43
     */
    public function getPurchaseDetail()
    {
        //采购单页面过来的创建付款申请单
        $ids = input('purchase_number');
        $pay_type = input('pay_type');
        //选择尾款付款类型 关联结算单
        if ($pay_type == 3) {

        } else {//选择预付款或者全款预付 关联采购单
            $purchase_order = $this->purchase_order->where('purchase_number', $ids)->find();
            if (!$purchase_order) {
                $this->error('请输入正确的采购单号！！');
            }
            $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
            $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
            //查询采购单对应的供应商信息
            $data = $this->supplier->where('id', $purchase_order['supplier_id'])->find();
            switch ($data['period']) {
                case 1:
                    $data['period'] = '1个月';
                    break;
                case 2:
                    $data['period'] = '2个月';
                    break;
                case 3:
                    $data['period'] = '3个月';
                    break;
            }
            switch ($data['currency']) {
                case 1:
                    $data['currency'] = '人民币';
                    break;
                case 2:
                    $data['currency'] = '美元';
                    break;
            }
            $data1['purchase_order'] = $purchase_order;
            $data1['purchase_detail'] = $puchase_detail;
            $data1['data'] = $data;
        }
        $this->success('', '', $data1);
    }
}
