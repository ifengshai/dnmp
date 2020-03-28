<?php

namespace app\admin\model\zendesk;

use app\admin\model\Admin;
use think\Model;


class Zendesk extends Model
{


    // 表名
    protected $name = 'zendesk';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [
        'tag_format'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'due_id', 'id')->setEagerlyType(0)->joinType('left');
    }
    public function lastComment()
    {
        return $this->hasMany(ZendeskComments::class,'zid','id')->order('id','desc')->limit(1);
    }
    public function getTagFormatAttr($value, $data)
    {
        $tagIds = $data['tags'];
        $tags = ZendeskTags::where('id','in',$tagIds)->column('name');
        return join(',',$tags);
    }
}
