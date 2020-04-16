<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateWorkOrderChangeSku extends Migrator
{

    public function change()
    {
        $table = $this->table('work_order_change_sku');
        $table->addColumn('prescription_option', 'text', array('null' => true,'comment' => '处方序列化的值'))
            ->update();
    }
}
