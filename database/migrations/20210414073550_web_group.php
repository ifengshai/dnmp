<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class WebGroup extends Migrator
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
        // create the table
        $table = $this->table('web_group', ['engine' => 'InnoDB']);
        $table->addColumn('customer_group_code', 'string', ['limit' => 32, 'default' => '', 'comment' => 'code'])
            ->addColumn('group_id', 'integer', ['limit' => MysqlAdapter::INT_SMALL, 'default' => 0, 'comment' => '组ID'])
            ->addColumn('site', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '站点'])
            ->addColumn('created_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
            ->create();
    }
}
