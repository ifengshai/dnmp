<?php

namespace app\admin\model\itemmanage;
use think\Db;
use think\Model;
use app\admin\model\platformmanage\ManagtoPlatform;

class ItemPlatformSku extends Model
{

    //制定数据库连接
    protected $connection = 'database.db_stock';
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
    //关联item
    public function item()
    {
        return $this->belongsTo('app\admin\model\itemmanage\Item', 'sku', 'sku');
    }
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
    /***
     * 查看平台sku对应的平台信息(原先，没有分库之前)
     */
    public function findItemPlatform_yuan($id)
    {
        $result = $this->alias('g')->where('g.id','=',$id)->join('managto_platform p','g.platform_type=p.id')
                 ->field('g.id,g.sku,g.platform_sku,g.platform_type,g.magento_id,g.is_upload,g.is_upload_images,g.uploaded_images,p.id as platform_id,p.status,p.managto_account,p.managto_key,p.managto_url,p.is_upload_item,p.is_del,p.name,p.item_attr_name,p.item_type,p.upload_field')->find();
        return $result ? $result : false;
    }
    /***
     * 查看平台sku对应的平台信息(分库之后)
     */
    public function findItemPlatform($id)
    {
        $resultItem = $this->where('id','=',$id)->field('id,sku,platform_sku,platform_type,magento_id,is_upload,is_upload_images,uploaded_images')->find();
        if(!$resultItem){
            return false;
        }
        $resultPlatform = Db::name('managto_platform')->where('id','=',$resultItem['platform_type'])->field('id as platform_id,status,managto_account,managto_key,managto_url,is_upload_item,is_del,name,item_attr_name,item_type,upload_field')->find();
        if(!$resultPlatform){
            return $resultItem;
        }
        $resultItem['platform_id']      = $resultPlatform['platform_id'];
        $resultItem['status']           = $resultPlatform['status'];
        $resultItem['managto_account']  = $resultPlatform['managto_account'];
        $resultItem['managto_key']      = $resultPlatform['managto_key'];
        $resultItem['managto_url']      = $resultPlatform['managto_url'];
        $resultItem['is_upload_item']   = $resultPlatform['is_upload_item'];
        $resultItem['is_del']           = $resultPlatform['is_del'];
        $resultItem['name']             = $resultPlatform['name'];
        $resultItem['item_attr_name']   = $resultPlatform['item_attr_name'];
        $resultItem['item_type']        = $resultPlatform['item_type'];
        $resultItem['upload_field']     = $resultPlatform['upload_field'];
        return $resultItem;
    }

}
