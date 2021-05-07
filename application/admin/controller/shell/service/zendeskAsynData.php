<?php
/**
 * 客服--批量修改邮件模板中的内容
 */
namespace app\admin\controller\shell\service;
use app\admin\controller\zendesk\Notice;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class zendeskAsynData extends Command
{
    public function __construct()
    {
        $this->model = new \app\admin\model\zendesk\Zendesk;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('zendesk_asyn_data')
            ->addArgument('type')
            ->addArgument('site')
            ->addArgument('start')
            ->addArgument('end')
            ->setDescription('zendesk run');
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getArgument('type');
        $site = $input->getArgument('site');
        $start = $input->getArgument('start');
        $end = $input->getArgument('end');

        $this->asyncTicketHttps($type,$site,$start,$end);
        $output->writeln("All is ok");
    }
    public function asyncTicketHttps($type,$site,$start,$end)
    {
        $ticketIds = (new Notice(request(), ['type' => $site]))->asyncUpdate($start,$end);

        //判断是否存在
        $nowTicketsIds = $this->model->where("type", $type)->column('ticket_id');

        //求交集的更新

        $intersects = array_intersect($ticketIds, $nowTicketsIds);
        //求差集新增
        $diffs = array_diff($ticketIds, $nowTicketsIds);
        //更新

        //$intersects = array('142871','142869');//测试是否更新
        //$diffs = array('144352','144349');//测试是否新增
        foreach($intersects as $intersect){
            (new Notice(request(), ['type' => $site, 'id' => $intersect]))->update();
            echo $intersect.'is ok'."\n";
        }
        //新增
        foreach($diffs as $diff){
            (new Notice(request(), ['type' => $site, 'id' => $diff]))->create();
            echo $diff.'ok'."\n";
        }
        echo 'all ok';
        exit;
    }

}