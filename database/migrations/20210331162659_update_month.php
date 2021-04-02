<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateMonth extends Migrator
{

    public function change()
    {
        $table = $this->table('datacenter_supply_month_web');
        $table
            ->addColumn('usernum', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '客户数'))
            ->addColumn('old_usernum', 'integer', array('limit' => 11,'default' => 0,'signed'=>false, 'comment' => '老用户数'))
            ->addColumn('old_usernum_rate', 'decimal', array('precision' => 10, 'scale' => 2,'default' => 0, 'comment' => '老用户占比：老用户数/客户数'))
            ->addColumn('old_usernum_sequential', 'decimal', array('precision' => 10, 'scale' => 2,'default' => 0,  'comment' => '老用户环比变动：当月老用户数/上月老用户数'))
            ->addColumn('new_usernum_sequential', 'decimal', array('precision' => 10, 'scale' => 2,'default' => 0, 'comment' => '新用户环比变动：当月新用户数/上月新用户数'))
            ->update();
    }
}
