<?php

namespace app\admin\controller\saleaftermanage;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\admin\model\AuthGroupAccess;
use think\exception\PDOException;
use think\exception\ValidateException;

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
                }else{
                    $list[$k]['work_type_str'] = '仓库工单';
                }

                if($v['is_check'] == 1){
                    $list[$k]['assign_user_name'] = $user_list[$v['assign_user_id']];
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




                    $result = $this->model->allowField(true)->save($params);
                    if (false === $result) {
                        throw new Exception("添加失败！！");
                    }

                    //循环插入措施
                    if ($params['measure_choose_id']) {
                        //措施
                        $measureList = [];
                        foreach ($params['measure_choose_id'] as $k => $v) {
                            $measureList[$k]['work_id'] = $this->model->id;
                            $measureList[$k]['measure_choose_id'] = $v;
                            $measureList[$k]['measure_content'] = config('workorder.step')[$v];
                            $measureList[$k]['create_time'] = date('Y-m-d H:i:s');
                        }
                        $res = $this->step->saveAll($measureList);
                        if (false === $res) {
                            throw new Exception("添加失败！！");
                        }
                    }

                    //循环插入承接人
                    if ($params['order_recept']) {
                        $recept_person_id = explode(',', trim($params['order_recept']['recept_person_id'], ','));
                        $recept_person = explode(',', trim($params['order_recept']['recept_person'], ','));
                        $recept_group_id = explode(',', trim($params['order_recept']['recept_group_id'], ','));
                        //措施
                        $receptList = [];
                        foreach ($recept_person_id as $k => $v) {
                            $receptList[$k]['work_id'] = $this->model->id;
                            $receptList[$k]['measure_choose_id'] = $v;
                            $receptList[$k]['measure_content'] = config('workorder.step')[$v];
                            $receptList[$k]['create_time'] = date('Y-m-d H:i:s');
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

        $this->view->assign('step', config('workorder.step')); //措施
        $this->assignconfig('workorder', config('workorder')); //JS专用，整个配置文件

        $this->view->assign('check_coupon', config('workorder.check_coupon')); //不需要审核的优惠券
        $this->view->assign('need_check_coupon', config('workorder.need_check_coupon')); //需要审核的优惠券

        //获取所有的国家
        $country = json_decode(file_get_contents('assets/js/country.js'), true);
        $this->view->assign('country', $country);

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
            $lens = $this->model->getLens($siteType,$res['showPrescriptions']);
            if ($res) {
                $this->success('操作成功！！', '', ['address' => $res,'lens' => $lens]);
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
}
