<?php

namespace app\admin\model\purchase;

use think\Model;


class Supplier extends Model
{

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'supplier';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    /**
     * 获取供应商
     */
    public function getSupplierData()
    {
        $data = $this->field('id,supplier_name')->where('status', 1)->select();
        $arr = [];
        foreach ($data as $v) {
            $arr[$v['id']] = $v['supplier_name'];
        }
        return $arr;
    }

    /**
     * 查询供应商id
     */
    public function getSupplierId($name = '')
    {
        if ($name) {
            $map['supplier_name'] = ['like', '%' . $name . '%'];
            $map['status'] = 1;
            return $this->where($map)->value('id');
        }
        return false;
    }
}
