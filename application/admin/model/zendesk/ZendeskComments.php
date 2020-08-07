<?php

namespace app\admin\model\zendesk;

use app\admin\model\zendesk\ZendeskAgents;
use think\Db;
use think\Model;


class ZendeskComments extends Model
{
    // 表名
    protected $name = 'zendesk_comments';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [

    ];
    public function agent()
    {
        return $this->hasOne(ZendeskAgents::class,'admin_id','due_id');
    }
   

    

    







}
