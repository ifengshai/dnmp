<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\saleaftermanage\WorkOrderNote;
use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\saleaftermanage\WorkOrderCheckRule;
use app\admin\model\saleaftermanage\WorkOrderDocumentary;
use app\admin\model\saleaftermanage\WorkOrderProblemStep;
use app\admin\model\saleaftermanage\WorkOrderStepType;
use app\admin\model\platformmanage\MagentoPlatform;

use think\Cache;

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
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\Workorderconfig;
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);
        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupdata = [];
        //加载承接组树形图
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        //填充空值 做不选择承接组用
        array_unshift($result,['id'=>0,'name'=>'不选择']);
        $groupdata = $result;
//        dump($result);die;

        $this->view->assign('groupdata', $groupdata);
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
     * 添加（弃用）
     */
    public function add1()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $res = $this->model->where(['type' => $params['type'], 'problem_belong' => $params['problem_belong'], 'problem_name' => $params['problem_name'], 'is_del' => 1])->find();
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
     * 新增问题类型 措施
     *
     * @return string
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Description
     * @throws Exception
     * @since 2020/6/24 15:43
     * @author jhh
     */
    public function add()
    {
        $judge = Cache::has('Workorderconfig_getConfigInfo');
        //判断缓存是否存在
        if ($judge === true) {
            //清除单个缓存文件
            $result = Cache::rm('Workorderconfig_getConfigInfo');
        }
        $step = $this->model->getAllStep();
        $extend_team = $this->model->getAllExtend();
        $extend_team = $this->model->getAllExtendArr();
        array_unshift($extend_team,['id'=>0,'name'=>'不选择']);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a", [], 'strip_tags');
            $res = $this->model->where(['type' => $params['type'], 'problem_belong' => $params['problem_belong'], 'problem_name' => $params['problem_name'], 'is_del' => 1])->find();
            if (!empty($res)) {
                $this->error('当前问题已存在,请不要重复添加');
            }
            if (empty($params['choose_id'])) {
                $params['choose_id'] = array();
            }
            //所有的措施遍历
            $all_step = Db::name('work_order_step_type')->where('is_del', 1)->field('id,step_name')->select();
            Db::startTrans();
            try {
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                    $this->model->validateFailException(true)->validate($validate);
                }
                $data['type'] = $params['type'];
                if($params['type'] == 2){
                    $data['problem_belong'] = $params['problem_belong1'];
                }else{
                    $data['problem_belong'] = $params['problem_belong'];
                }
                $data['problem_name'] = $params['problem_name'];
                $result = $this->model->insertGetId($data);
                foreach ($all_step as $k => $v) {
                    //不存在就新增一条某个问题对应的措施 存在判断是否更新 是否由审核变成不审核 承接组是否改变
                    if (in_array($v['id'], array_keys($params['choose_id'])) && $params['choose_id'][$v['id']]['is_on'] == 'on') {
                        $data = array();
                        $data['problem_id'] = $result;
                        $data['step_id'] = $v['id'];
//                        $data['extend_group_id'] = $params['extend'][$v['id'] - 1];
                        $data['extend_group_id'] = $params['choose_id'][$v['id']]['extend'];
                        if ($params['choose_id'][$v['id']]['is_checked'] == 'on') {
                            $data['is_check'] = 1;
                        }else{
                            $data['is_check'] = 0;
                        }
                        if ($params['choose_id'][$v['id']]['is_auto_complete'] == 'on') {
                            $data['is_auto_complete'] = 1;
                        }else{
                            $data['is_auto_complete'] = 0;
                        }
                        Db::name('work_order_problem_step')->insert($data);
                    }
                }
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
            $this->success();
        }
        $this->view->assign("step", $step);
        $this->view->assign("extend_team", $extend_team);
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
     * 编辑工单问题类型所对应的措施
     *
     * @Description
     * @return void
     * @since 2020/6/22 14:34
     * @author jhh
     */
    public function detail($ids = null)
    {
        $judge = Cache::has('Workorderconfig_getConfigInfo');
        //判断缓存是否存在
        if ($judge === true) {
            //清除单个缓存文件
            $result = Cache::rm('Workorderconfig_getConfigInfo');
        }
        $row = $this->model->getQuetionMeasure($ids);
        $step = $this->model->getAllStep();
        foreach ($step as $k => $v) {
            $result = Db::name('work_order_problem_step')->where(['problem_id' => $ids, 'step_id' => $step[$k]['id']])->find();
            if (!empty($result)) {
                $step[$k]['is_selected'] = 1;
                $step[$k]['is_check'] = $result['is_check'];
                $step[$k]['extend_group_id'] = $result['extend_group_id'];
                $step[$k]['is_auto_complete'] = $result['is_auto_complete'];
            } else {
                $step[$k]['is_selected'] = 0;
                $step[$k]['is_check'] = '';
                $step[$k]['extend_group_id'] = '';
                $step[$k]['is_auto_complete'] = '';
            }
        }
        $extend_team = $this->model->getAllExtend();
        $extend_team[0] = '不选择';
        $extend_team = $this->model->getAllExtendArr();
        array_unshift($extend_team,['id'=>0,'name'=>'不选择']);

        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a", [], 'strip_tags');
            if (empty($params['choose_id'])) {
                $params['choose_id'] = array();
            }
            //所有的措施遍历
            $all_step = Db::name('work_order_step_type')->where('is_del', 1)->field('id,step_name')->select();

            Db::startTrans();
            try {
                foreach ($all_step as $k => $v) {
                    //查找某一个问题类型所对应的措施是否存在
                    $problem_step = Db::name('work_order_problem_step')->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])->find();

                    //不存在就新增一条某个问题对应的措施 存在判断是否更新 是否由审核变成不审核 承接组是否改变
                    if (!$problem_step && in_array($v['id'], array_keys($params['choose_id']))) {
                        if ($params['choose_id'][$v['id']]['is_on'] == 'on') {
                            $data = array();
                            $data['problem_id'] = $params['problem_id'];
                            $data['step_id'] = $v['id'];
                            $data['extend_group_id'] = $params['choose_id'][$v['id']]['extend'];
                            if ($params['choose_id'][$v['id']]['is_checked'] == 'on') {
                                $data['is_check'] = 1;
                            }
                            if ($params['choose_id'][$v['id']]['is_auto_complete'] == 'on') {
                                $data['is_auto_complete'] = 1;
                            }
                            Db::name('work_order_problem_step')->insert($data);
                        }
                    } else if (!$problem_step && !in_array($v['id'], array_keys($params['choose_id']))) {
                        //不存在也没有选择不进行任何操作

                    } else if ($problem_step && !in_array($v['id'], array_keys($params['choose_id']))) {
                        if ($params['choose_id'][$v['id']]['is_on'] != 'on') {
                        //存在但是没有选择 就把他从记录中删除掉
                        Db::name('work_order_problem_step')
                            ->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])
                            ->delete();
                        }
                    } else if ($problem_step && in_array($v['id'], array_keys($params['choose_id']))) {
                        //存在但是没有勾选 就把他从记录中删除掉
                        if (!isset($params['choose_id'][$v['id']]['is_on'])) {
                            Db::name('work_order_problem_step')
                                ->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])
                                ->delete();
                        }else{
                            //存在这个问题类型对应的措施 也选择了 看是否需要更新
                            if (isset($params['choose_id'][$v['id']]['is_checked'])) {
                                $is_check = 1;
                            } else {
                                $is_check = 0;
                            }
                            if (isset($params['choose_id'][$v['id']]['is_auto_complete'])) {
                                $is_auto_complete = 1;
                            } else {
                                $is_auto_complete = 0;
                            }
                            Db::name('work_order_problem_step')
                                ->where(['problem_id' => $params['problem_id'], 'step_id' => $v['id']])
                                ->update(['extend_group_id' =>$params['choose_id'][$v['id']]['extend'], 'is_check' => $is_check, 'is_auto_complete' => $is_auto_complete]);
                        }
                    }
                }
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
            $this->success();
        }
        $this->view->assign("step", $step);
        $this->view->assign("extend_team", $extend_team);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 获取工单的配置信息
     *
     * @Description
     * @return void
     * @since 2020/06/19 11:04:57
     * @author lsw
     */
    public function getConfigInfo()
    {
        $arrConfig = Cache::get('Workorderconfig_getConfigInfo');
        if ($arrConfig) {
            return $arrConfig;
        }
        //所有问题类型
        $where['is_del'] = 1;
        $all_problem_type = $this->model->where($where)->select();
        //所有措施类型
        $all_step = (new WorkOrderStepType)->where($where)->select();
        //所有平台
        $all_platform     = (new MagentoPlatform)->field('id,name')->select();
        //所有的可用用户
        $usable_user = Db::name('admin')->where('status', 'normal')->column('id');
        //所有的组分别对应的用户
        $all_group =  Db::name('auth_group')->alias('a')->where('uid','in',$usable_user)->join('auth_group_access s ', 'a.id=s.group_id')->field('a.id,a.name,s.uid')->select();
        //所有的跟单员规则
        $all_documentary = (new WorkOrderDocumentary)->where($where)->select();
        //所有工单类型措施关系表
        $all_problem_step = (new WorkOrderProblemStep)->where($where)->select();
        //所有工单规则审核表
        $all_check_rule   = (new WorkOrderCheckRule)->where($where)->order('weight desc')->select();
        //客服部门角色组ID
        $customer_department_rule = config('workorder.customer_department_rule');
        //仓库部门角色组ID
        $warehouse_department_rule = config('workorder.warehouse_department_rule');
        //财务角色组
        $finance_department_rule   = config('workorder.finance_department_rule');
        //客服问题类型，仓库问题类型，大的问题类型分类,所有措施,所有平台,客服A/B分组,跟单组分组,
        //跟单人分组,大的问题类型分类two,问题类型/措施关系集合,分组对应的用户集合,审核人权重规则,审核组权重规则,所有的承接组,所有的承接人
        $customer_problem_type = $warehouse_problem_type = $customer_problem_classify_arr
        = $step = $platform = $kefumanage = $documentary_group = $documentary_person
        = $customer_problem_classify = $relation_problem_step = $group = $check_person_weight = $check_group_weight = $all_extend_group = $all_extend_person = [];
        //客服a,b组ID a,b组的主管ID,客服经理ID 
        $a_group_id = $b_group_id = $a_uid = $b_uid = $customer_manager_id = 0;
        //所有的组分别对应的有哪些用户
        //$all_group_user = Db::name('auth_group')->alias('a')->join('auth_group_access s ', 'a.id=s.group_id')->field('a.id,a.name,s.uid')->select();
        //不存在问题类型
        if (!empty($all_problem_type)) {
            $all_problem_type = collection($all_problem_type)->toArray();
            foreach ($all_problem_type as $v) {
                if (1 == $v['type']) {
                    $customer_problem_type[$v['id']] = $v['problem_name'];
                } elseif (2 == $v['type']) {
                    $warehouse_problem_type[$v['id']] = $v['problem_name'];
                }
                switch($v['problem_belong']){
                    case 1:
                        $customer_problem_classify['订单修改'][] = $v['id'];
                    break;
                    case 2:
                        $customer_problem_classify['物流仓库'][] = $v['id'];
                    break;
                    case 3:
                        $customer_problem_classify['产品质量'][] = $v['id'];
                    break;
                    case 4:
                        $customer_problem_classify['客户问题'][] = $v['id'];
                    break;
                    case 5:
                        $customer_problem_classify['仓库问题'][] = $v['id'];
                    break;
                    case 6:
                        $customer_problem_classify['其他'][]    = $v['id'];
                    break;    
                }
                $customer_problem_classify_arr[$v['problem_belong']][] =$v['id'];


            }
        }
        //存在措施
        if (!empty($all_step)) {
            $all_step         = collection($all_step)->toArray();
            foreach ($all_step as $sv) {
                $step[$sv['id']] = $sv['step_name'];
            }
        }
        //存在A、B组
        if (!empty($all_group)) {
            foreach ($all_group as $av) {
                if ('A组客服主管' == $av['name']) {
                    $a_group_id = $av['id'];
                    $a_uid = $av['uid'];
                } elseif ('B组客服主管' == $av['name']) {
                    $b_group_id = $av['id'];
                    $b_uid = $av['uid'];
                }elseif('客服经理' == $av['name']){
                    $customer_manager_id = $av['uid'];
                }
                $group[$av['id']][] = $av['uid'];
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

        }
        //不存在跟单规则
        if(!empty($all_documentary)){
            $all_documentary = collection($all_documentary)->toArray();
            //循环读取所有的跟单规则
            foreach($all_documentary as $dv){
                //组创建
                if(1 == $dv['type']){
                    $documentary_group[$dv['create_id']] = $dv;
                //人创建
                }elseif(2 == $dv['type']){
                    $documentary_person[$dv['create_id']] = $dv;

                }
            }
        }
        //存在工单类型措施关系表
        if(!empty($all_problem_step)){
            $all_problem_step = collection($all_problem_step)->toArray();
            foreach($all_problem_step as $fv){
                $relation_problem_step[$fv['problem_id']][] = $fv;
                if(0 != $fv['extend_group_id']){
                    $all_extend_group[] = $fv['extend_group_id'];
                } 
            }
        }
        dump(111);
        //根据所有的承接组求出所有的承接人
        if(!empty($all_extend_group)){
            $all_extend_group = array_unique($all_extend_group);
            $all_extend_person = Db::name('auth_group_access')->where('group_id','in',$all_extend_group)->column('uid');
        }
        dump(222);exit;
        //存在工单规则审核表
        if(!empty($all_check_rule)){
            $all_check_rule = collection($all_check_rule)->toArray();
            foreach($all_check_rule as $kv){
                if(1 == $kv['is_group_create']){
                    $check_group_weight[] = $kv;
                }elseif( 0 == $kv['is_group_create']){
                    $check_person_weight[] = $kv;
                }

            }
        }else{
            $all_check_rule = [];
        }
        //所有的平台
        foreach ($all_platform as $pv) {
            $platform[$pv['id']] = $pv['name'];
        }
        //不需要审核的优惠券
        $check_coupon = config('workorder.check_coupon');
        //需要审核的优惠券
        $need_check_coupon = config('workorder.need_check_coupon');
        $arr['customer_problem_type'] = $customer_problem_type;
        $arr['warehouse_problem_type'] = $warehouse_problem_type;
        $arr['customer_problem_classify_arr'] = $customer_problem_classify_arr;
        $arr['customer_problem_classify']     = $customer_problem_classify;
        $arr['step']                          = $step;
        $arr['platform']                      = $platform;
        $arr['kefumanage']                    = $kefumanage;
        $arr['all_problem_step']              = $relation_problem_step;
        $arr['check_group_weight']            = $check_group_weight;
        $arr['check_person_weight']           = $check_person_weight;
        $arr['customer_department_rule']      = $customer_department_rule;
        $arr['warehouse_department_rule']     = $warehouse_department_rule;
        $arr['finance_department_rule']       = $finance_department_rule;
        $arr['documentary_group']             = $documentary_group;
        $arr['documentary_person']            = $documentary_person;
        $arr['group']                         = $group;
        $arr['check_coupon']                  = $check_coupon;
        $arr['need_check_coupon']             = $need_check_coupon;
        $arr['customer_manager']              = $customer_manager_id;
        $arr['all_extend_person']             = $all_extend_person;
        $arr['all_extend_group']              = $all_extend_group;
        Cache::set('Workorderconfig_getConfigInfo', $arr);
        return $arr;
    }
    /**
     * 测试返回配置信息
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-06-22 16:26:59
     * @return void
     */
    public function test()
    {
        $info = $this->getConfigInfo();
        echo '<pre>';
        print_r($info);
    }
    /**
     * 清除工单配置缓存
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-06-22 16:27:29
     * @return void
     */
    public function clear()
    {
        $info = Cache::rm('Workorderconfig_getConfigInfo');
        if($info){
            $this->success('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //删除修改之后
    public function del($ids = "")
    {
        $judge = Cache::has('Workorderconfig_getConfigInfo');
        //判断缓存是否存在
        if ($judge === true) {
            //清除单个缓存文件
            $result = Cache::rm('Workorderconfig_getConfigInfo');
        }
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                if (!empty($this->model)) {
                    $fieldArr = $this->model->getTableFields();
                    if (in_array('is_del', $fieldArr)) {
                        $this->model->where($pk, 'in', $ids)->update(['is_del' => 2]);
                        $count = 1;
                    } else {
                        foreach ($list as $k => $v) {
                            $count += $v->delete();
                        }
                    }
                } else {
                    foreach ($list as $k => $v) {
                        $count += $v->delete();
                    }
                }

                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
