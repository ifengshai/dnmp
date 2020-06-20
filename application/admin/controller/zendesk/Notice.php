<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/3/25 16:54
 * @Email: 646054215@qq.com
 */

namespace app\admin\controller\zendesk;


use app\admin\model\zendesk\ZendeskAgents;
use app\admin\model\zendesk\ZendeskPosts;
use app\admin\model\zendesk\ZendeskTasks;
use think\Controller;
use think\Db;
use think\Exception;
use Zendesk\API\HttpClient as ZendeskAPI;
use app\admin\model\zendesk\Zendesk;
use app\admin\model\zendesk\ZendeskComments;
use app\admin\model\zendesk\ZendeskTags;

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
    public function __construct($request = null, $postData = [])
    {
        set_time_limit(0);
        parent::__construct();
        if (!$postData) {
            $postData = json_decode(file_get_contents("php://input"), true);
        }
        $this->postData = $postData;
        try {

            $username = '';
            if(isset($this->postData['username'])){
                $username = $this->postData['username'];
            }
            if ($this->postData['type'] == 'voogueme') {
                if(!$username){
                    $username = config('zendesk.voogueme')['username'];
                }
                $this->client = new ZendeskAPI(config('zendesk.voogueme')['subdomain']);
                $this->client->setAuth('basic', ['username' => $username, 'token' => config('zendesk.voogueme')['token']]);
            } else {
                if(!$username){
                    $username = config('zendesk.zeelool')['username'];
                }
                $this->client = new ZendeskAPI(config('zendesk.zeelool')['subdomain']);
                $this->client->setAuth('basic', ['username' => $username, 'token' => config('zendesk.zeelool')['token']]);
            }

        } catch (\Exception $e) {
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            return true;
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
        if ($type == 'zeelool') {
            $type = 1;
        } else {
            $type = 2;
        }
        //file_put_contents('/www/wwwroot/mjz/runtime/b.txt',json_encode($postData)."\r\n",FILE_APPEND);
        //评论s
        $comments = $this->getComments($id);
        //有错误的防止执行下一步
        if($comments == 'success'){
            return 'success';
        }
        $ticket = $this->getTicket($id);
        //有错误的防止执行下一步
        if($ticket == 'success'){
            return 'success';
        }
        //存在已创建的则跳过流程
        if(Zendesk::where(['ticket_id' => $id,'type' => $type])->find()){
            return 'success';
        }
        $via = $ticket->via;
        $priority = 0;
        if ($ticket->priority) {
            $priority = array_search(strtolower($ticket->priority), config('zendesk.priority'));
        }
        $tags = \app\admin\model\zendesk\ZendeskTags::where('name', 'in', $ticket->tags)->column('id');
        sort($tags);
        $tags = join(',',$tags);
        //开始插入相关数据
        //开启事务
        Db::startTrans();
        try {
            //根据用户的id获取用户的信息
            $user = $this->client->crasp()->findUser(['id' => $ticket->requester_id]);
            $userInfo = $user->user;
            $subject = $ticket->subject;
            $rawSubject = $ticket->raw_subject;
            if(!$ticket->subject && !$ticket->raw_subject){
                $subject = $rawSubject = substr($ticket->description,0,60).'...';
            }
            $zendesk_update_time = date('Y-m-d H:i:s',strtotime(str_replace(['T','Z'],[' ',''],$ticket->updated_at)) + 8*3600);
            //写入主表
            $zendesk = Zendesk::create([
                'ticket_id' => $id,
                'type' => $type,
                'channel' => $via->channel,
                'email' => $userInfo->email,
                'username' => $userInfo->name,
                'user_id' => $ticket->requester_id,
                'to_email' => $via->source->to->address,
                'priority' => $priority,
                'status' => array_search(strtolower($ticket->status), config('zendesk.status')),
                'tags' => $tags,
                'subject' => $subject,
                'raw_subject' => $rawSubject,
                'assignee_id' => $ticket->assignee_id ?: 0,
                'assign_id' => 0,
                'zendesk_update_time' => $zendesk_update_time,
            ]);
            $zid = $zendesk->id;
            foreach($comments as $comment){
                //获取所有的附件
                $attachments = [];
                if ($comment->attachments) {
                    foreach ($comment->attachments as $attachment) {
                        $attachments[] = $attachment->content_url;
                    }
                }
                //如果是chat或者voice并且有了分配人，那么创建的一个public设置为1，is_admin设置为1，due_id设置为admin_id,目的是为了记录chat和voice的工作量
                $admin_id = $due_id = ZendeskAgents::where('agent_id',$comment->author_id)->value('admin_id');
                //存在分配人，是chat或者voice，并且不是管理员主动创建的
                if($ticket->assignee_id && in_array($zendesk->channel,['chat','voice']) && $ticket->assignee_id != $ticket->requester_id) {
                    ZendeskComments::create([
                        'ticket_id' => $id,
                        'comment_id' => 0,
                        'zid' => $zid,
                        'author_id' => $ticket->assignee_id,
                        'body' => $zendesk->channel.'记录工作量',
                        'html_body' => $zendesk->channel.'记录工作量',
                        'is_public' => 1,
                        'is_admin' => 1,
                        'attachments' => '',
                        'is_created' => 1,
                        'due_id' => ZendeskAgents::where('agent_id',$ticket->assignee_id)->value('admin_id')
                    ]);
                }
                ZendeskComments::create([
                    'ticket_id' => $id,
                    'comment_id' => $comment->id,
                    'zid' => $zid,
                    'author_id' => $comment->author_id,
                    'body' => $comment->body,
                    'html_body' => $comment->html_body,
                    'is_public' => $comment->public ? 1 : 2,
                    'is_admin' => $admin_id ? 1 : 0,
                    'attachments' => json($attachments),
                    'is_created' => 1,
                    'due_id' => $due_id ? $due_id : 0,
                    'platform'=>$type,
                    'attachments' => join(',',$attachments)
                ]);
            }
            Db::commit();
            //写入附表
        } catch (Exception $e) {
            Db::rollback();
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            //echo $e->getMessage();
        }
        return 'success';
    }

    /**
     * 获取修改的通知
     */
    public function update()
    {
        $postData = $this->postData;
        $id = $postData['id'];
        $type = $postData['type'];
        if ($type == 'zeelool') {
            $type = 1;
        } else {
            $type = 2;
        }
        try{
            //$channel = $postData['channel'];
            //最后一条评论
            $comments = $this->getComments($id);
            //有错误的防止执行下一步
            if($comments == 'success'){
                return 'success';
            }
            $ticket = $this->getTicket($id);
            //有错误的防止执行下一步
            if($ticket == 'success'){
                return 'success';
            }
            //开始插入相关数据
            $tags = $ticket->tags;
            $tags = \app\admin\model\zendesk\ZendeskTags::where('name', 'in', $tags)->distinct(true)->column('id');
            sort($tags);
            $tags = join(',',$tags);

            $zendesk = Zendesk::where(['ticket_id' => $id,'type' => $type])->find();
            if(!$zendesk){
                return 'success';
            }
        }catch (Exception $e) {
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$id."\r\n",FILE_APPEND);
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            return 'success';
            //echo $e->getMessage();
        }
        //开启事务
        Db::startTrans();
        try {
            $zendesk_update_time = date('Y-m-d H:i:s',strtotime(str_replace(['T','Z'],[' ',''],$ticket->updated_at)) + 8*3600);
            //更新主表,目前应该只会更新status，其他不会更新
            $updateData = [
                'tags' => $tags,
                'status' => array_search(strtolower($ticket->status), config('zendesk.status')),
                'zendesk_update_time' => $zendesk_update_time
            ];
            //如果分配人修改，则同步修改分配人
            if($zendesk->assignee_id != $ticket->assignee_id && $ticket->assignee_id){

                $updateData['assignee_id'] = $ticket->assignee_id;
                $updateData['assign_id'] = ZendeskAgents::where('agent_id',$ticket->assignee_id)->value('admin_id');

            }
            //如果没有分配人
            if(!$ticket->assignee_id){
                $updateData['assignee_id'] = '';
                $updateData['assign_id'] = '';
            }
            //更新rating,如果存在的话
            if(!$zendesk->rating && $ticket->satisfaction_rating) {
                $score = $ticket->satisfaction_rating->score;
                $ratingComment = $ticket->satisfaction_rating->comment;
                $ratingReason = $ticket->satisfaction_rating->reason;
                $updateData['rating'] = $score;
                $updateData['comment'] = $ratingComment;
                $updateData['reason'] = $ratingReason;
                if($score == 'good') {
                    $updateData['rating_type'] = 1;
                }elseif($score == 'bad') {
                    $updateData['rating_type'] = 2;
                }
            }
            //如果存在抄送则更新
            if($ticket->follower_ids) {
                $follweIds = $ticket->follower_ids;
                $emailCcs = [];
                foreach($follweIds as $follweId) {
                    $userInfo = $this->client->crasp()->findUser(['id' => $follweId])->user;
                    $emailCcs[] = $userInfo->email;
                }
                if($emailCcs) {
                    $updateData['email_cc'] = join(',', $emailCcs);
                }
            }
            Zendesk::update($updateData, ['id' => $zendesk->id]);
            //写入附表
            //如果该ticket的分配时间不是今天，且修改后的状态是open或者new的话，则今天任务数-1（分担逻辑修改，改方法暂时不用）
//            if (in_array(strtolower($ticket->status), ['open', 'new']) && strtotime($zendesk->assign_time) < strtotime(date('Y-m-d', time()))) {
//                //找出今天的task
//                $task = ZendeskTasks::whereTime('create_time', 'today')
//                    ->where(['admin_id' => $zendesk->assign_id, 'type' => $zendesk->type])
//                    ->find();
//                //存在，则更新
//                if ($task) {
//                    $task->leave_count = $task->leave_count + 1;
//                    $task->target_count = $task->target_count - 1;
//                    $task->surplus_count = $task->surplus_count - 1;
//                    $task->complete_count = $task->complete_count - 1;
//                    $task->complete_apply_count = $task->complete_apply_count - 1;
//                    $task->save();
//                }
//            }
            //从stefen修改为其他用户，用户apply_count+1，complete_apply_count+1
//            if($ticket->assignee_id != '382940274852' && $zendesk->assignee_id == '382940274852'){
//                //找出今天的task
//                $task = ZendeskTasks::whereTime('create_time', 'today')
//                    ->where(['assignee_id' => $ticket->assignee_id, 'type' => $zendesk->type])
//                    ->find();
//                //存在，则更新
//                if ($task) {
//                    $task->complete_apply_count = $task->complete_apply_count + 1;
//                    $task->apply_count = $task->apply_count + 1;
//                    $task->save();
//                }
//            }
            //其他用户修改为stefen,今天分配的量-1
            if($ticket->assignee_id == '382940274852' && $zendesk->assignee_id != '382940274852'){
                //找出今天的task
                $task = ZendeskTasks::whereTime('create_time', 'today')
                    ->where(['admin_id' => $zendesk->assign_id, 'type' => $zendesk->type])
                    ->find();
                //存在，则更新
                if ($task) {
                    $task->surplus_count = $task->surplus_count + 1;
                    $task->complete_count = $task->complete_count - 1;
                    $task->complete_apply_count = $task->complete_apply_count - 1;
                    $task->save();
                    $zendesk->is_hide = 0;
                    $zendesk->save();
                }
            }
            //查找comment_id是否存在，不存在则添加
            foreach($comments as $comment) {
                if (!ZendeskComments::where('comment_id', $comment->id)->find()) {
                    //获取所有的附件
                    $attachments = [];
                    if ($comment->attachments) {
                        foreach ($comment->attachments as $attachment) {
                            $attachments[] = $attachment->content_url;
                        }
                    }
                    $admin_id = $due_id = ZendeskAgents::where('agent_id', $comment->author_id)->value('admin_id');
                    ZendeskComments::create([
                        'ticket_id' => $id,
                        'zid' => $zendesk->id,
                        'comment_id' => $comment->id,
                        'author_id' => $comment->author_id,
                        'body' => $comment->body,
                        'html_body' => $comment->html_body,
                        'is_public' => $comment->public ? 1 : 2,
                        'is_admin' => $admin_id ? 1 : 0,
                        'attachments' => join(',', $attachments),
                        'is_created' => 2,
                        'due_id' => $due_id ? $due_id : 0,
                        'platform'=>$type,
                    ]);
                }
            }
            Db::commit();
        } catch (Exception $e) {
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$id."\r\n",FILE_APPEND);
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            Db::rollback();
            //return true;
            //写入日志
        }
        return 'success';
    }

    /**
     * 根据id获取ticket
     * @param $id
     * @return mixed
     */
    public function getTicket($id)
    {
        try{
            return $this->client->tickets()->find($id)->ticket;
        }catch (\Exception $e) {
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$id."\r\n",FILE_APPEND);
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            return 'success';
            //echo $e->getMessage();
        }
    }

    /**
     * 返回最后一条comment
     * @param $id
     * @param $commentId
     * @return mixed
     */
    public function getLastComments($id)
    {
        try{
            $all = $this->client->tickets($id)->comments()->findAll();
            $comments = $all->comments;
            $count = $all->count;
            return $comments[$count - 1];
        }catch (\Exception $e) {
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$id."\r\n",FILE_APPEND);
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            return 'success';
            //echo $e->getMessage();
        }
    }

    /**
     * 获取所有评论
     * @param $id
     * @return mixed
     * @throws \Zendesk\API\Exceptions\MissingParametersException
     */
    public function getComments($id)
    {
        try{
            $all = $this->client->tickets($id)->comments()->findAll();
            $comments = $all->comments;
            return $comments;
        }catch (\Exception $e) {
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$id."\r\n",FILE_APPEND);
            file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$e->getMessage()."\r\n",FILE_APPEND);
            return 'success';
            //echo $e->getMessage();
        }
    }

    /**
     * 发送邮件
     * @param $ticket_id
     * @param $params
     * @param bool $echo
     * @return bool
     */
    public function autoUpdate($ticket_id, $params)
    {
        try {
            $res = $this->client->tickets()->update($ticket_id, $params);
            sleep(1);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
            //exception($e->getMessage(), 10001);
        }
        //返回最后一条评论的id
        $event = $res->audit->events;
        $commentId = $event[0]->id;
        return $commentId;


    }
    public function createTicket($params)
    {
        try {
            $res = $this->client->tickets()->create($params);
            sleep(1);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
            //exception($e->getMessage(), 10001);
        }
        $event = $res->audit->events;
        $commentId = $event[0]->id;
        return ['comment_id' => $commentId, 'ticket_id' => $res->ticket->id,'requester_id' => $res->ticket->requester_id];
    }

    /**
     * 上传附件
     * @param $attachment
     * @return array
     */
    public function attachment($attachment)
    {
        try {
            $res = $this->client->attachments()->upload([
                'file' => '.' . $attachment,
                'name' => basename($attachment),
            ]);
            return $res->upload->token;
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 合并工单
     * @param $ticket_id
     * @param $params
     * @return array
     */
    public function merge($ticket_id, $params)
    {
        try {
            $this->client->tickets($ticket_id)->merge($params);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 根据id获取用户信息
     * @param $userId
     * @return mixed
     */
    public function findUserById($userId)
    {
        $user = $this->client->crasp()->findUser(['id' => $userId]);
        return $user->user;
    }

    /**
     * 获取所有用户
     *
     * @Description
     * @return void
     * @since 2020/03/28 14:50:55
     * @author lsw
     */
    public function fetchUser($params)
    {
        try {
            return  $this->client->users()->findAll($params);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 创建新用户
     * @param $params
     * @return array
     */
    public function createUser($params)
    {
        try {
            return  $this->client->crasp()->createUser($params);
        } catch (\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 拉取posts的所有数据
     * @throws \Zendesk\API\Exceptions\ApiResponseException
     * @throws \Zendesk\API\Exceptions\AuthException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setPosts()
    {
        $res = $this->client->helpCenter->articles()->findAll(['per_page' => 100]);
        $page_count = $res->page_count;
        $type = $this->postData['type'] == 'zeelool' ? 1 : 2;
        for ($i = 1; $i <= $page_count; $i++) {
            $res = $this->client->helpCenter->articles()->findAll(['page' => $i, 'per_page' => 100]);
            $articles = $res->articles;
            foreach ($articles as $article) {
                if (!ZendeskPosts::where('post_id', $article->id)->find()) {
                    ZendeskPosts::create([
                        'post_id' => $article->id,
                        'title' => $article->title,
                        'html_url' => $article->html_url,
                        'type' => $type,
                        'author_id' => $article->author_id,
                        'body' => $article->body,
                        'create_time' => date('Y-m-d H:i:s', strtotime($article->created_at)),
                        'update_time' => date('Y-m-d H:i:s', strtotime($article->updated_at)),
                    ]);
                }
            }

        }
    }

    /**
     * 获取所有的标签
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setTags()
    {
        $res = $this->client->crasp()->findTags();
        $page_count = intval(ceil($res->count / 100));
        $type = $this->postData['type'] == 'zeelool' ? 1 : 2;
        for ($i = 1; $i <= $page_count; $i++) {
            $res = $this->client->crasp()->findTags(['page' => $i, 'per_page' => 100]);
            $tags = $res->tags;
            foreach ($tags as $tag) {
                if (!ZendeskTags::where('name', $tag->name)->find()) {
                    ZendeskTags::create([
                        'name' => $tag->name,
                        'count' => $tag->count,
                    ]);
                }
            }

        }
    }

    /**
     * 模板导入
     * @throws \Exception
     */
    public function getMacros()
    {
        //{{ticket.requester.first_name}}
        //{{ticket.id}}

        $res = $this->client->macros()->findAllActive();
        $macros = $res->macros;
        $type = $this->postData['type'] == 'zeelool' ? 1 : 2;
        foreach($macros as $macro){
            $data = [];
            $title = $macro->title;
            //echo $title;
            $template_name = mb_substr(strstr($title,'】'),1);
            $template_category = mb_substr(strstr($title,'】',true),1);
            $template_category = array_search($template_category,config('zendesk.template_category'));
            if(!$template_name && !$template_category){
                $template_category = 14;
                $template_name = $title;
            }
            $data = [
                'template_platform' => $type,
                'template_name' => $template_name,
                'template_description' => $macro->description ? $macro->description : $title,
                'template_permission' => 1,
                'template_category' => $template_category,
                'is_active' => 1,
                'create_person' => 1,
                'create_time' => date('Y-m-d H:i:s',time()),
                'update_time' => date('Y-m-d H:i:s',time()),
            ];
            $actions = $macro->actions;
            foreach($actions as $key => $action){
                if($action->field == 'comment_value_html'){
                    $data['template_content'] = str_replace(['{{ticket.requester.first_name}}','{{ticket.id}}'],['{{username}}','{{ticket_id}}'],$action->value);
                }
                if($action->field == 'subject'){
                    $data['mail_subject'] = $action->value;
                }
                if($action->field == 'current_tags'){
                    $tags = explode(' ',$action->value);
                    $tags = ZendeskTags::where('name','in',$tags)->column('id');
                    sort($tags);
                    $data['mail_tag'] = join(',',$tags);
                }
                if($action->field == 'status'){
                    $data['mail_status'] = array_search($action->value, config('zendesk.status'));
                }
                if($action->field == 'priority'){
                    $data['mail_level'] = array_search($action->value, config('zendesk.priority'));
                }
            }
            //dump($data);die;
            \app\admin\model\zendesk\ZendeskMailTemplate::create($data);

        }
    }

    /**
     * 拉取所有的邮件
     * @return bool
     * @throws \Zendesk\API\Exceptions\MissingParametersException
     * @throws \Zendesk\API\Exceptions\RouteException
     */
    public function setTickets()
    {
        $search = [
            'type' => 'ticket',
            'order_by' => 'created_at',
            'status' => ['open'],
            'assignee' => [
                'wangyian@nextmar.com',
                'yuanqianqian@nextmar.com',
                'mayumeng@nextmar.com',
                'wufan@nextmar.com',
                'zhaojinjin@nextmar.com',
                'lisen@nextmar.com',
                'liumengnan@nextmar.com'
            ],
            'sort' => 'asc'
        ];
        //$type = $this->postData['type'] == 'zeelool' ? 1 : 2;
        $type = 1;
        $params = $this->parseStr($search);
        $search = $this->client->search()->find($params);
        $tickets = $search->results;
        if (!$search->count) {
            return true;
        }
        echo $search->count;
        $page = ceil($search->count / 100 );
        //先获取第一页的,一次100条
        $this->findCommentsByTickets($tickets,$type);
        if($page > 1){
            //获取后续的
            for($i=2;$i<= $page;$i++){
                try{
                    $search = $this->client->search()->find($params,['page' => $i]);
                }catch (\Exception $e){
                    echo $e->getMessage();
                    $this->setTickets();
                }
                $tickets = $search->results;
                $this->findCommentsByTickets($tickets,$type);

            }
        }
    }

    /**
     * 获取的tickets写入数据库
     * @param $tickets
     * @param $type
     */
    public function findCommentsByTickets($tickets,$type)
    {
        $key = 0;
        foreach($tickets as $ticket){
            ++$key;
            $via = $ticket->via;
            $priority = 0;
            if ($ticket->priority) {
                $priority = array_search($ticket->priority, config('zendesk.priority'));
            }
            //开启事务
//            Db::startTrans();
//            try {
            $assign_id = \app\admin\model\zendesk\ZendeskAgents::where('agent_id',$ticket->assignee_id)->value('admin_id');
            $tags = ZendeskTags::where('name','in',$ticket->tags)->column('id');
            sort($tags);
            echo $ticket->id."\r\n";
//            echo $key."\r\n";
            if(!Zendesk::where(['ticket_id' => $ticket->id, 'type' => $type])->find()) {
                echo $ticket->id."\r\n";
                //根据用户的id获取用户的信息
                $user = $this->client->crasp()->findUser(['id' => $ticket->requester_id]);
                $userInfo = $user->user;
                $subject = $ticket->subject;
                $rawSubject = $ticket->raw_subject;
                if(!$ticket->subject && !$ticket->raw_subject){
                    $subject = $rawSubject = substr($ticket->description,0,62).'...';
                }
                // echo $subject;die;
                //写入主表
                $zendesk = Zendesk::create([
                    'ticket_id' => $ticket->id,
                    'type' => $type,
                    'channel' => $via->channel,
                    'email' => $userInfo->email,
                    'username' => $userInfo->name,
                    'user_id' => $ticket->requester_id,
                    'to_email' => $via->source->to->address,
                    'priority' => $priority,
                    'status' => array_search(strtolower($ticket->status), config('zendesk.status')),
                    'tags' => join(',',$tags),
                    'subject' => $subject,
                    'raw_subject' => $rawSubject,
                    'assignee_id' => $ticket->assignee_id ?: 0,
                    'assign_id' => $assign_id ?: 0,
                    'due_id' => $assign_id ?: 0,
                    'rating' => $ticket->satisfaction_rating->score,
                    'rating_type' => $ticket->satisfaction_rating->score == 'bad' ? 2 : 1,
                    'comment' => $ticket->satisfaction_rating->comment,
                    'reason' => $ticket->satisfaction_rating->reason,
                    'create_time' => date('Y-m-d H:i:s',(strtotime(str_replace(['T','Z'],[' ',''],$ticket->created_at))+8*3600)),
                    'update_time' => date('Y-m-d H:i:s',(strtotime(str_replace(['T','Z'],[' ',''],$ticket->updated_at))+8*3600)),
                    'assign_time' => date('Y-m-d H:i:s',(strtotime(str_replace(['T','Z'],[' ',''],$ticket->created_at))+8*3600)),
                    'shell' => 1
                ]);
                $zid = $zendesk->id;
                //echo $ticket->id."\r\n";
                $comments = $this->client->tickets($ticket->id)->comments()->findAll();
                //if($ticket->id != 24) {
                foreach($comments->comments as $comment){
                    //获取所有的附件
                    $attachments = [];
                    if ($comment->attachments) {
                        foreach ($comment->attachments as $attachment) {
                            $attachments[] = $attachment->content_url;
                        }
                    }
                    $admin_id = $due_id = ZendeskAgents::where('agent_id',$comment->author_id)->value('admin_id');
                    $is_admin = \app\admin\model\zendesk\ZendeskAccount::where('account_id',$comment->author_id)->find();
                    //存在分配人，是chat或者voice，并且不是管理员主动创建的
                    if($ticket->assignee_id && in_array($zendesk->channel,['chat','voice']) && $ticket->assignee_id != $ticket->requester_id) {
                        ZendeskComments::create([
                            'ticket_id' => $ticket->id,
                            'comment_id' => 0,
                            'zid' => $zid,
                            'author_id' => $ticket->assignee_id,
                            'body' => $zendesk->channel.'记录工作量',
                            'html_body' => $zendesk->channel.'记录工作量',
                            'is_public' => 1,
                            'is_admin' => 1,
                            'attachments' => '',
                            'is_created' => 1,
                            'due_id' => ZendeskAgents::where('agent_id',$ticket->assignee_id)->value('admin_id')
                        ]);
                    }
                    $res = ZendeskComments::create([
                        'ticket_id' => $ticket->id,
                        'comment_id' => $comment->id,
                        'zid' => $zid,
                        'author_id' => $comment->author_id,
                        'body' => $comment->body,
                        'html_body' => $comment->html_body,
                        'is_public' => $comment->public ? 1 : 2,
                        'is_admin' => $is_admin ? 1 : 0,
                        'attachments' => json($attachments),
                        'is_created' => 1,
                        'create_time' => date('Y-m-d H:i:s',(strtotime(str_replace(['T','Z'],[' ',''],$comment->created_at))+8*3600)),
                        'update_time' => date('Y-m-d H:i:s',(strtotime(str_replace(['T','Z'],[' ',''],$comment->created_at))+8*3600)),
                    ]);
                }
                echo $zendesk->ticket_id."\r\n";
                usleep(100);
                // }
                //sleep(1);
                //Db::commit();
            }

            //写入附表
//            } catch (Exception $e) {
//                Db::rollback();
//                $this->error($e->getMessage(), '');
//            }
        }

    }
    /**
     * 格式化筛选条件
     * @param array $array
     * @return string
     */
    public function parseStr(Array $array)
    {
        $params = '';
        array_walk($array, function ($val, $key) use (&$params) {
            if (is_array($val)) {
                //keytype
                if (isset($val['keytype'])) {
                    $params .= $val['keytype'] . $key . ':' . $val['value'] . ' ';
                } elseif (isset($val['valuetype'])) {
                    $params .= $key . $val['valuetype'] . $val['value'] . ' ';
                } else {
                    foreach ($val as $value) {
                        $params .= $key . ':' . $value . ' ';
                    }
                }
            } else {
                $params .= $key . ':' . $val . ' ';
            }
        });
        return $params;
    }

    /**
     * 同步应为消息通知断掉的今天的消息
     */
    public function asycTickets()
    {
        $type = $this->postData['type'];
        if ($type == 'zeelool') {
            $type = 1;
        } else {
            $type = 2;
        }
        $a = 1;
        $ticket_ids = Zendesk::where('ticket_id','in','105010,104326,105024,104644,104913,105119')->where('type',$type)->column('ticket_id');
        foreach($ticket_ids as $ticket_id){
            $ticket = $this->client->tickets()->find($ticket_id)->ticket;

            $id = $ticket->id;
//            if($a > 2){
//                break;
//            }
            $comments = $this->getComments($id);
            //开始插入相关数据
            $tags = $ticket->tags;
            $tags = \app\admin\model\zendesk\ZendeskTags::where('name', 'in', $tags)->distinct(true)->column('id');
            sort($tags);
            $tags = join(',',$tags);
            $zendesk = Zendesk::where(['ticket_id' => $id,'type' => $type])->find();
            if(!$zendesk){
                continue;
            }
            //echo 1;die;
            //开启事务
            Db::startTrans();
            try {
                //更新主表,目前应该只会更新status，其他不会更新
                $updateData = [
                    'tags' => $tags,
                    'status' => array_search(strtolower($ticket->status), config('zendesk.status')),
                    'update_time' => date('Y-m-d H:i:s',(strtotime(str_replace(['T','Z'],[' ',''],$ticket->updated_at))+8*3600)),
                ];
                //如果分配人修改，则同步修改分配人
                if($zendesk->assignee_id != $ticket->assignee_id && $ticket->assignee_id){

                    $updateData['assignee_id'] = $ticket->assignee_id;
                    $updateData['assign_id'] = $updateData['due_id'] = ZendeskAgents::where('agent_id',$ticket->assignee_id)->value('admin_id');
                }
                //更新rating,如果存在的话
                if(!$zendesk->rating && $ticket->satisfaction_rating) {
                    $score = $ticket->satisfaction_rating->score;
                    $ratingComment = $ticket->satisfaction_rating->comment;
                    $ratingReason = $ticket->satisfaction_rating->reason;
                    $updateData['rating'] = $score;
                    $updateData['comment'] = $ratingComment;
                    $updateData['reason'] = $ratingReason;
                    if($score == 'good') {
                        $updateData['rating_type'] = 1;
                    }elseif($score == 'bad') {
                        $updateData['rating_type'] = 2;
                    }
                }
                //如果存在抄送则更新
                if($ticket->follower_ids) {
                    $follweIds = $ticket->follower_ids;
                    $emailCcs = [];
                    foreach($follweIds as $follweId) {
                        $userInfo = $this->client->crasp()->findUser(['id' => $follweId])->user;
                        $emailCcs[] = $userInfo->email;
                    }
                    if($emailCcs) {
                        $updateData['email_cc'] = join(',', $emailCcs);
                    }
                }
                Zendesk::update($updateData, ['id' => $zendesk->id]);
                //写入附表
                //查找comment_id是否存在，不存在则添加
                foreach($comments as $comment){
                    if(!ZendeskComments::where('comment_id',$comment->id)->find()) {
                        $a++;
                        //获取所有的附件
                        $attachments = [];
                        if ($comment->attachments) {
                            foreach ($comment->attachments as $attachment) {
                                $attachments[] = $attachment->content_url;
                            }
                        }
                        $admin_id = $due_id = ZendeskAgents::where('agent_id',$comment->author_id)->value('admin_id');
                        $res = ZendeskComments::create([
                            'ticket_id' => $id,
                            'zid' => $zendesk->id,
                            'comment_id' => $comment->id,
                            'author_id' => $comment->author_id,
                            'body' => $comment->body,
                            'html_body' => $comment->html_body,
                            'is_public' => $comment->public ? 1 : 2,
                            'is_admin' => $admin_id ? 1 : 0,
                            'attachments' => json($attachments),
                            'is_created' => 2,
                            'due_id' => $due_id ? $due_id : 0
                        ]);
                        echo $res->id."\r\n";
                    }
                }

                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                //写入日志
            }

        }

    }
    /**
     * 脚本执行分配
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shellAssignTicket()
    {
        Zendesk::shellAssignTicket();
    }
    /**
     * 脚本执行分配修改版
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shellAssignTicketChange()
    {
        Zendesk::shellAssignTicketChange();
    }

    /**
     * 获取邮件模板
     * @return mixed
     * @throws \Exception
     */
    public function getTemplate()
    {
        $res = $this->client->macros()->findAllActive();
        $macros = $res->macros;
        return $macros;
    }

    /**
     * 同步丢失数据使用
     * @return array|bool
     * @throws \Zendesk\API\Exceptions\MissingParametersException
     * @throws \Zendesk\API\Exceptions\RouteException
     */
    public function asyncUpdate()
    {
        $params = 'type:ticket updated_at>=2020-06-01T15:00:00Z updated_at<=2020-06-01T20:00:00Z order_by:updated_at sort:asc';
         //Get all tickets
        $tickets = $this->client->search()->find($params);

        $ticketIds = [];
        if(!$tickets->count){
            return true;
        }

        echo $tickets->count;

        $page = ceil($tickets->count / 100 );
        if($page >= 1){
            //获取后续的
            for($i=1;$i<= $page;$i++){
                $search = $this->client->search()->find($params,['page' => $i]);
                foreach($search->results as $ticket){
                    $ticketIds[] = $ticket->id;
                }

            }
        }
         return array_filter($ticketIds);
    }
}