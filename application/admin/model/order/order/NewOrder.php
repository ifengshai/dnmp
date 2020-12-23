<?php

namespace app\admin\model\order\order;

use think\Model;

class NewOrder extends Model
{
    //数据库
    protected $connection = 'database.db_mojing_order';

    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => 'Zeelool', 'field' => 'site', 'value' => 1],
            ['name' => 'Voogueme', 'field' => 'site', 'value' => 2],
            ['name' => 'Nihao', 'field' => 'site', 'value' => 3],
            ['name' => 'Meeloog', 'field' => 'site', 'value' => 4],
            ['name' => 'Wesee', 'field' => 'site', 'value' => 5],
            ['name' => 'Zeelool_es', 'field' => 'site', 'value' => 9],
            ['name' => 'Zeelool_de', 'field' => 'site', 'value' => 10],
            ['name' => 'Zeelool_jp', 'field' => 'site', 'value' => 11],
        ];
    }
}
