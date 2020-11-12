<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\model\Auth;

/**
 * 开发任务管理
 *
 * @icon fa fa-circle-o
 */
class DevelopWebTask extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['deleteItem'];
    
    /**
     * DevelopWebTask模型对象
     * @var \app\admin\model\demand\DevelopWebTask
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\DevelopWebTask;
        $this->itWebTaskItem = new \app\admin\model\demand\DevelopWebTaskItem();
        $this->testRecord = new \app\admin\model\demand\DevelopTestRecord();
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

            //自定义姓名搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['nickname']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . $filter['nickname'] . '%'];
                $id = $admin->where($smap)->value('id');
                $task_ids = $this->itWebTaskItem->where('person_in_charge', $id)->column('task_id');
                $map['id'] = ['in', $task_ids];
                unset($filter['nickname']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //判断是否有完成按钮权限
        $this->assignconfig('is_problem_detail', $this->auth->check('demand/develop_web_task/problem_detail'));
        $this->assignconfig('is_edit', $this->auth->check('demand/develop_web_task/edit'));
        $this->assignconfig('is_set_status', $this->auth->check('demand/develop_web_task/set_task_complete_status'));
        $this->assignconfig('is_set_task_test_status', $this->auth->check('demand/develop_web_task/set_task_test_status'));
        $this->assignconfig('is_regression_test_info', $this->auth->check('demand/develop_web_task/regression_test_info'));
        $this->assignconfig('is_finish_task', $this->auth->check('demand/develop_web_task/is_finish_task'));
        $this->assignconfig('is_del_btu', $this->auth->check('demand/develop_web_task/del'));
        $this->assignconfig('is_test_info_btu', $this->auth->check('demand/develop_web_task/test_info'));//开发管理 记录测试问题
        $this->assignconfig('is_set_test_status_btu', $this->auth->check('demand/develop_web_task/set_test_status'));//开发管理 记录测试问题
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

                    $person_in_charge = $this->request->post("person_in_charge/a");
                    $title = $this->request->post("title/a");
                    $desc = $this->request->post("desc/a");
                    $plan_date = $this->request->post("plan_date/a");
                    $type = $this->request->post("type/a");

                    //执行过滤空值
                    array_walk($person_in_charge, 'trim_value');
                    array_walk($title, 'trim_value');
                    array_walk($type, 'trim_value');

                    if (count(array_filter($person_in_charge)) < 1 || count(array_filter($title)) < 1) {
                        $this->error('请先分配任务');
                    }

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);
                    //添加分配信息
                    if ($result !== false) {
                        $data = [];
                        foreach ($person_in_charge as $k => $v) {
                            $data[$k]['person_in_charge'] = $person_in_charge[$k];
                            $data[$k]['type'] = $type[$k];
                            $data[$k]['title'] = $title[$k];
                            $data[$k]['desc'] = $desc[$k];
                            $data[$k]['plan_date'] = $plan_date[$k];
                            $data[$k]['task_id'] = $this->model->id;
                        }
                        //批量添加
                        $res = $this->itWebTaskItem->allowField(true)->saveAll($data);
                        //有错误 则回滚数据
                        if (!$res) {
                            throw new Exception("添加失败！！请重新添加");
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
        $this->assign('phper_user', config('develop_demand.phper_user'));
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

                    //添加分配信息
                    if ($result !== false) {
                        $type = $this->request->post("type/a");
                        $person_in_charge = $this->request->post("person_in_charge/a");
                        $title = $this->request->post("title/a");
                        $desc = $this->request->post("desc/a");
                        $plan_date = $this->request->post("plan_date/a");
                        $item_id = $this->request->post("item_id/a");
                        $data = [];
                        foreach ($type as $k => $v) {
                            $data[$k]['person_in_charge'] = $person_in_charge[$k];
                            $data[$k]['type'] = $v;
                            $data[$k]['title'] = $title[$k];
                            $data[$k]['desc'] = $desc[$k];
                            $data[$k]['plan_date'] = $plan_date[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['task_id'] = $ids;
                            }
                        }
                        //批量修改
                        $this->itWebTaskItem->allowField(true)->saveAll($data);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //查询明细信息
        $map['task_id'] = $ids;
        $item = $this->itWebTaskItem->where($map)->select();
        $this->assign('item', $item);

        $this->view->assign("row", $row);
        $this->assign('phper_user', config('develop_demand.phper_user'));
        return $this->view->fetch();
    }



    /**
     * 逻辑删除
     * */
    public function del($ids = "")
    {
        if ($this->request->isAjax()) {
            $data['is_del'] =  2;
            $res = $this->model->allowField(true)->save($data, ['id' => input('ids')]);
            if ($res) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    /**
     * 删除子表数据
     *
     * @Description
     * @author wpl
     * @since 2020/06/01 14:02:31 
     * @return void
     */
    public function deleteItem()
    {
        if ($this->request->isAjax()) {
            $id = input('id');
            $res = $this->itWebTaskItem->destroy($id);
            if ($res) {
                $this->success();
            } else {
                $this->error();
            }
        }
    }


    /**
     * 详情
     */
    public function detail($ids = null)
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

        //查询明细信息
        $map['task_id'] = $ids;
        $item = $this->itWebTaskItem->where($map)->select();
        $this->assign('item', $item);

        $this->view->assign("row", $row);
        $this->assign('phper_user', config('develop_demand.phper_user'));
        return $this->view->fetch();
    }

    /**
     * 明细列表
     *
     * @Description
     * @author wpl
     * @since 2020/03/26 17:47:43 
     * @param [type] $ids
     * @return void
     */
    public function item($ids = null)
    {
        $ids = $ids ?? input('ids');
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $id = input('ids');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->itWebTaskItem
                ->where('task_id', $id)
                ->order($sort, $order)
                ->count();

            $list = $this->itWebTaskItem
                ->where('task_id', $id)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$v) {
                $v['person_in_charge_text'] = config('develop_demand.phper_user')[$v['person_in_charge']];
            }
            unset($v);
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $test_user_id = array_merge(Auth::getGroupUserId(config('demand.test_group_id')),Auth::getGroupUserId(config('demand.test_group_person_id')));
        $this->assignconfig('id', $ids);
        $this->assignconfig('user_id', session('admin.id'));
        $this->assignconfig('test_user', $test_user_id);
        return $this->view->fetch();
    }

    /**
     * 更改任务完成状态
     *
     * @Description
     * @author wpl
     * @since 2020/03/26 18:24:19 
     * @param [type] $ids
     * @return void
     */
    public function set_task_complete_status($ids = null)
    {
        $data['is_complete'] = 1;
        $data['complete_date'] = date('Y-m-d H:i:s', time());
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }

    /**
     * 更改通过测试状态
     *
     * @Description
     * @author wpl
     * @since 2020/03/26 18:24:19 
     * @param [type] $ids
     * @return void
     */
    public function set_task_test_status($ids = null)
    {
        $data['test_regression_adopt'] = 1;
        $data['test_regression_adopt_time'] = date('Y-m-d H:i:s', time());
        $data['test_regression_person'] = session('admin.nickname');
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }


    /**
     * 更改明细完成状态
     *
     * @Description
     * @author wpl
     * @since 2020/03/26 18:24:19 
     * @param [type] $ids
     * @return void
     */
    public function set_complete_status($ids = null)
    {
        $data['is_complete'] = 1;
        $data['complete_date'] = date('Y-m-d H:i:s', time());
        $res = $this->itWebTaskItem->save($data, ['id' => $ids]);
        //判断同主任务下是否都完成，如果都完成，则更改主任务为完成

        //查询同记录下是否还存在未测试通过的数据
        $itWebTaskInfo = $this->itWebTaskItem->get($ids);
        $map['task_id'] = $itWebTaskInfo['task_id'];
        $map['is_complete'] = 0;
        $num = $this->itWebTaskItem->where($map)->count();
        //如果不存在则修改主记录测试完成 并更新时间
        if ($num == 0) {
            $list['is_complete'] = 1;
            $list['complete_date'] = date('Y-m-d H:i:s', time());
            $this->model->save($list, ['id' => $itWebTaskInfo['task_id']]);
        }
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }

    /**
     * 更改测试通过状态
     *
     * @Description
     * @author wpl
     * @since 2020/03/26 18:24:19 
     * @param [type] $ids
     * @return void
     */
    public function set_test_status($ids = null)
    {
        Db::startTrans();
        try {
            $data['is_test_adopt'] = 1;
            $data['test_adopt_time'] = date('Y-m-d H:i:s', time());
            $res = $this->model->save($data, ['id' => $ids]);
            //有错误 则回滚数据
            if (!$res) {
                throw new Exception("修改失败");
            }
            /*            //查询同记录下是否还存在未测试通过的数据
            $itWebTaskInfo = $this->itWebTaskItem->get($ids);
            $map['task_id'] = $itWebTaskInfo['task_id'];
            $map['is_test_adopt'] = 0;
            $num = $this->itWebTaskItem->where($map)->count();
            //如果不存在则修改主记录测试完成 并更新时间
            if ($num == 0) {
                $list['is_test_adopt'] = 1;
                $list['test_adopt_time'] = date('Y-m-d H:i:s', time());
                $this->model->save($list, ['id' => $itWebTaskInfo['task_id']]);
            }*/
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

        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }

    /**
     * 记录测试问题
     *
     * @Description
     * @author wpl
     * @since 2020/03/28 10:30:31 
     * @param [type] $ids
     * @return void
     */
    public function test_info($ids = null)
    {
        /* $row = $this->itWebTaskItem->get($ids);
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
                    $params['type'] = 4;
                    $params['pid'] = $row->task_id;
                    $params['environment_type'] = 1;
                    $params['responsibility_user_id'] = $row->person_in_charge;
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['create_user_id'] = $this->auth->id;
                    $result = $this->testRecord->allowField(true)->save($params);
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
        return $this->view->fetch();*/

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
                    $params['type'] = 4;
                    $params['environment_type'] = 1;
                    $params['pid'] = $row->id;
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['create_user_id'] = $this->auth->id;
                    $result = $this->testRecord->allowField(true)->save($params);
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
        //查询此记录责任人id
        $person_ids = $this->itWebTaskItem->where('task_id', $ids)->field('person_in_charge')->select();
        foreach ($person_ids as &$v) {
            $v['person_in_charge_name'] = config('develop_demand.phper_user')[$v['person_in_charge']];
        }
        $this->assign('person_ids', $person_ids);
        return $this->view->fetch();
    }

    /**
     * 问题记录
     *
     * @Description
     * @author wpl
     * @since 2020/03/28 11:31:24 
     * @param [type] $ids
     * @return void
     */
    public function problem_detail($ids = null)
    {

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['opt_type'] == 1) {
                    $data['is_complete'] = 1;
                    $where['id'] = $params['id'];
                    $res = $this->testRecord->allowField(true)->save($data, $where);
                    if ($res) {
                        $this->success('成功');
                    } else {
                        $this->error('失败');
                    }
                } elseif ($params['opt_type'] == 2) {

                    $data['is_del'] = 2;
                    $where['id'] = $params['id'];
                    $res = $this->testRecord->allowField(true)->save($data, $where);
                    if ($res) {
                        $this->success('成功');
                    } else {
                        $this->error('失败');
                    }
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $map['pid'] = $ids;
        $map['type'] = 4;
        $map['is_del'] = 1;
        /*测试日志--测试环境*/
        $left_test_list = $this->testRecord
            ->where($map)
            ->where('environment_type', 1)
            ->order('id', 'desc')
            ->select();
        $left_test_list = collection($left_test_list)->toArray();
        foreach ($left_test_list as $k_left => $v_left) {
            $left_test_list[$k_left]['responsibility_user_name'] = config('develop_demand.phper_user')[$v_left['responsibility_user_id']];
            $left_test_list[$k_left]['create_user_name'] = config('demand.test_user')[$v_left['create_user_id']];
        }

        /*测试日志--正式环境*/
        $right_test_list = $this->testRecord
            ->where($map)
            ->where('environment_type', 2)
            ->order('id', 'desc')
            ->select();
        $right_test_list = collection($right_test_list)->toArray();
        foreach ($right_test_list as $k_right => $v_right) {
            $right_test_list[$k_right]['responsibility_user_name'] = config('develop_demand.phper_user')[$v_right['responsibility_user_id']];
            $right_test_list[$k_right]['create_user_name'] = config('demand.test_user')[$v_right['create_user_id']];
        }

        $bug_type = config('demand.bug_type'); //严重类型
        $this->view->assign("bug_type", $bug_type);
        $this->view->assign("left_test_list", $left_test_list);
        $this->view->assign("right_test_list", $right_test_list);
        return $this->view->fetch();
    }

    /**
     * 记录回归测试问题
     *
     * @Description
     * @author wpl
     * @since 2020/03/28 10:30:31 
     * @param [type] $ids
     * @return void
     */
    public function regression_test_info($ids = null)
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
                    $params['type'] = 4;
                    $params['environment_type'] = 2;
                    $params['pid'] = $row->id;
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['create_user_id'] = $this->auth->id;
                    $result = $this->testRecord->allowField(true)->save($params);
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
        //查询此记录责任人id
        $person_ids = $this->itWebTaskItem->where('task_id', $ids)->field('person_in_charge')->select();
        foreach ($person_ids as &$v) {
            $v['person_in_charge_name'] = config('develop_demand.phper_user')[$v['person_in_charge']];
        }
        $this->assign('person_ids', $person_ids);
        return $this->view->fetch();
    }

    /**
     * 产品经理确认
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 16:52:22 
     * @param [type] $ids
     * @return void
     */
    public function is_finish_task($ids = null)
    {
        $data['is_finish'] = 1;
        $data['finish_time'] = date('Y-m-d H:i:s', time());
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }
}
