<?php

namespace app\admin\controller;

use app\admin\model\purchase\PurchaseOrder;
use app\admin\model\warehouse\LogisticsInfo;
use app\common\controller\Backend;
use think\Db;
use fast\Alibaba;



/**
 * 定时任务
 * @internal
 */
class Crontab extends Backend
{

    protected $noNeedLogin = [
        'get_sales_order_num',
        'zeelool_order_custom_order_prescription',
        'zeelool_order_item_process',
        'voogueme_order_custom_order_prescription',
        'voogueme_order_item_process',
        'nihao_order_custom_order_prescription',
        'nihao_order_item_process',
        'set_purchase_order_logistics',
        'product_grade_list_crontab',
        'changeItemNewToOld',
        'get_sku_stock',
        'get_sku_price',
        'get_sku_allstock'

    ];
    protected $order_status =  "and status in ('processing','complete','creditcard_proccessing','free_processing')";

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
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type is null order by entity_id asc limit 1000";
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
                } else if (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY') && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } else if (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY') && $v['is_custom_lens'] == 1) {
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

            /**
             * 判断定制现片逻辑
             * 1、渐进镜 Progressive
             * 2、偏光镜 镜片类型包含Polarized
             * 3、染色镜 镜片类型包含Lens with Color Tint
             * 4、当cyl<=-4或cyl>=4
             */

            if ($final_params['prescription_type'] == 'Progressive') {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Polarized') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Lens with Color Tint') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if ($final_params['od_cyl']) {
                if (urldecode($final_params['od_cyl']) * 1 <= -4 || urldecode($final_params['od_cyl']) * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_cyl']) {
                if (urldecode($final_params['os_cyl']) * 1 <= -4 || urldecode($final_params['os_cyl']) * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['od_sph']) {
                if (urldecode($final_params['od_sph']) * 1 < -8 || urldecode($final_params['od_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_sph']) {
                if (urldecode($final_params['os_sph']) * 1 < -8 || urldecode($final_params['os_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            unset($final_params);
            unset($lens_params);
            unset($prescription_params);
            unset($product_options);
        }

        if ($items) {
            $batch_order_item_prescription_values = "";
            $frameArr = $orderArr = [];
            foreach ($items as $key => $value) {
                $frameArr[] = $value['sku'];
                $orderArr[] = $value['item_id'];
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
                    . "'" . $value['os_bd_r'] . "',"
                    . "'" . $value['is_custom_lens'] . "'"
                    . "),";
            }

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(order_id,item_id,product_id,qty_ordered,quote_item_id,name,sku,created_at,index_type,prescription_type,coatiing_name,year,month,frame_price,index_price,coatiing_price,
                frame_regural_price,is_special_price,index_price_old,index_name,index_id,lens,lens_old,total,total_old,information,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r,is_custom_lens) values$batch_order_item_prescription_values";
            $batch_order_item_prescription_insertSql = rtrim($batch_order_item_prescription_insertSql, ',');
            $result = Db::connect('database.db_zeelool')->execute($batch_order_item_prescription_insertSql);
            if ($result) {
                echo '<br>执行成功';
            } else {
                echo '<br>执行失败';
            }
            //新增镜架是否无框
            if ($frameArr) {
                $whereMap['platform_sku'] = ['in', $frameArr];
                $whereMap['platform_type'] = 1;
                $skuType = Db::connect('database.db_stock')->table('fa_item_platform_sku')->where($whereMap)->field('platform_sku,platform_frame_is_rimless')->select();
                if ($skuType) {
                    $skuType = collection($skuType)->toArray();
                    $frameRimless = [];
                    foreach ($skuType as $k => $v) {
                        if (2 == $v['platform_frame_is_rimless']) {
                            $frameRimless[] = $v['platform_sku'];
                        }
                    }
                }
                $wherePrescription['sku'] = ['in', $frameRimless];
                $wherePrescription['order_id'] = ['in', $orderArr];
                Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->where($wherePrescription)->update(['frame_type_is_rimless' => 2]);
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
                } else if (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY') && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } else if (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY') && $v['is_custom_lens'] == 1) {
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

            /**
             * 判断定制现片逻辑
             * 1、渐进镜 Progressive
             * 2、偏光镜 镜片类型包含Polarized
             * 3、染色镜 镜片类型包含Lens with Color Tint
             * 4、当cyl<=-4或cyl>=4
             */
            if ($final_params['prescription_type'] == 'Progressive') {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Polarized') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Lens with Color Tint') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if ($final_params['od_cyl']) {
                if ($final_params['od_cyl'] * 1 <= -4 || $final_params['od_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_cyl']) {
                if ($final_params['os_cyl'] * 1 <= -4 || $final_params['os_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['od_sph']) {
                if (urldecode($final_params['od_sph']) * 1 < -8 || urldecode($final_params['od_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_sph']) {
                if (urldecode($final_params['os_sph']) * 1 < -8 || urldecode($final_params['os_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
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
            $frameArr = $orderArr = [];
            foreach ($items as $key => $value) {
                $frameArr[] = $value['sku'];
                $orderArr[] = $value['item_id'];
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
                    . "'" . $value['os_bd_r'] . "',"
                    . "'" . $value['is_custom_lens'] . "'"
                    . "),";
            }

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(order_id,item_id,product_id,qty_ordered,quote_item_id,name,sku,created_at,index_type,prescription_type,coatiing_name,year,month,frame_price,index_price,coatiing_price,
                frame_regural_price,is_special_price,index_price_old,index_name,index_id,lens,lens_old,total,total_old,information,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r,is_custom_lens) values$batch_order_item_prescription_values";
            $batch_order_item_prescription_insertSql = rtrim($batch_order_item_prescription_insertSql, ',');

            $result = Db::connect('database.db_voogueme')->execute($batch_order_item_prescription_insertSql);
            if ($result) {
                echo '<br>执行成功';
            } else {
                echo '<br>执行失败';
            }
            //新增镜架是否无框
            if ($frameArr) {
                $whereMap['platform_sku'] = ['in', $frameArr];
                $whereMap['platform_type'] = 2;
                $skuType = Db::connect('database.db_stock')->table('fa_item_platform_sku')->where($whereMap)->field('platform_sku,platform_frame_is_rimless')->select();
                if ($skuType) {
                    $skuType = collection($skuType)->toArray();
                    $frameRimless = [];
                    foreach ($skuType as $k => $v) {
                        if (2 == $v['platform_frame_is_rimless']) {
                            $frameRimless[] = $v['platform_sku'];
                        }
                    }
                }
                $wherePrescription['sku'] = ['in', $frameRimless];
                $wherePrescription['order_id'] = ['in', $orderArr];
                Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->where($wherePrescription)->update(['frame_type_is_rimless' => 2]);
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
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type is null order by entity_id desc limit 1000";
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
                if ($v['third_name'] == '' || $v['third_name'] == 'Plastic Lenses' || $v['third_name'] == 'FRAME ONLY') {
                    $label[] = 1; //仅镜架
                } else if (($v['third_name'] && $v['third_name'] != 'Plastic Lenses' && $v['third_name'] != 'FRAME ONLY') && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } else if (($v['third_name'] && $v['third_name'] != 'Plastic Lenses' && $v['third_name'] != 'FRAME ONLY') && $v['is_custom_lens'] == 1) {
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
            $tmp_lens_params = json_decode($tmp_product_options['info_buyRequest']['tmplens']['prescription'], true);

            $finalResult[$key]['prescription_type'] = $tmp_product_options['info_buyRequest']['tmplens']['prescription_type'];
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


            /**
             * 判断定制现片逻辑
             * 1、渐进镜 Progressive
             * 2、偏光镜 镜片类型包含Polarized
             * 3、染色镜 镜片类型包含Lens with Color Tint
             * 4、当cyl<=-4或cyl>=4
             */
            if ($tmp_lens_params['prescription_type'] == 'Progressive') {
                $finalResult[$key]['is_custom_lens'] = 1;
            }

            if (strpos($tmp_product_options['info_buyRequest']['tmplens']['third_name'], 'Polarized') !== false) {
                $finalResult[$key]['is_custom_lens'] = 1;
            }

            if (strpos($tmp_product_options['info_buyRequest']['tmplens']['third_name'], 'Lens with Color Tint') !== false) {
                $finalResult[$key]['is_custom_lens'] = 1;
            }

            if ($tmp_lens_params['od_cyl']) {
                if ($tmp_lens_params['od_cyl'] * 1 <= -4 || $tmp_lens_params['od_cyl'] * 1 >= 4) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }
            if ($tmp_lens_params['os_cyl']) {
                if ($tmp_lens_params['os_cyl'] * 1 <= -4 || $tmp_lens_params['os_cyl'] * 1 >= 4) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }

            if ($tmp_lens_params['od_sph']) {
                if (urldecode($tmp_lens_params['od_sph']) * 1 < -8 || urldecode($tmp_lens_params['od_sph']) * 1 > 8) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }

            if ($tmp_lens_params['os_sph']) {
                if (urldecode($tmp_lens_params['os_sph']) * 1 < -8 || urldecode($tmp_lens_params['os_sph']) * 1 > 8) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }
        }


        if ($finalResult) {
            $batch_order_item_prescription_values = "";
            $frameArr = $orderArr = [];
            foreach ($finalResult as $key => $value) {
                $frameArr[] = $value['sku'];
                $orderArr[] = $value['item_id'];
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
                    . "'" . $this->filter($value['information']) . "',"
                    . "'" . $value['is_custom_lens'] . "'"
                    . "),";
            }

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(item_id,quote_item_id,order_id,sku,qty_ordered,created_at,name,second_id,second_name,second_price,third_id,third_price,third_name,four_id,four_price,four_name,frame_price,frame_regural_price,
                cart_currency,is_frame_only,zsl,lens_price,total,prescription_type,year,month,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,
                od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r,pdcheck,information,is_custom_lens) values$batch_order_item_prescription_values";
            $batch_order_item_prescription_insertSql = rtrim($batch_order_item_prescription_insertSql, ',');

            $result = Db::connect('database.db_nihao')->execute($batch_order_item_prescription_insertSql);

            if ($result) {
                echo '<br>执行成功';
            } else {
                echo '<br>执行失败';
            }
            //新增镜架是否无框
            if ($frameArr) {
                $whereMap['platform_sku'] = ['in', $frameArr];
                $whereMap['platform_type'] = 3;
                $skuType = Db::connect('database.db_stock')->table('fa_item_platform_sku')->where($whereMap)->field('platform_sku,platform_frame_is_rimless')->select();
                if ($skuType) {
                    $skuType = collection($skuType)->toArray();
                    $frameRimless = [];
                    foreach ($skuType as $k => $v) {
                        if (2 == $v['platform_frame_is_rimless']) {
                            $frameRimless[] = $v['platform_sku'];
                        }
                    }
                }
                $wherePrescription['sku'] = ['in', $frameRimless];
                $wherePrescription['order_id'] = ['in', $orderArr];
                Db::connect('database.db_zeelool')->table('sales_flat_order_item_prescription')->where($wherePrescription)->update(['frame_type_is_rimless' => 2]);
            }
        } else {
            echo '执行完毕！';
        }
    }


    /**
     * 定时统计每天的销量
     */
    public function get_sales_order_num()
    {
        Db::connect('database.db_zeelool')->table('sales_flat_order')->query("set time_zone='+8:00'");
        Db::connect('database.db_voogueme')->table('sales_flat_order')->query("set time_zone='+8:00'");
        Db::connect('database.db_nihao')->table('sales_flat_order')->query("set time_zone='+8:00'");

        //计算前一天的销量
        $stime = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $etime = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $map['created_at'] = ['between', [$stime, $etime]];
        $map['status'] = ['in', ['processing', 'complete', 'creditcard_proccessing']];
        $zeelool_count = Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->count(1);
        $zeelool_total = Db::connect('database.db_zeelool')->table('sales_flat_order')->where($map)->sum('base_grand_total');

        $voogueme_count = Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->count(1);
        $voogueme_total = Db::connect('database.db_voogueme')->table('sales_flat_order')->where($map)->sum('base_grand_total');

        $nihao_count = Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->count(1);
        $nihao_total = Db::connect('database.db_nihao')->table('sales_flat_order')->where($map)->sum('base_grand_total');

        $data['zeelool_sales_num'] = $zeelool_count;
        $data['voogueme_sales_num'] = $voogueme_count;
        $data['nihao_sales_num'] = $nihao_count;
        $data['all_sales_num'] = $zeelool_count + $voogueme_count + $nihao_count;
        $data['zeelool_sales_money'] = $zeelool_total;
        $data['voogueme_sales_money'] = $voogueme_total;
        $data['nihao_sales_money'] = $nihao_total;
        $data['all_sales_money'] = $zeelool_total + $voogueme_total + $nihao_total;
        $data['create_date'] = date("Y-m-d", strtotime("-1 day"));
        $data['createtime'] = date("Y-m-d H:i:s");
        Db::name('order_statistics')->insert($data);
        echo 'ok';
        die;
    }

    /**
     * 定时获取1688发货采购单 生成物流单绑定关系
     */
    public function set_purchase_order_logistics()
    {
        //查询线上已发货的采购单
        $purchase = new PurchaseOrder();
        $map['purchase_type'] = 2;
        $map['purchase_status'] = ['in', [6, 7]];
        $map['is_add_logistics'] = 0;
        $map['is_del'] = 1;
        $list = $purchase->where($map)->limit(50)->select();
        $list = collection($list)->toArray();

        foreach ($list as $k => $v) {
            $res = Alibaba::getOrderDetail($v['purchase_number']);
            if (!$res) {
                continue;
            }
            $res = collection($res)->toArray();
            if ($res['result']->nativeLogistics->logisticsItems[0]->logisticsBillNo) {
                $data[$k]['id'] = $v['id'];
                $data[$k]['logistics_number'] = $res['result']->nativeLogistics->logisticsItems[0]->logisticsBillNo;
                $data[$k]['logistics_company_no'] = $res['result']->nativeLogistics->logisticsItems[0]->logisticsCompanyNo;
                $data[$k]['logistics_company_name'] = $res['result']->nativeLogistics->logisticsItems[0]->logisticsCompanyName;
                $data[$k]['is_add_logistics'] = 1;

                $params[$k]['logistics_number'] = $res['result']->nativeLogistics->logisticsItems[0]->logisticsBillNo;
                $params[$k]['type'] = 1;
                $params[$k]['order_number'] = $v['purchase_number'];
                $params[$k]['purchase_id'] = $v['id'];
                $params[$k]['createtime'] = date('Y-m-d H:i:s');
                $params[$k]['create_person'] = 'Admin';
            }
        }

        $logistics = new LogisticsInfo();
        if ($data) {
            $purchase->saveAll($data);

            $logistics->saveAll($params);
        }

        echo 'ok';
    }



    /**
     * 每天9点 根据销量计算产品分级
     */
    public function product_grade_list_crontab()
    {
        $start = date("Y-m-d", strtotime("-3 month"));
        $end = date("Y-m-d", time());

        //$zeelool_model = Db::connect('database.db_zeelool')->table('sales_flat_order');

        $zeelool_model = new \app\admin\model\order\order\Zeelool;
        $voogueme_model = new \app\admin\model\order\order\Voogueme;
        $nihao_model = new \app\admin\model\order\order\Nihao;
        $intelligent_purchase_query_sql = "select sfoi.sku,round(sum(sfoi.qty_ordered),0) counter,IF
        ( datediff( now(),cpe.created_at) > 90, 90, datediff( now(),cpe.created_at ) ) days,cpe.created_at
 from sales_flat_order_item sfoi
 left join sales_flat_order sfo on sfo.entity_id=sfoi.order_id
 left join catalog_product_entity cpe on cpe.entity_id=sfoi.product_id
 where sfo.status in('complete','processing','creditcard_proccessing') and if (datediff(now(),cpe.created_at) > 90,sfo.created_at between '$start' and '$end',sfo.created_at between cpe.created_at and '$end')
 GROUP BY sfoi.sku order by counter desc";
        $zeelool_list = $zeelool_model->query($intelligent_purchase_query_sql);
        //查询sku映射关系表 
        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $sku_list = $itemPlatFormSku->column('sku', 'platform_sku');

        //查询产品库sku
        foreach ($zeelool_list as $k => $v) {
            $true_sku = $sku_list[$v['sku']];
            $zeelool_list[$k]['true_sku'] = $true_sku;
            $zeelool_list[$k]['zeelool_sku'] = $v['sku'];
        }

        //$voogueme_model = Db::connect('database.db_voogueme')->table('sales_flat_order');
        $voogueme_list = $voogueme_model->query($intelligent_purchase_query_sql);
        //查询产品库sku
        foreach ($voogueme_list as $k => $v) {
            $true_sku = $sku_list[$v['sku']];
            $voogueme_list[$k]['true_sku'] = $true_sku;
            $voogueme_list[$k]['voogueme_sku'] = $v['sku'];
        }

        // $nihao_model = Db::connect('database.db_nihao')->table('sales_flat_order');
        $nihao_list = $nihao_model->query($intelligent_purchase_query_sql);
        //查询产品库sku
        foreach ($nihao_list as $k => $v) {
            $true_sku = $sku_list[$v['sku']];
            $nihao_list[$k]['true_sku'] = $true_sku;
            $nihao_list[$k]['nihao_sku'] = $v['sku'];
        }

        //合并数组
        $lists = array_merge($zeelool_list, $voogueme_list, $nihao_list);


        $data = [];
        foreach ($lists as $k => $v) {
            if ($data[$v['true_sku']]) {
                if ($v['voogueme_sku']) {
                    $data[$v['true_sku']]['voogueme_sku'] = $v['voogueme_sku'];
                }

                if ($v['nihao_sku']) {
                    $data[$v['true_sku']]['nihao_sku'] = $v['nihao_sku'];
                }

                $data[$v['true_sku']]['counter'] = $data[$v['true_sku']]['counter'] + $v['counter'];

                if ($v['days'] > $data[$v['true_sku']]['days']) {
                    $data[$v['true_sku']]['days'] = $v['days'];
                }
            } else {
                $data[$v['true_sku']] = $v;
            }
        }


        //查询供货商
        $supplier = new \app\admin\model\purchase\SupplierSku;
        $supplier_list = $supplier->alias('a')->join(['fa_supplier' => 'b'], 'a.supplier_id=b.id')->column('b.supplier_name,b.purchase_person', 'a.sku');


        //删除无用数组 释放内存
        unset($lists);
        unset($zeelool_list);
        unset($voogueme_list);
        //重置KEY
        $data = array_values($data);

        $AA_num = 0;
        $A_num = 0;
        $B_num = 0;
        $CA_num = 0;
        $C_num = 0;
        $D_num = 0;
        $E_num = 0;
        $F_num = 0;
        $list = [];
        foreach ($data as $k => $val) {
            $list[$k]['counter'] = $val['counter'];
            $list[$k]['days'] = $val['days'];
            $list[$k]['created_at'] = $val['created_at'];
            $list[$k]['true_sku'] = $val['true_sku'];
            $list[$k]['zeelool_sku'] = $val['zeelool_sku'] ? $val['zeelool_sku'] : '';
            $list[$k]['voogueme_sku'] = $val['voogueme_sku'] ? $val['voogueme_sku'] : '';
            $list[$k]['nihao_sku'] = $val['nihao_sku'] ? $val['nihao_sku'] : '';

            //分等级产品
            $num = round($val['counter'] * 1 / $val['days'] * 1 * 30);
            $list[$k]['num'] = $num;
            $list[$k]['supplier_name'] =  $supplier_list[$val['true_sku']]['supplier_name'];
            $list[$k]['purchase_person'] =  $supplier_list[$val['true_sku']]['purchase_person'];


            if ($num >= 300) {
                $list[$k]['grade'] = 'A+';
                $AA_num++;
            } elseif ($num >= 150 && $num < 300) {
                $list[$k]['grade'] = 'A';
                $A_num++;
            } elseif ($num >= 90 && $num < 150) {
                $list[$k]['grade'] = 'B';
                $B_num++;
            } elseif ($num >= 60 && $num < 90) {
                $list[$k]['grade'] = 'C+';
                $CA_num++;
            } elseif ($num >= 30 && $num < 60) {
                $list[$k]['grade'] = 'C';
                $C_num++;
            } elseif ($num >= 15 && $num < 30) {
                $list[$k]['grade'] = 'D';
                $D_num++;
            } elseif ($num >= 1 && $num < 15) {
                $list[$k]['grade'] = 'E';
                $E_num++;
            } else {
                $list[$k]['grade'] = 'F';
                $F_num++;
            }
            $list[$k]['createtime'] = date('Y-m-d H:i:s');
        }
        unset($data);


        $map = [];
        foreach ($list as $k => $v) {
            if ($v['grade'] == 'A+' || $v['grade'] == 'A') {
                if ($v['zeelool_sku']) {
                    $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-2 day")), date("Y-m-d 00:00:00", time())]];
                    $map['b.sku'] = $v['zeelool_sku'];
                    $zeelool_num = $zeelool_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->sum('b.qty_ordered');
                }

                if ($v['voogueme_sku']) {
                    $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-2 day")), date("Y-m-d 00:00:00", time())]];
                    $map['b.sku'] = $v['voogueme_sku'];
                    $voogueme_num = $voogueme_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->sum('b.qty_ordered');
                }

                if ($v['nihao_sku']) {
                    $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-2 day")), date("Y-m-d 00:00:00", time())]];
                    $map['b.sku'] = $v['nihao_sku'];
                    $nihao_num = $nihao_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->sum('b.qty_ordered');
                }
                $list[$k]['days_sales_num'] = round(($zeelool_num + $voogueme_num + $nihao_num) / 2, 2);
            }

            if ($v['grade'] == 'B' || $v['grade'] == 'C' || $v['grade'] == 'C+') {
                if ($v['zeelool_sku']) {
                    $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-5 day")), date("Y-m-d 00:00:00", time())]];
                    $map['b.sku'] = $v['zeelool_sku'];
                    $zeelool_num = $zeelool_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->sum('b.qty_ordered');
                }

                if ($v['voogueme_sku']) {
                    $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-5 day")), date("Y-m-d 00:00:00", time())]];
                    $map['b.sku'] = $v['voogueme_sku'];
                    $voogueme_num = $voogueme_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->sum('b.qty_ordered');
                }

                if ($v['nihao_sku']) {
                    $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-5 day")), date("Y-m-d 00:00:00", time())]];
                    $map['b.sku'] = $v['nihao_sku'];
                    $nihao_num = $nihao_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->sum('b.qty_ordered');
                }
                $list[$k]['days_sales_num'] = round(($zeelool_num + $voogueme_num + $nihao_num) / 5, 2);
            }

            if ($v['grade'] == 'D' || $v['grade'] == 'E' || $v['grade'] == 'F') {
                if ($v['zeelool_sku']) {
                    $zeelool_sku[] = $v['zeelool_sku'];
                }

                if ($v['voogueme_sku']) {
                    $voogueme_sku[] = $v['voogueme_sku'];
                }

                if ($v['nihao_sku']) {
                    $nihao_sku[] = $v['nihao_sku'];
                }
            }
        }

        $map = [];
        $map['a.status'] = ['in', ['complete', 'processing', 'creditcard_proccessing']];
        $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime("-30 day")), date("Y-m-d 00:00:00", time())]];
        //计算三个站最近30天销量
        if ($zeelool_sku) {
            $map['b.sku'] = ['in', $zeelool_sku];
            $zeelool = $zeelool_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->column('sum(b.qty_ordered) as num', 'b.sku');
        }

        if ($voogueme_sku) {
            $map['b.sku'] = ['in', $voogueme_sku];
            $voogueme = $voogueme_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->column('sum(b.qty_ordered) as num', 'b.sku');
        }

        if ($nihao_sku) {
            $map['b.sku'] = ['in', $nihao_sku];
            $nihao = $nihao_model->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->group('b.sku')->column('sum(b.qty_ordered) as num', 'b.sku');
        }

        foreach ($list as $k => $v) {
            if ($v['grade'] == 'D' || $v['grade'] == 'E' || $v['grade'] == 'F') {
                $list[$k]['days_sales_num'] = round(($zeelool[$v['zeelool_sku']] + $voogueme[$v['voogueme_sku']] + $nihao[$v['nihao_sku']]) / 30, 2);
            }
        }

        if ($list) {
            //清空表
            Db::execute("truncate table fa_product_grade;");
            //批量添加
            $res = Db::table('fa_product_grade')->insertAll($list);
        }
        echo 'ok';
    }

    /***
     * 定时把新品sku变成老品
     */
    public function changeItemNewToOld()
    {
        //select*from table where now() >SUBDATE(times,interval -1 day);
        $where['is_new'] = 1;
        $itemId = Db::connect('database.db_stock')->name('item')->where($where)->where("now() >SUBDATE(check_time,interval -15 day)")->column('id');
        if (false == $itemId) {
            return 'ok';
        }
        $map['id'] = ['in', $itemId];
        Db::connect('database.db_stock')->name('item')->where($map)->update(['is_new' => 2]);
    }

    /**
     * 记录每天商品库存变化日志
     *
     * @Description
     * @author wpl
     * @since 2020/02/19 16:23:27 
     * @return void
     */
    public function get_sku_stock()
    {
        $where['is_del'] = 1;
        $item = Db::connect('database.db_stock')->name('item')->where($where)->field('sku,available_stock as stock_num')->select();

        if ($item) {
            Db::name('goods_stock_log')->insertAll($item);
            echo 'ok';
        }
    }

    /**
     * 定时获取SKU最新的采购单价
     *
     * @Description
     * @author wpl
     * @since 2020/02/19 16:23:27 
     * @return void
     */
    public function get_sku_price()
    {
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $result = $purchase->alias('a')
            ->where('a.is_del', 1)
            ->field('sku,purchase_price,createtime')
            ->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            ->order('createtime desc')
            ->select();
        $arr = [];
        foreach ($result as $v) {
            if (!isset($arr[$v['sku']])) {
                $arr[$v['sku']] = $v['purchase_price'];
            } else {
                continue;
            }
        }
        if ($arr) {
            $list = [];
            $i = 0;
            foreach ($arr as $k => $v) {
                $list[$i]['sku'] = $k;
                $list[$i]['price'] = $v;
                $list[$i]['createtime'] = date('Y-m-d H:i:s', time());
                $i++;
            }
            unset($arr);
        }

        if ($list) {
            //清空表
            Db::execute("truncate table fa_sku_price;");
            //批量添加
            $res = Db::table('fa_sku_price')->insertAll($list);
        }
        echo 'ok';
    }

    /**
     * 记录每天总库存数
     *
     * @Description
     * @author wpl
     * @since 2020/02/29 16:13:16 
     * @return void
     */
    public function get_sku_allstock()
    {
        $item = new \app\admin\model\itemmanage\Item;
        $num = $item->getAllStock();
        $data['allnum'] = $num;
        $data['createtime'] = date('Y-m-d H:i:s');
        $res = Db::table('fa_product_allstock_log')->insert($data);
    }
    /**
     * 更新zeelool站仪表盘数据
     *
     * z站今天的销售额($) 订单数	订单支付成功数	客单价($)	购物车总数	购物车总转化率(%)	新增购物车数	新增购物车转化率	新增注册用户数
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/02 17:39:31 
     * @return void
     */
    public function update_ashboard_data()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if(!$platform){
            return false;
        }
        switch($platform){
            case 1:
            $model = Db::connect('database.db_zeelool');
            break;
            case 2:
            $model = Db::connect('database.db_voogueme');
            break;
            case 3:
            $model = Db::connect('database.db_nihao');
            break;
            default:
            $model = false;
            break;            
        }
        if(false === $model){
            return false;
        }
        $order_status = $this->order_status;
        //昨日销售额sql
        $yesterday_sales_money_sql = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE DATEDIFF(created_at,NOW())=-1 $order_status";
        //过去7天销售额sql
        $pastsevenday_sales_money_sql = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate() $order_status";
        //过去30天销售额sql
        $pastthirtyday_sales_money_sql = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate() $order_status";
        //当月销售额sql
        $thismonth_sales_money_sql     = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m') $order_status";
        //上月销售额sql
        $lastmonth_sales_money_sql     = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')) =1 $order_status";
        //今年销售额sql
        $thisyear_sales_money_sql      = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE YEAR(created_at)=YEAR(NOW()) $order_status";
        //总共的销售额sql
        $total_sales_money_sql         = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE 1 $order_status";
        //昨天订单数sql
        $yesterday_order_num_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE DATEDIFF(created_at,NOW())=-1";
        //过去7天订单数sql
        $pastsevenday_order_num_sql    = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate()";
        //过去30天订单数sql
        $pastthirtyday_order_num_sql   = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate()";
        //当月订单数sql
        $thismonth_order_num_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')" ;
        //上月订单数sql
        $lastmonth_order_num_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')) =1";
        //今年订单数sql
        $thisyear_order_num_sql        = "SELECT count(*) counter FROM sales_flat_order WHERE YEAR(created_at)=YEAR(NOW())"; 
        //总共的订单数sql
        $total_order_num_sql           = "SELECT count(*) counter FROM sales_flat_order";
        //昨天订单支付成功数sql
        $yesterday_order_success_sql   = "SELECT count(*) counter FROM sales_flat_order WHERE DATEDIFF(created_at,NOW())=-1 $order_status";
        //过去7天订单支付成功数sql
        $pastsevenday_order_success_sql    = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate() $order_status";
        //过去30天订单支付成功数sql
        $pastthirtyday_order_success_sql   = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate() $order_status";
        //当月订单支付成功数sql
        $thismonth_order_success_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m') $order_status";
        //上月订单支付成功数sql
        $lastmonth_order_success_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')) =1 $order_status";
        //今年订单支付成功数sql
        $thisyear_order_success_sql        = "SELECT count(*) counter FROM sales_flat_order WHERE YEAR(created_at)=YEAR(NOW()) $order_status";
        //总共订单支付成功数sql
        $total_order_success_sql           = "SELECT count(*) counter FROM sales_flat_order WHERE 1 $order_status";
        //昨日客单价
        // $yesterday_unit_price_rs              = round(($yesterday_sales_money_rs/$yesterday_order_success_rs),2);
        // //过去7天客单价
        // $pastsevenday_unit_price_rs           = round(($pastsevenday_sales_money_rs/$pastsevenday_order_success_rs),2);
        // //过去30天客单价
        // $pastthirtyday_unit_price_rs          = round(($pastthirtyday_sales_money_rs/$pastthirtyday_order_success_rs),2);
        // //当月客单价
        // $thismonth_unit_price_rs           = round(($thismonth_sales_money_rs/$thismonth_order_num_rs),2);
        // //上月客单价
        // $lastmonth_unit_price_rs              = round(($lastmonth_sales_money_rs/$lastmonth_order_success_rs),2);
        // //今年客单价
        // $thisyear_unit_price_rs           = round(($thisyear_sales_money_rs/$thisyear_sales_money_rs),2);
        // //总共客单价
        // $total_unit_price_rs              = round(($total_sales_money_rs/$total_order_success_rs),2);
        //昨天购物车总数sql
        $yesterday_shoppingcart_total_sql     = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATEDIFF(created_at,NOW())=-1";
        //过去7天购物车总数sql
        $pastsevenday_shoppingcart_total_sql  = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate()";
        //过去30天购物车总数sql
        $pastthirtyday_shoppingcart_total_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate()";
        //当月购物车总数sql
        $thismonth_shoppingcart_total_sql     = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')";
        //上月购物车总数sql
        $lastmonth_shoppingcart_total_sql     = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')) =1";
        //今年购物车总数sql
        $thisyear_shoppingcart_total_sql      = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND YEAR(created_at)=YEAR(NOW())";
        //总共购物车总数sql
        $total_shoppingcart_total_sql         = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0";
        // //昨天购物车转化率
        // $yesterday_shoppingcart_conversion_rs = round(($yesterday_order_success_rs/$yesterday_shoppingcart_total_rs),2);
        // //过去7天购物车转化率
        // $pastsevenday_shoppingcart_conversion_rs = round(($pastsevenday_order_success_rs/$pastsevenday_shoppingcart_total_rs),2);
        // //过去30天购物车转化率
        // $pastthirtyday_shoppingcart_conversion_rs = round(($pastthirtyday_order_success_rs/$pastthirtyday_shoppingcart_total_rs),2);
        // //当月购物车转化率
        // $thismonth_shoppingcart_conversion_rs = round(($thismonth_order_success_rs/$thismonth_shoppingcart_total_rs),2);
        // //上月购物车转化率
        // $lastmonth_shoppingcart_conversion_rs = round(($lastmonth_order_success_rs/$lastmonth_shoppingcart_total_rs),2);
        // //今年购物车转化率
        // $thisyear_shoppingcart_conversion_rs = round(($thisyear_order_success_rs/$thisyear_shoppingcart_total_rs),2);
        // //总共购物车转化率
        // $total_shoppingcart_conversion_rs = round(($total_order_success_rs/$total_shoppingcart_total_rs),2); 
        //昨天新增购物车总数sql
        $yesterday_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATEDIFF(updated_at,NOW())=-1";
        //过去7天新增购物车总数sql
        $pastsevenday_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(updated_at) and created_at< curdate()";
        //过去30天新增购物车总数sql
        $pastthirtyday_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(updated_at) and created_at< curdate()";
        //当月新增购物车总数sql
        $thismonth_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND  DATE_FORMAT(updated_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')";
        //上月新增购物车总数sql
        $lastmonth_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(updated_at,'%Y%m')) =1";
        //今年新增购物车总数sql
        $thisyear_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0 AND YEAR(updated_at)=YEAR(NOW())";
        //总共新增购物车总数sql
        $total_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0";
        //昨天新增购物车转化率
        // $yesterday_shoppingcart_newconversion_rs = round(($yesterday_order_success_rs/$yesterday_shoppingcart_new_rs),2);
        // //过去7天新增购物车转化率
        // $pastsevenday_shoppingcart_newconversion_rs = round(($pastsevenday_order_success_rs/$pastsevenday_shoppingcart_new_rs),2);
        // //过去30天新增购物车转化率
        // $pastthirtyday_shoppingcart_newconversion_rs = round(($pastthirtyday_order_success_rs/$pastthirtyday_shoppingcart_new_rs),2);
        // //当月新增购物车转化率
        // $thismonth_shoppingcart_newconversion_rs = round(($thismonth_order_success_rs/$thismonth_shoppingcart_new_rs),2);                
        // //上月新增购物车转化率
        // $lastmonth_shoppingcart_newconversion_rs = round(($lastmonth_order_success_rs/$lastmonth_shoppingcart_new_rs),2);
        // //今年新增购物车转化率
        // $thisyear_shoppingcart_newconversion_rs = round(($thisyear_order_success_rs/$thisyear_shoppingcart_new_rs),2);
        // //总共新增购物车转化率
        // $total_shoppingcart_newconversion_rs = round(($total_order_success_rs/$total_shoppingcart_new_rs),2);
        //昨天新增注册用户数sql
        $yesterday_register_customer_sql       = "SELECT count(*) counter from customer_entity where DATEDIFF(created_at,NOW())=-1";
        //过去7天新增注册用户数sql
        $pastsevenday_register_customer_sql    = "SELECT count(*) counter from customer_entity where DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate()";
        //过去30天新增注册用户数sql
        $pastthirtyday_register_customer_sql   = "SELECT count(*) counter from customer_entity where DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate()";
        //当月新增注册用户数sql
        $thismonth_register_customer_sql       = "SELECT count(*) counter from customer_entity where DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')";
        //上月新增注册用户数sql
        $lastmonth_register_customer_sql       = "SELECT count(*) counter from customer_entity where PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')) =1";
        //今年新增注册用户数sql
        $thisyear_register_customer_sql        = "SELECT count(*) counter from customer_entity where YEAR(updated_at)=YEAR(NOW())";
        //总共新增注册用户数sql
        $total_register_customer_sql           = "SELECT count(*) counter from customer_entity";
        //昨天销售额
        $yesterday_sales_money_rs                   = $model->query($yesterday_sales_money_sql);
        //过去7天销售额
        $pastsevenday_sales_money_rs                = $model->query($pastsevenday_sales_money_sql);
        //过去30天销售额
        $pastthirtyday_sales_money_rs               = $model->query($pastthirtyday_sales_money_sql);
        //当月销售额
        $thismonth_sales_money_rs                   = $model->query($thismonth_sales_money_sql);
        //上月销售额
        $lastmonth_sales_money_rs                   = $model->query($lastmonth_sales_money_sql);
        //今年销售额
        $thisyear_sales_money_rs                    = $model->query($thisyear_sales_money_sql);
        //总共销售额
        $total_sales_money_rs                       = $model->query($total_sales_money_sql);
        //昨天订单数
        $yesterday_order_num_rs                     = $model->query($yesterday_order_num_sql);
        //过去7天订单数
        $pastsevenday_order_num_rs                  = $model->query($pastsevenday_order_num_sql);
        //过去30天订单数
        $pastthirtyday_order_num_rs                 = $model->query($pastthirtyday_order_num_sql);
        //当月订单数
        $thismonth_order_num_rs                     = $model->query($thismonth_order_num_sql);
        //上月订单数
        $lastmonth_order_num_rs                     = $model->query($lastmonth_order_num_sql);
        //今年订单数
        $thisyear_order_num_rs                      = $model->query($thisyear_order_num_sql);
        //总共订单数
        $total_order_num_rs                         = $model->query($total_order_num_sql);
        //昨天支付成功数
        $yesterday_order_success_rs                 = $model->query($yesterday_order_success_sql);
        //过去7天支付成功数
        $pastsevenday_order_success_rs              = $model->query($pastsevenday_order_success_sql);
        //过去30天支付成功数
        $pastthirtyday_order_success_rs             = $model->query($pastthirtyday_order_success_sql);
        //当月支付成功数
        $thismonth_order_success_rs                 = $model->query($thismonth_order_success_sql);
        //上月支付成功数
        $lastmonth_order_success_rs                 = $model->query($lastmonth_order_success_sql);
        //今年支付成功数
        $thisyear_order_success_rs                  = $model->query($thisyear_order_success_sql);
        //总共支付成功数
        $total_order_success_rs                     = $model->query($total_order_success_sql);
        //昨天购物车总数
        $yesterday_shoppingcart_total_rs            = $model->query($yesterday_shoppingcart_total_sql);
        //过去7天购物车总数
        $pastsevenday_shoppingcart_total_rs         = $model->query($pastsevenday_shoppingcart_total_sql);
        //过去30天购物车总数
        $pastthirtyday_shoppingcart_total_rs        = $model->query($pastthirtyday_shoppingcart_total_sql);
        //当月购物车总数
        $thismonth_shoppingcart_total_rs            = $model->query($thismonth_shoppingcart_total_sql);
        //上月购物车总数
        $lastmonth_shoppingcart_total_rs            = $model->query($lastmonth_shoppingcart_total_sql);
        //今年购物车总数
        $thisyear_shoppingcart_total_rs             = $model->query($thisyear_shoppingcart_total_sql);
        //总共购物车总数
        $total_shoppingcart_total_rs                = $model->query($total_shoppingcart_total_sql);
        //昨天新增购物车总数
        $yesterday_shoppingcart_new_rs              = $model->query($yesterday_shoppingcart_new_sql);
        //过去7天新增购物车总数
        $pastsevenday_shoppingcart_new_rs           = $model->query($pastsevenday_shoppingcart_new_sql);
        //过去30天新增购物车总数
        $pastthirtyday_shoppingcart_new_rs          = $model->query($pastthirtyday_shoppingcart_new_sql);
        //当月新增购物车总数
        $thismonth_shoppingcart_new_rs              = $model->query($thismonth_shoppingcart_new_sql);
        //上月新增购物车总数
        $lastmonth_shoppingcart_new_rs              = $model->query($lastmonth_shoppingcart_new_sql);
        //今年新增购物车总数
        $thisyear_shoppingcart_new_rs               = $model->query($thisyear_shoppingcart_new_sql);
        //总共新增购物车总数
        $total_shoppingcart_new_rs                  = $model->query($total_shoppingcart_new_sql);
        //昨天新增注册人数
        $yesterday_register_customer_rs             = $model->query($yesterday_register_customer_sql);
        //过去7天新增注册人数
        $pastsevenday_register_customer_rs          = $model->query($pastsevenday_register_customer_sql);
        //过去30天新增注册人数
        $pastthirtyday_register_customer_rs         = $model->query($pastthirtyday_register_customer_sql);
        //当月新增注册人数
        $thismonth_register_customer_rs             = $model->query($thismonth_register_customer_sql);
        //上月新增注册人数
        $lastmonth_register_customer_rs             = $model->query($lastmonth_register_customer_sql);
        //今年新增注册人数
        $thisyear_register_customer_rs              = $model->query($thisyear_register_customer_sql);
        //总共新增注册人数
        $total_register_customer_rs                 = $model->query($total_register_customer_sql);
        dump($yesterday_sales_money_rs);
        dump($pastsevenday_sales_money_rs);
        dump($pastthirtyday_sales_money_rs);
        dump($thismonth_sales_money_rs);
        dump($lastmonth_sales_money_rs);
        dump($thisyear_sales_money_rs);
        dump($total_sales_money_rs);                                                                        
    }
}
