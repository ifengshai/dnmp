<?php

namespace app\admin\controller\saleaftermanage;

use think\Db;
use fast\Tree;
use app\admin\model\Admin;
use think\Request;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\controller\Backend;
use app\admin\model\AuthGroup;
use app\admin\model\saleaftermanage\SaleAfterIssue;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\SaleAfterTaskRemark;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;


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
    protected $groupdata = [];
    protected $noNeedLogin = [
        'updateRepId',
        'updateMoreRepId',
        'updateCategoryVoogueme',
        'updateCategoryNihao',
        'updateCategoryProblemIdNihao',
        'contrastCategory',
        'zeeloolSaleImport'
    ];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\SaleAfterTask;
        //新加内容
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();
        Tree::instance()->init($groupList);
        $result = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        } else {
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $result = array_merge($result, Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['pid'])));
            }
        }
        //dump($result);
        $groupName = [];
        foreach ($result as $k => $v) {
            //$groupName['group_id'][] = $v['id'];
            $groupName[$v['id']] = $v['name'];
        }
        $staffArr = array_keys($groupName);
        $staffList = (new Admin())->getStaffList($staffArr);
        $this->groupdata = $groupName;
        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_ids' => $this->auth->getGroupIds()]);
        $this->view->assign('staffList',$staffList);
        $this->view->assign('groupdata', $this->groupdata);
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
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
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();
            foreach ($list as $key => $val){
                if($val['dept_id']){
                    $list[$key]['dept_id']= $deptArr[$val['dept_id']];

                }
                if($val['rep_id']){
                    $list[$key]['rep_id'] = $repArr[$val['rep_id']];
                }
            }
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
            if(!isset($params['problem_id'])){
                $this->error(__('Please select the problem category'));
            }
            if ($params) {
                $params = $this->preExcludeFields($params);
                // echo '<pre>';
                // var_dump($params);
                // exit;
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
                    $params['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname'); //创建人
                    $params['create_time']   = date("Y-m-d H:i:s",time());
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
                    $this->success('','saleaftermanage/sale_after_task/index');
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
        if($row['task_status'] ==2){ //如果任务已经处理完成
            $this->error(__('该任务已经处理完成，无需再次编辑'),'saleaftermanage/sale_after_task/index',50,50);
        }
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
                    // if(!empty($params['task_remark'])){
                    //     $data = [];
                    //     $data['tid'] = $tid;
                    //     $data['remark_record'] = strip_tags($params['task_remark']);
                    //     $data['create_person'] = session('admin.nickname');
                    //     $data['create_time']   = date("Y-m-d H:i:s",time());
                    //     (new SaleAfterTaskRemark())->allowField(true)->save($data);
                    // }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if (1 == $row['order_platform']) {
            $orderInfo = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($row['order_number']);
        } elseif (2 == $row['order_platform']) {
            $orderInfo = VooguemePrescriptionDetailHelper::get_one_by_increment_id($row['order_number']);
        } elseif (3 == $row['order_platform']) {
            $orderInfo = NihaoPrescriptionDetailHelper::get_one_by_increment_id($row['order_number']);
        }
        //求出本条记录相对应的售后备注记录
        $row->task_remark = (new SaleAfterTaskRemark())->getRelevanceRecord($ids);
        $this->view->assign("row", $row);
        $this->view->assign('orderInfo',$orderInfo);
        $this->view->assign('orderPlatform',$result['order_platform']);
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
        return $this->view->fetch();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /***
     * 异步请求获取订单所在平台和订单号处理(原先)
     * @param
     */
    // public function ajax( Request $request)
    // {
    //     if($this->request->isAjax()){
    //         $ordertype = $request->post('ordertype');
    //         $order_number = $request->post('order_number');
    //         if($ordertype<1 || $ordertype>5){ //不在平台之内
    //            return  $this->error('选择平台错误，请重新选择','','error',0);
    //         }
    //         if(!$order_number){
    //            return  $this->error('订单号不存在，请重新选择','','error',0);
    //         }
    //         $result = $this->model->getOrderInfo($ordertype,$order_number);
    //         if(!$result){
    //             return $this->error('找不到这个订单，请重新尝试','','error',0);
    //         }
    //         return $this->success('','',$result,0);
    //     }else{
    //         return $this->error('404 Not Found');
    //     }


    // }
    /***
     * 修改之后的异步请求获取订单所在平台和订单号处理
     */
        public function ajax(Request $request){
            if($this->request->isAjax()){
                $ordertype = $request->post('ordertype');
                $order_number = $request->post('order_number');
                if($ordertype<1 || $ordertype>5){ //不在平台之内
                    return $this->error('选择平台错误,请重新选择','','error',0);
                }
                if(!$order_number){
                    return  $this->error('订单号不存在，请重新选择','','error',0);
                }
                if ($ordertype == 1) {
                    $result = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 2) {
                    $result = VooguemePrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 3) {
                    $result = NihaoPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                }
                if(!$result){
                    return $this->error('找不到这个订单,请重新尝试','','error',0);
                }
                    return $this->success('','',$result,0);
            }else{
                return $this->error('404 Not Found');
            }
        }    
    /***
     * 异步获取订单平台
     * type 为 2 没有选择平台
     */
    public function getAjaxOrderPlatformList()
    {
        if($this->request->isAjax()){
            $json = (new MagentoPlatform())->getOrderPlatformList();
            if(!$json){
                $json = [0=>'请添加订单平台'];
            }
            $arrToObject = (object)($json);
            return json($arrToObject);
        }else{
            return $this->error('404 Not Found');
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
            return $this->error('404 Not Found');    
        }

    }
    /***
     * 查看任务详情
     * $param id  任务ID
     */
    public function detail(Request $request)
    {
        $id = $request->param('ids');
        if(!$id){
            $this->error('参数错误，请重新尝试','saleaftermanage/sale_after_task/index');
        }
        $result = $this->model->getTaskDetail($id);
        $result['problem_desc'] = strip_tags($result['problem_desc']);
        if(!$result){
            $this->error('任务信息不存在，请重新尝试','saleaftermanage/sale_after_task/index');
        }
        if (1 == $result['order_platform']) {
            $orderInfo = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        } elseif (2 == $result['order_platform']) {
            $orderInfo = VooguemePrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        } elseif (3 == $result['order_platform']) {
            $orderInfo = NihaoPrescriptionDetailHelper::get_one_by_increment_id($result['order_number']);
        }
        $this->view->assign('row',$result);
        $this->view->assign('orderPlatform',$result['order_platform']);
        $this->view->assign('orderInfo',$orderInfo);
        return $this->view->fetch();
    }
    /***
     * 异步更新任务状态(单个)
     */
    public function completeAjax(Request $request)
    {
        if($this->request->isGet()){
            $idss = $request->param('idss');
            if(!$idss){
              return   $this->$this->error('处理失败，请重新尝试');
            }
            $row = $this->model->get($idss);
            if(2 == $row['task_status']){
                $this->error('已经处理完成,无需再次操作！！');
            }
            $data = [];
            $data['task_status'] = 2;
            $data['handle_time'] = date("Y-m-d H:i:s",time());
            $result = $this->model->where('id',$idss)->update($data);
            if($result !== false){
              return  $this->success('处理成功','saleaftermanage/sale_after_task');
            }
        }else{
            return $this->error('请求失败,请勿请求');
        }
    }

    /***
     * 异步更新任务状态(多个)
     */
    public function completeAjaxAll($ids=null)
    {
        if($this->request->isAjax()){
            if(!$ids){
              return   $this->error('处理失败，请重新尝试');
            }
            $map['id'] = ['in',$ids];
            $list = $this->model->where($map)->field('id,task_status')->select();
            foreach($list as $val){
                if ( 2 <= $val['task_status']) {
                    $this->error('此状态无法处理完成操作！！');
                }
            }
            $data = [];
            $data['task_status'] = 2;
            $data['handle_time'] = date("Y-m-d H:i:s",time());
            $result = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if (false !== $result) {
                return $this->success('处理成功');
            } else {
                return $this->error('处理失败');
            }
        }else{
            return $this->error('请求失败,请勿请求');
        }
    }
    /***
     * 处理任务
     */
    public function handle_task($ids=null){
        $row = $this->model->get($ids);
        // echo '<pre>';
        // var_dump($row);
        // exit;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['task_status'] >=2){ //如果任务已经处理完成
            $this->error('该状态无法处理！！','saleaftermanage/sale_after_task');
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
            unset($params['id']);
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
                    if(!empty($params['task_remark'])){
                        $data = [];
                        $data['tid'] = $tid;
                        $data['remark_record'] = strip_tags($params['task_remark']);
                        $data['create_person'] = session('admin.nickname');
                        $data['create_time']   = date("Y-m-d H:i:s",time());
                        (new SaleAfterTaskRemark())->allowField(true)->save($data);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //求出本条记录相对应的售后备注记录
        $row->task_remark = (new SaleAfterTaskRemark())->getRelevanceRecord($ids);
        $row['problem_desc'] = strip_tags($row['problem_desc']);
        $this->view->assign("row", $row);
        $this->view->assign('SolveScheme',$this->model->getSolveScheme());
        return $this->view->fetch();
    }
    /**
     * 取消
     */
    public function closed($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,task_status')->select();
            foreach ($row as $v) {
                if ( 0 != $v['task_status']) {
                    $this->error('只有新建状态才能操作！！');
                }
            }
            $data['task_status'] = 3;
            $result = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if (false !== $result) {
                return $this->success('确认成功');
            } else {
                return $this->error('确认失败');
            }
        } else {
            return $this->error('404 Not Found');
        }
    }
    //更新旧数据的承接人,单个情况
   public function updateRepId()
   {
       $where['rep_id'] =0;
       $where['nickname'] = ['neq',''];
       $result = Db::name('sale_after_task')->alias('t')->where($where)->join('fa_admin a','t.rep_preson = a.nickname')
       ->field('DISTINCT(t.rep_preson),a.id as aid,a.nickname')->select();
       if(!$result){
           return false;
       }
       foreach($result as $v){
         Db::name('sale_after_task')->where(['rep_preson'=>$v['rep_preson']])->update(['rep_id'=>$v['aid']]);
       }
   }
   //更新多个承接人的情况，多个情况 zeelool
   public function updateMoreRepId()
   {
      $where['rep_id'] = 0;
      $where['rep_preson'] = ['like','%,%'];
      $result = Db::name('sale_after_task')->where($where)->field('DISTINCT(rep_preson),id,create_person')->select();
      if(!$result){
          return false;
      }
      foreach($result as $v){
          if(strpos($v['rep_preson'],'王伟') !== false){
             //echo $v['id'].'<br>';
             Db::name('sale_after_task')->where(['id'=>$v['id']])->update(['rep_id'=>75]);
          }else{
            $rep_id = Db::name('admin')->where(['nickname'=>$v['create_person']])->value('id');
            if($rep_id){
                Db::name('sale_after_task')->where(['id'=>$v['id']])->update(['rep_id'=>$rep_id]);
            }
            
          }
      }
   }

    //更新多个承接人的情况，多个情况 voogueme
    public function updateMoreRepIdVoogueme()
    {
        $where['rep_id'] = 0;
        $where['rep_preson'] = ['like','%,%'];
        $result = Db::name('sale_after_task')->where($where)->field('DISTINCT(rep_preson),id,create_person')->select();
        if(!$result){
            return false;
        }
        foreach($result as $v){
            if(strpos($v['rep_preson'],'白青青') !== false){
            Db::name('sale_after_task')->where(['id'=>$v['id']])->update(['rep_id'=>95]);
            }else{
            $rep_id = Db::name('admin')->where(['nickname'=>$v['create_person']])->value('id');
            if($rep_id){
                Db::name('sale_after_task')->where(['id'=>$v['id']])->update(['rep_id'=>$rep_id]);
            }
            
            }
        }
    }
   
   //旧售后记录日志添加到新系统备注当中,zeelool站
   public function updateLog()
   {
       $where['is_update'] = 1;
       $result = Db::table('zeelool_service_saled_log')->where($where)->limit(500)->select();
       if(!$result){
         return false;
       }
       foreach($result as $v){
           $tid = Db::name('sale_after_task')->where(['order_number'=>$v['increment_id']])->value('id');
           if($tid){
            $data['tid'] = $tid;
            $data['remark_record'] = $v['remark'];
            $data['create_person'] = $v['created_operater'];
            $data['create_time']   = $v['created_at'];
            Db::name('sale_after_task_remark')->insert($data);
            Db::table('zeelool_service_saled_log')->where(['id'=>$v['id']])->update(['is_update'=>2]);
           }
       }
   }
      //旧售后记录日志添加到新系统备注当中,voogueme站
      public function updateLogVoogueme()
      {
          $where['is_update'] = 1;
          $result = Db::table('voogueme_service_saled_log')->where($where)->limit(500)->select();
          if(!$result){
            return false;
          }
          foreach($result as $v){
              $tid = Db::name('sale_after_task')->where(['order_number'=>$v['increment_id']])->value('id');
              if($tid){
               $data['tid'] = $tid;
               $data['remark_record'] = $v['remark'];
               $data['create_person'] = $v['created_operater'];
               $data['create_time']   = $v['created_at'];
               Db::name('sale_after_task_remark')->insert($data);
               Db::table('voogueme_service_saled_log')->where(['id'=>$v['id']])->update(['is_update'=>2]);
              }
          }
      }
      ////旧售后记录日志添加到新系统备注当中,nihao站
      public function updateLogNihao()
      {
          $where['is_update'] = 1;
          $result = Db::table('nihao_service_saled_log')->where($where)->limit(500)->select();
          if(!$result){
            return false;
          }
          foreach($result as $v){
              $tid = Db::name('sale_after_task')->where(['order_number'=>$v['increment_id']])->value('id');
              if($tid){
               $data['tid'] = $tid;
               $data['remark_record'] = $v['remark'];
               $data['create_person'] = $v['created_operater'];
               $data['create_time']   = $v['created_at'];
               Db::name('sale_after_task_remark')->insert($data);
               Db::table('nihao_service_saled_log')->where(['id'=>$v['id']])->update(['is_update'=>2]);
              }
          }
      }
      //更新voogueme站的分类数据
      public function updateCategoryVoogueme()
      {
         $where['order_platform'] = 2;
         $where['is_update_category'] =1; 
         $result = Db::name('sale_after_task')->where($where)->field('id,problem_id,voogueme_category_name')->limit(200)->select();
         if(!$result){
             return false;
         }
         $categoryInfo = Db::connect('database.db_voogueme')->table('zeelool_service_saled_category')->field('id,name')->select();
         if(!$categoryInfo){
             return false;
         }
         $arr = [];
         foreach($categoryInfo as $vs){
             $arr[$vs['id']] = $vs['name'];
         }
         foreach($result as $v){
             $data['voogueme_category_name'] = $arr[$v['problem_id']];
             $data['is_update_category'] = 2;
             Db::name('sale_after_task')->where(['id'=>$v['id']])->update($data);
         }
      }
      //更新nihao站的分类数据
      public function updateCategoryNihao()
      {
        $where['order_platform'] = 3;
        $where['is_update_category'] =1; 
        $result = Db::name('sale_after_task')->where($where)->field('id,problem_id,nihao_category_name')->limit(200)->select();
        if(!$result){
            return false;
        }
        $categoryInfo = Db::connect('database.db_nihao')->table('zeelool_service_saled_category')->field('id,name')->select();
        if(!$categoryInfo){
            return false;
        }
        $arr = [];
        foreach($categoryInfo as $vs){
            $arr[$vs['id']] = $vs['name'];
        }
        foreach($result as $v){
            $data['nihao_category_name'] = $arr[$v['problem_id']];
            $data['is_update_category'] = 3;
            Db::name('sale_after_task')->where(['id'=>$v['id']])->update($data);
        }
      }
      //把voogueme站的分类更新到数据当中
      public function updateCategoryProblemId()
      {
          $where['order_platform'] = 2;
          $where['is_update_category'] = 2;
          $result = Db::name('sale_after_task')->where($where)->field('id,voogueme_category_name')->limit(300)->select();
          if(!$result){
              return false;
          }
          $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
          if(!$categoryInfo){
              return false;
          }
          $arr = [];
          foreach($categoryInfo as $vs){
            $arr[$vs['name']] = $vs['id'];
          }
          foreach($result as $v){
            
             if(isset($arr[$v['voogueme_category_name']])){
                $problem_id = $arr[$v['voogueme_category_name']];
                if($problem_id){
                    $data['problem_id'] = $problem_id;
                    $data['is_update_category'] = 4;
                    Db::name('sale_after_task')->where(['id'=>$v['id']])->update($data);
                }
             }elseif('其它' == $v['voogueme_category_name']){
                $data['problem_id'] = 19;
                $data['is_update_category'] = 4;
                Db::name('sale_after_task')->where(['id'=>$v['id']])->update($data);
             } 

          }
      }
      //把nihao站的分类更新到数据当中
      public function updateCategoryProblemIdNihao()
      {
        $where['order_platform'] = 3;
        $where['is_update_category'] = 3;
        $result = Db::name('sale_after_task')->where($where)->field('id,nihao_category_name')->limit(300)->select();
        if(!$result){
            return false;
        }
        $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
        if(!$categoryInfo){
            return false;
        }
        $arr = [];
        foreach($categoryInfo as $vs){
          $arr[$vs['name']] = $vs['id'];
        }
        foreach($result as $v){
           if(isset($arr[$v['nihao_category_name']])){
              $problem_id = $arr[$v['nihao_category_name']];
              if($problem_id){
                  $data['problem_id'] = $problem_id;
                  $data['is_update_category'] = 6;
                  Db::name('sale_after_task')->where(['id'=>$v['id']])->update($data);
              }
           }
        }
      }

      /***
       * 对比售后分类和问题分类
       */
      public function contrastCategory()
      {
        $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
        $categoryInfo2 = Db::table('zeelool_service_question_category')->field('id,pid,name')->select();
        $arr = $arr2 = [];
        foreach($categoryInfo as $v){
            $arr[] = $v['name'];
        }
        foreach($categoryInfo2 as $v2){
            $arr2[] = $v2['name'];
        }
        echo '<pre>';
        $arr3 =  array_diff($arr2,$arr);
        dump($arr3);
      }

      //导入zeelool站的问题管理数据
      public function zeeloolImport()
      {
          $result = Db::table('zeelool_service_question')->field('increment_id,email,cate_id,description,created_operator,task_operator,remark,created_at')->limit(12000,4000)->select();
          $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
          $categoryInfo2 = Db::table('zeelool_service_question_category')->field('id,name')->select();
          $user = Db::name('admin')->field('id,nickname')->select();
          //$sku  = Db::table('zeelool_service_question_sku')->field('increment_id,sku')->select();
          $categoryArr = $categoryArr2 = $userArr = [];
          foreach($categoryInfo as $vs){
            $categoryArr[$vs['name']] = $vs['id'];
          }
          foreach($categoryInfo2 as $vc){
            $categoryArr2[$vc['id']] = $vc['name'];
          }
          foreach($user as $vu){
            $userArr[$vu['nickname']] = $vu['id'];  
          }
          $arr = [];
          foreach($result as $k=> $v){
             $arr[$k]['order_number'] = $v['increment_id'];
             $arr[$k]['order_platform'] = 1;
             $arr[$k]['task_status'] = 2;
             $arr[$k]['prty_id'] = 1;
             $arr[$k]['customer_email'] = isset($v['email']) ? $v['email'] : ''; 
             $arr[$k]['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999); 
             $arr[$k]['problem_id'] = isset($categoryArr[$categoryArr2[$v['cate_id']]]) ? $categoryArr[$categoryArr2[$v['cate_id']]] : 0; 
             $arr[$k]['problem_desc'] = $v['description'].'<br/>'.$v['remark']; 
             $arr[$k]['create_person'] = $v['created_operator']; 
             $arr[$k]['create_time'] = $v['created_at']; 
             $arr[$k]['rep_id'] = isset($userArr[$v['task_operator']]) ? $userArr[$v['task_operator']] : 0; 
          }
          $this->model->allowField(true)->saveAll($arr);
      }
  
      //导入voogueme站问题管理数据
      public function vooguemeImport()
      {
        $result = Db::table('voogueme_service_question')->field('increment_id,email,cate_id,description,created_operator,task_operator,remark,created_at')->limit(0,4000)->select();
        $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
        $categoryInfo2 = Db::table('voogueme_service_question_category')->field('id,name')->select();
        $user = Db::name('admin')->field('id,nickname')->select();
        //$sku  = Db::table('zeelool_service_question_sku')->field('increment_id,sku')->select();
        $categoryArr = $categoryArr2 = $userArr = [];
        foreach($categoryInfo as $vs){
          $categoryArr[$vs['name']] = $vs['id'];
        }
        foreach($categoryInfo2 as $vc){
          $categoryArr2[$vc['id']] = $vc['name'];
        }
        foreach($user as $vu){
          $userArr[$vu['nickname']] = $vu['id'];  
        }
        $arr = [];
        foreach($result as $k=> $v){
           $arr[$k]['order_number'] = $v['increment_id'];
           $arr[$k]['order_platform'] = 2;
           $arr[$k]['task_status'] = 2;
           $arr[$k]['prty_id'] = 1;
           $arr[$k]['customer_email'] = isset($v['email']) ? $v['email'] : ''; 
           $arr[$k]['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999); 
           $arr[$k]['problem_id'] = isset($categoryArr[$categoryArr2[$v['cate_id']]]) ? $categoryArr[$categoryArr2[$v['cate_id']]] : 0; 
           $arr[$k]['problem_desc'] = $v['description'].'<br/>'.$v['remark']; 
           $arr[$k]['create_person'] = $v['created_operator']; 
           $arr[$k]['create_time'] = $v['created_at']; 
           $arr[$k]['rep_id'] = isset($userArr[$v['task_operator']]) ? $userArr[$v['task_operator']] : 0; 
        }
        $this->model->allowField(true)->saveAll($arr);
      }
      //导入你好站问题数据,为空不用执行
      public function nihaoImport()
      {
        $result = Db::table('nihao_service_question')->field('increment_id,email,cate_id,description,created_operator,task_operator,remark,created_at')->limit(0,4000)->select();
        echo '<pre>';
        var_dump($result);
        exit;
        $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
        $categoryInfo2 = Db::table('nihao_service_question_category')->field('id,name')->select();
        $user = Db::name('admin')->field('id,nickname')->select();
        $categoryArr = $categoryArr2 = $userArr = [];
        foreach($categoryInfo as $vs){
          $categoryArr[$vs['name']] = $vs['id'];
        }
        foreach($categoryInfo2 as $vc){
          $categoryArr2[$vc['id']] = $vc['name'];
        }
        foreach($user as $vu){
          $userArr[$vu['nickname']] = $vu['id'];  
        }
        $arr = [];
        foreach($result as $k=> $v){
           $arr[$k]['order_number'] = $v['increment_id'];
           $arr[$k]['order_platform'] = 3;
           $arr[$k]['task_status'] = 2;
           $arr[$k]['prty_id'] = 1;
           $arr[$k]['customer_email'] = isset($v['email']) ? $v['email'] : ''; 
           $arr[$k]['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999); 
           $arr[$k]['problem_id'] = isset($categoryArr[$categoryArr2[$v['cate_id']]]) ? $categoryArr[$categoryArr2[$v['cate_id']]] : 0; 
           $arr[$k]['problem_desc'] = $v['description'].'<br/>'.$v['remark']; 
           $arr[$k]['create_person'] = $v['created_operator']; 
           $arr[$k]['create_time'] = $v['created_at']; 
           $arr[$k]['rep_id'] = isset($userArr[$v['task_operator']]) ? $userArr[$v['task_operator']] : 0; 
        }
        $this->model->allowField(true)->saveAll($arr);
      }
      //导入zeelool售后数据
      public function zeeloolSaleImport()
      {
        $where['created_at'] = ['LT','2019-09-01 00:00:00'];
        $result = Db::table('zeelool_service_saled')->where($where)->field('increment_id,email,cate_id,solution_id,description,created_operator,skus,flag,task_operator,refund_amount,refund_mode,
        gift_coupons,tariff_amount,remark,status,complate_at,is_visable,created_at,updatetime')->limit(4000,4000)->select();
        var_dump($result);
        exit;
        $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
        $categoryInfo2 = Db::table('zeelool_service_saled_category')->field('id,name')->select();
        $user = Db::name('admin')->field('id,nickname')->select();
        $categoryArr = $categoryArr2 = $userArr = [];
        foreach($categoryInfo as $vs){
          $categoryArr[$vs['name']] = $vs['id'];
        }
        foreach($categoryInfo2 as $vc){
          $categoryArr2[$vc['id']] = $vc['name'];
        }
        foreach($user as $vu){
          $userArr[$vu['nickname']] = $vu['id'];  
        }
        $arr = [];
        foreach($result as $k =>$v)
        {
            $arr[$k]['order_number'] = $v['increment_id'];
            $arr[$k]['order_platform'] = 1;
        if('processing' == $v['status']){
            $arr[$k]['task_status'] = 1;
        }elseif('complate' == $v['status']){
            $arr[$k]['task_status'] = 2;
        } 
            $arr[$k]['customer_email'] = isset($v['email']) ? $v['email'] : '';
            $arr[$k]['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999); 
            $arr[$k]['problem_id'] = isset($categoryArr[$categoryArr2[$v['cate_id']]]) ? $categoryArr[$categoryArr2[$v['cate_id']]] : 0;
            $arr[$k]['handle_scheme'] = $v['solution_id'];
            $arr[$k]['problem_desc'] = $v['description'].'<br/>'.$v['remark'];
            $arr[$k]['create_person'] = $v['created_operator'];
            $arr[$k]['order_skus'] = isset($v['skus']) ? $v['skus'] : '';
            $arr[$k]['prty_id'] = $v['flag'];
            $arr[$k]['rep_id'] = isset($userArr[$v['task_operator']]) ? $userArr[$v['task_operator']] : 0;
            $arr[$k]['refund_money'] = $v['refund_amount'];
            $arr[$k]['refund_way'] = $v['refund_mode'];
            $arr[$k]['give_coupon'] = $v['gift_coupons'];
            $arr[$k]['tariff'] = $v['tariff_amount'];
            $arr[$k]['complate_time'] = isset($v['complate_at']) ? $v['complate_at'] : '0000-00-00 00:00:00';;
            $arr[$k]['create_time']  = $v['created_at'];
            $arr[$k]['handle_time']  = isset($v['updatetime']) ? $v['updatetime'] : '0000-00-00 00:00:00';

        }
        $this->model->allowField(true)->saveAll($arr);
      }
      //导入voogueme售后数据
      public function vooguemeSaleImport()
      {
        $where['created_at'] = ['LT','2019-09-01 00:00:00'];
        $result = Db::table('voogueme_service_saled')->where($where)->field('increment_id,email,cate_id,solution_id,description,created_operator,skus,flag,task_operator,refund_amount,refund_mode,
        gift_coupons,tariff_amount,remark,status,complate_at,is_visable,created_at,updatetime')->limit(0,4000)->select();
        var_dump($result);
        exit;
        $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
        $categoryInfo2 = Db::table('voogueme_service_saled_category')->field('id,name')->select();
        $user = Db::name('admin')->field('id,nickname')->select();
        $categoryArr = $categoryArr2 = $userArr = [];
        foreach($categoryInfo as $vs){
          $categoryArr[$vs['name']] = $vs['id'];
        }
        foreach($categoryInfo2 as $vc){
          $categoryArr2[$vc['id']] = $vc['name'];
        }
        foreach($user as $vu){
          $userArr[$vu['nickname']] = $vu['id'];  
        }
        $arr = [];
        foreach($result as $k =>$v)
        {
            $arr[$k]['order_number'] = $v['increment_id'];
            $arr[$k]['order_platform'] = 2;
        if('processing' == $v['status']){
            $arr[$k]['task_status'] = 1;
        }elseif('complate' == $v['status']){
            $arr[$k]['task_status'] = 2;
        } 
            $arr[$k]['customer_email'] = isset($v['email']) ? $v['email'] : '';
            $arr[$k]['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999); 
            $arr[$k]['problem_id'] = isset($categoryArr[$categoryArr2[$v['cate_id']]]) ? $categoryArr[$categoryArr2[$v['cate_id']]] : 0;
            $arr[$k]['handle_scheme'] = $v['solution_id'];
            $arr[$k]['problem_desc'] = $v['description'].'<br/>'.$v['remark'];
            $arr[$k]['create_person'] = $v['created_operator'];
            $arr[$k]['order_skus'] = isset($v['skus']) ? $v['skus'] : '';
            $arr[$k]['prty_id'] = $v['flag'];
            $arr[$k]['rep_id'] = isset($userArr[$v['task_operator']]) ? $userArr[$v['task_operator']] : 0;
            $arr[$k]['refund_money'] = $v['refund_amount'];
            $arr[$k]['refund_way'] = $v['refund_mode'];
            $arr[$k]['give_coupon'] = $v['gift_coupons'];
            $arr[$k]['tariff'] = $v['tariff_amount'];
            $arr[$k]['complate_time'] = isset($v['complate_at']) ? $v['complate_at'] : '0000-00-00 00:00:00';;
            $arr[$k]['create_time']  = $v['created_at'];
            $arr[$k]['handle_time']  = isset($v['updatetime']) ? $v['updatetime'] : '0000-00-00 00:00:00';;

        }
        $this->model->allowField(true)->saveAll($arr);
      }
        //导入nihao售后数据
        public function nihaoSaleImport()
        {
            $where['created_at'] = ['LT','2019-09-01 00:00:00'];
            $result = Db::table('nihao_service_saled')->where($where)->field('increment_id,email,cate_id,solution_id,description,created_operator,skus,flag,task_operator,refund_amount,refund_mode,
            gift_coupons,tariff_amount,remark,status,complate_at,is_visable,created_at,updatetime')->limit(0,4000)->select();
            var_dump($result);
            exit;
            $categoryInfo = Db::name('sale_after_issue')->field('id,name')->select();
            $categoryInfo2 = Db::table('nihao_service_saled_category')->field('id,name')->select();
            $user = Db::name('admin')->field('id,nickname')->select();
            $categoryArr = $categoryArr2 = $userArr = [];
            foreach($categoryInfo as $vs){
            $categoryArr[$vs['name']] = $vs['id'];
            }
            foreach($categoryInfo2 as $vc){
            $categoryArr2[$vc['id']] = $vc['name'];
            }
            foreach($user as $vu){
            $userArr[$vu['nickname']] = $vu['id'];  
            }
            $arr = [];
            foreach($result as $k =>$v)
            {
                $arr[$k]['order_number'] = $v['increment_id'];
                $arr[$k]['order_platform'] = 3;
            if('processing' == $v['status']){
                $arr[$k]['task_status'] = 1;
            }elseif('complate' == $v['status']){
                $arr[$k]['task_status'] = 2;
            } 
                $arr[$k]['customer_email'] = isset($v['email']) ? $v['email'] : '';
                $arr[$k]['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999); 
                $arr[$k]['problem_id'] = isset($categoryArr[$categoryArr2[$v['cate_id']]]) ? $categoryArr[$categoryArr2[$v['cate_id']]] : 0;
                $arr[$k]['handle_scheme'] = $v['solution_id'];
                $arr[$k]['problem_desc'] = $v['description'].'<br/>'.$v['remark'];
                $arr[$k]['create_person'] = $v['created_operator'];
                $arr[$k]['order_skus'] = isset($v['skus']) ? $v['skus'] : '';
                $arr[$k]['prty_id'] = $v['flag'];
                $arr[$k]['rep_id'] = isset($userArr[$v['task_operator']]) ? $userArr[$v['task_operator']] : 0;
                $arr[$k]['refund_money'] = $v['refund_amount'];
                $arr[$k]['refund_way'] = $v['refund_mode'];
                $arr[$k]['give_coupon'] = $v['gift_coupons'];
                $arr[$k]['tariff'] = $v['tariff_amount'];
                $arr[$k]['complate_time'] = isset($v['complate_at']) ? $v['complate_at'] : '0000-00-00 00:00:00';
                $arr[$k]['create_time']  = $v['created_at'];
                $arr[$k]['handle_time']  = isset($v['updatetime']) ? $v['updatetime'] : '0000-00-00 00:00:00';
    
            }
            $this->model->allowField(true)->saveAll($arr);
        }

}
