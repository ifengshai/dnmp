<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskTags extends Migrator
{

    public function change()
    {
        $table = $this->table('zendesk_tags', array('engine' => 'InnoDB', 'signed' => false));
        $table->addColumn('name', 'string', array('limit' => 50, 'comment' => 'tag的名称'))
            ->addColumn('count', 'integer', array('limit' => MysqlAdapter::INT_MEDIUM,'default' => 0,'signed'=>false, 'comment' => 'tag使用的数量'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->create();
    }
}
