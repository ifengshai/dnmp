<?php
/**
 * Class AsyncOrder.php
 * @package app\admin\controller\elasticsearch\async
 * @author  crasphb
 * @date    2021/4/23 14:47
 */

namespace app\admin\controller\elasticsearch\async;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\order\order\Order;

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
        $data['id'] = $id;
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
        $mergeData = $value['payment_time'] ?: $value['created_at'];
        $insertData = $this->formatDate($value, $mergeData);
        $this->esService->addToEs('mojing_order', $insertData);
    }

    /**
     * 更新订单
     *
     * @param $data
     * @param $entityId
     * @param $site
     *
     * @author crasphb
     * @date   2021/4/23 15:12
     */
    public function runUpdate($data, $entityId, $site)
    {
        $id = Order::where(['entity_id' => $entityId, 'site' => $site])->value('id');
        $data['id'] = $id;
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
        $mergeData = $value['payment_time'] ?: $value['created_at'];
        $insertData = $this->formatDate($value, $mergeData);
        $this->esService->updateEs('mojing_order', $insertData);
    }
}