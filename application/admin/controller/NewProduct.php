<?php

namespace app\admin\controller;

use app\admin\model\NewProductMapping;
use app\admin\model\purchase\NewProductReplenishOrder;
use app\common\controller\Backend;
use function fast\array_except;
use think\Request;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\itemmanage\ItemBrand;
use app\admin\model\purchase\Supplier;
use app\admin\model\itemmanage\ItemPlatformSku;
use think\Loader;
use fast\Alibaba;
use app\admin\model\NewProductMappingLog;

/**
 * 商品管理
 *
 * @icon fa fa-circle-o
 */
class NewProduct extends Backend
{

    /**
     * NewProduct模型对象
     * @var \app\admin\model\NewProduct
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\NewProduct;
        $this->attribute = new \app\admin\model\NewProductAttribute;
        $this->itemAttribute = new \app\admin\model\itemmanage\attribute\ItemAttribute;
        $this->category = new \app\admin\model\itemmanage\ItemCategory;
        $this->view->assign('categoryList', $this->category->categoryList());
        $this->view->assign('brandList', (new ItemBrand())->getBrandList());
        $this->view->assign('AllFrameColor', $this->itemAttribute->getFrameColor());
        $this->item = new \app\admin\model\itemmanage\Item;
        $num = $this->item->getOriginSku();
        $idStr = sprintf("%06d", $num);
        $this->assign('IdStr', $idStr);
        $this->platformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->magentoplatformarr = $this->magentoplatform->column('name', 'id');
        $this->assign('platform_plat',$this->magentoplatform->field('id,name')->select());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }


            //如果切换站点清除默认值
            $filter = json_decode($this->request->get('filter'), true);
// dump($filter);die;

            //可用库存搜索
            if ($filter['available_stock']) {
                $item = new \app\admin\model\itemmanage\Item();
                $item_where['available_stock'] = ['between', explode(',', $filter['available_stock'])];
                $skus = $item->where($item_where)->where(['is_del' => 1, 'is_open' => 1])->column('sku');
                $map['sku'] = ['in', $skus];
                unset($filter['available_stock']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            //平台搜索 (单选)
            if ($filter['platform_type']) {
                $new_product_mapping = new \app\admin\model\NewProductMapping();
                $skus = $new_product_mapping->where(['website_type' => $filter['platform_type']])->column('sku');
                $map1['sku'] = ['in', $skus];
                unset($filter['platform_type']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            //平台搜索 改为多选2020.08.17
            // if ($filter['platform_plat']) {
            //     // $new_product_mapping = new \app\admin\model\NewProductMapping();
            //     $wheres['platform_type'] = ['in',$filter['platform_plat']];
            //     dump($wheres);
            //     $skus = $this->platformsku
            //         ->where($wheres)
            //         // ->field('sku,platform_type,id')
            //         // ->select();
            //         ->column('sku,platform_type');
            //     foreach ($skus as $k=>$v){
            //
            //     }
            //     dump($skus);die;
            //     $map1['sku'] = ['in', $skus];
            //     unset($filter['platform_plat']);
            //     $this->request->get(['filter' => json_encode($filter)]);
            // }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['supplier', 'newproductattribute'])
                ->where($where)
                ->where($map)
                ->where($map1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['supplier', 'newproductattribute'])
                ->where($where)
                ->where($map)
                ->where($map1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');
            //查询商品分类
            $category = $this->category->where('is_del', 1)->column('name', 'id');
            //查询90天总销量
            $productgrade = new \app\admin\model\ProductGrade();
            $productarr = $productgrade->where(['true_sku' => ['in', $skus]])->column('counter', 'true_sku');
            //查询可用库存
            $stock = $this->item->where(['sku' => ['in', $skus]])->column('available_stock', 'sku');

            //查询平台
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $platformarr = $platform->where(['sku' => ['in', $skus]])->select();
            $platformarr = collection($platformarr)->toArray();

            //查询对应平台
            $magentoplatformarr = $this->magentoplatformarr;

            $arr = [];
            foreach ($platformarr as $v) {
                $arr[$v['sku']] .= $magentoplatformarr[$v['platform_type']] . ',';
            }
            foreach ($list as &$v) {
                $v['category_name'] = $category[$v['category_id']];
                if ($v['item_status'] == 1) {
                    $v['item_status_text'] = '待选品';
                } elseif ($v['item_status'] == 2) {
                    $v['item_status_text'] = '选品通过';
                } elseif ($v['item_status'] == 3) {
                    $v['item_status_text'] = '选品拒绝';
                } elseif ($v['item_status'] == 4) {
                    $v['item_status_text'] = '已取消';
                } elseif ($v['item_status'] == 0) {
                    $v['item_status_text'] = '新建';
                }
                //90天总销量
                $v['sales_num'] = $productarr[$v['sku']] ?: 0;
                $v['available_stock'] = $stock[$v['sku']] ?: 0;
                $v['platform_type'] = trim($arr[$v['sku']], ',');
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }


    //新增商品
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            //            dump($params);die;
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                $supplierSku = $params['supplier_sku'];
                $price = $params['price'];
                $skuId = $params['skuid'];
                //区分是镜架还是配饰
                $item_type = $params['item_type'];
                $data = $itemAttribute = [];
                if (3 == $item_type) { //配饰

                    if (!$params['supplier_id']) {
                        $this->error('供应商不能为空');
                    }

                    if (!array_filter($itemName)) {
                        $this->error('商品名称不能为空！！');
                    }

                    //求出对应的sku编码规则
                    $resultEncode = $this->category->getCategoryTexture($params['category_id']);
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
                    if (!empty($params['origin_skus']) && $params['item_count'] >= 1) { //正常情况
                        $count = $params['item_count'];
                        $row = Db::connect('database.db_stock')->name('item')->where(['sku' => $params['origin_skus']])->field('id,sku')->find();
                        $attributeWhere = [];
                        $attributeWhere['item_id'] = $row['id'];
                        $attributeWhere['accessory_color'] = ['in', $itemColor];
                        $attributeInfo = Db::connect('database.db_stock')->name('item_attribute')->where($attributeWhere)->field('id,accessory_color')->find();
                        if ($attributeInfo) {
                            $this->error('追加的商品SKU不能添加之前的颜色');
                        }
                        $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                    } elseif (empty($params['origin_skus']) && $params['item_count'] >= 1) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item_count'] < 1) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }

                    if (!empty($params['origin_skus'])) {
                        $data['origin_sku'] = $params['origin_sku'];
                    } else {
                        $data['origin_sku'] = $textureEncode . $params['origin_sku'];
                    }

                    Db::startTrans();
                    try {
                        foreach ($itemName as $k => $v) {
                            $data['name'] = $v;
                            $data['category_id'] = $params['category_id'];
                            $data['item_status'] = $params['item_status'];
                            $data['brand_id'] = $params['brand_id'];
                            $data['price'] = $price[$k];
                            $data['supplier_id'] = $params['supplier_id'];
                            $data['supplier_sku'] = $supplierSku[$k];
                            $data['create_person'] = session('admin.nickname');
                            $data['create_time'] = date("Y-m-d H:i:s", time());
                            $data['link'] = $params['link'];
                            //后来添加的商品数据
                            if (!empty($params['origin_skus'])) {
                                $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                                ++$count;
                            } else {
                                $data['sku'] = $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                            }

                            $lastInsertId = Db::name('new_product')->insertGetId($data);

                            if ($lastInsertId !== false) {
                                $itemAttribute['item_id'] = $lastInsertId;
                                $itemAttribute['attribute_type'] = 3;
                                $itemAttribute['accessory_texture'] = $params['frame_texture'];
                                $itemAttribute['accessory_color'] = $itemColor[$k];
                                $itemAttribute['frame_remark'] = $params['frame_remark'];
                                $itemAttribute['frame_images'] = $params['frame_images'];

                                $res = Db::name('new_product_attribute')->insert($itemAttribute);
                                if (!$res) {
                                    throw new Exception('添加失败！！');
                                }
                                //绑定供应商SKU关系
                                $supplier_data['sku'] = $data['sku'];
                                $supplier_data['supplier_sku'] = $supplierSku[$k];
                                $supplier_data['supplier_id'] = $data['supplier_id'];
                                $supplier_data['createtime'] = date("Y-m-d H:i:s", time());
                                $supplier_data['create_person'] = session('admin.nickname');
                                $supplier_data['link'] = $data['link'];
                                $supplier_data['is_matching'] = 1;
                                $supplier_data['skuid'] = $skuId[$k];
                                Db::name('supplier_sku')->insert($supplier_data);
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
                } else {

                    //求出材质对应的编码
                    if ($params['frame_texture']) {
                        $textureEncode = $this->itemAttribute->getTextureEncode($params['frame_texture']);
                    } else {
                        $textureEncode = 'O';
                    }

                    //如果是后来添加的
                    if (!empty($params['origin_skus']) && $params['item_count'] >= 1) { //正常情况
                        $count = $params['item_count'];
                        $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                    } elseif (empty($params['origin_skus']) && $params['item_count'] >= 1) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item_count'] < 1) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }

                    if (!$params['supplier_id']) {
                        $this->error('供应商不能为空');
                    }

                    Db::startTrans();
                    try {
                        if (empty($itemName)){
                            throw new Exception('商品不能为空！！');
                        }
                        if (!array_filter($itemName)) {
                            throw new Exception('商品名称不能为空！！');
                        }


                        foreach (array_filter($itemName) as $k => $v) {
                            $data['name'] = $v;
                            $data['category_id'] = $params['category_id'];
                            $data['item_status'] = $params['item_status'];
                            $data['brand_id'] = $params['brand_id'];
                            $data['supplier_id'] = $params['supplier_id'];
                            $data['supplier_sku'] = $supplierSku[$k];
                            $data['link'] = $params['link'];
                            $data['frame_is_rimless'] = $params['shape'] == 1 ? 2 : 1;
                            $data['price'] = $price[$k];
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
                            $lastInsertId = Db::name('new_product')->insertGetId($data);
                            if ($lastInsertId !== false) {

                                //添加商品属性
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
                                $itemAttribute['frame_images'] = $params['frame_images'];
                                $res = Db::name('new_product_attribute')->insert($itemAttribute);
                                if (!$res) {
                                    throw new Exception('添加失败！！');
                                }
                                //绑定供应商SKU关系
                                $supplier_data['sku'] = $data['sku'];
                                $supplier_data['supplier_sku'] = $supplierSku[$k];
                                $supplier_data['skuid'] = $skuId[$k];
                                $supplier_data['supplier_id'] = $data['supplier_id'];
                                $supplier_data['createtime'] = date("Y-m-d H:i:s", time());
                                $supplier_data['create_person'] = session('admin.nickname');
                                $supplier_data['link'] = $data['link'];
                                $supplier_data['is_matching'] = 1;
                                Db::name('supplier_sku')->insert($supplier_data);
                            } else {
                                throw new Exception('添加失败！！');
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
                }

                if ($res !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    public function submit()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                $supplierSku = $params['supplier_sku'];
                $price = $params['price'];
                $skuId = $params['skuid'];
                //区分是镜架还是配饰
                $item_type = $params['item_type'];
                $data = $itemAttribute = [];
                if (3 == $item_type) { //配饰

                    if (!$params['supplier_id']) {
                        $this->error('供应商不能为空');
                    }

                    if (!array_filter($itemName)) {
                        $this->error('商品名称不能为空！！');
                    }

                    //求出对应的sku编码规则
                    $resultEncode = $this->category->getCategoryTexture($params['category_id']);
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
                    if (!empty($params['origin_skus']) && $params['item_count'] >= 1) { //正常情况
                        $count = $params['item_count'];
                        $row = Db::connect('database.db_stock')->name('item')->where(['sku' => $params['origin_skus']])->field('id,sku')->find();
                        $attributeWhere = [];
                        $attributeWhere['item_id'] = $row['id'];
                        $attributeWhere['accessory_color'] = ['in', $itemColor];
                        $attributeInfo = Db::connect('database.db_stock')->name('item_attribute')->where($attributeWhere)->field('id,accessory_color')->find();
                        if ($attributeInfo) {
                            $this->error('追加的商品SKU不能添加之前的颜色');
                        }
                        $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                    } elseif (empty($params['origin_skus']) && $params['item_count'] >= 1) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item_count'] < 1) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }

                    if (!empty($params['origin_skus'])) {
                        $data['origin_sku'] = $params['origin_sku'];
                    } else {
                        $data['origin_sku'] = $textureEncode . $params['origin_sku'];
                    }

                    Db::startTrans();
                    try {
                        foreach ($itemName as $k => $v) {
                            $data['name'] = $v;
                            $data['category_id'] = $params['category_id'];
                            $data['item_status'] = $params['item_status'];
                            $data['brand_id'] = $params['brand_id'];
                            $data['price'] = $price[$k];
                            $data['supplier_id'] = $params['supplier_id'];
                            $data['supplier_sku'] = $supplierSku[$k];
                            $data['create_person'] = session('admin.nickname');
                            $data['create_time'] = date("Y-m-d H:i:s", time());
                            $data['link'] = $params['link'];
                            //后来添加的商品数据
                            if (!empty($params['origin_skus'])) {
                                $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                                ++$count;
                            } else {
                                $data['sku'] = $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                            }

                            $lastInsertId = Db::name('new_product')->insertGetId($data);

                            if ($lastInsertId !== false) {
                                $itemAttribute['item_id'] = $lastInsertId;
                                $itemAttribute['attribute_type'] = 3;
                                $itemAttribute['accessory_texture'] = $params['frame_texture'];
                                $itemAttribute['accessory_color'] = $itemColor[$k];
                                $itemAttribute['frame_remark'] = $params['frame_remark'];
                                $itemAttribute['frame_images'] = $params['frame_images'];

                                $res = Db::name('new_product_attribute')->insert($itemAttribute);
                                if (!$res) {
                                    throw new Exception('添加失败！！');
                                }
                                //绑定供应商SKU关系
                                $supplier_data['sku'] = $data['sku'];
                                $supplier_data['supplier_sku'] = $supplierSku[$k];
                                $supplier_data['supplier_id'] = $data['supplier_id'];
                                $supplier_data['createtime'] = date("Y-m-d H:i:s", time());
                                $supplier_data['create_person'] = session('admin.nickname');
                                $supplier_data['link'] = $data['link'];
                                $supplier_data['is_matching'] = 1;
                                $supplier_data['skuid'] = $skuId[$k];
                                Db::name('supplier_sku')->insert($supplier_data);
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
                } else {

                    //求出材质对应的编码
                    if ($params['frame_texture']) {
                        $textureEncode = $this->itemAttribute->getTextureEncode($params['frame_texture']);
                    } else {
                        $textureEncode = 'O';
                    }

                    //如果是后来添加的
                    if (!empty($params['origin_skus']) && $params['item_count'] >= 1) { //正常情况
                        $count = $params['item_count'];
                        $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                    } elseif (empty($params['origin_skus']) && $params['item_count'] >= 1) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item_count'] < 1) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }

                    if (!$params['supplier_id']) {
                        $this->error('供应商不能为空');
                    }

                    Db::startTrans();
                    try {

                        if (!array_filter($itemName)) {
                            throw new Exception('商品名称不能为空！！');
                        }


                        foreach (array_filter($itemName) as $k => $v) {
                            $data['name'] = $v;
                            $data['category_id'] = $params['category_id'];
                            $data['item_status'] = $params['item_status'];
                            $data['brand_id'] = $params['brand_id'];
                            $data['supplier_id'] = $params['supplier_id'];
                            $data['supplier_sku'] = $supplierSku[$k];
                            $data['link'] = $params['link'];
                            $data['frame_is_rimless'] = $params['shape'] == 1 ? 2 : 1;
                            $data['price'] = $price[$k];
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
                            $lastInsertId = Db::name('new_product')->insertGetId($data);
                            if ($lastInsertId !== false) {

                                //添加商品属性
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
                                $itemAttribute['frame_images'] = $params['frame_images'];
                                $res = Db::name('new_product_attribute')->insert($itemAttribute);
                                if (!$res) {
                                    throw new Exception('添加失败！！');
                                }
                                //绑定供应商SKU关系
                                $supplier_data['sku'] = $data['sku'];
                                $supplier_data['supplier_sku'] = $supplierSku[$k];
                                $supplier_data['skuid'] = $skuId[$k];
                                $supplier_data['supplier_id'] = $data['supplier_id'];
                                $supplier_data['createtime'] = date("Y-m-d H:i:s", time());
                                $supplier_data['create_person'] = session('admin.nickname');
                                $supplier_data['link'] = $data['link'];
                                $supplier_data['is_matching'] = 1;
                                Db::name('supplier_sku')->insert($supplier_data);
                            } else {
                                throw new Exception('添加失败！！');
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
                }

                if ($res !== false) {
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
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids, 'newproductattribute');
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($row['item_status'] == 2) {
            $this->error(__('The goods have been submitted for review and cannot be edited'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
//                        dump($params);die;
            //编辑保存的选品状态改为待选品
//            $params['item_status'] = 1;
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

                    //属性表
                    $this->attribute->allowField(true)->save($params, ['item_id' => $ids]);
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
            $allFrameSize = $this->itemAttribute->getFrameSize();
            //获取所有眼镜类型
            $allGlassesType = $this->itemAttribute->getGlassesType();
            //获取所有采购产地
            $allOrigin = $this->itemAttribute->getOrigin();
            //获取配镜类型
            $allFrameType = $this->itemAttribute->getFrameType();

            $this->assign('AllFrameType', $allFrameType);
            $this->assign('AllOrigin', $allOrigin);
            $this->assign('AllGlassesType', $allGlassesType);
            $this->assign('AllFrameSize', $allFrameSize);
            $this->assign('AllFrameGender', $allFrameGender);
            $this->assign('AllFrameShape', $allFrameShape);
            $this->assign('AllShape', $allShape);
            $this->assign('AllTexture', $allTexture);
        }
        //获取供应商
        $allSupplier = (new Supplier())->getSupplierData();
        $this->assign('AllSupplier', $allSupplier);
        $this->view->assign('template', $this->category->getAttrCategoryById($row['category_id']));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids, 'newproductattribute');

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
            $allFrameSize = $this->itemAttribute->getFrameSize();
            //获取所有眼镜类型
            $allGlassesType = $this->itemAttribute->getGlassesType();
            //获取所有采购产地
            $allOrigin = $this->itemAttribute->getOrigin();
            //获取配镜类型
            $allFrameType = $this->itemAttribute->getFrameType();

            $this->assign('AllFrameType', $allFrameType);
            $this->assign('AllOrigin', $allOrigin);
            $this->assign('AllGlassesType', $allGlassesType);
            $this->assign('AllFrameSize', $allFrameSize);
            $this->assign('AllFrameGender', $allFrameGender);
            $this->assign('AllFrameShape', $allFrameShape);
            $this->assign('AllShape', $allShape);
            $this->assign('AllTexture', $allTexture);
        }
        //获取供应商
        $allSupplier = (new Supplier())->getSupplierData();
        $this->assign('AllSupplier', $allSupplier);
        $this->view->assign('template', $this->category->getAttrCategoryById($row['category_id']));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

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
                return $this->error('对应分类不存在,请重新尝试');
            } elseif ($result == -1) {
                return $this->error('对应分类存在下级分类,请重新选择');
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
                $allFrameSize = $this->itemAttribute->getFrameSize();
                //获取所有眼镜类型
                $allGlassesType = $this->itemAttribute->getGlassesType();
                //获取所有采购产地
                $allOrigin = $this->itemAttribute->getOrigin();
                //获取配镜类型
                $allFrameType = $this->itemAttribute->getFrameType();
                //获取供应商
                $allSupplier = (new Supplier())->getSupplierData();

                $this->assign('AllFrameType', $allFrameType);
                $this->assign('AllOrigin', $allOrigin);
                $this->assign('AllGlassesType', $allGlassesType);
                $this->assign('AllFrameSize', $allFrameSize);
                $this->assign('AllFrameGender', $allFrameGender);
                $this->assign('AllFrameShape', $allFrameShape);
                $this->assign('AllShape', $allShape);
                $this->assign('AllTexture', $allTexture);
                $this->assign('AllSupplier', $allSupplier);
                //把选择的模板值传递给模板
                $this->assign('Result', $result);
                $data = $this->fetch('frame');
            } elseif ($result == 2) { //商品是镜片类型
                $data = $this->fetch('eyeglass');
            } elseif ($result >= 3) { //商品是饰品类型
                $info = $this->category->getCategoryTexture($categoryId);
                $this->assign('AllTexture', $info['textureResult']);
                $this->assign('AllFrameColor', $info['colorResult']);
                //获取供应商
                $allSupplier = (new Supplier())->getSupplierData();
                $this->assign('AllSupplier', $allSupplier);
                $data = $this->fetch('decoration');
            } else {
                $data = $this->fetch('attribute');
            }
            return $this->success('ok', '', $data);
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
                $this->error('商品SKU不存在，请重新尝试');
            }
            $this->success('', '', $result, 0);
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
            $sku = $this->request->post('sku');
            if (!$categoryId || !$sku) {
                $this->error('参数错误，请重新尝试');
            }
            $result = $this->category->getAttrCategoryById($categoryId);
            if (!$result) {
                $this->error('对应分类不存在,请从新尝试');
            } elseif ($result == -1) {
                $this->error('对应分类存在下级分类,请从新选择');
            }
            $allSupplier = (new Supplier())->getSupplierData();
            if ($result == 1) {
                $row = $this->model->getItemInfo($sku, 1);
                $this->assign('row', $row);
                $this->assign('AllSupplier', $allSupplier);

                //获取所有框型
                $allShape = $this->itemAttribute->getAllShape();
                //获取所有材质
                $allTexture = $this->itemAttribute->getAllTexture();
                //获取所有镜架形状
                $allFrameShape = $this->itemAttribute->getAllFrameShape();
                //获取所有适合性别
                $allFrameGender = $this->itemAttribute->getFrameGender();
                //获取所有型号
                $allFrameSize = $this->itemAttribute->getFrameSize();
                //获取所有眼镜类型
                $allGlassesType = $this->itemAttribute->getGlassesType();
                //获取所有采购产地
                $allOrigin = $this->itemAttribute->getOrigin();
                //获取配镜类型
                $allFrameType = $this->itemAttribute->getFrameType();
                //获取供应商
                $allSupplier = (new Supplier())->getSupplierData();

                $this->assign('AllFrameType', $allFrameType);
                $this->assign('AllOrigin', $allOrigin);
                $this->assign('AllGlassesType', $allGlassesType);
                $this->assign('AllFrameSize', $allFrameSize);
                $this->assign('AllFrameGender', $allFrameGender);
                $this->assign('AllFrameShape', $allFrameShape);
                $this->assign('AllShape', $allShape);
                $this->assign('AllTexture', $allTexture);
                $this->assign('AllSupplier', $allSupplier);
                $data = $this->fetch('frame');
            } elseif ($result == 2) { //商品是镜片类型
                $data = $this->fetch('eyeglass');
            } elseif ($result >= 3) { //商品是饰品类型
                $row = $this->model->getItemInfo($sku, 3);
                $result = $this->category->getCategoryTexture($categoryId);
                $this->assign('AllTexture', $result['textureResult'] ?? []);
                $this->assign('AllFrameColor', $result['colorResult'] ?? []);
                $this->assign('row', $row ?? []);
                $this->assign('AllSupplier', $allSupplier);
                $data = $this->fetch('decoration');
            } else {
                $data = $this->fetch('attribute');
            }
            $this->success('ok', '', $data);
        } else {
            $this->error(__('404 Not Found'));
        }
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
            $this->error('请求出错！！');
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
     * 审核通过
     */
    public function passAudit()
    {
        if ($this->request->isAjax()) {
            $ids = input('id');
            $site = input('site');
            //查询所选择的数据
            $where['new_product.id'] = $ids;
            $row = $this->model->where($where)->with(['newproductattribute'])->find();
            if (!$row) {
                $this->error('未查询到数据');
            }

            $row = $row->toArray();
            if ($row['item_status'] != 1 && $row['item_status'] != 2) {
                $this->error('此状态不能同步');
            }

            $map['id'] = $ids;
            $map['item_status'] = 1;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $params = $row;
                $params['create_person'] = session('admin.nickname');
                $params['create_time'] = date('Y-m-d H:i:s', time());
                $params['item_status'] = 1;
                unset($params['id']);
                unset($params['newproductattribute']);
                //查询商品表SKU是否存在
                $t_where['sku'] = $params['sku'];
                $t_where['is_del'] = 1;
                $count = $this->item->where($t_where)->count();
                //此SKU已存在 跳过
                if ($count < 1) {
                    //添加商品主表信息
                    $this->item->allowField(true)->save($params);
                    $attributeParams = $row['newproductattribute'];
                    unset($attributeParams['id']);
                    unset($attributeParams['frame_images']);
                    unset($attributeParams['frame_color']);
                    $attributeParams['item_id'] = $this->item->id;
                    //添加商品属性表信息
                    $this->itemAttribute->allowField(true)->save($attributeParams);
                }

                //添加对应平台映射关系
                $skuParams['site'] = $site;
                $skuParams['sku'] = $params['sku'];
                $skuParams['frame_is_rimless'] = $row['frame_is_rimless'];
                $skuParams['name'] = $row['name'];
                $skuParams['category_id'] = $row['category_id'];
                (new \app\admin\model\itemmanage\ItemPlatformSku())->addPlatformSku($skuParams);

                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        }

        //查询对应平台
        $magentoplatformarr = $this->magentoplatformarr;
        $magentoplatformarr = array_column($this->magentoplatform->getAuthSite(), 'name','id');

        $this->assign('platformarr', $magentoplatformarr);
        return $this->fetch('check');
    }

    /***
     * 多个一起审核拒绝
     */
    public function auditRefused($ids = null)
    {

        $ids = input('idd');
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            //查询所选择的数据
            $where['new_product.id'] = ['in', $ids];
            $row = $this->model->where($where)->with(['newproductattribute'])->select();
            $row = collection($row)->toArray();
            //            dump($row);die;
            foreach ($row as $k => $v) {
                if ($v['item_status'] != 1) {
                    $this->error('此状态不能审核！！');
                }
            }


            $map['item_status'] = 1;
            $data['item_status'] = 3;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('加载错误！！');
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
            $data['item_status'] = 4;
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


    /**
     * 采集1688商品信息
     */
    public function ajaxCollectionGoodsDetail()
    {
        $row['link'] = input('link');
        if (!$row['link']) {
            $this->error('商品链接不能为空！！');
        }
        //获取缓存名称
        $controllername = Loader::parseName($this->request->controller());
        $actionname = strtolower($this->request->action());
        $path = str_replace('.', '1', $controllername) . '_' . $actionname . '_' . md5($row['link']);
        //是否存在缓存
        $result = session($path);
        if (!$result) {
            //截取出商品id
            $name = parse_url($row['link']);
            preg_match('/\d+/', $name['path'], $goodsId);
            //先添加到铺货列表
            Alibaba::getGoodsPush([$goodsId[0]]);
            //获取商品详情
            $result = Alibaba::getGoodsDetail($goodsId[0]);
            session($path, $result);
        }

        $list = [];
        foreach ($result->productInfo->skuInfos as $k => $v) {
            $list[$k]['id'] = $k + 1;
            $list[$k]['title'] = $result->productInfo->subject;
            if (count($v->attributes) > 1) {
                $list[$k]['color'] = $v->attributes[0]->attributeValue . ':' . $v->attributes[1]->attributeValue;
            } else {
                $list[$k]['color'] = $v->attributes[0]->attributeValue;
            }
            $list[$k]['cargoNumber'] = $v->cargoNumber;
            $list[$k]['price'] = @$v->price ? @$v->price : @$v->consignPrice;
            $list[$k]['skuId'] = $v->skuId;
        }

        $categoryId = input('categoryId');
        $info = $this->category->getCategoryTexture($categoryId);
        if ($list) {
            $data['list'] = $list;
            $data['colorResult'] = $info['colorResult'];
            $this->success('采集成功！！', '', $data);
        } else {
            $this->error('未采集到数据！！', '', $list);
        }
    }

    /**
     * 需求提报列表
     *
     * @Description
     * @author wpl
     * @since 2020/07/13 13:56:00 
     * @return void
     */
    public function replenishEscalationList()
    {
        //        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\itemmanage\ItemPlatformSku();
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //默认站点
            $platform_type = input('label');
            if ($platform_type) {
                $map['platform_type'] = $platform_type;
            }
            //如果切换站点清除默认值
            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['platform_type']) {
                unset($map['platform_type']);
            }

            $this->request->get(['filter' => json_encode($filter)]);
            $params = $this->request->get();
            if ($filter['sku']) {
                $where['a.sku'] = ['like', '%' . $filter['sku'] . '%'];
            }
            if ($filter['category_id']) {
                $where['a.category_id'] = ['=', $filter['category_id']];
            }
            if ($filter['available_stock']) {
                $where['b.available_stock'] = ['between', explode(',', $filter['available_stock'])];
            }
            if ($filter['platform_type']) {
                $where['a.platform_type'] = ['=', $filter['platform_type']];
            }
            //            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('a')
                ->field('a.*,b.sku,b.available_stock')
                ->join(['fa_item' => 'b'], 'a.sku=b.sku')
                ->where($where)
                ->where($map)
                ->order('a.id', 'desc')
                ->count();

            $list = $this->model
                ->alias('a')
                ->field('a.*,b.sku,b.available_stock')
                ->join(['fa_item' => 'b'], 'a.sku=b.sku')
                ->where($where)
                ->where($map)
                ->order('a.id', 'desc')
                ->limit($params['offset'], $params['limit'])
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');
            //查询商品分类
            $category = $this->category->where('is_del', 1)->column('name', 'id');

            //查询生产周期
            $suppliersku = new \app\admin\model\purchase\SupplierSku();
            $product_cycle_arr = $suppliersku->where(['label' => 1, 'status' => 1, 'sku' => ['in', $skus]])->column('product_cycle', 'sku');
            //查询可用库存
            $stock = $this->item->where(['sku' => ['in', $skus]])->column('available_stock,on_way_stock', 'sku');
            //查询待入库数量
            $purchase = new \app\admin\model\purchase\PurchaseOrder();
            $wait_in_arr = $purchase->getWaitInStockNum($skus);
            foreach ($list as &$v) {
                $v['category_name'] = $category[$v['category_id']];
                $v['available_stock'] = $stock[$v['sku']]['available_stock'] ?: 0;
                $v['on_way_stock'] = $stock[$v['sku']]['on_way_stock'] ?: 0;
                $v['product_cycle'] = $product_cycle_arr[$v['sku']] ?: 7;
                $v['wait_in_num'] = $wait_in_arr[$v['sku']] ?: 0;
                $v['sales_days'] = $v['sales_num_90days'] > 0 ? round($v['stock'] / $v['sales_num_90days']) : 0;
                $num = $v['stock'] - ($v['sales_num_90days'] * 30);
                $v['replenish_num'] =  $num > 0 ? $num : 0;
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        //取第一个key为默认站点
        $site = input('site', $magentoplatformarr[0]['id']);

        $this->assignconfig('label', $site);
        $this->assign('site', $site);
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 加入补货清单
     *
     * @Description
     * @author wpl
     * @since 2020/07/14 10:31:27 
     * @return void
     */
    public function addReplenishOrder($ids = null)
    {
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $row = $platform->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            $mapping = new \app\admin\model\NewProductMapping();
            //判断如果有此SKU 则累加补货数量 否则添加
            $count = $mapping->where(['website_type' => $params['website_type'], 'sku' => $params['sku'], 'type' => $params['type'], 'is_show' => 1])->count();
            $params['create_time'] = date('Y-m-d H:i:s');
            $params['create_person'] = session('admin.nickname');
            if ($count > 0) {
                $result = $mapping->where(['website_type' => $params['website_type'], 'sku' => $params['sku'], 'type' => $params['type']])->setInc('replenish_num', $params['replenish_num']);
            } else {
                $result = $mapping->allowField(true)->save($params);
            }
            if ($result !== false) {
                //记录日志
                (new NewProductMappingLog)->addLog($params);
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }

        //查询对应平台
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        $this->assign('platformarr', $magentoplatformarr);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 补货需求清单
     *
     * @Description
     * @author wpl
     * @since 2020/07/13 13:56:00 
     * @return void
     */
    public function productMappingList()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\NewProductMapping();
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //默认站点
            $platform_type = input('label');
            if ($platform_type) {
                $map['website_type'] = $platform_type;
            }
            //如果切换站点清除默认值
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['website_type']) {
                unset($map['website_type']);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('is_show', 1)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('is_show', 1)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $skus = array_column($list, 'sku');
            //查询商品分类
            $category = $this->category->where('is_del', 1)->column('name', 'id');
            //查询90天总销量
            $productgrade = new \app\admin\model\ProductGrade();
            $productarr = $productgrade->where(['true_sku' => ['in', $skus]])->column('counter,grade', 'true_sku');
            //查询可用库存
            $stock = $this->item->where(['sku' => ['in', $skus]])->column('available_stock,on_way_stock', 'sku');

            foreach ($list as &$v) {
                $v['category_name'] = $category[$v['category_id']];
                //90天总销量
                $v['sales_num'] = $productarr[$v['sku']]['counter'] ?: 0;
                $v['grade'] = $productarr[$v['sku']]['grade'];
                $v['available_stock'] = $stock[$v['sku']]['available_stock'] ?: 0;
                $v['on_way_stock'] = $stock[$v['sku']]['on_way_stock'] ?: 0;
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        //取第一个key为默认站点
        $site = input('site', $magentoplatformarr[0]['id']);

        $this->assignconfig('label', $site);
        $this->assign('site', $site);
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 编辑补货需求数量
     *
     * @Description
     * @author wpl
     * @since 2020/07/15 13:42:19 
     * @return void
     */
    public function mappingEdit()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $ids = input('ids');
        $row = $this->model->get($ids);
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
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->item));
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
                    $this->success('操作成功！！');
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    /**
     * 计划任务 计划补货 每月7号执行一次 汇总各个平台原始sku相同的品的补货需求数量 加入补货需求单以供采购分配处理 汇总过后更新字段 is_show 的值 列表不显示
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/16
     * Time: 15:46
     */
    public function plan_replenishment()
    {
        //补货需求清单表
        $this->model = new \app\admin\model\NewProductMapping();
        //补货需求单子表
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //补货需求单主表
        $this->replenish = new \app\admin\model\purchase\NewProductReplenish();
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");
        if (empty($list)) {
            echo ('暂时没有紧急补货单需要处理');
            die;
        }
        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $sku_list = $this->model
            ->where(['is_show' => 1, 'type' => 1])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $sku_list = $this->array_group_by($sku_list, 'sku');

        $result = false;
        Db::startTrans();
        try {
            //首先插入主表 获取主表id new_product_replenish
            $data['type'] = 1;
            $data['create_person'] = 'Admin';
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = $this->replenish->insertGetId($data);

            //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
            $int = 0;
            foreach ($sku_list as $k => $v) {
                //求出此sku在此补货单中的总数量
                $sku_whole_num = array_sum(array_map(function ($val) {
                    return $val['replenish_num'];
                }, $v));
                //求出比例赋予新数组
                foreach ($v as $ko => $vo) {
                    $date[$int]['id'] = $vo['id'];
                    $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
                    $date[$int]['replenish_id'] = $res;
                    $int += 1;
                }
            }
            //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
            $res1 = $this->model->allowField(true)->saveAll($date);

            $number = 0;
            foreach ($list as $k => $v) {
                $arr[$number]['sku'] = $k;
                $arr[$number]['replenishment_num'] = $v;
                $arr[$number]['create_person'] = 'Admin';
                $arr[$number]['create_time'] = date('Y-m-d H:i:s');
                $arr[$number]['type'] = 1;
                $arr[$number]['replenish_id'] = $res;
                $number += 1;
            }
            //插入补货需求单子表 关联主表 new_product_replenish_order 关联字段replenish_id
            $result = $this->order->allowField(true)->saveAll($arr);
            //更新计划补货列表
            $ids = $this->model
                ->where(['is_show' => 1, 'type' => 1])
                ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
                ->setField('is_show', 0);
            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            echo $e->getMessage();
        } catch (PDOException $e) {
            Db::rollback();
            echo $e->getMessage();
        } catch (Exception $e) {
            Db::rollback();
            echo $e->getMessage();
        }
    }

    /**
     * *@param  [type] $arr [二维数组]
     * @param  [type] $key [键名]
     * @return [type]      [新的二维数组]
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/22
     * Time: 11:37
     */
    function array_group_by($arr, $key)
    {
        $grouped = array();
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge($value, array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }
        return $grouped;
    }


    /**
     * 紧急补货
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/17
     * Time: 9:22
     */
    public function emergency_replenishment()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
        //统计计划补货数据
        $list = $this->model
            ->where(['is_show' => 1, 'type' => 2])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->group('sku')
            ->column("sku,sum(replenish_num) as sum");

        if (empty($list)) {
            $this->error('暂时没有紧急补货单需要处理');
        }

        //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
        $sku_list = $this->model
            ->where(['is_show' => 1, 'type' => 2])
            ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
            ->field('id,sku,website_type,replenish_num')
            ->select();
        //根据sku对数组进行重新分配
        $sku_list = $this->array_group_by($sku_list, 'sku');

        $result = false;
        Db::startTrans();
        try {
            //首先插入主表 获取主表id new_product_replenish
            $data['type'] = 2;
            $data['create_person'] = session('admin.nickname');
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = Db::name('new_product_replenish')->insertGetId($data);

            //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
            $int = 0;
            foreach ($sku_list as $k => $v) {
                //求出此sku在此补货单中的总数量
                $sku_whole_num = array_sum(array_map(function ($val) {
                    return $val['replenish_num'];
                }, $v));
                //求出比例赋予新数组
                foreach ($v as $ko => $vo) {
                    $date[$int]['id'] = $vo['id'];
                    $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
                    $date[$int]['replenish_id'] = $res;
                    $int += 1;
                }
            }
            //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
            $res1 = $this->model->allowField(true)->saveAll($date);

            $number = 0;
            foreach ($list as $k => $v) {
                $arr[$number]['sku'] = $k;
                $arr[$number]['replenishment_num'] = $v;
                $arr[$number]['create_person'] = session('admin.nickname');
                $arr[$number]['create_time'] = date('Y-m-d H:i:s');
                $arr[$number]['type'] = 2;
                $arr[$number]['replenish_id'] = $res;
                $number += 1;
            }
            //插入补货需求单表
            $result = $this->order->allowField(true)->saveAll($arr);
            //更新计划补货列表
            $ids = $this->model
                ->where(['is_show' => 1, 'type' => 2])
                ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
                ->setField('is_show', 0);
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
            $this->success('操作成功！！');
        } else {
            $this->error(__('No rows were updated'));
        }
    }
}
