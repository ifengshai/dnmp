<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class GoodsDataView extends Backend
{
    public function _initialize()
    {
        parent::_initialize();

        $this->item_platform = new ItemPlatformSku();
    }
    /**
     * 商品数据-数据概览
     *
     * @return \think\Response
     */
    public function index()
    {
        //序列化的数据
        // $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        // $max_item_id_list = Db::connect('database.db_zeelool')->query($max_item_id_querySql);
        // if ($max_item_id_list) {
        //     $max_item_id = $max_item_id_list[0]['max_item_id'];
        // }
        //
        // $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        // $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1";
        //
        // $order_item_list = Db::connect('database.db_zeelool')->query($order_item_prescription_querySql);
        //
        // foreach ($order_item_list as $order_item_key => $order_item_value) {
        //     $product_options = unserialize($order_item_value['product_options']);
        //     dump($product_options);die;
        // }
        // $product_options = unserialize('');
        $label = input('label', 1);
        //判断站点
        switch ($label){
            case 1:
                $plat = 1;
                break;
            case 2:
                $plat = 2;
                break;
            case 3:
                $plat = 3;
                break;
        }
        $a_plus_data_item = $this->item_platform->where(['platform_type'=>$plat,'grade'=>'A+'])->field('sku,platform_type,grade')->select();
        $a_plus_data = [];
        $a_data = [];
        $b_data = [];
        $c_plus_data = [];
        $d_data = [];
        $e_data = [];
        $f_data = [];
        $this->assign('label', $label);
        $this->view->assign(compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'd_data', 'e_data', 'f_data'));
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
