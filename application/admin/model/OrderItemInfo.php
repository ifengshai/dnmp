<?php

namespace app\admin\model;

use think\Model;


class OrderItemInfo extends Model
{
    // 表名
    protected $name = 'order_item_info';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 获取最近30天数据
     */
    public function getAllData($platform)
    {
        $stime = date("Y-m-d", strtotime("-30 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        $map['platform']    = $platform;
        return $this->where($map)->select();
    }
}
