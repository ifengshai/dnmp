<?php

namespace app\admin\model\platformManage;

use think\Model;


class PlatformMap extends Model
{

    

    

    // 表名
    protected $name = 'platform_map';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 得到每个平台映射的字段
     */
    public function getPlatformMap($platformId)
    {
        $result = $this->where('platform_id','=',$platformId)->field('platform_field,magento_field')->select();
        if(!$result){
            return false;
        }
        $arr = [];
        foreach ($result as $k =>$v){
            $arr[$v['platform_field']] = $v['magento_field'];
        }
        return $arr;
    }

    







}
