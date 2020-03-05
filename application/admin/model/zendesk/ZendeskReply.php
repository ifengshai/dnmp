<?php

namespace app\admin\model\zendesk;

use think\Model;


class ZendeskReply extends Model
{
    // 表名
    protected $name = 'zendesk_reply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';


    // 追加属性
    protected $append = [

    ];
    

    







}
