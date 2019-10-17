<?php

namespace app\admin\model\order\printlabel;

use think\Model;
use think\Db;


class Nihao extends Model
{
    
    //数据库
    // protected $connection = 'database.db_nihao_online';
    protected $connection = 'database.db_nihao';
    
    // 表名
    protected $table = 'sales_flat_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    /**
     * 获取订单详情 nihao站
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getOrderDetail($ordertype, $entity_id)
    {
        switch ($ordertype) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            default:
                return false;
                break;
        }
        $map['order_id'] = $entity_id;
        $result = Db::connect($db)
            ->field('sku,name,qty_ordered,custom_prescription,original_price,price,discount_amount,product_options')
            ->table('sales_flat_order_item')
            ->where($map)
            ->select();
        foreach ($result as $k => &$v) {
            $v['product_options'] = unserialize($v['product_options']);
            $v['prescription'] = json_decode($v['product_options']['info_buyRequest']['tmplens']['prescription'], true);
            $v['prescription'] = array_merge($v['prescription'],$v['product_options']['info_buyRequest']['tmplens']);
            unset($v['product_options']);
        }
        unset($v);
        if (!$result) {
            return false;
        }
        return $result;
    }


}
