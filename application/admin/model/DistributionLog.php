<?php

namespace app\admin\model;

use think\Model;

class DistributionLog extends Model
{
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 记录配货流程操作日志
     *
     * @param object $auth  管理员
     * @param int $item_process_id  子订单表ID
     * @param string $remark  备注
     * @author lzh
     * @return bool
     */
    public static function record($auth,$item_process_id,$remark)
    {
        $ids = is_array($item_process_id) ?: explode(',',$item_process_id);
        foreach($ids as $val){
            self::create([
                'item_process_id' => $val,
                'remark' => $remark,
                'create_time' => time(),
                'create_person' => $auth->nickname
            ]);
        }
    }
}
