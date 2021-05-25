<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateSkuDay extends Migrator
{

    public function change()
    {
        $table = $this->table('datacenter_sku_day');
        $table
            ->addColumn('update_cart_num', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '更新购物车数'))
            ->addColumn('pay_lens_num', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '付费镜片数量'))
            ->update();
    }
}
