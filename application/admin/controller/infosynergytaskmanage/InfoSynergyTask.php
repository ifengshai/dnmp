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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            if(0 == $params['synergy_order_id']){
                $this->error(__('请选择关联单据类型'));    
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
                if(0<$params['refund_money']){
                    $params['is_refund'] = 2;
                }else{
                    $params['is_refund'] = 1;
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
                            $data[$keys]['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : 0;
                            $data[$keys]['change_sku']      = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data[$keys]['change_number']   = !empty($arr['change_number']) ? $arr['change_number'] : 0;
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
            if(0<$params['refund_money']){
                $params['is_refund'] = 2;
            }else{
                $params['is_refund'] = 1;
            }
            //更改类型
            $change_type = $params['change_type'];
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
                    (new InfoSynergyTaskChangeSku())->where(['tid'=>$row['id']])->delete();
                    if ($item) {
                        $data = [];
                        foreach ($item as $keys => $arr) {
                            $data[$keys]['tid']      = $row['id'];    
                            $data[$keys]['increment_id']    = $params['synergy_order_number'] ?: '';
                            $data[$keys]['platform_type']   = $params['order_platform'] ?: 0;
                            $data[$keys]['change_type']     = $change_type ?: 1;                                                    
                            $data[$keys]['original_sku']    = !empty($arr['original_sku']) ? $arr['original_sku'] : '';
                            $data[$keys]['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : 0;
                            $data[$keys]['change_sku']      = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data[$keys]['change_number']   = !empty($arr['change_number']) ? $arr['change_number'] : 0;
                            $data[$keys]['create_person']   = session('admin.nickname');
                            $data[$keys]['update_time']     = date("Y-m-d H:i:s", time());
                        }
                        (new InfoSynergyTaskChangeSku())->allowField(true)->saveAll($data); 
                        
                    }
                    if ($lens) {
                        $dataLens = [];
                        foreach ($lens['id'] as $k=> $v) {
                            //镜架数据
                            $dataLens[$k]['tid'] = $row['id'];
                            $dataLens[$k]['increment_id']    = $params['synergy_order_number'] ?: '';
                            $dataLens[$k]['platform_type']   = $params['order_platform'] ?: 0;  
                            $dataLens[$k]['original_name']   = $lens['original_name'][$k] ?: '';
                            $dataLens[$k]['original_sku']    = $lens['original_sku'][$k] ?: '';
                            $dataLens[$k]['original_number'] = $lens['original_number'][$k] ?: '';
                            $dataLens[$k]['change_type']     = $change_type ?: 2;
                            $dataLens[$k]['recipe_type']     = $lens['recipe_type'][$k] ?: '';
                            $dataLens[$k]['lens_type']       = $lens['lens_type'][$k] ?: '';
                            $dataLens[$k]['coating_type']    = $lens['coating_type'][$k] ?: '';
                            //镜片数据
                            $dataLens[$k]['second_name']     = $lens['second_name'][$k] ?: '';
                            $dataLens[$k]['zsl']             = $lens['zsl'][$k] ?: ''; 
                            $dataLens[$k]['od_sph']          = $lens['od_sph'][$k] ?: '';
                            $dataLens[$k]['od_cyl']          = $lens['od_cyl'][$k] ?: '';
                            $dataLens[$k]['od_axis']         = $lens['od_axis'][$k] ?: '';
                            $dataLens[$k]['od_add']          = $lens['od_add'][$k] ?: '';
                            $dataLens[$k]['pd_r']            = $lens['pd_r'][$k] ?: '';
                            $dataLens[$k]['od_pv']           = $lens['od_pv'][$k] ?: '';
                            $dataLens[$k]['od_bd']           = $lens['od_bd'][$k] ?: '';
                            $dataLens[$k]['od_pv_r']         = $lens['od_pv_r'][$k] ?: '';
                            $dataLens[$k]['od_bd_r']         = $lens['od_bd_r'][$k] ?: '';
                            $dataLens[$k]['os_sph']          = $lens['os_sph'][$k] ?: '';
                            $dataLens[$k]['os_cyl']          = $lens['os_cyl'][$k] ?: '';
                            $dataLens[$k]['os_axis']         = $lens['os_axis'][$k] ?: '';
                            $dataLens[$k]['os_add']          = $lens['os_add'][$k] ?: '';
                            $dataLens[$k]['pd_l']            = $lens['pd_l'][$k] ?: '';
                            $dataLens[$k]['os_pv']           = $lens['os_pv'][$k] ?: '';
                            $dataLens[$k]['os_bd']           = $lens['os_bd'][$k] ?: '';
                            $dataLens[$k]['os_pv_r']         = $lens['os_pv_r'][$k] ?: '';
                            $dataLens[$k]['os_bd_r']         = $lens['os_bd_r'][$k] ?: '';
                            $dataLens[$k]['create_person']   = session('admin.nickname');
                            $dataLens[$k]['update_time']     = date("Y-m-d H:i:s", time());  
                        }
                        (new InfoSynergyTaskChangeSku())->allowField(true)->saveAll($dataLens);
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
                ->with(['infosynergytaskcategory'])
                ->where($where)->where($addWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['infosynergytaskcategory'])
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
        $result['dept_id'] = explode('+', $result['dept_id']);
        $result['rep_id']  = explode('+', $result['rep_id']);
        $result['problem_desc'] = strip_tags($result['problem_desc']);
        //dump($result);
        $this->view->assign('row', $result);
        $categoryName = ((new InfoSynergyTaskCategory())->findTaskCategory($result['synergy_task_id']));
        $categoryName = !empty($categoryName) ? $categoryName :'无此分类';
        $this->view->assign('categoryName',$categoryName);
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
            $data['complete_time']   = date("Y-m-d H:i:s",time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                //如果是修改镜架的话更改库存
                if (12 == $row['synergy_task_id']) { //执行更改镜架的逻辑
                    (new Inventory())->changeFrame($row['id'], $row['order_platform'], $row['synergy_order_number']);
                }elseif((14 == $row['synergy_task_id']) || (37 == $row['synergy_task_id']) || (38 == $row['synergy_task_id'])){ //执行取消订单的逻辑
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
            if ($params) {
                $change_type = $params['change_type'];
                unset($params['change_type']);
                $tid    = $params['id'];
                unset($params['id']);
                $item = isset($params['item']) ? $params['item']  : '';
                $lens = isset($params['lens']) ? $params['lens']  : '';
                $synergy_status =  $params['synergy_status'] ?: 1;
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
                if(0<$params['refund_money']){
                    $params['is_refund'] = 2;
                }else{
                    $params['is_refund'] = 1;
                }               
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    if(2 == $synergy_status){
                        $params['complete_time']   = date("Y-m-d H:i:s",time());
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
                    (new InfoSynergyTaskChangeSku())->where(['tid'=>$tid])->delete();
                    if ($item) {
                        $data = [];
                        foreach ($item as $keys => $arr) {
                            $data[$keys]['tid']      = $row['id'];    
                            $data[$keys]['increment_id']    = $params['synergy_order_number'] ?: '';
                            $data[$keys]['platform_type']   = $params['order_platform'] ?: 0;
                            $data[$keys]['change_type']     = $change_type ?: 1;                                                    
                            $data[$keys]['original_sku']    = !empty($arr['original_sku']) ? $arr['original_sku'] : '';
                            $data[$keys]['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : 0;
                            $data[$keys]['change_sku']      = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data[$keys]['change_number']   = !empty($arr['change_number']) ? $arr['change_number'] : 0;
                            $data[$keys]['create_person']   = session('admin.nickname');
                            $data[$keys]['update_time']     = date("Y-m-d H:i:s", time());
                        }
                        (new InfoSynergyTaskChangeSku())->allowField(true)->saveAll($data); 
                        
                    }
                    if ($lens) {
                        $dataLens = [];
                        foreach ($lens['id'] as $k=> $v) {
                            //镜架数据
                            $dataLens[$k]['tid'] = $row['id'];
                            $dataLens[$k]['increment_id']    = $params['synergy_order_number'] ?: '';
                            $dataLens[$k]['platform_type']   = $params['order_platform'] ?: 0;  
                            $dataLens[$k]['original_name']   = $lens['original_name'][$k] ?: '';
                            $dataLens[$k]['original_sku']    = $lens['original_sku'][$k] ?: '';
                            $dataLens[$k]['original_number'] = $lens['original_number'][$k] ?: '';
                            $dataLens[$k]['change_type']     = $change_type ?: 2;
                            $dataLens[$k]['recipe_type']     = $lens['recipe_type'][$k] ?: '';
                            $dataLens[$k]['lens_type']       = $lens['lens_type'][$k] ?: '';
                            $dataLens[$k]['coating_type']    = $lens['coating_type'][$k] ?: '';
                            //镜片数据
                            $dataLens[$k]['second_name']     = $lens['second_name'][$k] ?: '';
                            $dataLens[$k]['zsl']             = $lens['zsl'][$k] ?: ''; 
                            $dataLens[$k]['od_sph']          = $lens['od_sph'][$k] ?: '';
                            $dataLens[$k]['od_cyl']          = $lens['od_cyl'][$k] ?: '';
                            $dataLens[$k]['od_axis']         = $lens['od_axis'][$k] ?: '';
                            $dataLens[$k]['od_add']          = $lens['od_add'][$k] ?: '';
                            $dataLens[$k]['pd_r']            = $lens['pd_r'][$k] ?: '';
                            $dataLens[$k]['od_pv']           = $lens['od_pv'][$k] ?: '';
                            $dataLens[$k]['od_bd']           = $lens['od_bd'][$k] ?: '';
                            $dataLens[$k]['od_pv_r']         = $lens['od_pv_r'][$k] ?: '';
                            $dataLens[$k]['od_bd_r']         = $lens['od_bd_r'][$k] ?: '';
                            $dataLens[$k]['os_sph']          = $lens['os_sph'][$k] ?: '';
                            $dataLens[$k]['os_cyl']          = $lens['os_cyl'][$k] ?: '';
                            $dataLens[$k]['os_axis']         = $lens['os_axis'][$k] ?: '';
                            $dataLens[$k]['os_add']          = $lens['os_add'][$k] ?: '';
                            $dataLens[$k]['pd_l']            = $lens['pd_l'][$k] ?: '';
                            $dataLens[$k]['os_pv']           = $lens['os_pv'][$k] ?: '';
                            $dataLens[$k]['os_bd']           = $lens['os_bd'][$k] ?: '';
                            $dataLens[$k]['os_pv_r']         = $lens['os_pv_r'][$k] ?: '';
                            $dataLens[$k]['os_bd_r']         = $lens['os_bd_r'][$k] ?: '';
                            $dataLens[$k]['create_person']   = session('admin.nickname');
                            $dataLens[$k]['update_time']     = date("Y-m-d H:i:s", time());  
                        }
                        (new InfoSynergyTaskChangeSku())->allowField(true)->saveAll($dataLens);
                    }                    
                    if (!empty($params['remark_record'])) {
                        $dataRecord = [];
                        $dataRecord['tid'] = $tid;
                        $dataRecord['remark_record'] = strip_tags($params['remark_record']);
                        $dataRecord['create_person'] = session('admin.nickname');
                        $dataRecord['create_time']   = date("Y-m-d H:i:s", time());
                        (new InfoSynergyTaskRemark())->allowField(true)->save($dataRecord);
                    }
                    if(2 == $synergy_status){
                        //如果是修改镜架的话更改库存
                        if (12 == $row['synergy_task_id']) { //执行更改镜架的逻辑
                            (new Inventory())->changeFrame($row['id'], $row['order_platform'], $row['synergy_order_number']);
                        }elseif((14 == $row['synergy_task_id']) || (37 == $row['synergy_task_id']) || (38 == $row['synergy_task_id'])){ //执行取消订单的逻辑
                            (new Inventory())->cancelOrder($row['id'], $row['order_platform'], $row['synergy_order_number']);
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
        $this->view->assign('orderReturnRemark', (new InfoSynergyTaskRemark())->getSynergyTaskRemarkById($row['id']));
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
    //批量导出功能
    //批量导出xls
/*     public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere.= " AND info_synergy_task.id IN ({$ids})";
        }
        list($where) = $this->buildparams();
        $list = $this->model
        ->with(['infosynergytaskcategory'])
        ->where($where)->where($addWhere)
        ->select();
        $repArr  = (new Admin())->getAllStaff();
        $list = collection($list)->toArray();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "信息协同任务单号")
            ->setCellValue("B1", "关联单据类型")
            ->setCellValue("C1", "关联单号");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "平台类型")
            ->setCellValue("E1", "任务状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "是否含有退款")
            ->setCellValue("G1", "退款金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "退款方式")
            ->setCellValue("I1", "承接人")
            ->setCellValue("J1", "任务优先级");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "任务分类")
            ->setCellValue("L1", "问题描述")
            ->setCellValue("M1", "补差价订单号")
            ->setCellValue("N1", "补发订单号")
            ->setCellValue("O1","订单SKU")
            ->setCellValue("P1", "创建人")
            ->setCellValue("Q1", "创建时间")
            ->setCellValue("R1", "完成时间");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('信息协同任务数据');
        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['synergy_number']);
            switch($value['synergy_order_id']){
                case 1:
                $value['synergy_order_id'] = '无';
                break;
                case 2:
                $value['synergy_order_id'] = '订单';
                break;
                case 3:
                $value['synergy_order_id'] = '采购单';
                break;
                case 4:
                $value['synergy_order_id'] = '质检单';
                break;
                case 5:
                $value['synergy_order_id'] = '入库单';
                break;
                case 6:
                $value['synergy_order_id'] = '出库单';
                break;
                case 7:
                $value['synergy_order_id'] = '库存盘点单';
                break;            
                default:
                $value['synergy_order_id'] = '请选择';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['synergy_order_id']);
            switch($value['order_platform']){
                case 2:
                $value['order_platform'] = 'voogueme';
                break;
                case 3:
                $value['order_platform'] = 'nihao';
                break;
                case 4:
                $value['order_platform'] = 'amazon';
                break;
                case 5:
                $value['order_platform'] = 'wesee';
                break;
                default:
                $value['order_platform'] = 'zeelool';
                break;            
            }
            switch($value['synergy_status']){
                case 1:
                $value['synergy_status'] = '处理中';
                break;
                case 2:
                $value['synergy_status'] = '处理完成';
                break;
                case 3:
                $value['synergy_status'] = '取消';
                default:
                $value['synergy_status'] = '未处理';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['synergy_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['order_platform']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['synergy_status']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['is_refund'] == 1 ? '无' : '有');
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['refund_money']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['refund_way']);
            if ($value['rep_id']) {
                $repNumArr = explode('+', $value['rep_id']);
                $value['rep'] = '';
                foreach ($repNumArr as $vals) {
                    $value['rep'] .= $repArr[$vals] . ' ';
                }
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['rep']);
            }else{
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['rep_id']);
            }
            switch($value['prty_id']){
                case 2:
                $value['prty_id'] = '中级';
                break;
                case 3:
                $value['prty_id'] = '低级';
                break;
                default:
                $value['prty_id'] = '高级';
                break;        

            }
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['prty_id']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['infosynergytaskcategory']['name']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['problem_desc']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['make_up_price_order']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['replacement_order']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['order_skus']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['complete_time']);


        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(100);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:P' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
       

        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '信息协同数据' . date("YmdHis", time());;
        // dump($spreadsheet);

        // if (!$spreadsheet) return false;
        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    } */
	/***
	 **任务处理完成之后添加备注功能
	 */
	public function add_remark($ids=null)
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
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);                                 
				if (!empty($params['remark_record'])) {
					$dataRecord = [];
					$dataRecord['tid'] = $row['id'];
					$dataRecord['remark_record'] = strip_tags($params['remark_record']);
					$dataRecord['create_person'] = session('admin.nickname');
					$dataRecord['create_time']   = date("Y-m-d H:i:s", time());
					(new InfoSynergyTaskRemark())->allowField(true)->save($dataRecord);
				}
				$this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
		$this->view->assign('orderReturnRemark', (new InfoSynergyTaskRemark())->getSynergyTaskRemarkById($row['id']));
        return $this->view->fetch();		
	}
	public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere.= " AND info_synergy_task.id IN ({$ids})";
        }
        list($where) = $this->buildparams();
        $list = $this->model
        ->with(['infosynergytaskcategory'])
        ->where($where)->where($addWhere)
        ->select();
        $repArr  = (new Admin())->getAllStaff();
        $list = collection($list)->toArray();
		if(!$list){
			return false;
		}
		$arr = [];
		foreach($list as $keys => $vals){
			$arr[] = $vals['id'];
		}
			$info = (new InfoSynergyTaskRemark())->fetchRelevanceRecord($arr);
		if($info){
			$info = collection($info)->toArray();
		}else{
			$info = [];
		}
		
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "信息协同任务单号")
            ->setCellValue("B1", "关联单据类型")
            ->setCellValue("C1", "关联单号");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "平台类型")
            ->setCellValue("E1", "任务状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "是否含有退款")
            ->setCellValue("G1", "退款金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "退款方式")
            ->setCellValue("I1", "承接人")
            ->setCellValue("J1", "任务优先级");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "任务分类")
            ->setCellValue("L1", "问题描述")
            ->setCellValue("M1", "补差价订单号")
            ->setCellValue("N1", "补发订单号")
            ->setCellValue("O1","订单SKU")
            ->setCellValue("P1", "创建人")
            ->setCellValue("Q1", "创建时间")
            ->setCellValue("R1", "完成时间")
			->setCellValue("S1","处理备注");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('信息协同任务数据');
        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['synergy_number']);
            switch($value['synergy_order_id']){
                case 1:
                $value['synergy_order_id'] = '无';
                break;
                case 2:
                $value['synergy_order_id'] = '订单';
                break;
                case 3:
                $value['synergy_order_id'] = '采购单';
                break;
                case 4:
                $value['synergy_order_id'] = '质检单';
                break;
                case 5:
                $value['synergy_order_id'] = '入库单';
                break;
                case 6:
                $value['synergy_order_id'] = '出库单';
                break;
                case 7:
                $value['synergy_order_id'] = '库存盘点单';
                break;            
                default:
                $value['synergy_order_id'] = '请选择';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['synergy_order_id']);
            switch($value['order_platform']){
                case 2:
                $value['order_platform'] = 'voogueme';
                break;
                case 3:
                $value['order_platform'] = 'nihao';
                break;
                case 4:
                $value['order_platform'] = 'amazon';
                break;
                case 5:
                $value['order_platform'] = 'wesee';
                break;
                default:
                $value['order_platform'] = 'zeelool';
                break;            
            }
            switch($value['synergy_status']){
                case 1:
                $value['synergy_status'] = '处理中';
                break;
                case 2:
                $value['synergy_status'] = '处理完成';
                break;
                case 3:
                $value['synergy_status'] = '取消';
                default:
                $value['synergy_status'] = '未处理';
                break;            
            }
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['synergy_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['order_platform']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['synergy_status']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['is_refund'] == 1 ? '无' : '有');
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['refund_money']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['refund_way']);
            if ($value['rep_id']) {
                $repNumArr = explode('+', $value['rep_id']);
                $value['rep'] = '';
                foreach ($repNumArr as $vals) {
                    $value['rep'] .= $repArr[$vals] . ' ';
                }
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['rep']);
            }else{
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['rep_id']);
            }
            switch($value['prty_id']){
                case 2:
                $value['prty_id'] = '中级';
                break;
                case 3:
                $value['prty_id'] = '低级';
                break;
                default:
                $value['prty_id'] = '高级';
                break;        

            }
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['prty_id']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['infosynergytaskcategory']['name']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['problem_desc']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['make_up_price_order']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['replacement_order']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['order_skus']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['complete_time']);
			if(array_key_exists($value['id'],$info)){
				$value['handle_result'] = $info[$value['id']];
				$spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['handle_result']);
			}else{
				$spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), '');
			}



        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(100);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(200);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:P' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
       

        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '信息协同数据' . date("YmdHis", time());;
        // dump($spreadsheet);

        // if (!$spreadsheet) return false;
        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }	

}
