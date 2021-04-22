<?php

namespace app\admin\model\web;

use think\Model;
use think\Log;

class WebUsers extends Model
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
     * 网站用户表同步 添加
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
                $params[$k]['entity_id'] = $v['entity_id'];
                $params[$k]['email'] = $v['email'];
                $params[$k]['site'] = $site;
                $params[$k]['group_id'] = $v['group_id'];
                $params[$k]['store_id'] = $v['store_id'];
                $params[$k]['created_at'] = strtotime($v['created_at']) + 28800;
                $params[$k]['updated_at'] = strtotime($v['updated_at']) + 28800;
                $params[$k]['resouce'] = $v['resouce'];
                $params[$k]['is_vip'] = $v['is_vip'];
            }
            (new WebUsers)->saveAll($params);
            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
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
                $params['email'] = $v['email'];
                $params['group_id'] = $v['group_id'];
                $params['store_id'] = $v['store_id'];
                $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                $params['resouce'] = $v['resouce'];
                $params['is_vip'] = $v['is_vip'];
                (new WebUsers)->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);
            }

            return true;

        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
        }
    }


    /**
     * 网站用户表同步 添加 - 批发站
     *
     * @param  array  $data  数据
     * @param  int  $site  站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public static function setInsertWeseeData($data = [], $site = null)
    {
        if (!$data) {
            return false;
        }

        try {
            $params = [];
            foreach ($data as $k => $v) {
                $params[$k]['entity_id'] = $v['id'];
                $params[$k]['email'] = $v['email'];
                $params[$k]['site'] = $site;
                $params[$k]['group_id'] = $v['group_id'];
                $params[$k]['store_id'] = $v['store_id'];
                $params[$k]['created_at'] = strtotime($v['created_at']) + 28800;
                $params[$k]['updated_at'] = strtotime($v['updated_at']) + 28800;
                $params[$k]['is_vip'] = $v['is_vip'];
            }
            (new WebUsers)->saveAll($params);

            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
        }
    }

    /**
     * 网站用户表同步 更新 - 批发站
     *
     * @param  array  $data  数据
     * @param  int  $site  站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public static function setUpdateWeseeData($data = [], $site = null)
    {
        if (!$data) {
            return false;
        }

        try {
            foreach ($data as $k => $v) {
                $params = [];
                $params['email'] = $v['email'];
                $params['group_id'] = $v['group_id'];
                $params['store_id'] = $v['store_id'];
                $params['updated_at'] = strtotime($v['updated_at']) + 28800;
                $params['is_vip'] = $v['is_vip'];
                (new WebUsers)->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);
            }

            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
        }
    }
}
