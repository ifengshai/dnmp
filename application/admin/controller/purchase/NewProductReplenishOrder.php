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
        $this->supplier = new \app\admin\model\purchase\Supplier;

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /***
     * 多个一起审核通过
     */
    public function morePassAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $row = $this->model->where($map)->field('id,status,is_verify')->select();
            foreach ($row as $v) {
                if ($v['status'] != 1) {
                    $this->error('只有待分配状态才能操作！！');
                }
                if ($v['is_verify'] != 0) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['is_verify'] = 1;
            $data['check_time'] = date("Y-m-d H:i:s", time());

            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

            if ($res !== false) {
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
            $row = $this->model->where($map)->field('id,status,is_verify')->select();
            foreach ($row as $v) {
                if ($v['status'] != 1) {
                    $this->error('只有待分配状态才能操作！！');
                }
                if ($v['is_verify'] != 0) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['is_verify'] = 2;
            $data['check_time'] = date("Y-m-d H:i:s", time());

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


    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/13
     * Time: 11:22
     */
    public function distribution()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where(['is_del' => 1,'is_verify'=>1])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model->where(['is_del' => 1,'is_verify'=>1])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
//                $list[$k]['supplier'] = $this->supplier->getSupplierData();
                $list[$k]['supplier'] = ['0'=>['name'=>'思蒙眼镜','num'=>10],'1'=>['name'=>'兴亮眼镜','num'=>11]];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('distribution');
    }

    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/13
     * Time: 15:23
     */
    public function distribution_confirm()
    {
        $ids = $this->request->post("ids/a");
        dump($ids);die;
        if (!$ids) {
            $this->error('缺少参数！！');
        }

    }

    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/13
     * Time: 17:22
     */
    public function distribute_detail()
    {
        $supplier = $this->supplier->getSupplierData();
        $this->assign('supplier',$supplier);
        return $this->view->fetch();
    }
    public function handle()
    {
        dump(11);
    }

}
