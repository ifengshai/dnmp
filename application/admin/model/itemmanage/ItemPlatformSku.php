<?php

namespace app\admin\model\itemmanage;

use think\Db;
use think\Model;
use app\admin\model\platformmanage\MagentoPlatform;

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
    protected $append = [];
    //关联item
    public function item()
    {
        return $this->belongsTo('app\admin\model\itemmanage\Item', 'sku', 'sku');
    }
    //添加商品平台sku
    public function addPlatformSku($row)
    {
        // $res = $this->where('sku',$row['sku'])->field('sku,name')->find();
        // if($res){
        //     return false;
        // }
        $platform = (new MagentoPlatform())->getOrderPlatformList();
        if (!empty($platform) && is_array($platform)) {
            $arr = [];
            foreach ($platform as $k => $v) {
                switch ($v) {
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
                $arr[$k]['platform_sku'] = $prefix . $row['sku'];
                $arr[$k]['name'] = $row['name'];
                $arr[$k]['platform_type'] = $k;
                $arr[$k]['create_person'] = session('admin.nickname') ? session('admin.nickname') : 'Admin';
                $arr[$k]['create_time'] = date("Y-m-d H:i:s", time());
            }
            $result = $this->allowField(true)->saveAll($arr);
            return $result ? $result : false;
        } else {
            return false;
        }
    }
    /***
     * 查找平台SKU
     */
    public function likePlatformSku($sku)
    {
        $result = $this->where('platform_sku', 'like', "%{$sku}%")->field('platform_sku')->limit(10)->select();
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
        $result = $this->where('platform_sku', 'eq', $platform_sku)->field('id,platform_sku')->find();
        return $result ? $result : false;
    }
    /***
     * 查看平台sku对应的平台信息(原先，没有分库之前)
     */
    public function findItemPlatform_yuan($id)
    {
        $result = $this->alias('g')->where('g.id', '=', $id)->join('magento_platform p', 'g.platform_type=p.id')
            ->field('g.id,g.sku,g.platform_sku,g.platform_type,g.magento_id,g.is_upload,g.is_upload_images,g.uploaded_images,p.id as platform_id,p.status,p.magento_account,p.magento_key,p.magento_url,p.is_upload_item,p.is_del,p.name,p.item_attr_name,p.item_type,p.upload_field')->find();
        return $result ? $result : false;
    }
    /***
     * 查看平台sku对应的平台信息(分库之后)
     */
    public function findItemPlatform($id)
    {
        $resultItem = $this->where('id', '=', $id)->field('id,sku,platform_sku,platform_type,magento_id,is_upload,is_upload_images,uploaded_images')->find();
        if (!$resultItem) {
            return false;
        }
        $resultPlatform = Db::name('magento_platform')->where('id', '=', $resultItem['platform_type'])->field('id as platform_id,status,magento_account,magento_key,magento_url,is_upload_item,is_del,name,item_attr_name,item_type,upload_field')->find();
        if (!$resultPlatform) {
            return $resultItem;
        }
        $resultItem['platform_id']      = $resultPlatform['platform_id'];
        $resultItem['status']           = $resultPlatform['status'];
        $resultItem['magento_account']  = $resultPlatform['magento_account'];
        $resultItem['magento_key']      = $resultPlatform['magento_key'];
        $resultItem['magento_url']      = $resultPlatform['magento_url'];
        $resultItem['is_upload_item']   = $resultPlatform['is_upload_item'];
        $resultItem['is_del']           = $resultPlatform['is_del'];
        $resultItem['name']             = $resultPlatform['name'];
        $resultItem['item_attr_name']   = $resultPlatform['item_attr_name'];
        $resultItem['item_type']        = $resultPlatform['item_type'];
        $resultItem['upload_field']     = $resultPlatform['upload_field'];
        return $resultItem;
    }
    /**
     * 查询平台sku库存是否充足
     */
    public function check_platform_sku_qty($change_sku,$order_platform)
    {
        $where['platform_sku'] = $change_sku;
        $where['platform_type'] = $order_platform;
        $where['is_open'] = 1;
        $where['is_del']  = 1;
        $result = $this->alias('g')->join('item m','g.sku=m.sku')->where($where)->field('m.id,m.sku,m.available_stock')->find();
        return $result;
    }

    /**
     * 根据平台SKU查出仓库SKU
     * @param $sku 平台SKU
     * @param $platform_type 对应平台
     * @return string
     */
    public function getTrueSku($sku = '', $platform_type = '')
    { 
        $map['platform_sku'] = $sku;
        $map['platform_type'] = $platform_type;
        return $this->where($map)->value('sku');
    }
}
