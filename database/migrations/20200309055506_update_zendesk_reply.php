<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateZendeskReply extends Migrator
{
    public function change()
    {
        $table = $this->table('zendesk_reply');
        $table->addColumn('source', 'string', array('limit' => 10, 'comment' => '来源'))
            ->update();
    }
}
