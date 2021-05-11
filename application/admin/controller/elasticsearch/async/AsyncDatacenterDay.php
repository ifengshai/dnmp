<?php
/**
 * Class AsyncDatacenterDay.php
 * @package app\admin\controller\elasticsearch\async
 * @author  crasphb
 * @date    2021/4/27 13:33
 */

namespace app\admin\controller\elasticsearch\async;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\operatedatacenter\DatacenterDay;

class AsyncDatacenterDay extends BaseElasticsearch
{
    /**
     * datacenter day同步
     *
     * @param $id
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/4/27 13:37
     */
    public function runInsert($id)
    {
        try {
            $datacenterDay = DatacenterDay::where('id', $id)->find();
            if($datacenterDay){
                $data = array_map(function ($value) {
                    return $value === null ? 0 : $value;
                }, $datacenterDay->toArray());
                $mergeData = strtotime($data['day_date']);
                $insertData = $this->formatDate($data, $mergeData);
                $this->esService->addToEs('mojing_datacenterday', $insertData);
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}