<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateAdmin extends Migrator
{

    public function change()
    {
        $table = $this->table('admin');
        $table->addColumn('position', 'string', array('limit' => 50, 'null' => true, 'comment' => '职位'))
            ->addColumn('mobile', 'string', array('limit' => 15, 'null' => true, 'comment' => '电话'))
            ->addColumn('userid', 'string', array('limit' => 32, 'null' => true, 'comment' => 'userid'))
            ->addColumn('unionid', 'string', array('limit' => 32, 'null' => true, 'comment' => 'unionid'))
            ->update();
    }
}
