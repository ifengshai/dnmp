<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateAuthGroup extends Migrator
{

    public function change()
    {
        $table = $this->table('auth_group');
        $table->addColumn('department_id','integer',array('limit' => 10,'signed' => false,'comment' => '钉钉部门id'))
            ->addColumn('parentid','integer',array('limit' => 10,'signed' => false,'comment' => '钉钉上级部门id'))
            ->update();
    }
}
