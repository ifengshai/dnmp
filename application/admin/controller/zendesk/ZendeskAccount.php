<?php

namespace app\admin\controller\zendesk;
use think\Db;
use app\common\controller\Backend;

/**
 * zendesk账户管理
 *
 * @icon fa fa-circle-o
 */
class ZendeskAccount extends Backend
{
    
    /**
     * ZendeskAccount模型对象
     * @var \app\admin\model\zendesk\ZendeskAccount
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\ZendeskAccount;

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

            $list = collection($list)->toArray();
            if(!empty($list)){
                foreach($list as $k =>$v){
                    if(1 == $v['account_type']){
                        $list[$k]['account_type'] = 'zeelool';
                    }elseif(2 == $v['account_type']){
                        $list[$k]['account_type'] = 'voogueme';
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 对象转数组
     *
     * @Description
     * @author lsw
     * @since 2020/03/30 11:19:37 
     * @param [type] $array
     * @return void
     */
    function object_array($array) {  
        if(is_object($array)) {  
            $array = (array)$array;  
        } 
        if(is_array($array)) {
            foreach($array as $key=>$value) {  
                $array[$key] = $this->object_array($value);  
            }  
        }  
        return $array;  
    }
    /**
     * 刷新账户
     *
     * @Description
     * @author lsw
     * @since 2020/03/31 10:01:35 
     * @return void
     */
    public function refresh_account()
    {
        if($this->request->isAjax()){
            //求出所有的account_id
            $accountIdArr = $this->model->column('account_id');
            $zeelool_res = (new Notice(request(),['type' => 'zeelool']))->fetchUser(['role'=>'admin']);
            $voogueme_res = (new Notice(request(),['type' => 'voogueme']))->fetchUser(['role'=>'admin']);
            $zeelool_info = $this->object_array($zeelool_res);
            $voogueme_info = $this->object_array($voogueme_res);
            if(!$zeelool_info && !$voogueme_info){
                return $this->error('账户配置错误，请联系开发人员');
            }
            $data = [];
            foreach($zeelool_info['users'] as $k=> $v){
                //已经存在的进行更新
                if(in_array($v['id'],$accountIdArr)){

                    $updateData = [
                        'user_type' => 2,
                        'account_user' => $v['name'],
                        'account_email' => $v['email'],
                    ];
                    //判断是否已绑定
                    $agent = \app\admin\model\zendesk\ZendeskAgents::where('agent_id',$v['id'])->find();
                    if(!$agent){
                        $updateData['is_used'] = 1;
                    }else{
                        $updateData['is_used'] = 2;
                    }
                    $this->model->where('account_id',$v['id'])->update($updateData);
                    continue;
                }
                $data[$k]['user_type']      = 2;
                $data[$k]['account_id']     = $v['id'];
                $data[$k]['account_type']   = 1;
                $data[$k]['account_user']   = $v['name'];
                $data[$k]['account_email']  = $v['email'];
                
            }
            foreach($voogueme_info['users'] as $vk=> $vv){
                if(in_array($vv['id'],$accountIdArr)){
                    $updateData = [
                        'user_type' => 2,
                        'account_user' => $v['name'],
                        'account_email' => $v['email'],
                    ];
                    //判断是否已绑定
                    $agent = \app\admin\model\zendesk\ZendeskAgents::where('agent_id',$vv['id'])->find();
                    if(!$agent){
                        $updateData['is_used'] = 1;
                    }else{
                        $updateData['is_used'] = 2;
                    }
                    $this->model->where('account_id',$v['id'])->update($updateData);
                    continue;
                }
                $data[$vk]['user_type']      = 2;
                $data[$vk]['account_id']     = $vv['id'];
                $data[$vk]['account_type']   = 2;
                $data[$vk]['account_user']   = $vv['name'];
                $data[$vk]['account_email']  = $vv['email'];
                
            }
            if(!empty($data)){
                Db::name('zendesk_account')->insertAll($data);
            }        
            return $this->success('账户刷新完毕');
        }else{
            return $this->error('404 Not found');
        }

    }

}
