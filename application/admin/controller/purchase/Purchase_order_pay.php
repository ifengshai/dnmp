<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;

/**
 * 采购单付款日志管理
 *
 * @icon fa fa-circle-o
 */
class Purchase_order_pay extends Backend
{
    
    /**
     * Purchase_order_pay模型对象
     * @var \app\admin\model\purchase\Purchase_order_pay
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\purchase\Purchase_order_pay;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
