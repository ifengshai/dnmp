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
    protected $order_status =  "and status in ('processing','complete','creditcard_proccessing','free_processing','paypal_canceled_reversal','paypal_reversed') and order_type not in (4,5)";
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
            case 4:
            $model = Db::connect('database.db_meeloog');
            break;
            case 9:
            $model = Db::connect('database.db_zeelool_es');
            break;
            case 10:
            $model = Db::connect('database.db_zeelool_de');
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
            if ($v['order_platform'] > 4) {
                continue;
            }
            $arr['yesterday_sales_money']                       += $v['yesterday_sales_money'];
            $arr['pastsevenday_sales_money']                    += $v['pastsevenday_sales_money'];
            $arr['pastthirtyday_sales_money']                   += $v['pastthirtyday_sales_money'];
            $arr['thismonth_sales_money']                       += $v['thismonth_sales_money'];
            $arr['lastmonth_sales_money']                       += $v['lastmonth_sales_money'];
            $arr['thisyear_sales_money']                        += $v['thisyear_sales_money'];
            $arr['lastyear_sales_money']                        += $v['lastyear_sales_money'];
            $arr['total_sales_money']                           += $v['total_sales_money'];
            $arr['yesterday_order_num']                         += $v['yesterday_order_num'];
            $arr['pastsevenday_order_num']                      += $v['pastsevenday_order_num'];
            $arr['pastthirtyday_order_num']                     += $v['pastthirtyday_order_num'];
            $arr['thismonth_order_num']                         += $v['thismonth_order_num'];
            $arr['lastmonth_order_num']                         += $v['lastmonth_order_num'];
            $arr['thisyear_order_num']                          += $v['thisyear_order_num'];
            $arr['lastyear_order_num']                          += $v['lastyear_order_num'];
            $arr['total_order_num']                             += $v['total_order_num'];
            $arr['yesterday_order_success']                     += $v['yesterday_order_success'];
            $arr['pastsevenday_order_success']                  += $v['pastsevenday_order_success'];
            $arr['pastthirtyday_order_success']                 += $v['pastthirtyday_order_success'];
            $arr['thismonth_order_success']                     += $v['thismonth_order_success'];
            $arr['lastmonth_order_success']                     += $v['lastmonth_order_success'];
            $arr['thisyear_order_success']                      += $v['thisyear_order_success'];
            $arr['lastyear_order_success']                      += $v['lastyear_order_success'];
            $arr['total_order_success']                         += $v['total_order_success'];
            $arr['yesterday_unit_price']                        += $v['yesterday_unit_price'];
            $arr['pastsevenday_unit_price']                     += $v['pastsevenday_unit_price'];
            $arr['pastthirtyday_unit_price']                    += $v['pastthirtyday_unit_price'];
            $arr['thismonth_unit_price']                        += $v['thismonth_unit_price'];
            $arr['lastmonth_unit_price']                        += $v['lastmonth_unit_price'];
            $arr['thisyear_unit_price']                         += $v['thisyear_unit_price'];
            $arr['lastyear_unit_price']                         += $v['lastyear_unit_price'];
            $arr['total_unit_price']                            += $v['total_unit_price'];
            $arr['yesterday_shoppingcart_total']                += $v['yesterday_shoppingcart_total'];
            $arr['pastsevenday_shoppingcart_total']             += $v['pastsevenday_shoppingcart_total'];
            $arr['pastthirtyday_shoppingcart_total']            += $v['pastthirtyday_shoppingcart_total'];
            $arr['thismonth_shoppingcart_total']                += $v['thismonth_shoppingcart_total'];
            $arr['lastmonth_shoppingcart_total']                += $v['lastmonth_shoppingcart_total'];
            $arr['thisyear_shoppingcart_total']                 += $v['thisyear_shoppingcart_total'];
            $arr['lastyear_shoppingcart_total']                 += $v['lastyear_shoppingcart_total'];
            $arr['total_shoppingcart_total']                    += $v['total_shoppingcart_total'];
            $arr['yesterday_shoppingcart_conversion']           += $v['yesterday_shoppingcart_conversion'];
            $arr['pastsevenday_shoppingcart_conversion']        += $v['pastsevenday_shoppingcart_conversion'];
            $arr['pastthirtyday_shoppingcart_conversion']       += $v['pastthirtyday_shoppingcart_conversion'];
            $arr['thismonth_shoppingcart_conversion']           += $v['thismonth_shoppingcart_conversion'];
            $arr['lastmonth_shoppingcart_conversion']           += $v['lastmonth_shoppingcart_conversion'];  
            $arr['thisyear_shoppingcart_conversion']            += $v['thisyear_shoppingcart_conversion'];
            $arr['lastyear_shoppingcart_conversion']            += $v['lastyear_shoppingcart_conversion']; 
            $arr['total_shoppingcart_conversion']               += $v['total_shoppingcart_conversion']; 
            $arr['yesterday_shoppingcart_new']                  += $v['yesterday_shoppingcart_new'];
            $arr['pastsevenday_shoppingcart_new']               += $v['pastsevenday_shoppingcart_new'];
            $arr['pastthirtyday_shoppingcart_new']              += $v['pastthirtyday_shoppingcart_new'];
            $arr['thismonth_shoppingcart_new']                  += $v['thismonth_shoppingcart_new'];
            $arr['lastmonth_shoppingcart_new']                  += $v['lastmonth_shoppingcart_new'];  
            $arr['thisyear_shoppingcart_new']                   += $v['thisyear_shoppingcart_new'];
            $arr['lastyear_shoppingcart_new']                   += $v['lastyear_shoppingcart_new'];  
            $arr['total_shoppingcart_new']                      += $v['total_shoppingcart_new']; 
            $arr['yesterday_shoppingcart_newconversion']        += $v['yesterday_shoppingcart_newconversion'];
            $arr['pastsevenday_shoppingcart_newconversion']     += $v['pastsevenday_shoppingcart_newconversion'];
            $arr['pastthirtyday_shoppingcart_newconversion']    += $v['pastthirtyday_shoppingcart_newconversion'];
            $arr['thismonth_shoppingcart_newconversion']        += $v['thismonth_shoppingcart_newconversion'];
            $arr['lastmonth_shoppingcart_newconversion']        += $v['lastmonth_shoppingcart_newconversion'];  
            $arr['thisyear_shoppingcart_newconversion']         += $v['thisyear_shoppingcart_newconversion'];
            $arr['lastyear_shoppingcart_newconversion']         += $v['lastyear_shoppingcart_newconversion']; 
            $arr['total_shoppingcart_newconversion']            += $v['total_shoppingcart_newconversion']; 
            $arr['yesterday_register_customer']                 += $v['yesterday_register_customer'];
            $arr['pastsevenday_register_customer']              += $v['pastsevenday_register_customer'];
            $arr['pastthirtyday_register_customer']             += $v['pastthirtyday_register_customer'];
            $arr['thismonth_register_customer']                 += $v['thismonth_register_customer'];
            $arr['lastmonth_register_customer']                 += $v['lastmonth_register_customer'];  
            $arr['thisyear_register_customer']                  += $v['thisyear_register_customer'];
            $arr['lastyear_register_customer']                  += $v['lastyear_register_customer']; 
            $arr['total_register_customer']                     += $v['total_register_customer'];
            $arr['yesterday_sign_customer']                     += $v['yesterday_sign_customer'];
            $arr['pastsevenday_sign_customer']                  += $v['pastsevenday_sign_customer'];
            $arr['pastthirtyday_sign_customer']                 += $v['pastthirtyday_sign_customer'];
            $arr['thismonth_sign_customer']                     += $v['thismonth_sign_customer'];
            $arr['lastmonth_sign_customer']                     += $v['lastmonth_sign_customer'];  
            $arr['thisyear_sign_customer']                      += $v['thisyear_sign_customer'];
            $arr['lastyear_sign_customer']                      += $v['lastyear_sign_customer']; 
            $arr['total_sign_customer']                         += $v['total_sign_customer'];             
        }
        //求出zeelool今天的总和
        $zeelool_data = $this->getList(1);
        //求出voogueme今天的总和
        $voogueme_data = $this->getList(2);
        //求出nihao今天的总和
        $nihao_data    = $this->getList(3);
        //求出meeloog的总和
        $meeloog_data  = $this->getList(4);
        //求出zeelool_es的总和
        // $zeelool_es_data = $this->getList(9);
        // //求出zeelool_de的总和
        // $zeelool_de_data = $this->getList(10);  
        //总和
        $arr['today_sales_money']                           = @round(($zeelool_data['today_sales_money'] + $voogueme_data['today_sales_money'] + $nihao_data['today_sales_money'] + $meeloog_data['today_sales_money']),2);
        $arr['today_order_num']                             = @($zeelool_data['today_order_num'] + $voogueme_data['today_order_num'] + $nihao_data['today_order_num'] + $meeloog_data['today_order_num']);
        $arr['today_order_success']                         = @($zeelool_data['today_order_success'] + $voogueme_data['today_order_success'] + $nihao_data['today_order_success'] + $meeloog_data['today_order_success']);
        if($arr['today_order_success']>0){
            $arr['today_unit_price']                        = round($arr['today_sales_money'] /$arr['today_order_success'],2); 
        }else{
            $arr['today_unit_price']                        = 0;
        }
        //$arr['today_unit_price']                            = @round(($zeelool_data['today_unit_price'] + $voogueme_data['today_unit_price'] + $nihao_data['today_unit_price'] + $meeloog_data['today_unit_price'])/4,2);
        $arr['today_shoppingcart_total']                    = @($zeelool_data['today_shoppingcart_total'] + $voogueme_data['today_shoppingcart_total'] + $nihao_data['today_shoppingcart_total'] + $meeloog_data['today_shoppingcart_total'] );
        $arr['today_shoppingcart_new']                      = @($zeelool_data['today_shoppingcart_new'] + $voogueme_data['today_shoppingcart_new'] + $nihao_data['today_shoppingcart_new'] + $meeloog_data['today_shoppingcart_new'] );
        $arr['today_register_customer']                     = @($zeelool_data['today_register_customer'] + $voogueme_data['today_register_customer'] + $nihao_data['today_register_customer'] + $meeloog_data['today_register_customer']);
        $arr['today_sign_customer']                         = @($zeelool_data['today_sign_customer'] + $voogueme_data['today_sign_customer'] + $nihao_data['today_sign_customer'] + $meeloog_data['today_sign_customer']);
       if($arr['today_shoppingcart_total']>0){
         $arr['today_shoppingcart_conversion']              = round($arr['today_order_success']/$arr['today_shoppingcart_total']*100,2);
         $arr['today_shoppingcart_newconversion']           = round($arr['today_order_success']/$arr['today_shoppingcart_new']*100,2);
        }else{
         $arr['today_shoppingcart_conversion']              = 0;
         $arr['today_shoppingcart_newconversion']           = 0;
       }
        //$arr['today_shoppingcart_conversion']               = @round(($zeelool_data['today_shoppingcart_conversion'] + $voogueme_data['today_shoppingcart_conversion'] + $nihao_data['today_shoppingcart_conversion'] + $meeloog_data['today_shoppingcart_conversion'])/4,2);
        //$arr['today_shoppingcart_newconversion']            = @round(($zeelool_data['today_shoppingcart_newconversion'] + $voogueme_data['today_shoppingcart_newconversion'] + $nihao_data['today_shoppingcart_newconversion'] + $meeloog_data['today_shoppingcart_newconversion'])/4,2);
        //保留2位小数点
        // $arr['yesterday_unit_price']                        = round($arr['yesterday_unit_price']/3,2);
        // $arr['pastsevenday_unit_price']                     = round($arr['pastsevenday_unit_price']/3,2);
        // $arr['pastthirtyday_unit_price']                    = round($arr['pastthirtyday_unit_price']/3,2);
        // $arr['thismonth_unit_price']                        = round($arr['thismonth_unit_price']/3,2);
        // $arr['lastmonth_unit_price']                        = round($arr['lastmonth_unit_price']/3,2);
        // $arr['thisyear_unit_price']                         = round($arr['thisyear_unit_price']/3,2);
        // $arr['lastyear_unit_price']                         = round($arr['lastyear_unit_price']/3,2);
        // $arr['total_unit_price']                            = round($arr['total_unit_price']/3 ,2);
        if($arr['yesterday_order_success']>0){
            $arr['yesterday_unit_price']                    = round($arr['yesterday_sales_money']/$arr['yesterday_order_success'],2);
        }else{
            $arr['yesterday_unit_price']                    = 0;
        }
        if($arr['pastsevenday_order_success']>0){
            $arr['pastsevenday_unit_price']                 = round($arr['pastsevenday_sales_money']/$arr['pastsevenday_order_success'],2);
        }else{
            $arr['pastsevenday_unit_price']                 = 0;
        }
        if($arr['pastthirtyday_order_success']>0){
            $arr['pastthirtyday_unit_price']                = round($arr['pastthirtyday_sales_money']/$arr['pastthirtyday_order_success'],2);
        }else{
            $arr['pastthirtyday_unit_price']                = 0;
        }
        if($arr['thismonth_order_success']>0){
            $arr['thismonth_unit_price']                    = round($arr['thismonth_sales_money']/$arr['thismonth_order_success'],2);
        }else{
            $arr['thismonth_unit_price']                    = 0;
        }
        if($arr['lastmonth_order_success']>0){
            $arr['lastmonth_unit_price']                    = round($arr['lastmonth_sales_money']/$arr['lastmonth_order_success'],2);
        }else{
            $arr['lastmonth_unit_price']                    = 0;
        }
        if($arr['thisyear_order_success']>0){
            $arr['thisyear_unit_price']                    = round($arr['thisyear_sales_money']/$arr['thisyear_order_success'],2);
        }else{
            $arr['thisyear_unit_price']                    = 0;
        }
        if($arr['lastyear_order_success']>0){
            $arr['lastyear_unit_price']                    = round($arr['lastyear_sales_money']/$arr['lastyear_order_success'],2);
        }else{
            $arr['lastyear_unit_price']                    = 0;
        }
        if($arr['total_order_success']>0){
            $arr['total_unit_price']                       = round($arr['total_sales_money']/$arr['total_order_success'],2);
        }else{
            $arr['total_unit_price']                       = 0;
        }                                                
        // $arr['yesterday_shoppingcart_conversion']           = round($arr['yesterday_shoppingcart_conversion']/3,2);
        // $arr['pastsevenday_shoppingcart_conversion']        = round($arr['pastsevenday_shoppingcart_conversion']/3,2);
        // $arr['pastthirtyday_shoppingcart_conversion']       = round($arr['pastthirtyday_shoppingcart_conversion']/3,2);
        // $arr['thismonth_shoppingcart_conversion']           = round($arr['thismonth_shoppingcart_conversion']/3,2);
        // $arr['lastmonth_shoppingcart_conversion']           = round($arr['lastmonth_shoppingcart_conversion']/3,2);  
        // $arr['thisyear_shoppingcart_conversion']            = round($arr['thisyear_shoppingcart_conversion']/3,2);
        // $arr['lastyear_shoppingcart_conversion']            = round($arr['lastyear_shoppingcart_conversion']/3,2); 
        // $arr['total_shoppingcart_conversion']               = round($arr['total_shoppingcart_conversion']/3,2);
        // $yesterday_order_success_data / $yesterday_shoppingcart_total_data
        if($arr['yesterday_shoppingcart_total']>0){
            $arr['yesterday_shoppingcart_conversion']      = round($arr['yesterday_order_success']/$arr['yesterday_shoppingcart_total']*100,2);
        }else{
            $arr['yesterday_shoppingcart_conversion']      = 0;
        }    
        if($arr['pastsevenday_shoppingcart_total']>0){
            $arr['pastsevenday_shoppingcart_conversion']   = round($arr['pastsevenday_order_success']/$arr['pastsevenday_shoppingcart_total']*100,2);
        }else{
            $arr['pastsevenday_shoppingcart_conversion']   = 0; 
        }
        if($arr['pastthirtyday_shoppingcart_total']>0){
            $arr['pastthirtyday_shoppingcart_conversion']   = round($arr['pastthirtyday_order_success']/$arr['pastthirtyday_shoppingcart_total']*100,2);
        }else{
            $arr['pastthirtyday_shoppingcart_conversion']   = 0; 
        }
        if($arr['thismonth_shoppingcart_total']>0){
            $arr['thismonth_shoppingcart_conversion']   = round($arr['thismonth_order_success']/$arr['thismonth_shoppingcart_total']*100,2);
        }else{
            $arr['thismonth_shoppingcart_conversion']   = 0; 
        }        
        if($arr['lastmonth_shoppingcart_total']>0){
            $arr['lastmonth_shoppingcart_conversion']   = round($arr['lastmonth_order_success']/$arr['lastmonth_shoppingcart_total']*100,2);
        }else{
            $arr['lastmonth_shoppingcart_conversion']   = 0; 
        }
        if($arr['thisyear_shoppingcart_total']>0){
            $arr['thisyear_shoppingcart_conversion']    = round($arr['thisyear_order_success']/$arr['thisyear_shoppingcart_total']*100,2);
        }else{
            $arr['thisyear_shoppingcart_conversion']    = 0; 
        }
        if($arr['lastyear_shoppingcart_total']>0){
            $arr['lastyear_shoppingcart_conversion']   = round($arr['lastyear_order_success']/$arr['lastyear_shoppingcart_total']*100,2);
        }else{
            $arr['lastyear_shoppingcart_conversion']   = 0; 
        }
        if($arr['total_shoppingcart_total']>0){
            $arr['total_shoppingcart_conversion']   = round($arr['total_order_success']/$arr['total_shoppingcart_total']*100,2);
        }else{
            $arr['total_shoppingcart_conversion']   = 0; 
        }

        if($arr['yesterday_shoppingcart_new']>0){
            $arr['yesterday_shoppingcart_newconversion']      = round($arr['yesterday_order_success']/$arr['yesterday_shoppingcart_new']*100,2);
        }else{
            $arr['yesterday_shoppingcart_newconversion']      = 0;
        }    
        if($arr['pastsevenday_shoppingcart_new']>0){
            $arr['pastsevenday_shoppingcart_newconversion']   = round($arr['pastsevenday_order_success']/$arr['pastsevenday_shoppingcart_new']*100,2);
        }else{
            $arr['pastsevenday_shoppingcart_newconversion']   = 0; 
        }
        if($arr['pastthirtyday_shoppingcart_new']>0){
            $arr['pastthirtyday_shoppingcart_newconversion']   = round($arr['pastthirtyday_order_success']/$arr['pastthirtyday_shoppingcart_new']*100,2);
        }else{
            $arr['pastthirtyday_shoppingcart_newconversion']   = 0; 
        }
        if($arr['thismonth_shoppingcart_new']>0){
            $arr['thismonth_shoppingcart_newconversion']   = round($arr['thismonth_order_success']/$arr['thismonth_shoppingcart_new']*100,2);
        }else{
            $arr['thismonth_shoppingcart_newconversion']   = 0; 
        }        
        if($arr['lastmonth_shoppingcart_new']>0){
            $arr['lastmonth_shoppingcart_newconversion']   = round($arr['lastmonth_order_success']/$arr['lastmonth_shoppingcart_new']*100,2);
        }else{
            $arr['lastmonth_shoppingcart_newconversion']   = 0; 
        }
        if($arr['thisyear_shoppingcart_new']>0){
            $arr['thisyear_shoppingcart_newconversion']    = round($arr['thisyear_order_success']/$arr['thisyear_shoppingcart_new']*100,2);
        }else{
            $arr['thisyear_shoppingcart_newconversion']    = 0; 
        }
        if($arr['lastyear_shoppingcart_new']>0){
            $arr['lastyear_shoppingcart_newconversion']   = round($arr['lastyear_order_success']/$arr['lastyear_shoppingcart_new']*100,2);
        }else{
            $arr['lastyear_shoppingcart_newconversion']   = 0; 
        }
        if($arr['total_shoppingcart_new']>0){
            $arr['total_shoppingcart_newconversion']   = round($arr['total_order_success']/$arr['total_shoppingcart_new']*100,2);
        }else{
            $arr['total_shoppingcart_newconversion']   = 0; 
        }                                          
        // $arr['yesterday_shoppingcart_newconversion']        = round($arr['yesterday_shoppingcart_newconversion']/3,2);
        // $arr['pastsevenday_shoppingcart_newconversion']     = round($arr['pastsevenday_shoppingcart_newconversion']/3,2);
        // $arr['pastthirtyday_shoppingcart_newconversion']    = round($arr['pastthirtyday_shoppingcart_newconversion']/3,2);
        // $arr['thismonth_shoppingcart_newconversion']        = round($arr['thismonth_shoppingcart_newconversion']/3,2);
        // $arr['lastmonth_shoppingcart_newconversion']        = round($arr['lastmonth_shoppingcart_newconversion']/3,2);  
        // $arr['thisyear_shoppingcart_newconversion']         = round($arr['thisyear_shoppingcart_newconversion']/3,2);
        // $arr['lastyear_shoppingcart_newconversion']         = round($arr['lastyear_shoppingcart_newconversion']/3,2); 
        // $arr['total_shoppingcart_newconversion']            = round($arr['total_shoppingcart_newconversion']/3,2);
        $arr['yesterday_sales_money']                       = round($arr['yesterday_sales_money'],2);
        $arr['pastsevenday_sales_money']                    = round($arr['pastsevenday_sales_money'],2);
        $arr['pastthirtyday_sales_money']                   = round($arr['pastthirtyday_sales_money'],2);
        $arr['thismonth_sales_money']                       = round($arr['thismonth_sales_money'],2);
        $arr['lastmonth_sales_money']                       = round($arr['lastmonth_sales_money'],2);
        $arr['thisyear_sales_money']                        = round($arr['thisyear_sales_money'],2);
        $arr['lastyear_sales_money']                        = round($arr['lastyear_sales_money'],2);
        $arr['total_sales_money']                           = round($arr['total_sales_money'],2);
        return $arr;
    }
}