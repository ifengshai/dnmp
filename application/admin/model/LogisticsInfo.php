<?php

namespace app\admin\model;

use think\Model;


class LogisticsInfo extends Model
{
    // 表名
    protected $name = 'logistics_info';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 添加物流单信息
     */
    public function addLogisticsInfo($params)
    {
        //查询物流汇总表是否已存在此关联单号记录
        $res = $this->where('order_number', $params['order_number'])->find();
        if ($res) {
            $list['id'] = $res['id'];
            
            $this->where($list)->update(['logistics_number' => $params['logistics_number']]);

        } else {
            $params['createtime'] = date('Y-m-d H:i:s', time());
            $params['create_person'] = session('admin.nickname');
            $this->allowField(true)->isUpdate(false)->data($params)->save();
        }
    }
}
