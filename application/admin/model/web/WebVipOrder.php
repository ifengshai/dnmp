<?php

namespace app\admin\model\web;

use think\Model;
use think\Log;

class WebVipOrder extends Model
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
    public static function setInsertData($data = [], $site = null): bool
    {
        if (!$data) {
            return false;
        }
        try {
            $params = [];
            foreach ($data as $k => $v) {
                $params[$k]['web_id'] = $v['id'];
                $params[$k]['customer_id'] = $v['customer_id'];
                $params[$k]['customer_email'] = $v['customer_email'];
                $params[$k]['site'] = $site;
                $params[$k]['order_number'] = $v['order_number'];
                $params[$k]['order_amount'] = $v['order_amount'];
                $params[$k]['order_status'] = $v['order_status'];
                $params[$k]['order_type'] = $v['order_type'];
                $params[$k]['paypal_token'] = $v['paypal_token'];
                $params[$k]['start_time'] = strtotime($v['start_time']) + 28800;
                $params[$k]['end_time'] = strtotime($v['end_time']) + 28800;
                $params[$k]['is_active_status'] = $v['is_active_status'];
                $params[$k]['created_at'] = strtotime($v['created_at']) + 28800;
                $params[$k]['updated_at'] = strtotime($v['updated_at']) + 28800;
                $params[$k]['pay_status'] = $v['pay_status'];
                $params[$k]['country_id'] = $v['country_id'];
            }
            (new WebVipOrder)->saveAll($params);
        } catch (\Exception $e) {
            Log::record('webVipOrder:'.$e->getMessage());
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
    public static function setUpdateData($data = [], $site = null): bool
    {
        if (!$data) {
            return false;
        }

        try {
            foreach ($data as $k => $v) {
                $params = [];
                $params['customer_email'] = $v['customer_email'];
                $params['customer_id'] = $v['customer_id'];
                $params['order_number'] = $v['order_number'];
                $params['order_amount'] = $v['order_amount'];
                $params['order_status'] = $v['order_status'];
                $params['order_type'] = $v['order_type'];
                $params['paypal_token'] = $v['paypal_token'];
                $params['start_time'] = strtotime($v['start_time']) + 28800;
                $params['end_time'] = strtotime($v['end_time']) + 28800;
                $params['is_active_status'] = $v['is_active_status'];
                $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                $params['pay_status'] = $v['pay_status'];
                $params['country_id'] = $v['country_id'];
                (new WebVipOrder)->where(['web_id' => $v['id'], 'site' => $site])->update($params);
            }

        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
        }
    }
}
