<?php
/**
 * Class AsyncEs.php
 * @package application\admin\controller\elasticsearch
 * @author  crasphb
 * @date    2021/4/1 14:50
 */

namespace app\admin\controller\elasticsearch;


use app\admin\model\order\order\NewOrder;

class AsyncEs extends BaseElasticsearch
{

    /**
     * 同步订单数据
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/4/1 15:21
     */
    public function asyncOrder()
    {
        ini_set('memory_limit', '2048M');
        NewOrder::chunk(1000,function($newOrder){
            array_map(function($value) {
                $value = array_map(function($v){
                    return $v === null ? 0 : $v;
                },$value);
                $date = $value['payment_time'] ?: $value['created_at'];
                $this->addToEs('mojing_order',array_merge($value,$this->formatDate($date)));
            },collection($newOrder)->toArray());
        });

    }

    /**
     * 格式化时间字段，方便后续查询聚合
     *
     * @param $date
     *
     * @return array
     * @author crasphb
     * @date   2021/4/1 15:21
     */
    public function formatDate($date)
    {
        return [
            'year' => date('Y',$date),
            'month' => date('m',$date),
            'month_date' => date('Ym',$date),
            'day' => date('d',$date),
            'day_date' => date('Ymd',$date),
            'hour' => date('H',$date),
            'hour_date' => date('YmdH',$date),
        ];
    }

    /**
     * 添加数据
     *
     * @param $indexName
     * @param $view
     *
     * @return mixed
     * @author crasphb
     * @date   2021/4/1 15:20
     */
    public function addToEs($indexName,$view)
    {
        $params = [
            'index' => $indexName,
            'type' => '_doc',
            'id' => $view['id'],
            'body' => $view
        ];
        return $this->esClient->index($params);
    }
}