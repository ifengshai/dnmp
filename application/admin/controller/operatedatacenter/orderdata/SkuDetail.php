<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

class SkuDetail extends Backend
{
    /**
     * sku明细分析
     *
     * @return \think\Response
     */
    public function index()
    {
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
    public function user_data_pie()
    {
        if ($this->request->isAjax()) {
            $json['column'] = ['首购人数', '复购人数'];
            $json['columnData'] = [
                [
                    'name' => '首购人数',
                    'value' => '430',
                ],
                [
                    'name' => '复购人数',
                    'value' => '1000',
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
    public function lens_data_pie()
    {
        if ($this->request->isAjax()) {
            $json['column'] = ['reading glasses', 'progressive', 'no prescription', 'sunglasses', 'sunglasses non-prescription', 'Frame Only', 'single vision'];
            $json['columnData'] = [
                [
                    'name' => 'reading glasses',
                    'value' => '430',
                ],
                [
                    'name' => 'progressive',
                    'value' => '1000',
                ],
                [
                    'name' => 'no prescription',
                    'value' => '3000',
                ],
                [
                    'name' => 'sunglasses',
                    'value' => '500',
                ],
                [
                    'name' => 'sunglasses non-prescription',
                    'value' => '200',
                ],
                [
                    'name' => 'Frame Only',
                    'value' => '3000',
                ],
                [
                    'name' => 'single vision',
                    'value' => '1000',
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
