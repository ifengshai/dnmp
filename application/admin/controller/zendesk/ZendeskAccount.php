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
                    }else{
                        $list[$k]['account_type'] = 'nihao';
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
            $zee_accountIdArr = $this->model->where('account_type',1)->column('account_id');
            $voo_accountIdArr = $this->model->where('account_type',2)->column('account_id');
            $nihao_accountIdArr = $this->model->where('account_type',3)->column('account_id');

            $zeelool_res = (new Notice(request(),['type' => 'zeelool']))->fetchUser(['role'=>'agent']);
            $voogueme_res = (new Notice(request(),['type' => 'voogueme']))->fetchUser(['role'=>'agent']);
            $nihao_res = (new Notice(request(),['type' => 'nihaooptical']))->fetchUser(['role'=>'admin']);
            $zeelool_info = $this->object_array($zeelool_res);
            $voogueme_info = $this->object_array($voogueme_res);
            $nihao_info = $this->object_array($nihao_res);

            if(!$zeelool_info && !$voogueme_info && !$nihao_info){
                return $this->error('账户配置错误，请联系开发人员');
            }
            $data = array();
            foreach($zeelool_info['users'] as $k=> $v){
                //已经存在的进行更新
                if(in_array($v['id'],$zee_accountIdArr)){

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
            if(!empty($data)){
                Db::name('zendesk_account')->insertAll($data);
                $data = array();
            }

            foreach($voogueme_info['users'] as $vk=> $vv){
                //已经存在的进行更新
                if(in_array($vv['id'],$voo_accountIdArr)){
                    $updateData = [
                        'user_type' => 2,
                        'account_user' => $vv['name'],
                        'account_email' => $vv['email'],
                    ];
                    //判断是否已绑定
                    $agent = \app\admin\model\zendesk\ZendeskAgents::where('agent_id',$vv['id'])->find();
                    if(!$agent){
                        $updateData['is_used'] = 1;
                    }else{
                        $updateData['is_used'] = 2;
                    }
                    $this->model->where('account_id',$vv['id'])->update($updateData);
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
            foreach($nihao_info['users'] as $nk=> $nv){
                //已经存在的进行更新
                if(in_array($nv['id'],$nihao_accountIdArr)){
                    $updateData = [
                        'user_type' => 2,
                        'account_user' => $nv['name'],
                        'account_email' => $nv['email'],
                    ];
                    //判断是否已绑定
                    $agent = \app\admin\model\zendesk\ZendeskAgents::where('agent_id',$nv['id'])->find();
                    if(!$agent){
                        $updateData['is_used'] = 1;
                    }else{
                        $updateData['is_used'] = 2;
                    }
                    $this->model->where('account_id',$nv['id'])->update($updateData);
                    continue;
                }
                $data[$nk]['user_type']      = 2;
                $data[$nk]['account_id']     = $nv['id'];
                $data[$nk]['account_type']   = 3;
                $data[$nk]['account_user']   = $nv['name'];
                $data[$nk]['account_email']  = $nv['email'];
            }
            if(!empty($data)){
                Db::name('zendesk_account')->insertAll($data);
            }
            return $this->success('账户刷新完毕');
        }else{
            return $this->error('404 Not found');
        }

    }
    /**
     * 
     *
     * @Description
     * @author lsw
     * @since 2020/05/22 13:56:10 
     * @return void
     */
    public function test()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.simplesat.io/api/answers/?page_size=3",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "X-Simplesat-Token: 83722ccb715d404c122464b6b072077812e6991c"
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $arr = json_decode($response,true);
        var_dump($arr);
    }

}
