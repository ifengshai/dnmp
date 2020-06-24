<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\controller\auth\Admin;
use app\api\controller\Ding;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 工单规则审核管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrderCheckRule extends Backend
{
    
    /**
     * WorkOrderCheckRule模型对象
     * @var \app\admin\model\saleaftermanage\WorkOrderCheckRule
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderCheckRule;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 工单措施与审核规则管理
     *
     * @return string|\think\response\Json
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Description
     * @throws \think\Exception
     * @since 2020/6/22 16:51
     * @author jhh
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

            foreach ($list as $k=>$v){
                $list[$k]['step_id'] = Db::name('work_order_step_type')->where('id',$v['step_id'])->value('step_name');
                $list[$k]['check_group_id'] = Db::name('auth_group')->where('id',$v['check_group_id'])->value('name');
                $list[$k]['create_group_id'] = Db::name('auth_group')->where('id',$v['create_group_id'])->value('name');
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);


            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * @return string
     * @return void
     * @throws Exception
     * @Description
     * @since 2020/6/23 11:16
     * @author jhh
     */
    public function add()
    {
        $workordersteptype = new \app\admin\model\saleaftermanage\Workorderconfig();
        //获取所有措施
        $step = $workordersteptype->getAllStep();
        $step = array_column($step->toArray(), 'step_name', 'id');
        //获取创建人信息
        $create_person = ['id'=>session('admin.id'),'nickname'=>session('admin.nickname')];
        //获取所有审核组
        $extend_team = $workordersteptype->getAllExtend();
        $create_team = $workordersteptype->getAllExtend();
        array_push($create_team,'非组创建');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a", [], 'strip_tags');


            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                $add_team = db('auth_group')->where('id',$params['add_group_id'])->find();
//                dump($params);die;
                Db::startTrans();
                try {
                    //创建组不为空代表为组创建 为空表示为当前登录人员创建
                    if (empty($add_team)){
                        $data['is_group_create'] = 0;
                        $data['work_create_person_id'] = $create_person['id'];
                        $data['work_create_person'] = $create_person['nickname'];
                        $data['step_id'] = $params['step_id'];
                        $data['step_value'] = $params['step_value'];
                        $data['symbol'] = $params['symbol'];
                        $data['check_group_id'] = $params['check_group_id'];
                        $data['check_group_name'] = db('auth_group')->where('id',$data['check_group_id'])->value('name');
                        $data['weight'] = $params['weight'];
                    }else{
                        $data['is_group_create'] = 1;
                        $data['create_group_id'] = $params['add_group_id'];
                        $data['step_id'] = $params['step_id'];
                        $data['step_value'] = $params['step_value'];
                        $data['symbol'] = $params['symbol'];
                        $data['check_group_id'] = $params['check_group_id'];
                        $data['check_group_name'] = db('auth_group')->where('id',$data['check_group_id'])->value('name');
                        $data['weight'] = $params['weight'];
                    }
                    $result = Db::name('work_order_check_rule')->insert($data);
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
        $this->view->assign("step", $step);
        $this->view->assign("extend_team", $extend_team);
        $this->view->assign("create_team", $create_team);
        $this->view->assign("create_person", $create_person);
        return $this->view->fetch();
    }

    /**
     * 编辑措施对应的审核规则
     *
     * @param null $ids
     * @return string
     * @return void
     * @throws \think\exception\DbException
     * @Description
     * @throws Exception
     * @since 2020/6/23 14:09
     * @author jhh
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $row['create_group_name'] = db('auth_group')->where('id',$row['create_group_id'])->value('name');
        $row['step_name'] = db('work_order_step_type')->where('id',$row['step_id'])->value('step_name');
        //获取所有审核组
        $workordersteptype = new \app\admin\model\saleaftermanage\Workorderconfig();
        $extend_team = $workordersteptype->getAllExtend();
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
            $params['check_group_name'] = db('auth_group')->where('id',$params['check_group_id'])->value('name');
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
        $this->view->assign("extend_team", $extend_team);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
