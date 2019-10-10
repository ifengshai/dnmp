<?php

namespace app\admin\controller\saleaftermanage;

use app\common\controller\Backend;
use app\admin\model\AuthGroup;
use app\admin\model\saleaftermanage\SaleAfterIssue;
use app\admin\model\platformmanage\ManagtoPlatform;
use app\admin\model\saleaftermanage\SaleAfterTaskRemark;
use app\admin\model\Admin;
use think\Request;
use think\Db;
use fast\Tree;

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
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();
            foreach ($list as $key => $val){
                $list[$key]['numberId'] = $key+1;
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
                    $params['task_number'] = 'CO'.date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = session('admin.nickname'); //创建人
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
        if($row['task_status'] ==2){ //如果任务已经处理完成
            $this->error('该任务已经处理完成，无需再次处理');
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
                    if(!empty($params['task_remark'])){
                        $params['task_status'] = 1;
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
            $json = (new ManagtoPlatform())->getOrderPlatformList();
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
            $this->error('参数错误，请重新尝试','/admin/saleaftermanage/sale_after_task/index');
        }
        $result = $this->model->getTaskDetail($id);
        if(!$result){
            $this->error('任务信息不存在，请重新尝试','/admin/saleaftermanage/sale_after_task/index');
        }
        //dump($result);
        $this->view->assign('row',$result);
        $this->view->assign('orderInfo',$this->model->getOrderInfo($result['order_platform'],$result['order_number']));
        return $this->view->fetch();
    }
    /***
     * 异步更新任务状态(单个)
     */
    public function completeAjax(Request $request)
    {
        if($this->request->isAjax()){
            $idss = $request->post('idss');
            if(!$idss){
              return   $this->$this->error('处理失败，请重新尝试');
            }
            $data = [];
            $data['task_status'] = 2;
            $data['handle_time'] = date("Y-m-d H:i:s",time());
            $result = $this->model->where('id',$idss)->update($data);
            if($result !== false){
              return  $this->success('ok');
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
            $arr = [];
            foreach($list as $val){
                if($val['task_status'] ==1){
                    $arr[] = $val['id'];    
                }
            }
            if(!empty($arr)){
                $data = [];
                $data['task_status'] = 2;
                $data['handle_time'] = date("Y-m-d H:i:s",time());
                $result = $this->model->where('id','in',$arr)->update($data);
                if($result !== false){
                  return  $this->success('操作成功');
                }
            }else{
                return $this->error(__('Select the updated record does not meet the requirements, please re-select'));
            }

        }else{
            return $this->error('请求失败,请勿请求');
        }
    }
    /***
     * 测试订单信息
     */
    public function ceshi(){
        $ordertype = 3;
        $order_number = '600070496';
        $result = $this->model->getOrderInfo($ordertype,$order_number);
        echo '<pre>';
        var_dump($result);

    }
    public function nihao()
    {
        $str = 'a:2:{s:15:"info_buyRequest";a:6:{s:7:"product";s:3:"405";s:8:"form_key";s:16:"XkbLBBEaNUn4Vddj";s:3:"qty";i:1;s:7:"options";a:1:{i:399;s:3:"759";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:17:{s:13:"is_frame_only";s:1:"1";s:12:"prescription";s:335:"{"customer_rx":"0","prescription_type":"Progressive","od_sph":"0.25","od_cyl":"0.00","od_axis":"None","os_sph":"0.50","os_cyl":"0.00","os_axis":"None","od_add":"1.50","os_add":"0.00","pd":"62","od_pv":"0.00","od_bd":"","od_pv_r":"0.00","od_bd_r":"","os_pv":"0.00","os_bd":"","os_pv_r":"0.00","os_bd_r":"","year":"Year","month":"Month"}";s:17:"prescription_type";s:11:"Progressive";s:11:"frame_price";d:22.949999999999999;s:19:"frame_regural_price";d:22.949999999999999;s:9:"second_id";s:6:"base-3";s:11:"second_name";s:12:"Resin Lenses";s:12:"second_price";i:0;s:8:"third_id";s:6:"lens-8";s:10:"third_name";s:7:"Classic";s:11:"third_price";d:55;s:7:"four_id";s:10:"coatings-9";s:9:"four_name";s:26:"Oleophobic Anti-Reflective";s:10:"four_price";d:9.9499999999999993;s:10:"lens_price";d:64.950000000000003;s:3:"zsl";s:4:"1.57";s:5:"total";d:87.900000000000006;}}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:7:"Crystal";s:11:"print_value";s:7:"Crystal";s:9:"option_id";s:3:"399";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:3:"759";s:11:"custom_view";b:0;}}}';
        $arr =  unserialize($str);
        $resultArr = $arr['info_buyRequest']['tmplens']['prescription'];
        $resultJson = json_decode($resultArr,true);
        echo '<pre>';
        var_dump($resultJson);

    }
    public function ceshi3()
    {
        $ordertype = 1;
        $order_number = '400026721';
        $result = $this->model->getOrderInfo($ordertype,$order_number);
        echo '<pre>';
        var_dump($result);
    }
    public function zeelool()
    {
        $str = 'a:2:{s:15:"info_buyRequest";a:9:{s:7:"product";s:3:"125";s:4:"uenc";s:80:"aHR0cHM6Ly9tLnplZWxvb2wuY29tL2xlbnMvaW5kZXgvaW5kZXgvbGVucy8yMDZfMzEwX29ub29mbA,,";s:8:"form_key";s:16:"Dx7ybRVskcXkwlCx";s:15:"related_product";s:0:"";s:15:"validate_rating";s:0:"";s:3:"qty";i:1;s:7:"options";a:1:{i:24;s:2:"71";}s:7:"tmplens";a:31:{s:10:"frame_type";s:0:"";s:10:"glass_type";s:10:"eyeglasses";s:19:"frame_regural_price";s:5:"17.95";s:11:"frame_price";s:5:"17.95";s:8:"lenskind";N;s:5:"prism";s:0:"";s:10:"prismprice";s:4:"0.00";s:5:"extra";s:0:"";s:10:"extraprice";s:4:"0.00";s:12:"prescription";s:276:"min_pd=54&max_pd=74&customer_rx=0&prescription_type=SingleVision&od_sph=-1.25&od_cyl=-0.75&od_axis=70&os_sph=-2.25&os_cyl=-0.25&os_axis=126&pd=0&pd_r=30.5&pd_l=30.5&pdcheck=on&od_pv=0.00&od_bd=&od_pv_r=0.00&od_bd_r=&os_pv=0.00&os_bd=&os_pv_r=0.00&os_bd_r=&save=Em&information=";s:10:"lens_title";s:24:"Standard Eyeglass Lenses";s:10:"index_type";s:14:"1.57 Mid-Index";s:11:"index_price";s:4:"0.00";s:10:"index_name";s:4:"1.57";s:10:"coating_id";s:9:"coating_3";s:13:"coatiing_name";s:15:"Premium Coating";s:14:"coatiing_price";s:4:"9.95";s:9:"lens_type";s:0:"";s:15:"lens_type_price";s:4:"0.00";s:9:"lens_tint";s:0:"";s:14:"lens_coating_1";s:0:"";s:20:"lens_coating_1_price";s:1:"0";s:14:"lens_coating_2";s:0:"";s:20:"lens_coating_2_price";s:1:"0";s:14:"lens_coating_3";s:15:"Premium Coating";s:20:"lens_coating_3_price";s:4:"9.95";s:14:"lens_coating_4";s:0:"";s:20:"lens_coating_4_price";s:1:"0";s:3:"rid";s:1:"0";s:4:"lens";s:4:"9.95";s:5:"total";s:5:"27.90";}s:11:"reset_count";b:1;}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:11:"Bright pink";s:11:"print_value";s:11:"Bright pink";s:9:"option_id";s:2:"24";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:2:"71";s:11:"custom_view";b:0;}}}';
        $arr = unserialize($str);
        echo '<pre>';
        var_dump($arr);
    }
}
