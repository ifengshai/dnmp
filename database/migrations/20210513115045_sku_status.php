<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class SkuStatus extends Migrator
{
    /**
     * sku每天状态记录表
     * @author mjj
     * @date   2021/5/13 11:53:49
     */
    public function change()
    {
        // create the table
        $table = $this->table('sku_status_dataday', ['engine' => 'InnoDB']);
        $table->addColumn('day_date', 'string', ['limit' => 255, 'default' => '', 'comment' => '创建日期'])
            ->addColumn('site', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '站点'])
            ->addColumn('sku', 'string', ['limit' => 255, 'default' => '', 'comment' => 'SKU'])
            ->addColumn('platform_sku', 'string', ['limit' => 255, 'default' => '', 'comment' => '平台SKU'])
            ->addColumn('category_name', 'string', ['limit' => 255, 'default' => '', 'comment' => '分类名称'])
            ->addColumn('status', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '在线状态1：在线2：售罄3：下架'])
            ->create();
    }
}
