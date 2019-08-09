<?php

namespace app\admin\controller\itemmanage;

use app\common\controller\Backend;
use think\Request;

/**
 * 商品管理
 *
 * @icon fa fa-circle-o
 */
class Item extends Backend
{
    
    /**
     * Item模型对象
     * @var \app\admin\model\itemmanage\Item
     */
    protected $model = null;
    protected $category = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->view->assign('categoryList',$this->category->categoryList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /***
     * 异步获取商品分类的信息
     */
    public function ajaxCategoryInfo(Request $request)
    {
        if($this->request->isAjax()){
            $categoryId = $this->request->post('categoryId');
            if(!$categoryId){
                $this->error('参数错误，请重新尝试');
            }
            $result = $this->category->categoryPropertyInfo($categoryId);
            if(!$result){
                $this->error('对应分类不存在,请从新尝试');
            }
            echo '<pre>';
            var_dump($result);
            exit;
        }else{
            $this->error(__('404 Not Found'));
        }
    }
}
