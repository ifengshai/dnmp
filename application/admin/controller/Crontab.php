<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Db;
use think\Hook;
use think\Validate;

/**
 * 定时任务
 * @internal
 */
class Crontab extends Backend
{

    protected $noNeedLogin = ['order_custom_order_prescription', 'index'];

    /**
     * 获取采购到货状态、到货时间
     */
    public function setPurchaseStatus()
    { }

    /**
     * 定时处理 订单列表分类
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function  order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type is null order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_zeelool')->query($order_entity_id_querySql);
        if (empty($order_entity_id_list)) {
            echo '处理完毕！';
            exit;
        }

        /**
         * 1：仅镜架
         * 2：仅现货处方镜
         * 3：仅定制处方镜
         * 4：镜架+现货
         * 5：镜架+定制
         * 6：现片+定制片
         */
        $type_1_entity_id = [];
        $type_2_entity_id = [];
        $type_3_entity_id = [];
        $type_4_entity_id = [];
        $type_5_entity_id = [];
        $type_6_entity_id = [];
        foreach ($order_entity_id_list as $key => $value) {

            $items = Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();

            $label = [];
            foreach ($items as $k => $v) {
                //如果镜片参数为真 或 不等于 Plastic Lenses 并且不等于 FRAME ONLY则此订单为含处方
                if ($v['index_type'] == '' || $v['index_type'] == 'Plastic Lenses' || $v['index_type'] == 'FRAME ONLY') {
                    $label[] = 1; //仅镜架
                } else if (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY') && $v['is_special_price'] == 0) {
                    $label[] = 2; //现片含处方
                } else if (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY') && $v['is_special_price'] == 1) {
                    $label[] = 3; //定制含处方
                }
            }

            //如果订单包括 仅镜架和现货处方镜 类型则为 镜架 + 现货
            if (in_array(1, $label) && in_array(2, $label) && !in_array(3, $label)) {
                $type_5_entity_id[] = $value['entity_id']; //镜架 + 现货

                //如果订单包括 仅镜架和定制处方镜 类型则为 镜架 + 定制
            } elseif (in_array(1, $label) && in_array(3, $label) && !in_array(2, $label)) {
                $type_4_entity_id[] = $value['entity_id']; //镜架 + 定制

                //如果订单只有 仅镜架 类型则为 仅镜架
            } elseif (in_array(1, $label) && !in_array(3, $label) && !in_array(2, $label)) {
                $type_1_entity_id[] = $value['entity_id']; //仅镜架

                //如果订单只有 现货 类型则为 现货处方镜
            } elseif (!in_array(1, $label) && !in_array(3, $label) && in_array(2, $label)) {
                $type_2_entity_id[] = $value['entity_id']; //仅现货处方镜

                //如果订单只有 定制 类型则为 仅定制处方镜
            } elseif (!in_array(1, $label) && in_array(3, $label) && !in_array(2, $label)) {
                $type_3_entity_id[] = $value['entity_id']; //仅定制处方镜
            } elseif (in_array(2, $label) && in_array(3, $label)) {
                $type_6_entity_id[] = $value['entity_id']; //现片+定制片
            } else {
                $type_1_entity_id[] = $value['entity_id']; //仅镜架
            }
        }

        if ($type_1_entity_id) {
            $map['entity_id'] = ['in', $type_1_entity_id];
            Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    protected function filter($origin_str)
    {
        return str_replace("'", "\'", $origin_str);
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function index()
    {
        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_zeelool')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id
from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id
order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_zeelool')->query($order_item_prescription_querySql);

        foreach ($order_item_list as $order_item_key => $order_item_value) {

            $product_options = unserialize($order_item_value['product_options']);
            // dump($product_options);
            $final_params['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coatiing_name'], 0, 100);
            $final_params['index_type'] = substr($product_options['info_buyRequest']['tmplens']['index_type'], 0, 100);

            $final_params['frame_price'] = $product_options['info_buyRequest']['tmplens']['frame_price'];
            $final_params['index_price'] = $product_options['info_buyRequest']['tmplens']['index_price'];
            $final_params['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coatiing_price'];

            $items[$order_item_key]['frame_regural_price'] = $final_params['frame_regural_price'] = $product_options['info_buyRequest']['tmplens']['frame_regural_price'];
            $items[$order_item_key]['is_special_price'] = $final_params['is_special_price'] = $product_options['info_buyRequest']['tmplens']['is_special_price'];
            $items[$order_item_key]['index_price_old'] = $final_params['index_price_old'] = $product_options['info_buyRequest']['tmplens']['index_price_old'];
            $items[$order_item_key]['index_name'] = $final_params['index_name'] = $product_options['info_buyRequest']['tmplens']['index_name'];
            $items[$order_item_key]['index_id'] = $final_params['index_id'] = $product_options['info_buyRequest']['tmplens']['index_id'];
            $items[$order_item_key]['lens'] = $final_params['lens'] = $product_options['info_buyRequest']['tmplens']['lens'];
            $items[$order_item_key]['lens_old'] = $final_params['lens_old'] = $product_options['info_buyRequest']['tmplens']['lens_old'];
            $items[$order_item_key]['total'] = $final_params['total'] = $product_options['info_buyRequest']['tmplens']['total'];
            $items[$order_item_key]['total_old'] = $final_params['total_old'] = $product_options['info_buyRequest']['tmplens']['total_old'];

            $prescription_params = $product_options['info_buyRequest']['tmplens']['prescription'];
            // dump($final_params);
            $prescription_params = explode("&", $prescription_params);
            $lens_params = array();
            foreach ($prescription_params as $key => $value) {
                // dump($value);
                $arr_value = explode("=", $value);
                $lens_params[$arr_value[0]] = $arr_value[1];
            }
            // dump($lens_params);
            $final_params = array_merge($lens_params, $final_params);
            // dump($final_params);            

            $items[$order_item_key]['order_id'] = $order_item_value['order_id'];
            $items[$order_item_key]['item_id'] = $order_item_value['item_id'];
            $items[$order_item_key]['product_id'] = $order_item_value['product_id'];
            $items[$order_item_key]['name'] = $order_item_value['name'];
            $items[$order_item_key]['sku'] = $order_item_value['sku'];
            $items[$order_item_key]['created_at'] = $order_item_value['created_at'];
            $items[$order_item_key]['qty_ordered'] = $order_item_value['qty_ordered'];
            $items[$order_item_key]['quote_item_id'] = $order_item_value['quote_item_id'];

            $items[$order_item_key]['coatiing_name'] = $final_params['coatiing_name'];
            $items[$order_item_key]['index_type'] = $final_params['index_type'];
            $items[$order_item_key]['prescription_type'] = $final_params['prescription_type'];

            $items[$order_item_key]['frame_price'] = $final_params['frame_price'] ? $final_params['frame_price'] : 0;
            $items[$order_item_key]['index_price'] = $final_params['index_price'] ? $final_params['index_price'] : 0;
            $items[$order_item_key]['coatiing_price'] = $final_params['coatiing_price'] ? $final_params['coatiing_price'] : 0;

            $items[$order_item_key]['year'] = $final_params['year'] ? $final_params['year'] : '';
            $items[$order_item_key]['month'] = $final_params['month'] ? $final_params['month'] : '';

            $items[$order_item_key]['information'] = str_replace("+", " ", urldecode($final_params['information']));

            $items[$order_item_key]['od_sph'] = $final_params['od_sph'];
            $items[$order_item_key]['os_sph'] = $final_params['os_sph'];

            $items[$order_item_key]['od_cyl'] = $final_params['od_cyl'];
            $items[$order_item_key]['os_cyl'] = $final_params['os_cyl'];

            $items[$order_item_key]['od_axis'] = $final_params['od_axis'];
            $items[$order_item_key]['os_axis'] = $final_params['os_axis'];

            if ($final_params['os_add'] && $final_params['od_add']) {
                $items[$order_item_key]['os_add'] = $final_params['os_add'];
                $items[$order_item_key]['od_add'] = $final_params['od_add'];
            } else {
                $items[$order_item_key]['total_add'] = $final_params['os_add'];
            }

            if ($final_params['pdcheck'] == 'on') {
                $items[$order_item_key]['pd_l'] = $final_params['pd_l'];
                $items[$order_item_key]['pd_r'] = $final_params['pd_r'];
            } else {
                $items[$order_item_key]['pd'] = $final_params['pd'];
            }

            if ($final_params['prismcheck'] == 'on') {
                $items[$order_item_key]['od_pv'] = $final_params['od_pv'];
                $items[$order_item_key]['od_bd'] = $final_params['od_bd'];
                $items[$order_item_key]['od_pv_r'] = $final_params['od_pv_r'];
                $items[$order_item_key]['od_bd_r'] = $final_params['od_bd_r'];

                $items[$order_item_key]['os_pv'] = $final_params['os_pv'];
                $items[$order_item_key]['os_bd'] = $final_params['os_bd'];
                $items[$order_item_key]['os_pv_r'] = $final_params['os_pv_r'];
                $items[$order_item_key]['os_bd_r'] = $final_params['os_bd_r'];
            }
            unset($final_params);
            unset($lens_params);
            unset($prescription_params);
            unset($product_options);
        }
        // dump($items);

        if ($items) {
            $batch_order_item_prescription_values = "";

            foreach ($items as $key => $value) {
                $batch_order_item_prescription_values .= "("
                    . $value['order_id'] . ","
                    . $value['item_id'] . ","
                    . $value['product_id'] . ","
                    . $value['qty_ordered'] . ","
                    . $value['quote_item_id'] . ","

                    . "'" . $this->filter($value['name']) . "',"
                    . "'" . $value['sku'] . "',"
                    . "'" . $value['created_at'] . "',"

                    . "'" . $value['index_type'] . "',"
                    . "'" . $value['prescription_type'] . "',"
                    . "'" . $value['coatiing_name'] . "',"

                    . "'" . $value['year'] . "',"
                    . "'" . $value['month'] . "',"

                    . "'" . $value['frame_price'] . "',"
                    . "'" . $value['index_price'] . "',"
                    . "'" . $value['coatiing_price'] . "',"

                    . "'" . $value['frame_regural_price'] . "',"
                    . "'" . $value['is_special_price'] . "',"
                    . "'" . $value['index_price_old'] . "',"
                    . "'" . $value['index_name'] . "',"
                    . "'" . $value['index_id'] . "',"
                    . "'" . $value['lens'] . "',"
                    . "'" . $value['lens_old'] . "',"
                    . "'" . $value['total'] . "',"
                    . "'" . $value['total_old'] . "',"
                    . "'" . $this->filter($value['information']) . "',"

                    . "'" . $value['od_sph'] . "',"
                    . "'" . $value['os_sph'] . "',"
                    . "'" . $value['od_cyl'] . "',"
                    . "'" . $value['os_cyl'] . "',"
                    . "'" . $value['od_axis'] . "',"
                    . "'" . $value['os_axis'] . "',"
                    . "'" . $value['pd_l'] . "',"
                    . "'" . $value['pd_r'] . "',"
                    . "'" . $value['pd'] . "',"
                    . "'" . $value['os_add'] . "',"
                    . "'" . $value['od_add'] . "',"
                    . "'" . $value['total_add'] . "',"
                    . "'" . $value['od_pv'] . "',"
                    . "'" . $value['od_bd'] . "',"
                    . "'" . $value['od_pv_r'] . "',"
                    . "'" . $value['od_bd_r'] . "',"
                    . "'" . $value['os_pv'] . "',"
                    . "'" . $value['os_bd'] . "',"
                    . "'" . $value['os_pv_r'] . "',"
                    . "'" . $value['os_bd_r'] . "'"
                    . "),";
            }

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(order_id,item_id,product_id,qty_ordered,quote_item_id,name,sku,created_at,index_type,prescription_type,coatiing_name,year,month,frame_price,index_price,coatiing_price,
                frame_regural_price,is_special_price,index_price_old,index_name,index_id,lens,lens_old,total,total_old,information,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r) values$batch_order_item_prescription_values";
            $batch_order_item_prescription_insertSql = rtrim($batch_order_item_prescription_insertSql, ',');
            $result = Db::connect('database.db_zeelool')->execute($batch_order_item_prescription_insertSql);
            if ($result) {
                echo '<br>执行成功';
            } else {
                echo '<br>执行失败';
            }
        } else {
            echo '执行完毕！';
        }
    }
}
