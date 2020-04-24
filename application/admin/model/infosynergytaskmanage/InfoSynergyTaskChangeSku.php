<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;


class InfoSynergyTaskChangeSku extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task_change_sku';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    public function getChangeSkuList($tid)
    {
        $result = $this->where('tid', '=', $tid)->select();
        if (!$result) {
            return false;
        }
        // foreach ($result as $k =>$v){
        //   $result[$k]['option'] = unserialize($v['options']);
        // }
        return $result;
    }


    /**
     * 根据平台 订单原SKU 获取更换之后的SKU
     * @param $increment_id 订单号
     * @param $platform_type 平台类型
     * @param $original_sku 订单原SKU
     * @return array
     */
    public function getChangeSkuData($increment_id, $platform_type, $original_sku)
    {
        $map['increment_id'] = $increment_id;
        $map['platform_type'] = $platform_type;
        $map['original_sku'] = $original_sku;
        $map['change_type'] = 1;
        $list = $this->where($map)->field('change_sku,change_number')->select();
        $list = collection($list)->toArray();
        return $list ?: [];
    }
}
