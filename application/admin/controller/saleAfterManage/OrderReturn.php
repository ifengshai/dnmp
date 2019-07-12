<?php

namespace app\admin\controller\saleaftermanage;
use think\Db;
use app\common\controller\Backend;
use app\admin\model\platformManage\ManagtoPlatform;
use app\admin\model\saleAfterManage\SaleAfterIssue;
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
                    $params['return_order_number'] = 'WRB'.rand(100,999).rand(100,999);
                    $params['create_person'] = session('admin.username');
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
                        $dataRemark['create_person'] = session('admin.username');
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
                12=>'a',34=>'b',57=>'c',84=>'d',
            ];
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
}
