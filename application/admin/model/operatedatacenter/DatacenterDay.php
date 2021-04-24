<?php
/**
 * Class DatacenterDay.php
 * @package app\admin\model\operatedatacenter
 * @author  crasphb
 * @date    2021/4/13 14:56
 */

namespace app\admin\model\operatedatacenter;


use think\Model;

class DatacenterDay extends Model
{
    // 表名
    protected $name = 'datacenter_day';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
}