<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class SkuOnsalesTime extends Migrator
{
    /**
     * 商品上下架时间记录表创建
     * @author mjj
     * @date   2021/5/13 11:50:36
     */
    public function change()
    {
        // create the table
        $table = $this->table('sku_shelves_time', ['engine' => 'InnoDB']);
        $table->addColumn('site', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '站点'])
            ->addColumn('sku', 'string', ['limit' => 255, 'default' => '', 'comment' => 'SKU'])
            ->addColumn('platform_sku', 'string', ['limit' => 255, 'default' => '', 'comment' => '平台SKU'])
            ->addColumn('shelves_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '上架时间'])
            ->addColumn('created_at', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
            ->create();
    }
}
