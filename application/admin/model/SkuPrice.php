<?php

namespace app\admin\model;

use think\Model;

class SkuPrice extends Model
{
    // 表名
    protected $name = 'sku_price';

    public function getAllData()
    {
        $this->column('price', 'sku');
    }
}
