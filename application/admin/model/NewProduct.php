<?php

namespace app\admin\model;

use think\Model;


class NewProduct extends Model
{
    // 表名
    protected $name = 'new_product';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //关联模型
    public function supplier()
    {
        return $this->belongsTo('app\admin\model\purchase\Supplier', 'supplier_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function newproductattribute()
    {
        return $this->belongsTo('NewProductAttribute', 'id', 'item_id', [], 'LEFT')->setEagerlyType(0);
    }

    /***
     * 查询商品名称是否重复
     */
    public function getInfoName($name)
    {
        $map['name'] = $name;
        $map['is_del'] = 1;
        $result = $this->where($map)->count();
        return $result ? $result : false;
    }

    /***
     * 模糊查询已经存在的源sku
     */
    public function likeOriginSku($value)
    {
        $item = new \app\admin\model\itemmanage\Item;
        $map['sku'] = ['like', '%' . $value . '%'];
        $map['is_del'] = 1;
        $result = $item->where($map)->field('sku')->distinct(true)->limit(10)->column('sku');
        return $result;
    }


    /***
     * 查询sku信息
     * @param $sku
     * @return bool
     */
    public function getItemInfo($sku)
    {
        $item = new \app\admin\model\itemmanage\Item;
        $map['is_del'] = 1;
        $map['sku'] = $sku;
        $result = $item->alias('m')->where($map)->join('item_attribute a', 'm.id=a.item_id', 'left')->find();

        if (!$result) {
            return false;
        }
        $where['m.sku'] = $result['sku'];
        $where['m.is_del'] = 1;
        $arr = $item->alias('m')->where($where)->join('item_attribute a', 'm.id=a.item_id', 'left')->field('m.name,a.frame_color,m.price')->select();
        $result['itemArr'] = $arr;
        $result['itemCount'] = $item->alias('m')->where($where)->count();
        return $result;
    }
}
