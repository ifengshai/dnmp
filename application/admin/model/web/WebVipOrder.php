<?php

namespace app\admin\model\web;

use app\enum\Site;
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
    public static function setInsertData($data = [], $site = null)
    {
        if (!$data) {
            return false;
        }
        try {
            $params = [];
            foreach ($data as $k => $v) {
                $params[$k]['web_id'] = $v['id'];
                if ($site == Site::NIHAO) {
                    $params[$k]['customer_id'] = $v['user_id'] ?: 0;
                    $params[$k]['order_number'] = $v['order_no'] ?: '';
                    $params[$k]['order_amount'] = $v['actual_payment'] ?: 0;
                    $params[$k]['order_status'] = $v['status'] ?: 0;
                } else {
                    $params[$k]['customer_id'] = $v['customer_id'] ?: 0;
                    $params[$k]['order_number'] = $v['order_number'] ?: '';
                    $params[$k]['order_amount'] = $v['order_amount'] ?: 0;
                    $params[$k]['order_status'] = $v['order_status'] ?: 0;
                    $params[$k]['customer_email'] = $v['customer_email'] ?: '';
                    $params[$k]['order_type'] = $v['order_type'] ?: 0;
                    $params[$k]['paypal_token'] = $v['paypal_token'] ?: '';
                    $params[$k]['pay_status'] = $v['pay_status'] ?: 0;
                    $params[$k]['is_active_status'] = $v['is_active_status'] ?: 0;
                }

                $params[$k]['site'] = $site;
                $params[$k]['start_time'] = strtotime($v['start_time']) > 0 ? strtotime($v['start_time'])+86400 : 0;
                $params[$k]['end_time'] = strtotime($v['end_time']) > 0 ? strtotime($v['end_time'])+86400 : 0;
                $params[$k]['created_at'] = time();
                $params[$k]['updated_at'] = time();

                $params[$k]['country_id'] = $v['country_id'] ?: 0;
            }
            (new WebVipOrder)->saveAll($params);

            return true;
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
    public static function setUpdateData($data = [], $site = null)
    {
        if (!$data) {
            return false;
        }

        try {
            foreach ($data as $k => $v) {
                $params = [];
                if ($site == Site::NIHAO) {
                    $params[$k]['order_number'] = $v['order_no'] ?: '';
                    $params[$k]['order_amount'] = $v['actual_payment'] ?: 0;
                    $params[$k]['order_status'] = $v['status'] ?: 0;
                } else {
                    $params[$k]['order_number'] = $v['order_number'] ?: '';
                    $params[$k]['order_amount'] = $v['order_amount'] ?: 0;
                    $params[$k]['order_status'] = $v['order_status'] ?: 0;
                    $params[$k]['customer_email'] = $v['customer_email'] ?: '';
                    $params[$k]['order_type'] = $v['order_type'] ?: 0;
                    $params[$k]['paypal_token'] = $v['paypal_token'] ?: '';
                    $params[$k]['pay_status'] = $v['pay_status'] ?: 0;
                    $params[$k]['is_active_status'] = $v['is_active_status'] ?: 0;
                }
                $params[$k]['start_time'] = strtotime($v['start_time']) > 0 ? strtotime($v['start_time'])+86400 : 0;
                $params[$k]['end_time'] = strtotime($v['end_time']) > 0 ? strtotime($v['end_time'])+86400 : 0;
                $params[$k]['updated_at'] = time();

                $params[$k]['country_id'] = $v['country_id'] ?: 0;
                (new WebVipOrder)->where(['web_id' => $v['id'], 'site' => $site])->update($params);
            }

            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
        }
    }
}
