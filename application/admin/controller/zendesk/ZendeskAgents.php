<?php

namespace app\admin\controller\zendesk;
use think\Db;
use app\admin\model\Admin;
use app\admin\model\zendesk\Zendesk;
use app\common\controller\Backend;
use app\admin\model\zendesk\ZendeskAccount;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
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
        //$user = Admin::where('status','normal')->column('nickname','id');
        $user = (new Admin())->getStaffListss();
        
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
                ->with(['admin'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['admin'])
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
        $account = (new ZendeskAccount())->getAccountList(1);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if(!empty($params['name'])){
                    //获取平台绑定信息
                    $zeedeskAccount = (new ZendeskAccount())->getNameById($params['name']);
                    if($zeedeskAccount){
                        $params['name']     = $zeedeskAccount['account_user'];
                        $params['agent_id'] = $zeedeskAccount['account_id'];
                        $zeedeskAccountId   = $zeedeskAccount['id'];
                    }
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
                    //更新账户信息
                    Db::name('zendesk_account')->where(['id'=>$zeedeskAccountId])->update(['is_used'=>2]);
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('account',$account);
        return $this->view->fetch();
    } 
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $account = (new ZendeskAccount())->getAccountList(1,2);
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
                if(!empty($params['name'])){
                    //获取平台绑定信息
                    $zeedeskAccount = (new ZendeskAccount())->getNameById($params['name']);
                    if($zeedeskAccount){
                        $params['name']     = $zeedeskAccount['account_user'];
                        $params['agent_id'] = $zeedeskAccount['account_id'];
                        $zeedeskAccountId   = $zeedeskAccount['id'];
                    }
                }
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
                    Db::name('zendesk_account')->where(['id'=>$zeedeskAccountId])->update(['is_used'=>2]);                    
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //默认z站的zeedesk账号
        $this->assign('account',$account);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 异步获取zendesk账户信息
     *
     * @Description
     * @author lsw
     * @since 2020/03/30 15:43:59 
     * @return void
     */
    public function get_zendesk_account()
    {
        if ($this->request->isAjax()) {
            $platform = $this->request->post('platform');
            if (!$platform) {
                return $this->error('没有选择站点,请重新尝试', '', 'error', 0);
            }
            $result = (new ZendeskAccount())->getAccountList($platform);
            if (!$result) {
                return $this->error('这个站点没有账户', '', 'error', 0);
            }
            return $this->success('', '', $result, 0);
        } else {
            return $this->error('请求错误,请重新尝试', '', 'error', 0);
        }       
    }
}
