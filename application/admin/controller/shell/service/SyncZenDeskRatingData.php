<?php
/**
 * 客服--同步
 */

namespace app\admin\controller\shell\service;

use app\admin\model\zendesk\Zendesk;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Queue;

class SyncZenDeskRatingData extends Command
{
    protected function configure()
    {
        $this->setName('sync_zen_desk_rating_data')
            ->addArgument('start')
            ->addArgument('end')
            ->setDescription('同步ZenDesk评论数据');
    }

    /**
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\db\exception\DataNotFoundException
     */
    protected function execute(Input $input, Output $output)
    {
        $startTime = $input->getArgument('start');
        $endTime = $input->getArgument('end');

        $output->writeln("Start");
        $zendeskTickets = (new Zendesk())->field(['ticket_id', 'type', 'id'])
            ->whereTime('update_time', [$startTime, $endTime])
            ->select();
        /** @var Zendesk $ticket */
        foreach ($zendeskTickets as $ticket) {
            $isPushed = Queue::push("app\admin\jobs\Zendesk", $ticket, "zendeskJobQueue");
            if ($isPushed !== false) {
                $output->info($ticket->ticket_id."->推送成功");
            } else {
                $output->error($ticket->ticket_id."->推送失败");
            }
        }
        $output->writeln("End");
    }
}