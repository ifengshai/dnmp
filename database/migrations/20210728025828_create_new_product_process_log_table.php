<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class CreateNewProductProcessLogTable extends Migrator
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
        $table = $this->table('new_product_process_logs', ['engine' => 'InnoDB']);
        $table->addColumn('new_product_process_id', 'integer', ['limit' => 11, 'comment' => '新品流程ID'])
            ->addColumn('type', 'integer',
                [
                    'limit' => MysqlAdapter::INT_TINY,
                    'comment' => '类型 1:新品选品【待提报】2:新品提报【待采购】3:新品采购【待入库】4:新品入库【待带回】5:新品带回【待设计】6:新品设计【待上架】7:新品上架【已上架】'
                ])
            ->addColumn('admin_id', 'integer', ['limit' => 11, 'comment' => '操作人ID'])
            ->addColumn('create_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('update_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
            ->addIndex('new_product_process_id')
            ->addIndex('type')
            ->addIndex('admin_id')
            ->create();
    }
}
