<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskTasksUpdate extends Migrator
{
    /**
     * 添加一个每天应完成的任务量
     */
    public function change()
    {
        $table = $this->table('zendesk_tasks');
        $table->addColumn('check_count', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '考核的数目'))
            ->addColumn('check_count', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '考核的数目'))
            ->save();
    }
}
