<?php


namespace app\admin\model\itemmanage;


use think\Model;

class ItemAttribute extends Model
{
    //制定数据库连接
    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'item_attribute';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    public function itemAttribute()
    {
        return $this->hasOne('app\admin\model\itemmanage\attribute\ItemAttribute', 'item_id', 'id');
    }

    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? $value : $value);
    }
}