<?php

namespace app\admin\controller\saleaftermanage;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 工单类型措施关系管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrderProblemStep extends Backend
{
    
    /**
     * WorkOrderProblemStep模型对象
     * @var \app\admin\model\saleaftermanage\WorkOrderProblemStep
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderProblemStep;

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
                ->alias('s')
                ->join('work_order_problem_type w','s.problem_id = w.id')
                ->join('work_order_step_type a','s.step_id = a.id')
                ->join('auth_group g','s.extend_group_id = g.id')
                ->order('s.id','asc')
                ->count();

            $list = $this->model
                ->where($where)
                ->alias('s')
                ->join('work_order_problem_type w','s.problem_id = w.id')
                ->join('work_order_step_type a','s.step_id = a.id')
                ->join('auth_group g','s.extend_group_id = g.id')
                ->field('s.id,s.problem_id,s.step_id,s.extend_group_id,w.problem_belong,w.problem_name,a.step_name,s.is_check,g.name')
                ->order('s.id','asc')
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
        return $this->view->fetch();
    }

    /**
     * 获取措施下拉列表
     *
     * @Description
     * @return void
     * @since 2020/6/19 14:40
     * @author jhh
     */
    public function getMeasureContent()
    {
        $return = Db::name('work_order_step_type')
            ->where('is_del',1)
            ->field('id,step_name')
            ->select();
        $return = array_column($return, 'step_name','id');
        return json($return);
    }

    /**
     * 获取问题类型下拉列表
     *
     * @Description
     * @return void
     * @since 2020/6/19 15:33
     * @author jhh
     */
    public function getQuestionType()
    {
        $return = Db::name('work_order_problem_type')
            ->where('is_del',1)
            ->field('id,problem_name')
            ->select();
        $return = array_column($return, 'problem_name','id');
        return json($return);
    }

    /**
     * 获取承接组
     *
     * @Description
     * @return void
     * @since 2020/6/19 16:20
     * @author jhh
     */
    public function getUserGroup()
    {
        $return = Db::name('auth_group')
            ->where('status','normal')
            ->where('id','>',1)
            ->field('id,name')
            ->select();
        $return = array_column($return, 'name','id');
        return json($return);
    }
}
