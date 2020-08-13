<?php

namespace app\admin\model\zendesk;

use think\Model;
use function Stringy\create as s;


class ZendeskReply extends Model
{
    // 表名
    protected $name = 'zendesk_reply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $preg_word = ['deliver','delivery','receive','track','ship','shipping','tracking','status','shipment','where','where is','find','update','eta','expected'];


    // 追加属性
    protected $append = [

    ];
    public function details()
    {
        return $this->hasMany(ZendeskReplyDetail::class,'reply_id',id);
    }
    public function getKeyPregAttr($value,$data)
    {
        $body = $data['body'];
        $res = [];
        foreach($this->preg_word as $key){
            if(s($body)->contains($key,false)){
                $res[] = $key;
            }
        }
        return join(',',$res);
    }
    

    







}
