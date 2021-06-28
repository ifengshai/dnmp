<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class CreateZendeskAdminTable extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('zendesk_admin', ['engine' => 'InnoDB']);
        $table->addColumn('admin_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '平台用户的id'])
            ->addColumn('group', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '组别：1：VIP'])
            ->addColumn('count', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '派单数目'])
            ->addTimestamps()
            ->create();
    }
}
