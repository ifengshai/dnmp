<?php

namespace app\admin\validate\itemmanage\attribute;

use think\Validate;

class ItemAttributeProperty extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name_en'=> 'unique:item_attribute_property'
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
        'add'  => ['name_en'],
        'edit' => ['']
    ];
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'name_en' => __('Name_en')
        ];
        parent::__construct($rules, $message, $field);
    }
    
}
