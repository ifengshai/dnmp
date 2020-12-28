<?php

namespace app\admin\model\order\order;

use think\Model;

class NewOrderProcess extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order';

    // 表名
    protected $name = 'order_process';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    /**
     * 统计未发货订单
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function undeliveredOrder($map = [])
    {
        $map['a.check_status'] = 0;
        //过滤补差价单
        $map['b.order_type'] = ['<>', 5];
        $map['b.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        return $this->alias('a')->where($map)->join(['fa_order' => 'b'], 'a.order_id=b.id')->group('b.site')->column('count(1)', 'b.site');
    }

    /**
     * 统计未发货订单SKU副数
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function undeliveredOrderNum($map = [])
    {
        $map['a.check_status'] = 0;
        //过滤补差价单
        $map['b.order_type'] = ['<>', 5];
        $map['b.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        return $this->alias('a')->where($map)->join(['fa_order' => 'b'], 'a.order_id=b.id')->column('sum(total_qty_ordered)', 'b.site');
    }

    /**
     * 统计处方镜副数
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 14:50:55 
     * @return void
     */
    public function getOrderPrescriptionNum($map)
    {

        $map['a.check_status'] = 0;
        //过滤补差价单
        $map['b.order_type'] = ['<>', 5];
        $map['b.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $list = $this->alias('a')->where($map)->field('c.order_prescription_type,b.site,count(1) as num')
        ->join(['fa_order' => 'b'], 'a.order_id=b.id')
        ->join(['fa_order_item_process' => 'c'],'c.order_id=b.id')
        ->group('c.order_prescription_type,c.site')
        ->select();
        $params = [];
        foreach($list as $k => $v) {
            $params[$v['site']][$v['order_prescription_type']] = $v['num'];
        }

        return $params;
    }
}
