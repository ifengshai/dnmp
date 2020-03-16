<?php

namespace app\admin\model;

use think\Model;


class OrderStatistics extends Model
{
    // 表名
    protected $name = 'order_statistics';

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
    public function getAllData()
    {
        $stime = date("Y-m-d", strtotime("-30 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        return $this->where($map)->select();
    }
    /**
     * 获取某个站点的购物车数量和购物车转化率
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/14 17:08:18 
     * @return void
     */
    public function getDataBySite($id=1)
    {
        $stime = date("Y-m-d", strtotime("-30 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];        
        if(1 == $id){
            return $this->where($map)->field('zeelool_shoppingcart_update_total,zeelool_shoppingcart_update_conversion,create_date')->select();
        }
    }
}
