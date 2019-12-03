<?php

namespace app\admin\model\warehouse;

use think\Model;
use app\admin\model\warehouse\Instock;
use app\admin\model\warehouse\InstockItem;
use app\admin\model\warehouse\OutStockLog;
use app\admin\model\itemmanage\Item;
use app\admin\model\warehouse\Check;
use app\admin\model\purchase\PurchaseOrderItem;

class OutStockItem extends Model
{
    // 表名
    protected $name = 'out_stock_item';

    /**
     * 先入先出逻辑
     */
    public function setPurchaseOrder($rows)
    {
        foreach ($rows as $key => &$value) {
            $map = [];
            //查询此sku
            $where['sku'] = $value['sku'];
            $where['no_stock_num'] = ['>', 0];
            $map['status'] = 2;
            $map['type_id'] = 1; //采购入库
            //查询入库信息 以审核时间排序
            $res = (new Instock())->hasWhere('instockItem', $where)
                ->field('no_stock_num,in_stock_num,in_stock_id,InstockItem.id as item_id,sku')
                ->where($map)
                ->order('check_time asc')
                ->group('InstockItem.id')
                ->limit(10)
                ->select();
            $res = collection($res)->toArray();
            
            $list = [];
            $data = [];
            /**
             * 判断出库单出库数量大于入库单中未出库数量
             * 则未出库数量扣除为0 进入下一次循环直到出库单出库数量扣除完为止
             * 记录所扣除的采购单id
             */
            foreach ($res as $k => $v) {
                if ($value['out_stock_num'] > $v['no_stock_num']) {
                    $list[$k]['id'] = $v['item_id'];
                    $list[$k]['no_stock_num'] = 0;

                    //记录扣除的采购单id 以及对应的入库单
                    $data[$k]['check_id'] = $v['check_id'];
                    $data[$k]['sku'] = $v['sku'];
                    $data[$k]['outstock_item_id'] = $value['id'];
                    $data[$k]['out_stock_id'] = $value['out_stock_id'];
                    $data[$k]['instock_item_id'] = $v['item_id'];
                    $data[$k]['out_stock_num'] = $v['no_stock_num'];
                    $data[$k]['createtime'] = date('Y-m-d H:i:s', time());
                   

                    //查询质检单采购单id 并添加采购单出库数量
                    $check = (new Check())->get($v['check_id']);
                    $purchase_map['sku'] = $v['sku'];
                    $purchase_map['purchase_id'] = $check['purchase_id'];
                    (new PurchaseOrderItem())->where($purchase_map)->setInc('outstock_num', $v['no_stock_num']);


                    $data[$k]['purchase_id'] = $check['purchase_id'];

                    //剩余未冲减数量 进入下次循环
                    $value['out_stock_num'] = $value['out_stock_num'] - $v['no_stock_num'];
                    
                } else {
                    $list[$k]['id'] = $v['item_id'];
                    $list[$k]['no_stock_num'] = $v['no_stock_num'] - $value['out_stock_num'];

                    //记录扣除的采购单id 以及对应的入库单
                    $data[$k]['check_id'] = $v['check_id'];
                    $data[$k]['sku'] = $v['sku'];
                    $data[$k]['out_stock_id'] = $value['out_stock_id'];
                    $data[$k]['outstock_item_id'] = $value['id'];
                    $data[$k]['instock_item_id'] = $v['item_id'];
                    $data[$k]['out_stock_num'] = $value['out_stock_num'];
                    $data[$k]['createtime'] = date('Y-m-d H:i:s', time());

                    //查询质检单采购单id 并添加采购单出库数量
                    $check = (new Check())->get($v['check_id']);
                    $purchase_map['sku'] = $v['sku'];
                    $purchase_map['purchase_id'] = $check['purchase_id'];
                    (new PurchaseOrderItem())->where($purchase_map)->setInc('outstock_num', $value['out_stock_num']);
                    
                    $data[$k]['purchase_id'] = $check['purchase_id'];
                    break;
                }
            }
          
            if ($list) {
                //批量更改出库数量
                (new InstockItem())->allowField(true)->saveAll($list);
            }
            //添加出库日志 记录扣除的对应采购单以及数量
            if ($data) {
                (new OutStockLog())->allowField(true)->saveAll($data);
            }
        }
        return true;
    }


    /**
     * 先入先出逻辑
     * 根据SKU 获取入库单信息
     */
    public function setOrderOutStock($rows)
    {

        $map = [];
        //查询此sku
        $where['sku'] = $rows['sku'];
        $where['no_stock_num'] = ['>', 0];
        $map['status'] = 2;
        $map['type_id'] = 1; //采购入库
        //查询入库信息 以审核时间排序
        $res = (new Instock())->hasWhere('instockItem', $where)
            ->field('no_stock_num,in_stock_num,in_stock_id,InstockItem.id as item_id,sku')
            ->where($map)
            ->order('check_time asc')
            ->group('InstockItem.id')
            ->limit(10)
            ->select();
        $res = collection($res)->toArray();
        $list = [];
        $data = [];

        /**
         * 判断出库单出库数量大于入库单中未出库数量
         * 则未出库数量扣除为0 进入下一次循环直到出库单出库数量扣除完为止
         * 记录所扣除的采购单id
         */
        foreach ($res as $k => $v) {
            if ($rows['out_stock_num'] > $v['no_stock_num']) {
                $list[$k]['id'] = $v['item_id'];
                $list[$k]['no_stock_num'] = 0;

                //记录扣除的采购单id 以及对应的入库单
                $data[$k]['check_id'] = $v['check_id'];
                $data[$k]['sku'] = $v['sku'];
                $data[$k]['instock_item_id'] = $v['item_id'];
                $data[$k]['out_stock_num'] = $v['no_stock_num'];
                $data[$k]['createtime'] = date('Y-m-d H:i:s', time());
                $data[$k]['order_number'] = $rows['increment_id'];

                 //查询质检单采购单id 并添加采购单出库数量
                 $check = (new Check())->get($v['check_id']);
                 $purchase_map['sku'] = $v['sku'];
                 $purchase_map['purchase_id'] = $check['purchase_id'];
                 (new PurchaseOrderItem())->where($purchase_map)->setInc('outstock_num', $v['no_stock_num']);


                 $data[$k]['purchase_id'] = $check['purchase_id'];

                 //剩余未冲减数量 进入下次循环
                 $rows['out_stock_num'] = $rows['out_stock_num'] - $v['no_stock_num'];

            } else {
                $list[$k]['id'] = $v['item_id'];
                $list[$k]['no_stock_num'] = $v['no_stock_num'] - $rows['out_stock_num'];

                //记录扣除的采购单id 以及对应的入库单
                $data[$k]['check_id'] = $v['check_id'];
                $data[$k]['sku'] = $v['sku'];
                $data[$k]['instock_item_id'] = $v['item_id'];
                $data[$k]['out_stock_num'] = $rows['out_stock_num'];
                $data[$k]['createtime'] = date('Y-m-d H:i:s', time());
                $data[$k]['order_number'] = $rows['increment_id'];

                //查询质检单采购单id 并添加采购单出库数量
                $check = (new Check())->get($v['check_id']);
                $purchase_map['sku'] = $v['sku'];
                $purchase_map['purchase_id'] = $check['purchase_id'];
                (new PurchaseOrderItem())->where($purchase_map)->setInc('outstock_num', $rows['out_stock_num']);
                
                $data[$k]['purchase_id'] = $check['purchase_id'];

                break;
            }
        }

        if ($list) {
            //批量更改出库数量
            (new InstockItem())->allowField(true)->saveAll($list);
        }
        //添加出库日志 记录扣除的对应采购单以及数量
        if ($data) {
            (new OutStockLog())->allowField(true)->saveAll($data);
        }
        return true;
    }
}
