<?php

namespace app\admin\model\order\printlabel;

use think\Model;
use think\Db;


class Voogueme extends Model
{



    //数据库
    // protected $connection = 'database.db_voogueme_online';
    protected $connection = 'database.db_voogueme';

    // 表名
    protected $table = 'sales_flat_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    
}
