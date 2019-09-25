<?php

namespace app\admin\controller\platformManage;

use app\common\controller\Backend;

/**
 * 平台字段映射
 *
 * @icon fa fa-circle-o
 */
class PlatformMap extends Backend
{
    
    /**
     * PlatformMap模型对象
     * @var \app\admin\model\platformManage\PlatformMap
     */
    protected $model = null;
    protected $magentoPlatform = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\platformManage\PlatformMap;
        $this->magentoPlatform = new \app\admin\model\platformManage\ManagtoPlatform;
        $this->view->assign('magentoList',$this->magentoPlatform->getOrderPlatformList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
