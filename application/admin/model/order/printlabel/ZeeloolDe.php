<?php

namespace app\admin\model\order\printlabel;

use think\Model;
use think\Db;


class ZeeloolDe extends Model
{
    //数据库
    protected $connection = 'database.db_zeelool_de';

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
     * 根据SKU查询订单号ID
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 14:51:20 
     * @return void
     */
    public function getOrderId($map)
    {
        if ($map) {
            $result = Db::connect('database.db_voogueme')
                ->table('sales_flat_order_item')
                ->alias('a')
                ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
                ->where($map)
                ->column('order_id');
            return $result;
        }
        return false;
    }
}
