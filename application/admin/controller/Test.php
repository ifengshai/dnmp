<?php

namespace app\admin\controller;

use app\common\controller\Backend;

class Test extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
    }

    public function test()
    {
        $skus = $this->newproduct->column('sku');
        $map['sku'] = ['not in', $skus];
        $map['is_del'] = 1;
        $map['category_id'] = ['<>',43];
        $data = $this->item->where($map)->select();
        $data = collection($data)->toArray();
        $list = [];
        foreach ($data as $k => $v) {
            $list[$k]['origin_sku'] = $v['origin_sku'];
            $list[$k]['sku'] = $v['sku'];
            $list[$k]['name'] = $v['name'];
            $list[$k]['category_id'] = $v['category_id'];
            $list[$k]['price'] = $v['price'];
            $list[$k]['attribute_id'] = $v['attribute_id'];
            $list[$k]['brand_id'] = $v['brand_id'];
            $list[$k]['stock'] = $v['stock'];
            $list[$k]['item_status'] = 2;
            $list[$k]['create_person'] = $v['create_person'];
            $list[$k]['create_time'] = $v['create_time'];
        }
        $this->newproduct->saveAll($list);
    }
}
