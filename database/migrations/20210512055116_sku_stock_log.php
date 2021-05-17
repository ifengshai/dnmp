<?php

use think\migration\Migrator;
use think\migration\db\Column;

class SkuStockLog extends Migrator
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
        $table = $this->table('sku_stock_log', ['engine' => 'InnoDB']);
        $table->addColumn('sku', 'string', ['limit' => 50, 'default' => '', 'comment' => 'sku'])
            ->addColumn('all_stock', 'integer', ['limit' => 6, 'default' => 0, 'comment' => '实时库存'])
            ->addColumn('distribution_occupy_stock', 'integer', ['limit' => 6, 'default' => 0, 'comment' => '配货占用库存'])
            ->addColumn('stock_money', 'decimal', ['precision' => 12, 'scale' => 4, 'default' => 0, 'comment' => '库存总金额'])
            ->addColumn('created_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
            ->create();
    }
}
