<?php
/**
 * Class DataMarket.php
 * @package app\admin\controller\elasticsearch\operate
 * @author  crasphb
 * @date    2021/4/16 11:33
 */

namespace app\admin\controller\elasticsearch\operate;


use app\admin\controller\elasticsearch\BaseElasticsearch;

class DataMarket extends BaseElasticsearch
{
    public function ajaxGetCharts()
    {
        //if ($this->request->isAjax()) {

            $siteAll = false;
            $start = date('Ymd', strtotime('-6 days'));
            $end = date('Ymd');
            $result = (new DashBoard())->buildDashBoardSearch([1,2,3,4,5,6,7,8,9,10,11,12], $start, $end, $siteAll);
            echo json_encode($result);

        //}

    }
}