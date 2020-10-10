<?php

namespace app\admin\controller\warehouse;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Db;

/**
 * 物流单汇总管理
 *
 * @icon fa fa-circle-o
 */
class LogisticsInfo extends Backend
{

    /**
     * LogisticsInfo模型对象
     * @var \app\admin\model\warehouse\LogisticsInfo
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['signin', 'batch_signin'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\LogisticsInfo;
        $this->purchase = new \app\admin\model\purchase\PurchaseOrder();
        $this->purchase_item = new \app\admin\model\purchase\PurchaseOrderItem();
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
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $purchase = new \app\admin\model\purchase\PurchaseOrder();
            foreach ($list as $k => $v) {
                if ($v['purchase_id']) {
                    $res = $purchase->where(['id' => $v['purchase_id']])->field('purchase_name,is_new_product')->find();
                    $list[$k]['purchase_name'] = $res->purchase_name;
                    $list[$k]['is_new_product'] = $res->is_new_product;
                } else {
                    $list[$k]['purchase_name'] = '';
                    $list[$k]['is_new_product'] = 0;
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 签收
     *
     * @Description
     * @author wpl
     * @since 2020/05/27 15:45:28 
     * @return void
     */
    public function signin($ids = null)
    {
        $item_platform = new ItemPlatformSku();
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if (!$ids) {
            $this->error('缺少参数！！');
        }

        if ($this->request->isAjax()) {
            $params['sign_person'] = session('admin.nickname');
            $params['sign_time'] = date('Y-m-d H:i:s');
            $params['status'] = 1;
            $res = $this->model->save($params, ['id' => $ids]);
            if (false !== $res) {
                //签收成功时更改采购单签收状态
                $count = $this->model->where(['purchase_id' => $row['purchase_id'], 'status' => 0])->count();
                if ($count > 0) {
                    $data['purchase_status'] = 9;
                } else {
                    $data['purchase_status'] = 7;
                }
                $data['receiving_time'] = date('Y-m-d H:i:s');
                $this->purchase->save($data, ['id' => $row['purchase_id']]);

                //签收扣减在途库存
                $batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
                $item = new \app\admin\model\itemmanage\Item();
                if ($row['batch_id']) {
                    $list = $batch_item->where(['purchase_batch_id' => $row['batch_id']])->select();
                    //根据采购单id获取补货单id再获取最初提报的比例
                    $replenish_id = Db::name('purchase_order')->where('id',$row['purchase_id'])->value('replenish_id');

                    foreach ($list as $v) {
                        //比例
                        $rate_arr = Db::name('new_product_mapping')
                            ->where(['sku'=>$v['sku'],'replenish_id'=>$replenish_id])
                            ->field('website_type,rate')
                            ->select();
                        //数量
                        $all_num = count($rate_arr);
                        //在途库存数量
                        $stock_num = $v['arrival_num'];
                        //在途库存分站 更新映射关系表
                        foreach ($rate_arr as $key => $val) {
                            //最后一个站点 剩余数量分给最后一个站
                            if (($all_num - $key) == 1) {
                                //根据sku站点类型进行在途库存的分配 签收完成之后在途库存就变成了待入库的数量
                                $item_platform->where(['sku'=>$v['sku'],'platform_type'=>$val['website_type']])->setDec('plat_on_way_stock',$stock_num);
                                //更新待入库数量
                                $item_platform->where(['sku'=>$v['sku'],'platform_type'=>$val['website_type']])->setInc('wait_instock_num',$stock_num);
                            } else {
                                $num = round($v['arrival_num'] * $val['rate']);
                                $stock_num -= $num;
                                $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('plat_on_way_stock', $num);
                                //更新待入库数量
                                $item_platform->where(['sku'=>$v['sku'],'platform_type'=>$val['website_type']])->setInc('wait_instock_num',$num);
                            }
                        }
                        //减总的在途库存也就是商品表里的在途库存
                        $item->where(['sku' => $v['sku']])->setDec('on_way_stock', $v['arrival_num']);
                        //减在途加待入库数量
                        $item->where(['sku' => $v['sku']])->setInc('wait_instock_num', $v['arrival_num']);

                    }
                } else {
                    if ($row['purchase_id']) {
                        $list = $this->purchase_item->where(['purchase_id' => $row['purchase_id']])->select();
                        //根据采购单id获取补货单id再获取最初提报的比例
                        $replenish_id = Db::name('purchase_order')->where('id',$row['purchase_id'])->value('replenish_id');
                        foreach ($list as $v) {
                            //比例
                            $rate_arr = Db::name('new_product_mapping')
                                ->where(['sku'=>$v['sku'],'replenish_id'=>$replenish_id])
                                ->field('website_type,rate')
                                ->select();
                            //数量
                            $all_num = count($rate_arr);
                            //在途库存数量
                            $stock_num = $v['purchase_num'];
                            //在途库存分站 更新映射关系表
                            foreach ($rate_arr as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    //根据sku站点类型进行在途库存的分配
                                    $item_platform->where(['sku'=>$v['sku'],'platform_type'=>$val['website_type']])->setDec('plat_on_way_stock',$stock_num);
                                    //更新待入库数量
                                    $item_platform->where(['sku'=>$v['sku'],'platform_type'=>$val['website_type']])->setInc('wait_instock_num',$stock_num);
                                } else {
                                    $num = round($v['purchase_num'] * $val['rate']);
                                    $stock_num -= $num;
                                    $item_platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('plat_on_way_stock', $num);
                                    //更新待入库数量
                                    $item_platform->where(['sku'=>$v['sku'],'platform_type'=>$val['website_type']])->setInc('wait_instock_num',$num);
                                }
                            }
                            //减总的在途库存也就是商品表里的在途库存
                            $item->where(['sku' => $v['sku']])->setDec('on_way_stock', $v['purchase_num']);
                            //减在途加待入库数量
                            $item->where(['sku' => $v['sku']])->setInc('wait_instock_num', $v['purchase_num']);
                        }
                    }
                }


                $this->success('签收成功');
            } else {
                $this->error('签收失败');
            }
        }
    }

    /**
     * 批量签收
     *
     * @Description
     * @author wpl
     * @since 2020/05/27 15:45:28 
     * @return void
     */
    public function batch_signin()
    {
        $item_platform = new ItemPlatformSku();
        $ids = input('ids/a');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        if ($this->request->isAjax()) {
            $params['sign_person'] = session('admin.nickname');
            $params['sign_time'] = date('Y-m-d H:i:s');
            $params['status'] = 1;
            $res = $this->model->save($params, ['id' => ['in', $ids]]);
            if (false !== $res) {

                $row = $this->model->where(['id' => ['in', $ids]])->select();
                foreach ($row as $k => $v) {
                    $data = [];
                    //签收成功时更改采购单签收状态
                    $count = $this->model->where(['purchase_id' => $v['purchase_id'], 'status' => 0])->count();
                    if ($count > 0) {
                        $data['purchase_status'] = 9;
                    } else {
                        $data['purchase_status'] = 7;
                    }
                    $data['receiving_time'] = date('Y-m-d H:i:s');
                    $this->purchase->where(['id' => $v['purchase_id']])->update($data);

                    //签收扣减在途库存
                    $batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
                    $item = new \app\admin\model\itemmanage\Item();
                    if ($v['batch_id']) {
                        $list = $batch_item->where(['purchase_batch_id' => $v['batch_id']])->select();
                        //根据采购单id获取补货单id再获取最初提报的比例
                        $replenish_id = Db::name('purchase_order')->where('id',$v['purchase_id'])->value('replenish_id');

                        foreach ($list as $val) {
                            //比例
                            $rate_arr = Db::name('new_product_mapping')
                                ->where(['sku'=>$val['sku'],'replenish_id'=>$replenish_id])
                                ->field('website_type,rate')
                                ->select();
                            //数量
                            $all_num = count($rate_arr);
                            //在途库存数量
                            $stock_num = $val['arrival_num'];
                            //在途库存分站 更新映射关系表
                            foreach ($rate_arr as $key => $vall) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    //根据sku站点类型进行在途库存的分配 签收完成之后在途库存就变成了待入库的数量
                                    $item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setDec('plat_on_way_stock',$stock_num);
                                    //更新待入库数量
                                    $item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setInc('wait_instock_num',$stock_num);
                                } else {
                                    $num = round($val['arrival_num'] * $vall['rate']);
                                    $stock_num -= $num;
                                    $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $num);
                                    //更新待入库数量
                                    $item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setInc('wait_instock_num',$num);
                                }
                            }
                            //减总的在途库存也就是商品表里的在途库存
                            $item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['arrival_num']);
                            //减在途加待入库数量
                            $item->where(['sku' => $v['sku']])->setInc('wait_instock_num', $v['arrival_num']);
                        }
                    } else {
                        if ($v['purchase_id']) {
                            $list = $this->purchase_item->where(['purchase_id' => $v['purchase_id']])->select();
                            //根据采购单id获取补货单id再获取最初提报的比例
                            $replenish_id = Db::name('purchase_order')->where('id',$v['purchase_id'])->value('replenish_id');
                            foreach ($list as $val) {
                                //比例
                                $rate_arr = Db::name('new_product_mapping')
                                    ->where(['sku'=>$val['sku'],'replenish_id'=>$replenish_id])
                                    ->field('website_type,rate')
                                    ->select();
                                //数量
                                $all_num = count($rate_arr);
                                //在途库存数量
                                $stock_num = $val['purchase_num'];
                                //在途库存分站 更新映射关系表
                                foreach ($rate_arr as $key => $vall) {
                                    //最后一个站点 剩余数量分给最后一个站
                                    if (($all_num - $key) == 1) {
                                        //根据sku站点类型进行在途库存的分配 签收完成之后在途库存就变成了待入库的数量
                                        $item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setDec('plat_on_way_stock',$stock_num);
                                        //更新待入库数量
                                        $item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setInc('wait_instock_num',$stock_num);
                                    } else {
                                        $num = round($val['purchase_num'] * $vall['rate']);
                                        $stock_num -= $num;
                                        $item_platform->where(['sku' => $val['sku'], 'platform_type' => $vall['website_type']])->setDec('plat_on_way_stock', $num);
                                        //更新待入库数量
                                        $item_platform->where(['sku'=>$val['sku'],'platform_type'=>$vall['website_type']])->setInc('wait_instock_num',$num);
                                    }
                                }
                                //减总的在途库存也就是商品表里的在途库存
                                $item->where(['sku' => $val['sku']])->setDec('on_way_stock', $val['purchase_num']);
                                //减在途加待入库数量
                                $item->where(['sku' => $v['sku']])->setInc('wait_instock_num', $v['purchase_num']);
                            }
                        }
                    }
                }
                $this->success('签收成功');
            } else {
                $this->error('签收失败');
            }
        }
    }
}
