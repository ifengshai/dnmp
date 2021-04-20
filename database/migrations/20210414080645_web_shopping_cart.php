<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class WebShoppingCart extends Migrator
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
        $table = $this->table('web_shopping_cart', ['engine' => 'InnoDB']);
        $table->addColumn('entity_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '原表ID'])
            ->addColumn('site', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '站点'])
            ->addColumn('store_id', 'integer', ['limit' => MysqlAdapter::INT_SMALL, 'default' => 0, 'comment' => '来源'])
            ->addColumn('is_active', 'integer', ['limit' => MysqlAdapter::INT_SMALL, 'default' => 0, 'comment' => '是否活跃'])
            ->addColumn('items_count', 'integer', ['limit' => 10, 'default' => 0, 'comment' => '数量'])
            ->addColumn('items_qty', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => '金额'])
            ->addColumn('base_currency_code', 'string', ['limit' => 50, 'default' => 0, 'comment' => '基础币种'])
            ->addColumn('quote_currency_code', 'string', ['limit' => 50, 'default' => 0, 'comment' => '购物车币种'])
            ->addColumn('grand_total', 'decimal', ['precision' => 12, 'scale' => 4, 'default' => 0, 'comment' => '实付币种支付金额'])
            ->addColumn('base_grand_total', 'decimal', ['precision' => 12, 'scale' => 4, 'default' => 0, 'comment' => '基础币种支付金额'])
            ->addColumn('customer_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '用户ID'])
            ->addColumn('customer_email', 'string', ['limit' => 255, 'default' => '', 'comment' => '邮箱'])
            ->addColumn('created_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
            ->create();
    }
}
