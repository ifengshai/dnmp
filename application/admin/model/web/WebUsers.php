<?php

namespace app\admin\model\web;

use app\admin\controller\elasticsearch\async\AsyncCustomer;
use app\enum\Site;
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
     * @param array    $data 数据
     * @param int|null $site 站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public static function setInsertData(array $data = [], int $site = null): bool
    {
        if (!$data) {
            return false;
        }
        try {
            $params = [];
            foreach ($data as $k => $v) {
                $params['email'] = $v['email'] ?: '';
                if ($site == Site::NIHAO) {
                    $params['entity_id'] = $v['id'];
                    $params['name'] = $v['name'];
                    $params['lastname'] = $v['lastname'];
                    $params['firstname'] = $v['firstname'];
                    $params['is_vip'] = $v['group'];
                } elseif ($site == Site::WESEEOPTICAL) {
                    $params['entity_id'] = $v['id'];
                } else {
                    $params['entity_id'] = $v['entity_id'];
                    $params['is_vip'] = $v['is_vip'];
                    $params['group_id'] = $v['group_id'] ?: 0;
                    $params['store_id'] = $v['store_id'] ?: 0;
                    $params['resouce'] = $v['resouce'] ?: 0;
                }
                $params['site'] = $site;
                $params['created_at'] = strtotime($v['created_at']);
                $params['updated_at'] = strtotime($v['updated_at']);
                $userId = (new WebUsers)->insertGetId($params);
                //新增用户信息
                (new AsyncCustomer())->runInsert($params, $userId);
            }

            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:' . $e->getMessage());
        }
    }

    /**
     * 网站用户表同步 更新
     *
     * @param array    $data 数据
     * @param int|null $site 站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public static function setUpdateData(array $data = [], int $site = null): bool
    {
        if (!$data) {
            return false;
        }

        try {
            foreach ($data as $k => $v) {
                $params = [];
                $params['email'] = $v['email'] ?: '';
                if ($site == Site::NIHAO) {
                    $params['name'] = $v['name'];
                    $params['lastname'] = $v['lastname'];
                    $params['firstname'] = $v['firstname'];
                    $params['is_vip'] = $v['group'];
                    $id = $v['id'];
                } elseif ($site == Site::WESEEOPTICAL) {
                    $id = $v['id'];
                } else {
                    $params['is_vip'] = $v['is_vip'];
                    $params['group_id'] = $v['group_id'] ?: 0;
                    $params['store_id'] = $v['store_id'] ?: 0;
                    $params['resouce'] = $v['resouce'] ?: 0;
                    $id = $v['entity_id'];
                }

                $params['updated_at'] = strtotime($v['updated_at']);
                (new WebUsers())->where(['entity_id' => $id, 'site' => $site])->update($params);

                $user = (new WebUsers())->where(['entity_id' => $id, 'site' => $site])->find();
                if ($user) {
                    //更新用户信息
                    (new AsyncCustomer())->runUpdate($user->toArray());
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::record('webUsers:' . $e->getMessage());
        }
    }


    /**
     * 网站用户表同步 添加 - 批发站
     *
     * @param array    $data 数据
     * @param int|null $site 站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public
    static function setInsertWeseeData(
        array $data = [],
        int $site = null
    ): bool {
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
            Log::record('webUsers:' . $e->getMessage());
        }
    }

    /**
     * 网站用户表同步 更新 - 批发站
     *
     * @param array    $data 数据
     * @param int|null $site 站点
     *
     * @return bool
     * @author wpl
     * @date   2021/4/15 9:30
     */
    public
    static function setUpdateWeseeData(
        array $data = [],
        int $site = null
    ): bool {
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

                (new WebUsers())->where(['entity_id' => $v['id'], 'site' => $site])->update($params);

                $user = (new WebUsers())->where(['entity_id' => $v['id'], 'site' => $site])->find();
                if ($user) {
                    //更新用户信息
                    (new AsyncCustomer())->runUpdate($user->toArray());
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::record('webUsers:' . $e->getMessage());
        }
    }
}
