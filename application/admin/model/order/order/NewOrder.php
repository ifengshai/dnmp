<?php

namespace app\admin\model\order\order;

use think\Model;

class NewOrder extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order';

    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => 'Zeelool', 'field' => 'site', 'value' => 1],
            ['name' => 'Voogueme', 'field' => 'site', 'value' => 2],
            ['name' => 'Nihao', 'field' => 'site', 'value' => 3],
            ['name' => 'Meeloog', 'field' => 'site', 'value' => 4],
            ['name' => 'Wesee', 'field' => 'site', 'value' => 5],
            ['name' => 'Zeelool_es', 'field' => 'site', 'value' => 9],
            ['name' => 'Zeelool_de', 'field' => 'site', 'value' => 10],
            ['name' => 'Zeelool_jp', 'field' => 'site', 'value' => 11],
            ['name' => 'Voogueme_acc', 'field' => 'site', 'value' => 12],
        ];
    }


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
    public function getSkuSalesNum($sku, $site)
    {

        if ($sku) {
            $map['b.sku'] = $sku;
        } else {
            $map['b.sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
        $map['a.created_at'] = ['between', [strtotime(date('Y-m-d', strtotime("-1 day"))), strtotime(date('Y-m-d'))]];
        $map['a.site'] = $site;
        $count = $this->where($map)
            ->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->count(1);
        return $count;
    }

    /**
     * 根据SKU统计订单SKU最近30天有效销量
     *
     * @Description
     * @author wpl
     * @since 2020/08/01 11:57:38 
     * @param [type] $sku sku
     * @param [type] $where 条件
     * @param [type] $site 站点
     * @return void
     */
    public function getSkuSalesNum30days($sku)
    {

        if ($sku) {
            $map['b.sku'] = ['in', $sku];
        } else {
            $map['b.sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
        $map['a.created_at'] = ['between', [strtotime(date('Y-m-d', strtotime("-30 day"))), strtotime(date('Y-m-d'))]];
        $count = $this->where($map)
            ->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->count(1);
        return $count;
    }
}
