<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/3/9 10:27
 * @Email: 646054215@qq.com
 */

namespace app\api\controller;


use app\admin\model\zendesk\ZendeskReply;
use think\Controller;
use app\admin\model\zendesk\ZendeskReplyDetail as Detail;
use Zendesk\API\HttpClient as ZendeskAPI;

//修改zendesk_detail数据库之前的数据
class ChangeZendesk extends Controller
{ //基本参数配置
    protected $subdomain = "zeelooloptical";
    protected $username = "complaint@zeelool.com";
    protected $token = "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B";
    public function index()
    {
        $detail = Detail::all();
        foreach($detail as $key => $val){
            //dump(strrpos($val->body,'Hi there'));die;
            //echo $val->body;die;
            if(strrpos($val->body,'Hi there') !== false){
                Detail::where('id',$val->id)->setField('is_admin',1);
                //dump($res);die;
            }
            if(!$val->body){
                Detail::where('id',$val->id)->setField('is_admin',1);
                //dump($res);die;
            }
        }
    }
    public function source()
    {
        $client = new ZendeskAPI($this->subdomain);
        $client->setAuth('basic', ['username' => $this->username, 'token' => $this->token]);
        $replys = ZendeskReply::where('source','')->select();
        //根据id获取类型
        foreach($replys as $reply){
            $ticket = $client->tickets()->find($reply->email_id);
            $source = $ticket->ticket->via->channel;
            $res = ZendeskReply::where('id',$reply->id)->setField('source',$source);
        }
        echo 'success';
    }
    public function tags()
    {
        $client = new ZendeskAPI($this->subdomain);
        $client->setAuth('basic', ['username' => $this->username, 'token' => $this->token]);
        $replys = ZendeskReply::where('tags','')->select();
        //根据id获取类型
        foreach($replys as $reply){
            $ticket = $client->tickets()->find($reply->email_id);
            $tags = join(',',$ticket->ticket->tags);
            $res = ZendeskReply::where('id',$reply->id)->setField('tags',$tags);
        }
        echo 'success';
    }

}