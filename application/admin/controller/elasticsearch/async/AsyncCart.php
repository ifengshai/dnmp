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
     *
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 13:02
     */
    public function runInsert($data, $id)
    {
        try {
            $data['id'] = $id;
            $insertData = $this->getData($data);
            $this->esService->addToEs('mojing_cart', $insertData);
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
        $insertData = [
            'id'              => $data['id'],
            'entity_id'       => $value['entity_id'],
            'site'            => $value['site'],
            'status'          => $value['is_active'],
            'update_time_day' => date('Ymd', $value['updated_at'] + 8*3600),
            'update_time'     => $value['updated_at'],
            'create_time'     => $mergeData,

        ];

        return $this->formatDate($insertData, $mergeData);
    }

    /**
     * 更新购物车
     *
     * @param $data
     * @param $id
     *
     * @author crasphb
     * @date   2021/4/24 13:02
     */
    public function runUpdate($data)
    {
        try {
            $updateData = $this->getData($data);

            $this->esService->updateEs('mojing_cart', $updateData);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}