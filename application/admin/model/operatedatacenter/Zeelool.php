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
        $this->model = new \app\admin\model\order\order\Zeelool();
    }
    //获取着陆页数据
    public function getLanding($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');
// dump($time_str);
// dump($type);
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间的数据
            $active_user_num = $this->where($where)->sum('landing_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_landing(1, $start);
                $arr['landing_num'] = $active_user_num + $today_active_user;
            } else {
                $arr['landing_num'] = $active_user_num;
            }
        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_landing(1, $start);
                $arr['landing_num'] = $today_active_user;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['landing_num'] = $this->where($where)->sum('landing_num');
            }
        }

        return $arr;
    }
    //产品详情页
    public function getDetail($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间的数据
            $active_user_num = $this->where($where)->sum('detail_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_target13(1, $start);
                $arr['detail_num'] = $active_user_num + $today_active_user;
            } else {
                $arr['detail_num'] = $active_user_num;
            }
        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_target13(1, $start);
                $arr['detail_num'] = $today_active_user;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['detail_num'] = $this->where($where)->sum('detail_num');
            }
        }

        return $arr;
    }
    //加购
    public function getCart($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间的数据
            $active_user_num = $this->where($where)->sum('cart_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_target1(1, $start);
                $arr['cart_num'] = $active_user_num + $today_active_user;
            } else {
                $arr['cart_num'] = $active_user_num;
            }
        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_target1(1, $start);
                $arr['cart_num'] = $today_active_user;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['cart_num'] = $this->where($where)->sum('cart_num');
            }
        }

        return $arr;
    }
    //交易次数
    public function getComplete($time_str = '', $type = 0)
    {
        $start = date('Y-m-d');
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间的数据
            $active_user_num = $this->where($where)->sum('complete_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_target_end(1, $start);
                $arr['complete_num'] = $active_user_num + $today_active_user;
            } else {
                $arr['complete_num'] = $active_user_num;
            }
        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                //今天的实时着陆页数据 z站
                $today_active_user = $this->google_target_end(1, $start);
                $arr['complete_num'] = $today_active_user;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['complete_num'] = $this->where($where)->sum('complete_num');
            }
        }

        return $arr;
    }

    /*
     * 统计活跃用户数
     */
    public function getActiveUser($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        $start = date('Y-m-d');
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间的数据
            $active_user_num = $this->where($map)->where($where)->sum('active_user_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                //今天的实时活跃用户数 zeelool
                $today_active_user = $this->google_active_user(1, $start);
                $arr['active_user_num'] = $active_user_num + $today_active_user;
            } else {
                $arr['active_user_num'] = $active_user_num;
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($map)->where($same_where)->sum('active_user_num');
            $arr['same_active_user_num'] = $same_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($map)->where($huan_where)->sum('active_user_num');
            $arr['huan_active_user_num'] = $huan_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                //今天的实时活跃用户数 zeelool
                $today_active_user = $this->google_active_user(1, $start);
                $arr['active_user_num'] = $today_active_user;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['active_user_num'] = $this->where($map)->where($where)->sum('active_user_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $where['day_date'] = ['between', [$time_str, $time_str]];
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];

            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($map)->where($same_where)->sum('active_user_num');
            $arr['same_active_user_num'] = $same_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($map)->where($huan_where)->sum('active_user_num');
            $arr['huan_active_user_num'] = $huan_order_unit_price == 0 ? '100%' : round(($arr['active_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        }

        return $arr;
    }

    /*
     * 统计注册用户数
     */
    public function getRegisterUser($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        $start = date('Y-m-d');
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        //今天的实时注册用户数
        $today_register_user_num = $this->model->table('customer_entity')->where($register_where)->count();

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间内的数据
            $register_num = $this->where($map)->where($where)->sum('register_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['register_user_num'] = $register_num + $today_register_user_num;
            } else {
                $arr['register_user_num'] = $register_num;
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($map)->where($same_where)->sum('register_num');
            $arr['same_register_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($map)->where($same_where)->sum('register_num');
            $arr['huan_register_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        } else {
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['register_user_num'] = $today_register_user_num;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['register_user_num'] = $this->where($map)->where($where)->sum('register_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
            $where['day_date'] = ['between', [$start, $time_str]];
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($map)->where($same_where)->sum('register_num');
            $arr['same_register_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($map)->where($same_where)->sum('register_num');
            $arr['huan_register_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['register_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';
        }

        return $arr;
    }

    /*
     * 统计vip用户数 zeelool
     */
    public function getVipUser($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        $start = date('Y-m-d');
        $register_where = [];
        $register_where[] = ['exp', Db::raw("DATE_FORMAT(start_time, '%Y-%m-%d') = '" . $start . "'")];
        $vip_where['order_status'] = 'Success';
        //今天的实时vip用户数
        $today_register_user_num = $this->model->table('oc_vip_order')->where($vip_where)->where($register_where)->count();

        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            //这段时间内的数据
            $register_num = $this->where($map)->where($where)->sum('vip_user_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['vip_user_num'] = $register_num + $today_register_user_num;
            } else {
                $arr['vip_user_num'] = $register_num;
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($map)->where($same_where)->sum('vip_user_num');
            $arr['same_vip_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($map)->where($same_where)->sum('register_num');
            $arr['huan_vip_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';

        } else {
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['vip_user_num'] = $today_register_user_num;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['vip_user_num'] = $this->where($map)->where($where)->sum('vip_user_num');
            }
            $same_start = $same_end = date('Y-m-d', strtotime("-1 years", strtotime($start)));
            $huan_start = $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($start)));
            $where['day_date'] = ['between', [$start, $time_str]];
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            //同比搜索时间段内的所有站的数据 同比时间段内的数据为0 那么同比增长为100%
            $same_order_unit_price = $this->where($map)->where($same_where)->sum('vip_user_num');
            $arr['same_vip_user_num'] = $same_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%';
            //环比时间段内的所有站的数据 环比时间段内的数据为0 那么环比增长为100%
            $huan_order_unit_price = $this->where($map)->where($same_where)->sum('vip_user_num');
            $arr['huan_vip_user_num'] = $huan_order_unit_price == 0 ? '100' . '%' : round(($arr['vip_user_num'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%';
        }

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
    public function getAgainUser($time_str = '', $type = 0)
    {
        $createat = explode(' ', $time_str);
        $again_num = $this->get_again_user($createat);
        $same_create_at[0] = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
        $same_create_at[1] = $createat[1];
        $same_create_at[3] = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
        $same_create_at[4] = $createat[4];
        $same_again_num = $this->get_again_user($same_create_at);
        $huan_create_at[0] = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
        $huan_create_at[1] = $createat[1];
        $huan_create_at[3] = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
        $huan_create_at[4] = $createat[4];
        $huan_again_num = $this->get_again_user($huan_create_at);
        // dump($createat);
        // dump($same_create_at);
        // dump($huan_create_at);

        $arrs['again_user_num'] = $again_num;
        $arrs['same_again_user_num'] = $same_again_num == 0 ? '100' . '%' : round(($arrs['again_user_num'] - $same_again_num) / $same_again_num * 100, 2) . '%';
        $arrs['huan_again_user_num'] = $huan_again_num == 0 ? '100' . '%' : round(($arrs['again_user_num'] - $huan_again_num) / $huan_again_num * 100, 2) . '%';
        return $arrs;

    }
    //获取某一段时间内的复购用户数
    public function get_again_user($createat){

        $where['created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        $where['customer_id'] = ['>',0];
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];

        //查询时间段内的订单 根据customer_id先计算出此事件段内的复购用户数
        $order = $this->model
            ->where($map_where)
            ->where($where)
            ->field('customer_id')
            ->select();
        //二维数组转一维数组
        foreach($order as &$val){
            $arr[] = $val['customer_id'];
        }
        // dump($arr);
        //复购用户数
        $again_num = 0;
        // dump($arr);
        if (!empty($arr)){
            $new_arr = array_count_values($arr);
            // dump($new_arr);
            //去重过后的新数组
            foreach ($new_arr as $k=>$v){
                if ($v > 1){
                    $again_num += 1;
                    unset($new_arr[$k]);
                }
            }

            $wheres['created_at'] = ['not between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            foreach ($new_arr as $key=>$val){
                //判断之前是否有这些订单
                $another_order = $this->model->where('customer_id',$key)->where($map_where)->where($wheres)->value('customer_id');
                if (!empty($another_order)){
                    $again_num += 1;
                }
            }
        }
        return $again_num;
    }


    /**
     * 统计订单数
     *
     * @type 0:计算某天的数据1：计算总的数据
     * 当type == 0时，$time_str传某天时间；当type == 1时，$time_str传时间段
     * 订单统计条件：时间，状态
     * @return void
     * @since 2020/02/26 17:36:58
     * @author wpl
     */
    public function getOrderNum($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->model->where($map_where)->where('order_type',1)->where($arr_where)->count();
        if ($type == 1) {
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $order_num = $this->where($map)->where($where)->sum('order_num');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['order_num'] = $order_num + $today_order_num;
            } else {
                $arr['order_num'] = $order_num;
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_order_num = $this->where($map)->where($same_where)->sum('order_num');
            $arr['same_order_num'] = $same_order_num != 0 ? round(($arr['order_num'] - $same_order_num) / $same_order_num * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_order_num = $this->where($map)->where($huan_where)->sum('order_num');
            $arr['huan_order_num'] = $huan_order_num != 0 ? round(($arr['order_num'] - $huan_order_num) / $huan_order_num * 100, 2) . '%' : 0;
        } else {
            //查询某天的数据
            if (!$time_str) {
                $time_str = $start;
            }
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if ($time_str == $start) {
                $arr['order_num'] = $today_order_num;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['order_num'] = $this->where($map)->where($where)->sum('order_num');
            }
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where = [];
            $same_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $same_start . "'")];
            $same_order_num = $this->where($map)->where($same_where)->sum('order_num');
            $arr['same_order_num'] = $same_order_num != 0 ? round(($arr['order_num'] - $same_order_num) / $same_order_num * 100, 2) . '%' : 0;

            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where = [];
            $huan_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $huan_start . "'")];
            $huan_order_num = $this->where($map)->where($huan_where)->sum('order_num');
            $arr['huan_order_num'] = $huan_order_num != 0 ? round(($arr['order_num'] - $huan_order_num) / $huan_order_num * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /**
     * 统计客单价
     *
     * @Description
     * @return void
     * @since 2020/02/26 17:36:58
     * @author wpl
     */
    public function getOrderUnitPrice($time_str = '', $type = 0)
    {
        $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map['order_type'] = 1;
        if ($type == 1) {
            //时间段统计客单价
            $createat = explode(' ', $time_str);
            $where['created_at'] = ['between', [$createat[0], $createat[3]]];
            $order_total = $this->model->where($map)->where($where)->sum('base_grand_total');
            $order_user = $this->model->where($map)->where($where)->count();
            $arr['order_unit_price'] = $order_user != 0 ? round($order_total / $order_user, 2) : 0;
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['created_at'] = ['between', [$same_start, $same_end]];
            $same_order_total = $this->model->where($map)->where($same_where)->sum('base_grand_total');
            $same_order_user = $this->model->where($map)->where($same_where)->count();
            $same_order_unit_price = $same_order_user != 0 ? round($same_order_total / $same_order_user, 2) : 0;
            $arr['same_order_unit_price'] = $same_order_unit_price != 0 ? round(($arr['order_unit_price'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['created_at'] = ['between', [$huan_start, $huan_end]];
            $huan_order_total = $this->model->where($map)->where($huan_where)->sum('base_grand_total');
            $huan_order_user = $this->model->where($map)->where($huan_where)->count();
            $huan_order_unit_price = $huan_order_user != 0 ? round($huan_order_total / $huan_order_user, 2) : 0;
            $arr['huan_order_unit_price'] = $huan_order_unit_price != 0 ? round(($arr['order_unit_price'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%' : 0;
        } else {
            $start = date('Y-m-d');
            if (!$time_str || $time_str == $start) {
                $where = [];
                $where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $time_str . "'")];
                //获取当天的客单价
                $order_total = $this->model->where($map)->where($where)->sum('base_grand_total');
                $order_user = $this->model->where($map)->where($where)->count();
                $arr['order_unit_price'] = $order_user != 0 ? round($order_total / $order_user, 2) : 0;
            } else {
                $where = [];
                $where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $time_str . "'")];
                //读取数据库中的客单价
                $arr['order_unit_price'] = $this->where('site', 1)->where($where)->value('order_unit_price');
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where = [];
            $same_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $same_start . "'")];
            $same_order_unit_price = $this->where('site', 1)->where($same_where)->value('order_unit_price');
            $arr['same_order_unit_price'] = $same_order_unit_price != 0 ? round(($arr['order_unit_price'] - $same_order_unit_price) / $same_order_unit_price * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where = [];
            $huan_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $huan_start . "'")];
            $huan_order_unit_price = $this->where('site', 1)->where($huan_where)->value('order_unit_price');
            $arr['huan_order_unit_price'] = $huan_order_unit_price != 0 ? round(($arr['order_unit_price'] - $huan_order_unit_price) / $huan_order_unit_price * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /*
     * 统计销售额
     * */
    public function getSalesTotalMoney($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_sales_total_money = $this->model->where($map_where)->where($arr_where)->where('order_type',1)->sum('base_grand_total');
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $sales_total_money = $this->where($map)->where($where)->sum('sales_total_money');
            //判断是否包含当天数据，如果包含需要加上今天的数据
            if ($start <= $createat[3]) {
                $arr['sales_total_money'] = $sales_total_money + $today_sales_total_money;
            } else {
                $arr['sales_total_money'] = $sales_total_money;
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_sales_total_money = $this->where($map)->where($same_where)->sum('sales_total_money');
            $arr['same_sales_total_money'] = $same_sales_total_money != 0 ? round(($arr['sales_total_money'] - $same_sales_total_money) / $same_sales_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_sales_total_money = $this->where($map)->where($huan_where)->sum('sales_total_money');
            $arr['huan_sales_total_money'] = $huan_sales_total_money != 0 ? round(($arr['sales_total_money'] - $huan_sales_total_money) / $huan_sales_total_money * 100, 2) . '%' : 0;
        } else {
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if (!$time_str) {
                $time_str = $start;
            }
            if ($time_str == $start) {
                $arr['sales_total_money'] = $today_sales_total_money;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['sales_total_money'] = $this->where($map)->where($where)->sum('sales_total_money');
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where = [];
            $same_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $same_start . "'")];
            $same_sales_total_money = $this->where($map)->where($same_where)->sum('sales_total_money');
            $arr['same_sales_total_money'] = $same_sales_total_money != 0 ? round(($arr['sales_total_money'] - $same_sales_total_money) / $same_sales_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where = [];
            $huan_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $huan_start . "'")];
            $huan_sales_total_money = $this->where($map)->where($huan_where)->sum('sales_total_money');
            $arr['huan_sales_total_money'] = $huan_sales_total_money != 0 ? round(($arr['sales_total_money'] - $huan_sales_total_money) / $huan_sales_total_money * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /*
     * 统计邮费
     * */
    public function getShippingTotalMoney($time_str = '', $type = 0)
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_shipping_total_money = $this->model->where($map_where)->where($arr_where)->where('order_type',1)->sum('base_shipping_amount');
        if ($type == 1) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $shipping_total_money = $this->where($map)->where($where)->sum('shipping_total_money');
            if ($start <= $createat[3]) {
                $arr['shipping_total_money'] = $shipping_total_money + $today_shipping_total_money;
            } else {
                $arr['shipping_total_money'] = $shipping_total_money;
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($createat[0])));
            $same_end = date('Y-m-d', strtotime("-1 years", strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start, $same_end]];
            $same_shipping_total_money = $this->where($map)->where($same_where)->sum('shipping_total_money');
            $arr['same_shipping_total_money'] = $same_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $same_shipping_total_money) / $same_shipping_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($createat[0])));
            $huan_end = date('Y-m-d', strtotime("-1 months", strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start, $huan_end]];
            $huan_shipping_total_money = $this->where($map)->where($huan_where)->sum('shipping_total_money');
            $arr['huan_shipping_total_money'] = $huan_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $huan_shipping_total_money) / $huan_shipping_total_money * 100, 2) . '%' : 0;
        } else {
            if (!$time_str) {
                $time_str = $start;
            }
            //判断当前时间是否等于当前时间，如果等于，则实时读取当天数据
            if ($time_str == $start) {
                $arr['shipping_total_money'] = $today_shipping_total_money;
            } else {
                $where['day_date'] = ['between', [$time_str, $time_str]];
                $arr['shipping_total_money'] = $this->where($map)->where($where)->sum('shipping_total_money');
            }
            //同比
            $same_start = date('Y-m-d', strtotime("-1 years", strtotime($time_str)));
            $same_where = [];
            $same_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $same_start . "'")];
            $same_shipping_total_money = $this->where($map)->where($same_where)->sum('shipping_total_money');
            $arr['same_shipping_total_money'] = $same_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $same_shipping_total_money) / $same_shipping_total_money * 100, 2) . '%' : 0;
            //环比
            $huan_start = date('Y-m-d', strtotime("-1 months", strtotime($time_str)));
            $huan_where = [];
            $huan_where[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $huan_start . "'")];
            $huan_shipping_total_money = $this->where($map)->where($huan_where)->sum('shipping_total_money');
            $arr['huan_shipping_total_money'] = $huan_shipping_total_money != 0 ? round(($arr['shipping_total_money'] - $huan_shipping_total_money) / $huan_shipping_total_money * 100, 2) . '%' : 0;
        }
        return $arr;
    }

    /*
     * 获取补发单数量
     * */
    public function getReplacementOrderNum($time_str = '')
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 4;  //补发
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->model->where($map_where)->where($arr_where)->count();
        if ($time_str) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $replacement_order_num = $this->where($map)->where($where)->sum('replacement_order_num');
            if ($start <= $createat[3]) {
                $arr['replacement_order_num'] = $replacement_order_num + $today_order_num;
            } else {
                $arr['replacement_order_num'] = $replacement_order_num;
            }
        } else {
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start, $end]];
            $arr['replacement_order_num'] = $today_order_num;
        }
        return $arr;
    }

    /*
     * 获取补发订单销售额
     * */
    public function getReplacementOrderTotal($time_str = '')
    {
        $map['site'] = 1;
        //查询当天的订单金额
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 4;  //补发
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_total = $this->model->where($map_where)->where($arr_where)->sum('base_grand_total');
        if ($time_str) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $replacement_order_total = $this->where($map)->where($where)->sum('replacement_order_total');
            if ($start <= $createat[3]) {
                $arr['replacement_order_total'] = $replacement_order_total + $today_order_total;
            } else {
                $arr['replacement_order_total'] = $replacement_order_total;
            }
        } else {
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start, $end]];
            $arr['replacement_order_total'] = $today_order_total;
        }
        return $arr;
    }

    /*
     * 获取网红单数量
     * */
    public function getOnlineCelebrityOrderNum($time_str = '')
    {
        $map['site'] = 1;
        //查询当天的订单数
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 3;  //网红
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_num = $this->model->where($map_where)->where($arr_where)->count();
        if ($time_str) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $online_celebrity_order_num = $this->where($map)->where($where)->sum('online_celebrity_order_num');
            if ($start <= $createat[3]) {
                $arr['online_celebrity_order_num'] = $online_celebrity_order_num + $today_order_num;
            } else {
                $arr['online_celebrity_order_num'] = $online_celebrity_order_num;
            }
        } else {
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start, $end]];
            $arr['online_celebrity_order_num'] = $today_order_num;
        }
        return $arr;
    }

    /*
     * 获取网红订单销售额
     * */
    public function getOnlineCelebrityOrderTotal($time_str = '')
    {
        $map['site'] = 1;
        //查询当天的订单金额
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 3;  //网红
        $start = date('Y-m-d');
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        $today_order_total = $this->model->where($map_where)->where($arr_where)->sum('base_grand_total');
        if ($time_str) {
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $online_celebrity_order_total = $this->where($map)->where($where)->sum('online_celebrity_order_total');
            if ($start <= $createat[3]) {
                $arr['online_celebrity_order_total'] = $online_celebrity_order_total + $today_order_total;
            } else {
                $arr['online_celebrity_order_total'] = $online_celebrity_order_total;
            }
        } else {
            $start = $end = date('Y-m-d');
            $where['day_date'] = ['between', [$start, $end]];
            $arr['online_celebrity_order_total'] = $today_order_total;
        }
        return $arr;
    }
    /*
     * 查询时间段内金额段之间的订单数
     * */
    public function getMoneyOrderNum($time_str = ''){
        //获取订单金额范围在[0,20)阶段的订单数
        $arr['order_total0'] = $this->getMoneyOrderNumInfo(0,$time_str);
        $arr['order_total20'] = $this->getMoneyOrderNumInfo(1,$time_str);
        $arr['order_total30'] = $this->getMoneyOrderNumInfo(2,$time_str);
        $arr['order_total40'] = $this->getMoneyOrderNumInfo(3,$time_str);
        $arr['order_total50'] = $this->getMoneyOrderNumInfo(4,$time_str);
        $arr['order_total60'] = $this->getMoneyOrderNumInfo(5,$time_str);
        $arr['order_total80'] = $this->getMoneyOrderNumInfo(6,$time_str);
        $arr['order_total100'] = $this->getMoneyOrderNumInfo(7,$time_str);
        $arr['order_total200'] = $this->getMoneyOrderNumInfo(8,$time_str);
        return $arr;
    }
    public function getMoneyOrderNumInfo($num,$time_str = ''){
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 1;
        switch ($num){
            case 0:
                $arr_where['base_grand_total'] = ['between',[0,20]];
                break;
            case 1:
                $arr_where['base_grand_total'] = ['between',[20,30]];
                break;
            case 2:
                $arr_where['base_grand_total'] = ['between',[30,40]];
                break;
            case 3:
                $arr_where['base_grand_total'] = ['between',[40,50]];
                break;
            case 4:
                $arr_where['base_grand_total'] = ['between',[50,60]];
                break;
            case 5:
                $arr_where['base_grand_total'] = ['between',[60,80]];
                break;
            case 6:
                $arr_where['base_grand_total'] = ['between',[80,100]];
                break;
            case 7:
                $arr_where['base_grand_total'] = ['between',[100,200]];
                break;
            default:
                $arr_where['base_grand_total'] = ['egt',200];
                break;
        }
        if($time_str){
            $createat = explode(' ', $time_str);
            $map['created_at'] = ['between', [$createat[0], $createat[3]]];
        }else{
            $start = date('Y-m-d');
            $map = [];
            $map[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        }
        $arr['order_num'] = $this->model->where($map_where)->where($arr_where)->where($map)->count();
        $order_num = $this->model->where($map_where)->where($map)->count();
        $arr['order_num_rate'] = $order_num ? round($arr['order_num']/$order_num*100,2).'%' : 0;
        return $arr;
    }
    /*
     * 获取订单运费数据统计信息
     * */
    public function getOrderShipping($time_str = ''){
        $arr['flatrate_free'] = $this->getOrderShippingInfo(0,$time_str);
        $arr['flatrate_nofree'] = $this->getOrderShippingInfo(1,$time_str);
        $arr['tablerate_free'] = $this->getOrderShippingInfo(2,$time_str);
        $arr['tablerate_nofree'] = $this->getOrderShippingInfo(3,$time_str);
        return $arr;
    }
    public function getOrderShippingInfo($num,$time_str = ''){
        $map_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map_where['order_type'] = 1;
        switch ($num){
            case 0:
                $arr_where['shipping_method'] = ['in',['freeshipping_freeshipping','flatrate_flatrate']];
                $arr_where['base_shipping_amount'] = 0;
                break;
            case 1:
                $arr_where['shipping_method'] = ['in',['freeshipping_freeshipping','flatrate_flatrate']];
                $arr_where['base_shipping_amount'] = ['gt',0];
                break;
            case 2:
                $arr_where['shipping_method'] = ['in',['tablerate_bestway']];
                $arr_where['base_shipping_amount'] = 0;
                break;
            case 3:
                $arr_where['shipping_method'] = ['in',['tablerate_bestway']];
                $arr_where['base_shipping_amount'] = ['gt',0];
                break;
        }
        if($time_str){
            $createat = explode(' ', $time_str);
            $map['created_at'] = ['between', [$createat[0], $createat[3]]];
        }else{
            $start = date('Y-m-d');
            $map = [];
            $map[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $start . "'")];
        }
        $arr['order_num'] = $this->model->where($map_where)->where($arr_where)->where($map)->count();
        $order_num = $this->model->where($map_where)->where($map)->count();
        $arr['order_num_rate'] = $order_num ? round($arr['order_num']/$order_num*100,2).'%' : 0;
        $order_total = $this->model->where($map_where)->where($arr_where)->where($map)->sum('base_shipping_amount');
        $arr['order_total'] = round($order_total,2);
        return $arr;
    }
    //活跃用户数 调用此方法
    public function google_active_user($site, $start_time)
    {
        // dump();die;
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_active_user($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        return $result[0]['ga:1dayUsers'] ? round($result[0]['ga:1dayUsers'], 2) : 0;
    }

    protected function getReport_active_user($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:1dayUsers");
        $adCostMetric->setAlias("ga:1dayUsers");
        // $adCostMetric->setExpression("ga:adCost");
        // $adCostMetric->setAlias("ga:adCost");

        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }

    //着陆页数据 调用此方法
    public function google_landing($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_landing1($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:sessions'] ? round($result[0]['ga:sessions'], 2) : 0;
    }
    //着陆页会话数
    protected function getReport_landing1($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        //着陆页的数量
        $adCostMetric->setExpression("ga:landingPagePath");
        $adCostMetric->setAlias("ga:landingPagePath");
        $adCostMetric->setExpression("ga:sessions");
        $adCostMetric->setAlias("ga:sessions");

        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }


    //目标13会话数 调用此方法 产品详情页
    public function google_target13($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_target13($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:goal13Starts'] ? round($result[0]['ga:goal13Starts'], 2) : 0;
    }
    //目标13会话数 产品详情页数据
    protected function getReport_target13($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        //着陆页的数量
        // $adCostMetric->setExpression("ga:landingPagePath");
        // $adCostMetric->setAlias("ga:landingPagePath");
        // $adCostMetric->setExpression("ga:sessions");
        // $adCostMetric->setAlias("ga:sessions");
        //目标4的数量
        $adCostMetric->setExpression("ga:goal13Starts");
        $adCostMetric->setAlias("ga:goal13Starts");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }

    //目标1会话数 调用此方法 购物车页面
    public function google_target1($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_target1($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:goal1Starts'] ? round($result[0]['ga:goal1Starts'], 2) : 0;
    }
    //目标1会话数 购物车页面数据
    protected function getReport_target1($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        //着陆页的数量
        // $adCostMetric->setExpression("ga:landingPagePath");
        // $adCostMetric->setAlias("ga:landingPagePath");
        // $adCostMetric->setExpression("ga:sessions");
        // $adCostMetric->setAlias("ga:sessions");
        //目标4的数量
        $adCostMetric->setExpression("ga:goal1Starts");
        $adCostMetric->setAlias("ga:goal1Starts");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }

    //最终电子商务页面交易次数数据
    public function google_target_end($site, $start_time)
    {
        $end_time = $start_time;
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth/oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        // $analytics = $this->initializeAnalytics();
        // Call the Analytics Reporting API V4.
        $response = $this->getReport_target_end($site, $analytics, $start_time, $end_time);
        // Print the response.
        $result = $this->printResults($response);
        // return $result;
        return $result[0]['ga:transactions'] ? round($result[0]['ga:transactions'], 2) : 0;
    }
    //最终电子商务页面交易次数数据
    protected function getReport_target_end($site, $analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        if ($site == 1) {
            $VIEW_ID = config('ZEELOOL_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 2) {
            $VIEW_ID = config('VOOGUEME_GOOGLE_ANALYTICS_VIEW_ID');
        } elseif ($site == 3) {
            $VIEW_ID = config('NIHAO_GOOGLE_ANALYTICS_VIEW_ID');
        }

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        //着陆页的数量
        // $adCostMetric->setExpression("ga:landingPagePath");
        // $adCostMetric->setAlias("ga:landingPagePath");
        $adCostMetric->setExpression("ga:Ecommerce");
        $adCostMetric->setAlias("ga:Ecommerce");
        //目标4的数量
        $adCostMetric->setExpression("ga:transactions");
        $adCostMetric->setAlias("ga:transactions");
        $sessionDayDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sessionDayDimension->setName("ga:day");
        $sessionDayDimension->setName("ga:date");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }
    protected function printResults($reports)
    {
        $finalResult = array();
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $finalResult[$rowIndex][$dimensionHeaders[$i]] = $dimensions[$i];
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $finalResult[$rowIndex][$entry->getName()] = $values[$k];
                    }
                }
            }
            return $finalResult;
        }
    }
}
