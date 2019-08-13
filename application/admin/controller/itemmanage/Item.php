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
//    protected $layout = '';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->view->assign('categoryList',$this->category->categoryList());
    }
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
//            echo '<pre>';
//            var_dump($params);
//            exit;
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /***
     * 异步获取商品分类的信息（原先）
     */
//    public function ajaxCategoryInfo(Request $request)
//    {
//        if($this->request->isAjax()){
//            $categoryId = $this->request->post('categoryId');
//            if(!$categoryId){
//                $this->error('参数错误，请重新尝试');
//            }
//            //原先
//            $result = $this->category->categoryPropertyInfo($categoryId);
//            if(!$result){
//              return  $this->error('对应分类不存在,请从新尝试');
//            }elseif ($result == -1){
//              return $this->error('对应分类存在下级分类,请从新选择');
//            }
//            $num = count($result)-1;
//            $this->view->engine->layout(false);
//            $this->assign('result',$result);
//            //传递最后一个key值
//            $this->assign('num',$num);
//            $data = $this->fetch('attribute');
//            return  $this->success('ok','',$data);
//
//        }else{
//           return $this->error(__('404 Not Found'));
//        }
//    }

    /***
     * 异步获取商品分类的信息之后（更改之后）
     */
    public function ajaxCategoryInfo()
    {
        if($this->request->isAjax()){
            $categoryId = $this->request->post('categoryId');
            if(!$categoryId){
                $this->error('参数错误，请重新尝试');
            }
            //原先
            $result = $this->category->getAttrCategoryById($categoryId);
            if(!$result){
                return  $this->error('对应分类不存在,请从新尝试');
            }elseif ($result == -1){
                return $this->error('对应分类存在下级分类,请从新选择');
            }
            $this->view->engine->layout(false);
            //传递最后一个key值
            if($result ==1){ //商品是镜架类型
                //获取所有框型
                $allShape = $this->itemAttribute->getAllShape();
                //获取所有材质
                $allTexture = $this->itemAttribute->getAllTexture();
                //获取所有镜架形状
                $allFrameShape = $this->itemAttribute->getAllFrameShape();
                //获取所有适合性别
                $allFrameGender = $this->itemAttribute->getFrameGender();
                //获取所有型号
                $allFrameSize  = $this->itemAttribute->getFrameSize();
                $this->assign('AllFrameSize',$allFrameSize);
                $this->assign('AllFrameGender',$allFrameGender);
                $this->assign('AllFrameShape',$allFrameShape);
                $this->assign('AllShape',$allShape);
                $this->assign('AllTexture',$allTexture);
                $data = $this->fetch('frame');
            }elseif($result ==2){ //商品是镜片类型
                $data = $this->fetch('eyeglass');
            }elseif($result ==3){ //商品是饰品类型
                $data = $this->fetch('decoration');
            }else{
                $data = $this->fetch('attribute');
            }
            return  $this->success('ok','',$data);

        }else{
            return $this->error(__('404 Not Found'));
        }
    }
}
