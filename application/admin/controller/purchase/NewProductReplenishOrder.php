<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;

/**
 * 补货需求单
 *
 * @icon fa fa-circle-o
 */
class NewProductReplenishOrder extends Backend
{
    
    /**
     * NewProductReplenishOrder模型对象
     * @var \app\admin\model\purchase\NewProductReplenishOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\NewProductReplenishOrder;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id, 'itemAttribute');
            if ($row['item_status'] != 1) {
                $this->error('此商品状态不能提交审核');
            }
            // if (false == $row['itemAttribute']['frame_images']) {
            //     $this->error('请先上传商品图片');
            // }
            $map['id'] = $id;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('提交审核成功');
            } else {
                $this->error('提交审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 提交审核之后审核通过
     */
    public function passAudit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['item_status'] != 2) {
                $this->error('此商品状态不能审核通过');
            }
            $map['id'] = $id;
            $data['item_status'] = 3;
            $data['check_time']  = date("Y-m-d H:i:s", time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                //添加到商品平台sku
                (new ItemPlatformSku())->addPlatformSku($row);
                $this->success('审核通过成功');
            } else {
                $this->error('审核通过失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 提交审核之后审核拒绝
     */
    public function auditRefused()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['item_status'] != 2) {
                $this->error('此商品状态不能审核拒绝');
            }
            $map['id'] = $id;
            $data['item_status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('审核拒绝成功');
            } else {
                $this->error('审核拒绝失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个一起审核通过
     */
    public function morePassAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,item_status')->select();
            foreach ($row as $v) {
                if ($v['item_status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['item_status'] = 3;
            $data['check_time']  = date("Y-m-d H:i:s", time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res != false) {
                $row = $this->model->where('id', 'in', $ids)->field('sku,name,frame_is_rimless')->select();
                if ($row) {
                    foreach ($row as $val) {
                        (new ItemPlatformSku())->addPlatformSku($val);
                    }
                }
                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个一起审核拒绝
     */
    public function moreAuditRefused($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,item_status')->select();
            foreach ($row as $v) {
                if ($v['item_status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['item_status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('审核拒绝成功');
            } else {
                $this->error('审核拒绝失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

}
