<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class SingleItem extends Backend
{
    /**
     * 商品数据-单品查询
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->view->fetch();
    }

    /**
     * 会话/销售额
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function sku_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '商品销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => [10, 26, 45, 40, 40, 65, 73, 80],
                    'name' => '现价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 最近30天销量
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function sku_sales_data_bar()
    {
        if ($this->request->isAjax()) {
            

            $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['columnData'] = [
                'type' => 'bar',
                'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                'name' => '最近30天销量'
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

}
