<?php
namespace app\admin\model\operatedatacenter;

use think\Model;


class Datacenter extends Model
{

    // 表名
    protected $name = 'datacenter_day';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];

    /**
     * 活跃用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:39:38
     */
    public function getActiveUser($time_str = '',$type = 0)
    {
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        $arr['active_user_num'] = $this->where($where)->sum('active_user_num');
        $same_order_unit_price = $this->where($same_where)->sum('active_user_num');
        $huan_order_unit_price = $this->where($huan_where)->sum('active_user_num');

        $arr['same_active_user_num'] = $arr['active_user_num'] == 0 ? '100'.'%' : round(($same_order_unit_price - $arr['active_user_num'])/$arr['active_user_num'] * 100,2).'%';
        $arr['huan_active_user_num'] = $arr['active_user_num'] == 0 ? '100'.'%' : round(($huan_order_unit_price - $arr['active_user_num'])/$arr['active_user_num'] * 100,2).'%';

        return $arr;
    }

    /**
     * 注册用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:39:50
     */
    public function getRegisterUser($time_str = '',$type = 0)
    {
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        $arr['register_user_num'] = $this->where($where)->sum('register_num');
        $same_order_unit_price = $this->where($same_where)->sum('register_num');
        $huan_order_unit_price = $this->where($huan_where)->sum('register_num');

        $arr['same_register_user_num'] = $arr['register_user_num'] == 0 ? '100'.'%' : round(($same_order_unit_price - $arr['register_user_num'])/$arr['register_user_num'] * 100,2).'%';
        $arr['huan_register_user_num'] = $arr['register_user_num'] == 0 ? '100'.'%' : round(($huan_order_unit_price - $arr['register_user_num'])/$arr['register_user_num'] * 100,2).'%';

        return $arr;
    }

    /**
     * 复购用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:04
     */
    public function getAgainUser($time_str = '',$type = 0)
    {
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        $arr['order_unit_price'] = $this->where($where)->sum('order_unit_price');
        $same_order_unit_price = $this->where($same_where)->sum('order_unit_price');
        $huan_order_unit_price = $this->where($huan_where)->sum('order_unit_price');

        $arr['same_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100'.'%' : round(($same_order_unit_price - $arr['order_unit_price'])/$arr['order_unit_price'] * 100,2).'%';
        $arr['huan_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100'.'%' : round(($huan_order_unit_price - $arr['order_unit_price'])/$arr['order_unit_price'] * 100,2).'%';

        return $arr;
    }

    /**
     * vip用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:15
     */
    public function getVipUser($time_str = '',$type = 0)
    {
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        $arr['vip_user_num'] = $this->where($where)->sum('vip_user_num');
        $same_order_unit_price = $this->where($same_where)->sum('vip_user_num');
        $huan_order_unit_price = $this->where($huan_where)->sum('vip_user_num');

        $arr['same_vip_user_num'] = $arr['vip_user_num'] == 0 ? '100'.'%' : round(($same_order_unit_price - $arr['vip_user_num'])/$arr['vip_user_num'] * 100,2).'%';
        $arr['huan_vip_user_num'] = $arr['vip_user_num'] == 0 ? '100'.'%' : round(($huan_order_unit_price - $arr['vip_user_num'])/$arr['vip_user_num'] * 100,2).'%';

        return $arr;
    }
    /**
     * 统计订单All
     * 0:计算某天的数据1：计算总的数据
     * 当type == 0时，$time_str传某天时间；当type == 1时，$time_str传时间段
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 10:44:38
     */
    public function getOrderNum($time_str = '',$type = 0)
    {
        if($type == 1){
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            //查询某天的数据
            if($time_str){
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                //查询当天的数据
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        // dump($where);
        // dump($same_where);
        // dump($huan_where);
        $arr['order_num'] = $this->where($where)->sum('order_num');
        $same_order_num = $this->where($same_where)->sum('order_num');
        $huan_order_num = $this->where($huan_where)->sum('order_num');
        $arr['same_order_num'] = $arr['order_num'] == 0 ? '100'.'%' : round(($same_order_num - $arr['order_num'])/$arr['order_num'] * 100,2).'%';
        $arr['huan_order_num'] = $arr['order_num'] == 0 ? '100'.'%' : round(($huan_order_num - $arr['order_num'])/$arr['order_num'] * 100,2).'%';
        // dump($same_order_num);
        // dump($huan_order_num);
        // dump($arr);
        return $arr;
    }

    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:38:39
     */
    public function getOrderUnitPrice($time_str = '',$type = 0)
    {
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        $arr['order_unit_price'] = $this->where($where)->sum('order_unit_price');
        $same_order_unit_price = $this->where($same_where)->sum('order_unit_price');
        $huan_order_unit_price = $this->where($huan_where)->sum('order_unit_price');

        $arr['same_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100'.'%' : round(($same_order_unit_price - $arr['order_unit_price'])/$arr['order_unit_price'] * 100,2).'%';
        $arr['huan_order_unit_price'] = $arr['order_unit_price'] == 0 ? '100'.'%' : round(($huan_order_unit_price - $arr['order_unit_price'])/$arr['order_unit_price'] * 100,2).'%';

        return $arr;
    }
    /*
     * 统计销售额
     * */
    public function getSalesTotalMoney($time_str = '',$type = 0){
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }

        $arr['sales_total_money'] = $this->where($where)->sum('sales_total_money');
        $same_sales_total_money = $this->where($same_where)->sum('sales_total_money');
        $huan_sales_total_money = $this->where($huan_where)->sum('sales_total_money');

        $arr['same_sales_total_money'] = $arr['sales_total_money'] == 0 ? '100'.'%' : round(($same_sales_total_money - $arr['sales_total_money'])/$arr['sales_total_money'] * 100,2).'%';
        $arr['huan_sales_total_money'] = $arr['sales_total_money'] == 0 ? '100'.'%' : round(($huan_sales_total_money - $arr['sales_total_money'])/$arr['sales_total_money'] * 100,2).'%';

        return $arr;
    }
    /*
     * 统计邮费
     * */
    public function getShippingTotalMoney($time_str = '',$type = 0){
        if($type == 1){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            if($time_str){
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($time_str)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($time_str)));
                $where['day_date'] = ['between', [$time_str,$time_str]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }else{
                $start = $end = date('Y-m-d');
                $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
                $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
                $where['day_date'] = ['between', [$start,$end]];
                $same_where['day_date'] = ['between', [$same_start,$same_end]];
                $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
            }
        }
        $arr['shipping_total_money'] = $this->where($where)->sum('shipping_total_money');
        $same_shipping_total_money = $this->where($same_where)->sum('shipping_total_money');
        $huan_shipping_total_money = $this->where($huan_where)->sum('shipping_total_money');

        $arr['same_shipping_total_money'] = $arr['shipping_total_money'] == 0 ? '100'.'%' : round(($same_shipping_total_money - $arr['shipping_total_money'])/$arr['shipping_total_money'] * 100,2).'%';
        $arr['huan_shipping_total_money'] = $arr['shipping_total_money'] == 0 ? '100'.'%' : round(($huan_shipping_total_money - $arr['shipping_total_money'])/$arr['shipping_total_money'] * 100,2).'%';

        return $arr;
    }


}
