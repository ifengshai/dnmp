<?php

namespace app\admin\controller;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Log;
use think\Request;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\itemmanage\ItemBrand;
use app\admin\model\purchase\Supplier;
use think\Loader;
use fast\Alibaba;
use app\admin\model\NewProductMappingLog;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['batch_export_xls'];

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
        $this->view->assign('AllProductSize', config('FRAME_SIZE'));
        $this->item = new \app\admin\model\itemmanage\Item;
        $num = $this->item->getOriginSku();
        $idStr = sprintf("%06d", $num);
        $this->assign('IdStr', $idStr);
        $this->platformsku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->magentoplatformarr = $this->magentoplatform->column('name', 'id');
        $this->assign('platform_plat', $this->magentoplatform->field('id,name')->select());
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
            if (!empty($filter['sku'])) {
                if (preg_match("/\s/", $filter['sku'])) {
                    $map['sku'] = ['in', preg_split("/\s+/", $filter['sku'])];
                    unset($filter['sku']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
            }
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
                if ($filter['platform_type'] == 10) {
                    $map['item_status'] = ['=', 1];
                } else {
                    $skus = $this->platformsku->where(['platform_type' => $filter['platform_type']])->column('sku');
                    $map1['sku'] = ['in', $skus];
                }
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

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();

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
            //查询可用库存
            $stock = $this->item->where(['sku' => ['in', $skus], 'is_del' => 1, 'is_open' => 1])->column('available_stock', 'sku');

            //查询平台
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $platformarr = $platform->where(['sku' => ['in', $skus]])->select();
            $platformarr = collection($platformarr)->toArray();
            $arrs = [];
            foreach ($platformarr as $ka => $va) {
                if ($arrs[$va['sku']]) {
                    $arrs[$va['sku']] = $arrs[$va['sku']] + $va['sales_num_90days'];
                } else {
                    $arrs[$va['sku']] = $va['sales_num_90days'];
                }
            }

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
                $v['sales_num_90days'] = $arrs[$v['sku']] ?: 0;
                $v['available_stock'] = $stock[$v['sku']] ?: 0;
                $v['platform_type'] = trim($arr[$v['sku']], ',');
            }
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 新增商品
     *
     * @Description
     * @author wpl
     * @since 2020/08/20 10:58:37 
     * @return void
     */
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
                $frame_size = $params['frame_size'] ?: [];
                //区分是镜架还是配饰
                $item_type = $params['item_type'];
                if ($params['goods_supply'] < 1){
                    $this->error('请选择货源');
                }
                $data = $itemAttribute = [];
                if (3 == $item_type) { //配饰

                    if (count(array_filter($frame_size)) != count(array_unique(array_filter($frame_size)))) {
                        $this->error('尺寸不能重复');
                    }

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
                    if (!empty($params['origin_skus']) && $params['item_count'] >= 1 && $params['category_id'] != 53) { //正常情况
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
                    } elseif (empty($params['origin_skus']) && $params['item_count'] >= 1 && $params['category_id'] != 53) { //去掉原始sku情况
                        $this->error(__('Make sure the original sku code exists'));
                    } elseif (!empty($params['origin_skus']) && $params['item_count'] < 1 && $params['category_id'] != 53) { //原始sku失败情况
                        $this->error(__('Make sure the original sku code is the correct sku code'));
                    }

                    if (!empty($params['origin_skus'])) {
                        $data['origin_sku'] = $params['origin_sku'];
                    } else {
                        $data['origin_sku'] = $textureEncode . $params['origin_sku'];
                    }
                    Db::startTrans();
                    try {

                        foreach (array_filter($itemName) as $k => $v) {
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
                            $data['goods_supply'] = $params['goods_supply'];
                            //后来添加的商品数据
                            if (!empty($params['origin_skus']) && $params['category_id'] != 53) {
                                $data['sku'] = $params['origin_sku'] . '-' . sprintf("%02d", $count + 1);
                                ++$count;
                            } elseif ($params['category_id'] == 53) {
                                //如果存在原始SKU
                                if ($params['origin_skus']) {
                                    $params['origin_sku'] = substr($params['origin_skus'], 0, strpos($params['origin_skus'], '-'));
                                    $textureEncode = '';
                                }

                                if ($frame_size[$k] == 0) {
                                    $data['sku'] = $textureEncode . $params['origin_sku'] . '-66';
                                } else {
                                    if ($frame_size[$k] < 10) {
                                        $data['sku'] = $textureEncode . $params['origin_sku'] . '-0' . $frame_size[$k];
                                    } else {
                                        $data['sku'] = $textureEncode . $params['origin_sku'] . '-' . $frame_size[$k];
                                    }
                                }

                                $count = Db::name('new_product')->where(['sku' => $data['sku']])->count();
                                if ($count > 0) {
                                    throw new  Exception('此SKU尺寸已存在');
                                }
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
                                $itemAttribute['frame_size'] = $frame_size[$k] ?: 0;

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
                        if (empty($itemName)) {
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
                            $data['goods_supply'] = $params['goods_supply'];
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
                                //如果是镜盒
                                if ($params['category_id'] == 32) {
                                    $data['sku'] = 'ACC' . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                                } else {
                                    $data['origin_sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'];
                                    $data['sku'] = $params['procurement_origin'] . $textureEncode . $params['origin_sku'] . '-' . sprintf("%02d", $k + 1);
                                }
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
                $this->assign('categoryId', $categoryId);
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
                $this->assign('categoryId', $categoryId);
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
            $itemPlatformSku = new ItemPlatformSku();
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
            $isExist = $itemPlatformSku->where('platform_type', $site)->where('sku', $row['sku'])->find();
            if ($isExist['id'] > 0) {
                $this->error('此sku在相应站点已有映射关系' . $isExist['platform_sku'] . '，请检查');
            }
            $map['id'] = $ids;
            $map['item_status'] = 1;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                $params = $row;
                if ($params['goods_supply'] == 1 || $params['goods_supply'] == 2){
                    $params['is_spot'] = 1;
                }else{
                    $params['is_spot'] = 2;
                }
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

                $result = $itemPlatformSku->addPlatformSku($skuParams);
                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        }

        //查询对应平台
        $magentoplatformarr = $this->magentoplatformarr;
        $magentoplatformarr = array_column($this->magentoplatform->getAuthSite(), 'name', 'id');

        $this->assign('platformarr', $magentoplatformarr);

        return $this->fetch('check');
    }

    /**
     * 批量审核通过
     */

    public function passaudits()
    {
        $ids = input('param.ids');

        if ($this->request->isAjax()) {

            $site = input('site');
            //查询所选择的数据
            $where['new_product.id'] = ['in', $ids];
            $row = $this->model->where($where)->with(['newproductattribute'])->select();

            if (!$row) {
                $this->error('未查询到数据');
            }
            $row = collection($row)->toArray();

            $test = [];
            foreach ($row as $key => $item) {
                if ($item['item_status'] != 1 && $item['item_status'] != 2) {
                    $this->error($item['sku'] . '数据状态不能同步');
                }
                $test[$key] = $item['id'];

                $map['id'] = $item['id'];
                $map['item_status'] = 1;
                $data['item_status'] = 2;
                $res = $this->model->where($map)->update($data);
                if ($res !== false) {
                    $params = $item;
                    if ($params['goods_supply'] == 1 || $params['goods_supply'] == 2){
                        $params['is_spot'] = 1;
                    }else{
                        $params['is_spot'] = 2;
                    }
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
                        $this->item->allowField(true)->isUpdate(false)->data($params)->save();
                        $attributeParams = $item['newproductattribute'];
                        unset($attributeParams['id']);
                        unset($attributeParams['frame_images']);
                        unset($attributeParams['frame_color']);
                        $attributeParams['item_id'] = $this->item->id;
                        //添加商品属性表信息
                        $this->itemAttribute->allowField(true)->isUpdate(false)->data($attributeParams)->save();
                    }

                    //添加对应平台映射关系
                    $skuParams['site'] = $site;
                    $skuParams['sku'] = $params['sku'];
                    $skuParams['frame_is_rimless'] = $item['frame_is_rimless'];
                    $skuParams['name'] = $item['name'];
                    $skuParams['category_id'] = $item['category_id'];
                    $result = (new \app\admin\model\itemmanage\ItemPlatformSku())->addPlatformSku($skuParams);
                } else {
                    $this->error('审核失败');
                }
            }
            $this->success('审核成功');
        } else {
            if ($ids) {
                $where['id'] = ['in', $ids];
            }
            $find = $this->model->where($where)->field('sku')->select();
            $sku = implode(',', array_column($find, 'sku'));
            //查询对应平台
            $magentoplatformarr = $this->magentoplatformarr;
            $magentoplatformarr = array_column($this->magentoplatform->getAuthSite(), 'name', 'id');

            $this->assign('platformarr', $magentoplatformarr);
            $this->assign('sku', $sku);
            $this->assign('ids', $ids);

            return $this->fetch('checkall');
        }
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
            //如果切换站点清除默认值
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['sku']) {
                $map['a.sku'] = ['like', '%' . trim($filter['sku']) . '%'];
                unset($filter['sku']);
            }
            if ($filter['category_id']) {
                $map['b.category_id'] = ['=', $filter['category_id']];
                unset($filter['category_id']);
            }
            if ($filter['available_stock']) {
                $map['b.available_stock'] = ['between', explode(',', $filter['available_stock'])];
            }
            if ($filter['platform_type']) {
                $map['a.platform_type'] = ['=', $filter['platform_type']];
                unset($filter['platform_type']);
            } else {
                $map['a.platform_type'] = 1;
            }
            $this->request->get(['filter' => json_encode($filter)]);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->alias('a')
                ->field('a.*,b.sku,b.available_stock,b.is_spot')
                ->join(['fa_item' => 'b'], 'a.sku=b.sku')
                ->where($where)
                ->where($map)
                ->count();

            $list = $this->model
                ->alias('a')
                ->field('a.*,b.sku,b.available_stock,b.is_spot')
                ->join(['fa_item' => 'b'], 'a.sku=b.sku')
                ->where($where)
                ->where($map)
                ->order('a.id', 'desc')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');
            //查询商品分类
            $category = $this->category->where('is_del', 1)->column('name', 'id');

            //查询生产周期
            $suppliersku = new \app\admin\model\purchase\SupplierSku();
            $product_cycle_arr = $suppliersku->where(['label' => 1, 'status' => 1, 'sku' => ['in', $skus]])->column('product_cycle', 'sku');
            //查询可用库存
            $stock = $this->item->where(['sku' => ['in', $skus]])->column('available_stock,on_way_stock,purchase_price', 'sku');
            //查询待入库数量
            $purchase = new \app\admin\model\purchase\PurchaseOrder();
            $wait_in_arr = $purchase->getWaitInStockNum($skus);
            //查询板材类sku
            $this->item = new \app\admin\model\itemmanage\Item();
            $skus = $this->item->getTextureSku();
            foreach ($list as &$v) {
                $v['category_name'] = $category[$v['category_id']];
                $v['available_stock'] = $stock[$v['sku']]['available_stock'] ?: 0;
                $v['on_way_stock'] = $stock[$v['sku']]['on_way_stock'] ?: 0;
                $v['product_cycle'] = $product_cycle_arr[$v['sku']] ?: 7;
                $v['wait_in_num'] = $wait_in_arr[$v['sku']] ?: 0;
                $v['sales_days'] = $v['average_90days_sales_num'] > 0 ? round($v['stock'] / $v['average_90days_sales_num']) : 0;
                $num = $v['stock'] - ($v['average_90days_sales_num'] * 30);
                $v['replenish_num'] = $num > 0 ? $num : 0;
                $v['purchase_price'] = $stock[$v['sku']]['purchase_price'];
                if (in_array($v['sku'], $skus)) {
                    $v['start_replenish_num'] = 50;
                } else {
                    $v['start_replenish_num'] = 300;
                }
            }

            $result = ["total" => $total, "rows" => $list];

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
            $skuCount = $platform->where(['sku' => $params['sku'], 'platform_type' => $params['website_type']])->count();
            if ($skuCount < 1) {
                $this->error('选择的站点没有此SKU');
            }
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
            if ($filter['is_spot']) {
                $sku = $this->item->where(['is_spot' => $filter['is_spot']])->column('sku');
                if ($sku) {
                    $map['sku'] = ['in', $sku];
                }
                unset($filter['is_spot']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                if ($filter['is_spot'] == 0) {
                    unset($filter['is_spot']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
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
                $v['is_spot'] = $this->item->where(['sku' => $v['sku']])->value('is_spot');
            }
            $result = ["total" => $total, "rows" => $list];

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
     * *@param  [type] $arr [二维数组]
     * @param  [type] $key [键名]
     *
     * @return [type]      [新的二维数组]
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/22
     * Time: 11:37
     */
    function array_group_by($arr, $key)
    {
        $grouped = [];
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
     * 紧急补货  2020.09.07改为计划任务周计划执行时间为每周三的24点，汇总各站提报的SKU及数量
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/7/17
     * Time: 9:22
     */
    // public function emergency_replenishment()
    // {
    //     $this->model = new \app\admin\model\NewProductMapping();
    //     $this->order = new \app\admin\model\purchase\NewProductReplenishOrder();
    //     //紧急补货分站点
    //     $platform_type = input('label');
    //     //统计计划补货数据
    //     $list = $this->model
    //         ->where(['is_show' => 1, 'type' => 2])
    //         // ->where(['is_show' => 1, 'type' => 2,'website_type'=>$platform_type]) //分站点统计补货需求 2020.9.4改为计划补货 不分站点
    //
    //         ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
    //         ->group('sku')
    //         ->column("sku,sum(replenish_num) as sum");
    //
    //     if (empty($list)) {
    //         $this->error('暂时没有紧急补货单需要处理');
    //     }
    //
    //     //统计各个站计划某个sku计划补货的总数 以及比例 用于回写平台sku映射表中
    //     $sku_list = $this->model
    //         ->where(['is_show' => 1, 'type' => 2])
    //
    //         ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
    //         ->field('id,sku,website_type,replenish_num')
    //         ->select();
    //     //根据sku对数组进行重新分配
    //     $sku_list = $this->array_group_by($sku_list, 'sku');
    //
    //     $result = false;
    //     Db::startTrans();
    //     try {
    //         //首先插入主表 获取主表id new_product_replenish
    //         $data['type'] = 2;
    //         $data['create_person'] = session('admin.nickname');
    //         $data['create_time'] = date('Y-m-d H:i:s');
    //         $res = Db::name('new_product_replenish')->insertGetId($data);
    //
    //         //遍历以更新平台sku映射表的 关联补货需求单id 以及各站虚拟仓占比
    //         $int = 0;
    //         foreach ($sku_list as $k => $v) {
    //             //求出此sku在此补货单中的总数量
    //             $sku_whole_num = array_sum(array_map(function ($val) {
    //                 return $val['replenish_num'];
    //             }, $v));
    //             //求出比例赋予新数组
    //             foreach ($v as $ko => $vo) {
    //                 $date[$int]['id'] = $vo['id'];
    //                 $date[$int]['rate'] = $vo['replenish_num'] / $sku_whole_num;
    //                 $date[$int]['replenish_id'] = $res;
    //                 $int += 1;
    //             }
    //         }
    //         //批量更新补货需求清单 中的补货需求单id以及虚拟仓比例
    //         $res1 = $this->model->allowField(true)->saveAll($date);
    //
    //         $number = 0;
    //         foreach ($list as $k => $v) {
    //             $arr[$number]['sku'] = $k;
    //             $arr[$number]['replenishment_num'] = $v;
    //             $arr[$number]['create_person'] = session('admin.nickname');
    //             $arr[$number]['create_time'] = date('Y-m-d H:i:s');
    //             $arr[$number]['type'] = 2;
    //             $arr[$number]['replenish_id'] = $res;
    //             $number += 1;
    //         }
    //         //插入补货需求单表
    //         $result = $this->order->allowField(true)->saveAll($arr);
    //         //更新计划补货列表
    //         $ids = $this->model
    //             ->where(['is_show' => 1, 'type' => 2])
    //
    //             ->whereTime('create_time', 'between', [date('Y-m-d H:i:s', strtotime("-1 month")), date('Y-m-d H:i:s')])
    //             ->setField('is_show', 0);
    //         Db::commit();
    //     } catch (ValidateException $e) {
    //         Db::rollback();
    //         $this->error($e->getMessage());
    //     } catch (PDOException $e) {
    //         Db::rollback();
    //         $this->error($e->getMessage());
    //     } catch (Exception $e) {
    //         Db::rollback();
    //         $this->error($e->getMessage());
    //     }
    //     if ($result !== false) {
    //         $this->success('操作成功！！');
    //     } else {
    //         $this->error(__('No rows were updated'));
    //     }
    // }


    /**
     * 不满足起订量列表
     *
     * @Description
     * @author wpl
     * @since 2020/09/08 09:43:46 
     * @return void
     */
    public function notSatisfiedOrderQuantityList()
    {
        //统计不满足起订量数据
        $this->notSatisfiedOrderQuantity();
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\admin\model\NewProductNotSatisfied();
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type', 1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 不满足起订量
     *
     * @Description
     * @author wpl
     * @since 2020/09/08 09:43:46 
     * @return void
     */
    protected function notSatisfiedOrderQuantity()
    {
        $this->mapping = new \app\admin\model\NewProductMapping();
        $list = $this->mapping->where(['is_show' => 1, 'type' => 1])->field('type,sku,sum(replenish_num) as replenish_num,website_type')->group('sku,type')->select();

        //查询板材类sku
        $this->item = new \app\admin\model\itemmanage\Item();
        $skus = $this->item->getTextureSku();

        //统计同款补货量
        $rows = $this->mapping->where(['is_show' => 1, 'type' => 1])->field("substring_index(sku, '-', 1) as origin_sku,sum(replenish_num) as replenish_num")->group('origin_sku,type')->select();
        $rows = collection($rows)->toArray();
        $spus = [];
        foreach ($rows as $k => $v) {
            $spus[$v['origin_sku']] = $v['replenish_num'];
        }

        $data = [];
        foreach ($list as $k => $v) {
            //判断sku材质是否为板材
            if (in_array($v['sku'], $skus)) {
                //判断板材补货量小于50 为不满足起订量
                if ($v['replenish_num'] < 50) {
                    $data[$k]['sku'] = $v['sku'];
                    //各个站的sku统计的数量
                    $data[$k]['z_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 1, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['v_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 2, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['nihao_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 3, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['m_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 4, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['w_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 5, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['a_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 8, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['z_es_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 9, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['z_de_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 10, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['type'] = $v['type'];
                    $data[$k]['replenish_num'] = $v['replenish_num'];
                }
            } else {
                $spu = substr($v['sku'], 0, strrpos($v['sku'], '-'));
                if ($spus[$spu] < 300) {
                    $data[$k]['sku'] = $v['sku'];
                    $data[$k]['z_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 1, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['v_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 2, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['nihao_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 3, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['m_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 4, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['w_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 5, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['a_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 8, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['z_es_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 9, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['z_de_sku_num'] = $this->mapping->where(['is_show' => 1, 'sku' => $v['sku'], 'website_type' => 10, 'type' => 1])->value('replenish_num') ?: 0;
                    $data[$k]['type'] = $v['type'];
                    $data[$k]['replenish_num'] = $v['replenish_num'];
                }
            }
        }
        //清空表
        Db::execute("truncate table fa_new_product_not_satisfied;");
        Db::table('fa_new_product_not_satisfied')->insertAll($data);
    }

    /**
     * 选品批量导出xls
     *
     * @Description
     * @return void
     * @since  2020/02/28 14:45:39
     * @author wpl
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        if ($ids) {
            $map['a.id'] = ['in', $ids];
        }

        //如果切换站点清除默认值
        $filter = json_decode($this->request->get('filter'), true);
        if ($filter['create_person']) {
            $map['a.create_person'] = $filter['create_person'];
            unset($filter['create_person']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

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
            if ($filter['platform_type'] == 10) {
                $map['item_status'] = ['=', 1];
            } else {
                $skus = $this->platformsku->where(['platform_type' => $filter['platform_type']])->column('sku');
                $map1['sku'] = ['in', $skus];
            }
            unset($filter['platform_type']);
            $this->request->get(['filter' => json_encode($filter)]);
        }


        [$where] = $this->buildparams();

        $list = $this->model->alias('a')
            ->join(['fa_supplier' => 'b'], 'a.supplier_id=b.id')
            ->where($where)
            ->where($map)
            ->where($map1)
            ->select();
        $list = collection($list)->toArray();
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "SKU")
            ->setCellValue("B1", "供应商SKU")
            ->setCellValue("C1", "供应商名称");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "单价")
            ->setCellValue("E1", "选品状态")
            ->setCellValue("F1", "商品名称");
        $item = new Item();
        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['supplier_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['supplier_name']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['price']);
            if ($value['item_status'] == 1) {
                $status = '待选品';
            } elseif ($value['item_status'] == 2) {
                $status = '选品通过';
            } elseif ($value['item_status'] == 3) {
                $status = '选品拒绝';
            } elseif ($value['item_status'] == 4) {
                $status = '取消';
            } else {
                $status = '新建';
            }
            $skuName = $item->where('sku', $value['sku'])->value('name');
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $status);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $skuName);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(40);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:E' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '选品数据' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
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


    public function batch_export_xls_copy()
    {

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $where['a.origin_sku'] = ['like', '%' . 'RN' . '%'];
        $list = $this->model
            ->alias('a')
            ->join(['fa_new_product_attribute' => 'b'], 'a.id = b.item_id')
            ->join(['fa_supplier' => 'c'], 'a.supplier_id=c.id')
            ->field('a.*,b.accessory_color,b.accessory_texture,c.supplier_name')
            ->where($where)->select();
        $list = collection($list)->toArray();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "SKU")
            ->setCellValue("B1", "供应商SKU")
            ->setCellValue("C1", "供应商名称");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "单价")
            ->setCellValue("E1", "选品状态")
            ->setCellValue("F1", "商品名称")
            ->setCellValue("G1", "商品颜色")
            ->setCellValue("H1", "材质");

        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['supplier_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['supplier_name']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['price']);
            if ($value['item_status'] == 1) {
                $status = '待选品';
            } elseif ($value['item_status'] == 2) {
                $status = '选品通过';
            } elseif ($value['item_status'] == 3) {
                $status = '选品拒绝';
            } elseif ($value['item_status'] == 4) {
                $status = '取消';
            } else {
                $status = '新建';
            }


            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $status);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['name']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['accessory_color']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['accessory_texture']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:H' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '选品数据RN' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
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

    //运营补货需求购物车删除（真删除）
    public function replenish_cart_del($ids = "")
    {

        Db::startTrans();
        try {
            $res = Db::name('new_product_mapping')->where('id', $ids)->delete();
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($res) {
            $this->success();
        } else {
            $this->error(__('No rows were deleted'));
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 提报历史
     *
     * @Description
     * @author wpl
     * @since 2020/07/13 13:56:00 
     * @return void
     */
    public function productMappingListHistory()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\NewProductMapping();
            $this->item = new Item();
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //默认站点
            $platformType = input('label');
            $map['website_type'] = $platformType;
            //如果切换站点清除默认值
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['website_type']) {
                $platformType = $filter['website_type'];
                unset($map['website_type']);
                if (100 == $filter['website_type']) {
                    unset($filter['website_type']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
            }
            //sku
            if ($filter['sku']) {
                //改为模糊搜索
                $map['a.sku'] = ['LIKE', '%' . trim($filter['sku'] . '%')];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            //补货类型
            if ($filter['type']) {
                $map['a.type'] = $filter['type'];
                unset($filter['type']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            //提报时间
            if ($filter['create_time']) {
                $arr = explode(' ', $filter['create_time']);
                $map['a.create_time'] = ['between', [$arr[0] . ' ' . $arr[1], $arr[3] . ' ' . $arr[4]]];
                unset($filter['create_time']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['is_spot']) {
                $sku = $this->item->where(['is_spot' => $filter['is_spot']])->column('sku');
                if ($sku) {
                    $map['a.sku'] = ['in', $sku];
                }
                unset($filter['is_spot']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                if ($filter['is_spot'] == 0) {
                    unset($filter['is_spot']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model->alias('a')
                ->join(['fa_new_product_replenish' => 'b'], 'a.replenish_id=b.id', 'left')
                ->join(['fa_new_product_replenish_list' => 'c'], 'a.replenish_id=c.replenish_id and a.sku = c.sku', 'left')
                ->join(['fa_purchase_order_item' => 'e'], 'a.sku=e.sku', 'left')
                ->join(['fa_purchase_order' => 'd'], 'e.purchase_id = d.id and b.id = d.replenish_id', 'left')
                ->where($where)
                ->where('is_show', 0)
                ->where('a.replenish_id<>0')
                ->where('d.replenish_id<>0')
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $this->model->alias('a')
                ->field('a.sku,a.type,a.create_time,a.replenish_num,b.status,c.real_dis_num,d.purchase_number,d.arrival_time,d.purchase_status,d.id as purchase_id,(c.distribute_num) as distribute_count')
                ->join(['fa_new_product_replenish' => 'b'], 'a.replenish_id=b.id', 'left')
                ->join(['fa_new_product_replenish_list' => 'c'], 'a.replenish_id=c.replenish_id and a.sku = c.sku', 'left')
                ->join(['fa_purchase_order_item' => 'e'], 'a.sku=e.sku', 'left')
                ->join(['fa_purchase_order' => 'd'], 'e.purchase_id = d.id and b.id = d.replenish_id', 'left')
                ->where($where)
                ->where('is_show', 0)
                ->where('a.replenish_id<>0')
                ->where('d.replenish_id<>0')
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['is_spot'] = $this->item->where(['sku' => $v['sku']])->value('is_spot');
            }
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }
        //查询对应平台权限
        $magentoplatformArr = array_values($this->magentoplatform->getNewAuthSite1());

        //取第一个key为默认站点
        $site = input('site', $magentoplatformArr[0]['id']);
        $this->assignconfig('label', $site);
        $this->assign('site', $site);
        $this->assign('magentoplatformarr', $magentoplatformArr);

        return $this->view->fetch();
    }

    /**
     * 详情
     *
     * @Description
     * @author wpl
     * @since 2020/09/07 10:48:34 
     * @return void
     */
    public function productMappingDetail()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            $this->model = new \app\admin\model\purchase\PurchaseBatch();
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $purchase_id = input('purchase_id');
            $platform_type = input('platform_type');
            $check_order_item = new \app\admin\model\warehouse\CheckItem();
            $in_stock = new \app\admin\model\warehouse\Instock();
            $in_stock_item = new \app\admin\model\warehouse\InstockItem();
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $map['a.purchase_id'] = $purchase_id;
            $total = $this->model->alias('a')
                ->join(['fa_purchase_batch_item' => 'b'], 'a.id=b.purchase_batch_id')
                ->join(['fa_check_order' => 'c'], 'a.id=c.batch_id', 'left')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            //查询分批到货
            $list = $this->model->alias('a')
                ->field('a.purchase_id,a.id,a.arrival_time,a.replenish_id,b.sku,b.arrival_num as wait_arrival_num,c.status,c.id as check_id')
                ->join(['fa_purchase_batch_item' => 'b'], 'a.id=b.purchase_batch_id')
                ->join(['fa_check_order' => 'c'], 'a.id=c.batch_id', 'left')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as &$v) {
                $check_list = [];
                $in_stock_list = [];
                if ($v['check_id']) {
                    //查询质检合格数量
                    $check_list = $check_order_item->where(['check_id' => $v['check_id'], 'sku' => $v['sku']])->find();
                    //查询入库状态及入库数量
                    $in_stock_list = $in_stock->where(['check_id' => $v['check_id']])->find();
                }
                $quantity_num = $check_list['quantity_num'] ?: 0;
                $arrivals_num = $check_list['arrivals_num'] ?: 0;
                $wait_arrival_num = $v['wait_arrival_num'];
                $instock_num = $in_stock_item->where(['in_stock_id' => $in_stock_list['id'], 'sku' => $v['sku']])->value('in_stock_num');
                if (100 != $platform_type) {
                    $rate = $in_stock_item->where(['replenish_id' => $v['replenish_id'], 'sku' => $v['sku'], 'website_type' => $platform_type])->value('rate');
                    $quantity_num = round($quantity_num * $rate);
                    $arrivals_num = round($arrivals_num * $rate);
                    $wait_arrival_num = round($wait_arrival_num * $rate);
                    $instock_num = round($instock_num * $rate);
                }
                $v['instock_status'] = $in_stock_list['status'];
                $v['quantity_num'] = $quantity_num;
                $v['arrivals_num'] = $arrivals_num;
                $v['wait_arrival_num'] = $wait_arrival_num;
                $v['instock_num'] = $instock_num;
            }
            unset($v);
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        $this->assignconfig('purchase_id', input('purchase_id'));

        return $this->view->fetch();
    }

    /**
     * 选品批量导入
     *
     * @Description
     * @author lzh
     * @since 2020/09/04 09:38:39
     * @return void
     */
    public function import()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $_item = new \app\admin\model\itemmanage\Item();
        $_platform = new \app\admin\model\itemmanage\ItemPlatformSku();

        //校验参数空值
        $file = $this->request->request('file');
        !$file && $this->error(__('Parameter %s can not be empty', 'file'));
        $label = $this->request->request('label');
        !$label && $this->error(__('Parameter %s can not be empty', 'label'));

        //查询对应平台权限
        $plat_form = array_column($this->magentoplatform->getAuthSite(), 'id');
        !in_array($label, $plat_form) && $this->error(__('站点类型错误'));

        //校验文件路径
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        !is_file($filePath) && $this->error(__('No results were found'));

        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        !in_array($ext, ['csv', 'xls', 'xlsx']) && $this->error(__('Unknown data format'));
        if ('csv' === $ext) {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if (0 == $n || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ('xls' === $ext) {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //模板文件列名
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= 11; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    if (!empty($val)) {
                        $fields[] = $val;
                    }
                }
            }

            //校验模板文件格式
            $listName = ['商品SKU', '类型', '补货需求数量'];
            $listName !== $fields && $this->error(__('模板文件格式错误！'));

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
            empty($data) && $this->error('表格数据为空！');

            //获取表格中sku集合
            $skuArr = [];
            foreach ($data as $k => $v) {
                //获取sku
                $sku = trim($v[0]);
                empty($sku) && $this->error(__('导入失败,第 ' . ($k + 1) . ' 行SKU为空！'));
                $skuArr[] = $sku;
            }

            //获取池子中现有数据
            $list = $this->model
                ->where('website_type', $label)
                ->where('is_show', 1)
                ->field('type,sku')
                ->select();
            $mappingArr = array_column(collection($list)->toArray(), 'type', 'sku');

            //获取站点sku列表
            $list = $_platform
                ->where('platform_type', $label)
                ->where(['sku' => ['in', $skuArr]])
                ->field('sku')
                ->select();
            $platformArr = array_column(collection($list)->toArray(), 'sku');

            //获取sku所属分类列表
            $list = $_item
                ->where(['sku' => ['in', $skuArr]])
                ->where('is_del', 1)
                ->where('is_open', 1)
                ->field('sku,category_id')
                ->select();
            $categoryArr = array_column(collection($list)->toArray(), 'category_id', 'sku');

            //类型
            $typeArr = ['月度计划' => 1, '周度计划' => 2, '日度计划' => 3];

            //批量导入
            $params = [];
            foreach ($data as $v) {
                //获取sku
                $sku = trim($v[0]);

                //校验sku是否重复
                isset($params[$sku]) && $this->error(__('导入失败,商品 ' . $sku . ' 重复！'));

                //获取类型
                $typeName = trim($v[1]);
                !isset($typeArr[$typeName]) && $this->error('导入失败,商品 ' . $sku . ' 类型错误！');

                //校验池子中商品是否有相同sku
                // isset($mappingArr[$sku]) && $mappingArr[$sku] == $typeArr[$typeName] && $this->error(__('导入失败,商品 '.$sku.' 已在补货列表中！'));
                //有重复数据的时候以导入的数据为准，覆盖掉系统中重复的数据，没有重复的数据还留在系统中
                isset($mappingArr[$sku]) && $mappingArr[$sku] == $typeArr[$typeName] && $this->model->where(['website_type' => $label, 'sku' => $sku, 'type' => $typeArr[$typeName]])->delete();

                //校验商品是否存在
                !in_array($sku, $platformArr) && $this->error(__('导入失败,商品 ' . $sku . ' 不存在！'));

                //获取商品分类
                $categoryId = isset($categoryArr[$sku]) ? $categoryArr[$sku] : 0;

                //获取补货量
                $replenishNum = (int)$v[2];
                empty($replenishNum) && $this->error(__('导入失败,商品 ' . $sku . ' 补货数量不能为空！'));

                $params[$sku] = [
                    'website_type'  => $label,
                    'sku'           => $sku,
                    'create_time'   => date('Y-m-d H:i:s'),
                    'create_person' => session('admin.nickname'),
                    'replenish_num' => $replenishNum,
                    'type'          => $typeArr[$typeName],
                    'category_id'   => $categoryId,
                ];
            }

            $this->model->allowField(true)->saveAll($params) ? $this->success('导入成功！') : $this->error('导入失败！');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }



    //已跑完
    // public function transfer_wesee_amazon()
    // {
    //     $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
    //     $skus = Db::name('zzzzzzzzzzz_amazonsku')->field('sku')->select();
    //     //50个sku一组
    //     $skus = array_chunk($skus, 50);
    //     foreach ($skus as $k => $v) {
    //         //生成调拨单号 插入主表
    //         $params['transfer_order_number'] = 'TO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
    //         $params['call_out_site'] = 5;
    //         $params['call_in_site'] = 8;
    //         $params['status'] = 0;
    //         $params['create_time'] = date("Y-m-d H:i:s");
    //         $params['create_person'] = '陈鹏';
    //
    //         $transfer_order_number = Db::name('transfer_order')->insertGetId($params);
    //
    //         foreach ($v as $kk => $vv){
    //             $sku_detail = $item_platform_sku->where(['sku'=>$vv['sku'],'platform_type'=>5])->value('stock');
    //             if ($sku_detail == 0){
    //                 echo 'sku'.$vv['sku'].'在批发站的库存为0';
    //             }else{
    //                 $data['transfer_order_id'] = $transfer_order_number;
    //                 $data['sku'] = $vv['sku'];
    //                 //调出数量
    //                 $data['num'] = $sku_detail;
    //                 //调出的虚拟仓库存
    //                 $data['stock'] = $sku_detail;
    //
    //                 $res = Db::name('transfer_order_item')->insert($data);
    //             }
    //         }
    //     }
    //
    //
    //
    // }

    // public function transfer_zeelool_es()
    // {
    //     //西语站
    //     $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
    //     $skus = Db::name('zzzz_es')->field('sku')->select();
    //
    //     //50个sku一组
    //     $skus = array_chunk($skus, 200);
    //     foreach ($skus as $k => $v) {
    //         //生成调拨单号 插入主表
    //         $params['transfer_order_number'] = 'TO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
    //         $params['call_out_site'] = 1;
    //         $params['call_in_site'] = 9;
    //         $params['status'] = 0;
    //         $params['create_time'] = date("Y-m-d H:i:s");
    //         $params['create_person'] = '张俊杰';
    //
    //         $transfer_order_number = Db::name('transfer_order')->insertGetId($params);
    //
    //         foreach ($v as $kk => $vv){
    //             $sku_detail = $item_platform_sku->where(['sku'=>$vv['sku'],'platform_type'=>1])->value('stock');
    //             if ($sku_detail == 0){
    //                 echo 'sku'.$vv['sku'].'库存为0';
    //             }else{
    //                 $data['transfer_order_id'] = $transfer_order_number;
    //                 $data['sku'] = $vv['sku'];
    //                 //调出数量
    //                 $data['num'] = '10';
    //                 //调出的虚拟仓库存
    //                 $data['stock'] = $sku_detail;
    //
    //                 $res = Db::name('transfer_order_item')->insert($data);
    //                 if ($res){
    //                     echo $vv['sku'].'插入成功';
    //                 }
    //             }
    //         }
    //     }
    //
    //
    //
    // }
    //
    // public function transfer_zeelool_de()
    // {
    //     //德语站
    //     $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
    //     $de_skus = Db::name('zzzz_de')->field('sku')->select();
    //     //50个sku一组
    //     $de_skus = array_chunk($de_skus, 200);
    //     foreach ($de_skus as $k => $v) {
    //         //生成调拨单号 插入主表
    //         $params['transfer_order_number'] = 'TO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
    //         $params['call_out_site'] = 1;
    //         $params['call_in_site'] = 10;
    //         $params['status'] = 0;
    //         $params['create_time'] = date("Y-m-d H:i:s");
    //         $params['create_person'] = '张俊杰';
    //
    //         $transfer_order_number = Db::name('transfer_order')->insertGetId($params);
    //
    //         foreach ($v as $kk => $vv){
    //             $sku_detail = $item_platform_sku->where(['sku'=>$vv['sku'],'platform_type'=>1])->value('stock');
    //             if ($sku_detail == 0){
    //                 echo 'sku'.$vv['sku'].'库存为0';
    //             }else{
    //                 $data['transfer_order_id'] = $transfer_order_number;
    //                 $data['sku'] = $vv['sku'];
    //                 //调出数量
    //                 $data['num'] = '10';
    //                 //调出的虚拟仓库存
    //                 $data['stock'] = $sku_detail;
    //
    //                 $res = Db::name('transfer_order_item')->insert($data);
    //                 if ($res){
    //                     echo $vv['sku'].'插入成功';
    //                 }
    //             }
    //         }
    //     }
    // }


    /**
     * M站饰品站脚本数据
     *
     */
    public function cat_import_data()
    {
        $data = Db::table('Sheet3')->select();
        $list = collection($data)->toArray();
        Db::startTrans();
        try {
            foreach ($list as $key => $value) {
                $supplier_id = Db::table('fa_supplier')->where('supplier_name', $value['供应商名称'])->value('id');
                if (empty($supplier_id)) {
                    $supplier_id = 0;
                }
                //                $add['category_id'] = 53;
                //                //直接设置选品通过
                //                $add['item_status'] = 2;
                //                $add['price'] = $value['单价'];
                //                $add['sku'] = $value['SKU'];
                //                $add['link'] = $value['1688商品购买页链接'];
                //                $add['create_time'] = date('Y-m-d H:i:s',time());
                //                $add['supplier_id'] =$supplier_id;
                //                $add['create_person'] =$value['创建人'];
                //                $add['name'] =$value['商品名称'];
                //                $add['supplier_sku'] =$value['供应商SKU'];
                //
                //                //添加商品表信息
                //                $lastInsertId = Db::name('new_product')->insertGetId($add);
                //                if ($lastInsertId !==false){
                //                    $itemAttribute['item_id'] = $lastInsertId;
                //                    $itemAttribute['attribute_type'] = 3;
                //                    $itemAttribute['accessory_texture'] = $value['材质'];
                //                    $itemAttribute['accessory_color'] = $value['商品颜色'];
                //                    $itemAttribute['frame_size'] = $value['尺寸'];
                //                    //添加商品属性表
                //                    $res = Db::name('new_product_attribute')->insert($itemAttribute);
                //                    if (!$res) {
                //                        throw new Exception('添加失败！！');
                //                    }
                //                    //绑定供应商SKU关系
                //                    $supplier_data['sku'] = $value['SKU'];
                //                    $supplier_data['supplier_id'] = $supplier_id;
                //                    $supplier_data['createtime'] = date("Y-m-d H:i:s", time());
                //                    $supplier_data['create_person'] = session('admin.nickname');
                //                    $supplier_data['link'] = $value['1688商品购买页链接'];
                //                    $supplier_data['is_matching'] = 1;
                //                    $supplier_data['status'] = 1;
                //                    if ($value['是否为主供应商'] =='是'){
                //                        $label  = 1;
                //                    }else{
                //                        $label = 0;
                //                    }
                //                    $supplier_data['label'] = $label;
                //                    $supplier_data['is_big_goods'] = 0;
                //                    $add['product_cycle'] = $supplier_data['product_cycle'] = $value['生产周期'];
                //                    $supplier_data['supplier_sku'] = $value['供应商SKU'];
                //                    Db::name('supplier_sku')->insert($supplier_data);

                //添加对应平台映射关系
                $skuParams['platform_type'] = 4;
                $skuParams['sku'] = $value['SKU'];
                $skuParams['platform_sku'] = $value['SKU'];
                $skuParams['name'] = $value['商品名称'];
                $skuParams['category_id'] = 53;
                $add['presell_status'] = $skuParams['presell_status'] = 1;
                $add['presell_residue_num'] = $skuParams['presell_residue_num'] = 100;
                $add['presell_num'] = $skuParams['presell_num'] = 100;
                $add['presell_create_time'] = $skuParams['presell_start_time'] = '2021-01-21 00:00:00';
                $add['presell_end_time'] = $skuParams['presell_end_time'] = '2022-01-21 00:00:00';
                $magento_platform = new \app\admin\model\platformManage\MagentoPlatform();
                $prefix = $magento_platform->getMagentoPrefix($skuParams['platform_type']);
                //判断前缀是否存在
                if (false == $prefix) {
                    return false;
                }
                //监测平台sku是否存在
                $itemPlatfromSku = new  ItemPlatformSku();
                $platformSkuExists = $itemPlatfromSku->getTrueSku($prefix . $skuParams['site']['sku'], $skuParams['site']['site']);
                if ($platformSkuExists) {
                    return false;
                }
                $skuParams['platform_sku'] = $prefix . $value['SKU'];
                $skuParams['platform_frame_is_rimless'] = '';
                $skuParams['create_person'] = $value['创建人'];
                $skuParams['create_time'] = date("Y-m-d H:i:s", time());
                //添加stock库商品表信息
                $Stock = Db::connect('database.db_stock');
                $Stock->table('fa_item_platform_sku')->insert($skuParams);
                //                    $add['item_status'] = 3;
                //                    unset($add['link']);
                //                    unset($add['supplier_id']);
                //                    unset($add['supplier_sku']);
                //
                //                    $Stock->table('fa_item')->insert($add);
                //                    unset($add);
                //                    $itemAttribute['frame_texture'] = 0;
                //                    $Stock->table('fa_item_attribute')->insert($itemAttribute);
                //
                //                }
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


    /**
     * @author zjw
     * @date   2021/4/24 10:20
     * 运营补货需求购物车
     */
    public function printing_batch_export_xls()
    {
        $this->model = new \app\admin\model\NewProductMapping();
        $value = input('param.filter');
        $filter = json_decode($value, true);
        if (empty($filter)) {
            $map['website_type'] = 1;
        } else {
            $map['website_type'] = $filter['website_type'];
        }
        $list = $this->model
            ->where('is_show', 1)
            ->where($map)
            ->order('id desc')
            ->select();
        $list = collection($list)->toArray();
        //查询商品分类
        $category = $this->category->where('is_del', 1)->column('name', 'id');
        foreach ($list as &$v) {
            $v['category_name'] = $category[$v['category_id']];
            $v['is_spot'] = $this->item->where(['sku' => $v['sku']])->value('is_spot');
            if ($v['is_spot'] == 1) {
                $v['is_spot'] = '大货';
            } elseif ($v['is_spot'] == 2) {
                $v['is_spot'] = '现货';
            } else {
                $v['is_spot'] = '-';
            }
            if ($v['type'] == 1) {
                $v['type'] = '月度计划';
            } elseif ($v['type'] == 2) {
                $v['type'] = '周度计划';
            } else {
                $v['type'] = '日度计划';
            }
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "ID")
            ->setCellValue("B1", "商品SKU")
            ->setCellValue("C1", "大货/现货");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "商品分类")
            ->setCellValue("E1", "类型")
            ->setCellValue("F1", "补货需求数量");

        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['id']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['is_spot']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['category_name']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['type']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['replenish_num']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:F' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '运营补货需求购物车' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
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
