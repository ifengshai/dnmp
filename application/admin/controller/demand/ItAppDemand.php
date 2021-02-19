<?php

namespace app\admin\controller\demand;

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
}
