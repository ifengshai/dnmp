<?php
namespace app\admin\model\operatedatacenter;

use think\Db;
use think\Model;


class Zeelool extends Model
{

    // 表名
    protected $name = 'datacenter_day';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];
    public function __construct()
    {
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
    }

    /**
     * 统计订单数
     *
     * @type 0:计算某天的数据1：计算总的数据
     * 当type == 0时，$time_str传某天时间；当type == 1时，$time_str传时间段
     * 订单统计条件：时间，状态
     * @author wpl
     * @since 2020/02/26 17:36:58
     * @return void
     */
    public function getOrderNum($time_str = '',$type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->zeelool->where($map_where)->where($arr_where)->count();
        if($type == 1){
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $order_num = $this->where($map)->where($where)->sum('order_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if($start <= $createat[3]){
                $arr['order_num'] = $order_num+$today_order_num;
            }else{
                $arr['order_num'] = $order_num;
            }
            //同比
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $same_order_num = $this->where($map)->where($same_where)->sum('order_num');
            $arr['same_order_num'] = $same_order_num != 0 ? round(($arr['order_num']-$same_order_num)/$same_order_num*100,2).'%' : 0;
            //环比
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            $huan_order_num = $this->where($map)->where($huan_where)->sum('order_num');
            $arr['huan_order_num'] = $huan_order_num != 0 ? round(($arr['order_num']-$huan_order_num)/$huan_order_num*100,2).'%' : 0;
        }else{
            //查询某天的数据
            if(!$time_str){
                $time_str = $start;
            }
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if($time_str == $start){
                $arr['order_num'] = $today_order_num;
            }else{
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['order_num'] = $this->where($map)->where($where)->sum('order_num');
            }
            $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $same_order_num = $this->where($map)->where($same_where)->sum('order_num');
            $arr['same_order_num'] = $same_order_num != 0 ? round(($arr['order_num']-$same_order_num)/$same_order_num*100,2).'%' : 0;

            $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            $huan_order_num = $this->where($map)->where($huan_where)->sum('order_num');
            $arr['huan_order_num'] = $huan_order_num != 0 ? round(($arr['order_num']-$huan_order_num)/$huan_order_num*100,2).'%' : 0;
        }
        return $arr;
    }

    /**
     * 统计客单价
     *
     * @Description
     * @author wpl
     * @since 2020/02/26 17:36:58
     * @return void
     */
    public function getOrderUnitPrice($time_str = '',$type = 0)
    {
        $map[] = ['exp',Db::raw("customer_id is not null and customer_id != 0")];
        $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        if($type == 1){
            //时间段统计客单价
            $createat = explode(' ', $time_str);
            $where['created_at'] = ['between', [$createat[0], $createat[3]]];
            $order_total = $this->zeelool->where($map)->where($where)->sum('base_grand_total');
            $order_user = $this->zeelool->where($map)->where($where)->count('distinct customer_id');
            $arr['order_unit_price'] = $order_user != 0 ? round($order_total/$order_user,2) : 0;
            //同比
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['created_at'] = ['between', [$same_start,$same_end]];
            $same_order_total = $this->zeelool->where($map)->where($same_where)->sum('base_grand_total');
            $same_order_user = $this->zeelool->where($map)->where($same_where)->count('distinct customer_id');
            $same_order_unit_price = $same_order_user != 0 ? round($same_order_total/$same_order_user,2) : 0;
            $arr['same_order_unit_price'] = $same_order_unit_price != 0 ? round(($arr['order_unit_price']-$same_order_unit_price)/$same_order_unit_price*100,2).'%' : 0;
            //环比
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['created_at'] = ['between', [$huan_start,$huan_end]];
            $huan_order_total = $this->zeelool->where($map)->where($huan_where)->sum('base_grand_total');
            $huan_order_user = $this->zeelool->where($map)->where($huan_where)->count('distinct customer_id');
            $huan_order_unit_price = $huan_order_user != 0 ? round($huan_order_total/$huan_order_user,2) : 0;
            $arr['huan_order_unit_price'] = $huan_order_unit_price != 0 ? round(($arr['order_unit_price']-$huan_order_unit_price)/$huan_order_unit_price*100,2).'%' : 0;
        }else{
            if(!$time_str){
                $time_str = date('Y-m-d');
            }
            $where['created_at'] = ['between', [$time_str,$time_str]];
            $order_total = $this->zeelool->where($map)->where($where)->sum('base_grand_total');
            $order_user = $this->zeelool->where($map)->where($where)->count('distinct customer_id');
            $arr['order_unit_price'] = $order_user != 0 ? round($order_total/$order_user,2) : 0;
            //同比
            $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
            $same_where['created_at'] = ['between', [$same_start,$same_end]];
            $same_order_total = $this->zeelool->where($map)->where($same_where)->sum('base_grand_total');
            $same_order_user = $this->zeelool->where($map)->where($same_where)->count('distinct customer_id');
            $same_order_unit_price = $same_order_user != 0 ? round($same_order_total/$same_order_user,2) : 0;
            $arr['same_order_unit_price'] = $same_order_unit_price != 0 ? round(($arr['order_unit_price']-$same_order_unit_price)/$same_order_unit_price*100,2).'%' : 0;
            //环比
            $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
            $huan_where['created_at'] = ['between', [$huan_start,$huan_end]];
            $huan_order_total = $this->zeelool->where($map)->where($huan_where)->sum('base_grand_total');
            $huan_order_user = $this->zeelool->where($map)->where($huan_where)->count('distinct customer_id');
            $huan_order_unit_price = $huan_order_user != 0 ? round($huan_order_total/$huan_order_user,2) : 0;
            $arr['huan_order_unit_price'] = $huan_order_unit_price != 0 ? round(($arr['order_unit_price']-$huan_order_unit_price)/$huan_order_unit_price*100,2).'%' : 0;
        }
        return $arr;
    }
    /*
     * 统计销售额
     * */
    public function getSalesTotalMoney($time_str = '',$type = 0){
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_sales_total_money = $this->zeelool->where($map_where)->where($arr_where)->sum('base_grand_total');
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $sales_total_money = $this->where($map)->where($where)->sum('sales_total_money');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if($start <= $createat[3]){
                $arr['sales_total_money'] = $sales_total_money+$today_sales_total_money;
            }else{
                $arr['sales_total_money'] = $sales_total_money;
            }
            //同比
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $same_sales_total_money = $this->where($map)->where($same_where)->sum('sales_total_money');
            $arr['same_sales_total_money'] = $same_sales_total_money != 0 ? round(($arr['sales_total_money']-$same_sales_total_money)/$same_sales_total_money*100,2).'%' : 0;
            //环比
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            $huan_sales_total_money = $this->where($map)->where($huan_where)->sum('sales_total_money');
            $arr['huan_sales_total_money'] = $huan_sales_total_money != 0 ?round(($arr['sales_total_money']-$huan_sales_total_money)/$huan_sales_total_money*100,2).'%' : 0;
        }else{
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if(!$time_str){
                $time_str = $start;
            }
            if($time_str == $start){
                $arr['sales_total_money'] = $today_sales_total_money;
            }else{
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['sales_total_money'] = $this->where($map)->where($where)->sum('sales_total_money');
            }
            //同比
            $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $same_sales_total_money = $this->where($map)->where($same_where)->sum('sales_total_money');
            $arr['same_sales_total_money'] = $same_sales_total_money != 0 ? round(($arr['sales_total_money']-$same_sales_total_money)/$same_sales_total_money*100,2).'%' : 0;
            //环比
            $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            $huan_sales_total_money = $this->where($map)->where($huan_where)->sum('sales_total_money');
            $arr['huan_sales_total_money'] = $huan_sales_total_money != 0 ?round(($arr['sales_total_money']-$huan_sales_total_money)/$huan_sales_total_money*100,2).'%' : 0;
        }
        return $arr;
    }
    /*
     * 统计邮费
     * */
    public function getShippingTotalMoney($time_str = '',$type = 0){
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_shipping_total_money = $this->zeelool->where($map_where)->where($arr_where)->sum('base_shipping_amount');
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $shipping_total_money = $this->where($map)->where($where)->sum('shipping_total_money');
            if($start <= $createat[3]){
                $arr['shipping_total_money'] = $shipping_total_money+$today_shipping_total_money;
            }else{
                $arr['shipping_total_money'] = $shipping_total_money;
            }
            //同比
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $same_shipping_total_money = $this->where($map)->where($same_where)->sum('shipping_total_money');
            $arr['same_shipping_total_money'] = $same_shipping_total_money != 0 ? round(($arr['shipping_total_money']-$same_shipping_total_money)/$same_shipping_total_money*100,2).'%' : 0;
            //环比
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            $huan_shipping_total_money = $this->where($map)->where($huan_where)->sum('shipping_total_money');
            $arr['huan_shipping_total_money'] = $huan_shipping_total_money != 0 ? round(($arr['shipping_total_money']-$huan_shipping_total_money)/$huan_shipping_total_money*100,2).'%' : 0;
        }else{
            if(!$time_str){
                $time_str = $start;
            }
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if($time_str == $start){
                $arr['shipping_total_money'] = $today_shipping_total_money;
            }else{
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['shipping_total_money'] = $this->where($map)->where($where)->sum('shipping_total_money');
            }
            //同比
            $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $same_shipping_total_money = $this->where($map)->where($same_where)->sum('shipping_total_money');
            $arr['same_shipping_total_money'] = $same_shipping_total_money != 0 ? round(($arr['shipping_total_money']-$same_shipping_total_money)/$same_shipping_total_money*100,2).'%' : 0;
            //环比
            $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            $huan_shipping_total_money = $this->where($map)->where($huan_where)->sum('shipping_total_money');
            $arr['huan_shipping_total_money'] = $huan_shipping_total_money != 0 ? round(($arr['shipping_total_money']-$huan_shipping_total_money)/$huan_shipping_total_money*100,2).'%' : 0;
        }
        return $arr;
    }
    /*
     * 获取补发单数量
     * */
    public function getReplacementOrderNum($time_str = ''){
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 4;  //补发
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->zeelool->where($map_where)->where($arr_where)->count();
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $replacement_order_num = $this->where($map)->where($where)->sum('replacement_order_num');
            if($start <= $createat[3]){
                $arr['replacement_order_num'] = $replacement_order_num+$today_order_num;
            }else{
                $arr['replacement_order_num'] = $replacement_order_num;
            }
        }else{
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start,$end]];
            $arr['replacement_order_num'] = $today_order_num;
        }
        return $arr;
    }
    /*
     * 获取补发订单销售额
     * */
    public function getReplacementOrderTotal($time_str = ''){
        $map['site'] = 1;
        //查询当天的订单金额
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 4;  //补发
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_total = $this->zeelool->where($map_where)->where($arr_where)->sum('base_grand_total');
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $replacement_order_total = $this->where($map)->where($where)->sum('replacement_order_total');
            if($start <= $createat[3]){
                $arr['replacement_order_total'] = $replacement_order_total+$today_order_total;
            }else{
                $arr['replacement_order_total'] = $replacement_order_total;
            }
        }else{
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start,$end]];
            $arr['replacement_order_total'] = $today_order_total;
        }
        return $arr;
    }
    /*
     * 获取网红单数量
     * */
    public function getOnlineCelebrityOrderNum($time_str = ''){
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 3;  //网红
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->zeelool->where($map_where)->where($arr_where)->count();
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $online_celebrity_order_num = $this->where($map)->where($where)->sum('online_celebrity_order_num');
            if($start <= $createat[3]){
                $arr['online_celebrity_order_num'] = $online_celebrity_order_num+$today_order_num;
            }else{
                $arr['online_celebrity_order_num'] = $online_celebrity_order_num;
            }
        }else{
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start,$end]];
            $arr['online_celebrity_order_num'] = $today_order_num;
        }
        return $arr;
    }
    /*
     * 获取网红订单销售额
     * */
    public function getOnlineCelebrityOrderTotal($time_str = ''){
        $map['site'] = 1;
        //查询当天的订单金额
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 3;  //网红
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_total = $this->zeelool->where($map_where)->where($arr_where)->sum('base_grand_total');
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $online_celebrity_order_total = $this->where($map)->where($where)->sum('online_celebrity_order_total');
            if($start <= $createat[3]){
                $arr['online_celebrity_order_total'] = $online_celebrity_order_total+$today_order_total;
            }else{
                $arr['online_celebrity_order_total'] = $online_celebrity_order_total;
            }
        }else{
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start,$end]];
            $arr['online_celebrity_order_total'] = $today_order_total;
        }
        return $arr;
    }
}
