<?php

namespace app\admin\model\itemmanage;

use think\Model;
use think\Db;
use app\admin\model\itemmanage\attribute\ItemAttribute;

class Item extends Model
{
    //制定数据库连接
    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    public function itemAttribute()
    {
        return $this->hasOne('app\admin\model\itemmanage\attribute\ItemAttribute', 'item_id', 'id');
    }

    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? $value : $value);
    }

    /***
     * 获取随机的SKU编码
     */
    public function getOriginSku()
    {
        return  rand(0, 99) . rand(0, 99) . rand(0, 99);
    }

    /***
     * 模糊查询已经存在的源sku
     */
    public function likeOriginSku($value)
    {
        $map['sku'] = ['like', '%' . $value . '%'];
        $map['is_del'] = 1;
        $result = $this->where($map)->field('sku')->distinct(true)->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['sku'];
        }
        return $arr;
    }

    /***
     * 查询sku信息
     * @param $sku
     * @param $type 1 镜架  2 镜片  3 配饰
     * @return bool
     */
    public function getItemInfo($sku, $type = 1)
    {
        $info = $this->where('sku', '=', $sku)->field('id,sku')->find();
        if (!$info) {
            return -1;
        }
        $result = $this->alias('m')->where('sku', '=', $sku)->join('item_attribute a', 'm.id=a.item_id')->find();
        if (!$result) {
            return false;
        }
        if (!empty($result['origin_sku'])) {
            //镜架类型
            if (1 == $type) {
                $colorArr = (new ItemAttribute())->getFrameColor();
                $arr = $this->alias('m')->where('origin_sku', '=', $result['origin_sku'])->join('item_attribute a', 'm.id=a.item_id')->field('m.name,m.price,a.frame_color')->select();
                if (is_array($arr)) {
                    foreach ($arr as $k => $v) {
                        $arr[$k]['frame_color_value'] = $colorArr[$v['frame_color']];
                    }
                }
                $result['itemArr'] = $arr;
            } elseif (3 <= $type) { //配饰类型
                $arr = $this->alias('m')->where('origin_sku', '=', $result['origin_sku'])->join('item_attribute a', 'm.id=a.item_id')->field('m.name,m.price,a.accessory_color')->select();
                $result['itemArr'] = $arr;
            }
            $result['itemCount'] = $this->where('origin_sku', '=', $result['origin_sku'])->count();
            $result['type'] = $type;
        }
        return $result ? $result : false;
    }
    /**
     * 获取商品表SKU数据
     * @return array
     */
    public function getItemSkuInfo()
    {
        $map['is_del'] = 1;
        $map['is_open'] = 1;
        return $this->where($map)->column('sku', 'id');
    }

    /***
     * 查询商品名称是否重复
     */
    public function getInfoName($name)
    {
        $result = $this->where('name', '=', $name)->value('name');
        return $result ? $result : false;
    }

    /***
     * 得到一条商品的记录(属性)
     */
    public function getItemRow($sku, $type = 1)
    {
        $result = $this->alias('g')->where('sku', '=', $sku)->join('item_attribute a', 'g.id=a.item_id')
            ->field('g.*,a.item_id,a.glasses_type,a.procurement_type,a.procurement_origin,a.frame_type,a.frame_width,a.frame_height,
            a.frame_length,a.frame_temple_length,a.frame_bridge,a.mirror_width,a.frame_color,a.frame_weight,a.frame_shape,a.shape,
            a.frame_texture,a.frame_gender,a.frame_size,a.frame_is_recipe,a.frame_piece,a.frame_is_advance,
            a.frame_temple_is_spring,a.frame_is_adjust_nose_pad')->find();
        if (!$result) {
            return false;
        }
        //获取所有眼镜形状
        $frameShape = (new ItemAttribute())->getAllFrameShape();
        //获得所有框型
        if ($type != 2) {
            $shape  = (new ItemAttribute())->getAllShape();
        } else {
            $shape  = (new ItemAttribute())->getAllShape(2);
        }
        //获取所有材质
        $texture    = (new ItemAttribute())->getAllTexture();
        //获取适合人群
        $frameGender   = (new ItemAttribute())->getFrameGender();
        //获取尺寸型号
        if ($type != 2) {
            $frameSize     = (new ItemAttribute())->getFrameSize();
        } else {
            $frameSize     = (new ItemAttribute())->getFrameSize(2);
        }
        //获取镜架所有的颜色
        $frameColor    = (new ItemAttribute())->getFrameColor();
        //获取眼镜类型
        $glassesType   = (new ItemAttribute())->getGlassesType();
        //获取所有线下采购产地
        $origin        = (new ItemAttribute())->getOrigin();
        //获取配镜类型
        $frameType     = (new ItemAttribute())->getFrameType();
        //获取调节是否调节鼻托
        //$frameIsAdjustNosePad = (new ItemAttribute())->getAllNosePad();
        //glasses_type多选字段
        //        $glassesTypeArr = explode(',',$result['glasses_type']);
        //        $frameShapeArr  = explode(',',$result['frame_shape']);
        //        $frameSizeArr   = explode(',',$result['frame_size']);
        //        $result['glasses_type'] = $result['frame_shape'] = $result['frame_size'] =[];
        //        foreach ($glassesTypeArr as $k => $v){
        //            $result['glasses_type'][]= $glassesType[$v];
        //        }
        //        foreach ($frameShapeArr as $k => $v){
        //            $result['frame_shape'][]= $frameShape[$v];
        //        }
        //        foreach ($frameSizeArr as $k => $v){
        //            $result['frame_size'][]= $frameSize[$v];
        //        }
        //frame_shape多选字段
        $result['glasses_type']       = $glassesType[$result['glasses_type']];
        $result['procurement_origin'] = $origin[$result['procurement_origin']];
        $result['frame_type']         = $frameType[$result['frame_type']];
        $result['frame_color']        = $frameColor[$result['frame_color']];
        $result['frame_shape']        = $frameShape[$result['frame_shape']];
        $result['shape']              = $shape[$result['shape']];
        $result['frame_texture']      = $texture[$result['frame_texture']];
        $result['frame_gender']       = $frameGender[$result['frame_gender']];
        $result['frame_size']         = $frameSize[$result['frame_size']];
        if ($result['is_open'] == 1) {
            $result['is_open'] = 'Enabled';
        } elseif ($result['is_open'] == 2) {
            $result['is_open'] = 'Disabled';
        }
        if ($result['frame_is_recipe'] == 1) { //是否可处方
            $result['frame_is_recipe'] = 1;
        } else {
            $result['frame_is_recipe'] = 0;
        }
        if ($result['frame_piece'] == 1) { //是否可夹片
            $result['frame_piece'] = 1;
        } else {
            $result['frame_piece'] = 0;
        }
        if ($type != 3) {
            if ($result['frame_is_advance'] == 1) { //是否渐进
                $result['frame_is_advance'] = "yes";
            } else {
                $result['frame_is_advance'] = "no";
            }
        } else {
            if ($result['frame_is_advance'] == 1) {
                $result['frame_is_advance'] = 1;
            } else {
                $result['frame_is_advance'] = 0;
            }
        }

        if ($result['frame_temple_is_spring'] == 1) { //镜架是否弹簧腿
            $result['frame_temple_is_spring'] = 1;
        } else {
            $result['frame_temple_is_spring'] = 0;
        }
        if ($type != 2) {
            if ($result['frame_is_adjust_nose_pad'] == 1) { //是否可以调节鼻托
                $result['frame_is_adjust_nose_pad'] = 1;
            } else {
                $result['frame_is_adjust_nose_pad'] = 0;
            }
        } else {
            if ($result['frame_is_adjust_nose_pad'] == 1) {
                $result['frame_is_adjust_nose_pad'] = 'nose_bridge';
            } else {
                $result['frame_is_adjust_nose_pad'] = 'nose_pad';
            }
        }
        return $result;
    }
    /***
     * 获取商品图片地址信息
     */
    public function getItemImagesRow($sku)
    {
        $result = $this->alias('g')->where('sku', '=', $sku)->join('item_attribute a', 'g.id=a.item_id')
            ->field('g.*,a.frame_images')->find();
        if (!$result) {
            return false;
        }
        return $result;
    }
    /***
     * 获取商品状态信息
     */
    public function getItemStatus($sku)
    {
        $result = $this->where('sku', '=', $sku)->field('sku as itemSku,item_status')->find();
        if (!$result) {
            return false;
        }
        $arr = [];
        return $arr[$result['itemSku']] = $result['item_status'];
    }

    /***
     * 获取商品状态信息
     */
    public function getGoodsInfo($sku)
    {
        $map['is_del'] = 1;
        $map['is_open']  = 1;
        $map['sku'] = $sku;
        $result = $this->where($map)->field('name,stock,occupy_stock,available_stock,real_time_qty')->find();
        return $result;
    }
    /***
     * 检测origin_sku是否存在
     */
    public function checkIsExistOriginSku($origin_sku)
    {
        $map['origin_sku'] = $origin_sku;
        $result = $this->where($map)->field('id,origin_sku')->find();
        return $result ? $result : false;
    }
    /***
     * 查找一个仓库sku是否存在
     * @param sku 平台sku
     */
    public function check_sku_qty($sku)
    {
        $where['is_open'] = 1;
        $where['is_del']  = 1;
        $where['sku']     = $sku;
        $result = $this->where($where)->field('id,sku,available_stock')->find();
        return $result;
    }
    /**
     * 查询一个通过审核之后的仓库sku是否存在
     * @param sku 仓库sku
     */
    public function pass_check_sku($sku)
    {
        $where['is_open'] = 1;
        $where['is_del']  = 1;
        $where['item_status'] = 3;
        $where['sku']     = $sku;
        $result = $this->where($where)->field('id,sku,available_stock,presell_residue_num,presell_num,presell_create_time,presell_end_time')->find();
        return $result;
    }

    /**
     * 获取仓库总库存
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getAllStock()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $where['category_id']  = ['<>', 43];
        return $this->where($where)->sum('stock');
    }

    /**
     * 获取仓库可用库存
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getAllAvailableStock()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $where['category_id']  = ['<>', 43];
        return $this->where($where)->sum('available_stock');
    }

    /**
     * 获取仓库总SKU个数
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getSkuNum()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->count(1);
    }

    /**
     * 获取仓库总库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getAllStockPrice()
    {
        $sku_pirce = new \app\admin\model\SkuPrice;
        $arr = $sku_pirce->getAllData();
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $where['category_id']  = ['<>', 43]; //不等于虚拟产品
        $res = $this->where($where)->field('sku,stock,price')->select();
        $allprice = 0;
        foreach ($res as $v) {
            if ($arr[$v['sku']]) {
                $allprice += $v['stock'] * $arr[$v['sku']];
            } else {
                $allprice += $v['stock'] * $v['price'];
            }
        }
        return $allprice;
    }

    /**
     * 获取仓库镜架总库存
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getFrameStock()
    {
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 1;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->sum('stock');
    }

    /**
     * 获取仓库镜架SKU
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getFrameSku()
    {
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 1;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->column('sku');
    }


    /**
     * 获取仓库镜架总库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getFrameStockPrice()
    {
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 1;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        //SKU实时进价
        $sku_pirce = new \app\admin\model\SkuPrice;
        $arr = $sku_pirce->getAllData();


        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $res = $this->where($where)->field('sku,stock,price')->select();
        $allprice = 0;
        foreach ($res as $v) {
            if ($arr[$v['sku']]) {
                $allprice += $v['stock'] * $arr[$v['sku']];
            } else {
                $allprice += $v['stock'] * $v['price'];
            }
        }
        return $allprice;
    }

    /**
     * 获取仓库饰品总库存
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getOrnamentsStock()
    {
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 3;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->sum('stock');
    }

    /**
     * 获取仓库饰品sku
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getOrnamentsSku()
    {
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 3;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->column('sku');
    }

    /**
     * 获取仓库饰品总库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getOrnamentsStockPrice()
    {
        //查询镜框分类有哪些
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = 3;
        $map['is_del'] = 1;
        $ids = $category->where($map)->column('id');

        //SKU实时进价
        $sku_pirce = new \app\admin\model\SkuPrice;
        $arr = $sku_pirce->getAllData();


        $where['category_id']  = ['in', $ids];
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $res = $this->where($where)->field('sku,stock,price')->select();
        $allprice = 0;
        foreach ($res as $v) {
            if ($arr[$v['sku']]) {
                $allprice += $v['stock'] * $arr[$v['sku']];
            } else {
                $allprice += $v['stock'] * $v['price'];
            }
        }
        return $allprice;
    }


    /**
     * 获取仓库样品总库存
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getSampleNumStock()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->sum('sample_num');
    }

    /**
     * 获取仓库样品总库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/02/24 16:47:21 
     * @return void
     */
    public function getSampleNumStockPrice()
    {
        //SKU实时进价
        $sku_pirce = new \app\admin\model\SkuPrice;
        $arr = $sku_pirce->getAllData();

        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $res = $this->where($where)->field('sku,sample_num,price')->select();
        $allprice = 0;
        foreach ($res as $v) {
            if ($arr[$v['sku']]) {
                $allprice += $v['sample_num'] * $arr[$v['sku']];
            } else {
                $allprice += $v['sample_num'] * $v['price'];
            }
        }
        return $allprice;
    }

    /**
     * 获取SKU单价
     *
     * @Description
     * @author wpl
     * @since 2020/03/09 10:47:11 
     * @return void
     */
    public function getSkuPrice()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        return $this->where($where)->column('purchase_price', 'sku');
    }

    /**
     * 获取新品SKU
     *
     * @Description
     * @author wpl
     * @since 2020/03/11 14:48:41 
     * @return void
     */
    public function getNewProductSku()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $where['is_new']  = 1;
        return $this->where($where)->column('sku');
    }

    /**
     * 查询商品名称、分类
     *
     * @Description
     * @author wpl
     * @since 2020/03/12 16:51:07 
     * @return void
     */
    public function getSkuInfo()
    {
        $where['a.is_del']  = 1;
        $where['a.is_open']  = 1;
        $where['b.is_del']  = 1;
        return $this->where($where)->alias('a')
            ->join(['fa_item_category' => 'b'], 'a.category_id=b.id')
            ->column('a.available_stock,a.name,b.name as type_name', 'sku');
    }

    /**
     * 产品库存分级
     *
     * @Description
     * @author wpl
     * @since 2020/03/18 15:38:11 
     * @return void
     */
    public function stockClass()
    {
        $where['is_del']  = 1;
        $where['is_open']  = 1;
        $where['category_id']  = ['<>', 43];
        $data = $this->field('sum(if(stock<0,1,0)) as a,
        sum(if(stock=0,1,0)) as b,
        sum(if(stock>=1 and stock<=10,1,0)) as c,
        sum(if(stock>=11 and stock<=30,1,0)) as d,
        sum(if(stock>=31 and stock<=50,1,0)) as e,
        sum(if(stock>=51 and stock<=70,1,0)) as f,
        sum(if(stock>=71 and stock<=100,1,0)) as g,
        sum(if(stock>=101,1,0)) as h
        ')->where($where)->select();
        $data = collection($data)->toArray();
        return $data;
    }
    /**
     * 获取不同分类的sku
     * param id 1 眼镜 3 配饰
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/18 17:37:02 
     * @param [type] $id
     * @return void
     */
    public function getDifferenceSku($id)
    {
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = $id;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        return $this->where($where)->column('sku');
    }
    /**
     * 获取不同分类sku的数量
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/20 10:19:47 
     * @param [type] $id sku分类 1 商品  3 配饰
     * @return void
     */
    public function getDifferenceSkuNUm($id)
    {
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = $id;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        return $this->where($where)->count('sku');
    }
    /**
     * 获取不同分类的sku中新品sku
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/20 13:43:33
     * @param $id  sku分类 1 商品 3 配饰 
     * @return void
     */
    public function getDifferenceNewSku($id)
    {
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = $id;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        return $this->where($where)->where(['is_new' => 1])->column('sku');
    }
    /**
     * 获取不同分类的sku中新品sku的数量
     *
     * @Description created by lsw
     * @author lsw
     * @since 2020/03/20 15:12:04 
     * @param [type] $id
     * @return void
     */
    public function getDifferenceNewSkuNum($id)
    {
        $category = new \app\admin\model\itemmanage\ItemCategory;
        $map['attribute_group_id'] = $id;
        $ids = $category->where($map)->column('id');

        $where['category_id']  = ['in', $ids];
        return $this->where($where)->where(['is_new' => 1])->count('sku');
    }

    /**
     * 获取SKU分类名称
     *
     * @Description
     * @author wpl
     * @since 2020/03/24 11:31:21 
     * @param [type] $sku
     * @return void
     */
    public function getSkuCategoryName()
    {
        $where['a.is_del']  = 1;
        $where['a.is_open']  = 1;
        return $this->alias('a')->where($where)->join(['fa_item_category' => 'b'], 'a.category_id=b.id')->column('b.name', 'sku');
    }

    /**
     * 获取实时库存
     *
     * @Description
     * @author wpl
     * @since 2020/04/25 10:26:15 
     * @param [type] $sku
     * @return void
     */
    public function getRealStock($sku)
    {
        $where['is_del']  = 1;
        $where['sku']  = $sku;
        return $this->where($where)->sum('stock-distribution_occupy_stock');
    }
}
