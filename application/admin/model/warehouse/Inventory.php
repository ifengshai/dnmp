<?php

namespace app\admin\model\warehouse;

use think\Model;


class Inventory extends Model
{





    // 表名
    protected $name = 'inventory_list';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];

    public function Inventoryone()
    {
        return $this->belongsTo('Inventory', 'id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function Inventoryitemtwo()
    {
        return $this->belongsTo('InventoryItem', 'id', 'inventory_id', [], 'LEFT')->setEagerlyType(0);
    }
    public function getStatusList()
    {
        return ['0' => __('待盘点'), '1' => __('盘点中'), '2' => __('已完成')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

//    public function InventoryItem()
//    {
//        return $this->hasMany('InventoryItem', 'inventory_id');
//    }
}
