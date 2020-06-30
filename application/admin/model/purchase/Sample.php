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
     * 借出,出库使用
     * 获取样品间列表 -- 条件：库存-借出数量>0
     *
     * @Description
     * @author mjj
     * @since 2020/05/25 10:31:35 
     * @return void
     */
    public function getlenddata(){
        $arr = array();
        $i = 0;
        $data = collection($this->order('id asc')->field('sku,location_id,stock,lend_num')->select())->toArray();
        foreach($data as $key=>$value){
            if($value['stock'] - $value['lend_num'] > 0){
                $arr[$i]['sku'] = $value['sku'];
                $arr[$i]['location'] = Db::name('purchase_sample_location')->where('id',$value['location_id'])->value('location');
                $i++;
            }
        }
        return $arr;
    }
    /**
     * 通过sku获取库位号
     *
     * @Description
     * @author mjj
     * @since 2020/06/06 09:53:35 
     * @param [type] $sku
     * @return void
     */
    public function getlocation($sku){
        $location = $this->alias('s')->join('fa_purchase_sample_location l','s.location_id=l.id')->where('s.sku',$sku)->value('location');
        return $location;
    }
    

    







}
