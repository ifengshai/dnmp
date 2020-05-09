<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/4/10 12:28
 * @Email: 646054215@qq.com
 */

namespace app\admin\command;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\admin\controller\zendesk\Notice;

class Crasp extends Command
{
    protected function configure()
    {
        $this
            ->setName('crasp')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, 'type value', ['1' => 'zeelool','2' => 'voogueme'])
            ->addOption('method', 'm', Option::VALUE_OPTIONAL, 'method', null)
            ->setDescription('zendesk notice run');
    }

    protected function execute(Input $input, Output $output)
    {
        // 覆盖安装
        $type = $input->getOption('type');
        $method = $input->getOption('method');
        (new Notice(request(), ['type' => $type]))->$method();
    }
}