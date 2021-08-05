<?php

namespace app\admin\model\operatedatacenter;

use think\Db;
use think\Model;


class VooguemeAcc extends Model
{
    const SITE = 14;
    // 表名
    protected $name = 'datacenter_day';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [

    ];

    public function __construct()
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
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
    public function getOrderNum($time_str = '', $time_str2 = '')
    {
        $map['site'] = self::SITE;
            if(!$time_str){
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start .' 00:00:00 - ' .$end.' 00:00:00';
            }
            //时间段总和
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $arr['order_num'] = $this->where($map)->where($where)->sum('order_num');
            if($time_str2){
                $createat2 = explode(' ', $time_str2);
                $huan_where['day_date'] = ['between', [$createat2[0], $createat2[3]]];
                $contrast_order_num = $this->where($map)->where($huan_where)->sum('order_num');
                $arr['contrast_order_num'] = $contrast_order_num ? round(($arr['order_num'] - $contrast_order_num) / $contrast_order_num * 100, 2) : 0;
            }
        return $arr;
    }
}
