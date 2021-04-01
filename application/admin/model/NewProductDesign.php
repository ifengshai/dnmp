<?php

namespace app\admin\model;

use think\Model;


class NewProductDesign extends Model
{

    

    

    // 表名
    protected $name = 'new_product_design';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => '代录尺寸', 'field' => 'label', 'value' => 1],
            ['name' => '待拍摄', 'field' => 'label', 'value' => 2],
            ['name' => '拍摄中', 'field' => 'label', 'value' => 3],
            ['name' => '待分配', 'field' => 'label', 'value' => 4],
            ['name' => '待修图', 'field' => 'label', 'value' => 5],
            ['name' => '修图中', 'field' => 'label', 'value' => 6],
            ['name' => '待审核', 'field' => 'label', 'value' => 7],

        ];
    }
}
