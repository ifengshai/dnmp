<?php

namespace app\admin\model\saleAfterManage;

use think\Model;


class SaleAfterTaskRemark extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'sale_after_task_remark';
    
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
     * 获取任务关联的记录
     * @param id  任务的ID
     */
    public function getRelevanceRecord($id)
    {
        return $this->where('tid','=',$id)->select();
    }

    







}
