<?php

namespace app\admin\controller\shell\supply;

use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\SkuSalesNum;
use app\admin\model\supplydatacenter\DullStockSite;
use app\enum\Site;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 供应链呆滞数据同步
 *
 * Class SupplyDullDataSync
 * @package app\admin\controller\shell\supply
 * @author fangke
 * @date   5/18/21 3:10 PM
 */
class SupplyDullDataSync extends Command
{
    protected function configure()
    {
        $this->setName('supply_dull_data_sync')
            ->setDescription('供应链呆滞数据同步');
    }

    protected function execute(Input $input, Output $output)
    {
        dump("start: ".date('Y-m-d H:i:s'));
        $itemPrices = Item::column('purchase_price', 'sku');
        dump(1);
        $itemPlatformSkus = ItemPlatformSku::hasWhere('category', [], 'ItemCategory.attribute_group_id')
            ->with('category')
            ->whereIn('platform_type',
                [
                    Site::ZEELOOL, Site::VOOGUEME, Site::NIHAO, Site::ZEELOOL_DE, Site::ZEELOOL_JP,
                    Site::WESEEOPTICAL
                ])
            ->field('sku,platform_type,stock,grade')
            ->select();
        $itemPlatformSkus = collection($itemPlatformSkus)->toArray();
        $skuCategories = array_column($itemPlatformSkus, null, 'sku');

        dump(2);
        $result = [];
        foreach ($itemPlatformSkus as $k => $platformSkus) {
            echo $k.PHP_EOL;
            $validDateAndStock = $this->getSkuValidDateAndStock($platformSkus['sku'],
                $platformSkus['platform_type']);
            $turnoverDays = 0;
            if ($validDateAndStock['sales_num'] > 0 && $validDateAndStock['days'] > 0 && $platformSkus['stock'] > 0) {
                $averageSalesFor30days = bcdiv($validDateAndStock['sales_num'], $validDateAndStock['days'], 8);
                $turnoverDays = bcdiv($platformSkus['stock'], $averageSalesFor30days, 8);
            }

//            该SKU相关数据
            $item = [
                'sku' => $platformSkus['sku'],
                'stock' => $platformSkus['stock'],
                'turnover_days' => $turnoverDays
            ];
//            呆滞库存
            if ($turnoverDays > 120) {
                $item['dull_stock'] = $platformSkus['stock'];
//                    呆滞价格
                $item['price'] = bcmul($itemPrices[$platformSkus['sku']], $platformSkus['stock'], 2);
//                    镜框呆滞
                if ($skuCategories[$platformSkus['sku']]['attribute_group_id'] == 1) {
                    $item['frame_dull_stock'] = $platformSkus['stock'];
                }
//                    饰品呆滞
                if ($skuCategories[$platformSkus['sku']]['attribute_group_id'] == 3) {
                    $item['acc_dull_stock'] = $platformSkus['stock'];
                }
            }
//            高风险呆滞库存、F级SKU
            if ($turnoverDays > 168 || $platformSkus['grade'] == 'F') {
                $item['high_risk_dull_stock'] = $platformSkus['stock'];
            } else {
//                低风险呆滞库存
                if ($turnoverDays > 120 && $turnoverDays <= 144) {
                    $item['low_risk_dull_stock'] = $platformSkus['stock'];
                } else {
//                    中风险呆滞库存
                    if ($turnoverDays > 144 && $turnoverDays <= 168) {
                        $item['medium_risk_dull_stock'] = $platformSkus['stock'];
                    }
                }
            }
            $result[$platformSkus['platform_type']][$platformSkus['grade']][] = $item;
        }

        dump(3);
        $items = [];
        $date = date('Y-m-d', time());
        $sort = ['A+' => 1, 'A' => 2, 'B' => 3, 'C+' => 4, 'C' => 5, 'D' => 6, 'E' => 7, 'F' => 8, 'Z' => 9];

        foreach ($result as $platformType => $grades) {
            $data = [];
            foreach ($grades as $grade => $skus) {
                $item = [
                    'site' => $platformType,
                    'day_date' => $date,
                    'grade' => $grade,
                    'sort' => $sort[$grade],
                    'price' => 0,
                    'sku_num' => 0,
                    'sku_ratio' => 0,
                    'stock' => 0,
                    'stock_ratio' => 0,
                    'dull_stock' => 0,
                    'dull_stock_sku_num' => 0,
                    'dull_stock_ratio' => 0,
                    'high_risk_dull_stock' => 0,
                    'high_risk_dull_stock_sku' => 0,
                    'medium_risk_dull_stock' => 0,
                    'medium_risk_dull_stock_sku' => 0,
                    'low_risk_dull_stock' => 0,
                    'low_risk_dull_stock_sku' => 0,
                    'frame_dull_stock' => 0,
                    'frame_dull_stock_ratio' => 0,
                    'acc_dull_stock' => 0,
                    'acc_dull_stock_ratio' => 0,
                ];

                $skuNum = count($skus);
                $stock = array_sum(array_column($skus, 'stock'));
                $dullStocks = array_column($skus, 'dull_stock');
                $dullStockSkuNum = count($dullStocks);
                $dullStock = array_sum($dullStocks);
                $highRiskDullStocks = array_column($skus, 'high_risk_dull_stock');
                $highRiskDullStockSkuNum = count($highRiskDullStocks);
                $highRiskDullStock = array_sum($highRiskDullStocks);

                $mediumRiskDullStocks = array_column($skus, 'medium_risk_dull_stock');
                $mediumRiskDullStockSkuNum = count($mediumRiskDullStocks);
                $mediumRiskDullStock = array_sum($mediumRiskDullStocks);

                $lowRiskDullStocks = array_column($skus, 'low_risk_dull_stock');
                $lowRiskDullStockSkuNum = count($lowRiskDullStocks);
                $lowRiskDullStock = array_sum($lowRiskDullStocks);

                $frameDullStocks = array_column($skus, 'frame_dull_stock');
                $frameDullStock = array_sum($frameDullStocks);
                $accDullStocks = array_column($skus, 'acc_dull_stock');
                $accDullStock = array_sum($accDullStocks);
                $prices = array_column($skus, 'price');
                $priceSum = array_sum($prices);

                $item['sku_num'] = $skuNum;
                $item['stock'] = $stock;
                $item['dull_stock'] = $dullStock;
                $item['dull_stock_sku_num'] = $dullStockSkuNum;
                $item['high_risk_dull_stock'] = $highRiskDullStock;
                $item['high_risk_dull_stock_sku'] = $highRiskDullStockSkuNum;
                $item['medium_risk_dull_stock'] = $mediumRiskDullStock;
                $item['medium_risk_dull_stock_sku'] = $mediumRiskDullStockSkuNum;
                $item['low_risk_dull_stock'] = $lowRiskDullStock;
                $item['low_risk_dull_stock_sku'] = $lowRiskDullStockSkuNum;
                $item['frame_dull_stock'] = $frameDullStock;
                $item['acc_dull_stock'] = $accDullStock;
                $item['price'] = floatval($priceSum);

                $data[] = $item;
            }

            $allSku = array_sum(array_column($data, 'sku_num'));
            $allStock = array_sum(array_column($data, 'stock'));
            $allDullStock = array_sum(array_column($data, 'dull_stock'));
            $allDullStockSku = array_sum(array_column($data, 'dull_stock_sku_num'));
            $allLowRiskDullStockSkuStock = array_sum(array_column($data, 'low_risk_dull_stock'));
            $allLowRiskDullStockSku = array_sum(array_column($data, 'low_risk_dull_stock_sku'));
            $allMediumRiskDullStockSkuStock = array_sum(array_column($data, 'medium_risk_dull_stock'));
            $allMediumRiskDullStockSku = array_sum(array_column($data, 'medium_risk_dull_stock_sku'));
            $allHighRiskDullStockSkuStock = array_sum(array_column($data, 'high_risk_dull_stock'));
            $allHighRiskDullStockSku = array_sum(array_column($data, 'high_risk_dull_stock_sku'));
            $allFrameDullStock = array_sum(array_column($data, 'frame_dull_stock'));
            $allAccDullStock = array_sum(array_column($data, 'acc_dull_stock'));
            $allPrice = array_sum(array_column($data, 'price'));

            $data = array_map(function ($value) use (
                $allFrameDullStock,
                $allAccDullStock,
                $allDullStock,
                $allStock,
                $allSku
            ) {
//                dump([$item, $allStock, $allSku, $allDullStock, $allFrameDullStock, $allAccDullStock]);
                $value['sku_ratio'] = bcmul(bcdiv($value['sku_num'], $allSku, 8), 100, 2);
                $value['stock_ratio'] = bcmul(bcdiv($value['stock'], $allStock, 8), 100, 2);
                $value['dull_stock_ratio'] = bcmul(bcdiv($value['dull_stock'], $allDullStock, 8), 100, 2);
                if ($allFrameDullStock > 0 && $value['frame_dull_stock'] > 0) {
                    $value['frame_dull_stock_ratio'] = bcmul(bcdiv($value['frame_dull_stock'], $allFrameDullStock,
                        8),
                        100, 2);
                }
                if ($allFrameDullStock > 0 && $value['acc_dull_stock'] > 0) {
                    $value['acc_dull_stock_ratio'] = bcmul(bcdiv($value['acc_dull_stock'], $allAccDullStock, 8),
                        100,
                        2);
                }

                return $value;
            }, $data);
            $data[] = [
                'site' => $platformType,
                'day_date' => $date,
                'grade' => 'Z',
                'sort' => $sort['Z'],
                'price' => $allPrice,
                'sku_num' => $allSku,
                'sku_ratio' => 100,
                'stock' => $allStock,
                'stock_ratio' => 100,
                'dull_stock' => $allDullStock,
                'dull_stock_sku_num' => $allDullStockSku,
                'dull_stock_ratio' => 100,
                'high_risk_dull_stock' => $allHighRiskDullStockSkuStock,
                'high_risk_dull_stock_sku' => $allHighRiskDullStockSku,
                'medium_risk_dull_stock' => $allMediumRiskDullStockSkuStock,
                'medium_risk_dull_stock_sku' => $allMediumRiskDullStockSku,
                'low_risk_dull_stock' => $allLowRiskDullStockSkuStock,
                'low_risk_dull_stock_sku' => $allLowRiskDullStockSku,
                'frame_dull_stock' => $allFrameDullStock,
                'frame_dull_stock_ratio' => 100,
                'acc_dull_stock' => $allAccDullStock,
                'acc_dull_stock_ratio' => 100,
            ];

            $defaultGrades = ['A+', 'A', 'B', 'C+', 'C', 'D', 'E', 'F', 'Z'];
            $grades = array_column($data, 'grade');
            $diffGrades = array_diff($defaultGrades, $grades);
            foreach ($diffGrades as $grade) {
                $item = [
                    'site' => $platformType,
                    'day_date' => $date,
                    'grade' => $grade,
                    'sort' => $sort[$grade],
                    'price' => 0,
                    'sku_num' => 0,
                    'sku_ratio' => 0,
                    'stock' => 0,
                    'stock_ratio' => 0,
                    'dull_stock' => 0,
                    'dull_stock_sku_num' => 0,
                    'dull_stock_ratio' => 0,
                    'high_risk_dull_stock' => 0,
                    'high_risk_dull_stock_sku' => 0,
                    'medium_risk_dull_stock' => 0,
                    'medium_risk_dull_stock_sku' => 0,
                    'low_risk_dull_stock' => 0,
                    'low_risk_dull_stock_sku' => 0,
                    'frame_dull_stock' => 0,
                    'frame_dull_stock_ratio' => 0,
                    'acc_dull_stock' => 0,
                    'acc_dull_stock_ratio' => 0,
                ];

                $data[] = $item;
            }

            $items = array_merge($items, $data);
        }
        dump("end: ".date('Y-m-d H:i:s'));
        file_put_contents('a.json', json_encode($items));
        DullStockSite::insertAll($items);
    }

    /**
     * 查询sku的有效天数的销量和有效天数
     *
     * @param $sku
     * @param $site
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author fangke
     * @date   5/18/21 4:28 PM
     */
    public function getSkuValidDateAndStock($sku, $site): array
    {
        $date = date('Y-m-d');
        $skuSalesNums = SkuSalesNum::field('sales_num')
            ->where('createtime', '<', $date)
            ->where('sku', '=', $sku)
            ->where('site', '=', $site)
            ->limit(30)
            ->order('createtime', 'desc')
            ->select();

        $data['sales_num'] = array_sum(array_column(collection($skuSalesNums)->toArray(), 'sales_num'));
        $days = count($skuSalesNums);
        $data['days'] = $days > 30 ? 30 : $days;

        return $data;
    }
}