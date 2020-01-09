<?php

namespace app\admin\model;

use think\Model;


class OrderLog extends Model
{
    // 表名
    protected $name = 'order_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 添加订单操作日志
     */
    public function setOrderLog($data)
    {
        if ($data) {
            $data['create_person'] = session('admin.nickname');
            $data['createtime'] = date('Y-m-d H:i:s');
            return $this->allowField(true)->save($data);
        }
        return false;
    }

    
}
