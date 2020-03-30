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
        self::beforeInsert(function ($zendesk){
            self::assignTicket($zendesk);
        });
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'due_id', 'id')->setEagerlyType(0)->joinType('left');
    }
    public function lastComment()
    {
        return $this->hasMany(ZendeskComments::class,'zid','id')->order('id','desc')->limit(1);
    }
    public function getStatusFormatAttr($value, $data)
    {
        return config('zendesk.status')[$data['status']];
    }
    public function getUsernameFormatAttr($value, $data)
    {
        return $data['username'].'——'.$data['email'];
    }
    public function getTagFormatAttr($value, $data)
    {
        $tagIds = $data['tags'];
        $tags = ZendeskTags::where('id','in',$tagIds)->column('name');
        return join(',',$tags);
    }
    //获取选项卡列表
    public function getTabList()
    {
        return [
            ['name'=>'我的全部','field'=>'me_task','value'=>1],
            ['name'=>'我的待处理','field'=>'me_task','value'=>2],
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
        $ticketData = $ticket->toArray();
        //判断该邮件是否属于之前的老用户
        $preTicket = Zendesk::where(['user_id' => $ticket->user_id,'assign_id' => ['>',0],'type' => $ticketData['type']])
            ->order('id','desc')
            ->limit(1)
            ->find();
        if(!$preTicket){
            //无老用户，则分配给最少单的用户
            $task = ZendeskTasks::whereTime('create_time','today')
                ->where(['type' => $ticketData['type']])
                ->order('surplus_count','desc')
                ->limit(1)
                ->find();
        }else{
            $task = ZendeskTasks::whereTime('create_time','today')
                ->where(['admin_id' => $preTicket->assign_id,'type' => $ticketData['type']])
                ->find();
        }
        if($task){
            //判断该用户是否已经分配满了，满的话则不分配
            if($task->target_count > $task->complete_count) {
                //修改zendesk的assign_id,assign_time
                $ticket->assign_id = $task->admin_id;
                $ticket->assign_time = date('Y-m-d H:i:s', time());

                //修改task的字段
                $task->surplus_count = $task->surplus_count - 1;
                $task->complete_count = $task->complete_count + 1;
                $task->save();
            }
        }

    }

    public static function shellAssignTicket()
    {
        //1，判断今天有无task，无，创建
        $tasks = ZendeskTasks::whereTime('create_time','today')->find();
        if(!$tasks) {
            //创建所有的tasks
            //获取所有的agents
            $agents = ZendeskAgents::withCount(['tickets' => function($query){
                $query->where('status','in','1,2');
            }])->select();
            foreach($agents as $agent){
                $target_count = $agent->count - $agent->tickets_count > 0 ?  $agent->count - $agent->tickets_count : 0;
                ZendeskTasks::create([
                    'type' => $agent->type,
                    'admin_id' => $agent->admin_id,
                    'leave_count' => $agent->tickets_count,
                    'target_count' => $target_count,
                    'surplus_count' => $target_count,
                    'complete_count' => 0
                ]);
            }
        }
        //获取所有未分配的邮件
        $waitTickets = self::where('assign_id',0)->select();
        foreach($waitTickets as $ticket){
            //判断该邮件是否有老用户
            $preTicket = Zendesk::where(['user_id' => $ticket->user_id,'assign_id' => ['>',0],'type' => $ticket->type])
                ->order('id','desc')
                ->limit(1)
                ->find();
            if(!$preTicket){
                //无老用户，则分配给最少单的用户
                $task = ZendeskTasks::whereTime('create_time','today')
                    ->where(['type' => $ticket->type])
                    ->order('surplus_count','desc')
                    ->limit(1)
                    ->find();
            }else{
                $task = ZendeskTasks::whereTime('create_time','today')
                    ->where(['admin_id' => $preTicket->assign_id,'type' => $ticket->type])
                    ->find();
            }
            if($task){
                //判断该用户是否已经分配满了，满的话则不分配
                if($task->target_count > $task->complete_count) {
                    //修改zendesk的assign_id,assign_time
                    $ticket->assign_id = $task->admin_id;
                    $ticket->assign_time = date('Y-m-d H:i:s', time());
                    $ticket->save();
                    //修改task的字段
                    $task->surplus_count = $task->surplus_count - 1;
                    $task->complete_count = $task->complete_count + 1;
                    $task->save();
                }
            }
        }
    }

}
