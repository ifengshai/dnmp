<?php

namespace app\admin\jobs;

use app\admin\model\order\order\NewOrder;
use think\Log;
use think\queue\Job;

class Order
{
    /**
     * fire方法是消息队列默认调用的方法
     *
     * @param Job         $job  当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        $order = new NewOrder();
        try {
            $order->where(['entity_id' => $data['entity_id'], 'site' => 10])
                ->update(['coupon_code' => $data['coupon_code'], 'coupon_rule_name' => $data['coupon_rule_name']]);
            echo $data['entity_id'] . '->success->' . PHP_EOL;
            $job->delete();
        } catch (\Throwable $throwable) {
            Log::error(__CLASS__ . $throwable->getMessage());
            $job->delete();
        }
    }
}