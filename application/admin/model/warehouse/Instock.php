<?php

namespace app\admin\model\warehouse;

use think\Model;


class Instock extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'in_stock';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联模型采购单
    public function purchaseorder()
    {
        return $this->belongsTo('app\admin\model\purchase\PurchaseOrder', 'purchase_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    //关联模型采购单
    public function instocktype()
    {
        return $this->belongsTo('InstockType', 'type_id')->setEagerlyType(0);
    }

    //关联模型 商品信息
    public function instockItem()
    {
        return $this->hasMany('InstockItem', 'in_stock_id');
    }

    //入库数量回写采购单入库数量
    public function setPurchaseOrder($id)
    {
        //查询入库单 查询出采购单号
        $data = $this->get($id);
        $map['status'] = 2;
        $map['purchase_id'] = $data['purchase_id'];
        $rows = $this
            ->hasWhere('instockItem')
            ->field("sum(in_stock_num) as num,sku")
            ->where($map)
            ->group('sku')
            ->select();
        $rows = collection($rows)->toArray();

        //写入对应采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrderItem;
        foreach ($rows as $v) {
            $where['purchase_id'] = $data['purchase_id'];
            $where['sku'] = $v['sku'];
            $purchase->save(['instock_num' => $v['num']], $where);
        }
    }
}
