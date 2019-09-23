<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;


class InfoSynergyTaskChangeSku extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task_change_sku';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    public function getChangeSkuList($tid)
    {
        $result = $this->where('tid','=',$tid)->select();
        if(!$result){
            return false;
        }
          foreach ($result as $k =>$v){
            $result[$k]['option'] = unserialize($v['options']);
          }
          return $result;
    }

    







}
