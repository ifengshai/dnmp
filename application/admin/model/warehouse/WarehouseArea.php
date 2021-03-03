<?php

namespace app\admin\model\warehouse;

use think\Model;


class WarehouseArea extends Model
{


    // 表名
    protected $name = 'warehouse_area';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 获取库区数据
     *
     * @Description
     * @author wpl
     * @since 2021/03/03 09:18:37 
     * @return void
     */
    public function getRowsData($area_name = null)
    {
        if ($area_name) {
            $where['name'] = ['like', '%' . $area_name . '%'];
        }
        $list = $this->where('status', 1)->where($where)->field('id,coding,name,type')->select();
        return collection($list)->toArray();
    }
}
