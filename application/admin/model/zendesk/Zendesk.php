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
        'tag_format',
        'status_format',
        'username_format'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'due_id', 'id')->setEagerlyType(0)->joinType('left');
    }
    public function lastComment()
    {
        return $this->hasMany(ZendeskComments::class,'zid','id')->order('id','desc')->limit(1);
    }
    public function getStatusFormatAttr($value, $data)
    {
        return config('zendesk.status')[$data['status']];
    }
    public function getUsernameFormatAttr($value, $data)
    {
        return $data['username'].'——'.$data['email'];
    }
    public function getTagFormatAttr($value, $data)
    {
        $tagIds = $data['tags'];
        $tags = ZendeskTags::where('id','in',$tagIds)->column('name');
        return join(',',$tags);
    }
    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name'=>'我的全部','field'=>'me_task','value'=>1],
            ['name'=>'我的待处理','field'=>'me_task','value'=>2],
        ];
    }
}
