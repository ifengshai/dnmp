<?php

namespace app\admin\controller\saleaftermanage;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\saleaftermanage\WorkOrderCheckRule;
use app\admin\model\saleaftermanage\WorkOrderDocumentary;
use app\admin\model\saleaftermanage\WorkOrderProblemStep;
use app\admin\model\saleaftermanage\WorkOrderStepType;
use app\admin\model\platformManage\MagentoPlatform;

/**
 * 工单问题类型管理
 *
 * @icon fa fa-circle-o
 */
class Workorderconfig extends Backend
{

    /**
     * Workorderconfig模型对象
     * @var \app\admin\model\saleaftermanage\Workorderconfig
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\Workorderconfig;
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
                $res = $this->model->where(['type'=>$params['type'],'problem_belong'=>$params['problem_belong'],'problem_name'=>$params['problem_name'],'is_del'=>1])->find();
                if (!empty($res)) {
                    $this->error('当前问题已存在,请不要重复添加');
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
            if ($params) {
                $params = $this->preExcludeFields($params);
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
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 获取工单的配置信息
     *
     * @Description
     * @author lsw
     * @since 2020/06/19 11:04:57
     * @return void
     */
    public function getConfigInfo()
    {
        //所有问题类型
        $where['is_del'] = 1;
        $all_problem_type = $this->model->where($where)->select();
        //所有措施类型
        $all_step         = (new WorkOrderStepType)->where($where)->select();
        //所有平台
        $all_platform     = (new MagentoPlatform)->field('id,name')->select();
        //客服A组主管，B组主管
        $where_group['a.name'] =['in',['B组客服主管','A组客服主管']];
        $all_group =  Db::name('auth_group')->alias('a')->join('auth_group_access s ', 'a.id=s.group_id')->where($where_group)->field('a.id,a.name,s.uid')->select();
        //所有的跟单员规则
        $all_documentary = (new WorkOrderDocumentary)->select();
        //不存在问题类型
        if (!$all_problem_type) {
        }
        //不存在措施
        if (!$all_step) {
        }
        //不存在A、B组
        if (!$all_group) {
        }
        //不存在跟单规则
        if(!$all_documentary){

        }
        $all_problem_type = collection($all_problem_type)->toArray();
        $all_step         = collection($all_step)->toArray();
        //客服问题类型，仓库问题类型，大的问题类型分类,所有措施
        $customer_problem_type = $warehouse_problem_type = $customer_problem_classify_arr = $step = $platform = $kefumanage = [];
        foreach ($all_problem_type as $v) {
            if (1 == $v['type']) {
                $customer_problem_type[$v['id']] = $v['problem_name'];
            } elseif (2 == $v['type']) {
                $warehouse_problem_type[$v['id']] = $v['problem_name'];
            }
            $customer_problem_classify_arr[$v['problem_belong']][] =$v['id'];
        }
        foreach ($all_step as $sv) {
            $step[$sv['id']] = $v['step_name'];
        }
        foreach ($all_platform as $pv) {
            $platform[$pv['id']] = $pv['name'];
        }
        $a_group_id = $b_group_id = $a_uid = $b_uid = 0;
        foreach ($all_group as $av) {
            if ('A组客服主管' == $av['name']) {
                $a_group_id = $av['id'];
                $a_uid = $av['uid'];
            } elseif ('B组客服主管' == $av['name']) {
                $b_group_id = $av['id'];
                $b_uid = $av['uid'];
            }
        }
        //A、B下面的分组的所有的人
        $where_group_id['a.pid'] = ['in',[$a_group_id,$b_group_id]];
        $all_group_person =  Db::name('auth_group')->alias('a')->join('auth_group_access s ', 'a.id=s.group_id')->where($where_group_id)->field('a.id,a.pid,a.name,s.uid')->select();
        if (!$all_group_person) {
        }
        foreach ($all_group_person as $gv) {
            if ($a_group_id == $gv['pid']) {
                $kefumanage[$a_uid][] = $gv['uid'];
            } elseif ($b_group_id == $gv['pid']) {
                $kefumanage[$b_uid][] = $gv['uid'];
            }
        }
        
        $arr['customer_problem_type']         = $customer_problem_type;
        $arr['warehouse_problem_type']        = $warehouse_problem_type;
        $arr['customer_problem_classify_arr'] = $customer_problem_classify_arr;
        $arr['step']                          = $step;
        $arr['platform']                      = $platform;
        $arr['kefumanage']                    = $kefumanage;
        return $arr;
    }
}
