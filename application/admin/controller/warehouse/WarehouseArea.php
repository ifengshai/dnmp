<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Loader;

/**
 * 库位管理
 *
 * @icon fa fa-circle-o
 */
class WarehouseArea extends Backend
{
    
    /**
     * StockHouse模型对象
     * @var \app\admin\model\warehouse\StockHouse
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['print_label'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\WarehouseArea;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 库区列表
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/2
     * Time: 14:12:01
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
            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['id']) {
                $map['a.id'] = ['=',$filter['id']];
                unset($filter['id']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['stock']) {
                $stockId = Db::name('warehouse_stock')->where('name','like',$filter['stock'])->column('id');
                $map['a.stock_id'] = ['in',$stockId];
                unset($filter['stock']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['coding']) {
                $map['a.coding'] = ['like','%'.$filter['coding'].'%'];
                unset($filter['coding']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['name']) {
                $map['a.name'] = ['like','%'.$filter['name'].'%'];
                unset($filter['name']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['type']) {
                $map['a.type'] = ['=',$filter['type']];
                unset($filter['type']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['status']) {
                $map['a.status'] = ['=',$filter['status']];
                unset($filter['status']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('a')
                ->join(['fa_warehouse_stock' => 'b'],'a.stock_id=b.id')
                ->where($map)
                ->where($where)
                ->order('a.id', $order)
                ->count();

            $list = $this->model
                ->alias('a')
                ->join(['fa_warehouse_stock' => 'b'],'a.stock_id=b.id')
                ->field('a.*,b.name as stock')
                ->where($map)
                ->where($where)
                ->order('a.id', $order)
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


                $map['coding'] = $params['coding'];
                $count = $this->model->where($map)->count();
                $count > 0 && $this->error('已存在此编码！');

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
        $type=[1=>'存储库区',2=>'拣货库区'];
        $this->view->assign("type", $type);
        $warehouseStock = Db::name('warehouse_stock')->column('name','id');
        $this->view->assign("warehouse_stock", $warehouseStock);
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

                $map['coding'] = $params['coding'];
                $map['id'] = ['<>', $row->id];
                $count = $this->model->where($map)->count();
                $count > 0 && $this->error('已存在此编码！');

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
        $type=[1=>'存储库区',2=>'拣货库区'];
        $this->view->assign("type", $type);
        $this->view->assign("row", $row);
        $warehouseStock = Db::name('warehouse_stock')->column('name','id');
        $this->view->assign("warehouse_stock", $warehouseStock);
        return $this->view->fetch();
    }

    /**
     * 启用、禁用
     */
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $this->success();
        } else {
            $this->error('修改失败！！');
        }
    }


}
