<?php

namespace app\admin\model\order\order;

use app\enum\OrderType;
use think\Model;
use think\model\relation\HasMany;

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
    public function getTabList(): array
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
            ['name' => 'zeelool_cn', 'field' => 'site', 'value' => 13],
            ['name' => 'alibaba', 'field' => 'site', 'value' => 14],
            ['name' => 'zeelool_fr', 'field' => 'site', 'value' => 15],
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
    public function getSkuSalesNumShell($sku, $site, $createTime)
    {

        if ($sku) {
            $map['b.sku'] = $sku;
        } else {
            $map['b.sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
        // $map['a.created_at'] = ['between', [strtotime(date('Y-m-d', strtotime("-1 day"))), strtotime(date('Y-m-d'))]];
        $map['a.created_at'] = $createTime;
        $map['a.site'] = $site;
        $count = $this->where($map)
            ->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->count(1);
        return $count;
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

    /**
     * 根据SKU统计订单SKU最近120天有效销量
     *
     * @Description
     * @param [type] $sku sku
     * @param [type] $site 站点
     * @return int|string
     * @throws \think\Exception
     * @author wpl
     * @since 2020/08/01 11:57:38
     */
    public function getSkuSalesNum120days($sku, $site)
    {
        if ($sku) {
            $map['b.sku'] = $sku;
        } else {
            $map['b.sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = [
            'in',
            ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']
        ];
        $map['a.payment_time'] = ['>', strtotime('-120 day')];
        $map['a.site'] = $site;
        $map['a.order_type'] = OrderType::REGULAR_ORDER;

        return $this->where($map)
            ->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->count('a.id');
    }


    /**
     * 统计订单SKU销量
     *
     * @Description
     * @author wpl
     * @since 2020/02/06 16:42:25 
     * @param [type] $sku 筛选条件
     * @return object
     */
    public function getOrderSalesNum($sku = [], $where)
    {
        if ($sku) {
            $map['sku'] = ['in', $sku];
        } else {
            $map['sku'] = ['not like', '%Price%'];
        }
        $map['a.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
        $list = $this
            ->alias('a')
            ->field('sku,count(1) as num,a.site')
            ->where($map)
            ->where($where)
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->group('sku,a.site')
            ->select();
        $sales_num_list = [];
        foreach ($list as $k => $v) {
            $sales_num_list[$v['site']][$v['sku']] = $v['num'];
        }

        return $sales_num_list;
    }


    /**
     * 关联子订单表
     * @return HasMany
     * @author wpl
     * @date   2021/5/17 18:44
     */
    public function newOrderItemProcess(): HasMany
    {
        return $this->hasMany(NewOrderItemProcess::class, 'order_id', 'id');
    }
}
