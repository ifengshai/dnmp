<?php

namespace app\admin\model\zendesk;

use app\admin\model\Admin;
use think\Db;
use think\Model;
use think\Exception;

class Zendesk extends Model
{
    // 表名
    protected $name = 'zendesk';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [
        'tag_format',
        'status_format',
        'username_format',
        'status_type'
    ];

    protected static function init()
    {
        parent::init();
        //新增后的回调函数，进行任务分配
        self::beforeInsert(function ($zendesk) {
            //如果存在assignee_id,则不需要自动分配
            //判断是否已分配，chat的情况会存在自动分配的情况，所有此处需要判断下
            if($zendesk->user_id){
                $assign_id = $due_id = Zendesk::where('user_id',$zendesk->user_id)->order('id','desc')->value('assign_id');
                $zendesk->assign_id = $assign_id;
                $zendesk->due_id = $due_id;
                $zendesk->assign_time = date('Y-m-d H:i:s',time());
            }elseif($zendesk->channel != 'voice'){  //电话的不自动分配
                self::assignTicket($zendesk);
            }

        });
    }

    /**
     * type获取
     * @return mixed
     */
    public function getType()
    {
        return $this->data['type'];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'assign_id', 'id')->setEagerlyType(0)->joinType('left');
    }

    public function lastComment()
    {
        return $this->hasMany(ZendeskComments::class, 'zid', 'id')->order('id', 'desc')->limit(1);
    }

    public function getStatusFormatAttr($value, $data)
    {
        return config('zendesk.status')[$data['status']];
    }
    public function getStatusTypeAttr($value, $data)
    {
        return [
            1 => '待处理',
            2 => '新增',
            3 => '已处理',
            4 => '待分配'
        ];
    }

    public function getUsernameFormatAttr($value, $data)
    {
        return $data['username'] . '——' . $data['email'];
    }

    public function getTagFormatAttr($value, $data)
    {
        $tagIds = $data['tags'];
        $tags = ZendeskTags::where('id', 'in', $tagIds)->column('name');
        sort($tags);
        return join(',', $tags);
    }

    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name' => '我的全部', 'field' => 'me_task', 'value' => 1],
            ['name' => '我的待处理', 'field' => 'me_task', 'value' => 2],
        ];
    }

    /**
     * 自动分配
     * @param $ticket
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assignTicket($ticket)
    {
        //判断该邮件是否属于之前的老用户
        $preTicket = Zendesk::where(['user_id' => $ticket->user_id, 'assign_id' => ['>', 0], 'type' => $ticket->getType(),'channel' => ['neq','voice']])
            ->order('id', 'desc')
            ->limit(1)
            ->find();
        if (!$preTicket) {
            //无老用户，则分配给最少单的用户
            $task = ZendeskTasks::whereTime('create_time', 'today')
                ->where(['type' => $ticket->getType()])
                ->order('surplus_count', 'desc')
                ->limit(1)
                ->find();
        } else {
            //判断老用户是否在表里面并且目标大于0
            $task = ZendeskTasks::whereTime('create_time', 'today')
                ->where(['admin_id' => $preTicket->assign_id, 'type' => $ticket->getType(),'target_count' => ['>',0]])
                ->find();
        }
        if(!$task){
            //则分配给最少单的用户
            $task = ZendeskTasks::whereTime('create_time', 'today')
                ->where(['type' => $ticket->getType()])
                ->order('surplus_count', 'desc')
                ->limit(1)
                ->find();
        }

        if ($task) {
            //判断该用户是否已经分配满了，满的话则不分配
            if ($task->target_count > $task->complete_count) {
                //修改zendesk的assign_id,assign_time
                $ticket->assign_id = $task->admin_id;
                $ticket->assign_time = date('Y-m-d H:i:s', time());
                $ticket->assignee_id = $task->assignee_id;

                //修改task的字段
                $task->surplus_count = $task->surplus_count - 1;
                $task->complete_count = $task->complete_count + 1;
                $task->complete_apply_count = $task->complete_apply_count + 1;
                $task->save();
            }
        }

    }

    /**
     * 脚本自动运行
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function shellAssignTicket()
    {
        //1，判断今天有无task，无，创建
        $tasks = ZendeskTasks::whereTime('create_time', 'today')->find();
        if (!$tasks) {
            //创建所有的tasks
            //获取所有的agents
            $agents = ZendeskAgents::withCount(['tickets' => function ($query) {
                $query->where('status', 'in', '1,2')->where('channel','in',['email','web','chat']);
            }])->select();
            foreach ($agents as $agent) {
                $target_count = $agent->count - $agent->tickets_count > 0 ? $agent->count - $agent->tickets_count : 0;
                ZendeskTasks::create([
                    'type' => $agent->getType(),
                    'admin_id' => $agent->admin_id,
                    'assignee_id' => $agent->agent_id,
                    'leave_count' => $agent->tickets_count,
                    'target_count' => $target_count,
                    'surplus_count' => $target_count,
                    'complete_count' => 0,
                    'check_count' => $agent->count,
                    'apply_count' => 0,
                    'complete_apply_count' => 0
                ]);
            }
        }
        //获取所有未分配的邮件
        $waitTickets = self::where(['assign_id' => 0,'status' => ['in','0,1,2,3'],'channel' => ['neq','voice']])->select();
        foreach ($waitTickets as $ticket) {
            //电话不分配
            if($ticket->channel == 'voice') continue;
            //判断该邮件是否有老用户
            $preTicket = Zendesk::where(['user_id' => $ticket->user_id, 'assign_id' => ['>', 0], 'type' => $ticket->getType(),'channel' => ['in',['email','web','chat']]])
                ->order('id', 'desc')
                ->limit(1)
                ->find();
            if (!$preTicket) {
                //无老用户，则分配给最少单的用户
                $task = ZendeskTasks::whereTime('create_time', 'today')
                    ->where(['type' => $ticket->getType()])
                    ->order('surplus_count', 'desc')
                    ->limit(1)
                    ->find();
            } else {
                //判断老用户是否在表里面并且目标大于0
                $task = ZendeskTasks::whereTime('create_time', 'today')
                    ->where(['admin_id' => $preTicket->assign_id, 'type' => $ticket->getType(),'target_count' => ['>',0]])
                    ->find();
            }
            if(!$task){
                //则分配给最少单的用户
                $task = ZendeskTasks::whereTime('create_time', 'today')
                    ->where(['type' => $ticket->getType()])
                    ->order('surplus_count', 'desc')
                    ->limit(1)
                    ->find();
            }
            if ($task) {
                //判断该用户是否已经分配满了，满的话则不分配
                if ($task->target_count > $task->complete_count) {
                    //修改zendesk的assign_id,assign_time
                    $ticket->assign_id = $task->admin_id;
                    $ticket->assignee_id = $task->assignee_id;
                    $ticket->assign_time = date('Y-m-d H:i:s', time());
                    $ticket->save();
                    //修改task的字段
                    $task->surplus_count = $task->surplus_count - 1;
                    $task->complete_count = $task->complete_count + 1;
                    $task->complete_apply_count = $task->complete_apply_count + 1;
                    $task->save();
                }
                usleep(1000);
            }
        }
    }

    /**
     * 修改后的分单脚本
     * 一、判断当天是否分配任务，若没有分配任务就在分配任务量表中插入用户的任务量分配信息
     * 二、分配任务：
     *          1.如果有老用户（未离职）处理过邮件，就把任务分配给该老用户
     */
    public static function shellAssignTicketChange1()
    {
        //1，判断今天有无task，无，创建
        $tasks = ZendeskTasks::whereTime('create_time', 'today')->find();
        //设置所有的隐藏
        self::where('id','>=',1)->setField('is_hide',1);
        if (!$tasks) {
            //创建所有的tasks
            //获取所有的agents
            $agents = Db::name('zendesk_agents')->alias('z')->join(['fa_admin'=>'a'],'z.admin_id=a.id')->field('z.*,a.userid')->where('a.status','<>','hidden')->where('z.count','<>',0)->select();
            //查询该用户今天是否休息
            $userlist_arr = array_filter(array_column($agents,'userid'));
            $userlist_str = implode(',',$userlist_arr);
            $time = strtotime(date('Y-m-d 0:0:0',time()));
            //通过接口获取休息人员名单
            $ding = new \app\api\controller\Ding;
            $restuser_arr=$ding->getRestList($userlist_str,$time);
            foreach ($agents as $agent) {
                if(!in_array($agent['admin_id'],$restuser_arr)){
                    //$target_count = $agent->count - $agent->tickets_count > 0 ? $agent->count - $agent->tickets_count : 0;
                     ZendeskTasks::create([
                        'type' => $agent['type'],
                        'admin_id' => $agent['admin_id'],
                        'assignee_id' => $agent['agent_id'],
                        'leave_count' => 0,
                        'target_count' => $agent['count'],
                        'surplus_count' => $agent['count'],
                        'complete_count' => 0,
                        'check_count' => $agent['count'],
                        'apply_count' => 0,
                        'complete_apply_count' => 0
                    ]); 
                }
            }
        }
        //获取所有的open和new的邮件
        $waitTickets = self::where(['status' => ['in','1,2'],'channel' => ['neq','voice']])->order('priority desc,zendesk_update_time asc')->select();
        foreach ($waitTickets as $ticket) {
            //电话不分配
            if($ticket->channel == 'voice') continue;
            $assign_id = $ticket->assign_id ?: 0;
            $account = ZendeskAgents::where(['admin_id' => $ticket->assign_id, 'type' => $ticket->getType()])->value('count');
            //找到该邮件是否已分配并且分配的人目标数目不为0并且是绑定用户的话
            if($assign_id && $account > 0){
                //如果已分配，则直接使得该ticket显示
                //判断老用户是否在表里面并且目标大于0
                $task = ZendeskTasks::whereTime('create_time', 'today')
                    ->where(['admin_id' => $ticket->assign_id, 'type' => $ticket->getType(),'surplus_count' => ['>',0]])
                    ->find();
                if($task){
                    //修改task的字段
                    $task->surplus_count = $task->surplus_count - 1;
                    $task->complete_count = $task->complete_count + 1;
                    $task->complete_apply_count = $task->complete_apply_count + 1;
                    $task->save();
                    self::where('id',$ticket->id)->setField('is_hide',0);
                }
            }else{
                //无分配人的，或者离职用户，或者目标为0的人的邮件，进行分配
                //判断该邮件是否有老用户
                //找出目前目标为不为0的账户
                $targetAccount = ZendeskAgents::where(['count' => ['>',0]])
                    ->column('admin_id');
                //判断当前用户是否在targetAccount中
                //$preTicket = [];
                $preTicket = Zendesk::where(['assign_id' => ['in',$targetAccount], 'type' => $ticket->getType(),'channel' => ['in',['email','web','chat']],'email' => $ticket->email])->order('id', 'desc')
                        ->limit(1)
                        ->find();
                if (!$preTicket) {
                    //无老用户，则分配给最少单的用户
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where(['type' => $ticket->getType()])
                        ->order('surplus_count', 'desc')
                        ->limit(1)
                        ->find();
                } else {
                    //判断老用户是否在表里面并且目标大于0
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where(['admin_id' => $preTicket->assign_id, 'type' => $ticket->getType(),'target_count' => ['>',0]])
                        ->find();
                }

                if(!$ticket->assignee_id || $ticket->assignee_id == 382940274852){
                    //最后一条回复的zendesk用户id
                    $commentAuthorId = ZendeskComments::where(['ticket_id' => $ticket->ticket_id,'is_admin' => 1,'author_id' => ['neq',382940274852]])->order('id desc')->value('author_id');
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where([
                            'assignee_id' => $commentAuthorId,
                            'type' => $ticket->getType(),
                            'target_count' => ['>',0]
                        ])
                        ->find();
                }

                if(!$task){
                    //则分配给最少单的用户
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where(['type' => $ticket->getType()])
                        ->order('surplus_count', 'desc')
                        ->limit(1)
                        ->find();
                }



                if ($task) {
                    //判断该用户是否已经分配满了，满的话则不分配
                    if ($task->target_count > $task->complete_count) {
                        //修改zendesk的assign_id,assign_time
                        $ticket->assign_id = $task->admin_id;
                        $ticket->assignee_id = $task->assignee_id;
                        $ticket->assign_time = date('Y-m-d H:i:s', time());
                        $ticket->save();
                        //修改task的字段
                        $task->surplus_count = $task->surplus_count - 1;
                        $task->complete_count = $task->complete_count + 1;
                        $task->complete_apply_count = $task->complete_apply_count + 1;
                        $task->save();
                        self::where('id',$ticket->id)->setField('is_hide',0);
                    }
                }
            }
            usleep(1000);
        }
    }
    public static function shellAssignTicketChange()
    {
        //1，判断今天有无task，无，创建
        $tasks = ZendeskTasks::whereTime('create_time', 'today')->find();
        //设置所有的隐藏
        self::where('id','>=',1)->setField('is_hide',1);
        if (!$tasks) {
            //创建所有的tasks
            //获取所有的agents
            $agents = Db::name('zendesk_agents')->alias('z')->join(['fa_admin'=>'a'],'z.admin_id=a.id')->field('z.*,a.userid')->where('a.status','<>','hidden')->where('z.count','<>',0)->select();
            foreach ($agents as $agent) {
                //$target_count = $agent->count - $agent->tickets_count > 0 ? $agent->count - $agent->tickets_count : 0;
                ZendeskTasks::create([
                    'type' => $agent['type'],
                    'admin_id' => $agent['admin_id'],
                    'assignee_id' => $agent['agent_id'],
                    'leave_count' => 0,
                    'target_count' => $agent['count'],
                    'surplus_count' => $agent['count'],
                    'complete_count' => 0,
                    'check_count' => $agent['count'],
                    'apply_count' => 0,
                    'complete_apply_count' => 0
                ]);
            }
        }
        //获取所有的open和new的邮件
        $waitTickets = self::where(['status' => ['in','1,2'],'channel' => ['neq','voice'],'ticket_id'=>59587])->order('priority desc,zendesk_update_time asc')->select();
        //找出所有离职用户id
        $targetAccount = Admin::where(['status' => ['=','hidden']])->column('id');
        dump($targetAccount);
        foreach ($waitTickets as $ticket) {
            dump($ticket);
            //电话不分配
            if($ticket->channel == 'voice') continue;

            if($ticket->assign_id == 0 || $ticket->assignee_id == 382940274852){
                dump(111);
                dump($ticket->assign_id);
                //判断是否处理过该用户的邮件
                $zendesk_id = Zendesk::where(['email'=>$ticket->email,'type'=>$ticket->getType()])->order('id','desc')->column('id');
                //查询接触过该用户邮件的最后一条评论
                $commentAuthorId = Db::name('zendesk_comments')
                    ->alias('c')
                    ->join('fa_admin a','c.due_id=a.id')
                    ->where(['c.zid' => ['in',$zendesk_id],'c.is_admin' => 1,'c.author_id' => ['neq',382940274852],'a.status'=>['neq','hidden']])
                    ->order('c.id','desc')
                    ->value('due_id');
                if($commentAuthorId){
                    dump(222);
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where([
                            'admin_id' => $commentAuthorId,
                            'type' => $ticket->getType(),
                            'target_count' => ['>',0]
                        ])
                        ->find();
                }else{
                    dump(333);
                    //则分配给最少单的用户
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where(['type' => $ticket->getType()])
                        ->order('surplus_count', 'desc')
                        ->limit(1)
                        ->find();
                }

            }else{
                dump(444);
                //判断有承接的邮件的承接人是否离职  ---根据admin中的status是否是hidden判断是否离职
                if(in_array($ticket->assign_id,$targetAccount)){
                    //离职，则分配给最少单的用户
                    $task = ZendeskTasks::whereTime('create_time', 'today')
                        ->where(['type' => $ticket->getType()])
                        ->order('surplus_count', 'desc')
                        ->limit(1)
                        ->find();
                }
            }
dump($task);exit;
            if ($task) {
                //判断该用户是否已经分配满了，满的话则不分配
                if ($task->target_count > $task->complete_count) {
                    $str = '';
                    $str = $ticket->ticket_id."--".$ticket->assign_id.'--';
                    //修改zendesk的assign_id,assign_time
                    $ticket->assign_id = $task->admin_id;
                    $ticket->assignee_id = $task->assignee_id;
                    $ticket->assign_time = date('Y-m-d H:i:s', time());
                    $ticket->save();
                    //修改task的字段
                    $task->surplus_count = $task->surplus_count - 1;
                    $task->complete_count = $task->complete_count + 1;
                    $task->complete_apply_count = $task->complete_apply_count + 1;
                    $task->save();
                    $str .= $task->admin_id;
                    self::where('id',$ticket->id)->setField('is_hide',0);
                    file_put_contents('/www/wwwroot/mojing/runtime/log/111.txt',$str."\r\n",FILE_APPEND);
                    echo $str." is ok"."\n";
                }
            }
            usleep(1000);
        }
    }

}
