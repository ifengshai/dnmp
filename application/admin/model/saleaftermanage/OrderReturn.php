<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class OrderReturn extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'order_return';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    //关联模型
    public function saleAfterIssue()
    {
        return $this->belongsTo('sale_after_issue', 'issue_id')->setEagerlyType(0);
    }

    /**
     * 获取退货单单号
     */
    public function getOrderReturnData()
    {
        $map['is_del'] = 1;
        $map['order_status'] = 2;
        return $this->where($map)->column('return_order_number','id');
    }
    







}
