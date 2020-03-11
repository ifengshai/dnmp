<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Cache;

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
    /**
     * 通过id判断需要传递的model
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 16:34:22 
     * @param [type] $id
     * @return void
     */
    public function get_model_by_id($id)
    {
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
        return $model;
    }
    /**
     * 根据站点获取今天销售额
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 15:26:02 
     * @return void
     */
    public function get_today_sales_money($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_sales_money_'.$id);
        if($cacheData){
            return $cacheData;
        }
        $order_status = $this->order_status;
        $model = $this->get_model_by_id($id);
        $where['order_platform'] = $id;
        //今日销售额sql
        $today_sales_money_sql   = "SELECT round(sum(base_grand_total),2)  base_grand_total FROM sales_flat_order WHERE TO_DAYS(created_at) = TO_DAYS(NOW()) $order_status";
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        //今日销售额
        $today_sales_money_rs    = $model->query($today_sales_money_sql);
        $today_sales_money_data  = $today_sales_money_rs[0]['base_grand_total'];
        Cache::set('operationAnalysis_get_today_sales_money_'.$id,$today_sales_money_data,3600);
        return $today_sales_money_data;
    }
    /**
     * 根据站点获取今天订单数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 15:27:22 
     * @return void
     */
    public function get_today_order_num($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_order_num_'.$id);
        if($cacheData){
            return $cacheData;
        }
        $model = $this->get_model_by_id($id);
        //今日订单数sql
        $today_order_num_sql     = "SELECT count(*) counter FROM sales_flat_order WHERE TO_DAYS(created_at) = TO_DAYS(NOW())";
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        //今日订单数
        $today_order_num_rs      = $model->query($today_order_num_sql);
        $today_order_num_data    = $today_order_num_rs[0]['counter'];
        Cache::set('operationAnalysis_get_today_order_num_'.$id,$today_order_num_data,3600);
        return $today_order_num_data;
    }
    /**
     * 根据站点获取今日订单支付成功数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 15:28:50 
     * @param [type] $id
     * @return void
     */
    public function get_today_order_success($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_order_success_'.$id);
        if($cacheData){
            return $cacheData;
        }
        $order_status = $this->order_status;
        $model = $this->get_model_by_id($id);
        //今日订单支付成功数sql
        $today_order_success_sql = "SELECT count(*) counter FROM sales_flat_order WHERE TO_DAYS(created_at) = TO_DAYS(NOW()) $order_status";
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        //今日订单支付成功数
        $today_order_success_rs       = $model->query($today_order_success_sql);
        $today_order_success_data     = $today_order_success_rs[0]['counter'];
        Cache::set('operationAnalysis_get_today_order_success_'.$id,$today_order_success_data,3600);
        return $today_order_success_data;
    }
     /**
     * 根据站点获取今日购物车总数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 16:11:26 
     * @return void
     */
    public function get_today_shoppingcart_total($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_shoppingcart_total_'.$id);
        if($cacheData){
           return $cacheData; 
        }
        $model = $this->get_model_by_id($id);
        //今日购物车总数sql
        $today_shoppingcart_total_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND TO_DAYS(created_at) = TO_DAYS(NOW())";
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //今日购物车总数
        $today_shoppingcart_total_rs    = $model->query($today_shoppingcart_total_sql);
        $today_shoppingcart_total_data  = $today_shoppingcart_total_rs[0]['counter'];
        Cache::set('operationAnalysis_get_today_shoppingcart_total_'.$id,$today_shoppingcart_total_data,3600);
        return $today_shoppingcart_total_data;

    }
    /**
     * 根据站点获取今日购物车新增总数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 16:11:26 
     * @return void
     */
    public function get_today_shoppingcart_new($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_shoppingcart_new_'.$id);
        if($cacheData){
            return $cacheData;
        }
        $model = $this->get_model_by_id($id);
        //今日新增购物车sql
        $today_shoppingcart_new_sql   = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND TO_DAYS(updated_at) = TO_DAYS(NOW())";
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        //今日新增购物车总数
        $today_shoppingcart_new_rs     = $model->query($today_shoppingcart_new_sql);
        $today_shoppingcart_new_data   = $today_shoppingcart_new_rs[0]['counter'];
        Cache::set('operationAnalysis_get_today_shoppingcart_new_'.$id,$today_shoppingcart_new_data,3600);
        return $today_shoppingcart_new_data;
    }   
    /**
     * 根据站点获取今日注册总数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 16:11:26 
     * @return void
     */
    public function get_today_register_customer($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_register_customer_'.$id);
        if($cacheData){
            return $cacheData;
        }
        $model = $this->get_model_by_id($id);
        //今日新增注册用户数
        $today_register_customer_sql  = "SELECT count(*) counter from customer_entity where TO_DAYS(created_at) = TO_DAYS(NOW())";
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //今日新增注册用户数
        $today_register_customer_rs    = $model->query($today_register_customer_sql);
        $today_register_customer_data           = $today_register_customer_rs[0]['counter'];
        Cache::set('operationAnalysis_get_today_register_customer_'.$id,$today_register_customer_data,3600);
        return $today_register_customer_data;         
    }
    /**
     * 根据站点获取今日登录用户数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/06 17:30:51 
     * @param [type] $id
     * @return void
     */
    public function get_today_sign_customer($id)
    {
        $cacheData = Cache::get('operationAnalysis_get_today_sign_customer_'.$id);
        if($cacheData){
            return $cacheData;
        }
        $model = $this->get_model_by_id($id);
        //今日新增登录用户数
        $today_sign_customer_sql = "SELECT count(*) counter from customer_entity where TO_DAYS(updated_at) = TO_DAYS(NOW())";
        $model->table('customer_entity')->query("set time_zone='+8:00'");
        //今日登录用户数
        $today_sign_customer_rs  = $model->query($today_sign_customer_sql);
        $today_sign_customer_data = $today_sign_customer_rs[0]['counter'];
        Cache::set('operationAnalysis_get_today_sign_customer_'.$id,$today_sign_customer_data,3600);
        return $today_sign_customer_data;  
    }             
	/***
	 *获取数据
	 *@param id 平台ID
	 */
	public function getList($id)
	{
        $model = $this->get_model_by_id($id);
        if(false == $model){
            return false;
        }
        $where['order_platform'] = $id;
        //求出本站点的今天所有的数据
        $today_sales_money_data                 = $this->get_today_sales_money($id);
        $today_order_num_data                   = $this->get_today_order_num($id);
        $today_order_success_data               = $this->get_today_order_success($id);
        $today_shoppingcart_total_data          = $this->get_today_shoppingcart_total($id);
        $today_shoppingcart_new_data            = $this->get_today_shoppingcart_new($id);
        $today_register_customer_data           = $this->get_today_register_customer($id);
        $today_sign_customer_data               = $this->get_today_sign_customer($id);
        if(false != $today_order_success_data){
            $today_unit_price_data              = round(($today_sales_money_data/$today_order_success_data),2);
        }else{
            $today_unit_price_data = 0;
        }
        if(false != $today_shoppingcart_total_data){
            $today_shoppingcart_conversion_data     = round(($today_order_success_data/$today_shoppingcart_total_data),4)*100;
        }else{
            $today_shoppingcart_conversion_data = 0;
        }
        if(false != $today_shoppingcart_new_data){
            $today_shoppingcart_newconversion_data  = round(($today_order_success_data/$today_shoppingcart_new_data),4)*100;
        }else{
            $today_shoppingcart_newconversion_data  = 0;
        } 
        $result = Db::name('operation_analysis')->where($where)->find();
        if($result){
            $result['today_sales_money']                = $today_sales_money_data;
            $result['today_order_num']                  = $today_order_num_data;
            $result['today_order_success']              = $today_order_success_data;
            $result['today_unit_price']                 = $today_unit_price_data;
            $result['today_shoppingcart_total']         = $today_shoppingcart_total_data;
            $result['today_shoppingcart_new']           = $today_shoppingcart_new_data;
            $result['today_register_customer']          = $today_register_customer_data;
            $result['today_sign_customer']              = $today_sign_customer_data;
            $result['today_shoppingcart_conversion']    = round($today_shoppingcart_conversion_data,2);
            $result['today_shoppingcart_newconversion'] = round($today_shoppingcart_newconversion_data,2);
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
        foreach($result as $v){
            $arr['yesterday_sales_money']                       += round($v['yesterday_sales_money'],2);
            $arr['pastsevenday_sales_money']                    += round($v['pastsevenday_sales_money'],2);
            $arr['pastthirtyday_sales_money']                   += round($v['pastthirtyday_sales_money'],2);
            $arr['thismonth_sales_money']                       += round($v['thismonth_sales_money'],2);
            $arr['lastmonth_sales_money']                       += round($v['lastmonth_sales_money'],2);
            $arr['thisyear_sales_money']                        += round($v['thisyear_sales_money'],2);
            $arr['lastyear_sales_money']                        += round($v['lastyear_sales_money'],2);
            $arr['total_sales_money']                           += round($v['total_sales_money'],2);
            $arr['yesterday_order_num']                         += round($v['yesterday_order_num'],2);
            $arr['pastsevenday_order_num']                      += round($v['pastsevenday_order_num'],2);
            $arr['pastthirtyday_order_num']                     += round($v['pastthirtyday_order_num'],2);
            $arr['thismonth_order_num']                         += round($v['thismonth_order_num'],2);
            $arr['lastmonth_order_num']                         += round($v['lastmonth_order_num'],2);
            $arr['thisyear_order_num']                          += round($v['thisyear_order_num'],2);
            $arr['lastyear_order_num']                          += round($v['lastyear_order_num'],2);
            $arr['total_order_num']                             += round($v['total_order_num'],2);
            $arr['yesterday_order_success']                     += round($v['yesterday_order_success'],2);
            $arr['pastsevenday_order_success']                  += round($v['pastsevenday_order_success'],2);
            $arr['pastthirtyday_order_success']                 += round($v['pastthirtyday_order_success'],2);
            $arr['thismonth_order_success']                     += round($v['thismonth_order_success'],2);
            $arr['lastmonth_order_success']                     += round($v['lastmonth_order_success'],2);
            $arr['thisyear_order_success']                      += round($v['thisyear_order_success'],2);
            $arr['lastyear_order_success']                      += round($v['lastyear_order_success'],2);
            $arr['total_order_success']                         += round($v['total_order_success'],2);
            $arr['yesterday_unit_price']                        += round($v['yesterday_unit_price'],2);
            $arr['pastsevenday_unit_price']                     += round($v['pastsevenday_unit_price'],2);
            $arr['pastthirtyday_unit_price']                    += round($v['pastthirtyday_unit_price'],2);
            $arr['thismonth_unit_price']                        += round($v['thismonth_unit_price'],2);
            $arr['lastmonth_unit_price']                        += round($v['lastmonth_unit_price'],2);
            $arr['thisyear_unit_price']                         += round($v['thisyear_unit_price'],2);
            $arr['lastyear_unit_price']                         += round($v['lastyear_unit_price'],2);
            $arr['total_unit_price']                            += round($v['total_unit_price'],2);
            $arr['yesterday_shoppingcart_total']                += round($v['yesterday_shoppingcart_total'],2);
            $arr['pastsevenday_shoppingcart_total']             += round($v['pastsevenday_shoppingcart_total'],2);
            $arr['pastthirtyday_shoppingcart_total']            += round($v['pastthirtyday_shoppingcart_total'],2);
            $arr['thismonth_shoppingcart_total']                += round($v['thismonth_shoppingcart_total'],2);
            $arr['lastmonth_shoppingcart_total']                += round($v['lastmonth_shoppingcart_total'],2);
            $arr['thisyear_shoppingcart_total']                 += round($v['thisyear_shoppingcart_total'],2);
            $arr['lastyear_shoppingcart_total']                 += round($v['lastyear_shoppingcart_total'],2);
            $arr['total_shoppingcart_total']                    += round($v['total_shoppingcart_total'],2);
            $arr['yesterday_shoppingcart_conversion']           += round($v['yesterday_shoppingcart_conversion'],2);
            $arr['pastsevenday_shoppingcart_conversion']        += round($v['pastsevenday_shoppingcart_conversion'],2);
            $arr['pastthirtyday_shoppingcart_conversion']       += round($v['pastthirtyday_shoppingcart_conversion'],2);
            $arr['thismonth_shoppingcart_conversion']           += round($v['thismonth_shoppingcart_conversion'],2);
            $arr['lastmonth_shoppingcart_conversion']           += round($v['lastmonth_shoppingcart_conversion'],2);  
            $arr['thisyear_shoppingcart_conversion']            += round($v['thisyear_shoppingcart_conversion'],2);
            $arr['lastyear_shoppingcart_conversion']            += round($v['lastyear_shoppingcart_conversion'],2); 
            $arr['total_shoppingcart_conversion']               += round($v['total_shoppingcart_conversion'],2); 
            $arr['yesterday_shoppingcart_new']                  += round($v['yesterday_shoppingcart_new'],2);
            $arr['pastsevenday_shoppingcart_new']               += round($v['pastsevenday_shoppingcart_new'],2);
            $arr['pastthirtyday_shoppingcart_new']              += round($v['pastthirtyday_shoppingcart_new'],2);
            $arr['thismonth_shoppingcart_new']                  += round($v['thismonth_shoppingcart_new'],2);
            $arr['lastmonth_shoppingcart_new']                  += round($v['lastmonth_shoppingcart_new'],2);  
            $arr['thisyear_shoppingcart_new']                   += round($v['thisyear_shoppingcart_new'],2);
            $arr['lastyear_shoppingcart_new']                   += round($v['lastyear_shoppingcart_new'],2);  
            $arr['total_shoppingcart_new']                      += round($v['total_shoppingcart_new'],2); 
            $arr['yesterday_shoppingcart_newconversion']        += round($v['yesterday_shoppingcart_newconversion'],2);
            $arr['pastsevenday_shoppingcart_newconversion']     += round($v['pastsevenday_shoppingcart_newconversion'],2);
            $arr['pastthirtyday_shoppingcart_newconversion']    += round($v['pastthirtyday_shoppingcart_newconversion'],2);
            $arr['thismonth_shoppingcart_newconversion']        += round($v['thismonth_shoppingcart_newconversion'],2);
            $arr['lastmonth_shoppingcart_newconversion']        += round($v['lastmonth_shoppingcart_newconversion'],2);  
            $arr['thisyear_shoppingcart_newconversion']         += round($v['thisyear_shoppingcart_newconversion'],2);
            $arr['lastyear_shoppingcart_newconversion']         += round($v['lastyear_shoppingcart_newconversion'],2); 
            $arr['total_shoppingcart_newconversion']            += round($v['total_shoppingcart_newconversion'],2); 
            $arr['yesterday_register_customer']                 += round($v['yesterday_register_customer'],2);
            $arr['pastsevenday_register_customer']              += round($v['pastsevenday_register_customer'],2);
            $arr['pastthirtyday_register_customer']             += round($v['pastthirtyday_register_customer'],2);
            $arr['thismonth_register_customer']                 += round($v['thismonth_register_customer'],2);
            $arr['lastmonth_register_customer']                 += round($v['lastmonth_register_customer'],2);  
            $arr['thisyear_register_customer']                  += round($v['thisyear_register_customer'],2);
            $arr['lastyear_register_customer']                  += round($v['lastyear_register_customer'],2); 
            $arr['total_register_customer']                     += round($v['total_register_customer'],2);
            $arr['yesterday_sign_customer']                     += round($v['yesterday_sign_customer'],2);
            $arr['pastsevenday_sign_customer']                  += round($v['pastsevenday_sign_customer'],2);
            $arr['pastthirtyday_sign_customer']                 += round($v['pastthirtyday_sign_customer'],2);
            $arr['thismonth_sign_customer']                     += round($v['thismonth_sign_customer'],2);
            $arr['lastmonth_sign_customer']                     += round($v['lastmonth_sign_customer'],2);  
            $arr['thisyear_sign_customer']                      += round($v['thisyear_sign_customer'],2);
            $arr['lastyear_sign_customer']                      += round($v['lastyear_sign_customer'],2); 
            $arr['total_sign_customer']                         += round($v['total_sign_customer'],2);             
        }
        //求出zeelool今天的总和
        $zeelool_data = $this->getList(1);
        //求出voogueme今天的总和
        $voogueme_data = $this->getList(2);
        //求出nihao今天的总和
        $nihao_data    = $this->getList(3);
        //总和
        $arr['today_sales_money']                           = @($zeelool_data['today_sales_money'] + $voogueme_data['today_sales_money'] + $nihao_data['today_sales_money']);
        $arr['today_order_num']                             = @($zeelool_data['today_order_num'] + $voogueme_data['today_order_num'] + $nihao_data['today_order_num']);
        $arr['today_order_success']                         = @($zeelool_data['today_order_success'] + $voogueme_data['today_order_success'] + $nihao_data['today_order_success']);
        $arr['today_unit_price']                            = @round(($zeelool_data['today_unit_price'] + $voogueme_data['today_unit_price'] + $nihao_data['today_unit_price'])/3,2);
        $arr['today_shoppingcart_total']                    = @($zeelool_data['today_shoppingcart_total'] + $voogueme_data['today_shoppingcart_total'] + $nihao_data['today_shoppingcart_total']);
        $arr['today_shoppingcart_new']                      = @($zeelool_data['today_shoppingcart_new'] + $voogueme_data['today_shoppingcart_new'] + $nihao_data['today_shoppingcart_new']);
        $arr['today_register_customer']                     = @($zeelool_data['today_register_customer'] + $voogueme_data['today_register_customer'] + $nihao_data['today_register_customer']);
        $arr['today_sign_customer']                         = @($zeelool_data['today_sign_customer'] + $voogueme_data['today_sign_customer'] + $nihao_data['today_sign_customer']);
        $arr['today_shoppingcart_conversion']               = @round(($zeelool_data['today_shoppingcart_conversion'] + $voogueme_data['today_shoppingcart_conversion'] + $nihao_data['today_shoppingcart_conversion'])/3,2);
        $arr['today_shoppingcart_newconversion']            = @round(($zeelool_data['today_shoppingcart_newconversion'] + $voogueme_data['today_shoppingcart_newconversion'] + $nihao_data['today_shoppingcart_newconversion'])/3,2);
        //保留2位小数点
        $arr['yesterday_unit_price']                        = round($arr['yesterday_unit_price']/3,2);
        $arr['pastsevenday_unit_price']                     = round($arr['pastsevenday_unit_price']/3,2);
        $arr['pastthirtyday_unit_price']                    = round($arr['pastthirtyday_unit_price']/3,2);
        $arr['thismonth_unit_price']                        = round($arr['thismonth_unit_price']/3,2);
        $arr['lastmonth_unit_price']                        = round($arr['lastmonth_unit_price']/3,2);
        $arr['thisyear_unit_price']                         = round($arr['thisyear_unit_price']/3,2);
        $arr['lastyear_unit_price']                         = round($arr['lastyear_unit_price']/3,2);
        $arr['total_unit_price']                            = round($arr['total_unit_price']/3 ,2);
        $arr['yesterday_shoppingcart_conversion']           = round($arr['yesterday_shoppingcart_conversion']/3,2);
        $arr['pastsevenday_shoppingcart_conversion']        = round($arr['pastsevenday_shoppingcart_conversion']/3,2);
        $arr['pastthirtyday_shoppingcart_conversion']       = round($arr['pastthirtyday_shoppingcart_conversion']/3,2);
        $arr['thismonth_shoppingcart_conversion']           = round($arr['thismonth_shoppingcart_conversion']/3,2);
        $arr['lastmonth_shoppingcart_conversion']           = round($arr['lastmonth_shoppingcart_conversion']/3,2);  
        $arr['thisyear_shoppingcart_conversion']            = round($arr['thisyear_shoppingcart_conversion']/3,2);
        $arr['lastyear_shoppingcart_conversion']            = round($arr['lastyear_shoppingcart_conversion']/3,2); 
        $arr['total_shoppingcart_conversion']               = round($arr['total_shoppingcart_conversion']/3,2);
        $arr['yesterday_shoppingcart_newconversion']        = round($arr['yesterday_shoppingcart_newconversion']/3,2);
        $arr['pastsevenday_shoppingcart_newconversion']     = round($arr['pastsevenday_shoppingcart_newconversion']/3,2);
        $arr['pastthirtyday_shoppingcart_newconversion']    = round($arr['pastthirtyday_shoppingcart_newconversion']/3,2);
        $arr['thismonth_shoppingcart_newconversion']        = round($arr['thismonth_shoppingcart_newconversion']/3,2);
        $arr['lastmonth_shoppingcart_newconversion']        = round($arr['lastmonth_shoppingcart_newconversion']/3,2);  
        $arr['thisyear_shoppingcart_newconversion']         = round($arr['thisyear_shoppingcart_newconversion']/3,2);
        $arr['lastyear_shoppingcart_newconversion']         = round($arr['lastyear_shoppingcart_newconversion']/3,2); 
        $arr['total_shoppingcart_newconversion']            = round($arr['total_shoppingcart_newconversion']/3,2);        
        return $arr;
    }
}