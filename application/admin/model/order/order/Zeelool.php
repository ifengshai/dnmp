<?php

namespace app\admin\model\order\order;

use think\Model;
use think\Db;


class Zeelool extends Model
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

    //名称获取器
    public function getCustomerFirstnameAttr($value, $data)
    {
        return $data['customer_firstname'] . ' ' . $data['customer_lastname'];
    }

    /**
     * 获取订单地址详情
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
        $map['address_type'] = 'shipping';
        $map['parent_id'] = $entity_id;
        $result = Db::connect($db)
            ->table('sales_flat_order_address')
            ->where($map)
            ->find();
        if (!$result) {
            return false;
        }
        return $result;
    }


    /**
     * 获取订单商品详情 多站公用方法
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getGoodsDetail($ordertype, $entity_id)
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
            $prescription_params = $v['product_options']['info_buyRequest']['tmplens']['prescription'];
            $lens_params = array();
            //处理处方参数
            foreach (explode("&", $prescription_params) as $prescription_key => $prescription_value) {
                $arr_value = explode("=", $prescription_value);
                if ($arr_value[0]) {
                    $lens_params[$arr_value[0]] = $arr_value[1];
                }
                

                //处理ADD转换    
                if (@$lens_params['os_add'] && @$lens_params['od_add']) {
                    $lens_params['total_add'] = '';
                } else {
                    $lens_params['total_add'] = @$lens_params['os_add'];
                }
                //处理PD转换  
                if (@$lens_params['pdcheck'] == 'on') {
                    $lens_params['pd'] = '';
                }
                // dump($lens_params);
                if (@$lens_params['prismcheck'] != 'on') {
                    $lens_params['od_pv'] = '';
                    $lens_params['od_bd'] = '';
                    $lens_params['od_pv_r'] = '';
                    $lens_params['od_bd_r'] = '';

                    $lens_params['os_pv'] = '';
                    $lens_params['os_bd'] = '';
                    $lens_params['os_pv_r'] = '';
                    $lens_params['os_bd_r'] = '';
                }
            }
            $v['product_options']['prescription'] = $lens_params;
            $v['product_options']['tmplens'] = $v['product_options']['info_buyRequest']['tmplens'];
        }
        unset($v);
        
        if (!$result) {
            return false;
        }
        return $result;
    }


    /**
     * 获取订单支付详情 多站公用方法
     * @param $ordertype 站点
     * @param $entity_id 订单id
     * @return array
     */
    public function getPayDetail($ordertype, $entity_id)
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
        $map['parent_id'] = $entity_id;
        $result = Db::connect($db)
            ->table('sales_flat_order_payment')
            ->field('additional_information,base_amount_paid,base_amount_ordered,base_shipping_amount,method,last_trans_id')
            ->where($map)
            ->find();
    
        $result['additional_information'] =  unserialize($result['additional_information']);
        
        if (!$result) {
            return false;
        }
        return $result;
    }
}
