<?php

namespace app\admin\model;

use think\Model;
use think\Db;


class StockLog extends Model
{
    // 表名
    protected $name = 'stock_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 插入日志表
     *
     * @Description
     * @author wpl
     * @since 2020/06/15 10:38:28 
     * @param [type] $params
     * @return void
     */
    public function setData($params)
    {
        if (!$params) {
            return false;
        }
        return $this->allowField(true)->save($params);
    }

}
