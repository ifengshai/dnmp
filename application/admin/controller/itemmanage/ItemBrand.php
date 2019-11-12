<?php

namespace app\admin\controller\itemmanage;

use app\common\controller\Backend;

/**
 * 商品分类管理
 *
 * @icon fa fa-circle-o
 */
class ItemBrand extends Backend
{
    
    /**
     * ItemBrand模型对象
     * @var \app\admin\model\itemmanage\ItemBrand
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemBrand;
        $this->view->assign('PutAway',$this->model->isPutAway());

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
        /***
     * 启用商品
     */
    public function startItemBrand($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,status')->select();
            foreach ($row as $v) {
                if ( 0 !=$v['status']) {
                    $this->error('只有禁用状态才能操作！！');
                }
            }
            $data['status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('启动成功');
            } else {
                $this->error('启动失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 禁止商品
     */
    public function forbiddenItemBrand($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,status')->select();
            foreach ($row as $v) {
                if ( 1 != $v['status']) {
                    $this->error('只有启用状态才能操作！！');
                }
            }
            $data['status'] = 0;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('禁止成功');
            } else {
                $this->error('禁止失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

}
