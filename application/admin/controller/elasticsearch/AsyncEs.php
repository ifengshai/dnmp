<?php
/**
 * Class AsyncEs.php
 * @package application\admin\controller\elasticsearch
 * @author  crasphb
 * @date    2021/4/1 14:50
 */

namespace app\admin\controller\elasticsearch;


use app\admin\model\operatedatacenter\Datacenter;
use app\admin\model\operatedatacenter\DatacenterDay;
use app\admin\model\order\order\NewOrder;
use think\Db;

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

    /**
     * 同步每日center的数据到es
     * @author crasphb
     * @date   2021/4/13 14:58
     */
    public function asyncDatacenterDay()
    {
        DatacenterDay::chunk(1000,function($newOrder){
            array_map(function($value) {
                $value = array_map(function($v){
                    return $v === null ? 0 : $v;
                },$value);

                $mergeData = strtotime($value['day_date']);
                $this->esService->addToEs('mojing_datacenterday',$value,$mergeData);
                echo 1 . PHP_EOL;
            },collection($newOrder)->toArray());
        });

    }

    public function asyncCart()
    {
        $i = 0;
        Db::connect('database.db_nihao')->table('sales_flat_quote')->chunk(1000,function($carts) use (&$i){
            array_map(function($value) use ($i) {
                $value = array_map(function($v){
                    return $v === null ? 0 : $v;
                },$value);
                $mergeData = strtotime($value['created_at']);
                $insertData = [
                    'id' => $value['entity_id'],
                    'site' => 3,
                    'status' => $value['is_active'],
                    'update_time_day' => date('Ymd',strtotime($value['updated_at'])),
                    'update_time' => strtotime($value['updated_at']),
                    'create_time' => $mergeData,

                ];
                $i++;
                $this->esService->addToEs('mojing_cart',$insertData,$mergeData);
                echo $i . PHP_EOL;
            },collection($carts)->toArray());
        });
    }


}