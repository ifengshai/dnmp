<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateOrderStatistics extends Migrator
{

    public function change()
    {
        $table = $this->table('order_statistics');
        $table
            ->addColumn('voogmechic_sales_num', 'integer',
                array('limit' => 11, 'default' => 0, 'signed' => false, 'comment' => 'voogmechic饰品站的销售数量'))
            ->addColumn('voogmechic_sales_money', 'decimal',
                array('precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'voogmechic饰品站的销售金额'))
            ->addColumn('voogmechic_unit_price', 'decimal',
                array('precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'voogmechic饰品站的客单价'))
            ->addColumn('voogmechic_shoppingcart_total', 'integer',
                array('limit' => 11, 'default' => 0, 'signed' => false, 'comment' => 'voogmechic饰品站购物车总数'))
            ->addColumn('voogmechic_shoppingcart_conversion', 'decimal',
                array('precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'voogmechic饰品站的购物车转化率'))
            ->addColumn('voogmechic_register_customer', 'integer',
                array('limit' => 11, 'default' => 0, 'signed' => false, 'comment' => 'voogmechic饰品站的注册用户数'))
            ->addColumn('voogmechic_shoppingcart_update_total', 'integer',
                array('limit' => 11, 'default' => 0, 'signed' => false, 'comment' => 'voogmechic饰品站更新的购物车总数'))
            ->addColumn('voogmechic_shoppingcart_update_conversion', 'decimal',
                array('precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'voogmechic饰品站购物车更新转化率'))
            ->update();
    }
}
