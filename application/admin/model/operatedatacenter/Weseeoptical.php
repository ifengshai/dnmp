<?php
/**
 * Class Weseeoptical.php
 * @package app\admin\model\operatedatacenter
 * @author  crasphb
 * @date    2021/5/14 13:13
 */

namespace app\admin\model\operatedatacenter;


use think\Db;
use think\Model;

class Weseeoptical extends Model
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
        $this->model = new \app\admin\model\order\order\NewWeseeoptical();
    }
    /**
     * 复购用户数
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/14
     * Time: 11:40:04
     */
    public function getAgainUser($time_str = '', $time_str2 = '')
    {
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' 00:00:00 - ' .$end;
        }
        $createat = explode(' ', $time_str);
        $again_num = $this->get_again_user($createat);
        $all_order_user = $this->get_all_order_user($createat);
        $all_order_user_rate = $all_order_user ? round(($again_num) / $all_order_user * 100, 2) : 0;
        $arrs['again_user_num'] = $again_num;
        $arrs['again_user_num_rate'] = $all_order_user_rate;
        if($time_str2){
            $createat2 = explode(' ', $time_str2);
            $contrast_again_num = $this->get_again_user($createat2);
            $contrast_all_order_user = $this->get_all_order_user($createat2);
            $arrs['contrast_again_user_num'] = $contrast_again_num ? round(($arrs['again_user_num'] - $contrast_again_num) / $contrast_again_num * 100, 2) : 0;
            $contrast_all_order_user_rate = $contrast_all_order_user ? round(($contrast_again_num) / $contrast_all_order_user * 100, 2) : 0;

            $arrs['all_contrast_again_user_num'] = $contrast_all_order_user_rate ? round(($all_order_user_rate - $contrast_all_order_user_rate) / $contrast_all_order_user_rate * 100, 2) : 0;
        }
        return $arrs;
    }
    /**
     * 获取某一个时间购买的用户总数
     * @param $createat
     *
     * @return int|string
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/5/14 12:53
     */
    public function get_all_order_user($createat)
    {
        $map_where['payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        $order_where['payment_time'] = ['lt',$createat[0]];

        $map['status'] = ['in', [2,3,4,9,10]];
        $map['order_type'] = 1;
        $map1['user_id'] = ['>',0];

        $order_model = new \app\admin\model\order\order\NewWeseeoptical();
        return $order_model
            ->where($map_where)
            ->where($map)
            ->where($map1)
            ->group('user_id')
            ->count('user_id');
    }
    //获取某一段时间内的复购用户数 new
    public function get_again_user($createat){
        $map_where['payment_time'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        $order_where['payment_time'] = ['lt',$createat[0]];

        $map['status'] = ['in', [2,3,4,9,10]];
        $map['order_type'] = 1;
        $map1['user_id'] = ['>',0];

        $order_model = new \app\admin\model\order\order\NewWeseeoptical();
        //复购用户数
        //查询时间段内的订单 根据customer_id先计算出此事件段内的复购用户数
        $again_buy_num1 = $order_model
            ->where($map_where)
            ->where($map)
            ->where($map1)
            ->group('user_id')
            ->having('count(user_id)>1')
            ->count('user_id');

        $again_buy_data2 = $order_model
            ->where($map_where)
            ->where($map)
            ->where($map1)
            ->group('user_id')
            ->having('count(user_id)<=1')
            ->column('user_id');
        $again_buy_num2 = 0;
        foreach ($again_buy_data2 as $v){
            //查询时间段内是否进行购买行为
            $order_where_arr['user_id'] = $v;
            $is_buy = $order_model->where($order_where)->where($order_where_arr)->where($map)->value('id');
            if($is_buy){
                $again_buy_num2++;
            }
        }

        $again_buy_num = $again_buy_num1+$again_buy_num2;
        return $again_buy_num;
    }
    /**
     * 用户统计
     * @param $time_str
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/5/14 17:33
     */
    public function getUserOrderData($time_str)
    {
        if(!$time_str){
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start . ' - '. $end;
        }
        $createat = explode(' ', $time_str);
        $customerWhere['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
        $orderWhere['status'] = ['in', [2,3,4,9,10]];
        $orderWhere['order_type'] = 1;
        $orderWhere['order_type'] = 1;
        //用户统计
        $customerCount = Db::connect('database.db_weseeoptical')
            ->table('users')
            ->where('name','<>','visitor')
            ->where($customerWhere)
            ->count();
        $orderCount = Db::connect('database.db_weseeoptical')
            ->table('orders')
            ->where($customerWhere)
            ->where($orderWhere)
            ->sum('base_actual_amount_paid');
        $result = [];
        $result[] = [
            'count' => $customerCount,
            'name' => '普通用户',
            'num'  => $orderCount,
            'rate' => '100%',
            'customerRate' => '100%',
        ];
        return $result;
    }
}