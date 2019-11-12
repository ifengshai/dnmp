<?php

namespace app\admin\controller;

use app\common\controller\Backend;
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['supplier'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['supplier'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();


            $list = collection($list)->toArray();
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
            if ($params) {
                $params = $this->preExcludeFields($params);
                $itemName = $params['name'];
                $itemColor = $params['color'];
                $supplierSku = $params['supplier_sku'];
                $skuId = $params['skuid'];
                $price = $params['price'];
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

                Db::startTrans();
                try {

                    if (!array_filter($itemName)) {
                        throw new Exception('商品名称不能为空！！');
                    }

                    foreach (array_filter($itemName) as $k => $v) {
                        $data['name'] = $v;
                        $data['category_id'] = $params['category_id'];
                        $data['item_status'] = $params['item_status'];
                        $data['brand_id']    = $params['brand_id'];
                        $data['supplier_id']    = $params['supplier_id'];
                        $data['supplier_sku']    = $supplierSku[$k];
                        $data['link']    = $params['link'];
                        $data['price']    = $price[$k];
                        $data['supplier_id']    = $params['supplier_id'];
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
                            $supplier_data['create_person'] = session('admin.username');
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
                return  $this->error('对应分类不存在,请重新尝试');
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
                $allFrameSize  = $this->itemAttribute->getFrameSize();
                //获取所有眼镜类型
                $allGlassesType = $this->itemAttribute->getGlassesType();
                //获取所有采购产地
                $allOrigin      = $this->itemAttribute->getOrigin();
                //获取配镜类型
                $allFrameType   = $this->itemAttribute->getFrameType();
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
            $sku        = $this->request->post('sku');
            if (!$categoryId || !$sku) {
                $this->error('参数错误，请重新尝试');
            }
            $result = $this->category->getAttrCategoryById($categoryId);
            if (!$result) {
                $this->error('对应分类不存在,请从新尝试');
            } elseif ($result == -1) {
                $this->error('对应分类存在下级分类,请从新选择');
            }
            if ($result == 1) {
                $row = $this->model->getItemInfo($sku);
                if (!$row) {
                    $this->error(__('此SKU不存在！！'));
                }
                $this->success('ok', '', $row);
            } elseif ($result == 2) { //商品是镜片类型
                $data = $this->fetch('eyeglass');
            } elseif ($result == 3) { //商品是饰品类型
                $data = $this->fetch('decoration');
            } else {
                $data = $this->fetch('attribute');
            }
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

    // /***
    //  * ajax请求比对商品名称是否重复
    //  */
    // public function ajaxGetInfoName()
    // {
    //     if ($this->request->isAjax()) {
    //         $name = $this->request->post('name');
    //         if (!$name) {
    //             $this->error('参数错误，请重新尝试');
    //         }
    //         $result = $this->model->getInfoName($name);
    //         if ($result < 1) {
    //             return  $this->success('可以添加');
    //         } else {
    //             return $this->error('商品名称已经存在,请重新添加');
    //         }
    //     } else {
    //         return $this->error(__('请求出错！！'));
    //     }
    // }

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
    public function passAudit($ids = null)
    {
        if ($this->request->isAjax()) {
            //查询所选择的数据
            $where['new_product.id'] = ['in', $ids];
            $row = $this->model->where($where)->with(['newproductattribute'])->select();
            $row = collection($row)->toArray();
            foreach($row as $k => $v) {
                if ($v['item_status'] != 1) {
                    $this->error('此状态不能审核！！');
                }
            }

            $map['id'] = ['in', $ids];
            $map['item_status'] = 1;
            $data['item_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res !== false) {
                if ($row) {
                    
                    foreach ($row as $val) {
                        $params = $val;
                        $params['create_person'] = session('admin.nickname');
                        $params['create_time'] = date('Y-m-d H:i:s', time());
                        $params['item_status'] = 1;
                        unset($params['id']);
                        //查询商品表SKU是否存在
                        $t_where['sku'] = $params['sku'];
                        $t_where['is_del'] = 1;
                        $count = $this->item->where($t_where)->count();
                        //此SKU已存在 跳过
                        if ($count > 0) {
                            continue;
                        }

                        //添加商品主表信息
                        $this->item->allowField(true)->isUpdate(false)->data($params, true)->save();
                        $attributeParams = $val['newproductattribute'];
                        unset($attributeParams['id']);
                        $attributeParams['item_id'] = $this->item->id;
                        //添加商品属性表信息
                        $this->itemAttribute->allowField(true)->isUpdate(false)->data($attributeParams, true)->save();
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
    public function auditRefused($ids = null)
    {
        if ($this->request->isAjax()) {
            $map['id'] = ['in', $ids];
            //查询所选择的数据
            $where['new_product.id'] = ['in', $ids];
            $row = $this->model->where($where)->with(['newproductattribute'])->select();
            $row = collection($row)->toArray();
            foreach($row as $k => $v) {
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
            $list[$k]['color'] = $v->attributes[0]->attributeValue;
            $list[$k]['cargoNumber'] = $v->cargoNumber;
            $list[$k]['price'] = @$v->price ? @$v->price : @$v->consignPrice;
            $list[$k]['skuId'] = $v->skuId;
        }
        if ($list) {
            $this->success('采集成功！！', '', $list);
        } else {
            $this->error('未采集到数据！！', '', $list);
        }
    }
}
