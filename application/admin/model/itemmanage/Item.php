<?php

namespace app\admin\model\itemmanage;

use think\Model;
use think\Db;
use app\admin\model\itemmanage\attribute\ItemAttribute;

class Item extends Model
{
    // 表名
    protected $name = 'item';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text'
    ];

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
        $result = $this->where('sku', 'like', "%{$value}%")->field('sku')->distinct(true)->limit(10)->select();
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
     * @return bool
     */
    public function getItemInfo($sku)
    {
        $result = $this->alias('m')->where('sku', '=', $sku)->join('item_attribute a', 'm.id=a.item_id')->find();
        if (!$result) {
            return false;
        }
        $colorArr = (new ItemAttribute())->getFrameColor();
        $arr = $this->alias('m')->where('origin_sku', '=', $result['origin_sku'])->join('item_attribute a', 'm.id=a.item_id')->field('m.name,a.frame_color')->select();
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                $arr[$k]['frame_color_value'] = $colorArr[$v['frame_color']];
            }
        }
        $result['itemArr'] = $arr;
        $result['itemCount'] = $this->where('origin_sku', '=', $result['origin_sku'])->count();
        return $result;
    }
    /**
     * 获取商品表SKU数据
     * @return array
     */
    public function getItemSkuInfo()
    {
        return $this->where('is_open', '=', 1)->column('sku', 'id');
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
    public function getItemRow($sku)
    {
        $result = Db::name('item')->alias('g')->where('sku','=',$sku)->join('item_attribute a','g.id=a.item_id')
            ->field('g.*,a.item_id,a.glasses_type,a.procurement_type,a.procurement_origin,a.frame_type,a.frame_width,a.frame_height,
            a.frame_length,a.frame_temple_length,a.frame_bridge,a.mirror_width,a.frame_color,a.frame_weight,a.frame_shape,a.shape,
            a.frame_texture,a.frame_gender,a.frame_size,a.frame_is_recipe,a.frame_piece,a.frame_is_advance,
            a.frame_temple_is_spring,a.frame_is_adjust_nose_pad')->find();
        if(!$result){
            return false;
        }
        //获取所有眼镜形状
        $frameShape = (new ItemAttribute())->getAllFrameShape();
        //获得所有框型
        $shape      = (new ItemAttribute())->getAllShape();
        //获取所有材质
        $texture    = (new ItemAttribute())->getAllTexture();
        //获取适合人群
        $frameGender   = (new ItemAttribute())->getFrameGender();
        //获取尺寸型号
        $frameSize     = (new ItemAttribute())->getFrameSize();
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
        if($result['is_open'] == 1){
            $result['is_open'] = 'Enabled';
        }elseif($result['is_open'] == 2){
            $result['is_open'] = 'Disabled';
        }
        if($result['frame_is_recipe'] == 1){ //是否可处方
            $result['frame_is_recipe'] = 1;
        }else{
            $result['frame_is_recipe'] = 0;
        }
        if($result['frame_piece'] == 1){ //是否可夹片
            $result['frame_piece'] = 1;
        }else{
            $result['frame_piece'] = 0;
        }
        if($result['frame_is_advance'] == 1){ //是否渐进
            $result['frame_is_advance'] = "yes";
        }else{
            $result['frame_is_advance'] = "no";
        }
        if($result['frame_temple_is_spring'] == 1){ //镜架是否弹簧腿
            $result['frame_temple_is_spring'] = 1;
        }else{
            $result['frame_temple_is_spring'] = 0;
        }
        if($result['frame_is_adjust_nose_pad'] == 1){ //是否可以调节鼻托
            $result['frame_is_adjust_nose_pad'] = 1;
        }else{
            $result['frame_is_adjust_nose_pad'] = 0;
        }
        return $result;
    }
    /***
     * 获取商品图片地址信息
     */
    public function getItemImagesRow($sku)
    {
        $result = $this->alias('g')->where('sku','=',$sku)->join('item_attribute a','g.id=a.item_id')
            ->field('g.*,a.frame_images')->find();
        if(!$result){
            return false;
        }
        return $result;
    }
    /***
     * 获取商品状态信息
     */
    public function getItemStatus($sku)
    {
        $result = $this->where('sku','=',$sku)->field('sku as itemSku,item_status')->find();
        if(!$result){
            return false;
        }
        $arr = [];
        return $arr[$result['itemSku']] = $result['item_status'];
    }
}
