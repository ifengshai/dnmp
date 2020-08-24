<?php

namespace app\admin\controller\warehouse;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Collection;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 调拨单
 *
 * @icon fa fa-circle-o
 */
class TransferOrder extends Backend
{

    /**
     * TransferOrder模型对象
     * @var \app\admin\model\warehouse\TransferOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\TransferOrder;
        $this->transferOrderItem = new \app\admin\model\warehouse\TransferOrderItem;

        //获取所有站点
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->magentoplatformarr = $this->magentoplatform->column('name', 'id');
        $this->assign('magentoplatformarr', $this->magentoplatformarr);
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
//         $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);

            //如果筛选条件有sku的话 查询这个调拨单子表中有这个sku的单子 进而查到主表数据
            if ($filter['sku']) {
                $ids = $this->transferOrderItem->where('sku','like','%'.$filter['sku'].'%')->group('transfer_order_id')->field('id')->column('transfer_order_id');
//                dump(collection($ids)->toArray());die;
                $map['id'] = ['in',$ids];
                unset($filter['sku']);
            }else{
                $map = array();
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
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
            $sku = $this->request->post("sku/a");
            $num = $this->request->post("num/a");
            $sku_stock = $this->request->post("sku_stock/a");

            if ($params['call_out_site'] == $params['call_in_site']){
                $this->error('调入仓和调出仓不能为同一个站');
            }
            if (count($sku) != count(array_unique($sku))) {
                $this->error('当前调拨单中存在相同的sku，请检查后重试');
            }
            //添加调拨单保存或提交审核时数据有效性的判断
            foreach($sku as $k=>$v){

                if (empty($v)){
                    $this->error('sku不能为空');
                }
                if ($num[$k] <= 0){
                    $this->error('调出数量不能为0，请确认'.$v.'调出数量');
                }
                if ($sku_stock[$k] == 0){
                    $this->error('请先选择调出仓及调入仓再填写sku，或检查当前sku:'.$v.'在调出仓的库存');
                }
                $item_platform = new ItemPlatformSku();
                $item_platform_sku = $item_platform->where(['sku'=>$v,'platform_type'=>$params['call_in_site']])->find();
                if (empty($item_platform_sku)){
                    $this->error('此sku'.$v.'暂未同步到调入仓，请先同步再进行操作');
                }
                $in_item_platform_sku = $item_platform->where(['sku'=>$v,'platform_type'=>$params['call_out_site']])->find();

                if ($in_item_platform_sku['stock'] < $num[$k]){
                    $this->error('调出数量不能大于当前站点虚拟仓库存');
                }
                // dump($num[$k]);
                // dump($in_item_platform_sku['stock']);die;
            }

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
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['create_person'] = session('admin.nickname');
                    $result = $this->model->allowField(true)->save($params);
                    if (false !== $result) {
                        $sku = $this->request->post("sku/a");
                        $num = $this->request->post("num/a");
                        $sku_stock = $this->request->post("sku_stock/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = trim($v);
                            $data[$k]['num'] = trim($num[$k]);
                            $data[$k]['stock'] = trim($sku_stock[$k]);
                            $data[$k]['transfer_order_id'] = $this->model->id;
                        }
                        //批量添加
                        $this->transferOrderItem->saveAll($data);
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
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //生成采购编号
        $transfer_order_number = 'TO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('transfer_order_number', $transfer_order_number);

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
            if ($params['call_out_site'] == $params['call_in_site']){
                $this->error('调入仓和调出仓不能为同一个站');
            }

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
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['create_person'] = session('admin.nickname');
                    $result = $row->allowField(true)->save($params);
                    if (false !== $result) {
                        $sku = $this->request->post("sku/a");
                        if (count($sku) != count(array_unique($sku))) {
                            $this->error('当前调拨单中存在相同的sku，请检查后重试');
                        }
                        $num = $this->request->post("num/a");
                        // dump($num);die;
                        $item_id = $this->request->post("item_id/a");
                        $sku_stock = $this->request->post("sku_stock/a");
                        foreach($sku as $k=>$v){

                            if (empty($v)){
                                $this->error('sku不能为空');
                            }
                            if ($num[$k] <= 0){
                                $this->error('调出数量不能为0，请确认'.$v.'调出数量');
                            }
                            if ($sku_stock[$k] == 0){
                                $this->error('请先选择调出仓及调入仓再填写sku，或检查当前sku:'.$v.'在调出仓的库存');
                            }
                            $item_platform = new ItemPlatformSku();
                            $item_platform_sku = $item_platform->where(['sku'=>$v,'platform_type'=>$params['call_in_site']])->find();
                            if (empty($item_platform_sku)){
                                $this->error('此sku'.$v.'暂未同步到调出仓，请先同步再进行操作');
                            }
                            $in_item_platform_sku = $item_platform->where(['sku'=>$v,'platform_type'=>$params['call_out_site']])->find();
                            if ($in_item_platform_sku['stock'] < $num[$k]){
                                $this->error('调出数量不能大于当前站点虚拟仓库存');
                            }
                        }
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = trim($v);
                            $data[$k]['num'] = trim($num[$k]);
                            $data[$k]['stock'] = trim($sku_stock[$k]);
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['transfer_order_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->transferOrderItem->allowField(true)->saveAll($data);
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
        $this->view->assign("row", $row);

        //查询产品信息
        $map['transfer_order_id'] = $ids;
        $item = $this->transferOrderItem->where($map)->select();
        $this->assign('item', $item);

        return $this->view->fetch();
    }

    /**
     * 详情
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
        $this->view->assign("row", $row);

        //查询产品信息
        $map['transfer_order_id'] = $ids;
        $item = $this->transferOrderItem->where($map)->select();
        $this->assign('item', $item);

        return $this->view->fetch();
    }
    public function cancel()
    {
        if ($this->request->isAjax()) {
//            $id = $this->request->params('ids');
            $id = $this->request->post("ids/a");
//            dump($id);die;
            $map['id'] = $id[0];
            $data['status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('取消成功');
            } else {
                $this->error('取消失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /**
     * 获取对应站点虚拟库存
     *
     * @Description
     * @author wpl
     * @since 2020/07/20 10:01:39 
     * @return void
     */
    public function getSkuData()
    {
        if ($this->request->isPost()) {
            $sku = input('sku');
            $platform_type = input('platform_type');
            $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $stock = $item_platform->where(['sku' => $sku, 'platform_type' => $platform_type])->value('stock');
            $this->success('', '', $stock);
        } else {
            $this->error(__('No rows were updated'));
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
        $status = input('status');
        $data['status'] = $status;
        Db::startTrans();
        try {
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res === false) {
                throw new Exception('审核失败！！');
            } 
            //审核通过
            if ($status == 2) {
                $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku();
                //审核通过冲减各站虚拟仓库存
                $where['transfer_order_id'] = ['in', $ids];
                $list = $this->transferOrderItem->alias('a')->field('a.*,b.call_out_site,b.call_in_site')->join(['fa_transfer_order' => 'b'], 'a.transfer_order_id=b.id')->where($where)->select();
                foreach ($list as $v) {
                    //查询虚拟仓库存
                    $stock = $item_platform->where(['sku' => $v['sku'], 'platform_type' => $v['call_out_site']])->value('stock');
                    //如果调出数量大于虚拟仓现有库存 则调出失败
                    if ($v['num'] > $stock) {
                        throw new Exception('id:' . $v['id'] . '|' . $v['sku'] . ':' . '虚拟仓库存不足');
                    }
                    //减少虚拟库存
                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $v['call_out_site']])->setDec('stock', $v['num']);
                    //增加虚拟库存
                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $v['call_in_site']])->setInc('stock', $v['num']);
                }
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
        $this->success();
    }


    /**
     * 删除合同里商品信息
     */
    public function deleteItem()
    {
        if ($this->request->isPost()) {
            $id = input('id');
            $res = $this->transferOrderItem->destroy($id);
            if ($res) {
                $this->success();
            } else {
                $this->error();
            }
        }
    }
}
