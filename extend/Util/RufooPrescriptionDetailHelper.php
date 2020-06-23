<?php

namespace Util;

use think\Db;

class RufooPrescriptionDetailHelper
{

	/* 
	* 获取一个订单的处方明细 依据 entity_id 
	* 参数说明 订单 $entity_id = 1254
	*/
	public static function get_one_by_entity_id($entity_id)
	{
		if (!$entity_id) {
			return false;
		}

		$model = new \app\admin\model\order\printlabel\Rufoo;
		$map['a.id'] = $entity_id;
		$item_list = $model->field('sku,b.total,optionname,lens_data,a.createtime,ordersn,a.status,a.price,a.dispatchprice,c.title,b.unitprice')->where($map)->alias('a')
			->join(['ims_ewei_shop_order_goods' => 'b'], 'a.id=b.orderid', 'left')
			->join(['ims_ewei_shop_goods' => 'c'], 'b.goodsid=c.id', 'left')
			->select();
		if ($item_list) {
			$item_list = collection($item_list)->toArray();
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

		if ($increment_id) {
			$querySql = "select sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfo.customer_email,sfo.customer_firstname,sfo.store_id,sfo.customer_lastname,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.increment_id='{$increment_id}'";
			$item_list = Db::connect('database.db_meeloog')->query($querySql);

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
			$querySql = "select sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.entity_id in($entity_id)";
			$item_list = Db::connect('database.db_meeloog')->query($querySql);

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
			$querySql = "select sfoi.original_price,sfoi.discount_amount,sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfoi.name,sfo.created_at
			from sales_flat_order_item sfoi
			left join sales_flat_order sfo on sfoi.order_id=sfo.entity_id 
			where sfo.increment_id in($increment_ids)";
			$item_list = Db::connect('database.db_meeloog')->query($querySql);

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

			$items[$item_key]['ordersn'] = $item_value['ordersn'];
			$items[$item_key]['status'] = $item_value['status'];
			$items[$item_key]['name'] = $item_value['title'];
			$items[$item_key]['sku'] = $item_value['sku'];
			$items[$item_key]['created_at'] = $item_value['createtime'];
			$items[$item_key]['qty_ordered'] = $item_value['total'];
			$product_options = json_decode($item_value['lens_data'], true);
			$final_params = array();
			$final_params['index_type'] = $item_value['optionname']; //镜片名称
			$final_params['unitprice'] = $item_value['unitprice']; //单价
			$final_params['price'] = $item_value['price']; //订单总价
			$final_params['index_price'] = $product_options['info_buyRequest']['tmplens']['index_price'];
			$final_params['coatiing_price'] = $product_options['info_buyRequest']['tmplens']['coatiing_price'];

			$items[$item_key]['index_name'] = $final_params['index_name'] = $item_value['optionname'];
			$items[$item_key]['lens'] = $final_params['lens'] =  $item_value['optionname'];

			if ($product_options) {
				$final_params = array_merge($product_options, $final_params);
			}
			
			$items[$item_key]['coatiing_name'] = $final_params['coatiing_name'];
			$items[$item_key]['index_type'] = $final_params['index_type'];
			$items[$item_key]['prescription_type'] = $final_params['type'];

			$items[$item_key]['frame_price'] = $final_params['frame_price'] ? $final_params['frame_price'] : 0;
			$items[$item_key]['index_price'] = $final_params['index_price'] ? $final_params['index_price'] : 0;
			$items[$item_key]['coatiing_price'] = $final_params['coatiing_price'] ? $final_params['coatiing_price'] : 0;

			$items[$item_key]['year'] = $final_params['year'] ? $final_params['year'] : '';
			$items[$item_key]['month'] = $final_params['month'] ? $final_params['month'] : '';

			$items[$item_key]['information'] = str_replace("+", " ", urldecode(urldecode($final_params['information'])));

			$items[$item_key]['od_sph'] = $final_params['od_sph'];
			$items[$item_key]['os_sph'] = $final_params['os_sph'];

			$items[$item_key]['od_cyl'] = $final_params['od_cyl'];
			$items[$item_key]['os_cyl'] = $final_params['os_cyl'];

			$items[$item_key]['od_axis'] = $final_params['od_axis'];
			$items[$item_key]['os_axis'] = $final_params['os_axis'];
			$items[$item_key]['pdcheck'] = $final_params['pdcheck'];

			if ($final_params['os_add'] && $final_params['od_add']) {
				$items[$item_key]['os_add'] = $final_params['os_add'];
				$items[$item_key]['od_add'] = $final_params['od_add'];
			} else {
				$items[$item_key]['total_add'] = $final_params['os_add'];
			}

			if ($final_params['pdcheck'] == 'on') {
				$items[$item_key]['pd_l'] = $final_params['pd_l'];
				$items[$item_key]['pd_r'] = $final_params['pd_r'];
			} else {
				$items[$item_key]['pd'] = $final_params['pd'];
			}

			if ($final_params['prismcheck'] == 'on') {
				$items[$item_key]['prismcheck'] = $final_params['prismcheck'];
				$items[$item_key]['od_pv'] = $final_params['od_pv'];
				$items[$item_key]['od_bd'] = $final_params['od_bd'];
				$items[$item_key]['od_pv_r'] = $final_params['od_pv_r'];
				$items[$item_key]['od_bd_r'] = $final_params['od_bd_r'];

				$items[$item_key]['os_pv'] = $final_params['os_pv'];
				$items[$item_key]['os_bd'] = $final_params['os_bd'];
				$items[$item_key]['os_pv_r'] = $final_params['os_pv_r'];
				$items[$item_key]['os_bd_r'] = $final_params['os_bd_r'];
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
