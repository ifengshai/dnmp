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
                $mergeData = $value['payment_time'] ?: $value['created_at'];
                $this->esService->addToEs('mojing_order',$value,$mergeData);
            },collection($newOrder)->toArray());
        });

    }


}