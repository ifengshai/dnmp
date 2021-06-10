<?php
/**
 * Class AsyncOrder.php
 * @package app\admin\controller\elasticsearch\async
 * @author  crasphb
 * @date    2021/4/23 14:47
 */

namespace app\admin\controller\elasticsearch\async;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\order\Order;

class AsyncOrder extends BaseElasticsearch
{
    /**
     * 创建订单
     *
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/23 15:12
     */
    public function runInsert($data, $id)
    {
        try {
            $data['id'] = $id;

            $insertData = $this->getData($data);;

            $res = $this->esService->addToEs('mojing_order', $insertData);
            if($data['site'] == 10) {
                dump($res);
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 格式化参数
     *
     * @param $data
     *
     * @return array
     * @author crasphb
     * @date   2021/4/28 9:23
     */
    protected function getData($data)
    {
        $value = array_map(function ($v) {
            return $v === null ? 0 : $v;
        }, $data);
        //nihao站的终端转换
        if ($value['site'] == 3 && $value['store_id'] == 2) {
            $value['store_id'] = 4;
        }
        $value['shipping_method_type'] = 0;
        //运输类型添加
        if (in_array($value['shipping_method'], ['freeshipping_freeshipping', 'flatrate_flatrate'])) {
            if ($value['base_shipping_amount'] == 0) {
                $value['shipping_method_type'] = 0;
            }
            if ($value['base_shipping_amount'] > 0) {
                $value['shipping_method_type'] = 1;
            }
        }
        if (in_array($value['shipping_method'], ['tablerate_bestway'])) {
            if ($value['base_shipping_amount'] == 0) {
                $value['shipping_method_type'] = 2;
            }
            if ($value['base_shipping_amount'] > 0) {
                $value['shipping_method_type'] = 3;
            }
        }
        $value['payment_time'] = $value['payment_time'] < 0 ? $value['created_at'] : $value['payment_time'];
        $mergeData = $value['payment_time'] >= $value['created_at'] ? $value['payment_time'] : $value['created_at'];
        //删除无用字段
        foreach($value as $key => $val) {
            if(!in_array($key,['id','site','increment_id','quote_id','status','store_id','base_grand_total','total_qty_ordered','order_type','order_prescription_type','shipping_method','shipping_title','shipping_method_type','country_id','region','region_id','payment_method','mw_rewardpoint_discount','mw_rewardpoint','base_shipping_amount','payment_time'])){
                unset($value[$key]);
            }
        }
        return $this->formatDate($value, $mergeData);
    }

    /**
     * 更新订单
     *
     * @param  $entityId
     * @param  $site
     *
     * @author crasphb
     * @date   2021/4/23 15:12
     */
    public function runUpdate($entityId, $site)
    {
        try {
            $order = Order::where(['entity_id' => $entityId, 'site' => $site])->find();
            if($order) {
                $updateData = $this->getData($order->toArray());
                $this->esService->updateEs('mojing_order', $updateData);
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}