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
        $map['is_del'] = 1;
        $map['sku'] = $sku;
        $result = $this->alias('m')->where($map)->join('new_product_attribute a', 'm.id=a.item_id')->find();
        if (!$result) {
            return false;
        }
        $where['origin_sku'] = $result['origin_sku'];
        $where['is_del'] = 1;
        $arr = $this->alias('m')->where($where)->join('new_product_attribute a', 'm.id=a.item_id')->field('m.name,a.frame_color,m.supplier_sku,m.price')->select();
        $result['itemArr'] = $arr;
        $result['itemCount'] = $this->where($where)->count();
        return $result;
    }
}
