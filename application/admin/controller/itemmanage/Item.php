<?php

namespace app\admin\controller\itemmanage;

use app\common\controller\Backend;
use think\Request;
use think\Db;
use app\admin\model\itemmanage\ItemBrand;
use app\admin\model\itemmanage\ItemPlatformSku;

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
    /**
     * 不需要登陆
     */
    protected $noNeedLogin = ['pullMagentoProductInfo','ceshi'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->view->assign('categoryList', $this->category->categoryList());
        $this->view->assign('brandList', (new ItemBrand())->getBrandList());
        $this->view->assign('AllFrameColor', $this->itemAttribute->getFrameColor());
        $num = $this->model->getOriginSku();
        $idStr = sprintf("%06d", $num);
        $this->assign('IdStr', $idStr);
    }

    /**
     * 查看商品列表
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where('is_open', '<', 3)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model->where('is_open', '<', 3)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //求出分类列表
            $categoryArr = $this->category->getItemCategoryList();
            //求出品牌列表
            $brandArr    = (new ItemBrand())->getBrandList();
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                if ($v['category_id']) {
                    $list[$k]['category_id'] = $categoryArr[$v['category_id']];
                }
                $list[$k]['brand_id']  = $brandArr[$v['brand_id']];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    //商品回收站
    public function recycle()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where(['is_open' => 3])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model->where(['is_open' => 3])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //求出分类列表
            $categoryArr = $this->category->getItemCategoryList();
            //求出品牌列表
            $brandArr    = (new ItemBrand())->getBrandList();
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                if ($v['category_id']) {
                    $list[$k]['category_id'] = $categoryArr[$v['category_id']];
                }
                $list[$k]['brand_id']  = $brandArr[$v['brand_id']];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    //新增商品原先代码
    public function add_yuan()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                if (is_array($itemName) && !in_array("", $itemName)) {
                    $data = $itemAttribute = [];
                    //求出材质对应的编码
                    if ($params['frame_texture']) {
                        $textureEncode = $this->itemAttribute->getTextureEncode($params['frame_texture']);
                    } else {
                        $textureEncode = 'O';
                    }
                    //一对一关联写入有错误，后期研究
                    //$attribute = $this->itemAttribute;
                    //                    foreach ($itemName as $k =>$v) {
                    //                        $data[$k]['name'] = $v;
                    //                        $data[$k]['category_id'] = $params['category_id'];
                    //                        $data[$k]['item_status'] = $params['item_status'];
                    //                        $data[$k]['create_person'] = session('admin.nickname');
                    //                        $data[$k]['create_time'] = date("Y-m-d H:i:s", time());
                    //                        $data[$k]['origin_sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'];
                    //                        $data[$k]['sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                    //                        $data[$k]['attribute_type'] = $params['attribute_type'];
                    //                        $data[$k]['glasses_type'] = $params['glasses_type'];
                    //                        $data[$k]['frame_height'] = $params['frame_height'];
                    //                        $data[$k]['frame_width'] = $params['frame_width'];
                    //                        $data[$k]['frame_color'] = $itemColor[$k];
                    //                        $data[$k]['frame_weight'] = $params['weight'];
                    //                        $data[$k]['frame_length'] = $params['frame_length'];
                    //                        $data[$k]['frame_temple_length'] = $params['frame_temple_length'];
                    //                        $data[$k]['shape'] = $params['shape'];
                    //                        $data[$k]['frame_bridge'] = $params['frame_bridge'];
                    //                        $data[$k]['mirror_width'] = $params['mirror_width'];
                    //                        $data[$k]['frame_type'] = $params['frame_type'];
                    //                        $data[$k]['frame_texture'] = $params['frame_texture'];
                    //                        $data[$k]['frame_shape'] = $params['frame_shape'];
                    //                        $data[$k]['frame_gender'] = $params['frame_gender'];
                    //                        $data[$k]['frame_size'] = $params['frame_size'];
                    //                        $data[$k]['frame_is_recipe'] = $params['frame_is_recipe'];
                    //                        $data[$k]['frame_piece'] = $params['frame_piece'];
                    //                        $data[$k]['frame_is_advance'] = $params['frame_is_advance'];
                    //                        $data[$k]['frame_temple_is_spring'] = $params['frame_temple_is_spring'];
                    //                        $data[$k]['frame_is_adjust_nose_pad'] = $params['frame_is_adjust_nose_pad'];
                    //                        $data[$k]['frame_remark'] = $params['frame_remark'];
                    //                        // $lastInsertId = Db::name('item')->insertGetId($data);
                    //                        $attribute->attribute_type = $params['attribute_type'];
                    //                        $attribute->glasses_type = $params['glasses_type'];
                    //                        $attribute->frame_height = $params['frame_height'];
                    //                        $attribute->frame_width = $params['frame_width'];
                    //                        $attribute->frame_color = $itemColor[$k];
                    //                        $attribute->frame_weight = $params['weight'];
                    //                        $attribute->frame_length = $params['frame_length'];
                    //                        $attribute->frame_temple_length = $params['frame_temple_length'];
                    //                        $attribute->shape = $params['shape'];
                    //                        $attribute->frame_bridge = $params['frame_bridge'];
                    //                        $attribute->mirror_width = $params['mirror_width'];
                    //                        $attribute->frame_type = $params['frame_type'];
                    //                        $attribute->frame_texture = $params['frame_texture'];
                    //                        $attribute->frame_shape = $params['frame_shape'];
                    //                        $attribute->frame_gender = $params['frame_gender'];
                    //                        $attribute->frame_size = $params['frame_size'];
                    //                        $attribute->frame_is_recipe = $params['frame_is_recipe'];
                    //                        $attribute->frame_piece = $params['frame_piece'];
                    //                        $attribute->frame_is_advance = $params['frame_is_advance'];
                    //                        $attribute->frame_temple_is_spring = $params['frame_temple_is_spring'];
                    //                        $attribute->frame_is_adjust_nose_pad = $params['frame_is_adjust_nose_pad'];
                    //                        $attribute->frame_remark = $params['frame_remark'];
                    //                        $this->model->itemAttribute = $attribute;
                    //                    }
                    //                    $result = $this->model->together('itemAttribute')->saveAll($data);
                    //如果是后来添加的
                    if (!empty($params['origin_skus']) && $params['item-count'] >= 1) { //正常情况
                        $count = $params['item-count'];
                        $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                    } elseif (empty($params['origin_skus']) && $params['item-count'] >= 1) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item-count'] < 1) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }
                    //                    //多选的frame_shape
                    //                    if(is_array($params['frame_shape'])){
                    //                        $params['frame_shape'] = implode(',',$params['frame_shape']);
                    //                    }
                    //                    //多选的frame_size
                    //                    if(is_array($params['frame_size'])){
                    //                        $params['frame_size']  = implode(',',$params['frame_size']);
                    //                    }
                    //                    //多选的glasses_type
                    //                    if(is_array($params['glasses_type'])){
                    //                        $params['glasses_type'] = implode(',',$params['glasses_type']);
                    //                    }
                    //                    //多选的frame_is_adjust_nose_pad
                    //                    if(is_array($params['frame_is_adjust_nose_pad'])){
                    //                        $params['frame_is_adjust_nose_pad'] = implode(',',$params['frame_is_adjust_nose_pad']);
                    //                    }
                    foreach ($itemName as $k => $v) {
                        $data['name'] = $v;
                        $data['category_id'] = $params['category_id'];
                        $data['item_status'] = $params['item_status'];
                        $data['brand_id']    = $params['brand_id'];
                        $data['create_person'] = session('admin.nickname');
                        $data['create_time'] = date("Y-m-d H:i:s", time());
                        //后来添加的商品数据
                        if (!empty($params['origin_skus'])) {
                            $data['origin_sku'] = $params['origin_sku'];
                            $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                            ++$count;
                        } else {
                            $data['origin_sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'];
                            $data['sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                        }
                        $lastInsertId = Db::name('item')->insertGetId($data);
                        if ($lastInsertId !== false) {

                            $itemAttribute['item_id'] = $lastInsertId;
                            $itemAttribute['attribute_type'] = $params['attribute_type'];
                            $itemAttribute['glasses_type'] = $params['glasses_type'];
                            $itemAttribute['frame_height'] = $params['frame_height'];
                            $itemAttribute['frame_width'] = $params['frame_width'];
                            $itemAttribute['frame_color'] = $itemColor[$k];
                            $itemAttribute['frame_weight'] = $params['weight'];
                            $itemAttribute['procurement_type'] = $params['procurement_type'];
                            $itemAttribute['procurement_origin'] = $params['procurement_origin'];
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
                } else {
                    $this->error(__('Please add product name and color'));
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
    //新增商品
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                if (is_array($itemName) && !in_array("", $itemName)) {
                    $data = $itemAttribute = [];
                    //求出材质对应的编码
                    if ($params['frame_texture']) {
                        $textureEncode = $this->itemAttribute->getTextureEncode($params['frame_texture']);
                    } else {
                        $textureEncode = 'O';
                    }
                    //如果是后来添加的
                    if (!empty($params['origin_skus']) && $params['item-count'] >= 1) { //正常情况
                        $count = $params['item-count'];
                        $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                    } elseif (empty($params['origin_skus']) && $params['item-count'] >= 1) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item-count'] < 1) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }
                    if (!empty($params['origin_skus'])) {
                        $data['origin_sku'] = $params['origin_sku'];
                    } else {
                        $data['origin_sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'];
                        $checkOriginSku     = $this->model->checkIsExistOriginSku($data['origin_sku']);
                        if ($checkOriginSku) {
                            $this->error(__('The commodity sku code already exists, please add the commodity again or contact the developer'));
                        }
                    }

                    Db::startTrans();
                    try {
                        foreach ($itemName as $k => $v) {
                            $data['name'] = $v;
                            $data['category_id'] = $params['category_id'];
                            $data['item_status'] = $params['item_status'];
                            $data['brand_id']    = $params['brand_id'];
                            $data['create_person'] = session('admin.nickname');
                            $data['create_time'] = date("Y-m-d H:i:s", time());
                            //后来添加的商品数据
                            if (!empty($params['origin_skus'])) {
                                $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                                ++$count;
                            } else {
                                $data['sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                            }
                            // $lastInsertId = Db::name('item')->insertGetId($data);
                            $lastInsertId = Db::connect('database.db_stock')->name('item')->insertGetId($data);
                            if ($lastInsertId !== false) {
                                $itemAttribute['item_id'] = $lastInsertId;
                                $itemAttribute['attribute_type'] = $params['attribute_type'];
                                $itemAttribute['glasses_type'] = $params['glasses_type'];
                                $itemAttribute['frame_height'] = $params['frame_height'];
                                $itemAttribute['frame_width'] = $params['frame_width'];
                                $itemAttribute['frame_color'] = $itemColor[$k];
                                $itemAttribute['frame_weight'] = $params['weight'];
                                $itemAttribute['procurement_type'] = $params['procurement_type'];
                                $itemAttribute['procurement_origin'] = $params['procurement_origin'];
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
                                //Db::name('item_attribute')->insert($itemAttribute);
                                Db::connect('database.db_stock')->name('item_attribute')->insert($itemAttribute);
                            }
                        }

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
                } else {
                    $this->error(__('Please add product name and color'));
                }
                if ($lastInsertId !== false) {
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
     * 编辑商品原先
     */
    public function edit_yuan($ids = null)
    {
        $row = $this->model->get($ids, 'itemAttribute');

        //        echo '<pre>';
        //        var_dump($row['itemAttribute']['frame_size']);
        //        exit;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($row['item_status'] == 2) {
            $this->error(__('The goods have been submitted for review and cannot be edited'), '/admin/itemmanage/item');
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
                $itemName = $params['name'];
                $itemColor = $params['color'];
                if (is_array($itemName) && !in_array("", $itemName)) {
                    $data = $itemAttribute = [];
                    foreach ($itemName as $k => $v) {
                        $data['name'] = $v;
                        $data['item_status'] = $params['item_status'];
                        $data['create_person'] = session('admin.nickname');
                        $data['create_time'] = date("Y-m-d H:i:s", time());
                        $item = Db::name('item')->where('id', '=', $row['id'])->update($data);
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
                        $itemAttr = Db::name('item_attribute')->where('item_id', '=', $row['id'])->update($itemAttribute);
                    }
                } else {
                    $this->error(__('Please add product name and color'));
                }
                $this->success();
                if (($item !== false) && ($itemAttr !== false)) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['itemAttribute']['frame_size']     = explode(',', $row['itemAttribute']['frame_size']);
        $row['itemAttribute']['frame_shape']    = explode(',', $row['itemAttribute']['frame_shape']);
        $row['itemAttribute']['glasses_type']   = explode(',', $row['itemAttribute']['glasses_type']);
        $row['itemAttribute']['frame_is_adjust_nose_pad'] = explode(',', $row['itemAttribute']['frame_is_adjust_nose_pad']);
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
        //获取是否可调节鼻托类型
        $allNosePad     = $this->itemAttribute->getAllNosePad();
        $this->assign('AllFrameType', $allFrameType);
        $this->assign('AllOrigin', $allOrigin);
        $this->assign('AllGlassesType', $allGlassesType);
        $this->assign('AllFrameSize', $allFrameSize);
        $this->assign('AllFrameGender', $allFrameGender);
        $this->assign('AllFrameShape', $allFrameShape);
        $this->assign('AllShape', $allShape);
        $this->assign('AllTexture', $allTexture);
        $this->assign('AllNosePad', $allNosePad);
        $this->view->assign('template', $this->category->getAttrCategoryById($row['category_id']));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /***
     * 后来修改编辑商品
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids, 'itemAttribute');
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($row['item_status'] == 2) {
            $this->error(__('The goods have been submitted for review and cannot be edited'), '/admin/itemmanage/item');
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
                $itemName = $params['name'];
                $itemColor = $params['color'];
                if (is_array($itemName) && !in_array("", $itemName)) {
                    $data = $itemAttribute = [];
                    Db::startTrans();
                    try {
                        foreach ($itemName as $k => $v) {
                            $data['name'] = $v;
                            $data['item_status'] = $params['item_status'];
                            $data['create_person'] = session('admin.nickname');
                            $data['create_time'] = date("Y-m-d H:i:s", time());
                            $item = Db::connect('database.db_stock')->name('item')->where('id', '=', $row['id'])->update($data);
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
                            $itemAttr = Db::connect('database.db_stock')->name('item_attribute')->where('item_id', '=', $row['id'])->update($itemAttribute);
                        }
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
                } else {
                    $this->error(__('Please add product name and color'));
                }
                $this->success();
                if (($item !== false) && ($itemAttr !== false)) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['itemAttribute']['frame_size']     = explode(',', $row['itemAttribute']['frame_size']);
        $row['itemAttribute']['frame_shape']    = explode(',', $row['itemAttribute']['frame_shape']);
        $row['itemAttribute']['glasses_type']   = explode(',', $row['itemAttribute']['glasses_type']);
        $row['itemAttribute']['frame_is_adjust_nose_pad'] = explode(',', $row['itemAttribute']['frame_is_adjust_nose_pad']);
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
        //获取是否可调节鼻托类型
        $allNosePad     = $this->itemAttribute->getAllNosePad();
        $this->assign('AllFrameType', $allFrameType);
        $this->assign('AllOrigin', $allOrigin);
        $this->assign('AllGlassesType', $allGlassesType);
        $this->assign('AllFrameSize', $allFrameSize);
        $this->assign('AllFrameGender', $allFrameGender);
        $this->assign('AllFrameShape', $allFrameShape);
        $this->assign('AllShape', $allShape);
        $this->assign('AllTexture', $allTexture);
        $this->assign('AllNosePad', $allNosePad);
        $this->view->assign('template', $this->category->getAttrCategoryById($row['category_id']));
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
        if ($this->request->isAjax()) {
            $categoryId = $this->request->post('categoryId');
            if (!$categoryId) {
                $this->error('参数错误，请重新尝试');
            }
            $result = $this->category->getAttrCategoryById($categoryId);
            if (!$result) {
                return  $this->error('对应分类不存在,请从新尝试');
            } elseif ($result == -1) {
                return $this->error('对应分类存在下级分类,请从新选择');
            }
            $this->view->engine->layout(false);
            //传递最后一个key值
            if ($result == 1) { //商品是镜架类型
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
                //获取是否可调节鼻托类型
                $allNosePad     = $this->itemAttribute->getAllNosePad();
                $this->assign('AllFrameType', $allFrameType);
                $this->assign('AllOrigin', $allOrigin);
                $this->assign('AllGlassesType', $allGlassesType);
                $this->assign('AllFrameSize', $allFrameSize);
                $this->assign('AllFrameGender', $allFrameGender);
                $this->assign('AllFrameShape', $allFrameShape);
                $this->assign('AllShape', $allShape);
                $this->assign('AllTexture', $allTexture);
                $this->assign('AllNosePad', $allNosePad);
                //把选择的模板值传递给模板
                $this->assign('Result', $result);
                $data = $this->fetch('frame');
            } elseif ($result == 2) { //商品是镜片类型
                $data = $this->fetch('eyeglass');
            } elseif ($result == 3) { //商品是饰品类型
                $data = $this->fetch('decoration');
            } else {
                $data = $this->fetch('attribute');
            }
            return  $this->success('ok', '', $data);
        } else {
            return $this->error(__('404 Not Found'));
        }
    }
    /***
     * 异步请求线下采购城市
     */
    public function ajaxGetProOrigin()
    {
        if ($this->request->isAjax()) {
            $data = $this->itemAttribute->getOrigin();
            if (!$data) {
                return $this->error('现在没有采购城市,请去添加', '', 'error', 0);
            }
            return $this->success('', '', $data, 0);
        } else {
            return $this->error(__('404 Not Found'));
        }
    }
    /***
     * 异步获取原始的sku(origin_sku)
     */
    public function ajaxGetLikeOriginSku(Request $request)
    {
        if ($this->request->isAjax()) {
            $origin_sku = $request->post('origin_sku');
            $result = $this->model->likeOriginSku($origin_sku);
            if (!$result) {
                return $this->error('商品SKU不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 根据商品分类和sku求出所有的商品
     */
    public function ajaxItemInfo()
    {
        if ($this->request->isAjax()) {
            $categoryId = $this->request->post('categoryId');
            $sku        = $this->request->post('sku');
            if (!$categoryId || !$sku) {
                $this->error('参数错误，请重新尝试');
            }
            $result = $this->category->getAttrCategoryById($categoryId);
            if (!$result) {
                return  $this->error('对应分类不存在,请从新尝试');
            } elseif ($result == -1) {
                return $this->error('对应分类存在下级分类,请从新选择');
            }
            if ($result == 1) {
                $row = $this->model->getItemInfo($sku);
                if (!$row) {
                    return false;
                }
                return  $this->success('ok', '', $row);
            } elseif ($result == 2) { //商品是镜片类型
                $data = $this->fetch('eyeglass');
            } elseif ($result == 3) { //商品是饰品类型
                $data = $this->fetch('decoration');
            } else {
                $data = $this->fetch('attribute');
            }
        } else {
            return $this->error(__('404 Not Found'));
        }
    }

    /**
     * 商品库存列表
     */
    public function goods_stock_list()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $skus = array_column($list, 'sku');
            //查询留样库存
            //查询实际采购信息 查询在途库存
            $purchase_map['stock_status'] = ['in', [0, 1]];
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $hasWhere['sku'] = ['in', $skus];
            $hasWhere['instock_num'] = 0;
            $purchase_list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
                ->where($purchase_map)
                ->column('sku,purchase_num,instock_num', 'sku');

            //查询样品数量
            $check = new \app\admin\model\warehouse\CheckItem;
            $check_list = $check->where('sku', 'in', $skus)->column('sum(sample_num) as sample_num', 'sku');

            foreach ($list as &$v) {
                $v['on_way_stock'] = @$purchase_list[$v['sku']]['purchase_num'] - @$purchase_list[$v['sku']]['instock_num'];
                $v['sample_stock'] = @$check_list[$v['sku']]['sample_num'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 商品库存详情
     */
    public function goods_stock_detail($ids = null)
    {
        $row = $this->model->get($ids);
        $this->assign('row', $row);

        //查询实际采购信息 查询在途库存
        $purchase_map['stock_status'] = ['in', [0, 1]];
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $hasWhere['sku'] = $row['sku'];
        $purchase_num = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->cache(true, 3600)
            ->sum('purchase_num-instock_num');
        $this->assign('purchase_num', $purchase_num);

        //查询此sku采购单出库情况
        $purchase_map['stock_status'] = ['in', [1, 2]];
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $hasWhere['sku'] = $row['sku'];
        $hasWhere['instock_num'] = ['>', 0];
        $list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->cache(true, 3600)
            ->select();

        //查询样品数量
        $check = new \app\admin\model\warehouse\CheckItem;
        $check_list = $check->where('sku', $row['sku'])->cache(true, 3600)->column('sum(sample_num) as sample_num', 'sku');
        foreach ($list as &$v) {
            $v['sample_stock'] = $check_list[$v['sku']]['sample_num'];
        }
        unset($v);
        $this->assign('list', $list);


        //在途库存列表
        $purchase_map['stock_status'] = ['in', [0, 1]];
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $hasWhere['sku'] = $row['sku'];
        $hasWhere['instock_num'] = 0;
        $info = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->cache(true, 3600)
            ->select();
        $this->assign('info', $info);
        return $this->view->fetch();
    }

    /***
     * ajax请求比对商品名称是否重复
     */
    public function ajaxGetInfoName()
    {
        if ($this->request->isAjax()) {
            $name = $this->request->post('name');
            if (!$name) {
                $this->error('参数错误，请重新尝试');
            }
            $result = $this->model->getInfoName($name);
            if (!$result) {
                return  $this->success('可以添加');
            } else {
                return $this->error('商品名称已经存在,请重新添加');
            }
        } else {
            return $this->error(__('404 Not Found'));
        }
    }

    /***
     * 商品详情表
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids, 'itemAttribute');
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
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
        $this->assign('AllFrameType', $allFrameType);
        $this->assign('AllOrigin', $allOrigin);
        $this->assign('AllGlassesType', $allGlassesType);
        $this->assign('AllFrameSize', $allFrameSize);
        $this->assign('AllFrameGender', $allFrameGender);
        $this->assign('AllFrameShape', $allFrameShape);
        $this->assign('AllShape', $allShape);
        $this->assign('AllTexture', $allTexture);
        $this->view->assign('template', $this->category->getAttrCategoryById($row['category_id']));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /***
     * 编辑商品的图片信息
     */
    public function images($id = null)
    {
        $row = $this->model->get($id, 'itemAttribute');
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $id = $params['id'];
            $item_status = $params['item_status'];
            $itemAttrData['frame_images'] = $params['frame_images'];
            $itemAttrData['create_frame_images_time'] = date("Y-m-d H:i:s", time());
            $itemAttrResult = Db::connect('database.db_stock')->name('item_attribute')->where('item_id', '=', $id)->update($itemAttrData);
            if ($item_status == 2) {
                $itemResult = Db::connect('database.db_stock')->name('item')->where('id', '=', $id)->update(['item_status' => $item_status]);
            } else {
                $itemResult = true;
            }
            if (($itemAttrResult !== false) && ($itemResult !== false)) {
                $this->success();
            } else {
                $this->error(__('Failed to upload product picture, please try again'));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /***
     *异步获取商品分类列表
     */
    public function ajaxGetItemCategoryList()
    {
        if ($this->request->isAjax()) {
            $json = $this->category->getItemCategoryList();
            if (!$json) {
                $json = [0 => '请添加商品分类'];
            }
            return json($json);
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 异步获取品牌分类列表
     */
    public function ajaxGetItemBrandList()
    {
        if ($this->request->isAjax()) {
            $json = (new ItemBrand())->getBrandList();
            if (!$json) {
                $json = [0 => '请添加商品分类'];
            }
            return json($json);
        } else {
            $this->error('404 Not found');
        }
    }

    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['item_status'] != 1) {
                $this->error('此商品状态不能提交审核');
            }
            $map['id'] = $id;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('提交审核成功');
            } else {
                $this->error('提交审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 提交审核之后审核通过
     */
    public function passAudit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['item_status'] != 2) {
                $this->error('此商品状态不能审核通过');
            }
            $map['id'] = $id;
            $data['item_status'] = 3;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                //添加到商品平台sku
                (new ItemPlatformSku())->addPlatformSku($row);
                $this->success('审核通过成功');
            } else {
                $this->error('审核通过失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 提交审核之后审核拒绝
     */
    public function auditRefused()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['item_status'] != 2) {
                $this->error('此商品状态不能审核拒绝');
            }
            $map['id'] = $id;
            $data['item_status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('审核拒绝成功');
            } else {
                $this->error('审核拒绝失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /***
     * 取消商品
     */
    public function cancel()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $map['id'] = $id;
            $data['item_status'] = 5;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('取消成功');
            } else {
                $this->error('取消失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 启用商品
     */
    public function startItem($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $data['is_open'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('启动成功');
            } else {
                $this->error('启动失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 禁止商品
     */
    public function forbiddenItem($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $data['is_open'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('禁止成功');
            } else {
                $this->error('禁止失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个一起审核通过
     */
    public function morePassAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $map['item_status'] = 2;
            $data['item_status'] = 3;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $row = $this->model->where('id', 'in', $ids)->field('sku,name')->select();
                if ($row) {
                    foreach ($row as $val) {
                        (new ItemPlatformSku())->addPlatformSku($val);
                    }
                }
                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个一起审核拒绝
     */
    public function moreAuditRefused($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $map['item_status'] = 2;
            $data['item_status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('拒绝审核成功');
            } else {
                $this->error('拒绝审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个商品移入回收站
     */
    public function moveRecycle($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $data['is_open'] = 3;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('移入回收站成功');
            } else {
                $this->error('移入回收站失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 一个还原
     */
    public function oneRestore()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $map['id'] = $id;
            $data['is_open'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('还原成功');
            } else {
                $this->error('还原失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 多个还原
     */
    public function moreRestore($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            $data['is_open'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('还原成功');
            } else {
                $this->error('还原失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /**
     * 异步获取商品库存信息
     */
    public function ajaxGoodsInfo()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $res = $this->model->getGoodsInfo($sku);
            if ($res) {
                $this->success('', '', $res);
            } else {
                $this->error('未找到数据！！');
            }
        }
    }
    /***
     * 异步检测sku是否存在
     */
    public function checkOriginIsExist()
    {
        if ($this->request->isAjax()) {
            $frame_texture     = $this->request->param('frame_texture');
            $procurment_origin = $this->request->param('procurement_origin');
            $origin_sku        = $this->request->param('origin_sku');
            if ($frame_texture) {
                $textureEncode = $this->itemAttribute->getTextureEncode($frame_texture);
            } else {
                $textureEncode = 'O';
            }
            $final_sku = $procurment_origin . $textureEncode . $origin_sku;
            $checkOriginSku     = $this->model->checkIsExistOriginSku($final_sku);
            if ($checkOriginSku) {
                return  $this->error(__('The commodity sku code already exists, please add the commodity again or contact the developer'));
            } else {
                return $this->success('可以使用这个sku');
            }
        } else {
            $this->error('404 Not Found');
        }
    }
    /***
     * 原先的crm平台商品的sku和库存变更到最新平台的库存表里面
     */
    public function changeSku()
    {
        $result = Db::connect('database.db_stock')->table('zeelool_product')->field('magento_sku as sku,true_qty as stock,remark')->select();
        if(!$result){
            return false;
        }
        $info   = Db::connect('database.db_stock')->name('item')->insertAll($result);
    }
    /***
     * 获取magento平台的商品信息
     */
    public function pullMagentoProductInfo()
    {
        //求出网站sku,分别对应的zeelool_sku,voogueme_sku,nihao_sku
        $sku_map = Db::connect('database.db_stock')->table('sku_map')->field('sku,zeelool_sku,voogueme_sku,nihao_sku')->order('id desc')->limit(3)->select();
        $magentoPlatform = Db::name('managto_platform')->field('id,managto_account,managto_key,managto_url')->select();
        $arr = $productInfo = [];
        foreach($sku_map as $k =>$v){
            if(!empty($v['zeelool_sku'])){
                $arr = $magentoPlatform[0];
                $magento_sku = $v['zeelool_sku'];    
            }elseif(!empty($v['voogueme_sku'])){
                $arr = $magentoPlatform[1];
                $magento_sku = $v['voogueme_sku'];
            }elseif(!empty($v['nihao_sku'])){
                $arr = $magentoPlatform[2];
                $magento_sku = $v['nihao_sku'];
            }else{
                continue;
            }
            try{
                $client = new \SoapClient($arr['managto_url'].'/api/soap/?wsdl');
                $session = $client->login($arr['managto_account'],$arr['managto_key']);
                $result = $client->call($session, 'catalog_product.info', $magento_sku);
                $client->endSession($session);
            }catch (\SoapFault $e){
                $this->error($e->getMessage());
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }
            $productInfo[] = $result;
        }
            echo '<pre>';
            var_dump($productInfo);
    }
    public function ceshi()
    {
        $magentoPlatform = Db::name('managto_platform')->where(['id'=>1])->field('id,managto_account,managto_key,managto_url')->find();
        $client = new \SoapClient($magentoPlatform['managto_url'].'/api/soap/?wsdl');
        $session = $client->login($magentoPlatform['managto_account'],$magentoPlatform['managto_key']);
        $result = $client->call($session, 'product_attribute.info', '459');
        dump($result);
        exit;
        

    }
    
}
