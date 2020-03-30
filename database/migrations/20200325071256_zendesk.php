<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Zendesk extends Migrator
{

    /**
     * zendesk主表信息
     */
    public function change()
    {
        $table = $this->table('zendesk', array('engine' => 'InnoDB', 'signed' => false));
        $table->addColumn('ticket_id','integer',array('limit' => 7, 'signed' => false, 'comment' => '邮件id'))
            ->addColumn('type', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '站点类型，1:zeeloolw,2:voogueme'))
            ->addColumn('channel', 'string', array('limit' => 15,'null' => true, 'comment' => '类型：email,web，chat等'))
            ->addColumn('email', 'string', array('limit' => 50, 'comment' => '发送人的email'))
            ->addColumn('username', 'string', array('limit' => 50, 'comment' => '发送人昵称'))
            ->addColumn('user_id', 'string', array('limit' => 20, 'comment' => '发送人id'))
            ->addColumn('to_email', 'string', array('limit' => 50,'null' => true, 'comment' => '接受的账号邮箱'))
            ->addColumn('priority', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '0:空,1:low,2:normal,3:high,4:urgent'))
            ->addColumn('status', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '1:new,2:open,3:pending,4:solved'))
            ->addColumn('tags', 'string', array('limit' => 50,'null' => true, 'comment' => 'tags,多个用，号连接，使用的tag的id连接'))
            ->addColumn('subject', 'string', array('limit' => 100, 'comment' => '邮件主题'))
            ->addColumn('raw_subject', 'string', array('limit' => 100, 'comment' => '邮件副主题'))
            ->addColumn('assignee_id', 'string', array('limit' => 20, 'comment' => '处理人id，zendesk对应的id'))
            ->addColumn('assign_id', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '分配人id，对应系统的用户id'))
            ->addColumn('due_id', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '处理人id，对应系统的用户id'))
            ->addColumn('email_cc','string', array('limit' => 300,'null' => true, 'comment' => '抄送人的信息'))
            ->addColumn('rating','string', array('limit' => 10,'null' => true, 'comment' => '评分'))
            ->addColumn('rating_type','integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '1:good,2:bad'))
            ->addColumn('comment','string', array('limit' => 250,'null' => true, 'comment' => '评分的内容'))
            ->addColumn('reason','string', array('limit' => 250,'null' => true, 'comment' => '评分的原因'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->addIndex(array('status'))
            ->addIndex(array('assign_id','status'))
            ->addIndex(array('due_id','status'))
            ->create();
    }
}
