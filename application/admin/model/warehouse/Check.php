<?php

namespace app\admin\model\warehouse;

use think\Model;


class Check extends Model
{

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'check_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联模型
    public function purchaseOrder()
    {
        return $this->belongsTo('app\admin\model\purchase\PurchaseOrder', 'purchase_id')
            ->setEagerlyType(0)->joinType('left');
    }

    //关联模型
    public function supplier()
    {
        return $this->belongsTo('app\admin\model\purchase\Supplier', 'supplier_id')->setEagerlyType(0)->joinType('left');
    }


    //关联模型
    public function orderReturn()
    {
        return $this->belongsTo('app\admin\model\saleaftermanage\OrderReturn', 'order_return_id')->setEagerlyType(0)->joinType('left');
    }

    public function checkItem()
    {
        return $this->hasMany('CheckItem', 'check_id');
    }

    /**
     * 获取当月到货数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/06 16:36:49 
     * @return void
     */
    public function getArrivalsNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['status'] = 2;
        return $this->alias('a')->where($where)->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')->sum('arrivals_num');
    }

    /**
     * 获取当月质检合格数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/06 16:36:49 
     * @return void
     */
    public function getQuantityNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['status'] = 2;
        return $this->alias('a')->where($where)->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')->sum('quantity_num');
    }

    /**
     * 获取当日到货数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/06 16:36:49 
     * @return void
     */
    public function getArrivalsNumToday()
    {
        $where['createtime'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['status'] = 2;
        return $this->alias('a')->where($where)->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')->sum('arrivals_num');
    }

    /**
     * 获取当日质检数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/06 16:36:49 
     * @return void
     */
    public function getCheckNumToday()
    {
        $where['createtime'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['status'] = 2;
        return $this->alias('a')->where($where)->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')->sum('check_num');
    }
}
