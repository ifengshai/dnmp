<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 开发任务管理
 *
 * @icon fa fa-circle-o
 */
class ItWebTask extends Backend
{

    /**
     * ItWebTask模型对象
     * @var \app\admin\model\demand\ItWebTask
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItWebTask;
        $this->itWebTaskItem = new \app\admin\model\demand\ItWebTaskItem;
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
            foreach ($list as $k => $v) {
                $list[$k]['sitetype'] = config('demand.siteType')[$v['site_type']]; //取站点
            }
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
                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);
                    //添加分配信息
                    if ($result !== false) {
                        $group_type = $this->request->post("group_type/a");
                        $person_in_charge = $this->request->post("person_in_charge/a");
                        $title = $this->request->post("title/a");
                        $desc = $this->request->post("desc/a");
                        $plan_date = $this->request->post("plan_date/a");

                        $data = [];
                        foreach ($group_type as $k => $v) {
                            $data[$k]['person_in_charge'] = $person_in_charge[$k];
                            $data[$k]['group_type'] = $v;
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
        $this->assignconfig('web_designer_user', config('demand.web_designer_user'));
        $this->assignconfig('phper_user', config('demand.phper_user'));
        $this->assignconfig('app_user', config('demand.app_user'));
        $this->assignconfig('test_user', config('demand.test_user'));
        $this->assign('siteType', config('demand.siteType'));
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
                        $group_type = $this->request->post("group_type/a");
                        $person_in_charge = $this->request->post("person_in_charge/a");
                        $title = $this->request->post("title/a");
                        $desc = $this->request->post("desc/a");
                        $plan_date = $this->request->post("plan_date/a");
                        $item_id = $this->request->post("item_id/a");
                        $data = [];
                        foreach ($group_type as $k => $v) {
                            $data[$k]['person_in_charge'] = $person_in_charge[$k];
                            $data[$k]['group_type'] = $v;
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
        $this->assignconfig('web_designer_user', config('demand.web_designer_user'));
        $this->assignconfig('phper_user', config('demand.phper_user'));
        $this->assignconfig('app_user', config('demand.app_user'));
        $this->assignconfig('test_user', config('demand.test_user'));
        $this->assign('siteType', config('demand.siteType'));
        return $this->view->fetch();
    }


    /**
     * 编辑
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
        $this->assignconfig('web_designer_user', config('demand.web_designer_user'));
        $this->assignconfig('phper_user', config('demand.phper_user'));
        $this->assignconfig('app_user', config('demand.app_user'));
        $this->assignconfig('test_user', config('demand.test_user'));
        $this->assign('siteType', config('demand.siteType'));
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
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $id = input('id');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->itWebTaskItem
                ->where($where)
                ->where('task_id', $id)
                ->order($sort, $order)
                ->count();

            $list = $this->itWebTaskItem
                ->where($where)
                ->where('task_id', $id)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$v) {
                if ($v['group_type'] == 1) {
                    $v['person_in_charge_text'] = config('demand.web_designer_user')[$v['person_in_charge']];
                } elseif ($v['group_type'] == 2) {
                    $v['person_in_charge_text'] = config('demand.phper_user')[$v['person_in_charge']];
                } elseif ($v['group_type'] == 3) {
                    $v['person_in_charge_text'] = config('demand.app_user')[$v['person_in_charge']];
                } elseif ($v['group_type'] == 4) {
                    $v['person_in_charge_text'] = config('demand.test_user')[$v['person_in_charge']];
                }
                $v['user_id'] = session('admin.id');
            }
            unset($v);
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('id', $ids);
        $this->assignconfig('test_user', config('demand.test_user'));
        return $this->view->fetch();
    }

    /**
     * 更改完成状态
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
     * 更改完成状态
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
            $data['test_person'] = session('admin.nickname');
            $res = $this->itWebTaskItem->save($data, ['id' => $ids]);
            //有错误 则回滚数据
            if (!$res) {
                throw new Exception("修改失败");
            }
            //查询同记录下是否还存在未测试通过的数据
            $itWebTaskInfo = $this->itWebTaskItem->get($ids);
            $map['task_id'] = $itWebTaskInfo['task_id'];
            $map['is_test_adopt'] = 0;
            $num = $this->itWebTaskItem->where($map)->count();
            //如果不存在则修改主记录测试完成 并更新时间
            if ($num == 0) {
                $list['is_test_adopt'] = 1;
                $list['test_adopt_time'] = date('Y-m-d H:i:s', time());
                $this->model->save($list, ['id' => $itWebTaskInfo['task_id']]);
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

        if ($res !== false) {
            $this->success('操作成功！！');
        } else {
            $this->error('操作失败！！');
        }
    }
}
