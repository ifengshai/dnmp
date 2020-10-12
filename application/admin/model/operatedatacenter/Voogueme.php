<?php
namespace app\admin\model\operatedatacenter;

use think\Model;


class Voogueme extends Model
{

    // 表名
    protected $name = 'datacenter_day';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];

    /**
     * 统计订单数
     *
     * @Description
     * @author wpl
     * @since 2020/02/26 17:36:58
     * @return void
     */
    public function getOrderNum($time_str = '')
    {
        $map['site'] = 2;
        if($time_str){
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_start = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[0])));
            $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($createat[3])));
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_start = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[0])));
            $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($createat[3])));
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }else{
            $start = $end = date('Y-m-d');
            $same_start = $same_end = date( 'Y-m-d', strtotime("-1 years",strtotime($start)));
            $huan_start = $huan_end = date( 'Y-m-d', strtotime("-1 months",strtotime($start)));
            $where['day_date'] = ['between', [$start,$end]];
            $same_where['day_date'] = ['between', [$same_start,$same_end]];
            $huan_where['day_date'] = ['between', [$huan_start,$huan_end]];
        }

        $arr['order_num'] = $this->where($map)->where($where)->sum('order_num');
        $arr['same_order_num'] = $this->where($map)->where($same_where)->sum('order_num');
        $arr['huan_order_num'] = $this->where($map)->where($huan_where)->sum('order_num');
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
    public function getLensStockPrice()
    {
        return $this->sum('stock_num*price');
    }








}
