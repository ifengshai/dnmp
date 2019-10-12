<?php

namespace app\admin\model\warehouse;

use think\Model;
use app\admin\model\warehouse\Instock;
use app\admin\model\warehouse\InstockItem;
use app\admin\model\warehouse\OutStockLog;
use app\admin\model\itemmanage\Item;

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
            //查询入库信息 以审核时间排序
            $res = (new Instock())->hasWhere('instockItem', $where)
                ->field('no_stock_num,in_stock_num,in_stock_id,instockItem.id as item_id,sku')
                ->where($map)
                ->order('check_time asc')
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
                    $data[$k]['purchase_id'] = $v['purchase_id'];
                    $data[$k]['sku'] = $v['sku'];
                    $data[$k]['outstock_item_id'] = $value['id'];
                    $data[$k]['instock_item_id'] = $v['item_id'];
                    $data[$k]['out_stock_num'] = $v['no_stock_num'];
                    $data[$k]['createtime'] = date('Y-m-d H:i:s', time());
                } else {
                    $list[$k]['id'] = $v['item_id'];
                    $list[$k]['no_stock_num'] = $v['no_stock_num'] - $value['out_stock_num'];

                    //记录扣除的采购单id 以及对应的入库单
                    $data[$k]['purchase_id'] = $v['purchase_id'];
                    $data[$k]['sku'] = $v['sku'];
                    $data[$k]['outstock_item_id'] = $value['id'];
                    $data[$k]['instock_item_id'] = $v['item_id'];
                    $data[$k]['out_stock_num'] = $value['out_stock_num'];
                    $data[$k]['createtime'] = date('Y-m-d H:i:s', time());
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
}
