<?php

namespace app\admin\model;

use think\Model;
use think\Db;


class OrderLog extends Model
{
    // 表名
    protected $name = 'order_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 添加订单操作日志
     */
    public function setOrderLog($data)
    {
        if ($data) {
            $data['create_person'] = session('admin.nickname');
            $data['createtime'] = date('Y-m-d H:i:s');
            return $this->allowField(true)->save($data);
        }
        return false;
    }

    /**
     * 获取当月质检总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 10:09:08 
     * @return void
     */
    public function getOrderCheckNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-01 H:i:s', time())]];
        $where['type'] = 5; //质检通过
        $ids = $this->where($where)->column('order_ids');
        $ids = implode(',', $ids);
        $num = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where('order_id', 'in', $ids)->sum('qty_ordered');
        return $num;
    }

    /**
     * 获取当日配镜架总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 10:09:08 
     * @return void
     */
    public function getOrderFrameNum()
    {
        $where['createtime'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['type'] = 2; //配镜架
        $ids = $this->where($where)->column('order_ids');
        $ids = implode(',', $ids);
        $num = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where('order_id', 'in', $ids)->sum('qty_ordered');
        return $num;
    }
}
