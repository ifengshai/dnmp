<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\AuthGroupAccess;
use app\admin\model\saleaftermanage\WorkOrderNote;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\saleaftermanage\WorkOrderCheckRule;
use app\admin\model\saleaftermanage\WorkOrderDocumentary;
use app\admin\model\saleaftermanage\WorkOrderProblemStep;
use app\admin\model\saleaftermanage\WorkOrderStepType;
use app\admin\model\platformManage\MagentoPlatform;

/**
 * 工单问题类型管理
 *
 * @icon fa fa-circle-o
 */
class Workorderconfig extends Backend
{

    /**
     * Workorderconfig模型对象
     * @var \app\admin\model\saleaftermanage\Workorderconfig
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\Workorderconfig;
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
                $res = $this->model->where(['type' => $params['type'], 'problem_belong' => $params['problem_belong'], 'problem_name' => $params['problem_name'], 'is_del' => 1])->find();
                if (!empty($res)) {
                    $this->error('当前问题已存在,请不要重复添加');
                }
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
     * 编辑工单问题类型所对应的措施
     *
     * @Description
     * @return void
     * @since 2020/6/22 14:34
     * @author jhh
     */
    public function detail($ids = null)
    {
        $row = $this->model->getQuetionMeasure($ids);
        $step = $this->model->getAllStep();
        foreach ($step as $k => $v) {
            $result = Db::name('work_order_problem_step')->where(['problem_id' => $ids, 'step_id' => $step[$k]['id']])->find();
            if (!empty($result)) {
                $step[$k]['is_selected'] = 1;
                $step[$k]['is_check'] = $result['is_check'];
                $step[$k]['extend_group_id'] = $result['extend_group_id'];
            } else {
                $step[$k]['is_selected'] = 0;
                $step[$k]['is_check'] = '';
                $step[$k]['extend_group_id'] = '';
            }
        }
        $extend_team = $this->model->getAllExtend();
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a", [], 'strip_tags');
            if (empty($params['choose_id'])) {
                $params['choose_id'] = array();
            }
            //所有的措施遍历
            $all_step = Db::name('work_order_step_type')->where('is_del', 1)->field('id,step_name')->select();
            Db::startTrans();
            try {
                foreach ($all_step as $k => $v) {
                    //查找某一个问题类型所对应的措施是否存在
                    $problem_step = Db::name('work_order_problem_step')->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])->find();

                    //不存在就新增一条某个问题对应的措施 存在判断是否更新 是否由审核变成不审核 承接组是否改变
                    if (!$problem_step && in_array($v['id'], array_keys($params['choose_id']))) {
                        $data = array();
                        $data['problem_id'] = $params['problem_id'];
                        $data['step_id'] = $v['id'];
                        $data['extend_group_id'] = $params['extend'][$v['id'] - 1];
                        if ($params['choose_id'][$v['id']]['is_checked'] = 'on') {
                            $data['is_check'] = 1;
                        }
                        Db::name('work_order_problem_step')->insert($data);
                    } else if (!$problem_step && !in_array($v['id'], array_keys($params['choose_id']))) {
                        //不存在也没有选择不进行任何操作

                    } else if ($problem_step && !in_array($v['id'], array_keys($params['choose_id']))) {
                        //存在但是没有选择 就把他从记录中删除掉
                        Db::name('work_order_problem_step')
                            ->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])
                            ->delete();
                    } else if ($problem_step && in_array($v['id'], array_keys($params['choose_id']))) {
                        //存在这个问题类型对应的措施 也选择了 看是否需要更新
                        if (isset($params['choose_id'][$v['id']]['is_checked'])) {
                            $is_check = 1;
                        } else {
                            $is_check = 0;
                        }
                        Db::name('work_order_problem_step')
                            ->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])
                            ->update(['extend_group_id' => $params['extend'][$v['id'] - 1], 'is_check' => $is_check]);
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
            $this->success();
        }
        $this->view->assign("step", $step);
        $this->view->assign("extend_team", $extend_team);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 获取工单的配置信息
     *
     * @Description
     * @return void
     * @since 2020/06/19 11:04:57
     * @author lsw
     */
    public function getConfigInfo()
    {
        //所有问题类型
        $where['is_del'] = 1;
        $all_problem_type = $this->model->where($where)->select();
        //所有措施类型
        $all_step = (new WorkOrderStepType)->where($where)->select();
        //所有平台
        $all_platform = (new MagentoPlatform)->field('id,name')->select();
        if (!$all_problem_type) {
            //不存在问题类型
        }
        if (!$all_step) {
            //不存在措施
        }
        $all_problem_type = collection($all_problem_type)->toArray();
        $all_step = collection($all_step)->toArray();
        //客服问题类型，仓库问题类型，大的问题类型分类,所有措施
        $customer_problem_type = $warehouse_problem_type = $customer_problem_classify_arr = $step = $platform = [];
        foreach ($all_problem_type as $v) {
            if (1 == $v['type']) {
                $customer_problem_type[$v['id']] = $v['problem_name'];
            } elseif (2 == $v['type']) {
                $warehouse_problem_type[$v['id']] = $v['problem_name'];
            }
            $customer_problem_classify_arr[$v['problem_belong']][] = $v['id'];
        }
        foreach ($all_step as $sv) {
            $step[$sv['id']] = $v['step_name'];
        }
        foreach ($all_platform as $pv) {
            $platform[$pv['id']] = $pv['name'];
        }
        $arr['customer_problem_type'] = $customer_problem_type;
        $arr['warehouse_problem_type'] = $warehouse_problem_type;
        $arr['customer_problem_classify_arr'] = $customer_problem_classify_arr;
        $arr['step'] = $step;
        $arr['platform'] = $platform;
        return $arr;
    }


}
