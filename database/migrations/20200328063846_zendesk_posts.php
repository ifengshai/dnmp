<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ZendeskPosts extends Migrator
{
    /**
     * zendesk 的知识库的文章表
     */
    public function change()
    {
        $table = $this->table('zendesk_posts', array('engine' => 'InnoDB', 'signed' => false,'comment' => ''));
        $table->addColumn('post_id','string',array('limit' => 15, 'null' => true, 'comment' => '文章id'))
            ->addColumn('type', 'integer', array('limit' => MysqlAdapter::INT_TINY,'default' => 0,'signed'=>false, 'comment' => '站点类型，1:zeeloolw,2:voogueme'))
            ->addColumn('html_url', 'string', array('limit' => 200, 'comment' => '文章链接'))
            ->addColumn('title', 'string', array('limit' => 150, 'comment' => '文章标题'))
            ->addColumn('author_id', 'string', array('limit' => 20, 'comment' => '发送人id'))
            ->addColumn('body', 'text', array( 'null' => true,'comment' => '文章内容'))
            ->addColumn('create_time', 'datetime', [])
            ->addColumn('update_time', 'datetime', [])
            ->create();
    }
}
