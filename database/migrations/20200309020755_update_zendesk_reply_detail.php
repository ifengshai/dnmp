<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateZendeskReplyDetail extends Migrator
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
        $table = $this->table('zendesk_reply_detail');
        $table->addColumn('is_admin', 'integer',array('limit' => 1,'default'=>2,'signed' => false,'comment'=>'1:admin,2:顾客'))
            ->update();
    }
}
