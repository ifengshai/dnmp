<?php

namespace app\admin\model\customer;

use think\Db;
use think\Model;


class WholesaleCustomer extends Model
{

    

    

    // 表名
    protected $name = 'wholesale_customer';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


    public function getCustomerEmail($order_platform,$email)
    {
        switch ($order_platform) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
/*            case 6:
                $db = 'database.db_weseeoptical';
                break;*/
            default:
                return 0;
                break;
        }
        if(!empty($email)){
            $result = Db::connect($db)->table('sales_flat_order o')->where('customer_email',$email)
                ->count();
            return $result;
        }

        return 0;

    }






}
