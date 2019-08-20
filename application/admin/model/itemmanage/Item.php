<?php

namespace app\admin\model\itemmanage;

use think\Model;
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
        return $this->hasOne('app\admin\model\itemmanage\attribute\ItemAttribute','item_id','id');
    }
    



    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    /***
     * 获取随机的SKU编码
     */
    public function getOriginSku()
    {
        return  rand(0,99).rand(0,99).rand(0,99);
    }

    /***
     * 模糊查询已经存在的源sku
     */
    public function likeOriginSku($value)
    {
        $result = $this->where('sku','like',"%{$value}%")->field('sku')->distinct(true)->limit(10)->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach ($result as $k=>$v){
            $arr[] = $v['sku'];
        }
        return $arr;
    }

    /***
     * 查询sku信息
     * @param $sku
     * @return bool
     */
    public function getItemInfo($sku){
        $result = $this->alias('m')->where('sku','=',$sku)->join('item_attribute a','m.id=a.item_id')->find();
        if(!$result){
            return false;
        }
        $colorArr =(new ItemAttribute())->getFrameColor();
        $arr = $this->alias('m')->where('origin_sku','=',$result['origin_sku'])->join('item_attribute a','m.id=a.item_id')->field('m.name,a.frame_color')->select();
        if(is_array($arr)){
            foreach($arr as $k =>$v){
                $arr[$k]['frame_color_value'] = $colorArr[$v['frame_color']];
            }
        }
        $result['itemArr'] = $arr;
        return $result;
    }
}
