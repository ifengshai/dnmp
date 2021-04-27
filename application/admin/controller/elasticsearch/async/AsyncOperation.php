<?php
/**
 * Class AsyncOperation.php
 * @package app\admin\controller\elasticsearch\async
 * @author  crasphb
 * @date    2021/4/27 10:51
 */

namespace app\admin\controller\elasticsearch\async;


use app\admin\controller\elasticsearch\BaseElasticsearch;
use app\admin\model\OperationAnalysis;

class AsyncOperation extends BaseElasticsearch
{
    /**
     * operation 数据更新
     * @param $site
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/4/27 11:00
     */
    public function runUpdate($site)
    {
        $operation = OperationAnalysis::where('order_plateform',$site)->find()->toArray();
        $insertData = array_map(function($value) {

            $value === null ? 0 : $value;
        },$operation);
        $mergeData = strtotime($insertData['day_date']);
        $this->esService->addToEs('mojing_datacenterday',$this->formatDate($insertData,$mergeData));
    }
}