<?php

namespace app\admin\controller\itemmanage;
use think\Db;
use think\Request;
use app\common\controller\Backend;
use app\admin\model\platformmanage\MagentoPlatform;

/**
 * 平台SKU预售管理
 *
 * @icon fa fa-circle-o
 */
class Itempresell extends Backend
{

    /**
     * Itempresell模型对象
     * @var \app\admin\model\itemmanage\Itempresell
     */
    protected $model = null;
    protected $platformSku = null;
    protected $noNeedLogin = ['updateItemPresellStatus'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\Itempresell;
        

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 商品预售首页
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
                    if(!empty($list) && is_array($list)){
                        $platform = (new MagentoPlatform())->getOrderPlatformList();
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
     * 添加平台商品预售
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if(empty($params['platform_sku'])){
                    $this->error(__('Platform sku cannot be empty'));
                }
                $whereData['platform_sku'] = $params['platform_sku'];
                $whereData['is_del'] = 1;
                $row = $this->model->where($whereData)->field('id,platform_sku')->find();
                if($row){
                    $this->error(__('This platform SKU has added presale, you can go to edit'));
                }
                $this->platformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
                $platformSku = $this->platformSku->where(['platform_sku'=>$params['platform_sku']])->field('sku,platform_sku,name,platform_type')->find();
                if(!$platformSku){
                    $this->error(__('Platform sku does not exist, please check if it is correct'));
                }
                if($params['presell_num']<=0){
                    $this->error(__('The number of pre-sale skus cannot be less than or equal to 0'));
                }
                if($params['presell_start_time'] == $params['presell_end_time']){
                    $this->error('预售开始时间和结束时间不能相等');
                }
                $params['sku'] = $platformSku['sku'];
                $params['platform_sku'] = $platformSku['platform_sku'];
                $params['name'] = $platformSku['name'] ? $platformSku['name'] :'';
                $params['platform_type'] = $platformSku['platform_type'];
                $params['presell_residue_num'] = $params['presell_num'];
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
                    $params['create_person'] = session('admin.nickname');
                    $params['create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if($now_time>=$params['presell_start_time']){ //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
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
    
       /***
     * 编辑商品预售
     */
    public function edit($ids=null)
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
                //变化的数量
                $num = $params['presell_num'];
                unset($params['presell_num']);
                if($params['presell_start_time'] == $params['presell_end_time']){
                    $this->error('Pre-sale start time and end time cannot be equal');
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
                    $now_time =  date("Y-m-d H:i:s", time());
                    if($now_time>=$params['presell_start_time']){ //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $row->allowField(true)->save($params,['platform_sku'=>$params['platform_sku']]);
                    $info   = $row->allowField(true)->where(['platform_sku'=>$params['platform_sku']])->inc('presell_num', $num)->inc('presell_residue_num', $num)->update();
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
                if (($result !== false) && ($info !==false)) {
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
        /***
     * 关闭预售
     */
    public function openEnd($ids = null)
    {
        if($this->request->isAjax()){
            $row = $this->model->get($ids);
            if($row['presell_status'] == 3){
                $this->error(__('Pre-sale closure, do not repeat the closure'));
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
    /**
     * 每10分钟执行一次
     * 更新商品预售状态
     */
    public function updateItemPresellStatus()
    {
        $now_time =  date("Y-m-d H:i:s", time());
        //1.更新当前时间段处在预售中的字段(预售中)
        $sql1 = 'update fa_item_presell set presell_status=2 where presell_start_time <= "{$now_time}"  and presell_start_time>= "{$now_time}"';
        //2.更新到未开始
        $sql2 = 'update fa_item_presell set presell_status=1 where presell_start_time > "{$now_time}"';
        //3.更新到已结束
        $sql3 = 'update fa_item_presell set presell_status=3 where presell_start_time > "{$now_time}"';
        DB::connect('database.db_stock')->name('item_presell')->query($sql1);
        DB::connect('database.db_stock')->name('item_presell')->query($sql2);
        DB::connect('database.db_stock')->name('item_presell')->query($sql3);

    }
}
