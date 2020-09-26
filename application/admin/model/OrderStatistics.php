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
     * 统计30天订单量
     *
     * @Description
     * @author wpl
     * @since 2020/03/18 14:34:01 
     * @return void
     */
    public function get30daysNum()
    {
        $stime = date("Y-m-d", strtotime("-30 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        return $this->where($map)->sum('all_sales_num');
    }

    /**
     * 获取某个站点的购物车数量和购物车转化率
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/14 17:08:18 
     * @return void
     */
    public function getDataBySite($id=1,$map)
    {      
        if(1 == $id){
            return $this->where($map)->field('zeelool_sales_money as sales_money,zeelool_unit_price as unit_price,
            zeelool_sales_num as sales_num,zeelool_shoppingcart_update_total as shoppingcart_update_total,
            zeelool_shoppingcart_update_conversion as shoppingcart_update_conversion,create_date')->select();
        }elseif(2 == $id){
            return $this->where($map)->field('voogueme_sales_money as sales_money,voogueme_unit_price as unit_price,
            voogueme_sales_num as sales_num,voogueme_shoppingcart_update_total as shoppingcart_update_total,
            voogueme_shoppingcart_update_conversion as shoppingcart_update_conversion,create_date')->select();
        }elseif(3 == $id){
            return $this->where($map)->field('nihao_sales_money as sales_money,nihao_unit_price as unit_price,
            nihao_sales_num as sales_num,nihao_shoppingcart_update_total as shoppingcart_update_total,
            nihao_shoppingcart_update_conversion as shoppingcart_update_conversion ,create_date')->select();
        }elseif(4 == $id){
            return $this->where($map)->field('meeloog_sales_money as sales_money,meeloog_unit_price as unit_price,
            meeloog_sales_num as sales_num,meeloog_shoppingcart_update_total as shoppingcart_update_total,
            meeloog_shoppingcart_update_conversion as shoppingcart_update_conversion ,create_date')->select();
        }elseif(9 == $id){
            return $this->where($map)->field('zeelool_es_sales_money as sales_money,zeelool_es_unit_price as unit_price,
            zeelool_es_sales_num as sales_num,zeelool_es_shoppingcart_update_total as shoppingcart_update_total,
            zeelool_es_shoppingcart_update_conversion as shoppingcart_update_conversion ,create_date')->select();
        }elseif(10 == $id){
            return $this->where($map)->field('zeelool_de_sales_money as sales_money,zeelool_de_unit_price as unit_price,
            zeelool_de_sales_num as sales_num,zeelool_de_shoppingcart_update_total as shoppingcart_update_total,
            zeelool_de_shoppingcart_update_conversion as shoppingcart_update_conversion ,create_date')->select();
        }elseif(11 == $id){
            return $this->where($map)->field('zeelool_jp_sales_money as sales_money,zeelool_jp_unit_price as unit_price,
            zeelool_jp_sales_num as sales_num,zeelool_jp_shoppingcart_update_total as shoppingcart_update_total,
            zeelool_jp_shoppingcart_update_conversion as shoppingcart_update_conversion ,create_date')->select();
        }
    }
}
