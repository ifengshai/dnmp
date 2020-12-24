<?php

namespace app\admin\model;

use think\Model;

class InterfaceTimeLog extends Model
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
     * 记录接口访问时间
     *
     * @param array $save_data  保存数据集合
     * @author lzh
     * @return bool
     */
    public function record($save_data)
    {
        $this->allowField(true)->saveAll($save_data);
    }
}
