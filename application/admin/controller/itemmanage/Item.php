<?php

namespace app\admin\controller\itemmanage;

use app\common\controller\Backend;
use think\Request;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\itemmanage\ItemBrand;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\itemmanage\attribute\ItemAttribute;

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
    protected $noNeedLogin = ['pullMagentoProductInfo', 'analyticMagentoField', 'analyticUpdate', 'ceshi', 'optimizeSku', 'pullMagentoProductInfoTwo', 'changeSkuToPlatformSku', 'findSku', 'skuMap', 'skuMapOne'];

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
            $brandArr    = (new ItemBrand())->getBrandToItemList();
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
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
                // echo '<pre>';
                // var_dump($itemColor);
                // exit;
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
                        $row = Db::connect('database.db_stock')->name('item')->where(['sku' => $params['origin_skus']])->field('id,sku')->find();
                        $attributeWhere = [];
                        $attributeWhere['item_id'] = $row['id'];
                        $attributeWhere['frame_color'] = ['in', $itemColor];
                        $attributeInfo = Db::connect('database.db_stock')->name('item_attribute')->where($attributeWhere)->field('id,frame_color')->find();
                        if ($attributeInfo) {
                            $this->error('追加的商品SKU不能添加之前的颜色');
                        }
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
                            $data['frame_is_rimless'] = $params['shape'] == 1 ? 2 : 1;
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
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
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
        if (2 == $row['item_status']) {
            $this->error(__('The goods have been submitted for review and cannot be edited'), 'itemmanage/item');
        }
        if (5 == $row['item_status']) {
            $this->error('此商品已经取消，不能编辑', 'itemmanage/item');
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
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
                // $attributeWhere = [];
                // $attributeWhere['item_id'] = $row['id'];
                // $attributeWhere['frame_color'] = ['in',$itemColor];
                // $attributeInfo = Db::connect('database.db_stock')->name('item_attribute')->where($attributeWhere)->field('id,frame_color')->find();
                // if($attributeInfo){
                //     $this->error('追加的商品SKU不能添加之前的颜色');
                // }
                if (is_array($itemName) && !in_array("", $itemName)) {
                    $data = $itemAttribute = [];
                    Db::startTrans();
                    try {
                        foreach ($itemName as $k => $v) {
                            $data['name'] = $v;
                            $data['item_status'] = $params['item_status'];
                            $data['create_person'] = session('admin.nickname');
                            $data['create_time'] = date("Y-m-d H:i:s", time());
                            $data['frame_is_rimless'] = $params['shape'] == 1 ? 2 : 1;
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
                // if (!$row) {
                //     return false;
                // }
                // // echo '<pre>';
                // // var_dump($row);
                // // exit;
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

            //计算SKU总采购数量
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $hasWhere['sku'] = ['in', $skus];
            $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
            $purchase_map['stock_status'] = ['in', [0, 1]];
            $purchase_list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
                ->where($purchase_map)
                ->group('sku')
                ->column('sum(purchase_num) as purchase_num', 'sku');

            //查询出满足条件的采购单号
            $ids = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
                ->where($purchase_map)
                ->group('PurchaseOrder.id')
                ->column('PurchaseOrder.id');

            //查询留样库存
            //查询实际采购信息 查询在途库存 = 采购数量 减去 到货数量
            $check_map['status'] = 2;
            $check_map['type'] = 1;
            $check_map['Check.purchase_id'] = ['in', $ids];
            $check = new \app\admin\model\warehouse\Check;
            $hasWhere['sku'] = ['in', $skus];
            $check_list = $check->hasWhere('checkItem', $hasWhere)
                ->where($check_map)
                ->group('sku')
                ->column('sum(arrivals_num) as arrivals_num', 'sku');
            foreach ($list as &$v) {
                $v['on_way_stock'] = @$purchase_list[$v['sku']] - @$check_list[$v['sku']];
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


        //查询此sku采购单库存情况
        $purchase_map['stock_status'] = ['in', [1, 2]];
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $hasWhere['sku'] = $row['sku'];
        $hasWhere['instock_num'] = ['>', 0];

        $list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->where('instock_num-outstock_num<>0')
            ->field('PurchaseOrderItem.*')
            ->group('PurchaseOrderItem.id')
            ->select();

        $this->assign('list', $list);


        //在途库存列表
        //计算SKU总采购数量
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $where['a.purchase_status'] = ['in', [2, 5, 6, 7]];
        $where['a.stock_status'] = ['in', [0, 1]];
        $where['b.sku'] = $row['sku'];

        $info = $purchase->alias('a')->where($where)->field('a.id,a.purchase_number,b.sku,a.purchase_status,a.receiving_time,a.create_person,a.createtime,b.purchase_num')
            ->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            ->group('b.id')
            ->select();

        //查询生产周期
        $supplier_sku = new \app\admin\model\purchase\SupplierSku;
        $supplier_where['sku'] = $row['sku'];
        $supplier_where['status'] = 1;
        $supplier_where['label'] = 1;
        $product_cycle = $supplier_sku->where($supplier_where)->value('product_cycle');


        $num = 0;
        $check = new \app\admin\model\warehouse\Check;
        foreach ($info as $k => $v) {
            //计算质检单到货数量
            $map['a.purchase_id'] = $v['id'];
            $map['a.status'] = 2;
            $map['b.sku'] = $v['sku'];
            $map['a.type'] = 1;
            $arrivals_num = $check->alias('a')->where($map)->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')->sum('arrivals_num');
            if ($v['purchase_num'] - $arrivals_num == 0) {
                unset($info[$k]);
                continue;
            }
            $info[$k]['arrivals_num'] = $arrivals_num;
            $num += $v['purchase_num'] - $arrivals_num;
            $product_cycle = $product_cycle ? $product_cycle : 7;
            $info[$k]['product_cycle_time'] = date('Y-m-d H:i:s', strtotime('+' . $product_cycle . ' day', strtotime($v['createtime'])));

        }

        $this->assign('info', $info);
        $this->assign('num', $num);
        $this->assign('product_cycle', $product_cycle);


        /**
         * @todo 待定
         */
        //查询占用订单
        // $zeelool = new \app\admin\model\order\order\Zeelool;
        // $voogueme = new \app\admin\model\order\order\Voogueme;
        // $nihao = new \app\admin\model\order\order\Nihao;
        // $map['sku'] = 'FT0020-04';
        // $itemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
        // $skus = $itemPlatformSku->where($map)->column('platform_sku','platform_type');
        // //查Z站对应SKU
        // $where['sku'] = $skus[1];
        // $zeeloolOrderList = $zeelool->alias('a')->where($where)
        // ->field('increment_id,sku,qty_ordered,status,custom_is_match_frame,custom_is_match_lens,custom_is_send_factory,custom_is_delivery,custom_print_label')
        // ->join(['sales_flat_order_item b'],'b.order_id=a.entity_id')
        // ->select();
        // $zeeloolOrderList = collection($zeeloolOrderList)->toArray();

        // //查V站对应SKU
        // $where['sku'] = $skus[2];
        // $vooguemeOrderList = $zeelool->alias('a')->where($where)
        // ->field('increment_id,sku,qty_ordered,status,custom_is_match_frame,custom_is_match_lens,custom_is_send_factory,custom_is_delivery,custom_print_label')
        // ->join(['sales_flat_order_item b'],'b.order_id=a.entity_id')
        // ->select();
        // $vooguemeOrderList = collection($vooguemeOrderList)->toArray();


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
        // echo '<pre>';
        // var_dump($row);
        // exit;
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
    public function images($ids = null)
    {
        $row = $this->model->get($ids, 'itemAttribute');
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $item_status = $params['item_status'];
            $itemAttrData['frame_images'] = $params['frame_images'];
            $itemAttrData['create_frame_images_time'] = date("Y-m-d H:i:s", time());
            $itemAttrResult = Db::connect('database.db_stock')->name('item_attribute')->where('item_id', '=', $ids)->update($itemAttrData);
            if ($item_status == 2) {
                $itemResult = Db::connect('database.db_stock')->name('item')->where('id', '=', $ids)->update(['item_status' => $item_status]);
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
            $json = (new ItemBrand())->getBrandToItemList();
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
            $row = $this->model->get($id, 'itemAttribute');
            if ($row['item_status'] != 1) {
                $this->error('此商品状态不能提交审核');
            }
            if (false == $row['itemAttribute']['frame_images']) {
                $this->error('请先上传商品图片');
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
            $data['check_time']  = date("Y-m-d H:i:s", time());
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
    public function cancel($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['item_status'] == 5) {
                $this->error('此商品已经取消,不能再次取消');
            }
            $map['id'] = $ids;
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
            $row = $this->model->where($map)->field('id,is_open')->select();
            foreach ($row as $v) {
                if ($v['is_open'] != 2) {
                    $this->error('只有禁用状态才能操作！！');
                }
            }
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
            $row = $this->model->where($map)->field('id,is_open')->select();
            foreach ($row as $v) {
                if ($v['is_open'] != 1) {
                    $this->error('只有启用状态才能操作！！');
                }
            }
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
            $row = $this->model->where($map)->field('id,item_status')->select();
            foreach ($row as $v) {
                if ($v['item_status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['item_status'] = 3;
            $data['check_time']  = date("Y-m-d H:i:s", time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res != false) {
                $row = $this->model->where('id', 'in', $ids)->field('sku,name,frame_is_rimless')->select();
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
            $row = $this->model->where($map)->field('id,item_status')->select();
            foreach ($row as $v) {
                if ($v['item_status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['item_status'] = 4;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('审核拒绝成功');
            } else {
                $this->error('审核拒绝失败');
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
            $row = $this->model->where($map)->field('id,is_open')->select();
            foreach ($row as $v) {
                if (3 == $v['is_open']) {
                    $this->error('只有不在回收站才能操作！！');
                }
            }
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
    public function oneRestore($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (3 != $row['is_open']) {
                $this->error('只有在回收站才能操作！！');
            }
            $map['id'] = $ids;
            $data['is_open'] = 2;
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
            $row = $this->model->where($map)->field('id,is_open')->select();
            foreach ($row as $v) {
                if (3 != $v['is_open']) {
                    $this->error('只有在回收站才能操作！！');
                }
            }
            $data['is_open'] = 2;
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
     * 异步检测仓库sku和库存的数量是否符合更改镜架的要求
     */
    public function checkSkuAndQty()
    {
        if ($this->request->isAjax()) {
            $change_sku = $this->request->param('change_sku');
            $change_number = $this->request->param('change_number');
            if (!$change_sku) {
                return $this->error('请先填写商品sku');
            }
            if ($change_number < 1) {
                return $this->error('变更数量不能小于1');
            }
            $result = $this->model->check_sku_qty($change_sku);
            if (!$result) {
                return $this->error('填写的sku不存在,请重新填写');
            }
            if ($result['available_stock'] < $change_number) {
                return $this->error('镜架可用数量大于可用库存数量,无法更改镜架');
            }
            return $this->success();
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
        $where['magento_sku'] = ['NEQ', ''];
        $where['is_visable'] = 1;
        $result = Db::connect('database.db_stock')->table('zeelool_product')->where($where)->field('name,magento_sku as sku,true_qty as stock,true_qty as available_stock,3 as item_status, remark')->select();
        if (!$result) {
            return false;
        } else {

            $info   = Db::connect('database.db_stock')->name('item')->insertAll($result);
        }
    }
    /***
     * 第一步
     * 获取magento平台的商品信息
     */
    public function pullMagentoProductInfo()
    {
        //求出网站sku,分别对应的zeelool_sku,voogueme_sku,nihao_sku
        $where['pull_status'] = 0;
        $sku_map = Db::connect('database.db_stock')->table('sku_map')->where($where)->field('sku,zeelool_sku,voogueme_sku,nihao_sku')->order('id desc')->limit(2)->select();
        //求出每个站点的地址,用户名和key
        $magentoPlatform = Db::name('magento_platform')->field('id,magento_account,magento_key,magento_url')->select();
        //求出每个站点的存储信息(对应的魔晶平台存储字段和magento平台存储字段)
        $platform_map = Db::name('platform_map')->field('platform_id,platform_field,magento_field')->select();
        if (!$platform_map) {
            return false;
        }
        $mapArr = $map = $arr = [];
        //每个平台的存储字段都放在一起
        foreach ($platform_map as $key => $val) {
            if (1 == $val['platform_id']) {
                $mapArr[1]['platform_field'][] = $val['platform_field'];
                $mapArr[1]['magento_field'][]  = $val['magento_field'];
            }
            if (2 == $val['platform_id']) {
                $mapArr[2]['platform_field'][] = $val['platform_field'];
                $mapArr[2]['magento_field'][]  = $val['magento_field'];
            }
            if (3 == $val['platform_id']) {
                $mapArr[3]['platform_field'][] = $val['platform_field'];
                $mapArr[3]['magento_field'][]  = $val['magento_field'];
            }
        }
        foreach ($sku_map as $k => $v) {
            if (!empty($v['zeelool_sku'])) {
                $where['pull_status'] = 1;
                $map = $mapArr[1];
                $arr = $magentoPlatform[0];
                $magento_sku = $v['zeelool_sku'];
            } elseif (!empty($v['voogueme_sku'])) {
                $where['pull_status'] = 2;
                $map = $mapArr[2];
                $arr = $magentoPlatform[1];
                $magento_sku = $v['voogueme_sku'];
            } elseif (!empty($v['nihao_sku'])) {
                $where['pull_status'] = 3;
                $map = $mapArr[3];
                $arr = $magentoPlatform[2];
                $magento_sku = $v['nihao_sku'];
            } else {
                Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['pull_status' => -1]);
                continue;
            }
            try {
                //调用magento平台的API获取商品信息
                $client = new \SoapClient($arr['magento_url'] . '/api/soap/?wsdl');
                $session = $client->login($arr['magento_account'], $arr['magento_key']);
                $result = $client->call($session, 'catalog_product.info', $magento_sku);
                $client->endSession($session);
            } catch (\SoapFault $e) {
                Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['pull_status' => -1]);
                //$this->error($e->getMessage());
                continue;
                //$this->error(11111);
                //$this->error($e->getMessage());
            } catch (\Exception $e) {
                Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['pull_status' => -1]);
                //$this->error($e->getMessage());
                continue;
                //$this->error(22222);
                //$this->error($e->getMessage());
            }
            $storeArr = [];
            //循环magento平台存储的字段
            foreach ($map['magento_field'] as $keys => $vals) {
                if (array_key_exists($vals, $result)) {
                    $storeArr[$vals] = $result[$vals];
                }
            }
            // echo '<pre>';
            // var_dump($storeArr);
            // exit;
            $serializeResult = serialize($storeArr);
            $where['information'] = $serializeResult;
            Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update($where);
            // echo '<pre>';
            // var_dump($storeArr);
            // $productInfo[] = $result;
        }
        // echo '<pre>';
        // var_dump($productInfo);
    }
    public function ceshi2()
    {
        $platform_map = Db::name('platform_map')->field('platform_id,platform_field,magento_field')->select();
        if (!$platform_map) {
            return false;
        }
        $mapArr = [];
        foreach ($platform_map as $k => $v) {
            if (1 == $v['platform_id']) {
                $mapArr[1]['platform_field'][] = $v['platform_field'];
                $mapArr[1]['magento_field'][]  = $v['magento_field'];
            }
            if (2 == $v['platform_id']) {
                $mapArr[2]['platform_field'][] = $v['platform_field'];
                $mapArr[2]['magento_field'][]  = $v['magento_field'];
            }
            if (3 == $v['platform_id']) {
                $mapArr[3]['platform_field'][] = $v['platform_field'];
                $mapArr[3]['magento_field'][]  = $v['magento_field'];
            }
        }
        echo '<pre>';
        var_dump($mapArr);
    }
    /***
     * 第二步
     * 解析magento字段获取字段的值
     */
    public  function  analyticMagentoField()
    {
        //求出每个站点的地址,用户名和key
        $magentoPlatform = Db::name('magento_platform')->field('id,magento_account,magento_key,magento_url')->select();
        $where['analytic_status'] = 0;
        $result = Db::connect('database.db_stock')->table('sku_map')->where($where)->field('sku,information,pull_status')->order('id desc')->limit(1)->select();
        if (!$result) {
            return false;
        }
        //求出每个站点的存储信息(对应的魔晶平台存储字段和magento平台存储字段)
        // $platform_map = Db::name('platform_map')->field('platform_id,platform_field,magento_field')->select();
        // if(!$platform_map){
        //     return false;
        // }
        $updateData['analytic_status'] = 1;
        foreach ($result as $k => $v) {
            $informationArr = unserialize($v['information']);
            if (empty($informationArr)) {
                continue;
            }
            // echo '<pre>';
            // var_dump($informationArr);
            $arr = $changeArr = [];
            if (1 == $v['pull_status']) { //zeelool商品
                $arr = $magentoPlatform[0];
            } elseif (2 == $v['pull_status']) { //voogueme站商品
                $arr = $magentoPlatform[1];
            } elseif (3 == $v['pull_status']) { //nihao商品
                $arr = $magentoPlatform[2];
            }
            //调用magento平台的API获取商品信息
            $client = new \SoapClient($arr['magento_url'] . '/api/soap/?wsdl');
            $session = $client->login($arr['magento_account'], $arr['magento_key']);
            foreach ($informationArr as $key => $val) {
                if (!empty($val)) {
                    $listAttributes = $client->call(
                        $session,
                        'product_attribute.options',
                        $key
                    );
                    if (empty($listAttributes)) {
                        $changeArr[$key] = $val;
                    } else {
                        foreach ($listAttributes as $keys => $vals) {
                            if ($val == $vals['value']) {
                                $changeArr[$key] = $vals['label'];
                            }
                        }
                    }
                }
            }
            $updateData['change_information'] = serialize($changeArr);
            Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update($updateData);
        }
    }
    /***
     * 第三步
     * 解析更新到数据库
     */
    public function analyticUpdate()
    {
        $where['change_information'] = ['NEQ', ''];
        $where['pull_status'] = ['GT', 0];
        $where['analytic_status'] = 1;
        $result = Db::connect('database.db_stock')->table('sku_map')->where($where)->join('fa_item g', 'g.sku=sku_map.sku')->field('g.id,sku_map.sku,sku_map.change_information,sku_map.pull_status')->order('id desc')->limit(1)->select();
        if (!$result) {
            return false;
        }
        $platform_map = Db::name('platform_map')->field('platform_id,platform_field,magento_field')->select();
        if (!$platform_map) {
            return false;
        }
        //获取所有材质
        $texture    = (new ItemAttribute())->getAllTexture();
        //获取所有眼镜形状
        $frameShape = (new ItemAttribute())->getAllFrameShape();
        //获取适合人群
        $frameGender   = (new ItemAttribute())->getFrameGender();
        //获取镜架所有的颜色
        //$frameColor    = (new ItemAttribute())->getFrameColor();
        //获取眼镜类型
        $glassesType   = (new ItemAttribute())->getGlassesType();
        //获取所有线下采购产地
        //$origin        = (new ItemAttribute())->getOrigin();
        //获取配镜类型
        $frameType     = (new ItemAttribute())->getFrameType();
        //每个平台的存储字段都放在一起
        foreach ($platform_map as $key => $val) {
            if (1 == $val['platform_id']) {
                $mapArr[1][$val['platform_field']] = $val['magento_field'];
            }
            if (2 == $val['platform_id']) {
                $mapArr[2][$val['platform_field']] = $val['magento_field'];
            }
            if (3 == $val['platform_id']) {
                $mapArr[3][$val['platform_field']] = $val['magento_field'];
            }
        }
        // echo '<pre>';
        // var_dump($mapArr);
        // exit;
        $arr = $map = $platform = $finalResult = [];
        foreach ($result as $k => $v) {
            if (!empty($v['change_information'])) {
                $arr = unserialize($v['change_information']);
                if (1 == $v['pull_status']) {
                    $map = $mapArr[1];
                } elseif (2 == $v['pull_status']) {
                    $map = $mapArr[2];
                } elseif (3 == $v['pull_status']) {
                    $map = $mapArr[3];
                }
                if (2 != $v['pull_status']) {
                    //获得所有框型
                    $shape  = (new ItemAttribute())->getAllShape();
                    //获取尺寸型号
                    $frameSize     = (new ItemAttribute())->getFrameSize();
                } else {
                    $shape  = (new ItemAttribute())->getAllShape(2);
                    $frameSize     = (new ItemAttribute())->getFrameSize(2);
                }
                foreach ($arr as $keys => $vals) {
                    //找出键名
                    $platformName =  array_search($keys, $map);
                    if ($platformName) {
                        $platform[$platformName] = $vals;
                    }
                }
                // echo '<pre>';
                // var_dump($platform);
                // exit;
                //判断是否存在材质
                $finalResult['item_id'] = $v['id'];
                if (array_key_exists('frame_texture', $platform)) {
                    //判断材质对应的值是否在平台的对应字段值当中，如果是的话求出值
                    if (in_array($platform['frame_texture'], $texture)) {
                        //  echo $platform['frame_texture'];
                        //  echo '<br/>';
                        //  echo array_search($platform['frame_texture'],$texture);
                        $finalResult['frame_texture'] =  array_search($platform['frame_texture'], $texture);
                        unset($platform['frame_texture']);
                    } else {
                        $finalResult['frame_texture'] = 0;
                    }
                }
                //判断是否存在眼镜类型
                if (array_key_exists('glasses_type', $platform)) {
                    if (in_array($platform['glasses_type'], $glassesType)) {
                        //echo array_search($platform['frame_texture'],$texture);
                        $finalResult['glasses_type'] = array_search($platform['glasses_type'], $glassesType);
                        unset($platform['glasses_type']);
                    } else {
                        $finalResult['glasses_type'] = 0;
                    }
                }
                //获取所有眼镜形状
                if (array_key_exists('frame_shape', $platform)) {
                    if (in_array(lcfirst($platform['frame_shape']), $frameShape)) {
                        // echo 1234;
                        // echo array_search($platform['frame_shape'],$frameShape);
                        $finalResult['frame_shape'] = array_search(lcfirst($platform['frame_shape']), $frameShape);
                        unset($platform['frame_shape']);
                    } else {
                        $finalResult['frame_shape'] = 0;
                    }
                }
                if (array_key_exists('shape', $platform)) {
                    if (in_array($platform['shape'], $shape)) {
                        //echo array_search($platform['shape'],$shape);
                        $finalResult['shape'] = array_search($platform['shape'], $shape);
                        unset($platform['shape']);
                    } else {
                        $finalResult['shape'] = 0;
                    }
                }
                if (array_key_exists('frame_gender', $platform)) {
                    if (in_array($platform['frame_gender'], $frameGender)) {
                        //echo array_search($platform['frame_gender'],$frameGender);
                        $finalResult['frame_gender'] = array_search($platform['frame_gender'], $frameGender);
                        unset($platform['frame_gender']);
                    } else {
                        $finalResult['frame_gender'] = 0;
                    }
                }
                if (array_key_exists('frame_size', $platform)) {
                    if (in_array(lcfirst($platform['frame_size']), $frameSize)) {
                        //echo array_search($platform['frame_size'],$frameSize);
                        $finalResult['frame_size'] = array_search(lcfirst($platform['frame_size']), $frameSize);
                        unset($platform['frame_size']);
                    } else {
                        $finalResult['frame_size'] = 0;
                    }
                }
                if (array_key_exists('frame_type', $platform)) {
                    if (in_array($platform['frame_type'], $frameType)) {
                        //echo array_search($platform['frame_type'],$frameType);
                        $finalResult['frame_type'] = array_search($platform['frame_type'], $frameType);
                        unset($platform['frame_type']);
                    } else {
                        $finalResult['frame_type'] = 0;
                    }
                }
                if (array_key_exists('frame_is_advance', $platform)) {
                    if (strcasecmp($platform['frame_is_advance'], 'yes') == 0) {
                        $finalResult['frame_is_advance'] = 1;
                        unset($platform['frame_is_advance']);
                    } else {
                        $finalResult['frame_is_advance'] = 0;
                    }
                }
                if (array_key_exists('frame_temple_is_spring', $platform)) {
                    if (strcasecmp($platform['frame_temple_is_spring'], 'yes') == 0) {
                        $finalResult['frame_temple_is_spring'] = 1;
                        unset($platform['frame_temple_is_spring']);
                    } else {
                        $finalResult['frame_temple_is_spring'] = 0;
                    }
                }
                if (array_key_exists('frame_is_adjust_nose_pad', $platform)) {
                    if (strcasecmp($platform['frame_is_adjust_nose_pad'], 'yes') == 0) {
                        $finalResult['frame_is_adjust_nose_pad'] = 1;
                        unset($platform['frame_is_adjust_nose_pad']);
                    } else {
                        $finalResult['frame_is_adjust_nose_pad'] = 0;
                    }
                }
                $arrFinal = array_merge($platform, $finalResult);
                $finalInsert = Db::connect('database.db_stock')->name('item_attribute')->insert($arrFinal);
                if ($finalInsert) {
                    Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['analytic_status' => 2]);
                    Db::connect('database.db_stock')->name('item')->where(['id' => $v['id']])->update(['category_id' => 6]);
                }
            }
        }
    }
    /***
     * 优化完善nihao站的sku
     */
    public function optimizeSku()
    {
        $where['nihao_sku'] = ['NEQ', ''];
        $where['status'] = 3;
        $result = Db::connect('database.db_stock')->table('sku_map')->where($where)->field('sku,nihao_sku')->order('id desc')->limit(10)->select();
        if (!$result) {
            return false;
        }
        foreach ($result as $k => $v) {
            $colorArr = explode('-', $v['sku']);
            $data['status'] = 4;
            $data['nihao_sku'] = $v['nihao_sku'] . '-' . $colorArr[1];
            Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update($data);
        }
    }
    /***
     *商品sku转化到平台sku库里面
     */
    public function changeSkuToPlatformSku()
    {
        $sql = "select name,sku,frame_is_rimless from fa_item where is_update_platform = 0 limit 100 ";
        $result = Db::connect('database.db_stock')->query($sql);
        if (!$result) {
            return false;
        }
        foreach ($result as $v) {
            if (!empty($v['sku'])) {
                $info = (new ItemPlatformSku())->addPlatformSku($v);
                if ($info) {
                    Db::connect('database.db_stock')->name('item')->where(['sku' => $v['sku']])->update(['is_update_platform' => 1]);
                }
            }
        }
    }
    /**
     * 查找对比fa_item中sku是否全部更新到fa_item_platform_sku当中
     */
    public function findSku()
    {
        $result = Db::connect('database.db_stock')->name('item')->field('sku')->select();
        if (!$result) {
            return false;
        }
        $arr =  $newArr = [];
        foreach ($result as $v) {
            $arr[] = $v['sku'];
        }
        $info = Db::connect('database.db_stock')->name('item_platform_sku')->where('sku', 'in', $arr)->distinct(true)->field('sku')->select();
        foreach ($info as $vs) {
            $newArr[] = $vs['sku'];
        }
        echo '<pre>';
        echo count(array_filter($arr)) . '<br/>';
        //var_dump($arr);
        echo count($newArr) . '<br/>';
        $finalArr = array_diff($newArr, $arr);
        var_dump($finalArr);
    }
    /***
     * 清除空的sku映射
     */
    public function skuMapOne()
    {
        $sql = "select sku,zeelool_sku,voogueme_sku,nihao_sku from sku_map where is_update_sku=1 limit 50";
        $result = Db::connect('database.db_stock')->query($sql);
        if (!$result) {
            return false;
        }
        foreach ($result as $v) {
            if (($v['zeelool_sku'] == '') && ($v['voogueme_sku'] == '') && ($v['nihao_sku'] == '')) {
                Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['is_update_sku' => 3]);
            } else {
                Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['is_update_sku' => 2]);
            }
        }
    }
    /***
     * 找出平台映射关系表(sku_map)中的对应关系映射变更到fa_item_platform_sku当中
     */
    public function skuMap()
    {
        $sql = "select sku,zeelool_sku,voogueme_sku,nihao_sku from sku_map where is_update_sku=2 limit 50";
        $result = Db::connect('database.db_stock')->query($sql);
        if (!$result) {
            return false;
        }
        $i = 0;
        foreach ($result as $k => $v) {
            if (!empty($v['zeelool_sku'])) {
                $zeeloolWhere['sku'] = $v['sku'];
                $zeeloolWhere['platform_type'] = 1;
                $zeeloolData['platform_sku'] = $v['zeelool_sku'];
                $zeeloolData['update_platform'] = 2;
                Db::connect('database.db_stock')->name('item_platform_sku')->where($zeeloolWhere)->update($zeeloolData);
                $i++;
            }
            if (!empty($v['voogueme_sku'])) {
                $vooguemeWhere['sku'] = $v['sku'];
                $vooguemeWhere['platform_type'] = 2;
                $vooguemeData['platform_sku'] = $v['voogueme_sku'];
                $vooguemeData['update_platform'] = 2;
                Db::connect('database.db_stock')->name('item_platform_sku')->where($vooguemeWhere)->update($vooguemeData);
                $i++;
            }
            if (!empty($v['nihao_sku'])) {
                $nihaoWhere['sku'] = $v['sku'];
                $nihaoWhere['platform_type'] = 3;
                $nihaoData['platform_sku'] = $v['nihao_sku'];
                $nihaoData['update_platform'] = 2;
                Db::connect('database.db_stock')->name('item_platform_sku')->where($nihaoWhere)->update($nihaoData);
                $i++;
            }
            Db::connect('database.db_stock')->table('sku_map')->where(['sku' => $v['sku']])->update(['is_update_sku' => 1]);
        }
        echo $i;
        Db::connect('database.db_stock')->name('num')->where(['id' => 1])->setInc('num', $i);
        // echo '<pre>';
        // var_dump($result);

    }
    /**
     * 从仓库sku找到zeelool的sku并且更新到sku_map数据库当中
     */
    // public function add_map_sku()
    // {
    //     $where['magento_sku'] = ['NEQ',''];
    //     $where['is_visable'] = 1;
    //     $result = M('product')->where($where)->field('magento_sku as sku')->select();
    // 	if(!$result){
    // 		echo 123;
    // 		return 123;
    // 	}	
    //     $map = M('map','sku_')->addAll($result);
    // }
    // public function ceshi(){

    // }
    /***
     * 定时任务
     */
    /***
     * 商品预售管理
     */
    public function presell()
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
                ->where(['item_status' => 3])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model->where('is_open', '<', 3)
                ->where(['item_status' => 3])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //求出分类列表
            $categoryArr = $this->category->getItemCategoryList();
            //求出品牌列表
            $brandArr    = (new ItemBrand())->getBrandToItemList();
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

    /***
     * 添加商品预售
     */
    public function add_presell()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if (empty($params['sku'])) {
                    $this->error(__('Platform sku cannot be empty'));
                }
                if (empty($params['presell_num'])) {
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
                if ($params['presell_start_time'] == $params['presell_end_time']) {
                    $this->error('预售开始时间和结束时间不能相等');
                }
                $row = $this->model->pass_check_sku($params['sku']);
                if ($row['presell_residue_num'] > 0) {
                    $this->error('SKU剩余预售数量没有扣完,不能添加');
                }
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
                    $params['presell_residue_num'] = $params['presell_num'];
                    $params['presell_num']         += $row['presell_num'];
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());

                    if ($now_time >= $params['presell_end_time']) { //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $this->model->allowField(true)->isUpdate(true)->save($params, ['sku' => $params['sku']]);
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
    /***
     * 检测商品sku是否存在
     * 
     */
    public function check_sku_exists()
    {
        if ($this->request->isAjax()) {
            $final_sku = $this->request->post('origin_sku');
            $checkOriginSku     = $this->model->pass_check_sku($final_sku);
            if ($checkOriginSku) {
                return  $this->success(__('此sku存在'));
            } else {
                return $this->error(__('此sku不存在'));
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 开启预售
     */
    public function openStart($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['presell_status'] == 1) {
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $now_time = date('Y-m-d H:i:s', time());
            if ($row['presell_end_time'] < $now_time) {
                $this->error(__('The closing time has expired, please select again'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('预售开启成功');
            } else {
                $this->error('预售开启失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 关闭预售
     */
    public function openEnd($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['presell_status'] == 2) {
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('关闭预售成功');
            } else {
                $this->error('关闭预售失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
}
