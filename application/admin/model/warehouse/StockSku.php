<?php

namespace app\admin\model\warehouse;

use think\Model;


class StockSku extends Model
{





    // 表名
    protected $name = 'store_sku';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联库位表
    public function storehouse()
    {
        return $this->belongsTo('app\admin\model\warehouse\StockHouse', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function storehouse1()
    {
        return $this->belongsTo('app\admin\model\warehouse\StockHouse', 'store_id', 'id', [], 'RIGHT')->setEagerlyType(0);
    }

    //关联商品表
    public function item()
    {
        return $this->belongsTo('app\admin\model\itemmanage\Item', 'item_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 获取库位下sku
     *
     * @Description
     * @author wpl
     * @since 2021/03/03 13:52:32 
     * @param [type] $store_id 库位id
     * @param [type] $sku s
     * @return void
     */
    public function getRowsData($store_id)
    {
        $list = $this->field('id,sku')->where(['store_id' => ['in', $store_id], 'is_del' => 1])->select();
        return collection($list)->toArray();
    }
    /**
     * 所属分仓
     * @return \think\model\relation\BelongsTo
     * @author crasphb
     * @date   2021/5/17 14:13
     */
    public function warehouseStock()
    {
        return $this->belongsTo(WarehouseStock::class,'stock_id','id');
    }
}
