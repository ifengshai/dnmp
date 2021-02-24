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
        //总库存
        $data['aa_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['aa_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['aa_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');

        //A等级
        $where['grade'] = 'A';
        $skus = $this->where($where)->column('true_sku');
        $data['a_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['a_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['a_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');

        //B等级
        $where['grade'] = 'B';
        $skus = $this->where($where)->column('true_sku');
        $data['b_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['b_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['b_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');

        //C+等级
        $where['grade'] = 'C+';
        $skus = $this->where($where)->column('true_sku');
        $data['ca_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['ca_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['ca_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');


        //C等级
        $where['grade'] = 'C';
        $skus = $this->where($where)->column('true_sku');
        $data['c_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['c_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['c_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');



        //D等级
        $where['grade'] = 'D';
        $skus = $this->where($where)->column('true_sku');
        $data['d_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['d_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['d_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');

        //E等级
        $where['grade'] = 'E';
        $skus = $this->where($where)->column('true_sku');
        $data['e_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['e_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');
        //库存金额
        $data['e_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');

        //F等级
        $where['grade'] = 'F';
        $skus = $this->where($where)->column('true_sku');
        $data['f_stock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock');
        $data['f_realstock_num'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->value('sum(stock)-sum(distribution_occupy_stock) as result');

        //库存金额
        $data['f_stock_price'] = $item->where(['sku' => ['in', $skus], 'is_del' => 1])->sum('stock*purchase_price');


        return $data;
    }
}
