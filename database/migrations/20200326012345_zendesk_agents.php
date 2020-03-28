<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskAgents extends Migrator
{

    public function change()
    {
        $table = $this->table('zendesk_agents', array('engine' => 'InnoDB', 'signed' => false));
        $table->addColumn('admin_id', 'integer', array('limit' => MysqlAdapter::INT_MEDIUM,'default' => 0,'signed'=>false, 'comment' => '平台用户的id'))
            ->addColumn('type', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '站点类型，1:zeeloolw,2:voogueme'))
            ->addColumn('name', 'string', array('limit' => 50, 'comment' => 'zendesk对应的管理员名称'))
            ->addColumn('agent_id', 'string', array('limit' => 50, 'comment' => 'zendesk对应的管理员的id'))
            ->addColumn('agent_type', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '用户类型，1:邮件组,2:电话组'))
            ->addColumn('count', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '派单数目'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->create();
    }
}
