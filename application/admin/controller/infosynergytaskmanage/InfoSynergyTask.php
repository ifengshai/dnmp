<?php

namespace app\admin\controller\infosynergytaskmanage;

use app\admin\controller\warehouse\Inventory;
use app\admin\model\Admin;
use app\common\controller\Backend;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\SaleAfterTask;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskRemark;
use app\admin\model\AuthGroup;
use think\Db;
use think\Request;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

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
        $this->view->assign('allGroup', (new AuthGroup())->getAllGroup());
        $this->view->assign('allAdmin', (new Admin())->getAllStaff());
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
            if (!isset($params['synergy_task_id'])) {
                $this->error(__('Please select the task category'));
            }
            if (in_array("", $params['rep_id'])) {
                $this->error(__('Please select the contractor'));
            }
            // echo '<pre>';
            // var_dump($params['change_type']);
            // exit;
            $item = isset($params['item']) ? $params['item']  : '';
            $lens = isset($params['lens']) ? $params['lens']  : '';
            // echo '<pre>';
            // var_dump($item);
            // exit;
            //更改类型
            $change_type = $params['change_type'];
            unset($params['change_type']);
            if ($params) {
                $params = $this->preExcludeFields($params);
                //承接部门和承接人写入数据库
                if (count($params['dept_id']) > 1) {
                    $params['dept_id'] = implode('+', $params['dept_id']);
                } else {
                    $params['dept_id'] = $params['dept_id'][0];
                }
                if (count($params['rep_id']) > 1) {
                    $params['rep_id']  = implode('+', $params['rep_id']);
                } else {
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
                    $params['synergy_number'] = 'WO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
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
                    if ($item) {
                        $data = [];
                        foreach ($item as $keys => $arr) {
                            $data[$keys]['tid'] = $this->model->id;
                            $data[$keys]['increment_id']    = $params['synergy_order_number'] ?: '';
                            $data[$keys]['platform_type']   = $params['order_platform'] ?: 0;
                            $data[$keys]['change_type']     = $change_type ?: 2;                                                    
                            $data[$keys]['original_sku']    = !empty($arr['original_sku']) ? $arr['original_sku'] : '';
                            $data[$keys]['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : '';
                            $data[$keys]['change_sku']      = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data[$keys]['change_number']   = !empty($arr['change_number']) ? $arr['change_number'] : '';
                            $data[$keys]['create_person']   = session('admin.nickname');
                            $data[$keys]['update_time']     = date("Y-m-d H:i:s", time());     
                        }
                        (new InfoSynergyTaskChangeSku())->allowField(true)->saveAll($data);
                    }
                    if ($lens) {
                        $dataLens = [];
                        foreach ($lens['original_sku'] as $k => $v) {
                            //镜架数据
                            $dataLens[$k]['tid'] = $this->model->id;
                            $dataLens[$k]['increment_id'] = $params['synergy_order_number'] ?: '';
                            $dataLens[$k]['platform_type'] = $params['order_platform'] ?: 0;
                            $dataLens[$k]['original_name'] = $lens['original_name'][$k] ?: '';
                            $dataLens[$k]['original_sku'] = $lens['original_sku'][$k] ?: '';
                            $dataLens[$k]['original_number'] = $lens['original_number'][$k] ?: '';
                            $dataLens[$k]['change_type'] = $change_type ?: '';
                            $dataLens[$k]['recipe_type'] = $lens['recipe_type'][$k] ?: '';
                            $dataLens[$k]['lens_type'] = $lens['lens_type'][$k] ?: '';
                            $dataLens[$k]['coating_type'] = $lens['coating_type'][$k] ?: '';
                            //镜片数据
                            $dataLens[$k]['second_name'] = $lens['second_name'][$k] ?: '';
                            $dataLens[$k]['zsl']         = $lens['zsl'][$k] ?: ''; 
                            $dataLens[$k]['od_sph'] = $lens['od_sph'][$k] ?: '';
                            $dataLens[$k]['od_cyl'] = $lens['od_cyl'][$k] ?: '';
                            $dataLens[$k]['od_axis'] = $lens['od_axis'][$k] ?: '';
                            $dataLens[$k]['od_add'] = $lens['od_add'][$k] ?: '';
                            $dataLens[$k]['pd_r'] = $lens['pd_r'][$k] ?: '';
                            $dataLens[$k]['od_pv'] = $lens['od_pv'][$k] ?: '';
                            $dataLens[$k]['od_bd'] = $lens['od_bd'][$k] ?: '';
                            $dataLens[$k]['od_pv_r'] = $lens['od_pv_r'][$k] ?: '';
                            $dataLens[$k]['od_bd_r'] = $lens['od_bd_r'][$k] ?: '';
                            $dataLens[$k]['os_sph'] = $lens['os_sph'][$k] ?: '';
                            $dataLens[$k]['os_cyl'] = $lens['os_cyl'][$k] ?: '';
                            $dataLens[$k]['os_axis'] = $lens['os_axis'][$k] ?: '';
                            $dataLens[$k]['os_add'] = $lens['os_add'][$k] ?: '';
                            $dataLens[$k]['pd_l'] = $lens['pd_l'][$k] ?: '';
                            $dataLens[$k]['os_pv'] = $lens['os_pv'][$k] ?: '';
                            $dataLens[$k]['os_bd'] = $lens['os_bd'][$k] ?: '';
                            $dataLens[$k]['os_pv_r'] = $lens['os_pv_r'][$k] ?: '';
                            $dataLens[$k]['os_bd_r'] = $lens['os_bd_r'][$k] ?: '';
                           // $dataLens[$k]['options'] = serialize($recipeLens[$k]);
                            $dataLens[$k]['create_person'] = session('admin.nickname');
                            $dataLens[$k]['create_time']   = date("Y-m-d H:i:s", time());
                        }
                        (new InfoSynergyTaskChangeSku())->allowField(true)->saveAll($dataLens);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //任务分类列表
        $this->view->assign('categoryList', (new InfoSynergyTaskCategory())->getIssueList(1, 0));
        //订单平台列表
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        //关联单据类型列表
        $this->view->assign('orderType', $this->model->orderType());
        //任务级别
        $this->view->assign('prtyIdList', (new SaleAfterTask())->getPrtyIdList());
        //测试承接部门
        $this->view->assign('deptList', $this->model->testDepId());
        //测试承接人
        $this->view->assign('repList', $this->model->testRepId());
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
        if(2 == $row['synergy_status']){
            $this->error(__('The collaborative task information has been completed and cannot be edit'),'infosynergytaskmanage/info_synergy_task/index');

        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $item = isset($params['item']) ? $params['item']  : '';
            $lens = isset($params['lens']) ? $params['lens']  : '';
            //承接部门和承接人写入数据库
            if (count($params['dept_id']) > 1) {
                $params['dept_id'] = implode('+', $params['dept_id']);
            } else {
                $params['dept_id'] = $params['dept_id'][0];
            }
            if (count($params['rep_id']) > 1) {
                $params['rep_id']  = implode('+', $params['rep_id']);
            } else {
                $params['rep_id'] = $params['rep_id'][0];
            }
            // echo '<pre>';
            // var_dump($lens);
            // exit;
            //更改类型
            $change_type = $params['change_type'];
            // var_dump($change_type);
            // exit;
            unset($params['change_type']);
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
                    if ($item) {
                        
                        foreach ($item as $arr) {
                            $data = [];
                            $data['increment_id']    = $params['synergy_order_number'] ?: '';
                            $data['platform_type']   = $params['order_platform'] ?: 0;
                            $data['change_type']     = $change_type ?: 1;                                                    
                            $data['original_sku']    = !empty($arr['original_sku']) ? $arr['original_sku'] : '';
                            $data['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : '';
                            $data['change_sku']      = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data['change_number']   = !empty($arr['change_number']) ? $arr['change_number'] : '';
                            $data['create_person']   = session('admin.nickname');
                            $data['update_time']     = date("Y-m-d H:i:s", time());
                            (new InfoSynergyTaskChangeSku())->allowField(true)->isUpdate(true,['id'=>$arr['id']])->save($data);                            
                        }
                        
                    }
                    if ($lens) {
                        $dataLens = [];
                        foreach ($lens['id'] as $k=> $v) {
                            //镜架数据
                            //$dataLens[$k]['id'] = $v;
                            $dataLens['increment_id']    = $params['synergy_order_number'] ?: '';
                            $dataLens['platform_type']   = $params['order_platform'] ?: 0;  
                            $dataLens['original_name']   = $lens['original_name'][$k] ?: '';
                            $dataLens['original_sku']    = $lens['original_sku'][$k] ?: '';
                            $dataLens['original_number'] = $lens['original_number'][$k] ?: '';
                            $dataLens['change_type']     = $change_type ?: 2;
                            $dataLens['recipe_type']     = $lens['recipe_type'][$k] ?: '';
                            $dataLens['lens_type']       = $lens['lens_type'][$k] ?: '';
                            $dataLens['coating_type']    = $lens['coating_type'][$k] ?: '';
                            //镜片数据
                            $dataLens['second_name']     = $lens['second_name'][$k] ?: '';
                            $dataLens['zsl']             = $lens['zsl'][$k] ?: ''; 
                            $dataLens['od_sph']          = $lens['od_sph'][$k] ?: '';
                            $dataLens['od_cyl']          = $lens['od_cyl'][$k] ?: '';
                            $dataLens['od_axis']         = $lens['od_axis'][$k] ?: '';
                            $dataLens['od_add']          = $lens['od_add'][$k] ?: '';
                            $dataLens['pd_r']            = $lens['pd_r'][$k] ?: '';
                            $dataLens['od_pv']           = $lens['od_pv'][$k] ?: '';
                            $dataLens['od_bd']           = $lens['od_bd'][$k] ?: '';
                            $dataLens['od_pv_r']         = $lens['od_pv_r'][$k] ?: '';
                            $dataLens['od_bd_r']         = $lens['od_bd_r'][$k] ?: '';
                            $dataLens['os_sph']          = $lens['os_sph'][$k] ?: '';
                            $dataLens['os_cyl']          = $lens['os_cyl'][$k] ?: '';
                            $dataLens['os_axis']         = $lens['os_axis'][$k] ?: '';
                            $dataLens['os_add']          = $lens['os_add'][$k] ?: '';
                            $dataLens['pd_l']            = $lens['pd_l'][$k] ?: '';
                            $dataLens['os_pv']           = $lens['os_pv'][$k] ?: '';
                            $dataLens['os_bd']           = $lens['os_bd'][$k] ?: '';
                            $dataLens['os_pv_r']         = $lens['os_pv_r'][$k] ?: '';
                            $dataLens['os_bd_r']         = $lens['os_bd_r'][$k] ?: '';
                            $dataLens['create_person']   = session('admin.nickname');
                            $dataLens['update_time']     = date("Y-m-d H:i:s", time());
                            (new InfoSynergyTaskChangeSku())->allowField(true)->isUpdate(true,['id'=>$v])->save($dataLens);
                        }
                        
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['dept_id'] = explode('+', $row['dept_id']);
        $row['rep_id']  = explode('+', $row['rep_id']);
        $this->view->assign("row", $row);
        //任务分类列表
        $this->view->assign('categoryList', (new InfoSynergyTaskCategory())->getIssueList(1, 0));
        //订单平台列表
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        //关联单据类型列表
        $this->view->assign('orderType', $this->model->orderType());
        //任务级别
        $this->view->assign('prtyIdList', (new SaleAfterTask())->getPrtyIdList());
        //信息协同任务SKU信息
            //    dump((new InfoSynergyTaskChangeSku())->getChangeSkuList($row['id']));
            //    exit;
        $this->view->assign('taskChangeSku', (new InfoSynergyTaskChangeSku())->getChangeSkuList($row['id']));
        return $this->view->fetch();
    }
    /**
     * 查看
     */
    public function index()
    {
        //关联订单号 订单列表传递
        $synergy_order_number = input('synergy_order_number');
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();   
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $rep    = $this->request->get('filter');
            $addWhere = '1=1';
            if($rep != '{}'){
                $whereArr = json_decode($rep,true);
                foreach($whereArr as $key => $whereval){
                    if(($key == 'dept') && (in_array($whereval,$deptArr))){
                        $dept_id = array_search($whereval,$deptArr);
                        $addWhere  .= " AND FIND_IN_SET($dept_id,dept_id)";
                        unset($whereArr['dept']);                 
                    }elseif($key == 'dept'){
                        $addWhere  .= " AND dept_id=''";
                        unset($whereArr['dept']);
                    }
                    if(($key == 'rep') && (in_array($whereval,$repArr))){
                        $rep_id  = array_search($whereval,$repArr);
                        $addWhere .= " AND FIND_IN_SET($rep_id,rep_id)";
                        unset($whereArr['rep']);
                    }elseif($key == 'rep'){
                        $addWhere .= " AND rep_id=''";
                        unset($whereArr['rep']);
                    }
                }
                $this->request->get(['filter'=>json_encode($whereArr)]);
            }
            //exit;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['infoSynergyTaskCategory'])
                ->where($where)->where($addWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['infoSynergyTaskCategory'])
                ->where($where)->where($addWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            foreach ($list as $key => $val) {
                if ($val['dept_id']) {
                    $deptNumArr = explode('+', $val['dept_id']);
                    $list[$key]['dept'] = '';
                    foreach ($deptNumArr as $values) {
                        $list[$key]['dept'] .= $deptArr[$values] . ' ';
                    }
                }
                if ($val['rep_id']) {
                    $repNumArr = explode('+', $val['rep_id']);
                    $list[$key]['rep'] = '';
                    foreach ($repNumArr as $vals) {
                        $list[$key]['rep'] .= $repArr[$vals] . ' ';
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign('getTabList', (new SaleAfterTask())->getTabList());
        $this->view->assign('nickname', session('admin.nickname'));
        $this->view->assign('idds', session('admin.id'));
        $this->assignconfig('synergy_order_number', $synergy_order_number ?? '');
        return $this->view->fetch();
    }
    /**
     * 获取关联单的数据类型
     */
    public function getOrderType()
    {
        if ($this->request->isAjax()) {
            $json = $this->model->orderType();
            if (!$json) {
                $json = [0 => '请先添加关联单类型'];
            }
            $arrToObject = (object) ($json);
            return json($arrToObject);
        } else {
            $this->error('请求错误');
        }
        //        $json = $this->model->orderType();
        //        if(!$json){
        //            $json = [0=>'请先添加关联单类型'];
        //        }
        //        $arrToObject = (object)($json);
        //        dump(json_encode($arrToObject));
    }
    /***
     * 异步获取承接人信息
     */
    public function ajaxFindRecipient(Request $request)
    {
        if ($this->request->isAjax()) {
            $strIds = $this->request->post('arrIds');
            if (!$strIds) {
                return $this->error('没有选择承接部门,请重新尝试', '', 'error', 0);
            }
            $arrIds = explode('&', $strIds);
            $result = (new Admin())->getStaffList($arrIds);
            if (!$result) {
                return $this->error('选择这个部门没有承接的人', '', 'error', 0);
            }
            return $this->success('', '', $result, 0);
        } else {
            return $this->error('请求错误,请重新尝试', '', 'error', 0);
        }
    }
    public function detail(Request $request)
    {
        $id = $request->param('ids');
        if (!$id) {
            $this->error('参数错误，请重新尝试', 'saleaftermanage/sale_after_task');
        }
        $result = $this->model->getInfoSynergyDetail($id);
        if (!$result) {
            $this->error('任务信息不存在，请重新尝试', 'saleaftermanage/sale_after_task');
        }
        $result['problem_desc'] = strip_tags($result['problem_desc']);
        //dump($result);
        $this->view->assign('row', $result);
        $this->view->assign('categoryList', (new InfoSynergyTaskCategory())->getIssueList(1, 0));
        //订单平台列表
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        //关联单据类型列表
        $this->view->assign('orderType', $this->model->orderType());
        //任务级别
        $this->view->assign('prtyIdList', (new SaleAfterTask())->getPrtyIdList());
        $this->view->assign('taskChangeSku', (new InfoSynergyTaskChangeSku())->getChangeSkuList($result['id']));
        //订单备注表
        $this->view->assign('orderReturnRemark', (new InfoSynergyTaskRemark())->getSynergyTaskRemarkById($result['id']));
        //        $this->view->assign('orderInfo',$this->model->getOrderInfo($result['order_platform'],$result['order_number']));
        return $this->view->fetch();
    }
    /**
     * 处理完成
     */
    public function handleComplete($ids = null)
    {
        if ($this->request->isAjax()) {
            if(1 < count($ids)){
                $this->error('只能单个处理完成,不能批量处理完成');
            }
            $row = $this->model->get($ids);
            if(2 <= $row['synergy_status']){
                $this->error('此状态不能处理完成');
            }
            $map['id'] = ['in', $ids];
            $data['synergy_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                //如果是修改镜架的话更改库存
                if (12 == $row['synergy_task_id']) { //执行更改镜架的逻辑
                    (new Inventory())->changeFrame($row['id'], $row['order_platform'], $row['synergy_order_number']);
                }elseif(14 == $row['synergy_task_id']){ //执行取消订单的逻辑
                    (new Inventory())->cancelOrder($row['id'], $row['order_platform'], $row['synergy_order_number']);
                }
                $this->success('操作成功');
            } else {
                $this->error('操作失败,请重新尝试');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 处理协同任务
     */
    public function handle_task($ids=null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if(2 <= $row['synergy_status']){
            $this->error(__('The collaborative task information has been completed and cannot be processed'),'infosynergytaskmanage/info_synergy_task/index');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $tid    = $params['id'];
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
                    $params['synergy_status'] =1;
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
                    if (!empty($params['remark_record'])) {
                        $dataRecord = [];
                        $dataRecord['tid'] = $tid;
                        $dataRecord['remark_record'] = strip_tags($params['remark_record']);
                        $dataRecord['create_person'] = session('admin.username');
                        $dataRecord['create_time']   = date("Y-m-d H:i:s", time());
                        (new InfoSynergyTaskRemark())->allowField(true)->save($dataRecord);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['dept_id'] = explode('+', $row['dept_id']);
        $row['rep_id']  = explode('+', $row['rep_id']);
        $row['problem_desc'] = strip_tags($row['problem_desc']);
        $this->view->assign("row", $row);
        //任务分类列表
        $this->view->assign('categoryList', (new InfoSynergyTaskCategory())->getIssueList(1, 0));
        //订单平台列表
        $this->view->assign("orderPlatformList", (new MagentoPlatform())->getOrderPlatformList());
        //关联单据类型列表
        $this->view->assign('orderType', $this->model->orderType());
        //任务级别
        $this->view->assign('prtyIdList', (new SaleAfterTask())->getPrtyIdList());
        //信息协同任务SKU信息
        //        dump((new InfoSynergyTaskChangeSku())->getChangeSkuList($row['id']));
        //        exit;
        $this->view->assign('taskChangeSku', (new InfoSynergyTaskChangeSku())->getChangeSkuList($row['id']));
        return $this->view->fetch();

    }
    /**
     * 取消
     */
    public function closed($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,synergy_status')->select();
            foreach ($row as $v) {
                if ( 0 != $v['synergy_status']) {
                    $this->error('只有新建状态才能操作！！');
                }
            }
            $data['synergy_status'] = 3;
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


}
