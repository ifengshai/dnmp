<?php

namespace app\admin\model;

use think\Model;

class WarehouseData extends Model
{
    // 表名
    protected $name = 'warehouse_data';

    /**
     * 获取采购数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/23 11:12:07 
     * @return void
     */
    public function getPurchaseData()
    {
        $where['create_time'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $list = $this->where($where)
        ->field('all_purchase_num,create_date,all_purchase_price,online_purchase_num,online_purchase_price,purchase_num,purchase_price')
        ->order('create_date asc')
        ->select();
        $list = collection($list)->toArray();
        return $list ?? [];
    }
}
