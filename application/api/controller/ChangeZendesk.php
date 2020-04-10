<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/3/9 10:27
 * @Email: 646054215@qq.com
 */

namespace app\api\controller;


use app\admin\model\zendesk\Zendesk;
use app\admin\model\zendesk\ZendeskComments;
use app\admin\model\zendesk\ZendeskReply;
use think\Controller;
use app\admin\model\zendesk\ZendeskReplyDetail as Detail;
use think\Db;
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
    //同步测试站和正式站的数据
    public function asycTicket()
    {
        $database = [
            // 数据库类型
            'type'        => 'mysql',
            // 服务器地址
            'hostname'    => '127.0.0.1',
            // 数据库名
            'database'    => 'mojing_test',
            // 数据库用户名
            'username'    => 'mojing_test',
            // 数据库密码
            'password'    => 'rmDdHj75ti8PR3M5',
            // 数据库连接端口
            'hostport'    => '3306',
            // 数据库编码默认采用utf8
            'charset'     => 'utf8',
            // 数据库表前缀
            'prefix'      => 'fa_',
        ];
        $db = Db::connect($database);
        $tickets = $db->name('zendesk')->where('id','>',10751)->limit(1)->select();
        foreach($tickets as $ticket){
            if(Zendesk::where('ticket_id',$ticket->id)->find()){
                continue;
            }
            $data = collection($ticket)->toArray();
            $zid = $data['id'];
            unset($data['id']);
            $zendesk = Zendesk::create($data);
            $comments = $db->name('zendeskComments')->where('zid',$zid)->select();
            foreach($comments as $comment){
                $commentData = collection($comment)->toArray();
                unset($commentData['id']);
                $commentData['zid'] = $zendesk->id;
                ZendeskComments::create($commentData);
            }
            sleep(1);
        }
    }
}