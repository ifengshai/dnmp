<?php

namespace app\admin\controller\financepurchase;

use app\admin\model\financepurchase\FinancePurchase;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemCategory;
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

class PurchasePay extends Backend
{
    protected $noNeedRight = [];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FinancePurchase();
        $this->purchase_order = new PurchaseOrder();
        $this->supplier = new \app\admin\model\purchase\Supplier;
    }

    /**
     * 采购付款申请单列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/13
     * Time: 14:03:39
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
            $map = [];
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['supplier_name']){
                $supplier = Db::name('supplier')->where('supplier_name','like','%' . trim($filter['supplier_name']) . '%')->value('id');
                $map['supplier_id'] = ['=',$supplier];
                unset($filter['supplier_name']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
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
            foreach ($list as $k => $v) {
                $list[$k]['supplier_name'] = $this->supplier->where('id', $v['supplier_id'])->value('supplier_name');
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
            // dump($params);
            // dump($reason);
            // die;
            if ($params) {
                $params = $this->preExcludeFields($params);
                Db::startTrans();
                try {
                    //校验是否存在未完成的付款申请单
                    if ($params['pay_type'] == 1 || $params['pay_type'] == 2) {
                        $finance_pirchase = $this->model->where('purchase_id', $params['purchase_id'])->where('status', 'in', [0, 1, 2])->find();
                    } else {
                        $finance_pirchase = $this->model->where('order_number', $params['order_number'])->where('status', 'in', [0, 1, 2])->find();
                    }
                    if (!empty($finance_pirchase)) {
                        $this->error('当前单号存在未完成的付款申请单，请检查后重试');
                    }

                    $insert['order_number'] = $params['order_number'];
                    $insert['pay_type'] = $params['pay_type'];
                    switch ($insert['pay_type']) {
                        case 1:
                            $pay_type = '预付款';
                            $insert['pay_rate'] = 0.3;
                            break;
                        case 2:
                            $pay_type = '全款';
                            $insert['pay_rate'] = 1;
                            break;
                        case 3:
                            $pay_type = '尾款';
                            $insert['pay_rate'] = 1;
                            break;
                        default:
                            $pay_type = '其他';
                    }
                    $insert['status'] = $params['status'];
                    $insert['remark'] = $params['remark'];
                    $insert['purchase_id'] = $params['purchase_id'];
                    $insert['supplier_id'] = $params['supplier_id'];
                    $insert['order_number'] = $params['order_number'];
                    $insert['pay_grand_total'] = $params['pay_grand_total'];
                    $insert['base_currency_code'] = $params['base_currency_code'];
                    $insert['create_time'] = time();
                    $insert['create_person'] = session('admin.nickname');
                    switch ($params['base_currency_code']) {
                        case 'CNY':
                            $currency = '人民币';
                            break;
                        case 'USD':
                            $currency = '美元';
                            break;
                        default:
                            $currency = '人民币';
                    }
                    //采购单信息
                    $purchase_order = $this->purchase_order->where('id', $insert['purchase_id'])->find();
                    //提交审核 需要创建钉钉审批单
                    if ($insert['status'] == 1) {
                        $initiate_approval = new Ding();
                        //当前用户信息
                        $admin = Db::name('admin')->where('id', session('admin.id'))->find();
                        // $arr['originator_user_id'] = $admin['userid'];
                        // $arr['dept_id'] = $admin['department_id'];
                        // //任萍 王涛 王剑
                        // $arr['approvers'] = '1007304767660594,0221135665945008,0647044715938022';
                        // //抄送 屈金金
                        // $arr['cc_list'] = '204112301323897192';
                        $arr['originator_user_id'] = '071829462027950349';
                        $arr['dept_id'] = '143678442';
                        // $arr['approvers'] = '285501046927507550,0550643549844645,056737345633028055';
                        //江昊辉 樊志刚 张靖威
                        // $arr['approvers'] = '285501046927507550,066842141526868909,095453484824626315';
                        //王重阳 江昊辉 张靖威
                        // $arr['approvers'] = '011240312429620945,285501046927507550,095453484824626315';
                        //刘超 红亚 玉晓
                        $arr['approvers'] = '0704513051687725,310818292339015332,111525355037914674';
                        $arr['cc_list'] = '071829462027950349';
                        // $arr['cc_list'] = '285501046927507550';
                        //结算单创建采购付款申请单
                        if ($params['pay_type'] == 3) {
                            foreach ($reason as $kk => $vv) {
                                if (is_array($vv)){
                                    $item = new Item();
                                    $category_id = $item->where('sku',$vv['name'])->value('category_id');
                                    $type = $this->category($category_id);
                                    $reasons[$kk] = [
                                        ['name' => '采购品名', 'value' => $vv['name']],
                                        ['name' => '采购单号', 'value' => $vv['number']],
                                        ['name' => '采购批次', 'value' =>  $vv['batch'] ? $vv['batch']:0],
                                        ['name' => '商品分类', 'value' => $type],
                                        ['name' => '采购数量', 'value' => $vv['num']],
                                        ['name' => '采购单价', 'value' => $vv['single']],
                                        ['name' => '入库数量', 'value' => $vv['in_number']],
                                        ['name' => '扣款', 'value' => $vv['kou_money']],
                                        ['name' => '金额', 'value' => $vv['money']],
                                        ['name' => '运费', 'value' => $vv['freight']]
                                    ];
                                }
                            }
                            $arr['form_component_values'] = [
                                ['name' => '采购方式', 'value' => $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购'],
                                ['name' => '采购产品类型', 'value' => $type],
                                ['name' => '付款类型', 'value' => $pay_type],
                                ['name' => '供应商名称', 'value' => $params['supplier_name']],
                                ['name' => '币种', 'value' => $currency],
                                ['name' => '付款比例', 'value' => '100%'],
                                ['name' => '采购事由', 'value' =>
                                    $reasons
                                ],
                                ['name' => '付款总金额', 'value' => $params['pay_grand_total']],
                                ['name' => '收款方名称', 'value' => $params['linkname']],
                                ['name' => '收款方账户', 'value' => $params['bank_account']],
                                ['name' => '收款方开户行', 'value' => $params['opening_bank_address']],
                            ];
                        } else {
                            //采购列表创建采购付款申请单 采购事由只会有一条 就是以整个采购单为维度 不考虑批次 无批次 无入库数量 无入库金额
                            // 采购事由总金额是采购单总采购金额 付款总金额是采购单总金额加运费乘以付款比例
                            $item = new Item();
                            //分类拿sku的最上级分类
                            $category_id = $item->where('sku',$reason['name'])->value('category_id');
                            $type = $this->category($category_id);
                            $arr['form_component_values'] = [
                                ['name' => '采购方式', 'value' => $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购'],
                                ['name' => '采购产品类型', 'value' => '镜框'],
                                ['name' => '付款类型', 'value' => $pay_type],
                                ['name' => '供应商名称', 'value' => $params['supplier_name']],
                                ['name' => '币种', 'value' => $currency],
                                ['name' => '付款比例', 'value' => $insert['pay_rate'] * 100 . '%'],
                                ['name' => '采购事由', 'value' => [
                                    [
                                        ['name' => '采购品名', 'value' => $reason['name']],
                                        ['name' => '采购单号', 'value' => $params['purchase_number']],
                                        ['name' => '采购批次', 'value' => '0'],
                                        ['name' => '商品分类', 'value' => $type],
                                        ['name' => '采购数量', 'value' => $reason['num']],
                                        ['name' => '采购单价', 'value' => $reason['single']],
                                        ['name' => '入库数量', 'value' => '0'],
                                        ['name' => '扣款', 'value' =>'0'],
                                        ['name' => '金额', 'value' => $reason['money']],
                                        ['name' => '运费', 'value' => $reason['freight']]
                                    ]
                                ]],
                                ['name' => '付款总金额', 'value' => $params['pay_grand_total']],
                                ['name' => '收款方名称', 'value' => $params['linkname']],
                                ['name' => '收款方账户', 'value' => $params['bank_account']],
                                ['name' => '收款方开户行', 'value' => $params['opening_bank_address']],
                            ];
                        }

                        // dump($arr);die;
                        $res = $initiate_approval->initiate_approval($arr);
                        if ($res['errcode'] != 0 || $res === false) {
                            throw new Exception('发起审批失败'.$res['errmsg']);
                        }
                    }
                    $insert['process_instance_id'] = $res['process_instance_id'];
                    // dump($insert);die;
                    $finance_purchase_id = Db::name('finance_purchase')->insertGetId($insert);
                    $label = input('label');
                    //结算单页面过来的创建付款申请单 需要更新结算的付款申请单id字段
                    if ($label == 'statement') {
                        $ids = input('ids');
                        Db::name('finance_statement')->where('id',$ids)->update(['finance_purcahse_id'=>$finance_purchase_id]);
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
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            $this->success('添加成功！！');
        }
        $label = input('label');
        //采购单页面过来的创建付款申请单
        if ($label == 'purchase') {
            $ids = $ids ? $ids : input('ids');
            $purchase_order = $this->purchase_order->where('id', $ids)->find();
            $purchase_order['purchase_type'] = $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购';
            $this->assign('purchase_order', $purchase_order);
            $puchase_detail = Db::name('purchase_order_item')->where('purchase_id', $purchase_order['id'])->find();
            //获取当前sku的分类
            $item = new Item();
            $category_id = $item->where('sku',$puchase_detail['sku'])->value('category_id');
            if (!empty($category_id)){
                $type = $this->category($category_id);
                $puchase_detail['type'] = $type;
            }
            $this->assign('purchase_detail', $puchase_detail);
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
            switch ($purchase_order['pay_type']) {
                case 1:
                    $all = number_format(($puchase_detail['purchase_total'] + $purchase_order['purchase_freight']) * 0.3,2,".","");
                    break;
                case 2:
                    $all = number_format($puchase_detail['purchase_total'] + $purchase_order['purchase_freight'],2,".","");
                    break;
            }
            $this->assign('supplier', $data);
            $this->assign('all', $all);
            //生成付款申请单编号
            $order_number = 'PR' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $this->assign('order_number', $order_number);
            $this->assignconfig('newdatetime', date('Y-m-d H:i:s'));
            return $this->view->fetch();
        }
        //结算单页面过来的创建付款申请单
        if ($label == 'statement') {
            $ids = $ids ? $ids : input('ids');
            //结算单详情
            $statement = Db::name('finance_statement')->where('id', $ids)->find();
            //供应商详情
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            //结算单对应的所有的 采购单 有批次的采购单会有两条
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $item = new Item();
                $category_id = $item->where('sku',$puchase_details['sku'])->value('category_id');
                $type = $this->category($category_id);
                $puchase_detail[$k]['type'] = $type;
                // dump($type);die;
                $puchase_detail[$k]['sku'] = $puchase_details['sku'];
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];

                if (!empty($v['purchase_batch']) && $v['purchase_batch'] != 1){
                    $puchase_detail[$k]['freight'] = 0.00;
                }
            }
            // dump($puchase_detail);
            $this->assign('statement', $statement);
            $this->assign('purchase_detail', $puchase_detail);
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
            $this->assign('supplier', $data);
            //生成付款申请单编号
            $order_number = 'PR' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $this->assign('order_number', $order_number);
            $this->assign('id', $ids);
            $this->assignconfig('newdatetime', date('Y-m-d H:i:s'));
            return $this->view->fetch('add_statement');
        }

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
            $reason = $this->request->post("reason/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $update['status'] = $params['status'];
                    $update['pay_type'] = $params['pay_type'];
                    $update['remark'] = $params['remark'];
                    $update['pay_grand_total'] = $params['pay_grand_total'];
                    switch ($params['pay_type']) {
                        case 1:
                            $pay_type = '预付款';
                            $update['pay_rate'] = 0.3;
                            break;
                        case 2:
                            $pay_type = '全款';
                            $update['pay_rate'] = 1;
                            break;
                        case 3:
                            $pay_type = '尾款';
                            $update['pay_rate'] = 1;
                            break;
                    }
                    switch ($params['base_currency_code']) {
                        case 'CNY':
                            $currency = '人民币';
                            break;
                        case 'USD':
                            $currency = '美元';
                            break;
                    }
                    //采购单信息
                    $purchase_order = $this->purchase_order->where('id', $params['purchase_id'])->find();
                    //提交审核 需要创建钉钉审批单
                    if ($params['status'] == 1) {
                        $initiate_approval = new Ding();
                        //当前用户信息
                        $admin = Db::name('admin')->where('id', session('admin.id'))->find();
                        // $arr['originator_user_id'] = $admin['userid'];
                        // $arr['dept_id'] = $admin['department_id'];
                        // //任萍 王涛 王剑
                        // $arr['approvers'] = '1007304767660594,0221135665945008,0647044715938022';
                        // //抄送 屈金金
                        // $arr['cc_list'] = '204112301323897192';
                        $arr['originator_user_id'] = '071829462027950349';
                        $arr['dept_id'] = '143678442';
                        // $arr['approvers'] = '285501046927507550,0550643549844645,056737345633028055';
                        //刘超 红亚 玉晓
                        $arr['approvers'] = '0704513051687725,310818292339015332,111525355037914674';
                        $arr['cc_list'] = '071829462027950349';

                        if ($params['pay_type'] == 3) {
                            foreach ($reason as $kk => $vv) {
                                if (is_array($vv)){
                                    $item = new Item();
                                    $category_id = $item->where('sku',$vv['name'])->value('category_id');
                                    $type = $this->category($category_id);
                                    $reasons[$kk] = [
                                        ['name' => '采购品名', 'value' => $vv['name']],
                                        ['name' => '采购单号', 'value' => $vv['number']],
                                        ['name' => '采购批次', 'value' =>  $vv['batch'] ? $vv['batch']:0],
                                        ['name' => '商品分类', 'value' => $type],
                                        ['name' => '采购数量', 'value' => $vv['num']],
                                        ['name' => '采购单价', 'value' => $vv['single']],
                                        ['name' => '入库数量', 'value' => $vv['in_number']],
                                        ['name' => '扣款', 'value' => $vv['kou_money']],
                                        ['name' => '金额', 'value' => $vv['money']],
                                        ['name' => '运费', 'value' => $vv['freight']]
                                    ];
                                }
                            }
                            $arr['form_component_values'] = [
                                ['name' => '采购方式', 'value' => $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购'],
                                ['name' => '采购产品类型', 'value' => $type],
                                ['name' => '付款类型', 'value' => $pay_type],
                                ['name' => '供应商名称', 'value' => $params['supplier_name']],
                                ['name' => '币种', 'value' => $currency],
                                ['name' => '付款比例', 'value' => '100%'],
                                ['name' => '采购事由', 'value' =>
                                    $reasons
                                ],
                                ['name' => '付款总金额', 'value' => $params['pay_grand_total']],
                                ['name' => '收款方名称', 'value' => $params['linkname']],
                                ['name' => '收款方账户', 'value' => $params['bank_account']],
                                ['name' => '收款方开户行', 'value' => $params['opening_bank_address']],
                            ];
                        } else {
                            $item = new Item();
                            $category_id = $item->where('sku',$reason['name'])->value('category_id');
                            $type = $this->category($category_id);
                            $arr['form_component_values'] = [
                                ['name' => '采购方式', 'value' => $purchase_order['purchase_type'] == 1 ? '线下采购' : '线上采购'],
                                ['name' => '采购产品类型', 'value' => '镜框'],
                                ['name' => '付款类型', 'value' => $pay_type],
                                ['name' => '供应商名称', 'value' => $params['supplier_name']],
                                ['name' => '币种', 'value' => $currency],
                                ['name' => '付款比例', 'value' => $update['pay_rate'] * 100 . '%'],
                                ['name' => '采购事由', 'value' => [
                                    [
                                        ['name' => '采购品名', 'value' => $reason['name']],
                                        ['name' => '采购单号', 'value' => $params['purchase_number']],
                                        ['name' => '采购批次', 'value' => '0'],
                                        ['name' => '商品分类', 'value' => $type],
                                        ['name' => '采购数量', 'value' => $reason['num']],
                                        ['name' => '采购单价', 'value' => $reason['single']],
                                        ['name' => '入库数量', 'value' => '0'],
                                        ['name' => '扣款', 'value' =>'0'],
                                        ['name' => '金额', 'value' => $reason['money']],
                                        ['name' => '运费', 'value' => $reason['freight']]
                                    ]
                                ]],
                                ['name' => '付款总金额', 'value' => $params['pay_grand_total']],
                                ['name' => '收款方名称', 'value' => $params['linkname']],
                                ['name' => '收款方账户', 'value' => $params['bank_account']],
                                ['name' => '收款方开户行', 'value' => $params['opening_bank_address']],
                            ];
                        }

                        // dump($arr);die;
                        $res = $initiate_approval->initiate_approval($arr);
                        if ($res['errcode'] != 0 || $res === false) {
                            throw new Exception('发起审批失败'.$res['errmsg']);
                        }
                    }
                    $update['process_instance_id'] = $res['process_instance_id'];
                    $result = Db::name('finance_purchase')->where('order_number', $params['order_number'])->update($update);
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
                    $this->success('添加成功！！');
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if ($row['pay_type'] == 3){
            //结算单对应的付款申请单
            //结算单详情
            $statement = Db::name('finance_statement')->where('id', $row['purchase_id'])->find();
            //供应商详情
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            //结算单对应的所有的 采购单 有批次的采购单会有两条
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $item = new Item();
                $category_id = $item->where('sku',$puchase_details['sku'])->value('category_id');
                $type = $this->category($category_id);
                $puchase_detail[$k]['type'] = $type;
                $puchase_detail[$k]['sku'] = $puchase_details['sku'];
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];
                if ($v['purchase_batch'] !== 1){
                    $puchase_detail[$k]['freight'] = 0.00;
                }
            }
            $this->assign('statement', $statement);
            $this->assign('purchase_detail', $puchase_detail);
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
            $this->assign('supplier', $data);
            $this->assign('id', $ids);
            $this->assign('row', $row);
            $this->assign('order_number', $row['order_number']);
            return $this->view->fetch('edit_statement');
        }else{
            //采购单对应的付款申请单
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
        if ($row['pay_type'] == 3){
            //结算单对应的付款申请单
            //结算单详情
            $statement = Db::name('finance_statement')->where('id', $row['purchase_id'])->find();
            //供应商详情
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            //结算单对应的所有的 采购单 有批次的采购单会有两条
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $item = new Item();
                $category_id = $item->where('sku',$puchase_details['sku'])->value('category_id');
                $type = $this->category($category_id);
                $puchase_detail[$k]['type'] = $type;
                $puchase_detail[$k]['sku'] = $puchase_details['sku'];
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];

                if (!empty($v['purchase_batch']) && $v['purchase_batch'] != 1){
                    $puchase_detail[$k]['freight'] = 0.00;
                }
            }
            $this->assign('statement', $statement);
            $this->assign('purchase_detail', $puchase_detail);
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
            $this->assign('supplier', $data);
            $this->assign('order_number', $row['order_number']);
            return $this->view->fetch('detail_statement');
        }else {
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
        if (strlen($ids) !== 22) {
            $this->error('请输入正确的单号！！');
        }
        //选择尾款付款类型 关联结算单
        if ($pay_type == 3) {
            $statement = Db::name('finance_statement')->where('statement_number', $ids)->find();
            empty($statement) && $this->error('当前结算单号不存在！！');
            $statement['status'] !== 6 && $this->error('当前结算单号未完成 请检查结算单状态！！');
            $statement['purchase_type'] = '';
            $data = $this->supplier->where('id', $statement['supplier_id'])->find();
            $data1['statement'] = $statement;
            $puchase_detail = Db::name('finance_statement_item')->where('statement_id', $statement['id'])->select();
            foreach ($puchase_detail as $k => $v) {
                $puchase_details = Db::name('purchase_order_item')->where('purchase_id', $v['purchase_id'])->find();
                $puchase_detail[$k]['purchase_num'] = $puchase_details['purchase_num'];
                $puchase_detail[$k]['purchase_price'] = $puchase_details['purchase_price'];
            }
            $data1['item'] = $puchase_detail;
            $data1['data'] = $data;
            // dump($data1);die;
        } else {//选择预付款或者全款预付 关联采购单
            $purchase_order = $this->purchase_order->where('purchase_number', $ids)->field('id,purchase_type,purchase_number,purchase_name,purchase_total,supplier_id')->find();
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
            // dump(collection($data1)->toArray());die;
        }
        $this->success('', '', $data1);
    }

    /**
     * 取消
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/1/15
     * Time: 11:14:20
     */
    public function cancel($ids = null)
    {
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->get($ids);
        if ($row['status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res !== false) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }

    //审核
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        $status = $this->request->post("status");
        // dump($ids);
        // dump($status);die;
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        foreach ($row as $v) {
            if ($v['status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }
        Db::startTrans();
        try {
            //更新主表状态
            Db::name('finance_purchase')->where('id', 'in', $ids)->update(['status' => $status]);

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
        $this->success();
    }


    public function category($category_id)
    {
        $item_category = new ItemCategory();
        $sku_category = $item_category->where('id',$category_id)->find();
        if ($sku_category['pid'] !== 0){
            $this->category($sku_category['pid']);
        }else{
            return $sku_category['name'];
        }
    }
}
