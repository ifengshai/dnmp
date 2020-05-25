<?php

namespace app\admin\model\purchase;

use think\Model;
use think\Db;

class Sample extends Model
{

    

    

    // 表名
    protected $name = 'purchase_sample';
    
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
     * 获取样品间列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/25 10:31:35 
     * @return void
     */
    public function getlenddata(){
        $data = collection($this->order('id asc')->field('sku,location_id')->select())->toArray();
        foreach($data as $key=>$value){
            $data[$key]['location'] = Db::name('purchase_sample_location')->where('id',$value['location_id'])->value('location');
        }
        return $data;
    }
    

    







}
