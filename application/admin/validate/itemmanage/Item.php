<?php

namespace app\admin\validate\itemmanage;

use think\Validate;

class Item extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'sku'=> 'unique:item',
        'name'=> 'unique:item'
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['sku','name'],
        'edit' => ['sku','name']
    ];
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'name_en' => __('Name_en')
        ];
        parent::__construct($rules, $message, $field);
    }
}
