<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;
use think\Db;

/**
 * 技术部网站组需求管理
 *
 * @icon fa fa-circle-o
 */
class ItWebDemand extends Backend
{

    /**
     * ItWebDemand模型对象
     * @var \app\admin\model\demand\ItWebDemand
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\demand\ItWebDemand;

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 技术部网站需求列表
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
                ->where('type', 2)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type', 2)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $k => $v){
                $list[$k]['site_type'] = config('demand.siteType')[$v['site_type']];//取站点
                $entry_user_id_arr = explode(',',$v['entry_user_id']);
                foreach ($entry_user_id_arr as $k1 => $user_name ){
                    $entry_user_id_arr[$k1] = config('demand.entryUserId')[$user_name];//取提出人
                }

                //$list[$k]['entry_user_id'] = implode(",", $entry_user_id_arr);//取提出人姓名
                $list[$k]['entry_user_id'] = $entry_user_id_arr;//取提出人姓名
                $list[$k]['all_complexity'] = config('demand.allComplexity')[$v['all_complexity']];//复杂度

                $list[$k]['All_group'] = array();
                if($v['web_designer_group'] == 1){
                    $list[$k]['All_group'][] = '前端';
                }
                if($v['phper_group'] == 1){
                    $list[$k]['All_group'][] = '后端';
                }
                if($v['app_group'] == 1){
                    $list[$k]['All_group'][] = 'app';
                }
            }
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
                if (!$params['entry_user_id']) {
                    $this->error(__('提出人必选'));
                } else {
                    $params['entry_user_id'] = implode(",", $params['entry_user_id']);
                }

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

}
