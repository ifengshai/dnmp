<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\common\controller\Backend;
use fast\Tree;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 工单跟单规则管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrderDocumentary extends Backend
{
    
    /**
     * WorkOrderDocumentary模型对象
     * @var \app\admin\model\saleaftermanage\WorkOrderDocumentary
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderDocumentary;
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);
        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupdata = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
            foreach ($result as $k => $v) {
                $groupdata[$v['id']] = $v['name'];
            }
        } else {
            $result = [];
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $childlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['id']));
                $temp = [];
                foreach ($childlist as $k => $v) {
                    $temp[$v['id']] = $v['name'];
                }
                $result[__($n['name'])] = $temp;
            }
            $groupdata = $result;
        }

        $this->view->assign('groupdata', $groupdata);

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
     * 添加工单跟单规则
     *
     * @return string
     * @return void
     * @throws Exception
     * @Description
     * @since 2020/6/24 9:29
     * @author jhh
     */
    public function add()
    {
        $judge = Cache::has('Workorderconfig_getConfigInfo');
        //判断缓存是否存在
        if ($judge === true) {
            //清除单个缓存文件
            $result = Cache::rm('Workorderconfig_getConfigInfo');
        }
        $workordersteptype = new \app\admin\model\saleaftermanage\Workorderconfig();
        //获取创建人信息
        $admin = new Admin();
        $create_person = $admin->getAllStaff();
        //获取所有创建组
        $extend_team = $workordersteptype->getAllExtend();

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
                    //当前登录人创建
                    if ($params['create_belong'] == 1){
                        //此人对应的跟单规则是否存在
                        $info = db('work_order_documentary')->where(['type'=>2,'create_id'=>$params['person']])->find();
                        if (!empty($info)){
                            $this->error(__('当前人对应的跟单规则已存在', ''));
                        }
                        $data['type'] = 2;
                        $data['create_id'] = $params['person'];
                        $data['create_name'] = db('admin')->where('id',$params['person'])->value('nickname');
                        $data['documentary_group_id'] = $params['follow'];
                        $data['documentary_group_name'] = db('auth_group')->where('id',$params['follow'])->value('name');
                    }else{
                        //组创建 此组对应的跟单规则是否存在
                        $info = db('work_order_documentary')->where(['type'=>1,'create_id'=>$params['extend']])->find();
                        if (!empty($info)){
                            $this->error(__('当前组对应的跟单规则已存在', ''));
                        }
                        $data['type'] = 1;
                        $data['create_id'] = $params['extend'];
                        $data['create_name'] = db('auth_group')->where('id',$params['extend'])->value('name');
                        $data['documentary_group_id'] = $params['follow'];
                        $data['documentary_group_name'] = db('auth_group')->where('id',$params['follow'])->value('name');
                    }
                    $result = $this->model->allowField(true)->save($data);
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

        $this->view->assign("extend_team", $extend_team);
        $this->view->assign("create_person", $create_person);
        return $this->view->fetch();
    }

    /**
     * 工单跟单规则编辑
     *
     * @param null $ids
     * @return string
     * @return void
     * @throws \think\exception\DbException
     * @Description
     * @throws Exception
     * @since 2020/6/24 10:17
     * @author jhh
     */
    public function edit($ids = null)
    {
        $judge = Cache::has('Workorderconfig_getConfigInfo');
        //判断缓存是否存在
        if ($judge === true) {
            //清除单个缓存文件
            $result = Cache::rm('Workorderconfig_getConfigInfo');
        }
        $workordersteptype = new \app\admin\model\saleaftermanage\Workorderconfig();
        //获取创建人信息
        $admin = new Admin();
        $create_person = $admin->getAllStaff();
        //获取所有创建组
        $extend_team = $workordersteptype->getAllExtend();
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
                    //当前登录人创建
                    if ($params['create_belong'] == 1){
                        $data['type'] = 2;
                        $data['create_id'] = $params['person'];
                        $data['create_name'] = db('admin')->where('id',$params['person'])->value('nickname');
                        $data['documentary_group_id'] = $params['follow'];
                        $data['documentary_group_name'] = db('auth_group')->where('id',$params['follow'])->value('name');
                    }else{
                        //组创建 此组对应的跟单规则是否存在
                        $data['type'] = 1;
                        $data['create_id'] = $params['extend'];
                        $data['create_name'] = db('auth_group')->where('id',$params['extend'])->value('name');
                        $data['documentary_group_id'] = $params['follow'];
                        $data['documentary_group_name'] = db('auth_group')->where('id',$params['follow'])->value('name');
                    }
                    $result = $row->allowField(true)->save($data);
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

        $this->view->assign("extend_team", $extend_team);
        $this->view->assign("create_person", $create_person);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
