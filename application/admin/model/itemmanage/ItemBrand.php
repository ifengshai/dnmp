<?php

namespace app\admin\model\itemmanage;

use think\Model;


class ItemBrand extends Model
{
 
    //制定数据库连接
    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'item_brand';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function isPutAway()
    {
        return [1=>'启用',0=>'禁用'];
    }

    /***
     * 得到商品品牌列表
     */
    public function getBrandList()
    {
        $where['is_del'] = 1;
        $where['status'] = 1;
        $result = $this->where($where)->field('id,name_cn')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        $arr[0] = '无';
        foreach($result as $k=>$v){
            $arr[$v['id']] = $v['name_cn'];
        }
        return $arr;
    }
    /***
     * 商品列表得到商品品牌列表
     * 
     */
    public function getBrandToItemList()
    {
        $result = $this->field('id,name_cn')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        $arr[0] = '无';
        foreach($result as $k=>$v){
            $arr[$v['id']] = $v['name_cn'];
        }
        return $arr;
    }
    







}
