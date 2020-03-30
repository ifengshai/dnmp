<?php

namespace app\admin\controller\zendesk;

use app\admin\model\Admin;
use app\common\controller\Backend;

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
     * 获取平台Agents用户
     *
     * @Description
     * @author lsw
     * @since 2020/03/28 14:58:26 
     * @return void
     */
    public function getPlatformUser()
    {
        $res = (new Notice(request(),['type' => 'zeelool']))->fetchUser();
    }
}
