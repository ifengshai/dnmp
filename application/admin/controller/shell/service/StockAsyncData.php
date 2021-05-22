<?php

namespace app\admin\controller\shell\service;

use app\admin\model\itemmanage\Item;
use app\admin\model\SkuStockLog;
use app\admin\model\warehouse\ProductBarCodeItem;
use think\console\Command;
use think\console\command\make\Model;
use think\console\Input;
use think\console\Output;

class StockAsyncData extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('stock_data')
            ->setDescription('记录每天总库存');
    }

    protected function execute(Input $input, Output $output)
    {
        $productBarCode = new ProductBarCodeItem();
        //计算每个SKU实时库存金额
        $data = $productBarCode
            ->field('a.sku,sum(if(actual_purchase_price>0,actual_purchase_price,purchase_price)) as money')
            ->alias('a')
            ->where(['library_status' => 1])
            ->where('location_code_id != 0')
            ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id = b.purchase_id and a.sku = b.sku')
            ->group('a.sku')
            ->select();
        $skuMoneyData = [];
        foreach ($data as $k => $v) {
            $skuMoneyData[$v['sku']] = $v['money'];
        }

        //查询配货占用库存
        $item = new Item();
        $skus = $item
            ->where(['is_del' => 1, 'is_open' => 1, 'category_id' => ['<>', 43]])
            ->column('distribution_occupy_stock', 'sku');


        //统计sku实时库存
        $list = $productBarCode
            ->field('sku,count(1) as all_stock')
            ->where(['library_status' => 1])
            ->where('location_code_id != 0')
            ->group('sku')
            ->select();

        foreach ($list as $k => &$v) {
            $v['stock_money'] = $skuMoneyData[$v['sku']] ?: 0;
            $v['distribution_occupy_stock'] = $skus[$v['sku']] ?: 0;
            $v['created_at'] = time();
            $v['updated_at'] = time();
        }
        unset($v);
        if (!empty($list)) {
            $list = collection($list)->toArray();
            (new SkuStockLog())->saveAll($list);
        }

        $output->writeln("All is ok");
    }
}