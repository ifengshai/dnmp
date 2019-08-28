<?php

namespace app\admin\controller\itemmanage;
use think\Db;
use think\Request;
use app\common\controller\Backend;
use app\admin\model\platformManage\ManagtoPlatform;
/**
 * 平台SKU管理
 *
 * @icon fa fa-circle-o
 */
class ItemPlatformSku extends Backend
{
    
    /**
     * ItemPlatformSku模型对象
     * @var \app\admin\model\itemmanage\ItemPlatformSku
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemPlatformSku;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    //平台SKU首页
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
            if(!empty($list) && is_array($list)){
                $platform = (new ManagtoPlatform())->getOrderPlatformList();
                foreach ($list as $k =>$v){
                    if($v['platform_type']){
                        $list[$k]['platform_type'] = $platform[$v['platform_type']];
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    //商品预售首页
    public function presell()
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
                ->whereNotNull('presell_create_time')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->whereNotNull('presell_create_time')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            if(!empty($list) && is_array($list)){
                $platform = (new ManagtoPlatform())->getOrderPlatformList();
                foreach ($list as $k =>$v){
                    if($v['platform_type']){
                        $list[$k]['platform_type'] = $platform[$v['platform_type']];
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /***
     * 商品上架
     */
    public function putaway()
    {
        if($this->request->isAjax()){
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if($row['platform_sku_status'] == 1){
                $this->error('商品正在上架中,不能重复上架');
            }
            $map['id'] = $id;
            $data['platform_sku_status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('上架成功');
            } else {
                $this->error('上架失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /****
     * 商品下架
     */
    public function soldOut()
    {
        if($this->request->isAjax()){
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if($row['platform_sku_status'] == 2){
                $this->error('商品正在下架中,不能重复下架');
            }
            $map['id'] = $id;
            $data['platform_sku_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('下架成功');
            } else {
                $this->error('下架失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /***
     * 添加商品预售
     */
    public function addPresell()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if(empty($params['platform_sku'])){
                    $this->error(__('Platform sku cannot be empty'));
                }
                if(empty($params['presell_num'])){
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
//                echo $params['presell_start_time'];
//                echo '<br>';
//                echo $params['presell_end_time'];
//                echo '<br>';
//                echo date("Y-m-d H:i:s", time());
//                exit;
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
                    $params['presell_create_person'] = session('admin.nickname');
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if($now_time>=$params['presell_start_time']){ //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $this->model->allowField(true)->save($params,['platform_sku'=>$params['platform_sku']]);
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
    /***
     * 编辑商品预售
     */
    public function editPresell($ids=null)
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
                if(empty($params['platform_sku'])){
                    $this->error(__('Platform sku cannot be empty'));
                }
                if(empty($params['presell_num'])){
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params['presell_create_person'] = session('admin.nickname');
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if($now_time>=$params['presell_start_time']){ //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $row->allowField(true)->save($params,['platform_sku'=>$params['platform_sku']]);
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
    /***
     * 开启预售
     */
    public function openStart($ids = null)
    {
        if($this->request->isAjax()){
            $row = $this->model->get($ids);
            if($row['presell_status'] == 2){
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $now_time = date('Y-m-d H:i:s',time());
            if($row['presell_end_time']<$now_time){
                $this->error(__('The closing time has expired, please select again'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 2;
            $data['presell_open_time'] =  date('Y-m-d H:i:s',time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('预售开启成功');
            } else {
                $this->error('预售开启失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /***
     * 关闭预售
     */
    public function openEnd($ids = null)
    {
        if($this->request->isAjax()){
            $row = $this->model->get($ids);
            if($row['presell_status'] == 3){
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 3;
            $data['presell_open_time'] =  date('Y-m-d H:i:s',time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('关闭预售成功');
            } else {
                $this->error('关闭预售失败');
            }
        }else{
            $this->error('404 Not found');
        }
    }
    /***
     * 异步查询平台sku
     */
    public function ajaxGetLikePlatformSku(Request $request)
    {
        if ($this->request->isAjax()) {
            $origin_sku = $request->post('origin_sku');
            $result = $this->model->likePlatformSku($origin_sku);
            if (!$result) {
                return $this->error('商品SKU不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 异步查询用户输入的平台sku是否存在
     */
    public function ajaxGetPlatformSkuInfo(Request $request)
    {
        if ($this->request->isAjax()) {
            $platform_sku = $request->post('platform_sku');
            $result = $this->model->getPlatformSku($platform_sku);
            if (!$result) {
                return $this->error('平台商品SKU不存在，请重新填写');
            }
            return $this->success('是正确的SKU');
        } else {
            $this->error('404 not found');
        }
    }
}
