<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskTasks extends Migrator
{
    /**
     * 分配任务的表
     */
    public function change()
    {
        $table = $this->table('zendesk_tasks', array('engine' => 'InnoDB', 'signed' => false,'comment' => '分配任务的表'));
        $table->addColumn('type', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '站点类型，1:zeeloolw,2:voogueme'))
            ->addColumn('assignee_id', 'string', array('limit' => 20,'null' => true, 'comment' => '处理人id，zendesk对应的id'))
            ->addColumn('admin_id', 'integer', array('limit' => MysqlAdapter::INT_MEDIUM,'default' => 0,'signed'=>false, 'comment' => '用户id'))
            ->addColumn('leave_count', 'integer', array('limit' => MysqlAdapter::INT_MEDIUM,'default' => 0,'signed'=>false, 'comment' => '之前剩余的open,new的数目'))
            ->addColumn('target_count', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '目标的数目'))
            ->addColumn('surplus_count', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '剩余待分配的数目'))
            ->addColumn('complete_count', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '已经分配的数目'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->create();
    }
}
