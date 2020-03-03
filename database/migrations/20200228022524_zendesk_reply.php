<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskReply extends Migrator
{
    /**
     * 自动回复的主表
     */
    public function change()
    {
        $table = $this->table('zendesk_reply',array('engine'=>'MyISAM','signed' => false));
        $table->addColumn('email', 'string',array('limit' => 50,'default'=>'','comment'=>'发送人的email'))
            ->addColumn('title','string',array('limit' => 100,'comment' => '邮件主题'))
            ->addColumn('email_id', 'integer',array('limit' => 10,'signed' => false,'comment'=>'email的id'))
            ->addColumn('body','text',array('comment' => '发送内容'))
            ->addColumn('html_body','text',array('comment' => '发送内容的html格式'))
            ->addColumn('tags','string',array('limit' => 20,'null' => true,'comment' => 'tags,多个用，号连接'))
            ->addColumn('status','string',array('limit' => 10,'comment' => '状态'))
            ->addColumn('requester_id','string',array('limit' => 20,'comment' => '发送人id'))
            ->addColumn('assignee_id','string',array('limit' => 20,'null' => true,'comment' => '最后处理人id'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->addIndex(array('status'))
            ->addIndex(array('requester_id'))
            ->addIndex(array('assignee_id'))
            ->create();
    }
}
