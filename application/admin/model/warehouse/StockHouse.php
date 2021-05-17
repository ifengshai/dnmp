<?php

namespace app\admin\model\warehouse;

use think\Cache;
use think\Model;


class StockHouse extends Model
{


    // 表名
    protected $name = 'store_house';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 获取所有库位
     * @return array
     */
    public function getStockHouseData()
    {
        return $this->where('status', '=', 1)->column('coding', 'id');
    }

    /**
     * 获取所有货架号
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/18
     * Time: 15:10:01
     */
    public function get_shelf_number()
    {
        $shelf_number = $this->where('status', 1)->field('id,coding')->select();
        $shelf_number = collection($shelf_number)->toArray();
        foreach ($shelf_number as $k => $v) {
            $shelf_number[$k]['shelf_number'] = preg_replace("/\\d+/", '', (explode('-', $v['coding']))[0]);
            unset($shelf_number[$k]['coding']);
        }
        $arr = array_filter(array_values(array_column($shelf_number, 'shelf_number', 'shelf_number')));

        return $arr;
    }

    /**
     * 获取库位
     *
     * @Description
     * @author wpl
     * @since 2021/03/03 11:31:55 
     *
     * @param     [type] $area_id 库区id
     * @param     [type] $coding 库位编码
     *
     * @return void
     */
    public function getLocationData($area_id = null, $coding = null)
    {
        if ($coding) {
            $where['coding'] = ['like', $coding.'%'];
        }
        $cacheName = 'getLocationData_'.md5(serialize($where)).$area_id;
        $list = unserialize(Cache::get($cacheName));
        if (!$list) {
            $list = $this
                ->field('id as location_id,coding,library_name')
                ->where($where)
                ->where(['area_id' => ['in', $area_id], 'type' => 1, 'status' => 1])
                ->select();
            $list = collection($list)->toArray();
            Cache::set($cacheName, serialize($list));
        }

        return $list ?: [];
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
