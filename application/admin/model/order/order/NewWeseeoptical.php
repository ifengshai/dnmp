<?php
/**
 * Class NewWeseeoptical.php
 * @package app\admin\model\order\order
 * @author  crasphb
 * @date    2021/5/14 13:14
 */

namespace app\admin\model\order\order;


use think\Model;

class NewWeseeoptical extends Model
{


    //数据库
    protected $connection = 'database.db_weseeoptical';


    // 表名
    protected $table = 'orders';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
}