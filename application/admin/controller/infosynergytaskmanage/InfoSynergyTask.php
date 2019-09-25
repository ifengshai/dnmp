<?php

namespace app\admin\controller\infosynergytaskmanage;

use app\admin\model\Admin;
use app\common\controller\Backend;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory;
use app\admin\model\platformmanage\ManagtoPlatform;
use app\admin\model\saleaftermanage\SaleAfterTask;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskRemark;
use app\admin\model\AuthGroup;
use think\Db;
use think\Request;

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
            //            dump($params);
            //            //exit;
            $item = isset($params['item']) ? $params['item']  : '';
            $lens = isset($params['lens']) ? $params['lens']  : '';
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
                        foreach ($item as $arr) {
                            $data = [];
                            $data['tid'] = $this->model->id;
                            $data['original_sku'] = !empty($arr['original_sku']) ? $arr['original_sku'] : '';
                            $data['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : '';
                            $data['change_sku'] = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data['change_number'] = !empty($arr['change_number']) ? $arr['change_number'] : '';
                            $data['create_person'] = session('admin.nickname');
                            $data['create_time']     = date("Y-m-d H:i:s", time());
                            (new InfoSynergyTaskChangeSku())->allowField(true)->save($data);
                        }
                    }
                    if ($lens) {
                        $dataLens = [];
                        $recipeLens = [];
                        foreach ($lens['original_sku'] as $k => $v) {
                            //镜架数据
                            $dataLens[$k]['tid'] = $this->model->id;
                            $dataLens[$k]['original_name'] = $lens['original_name'][$k];
                            $dataLens[$k]['original_sku'] = $lens['original_sku'][$k];
                            $dataLens[$k]['original_number'] = $lens['original_number'][$k];
                            $dataLens[$k]['change_type'] = 2;
                            $dataLens[$k]['recipe_type'] = $lens['recipe_type'][$k];
                            $dataLens[$k]['lens_type'] = $lens['lens_type'][$k];
                            $dataLens[$k]['coating_type'] = $lens['coating_type'][$k];
                            //镜片数据
                            $recipeLens[$k]['od_sph'] = $lens['od_sph'][$k];
                            $recipeLens[$k]['od_cyl'] = $lens['od_cyl'][$k];
                            $recipeLens[$k]['od_axis'] = $lens['od_axis'][$k];
                            $recipeLens[$k]['od_add'] = $lens['od_add'][$k];
                            $recipeLens[$k]['pd_r'] = $lens['pd_r'][$k];
                            $recipeLens[$k]['od_pv'] = $lens['od_pv'][$k];
                            $recipeLens[$k]['od_bd'] = $lens['od_bd'][$k];
                            $recipeLens[$k]['od_pv_r'] = $lens['od_pv_r'][$k];
                            $recipeLens[$k]['od_bd_r'] = $lens['od_bd_r'][$k];
                            $recipeLens[$k]['os_sph'] = $lens['os_sph'][$k];
                            $recipeLens[$k]['os_cyl'] = $lens['os_cyl'][$k];
                            $recipeLens[$k]['os_axis'] = $lens['os_axis'][$k];
                            $recipeLens[$k]['os_add'] = $lens['os_add'][$k];
                            $recipeLens[$k]['pd_l'] = $lens['pd_l'][$k];
                            $recipeLens[$k]['os_pv'] = $lens['os_pv'][$k];
                            $recipeLens[$k]['os_bd'] = $lens['os_bd'][$k];
                            $recipeLens[$k]['os_pv_r'] = $lens['os_pv_r'][$k];
                            $recipeLens[$k]['os_bd_r'] = $lens['os_bd_r'][$k];
                            $dataLens[$k]['options'] = serialize($recipeLens[$k]);
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
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
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
    /**
     * 编辑
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
            $tid    = $params['id'];
            unset($params['id']);
            $item = isset($params['item']) ? $params['item']  : '';
            $lens = isset($params['lens']) ? $params['lens']  : '';
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
                    if (!empty($params['remark_record'])) {
                        $dataRecord = [];
                        $dataRecord['tid'] = $tid;
                        $dataRecord['remark_record'] = strip_tags($params['remark_record']);
                        $dataRecord['create_person'] = session('admin.username');
                        $dataRecord['create_time']   = date("Y-m-d H:i:s", time());
                        (new InfoSynergyTaskRemark())->allowField(true)->save($dataRecord);
                    }
                    if ($item) {
                        foreach ($item as $arr) {
                            $data = [];
                            //$data['id'] = $arr['id'];
                            $data['original_sku'] = !empty($arr['original_sku']) ? $arr['original_sku'] : '';
                            $data['original_number'] = !empty($arr['original_number']) ? $arr['original_number'] : '';
                            $data['change_sku'] = !empty($arr['change_sku']) ? $arr['change_sku'] : '';
                            $data['change_number'] = !empty($arr['change_number']) ? $arr['change_number'] : '';
                            $data['create_person'] = session('admin.nickname');
                            $data['update_time']     = date("Y-m-d H:i:s", time());
                            (new InfoSynergyTaskChangeSku())->allowField(true)->where('id', $arr['id'])->save($data, ['id', $arr['id']]);
                        }
                    }
                    if ($lens) {
                        $dataLens = [];
                        $recipeLens = [];
                        foreach ($lens['id'] as $k => $v) {
                            //镜架数据
                            $dataLens[$k]['id'] = $v;
                            $dataLens[$k]['original_name'] = $lens['original_name'][$k];
                            $dataLens[$k]['original_sku'] = $lens['original_sku'][$k];
                            $dataLens[$k]['original_number'] = $lens['original_number'][$k];
                            $dataLens[$k]['change_type'] = 2;
                            $dataLens[$k]['recipe_type'] = $lens['recipe_type'][$k];
                            $dataLens[$k]['lens_type'] = $lens['lens_type'][$k];
                            $dataLens[$k]['coating_type'] = $lens['coating_type'][$k];
                            //镜片数据
                            $recipeLens[$k]['od_sph'] = $lens['od_sph'][$k];
                            $recipeLens[$k]['od_cyl'] = $lens['od_cyl'][$k];
                            $recipeLens[$k]['od_axis'] = $lens['od_axis'][$k];
                            $recipeLens[$k]['od_add'] = $lens['od_add'][$k];
                            $recipeLens[$k]['pd_r'] = $lens['pd_r'][$k];
                            $recipeLens[$k]['od_pv'] = $lens['od_pv'][$k];
                            $recipeLens[$k]['od_bd'] = $lens['od_bd'][$k];
                            $recipeLens[$k]['od_pv_r'] = $lens['od_pv_r'][$k];
                            $recipeLens[$k]['od_bd_r'] = $lens['od_bd_r'][$k];
                            $recipeLens[$k]['os_sph'] = $lens['os_sph'][$k];
                            $recipeLens[$k]['os_cyl'] = $lens['os_cyl'][$k];
                            $recipeLens[$k]['os_axis'] = $lens['os_axis'][$k];
                            $recipeLens[$k]['os_add'] = $lens['os_add'][$k];
                            $recipeLens[$k]['pd_l'] = $lens['pd_l'][$k];
                            $recipeLens[$k]['os_pv'] = $lens['os_pv'][$k];
                            $recipeLens[$k]['os_bd'] = $lens['os_bd'][$k];
                            $recipeLens[$k]['os_pv_r'] = $lens['os_pv_r'][$k];
                            $recipeLens[$k]['os_bd_r'] = $lens['os_bd_r'][$k];
                            $dataLens[$k]['options'] = serialize($recipeLens[$k]);
                            $dataLens[$k]['create_person'] = session('admin.nickname');
                            $dataLens[$k]['update_time']   = date("Y-m-d H:i:s", time());
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
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
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
            $deptArr = (new AuthGroup())->getAllGroup();
            $repArr  = (new Admin())->getAllStaff();
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
            $this->error('参数错误，请重新尝试', '/admin/saleaftermanage/sale_after_task/index');
        }
        $result = $this->model->getInfoSynergyDetail($id);
        if (!$result) {
            $this->error('任务信息不存在，请重新尝试', '/admin/saleaftermanage/sale_after_task/index');
        }
        //dump($result);
        $this->view->assign('row', $result);
        $this->view->assign('categoryList', (new InfoSynergyTaskCategory())->getIssueList(1, 0));
        //订单平台列表
        $this->view->assign("orderPlatformList", (new ManagtoPlatform())->getOrderPlatformList());
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
}
