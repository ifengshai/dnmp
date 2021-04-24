<?php
/**
 * Class AsyncCart.php
 * @package app\admin\controller\elasticsearch\async
 * @author  crasphb
 * @date    2021/4/23 15:16
 */

namespace app\admin\controller\elasticsearch\async;


use app\admin\controller\elasticsearch\BaseElasticsearch;

class AsyncCart extends BaseElasticsearch
{
    /**
     * 创建购物车
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 13:02
     */
    public function runInsert($data,$id)
    {
        $value = array_map(function($v){
            return $v === null ? 0 : $v;
        },$data);
        $mergeData = $value['created_at'];
        $insertData = [
            'id' => $id,
            'entity_id' => $value['entity_id'],
            'site' => $value['site'],
            'status' => $value['is_active'],
            'update_time_day' => date('Ymd',$value['updated_at']),
            'update_time' => $value['updated_at'],
            'create_time' => $mergeData,

        ];
        $this->esService->addToEs('mojing_cart',$this->formatDate($insertData,$mergeData));
    }

    /**
     * 更新购物车
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 13:02
     */
    public function runUpdate($data,$id)
    {
        $value = array_map(function($v){
            return $v === null ? 0 : $v;
        },$data);
        $mergeData = $value['created_at'];
        $insertData = [
            'id' => $id,
            'entity_id' => $value['entity_id'],
            'site' => $value['site'],
            'status' => $value['is_active'],
            'update_time_day' => date('Ymd',$value['updated_at']),
            'update_time' => $value['updated_at'],
            'create_time' => $mergeData,

        ];
        $updateData = $this->formatDate($insertData,$mergeData);
        $this->esService->updateEs('mojing_cart', $updateData);
    }
}