<?php

namespace Util;

use think\Db;

class ZeeloolPrescriptionDetailHelper
{

	/* 
	* 获取一个订单的处方明细 依据 entity_id 
	* 参数说明 订单 $entity_id = 1254
	*/
	public static function get_one_by_entity_id($entity_id)
	{
		if ($entity_id) {
			$querySql = "select sfo.is_new_version,sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.entity_id=$entity_id";
			$item_list = Db::connect('database.db_zeelool')->query($querySql);

			// 如果为空，则直接返回false
			if (empty($item_list)) {
				return false;
			}

			return self::list_convert($item_list);
		}

		return false;
	}

	/* 
	* 获取一个订单的处方明细 依据 increment_id
	* 参数说明 $increment_id = '400083065'
	*/
	public static function get_one_by_increment_id($increment_id)
	{

		// if($increment_id){

		// }

		if ($increment_id) {
			$querySql = "select sfo.is_new_version,sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfo.customer_email,sfo.customer_firstname,sfo.customer_lastname,sfo.store_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.increment_id='{$increment_id}'";
			$item_list = Db::connect('database.db_zeelool')->query($querySql);

			// 如果为空，则直接返回false
			if (empty($item_list)) {
				return false;
			}

			return self::list_convert($item_list);
		}

		return false;
	}

	/*
	* 获取订单列表的处方明细  依据 entity_ids
	* 参数说明 $entity_id = 1254,1235,45687
	*/
	public static function get_list_by_entity_ids($entity_id)
	{
		if ($entity_id) {
			$querySql = "select sfo.is_new_version,sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.entity_id in($entity_id)";
			$item_list = Db::connect('database.db_zeelool')->query($querySql);

			// 如果为空，则直接返回false
			if (empty($item_list)) {
				return false;
			}

			return self::list_convert($item_list);
		}
		return false;
	}

	/*
	* 获取订单列表的处方明细  依据 increment_ids
	* 参数说明 $increment_ids = " '400083065','100046454','400082960' "
	*/
	public static function get_list_by_increment_ids($increment_ids)
	{
		if ($increment_ids) {
			$increment_ids = rtrim($increment_ids, ',');
			$querySql = "select sfo.is_new_version,sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.increment_id in($increment_ids)";
			$item_list = Db::connect('database.db_zeelool')->query($querySql);

			// 如果为空，则直接返回false
			if (empty($item_list)) {
				return false;
			}

			return self::list_convert($item_list);
		}
		return false;
	}

	/*
	* 解析处方  依据 $item_list
	* 参数说明 $item_list 查询的结果列表
	*/
	protected function list_convert($item_list)
	{


		$items = array();
		foreach ($item_list as $item_key => $item_value) {

			$items[$item_key]['increment_id'] = $item_value['increment_id'];
			$items[$item_key]['status'] = $item_value['status'];
			$items[$item_key]['order_id'] = $item_value['order_id'];
			$items[$item_key]['item_id'] = $item_value['item_id'];
			$items[$item_key]['name'] = $item_value['name'];
			$items[$item_key]['sku'] = $item_value['sku'];
			$items[$item_key]['created_at'] = $item_value['created_at'];
			$items[$item_key]['qty_ordered'] = $item_value['qty_ordered'];
			$items[$item_key]['quote_item_id'] = $item_value['quote_item_id'];
			$items[$item_key]['discount_amount'] = $item_value['discount_amount'];
			$items[$item_key]['original_price'] = $item_value['original_price'];
			$product_options = unserialize($item_value['product_options']);
			//判断是否为新处方
			if ($item_value['is_new_version'] == 1) {
				$items[$item_key]['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coating_name'], 0, 100);
				$items[$item_key]['index_type'] = substr($product_options['info_buyRequest']['tmplens']['lens_data_name'], 0, 100);
				$items[$item_key]['index_price'] = $product_options['info_buyRequest']['tmplens']['lens_base_price'];
				$items[$item_key]['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coating_base_price'];
				$items[$item_key]['index_price_old'] = $product_options['info_buyRequest']['tmplens']['lens_base_price'];
				$items[$item_key]['index_name'] = $product_options['info_buyRequest']['tmplens']['lens_data_name'];
				$items[$item_key]['index_id'] = $product_options['info_buyRequest']['tmplens']['lens_id'];
				if ($product_options['info_buyRequest']['tmplens']['color_id']) {
					$items[$item_key]['index_type'] = $items[$item_key]['index_type'] . '-' . $product_options['info_buyRequest']['tmplens']['color_data_name'];
				} 
			} else {
				$items[$item_key]['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coatiing_name'], 0, 100);
				$items[$item_key]['index_type'] = substr($product_options['info_buyRequest']['tmplens']['index_type'], 0, 100);
				$items[$item_key]['index_price'] = $product_options['info_buyRequest']['tmplens']['index_price'];
				$items[$item_key]['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coatiing_price'];
				$items[$item_key]['index_price_old'] = $product_options['info_buyRequest']['tmplens']['index_price_old'];
				$items[$item_key]['index_name'] = $product_options['info_buyRequest']['tmplens']['index_name'];
				$items[$item_key]['index_id'] =  $product_options['info_buyRequest']['tmplens']['index_id'];
				if ($product_options['info_buyRequest']['tmplens']['color_name']) {
					$items[$item_key]['index_type'] = $items[$item_key]['index_type'] . '-' . $product_options['info_buyRequest']['tmplens']['color_name'];
				} 
			}
			$items[$item_key]['frame_price'] = $product_options['info_buyRequest']['tmplens']['frame_price'];
			//添加color-name 参数	
			$items[$item_key]['color_name']  = isset($product_options['info_buyRequest']['tmplens']['color_data_name']) ? $product_options['info_buyRequest']['tmplens']['color_data_name'] : '';
			$items[$item_key]['frame_regural_price'] = $product_options['info_buyRequest']['tmplens']['frame_regural_price'];
			$items[$item_key]['is_special_price'] = $product_options['info_buyRequest']['tmplens']['is_special_price'];
			$items[$item_key]['lens'] = $product_options['info_buyRequest']['tmplens']['lens'];
			$items[$item_key]['lens_old'] = $product_options['info_buyRequest']['tmplens']['lens_old'];
			$items[$item_key]['total'] =  $product_options['info_buyRequest']['tmplens']['total'];
			$items[$item_key]['total_old'] = $product_options['info_buyRequest']['tmplens']['total_old'];
			$items[$item_key]['options']  = $product_options['options'];
			$items[$item_key]['cart_currency'] = $product_options['info_buyRequest']['cart_currency'];
			$items[$item_key]['coating_id'] = $product_options['info_buyRequest']['tmplens']['coating_id'];
			$items[$item_key]['color_id'] = $product_options['info_buyRequest']['tmplens']['color_id'];

			$prescription_params = $product_options['info_buyRequest']['tmplens']['prescription'];
			$prescription_params = explode("&", $prescription_params);
			$lens_params = array();
			foreach ($prescription_params as $key => $value) {
				$arr_value = explode("=", $value);
				$lens_params[$arr_value[0]] = $arr_value[1];
				$items[$item_key][$arr_value[0]] = $arr_value[1];
			}
			
			

			$items[$item_key]['year'] = $product_options['info_buyRequest']['tmplens']['year'] ? $product_options['info_buyRequest']['tmplens']['year'] : '';
			$items[$item_key]['month'] = $product_options['info_buyRequest']['tmplens']['month'] ? $product_options['info_buyRequest']['tmplens']['month'] : '';

			$items[$item_key]['information'] = str_replace("+", " ", urldecode(urldecode($product_options['info_buyRequest']['tmplens']['information'])));

			$items[$item_key]['os_add'] = urldecode($items[$item_key]['os_add']);
            $items[$item_key]['od_add'] = urldecode($items[$item_key]['od_add']);
			//判断双ADD还是单ADD
			if ($items[$item_key]['os_add'] && $items[$item_key]['od_add'] && $items[$item_key]['os_add'] * 1 != 0 && $items[$item_key]['od_add'] * 1 != 0) {
				//如果新处方add 对调 因为旧处方add左右眼颠倒
				if ($item_value['is_new_version'] == 1) {
					$items[$item_key]['os_add'] = $lens_params['od_add'];
					$items[$item_key]['od_add'] = $lens_params['os_add'];
				} else {
					$items[$item_key]['os_add'] = $lens_params['os_add'];
					$items[$item_key]['od_add'] = $lens_params['od_add'];
				}
			} else {
				if ($items[$item_key]['od_add'] && $lens_params['od_add']*1 != 0) {
					$items[$item_key]['total_add'] = $lens_params['od_add'];
				} else {
					$items[$item_key]['total_add'] = $lens_params['os_add'];
				}
			}

			//添加上客户邮箱,客户姓名
			if (isset($item_value['customer_email'])) {
				$items[$item_key]['customer_email'] = $item_value['customer_email'];
			}
			if (isset($item_value['customer_firstname'])) {
				$items[$item_key]['customer_firstname'] = $item_value['customer_firstname'];
			}
			if (isset($item_value['customer_lastname'])) {
				$items[$item_key]['customer_lastname']  = $item_value['customer_lastname'];
			}
			//添加上订单来源
			if (isset($item_value['store_id'])) {
				$items[$item_key]['store_id'] = $item_value['store_id'];
			}
		}
	
		return $items;
	}
}
