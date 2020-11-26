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
     * @param mixed $item_process_id  子订单表ID
     * @param int $distribution_node  操作类型：1 打印标签 2 配货 3 配镜片 4 加工 5 印logo 6 成品质检 7 合单 8 审单 9 标记异常 10 处理异常
     * @param string $remark  备注
     * @author lzh
     * @return bool
     */
    public static function record($auth,$item_process_id,$distribution_node,$remark)
    {
        $ids = is_array($item_process_id) ? $item_process_id : explode(',',$item_process_id);
        foreach($ids as $val){
            self::create([
                'item_process_id' => $val,
                'distribution_node' => $distribution_node,
                'remark' => $remark,
                'create_time' => time(),
                'create_person' => $auth->nickname
            ]);
        }
    }
}
