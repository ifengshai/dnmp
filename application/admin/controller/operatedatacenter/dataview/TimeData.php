<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class TimeData extends Backend
{
    /**
     * 分时数据
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 销售量
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:09:27 
     * @return void
     */
    public function sales_num_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['销售量'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '销售量',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 销售额
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:49 
     * @return void
     */
    public function sales_money_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['销售额'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '销售额',
                    'smooth' => true //平滑曲线
                ]

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 订单数
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:28 
     * @return void
     */
    public function order_num_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['订单数量'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '订单数量',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 客单价
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:04 
     * @return void
     */
    public function unit_price_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['客单价'];
            $json['columnData'] = [

                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '客单价',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
