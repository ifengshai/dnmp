<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use Mpdf\Mpdf;

/**
 * 退销单管理
 *
 * @icon fa fa-circle-o
 */
class PurchaseReturn extends Backend
{

    /**
     * PurchaseReturn模型对象
     * @var \app\admin\model\warehouse\PurchaseReturn
     */
    protected $model = null;

    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\PurchaseReturn;
        $this->purchase_return_item = new \app\admin\model\purchase\PurchaseReturnItem;
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
            //退销状态
            $map['purchase_return.status'] = ['in', [1, 2, 3, 4]];
            $total = $this->model
                ->with(['purchaseorder', 'supplier'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['purchaseorder', 'supplier'])
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

        //查询供应商
        $supplier = new \app\admin\model\purchase\Supplier;
        $data = $supplier->getSupplierData();
        $this->assign('supplier', $data);

        //查询采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $purchase->getPurchaseReturnData([1, 2], []);
        $this->assign('purchase_data', $purchase_data);


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = $ids;
        $return_arr = $this->purchase_return_item->where($return_item_map)->select();
        $return_arr = collection($return_arr)->toArray();

        $check_item_id = array_column($return_arr, 'check_item_id');
        $skus = array_column($return_arr, 'sku');
        //查询采购单商品信息
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $map['purchase_id'] = $row['purchase_id'];
        $map['sku'] = ['in', $skus];
        $item = $purchase_item->where($map)->column('*', 'sku');

        //查询质检信息
        $check_map['id'] = ['in', $check_item_id];
        $check = new \app\admin\model\warehouse\CheckItem;
        $list = $check
            ->where($check_map)
            ->column('*', 'id');


        // //查询已退数量
        // $return_map['purchase_id'] = $row['purchase_id'];
        // $return_item = $this->model->hasWhere('purchaseReturnItem', ['sku' => ['in', array_keys($return_arr)]])
        //     ->where($return_map)
        //     ->group('sku')
        //     ->column('sum(return_num) as return_all_num', 'sku');

        foreach ($return_arr as $k => $v) {
            $return_arr[$k]['purchase_price'] = $item[$v['sku']]['purchase_price'];
            $return_arr[$k]['supplier_sku'] = $item[$v['sku']]['supplier_sku'];
            $return_arr[$k]['purchase_num'] = $item[$v['sku']]['purchase_num'];
            $return_arr[$k]['arrivals_num'] = $list[$v['check_item_id']]['arrivals_num'];
            $return_arr[$k]['quantity_num'] = $list[$v['check_item_id']]['quantity_num'];
            $return_arr[$k]['unqualified_num'] = $list[$v['check_item_id']]['unqualified_num'];
        }

        /***********end***************/
        $this->assign('item', $return_arr);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //添加物流单号
    public function logistics($ids = null)
    {
        $do_type = input('do_type');
        if(1 == $do_type){//批量录入
            $ids = explode(',', $ids);
            $row = $this->model->where(['id' => ['in', $ids]])->select();
            foreach ($row as $v) {
                1 != $v['status'] && $this->error(__('批量录入必须是待发货状态'), url('index'));
            }
        }else{//单个
            $row = $this->model->get($ids);
            !$row && $this->error(__('No Results were found'));
        }

        $adminIds = $this->getDataLimitAdminIds();
        is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds) && $this->error(__('You have no permission'));

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            !$params && $this->error(__('Parameter %s can not be empty', ''));
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

                $logistics = new \app\admin\model\LogisticsInfo();
                $params['status'] = 2;
                if(1 == $do_type){
                    $result = $this->model->allowField(true)->isUpdate(true, ['id' => ['in', $ids]])->save($params);
                    foreach ($row as $v) {
                        if ($params['logistics_number'])
                        //添加物流汇总表
                        $logistics->addLogisticsInfo([
                            'logistics_number'=>$params['logistics_number'],
                            'type'=>2,
                            'order_number'=>$v['return_number']
                        ]);
                    }
                }else{
                    $result = $row->allowField(true)->save($params);
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
            false !== $result ? $this->success('添加成功！！', '', url('index')) : $this->error(__('No rows were updated'));
        }

        $this->view->assign("row", $row);
        $this->view->assign("do_type", $do_type);
        return $this->view->fetch();
    }

    /**
     * 打印
     *
     * @Description
     * @author wpl
     * @since 2020/01/15 14:25:17 
     * @return void
     */
    public function print()
    {
        $ids = $this->request->get("ids");
        //查询退销信息
        $PurchaseReturn_map['id'] = ['in', $ids];
        $PurchaseReturn = new \app\admin\model\purchase\PurchaseReturn;
        $return_list = $PurchaseReturn->with(['purchaseReturnItem'])
            ->where($PurchaseReturn_map)
            ->select();
        $return_list = collection($return_list)->toArray();


        /***********查询退销商品信息***************/
        //查询退销单商品信息
        $return_item_map['return_id'] = ['in', $ids];
        $return_arr = $this->purchase_return_item->where($return_item_map)->select();
        $return_arr = collection($return_arr)->toArray();

        $check_item_id = array_column($return_arr, 'check_item_id');

        //查询质检信息
        $check_map['id'] = ['in', $check_item_id];
        $check = new \app\admin\model\warehouse\CheckItem;
        $list = $check
            ->where($check_map)
            ->column('*', 'id');
        foreach ($return_list as $k => &$v) {
            foreach ($v['purchase_return_item'] as &$va) {
                $va['supplier_sku'] = $list[$va['check_item_id']]['supplier_sku'];
                $va['purchase_num'] = $list[$va['check_item_id']]['purchase_num'];
                $va['arrivals_num'] = $list[$va['check_item_id']]['arrivals_num'];
                $va['quantity_num'] = $list[$va['check_item_id']]['quantity_num'];
            }
        }
        $this->assign('return_list', $return_list);
        /***********end***************/

        //去掉控制台
        $this->view->engine->layout(false);

        $dir = './pdftmp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777);
        }

        $mpdf = new Mpdf(['tempDir' => $dir]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        //打印模板
        $html =  $this->fetch('print');

        $mpdf->WriteHTML($html);

        $mpdf->Output('pdf.pdf', 'I'); //D是下载
        die;
    }
}
