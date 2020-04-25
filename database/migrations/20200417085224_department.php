<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class Department extends Migrator
{
    public function change()
    {
        $table = $this->table('department', array('engine' => 'InnoDB', 'signed' => false,'collation' => 'utf8mb4_general_ci'));
        $table->addColumn('name', 'string', array('limit' => 50, 'null' => true, 'comment' => '部门名称'))
            ->addColumn('pid', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => 'pid'))
            ->addColumn('department_id', 'string', array('limit' => 32, 'null' => true, 'comment' => '部门id'))
            ->addColumn('parentid', 'string', array('limit' => 32, 'null' => true, 'comment' => 'dingding中对应的parentid'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->create();
    }
}