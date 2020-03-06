<?php

namespace app\admin\model;

use think\Model;
use think\Db;


class OperationAnalysis extends Model
{
    // 表名
    protected $name = 'operation_analysis';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    protected $order_status =  "and status in ('processing','complete','creditcard_proccessing','free_processing')";
	/***
	 *获取数据
	 *@param id 平台ID
	 */
	public function getList($id)
	{
        $order_status = $this->order_status;
        switch($id){
            case 1:
            $model = Db::connect('database.db_zeelool');
            break;
            case 2:
            $model = Db::connect('database.db_voogueme');
            break;
            case 3:
            $model = Db::connect('database.db_nihao');
            break;
            default:
            $model = false;
            break;            
        }
        $where['order_platform'] = $id;
        //求出本站点的今天所有的数据
        //今日销售额sql
        $today_sales_money_sql   = "SELECT round(sum(base_grand_total),2)  base_grand_total FROM sales_flat_order WHERE TO_DAYS(created_at) = TO_DAYS(NOW()) $order_status";
        //今日订单数sql
        $today_order_num_sql     = "SELECT count(*) counter FROM sales_flat_order WHERE TO_DAYS(created_at) = TO_DAYS(NOW())";
        //今日订单支付成功数sql
        $today_order_success_sql = "SELECT count(*) counter FROM sales_flat_order WHERE TO_DAYS(created_at) = TO_DAYS(NOW()) $order_status";
        //今日客单价
        //今日购物车总数sql
        $today_shoppingcart_total_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND TO_DAYS(created_at) = TO_DAYS(NOW())";
        //今日新增购物车sql
        $today_shoppingcart_new_sql   = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND TO_DAYS(updated_at) = TO_DAYS(NOW())";
        //今日新增注册用户数
        $today_register_customer_sql  = "SELECT count(*) counter from customer_entity where TO_DAYS(updated_at) = TO_DAYS(NOW())";   
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //今日销售额
        $today_sales_money_rs                   = $model->query($today_sales_money_sql);
        //今日订单数
        $today_order_num_rs                     = $model->query($today_order_num_sql);
        //今日订单支付成功数
        $today_order_success_rs                 = $model->query($today_order_success_sql);
        //今日购物车总数
        $today_shoppingcart_total_rs            = $model->query($today_shoppingcart_total_sql);
        //今日新增购物车总数
        $today_shoppingcart_new_rs              = $model->query($today_shoppingcart_new_sql);
        //今日新增注册用户数
        $today_register_customer_rs             = $model->query($today_register_customer_sql);
        $today_sales_money_data                 = $today_sales_money_rs[0]['base_grand_total'];
        $today_order_num_data                   = $today_order_num_rs[0]['counter'];
        $today_order_success_data               = $today_order_success_rs[0]['counter'];
        $today_unit_price_data                  = round(($today_sales_money_data/$today_order_success_data),2);
        $today_shoppingcart_total_data          = $today_shoppingcart_total_rs[0]['counter'];
        $today_shoppingcart_new_data            = $today_shoppingcart_new_rs[0]['counter'];
        $today_register_customer_data           = $today_register_customer_rs[0]['counter'];
        $today_shoppingcart_conversion_data     = round(($today_order_success_data/$today_shoppingcart_total_data),4)*100;
        $today_shoppingcart_newconversion_data  = round(($today_order_success_data/$today_shoppingcart_new_data),4)*100;

        $result = Db::name('operation_analysis')->where($where)->find();
        if($result){
            $result['today_sales_money']                = $today_sales_money_data;
            $result['today_order_num']                  = $today_order_num_data ;
            $result['today_order_success']              = $today_order_success_data;
            $result['today_unit_price']                 = $today_unit_price_data;
            $result['today_shoppingcart_total']         = $today_shoppingcart_total_data;
            $result['today_shoppingcart_new']           = $today_shoppingcart_new_data;
            $result['today_register_customer']          = $today_register_customer_data;
            $result['today_shoppingcart_conversion']    = $today_shoppingcart_conversion_data;
            $result['today_shoppingcart_newconversion'] = $today_shoppingcart_newconversion_data;
        }
		return $result;
    }
    /***
     * 获取所有数据相加
     */
    public function getAllList()
    {
        $result = Db::name('operation_analysis')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach($result as $k=>$v){
            $arr['yesterday_sales_money']                       += $v['yesterday_sales_money'];
            $arr['pastsevenday_sales_money']                    += $v['pastsevenday_sales_money'];
            $arr['pastthirtyday_sales_money']                   += $v['pastthirtyday_sales_money'];
            $arr['thismonth_sales_money']                       += $v['thismonth_sales_money'];
            $arr['lastmonth_sales_money']                       += $v['lastmonth_sales_money'];
            $arr['thisyear_sales_money']                        += $v['thisyear_sales_money'];
            $arr['total_sales_money']                           += $v['total_sales_money'];
            $arr['yesterday_order_num']                         += $v['yesterday_order_num'];
            $arr['pastsevenday_order_num']                      += $v['pastsevenday_order_num'];
            $arr['pastthirtyday_order_num']                     += $v['pastthirtyday_order_num'];
            $arr['thismonth_order_num']                         += $v['thismonth_order_num'];
            $arr['lastmonth_order_num']                         += $v['lastmonth_order_num'];
            $arr['thisyear_order_num']                          += $v['thisyear_order_num'];
            $arr['total_order_num']                             += $v['total_order_num'];
            $arr['yesterday_order_success']                     += $v['yesterday_order_success'];
            $arr['pastsevenday_order_success']                  += $v['pastsevenday_order_success'];
            $arr['pastthirtyday_order_success']                 += $v['pastthirtyday_order_success'];
            $arr['thismonth_order_success']                     += $v['thismonth_order_success'];
            $arr['lastmonth_order_success']                     += $v['lastmonth_order_success'];
            $arr['thisyear_order_success']                      += $v['thisyear_order_success'];
            $arr['total_order_success']                         += $v['total_order_success'];
            $arr['yesterday_unit_price']                        += $v['yesterday_unit_price'];
            $arr['pastsevenday_unit_price']                     += $v['pastsevenday_unit_price'];
            $arr['pastthirtyday_unit_price']                    += $v['pastthirtyday_unit_price'];
            $arr['thismonth_unit_price']                        += $v['thismonth_unit_price'];
            $arr['lastmonth_unit_price']                        += $v['lastmonth_unit_price'];
            $arr['thisyear_unit_price']                         += $v['thisyear_unit_price'];
            $arr['total_unit_price']                            += $v['total_unit_price'];
            $arr['yesterday_shoppingcart_total']                += $v['yesterday_shoppingcart_total'];
            $arr['pastsevenday_shoppingcart_total']             += $v['pastsevenday_shoppingcart_total'];
            $arr['pastthirtyday_shoppingcart_total']            += $v['pastthirtyday_shoppingcart_total'];
            $arr['thismonth_shoppingcart_total']                += $v['thismonth_shoppingcart_total'];
            $arr['lastmonth_shoppingcart_total']                += $v['lastmonth_shoppingcart_total'];
            $arr['thisyear_shoppingcart_total']                 += $v['thisyear_shoppingcart_total'];
            $arr['total_shoppingcart_total']                    += $v['total_shoppingcart_total'];
            $arr['yesterday_shoppingcart_conversion']           += $v['yesterday_shoppingcart_conversion'];
            $arr['pastsevenday_shoppingcart_conversion']        += $v['pastsevenday_shoppingcart_conversion'];
            $arr['pastthirtyday_shoppingcart_conversion']       += $v['pastthirtyday_shoppingcart_conversion'];
            $arr['thismonth_shoppingcart_conversion']           += $v['thismonth_shoppingcart_conversion'];
            $arr['lastmonth_shoppingcart_conversion']           += $v['lastmonth_shoppingcart_conversion'];  
            $arr['thisyear_shoppingcart_conversion']            += $v['thisyear_shoppingcart_conversion']; 
            $arr['total_shoppingcart_conversion']               += $v['total_shoppingcart_conversion']; 
            $arr['yesterday_shoppingcart_new']                  += $v['yesterday_shoppingcart_new'];
            $arr['pastsevenday_shoppingcart_new']               += $v['pastsevenday_shoppingcart_new'];
            $arr['pastthirtyday_shoppingcart_new']              += $v['pastthirtyday_shoppingcart_new'];
            $arr['thismonth_shoppingcart_new']                  += $v['thismonth_shoppingcart_new'];
            $arr['lastmonth_shoppingcart_new']                  += $v['lastmonth_shoppingcart_new'];  
            $arr['thisyear_shoppingcart_new']                   += $v['thisyear_shoppingcart_new']; 
            $arr['total_shoppingcart_new']                      += $v['total_shoppingcart_new']; 
            $arr['yesterday_shoppingcart_newconversion']        += $v['yesterday_shoppingcart_newconversion'];
            $arr['pastsevenday_shoppingcart_newconversion']     += $v['pastsevenday_shoppingcart_newconversion'];
            $arr['pastthirtyday_shoppingcart_newconversion']    += $v['pastthirtyday_shoppingcart_newconversion'];
            $arr['thismonth_shoppingcart_newconversion']        += $v['thismonth_shoppingcart_newconversion'];
            $arr['lastmonth_shoppingcart_newconversion']        += $v['lastmonth_shoppingcart_newconversion'];  
            $arr['thisyear_shoppingcart_newconversion']         += $v['thisyear_shoppingcart_newconversion']; 
            $arr['total_shoppingcart_newconversion']            += $v['total_shoppingcart_newconversion']; 
            $arr['yesterday_register_customer']                 += $v['yesterday_register_customer'];
            $arr['pastsevenday_register_customer']              += $v['pastsevenday_register_customer'];
            $arr['pastthirtyday_register_customer']             += $v['pastthirtyday_register_customer'];
            $arr['thismonth_register_customer']                 += $v['thismonth_register_customer'];
            $arr['lastmonth_register_customer']                 += $v['lastmonth_register_customer'];  
            $arr['thisyear_register_customer']                  += $v['thisyear_register_customer']; 
            $arr['total_register_customer']                     += $v['total_register_customer']; 
        }
        return $arr;
    }
}