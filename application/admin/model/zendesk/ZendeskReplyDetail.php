<?php

namespace app\admin\model\zendesk;

use think\Model;


class ZendeskReplyDetail extends Model
{
    // 表名
    protected $name = 'zendesk_reply_detail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';


    // 追加属性
    protected $append = [

    ];
    

    







}
