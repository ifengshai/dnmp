<?php

namespace app\admin\controller\itemmanage;

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
}
