<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;

class Test extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
    }
    public function test123()
    {
        echo 1111;
    }

    /**
     * 更新采购负责人
     *
     * @Description
     * @author wpl
     * @since 2020/04/29 15:43:38 
     * @return void
     */
    public function test()
    {
        $ids = [];
        $person = '';
        $str = explode('
        ', $person);
        $supplier = new \app\admin\model\purchase\Supplier();
        foreach ($ids as $k => $v) {
            $supplier->where('id', $v)->update(['purchase_person' => $str[$k]]);
        }
    }

}
