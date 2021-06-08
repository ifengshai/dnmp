<?php
/**
 * 客服--批量修改邮件模板中的内容
 */
namespace app\admin\controller\shell\service;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class EmailAsynData extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('email_asyn_data')
            ->setDescription('email run');
    }

    protected function execute(Input $input, Output $output)
    {
        $data = Db::name('zendesk_comments')
            ->where('ticket_id',29535)
            ->field('id,body,html_body')
            ->select();
        foreach ($data as $value){
            $arr = [];
            if(strpos($value['body'],'nihao.zendesk.com')!==false){
                $arr['body'] = str_replace('nihao.zendesk.com','meeloog.zendesk.com',$value['body']);
            }
            if(strpos($value['html_body'],'nihao.zendesk.com')!==false){
                $arr['html_body'] = str_replace('nihao.zendesk.com','meeloog.zendesk.com',$value['html_body']);
            }
            Db::name('zendesk_comments')
                ->where('id',$value['id'])
                ->update($arr);
            echo $value['id']." is ok"."\n";
            usleep(10000);
        }
        $output->writeln("All is ok");
    }
}