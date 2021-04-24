<?php
/**
 * Class AsyncCustomer.php
 * @package app\admin\controller\elasticsearch\async
 * @author  crasphb
 * @date    2021/4/23 15:16
 */

namespace app\admin\controller\elasticsearch\async;


use app\admin\controller\elasticsearch\BaseElasticsearch;

class AsyncCustomer extends BaseElasticsearch
{
    /**
     * 新增用户信息
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 9:48
     */
    public function runInsert($data,$id)
    {
        $data['id'] = $id;
        $value = array_map(function($v){
            return $v === null ? 0 : $v;
        },$data);
        $mergeData = $value['created_at'];
        $insertData = [
            'id' => $id,
            'site' => $value['site'],
            'email' => $value['email'],
            'update_time_day' => date('Ymd',$value['updated_at']),
            'update_time' => $value['updated_at'],
            'create_time' => $mergeData,
            'is_vip' => $value['is_vip'] ?? 0,
            'group_id' => $value['group_id'],
            'store_id' => $value['store_id'],
            'resouce' => $value['resouce'] ?? 0,

        ];
        $insertData = $this->formatDate($insertData,$mergeData);
        $this->esService->addToEs('mojing_customer',$insertData);
    }

    /**
     * 更新用户信息
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 9:48
     */
    public function runUpdate($data,$id)
    {
        $data['id'] = $id;
        $value = array_map(function($v){
            return $v === null ? 0 : $v;
        },$data);
        $value['update_time_day'] = date('Ymd',$value['updated_at']);
        $mergeData = $value['updated_at'];
        $insertData = $this->formatDate($value,$mergeData);
        $this->esService->updateEs('mojing_customer',$insertData);
    }
}