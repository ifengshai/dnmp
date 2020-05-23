<?php

namespace app\admin\controller\demand\testmanage;

use app\common\model\Auth;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\controller\Backend;

/**
 * 测试优化管理
 *
 * @icon fa fa-circle-o
 */
class ItTestOptimize extends Backend
{
    
    /**
     * ItTestOptimize模型对象
     * @var \app\admin\model\demand\testManage\ItTestOptimize
     */
    protected $model = null;
    protected $itWebDemand = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\testmanage\ItTestOptimize;
        $this->view->assign('orderPlatformList',config('demand.siteType'));
        $adminId = session('admin.id');
        $isTest = 0;
        //判断是否是普通的测试
        $testAuthUserIds = Auth::getUsersId('demand/it_web_demand/test_group_finish') ?: [];
        if(in_array($adminId,$testAuthUserIds)){
            $isTest = 1;
        }
        $this->assignconfig('isTest',$isTest);

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
            if(!empty($list)){
                foreach($list as $k => $v){
                    if(!empty($v['optimize_site_type'])){
                        $list[$k]['optimize_site_type'] = config('demand.siteType')[$v['optimize_site_type']];
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        if(148 == session('admin.id')){
            $isTests = 1;
        }else{
            $isTests = 0;
        }
        $this->assignconfig('isCheck',$isTests);
        $this->assignconfig('is_test_opt_del', $this->auth->check('demand/testmanage/it_test_optimize/del'));//删除按钮
        $this->assignconfig('is_test_opt_edit', $this->auth->check('demand/testmanage/it_test_optimize/edit'));//编辑按钮
        $this->assignconfig('is_test_opt_plan', $this->auth->check('demand/testmanage/it_test_optimize/plan'));//安排按钮
        $this->assignconfig('is_test_opt_handle', $this->auth->check('demand/testmanage/it_test_optimize/not_handle'));//处理按钮

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
                    $params['create_person_id'] = session('admin.id');
                    $params['create_person']    = session('admin.nickname');
                    $params['create_time']      = date("Y-m-d H:i:s",time());
                    $params['update_time']      = date("Y-m-d H:i:s",time());
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
                    $params['update_time']   = date("Y-m-d H:i:s",time());
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
     * 安排
     *
     * @Description
     * @author lsw
     * @since 2020/03/31 15:31:30 
     * @return void
     */
    public function plan($ids = null)
    {
        $this->itWebDemand =  new \app\admin\model\demand\ItWebDemand;
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
                $params['site_type']        = $row['optimize_site_type'];
                $params['entry_user_id']    = $row['create_person_id'];
                $params['title']            = $row['optimize_title'];
                $params['content']          = $row['optimize_description'];
                $params['status']           = 1;
                $params['create_time']      = $optimize['update_time'] =  date("Y-m-d H:i:s",time());
                $optimize['optimize_type']  = $params['type'];
                $optimize['operate_status'] = 1;
                $optimize['optimize_status'] = 2;

                $result = false;
                $info   = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $this->itWebDemand->allowField(true)->save($params);
                    $info = $row->allowField(true)->save($optimize);
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
                if (($result !== false) &&($info !== false)) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("demand_type",config('demand.demand_type'));
        $this->view->assign("allComplexity",config('demand.allComplexity'));
        $this->view->assign("row", $row);
        return $this->view->fetch();        
    }
    /**
     * 暂不处理
     *
     * @Description
     * @author lsw
     * @since 2020/03/31 17:58:00 
     * @return void
     */
    public function not_handle($ids=null)
    {
        if($this->request->isAjax()){
            $row = $this->model->get($ids);
            if (1 !=$row['optimize_status']) {
                $this->error(__('只有待处理状态才能此操作'));
            }
            $map['id'] = $ids;
            $data['optimize_status'] = 3;
            $data['update_time'] = date("Y-m-d H:i:s",time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }           
        }else{
            return $this->error('404 Not found');
        }
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

}
