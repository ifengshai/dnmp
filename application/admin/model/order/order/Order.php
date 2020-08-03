<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class Order extends Model
{
    //数据库
    // protected $connection = 'database';
    protected $connection = 'database.db_zeelool';

    // 表名
    protected $table = 'sales_flat_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 根据SKU统计订单SKU销量
     *
     * @Description
     * @author wpl
     * @since 2020/08/01 11:57:38 
     * @param [type] $sku sku
     * @param [type] $where 条件
     * @param [type] $site 站点
     * @return void
     */
    public function getSkuSalesNum($sku, $where, $site)
    {
        if ($site == 1) {
            $model = $this;
        } elseif ($site == 2) {
            $model = new \app\admin\model\order\order\Voogueme();
        } elseif ($site == 3) {
            $model = new \app\admin\model\order\order\Nihao();
        } elseif ($site == 4) {
            $model = new \app\admin\model\order\order\Meeloog();
        } elseif ($site == 5) {
            $model = new \app\admin\model\order\order\Weseeoptical();
        } 

        if ($sku) {
            $map['sku'] = $sku;
        } else {
            $map['sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $res = $model
            ->where($map)
            ->where($where)
            ->alias('a')
            ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
            ->sum('b.qty_ordered');
        return $res;
    }
}
