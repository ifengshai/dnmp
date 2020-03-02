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
	/***
	 *获取数据
	 *@param id 平台ID
	 */
	public function getList($id)
	{
        $where['order_platform'] = $id;
		$result = Db::name('operation_analysis')->where($where)->find();
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