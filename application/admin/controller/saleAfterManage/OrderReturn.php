<?php

namespace app\admin\controller\saleaftermanage;
use app\admin\model\infosynergytaskmanage\InfoSynergyTask;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\platformManage\ManagtoPlatform;
use app\admin\model\saleAfterManage\SaleAfterIssue;
use app\admin\model\saleAfterManage\SaleAfterTask;
use app\admin\model\saleAfterManage\OrderReturnItem;
use app\admin\model\saleAfterManage\OrderReturnRemark;
use think\Request;

/**
 * 退货列管理
 *
 * @icon fa fa-circle-o
 */
class OrderReturn extends Backend
{
    
    /**
     * OrderReturn模型对象
     * @var \app\admin\model\saleaftermanage\OrderReturn
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\OrderReturn;

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
                ->with(['saleAfterIssue'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['saleAfterIssue'])
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
                    $params['return_order_number'] = 'WRB'.date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname');
                    $result = $this->model->allowField(true)->save($params);
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
                    $item = $params['item'];
                    foreach ($item as $arr){
                        $data=[];
                        $data['order_return_id'] = $this->model->id;
                        $data['return_sku'] = $arr['item_sku'];
                        $data['return_sku_name'] = $arr['item_name'];
                        $data['sku_qty'] = $arr['sku_qty'];
                        $data['return_sku_qty'] = $arr['return_sku_qty'];
                        $data['arrived_sku_qty'] = $arr['arrived_sku_qty'];
                        $data['check_sku_qty']   = $arr['check_sku_qty'];
                        $data['create_time']     = date("Y-m-d H:i:s",time());
                        (new OrderReturnItem())->allowField(true)->save($data);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
        //dump((new SaleAfterIssue())->getIssueList(2));
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(2));
        return $this->view->fetch();
    }

    /***
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
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
            $tid = $params['id'];
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
                    if($params['item']){
                        $item = $params['item'];
                        foreach ($item as  $arr){
                            $data = [];
                            $data['return_sku'] = $arr['item_sku'];
                            $data['return_sku_name'] = $arr['item_name'];
                            $data['sku_qty'] = $arr['sku_qty'];
                            $data['return_sku_qty'] = $arr['return_sku_qty'];
                            $data['arrived_sku_qty'] = $arr['arrived_sku_qty'];
                            $data['check_sku_qty']   = $arr['check_sku_qty'];
                            (new OrderReturnItem())->where('order_return_id','=',$tid)->update($data);
                        }
                    }
                    if($params['task_remark']){
                        $dataRemark = [];
                        $dataRemark['order_return_id'] = $tid;
                        $dataRemark['remark_record'] = strip_tags($params['task_remark']);
                        $dataRemark['create_person'] = session('admin.nickname');
                        $dataRemark['create_time']   = date("Y-m-d H:i:s",time());
                        (new OrderReturnRemark())->allowField(true)->save($dataRemark);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->view->assign('orderReturnRemark',(new OrderReturnRemark())->getOrderReturnRemark($row['id']));
        $this->view->assign("orderReturnItem",(new OrderReturnItem())->getOrderReturnItem($row['id']));
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(2));
        return $this->view->fetch();
    }
    public function getAjaxOrderPlatformList()
    {
        if($this->request->isAjax()){
            $json = (new ManagtoPlatform())->getOrderPlatformList();
            if(!$json){
                $json = [0=>'请添加订单平台'];
            }
            $arrToObject = (object)($json);
            return json($arrToObject);
        }else{
            $arr=[
                1,2,3
            ];
            return $arr;
            $json = json_encode($arr);
            return $this->success('ok','',$json);
        }
    }
    /**
     *退货单详情信息
     */
    public function detail(Request $request)
    {
        $id = $request->param('ids');
        if(!$id){
            $this->error('参数错误，请重新尝试','/admin/saleaftermanage/order_return/index');
        }
        $row = $this->model->get($id);
        if(!$row){
            $this->error('退货信息不存在，请重新尝试','/admin/saleaftermanage/order_return/index');
        }
        $this->view->assign("row", $row);
        $this->view->assign('orderReturnRemark',(new OrderReturnRemark())->getOrderReturnRemark($row['id']));
        $this->view->assign("orderReturnItem",(new OrderReturnItem())->getOrderReturnItem($row['id']));
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(2));
        return $this->view->fetch();
    }
    /***
     * 新建退款
     */
    public function refund()
    {

    }
    /***
     * 新建退货单审核不通过
     */
    public function checkNoPass()
    {

    }
    /***
     * 新建订单检索功能
     */
    public function search(Request $request)
    {
        if($request->isPost()){
            //获取输入的订单号
            $increment_id = $request->post('increment_id');
            //获取输入的平台
            $order_platform = $request->post('order_platform');
            //获取客户邮箱地址
            $customer_email = $request->post('customer_email');
            //获取客户姓名
            $customer_name  = $request->post('customer_name');
            //获取客户电话
            $customer_phone = $request->post('customer_phone');
            //获取运单号
            $track_number   = $request->post('track_number');
            if($order_platform<1){
               return $this->error('请选择正确的订单平台');
            }
            if($customer_name){
                $customer_name = explode(' ',$customer_name);
            }
            //求出用户的所有订单信息
            $customer = (new SaleAfterTask())->getCustomerEmail($order_platform,$increment_id,$customer_name,$customer_phone,$track_number,$customer_email);
            if(!$customer){
                $this->error('找不到订单信息，请重新尝试','/admin/saleaftermanage/order_return/search');
            }
            //求出所有的订单号
            $allIncrementOrder = $customer['increment_id'];
            //求出会员的个人信息
            $customerInfo = $customer['info'];
            unset($customer['info']);
            unset($customer['increment_id']);
            $infoSynergyTaskResult = Db::name('info_synergy_task')->where('order_platform',$order_platform)->where('synergy_order_number','in',$allIncrementOrder)->order('id desc')->select();
            $saleAfterTaskResult = Db::name('sale_after_task')->where('order_platform',$order_platform)->where('order_number','in',$allIncrementOrder)->order('id desc')->select();
            $orderReturnResult = Db::name('order_return')->where('order_platform',$order_platform)->where('increment_id','in',$allIncrementOrder)->order('id desc')->select();
             //求出承接部门和承接人
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();
            //求出订单平台
            $orderPlatformList = (new ManagtoPlatform())->getOrderPlatformList();
            //求出任务优先级
            $prtyIdList     = (new SaleAfterTask())->getPrtyIdList();
            //求出售后问题分类列表
            $issueList      = (new SaleAfterIssue())->getAjaxIssueList();
            //求出信息协同任务分类列表
            $synergyTaskList = (new InfoSynergyTaskCategory())->getSynergyTaskCategoryList();
            //求出关联单据类型
            $relateOrderType = (new InfoSynergyTask())->orderType();
            if(!empty($infoSynergyTaskResult)){
                foreach ($infoSynergyTaskResult as $k=>$v){
                    if ($v['dept_id']) {
                        $deptNumArr = explode('+',$v['dept_id']);
                        $infoSynergyTaskResult[$k]['dept_id'] = '';
                        foreach($deptNumArr as $values){
                            $infoSynergyTaskResult[$k]['dept_id'].= $deptArr[$values].' ';
                        }

                    }
                    if ($v['rep_id']) {
                        $repNumArr = explode('+',$v['rep_id']);
                        $infoSynergyTaskResult[$k]['rep_id'] = '';
                        foreach ($repNumArr as $vals){
                            $infoSynergyTaskResult[$k]['rep_id'].= $repArr[$vals].' ';
                        }
                    }
                    if($v['order_platform']){
                        $infoSynergyTaskResult[$k]['order_platform'] = $orderPlatformList[$v['order_platform']];
                    }
                    if($v['prty_id']){
                        $infoSynergyTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
                    }
                    if($v['synergy_task_id']){
                        $infoSynergyTaskResult[$k]['synergy_task_id'] = $synergyTaskList[$v['synergy_task_id']];
                    }
                    if($v['synergy_order_id']){
                        $infoSynergyTaskResult[$k]['synergy_order_id'] = $relateOrderType[$v['synergy_order_id']];
                    }
                }
            }
            if(!empty($saleAfterTaskResult)){
                foreach ($saleAfterTaskResult as $k=>$v) {
                    if ($v['dept_id']) {
                        $saleAfterTaskResult[$k]['dept_id'] = $deptArr[$v['dept_id']];

                    }
                    if ($v['rep_id']) {
                        $saleAfterTaskResult[$k]['rep_id'] = $repArr[$v['rep_id']];
                    }
                    if($v['order_platform']){
                        $saleAfterTaskResult[$k]['order_platform'] = $orderPlatformList[$v['order_platform']];
                    }
                    if($v['task_status'] == 1){
                        $saleAfterTaskResult[$k]['task_status'] = '处理中';
                    }elseif($v['task_status'] == 2){
                        $saleAfterTaskResult[$k]['task_status'] = '处理完成';
                    }else{
                        $saleAfterTaskResult[$k]['task_status'] = '未处理';
                    }
                    if($v['prty_id']){
                        $saleAfterTaskResult[$k]['prty_id'] = $prtyIdList[$v['prty_id']];
                    }
                    if($v['problem_id']){
                        $saleAfterTaskResult[$k]['problem_id'] = $issueList[$v['problem_id']];
                    }
                }
            }
            if(!empty($orderReturnResult)){
                foreach ($orderReturnResult as $k1=>$v1){
                    if($v1['order_platform']){
                        $orderReturnResult[$k1]['order_platform'] = $orderPlatformList[$v1['order_platform']];
                    }
                }
            }
            $this->view->assign('infoSynergyTaskResult',$infoSynergyTaskResult);
            $this->view->assign('saleAfterTaskResult',$saleAfterTaskResult);
            $this->view->assign('orderReturnResult',$orderReturnResult);
            $this->view->assign('orderInfoResult',$customer);
            $this->view->assign('orderPlatform',$orderPlatformList[$order_platform]);
            $this->view->assign('customerInfo',$customerInfo);
        }
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
        return $this->view->fetch();
    }
    public function ceshi()
    {
        $result = Db::connect('database.db_voogueme')->table('sales_flat_order o')->join('sales_flat_shipment_track s','o.entity_id=s.order_id','left')->join('sales_flat_order_payment p','o.entity_id=p.parent_id','left')->join('sales_flat_order_address a','o.entity_id=a.parent_id')->where('o.customer_email','brendarobinson5383@gmail.com')
            ->field('o.entity_id,o.status,o.coupon_code,o.store_id,o.increment_id,o.customer_email,o.customer_firstname,o.customer_lastname,o.order_currency_code,o.total_item_count,o.base_grand_total,o.total_paid,o.created_at,s.track_number,s.title,p.base_amount_paid,p.base_amount_ordered,p.base_shipping_amount,p.method,p.last_trans_id,p.additional_information,a.telephone,a.postcode,a.street,a.city,a.region,a.country_id')->select();
    }
    public function ceshi2()
    {
        $str = 'a:2:{s:15:"info_buyRequest";a:6:{s:7:"product";s:3:"641";s:8:"form_key";s:16:"r1L18dahcXb0TWxJ";s:3:"qty";i:1;s:7:"options";a:1:{i:534;s:3:"735";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:14:{s:19:"frame_regural_price";s:5:"27.82";s:11:"frame_price";s:5:"27.82";s:12:"prescription";s:320:"min_pd=54&max_pd=78&progressive_bifocal=62&customer_rx=0&prescription_type=Progressive&od_sph=-3.50&od_cyl=-0.75&od_axis=156&os_add=2.25&os_sph=-3.75&os_cyl=-1.00&os_axis=31&pd_r=33.5&pd_l=33.5&pdcheck=on&od_pv=0.00&od_bd=&od_pv_r=0.00&od_bd_r=&os_pv=0.00&os_bd=&os_pv_r=0.00&os_bd_r=&save=Brenda%27s+Rx&information=&pd=";s:16:"is_special_price";s:1:"1";s:10:"index_type";s:64:"1.61 Photochromic Digital Free Form Progressive Hoya® VI - Gray";s:11:"index_price";d:93.5;s:10:"index_name";s:4:"1.61";s:8:"index_id";s:13:"refractive_58";s:10:"coating_id";s:9:"coating_2";s:13:"coatiing_name";s:74:"Super Hydrophobic (water resistant, easy to clean) Anti-Reflective Coating";s:14:"coatiing_price";s:4:"8.95";s:3:"rid";s:1:"0";s:4:"lens";s:6:"102.45";s:5:"total";s:6:"130.27";}}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:3:"Red";s:11:"print_value";s:3:"Red";s:9:"option_id";s:3:"534";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:3:"735";s:11:"custom_view";b:0;}}}';
        $arr = unserialize($str);
        dump($arr);
    }

}
