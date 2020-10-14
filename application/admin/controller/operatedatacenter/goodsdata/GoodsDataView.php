<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class GoodsDataView extends Backend
{
    /**
     * 商品数据-数据概览
     *
     * @return \think\Response
     */
    public function index()
    {
        $label = input('label', 1);
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /**
     * 镜框销量/客单价趋势
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:02 
     * @return void
     */
    public function goods_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '镜框销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => [10, 26, 45, 40, 40, 65, 73, 80],
                    'name' => '副单价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 各品类商品销量趋势
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function goods_type_data_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['平光镜', '太阳镜'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '平光镜',
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '太阳镜',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
