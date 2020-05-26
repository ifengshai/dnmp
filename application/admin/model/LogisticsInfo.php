<?php

namespace app\admin\model;

use think\Model;


class LogisticsInfo extends Model
{
    // 表名
    protected $name = 'logistics_info';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 添加物流单信息
     */
    public function addLogisticsInfo($params)
    {
        $params['createtime'] = date('Y-m-d H:i:s', time());
        $params['create_person'] = session('admin.nickname');
        return $this->allowField(true)->isUpdate(false)->data($params)->save();
    }
}
