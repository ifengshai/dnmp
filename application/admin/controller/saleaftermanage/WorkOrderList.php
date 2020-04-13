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
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

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
                    $params['create_user_name'] = session('admin.nickname');
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
        //获取用户ID和所在权限组
        $userId = session('admin.id');
        $userGroupAccess = AuthGroupAccess::where(['uid' => $userId])->column('group_id');
        $warehouseArr = config('workorder.warehouse_department_rule');
        $checkIsWarehouse = array_intersect($userGroupAccess, $warehouseArr);
        if (!empty($checkIsWarehouse)) {
            $this->view->assign('work_type',2);
            $this->assignconfig('work_type',2);
            $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型       
        } else {
            $this->view->assign('work_type',1);
            $this->assignconfig('work_type',1);
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
        $sitetype = input('sitetype');
        $order_number = input('order_number');
        $skus = $this->model->getSkuList($sitetype, $order_number);
        if ($skus) {
            $this->success('操作成功！！', '', $skus);
        } else {
            $this->error('未获取到数据！！');
        }
    }
}
