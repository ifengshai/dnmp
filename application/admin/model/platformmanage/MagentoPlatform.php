<?php

namespace app\admin\model\platformManage;

use think\Model;


class MagentoPlatform extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'magento_platform';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 禁用还是启用的状态
     */
    public function getPlatformStatus()
    {
        return [1 => '启用', 2 => '禁用'];
    }
    /***
     * 是否需要上传商品信息到平台
     */
    public function getPlatformIsUpload()
    {
        return [1 => '上传', 2 => '不上传'];
    }
    /***
     * @return array
     */
    public function getOrderPlatformList()
    {
        $result = $this->where('status', '=', 1)->field('id,name')->select();
        if (!$result) {
            return [0 => '请先添加平台'];
        }
        $arr = [];
        foreach ($result as $key => $val) {
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }
    /**
     * 根据条件获取平台
     *
     * @Description
     * @author lsw
     * @since 2020/06/02 15:22:31 
     * @return void
     */
    public function getNewOrderPlatformList($arr)
    {
        $result = $this->where('status', '=', 1)->where('id','in',$arr)->field('id,name')->select();
        if (!$result) {
            return [0 => '请先添加平台'];
        }
        $arr = [];
        foreach ($result as $key => $val) {
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }
    /**
     * 求出所有的对接平台
     */
    public function magentoPlatformList()
    {
        $where['status'] = 1;
        $where['is_del'] = 1;
        $where['is_upload_item'] = 1;
        $result = $this->where($where)->field('id,magento_account,magento_key,name')->select();
        return $result ? $result : false;
    }


    /**
     * 获取对应平台id
     */
    public function getMagentoPlatform($name = '')
    {
        $where['status'] = 1;
        $where['is_del'] = 1;
        $where['name'] = $name;
        $id = $this->where($where)->value('id');
        return $id ? $id : false;
    }

    /**
     * 获取对应平台id
     */
    public function getMagentoPrefix($id = '')
    {
        $where['status'] = 1;
        $where['is_del'] = 1;
        $where['id'] = $id;
        $name = $this->where($where)->value('prefix');
        return $name ? $name : false;
    }
}
