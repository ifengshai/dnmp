<?php

namespace app\admin\model\platformManage;

use think\Model;


class ManagtoPlatform extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'managto_platform';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function getOrderPlatformList()
    {
        $result = $this->where('status','=',1)->field('id,name')->select();
        if(!$result){
            return [0=>'请先添加平台'];
        }
        $arr = [];
        foreach($result as $key=>$val){
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }









}
