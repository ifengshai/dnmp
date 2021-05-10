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
     *
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 9:48
     */
    public function runInsert($data, $id)
    {
        try {
            $data['id'] = $id;
            $insertData = $this->getData($data);
            $this->esService->addToEs('mojing_customer', $insertData);
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
     * @date   2021/4/28 9:25
     */
    protected function getData($data)
    {
        $value = array_map(function ($v) {
            return $v === null ? 0 : $v;
        }, $data);
        $mergeData = $value['created_at'];
        $updateData = [
            'id'              => $data['id'],
            'entity_id'       => $value['entity_id'],
            'site'            => $value['site'],
            'email'           => $value['email'],
            'update_time_day' => date('Ymd', $value['updated_at'] + 8*3600),
            'update_time'     => $value['updated_at'],
            'create_time'     => $mergeData,
            'is_vip'          => $value['is_vip'] ?? 0,
            'group_id'        => $value['group_id'],
            'store_id'        => $value['store_id'],
            'resouce'         => $value['resouce'] ?? 0,

        ];

        return $this->formatDate($updateData, $mergeData);
    }

    /**
     * 更新用户信息
     *
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 9:48
     */
    public function runUpdate($data)
    {
        try {
            $updateData = $this->getData($data);
            $this->esService->updateEs('mojing_customer', $updateData);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}