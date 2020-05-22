<?php

namespace app\admin\model\purchase;

use think\Db;
use think\Model;


class SampleWorkorder extends Model
{

    

    

    // 表名
    protected $name = 'purchase_sample_workorder';
    
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
        $data = Db::name('purchase_sample_location')->order('id asc')->column('location', 'id');
        return $data;
    }
    

    







}
