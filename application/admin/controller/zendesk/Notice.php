<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/3/25 16:54
 * @Email: 646054215@qq.com
 */

namespace app\admin\controller\zendesk;


use think\Controller;
use think\Db;
use think\Exception;
use Zendesk\API\HttpClient as ZendeskAPI;
use app\admin\model\zendesk\Zendesk;
use app\admin\model\zendesk\ZendeskComments;

/**
 * 通知方法
 * Class Notice
 * @package app\admin\controller\zendesk
 */
class Notice extends Controller
{
    public $postData = [];
    /**
     * 方法初始化
     * @throws \Exception
     */
    public function __construct($request = null ,$postData = [])
    {
        parent::__construct();
        if(!$postData){
            $postData = json_decode(file_get_contents("php://input"), true);
        }
        $this->postData = $postData;
        try {
            if($this->postData['type'] == 'voogueme'){
                $this->client = new ZendeskAPI(config('zendesk.voogueme')['subdomain']);
                $this->client->setAuth('basic', ['username' => config('zendesk.voogueme')['username'], 'token' => config('zendesk.voogueme')['token']]);
            }else{
                $this->client = new ZendeskAPI(config('zendesk.zeelool')['subdomain']);
                $this->client->setAuth('basic', ['username' => config('zendesk.zeelool')['username'], 'token' => config('zendesk.zeelool')['token']]);
            }

        } catch (\Exception $e) {
            exception('zendesk链接失败', 100006);
        }
    }

    /**
     * 获取到新增的通知
     */
    public function create()
    {
        $postData = $this->postData;
        $id = $postData['id'];
        $type = $postData['type'];
        if($type == 'zeelool'){
            $type = 1;
        }else{
            $type = 2;
        }
        //最后一条评论
        $comment = $this->getComments($id);
        $ticket = $this->getTicket($id)->ticket;
        $via = $ticket->via;
        $priority = 0;
        if($ticket->priority){
            $priority = array_search($ticket->priority, config('zendesk.priority'));
        }
        //开始插入相关数据
        //开启事务
        Db::startTrans();
        try {
            //写入主表
            $zendesk = Zendesk::create([
                'ticket_id' => $id,
                'type' => $type,
                'channel' => $via->channel,
                'email' => $via->source->from->address,
                'username' => $via->source->from->name,
                'user_id' => $ticket->requester_id,
                'to_email' => $via->source->to->address,
                'priority' => $priority,
                'status' => array_search($ticket->status, config('zendesk.status')),
                'tags' => '',
                'subject' => $ticket->subject,
                'raw_subject' => $ticket->raw_subject,
                'assignee_id' => $ticket->assignee_id ?: 0,
                'assign_id' => 1,
            ]);
            //获取所有的附件
            $attachments = [];
            if($comment->attachments){
                foreach($comment->attachments as $attachment)
                {
                    $attachments[] = $attachment->content_url;
                }
            }
            ZendeskComments::create([
                'ticket_id' => $id,
                'zid' => $zendesk->getLastInsID(),
                'author_id' => $comment->author_id,
                'body' => $comment->body,
                'html_body' => $comment->html_body,
                'is_public' => $comment->public ? 1 : 2,
                'is_admin' => 0,
                'attachments' => json($attachments)
            ]);
            Db::commit();
            //写入附表
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(),'');
        }
    }
    /**
     * 获取修改的通知
     */
    public function update()
    {
        $postData = $this->postData;
        $id = $postData['id'];
        //最后一条评论
        $comment = $this->getComments($id);
        $ticket = $this->getTicket($id);
        //开始插入相关数据
        $tags = $ticket->tags;
        $tags = join(',',\app\admin\model\zendesk\ZendeskTags::where('name','in',$tags)->column('id'));
        //开启事务
        Db::startTrans();
        try {
            $zendesk = Zendesk::where('ticket_id',$id)->find();
            //更新主表,目前应该只会更新status，其他不会更新
            Zendesk::update([
                'tags' => $tags,
                'status' => array_search($ticket->status, config('zendesk.status')),
            ],['id' => $zendesk->id]);
            //写入附表
            //获取所有的附件
            $attachments = [];
            if($comment->attachments){
                foreach($comment->attachments as $attachment)
                {
                    $attachments[] = $attachment->content_url;
                }
            }
            ZendeskComments::create([
                'ticket_id' => $id,
                'zid' => $zendesk->id,
                'author_id' => $comment->author_id,
                'body' => $comment->body,
                'html_body' => $comment->html_body,
                'is_public' => $comment->public ? 1 : 2,
                'is_admin' => 0,
                'attachments' => json($attachments)
            ]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            //写入日志
        }
    }
    /**
     * 根据id获取ticket
     * @param $id
     * @return mixed
     */
    public function getTicket($id)
    {
        return $this->client->tickets()->find($id);
    }
    /**
     * 返回最后一条comment
     * @param $id
     * @param $commentId
     * @return mixed
     */
    public function getComments($id)
    {
        $all = $this->client->tickets($id)->comments()->findAll();
        $comments = $all->comments;
        $count = $all->count;
        return $comments[$count-1];
    }

    /**
     * 发送邮件
     * @param $ticket_id
     * @param $params
     * @param bool $echo
     * @return bool
     */
    public function autoUpdate($ticket_id,$params)
    {
        try {
            $this->client->tickets()->update($ticket_id, $params);
            sleep(1);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
            //exception($e->getMessage(), 10001);
        }
        return true;


    }
    public function attachment($attachment)
    {
        try {
            $res = $this->client->attachments()->upload([
                'file' => '.'.$attachment,
                'name' => basename($attachment),
            ]);
            return $res->upload->token;
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }
    public function merge($ticket_id,$params)
    {
        try {
            $this->client->tickets($ticket_id)->merge($params);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }

}