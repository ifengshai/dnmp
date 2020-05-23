<?php

namespace app\admin\model\purchase;

use think\Db;
use think\Model;


class SampleLocation extends Model
{

    

    

    // 表名
    protected $name = 'purchase_sample_location';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 获取库位列表
     */
    public function getPurchaseLocationData()
    {
        $data = $this->order('id asc')->column('location', 'id');
        return $data;
    }
    /*
     * 通过id获取库位id
     * */
    public function getLocationName($location_id){
        $location_name = $this->where('id',$location_id)->value('location');
        return $location_name;
    }

    







}
