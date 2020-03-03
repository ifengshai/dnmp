<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskReplyDetail extends Migrator
{
    /**
     * 自动回复的副表
     */
    public function change()
    {
        $table = $this->table('zendesk_reply_detail',array('engine'=>'MyISAM','signed' => false));
        $table->addColumn('reply_id', 'integer',array('limit' => 10,'default'=>'0','signed' => false,'comment'=>'email的id'))
            ->addColumn('body','text',array('comment' => '发送内容'))
            ->addColumn('html_body','text',array('comment' => '发送内容的html格式'))
            ->addColumn('tags','string',array('limit' => 20,'null' => true,'comment' => 'tags,多个用，号连接'))
            ->addColumn('status','string',array('limit' => 10,'comment' => '状态'))
            ->addColumn('assignee_id','string',array('limit' => 20,'null' => true,'default'=>'0','comment' => '最后处理人id'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->addIndex(array('status'))
            ->addIndex(array('assignee_id'))
            ->create();
    }
}
