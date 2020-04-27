<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class ItTestRecord extends Backend
{

    /**
     * ItTestRecord模型对象
     * @var \app\admin\model\demand\ItTestRecord
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItTestRecord;
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

            //自定义姓名搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['nickname']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . $filter['nickname'] . '%'];
                $id = $admin->where($smap)->value('id');
                $task_ids = $this->itWebTaskItem->where('person_in_charge', $id)->column('task_id');
                $map['id'] = ['in', $task_ids];
                unset($filter['nickname']);
                $this->request->get(['filter' => json_encode($filter)]);
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
            $demand = new \app\admin\model\demand\ItWebDemand();
            $demandInfo = $demand->where('is_del', 1)->column('title', 'id');
            foreach ($list as &$v) {
                $v['title'] = $demandInfo[$v['pid']];

                //前端组
                if ($v['responsibility_group'] == 1) {
                    $v['responsibility_user_name'] = $this->extract_username($v['responsibility_user_id'], 'web_designer_user');
                } elseif ($v['responsibility_group'] == 2) {
                    $v['responsibility_user_name'] = $this->extract_username($v['responsibility_user_id'], 'phper_user');
                } elseif ($v['responsibility_group'] == 3) {
                    $v['responsibility_user_name'] = $this->extract_username($v['responsibility_user_id'], 'app_user');
                }

                $v['create_user_name'] = config('demand.test_user')[$v['create_user_id']];
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /*
     * 取出配置文件的数据，
     * $user_id string 数据格式以逗号分隔
     * $config_name string 配置名称
     * */
    protected function extract_username($user_id, $config_name)
    {
        $user_id_arr = explode(',', $user_id);
        $user_name_arr = array();
        foreach ($user_id_arr as $v) {
            $user_name_arr[] = config('demand.' . $config_name)[$v];
        }
        $user_name = implode(',', $user_name_arr);
        return $user_name;
    }
}
