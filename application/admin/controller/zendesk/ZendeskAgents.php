<?php

namespace app\admin\controller\zendesk;

use app\admin\model\Admin;
use app\admin\model\zendesk\ZendeskAccount;
use app\common\controller\Backend;
use think\Db;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class ZendeskAgents extends Backend
{
    
    /**
     * ZendeskAgents模型对象
     * @var \app\admin\model\zendesk\ZendeskAgents
     */
    protected $model = null;
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\ZendeskAgents;
        $type = [
            1 => 'zeelool',
            2 => 'voogueme',
        ];
        $agent_type = [
            1 => '邮件组',
            2 => '电话组'
        ];
        $user = Admin::where('status','normal')->column('nickname','id');
        $this->assign(compact('type','agent_type','user'));
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
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
                ->with(['admin','agent'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['admin','agent'])
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
                    if($result){
                        //is_used变为2
                        ZendeskAccount::where('account_id',$params['agent_id'])->setField('is_used',2);
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
            $agent_id = $row->agent_id;
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
                    if ($result) {
                        //is_used变为2
                        ZendeskAccount::where('account_id',$params['agent_id'])->setField('is_used',2);
                        ZendeskAccount::where('account_id',$agent_id)->setField('is_used',1);
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
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $agents = ZendeskAccount::where('account_type',$row->type)->field('account_id,account_user')->select();
        $this->view->assign("row", $row);
        $this->view->assign("agents", $agents);
        return $this->view->fetch();
    }

    /**
     * ajax获取zendesk管理员列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAgents()
    {
        if($this->request->isPost()) {
            $type = input('type');
            $agents = ZendeskAccount::where(['account_type' => $type, 'is_used' => 1])->field('account_id,account_user')->select();
            $html = '<option value="">请选择</option>';
            foreach($agents as $agent){
                $html .= "<option value='{$agent->account_id}'>{$agent->account_user}</option>";
            }
            return $html;
        }
        $this->error('not found');
    }

    /**
     * zendesk列表筛选获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAgentsList()
    {
        $agents = $this->model->with('admin')->select();
        $res = [];
        foreach($agents as $agent){
            $res[] = $agent->admin->nickname;
        }
        return $res;
    }

    /**
     * 删除
     * @param string $ids
     */
    public function del($ids = "")
    {
        if ($this->request->isAjax()) {
            $agentIds = $this->model->where('id','in',$ids)->column('agent_id');
            $this->model->where('id','in',$ids)->delete();
            $res = ZendeskAccount::where('account_id','in',$agentIds)->setField('is_used',1);
            if ($res) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }
}
