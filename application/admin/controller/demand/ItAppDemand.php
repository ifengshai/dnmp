<?php

namespace app\admin\controller\demand;

use app\api\controller\Ding;
use app\common\controller\Backend;
use Think\Db;

/**
 * app需求管理
 *
 * @icon fa fa-circle-o
 */
class ItAppDemand extends Backend
{
    
    /**
     * ItAppDemand模型对象
     * @var \app\admin\model\demand\ItAppDemand
     */
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItAppDemand;

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
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
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

//            foreach ($list as $row) {
//                $row->visible(['create_time','title','node_time','it_web_demand_id','version_number']);
//
//            }
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                if ($value['develop_finish_status'] ==1){
                    $list[$key]['develop_finish_status'] = '是';
                }else{
                    $list[$key]['develop_finish_status'] = '否';
                }
                if ($value['test_is_finish'] ==1){
                    $list[$key]['test_is_finish'] = '是';
                }else{
                    $list[$key]['test_is_finish'] = '否';
                }
                if ($value['online_status'] ==1){
                    $list[$key]['online_status'] = '是';
                }else{
                    $list[$key]['online_status'] = '否';
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                empty($params['importance']) && $this->error('请选择重要程度');
                empty($params['degree_of_urgency']) && $this->error('请选择紧急程度');
                empty($params['development_difficulty']) && $this->error('请选择开发难度');
                empty($params['priority']) && $this->error('请选择优先级');
                empty($params['node_time']) && $this->error('任务周期不能为空');
                if ($params['title'] !== $params['start_title'] || $params['content'] !== $params['start_content'] || $params['accessory'] !== $params['start_accessory']) {
                    $upload_value['secondary_operation'] = 2;
                }
                $upload_value['priority'] = $params['priority'];
                $upload_value['node_time'] = $params['node_time'];
                $upload_value['site_type'] = implode(',', $params['site_type']);
                $upload_value['product_remarks'] = $params['product_remarks'];
                $upload_value['type'] = $params['type'];
                $upload_value['site'] = $params['site'];
                //非空
                if (!empty($params['copy_to_user_id'])) {
                    $upload_value['copy_to_user_id'] = implode(',', $params['copy_to_user_id']);
                }
                $upload_value['title'] = $params['title'];
                $upload_value['content'] = $params['content'];
                $upload_value['remark'] = $params['remark'];
                $upload_value['accessory'] = $params['accessory'];
                $upload_value['is_emergency'] = $params['is_emergency'] ? $params['is_emergency'] : 0;
                $upload_value['functional_module'] = $params['functional_module'];
                $upload_value['importance'] = $params['importance'];
                $upload_value['degree_of_urgency'] = $params['degree_of_urgency'];
                $upload_value['development_difficulty'] = $params['development_difficulty'];

                $upload_value['priority'] = $params['priority'];
                if (!empty($params['important_reasons'])) {
                    $upload_value['important_reasons'] = implode(',', $params['important_reasons']);
                }
            }
            $res  = Db::name('it_app_demand')->where('id',$params['id'])->update($upload_value);
            if ($res){
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
        $row = $this->model->get($ids);
        $row = $row->toArray();
        $row['site_type_arr'] = explode(',', $row['site_type']);
        $row['copy_to_user_id_arr'] = explode(',', $row['copy_to_user_id']);
        $row['important_reasons'] = explode(',', $row['important_reasons']);
        $this->view->assign('demand_type', input('demand_type'));
        $this->view->assign("type", input('type'));
        $this->view->assign("row", $row);
        //确认权限
        $this->view->assign('pm_status', $this->auth->check('demand/it_web_demand/pm_status'));
        $this->view->assign('admin_id', session('admin.id'));
        return $this->view->fetch();
    }

    /**
     * 需求操作
     */
    public function operation_show($ids = null){
        $row = $this->model->get($ids);
        $row = $row->toArray();
        $this->assign('row',$row);
        return $this->view->fetch();
    }


    /**
     * 修改预期时间
     * 仅产品组可以操作
     */

    public function expected_time_of_modification(){
        if ($this->request->isPost()){
            $params = input('param.');
            if (empty($params['id'])){
                $this->error('缺少重要参数');
            }
            $app_demand = $this->model->find($params['id']);
            $app_demand->update_time = date('Y-m-d H:i:s',time());
            $app_demand->node_time = $params['node_time'];
            if ($app_demand->save()){
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
    }


    /**
     *确认版本号
     * 仅运营组可以操作
     */
    public function confirm_version_number(){
        if ($this->request->isPost()){
            $params = input('param.');
            if (empty($params['id'])){
                $this->error('缺少重要参数');
            }
            $app_demand = $this->model->find($params['id']);
            $app_demand->update_time = date('Y-m-d H:i:s',time());
            $app_demand->version_number = $params['version_number'];

            if ($app_demand->save()){
                $this->success('操作成功','It_app_demand/index');
            }else{
                $this->error('操作失败');
            }
        }
    }

    /**
     *开发完成
     * 仅开发组可以操作
     */
    public function development_iscomplete(){
        if ($this->request->isPost()){
            $params = input('param.');
            if (empty($params['id'])){
                $this->error('缺少重要参数');
            }
            $app_demand = $this->model->find($params['id']);
            $app_demand->update_time = date('Y-m-d H:i:s',time());
            $app_demand->develop_finish_time = date('Y-m-d H:i:s',time());
            $app_demand->develop_finish_status = 1;

            if ($app_demand->save()){
                //开发人员完成 推送给测试主管
                Ding::cc_ding('350', '任务ID:' . $params['id'] . '+任务已开发完毕，请安排人员测试', $app_demand->title, $this->request->domain() . url('index') . '?ref=addtabs');
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
    }

    /**
     *测试完成
     */
    public function testing_iscomplete(){
        if ($this->request->isPost()){
            $params = input('param.');
            if (empty($params['id'])){
                $this->error('缺少重要参数');
            }
            $app_demand = $this->model->find($params['id']);
            $app_demand->update_time = date('Y-m-d H:i:s',time());
            $app_demand->test_finish_time = date('Y-m-d H:i:s',time());
            $app_demand->test_is_finish = 1;

            if ($app_demand->save()){
                //测试完成后 钉钉推送产品
                Ding::cc_ding('350', '任务ID:' . $params['id'] . '+任务已测试完毕，准备上线', $app_demand->title, $this->request->domain() . url('index') . '?ref=addtabs');
                $this->success('操作成功','');
            }else{
                $this->error('操作失败');
            }
        }
    }
    /**
     *上线操作
     *
     */
    public function online_iscomplete(){
        if ($this->request->isPost()){
            $params = input('param.');
            if (empty($params['id'])){
                $this->error('缺少重要参数');
            }
            $app_demand = $this->model->find($params['id']);
            $app_demand->update_time = date('Y-m-d H:i:s',time());
            $app_demand->online_finish_time = date('Y-m-d H:i:s',time());
            $app_demand->online_status = 1;

            if ($app_demand->save()){
                //上线后 钉钉推送给提出人
                Ding::cc_ding('350', '任务ID:' . $params['id'] . '+任务完成，已提交上线', $app_demand->title, $this->request->domain() . url('index') . '?ref=addtabs');
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
    }
}
