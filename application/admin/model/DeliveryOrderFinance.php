<?php
/**
 * Class DeliveryOrderFinance.php
 * @package app\admin\model
 * @author  crasphb
 * @date    2021/4/10 14:47
 */

namespace app\admin\model;


use think\Model;

/**
 * 发货系统金额表
 *
 * Class DeliveryOrderFinance
 * @package app\admin\model
 * @author  crasphb
 * @date    2021/4/10 18:30
 */
class DeliveryOrderFinance extends Model
{
//数据库
    protected $connection = 'database.db_delivery';


    // 表名
    protected $table = 'ld_delivery_order_finance';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
}