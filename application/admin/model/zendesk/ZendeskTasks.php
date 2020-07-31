<?php

namespace app\admin\model\zendesk;

use think\Model;


class ZendeskTasks extends Model
{





    // 表名
    protected $name = 'zendesk_tasks';

    // 定义时间戳字段名
    protected $autoWriteTimestamp = 'timestamp';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 追加属性
    protected $append = [

    ];










}
