<?php

/**
 * 订单数据解析
 * 执行时间：
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use think\Db;

class OrderData extends Backend
{
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->order = new \app\admin\model\order\Order();
        $this->orderitem = new \app\admin\model\order\OrderItem();
        $this->orderoptions = new \app\admin\model\order\OrderOptions();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
    }



    /**
     * 处理订单数据
     *
     * @Description
     * @author wpl
     * @since 2020/10/21 14:55:50 
     * @return void
     */
    public function process_order_data()
    {
        //查询订单表最大id
        $id = $this->order->max('entity_id');

        $list = $this->zeelool->where(['entity_id' => ['>', $id]])
            ->field('entity_id,status,store_id,increment_id as order_number,base_grand_total,order_type,base_currency_code,customer_email,customer_firstname,customer_lastname,created_at,updated_at')
            ->limit(200)
            ->select();
        $list = collection($list)->toArray();

        $order_ids = array_column($list, 'entity_id');
        //查询每个单号购买的商品数量
        $goods_data = Db::connect('database.db_zeelool')->table('sales_flat_order_item')->where(['order_id' => ['in', $order_ids]])->column("sum(qty_ordered)", 'order_id');
        foreach ($list as &$v) {
            $v['status'] = $v['status'] ?: '';
            $v['created_at'] = strtotime($v['created_at']);
            $v['customer_firstname'] = $v['customer_firstname'] ?: '';
            $v['customer_lastname'] = $v['customer_lastname'] ?: '';
            $v['customer_email'] = $v['customer_email'] ?: '';
            $v['updated_at'] = strtotime($v['updated_at']);
            $v['all_goods_num'] = $goods_data[$v['entity_id']] ?? 0;
        }
        $this->order->insertAll($list);
    }

    /**
     * 处理订单处方数据
     *
     * @Description
     * @author wpl
     * @since 2020/10/21 14:55:50 
     * @return void
     */
    public function process_order_options_data()
    {
        //查询订单表最大id
        $id = $this->orderoptions->max('item_id');

        $list = Db::connect('database.db_zeelool')
            ->table('sales_flat_order_item')
            ->alias('a')
            ->field('a.*,b.increment_id as order_number')
            ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
            ->where(['item_id' => ['>', $id]])
            ->limit(200)
            ->select();
        $arr = []; //处方表数据
        $data = []; //子订单表数据
        foreach ($list as $k => $v) {
            $arr['item_id'] = $v['item_id'];
            $arr['order_id'] = $v['order_id'];
            $arr['sku'] = $v['sku'];
            $arr['qty'] = $v['qty_ordered'];
            $arr['base_row_total'] = $v['base_row_total'];
            $options = unserialize($v['product_options']);
            $arr['index_type'] = $options['info_buyRequest']['tmplens']['index_type'] ?: '';
            $arr['index_name'] = $options['info_buyRequest']['tmplens']['index_name'] ?: '';
            $prescription_params = explode("&", $options['info_buyRequest']['tmplens']['prescription']);
            $options_params = array();
            foreach ($prescription_params as $key => $value) {
                $arr_value = explode("=", $value);
                $options_params[$arr_value[0]] = $arr_value[1];
            }
            $arr['prescription_type'] = $options_params['prescription_type'] ?: '';
            $arr['coatiing_name'] = $options['info_buyRequest']['tmplens']['coatiing_name'] ?: '';
            $arr['coatiing_price'] = $options['info_buyRequest']['tmplens']['coatiing_price'];
            $arr['frame_price'] = $options['info_buyRequest']['tmplens']['frame_price'];
            $arr['index_price'] = $options['info_buyRequest']['tmplens']['index_price'];
            $arr['frame_regural_price'] = $options['info_buyRequest']['tmplens']['frame_regural_price'];
            $arr['is_special_price'] = $options['info_buyRequest']['tmplens']['is_special_price'] ?? 0;
            $arr['lens_price'] = $options['info_buyRequest']['tmplens']['lens'] ?? 0;
            $arr['total'] = $options['info_buyRequest']['tmplens']['total'] ?? 0;
            $arr['od_sph'] = $options_params['od_sph'] ?: '';;
            $arr['os_sph'] = $options_params['os_sph'] ?: '';;
            $arr['od_cyl'] = $options_params['od_cyl'] ?: '';;
            $arr['os_cyl'] = $options_params['os_cyl'] ?: '';;
            $arr['od_axis'] = $options_params['od_axis'];
            $arr['pd_l'] = $options_params['pd_l'];
            $arr['pd_r'] = $options_params['pd_r'];
            $arr['pd'] = $options_params['pd'];
            $arr['os_add'] = $options_params['os_add'];
            $arr['od_add'] = $options_params['od_add'];
            $arr['od_pv'] = $options_params['od_pv'];
            $arr['os_pv'] = $options_params['os_pv'];
            $arr['od_pv_r'] = $options_params['od_pv_r'];
            $arr['os_pv_r'] = $options_params['os_pv_r'];
            $arr['od_bd'] = $options_params['od_bd'];
            $arr['os_bd'] = $options_params['os_bd'];
            $arr['od_bd_r'] = $options_params['od_bd_r'];
            $arr['os_bd_r'] = $options_params['os_bd_r'];
            $arr['is_prescription'] = 0;
            $arr['is_custom_lens'] = 0;

            $options_id = $this->orderoptions->insertGetId($arr);
            for ($i = 0; $i < $v['qty_ordered']; $i++) {
                $data[$i]['item_id'] = $v['item_id'];
                $data[$i]['order_id'] = $v['order_id'];
                $data[$i]['option_id'] = $options_id;
                $str = '';
                if ($i < 10) {
                    $str = '0' . $i+1;
                } else {
                    $str = $i+1;
                }
                $data[$i]['item_order_number'] = $v['order_number'] . '-' . $str;
                $data[$i]['sku'] = $v['sku'];
                $data[$i]['created_at'] = strtotime($v['created_at']);
                $data[$i]['updated_at'] = strtotime($v['updated_at']);
            }
            $this->orderitem->insertAll($data);


            echo $k . "\n";
        }
        echo 'ok';
        die;
    }
}
