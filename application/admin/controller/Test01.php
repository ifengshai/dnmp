<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use fast\Http;

class Test01 extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
    }

    public function test01()
    {
        $this->item = new \app\admin\model\itemmanage\Item();


        $this->new_product = new \app\admin\model\NewProduct();
        $list = $this->new_product->alias('a')->field('sku,frame_width,frame_height,frame_length,frame_temple_length,frame_bridge,frame_bridge,mirror_width,frame_color,frame_texture,shape,frame_shape,price')
        ->where(['item_status' => 2, 'is_del' => 1])
        ->join(['fa_new_product_attribute' => 'b'],'a.id=b.item_id')
        ->select();
        $list = collection($list)->toArray();

        foreach($list as $k => $v) {

        }

    }

    public function test02()
    {

    }
}
