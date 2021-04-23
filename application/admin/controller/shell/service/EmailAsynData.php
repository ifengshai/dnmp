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
        $data = Db::name('zendesk_mail_template')
            ->field('id,template_content')
            ->select();
        foreach ($data as $value){
            if(strpos($value['template_content'],'Dear Customer,')!==false){
                $str = str_replace('Dear Customer,','Dear {{username}},',$value['template_content']);
                Db::name('zendesk_mail_template')
                    ->where('id',$value['id'])
                    ->update(['template_content'=>$str]);
                echo $value['id']." is ok"."\n";
                usleep(10000);
            }
        }
        $output->writeln("All is ok");
    }
}