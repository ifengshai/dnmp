<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Sample extends Backend
{
    
    /**
     * Sample模型对象
     * @var \app\admin\model\purchase\Sample
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->sample = new \app\admin\model\purchase\Sample;
        $this->samplelocation = new \app\admin\model\purchase\SampleLocation;
        $this->sampleworkorder = new \app\admin\model\purchase\SampleWorkorder;
        $this->item = new \app\admin\model\itemmanage\Item;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

   /**
    * 样品间列表
    *
    * @Description
    * @author mjj
    * @since 2020/05/23 15:04:06 
    * @return void
    */
    public function sample_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->sample
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->sample
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['location'] = $this->samplelocation->getLocationName($value['location_id']);
                $list[$key]['is_lend'] = $value['is_lend'] == 1 ? '是' : '否';
                $list[$key]['product_name'] = $this->item->where('sku',$value['sku'])->value('name');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 库位列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:03:40 
     * @return void
     */
    public function sample_location_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->samplelocation
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->samplelocation
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
     * 库位增加
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 14:59:04 
     * @return void
     */
    public function sample_location_add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                //判断库位号是否重复
                $location_repeat = Db::name('purchase_sample_location')
                    ->where('location',$params['location'])
                    ->find();
                if($location_repeat){
                    $this->error(__('库位号不能重复'));
                }
                $params['createtime'] = date('Y-m-d H:i:s',time());
                $params['create_user'] = session('admin.nickname');
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->samplelocation));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->samplelocation->validateFailException(true)->validate($validate);
                    }
                    $result = $this->samplelocation->allowField(true)->save($params);
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
     * 库位编辑
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:05:29 
     * @param [type] $ids
     * @return void
     */
    public function sample_location_edit($ids = null)
    {
        $row = $this->samplelocation->get($ids);
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
                //判断库位号是否重复
                $location_repeat = Db::name('purchase_sample_location')
                    ->where('location',$params['location'])
                    ->find();
                if($location_repeat){
                    $this->error(__('库位号不能重复'));
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->samplelocation));
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 库位删除
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:05:41 
     * @param string $ids
     * @return void
     */
    public function sample_location_del($ids = "")
    {
        if ($ids) {
            $pk = $this->samplelocation->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->samplelocation->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->samplelocation->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                if (!empty($this->samplelocation)) {
                    $fieldArr = $this->samplelocation->getTableFields();
                    if (in_array('is_del', $fieldArr)) {
                        $this->samplelocation->where($pk, 'in', $ids)->update(['is_del' => 2]);
                        $count = 1;
                    } else {
                        foreach ($list as $k => $v) {
                            $count += $v->delete();
                        }
                    }
                } else {
                    foreach ($list as $k => $v) {
                        $count += $v->delete();
                    }
                }

                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    /**
     * 入库列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:11 
     * @return void
     */
    public function sample_workorder_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $type['type'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->sampleworkorder
                ->where($where)
                ->where($type)
                ->order($sort, $order)
                ->count();

            $list = $this->sampleworkorder
                ->where($where)
                ->where($type)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['status_id'] = $value['status'];
                if($value['status'] == 1){
                    $list[$key]['status'] = '新建';
                }elseif($value['status'] == 2){
                    $list[$key]['status'] = '待审核';
                }elseif($value['status'] == 3){
                    $list[$key]['status'] = '已审核';
                }elseif($value['status'] == 4){
                    $list[$key]['status'] = '已拒绝';
                }elseif($value['status'] == 5){
                    $list[$key]['status'] = '已取消';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 入库添加
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:22 
     * @return void
     */
    public function sample_workorder_add()
    {
        $location_number = 'IN2'.date('YmdHis').rand(100,999).rand(100,999);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                //生成入库主表数据
                $workorder['location_number'] = $location_number;
                $workorder['status'] = $params['status'];
                $workorder['create_user'] = session('admin.nickname');
                $workorder['createtime'] = date('Y-m-d H:i:s',time());
                $workorder['type'] = 1;
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder);
                $parent_id = $this->sampleworkorder->id;
                $product_data = explode(',',$params['product_list_data']);
                foreach ($product_data as $key=>$value){
                    $info = explode('_',$value);
                    $workorder_item['parent_id'] = $parent_id;
                    $workorder_item['sku'] = $info[0];
                    $workorder_item['stock'] = $info[1];
                    $workorder_item['location_id'] = $info[2];
                    Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取库位数据
        $location_data = $this->samplelocation->getPurchaseLocationData();
        $this->assign('location_data', $location_data);

        $this->assign('location_number', $location_number);

        return $this->view->fetch();
    }
    /**
     * 入库编辑
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:32 
     * @param [type] $ids
     * @return void
     */
    public function sample_workorder_edit($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                //更新状态
                $workorder['status'] = $params['status'];
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder,['id'=> input('ids')]);
                //处理商品
                Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->delete();
                $product_data = explode(',',$params['product_list_data']);
                foreach ($product_data as $key=>$value){
                    $workorder_item = array();
                    $info = explode('_',$value);
                    $workorder_item['parent_id'] = $ids;
                    $workorder_item['sku'] = $info[0];
                    $workorder_item['stock'] = $info[1];
                    $workorder_item['location_id'] = $info[2];
                    Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                    if($params['status'] == 3){
                        unset($workorder_item['parent_id']);
                        $workorder_item['is_lend'] = 0;
                        $workorder_item['is_lend_num'] = 0;
                        $this->sample->insert($workorder_item);
                     }
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取库位数据
        $location_data = $this->samplelocation->getPurchaseLocationData();
        $this->assign('location_data', $location_data);

        //获取入库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
        $product_arr = array();
        foreach ($product_list as $key=>$value){
            $product_arr[] =  $value['sku'].'_'.$value['stock'].'_'.$value['location_id'];
            $product_list[$key]['location'] = $this->samplelocation->where('id',$value['location_id'])->value('location');
        }
        $product_str = implode(',',$product_arr);
        $this->assign('product_str', $product_str);
        $this->assign('product_list', $product_list);


        return $this->view->fetch();
    }
}
