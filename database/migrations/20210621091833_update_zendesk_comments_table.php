<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class UpdateZendeskCommentsTable extends Migrator
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
        $table = $this->table('zendesk');
        $table
            ->addColumn('assign_id_next', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '第二承接人'))
            ->addColumn('is_urgency', 'integer', ['limit' => 1, 'default' => 0, 'comment' => '是否紧急：1：紧急'])
            ->addColumn('is_difficult', 'integer', ['limit' => 1, 'default' => 0, 'comment' => '是否疑难：1：疑难'])
            ->update();
    }
}
