<?php

namespace app\admin\model\itemmanage;

use think\Model;


class Item_presell_log extends Model
{

    

    //制定数据库连接
    protected $connection = 'database.db_stock';

    // 表名
    protected $name = 'item_presell_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    /***
     * 根据sku的ID获取预售历史记录
     * @param sku  预售的SKU
     */
    public function getHistoryRecord($sku)
    {
        $where['sku'] = $sku;
        return $this->where($where)->select();

    }

    /**
     * 添加预售日志
     *
     * @Description
     * @author wpl
     * @since 2020/07/21 15:39:40 
     * @param array $params
     * @return void
     */
    public function setData($params = [])
    {
        if ($params) {
            $params['create_time'] = date('Y-m-d H:i:s');
            $params['create_person'] = session('admin.nickname');
            return $this->allowField(true)->save($params);
        }
        return false;
    }

}
