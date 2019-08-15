<?php

namespace app\admin\controller\itemmanage;

use app\common\controller\Backend;
use think\Request;
use think\Db;
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
        $this->view->assign('AllFrameColor',$this->itemAttribute->getFrameColor());
        $num = $this->model->getLastID();
        $idStr = sprintf("%06d", $num);
        $this->assign('IdStr',$idStr);
    }
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                if(is_array($itemName)){
                    $data = $itemAttribute = [];
                    //求出材质对应的编码
                    if($params['frame_texture']){
                        $textureEncode = $this->itemAttribute->getTextureEncode($params['frame_texture']);
                    }else{
                        $textureEncode = 'O';
                    }
                    foreach ($itemName as $k =>$v){
                        $data['name'] = $v;
                        $data['category_id'] = $params['category_id'];
                        $data['item_status'] = $params['item_status'];
                        $data['create_person'] = session('admin.nickname');
                        $data['create_time'] = date("Y-m-d H:i:s",time());
                        $data['origin_sku'] = $params['procurement_origin'].$textureEncode.$params['origin_sku'];
                        $data['sku'] = $params['procurement_origin'].$textureEncode.$params['origin_sku'].'-'.sprintf("%02d", $k+1);
                        $lastInsertId = Db::name('item')->insertGetId($data);
                        if($lastInsertId){
                            $itemAttribute['item_id'] = $lastInsertId;
                            $itemAttribute['attribute_type'] = $params['attribute_type'];
                            $itemAttribute['glasses_type'] = $params['glasses_type'];
                            $itemAttribute['frame_height'] = $params['frame_height'];
                            $itemAttribute['frame_width'] = $params['frame_width'];
                            $itemAttribute['frame_color'] = $itemColor[$k];
                            $itemAttribute['frame_weight'] = $params['weight'];
                            $itemAttribute['frame_length'] = $params['frame_length'];
                            $itemAttribute['frame_temple_length'] = $params['frame_temple_length'];
                            $itemAttribute['shape'] = $params['shape'];
                            $itemAttribute['frame_bridge'] = $params['frame_bridge'];
                            $itemAttribute['mirror_width'] = $params['mirror_width'];
                            $itemAttribute['frame_type'] = $params['frame_type'];
                            $itemAttribute['frame_texture'] = $params['frame_texture'];
                            $itemAttribute['frame_shape'] = $params['frame_shape'];
                            $itemAttribute['frame_gender'] = $params['frame_gender'];
                            $itemAttribute['frame_size'] = $params['frame_size'];
                            $itemAttribute['frame_is_recipe'] = $params['frame_is_recipe'];
                            $itemAttribute['frame_piece'] = $params['frame_piece'];
                            $itemAttribute['frame_is_advance'] = $params['frame_is_advance'];
                            $itemAttribute['frame_temple_is_spring'] = $params['frame_temple_is_spring'];
                            $itemAttribute['frame_is_adjust_nose_pad'] = $params['frame_is_adjust_nose_pad'];
                            $itemAttribute['frame_remark'] = $params['frame_remark'];
                            Db::name('item_attribute')->insert($itemAttribute);
                        }
                    }
                }
                if ($lastInsertId !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
//
//                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
//                    $params[$this->dataLimitField] = $this->auth->id;
//                }
//                $result = false;
//                Db::startTrans();
//                try {
//                    //是否采用模型验证
//                    if ($this->modelValidate) {
//                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
//                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
//                        $this->model->validateFailException(true)->validate($validate);
//                    }
//
//                    $result = $this->model->allowField(true)->save($params);
//                    Db::commit();
//                } catch (ValidateException $e) {
//                    Db::rollback();
//                    $this->error($e->getMessage());
//                } catch (PDOException $e) {
//                    Db::rollback();
//                    $this->error($e->getMessage());
//                } catch (Exception $e) {
//                    Db::rollback();
//                    $this->error($e->getMessage());
//                }
//                if ($result !== false) {
//                    $this->success();
//                } else {
//                    $this->error(__('No rows were inserted'));
//                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids,'itemAttribute');
//        dump($row['itemAttribute']['procurement_origin']);
//        exit;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $allShape = $this->itemAttribute->getAllShape();
        //获取所有材质
        $allTexture = $this->itemAttribute->getAllTexture();
        //获取所有镜架形状
        $allFrameShape = $this->itemAttribute->getAllFrameShape();
        //获取所有适合性别
        $allFrameGender = $this->itemAttribute->getFrameGender();
        //获取所有型号
        $allFrameSize  = $this->itemAttribute->getFrameSize();
        //获取所有眼镜类型
        $allGlassesType = $this->itemAttribute->getGlassesType();
        //获取所有采购产地
        $allOrigin      = $this->itemAttribute->getOrigin();
        //获取配镜类型
        $allFrameType   = $this->itemAttribute->getFrameType();
        $this->assign('AllFrameType',$allFrameType);
        $this->assign('AllOrigin',$allOrigin);
        $this->assign('AllGlassesType',$allGlassesType);
        $this->assign('AllFrameSize',$allFrameSize);
        $this->assign('AllFrameGender',$allFrameGender);
        $this->assign('AllFrameShape',$allFrameShape);
        $this->assign('AllShape',$allShape);
        $this->assign('AllTexture',$allTexture);
        $this->view->assign('template',$this->category->getAttrCategoryById($row['category_id']));
        $this->view->assign("row", $row);
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
                //获取所有眼镜类型
                $allGlassesType = $this->itemAttribute->getGlassesType();
                //获取所有采购产地
                $allOrigin      = $this->itemAttribute->getOrigin();
                //获取配镜类型
                $allFrameType   = $this->itemAttribute->getFrameType();
                $this->assign('AllFrameType',$allFrameType);
                $this->assign('AllOrigin',$allOrigin);
                $this->assign('AllGlassesType',$allGlassesType);
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
    /***
     * 异步请求线下采购城市
     */
    public function ajaxGetProOrigin()
    {
        if($this->request->isAjax()){
            $data = $this->itemAttribute->getOrigin();
            if(!$data){
                return $this->error('现在没有采购城市,请去添加','','error',0);
            }
                return $this->success('','',$data,0);
        }else{
            return $this->error(__('404 Not Found'));
        }
    }
}
