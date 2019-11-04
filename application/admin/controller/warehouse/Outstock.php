<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 出库单管理
 *
 * @icon fa fa-circle-o
 */
class Outstock extends Backend
{

    /**
     * Outstock模型对象
     * @var \app\admin\model\warehouse\Outstock
     */
    protected $model = null;

    //当前是否为关联查询
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\Outstock;
        $this->type = new \app\admin\model\warehouse\OutstockType;
        $this->item = new \app\admin\model\warehouse\OutStockItem;
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
                ->with(['outstocktype'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['outstocktype'])
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

                    $sku = $this->request->post("sku/a");
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }


                    $params['create_person'] = session('admin.username');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {
                        $out_stock_num = $this->request->post("out_stock_num/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['out_stock_num'] = $out_stock_num[$k];
                            $data[$k]['out_stock_id'] = $this->model->id;
                        }
                        //批量添加
                        $this->item->allowField(true)->saveAll($data);
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
                if ($result !== false) {
                    $this->success('添加成功！！', '', url('index'));
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //查询出库分类
        $type = $this->type->select();
        $this->assign('type', $type);


        //质检单
        $outstock_number = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('outstock_number', $outstock_number);
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

                    $sku = $this->request->post("sku/a");
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    $result = $row->allowField(true)->save($params);

                    //修改产品
                    if ($result !== false) {
                        $item_id = $this->request->post("item_id/a");
                        $out_stock_num = $this->request->post("out_stock_num/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['out_stock_num'] = $out_stock_num[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['out_stock_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->item->allowField(true)->saveAll($data);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);


        /***********查询出库商品信息***************/
        //查询入库单商品信息
        $item_map['out_stock_id'] = $ids;
        $item = $this->item->where($item_map)->select();

        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function detail($ids = null)
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

        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);


        /***********查询出库商品信息***************/
        //查询入库单商品信息
        $item_map['out_stock_id'] = $ids;
        $item = $this->item->where($item_map)->select();

        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //删除入库单里的商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->item->destroy($id);
        if ($res) {
            $this->success();
        } else {
            $this->error();
        }
    }

    /**
     * 审核
     */
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        foreach ($row as $v) {
            if ($v['status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }

        $data['status'] = 2;
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);

        if ($res != false) {
            /**
             * @todo 审核通过扣减库存逻辑 
             */

            if ($data['status'] == 2) {
                //查询入库单商品信息
                $where['out_stock_id'] = ['in', $ids];
                $list = $this->item->where($where)->select();
                //出库扣减库存
                foreach ($list as $v) {
                    //扣除商品表商品总库存
                    //总库存
                    $item = new \app\admin\model\itemmanage\Item;
                    $item_map['sku'] = $v['sku'];
                    $item->where($item_map)->setDec('stock', $v['out_stock_num']);
                    //可用库存
                    $item->where($item_map)->setDec('available_stock', $v['out_stock_num']);
                }

                //先入先出逻辑
                $this->item->setPurchaseOrder($list);
            }

            $this->success();
        } else {
            $this->error('修改失败！！');
        }
    }

    /**
     * 取消
     */
    public function cancel($ids = null)
    {
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->get($ids);
        if ($row['status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }


    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['status'] != 0) {
                $this->error('此状态不能提交审核');
            }

            //查询入库明细数据
            $list = $this->item
                ->where(['out_stock_id' => ['in', $id]])
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');

            //查询存在产品库的sku
            $item = new \app\admin\model\itemmanage\Item;
            $skus = $item->where(['sku' => ['in', $skus]])->column('sku');

            foreach ($list as $v) {
                if (!in_array($v['sku'], $skus)) {
                    $this->error('此sku:' . $v['sku'] . '不存在！！');
                }
            }


            $map['id'] = $id;
            $data['status'] = 1;
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
}
