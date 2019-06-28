<?php

namespace app\admin\model\saleAfterManage;

use think\Model;
use think\Db;


class SaleAfterTask extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'sale_after_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function getOrderPlatformList()
    {
        //return config('site.order_platform');
        return [0=>'请选择',1=>'zeelool站',2=>'Voogueme站',3=>'nihao',4=>'amazon',5=>'App'];
    }

    public function getOrderStatusList()
    {
        //return config('site.order_status');
        return [0=>'未付款',1=>'已付款'];
    }
    //优先级返回数据
    public function getPrtyIdList()
    {
        return [1=>'高',2=>'中',3=>'低'];
    }
    
    public function getOrderInfo($ordertype,$order_number)
    {
        switch ($ordertype){
            case 1:
                $db = 'database.db_config1';
                break;
            case 2:
                $db = 'database.db_config2';
                break;
            default:
                return false;
                break;
        }
        $result = Db::connect($db)->table('sales_flat_order')->where('increment_id','=',$order_number)->field('entity_id,status,increment_id,customer_email,customer_firstname,customer_lastname,total_item_count')->find();
        if(!$result){
            return false;
        }
        $item = Db::connect($db)->table('sales_flat_order_item')->where('order_id','=',$result['entity_id'])->field('item_id,name,sku,qty_ordered,product_options')->select();
        if(!$item){
            return false;
        }
        $arr = [];
        foreach($item as $key=> $val){
            $arr[$key]['item_id'] = $val['item_id'];
            $arr[$key]['name']    = $val['name'];
            $arr[$key]['sku']     = $val['sku'];
            $arr[$key]['qty_ordered']     = $val['qty_ordered'];
            $arr[$key]['product_options']     = unserialize($val['product_options']);
        }
        $result['item'] = $arr;
        return $result ? $result : false;
    }






}
