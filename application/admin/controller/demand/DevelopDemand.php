<?php

namespace app\admin\controller\demand;

use app\api\controller\Ding;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\model\Auth;

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
    protected $noNeedRight = ['del'];  //解决创建人无删除权限问题 暂定
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\DevelopDemand;
        $this->testRecord = new \app\admin\model\demand\DevelopTestRecord();
        $this->assignconfig('admin_id', session('admin.id'));
        $this->view->assign('getTabList', $this->model->getTabList());
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

            $filter = json_decode($this->request->get('filter'), true);
            $meWhere = '';
            //我的
            if (isset($filter['me_task'])) {

                $adminId = session('admin.id');
                //经理
                $authUserIds = Auth::getUsersId('demand/develop_demand/review') ?: [];
                //经理
                if (in_array($adminId, $authUserIds)) {
                    $meWhere = "(review_status_manager = 0 or ( (is_test =1 and test_is_passed=1 and is_finish_task =0) or (is_test =0 and is_finish=1 and is_finish_task=0)  ) )";
                }
                /* old
				//开发主管
                $authDevelopUserIds = Auth::getUsersId('demand/develop_demand/review_status_develop') ?: [];
                if (!in_array($adminId, $authUserIds) && in_array($adminId, $authDevelopUserIds)) {
                    $meWhere = "((review_status_manager =1 and is_finish_task =0 and review_status_develop = 0) or FIND_IN_SET({$adminId},assign_developer_ids))"; //主管 需要主管审核的 主管本人的任务  未完成，需主管确认完成的
                }
				*/
                 //开发主管
                $authDevelopUserIds = Auth::getUsersId('demand/develop_demand/review_status_develop') ?: [];
                if (!in_array($adminId, $authUserIds) && in_array($adminId, $authDevelopUserIds)) {
                    $meWhere = "(is_finish_task =0 or FIND_IN_SET({$adminId},assign_developer_ids))"; //
                }
				
				//判断是否是普通的测试
                $testAuthUserIds = Auth::getUsersId('demand/develop_web_task/set_test_status') ?: [];
                if (!in_array($adminId, $authUserIds) && in_array($adminId, $testAuthUserIds)) {
                    $meWhere = "(is_test = 1 and FIND_IN_SET({$adminId},test_person) and is_test_complete =0)"; //测试用户
                }
                //显示有分配权限的人，此类人跟点上线的是一类人，此类人应该可以查看所有的权限
                $assignAuthUserIds = Auth::getUsersId('demand/it_web_demand/distribution') ?: [];
                if (in_array($adminId, $assignAuthUserIds)) {
                    $meWhere = "1 = 1";
                }
                // 不是主管和经理的, 是否为开发人或测试认，或创建人
                if (!$meWhere) {
                    $meWhere .= "FIND_IN_SET({$adminId},assign_developer_ids)  or FIND_IN_SET({$adminId},test_person)  or FIND_IN_SET({$adminId}, create_person_id)";
                }
                unset($filter['me_task']);
            }
            //搜索负责人
            if ($filter['nickname']) {
                //查询用户表id
                $admin = new \app\admin\model\Admin();
                $userIds = $admin->where('status', 'normal')->where('nickname', '=', $filter['nickname'] )->value('id');
                if ($userIds)  $map = "FIND_IN_SET({$userIds},assign_developer_ids)";
                unset($filter['nickname']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
          
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->where('type', '2')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->where('type', '2')
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

                if ($val['is_test'] == 1 && $val['test_person'] != '') {
                    if (in_array(session('admin.id'), explode(',', $val['test_person']))) {
                        $list[$k]['is_test_record_hidden'] = 1; //显示 记录问题
                        $list[$k]['is_test_finish_hidden'] = 1; //显示  测试通过
                        $list[$k]['is_test_detail_log'] = 0; //不显示  问题详情
                    }
                }


                if ($val['review_status_manager'] == 0) {
                    $list[$k]['status_str'] = '产品待审核';
                } elseif ($val['review_status_manager'] == 1 && $val['review_status_develop'] == 0) {
                    $list[$k]['status_str'] = '开发待审核';
                } elseif ($val['review_status_manager'] == 1 && $val['review_status_develop'] == 1) {
                    $list[$k]['status_str'] = '审核通过';
                } else {
                    $list[$k]['status_str'] = '审核拒绝';
                }
                //判断审核通过
                if ($val['review_status_manager'] == 1 && $val['review_status_develop'] == 1) {
                    if ($val['is_test'] == 1) {
                        if ($val['is_finish'] == 1 && $val['test_is_passed'] == 0) {
                            $list[$k]['develop_status_str'] = '待测试';
                        } elseif ($val['is_finish'] == 1 && $val['test_is_passed'] == 1 && $val['is_finish_task'] == 0) {
                            $list[$k]['develop_status_str'] = '待上线';
                        } elseif ($val['is_finish'] == 1 && $val['test_is_passed'] == 1 && $val['is_finish_task'] == 1 && $val['is_test_complete'] == 0) {
                            $list[$k]['develop_status_str'] = '待回测';
                        } elseif ($val['is_test_complete'] == 1) {
                            $list[$k]['develop_status_str'] = '已完成';
                        } else {
                            $list[$k]['develop_status_str'] = '开发ing';
                        }
                    } else {
                        if ($val['is_finish'] == 1 && $val['is_finish_task'] == 0) {
                            $list[$k]['develop_status_str'] = '待上线';
                        } elseif ($val['is_finish'] == 1  && $val['is_finish_task'] == 1) {
                            $list[$k]['develop_status_str'] = '已完成';
                        } else {
                            $list[$k]['develop_status_str'] = '开发ing';
                        }
                    }
                }

                $list[$k]['expected_time'] = $val['expected_time'] ? date('Y-m-d', strtotime($val['expected_time'])) : '';
                $list[$k]['estimated_time'] = $val['estimated_time'] ? date('Y-m-d', strtotime($val['estimated_time'])) : '';
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
        //bug列表页 是否有上线按钮权限
        $this->assignconfig('is_finish_bug', $this->auth->check('demand/develop_demand/is_finish_bug'));
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
            $filter = json_decode($this->request->get('filter'), true);
            $meWhere = '';
            if (isset($filter['me_task'])) {

                $adminId = session('admin.id');
                //经理
                $authUserIds = Auth::getUsersId('demand/develop_demand/review') ?: [];
                //经理
                if (in_array($adminId, $authUserIds)) {
                    $meWhere = "(review_status_manager = 0 or ( (is_test =1 and test_is_passed=1 and is_finish_task =0) or (is_test =0 and is_finish=1 and is_finish_task=0)  ) )";
                }

                //开发主管
                $authDevelopUserIds = Auth::getUsersId('demand/develop_demand/review_status_develop') ?: [];
                if (!in_array($adminId, $authUserIds) && in_array($adminId, $authDevelopUserIds)) {
                    $meWhere = "(is_finish_task =0 or FIND_IN_SET({$adminId},assign_developer_ids))"; //
                }

                //判断是否是普通的测试
                $testAuthUserIds = Auth::getUsersId('demand/develop_web_task/set_test_status') ?: [];
                if (!in_array($adminId, $authUserIds) && in_array($adminId, $testAuthUserIds)) {
                    $meWhere = "(is_test = 1 and FIND_IN_SET({$adminId},test_person) and is_test_complete =0)"; //测试用户
                }
                //显示有分配权限的人，此类人跟点上线的是一类人，此类人应该可以查看所有的权限
                $assignAuthUserIds = Auth::getUsersId('demand/it_web_demand/distribution') ?: [];
                if (in_array($adminId, $assignAuthUserIds)) {
                    $meWhere = "1 = 1";
                }
                // 不是主管和经理的, 是否为开发人或测试认，或创建人
                if (!$meWhere) {
                    $meWhere .= "FIND_IN_SET({$adminId},assign_developer_ids)  or FIND_IN_SET({$adminId},test_person)  or FIND_IN_SET({$adminId}, create_person_id)";
                }
                unset($filter['me_task']);
            }
           
            //搜索负责人
            if ($filter['nickname']) {
                //查询用户表id
                $admin = new \app\admin\model\Admin();
                $userIds = $admin->where('status', 'normal')->where('nickname', '=', $filter['nickname'] )->value('id');
                if ($userIds)  $map = "FIND_IN_SET({$userIds},assign_developer_ids)";
                unset($filter['nickname']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->where('type', '1')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($meWhere)
                ->where($map)
                ->where('type', '1')
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
                if ($val['is_test'] == 1 && $val['test_person'] != '') {
                    if (in_array(session('admin.id'), explode(',', $val['test_person']))) {
                        $list[$k]['is_test_record_hidden'] = 1; //显示 记录问题
                        $list[$k]['is_test_finish_hidden'] = 1; //显示  测试通过
                        $list[$k]['is_test_detail_log'] = 0; //不显示  问题详情
                    }
                }

                if ($val['assign_developer_ids'] != '') {
                    if (in_array(session('admin.id'), explode(',', $val['assign_developer_ids']))) {
                        $list[$k]['is_developer_opt'] = 1; //开发完成
                    }
                }

                if ($val['review_status_manager'] == 1 && $val['review_status_develop'] == 1) {
                    if ($val['is_test'] == 1) {
                        if ($val['is_finish'] == 1 && $val['test_is_passed'] == 0) {
                            $list[$k]['develop_status_str'] = '待测试';
                        } elseif ($val['is_finish'] == 1 && $val['test_is_passed'] == 1 && $val['is_finish_task'] == 0) {
                            $list[$k]['develop_status_str'] = '待上线';
                        } elseif ($val['is_finish'] == 1 && $val['test_is_passed'] == 1 && $val['is_finish_task'] == 1 && $val['is_test_complete'] == 0) {
                            $list[$k]['develop_status_str'] = '待回测';
                        } elseif ($val['is_test_complete'] == 1) {
                            $list[$k]['develop_status_str'] = '已完成';
                        } else {
                            $list[$k]['develop_status_str'] = '开发ing';
                        }
                    } else {
                        if ($val['is_finish'] == 1 && $val['is_finish_task'] == 0) {
                            $list[$k]['develop_status_str'] = '待上线';
                        } elseif ($val['is_finish'] == 1  && $val['is_finish_task'] == 1) {
                            $list[$k]['develop_status_str'] = '已完成';
                        } else {
                            $list[$k]['develop_status_str'] = '开发ing';
                        }
                    }
                }
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
        //bug列表页 是否有上线按钮权限
        $this->assignconfig('is_finish_bug', $this->auth->check('demand/develop_demand/is_finish_bug'));
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

                    if ($params['type'] == 1) { //如果为BUG类型,更新
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
                    Ding::dingHookByDevelop(__FUNCTION__, $this->model);
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('demand_type', input('demand_type'));
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
        $this->view->assign('demand_type', input('demand_type'));
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
            $res = $this->model->allowField(true)->save($data, ['id' => input('ids')]);
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
                    $res = $this->model->get(input('ids'));
                    Ding::dingHookByDevelop('review', $res);
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
                    $res = $this->model->get(input('id'));
                    Ding::dingHookByDevelop('distribution', $res);
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
        $result = $this->model->save($data, ['id' => $ids]);
        if ($result) {
            $res = $this->model->get(input('ids'));
            Ding::dingHookByDevelop('set_complete_status', $res);
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
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
                    $res = $this->model->get(input('ids'));
                    Ding::dingHookByDevelop('test_record_bug', $res);
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
        $map['type'] = 2;
        $map['is_del'] = 1;
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
            $res = $this->model->get(input('ids'));
            Ding::dingHookByDevelop('test_is_passed', $res);
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
            $res = $this->model->get(input('ids'));
            Ding::dingHookByDevelop('is_finish_task', $res);
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }


    /**
     * bug上线操作 更改is_finish_task字段
     *
     * @Description
     * @author fzg
     * @since 2020/05/07 16:52:22 
     * @param [type] $ids
     * @return void
     */
    public function is_finish_bug($ids = null)
    {
        $data['is_finish_task'] = 1;
        $data['finish_task_time'] = date('Y-m-d H:i:s', time());
        $res = $this->model->save($data, ['id' => $ids]);
        if ($res !== false) {
            $res = $this->model->get(input('ids'));
            Ding::dingHookByDevelop('is_finish_bug', $res);
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
                    $res = $this->model->get(input('ids'));
                    Ding::dingHookByDevelop('regression_test_info', $res);
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
