<?php

namespace app\admin\controller\zendesk;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class ZendeskReply extends Backend
{
    
    /**
     * ZendeskReply模型对象
     * @var \app\admin\model\zendesk\ZendeskReply
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\ZendeskReply;

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
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $data = collection($list)->toArray();
            foreach ($list as $key => &$v) {
                $data[$key]['key_preg'] = $v->key_preg;
                //获取客户详情里第一次回复的内容
                $details = $v->details;
                $data[$key]['answer_preg'] = '';
                foreach($details as $detail){
                    if($detail->is_admin == 2){ //客户第一次回复
                        $data[$key]['answer_preg'] = $detail->key_preg;
                        break;
                    }
                }
            }
            $result = array("total" => $total, "rows" => $data);

            return json($result);
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
            $result = $row->allowField(true)->save($params);
            if($result){
                //更改zendesk的状态
                $zendesk = controller('Api/Zendesk');
                $query = [
                    'tags' => explode(',',$params['tags']),
                    'status' => 'pending'
                ];
                $res = $zendesk->autoUpdate($row->email_id,$query);
                if($res){
                    $this->success();
                }
                $this->error('请重新尝试修改');
            }
        }
        $this->view->assign("row", $row);
        $status = ['new' => 'new','open' => 'open', 'pending' => 'pending','solved' => 'solved'];
        $this->view->assign("status", $status);
        return $this->view->fetch();
    }
    

}
