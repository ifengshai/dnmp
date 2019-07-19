<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;


class InfoSynergyTaskRemark extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task_remark';
    
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
     * 根据id获取关联的备注信息
     */
    public function getSynergyTaskRemarkById($id){
        return $this->where('tid','=',$id)->select();
    }






}
