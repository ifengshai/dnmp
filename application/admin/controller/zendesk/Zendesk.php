<?php

namespace app\admin\controller\zendesk;

use app\admin\model\Admin;
use app\common\controller\Backend;
use app\admin\model\zendesk\ZendeskTags;
use app\admin\model\zendesk\ZendeskAgents;
use app\admin\model\zendesk\ZendeskComments;
use think\Db;
use think\Exception;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Zendesk extends Backend
{
    protected $model = null;
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\zendesk\Zendesk;

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
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['admin','lastComment'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['admin','lastComment'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

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
        $ticket = $this->model->where('id',$ids)->find();
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
                    $tags = ZendeskTags::where('id','in',$params['tags'])->column('name');
                    $status = config('zendesk.status')[$params['status']];
                    $author_id = $assignee_id = ZendeskAgents::where(['admin_id' => session('admin.id'),'agent_type' => $ticket->type])->value('agent_id');
                    if(!$author_id){
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
                    //修改主题
                    if($params['subject'] != $ticket->subject){
                        $updateData['subject'] = $params['subject'];
                    }
                    $priority = config('zendesk.priority')[$params['priority']];
                    $body = $params['content'];
                    if($priority){
                        $updateData['priority'] = $priority;
                    }
                    //由于编辑器或默认带个<br>,所以去除标签判断有无值
                    if(strip_tags($body)){
                        $updateData['comment']['html_body'] = $body;
                    }
                    if($params['image']){
                        //附件上传
                        $attachments = explode(',',$params['image']);
                        $token = [];
                        foreach($attachments as $attachment){
                            $res = (new Notice(request(),['type' => 'zeelool']))->attachment($attachment);
                            if(isset($res['code'])){
                                throw new Exception($res['message'], 10001);
                            }
                            $token[] = $res;
                        }
                        if($token){
                            $updateData['comment']['uploads'] = $token;
                        }
                    }
                    //私有的
                    if($params['public_type'] == 1){
                        $updateData['comment']['public'] = false;
                    }
                    //开始发送
                    $res = (new Notice(request(),['type' => 'zeelool']))->autoUpdate($ticket->ticket_id,$updateData);
                    if(isset($res['code'])){
                        throw new Exception($res['message'], 10001);
                    }
                    //开始写入数据库
                    $agent_id = ZendeskAgents::where('admin_id',session('admin.id'))->value('agent_id');
                    //更新主表的状态和priority，tags,due_id，assignee_id等
                    $result = $this->model->where('id',$ids)->update([
                        'subject' => $params['subject'],
                        'priority' => $params['priority'],
                        'status' => $params['status'],
                        'tags' => join(',',$params['tags']),
                        'assignee_id' => $agent_id,
                        'due_id' => session('admin.id'),
                    ]);
                    //评论表添加内容,有body时添加评论，修改状态等不添加
                    if(strip_tags($params['content'])){
                        $result = ZendeskComments::create([
                            'ticket_id' => $ticket->ticket_id,
                            'zid' => $ids,
                            'author_id' => $agent_id,
                            'body' => strip_tags($params['content']),
                            'html_body' => $params['content'],
                            'is_public' => $params['public_type'],
                            'is_admin' => 1,
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
                    $this->success('回复成功！！',url('zendesk/index'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //获取所有的tags
        $tags = ZendeskTags::column('name','id');

        $comments = ZendeskComments::where('zid',$ids)->order('id','desc')->select();
        //获取该用户的所有状态不为close，sloved的ticket
        $tickets = $this->model
            ->where(['user_id' => $ticket->user_id,'status' => ['in', [1,2,3]]])
            ->where('id','neq',$ids)
            ->field('ticket_id,id,username,subject')
            ->order('id desc')
            ->select();
        $this->view->assign(compact('tags','ticket','comments','tickets'));
        return $this->view->fetch();
    }

    /**
     * ajax获取ticket的详情
     * @return \think\response\Json
     */
    public function getTicket()
    {
        $ticket_id = input('nid');
        $pid = input('pid');
        //合并到的信息
        $ticket = $this->model->where('ticket_id',$ticket_id)->field('id,ticket_id,subject')->find();
        //合并的最后一条评论
        $comment = $this->model->where('ticket_id',$pid)->with('lastComment')->find();
        $ticket['lastComment'] = $comment->lastComment[0]->html_body;
        return json($ticket);
    }
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
            $data = [
                'ids' => [$ids],
                'target_comment_is_public' => $target_comment_is_public,
                'source_comment_is_public' => $source_comment_is_public,
            ];
            if($target_comment){
                $data['target_comment'] = $target_comment;
            }
            if($source_comment){
                $data['source_comment'] = $source_comment;
            }
            $result = false;
            try {
                //合并工单
                $result = (new Notice(request(),['type' => 'zeelool']))->merge($ticket,$data);
                if(isset($result['code'])){
                    throw new Exception($result['message'], 10001);
                }
                //修改数据库，修改状态
                //获取closed_by_merge的tag的id
                $tagId = ZendeskTags::where('name','closed_by_merge')->value('id');
                //获取被合并的tags
                $tagIds = $this->model->where('ticket_id',$ticket)->value('tags');
                if($tagIds){
                    $tagIds = explode(',',$tagIds);
                    array_unshift($tagIds, $tagId);
                    $tagIds = join(',',$tagIds);
                }else{
                    $tagIds = $tagId;
                }

                $agent_id = ZendeskAgents::where('admin_id',session('admin.id'))->value('agent_id');
                $zid = $this->model->where('ticket_id',$ids)->value('id');
                //被合并的状态closed，添加content，tag：closed_by_merge
                $this->model->where('ticket_id',$ids)->update([
                    'status' => '5',
                    'tags' =>$tagIds,
                    'assignee_id' => $agent_id,
                    'due_id' => session('admin.id'),
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
                $this->model->where('ticket_id',$ticket)->update([
                    'assignee_id' => $agent_id,
                    'due_id' => session('admin.id'),
                ]);
                $zid = $this->model->where('ticket_id',$ticket)->value('id');
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
                $this->success('回复成功！！',url('zendesk/index'));
            } else {
                $this->error(__('No rows were updated'));
            }
        }
        $this->error(__('Parameter %s can not be empty', ''));
    }
}
