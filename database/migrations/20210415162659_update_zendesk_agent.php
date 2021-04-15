<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateZendeskAgent extends Migrator
{

    public function change()
    {
        $table = $this->table('zendesk_agents');
        $table
            ->addColumn('account_level', 'string', array('limit' => 255, 'comment' => '账号级别'))
            ->update();
    }
}
