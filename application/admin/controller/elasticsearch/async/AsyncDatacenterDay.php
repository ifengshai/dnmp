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
                $mergeData = strtotime($data['day_date']) + 8*3600;
                $insertData = $this->formatDate($data, $mergeData);
                $this->esService->addToEs('mojing_datacenterday', $insertData);
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 更新datacenter
     * @param $data
     *
     * @author crasphb
     * @date   2021/5/27 9:06
     */
    public function runUpdate($data)
    {
        try {
            $datacenterDay = DatacenterDay::where(['day_date' => $data['data'], 'site' => $data['site']])->find();
            if($datacenterDay){
                $data = array_map(function ($value) {
                    return $value === null ? 0 : $value;
                }, $datacenterDay->toArray());
                $mergeData = strtotime($data['day_date']) + 8*3600;
                $updateData = $this->formatDate($data, $mergeData);
                $this->esService->updateEs('mojing_datacenterday', $updateData);
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 每天凌晨4点同步数据
     * @author huangbinbin
     * @date   2021/6/10 14:19
     */
    public function asyncDatacenterDay()
    {
        DatacenterDay::chunk(10000,function($newOrder){
            array_map(function($value) {
                $value = array_map(function($v){
                    return $v === null ? 0 : $v;
                },$value);

                $mergeData = strtotime($value['day_date'])  + 8*3600;
                $updateData = $this->formatDate($value,$mergeData);
                $this->esService->updateEs('mojing_datacenterday',$updateData);
            },collection($newOrder)->toArray());
        });


    }
}