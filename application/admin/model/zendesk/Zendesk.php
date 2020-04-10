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
        'username_format'
    ];

    protected static function init()
    {
        parent::init();
        //新增后的回调函数，进行任务分配
        self::beforeInsert(function ($zendesk) {
            //如果存在assignee_id,则不需要自动分配
            //判断是否已分配，chat的情况会存在自动分配的情况，所有此处需要判断下
            if($zendesk->assignee_id){
                $assign_id = $due_id = ZendeskAgents::where('agent_id',$zendesk->assignee_id)->value('admin_id');
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

}
