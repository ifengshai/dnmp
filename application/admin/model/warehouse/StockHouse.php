<?php

namespace app\admin\model\warehouse;

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
        $shelf_number = $this->where('status',1)->field('id,coding')->select();
        $shelf_number = collection($shelf_number)->toArray();
        foreach ($shelf_number as $k=>$v){
            $shelf_number[$k]['shelf_number'] = preg_replace("/\\d+/",'', (explode('-',$v['coding']))[0]);
            unset($shelf_number[$k]['coding']);
        }
        $arr = array_values(array_column($shelf_number, 'shelf_number', 'shelf_number'));
        return $arr;
    }
}
