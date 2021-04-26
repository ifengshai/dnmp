<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class WebUsers extends Migrator
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
        $table = $this->table('web_users', ['engine' => 'InnoDB']);
        $table->addColumn('entity_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '网站用户表ID'])
            ->addColumn('email', 'string', ['limit' => 255, 'default' => '', 'comment' => '邮箱'])
            ->addColumn('site', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '站点'])
            ->addColumn('group_id', 'integer', ['limit' => MysqlAdapter::INT_SMALL, 'default' => 0, 'comment' => '分组ID'])
            ->addColumn('store_id', 'integer', ['limit' => MysqlAdapter::INT_SMALL, 'default' => 0, 'comment' => '来源'])
            ->addColumn('created_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
            ->addColumn('resouce', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '注册来源0：普通注册1：弹窗注册2:facebook3:google'])
            ->addColumn('is_vip', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '是否为VIP,1是 0否'])
            ->create();
    }
}
