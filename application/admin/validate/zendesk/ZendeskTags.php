<?php

namespace app\admin\validate\zendesk;

use think\Validate;

class ZendeskTags extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require|unique:zendesk_tags'
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'name.require'  =>  '标签名必填',
        'name.unique' =>  '标签名已存在',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['name'],
        'edit' => ['name'],
    ];
    
}
