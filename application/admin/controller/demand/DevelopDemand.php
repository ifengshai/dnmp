<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 需求平台-开发组-日常需求管理
 *
 * @icon fa fa-circle-o
 */
class DevelopDemand extends Backend
{

    /**
     * DevelopDemand模型对象
     * @var \app\admin\model\demand\DevelopDemand
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\DevelopDemand;
        $this->testRecord = new \app\admin\model\demand\DevelopTestRecord();
        $this->assignconfig('admin_id', session('admin.id'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看 开发组日常需求
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
                ->where('type','2')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type','2')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            //查询用户表id
            $admin = new \app\admin\model\Admin();
            $userInfo = $admin->where('status', 'normal')->column('nickname', 'id');
            foreach ($list as $k => $val) {
                $userids = explode(',', $val['assign_developer_ids']);
                $nickname = [];
                foreach ($userids as $v) {
                    if (!$v) {
                        continue;
                    }
                    $nickname[] = $userInfo[$v];
                }
                $test_userids = explode(',', $val['test_person']);
                $test_nickname = [];
                foreach ($test_userids as $v) {
                    $test_nickname[] = $userInfo[$v];
                }
                $list[$k]['nickname'] = implode(',', $nickname);
                $list[$k]['test_person'] = implode(',', $test_nickname);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('username', session('admin.nickname'));
        //判断编辑按钮权限
        $this->assignconfig('is_edit', $this->auth->check('demand/develop_demand/edit'));
        //判断分配权限
        $this->assignconfig('is_distribution', $this->auth->check('demand/develop_demand/distribution'));
        //判断产品经理审核权限
        $this->assignconfig('review_status_manager_btn', $this->auth->check('demand/develop_demand/review'));
        //判断是否有开发主管审核权限
        $this->assignconfig('review_status_btn', $this->auth->check('demand/develop_demand/review_status_develop'));
        //判断测试确认按钮
        $this->assignconfig('test_btn', $this->auth->check('demand/develop_demand/test_distribution'));
        //判断开发完成按钮
        $this->assignconfig('is_set_status', $this->auth->check('demand/develop_demand/set_complete_status'));
        //判断测试记录问题按钮
        $this->assignconfig('test_record_bug', $this->auth->check('demand/develop_demand/test_record_bug'));
        //判断通过测试按钮
        $this->assignconfig('test_is_passed', $this->auth->check('demand/develop_demand/test_is_passed'));
        //判断产品经理确认按钮
        $this->assignconfig('is_finish_task', $this->auth->check('demand/develop_demand/is_finish_task'));
        //判断回归测试按钮
        $this->assignconfig('regression_test_info', $this->auth->check('demand/develop_demand/regression_test_info'));
        //判断回归测试完成按钮
        $this->assignconfig('test_complete', $this->auth->check('demand/develop_demand/test_complete'));
        //判断删除按钮
        $this->assignconfig('is_del_btu', $this->auth->check('demand/develop_demand/del'));
        return $this->view->fetch();
    }



    /**
     * 查看 开发组BUG列表
     */
    public function develop_bug_list()
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
                ->where('type','1')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type','1')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            //查询用户表id
            $admin = new \app\admin\model\Admin();
            $userInfo = $admin->where('status', 'normal')->column('nickname', 'id');
            foreach ($list as $k => $val) {
                $userids = explode(',', $val['assign_developer_ids']);
                $nickname = [];
                foreach ($userids as $v) {
                    if (!$v) {
                        continue;
                    }
                    $nickname[] = $userInfo[$v];
                }
                $test_userids = explode(',', $val['test_person']);
                $test_nickname = [];
                foreach ($test_userids as $v) {
                    $test_nickname[] = $userInfo[$v];
                }
                $list[$k]['nickname'] = implode(',', $nickname);
                $list[$k]['test_person'] = implode(',', $test_nickname);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('username', session('admin.nickname'));
        //判断编辑按钮权限
        $this->assignconfig('is_edit', $this->auth->check('demand/develop_demand/edit'));
        //判断分配权限
        $this->assignconfig('is_distribution', $this->auth->check('demand/develop_demand/distribution'));
        //判断产品经理审核权限
        $this->assignconfig('review_status_manager_btn', $this->auth->check('demand/develop_demand/review'));
        //判断是否有开发主管审核权限
        $this->assignconfig('review_status_btn', $this->auth->check('demand/develop_demand/review_status_develop'));
        //判断测试确认按钮
        $this->assignconfig('test_btn', $this->auth->check('demand/develop_demand/test_distribution'));
        //判断开发完成按钮
        $this->assignconfig('is_set_status', $this->auth->check('demand/develop_demand/set_complete_status'));
        //判断测试记录问题按钮
        $this->assignconfig('test_record_bug', $this->auth->check('demand/develop_demand/test_record_bug'));
        //判断通过测试按钮
        $this->assignconfig('test_is_passed', $this->auth->check('demand/develop_demand/test_is_passed'));
        //判断产品经理确认按钮
        $this->assignconfig('is_finish_task', $this->auth->check('demand/develop_demand/is_finish_task'));
        //判断回归测试按钮
        $this->assignconfig('regression_test_info', $this->auth->check('demand/develop_demand/regression_test_info'));
        //判断回归测试完成按钮
        $this->assignconfig('test_complete', $this->auth->check('demand/develop_demand/test_complete'));
        //判断删除按钮
        $this->assignconfig('is_del_btu', $this->auth->check('demand/develop_demand/del'));
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
                    $params['create_person'] = session('admin.nickname');
                    $params['create_person_id'] = session('admin.id');
                    $params['createtime'] = date('Y-m-d H:i:s');

                    if ($params['type']==1){//如果为BUG类型,更新
                        $params['review_status_develop'] = 1;
                        $params['review_status_manager'] = 1;
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
        $this->view->assign('demand_type',input('demand_type'));
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
        $this->view->assign('demand_type',input('demand_type'));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 逻辑删除
     * */
    public function del($ids = "")
    {
        if ($this->request->isAjax()) {
            $data['is_del'] =  2;
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                $this->success('成功');
            } else {
                $this->error('失败');
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

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 产品经理审核
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 10:02:24 
     * @return void
     */
    public function review($ids = null)
    {
        $id = $ids ?: input('id');
        $label = input('label');
        $row = $this->model->get($id);
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
                    $params['review_manager_time'] = date('Y-m-d H:i:s');
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
        if ($label == 'refuse') {
            return $this->view->fetch('review_refuse');
        } else {
            return $this->view->fetch();
        }
    }

    /**
     * 开发主管审核
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 10:02:24 
     * @return void
     */
    public function review_status_develop($ids = null)
    {
        $row = $this->model->get($ids);
        if ($this->request->isPost()) {
            $result = false;
            Db::startTrans();
            try {
                //是否采用模型验证
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                    $row->validateFailException(true)->validate($validate);
                }

                $params['review_status_develop'] = input('review_status_develop');
                $params['review_devel_time'] = date('Y-m-d H:i:s');
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
        return $this->view->fetch();
    }

    /**
     * 分配
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 14:59:20 
     * @return void
     */
    public function distribution()
    {
        $id = input('id');
        $row = $this->model->get($id);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $assign_developer_ids = $this->request->post("assign_developer_ids/a");
                    $params['assign_developer_ids'] = implode(',', $assign_developer_ids);
                    $result = $this->model->allowField(true)->save($params, ['id' => input('id')]);
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
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 测试分配
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 16:05:57 
     * @return void
     */
    public function test_distribution($ids = null)
    {
        $id = $ids ?? input('id');
        $row = $this->model->get($id);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $test_person = $this->request->post("test_person/a");
                    $params['test_person'] = implode(',', $test_person);
                    $result = $this->model->allowField(true)->save($params, ['id' => input('id')]);
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
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 设置完成状态
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 16:52:22 
     * @param [type] $ids
     * @return void
     */
    public function set_complete_status($ids = null)
    {
        $data['is_finish'] = 1;
        $data['finish_time'] = date('Y-m-d H:i:s', time());
        $data['finish_person'] = session('admin.nickname');
        $data['finish_person_id'] = session('admin.id');
        $res = $this->model->save($data, ['id' => $ids]);
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
     * @since 2020/03/31 17:08:09 
     * @param [type] $ids
     * @return void
     */
    public function test_record_bug($ids = null)
    {
        $id = $ids ?? input('id');
        $row = $this->model->get($id);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $params['type'] = 2;
                    $params['pid'] = $row->id;
                    $params['environment_type'] = 1;
                    $params['responsibility_user_id'] = $row->assign_developer_ids;
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
        $this->assign('row', $row);
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
        $map['pid'] = $ids;
        $map['type'] = 2;
        /*测试日志--测试环境*/
        $list = $this->testRecord
            ->where($map)
            ->where('environment_type', 1)
            ->order('id', 'desc')
            ->select();
        $list = collection($list)->toArray();

        //查询用户表id
        $admin = new \app\admin\model\Admin();
        $userInfo = $admin->where('status', 'normal')->column('nickname', 'id');
        foreach ($list as $k => $val) {
            $userids = explode(',', $val['responsibility_user_id']);
            $nickname = [];
            foreach ($userids as $v) {
                $nickname[] = $userInfo[$v];
            }
            $list[$k]['responsibility_user_name'] = implode(',', $nickname);
            $list[$k]['create_user_name'] = $userInfo[$val['create_user_id']];
        }
        /*测试日志--正式环境*/
        $right_list = $this->testRecord
            ->where($map)
            ->where('environment_type', 2)
            ->order('id', 'desc')
            ->select();
        $right_list = collection($right_list)->toArray();
        foreach ($right_list as $k => $val) {
            $userids = explode(',', $val['responsibility_user_id']);
            $nickname = [];
            foreach ($userids as $v) {
                $nickname[] = $userInfo[$v];
            }
            $right_list[$k]['responsibility_user_name'] = implode(',', $nickname);
            $right_list[$k]['create_user_name'] = $userInfo[$val['create_user_id']];
        }

        $bug_type = config('demand.bug_type'); //严重类型
        $this->view->assign("bug_type", $bug_type);
        $this->view->assign("list", $list);
        $this->view->assign("right_list", $right_list);
        return $this->view->fetch();
    }

    /**
     * 测试通过状态
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 16:52:22 
     * @param [type] $ids
     * @return void
     */
    public function test_is_passed($ids = null)
    {
        $data['test_is_passed'] = 1;
        $data['test_finish_time'] = date('Y-m-d H:i:s', time());
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
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
        $data['is_finish_task'] = 1;
        $data['finish_task_time'] = date('Y-m-d H:i:s', time());
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }

    /**
     * 回归测试记录测试问题
     *
     * @Description
     * @author wpl
     * @since 2020/03/31 17:08:09 
     * @param [type] $ids
     * @return void
     */
    public function regression_test_info($ids = null)
    {
        $id = $ids ?? input('id');
        $row = $this->model->get($id);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $params['type'] = 2;
                    $params['pid'] = $row->id;
                    $params['environment_type'] = 2;
                    $params['responsibility_user_id'] = $row->assign_developer_ids;
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
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 回归测试完成
     *
     * @Description
     * @author wpl
     * @since 2020/04/30 17:24:26 
     * @return void
     */
    public function test_complete($ids = null)
    {
        $data['is_test_complete'] = 1;
        $data['test_complete_time'] = date('Y-m-d H:i:s', time());
        $data['test_complete_person'] = session('admin.nickname');
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }
}
