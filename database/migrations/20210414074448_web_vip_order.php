<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class WebVipOrder extends Migrator
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
        $table = $this->table('web_vip_order', ['engine' => 'InnoDB']);
        $table->addColumn('customer_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '用户表ID'])
            ->addColumn('customer_email', 'string', ['limit' => 255, 'default' => '', 'comment' => '用户邮箱'])
            ->addColumn('site', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '站点'])
            ->addColumn('order_number', 'string', ['limit' => 255, 'default' => '', 'comment' => '订单编号'])
            ->addColumn('order_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => '订单金额'])
            ->addColumn('order_status', 'string', ['limit' => 50, 'default' => 0, 'comment' => '订单状态'])
            ->addColumn('order_type', 'string', ['limit' => 50, 'default' => 0, 'comment' => '订单类型'])
            ->addColumn('paypal_token', 'string', ['limit' => 255, 'default' => 0, 'comment' => 'paypal_token'])
            ->addColumn('start_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '开始时间'])
            ->addColumn('end_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '结束时间'])
            ->addColumn('is_active_status', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '是否启用'])
            ->addColumn('created_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->addColumn('updated_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
            ->addColumn('pay_status', 'string', ['limit' => 50, 'default' => 0, 'comment' => '支付状态'])
            ->addColumn('country_id', 'string', ['limit' => 20, 'default' => 0, 'comment' => '国家'])
            ->create();
    }
}
