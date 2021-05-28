<?php

namespace app\admin\model\web;

use app\admin\controller\elasticsearch\async\AsyncCustomer;
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
                $params['entity_id'] = $v['entity_id'];
                $params['email'] = $v['email'] ?: '';
                $params['site'] = $site;
                $params['group_id'] = $v['group_id'] ?: 0;
                $params['store_id'] = $v['store_id'] ?: 0;
                $params['created_at'] = strtotime($v['created_at']);
                $params['updated_at'] = strtotime($v['updated_at']);
                $params['resouce'] = $v['resouce'] ?: 0;
                $params['is_vip'] = $v['is_vip'];
                $userId = (new WebUsers)->insertGetId($params);
                //新增用户信息
                (new AsyncCustomer())->runInsert($params, $userId);
            }

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
                $params['email'] = $v['email'] ?: '';
                $params['group_id'] = $v['group_id'] ?: 0;
                $params['store_id'] = $v['store_id'] ?: 0;
                $params['updated_at'] = strtotime($v['updated_at']);
                $params['resouce'] = $v['resouce'] ?: 0;
                $params['is_vip'] = $v['is_vip'] ?: 0;
                (new WebUsers())->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);

                $user = (new WebUsers())->where(['entity_id' => $v['entity_id'], 'site' => $site])->find();
                if ($user) {
                    //更新用户信息
                    (new AsyncCustomer())->runUpdate($user->toArray());
                }
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
                $params['entity_id'] = $v['id'];
                $params['email'] = $v['email'] ?: '';
                $params['site'] = $site;
                $params['group_id'] = $v['group_id'] ?: 0;
                $params['store_id'] = $v['store_id'] ?: 0;
                $params['created_at'] = strtotime($v['created_at']);
                $params['updated_at'] = strtotime($v['updated_at']);
                $params['is_vip'] = $v['is_vip'];
                $userId = (new WebUsers)->insertGetId($params);
                //新增用户信息
                (new AsyncCustomer())->runInsert($params, $userId);
            }

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
                $params['group_id'] = $v['group_id'] ?: 0;
                $params['store_id'] = $v['store_id'] ?: 0;
                $params['updated_at'] = strtotime($v['updated_at']);
                $params['is_vip'] = $v['is_vip'] ?: 0;

                (new WebUsers())->where(['entity_id' => $v['entity_id'], 'site' => $site])->update($params);

                $user = (new WebUsers())->where(['entity_id' => $v['entity_id'], 'site' => $site])->find();
                if ($user) {
                    //更新用户信息
                    (new AsyncCustomer())->runUpdate($user->toArray());
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:'.$e->getMessage());
        }
    }
}
