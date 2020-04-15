<?php

namespace app\admin\controller\saleaftermanage;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\admin\model\AuthGroupAccess;
use think\exception\PDOException;
use think\exception\ValidateException;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;

/**
 * 售后工单列管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrderList extends Backend
{

    /**
     * WorkOrderList模型对象
     * @var \app\admin\model\saleaftermanage\WorkOrderList
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderList;
        $this->step = new \app\admin\model\saleaftermanage\WorkOrderMeasure;
        $this->view->assign('step', config('workorder.step')); //措施
        $this->assignconfig('workorder', config('workorder')); //JS专用，整个配置文件

        $this->view->assign('check_coupon', config('workorder.check_coupon')); //不需要审核的优惠券
        $this->view->assign('need_check_coupon', config('workorder.need_check_coupon')); //需要审核的优惠券

        //获取所有的国家
        $country = json_decode(file_get_contents('assets/js/country.js'), true);
        $this->view->assign('country', $country);        
        $this->recept = new \app\admin\model\saleaftermanage\WorkOrderRecept;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    //根据主记录id，获取措施相关信息
    public function sel_order_recept($id){
        $step = $this->step->where('work_id',$id)->select();
        $step_arr = collection($step)->toArray();
        foreach ($step_arr as $k => $v){
            $recept = $this->recept->where('measure_id',$v['id'])->select();
            $recept_arr = collection($recept)->toArray();
            $step_arr[$k]['recept'] = $recept_arr;
        }
        return $step_arr;
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
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $admin = new \app\admin\model\Admin();
            $user_list = $admin->where('status', 'normal')->column('nickname', 'id');
            $user_list = collection($user_list)->toArray();



            foreach ($list as $k => $v){
                if($v['work_type'] == 1){
                    $list[$k]['work_type_str'] = '客服工单';
                } else {
                    $list[$k]['work_type_str'] = '仓库工单';
                }

                if ($v['is_check'] == 1) {
                    $list[$k]['assign_user_name'] = $user_list[$v['assign_user_id']];
                }

                $list[$k]['step_num'] = $this->sel_order_recept($v['id']);

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
                    //dump($params);die;
                    //判断工单类型 1客服 2仓库
                    if ($params['work_type'] == 1) {
                        $params['problem_type_content'] = config('workorder.customer_problem_type')[$params['problem_type_id']];
                    } elseif ($params['work_type'] == 2) {
                        $params['problem_type_content'] = config('workorder.warehouse_problem_type')[$params['problem_type_id']];
                        $params['after_user_id'] = config('workorder.copy_group'); //经手人
                    }


                    //判断审核人
                    if ($params['is_check'] == 1 || $params['need_coupon_id']) {
                        /**
                         * 1、退款金额大于30 经理审核
                         * 2、赠品数量大于1 经理审核
                         * 3、补发数量大于1 经理审核
                         * 4、优惠券等于100% 经理审核  50%主管审核 固定额度无需审核
                         */
                        $coupon = config('workorder.need_check_coupon')[$params['need_coupon_id']]['sum'];
                        if ($params['refund_money'] > 30 || array_sum($params['replacement']['original_number']) > 1 || $coupon == 100) {
                            //客服经理
                            $params['assign_user_id'] = config('workorder.customer_manager');
                        } else {
                            //创建人对应主管
                            $params['assign_user_id'] = array_search(session('admin.id'), config('workorder.kefumanage'));
                        }
                    }

                    //如果积分大于200需要审核
                    if ($params['integral'] > 200) {
                        //需要审核
                        $params['is_check'] = 1;
                        //创建人对应主管
                        $params['assign_user_id'] = array_search(session('admin.id'), config('workorder.kefumanage'));
                    }

                    $params['create_user_name'] = session('admin.nickname');
                    $params['create_user_id'] = session('admin.id');
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['order_sku'] = implode(',', $params['order_sku']);

                    $result = $this->model->allowField(true)->save($params);
                    if (false === $result) {
                        throw new Exception("添加失败！！");
                    }
                    //修改镜架操作
                    $this->model->changeLens($params,$this->model->getLastInsID());
                    //循环插入措施
                    if (count(array_filter($params['measure_choose_id'])) > 0) {

                        //措施
                        $measureList = [];
                        foreach ($params['measure_choose_id'] as $k => $v) {
                            $measureList[$k]['work_id'] = $this->model->id;
                            $measureList[$k]['measure_choose_id'] = $v;
                            $measureList[$k]['measure_content'] = config('workorder.step')[$v];
                            $measureList[$k]['create_time'] = date('Y-m-d H:i:s');

                            //根据措施读取承接组、承接人 默认是客服问题组配置
                            $appoint_ids = $params['order_recept']['appoint_ids'][$v];
                            $appoint_users = $params['order_recept']['appoint_users'][$v];
                            $appoint_group = $params['order_recept']['appoint_group'][$v];
                            //循环插入承接人
                            $appointList = [];
                            foreach ($appoint_ids as $key => $val) {
                                $appointList[$key]['work_id'] = $this->model->id;
                                $appointList[$key]['measure_id'] = $v;
                                //如果没有承接人 默认为创建人
                                if ($val == 'undefined') {
                                    $appointList[$key]['recept_group_id'] = array_search(session('admin.id'), config('workorder.kefumanage'));
                                    $appointList[$key]['recept_person_id'] = session('admin.id');
                                    $appointList[$key]['recept_person'] = session('admin.nickname');
                                } else {

                                    $appointList[$key]['recept_group_id'] = $appoint_group[$key];
                                    $appointList[$key]['recept_person_id'] = $val;
                                    $appointList[$key]['recept_person'] = $appoint_users[$key];
                                }

                                $appointList[$key]['create_time'] = date('Y-m-d H:i:s');
                            }
                            $receptRes = $this->recept->saveAll($appointList);
                            if (false === $receptRes) {
                                throw new Exception("添加失败！！");
                            }
                        }

                        $res = $this->step->saveAll($measureList);
                        if (false === $res) {
                            throw new Exception("添加失败！！");
                        }
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //获取用户ID和所在权限组
        $userId = session('admin.id');
        $userGroupAccess = AuthGroupAccess::where(['uid' => $userId])->column('group_id');
        $warehouseArr = config('workorder.warehouse_department_rule');
        $checkIsWarehouse = array_intersect($userGroupAccess, $warehouseArr);
        if (!empty($checkIsWarehouse)) {
            $this->view->assign('work_type', 2);
            $this->assignconfig('work_type', 2);
            $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型       
        } else {
            $this->view->assign('work_type', 1);
            $this->assignconfig('work_type', 1);
            $this->view->assign('problem_type', config('workorder.customer_problem_type')); //客服问题类型
        }

        //查询用户id对应姓名
        $admin = new \app\admin\model\Admin();
        $users = $admin->where('status', 'normal')->column('nickname', 'id');
        $this->assignconfig('users', $users); //返回用户
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     *
     * @Description
     * @author lsw
     * @since 2020/04/14 15:00:19 
     * @param [type] $ids
     * @return void
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['create_user_id'] != session('admin.id')){
            return $this->error(__('非本人创建不能编辑'));
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
                if($params['order_sku']){
                    $params['order_sku'] = implode(',',$params['order_sku']);
                }
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
        if (1 == $row->work_type) { //判断工单类型，客服工单
            $this->view->assign('work_type', 1);
            $this->assignconfig('work_type', 1);
            $this->view->assign('problem_type', config('workorder.customer_problem_type')); //客服问题类型          
        } else { //仓库工单
            $this->view->assign('work_type', 2);
            $this->assignconfig('work_type', 2);
            $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型
        }
            $skus = $this->model->getSkuList($row->work_platform, $row->platform_order);
            $arrSkus = [];
            foreach($skus['sku'] as $val){
                $arrSkus[$val] = $val;
            }
            $this->view->assign('skus',$arrSkus);
            return $this->view->fetch();
    }

    /**
     * 获取订单sku数据
     *
     * @Description
     * @author wpl
     * @since 2020/04/10 15:41:09 
     * @return void
     */
    public function get_sku_list()
    {
        if (request()->isAjax()) {
            $sitetype = input('sitetype');
            $order_number = input('order_number');
            $skus = $this->model->getSkuList($sitetype, $order_number);
            if ($skus) {
                $this->success('操作成功！！', '', $skus);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }

    /**
     * 根据处方获取地址信息以及处方信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ajaxGetAddress()
    {
        if (request()->isAjax()) {
            $incrementId = input('increment_id');
            $siteType = input('site_type');
            //获取地址、处方等信息
            $res = $this->model->getAddress($siteType, $incrementId);
            //请求接口获取lens_type，coating_type，prescription_type等信息
            $lens = $this->model->getReissueLens($siteType, $res['showPrescriptions']);
            if ($res) {
                $this->success('操作成功！！', '', ['address' => $res, 'lens' => $lens]);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }

    /**
     * 根据country获取Province
     * @return array
     */
    public function ajaxGetProvince()
    {
        $countryId = input('country_id');
        $country = json_decode(file_get_contents('assets/js/country.js'), true);
        $province = $country[$countryId];
        return $province ?: [];
    }

    /**
     * 获取更改镜片的数据
     * @throws Exception
     */
    public function ajaxGetChangeLens()
    {
        if (request()->isAjax()) {
            $incrementId = input('increment_id');
            $siteType = input('site_type');
            //获取地址、处方等信息
            $res = $this->model->getAddress($siteType, $incrementId);
            $lens = $this->model->getReissueLens($siteType, $res['prescriptions'], 2);
            if ($res) {
                $this->success('操作成功！！', '', $lens);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }

    /**
     * 赠品表单
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ajaxGetGiftLens()
    {
        if (request()->isAjax()) {
            $incrementId = input('increment_id');
            $siteType = input('site_type');
            //获取地址、处方等信息
            $res = $this->model->getAddress($siteType, $incrementId);
            $lens = $this->model->getReissueLens($siteType, $res['prescriptions'], 3);
            if ($res) {
                $this->success('操作成功！！', '', $lens);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }

    /**
     * ajax根据prescription_type获取镜片信息
     */
    public function ajaxGetLensType()
    {
        if (request()->isAjax()) {
            $siteType = input('site_type');
            $prescriptionType = input('prescription_type');
            $key = $siteType . '_getlens';
            $data = session($key);
            if(!$data){
                $data = $this->model->getLensData($siteType);
            }
            $lensType = $data['lens_list'][$prescriptionType] ?: [];
            if ($lensType) {
                $this->success('操作成功！！', '', $lensType);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }
    /**
     * 获取订单order的镜框等信息
     *
     * @Description
     * @author lsw
     * @since 2020/04/13 17:28:49 
     * @return void
     */
    public function ajax_get_order($ordertype = null, $order_number = null)
    {
        if ($this->request->isAjax()) {
            if ($ordertype < 1 || $ordertype > 5) { //不在平台之内
                return $this->error('选择平台错误,请重新选择', '', 'error', 0);
            }
            if (!$order_number) {
                return  $this->error('订单号不存在，请重新选择', '', 'error', 0);
            }
            if ($ordertype == 1) {
                $result = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 2) {
                $result = VooguemePrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 3) {
                $result = NihaoPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif (5 == $ordertype) {
                $result = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            }
            if(!$result){
                $this->error('找不到这个订单,请重新尝试','','error',0);
            }
            $arr = [];
            foreach($result as $val){
                for($i=0;$i<$val['qty_ordered'];$i++){
                    $arr[] = $val['sku'];
                }
            }
            return $this->success('','',$arr,0);
        }else{
            return $this->error('404 Not Found');
        }
    }
}
