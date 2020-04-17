<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/4/17 10:02
 * @Email: 646054215@qq.com
 */

namespace app\admin\model\saleaftermanage;


use think\Model;

class WorkOrderRemark extends Model
{
    /// 表名
    protected $name = 'work_order_remark';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
}