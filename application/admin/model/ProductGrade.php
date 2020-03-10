<?php

namespace app\admin\model;

use think\Model;


class ProductGrade extends Model
{
    // 表名
    protected $name = 'product_grade';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 获取不同等级sku库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/10 13:54:34 
     * @return void
     */
    public function getSkuStock()
    {
        $item = new \app\admin\model\itemmanage\Item();

        //A+等级
        $where['grade'] = 'A+';
        $skus = $this->where($where)->column('true_sku');
        $data['aa_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        
        $data['aa_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');



        //A等级
        $where['grade'] = 'A';
        $skus = $this->where($where)->column('true_sku');
        $data['a_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');

        //B等级
        $where['grade'] = 'B';
        $skus = $this->where($where)->column('true_sku');
        $data['b_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');

        //C+等级
        $where['grade'] = 'C+';
        $skus = $this->where($where)->column('true_sku');
        $data['ca_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');

        //C等级
        $where['grade'] = 'C';
        $skus = $this->where($where)->column('true_sku');
        $data['c_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');


        //D等级
        $where['grade'] = 'D';
        $skus = $this->where($where)->column('true_sku');
        $data['d_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');

        //E等级
        $where['grade'] = 'E';
        $skus = $this->where($where)->column('true_sku');
        $data['e_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');

        //F等级
        $where['grade'] = 'F';
        $skus = $this->where($where)->column('true_sku');
        $data['f_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');

        return $data;
    }
}
