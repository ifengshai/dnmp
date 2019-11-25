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

    protected $noNeedLogin = ['zeelool_order_custom_order_prescription', 'zeelool_order_item_process', 'voogueme_order_custom_order_prescription', 'voogueme_order_item_process', 'nihao_order_custom_order_prescription', 'nihao_order_item_process'];

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
    public function  zeelool_order_custom_order_prescription()
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
            if (!$items) {
                continue;
            }

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
                $type_4_entity_id[] = $value['entity_id']; //镜架 + 现货

                //如果订单包括 仅镜架和定制处方镜 类型则为 镜架 + 定制
            } elseif (in_array(1, $label) && in_array(3, $label) && !in_array(2, $label)) {
                $type_5_entity_id[] = $value['entity_id']; //镜架 + 定制

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
    public function zeelool_order_item_process()
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

    /**
     * 定时处理 订单列表分类
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function  voogueme_order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type is null order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_voogueme')->query($order_entity_id_querySql);
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

            $items = Db::connect('database.db_voogueme')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();
            if (!$items) {
                continue;
            }

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
                $type_4_entity_id[] = $value['entity_id']; //镜架 + 现货

                //如果订单包括 仅镜架和定制处方镜 类型则为 镜架 + 定制
            } elseif (in_array(1, $label) && in_array(3, $label) && !in_array(2, $label)) {
                $type_5_entity_id[] = $value['entity_id']; //镜架 + 定制

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
            Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function voogueme_order_item_process()
    {
        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_voogueme')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id
from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id
order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_voogueme')->query($order_item_prescription_querySql);

        foreach ($order_item_list as $order_item_key => $order_item_value) {

            $product_options = unserialize($order_item_value['product_options']);

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

            $final_params = array_merge($lens_params, $final_params);

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


        if ($items) {
            $batch_order_item_prescription_values = "";
            $batch_order_item_updateSql = "";
            $batch_order_updateSql = "";
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

            $result = Db::connect('database.db_voogueme')->execute($batch_order_item_prescription_insertSql);
            if ($result) {
                echo '<br>执行成功';
            } else {
                echo '<br>执行失败';
            }
        } else {
            echo '执行完毕！';
        }
    }


    /**
     * 定时处理 订单列表分类
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function  nihao_order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type is null order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_nihao')->query($order_entity_id_querySql);
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

            $items = Db::connect('database.db_nihao')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();
            if (!$items) {
                continue;
            }
            
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
                $type_4_entity_id[] = $value['entity_id']; //镜架 + 现货

                //如果订单包括 仅镜架和定制处方镜 类型则为 镜架 + 定制
            } elseif (in_array(1, $label) && in_array(3, $label) && !in_array(2, $label)) {
                $type_5_entity_id[] = $value['entity_id']; //镜架 + 定制

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
            Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function nihao_order_item_process()
    {

        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_nihao')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        // echo 'fetch_template<br>';
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id
from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id
order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_nihao')->query($order_item_prescription_querySql);

        $finalResult = array();
        foreach ($order_item_list as $key => $value) {
            $finalResult[$key]['item_id'] = $value['item_id'];
            $finalResult[$key]['quote_item_id'] = $value['quote_item_id'];
            $finalResult[$key]['order_id'] = $value['order_id'];
            $finalResult[$key]['sku'] = $value['sku'];
            $finalResult[$key]['qty_ordered'] = $value['qty_ordered'];
            $finalResult[$key]['created_at'] = $value['created_at'];
            $finalResult[$key]['name'] = $value['name'];
            unset($tmp_product_options);
            $tmp_product_options = unserialize($value['product_options']);

            $finalResult[$key]['second_id'] = $tmp_product_options['info_buyRequest']['tmplens']['second_id'];
            $finalResult[$key]['second_name'] = $tmp_product_options['info_buyRequest']['tmplens']['second_name'];
            $finalResult[$key]['second_price'] = $tmp_product_options['info_buyRequest']['tmplens']['second_price'];

            $finalResult[$key]['third_id'] = $tmp_product_options['info_buyRequest']['tmplens']['third_id'];
            $finalResult[$key]['third_price'] = $tmp_product_options['info_buyRequest']['tmplens']['third_price'];
            $finalResult[$key]['third_name'] = $tmp_product_options['info_buyRequest']['tmplens']['third_name'];

            $finalResult[$key]['four_id'] = $tmp_product_options['info_buyRequest']['tmplens']['four_id'];
            $finalResult[$key]['four_price'] = $tmp_product_options['info_buyRequest']['tmplens']['four_price'];
            $finalResult[$key]['four_name'] = $tmp_product_options['info_buyRequest']['tmplens']['four_name'];

            $finalResult[$key]['frame_price'] = $tmp_product_options['info_buyRequest']['tmplens']['frame_price'];
            $finalResult[$key]['frame_regural_price'] = $tmp_product_options['info_buyRequest']['tmplens']['frame_regural_price'];

            $finalResult[$key]['cart_currency'] = $tmp_product_options['info_buyRequest']['cart_currency'];

            $finalResult[$key]['is_frame_only'] = $tmp_product_options['info_buyRequest']['tmplens']['is_frame_only'];
            $finalResult[$key]['zsl'] = $tmp_product_options['info_buyRequest']['tmplens']['zsl'];

            $finalResult[$key]['lens_price'] = $tmp_product_options['info_buyRequest']['tmplens']['lens_price'];
            $finalResult[$key]['total'] = $tmp_product_options['info_buyRequest']['tmplens']['total'];

            $tmp_lens_params = array();
            $tmp_lens_params = json_decode($tmp_product_options['info_buyRequest']['tmplens']['prescription'], ture);

            $finalResult[$key]['prescription_type'] = $tmp_lens_params['prescription_type'];
            // dump($tmp_lens_params);
            $finalResult[$key]['year'] = $tmp_lens_params['year'];
            $finalResult[$key]['month'] = $tmp_lens_params['month'];

            $finalResult[$key]['od_sph'] = $tmp_lens_params['od_sph'];
            $finalResult[$key]['od_cyl'] = $tmp_lens_params['od_cyl'];
            $finalResult[$key]['od_axis'] = $tmp_lens_params['od_axis'];

            $finalResult[$key]['os_sph'] = $tmp_lens_params['os_sph'];
            $finalResult[$key]['os_cyl'] = $tmp_lens_params['os_cyl'];
            $finalResult[$key]['os_axis'] = $tmp_lens_params['os_axis'];

            //处理ADD  当ReadingGlasses时 是 双ADD值
            if ($tmp_lens_params['prescription_type'] == 'Reading Glasses' &&  strlen($tmp_lens_params['os_add']) > 0 && strlen($tmp_lens_params['od_add']) > 0) {
                // echo '双ADD值';         
                $finalResult[$key]['od_add'] = $tmp_lens_params['od_add'];
                $finalResult[$key]['os_add'] = $tmp_lens_params['os_add'];
            } else {
                // echo '单ADD值';
                $finalResult[$key]['total_add'] = $tmp_lens_params['od_add'];
            }

            $finalResult[$key]['pdcheck'] = $tmp_lens_params['pdcheck'];

            //处理PD值
            if ($tmp_lens_params['pdcheck'] && strlen($tmp_lens_params['pd_r']) > 0 && strlen($tmp_lens_params['pd_l']) > 0) {
                // echo '双PD值';
                $finalResult[$key]['pd_r'] = $tmp_lens_params['pd_r'];
                $finalResult[$key]['pd_l'] = $tmp_lens_params['pd_l'];
            } else {
                // echo '单PD值';
                $finalResult[$key]['pd'] = $tmp_lens_params['pd'];
            }

            //斜视值
            if ($tmp_lens_params['prismcheck'] == 'on') {
                $finalResult[$key]['od_bd'] = $tmp_lens_params['od_bd'];
                $finalResult[$key]['od_pv'] = $tmp_lens_params['od_pv'];
                $finalResult[$key]['os_pv'] = $tmp_lens_params['os_pv'];
                $finalResult[$key]['os_bd'] = $tmp_lens_params['os_bd'];

                $finalResult[$key]['od_pv_r'] = $tmp_lens_params['od_pv_r'];
                $finalResult[$key]['od_bd_r'] = $tmp_lens_params['od_bd_r'];
                $finalResult[$key]['os_pv_r'] = $tmp_lens_params['os_pv_r'];
                $finalResult[$key]['os_bd_r'] = $tmp_lens_params['os_bd_r'];
            }

            //用户留言
            $finalResult[$key]['information'] = $tmp_lens_params['information'];
        }
        // dump($finalResult);

        if ($finalResult) {
            $batch_order_item_prescription_values = "";
            foreach ($finalResult as $key => $value) {
                $batch_order_item_prescription_values .= "("
                    . "'" . $value['item_id'] . "',"
                    . "'" . $value['quote_item_id'] . "',"
                    . "'" . $value['order_id'] . "',"
                    . "'" . $value['sku'] . "',"
                    . "'" . $value['qty_ordered'] . "',"
                    . "'" . $value['created_at'] . "',"

                    . "'" . $this->filter($value['name']) . "',"

                    . "'" . $value['second_id'] . "',"
                    . "'" . $value['second_name'] . "',"
                    . "'" . $value['second_price'] . "',"

                    . "'" . $value['third_id'] . "',"
                    . "'" . $value['third_price'] . "',"
                    . "'" . $value['third_name'] . "',"

                    . "'" . $value['four_id'] . "',"
                    . "'" . $value['four_price'] . "',"
                    . "'" . $value['four_name'] . "',"

                    . "'" . $value['frame_price'] . "',"
                    . "'" . $value['frame_regural_price'] . "',"
                    . "'" . $value['cart_currency'] . "',"
                    . "'" . $value['is_frame_only'] . "',"
                    . "'" . $value['zsl'] . "',"
                    . "'" . $value['lens_price'] . "',"
                    . "'" . $value['total'] . "',"
                    . "'" . $value['prescription_type'] . "',"

                    . "'" . $value['year'] . "',"
                    . "'" . $value['month'] . "',"

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
                    . "'" . $value['os_bd_r'] . "',"
                    . "'" . $value['pdcheck'] . "',"
                    . "'" . $this->filter($value['information']) . "'"
                    . "),";
            }

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(item_id,quote_item_id,order_id,sku,qty_ordered,created_at,name,second_id,second_name,second_price,third_id,third_price,third_name,four_id,four_price,four_name,frame_price,frame_regural_price,
                cart_currency,is_frame_only,zsl,lens_price,total,prescription_type,year,month,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,
                od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r,pdcheck,information) values$batch_order_item_prescription_values";
            $batch_order_item_prescription_insertSql = rtrim($batch_order_item_prescription_insertSql, ',');

            $result = Db::connect('database.db_nihao')->execute($batch_order_item_prescription_insertSql);

            if ($result) {
                echo '<br>执行成功';
            } else {
                echo '<br>执行失败';
            }
            C('SHOW_PAGE_TRACE', true);
        } else {
            echo '执行完毕！';
        }
    }
}
