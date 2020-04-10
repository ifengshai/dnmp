<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskUpdate extends Migrator
{
    /**
     * 添加一个分配的时间
     */
    public function change()
    {
        $table = $this->table('zendesk');
        $table->addColumn('assign_time','datetime',['null' => true])
        ->save();
    }
}
