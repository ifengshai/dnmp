<?php

namespace app\admin\model\itemmanage;

use think\Model;
use app\admin\model\platformManage\ManagtoPlatform;

class ItemPlatformSku extends Model
{

    

    

    // 表名
    protected $name = 'item_platform_sku';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    //添加商品平台sku
    public function addPlatformSku($row)
    {
        $res = $this->where('sku',$row['sku'])->field('sku,name')->find();
        if($res){
            return false;
        }
        $platform = (new ManagtoPlatform())->getOrderPlatformList();
        if(!empty($platform) && is_array($platform)){
            $arr = [];
            foreach ($platform as $k =>$v){
                switch ($v){
                    case 'zeelool':
                        $prefix = 'Z';
                        break;
                    case 'voogueme':
                        $prefix = 'G';
                        break;
                    case 'nihao':
                        $prefix = 'N';
                        break;
                    case 'amazon':
                        $prefix = 'A';
                        break;
                }
                $arr[$k]['sku'] = $row['sku'];
                $arr[$k]['platform_sku'] = $prefix.$row['sku'];
                $arr[$k]['name'] = $row['name'];
                $arr[$k]['platform_type'] = $k;
                $arr[$k]['create_person'] = session('admin.nickname');
                $arr[$k]['create_time'] = date("Y-m-d H:i:s", time());
            }
            $result = $this->allowField(true)->saveAll($arr);
            return $result ? $result : false;
        }else{
            return false;
        }


    }
    /***
     * 查找平台SKU
     */
    public function likePlatformSku($sku)
    {
        $result = $this->where('platform_sku','like',"%{$sku}%")->field('platform_sku')->limit(10)->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[] = $v['platform_sku'];
        }
        return $arr;
    }
    /***
     * 查看用户输入的sku是否存在
     */
    public function getPlatformSku($platform_sku)
    {
        $result = $this->where('platform_sku','eq',$platform_sku)->field('id,platform_sku')->find();
        return $result ? $result : false;
    }
}
