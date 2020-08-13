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
use app\admin\model\itemmanage\Item_presell_log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use fast\Soap;

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
    //protected $noNeedLogin = ['pullMagentoProductInfo', 'analyticMagentoField', 'analyticUpdate', 'ceshi', 'optimizeSku', 'pullMagentoProductInfoTwo', 'changeSkuToPlatformSku', 'findSku', 'skuMap', 'ajaxGoodsInfo'];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['ajaxGoodsInfo'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\Item;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->view->assign('categoryList', $this->category->categoryList());
        $this->view->assign('brandList', (new ItemBrand())->getBrandList());
        $this->view->assign('AllFrameColor', $this->itemAttribute->getFrameColor());
        $this->view->assign('AllDecorationColor', $this->itemAttribute->getFrameColor(3));
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
        if ($this->request->isAjax()) { // 判断是否为Ajax调用
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
    //新增商品(原)
    public function add_yuan()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                $price     = $params['price'];
                // dump($params);
                // exit;
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
                //区分是镜架还是配饰
                $item_type = $params['item_type'];
                $data = $itemAttribute = [];
                if (3 == $item_type) { //配饰
                    if (is_array($itemName) && !in_array("", $itemName)) {
                        //求出对应的sku编码规则
                        $resultEncode  = $this->category->getCategoryTexture($params['category_id']);
                        $textureEncodeInfo = $resultEncode['typeResult'];
                        if (false !== strpos($textureEncodeInfo, '-')) {
                            $textureArr = explode('-', $textureEncodeInfo);
                            $textureEncode = $textureArr[0];
                        } else {
                            $textureEncode = $textureEncodeInfo;
                        }
                        if (!$textureEncode) {
                            $this->error(__('The corresponding encoding rule does not exist, please try again'));
                        }
                        //如果是后来添加的
                        if (!empty($params['origin_skus']) && $params['item-count'] >= 1) { //正常情况
                            $count = $params['item-count'];
                            $row = Db::connect('database.db_stock')->name('item')->where(['sku' => $params['origin_skus']])->field('id,sku')->find();
                            $attributeWhere = [];
                            $attributeWhere['item_id'] = $row['id'];
                            $attributeWhere['accessory_color'] = ['in', $itemColor];
                            $attributeInfo = Db::connect('database.db_stock')->name('item_attribute')->where($attributeWhere)->field('id,accessory_color')->find();
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
                                $data['price']       = $price[$k];
                                $data['create_person'] = session('admin.nickname');
                                $data['create_time'] = date("Y-m-d H:i:s", time());
                                //后来添加的商品数据
                                if (!empty($params['origin_skus'])) {
                                    $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                                    ++$count;
                                } else {
                                    $data['sku'] = $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                                }
                                // $lastInsertId = Db::name('item')->insertGetId($data);
                                $lastInsertId = Db::connect('database.db_stock')->name('item')->insertGetId($data);
                                if ($lastInsertId !== false) {
                                    $itemAttribute['item_id'] = $lastInsertId;
                                    $itemAttribute['attribute_type'] = 3;
                                    $itemAttribute['accessory_texture'] = $params['frame_texture'];
                                    $itemAttribute['accessory_color'] = $itemColor[$k];
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
                        if ($lastInsertId !== false) {
                            $this->success();
                        } else {
                            $this->error(__('No rows were inserted'));
                        }
                    }
                } else { //镜架
                    if (is_array($itemName) && !in_array("", $itemName)) {
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
                                $data['price']       = $price[$k];
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
                $price     = $params['price'];
                // dump($params);
                // exit;
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
                //区分是镜架还是配饰
                $item_type = $params['item_type'];
                $data = $itemAttribute = [];
                if (3 == $item_type) { //配饰
                    if (is_array($itemName) && !in_array("", $itemName)) {
                        //求出对应的sku编码规则
                        $resultEncode  = $this->category->getCategoryTexture($params['category_id']);
                        $textureEncodeInfo = $resultEncode['typeResult'];
                        if (false !== strpos($textureEncodeInfo, '-')) {
                            $textureArr = explode('-', $textureEncodeInfo);
                            $textureEncode = $textureArr[0];
                        } else {
                            $textureEncode = $textureEncodeInfo;
                        }
                        if (!$textureEncode) {
                            $this->error(__('The corresponding encoding rule does not exist, please try again'));
                        }
                        //如果是后来添加的
                        if (!empty($params['origin_skus']) && $params['item-count'] >= 1) { //正常情况
                            $count = $params['item-count'];
                            $row = Db::connect('database.db_stock')->name('item')->where(['sku' => $params['origin_skus']])->field('id,sku')->find();
                            $attributeWhere = [];
                            $attributeWhere['item_id'] = $row['id'];
                            $attributeWhere['accessory_color'] = ['in', $itemColor];
                            $attributeInfo = Db::connect('database.db_stock')->name('item_attribute')->where($attributeWhere)->field('id,accessory_color')->find();
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
                                $data['price']       = $price[$k];
                                $data['create_person'] = session('admin.nickname');
                                $data['create_time'] = date("Y-m-d H:i:s", time());
                                //后来添加的商品数据
                                if (!empty($params['origin_skus'])) {
                                    $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                                    ++$count;
                                } else {
                                    $data['sku'] = $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                                }
                                // $lastInsertId = Db::name('item')->insertGetId($data);
                                $lastInsertId = Db::connect('database.db_stock')->name('item')->insertGetId($data);
                                if ($lastInsertId !== false) {
                                    $itemAttribute['item_id'] = $lastInsertId;
                                    $itemAttribute['attribute_type'] = 3;
                                    $itemAttribute['accessory_texture'] = $params['frame_texture'];
                                    $itemAttribute['accessory_color'] = $itemColor[$k];
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
                        if ($lastInsertId !== false) {
                            $this->success();
                        } else {
                            $this->error(__('No rows were inserted'));
                        }
                    }
                } else { //镜架
                    if (is_array($itemName) && !in_array("", $itemName)) {
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
                                $data['price']       = $price[$k];
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
            }

            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /***
     * 后来修改编辑商品
     */
    public function edit_yuan($ids = null)
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
                $price     = $params['price'];
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
                $item_type = $params['item_type'];
                $data = $itemAttribute = [];
                if (3 == $item_type) {
                    if (is_array($itemName) && !in_array("", $itemName)) {

                        Db::startTrans();
                        try {
                            foreach ($itemName as $k => $v) {
                                $data['name'] = $v;
                                $data['price']       = $price[$k];
                                $data['item_status'] = $params['item_status'];
                                $item = Db::connect('database.db_stock')->name('item')->where('id', '=', $row['id'])->update($data);
                                $itemAttribute['attribute_type'] = 3;
                                $itemAttribute['accessory_color'] = $itemColor[$k];
                                $itemAttribute['accessory_texture'] = $params['frame_texture'];
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
                } else {
                    if (is_array($itemName) && !in_array("", $itemName)) {
                        Db::startTrans();
                        try {
                            foreach ($itemName as $k => $v) {
                                $data['name'] = $v;
                                $data['price']       = $price[$k];
                                $data['item_status'] = $params['item_status'];
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
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $result = $this->category->getAttrCategoryById($row['category_id']);
        if (3 <= $result) {
            $info = $this->category->getCategoryTexture($row['category_id']);
            $this->assign('AllTexture', $info['textureResult']);
            $this->assign('AllFrameColor', $info['colorResult']);
        } else {
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
        }
        $this->view->assign('template', $this->category->getAttrCategoryById($row['category_id']));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
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
                $price     = $params['price'];
                if (count($itemColor) != count(array_unique($itemColor))) {
                    $this->error('同一款商品的颜色值不能相同');
                }
                $item_type = $params['item_type'];
                $data = $itemAttribute = [];
                if (3 == $item_type) {
                    if (is_array($itemName) && !in_array("", $itemName)) {

                        Db::startTrans();
                        try {
                            foreach ($itemName as $k => $v) {
                                $data['name'] = $v;
                                $data['price']       = $price[$k];
                                $data['item_status'] = $params['item_status'];
                                $item = Db::connect('database.db_stock')->name('item')->where('id', '=', $row['id'])->update($data);
                                $itemAttribute['attribute_type'] = 3;
                                $itemAttribute['accessory_color'] = $itemColor[$k];
                                $itemAttribute['accessory_texture'] = $params['frame_texture'];
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
                } else {
                    if (is_array($itemName) && !in_array("", $itemName)) {
                        Db::startTrans();
                        try {
                            foreach ($itemName as $k => $v) {
                                $data['name'] = $v;
                                $data['price']       = $price[$k];
                                $data['item_status'] = $params['item_status'];
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
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $result = $this->category->getAttrCategoryById($row['category_id']);
        if (3 <= $result) {
            $info = $this->category->getCategoryTexture($row['category_id']);
            $this->assign('AllTexture', $info['textureResult']);
            $this->assign('AllFrameColor', $info['colorResult']);
        } else {
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
        }
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
            } elseif ($result >= 3) { //商品是饰品类型
                $info = $this->category->getCategoryTexture($categoryId);
                $this->assign('AllTexture', $info['textureResult']);
                $this->assign('AllFrameColor', $info['colorResult']);
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
            } elseif ($result == 2) { //商品是镜片类型
                $data = $this->fetch('eyeglass');
            } elseif ($result >= 3) { //商品是饰品类型
                $row  = $this->model->getItemInfo($sku, $result);
                $result = $this->category->getCategoryTexture($categoryId);
                $this->assign('AllTexture', $result['textureResult']);
                $this->assign('AllFrameColor', $result['colorResult']);
                $data = $this->fetch('decoration');
            } else {
                $data = $this->fetch('attribute');
            }
            return  $this->success('ok', '', $row);
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
            $map['is_open'] = 1;
            $map['is_del'] = 1;
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $item_platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            //查询各站SKU虚拟库存
            foreach ($list as &$v) {
                $v['zeelool_stock'] = $item_platform->where(['sku' => $v['sku'], 'platform_type' => 1])->value('stock');
                $v['voogueme_stock'] = $item_platform->where(['sku' => $v['sku'], 'platform_type' => 2])->value('stock');
                $v['nihao_stock'] = $item_platform->where(['sku' => $v['sku'], 'platform_type' => 3])->value('stock');
                $v['meeloog_stock'] = $item_platform->where(['sku' => $v['sku'], 'platform_type' => 4])->value('stock');
                $v['wesee_stock'] = $item_platform->where(['sku' => $v['sku'], 'platform_type' => 5])->value('stock');
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
        $purchase_map['is_del'] = 1;
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
        $where['a.purchase_status'] = ['in', [2, 5, 6]];
        // $where['a.stock_status'] = ['in', [0, 1]];
        $where['a.is_del'] = 1;
        $where['b.sku'] = $row['sku'];

        $info = $purchase->alias('a')->where($where)->field('a.id,a.purchase_number,b.sku,a.purchase_status,a.receiving_time,a.create_person,a.createtime,b.purchase_num,a.arrival_time,receiving_time')
            ->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            ->group('b.id')
            ->select();

        // $check = new \app\admin\model\warehouse\Check;
        // foreach ($info as $k => $v) {
        //     //计算质检单到货数量
        //     $map['a.purchase_id'] = $v['id'];
        //     $map['a.status'] = 2;
        //     $map['b.sku'] = $v['sku'];
        //     $map['a.type'] = 1;
        //     $arrivals_num = $check->alias('a')->where($map)->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')->sum('arrivals_num');
        //     if ($v['purchase_num'] - $arrivals_num == 0) {
        //         unset($info[$k]);
        //         continue;
        //     }
        //     $info[$k]['arrivals_num'] = $arrivals_num;
        // }

        $this->assign('info', $info);


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
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $result = $this->category->getAttrCategoryById($row['category_id']);
        if (3 <= $result) {
            $info = $this->category->getCategoryTexture($row['category_id']);
            $this->assign('AllTexture', $info['textureResult']);
            $this->assign('AllFrameColor', $info['colorResult']);
        } else {
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
        }
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
            // if (false == $row['itemAttribute']['frame_images']) {
            //     $this->error('请先上传商品图片');
            // }
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

            Db::startTrans();
            try {
                $map['id'] = $id;
                $data['item_status'] = 3;
                $data['check_time']  = date("Y-m-d H:i:s", time());
                $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
                if ($res === false) {
                    throw new Exception('审核失败！！');
                }

                //查询同步的平台
                $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
                $magento_platform = new \app\admin\model\platformmanage\MagentoPlatform();
                $platformArr = $platform->where(['sku' => $row['sku'], 'is_upload' => 2])->select();
                $error_num = [];
                $uploadItemArr = [];
                foreach ($platformArr as $k => $v) {
                    // $magentoArr = $magento_platform->where('id', '=', $v['platform_type'])->find();
                    //审核通过把SKU同步到有映射关系的平台
                    $uploadItemArr['skus']  = [$v['platform_sku']];
                    $uploadItemArr['site']  = $v['platform_type'];
                    $soap_res = Soap::createProduct($uploadItemArr);
                    if (!$soap_res) {
                        $error_num[] = $v['platform_type'];
                    } else {
                        $platform->where(['sku' => $row['sku'], 'platform_type' => $v['platform_type']])->update(['is_upload' => 1]);
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

            if ($error_num) {
                $this->error('站点同步失败:' . implode(',', $error_num));
            }

            $this->success('审核成功！！');
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
            $row = $this->model->where($map)->field('id,item_status,sku')->select();
            foreach ($row as $v) {
                if ($v['item_status'] != 2) {
                    $this->error('只有待审核状态才能操作！！');
                }
            }
            $data['item_status'] = 3;
            $data['check_time']  = date("Y-m-d H:i:s", time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                foreach ($row as $val) {
                    //查询同步的平台
                    $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
                    // $magento_platform = new \app\admin\model\platformmanage\MagentoPlatform();
                    $platformArr = $platform->where(['sku' => $val['sku'], 'is_upload' => 2])->select();
                    $uploadItemArr = [];
                    foreach ($platformArr as $k => $v) {
                        // $magentoArr = $magento_platform->where('id', '=', $v['platform_type'])->find();
                        //审核通过把SKU同步到有映射关系的平台
                      
                        $uploadItemArr['skus']  = [$v['platform_sku']];
                        $uploadItemArr['site']  = $v['platform_type'];
                        $soap_res = Soap::createProduct($uploadItemArr);
                        if ($soap_res) {
                            $platform->where(['sku' => $val['sku'], 'platform_type' => $v['platform_type']])->update(['is_upload' => 1]);
                        }
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
            $site = input('site');
            if ($site == 0){
                $this->error('请先选择平台！！');
            }
            $itemplatform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $info = $itemplatform->where(['sku'=>$sku,'platform_type'=>$site])->field('stock')->find();
            $res = $this->model->getGoodsInfo($sku);
            $res['platform_stock'] = $info['stock'];
            $res['now_stock'] = $res['stock'] - $res['distribution_occupy_stock'];
            // dump($res);die;
            if ($res) {
                if ($info) {
                    $this->success('', '', $res);
                } else {
                    $this->error('当前平台未同步sku！！');
                }
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
        }
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
     * @todo 弃用
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
            $whereData['item_status'] = 3;
            $whereData['is_open'] = ['LT', 3];
            $whereData['presell_create_time'] = ['NEQ', '0000-00-00 00:00:00'];
            $total = $this->model->where($whereData)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model->where($whereData)
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
     * @todo 弃用
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
                if ($params['presell_create_time'] == $params['presell_end_time']) {
                    $this->error('预售开始时间和结束时间不能相等');
                }
                $row = $this->model->pass_check_sku($params['sku']);
                if (!$row['sku']) {
                    $this->error('商品sku不存在,请重新尝试');
                }
                if ('0000-00-00 00:00:00' != $row['presell_create_time']) {
                    $log['sku'] = $row['sku'];
                    $log['presell_num'] = $row['presell_num'];
                    $log['presell_residue_num'] = $row['presell_residue_num'];
                    $log['virtual_presell_num'] = $row['presell_num'] - $row['presell_residue_num'];
                    $log['presell_create_time'] = $row['presell_create_time'];
                    $log['presell_end_time'] = $row['presell_end_time'];
                    (new Item_presell_log())->allowField(true)->save($log);
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
                    $now_time =  date("Y-m-d H:i:s", time());
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
     * @todo 弃用
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
     * @todo 弃用
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
     * @todo 弃用
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
    /***
     * 编辑预售
     * @todo 弃用
     */
    public function edit_presell($ids = null)
    {
        $row = $this->model->get($ids);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if (empty($params['sku'])) {
                    $this->error(__('Platform sku cannot be empty'));
                }
                if ($params['presell_start_time'] == $params['presell_end_time']) {
                    $this->error('预售开始时间和结束时间不能相等');
                }
                $row = $this->model->pass_check_sku($params['sku']);
                $params['presell_num'] = $row['presell_num'] + $params['presell_change_num'];
                $params['presell_residue_num'] = $row['presell_residue_num'] + $params['presell_change_num'];
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
                    $now_time =  date("Y-m-d H:i:s", time());
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
        } else {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
    }
    /***
     * 预售历史记录
     * @todo 弃用
     */
    public function presell_history($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('此SKU不存在,请重新尝试'));
        }
        $result = (new Item_presell_log())->getHistoryRecord($row['sku']);
        if ($result) {
            $this->view->assign('result', $result);
        }
        return $this->view->fetch();
    }


    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere .= " AND id IN ({$ids})";
        }
        list($where) = $this->buildparams();
        $list = $this->model->where('is_open', '<', 3)
            ->where($addWhere)
            ->where($where)
            ->select();
        //分类列表	
        $categoryArr = $this->category->getItemCategoryList();
        $list = collection($list)->toArray();
        if (!$list) {
            return false;
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "自增ID")
            ->setCellValue("B1", "商品名称")
            ->setCellValue("C1", "原始SKU")
            ->setCellValue("D1", "商品SKU")
            ->setCellValue("E1", "参考进价");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "商品分类")
            ->setCellValue("G1", "SKU状态")
            ->setCellValue("H1", "商品库存")
            ->setCellValue("I1", "SKU启用状态")
            ->setCellValue("J1", "是否新品");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "创建人")
            ->setCellValue("L1", "创建时间");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('商品SKU数据');

        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['id']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['name']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['origin_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['price']);
            if ($value['category_id']) {
                $value['category_name'] = $categoryArr[$v['category_id']];
                $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['category_name']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), '暂无分类');
            }
            switch ($value['item_status']) {
                case 1:
                    $value['item_status'] = '新建';
                    break;
                case 2:
                    $value['item_status'] = '待审核';
                    break;
                case 3:
                    $value['item_status'] = '审核通过';
                    break;
                case 4:
                    $value['item_status'] = '审核拒绝';
                    break;
                case 5:
                    $value['item_status'] = '取消';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['item_status']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['stock']);
            if (1 == $value['is_open']) {
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), '启用');
            } elseif (2 == $value['is_open']) {
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), '禁用');
            }
            if (1 == $value['is_new']) {
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), '是');
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), '不是');
            }
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['create_time']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(80);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:P' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '商品数据' . date("YmdHis", time());;
        if ($format == 'xls') {
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }
        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);
        $writer->save('php://output');
    }
}
