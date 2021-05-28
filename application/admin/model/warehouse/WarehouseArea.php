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
    public function getRowsData($areaName = null,$stockId = null)
    {
        if ($areaName) {
            $where['name'] = ['like', '%' . $areaName . '%'];
        }
        if ($stockId) {
            $where['stock_id'] = ['=',$stockId];
        }
        $list = $this->where('status', 1)->where($where)->field('id,coding,name,type')->select();
        return collection($list)->toArray();
    }

    /**
     * 所属分仓
     * @return \think\model\relation\BelongsTo
     * @author crasphb
     * @date   2021/5/17 14:13
     */
    public function warehouseStock()
    {
        return $this->belongsTo(WarehouseStock::class,'stock_id','id');
    }
}
