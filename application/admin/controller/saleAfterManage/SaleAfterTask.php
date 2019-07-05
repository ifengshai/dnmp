<?php

namespace app\admin\controller\saleAfterManage;

use app\common\controller\Backend;
use app\admin\model\saleAfterManage\SaleAfterIssue;
use app\admin\model\platformManage\ManagtoPlatform;
use think\Request;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class SaleAfterTask extends Backend
{
    
    /**
     * SaleAfterTask模型对象
     * @var \app\admin\model\saleAfterManage\SaleAfterTask
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleAfterManage\SaleAfterTask;
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign('prtyIdList',$this->model->getPrtyIdList());
        $this->view->assign('issueList',(new SaleAfterIssue())->getIssueList(1,0));
        $this->view->assign('getTabList',$this->model->getTabList());

    }
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
    /**
     * 添加
     */
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
                    $params['task_number'] = 'CO'.rand(100,999).rand(100,999);
                    $params['create_person'] = session('admin.username'); //创建人
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
                    $this->success('','/admin/saleaftermanage/sale_after_task/index');
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        echo $row['prty_id'];
        if($row['task_status'] ==2){ //如果任务已经处理完成
            $this->error('该任务已经处理完成，无需再次处理');
        }
        //dump($row);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
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
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
        return $this->view->fetch();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /***
     * 异步请求获取订单所在平台和订单号处理
     * @param
     */
    public function ajax( Request $request)
    {
        if($this->request->isAjax()){
            $ordertype = $request->post('ordertype');
            $order_number = $request->post('order_number');
            if($ordertype<1 || $ordertype>5){ //不在平台之内
               return  $this->error('选择平台错误，请重新选择','','error',0);
            }
            if(!$order_number){
               return  $this->error('订单号不存在，请重新选择','','error',0);
            }
            $result = $this->model->getOrderInfo($ordertype,$order_number);
            if(!$result){
                return $this->error('找不到这个订单，请重新尝试','','error',0);
            }
            return $this->success('','',$result,0);
        }else{
            $arr=[
                12=>'a',34=>'b',57=>'c',84=>'d',
            ];
            $json = json_encode($arr);
            return $this->success('ok','',$json);
        }


    }

    /***
     * 异步获取订单平台
     * type 为 2 没有选择平台
     */
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

    /***
     * 异步获取问题列表
     */
    public function ajaxGetIssueList()
    {
        if($this->request->isAjax()){
            $json = (new SaleAfterIssue())->getAjaxIssueList();
            if(!$json){
                $json = [0=>'请先添加任务问题'];
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
    public function ceshi()
    {

        $json = (new ManagtoPlatform())->getOrderPlatformList();
//        $arr = (object)($json);
//        dump(json_encode($arr));
        dump($json);
        dump(json_encode($json));
    }
}
