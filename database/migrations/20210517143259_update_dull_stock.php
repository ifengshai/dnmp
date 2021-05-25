<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateDullStock extends Migrator
{

    public function change()
    {
        $table = $this->table('supply_dull_stock');
        $table
            ->addColumn('sku_num', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => 'sku数量'))
            ->addColumn('high_sku_num', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '高风险sku数量'))
            ->addColumn('center_sku_num', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '中风险sku数量'))
            ->addColumn('low_sku_num', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '低风险sku数量'))
            ->addColumn('glass_stock', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '镜框呆滞库存量'))
            ->addColumn('box_stock', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '饰品呆滞库存量'))
            ->update();
    }
}
