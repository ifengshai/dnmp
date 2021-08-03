<?php

namespace app\admin\model;

use think\Model;
use think\model\relation\HasMany;

/**
 * 新品流程
 *
 * Class NewProductProcess
 * @package app\admin\model
 * @author fangke
 * @date   2021/7/28 11:16 上午
 */
class NewProductProcess extends Model
{
    // 表名
    protected $name = 'new_product_processes';
//    时间戳自动写入
    protected $autoWriteTimestamp = true;

    // 关联Log
    public function logs(): HasMany
    {
        return $this->hasMany(NewProductProcessLog::class, 'new_product_process_id');
    }

    public function getStatusNames()
    {
        return [
            1 => '新品选品',
            2 => '新品提报',
            3 => '新品采购',
            4 => '新品入库',
            5 => '新品带回',
            6 => '新品设计',
            7 => '新品上架',
        ];
    }

    public function getCountByCreateTime($start_date, $end_date)
    {
        $start_date = strtotime($start_date);
        $end_date = strtotime($end_date);
        $query = $this->where("create_time between '{$start_date}' and '{$end_date}'");
        $data = [];
        for ($i = 1; $i <= 7; $i++) {
            $data[] = [
                'status' => $i,
                'name' => $this->getStatusNames()[$i],
                'value' => (clone $query)->where("status >= {$i}")->count(),
            ];
        }
        return $data;
    }
}
