<?php

namespace app\admin\controller\zendesk;
use think\Db;
use app\common\controller\Backend;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\platformmanage\MagentoPlatform;

/**
 * 自定义邮件模板管理
 *
 * @icon fa fa-circle-o
 */
class ZendeskMailTemplate extends Backend
{
    
    /**
     * ZendeskMailTemplate模型对象
     * @var \app\admin\model\zendesk\ZendeskMailTemplate
     */
    protected $model = null;
    /**
     * 前台权限显示设置
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/25 16:14:49 
     * @return void
     */
    private function template_permission()
    {
        $arr = [
            1=>'所有人可用',
            2=>'仅自己可用'
        ];
        return $arr;
    }
    /**
     * 前台模板分类显示设置
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/25 16:16:05 
     * @return void
     */
    private function template_category()
    {
        $arr = [
            1=> '售前',
            2=> '售中',
            3=> '售后',
            4=> '物流',
            5=> '超时',
            6=> '疫情',
            7=> '电话',
            8=> '其他'
        ];
        return $arr;
    }
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\ZendeskMailTemplate;
        $this->view->assign(
            [
                "orderPlatformList"     => (new MagentoPlatform())->getOrderPlatformList(),
                "templatePermission"    => $this->template_permission(),
                "templateCategory"      => $this->template_category()
            ]);
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
        $platform =  (new MagentoPlatform())->getOrderPlatformList();
        $templateCategory = $this->template_category();
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $whereAnd = [
                'template_permission'=> 1
            ];
            $whereOr = [
                'template_permission' => 2,
                'create_person' => session('admin.nickname'),
            ];
            $total = $this->model
                ->where($where)->where($whereAnd)
                ->whereOr(function($query) use ($whereOr){
                    $query->where($whereOr);
                })->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)->where($whereAnd)
                ->whereOr(function($query) use ($whereOr){
                    $query->where($whereOr);
                })->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            if(!empty($list) && is_array($list)){
                foreach ($list as $k =>$v){
                    if($v['template_platform']){
                        $list[$k]['template_platform'] = $platform[$v['template_platform']];
                    }
                    if($v['template_category']){
                        $list[$k]['template_category'] = $templateCategory[$v['template_category']];
                    }
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
        $platform =  (new MagentoPlatform())->getOrderPlatformList();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
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
                    $params['create_time']   = date("Y-m-d H:i:s",time());
                    $params['update_time']   = date("Y-m-d H:i:s",time());
                    $params['create_person'] = session('admin.nickname');
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
                    $params['update_time']   = date("Y-m-d H:i:s",time());
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

}
