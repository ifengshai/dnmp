<?php

namespace app\admin\controller\infosynergytaskmanage;

use app\common\controller\Backend;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use app\admin\model\platformManage\ManagtoPlatform;
use app\admin\model\saleAfterManage\SaleAfterTask;
use think\Db;
/**
 * 协同任务管理
 *
 * @icon fa fa-circle-o
 */
class InfoSynergyTask extends Backend
{
    
    /**
     * InfoSynergyTask模型对象
     * @var \app\admin\model\infosynergytaskmanage\InfoSynergyTask
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                //承接部门和承接人写入数据库
                if(count($params['dept_id'])>1){
                    $params['dept_id'] = implode('+',$params['dept_id']);
                }else{
                    $params['dept_id'] = $params['dept_id'][0];
                }
                if(count($params['rep_id'])>1){
                    $params['rep_id']  = implode('+',$params['rep_id']);
                }else{
                    $params['rep_id'] = $params['rep_id'][0];
                }
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
                    $params['synergy_number'] = 'WO'.rand(100,999).rand(100,999);
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
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //任务分类列表
        $this->view->assign('categoryList',(new InfoSynergyTaskCategory())->getIssueList(1,0));
        //订单平台列表
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
        //关联单据类型列表
        $this->view->assign('orderType',$this->model->orderType());
        //任务级别
        $this->view->assign('prtyIdList',(new SaleAfterTask())->getPrtyIdList());
        //测试承接部门
        $this->view->assign('deptList',$this->model->testDepId());
        //测试承接人
        $this->view->assign('repList',$this->model->testRepId());
        return $this->view->fetch();
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
                ->with(['infoSynergyTaskCategory'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['infoSynergyTaskCategory'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $deptArr = $this->model->testDepId();
            $repArr  = $this->model->testRepId();
//            dump($deptArr);
//            dump($repArr);
            foreach ($list as $key => $val){
                if($val['dept_id']){
                    $deptNumArr = explode('+',$val['dept_id']);
                    $list[$key]['dept'] = '';
                    foreach($deptNumArr as $values){
                        $list[$key]['dept'].= $deptArr[$values].' ';
                    }
                }
                if($val['rep_id']){
                    $repNumArr = explode('+',$val['rep_id']);
                    $list[$key]['rep'] = '';
                    foreach ($repNumArr as $vals){
                        $list[$key]['rep'].= $repArr[$vals].' ';
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 获取关联单的数据类型
     */
    public function getOrderType()
    {
        if($this->request->isAjax()){
            $json = $this->model->orderType();
            if(!$json){
                $json = [0=>'请先添加关联单类型'];
            }
            $arrToObject = (object)($json);
            return json($arrToObject);
        }else{
            $this->error('请求错误');
        }
//        $json = $this->model->orderType();
//        if(!$json){
//            $json = [0=>'请先添加关联单类型'];
//        }
//        $arrToObject = (object)($json);
//        dump(json_encode($arrToObject));
    }

}
