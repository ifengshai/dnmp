<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskComments extends Migrator
{
    public function change()
    {
        $table = $this->table('zendesk_comments', array('engine' => 'InnoDB', 'signed' => false,'collation' => 'utf8mb4_general_ci'));
        $table->addColumn('ticket_id','integer',array('limit' => 7, 'signed' => false, 'comment' => '邮件id'))
            ->addColumn('zid', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => 'zendesk对应主表id'))
            ->addColumn('author_id', 'string', array('limit' => 20,'null' => true, 'comment' => '回复人id，对应的zendesk'))
            ->addColumn('due_id', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '处理人id，对应系统的用户id'))
            ->addColumn('body', 'text', array( 'null' => true, 'limit' => MysqlAdapter::TEXT_LONG,'comment' => '回复内容'))
            ->addColumn('html_body', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG,'comment' => '回复内容html格式'))
            ->addColumn('is_public', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '是否公开1：true，2：false'))
            ->addColumn('is_admin', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '是否是管理员回复，1：是，2：否'))
            ->addColumn('attachments', 'text', array('null' => true,'comment' => '附件，多个以，隔开'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->addIndex(array('zid','is_admin','due_id'))
            ->addIndex(array('zid','is_public'))
            ->create();
    }
}
