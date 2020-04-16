<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateWorkOrderList extends Migrator
{

    public function change()
    {
        $table = $this->table('work_order_list');
        $table->addColumn('integral_describe', 'string', array('limit' => 255, 'null' => true, 'comment' => '积分描述'))
            ->update();
    }
}
