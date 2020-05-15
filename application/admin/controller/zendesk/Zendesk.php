<?php

namespace app\admin\controller\zendesk;

use app\admin\model\zendesk\ZendeskPosts;
use app\admin\model\zendesk\ZendeskTasks;
use app\common\controller\Backend;
use app\admin\model\zendesk\ZendeskTags;
use app\admin\model\zendesk\ZendeskAgents;
use app\admin\model\zendesk\ZendeskComments;
use app\admin\model\zendesk\ZendeskMailTemplate;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use League\HTMLToMarkdown\HtmlConverter;


/**
 *
 *
 * @icon fa fa-circle-o
 */
class Zendesk extends Backend
{
    protected $model = null;
    protected $relationSearch = true;
    protected $noNeedLogin = ['asycTicketsUpdate','asycTicketsVooguemeUpdate','asycTicketsAll','asycTicketsAll2','asycTicketsAll3'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\Zendesk;
        $this->view->assign('getTabList', $this->model->getTabList());
        $this->assignconfig('admin_id', session('admin.id'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $tags = ZendeskTags::column('name','id');
        $this->view->assign('tags',$tags);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            $map = [];
            $andWhere = '';
            $me_task = $filter['me_task'];
            if ($me_task == 1) { //我的所有任务
                unset($filter['me_task']);
                $map['zendesk.assign_id'] = session('admin.id');
            } elseif ($me_task == 2) { //我的待处理任务
                unset($filter['me_task']);
                $map['zendesk.assign_id'] = session('admin.id');
                $map['zendesk.status'] = ['in', [1, 2]];
                $map['zendesk.is_hide'] = 0;
                $taskCount = ZendeskTasks::where('admin_id',session('admin.id'))->value('target_count');
            }
            //类型筛选
            if($filter['status_type']){
//                待处理：new;open状态下的工单
//                新增：update时间为选择时间，new、open状态的工单
//                已处理：public comment
//                待分配：没有承接人的工单
                $status_type = $filter['status_type'];
                unset($filter['status_type']);
                switch($status_type){
                    case 1:
                        $map['zendesk.status'] = ['in', [1, 2]];
                        break;
                    case 2:
                        $update_time = $filter['update_time'] ?? '';
                        if(!$update_time){
                            $this->error('请选择更新时间');
                        }
                        $map['zendesk.status'] = ['in', [1, 2]];
                        break;
                    case 3:
                        //获取public =1 is_admin=1的zid列表
                        $zids = ZendeskComments::where(['is_public' => 1,'is_admin' => 1])->column('zid');
                        $map['zendesk.id'] = ['in',$zids];
                        break;
                    case 4:
                        //获取所有的账号admin_id
                        $admin_ids = ZendeskAgents::column('admin_id');
                        $map['zendesk.assign_id'] = ['not in',$admin_ids];
                        $map['zendesk.status'] = ['in', [1, 2]];
                        break;
                }
            }
            if($filter['tags']) {
                $andWhere = "FIND_IN_SET({$filter['tags']},tags)";
                unset($filter['tags']);
            }
            if($filter['content']) {
                $comments = ZendeskComments::where('body','like','%'.$filter['content'].'%')->column('ticket_id');
                $tickets = $this->model->where('subject','like','%'.$filter['content'].'%')->column('ticket_id');
                $ticket_ids = array_merge($comments,$tickets);
                $map['zendesk.ticket_id'] = ['in',$ticket_ids];
                unset($filter['content']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //默认使用
            $orderSet = 'priority desc,zendesk_update_time asc,id asc';
            if($me_task == 2){
                $orderSet = 'priority desc,zendesk_update_time asc,id asc';
            }
            if($sort != 'zendesk.id' && $sort){
                $orderSet = "{$sort} {$order}";
            }
            $total = $this->model
                ->with(['admin'])
                ->where($where)
                ->where($map)
                ->where($andWhere)
                ->where('channel','in',['email','web','chat'])
                ->count();


            $list = $this->model
                ->with(['admin'])
                ->where($where)
                ->where($map)
                ->where($andWhere)
                ->where('channel','in',['email','web','chat'])
                ->order($orderSet)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $type = input('type');
                    $siteName = 'zeelool';
                    if($type == 2) {
                        $siteName = 'voogueme';
                    }
                    $tags = ZendeskTags::where('id', 'in', $params['tags'])->column('name');
                    $status = config('zendesk.status')[$params['status']];
                    $author_id = $assignee_id = ZendeskAgents::where(['admin_id' => session('admin.id'), 'type' => $type])->value('agent_id');
                    if (!$author_id) {
                        throw new Exception('请将用户先绑定zendesk的账号', 10001);
                    }
                    //发送邮件的参数
                    $createData = [
                        'comment' => [
                            'author_id' => $author_id,
                        ],
                        'tags' => $tags,
                        'status' => $status,
                        'assignee_id' => $assignee_id,
                        'submitter_id' => $assignee_id
                    ];
                    if(!$params['subject']) {
                        throw new Exception('邮件标题不能为空', 10001);
                    }
                    if(!strip_tags($params['content'])) {
                        throw new Exception('内容不能为空', 10001);
                    }
                    if(!$params['email']) {
                        throw new Exception('发送人不能为空', 10001);
                    }
                    $createData['requester'] = [
                        'email' => $params['email']
                    ];
                    //判断当前邮箱是否存在
                    if(!$this->model->where('email',$params['email'])->find()){
                        $emailName = strstr($params['email'],'@',true);
                        $createData['requester']['name'] = $emailName;
//
//                        (new Notice(request(), ['type' => $siteName]))->createUser(
//                            ['user' => ['name' => $params['email'], 'email' => $params['email']]]
//                        );
                    }

                    //有抄送，添加抄送
                    if ($params['email_cc']) {
                        $email_ccs = $this->emailCcs($params['email_cc'], []);
                        $createData['email_ccs'] = $email_ccs;
                    }
                    $priority = config('zendesk.priority')[$params['priority']];
                    $body = $params['content'];
                    if ($priority) {
                        $createData['priority'] = $priority;
                    }
                    //由于编辑器或默认带个<br>,所以去除标签判断有无值
                    if (strip_tags($body)) {
//                        $converter = new HtmlConverter();
//                        $createData['comment']['body'] = $converter->convert($body);
                        $createData['comment']['html_body'] = $body;

                    }
                    if ($params['image']) {
                        //附件上传
                        $attachments = explode(',', $params['image']);
                        $token = [];
                        foreach ($attachments as $attachment) {
                            $res = (new Notice(request(), ['type' => $siteName]))->attachment($attachment);
                            if (isset($res['code'])) {
                                throw new Exception($res['message'], 10001);
                            }
                            $token[] = $res;
                        }
                        if ($token) {
                            $createData['comment']['uploads'] = $token;
                        }
                    }
                    //私有的
                    if ($params['public_type'] == 1) {
                        $createData['comment']['public'] = false;
                    }
                    //开始发送
                    $res = (new Notice(request(), ['type' => $siteName]))->createTicket($createData);
                    if (isset($res['code'])) {
                        throw new Exception($res['message'], 10001);
                    }
                    //开始写入数据库
                    $agent_id = ZendeskAgents::where(['admin_id' => session('admin.id'), 'type' => $type])->value('agent_id');
                    //对tag进行排序
                    $zendeskTags = $params['tags'];
                    sort($zendeskTags);
                    //更新主表的状态和priority，tags,due_id，assignee_id等
                    //根据用户的id获取用户的信息
                    $userInfo = (new Notice(request(), ['type' => $siteName]))->findUserById($res['requester_id']);
                    $rawSubject = $subject = $params['subject'];
                    //写入主表
                    $zendesk = \app\admin\model\zendesk\Zendesk::create([
                        'ticket_id' => $res['ticket_id'],
                        'type' => $type,
                        'channel' => 'web',
                        'email' => $userInfo->email,
                        'username' => $userInfo->name,
                        'user_id' => $res['requester_id'],
                        'to_email' => '',
                        'priority' => $params['priority'],
                        'status' => $params['status'],
                        'tags' => join(',',$zendeskTags),
                        'subject' => $subject,
                        'raw_subject' => $rawSubject,
                        'assignee_id' => $assignee_id,
                        'assign_id' => $agent_id,
                        'email_cc' => $params['email_cc'],
                        'zendesk_update_time' => date('Y-m-d H:i:s',time()+8*3600)
                    ]);
                    $zid = $zendesk->id;
                    //评论表添加内容,有body时添加评论，修改状态等不添加
                    if (strip_tags($params['content'])) {
                        $result = ZendeskComments::create([
                            'ticket_id' => $res['ticket_id'],
                            'comment_id' => $res['comment_id'],
                            'zid' => $zid,
                            'author_id' => $agent_id,
                            'body' => strip_tags($params['content']),
                            'html_body' => $params['content'],
                            'is_public' => $params['public_type'],
                            'is_admin' => 1,
                            'due_id' => session('admin.id'),
                            'attachments' => $params['image']
                        ]);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //获取所有的tags
        $tags = ZendeskTags::order('count desc')->column('name', 'id');
        //站点类型，默认zeelool，1：zeelool，2：voogueme
        $type = input('type',1);
        //获取所有的消息模板
        //获取所有的消息模板
        $templateAll = ZendeskMailTemplate::where([
            'template_platform' => $type,
            'template_permission' => 1,
            'is_active' => 1])
            ->whereOr(['template_permission' => 2,'is_active' => 1, 'create_person' => session('admin.id')])
            ->order('used_time desc,template_category desc,id desc')
            ->select();

        foreach ($templateAll as $key => $template) {
            $category = '';
            if ($template['template_category']) {
                $category = '【' . config('zendesk.template_category')[$template['template_category']] . '】';
            }
            $templates[] = [
                'id' => $template['id'],
                'title' => $category . $template['template_name']
            ];

        }

        $this->view->assign(compact('tags',  'templates','type'));
        return $this->view->fetch();
    }
    /**
     * 发送邮件
     * @param null $ids
     * @return string
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        //获取主的ticket
        $ticket = $this->model->where('id', $ids)->find();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //获取发送邮件的所有的参数
                    //1、tag
                    //2、priority
                    //status
                    $tags = ZendeskTags::where('id', 'in', $params['tags'])->column('name');
                    $status = config('zendesk.status')[$params['status']];
                    $author_id = $assignee_id = ZendeskAgents::where(['admin_id' => session('admin.id'), 'type' => $ticket->type])->value('agent_id');
                    if (!$author_id) {
                        throw new Exception('请将用户先绑定zendesk的账号', 10001);
                    }
                    $siteName = 'zeelool';
                    if($ticket->type == 2){
                        $siteName = 'voogueme';
                    }
                    //发送邮件的参数
                    $updateData = [
                        'comment' => [
                            'author_id' => $author_id,
                        ],
                        'tags' => $tags,
                        'status' => $status,
                        'assignee_id' => $assignee_id
                    ];
                    //有抄送，添加抄送
                    if ($params['email_cc']) {
                        $email_ccs = $this->emailCcs($params['email_cc'], $ticket->email_cc);
                        $updateData['email_ccs'] = $email_ccs;
                    }
                    //修改主题
                    if ($params['subject'] != $ticket->subject) {
                        $updateData['subject'] = $params['subject'];
                    }
                    $priority = config('zendesk.priority')[$params['priority']];
                    $body = $params['content'];
                    if ($priority) {
                        $updateData['priority'] = $priority;
                    }
                    //由于编辑器或默认带个<br>,所以去除标签判断有无值
                    if (strip_tags($body)) {
//                        $converter = new HtmlConverter();
//                        $updateData['comment']['body'] = $converter->convert($body);
                        $updateData['comment']['html_body'] = $body;
                    }
                    if ($params['image']) {
                        //附件上传
                        $attachments = explode(',', $params['image']);
                        $token = [];
                        foreach ($attachments as $attachment) {
                            $res = (new Notice(request(), ['type' => $siteName]))->attachment($attachment);
                            if (isset($res['code'])) {
                                throw new Exception($res['message'], 10001);
                            }
                            $token[] = $res;
                        }
                        if ($token) {
                            $updateData['comment']['uploads'] = $token;
                        }
                    }
                    //私有的
                    if ($params['public_type'] == 1) {
                        $updateData['comment']['public'] = false;
                    }
                    //开始发送
                    $res = (new Notice(request(), ['type' => $siteName]))->autoUpdate($ticket->ticket_id, $updateData);
                    if (isset($res['code'])) {
                        throw new Exception($res['message'], 10001);
                    }
                    //开始写入数据库
                    $agent_id = ZendeskAgents::where(['admin_id' => session('admin.id'), 'type' => $ticket->type])->value('agent_id');
                    //对tag进行排序
                    $zendeskTags = $params['tags'];
                    sort($zendeskTags);

                    //更新主表的状态和priority，tags,due_id，assignee_id等
                    $result = $this->model->where('id', $ids)->update([
                        'subject' => $params['subject'],
                        'priority' => $params['priority'],
                        'status' => $params['status'],
                        'tags' => join(',', $zendeskTags),
                        'assignee_id' => $agent_id,
                        'due_id' => session('admin.id'),
                        'email_cc' => $params['email_cc'],
                        'zendesk_update_time' => date('Y-m-d H:i:s',time() + 8*3600)
                    ]);
                    //评论表添加内容,有body时添加评论，修改状态等不添加
                    if (strip_tags($params['content'])) {
                        $result = ZendeskComments::create([
                            'ticket_id' => $ticket->ticket_id,
                            'comment_id' => $res,
                            'zid' => $ids,
                            'author_id' => $agent_id,
                            'body' => strip_tags($params['content']),
                            'html_body' => $params['content'],
                            'is_public' => $params['public_type'],
                            'is_admin' => 1,
                            'due_id' => session('admin.id'),
                            'attachments' => $params['image']
                        ]);
                    }

                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('回复成功！！', url('zendesk/index'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //获取所有的tags
        $tags = ZendeskTags::order('count desc')->column('name', 'id');

        $comments = ZendeskComments::with(['agent' => function($query) use($ticket){
            $query->where('type',$ticket->type);
        }])->where('zid', $ids)->order('id', 'desc')->select();
        //获取该用户的所有状态不为close，sloved的ticket
        $tickets = $this->model
            ->where(['user_id' => $ticket->user_id, 'status' => ['in', [1, 2, 3]], 'type' => $ticket->type])
            ->where('id', 'neq', $ids)
            ->field('ticket_id,id,username,subject,update_time,zendesk_update_time')
            ->order('id desc')
            ->select();
        //获取该用户最新的5条ticket
        $recentTickets = $this->model
            ->where(['user_id' => $ticket->user_id, 'type' => $ticket->type])
            ->where('id', 'neq', $ids)
            ->field('ticket_id,id,username,subject,status')
            ->order('id desc')
            ->limit(5)
            ->select();
        //获取所有的消息模板
        $templateAll = ZendeskMailTemplate::where([
            'template_platform' => $ticket->type,
            'template_permission' => 1,
            'is_active' => 1])
            ->whereOr(['template_permission' => 2,'is_active' => 1, 'create_person' => session('admin.id')])
            ->order('used_time desc,template_category desc,id desc')
            ->select();

        foreach ($templateAll as $key => $template) {
            $category = '';
            if ($template['template_category']) {
                $category = '【' . config('zendesk.template_category')[$template['template_category']] . '】';
            }
            $templates[] = [
                'id' => $template['id'],
                'title' => $category . $template['template_name']
            ];

        }
        //array_unshift($templates, 'Apply Macro');
        //获取当前用户的最新5个的订单
        if($ticket->type == 1){
            $orderModel = new \app\admin\model\order\order\Zeelool;
        }else{
            $orderModel = new \app\admin\model\order\order\Voogueme;
        }

        $orders = $orderModel
            ->where('customer_email',$ticket->email)
            ->order('entity_id desc')
            ->limit(5)
            ->select();        
        $btn = input('btn',0);
        $this->view->assign(compact('tags', 'ticket', 'comments', 'tickets', 'recentTickets', 'templates','orders','btn'));
        $this->view->assign('rows', $row);
        $this->view->assign('orderUrl',config('zendesk.platform_url')[$ticket->type]);
        return $this->view->fetch();
    }

    /**
     * 获取邮箱
     *
     * @Description
     * @author wpl
     * @since 2020/03/30 09:25:07 
     * @return void
     */
    public function getEmail()
    {
        $term = input('term');
        $where['email|username'] = ['like', '%' . $term . '%'];
        $data = $this->model->where($where)->column('email');
        return json(array_unique($data));
    }

    /**
     * ajax获取ticket的详情
     * @return \think\response\Json
     */
    public function getTicket()
    {
        $ticket_id = input('nid');
        $pid = input('pid');
        if ($ticket_id == $pid) {
            $this->error("You selected the same ticket as source and target: #{$ticket_id}. You cannot merge a ticket into itself.
Please close this window and try again.");
        }
        //合并到的信息
        $ticket = $this->model->where('ticket_id', $ticket_id)->field('id,ticket_id,subject')->find();
        if (!$ticket) {
            $this->error("You are unable to merge into #{$ticket_id}. Tickets don't find, tickets that are shared with other accounts, and tickets you don't have access to cannot be merged into.
Please close this window and try again.");
        }
        //合并的最后一条评论
        $comment = $this->model->where('ticket_id', $pid)->with('lastComment')->find();
        if (in_array($comment->status,[4,5])) {
            $this->error("You are unable to merge into #{$ticket_id}. Tickets that are Closed, tickets that are shared with other accounts, and tickets you don\'t have access to cannot be merged into.
Please close this window and try again.");
        }
        $ticket['lastComment'] = $comment->lastComment[0]->html_body;
        return $this->success('success', '', $ticket);
    }

    /**
     * 合并工单
     * @throws \Exception
     */
    public function setMerge()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            //获取所有的合并工单的参数
            $ticket = $params['merge_in_id'];
            $ids = $params['merge_to_id'];
            $target_comment = $params['merge_in'];
            $source_comment = $params['merge_to'];
            $target_comment_is_public = isset($params['merge_in_check']) ? true : false;
            $source_comment_is_public = isset($params['merge_to_check']) ? true : false;
            //获取邮件的type类型，是v还是z站
            $type = $this->model->where('ticket_id', $ticket)->value('type');
            $siteName = '';
            if($type == 1){
                $siteName = 'zeelool';
            }elseif($type == 2){
                $siteName = 'voogueme';
            }

            $data = [
                'ids' => [$ids],
                'target_comment_is_public' => $target_comment_is_public,
                'source_comment_is_public' => $source_comment_is_public
            ];
            $converter = new HtmlConverter();
            if ($target_comment) {
                $data['target_comment'] = $converter->convert($target_comment);
            }
            if ($source_comment) {
                $data['source_comment'] = $converter->convert($source_comment);
            }
            $result = false;

            try {
                //获取当前用户绑定的账户邮箱
                $agentId = ZendeskAgents::where('admin_id',session('admin.id'))->value('agent_id');
                $username = \app\admin\model\zendesk\ZendeskAccount::where('account_id',$agentId)->value('account_email');
                //合并工单
                $result = (new Notice(request(), ['type' => $siteName,'username' => $username]))->merge($ticket, $data);
                if (isset($result['code'])) {
                    throw new Exception($result['message'], 10001);
                }
                //修改数据库，修改状态
                //获取closed_by_merge的tag的id
                $tagId = ZendeskTags::where('name', 'closed_by_merge')->value('id');
                //获取被合并的tags
                $tagIds = $this->model->where('ticket_id', $ticket)->value('tags');
                if ($tagIds) {
                    $tagIds = explode(',', $tagIds);
                    array_unshift($tagIds, $tagId);
                    $tagIds = join(',', $tagIds);
                } else {
                    $tagIds = $tagId;
                }

                $agent_id = ZendeskAgents::where(['admin_id' => session('admin.id'),'type' => $type])->value('agent_id');
                $zid = $this->model->where('ticket_id', $ids)->value('id');
                //被合并的状态closed，添加content，tag：closed_by_merge
                $this->model->where('ticket_id', $ids)->update([
                    'status' => '5',
                    'tags' => $tagIds,
                    'assignee_id' => $agent_id,
                    'assign_id' => $agent_id,
                    'due_id' => session('admin.id'),
                    'zendesk_update_time' => date('Y-m-d H:i:s',time() + 8*3600)
                ]);

                ZendeskComments::create([
                    'ticket_id' => $ids,
                    'zid' => $zid,
                    'author_id' => $agent_id,
                    'body' => strip_tags($source_comment),
                    'html_body' => $source_comment,
                    'is_public' => $source_comment_is_public,
                    'is_admin' => 1
                ]);
                //合并的添加评论content
                $this->model->where('ticket_id', $ticket)->update([
                    'assignee_id' => $agent_id,
                    'assign_id' => $agent_id,
                    'due_id' => session('admin.id'),
                ]);
                $zid = $this->model->where('ticket_id', $ticket)->value('id');
                ZendeskComments::create([
                    'ticket_id' => $ticket,
                    'zid' => $zid,
                    'author_id' => $agent_id,
                    'body' => strip_tags($target_comment),
                    'html_body' => $target_comment,
                    'is_public' => $target_comment_is_public,
                    'is_admin' => 1
                ]);
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success('回复成功！！', url('zendesk/index'));
            } else {
                $this->error(__('No rows were updated'));
            }
        }
        $this->error(__('Parameter %s can not be empty', ''));
    }

    /**
     * 知识库搜索文章
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchPosts()
    {
        if ($this->request->isPost()) {
            $text = input('text');
            $type = input('type');
            $posts = ZendeskPosts::where('title', 'like', '%' . $text . '%')->where('type', $type)->select();
            //拼接html
            $html = '';
            $post_html = '';
            foreach ($posts as $key => $post) {
                $html .= <<<DOC
<div class="card" data-num="{$key}">
                                <div class="card-body">
                                <h4 class="card-title"><a href="javascript:void(0)" style="color:#2f3941;font-weight: bold;">{$post->title}</a></h4>
                            <a href="javascript:void(0)" data-title="{$post->title}" data-link="{$post->html_url}" class="card-link">add link</a>
                            <button class="btn btn-xs btn-primary pull-right" style="display:none;">linked</button>
                            </div>
                            </div>
DOC;
                $post_html .= <<<DOC
<div class="post-row" style="display:none;">
                            <div class="mailbox-read-info">
                                <h3>{$post->title}</h3>
                            </div>
                            <div class="row  show-posts">
                            {$post->body}
                            </div>
                        </div>
DOC;

            }


            return json(['html' => $html, 'post_html' => $post_html]);
        }
        $this->error('there has something wrong');
    }

    /**
     * 添加抄送，删除的目前先不做，sdk删除不能用
     * @param $emailCcs
     * @param $preEmailCcs
     * @return array
     */
    public function emailCcs($emailCcs, $preEmailCcs)
    {
        if($preEmailCcs){
            $preEmailCcs = explode(',', $preEmailCcs);
        }

        $emailCcs = explode(',', $emailCcs);
        //pre并em，删除，
        //$del = array_diff($preEmailCcs,$emailCcs);
        //em并pre，新增
        //$add = array_diff($emailCcs,$preEmailCcs);
        $emails = [];
        foreach ($emailCcs as $email) {
            $emails[] = [
                'user_email' => $email,
                'action' => 'put'
            ];
        }
        return $emails;

    }

    /**
     * 申请分配
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function moreTasks()
    {

        $admin_id = session('admin.id');
        //判断是否已完成目标且不存在未完成的
        $now = $this->model->where('assign_id',$admin_id)->where('status', 'in', '1,2')->where('channel','in',['email','web','chat'])->count();
        if($now){
            $this->error("请先处理完成已分配的工单");
        }
        //判断今天是否完成工作量
        $tasks = ZendeskTasks::whereTime('create_time', 'today')
            ->where(['admin_id' => $admin_id])
            ->select();
        foreach($tasks as $task){
            if($task->surplus_count > 0){
                $this->error("请先完成今天的任务量再进行申请");
            }
        }
        $user_ids = $this->model->where('assign_id','neq',$admin_id)->where('assign_id','>',0)->column('user_id');
        $tickets = $this->model->where(['user_id' => ['not in', $user_ids],'assign_id' => 0,'status' => 1])->order('id desc')->limit(10)->select();
        foreach($tickets as $ticket){
            $task = ZendeskTasks::whereTime('create_time', 'today')
                ->where(['admin_id' => $admin_id, 'type' => $ticket->getType()])
                ->find();
            //修改zendesk的assign_id,assign_time
            $this->model->where('id',$ticket->id)->update([
                'assign_id' => $admin_id,
                'assignee_id' => $task->assignee_id,
                'assign_time' => date('Y-m-d H:i:s', time()),
            ]);
            //分配数目+1
            $task->complete_apply_count = $task->complete_apply_count + 1;
            $task->apply_count = $task->apply_count + 1;
            $task->save();
        }
    }
    /**
     * 申请分配修改
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function moreTasksChange()
    {
        $admin_id = session('admin.id');
        //判断是否已完成目标且不存在未完成的
        $now = $this->model->where('assign_id',$admin_id)->where('status', 'in', '1,2')->where('is_hide',0)->where('channel','in',['email','web','chat'])->count();
        if($now){
            $this->error("请先处理完成近日已分配的工单");
        }
        //判断今天是否完成工作量
        $tasks = ZendeskTasks::whereTime('create_time', 'today')
            ->where(['admin_id' => $admin_id])
            ->select();
        foreach($tasks as $task){
            if($task->surplus_count > 0){
                $this->error("请先完成今天的任务量再进行申请");
            }
        }
        $user_ids = $this->model->where('assign_id','neq',$admin_id)->where('assign_id','>',0)->column('user_id');
        $tickets = $this->model->where(['status' => ['in',[1,2]]])->order('id desc')->select();
        $key = 1;
        foreach($tickets as $ticket){
            //分配10个并且状态为new或者open的分配人是自己的分配
            if($key <= 10 && ($ticket->status == 1 || $ticket->assign_id != $admin_id)){
                $task = ZendeskTasks::whereTime('create_time', 'today')
                    ->where(['admin_id' => $admin_id, 'type' => $ticket->getType()])
                    ->find();
                //修改zendesk的assign_id,assign_time
                $this->model->where('id',$ticket->id)->update([
                    'assign_id' => $admin_id,
                    'assignee_id' => $task->assignee_id,
                    'assign_time' => date('Y-m-d H:i:s', time()),
                ]);
                //分配数目+1
                $task->complete_apply_count = $task->complete_apply_count + 1;
                $task->apply_count = $task->apply_count + 1;
                $task->save();
                $this->model->where('id',$ticket->id)->setField('is_hide',0);
            }
        }
    }

    /**
     * 同步丢失数据使用
     * 同步未常见的工单，由于通知失败导致的
     */
    public function asycTickets()
    {
        set_time_limit(0);
        for($i=123018;$i<123019;$i++){
            (new Notice(request(), ['type' => 'zeelool','id' => $i]))->create();
        }
    }
    public function asycTicketsVoogueme()
    {
        set_time_limit(0);
        for($i=63382;$i<63384;$i++){
            (new Notice(request(), ['type' => 'voogueme','id' => $i]))->create();
        }
    }

    /**
     * 同步丢失数据使用
     * 更新同步未常见的工单，由于通知失败导致的
     */
    public function asycTicketsUpdate()
    {
        $ticketIds = (new Notice(request(), ['type' => 'zeelool']))->asyncUpdate();
        foreach($ticketIds as $ticketId){
            (new Notice(request(), ['type' => 'zeelool','id' => $ticketId]))->update();
            echo $ticketId."\r\n";
        }
    }
    public function asycTicketsVooguemeUpdate()
    {
        $ticketIds = (new Notice(request(), ['type' => 'voogueme']))->asyncUpdate();
        foreach($ticketIds as $ticketId){
            (new Notice(request(), ['type' => 'voogueme','id' => $ticketId]))->update();
            echo $ticketId."\r\n";
        }
    }

    /**
     * 同步所有数据
     * @throws \Exception
     */
    public function asycTicketsAll()
    {
        $tickets = $this->model->where('is_hide',0)->order('id asc')->select();
        foreach($tickets as $ticket){
            $ticketId = $ticket->ticket_id;
            if($ticket->type == 1){
                (new Notice(request(), ['type' => 'zeelool','id' => $ticketId]))->update();
            }elseif($ticket->type == 2){
                (new Notice(request(), ['type' => 'voogueme','id' => $ticketId]))->update();
            }
            echo $ticketId."\r\n";
        }
    }
    public function asycTicketsAll2()
    {
        $tickets = $this->model->where('id','between',[33500,34660])->order('id asc')->select();
        foreach($tickets as $ticket){
            $ticketId = $ticket->ticket_id;
            if($ticket->type == 1){
                (new Notice(request(), ['type' => 'zeelool','id' => $ticketId]))->update();
            }elseif($ticket->type == 2){
                (new Notice(request(), ['type' => 'voogueme','id' => $ticketId]))->update();
            }
            echo $ticketId."\r\n";
        }
    }
    public function asycTicketsAll3()
    {
        $tickets = $this->model->where('id','between',[35507,37937])->order('id asc')->select();
        foreach($tickets as $ticket){
            $ticketId = $ticket->ticket_id;
            if($ticket->type == 1){
                (new Notice(request(), ['type' => 'zeelool','id' => $ticketId]))->update();
            }elseif($ticket->type == 2){
                (new Notice(request(), ['type' => 'voogueme','id' => $ticketId]))->update();
            }
            echo $ticketId."\r\n";
        }
    }
}
