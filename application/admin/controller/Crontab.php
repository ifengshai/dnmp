<?php

namespace app\admin\controller;

use app\admin\model\purchase\PurchaseOrder;
use app\admin\model\warehouse\LogisticsInfo;
use app\common\controller\Backend;
use think\Db;
use fast\Alibaba;
use fast\Trackingmore;

/**
 * 定时任务
 * @internal
 */
class Crontab extends Backend
{
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->lens = new \app\admin\model\lens\Index;
    }


    protected $order_status =  "and status in ('processing','complete','creditcard_proccessing','free_processing','paypal_canceled_reversal','paypal_reversed') and order_type not in (4,5)";


    /**
     * 定时处理 订单列表分类
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function zeelool_order_custom_order_prescription()
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
                if ($v['index_type'] == '' || $v['index_type'] == 'Plastic Lenses' || $v['index_type'] == 'FRAME ONLY' || $v['index_type'] == 'Frame Only' || $v['index_type'] == 'Frameonly' || ($v['index_type'] == '1.57 Sunglasses Non Prescription' && !$v['options_color']) || ($v['index_type'] == 'Sunglasses Frameonly' && !$v['options_color'])) {
                    $label[] = 1; //仅镜架
                } elseif (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY' && $v['index_type'] != 'Frame Only' && $v['index_type'] != 'Frameonly' || ($v['index_type'] == '1.57 Sunglasses Non Prescription' && $v['options_color']) || ($v['index_type'] == 'Sunglasses Frameonly' && $v['options_color'])) && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY' && $v['index_type'] != 'Frame Only' && $v['index_type'] != 'Frameonly' || ($v['index_type'] == '1.57 Sunglasses Non Prescription' && $v['options_color']) || ($v['index_type'] == 'Sunglasses Frameonly' && $v['options_color'])) && $v['is_custom_lens'] == 1) {
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
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";

        $order_item_list = Db::connect('database.db_zeelool')->query($order_item_prescription_querySql);

        foreach ($order_item_list as $order_item_key => $order_item_value) {
            $product_options = unserialize($order_item_value['product_options']);
            // dump($product_options);

            if ($product_options['info_buyRequest']['tmplens']['coating_name']) {
                $final_params['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coating_name'], 0, 100);
            } else {
                $final_params['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coatiing_name'], 0, 100);
            }

            if ($product_options['info_buyRequest']['tmplens']['lens_data_name']) {
                $final_params['index_type'] = substr($product_options['info_buyRequest']['tmplens']['lens_data_name'], 0, 100);
            } else {
                $final_params['index_type'] = substr($product_options['info_buyRequest']['tmplens']['index_type'], 0, 100);
            }

            $final_params['frame_price'] = $product_options['info_buyRequest']['tmplens']['frame_base_price'];
            if ($product_options['info_buyRequest']['tmplens']['lens_base_price']) {
                $final_params['index_price'] = $product_options['info_buyRequest']['tmplens']['lens_base_price'];
            } else {
                $final_params['index_price'] = $product_options['info_buyRequest']['tmplens']['index_price'];
            }

            if ($product_options['info_buyRequest']['tmplens']['coating_base_price']) {
                $final_params['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coating_base_price'];
            } else {
                $final_params['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coatiing_price'];
            }

            $items[$order_item_key]['frame_regural_price'] = $final_params['frame_regural_price'] = $product_options['info_buyRequest']['tmplens']['frame_regural_price'];
            $items[$order_item_key]['goods_type'] = $final_params['goods_type'] = $product_options['info_buyRequest']['tmplens']['goods_type'];
            $items[$order_item_key]['is_prescribe'] = $final_params['is_prescribe'] = $product_options['info_buyRequest']['tmplens']['is_prescribe'];
            $items[$order_item_key]['is_special_price'] = $final_params['is_special_price'] = $product_options['info_buyRequest']['tmplens']['is_special_price'];
            $items[$order_item_key]['index_price_old'] = $final_params['index_price_old'] = $product_options['info_buyRequest']['tmplens']['index_price_old'];
            $items[$order_item_key]['index_name'] = $final_params['index_name'] =  $final_params['index_type'];
            $items[$order_item_key]['index_id'] = $final_params['index_id'] = $product_options['info_buyRequest']['tmplens']['lens_id'] ?: $product_options['info_buyRequest']['tmplens']['index_id'];
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
            $items[$order_item_key]['options_color'] = $product_options['info_buyRequest']['tmplens']['sungless_color_name'];

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

            $final_params['os_add'] = urldecode($final_params['os_add']);
            $final_params['od_add'] = urldecode($final_params['od_add']);

            //判断双ADD还是单ADD
            if ((float) $final_params['os_add'] && (float) $final_params['od_add'] && $final_params['os_add'] != '0.00' && $final_params['od_add'] * 1 != '0.00') {
                //如果新处方add 对调 因为旧处方add左右眼颠倒
                $items[$order_item_key]['os_add'] = $final_params['os_add'];
                $items[$order_item_key]['od_add'] = $final_params['od_add'];
            } else {
                if ($items[$order_item_key]['od_add'] && (float) $final_params['od_add'] * 1 != 0) {
                    $items[$order_item_key]['total_add'] = $final_params['od_add'];
                } else {
                    $items[$order_item_key]['total_add'] = $final_params['os_add'];
                }
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
             * 4、当cyl<=-4或cyl>=4 或 sph < -8或 sph>8
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

            if (strpos($final_params['index_type'], 'Tinted') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Color Tint') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if ($final_params['od_cyl']) {
                if ((float) urldecode($final_params['od_cyl']) * 1 <= -4 || (float) urldecode($final_params['od_cyl']) * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_cyl']) {
                if ((float) urldecode($final_params['os_cyl']) * 1 <= -4 || (float) urldecode($final_params['os_cyl']) * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['od_sph']) {
                if ((float) urldecode($final_params['od_sph']) * 1 < -8 || (float) urldecode($final_params['od_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_sph']) {
                if ((float) urldecode($final_params['os_sph']) * 1 < -8 || (float) urldecode($final_params['os_sph']) * 1 > 8) {
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
                    . "'" . $value['goods_type'] . "',"
                    . "'" . $value['is_prescribe'] . "',"
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
                    . "'" . $value['is_custom_lens'] . "',"
                    . "'" . $value['options_color'] . "'"
                    . "),";
            }

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(order_id,item_id,product_id,qty_ordered,quote_item_id,name,sku,created_at,index_type,prescription_type,coatiing_name,year,month,frame_price,index_price,coatiing_price,
                frame_regural_price,goods_type,is_prescribe,is_special_price,index_price_old,index_name,index_id,lens,lens_old,total,total_old,information,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r,is_custom_lens,options_color) values$batch_order_item_prescription_values";
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
    public function voogueme_order_custom_order_prescription()
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
                if ($v['index_type'] == '' || $v['index_type'] == 'Plastic Lenses' || stripos($v['index_type'], 'FRAME ONLY') !== false || stripos($v['index_type'], 'FRAME ONLY (Plastic Lenses)') !== false) {
                    $label[] = 1; //仅镜架
                } elseif (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && stripos($v['index_type'], 'FRAME ONLY') === false && stripos($v['index_type'], 'FRAME ONLY (Plastic Lenses)') === false) && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && stripos($v['index_type'], 'FRAME ONLY') === false && stripos($v['index_type'], 'FRAME ONLY (Plastic Lenses)') === false) && $v['is_custom_lens'] == 1) {
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
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_voogueme')->query($order_item_prescription_querySql);

        foreach ($order_item_list as $order_item_key => $order_item_value) {
            $product_options = unserialize($order_item_value['product_options']);

            $final_params['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coatiing_name'], 0, 100);
            $final_params['index_type'] = substr($product_options['info_buyRequest']['tmplens']['index_type'], 0, 100);

            $final_params['frame_price'] = $product_options['info_buyRequest']['tmplens']['frame_base_price'];
            $final_params['index_price'] = $product_options['info_buyRequest']['tmplens']['lens_base_price'];
            $final_params['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coating_base_price'];

            $items[$order_item_key]['frame_regural_price'] = $final_params['frame_regural_price'] = $product_options['info_buyRequest']['tmplens']['frame_regural_price'];
            $items[$order_item_key]['goods_type'] = $final_params['goods_type'] = $product_options['info_buyRequest']['tmplens']['goods_type'];
            $items[$order_item_key]['is_prescribe'] = $final_params['is_prescribe'] = $product_options['info_buyRequest']['tmplens']['is_prescribe'];
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
                $final_params['od_cyl'] = urldecode($final_params['od_cyl']);
                if ((float) $final_params['od_cyl'] * 1 <= -4 || (float) $final_params['od_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_cyl']) {
                $final_params['os_cyl'] = urldecode($final_params['os_cyl']);
                if ((float) $final_params['os_cyl'] * 1 <= -4 || (float) $final_params['os_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['od_sph']) {
                if ((float) urldecode($final_params['od_sph']) * 1 < -8 || (float) urldecode($final_params['od_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_sph']) {
                if ((float) urldecode($final_params['os_sph']) * 1 < -8 || (float) urldecode($final_params['os_sph']) * 1 > 8) {
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
                    . "'" . $value['goods_type'] . "',"
                    . "'" . $value['is_prescribe'] . "',"
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
                frame_regural_price,goods_type,is_prescribe,is_special_price,index_price_old,index_name,index_id,lens,lens_old,total,total_old,information,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,total_add,od_pv,od_bd,od_pv_r,od_bd_r,os_pv,os_bd,os_pv_r,os_bd_r,is_custom_lens) values$batch_order_item_prescription_values";
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
    public function nihao_order_custom_order_prescription()
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
                } elseif (($v['third_name'] && $v['third_name'] != 'Plastic Lenses' && $v['third_name'] != 'FRAME ONLY') && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif (($v['third_name'] && $v['third_name'] != 'Plastic Lenses' && $v['third_name'] != 'FRAME ONLY') && $v['is_custom_lens'] == 1) {
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

        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";
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
            $finalResult[$key]['index_price'] = $tmp_product_options['info_buyRequest']['tmplens']['lens_base_price'];
            $finalResult[$key]['frame_price'] = $tmp_product_options['info_buyRequest']['tmplens']['frame_base_price'];
            $finalResult[$key]['frame_regural_price'] = $tmp_product_options['info_buyRequest']['tmplens']['frame_regural_price'];
            $finalResult[$key]['goods_type'] = $tmp_product_options['info_buyRequest']['tmplens']['goods_type'];
            $finalResult[$key]['is_prescribe'] = $tmp_product_options['info_buyRequest']['tmplens']['is_prescribe'];


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
                if ((float) $tmp_lens_params['od_cyl'] * 1 <= -4 || (float) $tmp_lens_params['od_cyl'] * 1 >= 4) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }
            if ($tmp_lens_params['os_cyl']) {
                if ((float) $tmp_lens_params['os_cyl'] * 1 <= -4 || (float) $tmp_lens_params['os_cyl'] * 1 >= 4) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }

            if ($tmp_lens_params['od_sph']) {
                if ((float) urldecode($tmp_lens_params['od_sph']) * 1 < -8 || (float) urldecode($tmp_lens_params['od_sph']) * 1 > 8) {
                    $finalResult[$key]['is_custom_lens'] = 1;
                }
            }

            if ($tmp_lens_params['os_sph']) {
                if ((float) urldecode($tmp_lens_params['os_sph']) * 1 < -8 || (float) urldecode($tmp_lens_params['os_sph']) * 1 > 8) {
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
                    
                    . "'" . $value['index_price'] . "',"

                    . "'" . $value['frame_price'] . "',"
                    . "'" . $value['frame_regural_price'] . "',"
                    . "'" . $value['goods_type'] . "',"
                    . "'" . $value['is_prescribe'] . "',"
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

            $batch_order_item_prescription_insertSql = "INSERT INTO sales_flat_order_item_prescription(item_id,quote_item_id,order_id,sku,qty_ordered,created_at,name,second_id,second_name,second_price,third_id,third_price,third_name,four_id,four_price,four_name,index_price,frame_price,frame_regural_price,goods_type,is_prescribe,
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
     * 定时处理 订单列表分类 meeloog站点
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function meeloog_order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type is null order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_meeloog')->query($order_entity_id_querySql);
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
            $items = Db::connect('database.db_meeloog')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();
            if (!$items) {
                continue;
            }

            $label = [];
            foreach ($items as $k => $v) {
                //如果镜片参数为真 或 不等于 Plastic Lenses 并且不等于 FRAME ONLY则此订单为含处方
                if ($v['index_type'] == '' || $v['index_type'] == 'Plastic Lenses' || $v['index_type'] == 'FRAME ONLY' || $v['index_type'] == 'FRAME ONLY (Plastic lenses)') {
                    $label[] = 1; //仅镜架
                } elseif (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY' && $v['index_type'] != 'FRAME ONLY (Plastic lenses)') && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif (($v['index_type'] && $v['index_type'] != 'Plastic Lenses' && $v['index_type'] != 'FRAME ONLY' && $v['index_type'] != 'FRAME ONLY (Plastic lenses)') && $v['is_custom_lens'] == 1) {
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
            Db::connect('database.db_meeloog')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_meeloog')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_meeloog')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_meeloog')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_meeloog')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_meeloog')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function meeloog_order_item_process()
    {
        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_meeloog')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_meeloog')->query($order_item_prescription_querySql);

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

            $result = Db::connect('database.db_meeloog')->execute($batch_order_item_prescription_insertSql);
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
                Db::connect('database.db_meeloog')->table('sales_flat_order_item_prescription')->where($wherePrescription)->update(['frame_type_is_rimless' => 2]);
            }
        } else {
            echo '执行完毕！';
        }
    }


    /**
     * 定时处理 订单列表分类 meeloog站点
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function wesee_order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type > 0 order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_weseeoptical')->query($order_entity_id_querySql);
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
            $items = Db::connect('database.db_weseeoptical')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();
            if (!$items) {
                continue;
            }

            $label = [];
            foreach ($items as $k => $v) {
                //如果镜片参数为真 或 不等于 Plastic Lenses 并且不等于 FRAME ONLY则此订单为含处方
                if (($v['index_type'] == '' || !$v['index_type']) && !$v['sph_degree']) {
                    $label[] = 1; //仅镜架
                } elseif ($v['index_type'] && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif ($v['sph_degree'] && $v['is_custom_lens'] == 1) {
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
            Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_weseeoptical')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function wesee_order_item_process()
    {
        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_weseeoptical')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_weseeoptical')->query($order_item_prescription_querySql);
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
            $items[$order_item_key]['sph_degree'] = $final_params['degrees'] = $product_options['info_buyRequest']['tmplens']['degrees'];

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



            //如果为太阳镜 拼接颜色
            if (@$product_options['info_buyRequest']['tmplens']['sungless_color_name']) {
                $items[$order_item_key]['index_name'] .= ' ' . $product_options['info_buyRequest']['tmplens']['sungless_color_name'];
                $items[$order_item_key]['index_type'] .= ' ' . $product_options['info_buyRequest']['tmplens']['sungless_color_name'];
            }


            /**
             * 判断定制现片逻辑
             * 1、渐进镜 Progressive
             * 2、偏光镜 镜片类型包含Polarized
             * 3、染色镜 镜片类型包含Lens with Color Tint
             * 4、当cyl<=-4或cyl>=4
             */

            if (strpos($final_params['index_type'], 'Lens with Color Tint') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            } else {
                $items[$order_item_key]['is_custom_lens'] = 0;
            }

            if ($final_params['degrees']) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            } else {
                $items[$order_item_key]['is_custom_lens'] = 0;
            }

            unset($final_params);
            unset($prescription_params);
            unset($product_options);
        }
        if ($items) {
            $result = Db::connect('database.db_weseeoptical')->table('sales_flat_order_item_prescription')->insertAll($items);
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
     * 西语站
     * 定时处理 订单列表分类
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function zeelool_es_order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type = 0 order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_zeelool_es')->query($order_entity_id_querySql);
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
            $items = Db::connect('database.db_zeelool_es')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();
            if (!$items) {
                continue;
            }

            $label = [];
            foreach ($items as $k => $v) {
                //如果镜片参数为真 或 不等于 Plastic Lenses 并且不等于 FRAME ONLY则此订单为含处方
                if ($v['index_type'] == '' || $v['index_type'] == 'Lentes  Plástico' || stripos($v['index_type'], 'SOLO MONTURA') !== false || stripos($v['index_type'], 'SOLO MONTURA (Lentes  Plástico)') !== false) {
                    $label[] = 1; //仅镜架
                } elseif (($v['index_type'] && $v['index_type'] != 'Lentes  Plástico' && stripos($v['index_type'], 'SOLO MONTURA') === false && stripos($v['index_type'], 'SOLO MONTURA (Lentes  Plástico)') === false) && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif (($v['index_type'] && $v['index_type'] != 'Lentes  Plástico' && stripos($v['index_type'], 'SOLO MONTURA') === false && stripos($v['index_type'], 'SOLO MONTURA (Lentes  Plástico)') === false) && $v['is_custom_lens'] == 1) {
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
            Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_zeelool_es')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function zeelool_es_order_item_process()
    {
        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_zeelool_es')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_zeelool_es')->query($order_item_prescription_querySql);

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
            $prescription_params = explode("&", $prescription_params);
            $lens_params = array();
            foreach ($prescription_params as $key => $value) {
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

            if (strpos($final_params['index_type'], 'Polarizadas') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Lens with Color Tint') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if ($final_params['od_cyl']) {
                $final_params['od_cyl'] = urldecode($final_params['od_cyl']);
                if ((float) $final_params['od_cyl'] * 1 <= -4 || (float) $final_params['od_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_cyl']) {
                $final_params['os_cyl'] = urldecode($final_params['os_cyl']);
                if ((float) $final_params['os_cyl'] * 1 <= -4 || (float) $final_params['os_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['od_sph']) {
                if ((float) urldecode($final_params['od_sph']) * 1 < -8 || (float) urldecode($final_params['od_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_sph']) {
                if ((float) urldecode($final_params['os_sph']) * 1 < -8 || (float) urldecode($final_params['os_sph']) * 1 > 8) {
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

            $result = Db::connect('database.db_zeelool_es')->execute($batch_order_item_prescription_insertSql);
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
     * 德语站
     * 定时处理 订单列表分类
     * 1：仅镜架
     * 2：仅现货处方镜
     * 3：仅定制处方镜
     * 4：镜架+现货
     * 5：镜架+定制
     * 6：现片+定制片
     */
    public function zeelool_de_order_custom_order_prescription()
    {
        $order_entity_id_querySql = "select sfo.entity_id from sales_flat_order sfo where sfo.custom_order_prescription_type = 0 order by entity_id desc limit 1000 ";
        $order_entity_id_list = Db::connect('database.db_zeelool_de')->query($order_entity_id_querySql);
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
            $items = Db::connect('database.db_zeelool_de')->table('sales_flat_order_item_prescription')->where('order_id=' . $value['entity_id'])->select();
            if (!$items) {
                continue;
            }

            $label = [];
            foreach ($items as $k => $v) {
                //如果镜片参数为真 或 不等于 Plastic Lenses 并且不等于 FRAME ONLY则此订单为含处方
                if ($v['index_type'] == '' || $v['index_type'] == 'Kunstsbisffgläser' || stripos($v['index_type'], 'Nur Rahmen') !== false || stripos($v['index_type'], 'Nur Rahmen (Kunstsbisffgläser)') !== false) {
                    $label[] = 1; //仅镜架
                } elseif (($v['index_type'] && $v['index_type'] != 'Kunstsbisffgläser' && stripos($v['index_type'], 'Nur Rahmen') === false && stripos($v['index_type'], 'Nur Rahmen (Kunstsbisffgläser)') === false) && $v['is_custom_lens'] == 0) {
                    $label[] = 2; //现片含处方
                } elseif (($v['index_type'] && $v['index_type'] != 'Kunstsbisffgläser' && stripos($v['index_type'], 'Nur Rahmen') === false && stripos($v['index_type'], 'Nur Rahmen (Kunstsbisffgläser)') === false) && $v['is_custom_lens'] == 1) {
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
            Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 1]);
        }

        if ($type_2_entity_id) {
            $map['entity_id'] = ['in', $type_2_entity_id];
            Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 2]);
        }

        if ($type_3_entity_id) {
            $map['entity_id'] = ['in', $type_3_entity_id];
            Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 3]);
        }


        if ($type_4_entity_id) {
            $map['entity_id'] = ['in', $type_4_entity_id];
            Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 4]);
        }


        if ($type_5_entity_id) {
            $map['entity_id'] = ['in', $type_5_entity_id];
            Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 5]);
        }


        if ($type_6_entity_id) {
            $map['entity_id'] = ['in', $type_6_entity_id];
            Db::connect('database.db_zeelool_de')->table('sales_flat_order')->where($map)->update(['custom_order_prescription_type' => 6]);
        }

        echo "执行成功！！";
    }

    /**
     * 定时处理订单处方表序列化数据
     */
    public function zeelool_de_order_item_process()
    {
        $max_item_id_querySql = "select max(boi.item_id) max_item_id from sales_flat_order_item_prescription boi";
        $max_item_id_list = Db::connect('database.db_zeelool_de')->query($max_item_id_querySql);
        if ($max_item_id_list) {
            $max_item_id = $max_item_id_list[0]['max_item_id'];
        }

        $max_item_id = $max_item_id > 0 ? $max_item_id : 0;
        $order_item_prescription_querySql = "select sfoi.item_id,sfoi.order_id,sfoi.product_id,sfoi.`name`,sfoi.sku,sfoi.product_options,sfoi.created_at,sfoi.qty_ordered,sfoi.quote_item_id from sales_flat_order_item sfoi where sfoi.item_id > $max_item_id order by sfoi.item_id asc limit 1000";
        $order_item_list = Db::connect('database.db_zeelool_de')->query($order_item_prescription_querySql);

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
            $prescription_params = explode("&", $prescription_params);
            $lens_params = array();
            foreach ($prescription_params as $key => $value) {
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

            if (strpos($final_params['index_type'], 'Polarisierende') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if (strpos($final_params['index_type'], 'Lens with Color Tint') !== false) {
                $items[$order_item_key]['is_custom_lens'] = 1;
            }

            if ($final_params['od_cyl']) {
                $final_params['od_cyl'] = urldecode($final_params['od_cyl']);
                if ((float) $final_params['od_cyl'] * 1 <= -4 || (float) $final_params['od_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_cyl']) {
                $final_params['os_cyl'] = urldecode($final_params['os_cyl']);
                if ((float) $final_params['os_cyl'] * 1 <= -4 || (float) $final_params['os_cyl'] * 1 >= 4) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['od_sph']) {
                if ((float) urldecode($final_params['od_sph']) * 1 < -8 || (float) urldecode($final_params['od_sph']) * 1 > 8) {
                    $items[$order_item_key]['is_custom_lens'] = 1;
                }
            }

            if ($final_params['os_sph']) {
                if ((float) urldecode($final_params['os_sph']) * 1 < -8 || (float) urldecode($final_params['os_sph']) * 1 > 8) {
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

            $result = Db::connect('database.db_zeelool_de')->execute($batch_order_item_prescription_insertSql);
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
     * 定时统计每天的销量(弃用)
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
     * 定时统计每天的销量等数据
     *之后修改添加
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/09 09:32:05
     * @return void
     */
    public function get_sales_order_data()
    {
        $zeelool_model = Db::connect('database.db_zeelool');
        $voogueme_model = Db::connect('database.db_voogueme');
        $nihao_model    = Db::connect('database.db_nihao');
        $meeloog_model  = Db::connect('database.db_meeloog');
        $zeelool_es_model = Db::connect('database.db_zeelool_es');
        $zeelool_de_model = Db::connect('database.db_zeelool_de');
        $zeelool_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $voogueme_model->table('customer_entity')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $nihao_model->table('customer_entity')->query("set time_zone='+8:00'");
        $meeloog_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $meeloog_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $meeloog_model->table('customer_entity')->query("set time_zone='+8:00'");

        $zeelool_es_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_es_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_es_model->table('customer_entity')->query("set time_zone='+8:00'");

        $zeelool_de_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $zeelool_de_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_de_model->table('customer_entity')->query("set time_zone='+8:00'");

        //计算前一天的销量
        $stime = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $etime = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $map['created_at'] = $date['created_at'] = $update['updated_at'] =  ['between', [$stime, $etime]];
        $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete']];
        $map['order_type'] = ['not in',[4,5]];
        $zeelool_count = $zeelool_model->table('sales_flat_order')->where($map)->count(1);
        $zeelool_total = $zeelool_model->table('sales_flat_order')->where($map)->sum('base_grand_total');
        //zeelool客单价
        if ($zeelool_count > 0) {
            $zeelool_unit_price = round(($zeelool_total / $zeelool_count), 2);
        } else {
            $zeelool_unit_price = 0;
        }

        //zeelool购物车数 SELECT count(*) counter from sales_flat_quote where base_grand_total>0
        $zeelool_shoppingcart_total = $zeelool_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
        //zeelool购物车更新数
        $zeelool_shoppingcart_update_total = $zeelool_model->table('sales_flat_quote')->where($update)->where('base_grand_total', 'GT', 0)->count('*');
        //zeelool购物车转化率
        if ($zeelool_shoppingcart_total > 0) {
            $zeelool_shoppingcart_conversion = round(($zeelool_count / $zeelool_shoppingcart_total) * 100, 2);
        } else {
            $zeelool_shoppingcart_conversion = 0;
        }
        //zeelool购物车更新转化率
        if ($zeelool_shoppingcart_update_total > 0) {
            $zeelool_shoppingcart_update_conversion = round(($zeelool_count / $zeelool_shoppingcart_update_total) * 100, 2);
        } else {
            $zeelool_shoppingcart_update_conversion = 0;
        }

        //zeelool注册用户数SELECT count(*) counter from customer_entity
        $zeelool_register_customer = $zeelool_model->table('customer_entity')->where($date)->count('*');
        $voogueme_count = $voogueme_model->table('sales_flat_order')->where($map)->count(1);
        $voogueme_total = $voogueme_model->table('sales_flat_order')->where($map)->sum('base_grand_total');
        //voogueme客单价
        if ($voogueme_count > 0) {
            $voogueme_unit_price = round(($voogueme_total / $voogueme_count), 2);
        } else {
            $voogueme_unit_price = 0;
        }
        //voogueme购物车数
        $voogueme_shoppingcart_total = $voogueme_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
        //voogueme购物车更新数
        $voogueme_shoppingcart_update_total = $voogueme_model->table('sales_flat_quote')->where($update)->where('base_grand_total', 'GT', 0)->count('*');
        //voogueme购物车转化率
        if ($voogueme_shoppingcart_total > 0) {
            $voogueme_shoppingcart_conversion = round(($voogueme_count / $voogueme_shoppingcart_total) * 100, 2);
        } else {
            $voogueme_shoppingcart_conversion = 0;
        }
        //voogueme购物车更新转化率
        if ($voogueme_shoppingcart_update_total > 0) {
            $voogueme_shoppingcart_update_conversion = round(($voogueme_count / $voogueme_shoppingcart_update_total) * 100, 2);
        } else {
            $voogueme_shoppingcart_update_conversion = 0;
        }

        //voogueme注册用户数
        $voogueme_register_customer = $voogueme_model->table('customer_entity')->where($date)->count('*');
        $nihao_count = $nihao_model->table('sales_flat_order')->where($map)->count(1);
        $nihao_total = $nihao_model->table('sales_flat_order')->where($map)->sum('base_grand_total');
        //nihao客单价
        if ($nihao_count > 0) {
            $nihao_unit_price = round(($nihao_total / $nihao_count), 2);
        } else {
            $nihao_unit_price = 0;
        }

        //nihao购物车数
        $nihao_shoppingcart_total = $nihao_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
        //nihao站购物车更新数
        $nihao_shoppingcart_update_total = $nihao_model->table('sales_flat_quote')->where($update)->where('base_grand_total', 'GT', 0)->count('*');
        //nihao购物车转化率
        if ($nihao_shoppingcart_total > 0) {
            $nihao_shoppingcart_conversion = round(($nihao_count / $nihao_shoppingcart_total) * 100, 2);
        } else {
            $nihao_shoppingcart_conversion = 0;
        }

        //nihao站购物车更新转化率
        if ($nihao_shoppingcart_update_total > 0) {
            $nihao_shoppingcart_update_conversion = round(($nihao_count / $nihao_shoppingcart_update_total) * 100, 2);
        } else {
            $nihao_shoppingcart_update_conversion = 0;
        }

        //nihao注册用户数
        $nihao_register_customer = $nihao_model->table('customer_entity')->where($date)->count('*');

        $meeloog_count = $meeloog_model->table('sales_flat_order')->where($map)->count(1);
        $meeloog_total = $meeloog_model->table('sales_flat_order')->where($map)->sum('base_grand_total');
        //meeloog客单价
        if ($meeloog_count > 0) {
            $meeloog_unit_price = round(($meeloog_total / $meeloog_count), 2);
        } else {
            $meeloog_unit_price = 0;
        }

        //meeloog购物车数
        $meeloog_shoppingcart_total = $meeloog_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
        //meeloog购物车更新数
        $meeloog_shoppingcart_update_total = $meeloog_model->table('sales_flat_quote')->where($update)->where('base_grand_total', 'GT', 0)->count('*');
        //meeloog购物车转化率
        if ($meeloog_shoppingcart_total > 0) {
            $meeloog_shoppingcart_conversion = round(($meeloog_count / $meeloog_shoppingcart_total) * 100, 2);
        } else {
            $meeloog_shoppingcart_conversion = 0;
        }

        //meeloog购物车更新转化率
        if ($meeloog_shoppingcart_update_total > 0) {
            $meeloog_shoppingcart_update_conversion = round(($meeloog_count / $meeloog_shoppingcart_update_total) * 100, 2);
        } else {
            $meeloog_shoppingcart_update_conversion = 0;
        }
        //nihao注册用户数
        $meeloog_register_customer = $meeloog_model->table('customer_entity')->where($date)->count('*');
        
        //zeelool es
        $zeelool_es_count = $zeelool_es_model->table('sales_flat_order')->where($map)->count(1);
        $zeelool_es_total = $zeelool_es_model->table('sales_flat_order')->where($map)->sum('base_grand_total');
        //zeelool_es客单价
        if ($zeelool_es_count > 0) {
            $zeelool_es_unit_price = round(($zeelool_es_total / $zeelool_es_count), 2);
        } else {
            $zeelool_es_unit_price = 0;
        }

        //zeelool_es购物车数
        $zeelool_es_shoppingcart_total = $zeelool_es_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
        //zeelool_es购物车更新数
        $zeelool_es_shoppingcart_update_total = $zeelool_es_model->table('sales_flat_quote')->where($update)->where('base_grand_total', 'GT', 0)->count('*');
        //zeelool_es购物车转化率
        if ($zeelool_es_shoppingcart_total > 0) {
            $zeelool_es_shoppingcart_conversion = round(($zeelool_es_count / $zeelool_es_shoppingcart_total) * 100, 2);
        } else {
            $zeelool_es_shoppingcart_conversion = 0;
        }

        //zeelool_es购物车更新转化率
        if ($zeelool_es_shoppingcart_update_total > 0) {
            $zeelool_es_shoppingcart_update_conversion = round(($zeelool_es_count / $zeelool_es_shoppingcart_update_total) * 100, 2);
        } else {
            $zeelool_es_shoppingcart_update_conversion = 0;
        }

        //zeelool_es注册用户数
        $zeelool_es_register_customer = $zeelool_es_model->table('customer_entity')->where($date)->count('*');

        //zeelool de
        $zeelool_de_count = $zeelool_de_model->table('sales_flat_order')->where($map)->count(1);
        $zeelool_de_total = $zeelool_de_model->table('sales_flat_order')->where($map)->sum('base_grand_total');
        //zeelool_de客单价
        if ($zeelool_de_count > 0) {
            $zeelool_de_unit_price = round(($zeelool_de_total / $zeelool_de_count), 2);
        } else {
            $zeelool_de_unit_price = 0;
        }

        //zeelool_de购物车数
        $zeelool_de_shoppingcart_total = $zeelool_de_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
        //zeelool_de购物车更新数
        $zeelool_de_shoppingcart_update_total = $zeelool_de_model->table('sales_flat_quote')->where($update)->where('base_grand_total', 'GT', 0)->count('*');
        //zeelool_de购物车转化率
        if ($zeelool_de_shoppingcart_total > 0) {
            $zeelool_de_shoppingcart_conversion = round(($zeelool_de_count / $zeelool_de_shoppingcart_total) * 100, 2);
        } else {
            $zeelool_de_shoppingcart_conversion = 0;
        }

        //zeelool_de购物车更新转化率
        if ($zeelool_de_shoppingcart_update_total > 0) {
            $zeelool_de_shoppingcart_update_conversion = round(($zeelool_de_count / $zeelool_de_shoppingcart_update_total) * 100, 2);
        } else {
            $zeelool_de_shoppingcart_update_conversion = 0;
        }

        //zeelool_de注册用户数
        $zeelool_de_register_customer = $zeelool_de_model->table('customer_entity')->where($date)->count('*');
        

        $data['zeelool_sales_num']                          = $zeelool_count;
        $data['voogueme_sales_num']                         = $voogueme_count;
        $data['nihao_sales_num']                            = $nihao_count;
        $data['meeloog_sales_num']                          = $meeloog_count;
        $data['zeelool_es_sales_num']                       = $zeelool_es_count;
        $data['zeelool_de_sales_num']                       = $zeelool_de_count;
        $data['all_sales_num']                              = $zeelool_count + $voogueme_count + $nihao_count + $meeloog_count;
        $data['zeelool_sales_money']                        = $zeelool_total;
        $data['voogueme_sales_money']                       = $voogueme_total;
        $data['nihao_sales_money']                          = $nihao_total;
        $data['meeloog_sales_money']                        = $meeloog_total;
        $data['zeelool_es_sales_money']                     = $zeelool_es_total;
        $data['zeelool_de_sales_money']                     = $zeelool_de_total;
        $data['all_sales_money']                            = $zeelool_total + $voogueme_total + $nihao_total + $meeloog_total;
        $data['zeelool_unit_price']                         = $zeelool_unit_price;
        $data['voogueme_unit_price']                        = $voogueme_unit_price;
        $data['nihao_unit_price']                           = $nihao_unit_price;
        $data['meeloog_unit_price']                         = $meeloog_unit_price;
        $data['zeelool_es_unit_price']                      = $zeelool_es_unit_price;
        $data['zeelool_de_unit_price']                      = $zeelool_de_unit_price;    
        $data['all_unit_price']                             = @round(($zeelool_unit_price + $voogueme_unit_price + $nihao_unit_price + $meeloog_unit_price + $zeelool_de_unit_price + $zeelool_es_unit_price) / 6, 2);
        $data['zeelool_shoppingcart_total']                 = $zeelool_shoppingcart_total;
        $data['voogueme_shoppingcart_total']                = $voogueme_shoppingcart_total;
        $data['nihao_shoppingcart_total']                   = $nihao_shoppingcart_total;
        $data['meeloog_shoppingcart_total']                 = $meeloog_shoppingcart_total;
        $data['zeelool_es_shoppingcart_total']              = $zeelool_es_shoppingcart_total;
        $data['zeelool_de_shoppingcart_total']              = $zeelool_de_shoppingcart_total;
        $data['all_shoppingcart_total']                     = $zeelool_shoppingcart_total + $voogueme_shoppingcart_total + $nihao_shoppingcart_total + $meeloog_shoppingcart_total + $zeelool_es_shoppingcart_total + $zeelool_de_shoppingcart_total;
        $data['zeelool_shoppingcart_conversion']            = $zeelool_shoppingcart_conversion;
        $data['voogueme_shoppingcart_conversion']           = $voogueme_shoppingcart_conversion;
        $data['nihao_shoppingcart_conversion']              = $nihao_shoppingcart_conversion;
        $data['meeloog_shoppingcart_conversion']            = $meeloog_shoppingcart_conversion;
        $data['zeelool_es_shoppingcart_conversion']         = $zeelool_es_shoppingcart_conversion;
        $data['zeelool_de_shoppingcart_conversion']         = $zeelool_de_shoppingcart_conversion; 
        $data['all_shoppingcart_conversion']                = @round(($zeelool_shoppingcart_conversion + $voogueme_shoppingcart_conversion + $nihao_shoppingcart_conversion + $meeloog_shoppingcart_conversion + $zeelool_es_shoppingcart_conversion + $zeelool_de_shoppingcart_conversion) / 6, 2);
        $data['zeelool_register_customer']                  = $zeelool_register_customer;
        $data['voogueme_register_customer']                 = $voogueme_register_customer;
        $data['nihao_register_customer']                    = $nihao_register_customer;
        $data['meeloog_register_customer']                  = $meeloog_register_customer;
        $data['zeelool_es_register_customer']               = $zeelool_es_register_customer;
        $data['zeelool_de_register_customer']               = $zeelool_de_register_customer;
        $data['all_register_customer']                      = $zeelool_register_customer + $voogueme_register_customer + $nihao_register_customer + $meeloog_register_customer + $zeelool_es_register_customer + $zeelool_de_register_customer;
        $data['zeelool_shoppingcart_update_total']          = $zeelool_shoppingcart_update_total;
        $data['voogueme_shoppingcart_update_total']         = $voogueme_shoppingcart_update_total;
        $data['nihao_shoppingcart_update_total']            = $nihao_shoppingcart_update_total;
        $data['meeloog_shoppingcart_update_total']          = $meeloog_shoppingcart_update_total;
        $data['zeelool_es_shoppingcart_update_total']       = $zeelool_es_shoppingcart_update_total;
        $data['zeelool_de_shoppingcart_update_total']       = $zeelool_de_shoppingcart_update_total;
        $data['all_shoppingcart_update_total']              = $zeelool_shoppingcart_update_total + $voogueme_shoppingcart_update_total + $nihao_shoppingcart_update_total + $meeloog_shoppingcart_update_total + $zeelool_es_shoppingcart_update_total + $zeelool_de_shoppingcart_update_total;
        $data['zeelool_shoppingcart_update_conversion']     = $zeelool_shoppingcart_update_conversion;
        $data['voogueme_shoppingcart_update_conversion']    = $voogueme_shoppingcart_update_conversion;
        $data['nihao_shoppingcart_update_conversion']       = $nihao_shoppingcart_update_conversion;
        $data['meeloog_shoppingcart_update_conversion']     = $meeloog_shoppingcart_update_conversion;
        $data['zeelool_es_shoppingcart_update_conversion']  = $zeelool_es_shoppingcart_update_conversion;
        $data['zeelool_de_shoppingcart_update_conversion']  = $zeelool_de_shoppingcart_update_conversion;
        $data['all_shoppingcart_update_conversion']       = @round(($zeelool_shoppingcart_update_conversion + $voogueme_shoppingcart_update_conversion + $nihao_shoppingcart_update_conversion + $meeloog_shoppingcart_update_conversion + $zeelool_es_shoppingcart_update_conversion + $zeelool_de_shoppingcart_update_conversion) / 6, 2);
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
            $res = Alibaba::getOrderDetail(trim($v['purchase_number']));
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
                $params[$k]['logistics_company_no'] = $res['result']->nativeLogistics->logisticsItems[0]->logisticsCompanyNo;
                $params[$k]['source'] = 2;
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
        set_time_limit(0);
        $start = date("Y-m-d", strtotime("-3 month"));
        $end = date("Y-m-d", time());

        //$zeelool_model = Db::connect('database.db_zeelool')->table('sales_flat_order');

        $zeelool_model = new \app\admin\model\order\order\Zeelool;
        $voogueme_model = new \app\admin\model\order\order\Voogueme;
        $nihao_model = new \app\admin\model\order\order\Nihao;
        $meeloog_model = new \app\admin\model\order\order\Meeloog;
        $intelligent_purchase_query_sql = "SELECT a.sku,if(counter,counter,0) as counter, 
        IF ( datediff( now( ), a.created_at ) > 90, 90, datediff( now( ), a.created_at ) ) days, a.created_at 
        FROM catalog_product_entity a 
        LEFT JOIN ( SELECT sku, round( sum( qty_ordered ) ) as counter FROM sales_flat_order_item sfoi 
        INNER JOIN sales_flat_order sfo ON sfo.entity_id = sfoi.order_id 
        WHERE sfo.STATUS IN ( 'complete', 'processing', 'free_proccessing', 'paypal_reversed' ) 
        AND sfo.created_at BETWEEN '$start' AND '$end' GROUP BY sku ) b ON substring_index(a.sku,'-',2) = b.sku where a.sku NOT LIKE 'Price%' ORDER BY counter DESC";

        $zeelool_list = $zeelool_model->query($intelligent_purchase_query_sql);
        //查询sku映射关系表
        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $sku_list = $itemPlatFormSku->column('sku', 'platform_sku');

        //查询产品库sku
        $zeelool_sku = [];
        foreach ($zeelool_list as $k => $v) {
            //判断库存时去掉-s 等
            $arr = explode('-', $v['sku']);
            $sku = $arr[0] . '-' . $arr[1];
            if (in_array($sku, $zeelool_sku)) {
                unset($zeelool_list[$k]);
                continue;
            }
            $true_sku = $sku_list[$sku];
            $zeelool_list[$k]['true_sku'] = $true_sku;
            $zeelool_list[$k]['zeelool_sku'] = $sku;
            $zeelool_sku[] = $sku;
        }


        $voogueme_list = $voogueme_model->query($intelligent_purchase_query_sql);
        //查询产品库sku
        $voogueme_sku = [];
        foreach ($voogueme_list as $k => $v) {
            //判断库存时去掉-s 等
            $arr = explode('-', $v['sku']);
            $sku = $arr[0] . '-' . $arr[1];
            if (in_array($sku, $voogueme_sku)) {
                unset($voogueme_list[$k]);
                continue;
            }
            $true_sku = $sku_list[$sku];
            $voogueme_list[$k]['true_sku'] = $true_sku;
            $voogueme_list[$k]['voogueme_sku'] = $sku;
            $voogueme_sku[] = $sku;
        }

        // $nihao_model = Db::connect('database.db_nihao')->table('sales_flat_order');
        $nihao_list = $nihao_model->query($intelligent_purchase_query_sql);
        //查询产品库sku
        $nihao_sku = [];
        foreach ($nihao_list as $k => $v) {
            //判断库存时去掉-s 等
            $arr = explode('-', $v['sku']);
            $sku = $arr[0] . '-' . $arr[1];
            if (in_array($sku, $nihao_sku)) {
                unset($nihao_list[$k]);
                continue;
            }
            $true_sku = $sku_list[$sku];
            $nihao_list[$k]['true_sku'] = $true_sku;
            $nihao_list[$k]['nihao_sku'] = $sku;
            $nihao_sku[] = $sku;
        }

        //合并数组
        $lists = array_merge($zeelool_list, $voogueme_list, $nihao_list);

        $data = [];
        foreach ($lists as $k => $v) {
            if ($v['true_sku'] == 'Express Shipping') {
                continue;
            }
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
        // $where['a.label'] = 1;
        $where['a.status'] = 1;
        $where['b.status'] = 1;
        $supplier_list = $supplier->alias('a')->where($where)->join(['fa_supplier' => 'b'], 'a.supplier_id=b.id')->column('b.supplier_name,b.purchase_person', 'a.sku');

        //查询产品库正常SKU
        $skus = $this->item->where(['is_open' => 1, 'is_del' => 1])->column('sku');

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
            if (!in_array($val['true_sku'], $skus)) {
                continue;
            }
            $list[$k]['counter'] = $val['counter'] ?? 0;
            $list[$k]['days'] = $val['days'] == 0 ? 1 : $val['days'];
            $list[$k]['created_at'] = $val['created_at'];
            $list[$k]['true_sku'] = $val['true_sku'];
            $list[$k]['zeelool_sku'] = $val['zeelool_sku'] ? $val['zeelool_sku'] : '';
            $list[$k]['voogueme_sku'] = $val['voogueme_sku'] ? $val['voogueme_sku'] : '';
            $list[$k]['nihao_sku'] = $val['nihao_sku'] ? $val['nihao_sku'] : '';

            //分等级产品
            $days = $val['days'] == 0 ? 1 : $val['days'];
            $num = round($val['counter'] * 1 / $days * 1 * 30);
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
            $zeelool_num = 0;
            $voogueme_num = 0;
            $nihao_num = 0;
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
        Db::connect('database.db_stock')->name('item')->query("set time_zone='+8:00'");
        $where['is_new'] = 1;
        $itemId = Db::connect('database.db_stock')->name('item')->where($where)->where("now() >SUBDATE(sku_putaway_time,interval -15 day)")->column('id');
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
        $item = Db::connect('database.db_stock')->name('item')->where($where)->field('sku,available_stock as stock_num,stock,occupy_stock,distribution_occupy_stock')->select();

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

        $item = new \app\admin\model\itemmanage\Item();
        if ($arr) {
            $list = [];
            $i = 0;
            foreach ($arr as $k => $v) {
                $item->where('sku', $k)->update(['purchase_price' => $v]);

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
    public function update_ashboard_data_one()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;    
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $order_status = $this->order_status;
        $order_type = " and order_type not in (4,5)";
        //昨日销售额sql
        $yesterday_sales_money_sql = "SELECT round(sum(base_grand_total),2)  base_grand_total   FROM sales_flat_order WHERE DATEDIFF(created_at,NOW())=-1  $order_status";
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
        //上一年销售额sql
        $lastyear_sales_money_sql      = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE year(created_at)=year(date_sub(now(),interval 1 year)) $order_status";
        //总共的销售额sql
        $total_sales_money_sql         = "SELECT round(sum(base_grand_total),2) base_grand_total FROM sales_flat_order WHERE 1 $order_status";
        //昨天订单数sql
        $yesterday_order_num_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE DATEDIFF(created_at,NOW())=-1";
        //过去7天订单数sql
        $pastsevenday_order_num_sql    = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(created_at) and created_at< curdate()";
        //过去30天订单数sql
        $pastthirtyday_order_num_sql   = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(created_at) and created_at< curdate() ";
        //当月订单数sql
        $thismonth_order_num_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')";
        //上月订单数sql
        $lastmonth_order_num_sql       = "SELECT count(*) counter FROM sales_flat_order WHERE PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')) =1 ";
        //今年订单数sql
        $thisyear_order_num_sql        = "SELECT count(*) counter FROM sales_flat_order WHERE YEAR(created_at)=YEAR(NOW()) ";
        //上一年的订单数sql
        $lastyear_order_num_sql        = "SELECT count(*) counter FROM sales_flat_order WHERE year(created_at)=year(date_sub(now(),interval 1 year))";
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
        //上一年订单支付成功数sql
        $lastyear_order_success_sql        = "SELECT count(*) counter FROM sales_flat_order WHERE year(created_at)=year(date_sub(now(),interval 1 year)) $order_status";
        //总共订单支付成功数sql
        $total_order_success_sql           = "SELECT count(*) counter FROM sales_flat_order WHERE 1 $order_status";
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
        $thisyear_register_customer_sql        = "SELECT count(*) counter from customer_entity where YEAR(created_at)=YEAR(NOW())";
        //上年新增注册用户数sql
        $lastyear_register_customer_sql        = "SELECT count(*) counter FROM customer_entity WHERE year(created_at)=year(date_sub(now(),interval 1 year))";
        //总共新增注册用户数sql
        $total_register_customer_sql           = "SELECT count(*) counter from customer_entity";
        //昨天新增登录用户数sql
        $yesterday_sign_customer_sql           = "SELECT count(*) counter from customer_entity where DATEDIFF(updated_at,NOW())=-1";
        //过去7天新增登录用户数sql
        $pastsevenday_sign_customer_sql        = "SELECT count(*) counter from customer_entity where DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= date(updated_at) and updated_at< curdate()";
        //过去30天新增注册用户数sql
        $pastthirtyday_sign_customer_sql   = "SELECT count(*) counter from customer_entity where DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= date(updated_at) and updated_at< curdate()";
        //当月新增注册用户数sql
        $thismonth_sign_customer_sql       = "SELECT count(*) counter from customer_entity where DATE_FORMAT(updated_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')";
        //上月新增注册用户数sql
        $lastmonth_sign_customer_sql       = "SELECT count(*) counter from customer_entity where PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(updated_at,'%Y%m')) =1";
        //今年新增注册用户数sql
        $thisyear_sign_customer_sql        = "SELECT count(*) counter from customer_entity where YEAR(updated_at)=YEAR(NOW())";
        //上年新增注册用户数sql
        $lastyear_sign_customer_sql        = "SELECT count(*) counter FROM customer_entity WHERE year(updated_at)=year(date_sub(now(),interval 1 year))";
        //总共新增注册用户数sql
        $total_sign_customer_sql           = "SELECT count(*) counter from customer_entity";
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('customer_entity')->query("set time_zone='+8:00'");
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
        //上年销售额
        $lastyear_sales_money_rs                    = $model->query($lastyear_sales_money_sql);
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
        //去年订单数
        $lastyear_order_num_rs                      = $model->query($lastyear_order_num_sql);
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
        //上年支付成功数
        $lastyear_order_success_rs                  = $model->query($lastyear_order_success_sql);
        //总共支付成功数
        $total_order_success_rs                     = $model->query($total_order_success_sql);
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
        //去年新增注册人数
        $lastyear_register_customer_rs              = $model->query($lastyear_register_customer_sql);
        //总共新增注册人数
        $total_register_customer_rs                 = $model->query($total_register_customer_sql);
        //昨天新增登录人数
        $yesterday_sign_customer_rs                 = $model->query($yesterday_sign_customer_sql);
        //过去7天新增登录人数
        $pastsevenday_sign_customer_rs              = $model->query($pastsevenday_sign_customer_sql);
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_rs             = $model->query($pastthirtyday_sign_customer_sql);
        //当月新增登录人数
        $thismonth_sign_customer_rs                 = $model->query($thismonth_sign_customer_sql);
        //上月新增登录人数
        $lastmonth_sign_customer_rs                 = $model->query($lastmonth_sign_customer_sql);
        //今年新增登录人数
        $thisyear_sign_customer_rs                  = $model->query($thisyear_sign_customer_sql);
        //去年新增登录人数
        $lastyear_sign_customer_rs                  = $model->query($lastyear_sign_customer_sql);
        //总共新增登录人数
        $total_sign_customer_rs                     = $model->query($total_sign_customer_sql);
        //昨天销售额data
        $yesterday_sales_money_data                 = $yesterday_sales_money_rs[0]['base_grand_total'];
        //过去7天销售额data
        $pastsevenday_sales_money_data              = $pastsevenday_sales_money_rs[0]['base_grand_total'];
        //过去30天销售额data
        $pastthirtyday_sales_money_data             = $pastthirtyday_sales_money_rs[0]['base_grand_total'];
        //当月销售额data
        $thismonth_sales_money_data                 = $thismonth_sales_money_rs[0]['base_grand_total'];
        //上月销售额data
        $lastmonth_sales_money_data                 = $lastmonth_sales_money_rs[0]['base_grand_total'];
        //今年销售额data
        $thisyear_sales_money_data                  = $thisyear_sales_money_rs[0]['base_grand_total'];
        //去年销售额data
        $lastyear_sales_money_data                  = $lastyear_sales_money_rs[0]['base_grand_total'];
        //总计销售额data
        $total_sales_money_data                     = $total_sales_money_rs[0]['base_grand_total'];
        //昨日订单数data
        $yesterday_order_num_data                   = $yesterday_order_num_rs[0]['counter'];
        //过去7天订单数data
        $pastsevenday_order_num_data                = $pastsevenday_order_num_rs[0]['counter'];
        //过去30天订单数data
        $pastthirtyday_order_num_data               = $pastthirtyday_order_num_rs[0]['counter'];
        //当月订单数data
        $thismonth_order_num_data                   = $thismonth_order_num_rs[0]['counter'];
        //上月订单数data
        $lastmonth_order_num_data                   = $lastmonth_order_num_rs[0]['counter'];
        //今年订单数data
        $thisyear_order_num_data                    = $thisyear_order_num_rs[0]['counter'];
        //上年订单数data
        $lastyear_order_num_data                    = $lastyear_order_num_rs[0]['counter'];
        //总共订单数data
        $total_order_num_data                       = $total_order_num_rs[0]['counter'];
        //昨日支付成功数data
        $yesterday_order_success_data               = $yesterday_order_success_rs[0]['counter'];
        //过去7天支付成功数data
        $pastsevenday_order_success_data            = $pastsevenday_order_success_rs[0]['counter'];
        //过去30天支付成功数data
        $pastthirtyday_order_success_data           = $pastthirtyday_order_success_rs[0]['counter'];
        //当月支付成功数data
        $thismonth_order_success_data               = $thismonth_order_success_rs[0]['counter'];
        //上月支付成功数data
        $lastmonth_order_success_data               = $lastmonth_order_success_rs[0]['counter'];
        //今年支付成功数data
        $thisyear_order_success_data                = $thisyear_order_success_rs[0]['counter'];
        //去年支付成功数data
        $lastyear_order_success_data                = $lastyear_order_success_rs[0]['counter'];
        //总共支付成功数data
        $total_order_success_data                   = $total_order_success_rs[0]['counter'];
        //昨日客单价data
        $yesterday_unit_price_data                  = @round(($yesterday_sales_money_data / $yesterday_order_success_data), 2);
        //过去7天客单价data
        $pastsevenday_unit_price_data               = @round(($pastsevenday_sales_money_data / $pastsevenday_order_success_data), 2);
        //过去30天客单价data
        $pastthirtyday_unit_price_data              = @round(($pastthirtyday_sales_money_data / $pastthirtyday_order_success_data), 2);
        //当月客单价data
        $thismonth_unit_price_data                  = @round(($thismonth_sales_money_data / $thismonth_order_success_data), 2);
        //上月客单价data
        $lastmonth_unit_price_data                  = @round(($lastmonth_sales_money_data / $lastmonth_order_success_data), 2);
        //今年客单价data
        $thisyear_unit_price_data                   = @round(($thisyear_sales_money_data / $thisyear_order_success_data), 2);
        //上一年客单价data
        $lastyear_unit_price_data                   = @round(($lastyear_sales_money_data / $lastyear_order_success_data), 2);
        //总共客单价data
        $total_unit_price_data                      = @round(($total_sales_money_data / $total_order_success_data), 2);
        //昨天新增注册人数
        $yesterday_register_customer_data           = $yesterday_register_customer_rs[0]['counter'];
        //过去7天新增注册人数
        $pastsevenday_register_customer_data        = $pastsevenday_register_customer_rs[0]['counter'];
        //过去30天新增注册人数
        $pastthirtyday_register_customer_data       = $pastthirtyday_register_customer_rs[0]['counter'];
        //当月新增注册人数
        $thismonth_register_customer_data           = $thismonth_register_customer_rs[0]['counter'];
        //上月新增注册人数
        $lastmonth_register_customer_data           = $lastmonth_register_customer_rs[0]['counter'];
        //今年新增注册人数
        $thisyear_register_customer_data            = $thisyear_register_customer_rs[0]['counter'];
        //上年新增注册人数
        $lastyear_register_customer_data            = $lastyear_register_customer_rs[0]['counter'];
        //总共新增注册人数
        $total_register_customer_data               = $total_register_customer_rs[0]['counter'];
        //昨天新增登录人数
        $yesterday_sign_customer_data               = $yesterday_sign_customer_rs[0]['counter'];
        //过去7天新增登录人数
        $pastsevenday_sign_customer_data            = $pastsevenday_sign_customer_rs[0]['counter'];
        //过去30天新增登录人数
        $pastthirtyday_sign_customer_data           = $pastthirtyday_sign_customer_rs[0]['counter'];
        //当月新增登录人数
        $thismonth_sign_customer_data               = $thismonth_sign_customer_rs[0]['counter'];
        //上月新增登录人数
        $lastmonth_sign_customer_data               = $lastmonth_sign_customer_rs[0]['counter'];
        //今年新增登录人数
        $thisyear_sign_customer_data                = $thisyear_sign_customer_rs[0]['counter'];
        //上年新增登录人数
        $lastyear_sign_customer_data                = $lastyear_sign_customer_rs[0]['counter'];
        //总共新增登录人数
        $total_sign_customer_data                   = $total_sign_customer_rs[0]['counter'];
        $updateData['yesterday_sales_money']        = $yesterday_sales_money_data ?? 0;
        $updateData['pastsevenday_sales_money']     = $pastsevenday_sales_money_data ?? 0;
        $updateData['pastthirtyday_sales_money']    = $pastthirtyday_sales_money_data ?? 0;
        $updateData['thismonth_sales_money']        = $thismonth_sales_money_data ?? 0;
        $updateData['lastmonth_sales_money']        = $lastmonth_sales_money_data ?? 0;
        $updateData['thisyear_sales_money']         = $thisyear_sales_money_data ?? 0;
        $updateData['lastyear_sales_money']         = $lastyear_sales_money_data ?? 0;
        $updateData['total_sales_money']            = $total_sales_money_data ?? 0;

        $updateData['yesterday_order_num']         = $yesterday_order_num_data ?? 0;
        $updateData['pastsevenday_order_num']      = $pastsevenday_order_num_data ?? 0;
        $updateData['pastthirtyday_order_num']     = $pastthirtyday_order_num_data ?? 0;
        $updateData['thismonth_order_num']         = $thismonth_order_num_data ?? 0;
        $updateData['lastmonth_order_num']         = $lastmonth_order_num_data ?? 0;
        $updateData['thisyear_order_num']          = $thisyear_order_num_data ?? 0;
        $updateData['lastyear_order_num']          = $lastyear_order_num_data ?? 0;
        $updateData['total_order_num']             = $total_order_num_data ?? 0;

        $updateData['yesterday_order_success']      = $yesterday_order_success_data ?? 0;
        $updateData['pastsevenday_order_success']   = $pastsevenday_order_success_data ?? 0;
        $updateData['pastthirtyday_order_success']  = $pastthirtyday_order_success_data ?? 0;
        $updateData['thismonth_order_success']      = $thismonth_order_success_data ?? 0;
        $updateData['lastmonth_order_success']      = $lastmonth_order_success_data ?? 0;
        $updateData['thisyear_order_success']       = $thisyear_order_success_data ?? 0;
        $updateData['lastyear_order_success']       = $lastyear_order_success_data ?? 0;
        $updateData['total_order_success']          = $total_order_success_data ?? 0;

        $updateData['yesterday_unit_price']         = $yesterday_unit_price_data ?? 0;
        $updateData['pastsevenday_unit_price']      = $pastsevenday_unit_price_data ?? 0;
        $updateData['pastthirtyday_unit_price']     = $pastthirtyday_unit_price_data ?? 0;
        $updateData['thismonth_unit_price']         = $thismonth_unit_price_data ?? 0;
        $updateData['lastmonth_unit_price']         = $lastmonth_unit_price_data ?? 0;
        $updateData['thisyear_unit_price']          = $thisyear_unit_price_data ?? 0;
        $updateData['lastyear_unit_price']          = $lastyear_unit_price_data ?? 0;
        $updateData['total_unit_price']             = $total_unit_price_data ?? 0;

        $updateData['yesterday_register_customer']      = $yesterday_register_customer_data ?? 0;
        $updateData['pastsevenday_register_customer']   = $pastsevenday_register_customer_data ?? 0;
        $updateData['pastthirtyday_register_customer']  = $pastthirtyday_register_customer_data ?? 0;
        $updateData['thismonth_register_customer']      = $thismonth_register_customer_data ?? 0;
        $updateData['lastmonth_register_customer']      = $lastmonth_register_customer_data ?? 0;
        $updateData['thisyear_register_customer']       = $thisyear_register_customer_data ?? 0;
        $updateData['lastyear_register_customer']       = $lastyear_register_customer_data ?? 0;
        $updateData['total_register_customer']          = $total_register_customer_data ?? 0;

        $updateData['yesterday_sign_customer']      = $yesterday_sign_customer_data ?? 0;
        $updateData['pastsevenday_sign_customer']   = $pastsevenday_sign_customer_data ?? 0;
        $updateData['pastthirtyday_sign_customer']  = $pastthirtyday_sign_customer_data ?? 0;
        $updateData['thismonth_sign_customer']      = $thismonth_sign_customer_data ?? 0;
        $updateData['lastmonth_sign_customer']      = $lastmonth_sign_customer_data ?? 0;
        $updateData['thisyear_sign_customer']       = $thisyear_sign_customer_data ?? 0;
        $updateData['lastyear_sign_customer']       = $lastyear_sign_customer_data ?? 0;
        $updateData['total_sign_customer']          = $total_sign_customer_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
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
    public function update_ashboard_data_two()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;        
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $order_status = $this->order_status;
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
        //上一年订单支付成功数sql
        $lastyear_order_success_sql        = "SELECT count(*) counter FROM sales_flat_order WHERE year(created_at)=year(date_sub(now(),interval 1 year)) $order_status";
        //总共订单支付成功数sql
        $total_order_success_sql           = "SELECT count(*) counter FROM sales_flat_order WHERE 1 $order_status";
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
        //上年购物车总数sql
        $lastyear_shoppingcart_total_sql      = "SELECT count(*) counter FROM sales_flat_quote WHERE year(created_at)=year(date_sub(now(),interval 1 year))";
        //总共购物车总数sql
        $total_shoppingcart_total_sql         = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0";
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
        //上年新增购物车总数sql
        $lastyear_shoppingcart_new_sql = "SELECT count(*) counter FROM sales_flat_quote WHERE base_grand_total>0 AND year(updated_at)=year(date_sub(now(),interval 1 year))";
        //总共新增购物车总数sql
        $total_shoppingcart_new_sql = "SELECT count(*) counter from sales_flat_quote where base_grand_total>0";
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_quote')->query("set time_zone='+8:00'");
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
        //上年支付成功数
        $lastyear_order_success_rs                  = $model->query($lastyear_order_success_sql);
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
        //上年购物车总数
        $lastyear_shoppingcart_total_rs             = $model->query($lastyear_shoppingcart_total_sql);
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
        //上年新增购物车总数
        $lastyear_shoppingcart_new_rs               = $model->query($lastyear_shoppingcart_new_sql);
        //总共新增购物车总数
        $total_shoppingcart_new_rs                  = $model->query($total_shoppingcart_new_sql);
        //昨日支付成功数data
        $yesterday_order_success_data               = $yesterday_order_success_rs[0]['counter'];
        //过去7天支付成功数data
        $pastsevenday_order_success_data            = $pastsevenday_order_success_rs[0]['counter'];
        //过去30天支付成功数data
        $pastthirtyday_order_success_data           = $pastthirtyday_order_success_rs[0]['counter'];
        //当月支付成功数data
        $thismonth_order_success_data               = $thismonth_order_success_rs[0]['counter'];
        //上月支付成功数data
        $lastmonth_order_success_data               = $lastmonth_order_success_rs[0]['counter'];
        //今年支付成功数data
        $thisyear_order_success_data                = $thisyear_order_success_rs[0]['counter'];
        //去年支付成功数data
        $lastyear_order_success_data                = $lastyear_order_success_rs[0]['counter'];
        //总共支付成功数data
        $total_order_success_data                   = $total_order_success_rs[0]['counter'];
        //昨天购物车总数data
        $yesterday_shoppingcart_total_data          = $yesterday_shoppingcart_total_rs[0]['counter'];
        //过去7天购物车总数data
        $pastsevenday_shoppingcart_total_data       = $pastsevenday_shoppingcart_total_rs[0]['counter'];
        //过去30天购物车总数data
        $pastthirtyday_shoppingcart_total_data      = $pastthirtyday_shoppingcart_total_rs[0]['counter'];
        //当月购物车总数data
        $thismonth_shoppingcart_total_data          = $thismonth_shoppingcart_total_rs[0]['counter'];
        //上月购物车总数data
        $lastmonth_shoppingcart_total_data          = $lastmonth_shoppingcart_total_rs[0]['counter'];
        //今年购物车总数data
        $thisyear_shoppingcart_total_data           = $thisyear_shoppingcart_total_rs[0]['counter'];
        //上一年购物车总数data
        $lastyear_shoppingcart_total_data           = $lastyear_shoppingcart_total_rs[0]['counter'];
        //总共购物车总数data
        $total_shoppingcart_total_data              = $total_shoppingcart_total_rs[0]['counter'];
        //昨天购物车转化率data
        $yesterday_shoppingcart_conversion_data     = @round(($yesterday_order_success_data / $yesterday_shoppingcart_total_data), 4) * 100;
        //过去7天购物车转化率data
        $pastsevenday_shoppingcart_conversion_data  = @round(($pastsevenday_order_success_data / $pastsevenday_shoppingcart_total_data), 4) * 100;
        //过去30天购物车转化率data
        $pastthirtyday_shoppingcart_conversion_data = @round(($pastthirtyday_order_success_data / $pastthirtyday_shoppingcart_total_data), 4) * 100;
        //当月购物车转化率data
        $thismonth_shoppingcart_conversion_data     = @round(($thismonth_order_success_data / $thismonth_shoppingcart_total_data), 4) * 100;
        //上月购物车转化率data
        $lastmonth_shoppingcart_conversion_data     = @round(($lastmonth_order_success_data / $lastmonth_shoppingcart_total_data), 4) * 100;
        //今年购物车转化率
        $thisyear_shoppingcart_conversion_data      = @round(($thisyear_order_success_data / $thisyear_shoppingcart_total_data), 4) * 100;
        //上年购物车总数sql
        $lastyear_shoppingcart_conversion_data      = @round(($lastyear_order_success_data / $lastyear_shoppingcart_total_data), 4) * 100;
        //总共购物车转化率
        $total_shoppingcart_conversion_data         = @round(($total_order_success_data / $total_shoppingcart_total_data), 4) * 100;
        //昨天新增购物车数
        $yesterday_shoppingcart_new_data            = $yesterday_shoppingcart_new_rs[0]['counter'];
        //过去7天新增购物车数
        $pastsevenday_shoppingcart_new_data         = $pastsevenday_shoppingcart_new_rs[0]['counter'];
        //过去30天新增购物车数
        $pastthirtyday_shoppingcart_new_data        = $pastthirtyday_shoppingcart_new_rs[0]['counter'];
        //当月新增购物车数
        $thismonth_shoppingcart_new_data            = $thismonth_shoppingcart_new_rs[0]['counter'];
        //上月新增购物车数
        $lastmonth_shoppingcart_new_data            = $lastmonth_shoppingcart_new_rs[0]['counter'];
        //今年新增购物车数
        $thisyear_shoppingcart_new_data             = $thisyear_shoppingcart_new_rs[0]['counter'];
        //上年新增购物车数
        $lastyear_shoppingcart_new_data             = $lastyear_shoppingcart_new_rs[0]['counter'];
        //总共新增购物车数
        $total_shoppingcart_new_data                = $total_shoppingcart_new_rs[0]['counter'];
        //昨天新增购物车转化率
        $yesterday_shoppingcart_newconversion_data  = @round(($yesterday_order_success_data / $yesterday_shoppingcart_new_data), 4) * 100;
        //过去7天新增购物车转化率
        $pastsevenday_shoppingcart_newconversion_data = @round(($pastsevenday_order_success_data / $pastsevenday_shoppingcart_new_data), 4) * 100;
        //过去30天新增购物车转化率
        $pastthirtyday_shoppingcart_newconversion_data = @round(($pastthirtyday_order_success_data / $pastthirtyday_shoppingcart_new_data), 4) * 100;
        //当月新增购物车转化率
        $thismonth_shoppingcart_newconversion_data = @round(($thismonth_order_success_data / $thismonth_shoppingcart_new_data), 4) * 100;
        //上月新增购物车转化率
        $lastmonth_shoppingcart_newconversion_data = @round(($lastmonth_order_success_data / $lastmonth_shoppingcart_new_data), 4) * 100;
        //今年新增购物车转化率
        $thisyear_shoppingcart_newconversion_data  = @round(($thisyear_order_success_data / $thisyear_shoppingcart_new_data), 4) * 100;
        //上年新增购物车总数sql
        $lastyear_shoppingcart_newconversion_data  = @round(($lastyear_order_success_data / $lastyear_shoppingcart_new_data), 4) * 100;
        //总共新增购物车转化率
        $total_shoppingcart_newconversion_data     = @round(($total_order_success_data / $total_shoppingcart_new_data), 4) * 100;

        $updateData['yesterday_shoppingcart_total']        = $yesterday_shoppingcart_total_data ?? 0;
        $updateData['pastsevenday_shoppingcart_total']     = $pastsevenday_shoppingcart_total_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_total']    = $pastthirtyday_shoppingcart_total_data ?? 0;
        $updateData['thismonth_shoppingcart_total']        = $thismonth_shoppingcart_total_data ?? 0;
        $updateData['lastmonth_shoppingcart_total']        = $lastmonth_shoppingcart_total_data ?? 0;
        $updateData['thisyear_shoppingcart_total']         = $thisyear_shoppingcart_total_data ?? 0;
        $updateData['lastyear_shoppingcart_total']         = $lastyear_shoppingcart_total_data ?? 0;
        $updateData['total_shoppingcart_total']            = $total_shoppingcart_total_data ?? 0;

        $updateData['yesterday_shoppingcart_conversion']         = $yesterday_shoppingcart_conversion_data ?? 0;
        $updateData['pastsevenday_shoppingcart_conversion']      = $pastsevenday_shoppingcart_conversion_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_conversion']     = $pastthirtyday_shoppingcart_conversion_data ?? 0;
        $updateData['thismonth_shoppingcart_conversion']         = $thismonth_shoppingcart_conversion_data ?? 0;
        $updateData['lastmonth_shoppingcart_conversion']         = $lastmonth_shoppingcart_conversion_data ?? 0;
        $updateData['thisyear_shoppingcart_conversion']          = $thisyear_shoppingcart_conversion_data ?? 0;
        $updateData['lastyear_shoppingcart_conversion']          = $lastyear_shoppingcart_conversion_data ?? 0;
        $updateData['total_shoppingcart_conversion']             = $total_shoppingcart_conversion_data ?? 0;

        $updateData['yesterday_shoppingcart_new']         = $yesterday_shoppingcart_new_data ?? 0;
        $updateData['pastsevenday_shoppingcart_new']      = $pastsevenday_shoppingcart_new_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_new']     = $pastthirtyday_shoppingcart_new_data ?? 0;
        $updateData['thismonth_shoppingcart_new']         = $thismonth_shoppingcart_new_data ?? 0;
        $updateData['lastmonth_shoppingcart_new']         = $lastmonth_shoppingcart_new_data ?? 0;
        $updateData['thisyear_shoppingcart_new']          = $thisyear_shoppingcart_new_data ?? 0;
        $updateData['lastyear_shoppingcart_new']          = $lastyear_shoppingcart_new_data ?? 0;
        $updateData['total_shoppingcart_new']             = $total_shoppingcart_new_data ?? 0;

        $updateData['yesterday_shoppingcart_newconversion']      = $yesterday_shoppingcart_newconversion_data ?? 0;
        $updateData['pastsevenday_shoppingcart_newconversion']   = $pastsevenday_shoppingcart_newconversion_data ?? 0;
        $updateData['pastthirtyday_shoppingcart_newconversion']  = $pastthirtyday_shoppingcart_newconversion_data ?? 0;
        $updateData['thismonth_shoppingcart_newconversion']      = $thismonth_shoppingcart_newconversion_data ?? 0;
        $updateData['lastmonth_shoppingcart_newconversion']      = $lastmonth_shoppingcart_newconversion_data ?? 0;
        $updateData['thisyear_shoppingcart_newconversion']       = $thisyear_shoppingcart_newconversion_data ?? 0;
        $updateData['lastyear_shoppingcart_newconversion']       = $lastyear_shoppingcart_newconversion_data ?? 0;
        $updateData['total_shoppingcart_newconversion']          = $total_shoppingcart_newconversion_data ?? 0;
        //查找是否存在的记录
        $result = Db::name('operation_analysis')->where(['order_platform' => $platform])->field('id,order_platform')->find();
        if (!$result) {
            $updateData['order_platform'] = $platform;
            $updateData['create_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->insert($updateData);
        } else {
            $updateData['update_time']    = date('Y-m-d h:i:s', time());
            $info = Db::name('operation_analysis')->where(['order_platform' => $platform])->update($updateData);
        }
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    /**
     * 定时更新供应链大屏-采购数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/09 11:35:05
     * @return void
     */
    public function purchase_data()
    {
        //当月采购总数
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        $purchaseNum = $purchase->getPurchaseNum();

        //当月采购总金额
        $purchasePrice = $purchase->getPurchasePrice();

        //当月采购镜架总数
        $purchaseFrameNum = $purchase->getPurchaseFrameNum();

        //当月采购镜架总金额
        $purchaseFramePrice = $purchase->getPurchaseFramePrice();

        //当月采购总SKU数
        $purchaseSkuNum = $purchase->getPurchaseSkuNum();

        //当月销售总数
        $zeeloolSkuNum = $this->zeelool->getOrderSkuNum();
        $vooguemeSkuNum = $this->voogueme->getOrderSkuNum();
        $nihaoSkuNum = $this->nihao->getOrderSkuNum();
        $salesNum = $zeeloolSkuNum + $vooguemeSkuNum + $nihaoSkuNum;

        //当月销售总成本
        $zeeloolSalesCost = $this->zeelool->getOrderSalesCost();
        $vooguemeSalesCost = $this->voogueme->getOrderSalesCost();
        $nihaoSalesCost = $this->nihao->getOrderSalesCost();
        $salesCost = $zeeloolSalesCost + $vooguemeSalesCost + $nihaoSalesCost;


        //当月到货总数
        $check = new \app\admin\model\warehouse\Check();
        $arrivalsNum = $check->getArrivalsNum();

        //当月质检合格数量
        $quantityNum = $check->getQuantityNum();

        //合格率
        if ($arrivalsNum > 0) {
            $quantityPercent = round($quantityNum / $arrivalsNum * 100, 2);
        }

        $dataConfig = new \app\admin\model\DataConfig();

        $data['value'] = $purchaseNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'purchaseNum')->update($data);

        $data['value'] = $purchasePrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'purchasePrice')->update($data);

        $data['value'] = $purchaseFrameNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'purchaseFrameNum')->update($data);

        $data['value'] = $purchaseFramePrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'purchaseFramePrice')->update($data);

        $data['value'] = $purchaseSkuNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'purchaseSkuNum')->update($data);

        $data['value'] = $arrivalsNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'arrivalsNum')->update($data);

        $data['value'] = $salesNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'salesNum')->update($data);

        $data['value'] = $quantityNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'quantityNum')->update($data);

        $data['value'] = $quantityPercent ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'quantityPercent')->update($data);

        $data['value'] = $purchaseNum ? round($purchasePrice / $purchaseNum, 2) : 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'purchaseAveragePrice')->update($data);

        $data['value'] = $salesCost;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'salesCost')->update($data);

        $data['value'] = $customSkuNum ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'customSkuNum')->update($data);

        $data['value'] = $customSkuNumMoney ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'customSkuNumMoney')->update($data);

        $data['value'] = $customSkuQty ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'customSkuQty')->update($data);

        $data['value'] = $customSkuPrice ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'customSkuPrice')->update($data);
    }

    /**
     * 定时更新供应链大屏-库存数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/09 11:42:50
     * @return void
     */
    public function stock_data()
    {
        $dataConfig = new \app\admin\model\DataConfig();
        //仓库总库存
        $allStock = $this->item->getAllStock();
        $data['value'] = $allStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'allStock')->update($data);

        //仓库库存总金额
        $allStockPrice = $this->item->getAllStockPrice();
        $data['value'] = $allStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'allStockPrice')->update($data);

        //镜架库存统计
        $frameStock = $this->item->getFrameStock();
        $data['value'] = $frameStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'frameStock')->update($data);

        //镜架总金额
        $frameStockPrice = $this->item->getFrameStockPrice();
        $data['value'] = $frameStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'frameStockPrice')->update($data);

        //镜片库存
        $lensStock = $this->lens->getLensStock();
        $data['value'] = $lensStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'lensStock')->update($data);

        //镜片库存总金额
        $lensStockPrice = $this->lens->getLensStockPrice();
        $data['value'] = $lensStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'lensStockPrice')->update($data);

        //饰品库存
        $ornamentsStock = $this->item->getOrnamentsStock();
        $data['value'] = $ornamentsStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'ornamentsStock')->update($data);

        //饰品库存总金额
        $ornamentsStockPrice = $this->item->getOrnamentsStockPrice();
        $data['value'] = $ornamentsStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'ornamentsStockPrice')->update($data);

        //样品库存
        $sampleNumStock = $this->item->getSampleNumStock();
        $data['value'] = $sampleNumStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'sampleNumStock')->update($data);

        //样品库存总金额
        $sampleNumStockPrice = $this->item->getSampleNumStockPrice();
        $data['value'] = $sampleNumStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'sampleNumStockPrice')->update($data);

        //查询总SKU数
        $skuNum = $this->item->getSkuNum();
        $data['value'] = $skuNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'skuNum')->update($data);

        //三个站待处理订单
        $zeeloolNum = $this->zeelool->getPendingOrderNum();
        $vooguemeNum = $this->voogueme->getPendingOrderNum();
        $nihaoNum = $this->nihao->getPendingOrderNum();
        $allPendingOrderNum = $zeeloolNum + $vooguemeNum + $nihaoNum;
        $data['value'] = $allPendingOrderNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'allPendingOrderNum')->update($data);

        /***
         * 库存周转天数 库存周转率
         * 库存周转天数 = 7*(期初总库存+期末总库存)/2/7天总销量
         * 库存周转率 =  360/库存周转天数
         */

        //查询最近7天总销量
        $orderStatistics = new \app\admin\model\OrderStatistics();
        $stime = date("Y-m-d", strtotime("-7 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        $allSalesNum = $orderStatistics->where($map)->sum('all_sales_num');
        $data['value'] = $allSalesNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'allSalesNum')->update($data);

        //期初总库存
        $productAllStockLog = new \app\admin\model\ProductAllStock();
        $start7days = $productAllStockLog->where('createtime', 'like', $stime . '%')->value('allnum');
        $end7days = $productAllStockLog->where('createtime', 'like', $etime . '%')->value('allnum');
        //库存周转天数
        if ($allSalesNum) {
            $stock7days = round(7 * ($start7days + $end7days) / 2 / $allSalesNum, 2);
            $data['value'] = $stock7days;
            $data['updatetime'] = date('Y-m-d H:i:s', time());
            $dataConfig->where('key', 'stock7days')->update($data);
        }

        //库存周转率
        if ($stock7days) {
            $stock7daysPercent = round(360 / $stock7days, 2);
            $data['value'] = $stock7daysPercent;
            $data['updatetime'] = date('Y-m-d H:i:s', time());
            $dataConfig->where('key', 'stock7daysPercent')->update($data);
        }

        //在途库存
        $onwayAllStock = $this->onway_all_stock();
        $data['value'] = $onwayAllStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onwayAllStock')->update($data);

        //在途库存总金额
        $onwayAllStockPrice = $this->onway_all_stock_price();
        $data['value'] = $onwayAllStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onwayAllStockPrice')->update($data);

        //在途镜架库存
        $onwayFrameAllStock = $this->onway_frame_all_stock();
        $data['value'] = $onwayFrameAllStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onwayFrameAllStock')->update($data);

        //在途镜架库存总金额
        $onwayFrameAllStockPrice = $this->onway_frame_all_stock_price();
        $data['value'] = $onwayFrameAllStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onwayFrameAllStockPrice')->update($data);

        //在途饰品库存
        $onwayOrnamentAllStock = $this->onway_ornament_all_stock();
        $data['value'] = $onwayOrnamentAllStock;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onwayOrnamentAllStock')->update($data);

        //在途饰品库存总金额
        $onwayOrnamentAllStockPrice = $this->onway_ornament_all_stock_price();
        $data['value'] = $onwayOrnamentAllStockPrice;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onwayOrnamentAllStockPrice')->update($data);
    }

    /**
     * 定时更新供应链大屏-仓库数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/04 17:05:15
     * @return void
     */
    public function warehouse_data()
    {
        $dataConfig = new \app\admin\model\DataConfig();
        //当月总单量
        $orderStatistics = new \app\admin\model\OrderStatistics();
        $stime = date("Y-m-01 00:00:00");
        $etime = date("Y-m-d H:i:s", time());
        $map['create_date'] = ['between', [$stime, $etime]];
        $lastMonthAllSalesNum = $orderStatistics->where($map)->sum('all_sales_num');
        $data['value'] = $lastMonthAllSalesNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'lastMonthAllSalesNum')->update($data);

        //未出库订单总数
        $zeeloolUnorderNum = $this->zeelool->undeliveredOrder([]);
        $vooguemeUnorderNum = $this->voogueme->undeliveredOrder([]);
        $nihaoUnorderNum = $this->nihao->undeliveredOrder([]);
        $allUnorderNum = $zeeloolUnorderNum + $vooguemeUnorderNum + $nihaoUnorderNum;
        $data['value'] = $allUnorderNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'allUnorderNum')->update($data);

        //7天未出库订单总数
        $map = [];
        $stime = date("Y-m-d H:i:s", strtotime("-7 day"));
        $etime = date("Y-m-d H:i:s", time());
        $map['a.created_at'] = ['between', [$stime, $etime]];
        $zeeloolUnorderNum = $this->zeelool->undeliveredOrder($map);
        $vooguemeUnorderNum = $this->voogueme->undeliveredOrder($map);
        $nihaoUnorderNum = $this->nihao->undeliveredOrder($map);
        $days7UnorderNum = $zeeloolUnorderNum + $vooguemeUnorderNum + $nihaoUnorderNum;
        $data['value'] = $days7UnorderNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'days7UnorderNum')->update($data);

        //当月质检总数
        $orderLog = new \app\admin\model\OrderLog();
        $orderCheckNum = $orderLog->getOrderCheckNum();
        $data['value'] = $orderCheckNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'orderCheckNum')->update($data);

        //当日配镜架总数 弃用
        // $orderFrameNum = $orderLog->getOrderFrameNum();
        $data['value'] = 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'orderFrameNum')->update($data);

        //当日配镜片总数 弃用
        // $orderLensNum = $orderLog->getOrderLensNum();
        $data['value'] = 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'orderLensNum')->update($data);

        //当日加工总数 弃用
        // $orderFactoryNum = $orderLog->getOrderFactoryNum();
        $data['value'] = 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'orderFactoryNum')->update($data);

        //当日质检总数 弃用
        // $orderCheckNewNum = $orderLog->getOrderCheckNewNum();
        $data['value'] = 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'orderCheckNewNum')->update($data);

        //当日出库总数
        $outStock = new \app\admin\model\warehouse\Outstock();
        $outStockNum = $outStock->getOutStockNum();
        $data['value'] = $outStockNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'outStockNum')->update($data);

        //当日质检入库总数
        $inStock = new \app\admin\model\warehouse\Instock();
        $inStockNum = $inStock->getInStockNum();
        $data['value'] = $inStockNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'inStockNum')->update($data);

        //总压单率
        $data['value'] = $pressureRate ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'pressureRate')->update($data);

        //7天压单率
        $data['value'] = $pressureRate7days ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'pressureRate7days')->update($data);

        //获取当月物流妥投数量
        $list = $this->getTrackingMoreStatusNumberCount();
        $monthAppropriate = $list['delivered'];

        //当月妥投总量
        $data['value'] = $monthAppropriate ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'monthAppropriate')->update($data);

        $allAppropriateNum = array_sum($list);
        $monthAppropriatePercent = $allAppropriateNum ? $list['delivered'] / $allAppropriateNum * 100 : 0;

        //当月妥投占比
        $data['value'] = $monthAppropriatePercent ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'monthAppropriatePercent')->update($data);

        //超时订单总数
        $data['value'] = $overtimeOrder ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'overtimeOrder')->update($data);
    }

    /**
     * 获取当月物流妥投数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/24 14:04:27
     * @return void
     */
    protected function getTrackingMoreStatusNumberCount()
    {
        //转国内时间
        $starttime = strtotime(date('Y-m-01 00:00:00', time())) - 8 * 3600;
        $endtime = strtotime(date('Y-m-d H:i:s', time()));
        $track = new Trackingmore();
        $track = $track->getStatusNumberCount($starttime, $endtime);
        return $track['data'];
    }



    /**
     * 定时更新供应链大屏-选品数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/10 09:56:39
     * @return void
     */
    public function select_product_data()
    {
        $dataConfig = new \app\admin\model\DataConfig();
        //在售SKU总数
        $onSaleSkuNum = $this->itemplatformsku->onSaleSkuNum();
        $data['value'] = $onSaleSkuNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onSaleSkuNum')->update($data);

        //在售镜架总数
        $onSaleFrameNum = $this->itemplatformsku->onSaleFrameNum();
        $data['value'] = $onSaleFrameNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onSaleFrameNum')->update($data);

        //在售饰品总数
        $onSaleOrnamentsNum = $this->itemplatformsku->onSaleOrnamentsNum();
        $data['value'] = $onSaleOrnamentsNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'onSaleOrnamentsNum')->update($data);


        //当月选品总数
        $new_product = new \app\admin\model\NewProduct();
        $selectProductNum = $new_product->selectProductNum();
        $data['value'] = $selectProductNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'selectProductNum')->update($data);

        //当月新品上线总数
        $new_product = new \app\admin\model\NewProduct();
        $selectProductAdoptNum = $new_product->selectProductAdoptNum();
        $data['value'] = $selectProductAdoptNum;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'selectProductAdoptNum')->update($data);

        // //查询新品SKU 待定
        // $newSkus = $this->item->getNewProductSku();
        // $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed']];
        // $stime = date("Y-m-d 00:00:00", strtotime("-10 day"));
        // $etime = date("Y-m-d H:i:s", time());
        // $where['a.created_at'] = ['between', [$stime, $etime]];

        // //Zeelool
        // $zeelool_res = $this->zeelool->alias('a')->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->where($where)->group('b.sku')->column("sum(qty_ordered)", 'b.sku');
        // //Voogueme
        // $voogueme_res = $this->voogueme->alias('a')->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->where($where)->group('b.sku')->column("sum(qty_ordered)", 'b.sku');
        // //Nihao
        // $nihao_res = $this->nihao->alias('a')->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')->where($where)->group('b.sku')->column("sum(qty_ordered)", 'b.sku');


        //新品十天内的销量
        $data['value'] = $days10SalesNum ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'days10SalesNum')->update($data);

        //新品十天内的销量占比
        $data['value'] = $days10SalesNumPercent ?? 0;
        $data['updatetime'] = date('Y-m-d H:i:s', time());
        $dataConfig->where('key', 'days10SalesNumPercent')->update($data);
    }

    /**
     * 记录仓库每天加工数据
     *
     * @Description
     * @author wpl
     * @since 2020/03/14 14:15:37
     * @return void
     */
    public function warehouse_data_everyday()
    {
        $time = [];
        //到货数量
        $check = new \app\admin\model\warehouse\Check();
        $data['arrival_num'] = ($check->getArrivalsNumToday($time)) ?? 0;

        //质检数量
        $data['check_num'] = ($check->getCheckNumToday($time)) ?? 0;

        //打印标签
        $zeeloolPrintLabelNum = $this->zeelool->printLabelNum($time);
        $vooguemePrintLabelNum = $this->voogueme->printLabelNum($time);
        $nihaoPrintLabelNum = $this->nihao->printLabelNum($time);
        $data['print_label_num'] = ($zeeloolPrintLabelNum + $vooguemePrintLabelNum + $nihaoPrintLabelNum) ?? 0;
        //配镜架
        $zeeloolFrameNum = $this->zeelool->frameNum($time);
        $vooguemeFrameNum = $this->voogueme->frameNum($time);
        $nihaoFrameNum =  $this->nihao->frameNum($time);
        $data['frame_num'] = ($zeeloolFrameNum + $vooguemeFrameNum + $nihaoFrameNum) ?? 0;

        //配镜片
        $zeeloolLensNum = $this->zeelool->lensNum($time);
        $vooguemeLensNum = $this->voogueme->lensNum($time);
        $nihaoLensNum = $this->nihao->lensNum($time);
        $data['lens_num'] = ($zeeloolLensNum + $vooguemeLensNum + $nihaoLensNum) ?? 0;

        //加工
        $zeeloolfactoryNum = $this->zeelool->factoryNum($time);
        $vooguemefactoryNum = $this->voogueme->factoryNum($time);
        $nihaofactoryNum = $this->nihao->factoryNum($time);
        $data['machining_num'] = ($zeeloolfactoryNum + $vooguemefactoryNum + $nihaofactoryNum) ?? 0;

        //成品质检
        $zeeloolfactoryNum = $this->zeelool->checkNum($time);
        $vooguemefactoryNum = $this->voogueme->checkNum($time);
        $nihaofactoryNum = $this->nihao->checkNum($time);
        $data['quality_num'] = ($zeeloolfactoryNum + $vooguemefactoryNum + $nihaofactoryNum) ?? 0;

        $data['create_time'] = date('Y-m-d H:i:s', time());
        $data['create_date'] = date('Y-m-d');

        //计算每天采购数量
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        //总采购数量
        $data['all_purchase_num'] = $purchase->getPurchaseNumNow([], $time);
        //总采购金额
        $data['all_purchase_price'] = $purchase->getPurchasePriceNow([], $time);
        //线上采购数量
        $data['online_purchase_num'] = $purchase->getPurchaseNumNow(['purchase_type' => 2], $time);
        //线上采购金额
        $data['online_purchase_price'] = $purchase->getPurchasePriceNow(['purchase_type' => 2], $time);
        //线下采购数量
        $data['purchase_num'] = $data['all_purchase_num'] - $data['online_purchase_num'];
        //线下采购金额
        $data['purchase_price'] = $data['all_purchase_price'] - $data['online_purchase_price'];

        //添加数据
        $model = new \app\admin\model\WarehouseData();
        $model->save($data);
        echo 'ok';
    }

    /**
     * 在途库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21
     * @return void
     */
    protected function onway_all_stock()
    {
        //计算SKU总采购数量
        $item = new \app\admin\model\itemmanage\Item();
        $on_way_stock = $item->where(['is_del' => 1, 'is_open' => 1])->sum('on_way_stock');
        return $on_way_stock;
    }

    /**
     * 在途库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21
     * @return void
     */
    protected function onway_all_stock_price()
    {
        //计算SKU总采购数量
        $item = new \app\admin\model\itemmanage\Item();
        $on_way_stock_price = $item->where(['is_del' => 1, 'is_open' => 1])->sum('on_way_stock*purchase_price');
        return $on_way_stock_price;
    }

    /**
     * 在途镜架库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21
     * @return void
     */
    protected function onway_frame_all_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 1;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $on_way_stock = $item->where($where)->sum('on_way_stock');
        return $on_way_stock;
    }

    /**
     * 在途镜架库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21
     * @return void
     */
    protected function onway_frame_all_stock_price()
    {
        $item = new \app\admin\model\itemmanage\Item();
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 1;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $on_way_stock_price = $item->where($where)->sum('on_way_stock*purchase_price');
        return $on_way_stock_price;
    }

    /**
     * 在途镜架库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21
     * @return void
     */
    protected function onway_ornament_all_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 3;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $on_way_stock = $item->where($where)->sum('on_way_stock');
        return $on_way_stock;
    }

    /**
     * 在途镜架库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21
     * @return void
     */
    protected function onway_ornament_all_stock_price()
    {
        $item = new \app\admin\model\itemmanage\Item();
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 3;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $on_way_stock_price = $item->where($where)->sum('on_way_stock*purchase_price');
        return $on_way_stock_price;
    }



    /**
     * 更新order_statistics表字段
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/11 17:11:21
     * @return void
     */
    public function get_sales_order_update()
    {
        $zeelool_model  = Db::connect('database.db_zeelool');
        $voogueme_model = Db::connect('database.db_voogueme');
        $nihao_model    = Db::connect('database.db_nihao');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $voogueme_model->table('customer_entity')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $nihao_model->table('customer_entity')->query("set time_zone='+8:00'");
        $where['zeelool_shoppingcart_total'] = 0;
        $result = Db::name('order_statistics')->where($where)->limit(1)->select();
        if (!$result) {
            echo 'ok2';
            exit;
        }
        $data = [];
        foreach ($result as $v) {
            $starttime          = $v['create_date'] . ' ' . '00:00:00';
            $endtime            = $v['create_date'] . ' ' . '23:59:59';
            $map['created_at']  = $date['created_at'] = ['between', [$starttime, $endtime]];
            $map['status']      = ['in', ['processing', 'complete', 'creditcard_proccessing']];
            $data['all_unit_price'] = @round(($v['zeelool_unit_price'] + $v['voogueme_unit_price'] + $v['nihao_unit_price']) / 3, 2);
            //zeelool的购物车总数
            $data['zeelool_shoppingcart_total'] =  $zeelool_shoppingcart_total = $zeelool_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
            //zeelool购物车转化率
            $data['zeelool_shoppingcart_conversion'] = $zeelool_shoppingcart_conversion = @round(($v['zeelool_sales_num'] / $zeelool_shoppingcart_total) * 100, 2);
            //zeelool的注册人数
            $data['zeelool_register_customer'] = $zeelool_register_customer = $zeelool_model->table('customer_entity')->where($date)->count('*');
            //voogueme的购物车总数
            $data['voogueme_shoppingcart_total'] =  $voogueme_shoppingcart_total = $voogueme_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
            //voogueme的购物车转化率
            $data['voogueme_shoppingcart_conversion'] = $voogueme_shoppingcart_conversion = @round(($v['voogueme_sales_num'] / $voogueme_shoppingcart_total) * 100, 2);
            //voogueme的注册人数
            $data['voogueme_register_customer'] = $voogueme_register_customer = $voogueme_model->table('customer_entity')->where($date)->count('*');
            //nihao的购物车总数
            $data['nihao_shoppingcart_total']   = $nihao_shoppingcart_total = $nihao_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
            //nihao的购物车转化率
            $data['nihao_shoppingcart_conversion'] = $nihao_shoppingcart_conversion = @round(($v['nihao_sales_num'] / $nihao_shoppingcart_total) * 100, 2);
            //nihao的注册人数
            $data['nihao_register_customer'] = $nihao_register_customer = $nihao_model->table('customer_entity')->where($date)->count('*');
            $data['all_shoppingcart_total']  = $zeelool_shoppingcart_total + $voogueme_shoppingcart_total + $nihao_shoppingcart_total;
            $data['all_shoppingcart_conversion'] = @round(($zeelool_shoppingcart_conversion + $voogueme_shoppingcart_conversion + $nihao_shoppingcart_conversion) / 3, 2);
            $data['all_register_customer']   = $zeelool_register_customer + $voogueme_register_customer + $nihao_register_customer;
            Db::name('order_statistics')->where(['id' => $v['id']])->update($data);
        }
        echo 'ok';
        die;
    }
    /**
     * 更新order_statistics表字段
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/11 17:11:21
     * @return void
     */
    public function get_sales_order_update_two()
    {
        $zeelool_model  = Db::connect('database.db_zeelool');
        $voogueme_model = Db::connect('database.db_voogueme');
        $nihao_model    = Db::connect('database.db_nihao');
        $zeelool_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $zeelool_model->table('customer_entity')->query("set time_zone='+8:00'");
        $voogueme_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $voogueme_model->table('customer_entity')->query("set time_zone='+8:00'");
        $nihao_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $nihao_model->table('customer_entity')->query("set time_zone='+8:00'");
        $where['zeelool_shoppingcart_update_total'] = 0;
        $result = Db::name('order_statistics')->where($where)->limit(1)->select();
        if (!$result) {
            echo 'ok2';
            exit;
        }
        $data = [];
        foreach ($result as $v) {
            $starttime          = $v['create_date'] . ' ' . '00:00:00';
            $endtime            = $v['create_date'] . ' ' . '23:59:59';
            $date['updated_at'] = ['between', [$starttime, $endtime]];
            //zeelool的购物车总数
            $data['zeelool_shoppingcart_update_total'] =  $zeelool_shoppingcart_update_total = $zeelool_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
            //zeelool购物车转化率
            $data['zeelool_shoppingcart_update_conversion'] = $zeelool_shoppingcart_update_conversion = @round(($v['zeelool_sales_num'] / $zeelool_shoppingcart_update_total) * 100, 2);
            //voogueme的购物车总数
            $data['voogueme_shoppingcart_update_total'] =  $voogueme_shoppingcart_update_total = $voogueme_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
            //voogueme的购物车转化率
            $data['voogueme_shoppingcart_update_conversion'] = $voogueme_shoppingcart_update_conversion = @round(($v['voogueme_sales_num'] / $voogueme_shoppingcart_update_total) * 100, 2);
            //nihao的购物车总数
            $data['nihao_shoppingcart_update_total']   = $nihao_shoppingcart_update_total = $nihao_model->table('sales_flat_quote')->where($date)->where('base_grand_total', 'GT', 0)->count('*');
            //nihao的购物车转化率
            $data['nihao_shoppingcart_update_conversion'] = $nihao_shoppingcart_update_conversion = @round(($v['nihao_sales_num'] / $nihao_shoppingcart_update_total) * 100, 2);
            $data['all_shoppingcart_update_total']  = $zeelool_shoppingcart_update_total + $voogueme_shoppingcart_update_total + $nihao_shoppingcart_update_total;
            $data['all_shoppingcart_update_conversion'] = @round(($zeelool_shoppingcart_update_conversion + $voogueme_shoppingcart_update_conversion + $nihao_shoppingcart_update_conversion) / 3, 2);
            Db::name('order_statistics')->where(['id' => $v['id']])->update($data);
        }
        echo 'ok';
        die;
    }
    /**
     * 计算镜架销售副数和配饰销售副数
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/20 17:15:36
     * @return void
     */
    public function calculate_order_item_num()
    {
        $platform = $this->request->get('platform');
        if (!$platform) {
            return 'error';
        }
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            default:
                $model = false;
                break;
        }
        if (false === $model) {
            return false;
        }
        $model->query("set time_zone='+8:00'");
        //计算前一天的销量
        $stime = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $etime = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $map['m.created_at'] =  ['between', [$stime, $etime]];
        $whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing','paypal_canceled_reversal','paypal_reversed')";
        //求出眼镜所有sku
        $frame_sku  = $this->itemplatformsku->getDifferencePlatformSku(1, $platform);
        //求出饰品的所有sku
        $decoration_sku = $this->itemplatformsku->getDifferencePlatformSku(3, $platform);
        //眼镜销售副数
        $data['frame_sales_num']            = $model->table('sales_flat_order_item m')->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')->where($whereItem)->where($map)->where('m.sku', 'in', $frame_sku)->count('*');
        //眼镜动销数
        $data['frame_in_print_num']         = $model->table('sales_flat_order_item m')->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')->where($whereItem)->where($map)->where('m.sku', 'in', $frame_sku)->count('distinct m.sku');
        //配饰的销售副数
        $data['decoration_sales_num']       = $model->table('sales_flat_order_item m')->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')->where($whereItem)->where($map)->where('m.sku', 'in', $decoration_sku)->count('*');
        //配饰动销数
        $data['decoration_in_print_num']    = $model->table('sales_flat_order_item m')->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')->where($whereItem)->where($map)->where('m.sku', 'in', $decoration_sku)->count('distinct m.sku');
        $data['platform']    = $platform;
        $data['create_date'] = date("Y-m-d", strtotime("-1 day"));
        $data['createtime']  = date("Y-m-d H:i:s");
        Db::name('order_item_info')->insert($data);
        echo 'ok';
        die;
    }

    /**
     * 每天定时获取库存数据
     *
     * @Description
     * @author wpl
     * @since 2020/04/27 09:48:05
     * @return void
     */
    public function get_stock_data()
    {
        //清空表
        Db::execute("truncate table fa_temp_stock;");

        //查询当天所有有库存的数据
        $map['item_status'] = 3;
        $map['is_open'] = 1;
        $map['is_del'] = 1;
        $map['available_stock'] = ['>', 0];
        $list = $this->item->field('sku,available_stock as stock')->where($map)->select();
        $list = collection($list)->toArray();
        foreach ($list as &$v) {
            $v['type'] = 1;
            $v['createtime'] = date('Y-m-d H:i:s');
        }
        unset($v);
        Db::table('fa_temp_stock')->insertAll($list);


        //查询当天所有无库存数据
        $map['available_stock'] = ['<=', 0];
        $info = $this->item->field('sku,available_stock as stock')->where($map)->select();
        $info = collection($info)->toArray();
        foreach ($info as &$v) {
            $v['type'] = 2;
            $v['createtime'] = date('Y-m-d H:i:s');
        }
        unset($v);
        Db::table('fa_temp_stock')->insertAll($info);
        echo 'ok';
    }

    /**
     * 定时统计SKU变化情况
     *
     * @Description
     * @author wpl
     * @since 2020/04/27 10:34:10
     * @return void
     */
    public function set_stock_change()
    {
        //查询
        $list = Db::table('fa_temp_stock')->select();

        //查询当天所有有库存的数据
        $map['item_status'] = 3;
        $map['is_open'] = 1;
        $map['is_del'] = 1;
        $res = $this->item->where($map)->column('available_stock as stock', 'sku');
        $info = [];
        foreach ($list as $k => $v) {
            //从有库存到无库存
            if ($v['type'] == 1 && $res[$v['sku']] <= 0) {
                $info[$k]['sku'] = $v['sku'];
                $info[$k]['type'] = 1;
                $info[$k]['change_num'] = $v['stock'] - $res[$v['sku']];
                $info[$k]['createtime'] = date('Y-m-d H:i:s', time());

                Db::table('fa_temp_stock')->where(['sku' => $v['sku']])->update([
                    'stock' => $res[$v['sku']],
                    'type'  => 2
                ]);
            }
            //从无到有
            if ($v['type'] == 2 && $res[$v['sku']] > 0) {
                $info[$k]['sku'] = $v['sku'];
                $info[$k]['type'] = 2;
                $info[$k]['change_num'] = $res[$v['sku']] - $v['stock'];
                $info[$k]['createtime'] = date('Y-m-d H:i:s', time());

                Db::table('fa_temp_stock')->where(['sku' => $v['sku']])->update([
                    'stock' => $res[$v['sku']],
                    'type'  => 1
                ]);
            }
        }

        Db::table('fa_goods_stock_change')->insertAll(array_values($info));
    }
    public function get_workload_data()
    {
        //求出平台
        $platform = $this->request->get('platform');
        if (!$platform) {
            return false;
        }
        $where['type'] = $whereComments['platform'] = $platform;
        $whereComments['author_id']    = ['neq', '382940274852'];
        $whereComments['is_admin']  = 1;
        //zendesk
        $zendesk_model = Db::name('zendesk');
        $zendesk_comments = Db::name('zendesk_comments');
        $zendesk_model->query("set time_zone='+8:00'");
        $zendesk_comments->query("set time_zone='+8:00'");
        //zendesk_comments
        //$zendesk_comments = Db::name('zendesk_comments');
        //计算前一天的销量
        $stime = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $etime = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $map['create_time'] = $date['c.create_time'] = $update['zendesk_update_time'] =  ['between', [$stime, $etime]];
        //获取昨天待处理的open、new量
        $wait_num = $zendesk_model->where($where)->where(['status' => ['in', '1,2'], 'channel' => ['neq', 'voice']])->count("*");
        //获取昨天新增的open、new量
        $increment_num = $zendesk_model->where($where)->where(['status' => ['in', '1,2'], 'channel' => ['neq', 'voice']])->where($update)->count("*");
        //获取昨天已回复量
        $reply_num  = $zendesk_comments->where($map)->where(['is_public' => 1])->where($whereComments)->count('*');
        //获取昨天待分配的open、new量
        $waiting_num = $zendesk_model->where($where)->where(['status' => ['in', '1,2'], 'channel' => ['neq', 'voice']])->where(['assign_id' => 0])->where($update)->count("*");
        //获取昨天的pendding量
        $pending_num = $zendesk_model->where($where)->where(['status' => ['eq', '3'], 'channel' => ['neq', 'voice']])->where($update)->count("*");
        $data['platform']       = $platform;
        $data['wait_num']       = $wait_num;
        $data['increment_num']  = $increment_num;
        $data['reply_num']      = $reply_num;
        $data['waiting_num']    = $waiting_num;
        $data['pending_num']    = $pending_num;
        $data['create_date'] = date("Y-m-d", strtotime("-1 day"));
        $data['createtime'] = date("Y-m-d H:i:s");
        Db::name('workload_statistics')->insert($data);
        echo 'ok';
        die;
    }
}
