<?php

namespace app\admin\model\web;

use think\Model;
use think\Log;

class WebGroup extends Model
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
     * 网站用户分组表 添加
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
                $params[$k]['group_id'] = $v['customer_group_id'];
                $params[$k]['customer_group_code'] = $v['customer_group_code'];
                $params[$k]['site'] = $site;
                $params[$k]['created_at'] = time();
                $params[$k]['updated_at'] = time();
            }
            (new WebGroup)->saveAll($params);
            return true;
        } catch (\Exception $e) {
            Log::record('WebGroup:'.$e->getMessage());
        }
    }

    /**
     * 网站用户分组表同步 更新
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
                $params[$k]['group_id'] = $v['customer_group_id'];
                $params[$k]['customer_group_code'] = $v['customer_group_code'];
                (new WebGroup)->where(['group_id' => $v['customer_group_id'], 'site' => $site])->update($params);
            }

            return true;

        } catch (\Exception $e) {
            Log::record('webGroup:'.$e->getMessage());
        }
    }
}
