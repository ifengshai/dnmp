<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateZendeskReplyDetail extends Migrator
{
    public function change()
    {
        $table = $this->table('zendesk_reply_detail');
        $table->addColumn('is_admin', 'integer', array('limit' => 1, 'default' => 2, 'signed' => false, 'comment' => '1:admin,2:顾客'))
            ->update();
    }
}
