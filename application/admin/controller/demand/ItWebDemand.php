<?php

namespace app\admin\controller\demand;

use app\common\controller\Backend;
use think\Db;
use think\Request;

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
                $list[$k]['sitetype'] = config('demand.siteType')[$v['site_type']];//取站点
                $user_detail = $this->auth->getUserInfo($list[$k]['entry_user_id']);
                $list[$k]['entry_user_name'] = $user_detail['nickname'];//取提出人

                $list[$k]['allcomplexity'] = config('demand.allComplexity')[$v['all_complexity']];//复杂度

                $list[$k]['Allgroup'] = array();
                if($v['web_designer_group'] == 1){
                    $list[$k]['Allgroup'][] = '前端';
                }
                if($v['phper_group'] == 1){
                    $list[$k]['Allgroup'][] = '后端';
                }
                if($v['app_group'] == 1){
                    $list[$k]['Allgroup'][] = 'app';
                }
                if($v['test_group'] == 1){
                    $list[$k]['testgroup'] = '是';
                }else{
                    $list[$k]['testgroup'] = '否';
                }

                //权限赋值
                $this->user_id = $this->auth->id;
                //检查有没有权限
                $list[$k]['demand_add'] = $this->auth->check('demand/it_web_demand/add');
                $list[$k]['demand_through_demand'] = $this->auth->check('demand/it_web_demand/through_demand');
                $list[$k]['demand_distribution'] = $this->auth->check('demand/it_web_demand/distribution');
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
                if ($params['copy_to_user_id']) {
                    $params['copy_to_user_id'] = implode(",", $params['copy_to_user_id']);
                }
                $params['entry_user_id'] = $this->auth->id;

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

        /*$user_id = $this->auth->id;
        $user_name = $this->auth->username;
        $this->view->assign('user_id',$this->auth->id);
        $this->view->assign('user_name', $this->auth->username);*/
        return $this->view->fetch();
    }

    /**
     * 通过需求
     * */
    public function through_demand($ids = null)
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
        if ($this->request->isAjax()) {
            //$this->success('成功','',$a);

            $data['status'] =  2;
            $res = $this->model->allowField(true)->save($data,['id'=> input('ids')]);
            if ($res) {
                $this->success('成功','',$ids);
            } else {
                $this->error('失败');
            }
        }
    }


    /**
     * 分配
     */
    public function distribution($ids = null)
    {
        if($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $update_date = array();
                if($params['status'] == 1){
                    if($params['test_group'] == 1){
                        if(!$params['test_user_id']){
                            $this->error('未分配测试责任人');
                        }
                        $update_date['test_group'] = $params['test_group'];
                        $update_date['test_complexity'] = $params['test_complexity'];
                        $update_date['test_user_id'] = implode(',',$params['test_user_id']);
                    }else{
                        $update_date['test_group'] = $params['test_group'];
                        $update_date['test_complexity'] = '';
                        $update_date['test_user_id'] = '';
                    }
                    $update_date['status'] = 2;
                }
                if($params['status'] == 2){
                    if($params['web_designer_group'] == 1){
                        if(!$params['web_designer_user_id']){
                            $this->error('未分配前端责任人');
                        }
                        $update_date['web_designer_group'] = $params['web_designer_group'];
                        $update_date['web_designer_complexity'] = $params['web_designer_complexity'];
                        $update_date['web_designer_expect_time'] = $params['web_designer_expect_time'];
                        $update_date['web_designer_user_id'] = implode(',',$params['web_designer_user_id']);
                    }
                    if($params['phper_group'] == 1){
                        if(!$params['phper_user_id']){
                            $this->error('未分配后端责任人');
                        }
                        $update_date['phper_group'] = $params['phper_group'];
                        $update_date['phper_complexity'] = $params['phper_complexity'];
                        $update_date['phper_expect_time'] = $params['phper_expect_time'];
                        $update_date['phper_user_id'] = implode(',',$params['phper_user_id']);
                    }
                    if($params['app_group'] == 1){
                        if(!$params['app_user_id']){
                            $this->error('未分配app责任人');
                        }
                        $update_date['app_group'] = $params['app_group'];
                        $update_date['app_complexity'] = $params['app_complexity'];
                        $update_date['app_expect_time'] = $params['app_expect_time'];
                        $update_date['app_user_id'] = implode(',',$params['app_user_id']);
                    }

                }

                $res = $this->model->allowField(true)->save($update_date,['id'=> $params['id']]);
                if ($res) {
                    $this->success('成功','',$ids);
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row = $this->model->get(['id' => $ids]);
        $row_arr = $row->toArray();

        //如果已分配前端人员
        if($row_arr['web_designer_user_id']){
            $web_userids = explode(',',$row_arr['web_designer_user_id']);
            foreach ($web_userids as $k1 => $v1){
                $web_userid_arr[$k1]['user_id'] = $v1;
                $web_userid_arr[$k1]['user_name'] = config('demand.web_designer_user')[$v1];
            }
        }

        //如果已分配后端人员
        if($row_arr['phper_user_id']){
            $phper_userids = explode(',',$row_arr['phper_user_id']);
            foreach ($phper_userids as $k2 => $v2){
                $phper_userid_arr[$k2]['user_id'] = $v2;
                $phper_userid_arr[$k2]['user_name'] = config('demand.phper_user')[$v2];
            }
        }

        //如果已分配app人员
        if($row_arr['app_user_id']){
            $app_userids = explode(',',$row_arr['app_user_id']);
            foreach ($app_userids as $k3 => $v3){
                $app_userid_arr[$k3]['user_id'] = $v3;
                $app_userid_arr[$k3]['user_name'] = config('demand.app_user')[$v3];
            }
        }


        //如果已分配app人员
        if($row_arr['test_group'] == 1){
            if($row_arr['test_user_id']){
                $test_userids = explode(',',$row_arr['test_user_id']);
                foreach ($test_userids as $k4 => $v4){
                    $test_userid_arr[$k4]['user_id'] = $v4;
                    $test_userid_arr[$k4]['user_name'] = config('demand.test_user')[$v4];
                }
            }
        }

        $this->view->assign("web_userid_arr", $web_userid_arr);
        $this->view->assign("phper_userid_arr", $phper_userid_arr);
        $this->view->assign("app_userid_arr", $app_userid_arr);
        $this->view->assign("test_userid_arr", $test_userid_arr);

        $this->view->assign("row", $row_arr);
        return $this->view->fetch();
    }


}
