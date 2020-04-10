<?php

namespace app\admin\model\warehouse;

use think\Model;


class Outstock extends Model
{
    // 表名
    protected $name = 'out_stock';

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
    public function outstocktype()
    {
        return $this->belongsTo('OutstockType', 'type_id')->setEagerlyType(0);
    }

    public function outstockitem()
    {
        return $this->belongsTo('OutStockItem', 'id', 'out_stock_id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 获取当日出库总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 16:04:57 
     * @return void
     */
    public function getOutStockNum()
    {
        $where['createtime'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['status'] = 2;
        return $this->where($where)->alias('a')->join(['fa_out_stock_item' => 'b'], 'a.id=b.out_stock_id')->sum('out_stock_num');
    }
}
