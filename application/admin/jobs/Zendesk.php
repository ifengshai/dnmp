<?php

namespace app\admin\jobs;

use app\admin\controller\zendesk\Notice;
use think\Log;
use think\queue\Job;

class Zendesk
{
    /**
     * fire方法是消息队列默认调用的方法
     *
     * @param  Job  $job  当前的任务对象
     * @param  array|mixed  $data  发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        $sites = [1 => 'zeelool', 2 => 'voogueme', 3 => 'nihaooptical'];
        try {
            $ticket = (new Notice(request(), ['type' => $sites[$data['type']]]))->getTicket($data['ticket_id']);
            if ($ticket !== 'success') {
                echo $data['ticket_id'].'->success->'.$ticket->satisfaction_rating->score.PHP_EOL;
                if ($ticket->satisfaction_rating) {
                    $score = $ticket->satisfaction_rating->score;
                    $ratingComment = $ticket->satisfaction_rating->comment ?? null;
                    $ratingReason = $ticket->satisfaction_rating->reason ?? null;
                    $updateData['rating'] = $score;
                    $updateData['comment'] = $ratingComment;
                    $updateData['reason'] = $ratingReason;
                    if ($score == 'good') {
                        $updateData['rating_type'] = 1;
                    } elseif ($score == 'bad') {
                        $updateData['rating_type'] = 2;
                    }
                    \app\admin\model\zendesk\Zendesk::update($updateData, ['id' => $data['id']]);
                }
                $job->delete();
            } else {
                echo $data['ticket_id'].'->error'.PHP_EOL;
            }
        } catch (\Throwable $throwable) {
            Log::error(__CLASS__.$throwable->getMessage());
            $job->delete();
        }
    }
}