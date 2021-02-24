<?php

namespace app\admin\controller\zendesk;

use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\zendesk\ZendeskPosts;
use app\admin\model\zendesk\ZendeskTasks;
use app\common\controller\Backend;
use app\admin\model\zendesk\ZendeskTags;
use app\admin\model\zendesk\ZendeskAgents;
use app\admin\model\zendesk\ZendeskComments;
use app\admin\model\zendesk\ZendeskMailTemplate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use League\HTMLToMarkdown\HtmlConverter;
use Think\Log;


/**
 *
 *
 * @icon fa fa-circle-o
 */
class Zendesk extends Backend
{
    protected $model = null;
    protected $relationSearch = true;
    protected $noNeedLogin = ['asycTicketsUpdate','asycTicketsVooguemeUpdate','asycTicketsAll','asycTicketsAll2','asycTicketsAll3','asyncTicketHttps'];
    protected $noNeedRight=['zendesk_export,email_toload_more,order_toload_more'];
    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    //protected $noNeedRight = ['edit_recipient'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\Zendesk;
        $this->ordernodedeltail = new \app\admin\model\order\order\Ordernodedeltail();
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
        //是否有同步权限
        $artificial_synchronous = $this->auth->check('zendesk/zendesk/artificial_synchronous');
        $this->view->assign('artificial_synchronous',$artificial_synchronous);

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
                $now_admin_id = session('admin.id');
                $map[] = ['exp', Db::raw("zendesk.due_id=$now_admin_id")];
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
                        $update_time = $filter['zendesk_update_time'] ?? '';
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
                        $map['zendesk.is_hide'] = 1;
                        $map['zendesk.status'] = ['in', [1, 2]];
                        break;
                }
            }
        
            //承接人筛选
            if($filter['assign_id']){
                $map['zendesk.assign_id'] = $filter['assign_id'];
                unset($filter['assign_id']);
            }
            //处理人筛选
            if($filter['due_id']){
                $map['zendesk.due_id'] = $filter['due_id'];
                unset($filter['due_id']);
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
            //$orderSet = 'priority desc,zendesk_update_time asc,id asc';
            $orderSet = 'zendesk_update_time asc';
            if($me_task == 2){
                //$orderSet = 'priority desc,zendesk_update_time asc,id asc';
                $orderSet = 'zendesk_update_time asc';
            }
            if($sort != 'zendesk.id' && $sort){
                $orderSet = "{$sort} {$order}";
            }
            $total = $this->model
                ->with('admin')
                ->where($where)
                ->where($map)
                ->where($andWhere)
                ->where('channel','in',['email','web','chat'])
                ->count();

            $list = $this->model
                ->with('admin')
                ->where($where)
                ->where($map)
                ->where($andWhere)
                ->where('channel','in',['email','web','chat'])
                ->order($orderSet)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $admin = Db::name('admin')->column('nickname','id');
            foreach($list as $k=>$v){
                $list[$k]['assign_id_nickname'] = $admin[$v['assign_id']];
                $list[$k]['due_id_nickname'] = $admin[$v['due_id']];
            }
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
                    }elseif($type == 3){
                        $siteName = 'nihaooptical';
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
                    $findEmail = $this->model->where('email',$params['email'])->find();
                    if(!$findEmail){
                        $emailName = strstr($params['email'],'@',true);
                        $createData['requester']['name'] = $emailName;
//
//                        (new Notice(request(), ['type' => $siteName]))->createUser(
//                            ['user' => ['name' => $params['email'], 'email' => $params['email']]]
//                        );
                    }else{
                        $createData['requester']['name'] = $findEmail->username;
                    }
                    //有抄送，添加抄送
                    if ($params['email_cc']) {
                        $email_ccs = $this->emailCcs($params['email_cc'], []);
                        $createData['email_ccs'] = $email_ccs;
                    }
                    //获取签名
                    $sign = Db::name('zendesk_signvalue')->where('site',$type)->value('signvalue');
                    //获取zendesk用户的昵称
                    $zendesk_nickname = Db::name('zendesk_agents')->where('type',$type)->where('admin_id',session('admin.id'))->value('nickname');
                    $zendesk_nickname = $zendesk_nickname ? $zendesk_nickname : $siteName;
                    //替换签名中的昵称
                    if(strpos($sign,'{{agent.name}}')!==false){
                        $sign = str_replace('{{agent.name}}',$zendesk_nickname,$sign);
                    }             
                    $sign = $sign ? $sign : '';
                    //替换回复内容中的<p>为<span style="display:block">,替换</p>为</span>
                    if(strpos($sign,'<p>')!==false){
                        $sign = str_replace('<p>','<span style="display:block">',$sign);
                    } 
                    if(strpos($sign,'</p>')!==false){
                        $sign = str_replace('</p>','</span>',$sign);
                    } 
                    
                    $priority = config('zendesk.priority')[$params['priority']];
                    if ($priority) {
                        $createData['priority'] = $priority;
                    }
                    $createData['subject'] = $params['subject'];
                    //由于编辑器或默认带个<br>,所以去除标签判断有无值
                    if (strip_tags($params['content'])) {
//                        $converter = new HtmlConverter();
//                        $createData['comment']['body'] = $converter->convert($body);
                        $createData['comment']['html_body'] = $params['content'].$sign;
                    }
                    //file_put_contents('/www/wwwroot/mojing/runtime/log/111.txt','add:' . $params['content'].$sign."\r\n",FILE_APPEND);
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
                        'assign_id' => session('admin.id'),
                        'email_cc' => $params['email_cc'],
                        'zendesk_update_time' => date('Y-m-d H:i:s',time())
                    ]);
                    $zid = $zendesk->id;
                    //评论表添加内容,有body时添加评论，修改状态等不添加
                    if ($params['content']) {
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
                            'attachments' => $params['image'],
                            'mail_template_id' => $params['mail_template_id'],
                            'platform'=>$type
                        ]);
                        ZendeskTasks::whereTime('create_time', 'today')
                            ->where([
                                'admin_id' => session('admin.id'),
                                'type' => $type,
                            ])
                            ->setInc('reply_count',1);
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
        //站点类型，默认zeelool，1：zeelool，2：voogueme, 3:nihao
        $type = input('type',1);
        //获取所有的消息模板
        //获取所有的消息模板
        $templateAll = ZendeskMailTemplate::where([
            'template_platform' => $type,
            'template_permission' => 1,
            'is_active' => 1])
            ->whereOr('template_permission=2 and is_active =1 and create_person = "'. session('admin.nickname').'"')
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
        $status = input('param.status');
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


        $siteName = 'zeelool';
        if($ticket->type == 2){
            $siteName = 'voogueme';
        } elseif($ticket->type == 3){
            $siteName = 'nihaooptical';
        }
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
                    //获取签名
                    $sign = Db::name('zendesk_signvalue')->where('site',$ticket->type)->value('signvalue');
                    //获取zendesk用户的昵称
                    $zendesk_nickname = Db::name('zendesk_agents')->where('admin_id',session('admin.id'))->value('nickname');
                    $zendesk_nickname = $zendesk_nickname ? $zendesk_nickname : $siteName;
                    //替换签名中的昵称
                    if(strpos($sign,'{{agent.name}}')!==false){
                        $sign = str_replace('{{agent.name}}',$zendesk_nickname,$sign);
                    }
                    $sign = $sign ? $sign : '';
                    //替换回复内容中的<p>为<span style="display:block">,替换</p>为</span>
                    if(strpos($params['content'],'<p>')!==false){
                        $params['content'] = str_replace('<p>','<span style="display:block">',$params['content']);
                    }
                    if(strpos($params['content'],'</p>')!==false){
                        $params['content'] = str_replace('</p>','</span>',$params['content']);
                    }

                    $priority = config('zendesk.priority')[$params['priority']];
                    if ($priority) {
                        $updateData['priority'] = $priority;
                    }
                    //由于编辑器或默认带个<br>,所以去除标签判断有无值
                    if (strip_tags($params['content'])) {
//                        $converter = new HtmlConverter();
//                        $updateData['comment']['body'] = $converter->convert($body);
                        $updateData['comment']['html_body'] = $params['content'].$sign;
                    }

                    //file_put_contents('/www/wwwroot/mojing/runtime/log/111.txt','edit:' . $params['content'].$sign."\r\n",FILE_APPEND);
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
                        'due_id' => 0,
                        'email_cc' => $params['email_cc'],
                        'is_hide' => 1,
                        'zendesk_update_time' => date('Y-m-d H:i:s',time())
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
                            'attachments' => $params['image'],
                            'platform'=>$ticket->type
                        ]);
                        ZendeskTasks::whereTime('create_time', 'today')
                            ->where([
                                'admin_id' => session('admin.id'),
                                'type' => $ticket->type,
                            ])
                            ->setInc('reply_count',1);
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
        foreach ($comments as $comment){
            if($comment->is_admin == 1){
                //获取签名
                $sign = Db::name('zendesk_signvalue')->where('site',$ticket->type)->value('signvalue');
                //获取当前评论的用户的昵称
                $zendesk_nickname = Db::name('zendesk_agents')->where('admin_id',$comment->due_id)->value('nickname');
                $zendesk_nickname = $zendesk_nickname ? $zendesk_nickname : $siteName;
                //替换签名中的昵称
                if(strpos($sign,'{{agent.name}}')!==false){
                    $sign = str_replace('{{agent.name}}',$zendesk_nickname,$sign);
                }
                $comment->sign= $sign ? $sign : '';
            }
        }
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
        $recentTickets_count = $this->model
            ->where(['user_id' => $ticket->user_id, 'type' => $ticket->type])
            ->where('id', 'neq', $ids)
            ->count();


        //获取所有的消息模板
        $templateAll = ZendeskMailTemplate::where([
            'template_platform' => $ticket->type,
            'template_permission' => 1,
            'is_active' => 1])
            ->whereOr('template_permission=2 and is_active =1 and create_person = '. session('admin.id'))
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
            $site =1;
            $orderModel = new \app\admin\model\order\order\Zeelool;
            $customer_entity = Db::connect('database.db_zeelool');
        }elseif($ticket->type == 2){
            $site =2;
            $orderModel = new \app\admin\model\order\order\Voogueme;
            $customer_entity = Db::connect('database.db_voogueme');
        }else{
            $site =3;
            $orderModel = new \app\admin\model\order\order\Nihao;
            $is_vip = 0;
        }

        //查询该用户是否是会员
        if ($customer_entity){
            $is_vip = $customer_entity->table('customer_entity')->where('entity_id',$ticket->user_id)->value('is_vip');
            if (empty($is_vip)){
                $is_vip = 0;
            }
        }

        $orders = $orderModel
//            ->alias('ord')
//            ->join(['fa_order_process=>pro'],'ord.id = pro.order_id')
            ->where('customer_email',$ticket->email)
            ->order('entity_id desc')
            ->field('increment_id,created_at,order_currency_code,status,entity_id')
            ->limit(5)
            ->select();
        $orders_count = $orderModel
            ->where('customer_email',$ticket->email)
            ->count();
        $orders = collection($orders)->toArray();
        foreach ($orders as $key=>$item){
            $orders[$key]['track_number'] = Db::connect('database.db_mojing_order')->table('fa_order_process')->where('entity_id',$item['entity_id'])->value('track_number');
            //查询该订单下是否有工单
            $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();
            $swhere = [];
            $swhere['platform_order'] = ['eq', $item['increment_id']];
            $swhere['work_platform'] = $site;
            $swhere['work_status'] = ['not in', [0, 4, 6]];
            $orders[$key]['workorder_list'] = $workorder->where($swhere)->select();
        }
//        foreach ($orders as $key=>$ite){
//            $model =  Db::connect('database.db_mojing_order');
//            $find_value = $model->table('fa_order')->where('increment_id',$ite['increment_id'])->select();
//            dump($find_value);die();
//        }

//        dump(collection($orders)->toArray());die();
        $btn = input('btn',0);

        //查询魔晶账户
        // $admin = new \app\admin\model\Admin();
        // $username = $admin->where('status','normal')->column('nickname','id');
        $order_platform =1;
        $this->view->assign('order_platform', $order_platform);

        $this->view->assign(compact('tags', 'ticket', 'comments', 'tickets', 'recentTickets', 'templates','orders','btn'));
        $this->view->assign('rows', $row);
        $this->view->assign('is_vip', $is_vip);
        $this->view->assign('ids', $ids);
        $this->view->assign('status', $status);

        $this->view->assign('orders_countds', $orders_count);
        $this->view->assign('recentTickets_count', $recentTickets_count);
        // $this->view->assign('username', $username);
        $this->view->assign('orderUrl',config('zendesk.platform_url')[$ticket->type]);
        return $this->view->fetch();
    }
    //邮件加载更多
    public function email_toload_more(){
        $this->view->engine->layout(false);
        $data = input();

        $page = $data['page']?$data['page']:1;
        //获取该用户最新的5条ticket
        $recentTickets = $this->model
            ->where(['user_id' => $data['user_id'], 'type' => $data['type']])
            ->where('id', 'neq', $data['ids'])
            ->field('ticket_id,id,username,subject,status')
            ->order('id desc')
            ->paginate(5)->toArray();
        $this->assign('recentTickets',$recentTickets['data']);
        return $this->view->fetch();
    }

    //订单加载更多
    public function order_toload_more(){
        $this->view->engine->layout(false);
        $data = input();
        if($data['type'] == 1){
            $orderModel = new \app\admin\model\order\order\Zeelool;
        }elseif($data['type'] == 2){
            $orderModel = new \app\admin\model\order\order\Voogueme;
        }else{
            $orderModel = new \app\admin\model\order\order\Nihao;
        }
        $page = $data['page']?$data['page']:1;
        $orders = $orderModel
            ->where('customer_email',$data['email'])
            ->order('entity_id desc')
            ->field('increment_id,created_at,order_currency_code,status,entity_id')
            ->paginate(5)->toArray();
        $this->assign('orders',$orders['data']);
        $this->assign('entity_id',$data['entity_id']);
        $this->view->assign('orderUrl',config('zendesk.platform_url')[$data['type']]);
        return $this->view->fetch();
    }

    /**
     *
     * 物流节点
     */
    public function logistics_node(){
        $entity_id = input('param.entity_id');
//        $site = input('param.order_platform');
        $site = 1;

        //获取订单信息对应的所有物流信息
        $courier = Db::name('order_node_courier')
            ->alias('a')
            ->join(['fa_order_node' => 'b'], 'a.order_id=b.order_id')
            ->where('a.order_id',$entity_id)->where('a.site',$site)
            ->order('create_time desc')
            ->field('a.content,a.create_time,a.site,a.track_number,b.shipment_data_type')
            ->select();
        $courier_one  = $courier[0];
        unset($courier[0]);
        $courier_two = array_values($courier);
        $this->assign('courier_one',$courier_one);
        $this->assign('courier_two',$courier_two);
        return $this->view->fetch();
    }



    public function order_detail($order_number = null)
    {
        $order_number = input('param.ids');

        $new_order = new NewOrder();
        $new_order_process = new NewOrderProcess();
        if (empty($order_number)){
            $this->error('缺少重要参数');
        }
        Log::write("输出订单号");
        Log::write($order_number);
        $new_order_item_process_id =$new_order->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->where('a.increment_id',$order_number)
            ->field('b.id,b.sku,b.distribution_status')
            ->select();
        $new_order_item_process_id2 = array_column($new_order_item_process_id,'sku','id');
        $is_shendan = $new_order_process->where('increment_id',$order_number)->field('check_time,check_status,delivery_time')->find();
        //子单节点日志
        foreach ($new_order_item_process_id as $k=>$v){
            $distribution_log[$v['id']] = Db::name('distribution_log')->where('item_process_id',$v['id'])->select();
        }

        $new_order_item_process_id1 =array_column($new_order_item_process_id, 'id');
        $distribution_log_times = Db::name('distribution_log')
            ->where('item_process_id','in',$new_order_item_process_id1)
            ->where('distribution_node',1)
            ->order('create_time asc')
            ->column('create_time');

        //查询订单详情
        $ruleList = collection($this->ordernodedeltail ->where(['order_number' => ['eq', $order_number]])->order('node_type asc')->field('node_type,create_time,handle_user_name,shipment_type,track_number')->select())->toArray();

        $new_ruleList = array_column($ruleList, NULL, 'node_type');
        $key_list = array_keys($new_ruleList);

        $id = $this->request->get('id');
        $label = $this->request->get('label', 1);

        $this->view->assign(compact('order_number', 'id', 'label'));
        $this->view->assign("list", $new_ruleList);
        $this->view->assign("is_shendan", $is_shendan);
        $this->view->assign("distribution_log_times", $distribution_log_times);
        $this->view->assign("distribution_log", $distribution_log);
        $this->view->assign("key_list", $key_list);
        $this->view->assign("new_order_item_process_id2", $new_order_item_process_id2);
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
        //查询原订单的站点
        $type = $this->model->where('ticket_id',$pid)->value('type');
        if ($ticket_id == $pid) {
            $this->error("You selected the same ticket as source and target: #{$ticket_id}. You cannot merge a ticket into itself.
Please close this window and try again.");
        }
        //合并到的信息
        $ticket = $this->model->where('ticket_id', $ticket_id)->where('type',$type)->field('id,ticket_id,subject')->find();
        if (!$ticket) {
            $this->error("You are unable to merge into #{$ticket_id}. Tickets don't find, tickets that are shared with other accounts, and tickets you don't have access to cannot be merged into.
Please close this window and try again.");
        }
        //合并的最后一条评论
        $comment = $this->model->where('ticket_id', $pid)->where('type',$type)->with('lastComment')->find();
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
            }else{
                $siteName = 'nihaooptical';
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
                    'assign_id' => session('admin.id'),
                    'due_id' => session('admin.id'),
                    'zendesk_update_time' => date('Y-m-d H:i:s',time())
                ]);

                ZendeskComments::create([
                    'ticket_id' => $ids,
                    'zid' => $zid,
                    'author_id' => $agent_id,
                    'body' => strip_tags($source_comment),
                    'html_body' => $source_comment,
                    'is_public' => $source_comment_is_public,
                    'platform'=>$type,
                    'is_admin' => 1
                ]);
                //合并的添加评论content
                $this->model->where('ticket_id', $ticket)->update([
                    'assignee_id' => $agent_id,
                    'assign_id' => session('admin.id'),
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
                    'platform'=>$type,
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
        $now = $this->model->where('due_id',$admin_id)->where('is_hide',0)->where('status', 'in', '1,2')->where('channel','in',['email','web','chat'])->count();
        if($now >= 5){
            $this->error("请先处理完成已分配的工单");
        }
        //获取用户assignee_id 以及对应站点
        $task = ZendeskTasks::whereTime('create_time', 'today')
                ->where(['admin_id' => $admin_id])
                ->find();
        //按照更新时间查询未分配的open和new的邮件
        $tickets = Db::name('zendesk')->where('status', 'in', '1,2')->where(['is_hide'=>1])->where('type',$task->type)->where('channel', '<>', 'voice')->order('zendesk_update_time','asc')->limit(10)->select();
        $i = 0;
        foreach($tickets as $item){
            if($i<10){
                $recipient = Db::name('zendesk')
                    ->alias('z')
                    ->join('fa_admin a','z.assign_id=a.id')
                    ->join('fa_zendesk_agents za','z.assign_id = za.admin_id')
                    ->where(['a.status'=>['neq','hidden'],'za.count'=>['neq',0],'za.type'=>$item['type'],'z.id'=>$item['id']])
                    ->field('z.assign_id,za.agent_id,z.id')
                    ->find();
                if($recipient['id']){
                    $this->model->where('id',$item['id'])->update([
                        'is_hide' => 0,
                        'due_id' => $admin_id,
                        'assign_time' => date('Y-m-d H:i:s', time()),
                    ]);
                    //分配数目+1
                    $task->complete_apply_count = $task->complete_apply_count + 1;
                    $task->apply_count = $task->apply_count + 1;
                    $task->save();
                    $i++;
                }else{
                    //修改zendesk的assign_id,assign_time
                    $this->model->where('id',$item['id'])->update([
                        'is_hide' => 0,
                        'due_id' => $admin_id,
                        'assign_id' => $admin_id,
                        'assignee_id' => $task->assignee_id,
                        'assign_time' => date('Y-m-d H:i:s', time()),
                    ]);
                    //分配数目+1
                    $task->complete_apply_count = $task->complete_apply_count + 1;
                    $task->apply_count = $task->apply_count + 1;
                    $task->save();
                    $i++;
                }
            }else{
                break;
            }
        }
        if ($i == 10) {
            $this->success("申请成功");
        } else {
            $this->error("申请失败");
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
        $ticketIds = $this->model->where(['status' => ['in','1,2'],'type' => 1])->column('ticket_id');
        foreach($ticketIds as $ticketId){
            (new Notice(request(), ['type' => 'zeelool','id' => $ticketId]))->update();
            echo $ticketId."\r\n";
        }
    }
    public function asycTicketsVooguemeUpdate()
    {
        $ticketIds = $this->model->where(['status' => ['in','1,2'],'type' => 2])->column('ticket_id');
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

    /*
     * 手动同步数据方法
     * 主管，经理有权限
     * */
    public function artificial_synchronous()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            switch ($params['site']) {
                case 1:
                    $site_str = 'zeelool';
                    break;
                case 2:
                    $site_str = 'voogueme';
                    break;
                case 3:
                    $site_str = 'nihaooptical';
                    break;
                default:
                    $site_str = '';
                    break;
            }

            if($site_str == ''){
                $this->error("站点匹配错误，请联系技术");
            }
            $intersects = array();
            $diffs = array();
            if(!$params['ticket_id']){
                $this->error("请点击add");
            }
            foreach ($params['ticket_id'] as $val){
                $where['ticket_id'] = $val;
                $where['type'] = $params['site'];
                $tickets = $this->model->where($where)->find();
                if($tickets->id){
                    //存在，更新
                    $intersects[] = $val;
                }else{
                    //不存在，新增
                    $diffs[] = $val;
                }
            }
            if($intersects){
                //更新
                foreach($intersects as $intersect){
                    (new Notice(request(), ['type' => $site_str,'id' => $intersect]))->update();
                }
            }
            if($diffs){
                //新增
                foreach($diffs as $diff){
                    (new Notice(request(), ['type' => $site_str,'id' => $diff]))->create();
                }
            }
            $this->success("同步完成");
        }

        return $this->view->fetch();
    }
    /**
     * https断掉的数据更新
     * @return [type] [description]
     * 1---zeelool
     * 2---voogueme
     * 3---nihaooptical
     */
    public function asyncTicketHttps()
    {
        $ticketIds = (new Notice(request(), ['type' => 'voogueme']))->asyncUpdate();

        //判断是否存在
        $nowTicketsIds = $this->model->where("type",2)->column('ticket_id');

        //求交集的更新

        $intersects = array_intersect($ticketIds, $nowTicketsIds);
        //求差集新增
        $diffs = array_diff($ticketIds, $nowTicketsIds);
        //更新

        //$intersects = array('142871','142869');//测试是否更新
        //$diffs = array('144352','144349');//测试是否新增
        foreach($intersects as $intersect){
            (new Notice(request(), ['type' => 'voogueme','id' => $intersect]))->update();
            echo $intersect.'is ok'."\n";
        }
        //新增
        foreach($diffs as $diff){
            (new Notice(request(), ['type' => 'voogueme','id' => $diff]))->create();
            echo $diff.'ok'."\n";
        }
        echo 'all ok';
        exit;
    }
    /**
     * zendesk签名
     *
     * @Description
     * @author mjj
     * @since 2020/06/18 15:25:29 
     * @return void
     */
    public function signvalue(){
        $signarr = Db::name('zendesk_signvalue')->select();
        $this->view->assign('signarr',$signarr);
        return $this->view->fetch();
    }
    /**
     * 修改zendesk签名
     *
     * @Description
     * @author mjj
     * @since 2020/06/18 16:51:27 
     * @param [type] $site
     * @return void
     */
    public function signvalue_edit(){
        $params = $this->request->post("row/a");
        //查询是否存在
        $is_exist = Db::name('zendesk_signvalue')->where('site',$params['site'])->value('id');
        if($is_exist){
            $result = Db::name('zendesk_signvalue')->where('site',$params['site'])->update(['signvalue'=>$params['signvalue']]);
        }else{
            $arr['site'] = $params['site'];
            $arr['signvalue'] = $params['signvalue'];
            $result = Db::name('zendesk_signvalue')->insert($arr);
        }
        
        if($result){
            $this->success('操作成功！！',url('zendesk/signvalue'));
        }else{
            $this->error('操作失败！！');
        }
    }
    /**
     * 修改承接人
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-07-18 19:10:00
     * @return void
     */
    public function edit_recipient($ids=null)
    {
        if($this->request->isAjax()){
            $params = $this->request->post("row/a");
            if(!$params['id']){
                $this->error('承接人不存在，请重新尝试');
            }
            //查询当前邮件原本的承接人数据
            $agent_id = Db::name('zendesk_agents')->where('admin_id',$params['id'])->value('agent_id');
            if($params['type'] == 1 || $params['type'] == 3){
                //修改承接人
                $data['assign_id']  = $params['id'];
                $data['assignee_id']  = $agent_id;
            }
            if($params['type'] == 2 || $params['type'] == 3){
                //修改处理人
                $data['due_id']     = $params['id'];
            }
            $result = $this->model->where(['id'=>$ids])->update($data);
            if($result){
                $this->success('修改成功');
            }
        }
        $issueList = Db::name('zendesk_agents')->alias('z')->join('fa_admin a','z.admin_id=a.id')->column('z.admin_id,a.nickname');
        $this->assign('issueList',$issueList);
        return $this->view->fetch();
    }
    /**
     * 批量修改承接人
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-08-03 10:04:18
     * @return void
     */
    public function batch_edit_recipient($ids=null)
    {
        if($this->request->isAjax()){
            $type_arr = $this->model->where('id','in',$ids)->column('type');
            $type_arr = array_unique($type_arr);
            if(count($type_arr) == 1){
                $params = $this->request->post("row/a");
                if(!$params['id']){
                    $this->error('承接人不存在，请重新尝试');
                }
                //查询当前邮件原本的承接人数据
                $agent_id = Db::name('zendesk_agents')->where('admin_id',$params['id'])->value('agent_id');
                if($params['type'] == 1 || $params['type'] == 3){
                    //修改承接人
                    $data['assign_id']  = $params['id'];
                    $data['assignee_id']  = $agent_id;
                }
                if($params['type'] == 2 || $params['type'] == 3){
                    //修改处理人
                    $data['due_id']     = $params['id'];
                }
                $result = $this->model->where('id','in',$ids)->update($data);
                if($result){
                    $this->success('修改成功');
                }
            }else{
                $this->error('不同站点的工单不能批量修改承接人');
            }
        }
        $issueList = Db::name('zendesk_agents')->alias('z')->join('fa_admin a','z.admin_id=a.id')->column('z.admin_id,a.nickname');
        $this->assign('issueList',$issueList);
        return $this->view->fetch('edit_recipient');
    }

    /**
     *  邮件工单列表导出功能
     *
     *
     */
    public function zendesk_export()
    {

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');

        $map = [];
        if ($ids) {
            $map['zendesk.id'] = ['in', $ids];
        }

        $this->request->filter(['strip_tags']);
        $tags = ZendeskTags::column('name', 'id');
        $this->view->assign('tags', $tags);

        //如果发送的来源是Selectpage，则转发到Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        $filter = json_decode($this->request->get('filter'), true);

        $andWhere = '';
        $me_task = $filter['me_task'];
        if ($me_task == 1) { //我的所有任务
            unset($filter['me_task']);
            $map['zendesk.assign_id'] = session('admin.id');
        } elseif ($me_task == 2) { //我的待处理任务
            unset($filter['me_task']);
            $now_admin_id = session('admin.id');
            $map[] = ['exp', Db::raw("zendesk.due_id=$now_admin_id")];
            $map['zendesk.status'] = ['in', [1, 2]];
            $map['zendesk.is_hide'] = 0;
            $taskCount = ZendeskTasks::where('admin_id', session('admin.id'))->value('target_count');
        }
        //类型筛选
        if ($filter['status_type']) {
//                待处理：new;open状态下的工单
//                新增：update时间为选择时间，new、open状态的工单
//                已处理：public comment
//                待分配：没有承接人的工单
            $status_type = $filter['status_type'];
            unset($filter['status_type']);
            switch ($status_type) {
                case 1:
                    $map['zendesk.status'] = ['in', [1, 2]];
                    break;
                case 2:
                    $update_time = $filter['zendesk_update_time'] ?? '';
                    if (!$update_time) {
                        $this->error('请选择更新时间');
                    }
                    $map['zendesk.status'] = ['in', [1, 2]];
                    break;
                case 3:
                    //获取public =1 is_admin=1的zid列表
                    $zids = ZendeskComments::where(['is_public' => 1, 'is_admin' => 1])->column('zid');
                    $map['zendesk.id'] = ['in', $zids];
                    break;
                case 4:
                    //获取所有的账号admin_id
                    $map['zendesk.is_hide'] = 1;
                    $map['zendesk.status'] = ['in', [1, 2]];
                    break;
            }
        }

        //承接人筛选
        if ($filter['assign_id']) {
            $map['zendesk.assign_id'] = $filter['assign_id'];
            unset($filter['assign_id']);
        }
        //处理人筛选
        if ($filter['due_id']) {
            $map['zendesk.due_id'] = $filter['due_id'];
            unset($filter['due_id']);
        }
        if ($filter['tags']) {
            $andWhere = "FIND_IN_SET({$filter['tags']},tags)";
            unset($filter['tags']);
        }
        if ($filter['content']) {
            $comments = ZendeskComments::where('body', 'like', '%' . $filter['content'] . '%')->column('ticket_id');
            $tickets = $this->model->where('subject', 'like', '%' . $filter['content'] . '%')->column('ticket_id');
            $ticket_ids = array_merge($comments, $tickets);
            $map['zendesk.ticket_id'] = ['in', $ticket_ids];
            unset($filter['content']);
        }
        $this->request->get(['filter' => json_encode($filter)]);
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        //默认使用
        $orderSet = 'zendesk_update_time asc';
        if ($me_task == 2) {
            $orderSet = 'zendesk_update_time asc';
        }
        if ($sort != 'zendesk.id' && $sort) {
            $orderSet = "{$sort} {$order}";
        }

        $list = Db::table("fa_zendesk")->alias("zendesk")
            ->join(['fa_admin' => 'admin'], 'zendesk.assign_id=admin.id','LEFT')
            ->join(['fa_admin' => 'admin_due'], 'zendesk.due_id=admin_due.id','LEFT')
            ->where($where)
            ->where($map)
            ->where($andWhere)
            ->where('channel', 'in', ['email', 'web', 'chat'])
            ->field("zendesk.id,zendesk.ticket_id,zendesk.type,zendesk.channel,zendesk.email,zendesk.username,zendesk.user_id,zendesk.to_email,zendesk.priority,zendesk.status,zendesk.tags,zendesk.subject,zendesk.raw_subject,zendesk.assignee_id,zendesk.assign_id,zendesk.due_id,zendesk.email_cc,zendesk.rating,zendesk.rating_type,zendesk.comment,zendesk.reason,zendesk.create_time,zendesk.update_time,zendesk.assign_time,zendesk.shell,zendesk.is_hide,zendesk.zendesk_update_time,zendesk.recipient
            ,admin.nickname as 'assign_nickname' ,admin_due.nickname as 'due_nickname'")
            ->order($orderSet)
            ->select();
         //获取所有tags数据
        $tags_list=(new ZendeskTags())->tags_list();
        $template_list=(new ZendeskMailTemplate())->template_list();
        $list = collection($list)->toArray();

        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "站点")
            ->setCellValue("B1", "Ticket ID")
            ->setCellValue("C1", "发送人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "承接人")
            ->setCellValue("E1", "处理人");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "Subject")
            ->setCellValue("G1", "Tags");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "Status")
            ->setCellValue("I1", "Priority")
            ->setCellValue("J1", "渠道");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "创建时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("L1", "更新时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("M1", "回复次数");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("N1", "首次响应时长");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("O1", "是否客服发出");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("P1", "kf首次回复时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("Q1", "回复模板");
        foreach ($list as $key => $value) {
            $arr=explode(",",$value['tags']);
            $tags_name="";
            foreach ($arr as $arrK=>$arrV){
                $tags_name.= $tags_list[$arrV].',';
            }
            if (!empty($tags_name)){
                $tags_name = rtrim($tags_name, ',');
            }
            $value['tags_name'] = $tags_name;

            switch ($value['type']) {
                case 1:
                    $value['site_type'] = 'Zeelool';
                    break;
                case 2:
                    $value['site_type'] = 'Voogueme';
                    break;
                case 3:
                    $value['site_type'] = 'Nihao';
                    break;
                case 4:
                    $value['site_type'] = 'meeloog';
                    break;
                case 5:
                    $value['site_type'] = 'wesee';
                    break;
                case 6:
                    $value['site_type'] = 'Wesee';
                    break;
                case 9:
                    $value['site_type'] = 'zeelool_es';
                    break;
                case 10:
                    $value['site_type'] = 'zeelool_de';
                    break;
                case 11:
                    $value['site_type'] = 'zeelool_jp';
                    break;
                default:
                    $value['site_type'] = '';
                    break;
            }


            switch ($value['priority']) {
                case 0:
                    $value['priority_name'] = '无';
                    break;
                case 1:
                    $value['priority_name'] = 'Low';
                    break;
                case 2:
                    $value['priority_name'] = 'Normal';
                    break;
                case 3:
                    $value['priority_name'] = 'High';
                    break;
                case 4:
                    $value['priority_name'] = 'Urgent';
                    break;
                default:
                    $value['priority_name'] = '';
                    break;
            }
            switch ($value['status']) {
                case 1:
                    $value['status_name'] = 'New';
                    break;
                case 2:
                    $value['status_name'] = 'Open';
                    break;
                case 3:
                    $value['status_name'] = 'Pending';
                    break;
                case 4:
                    $value['status_name'] = 'Solved';
                    break;
                case 5:
                    $value['status_name'] = 'Close';
                    break;
                default:
                    $value['status_name'] = '';
                    break;
            }


            //处理回复数据
            $comments_map['zid'] = $value['id'];
            $comments_list=Db::table('fa_zendesk_comments')
                ->alias('zendesk_comments')
                ->join(['fa_admin' => 'admin_due'], 'zendesk_comments.due_id=admin_due.id','LEFT')
                ->where($comments_map)
                ->field("zendesk_comments.is_admin,zendesk_comments.create_time,zendesk_comments.is_created,zendesk_comments.mail_template_id,zendesk_comments.due_id,admin_due.nickname as 'due_nickname'")//处理人拼接
                ->order("zendesk_comments.id  ")
                ->select();

            $due_name=array();
            //邮件工单处理人+回复处理人
            array_push($due_name,$value['due_nickname']);
            //有回复数据时,计算回复数据
            if (!empty($comments_list)){
                //回复次数
                $replies=0;
                $admin_data=array();
                $template_info="";
                foreach ($comments_list as $commentsK=>$commentsV){
                    if ($commentsV['is_admin']=='1'){
                        $replies+=1;//客服人员回复次数
                        array_push($admin_data,$commentsV['create_time']);
                        array_push($due_name,$commentsV['due_nickname']);
                    }
                    $template_info.=$template_list[$commentsV['mail_template_id']].",";
                }
                $is_admin=$comments_list[0]['is_admin']=='1'?'是':'否';
                //预防没有客服人员回复,首次回复时间处理
                if (!empty($admin_data)) {
                    $value['reply_minutes'] = round((strtotime($admin_data[0]) - strtotime($value['create_time'])) / 60, 1);
                    //客服首次回复时间
                    $value['fist_time']=$admin_data[0];
                }

                if (!empty($template_info)){
                    $template_info = rtrim($template_info, ',');
                }
                //回复模板
                $value['template_info']=$template_info;
                $value['replies']=$replies;
                $value['is_admin']=$is_admin;
            }

            if (!empty($due_name)){
                //对处理人进行去重复
                $due_name=array_unique($due_name);
                $due_name_trim = rtrim(implode(",", $due_name), ',');
                $value['due_nickname']=$due_name_trim;
            }
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['site_type'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['ticket_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['email']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['assign_nickname']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['due_nickname']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['subject']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['tags_name']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['status_name']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['priority_name']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['channel']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['update_time']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['replies']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['reply_minutes']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['is_admin']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['fist_time']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['template_info']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);

        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(50);


        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '邮件工单列表' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }


    function uniquArr($array){
        $result = array();
        foreach($array as $k=>$val){
            $code = false;
            foreach($result as $_val){
                if($_val['id'] == $val['id']){
                    $code = true;
                    break;
                }
            }
            if(!$code){
                $result[]=$val;
            }
        }
        return $result;
    }



}
