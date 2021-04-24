<?php

namespace app\admin\model\web;

use app\admin\controller\elasticsearch\async\AsyncCart;
use think\Model;
use think\Log;

class WebShoppingCart extends Model
{
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 网站VIP订单表同步 添加
     *
     * @param  array  $data  数据
     * @param  int  $site  站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public static function setInsertData($data = [], $site = null)
    {
        if (!$data) {
            return false;
        }
        try {
            $params = [];
            foreach ($data as $k => $v) {
                $params['entity_id'] = $v['entity_id'];
                $params['store_id'] = $v['store_id'];
                $params['is_active'] = $v['is_active'];
                $params['site'] = $site;
                $params['items_count'] = $v['items_count'];
                $params['items_qty'] = $v['items_qty'];
                $params['base_currency_code'] = $v['base_currency_code'];
                $params['quote_currency_code'] = $v['quote_currency_code'];
                $params['grand_total'] = $v['grand_total'];
                $params['base_grand_total'] = $v['base_grand_total'];
                $params['customer_id'] = $v['customer_id'];
                $params['customer_email'] = $v['customer_email'];
                $params['created_at'] = strtotime($v['created_at']) + 28800;
                $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                $cartId = (new WebShoppingCart())->insertGetId($params);
                (new AsyncCart())->runInsert($params,$cartId);

            }

            return true;
        } catch (\Exception $e) {
            Log::record('webShoppingCart:'.$e->getMessage());
        }
    }

    /**
     * 网站用户表同步 更新
     *
     * @param  array  $data  数据
     * @param  int  $site  站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public static function setUpdateData($data = [], $site = null)
    {
        if (!$data) {
            return false;
        }

        try {
            foreach ($data as $k => $v) {
                $params = [];
                $params['store_id'] = $v['store_id'];
                $params['is_active'] = $v['is_active'];
                $params['items_count'] = $v['items_count'];
                $params['items_qty'] = $v['items_qty'];
                $params['base_currency_code'] = $v['base_currency_code'];
                $params['quote_currency_code'] = $v['quote_currency_code'];
                $params['grand_total'] = $v['grand_total'];
                $params['base_grand_total'] = $v['base_grand_total'];
                $params['customer_id'] = $v['customer_id'];
                $params['customer_email'] = $v['customer_email'];
                $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                $cart = (new WebShoppingCart())->where(['entity_id' => $v['entity_id'], 'site' => $site])->find();
                $cart->save($params);
                (new AsyncCart())->runUpdate($params,$cart->id);
            }

            return true;
        } catch (\Exception $e) {
            Log::record('webShoppingCart:'.$e->getMessage());
        }
    }
}
