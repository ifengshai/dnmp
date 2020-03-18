<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateAdmin extends Migrator
{

    public function change()
    {
        $table = $this->table('admin');
        $table->addColumn('position','string',array('limit' => 50,'null' => true,'comment' => '职位'))
            ->addColumn('mobile','string',array('limit' => 15,'null' => true,'comment' => '电话'))
            ->addColumn('department_id','integer',array('limit' => 15,'signed' => false,'comment' => '部门id'))
            ->addColumn('userid','string',array('limit' => 32,'comment' => 'userid'))
            ->addColumn('unionid','string',array('limit' => 32,'comment' => 'unionid'))
            ->update();
    }
}
