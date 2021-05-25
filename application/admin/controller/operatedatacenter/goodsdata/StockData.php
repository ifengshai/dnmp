<?php

namespace app\admin\controller\operatedatacenter\goodsdata;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\order\order\NewOrderItemOption;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\supplydatacenter\DullStockSite;
use app\common\controller\Backend;
use think\Request;

/**
 * Class StockData
 * @package app\admin\controller\operatedatacenter\goodsdata
 * @author fangke
 * @date   5/17/21 11:02 AM
 */
class StockData extends Backend
{
    /**
     * @var MagentoPlatform
     * @author fangke
     * @date   5/17/21 11:28 AM
     */
    private $magentoPlatform;

    /**
     * @author fangke
     * @date   5/17/21 11:28 AM
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->magentoPlatform = new MagentoPlatform();
    }

    /**
     * 商品仪表-库存数据
     *
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author fangke
     * @date   5/17/21 11:34 AM
     */
    public function index()
    {
        //查询对应平台权限
        $magentoPlatforms = $this->magentoPlatform->getAuthSite([
            'zeelool', 'voogueme', 'nihao', 'wesee', 'zeelool_de', 'zeelool_jp'
        ]);

        $this->view->assign('magentoPlatforms', $magentoPlatforms);
        return $this->fetch();
    }

    /**
     * 获取库存概况
     *
     * @param  Request  $request
     * @author fangke
     * @date   5/17/21 2:40 PM
     */
    public function getStockOverView(Request $request)
    {
        $platform = $request->post('platform', 1);

        $skus = ItemPlatformSku::hasWhere('category', ['attribute_group_id' => ['in', [1, 3]]],
            'ItemCategory.attribute_group_id')
            ->with('category')
            ->where('platform_type', '=', $platform)
            ->field('stock,platform_sku')
            ->select();

        $frameGroupID = 1;
        $accGroupID = 3;
        $frameSkus = [];
        $accSkus = [];

        foreach ($skus as $sku) {
            switch ($sku['attribute_group_id']) {
                case $frameGroupID:
                    $frameSkus[] = $sku;
                    break;
                case $accGroupID:
                    $accSkus[] = $sku;
                    break;
            }
        }

//        SKU个数
        $frameSkuNum = count($frameSkus);
        $accSkuNum = count($accSkus);
        $allSkuNum = intval(bcadd($frameSkuNum, $accSkuNum));
//        库存量
        $frameStockNum = array_sum(array_column($frameSkus, 'stock'));
        $accStockNum = array_sum(array_column($accSkus, 'stock'));;
        $allStockNum = intval(bcadd($frameStockNum, $accStockNum));

        $framePlatformSkus = array_column($frameSkus, 'platform_sku');
        $accPlatformSkus = array_column($accSkus, 'platform_sku');

//        近30天销量
        $frameSalesLast30Days = 0;
        $accSalesLast30Days = 0;

        $startTime = strtotime(date('Y-m-d', strtotime("-30 day")));
        $endTime = strtotime(date('Y-m-d'));
        $orderItems = NewOrderItemOption::hasWhere('newOrder', [
            'status' => [
                'in', [
                    'free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete',
                    'delivered'
                ]
            ],
            'site' => ['=', $platform],
            'created_at' => ['between', [$startTime, $endTime]]
        ], 'NewOrder.id,NewOrder.status,NewOrder.site,NewOrder.created_at')
            ->field('NewOrderItemOption.order_id,NewOrderItemOption.sku,NewOrderItemOption.qty')
            ->whereIn('NewOrderItemOption.sku', array_merge($framePlatformSkus, $accPlatformSkus))
            ->select();

        foreach ($orderItems as $orderItem) {
            if (in_array($orderItem['sku'], $framePlatformSkus)) {
                $frameSalesLast30Days = bcadd($frameSalesLast30Days, $orderItem['qty']);
            }
            if (in_array($orderItem['sku'], $accPlatformSkus)) {
                $accSalesLast30Days = bcadd($accSalesLast30Days, $orderItem['qty']);
            }
        }
        $allSalesLast30Days = bcadd($frameSalesLast30Days, $accSalesLast30Days);

        $frameTurnoverMonths = 0;
        $accTurnoverMonths = 0;
        $allTurnoverMonths = 0;
        if ($frameStockNum > 0 && $frameSalesLast30Days > 0) {
            $frameTurnoverMonths = bcdiv($frameStockNum, $frameSalesLast30Days, 1);
        }
        if ($accStockNum > 0 && $accSalesLast30Days > 0) {
            $accTurnoverMonths = bcdiv($accStockNum, $accSalesLast30Days, 1);
        }
        if ($allStockNum > 0 && $allSalesLast30Days > 0) {
            $allTurnoverMonths = bcdiv($allStockNum, $allSalesLast30Days, 1);
        }

        //呆滞库存量
        $dullStocks = DullStockSite::where('site', '=', $platform)
            ->where('day_date', '=', date('Y-m-d', time()))
            ->where('grade', '<>', 'Z')
            ->field('grade,frame_dull_stock,frame_dull_stock_ratio,acc_dull_stock,acc_dull_stock_ratio')
            ->select();

        $dullStocks = collection($dullStocks)->toArray();

        $frameDullStock = array_sum(array_column($dullStocks, 'frame_dull_stock'));
        $accDullStock = array_sum(array_column($dullStocks, 'acc_dull_stock'));

        $frameDullStockRatio = 0;
        $accDullStockRatio = 0;
        $allDullStockRatio = 0;

        if ($frameDullStock > 0 && $frameStockNum > 0) {
            $frameDullStockRatio = bcmul(bcdiv($frameDullStock, $frameStockNum, 2), 100, 2);
        }
        if ($accDullStock > 0 && $accStockNum > 0) {
            $accDullStockRatio = bcmul(bcdiv($accDullStock, $accStockNum, 2), 100, 2);
        }

        $allDullStock = bcadd($frameDullStock, $accDullStock);
        if ($allDullStock > 0 && $allStockNum > 0) {
            $allDullStockRatio = bcmul(bcdiv($allDullStock, $allStockNum, 2), 100, 2);
        }
        $result = [
            [
                'category' => '镜框', 'sku_num' => $frameSkuNum, 'stock' => $frameStockNum,
                'sales_last_30_days' => $frameSalesLast30Days, 'turnover_months' => $frameTurnoverMonths,
                'sluggish_stock' => $frameDullStock, 'sluggish_stock_ratio' => $frameDullStockRatio
            ],
            [
                'category' => '饰品', 'sku_num' => $accSkuNum, 'stock' => $accStockNum,
                'sales_last_30_days' => $accSalesLast30Days, 'turnover_months' => $accTurnoverMonths,
                'sluggish_stock' => $accDullStock, 'sluggish_stock_ratio' => $accDullStockRatio
            ],
            [
                'category' => '总计', 'sku_num' => $allSkuNum, 'stock' => $allStockNum,
                'sales_last_30_days' => $allSalesLast30Days, 'turnover_months' => $allTurnoverMonths,
                'sluggish_stock' => $allDullStock, 'sluggish_stock_ratio' => $allDullStockRatio
            ]
        ];

        $this->success('', '', $result);
    }

    /**
     * 获取库存分级
     *
     * @param  Request  $request
     * @author fangke
     * @date   5/17/21 3:48 PM
     */
    public function getStockGrading(Request $request)
    {
        $platform = $request->post('platform', 1);

        $dullStocks = DullStockSite::where('site', '=', $platform)
            ->where('day_date', '=', date('Y-m-d', time()))
            ->order('sort')
            ->field('grade,sku_num,sku_ratio,stock,stock_ratio,dull_stock,dull_stock_sku_num,dull_stock_ratio,high_risk_dull_stock,high_risk_dull_stock_sku,medium_risk_dull_stock,medium_risk_dull_stock_sku,low_risk_dull_stock,low_risk_dull_stock_sku')
            ->select();

        $this->success('', '', $dullStocks);
    }

    /**
     * 库存健康状况
     *
     * @param  Request  $request
     * @author fangke
     * @date   5/17/21 5:23 PM
     */
    public function stockHealthStatus(Request $request)
    {
        $platform = $request->get('platform', 1);
        $stockHealthStatus = ItemPlatformSku::group('stock_health_status')
            ->where('outer_sku_status', '=', 1)
            ->whereIn('stock_health_status', [1, 2, 3, 4, 5])
            ->where('platform_type', '=', $platform)
            ->field("count(id) as value,stock_health_status")
            ->select();

        $data = collection($stockHealthStatus)->toArray();
        $map = [
            1 => '正常',
            2 => '高风险',
            3 => '中风险',
            4 => '低风险',
            5 => '运营新品'
        ];

        $data = array_map(function ($item) use ($map) {
            $item['name'] = $map[$item['stock_health_status']];

            return $item;
        }, $data);

        $total = array_sum(array_column($data, 'value'));

        $result = [
            'data' => $data,
            'total' => $total
        ];

        $column = $map;
        $json['column'] = $column;
        $json['columnData'] = $result['data'];
        $json['total'] = $result['total'];

        $this->success('', '', $json);
    }
}