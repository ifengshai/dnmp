<?php

namespace app\admin\model;

use think\Model;

/**
 * 新品流程记录
 *
 * Class NewProductProcessLog
 * @package app\admin\model
 * @author fangke
 * @date   2021/7/28 11:16 上午
 */
class NewProductProcessLog extends Model
{
    // 表名
    protected $name = 'new_product_process_logs';
    // 时间戳自动写入
    protected $autoWriteTimestamp = true;

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
