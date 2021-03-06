<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use app\admin\model\warehouse\StockHouse;
use app\admin\model\itemmanage;
use app\admin\model\itemmanage\Item;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * SKU库位绑定
 *
 * @icon fa fa-circle-o
 */
class StockSku extends Backend
{

    /**
     * StockSku模型对象
     * @var \app\admin\model\warehouse\StockSku
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\StockSku;
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
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['area_coding']) {
                $area_id = Db::name('warehouse_area')->where('coding',$filter['area_coding'])->value('id');
                $all_store_id = Db::name('store_house')->where('area_id',$area_id)->column('id');
                $map['storehouse.id'] = ['in',$all_store_id];
                unset($filter['area_coding']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['storehouse'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['storehouse'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //查询商品SKU
            $item = new \app\admin\model\itemmanage\Item;
            $arr = $item->where('is_del', 1)->column('name,is_open', 'sku');
            //所有库区编码id
            $area_coding = Db::name('warehouse_area')->column('coding','id');
            foreach ($list as $k => $row) {
                $row->getRelation('storehouse')->visible(['coding', 'library_name', 'status']);
                $list[$k]['name'] = $arr[$row['sku']]['name'];
                $list[$k]['is_open'] = $arr[$row['sku']]['is_open'];
                $store_house = Db::name('store_house')->where('id',$row['store_id'])->find();
                //获得库位所属库区编码
                $list[$k]['area_coding'] = $area_coding[$store_house['area_id']];
                $list[$k]['area_status'] = Db::name('warehouse_area')->where('id',$store_house['area_id'])->value('status');
            }

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
                //判断选择的库位是否已存在
                $map['store_id'] = $params['store_id'];
                $map['is_del'] = 1;
                $count = $this->model->where($map)->count();
                if ($count > 0) {
                    $this->error('库位已绑定！！');
                }
                $store_house = Db::name('store_house')->where('id',$params['store_id'])->find();
                if ($store_house['area_id'] != $params['area_id']){
                    $this->error('库位不在当前选择库区！！');
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
                    $params['createtime'] = date('Y-m-d H:i:s', time());
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
        //查询库区数据
        $data1 = Db::name('warehouse_area')->column('coding','id');
        $this->assign('data1', $data1);
        //查询库位数据
        $data = (new StockHouse())->getStockHouseData();
        $this->assign('data', $data);

        //查询商品SKU数据
        $info = (new Item())->getItemSkuInfo();
        $this->assign('info', $info);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $row['area_id'] = Db::name('store_house')->where('id',$row['store_id'])->value('id');
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

                //判断选择的库位是否已存在
                $map['store_id'] = $params['store_id'];
                $map['id'] = ['<>', $row->id];
                $map['is_del'] = 1;
                $count = $this->model->where($map)->count();
                if ($count > 0) {
                    $this->error('库位已绑定！！');
                }
                $store_house = Db::name('store_house')->where('id',$params['store_id'])->find();
                if ($store_house['area_id'] != $params['area_id']){
                    $this->error('库位不在当前选择库区！！');
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
        //查询库区数据
        $data1 = Db::name('warehouse_area')->column('coding','id');
        $this->assign('data1', $data1);
        //查询库位数据
        $data = (new StockHouse())->getStockHouseData();
        $this->assign('data', $data);

        //查询商品SKU数据
        $info = (new Item())->getItemSkuInfo();
        $this->assign('info', $info);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
