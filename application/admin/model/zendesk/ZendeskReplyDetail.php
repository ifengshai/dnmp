<?php

namespace app\admin\model\zendesk;

use think\Model;
use function Stringy\create as s;


class ZendeskReplyDetail extends Model
{
    // 表名
    protected $name = 'zendesk_reply_detail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';


    // 追加属性
    protected $append = [

    ];
    protected $auto_answer = [
        'order status',
        'change information',
        'others'
    ];
    public function getKeyPregAttr($value,$data)
    {
        $body = $data['body'];
        foreach($this->auto_answer as $key){
            if(s($body)->contains($key,false)){
                return $key;
            }
        }
    }
    

    







}
