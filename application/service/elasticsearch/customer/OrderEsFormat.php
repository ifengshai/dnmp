<?php
/**
 * Class OrderEsFormat.php
 * @package app\service\elasticsearch\customer
 * @author  crasphb
 * @date    2021/5/8 15:18
 */

namespace app\service\elasticsearch\customer;


use app\enum\Store;
use app\service\elasticsearch\BaseEsFormatData;

class OrderEsFormat extends BaseEsFormatData
{
    /**
     * 订单数据 -- 订单关键指标
     *
     * @param       $site
     * @param       $data
     * @param array $compareData
     *
     * @return array
     * @author crasphb
     * @date   2021/4/21 11:43
     */
    public function formatPurchaseData($site, $data, $compareData = [])
    {
        //顶部指标
        $orderNum = $data['sumOrder']['value'];
        $allAvgPrice = round($data['allAvgPrice']['value'], 2);
        $allDaySalesAmount = round($data['allDaySalesAmount']['value'], 2);
        $allShippingAmount = round($data['allShippingAmount']['value'], 2);

        $compareOrderNumRate = $compareAllAvgPriceRate = $compareAllDaySalesAmountRate = $compareAllShippingAmountRate = 0;
        if ($compareData) {

            $compareOrderNum = $compareData['sumOrder']['value'];
            $compareAllAvgPrice = round($compareData['allAvgPrice']['value'], 2);
            $compareAllDaySalesAmount = round($compareData['allDaySalesAmount']['value'], 2);
            $compareAllShippingAmount = round($compareData['allShippingAmount']['value'], 2);

            $compareOrderNumRate = $compareOrderNum ? bcmul(bcdiv(bcsub($orderNum, $compareOrderNum), $compareOrderNum, 4), 100, 2) : 0;
            $compareAllAvgPriceRate = $compareAllAvgPrice ? bcmul(bcdiv(bcsub($allAvgPrice, $compareAllAvgPrice), $compareAllAvgPrice, 4), 100, 2) : 0;
            $compareAllDaySalesAmountRate = $compareAllDaySalesAmount ? bcmul(bcdiv(bcsub($allDaySalesAmount, $compareAllDaySalesAmount), $compareAllDaySalesAmount, 4), 100, 2) : 0;
            $compareAllShippingAmountRate = $compareAllShippingAmount ? bcmul(bcdiv(bcsub($allShippingAmount, $compareAllShippingAmount), $compareAllShippingAmount, 4), 100, 2) : 0;
        }

        //平台数据
        $store = $data['store']['buckets'];
        $storeData = [];
        $siteName = $this->getSiteName($site);
        $storeDataStr = '';
        foreach ($store as $key => $val) {
            $storeName = '';
            switch ($val['key']) {
                case Store::PC:
                    $storeName = '网页端';
                    break;
                case Store::WAP:
                    $storeName = '移动端';
                    break;
                case Store::IOS:
                    $storeName = 'IOS';
                    break;
                case Store::ANDROID:
                    $storeName = 'ANDROID';
                    break;
                case Store::WAP_WESEE:
                    $storeName = '移动端';
                    break;
            }
            $storeData = [
                'siteName'       => $siteName,
                'storeName'      => $storeName,
                'daySalesAmount' => $val['daySalesAmount']['value'] ?? 0,
                'avgPrice'       => $val['avgPrice']['value'] ?? 0,
                'orderNum'       => $val['doc_count'],
            ];
            $storeDataStr .= '<tr><td>' . $val['key'] . '</td><td>' . $siteName . '</td><td>' . $storeName . '</td><td>' . $storeData['daySalesAmount'] . '</td><td>' . $storeData['orderNum'] . '</td><td>' . round($storeData['avgPrice'],2) . '</td></tr>';
        }
        //订单类型
        //0 ，平邮免邮
        //1.平邮
        //2.商业快递免邮
        //3. 商业快递
        $shipType = $data['shipType']['buckets'];
        $shipTypeData = array_combine(array_column($shipType, 'key'), $shipType);
        foreach ($shipTypeData as $key => $val) {
            $shipTypeData[$key]['rate'] = $orderNum ? bcmul(bcdiv($val['doc_count'], $orderNum, 4), 100, 2) . '%' : '0%';
        }
        //金额分部
        $priceRangesData = array_combine(array_column($data['priceRanges']['buckets'], 'from'), $data['priceRanges']['buckets']);
        foreach ($priceRangesData as $key => $val) {
            $priceRangesData[$key]['rate'] = $orderNum ? bcmul(bcdiv($val['doc_count'], $orderNum, 4), 100, 2) . '%' : '0%';
        }

        $countryStr = '';
        //国家分部
        $countrySaleData = $data['countrySale']['buckets'];
        foreach ($countrySaleData as $key => $val) {
            $countrySaleDataRrate = $orderNum ? bcmul(bcdiv($val['doc_count'], $orderNum, 4), 100, 2) . '%' : '0%';
            $countryStr .= '<tr><td>' . $val['key'] . '</td><td>' . $val['doc_count'] . '</td><td>' . $countrySaleDataRrate . '</td></tr>';
        }

        return compact('orderNum', 'allAvgPrice', 'allDaySalesAmount', 'allShippingAmount', 'priceRangesData', 'storeDataStr', 'countryStr', 'compareOrderNumRate', 'compareAllAvgPriceRate', 'compareAllDaySalesAmountRate', 'compareAllShippingAmountRate', 'shipTypeData');
    }

    /**
     * 订单数据 -- 订单关键指标的图标
     * @param $data
     *
     * @return array
     * @author crasphb
     * @date   2021/5/8 16:03
     */
    public function formatActiveOrderData($data) {
        $dayBuckets = $data['daySale']['buckets'];
        $days = $dayBuckets ? array_column($dayBuckets, 'key') : [];
        $orderNum = $dayBuckets ? array_column($dayBuckets, 'orderNum') : [];
        $activeUserNum = $dayBuckets ? array_column($dayBuckets, 'activeUserNum') : [];
        //订单量趋势
        $ydata = [
            [
                'value' => array_values(array_column($orderNum,'value')),
                'name' => '订单数',
            ],
            [
                'value' => array_values(array_column($activeUserNum,'value')),
                'name' => '活跃用户数',
            ]

        ];
        $echarts = $this->getMutilEcharts($days, $ydata);
        return compact('echarts');
    }

    /**
     * 转化率
     * @param $data
     *
     * @return array
     * @author crasphb
     * @date   2021/5/12 11:16
     */
    public function formatConversionRateData($data)
    {
        //表格指标
        //注册
        $allRegisterNum = $data['allRegisterNum']['value'];
        $allLoginNum = $data['allLoginNum']['value'];
        //总回话数
        $allSessions = $data['allSessions']['value'];
        //客单价
        $allAvgPrice = round($data['allAvgPrice']['value'],2);
        //销售各
        $allSalesTotalMoney = $data['allSalesTotalMoney']['value'];
        //ga加购数
        $allAddToCartNum = $data['allAddToCartNum']['value'];
        //订单数
        $allOrderNum = $data['allOrderNum']['value'];
        //新增购物车数
        $allNewCartNum = $data['allNewCartNum']['value'];
        //更新购物车数
        $allUpdateCartNum = $data['allUpdateCartNum']['value'];

        //加购率
        $allAddToCartRate = $this->getDecimal($allNewCartNum,$allSessions);
        //回话转化率
        $allSessionRate = $this->getDecimal($allOrderNum,$allSessions);

        $daySaleBuckets = $data['daySale']['buckets'];
        $daySaleData = array_combine(array_column($daySaleBuckets, 'key'), $daySaleBuckets);
        //表横坐标
        $days = array_keys($daySaleData);
        $dayChartsSession = array_column($daySaleData,'sessions');
        $dayChartsSales = array_column($daySaleData,'salesTotalMoney');
        $dayChartsUpdateCartNum = array_column($daySaleData,'updateCartNum');
        $dayChartsCreateCartNum = array_column($daySaleData,'newCartNum');
        $dayChartsOrderNum = array_column($daySaleData,'orderNum');
        $daySaleStr = '';
        $daySaleDataReverse = array_reverse($daySaleData);
        foreach ($daySaleDataReverse as $key => $val) {
            $date = date('Y-m-d',strtotime($val['key']));
            $session = $val['sessions']['value'] ?: 0;
            $orderNum = $val['orderNum']['value'] ?: 0;
            $avgPrice = $val['avgPrice']['value'] ?: 0.00;
            $addToCartNum = $val['addToCartNum']['value'] ?: 0;
            $newCartNum = $val['newCartNum']['value'] ?: 0;
            $updateCartNum = $val['updateCartNum']['value'] ?: 0;
            $registerNum = $val['registerNum']['value'] ?: 0;
            $loginNum = $val['loginNum']['value'] ?: 0;
            $salesTotalMoney = $val['salesTotalMoney']['value'] ?: 0.00;
            //加购率
            $addToCartRate = $this->getDecimal($newCartNum,$session);
            //回话转化率
            $sessionRate = $this->getDecimal($orderNum,$session);
            $daySaleStr .= '<tr><td>' . $date . '</td><td>' . $loginNum . '</td><td>' . $session . '</td><td>' . $addToCartRate . '</td><td>' . $sessionRate . '</td><td>' . $orderNum . '</td><td>' . $avgPrice . '</td><td>' . $newCartNum . '</td><td>' . $updateCartNum . '</td><td>' . $salesTotalMoney . '</td><td>' . $registerNum . '</td></td></tr>';
        }
        $daySaleStr .= '<tr><td> 合计 </td><td>' . $allLoginNum . '</td><td>' . $allSessions . '</td><td>' . $allAddToCartRate . '</td><td>' . $allSessionRate . '</td><td>' . $allOrderNum . '</td><td>' . $allAvgPrice . '</td><td>' . $allNewCartNum . '</td><td>' . $allUpdateCartNum . '</td><td>' . $allSalesTotalMoney . '</td><td>' . $allRegisterNum . '</td></td></tr>';
        //回话-销售趋势
        $ydataSessionSale = [
            [
                'value' => array_values(array_column($dayChartsSession,'value')),
                'name' => '会话数',
            ],
            [
                'value' => array_values(array_column($dayChartsSales,'value')),
                'name' => '销售额',
            ]

        ];
        $echartsSessionSale = $this->getMutilEcharts($days, $ydataSessionSale);
        //购物侧后-订单趋势
        $ydataCartOrder = [
            [
                'value' => array_values(array_column($dayChartsUpdateCartNum,'value')),
                'name' => '更新购物车数目',
            ],
            [
                'value' => array_values(array_column($dayChartsOrderNum,'value')),
                'name' => '订单数量',
            ]

        ];
        $echartsCartOrder = $this->getMutilEcharts($days, $ydataCartOrder);
        return compact('daySaleStr','echartsSessionSale','echartsCartOrder');
    }

    public function formatHourData($orderData, $createCartData, $updateCartData, $gaData = [], $today = true)
    {
        $hourSale = $orderData['hourSale']['buckets'];
        $hourCreateCart = $createCartData['hourCart']['buckets'];
        $hourUpdateCart = $updateCartData['hourCart']['buckets'];


        $hourSaleFormat = array_combine(array_column($hourSale, 'key'), $hourSale);
        $hourCreateCartFormat = array_combine(array_column($hourCreateCart, 'key'), $hourCreateCart);
        $hourUpdateCartFormat = array_combine(array_column($hourUpdateCart, 'key'), $hourUpdateCart);
        $finalLists = $this->formatHour($today);
        //时段销量数据
        $allSession = $allCreateCartToOrderNum = $allUpdateCartToOrderNum = $createCartToOrderNum = $updateCartToOrderNum = 0;
        $arr = [];

        foreach ($finalLists as $key => $finalList) {
            foreach ($gaData as $gaKey => $gaValue) {
                if ((int)$finalList['hour'] == (int)substr($gaValue['ga:dateHour'], 8)) {
                    $arr[$key]['sessions'] += $gaValue['ga:sessions'];
                }
            }

            $formatHour = $finalList['hour'];
            if($today) {
                if($formatHour > date('H')) continue;
            }

            if (strlen($finalList['hour']) == 1) {
                $formatHour = '0' . $finalList['hour'];
            }
            if(!isset($hourSaleFormat[$formatHour])) {
                continue;
            }
            $arr[$key]['hour_created'] = $finalList['hour_created'];
            $hourOrderData = $hourSaleFormat[$formatHour];


            $hourCreateCartDataIdsArr = $hourUpdateCartDataIdsArr = [];
            //获取当前时间的新增购物车id
            if(isset($hourCreateCartFormat[$formatHour])){
                $hourCreateCartData = $hourCreateCartFormat[$formatHour];
                $hourCreateCartDataIds = $hourCreateCartData['ids']['buckets'];
                $hourCreateCartDataIdsArr = array_column($hourCreateCartDataIds,'key');
            }

            //获取当前时间的修改购物车id
            if(isset($hourUpdateCartFormat[$formatHour])){
                $hourUpdateCartData = $hourUpdateCartFormat[$formatHour];
                $hourUpdateCartDataIds = $hourUpdateCartData['ids']['buckets'];
                $hourUpdateCartDataIdsArr = array_column($hourUpdateCartDataIds,'key');
            }

            //获取产生的订单购车id
            $hourOrderDataIds = $hourOrderData['quoteIds']['buckets'];
            $hourOrderDataIdsArr = array_column($hourOrderDataIds,'key');

            //求新增购物车产生的订单数
            $createCartToOrderNum = count(array_intersect($hourOrderDataIdsArr,$hourCreateCartDataIdsArr));
            //求更新购物车产生的订单数
            $updateCartToOrderNum = count(array_intersect($hourOrderDataIdsArr,$hourUpdateCartDataIdsArr));

            $arr[$key]['daySalesAmount'] = round($hourOrderData['daySalesAmount']['value'], 2);
            $arr[$key]['avgPrice'] = round($hourOrderData['avgPrice']['value'], 2);
            $arr[$key]['totalQtyOrdered'] = $hourOrderData['totalQtyOrdered']['value'];
            $arr[$key]['orderCounter'] = $hourOrderData['doc_count'];
            //新增购物车数目
            $arr[$key]['createCartCount'] = $hourCreateCartData['doc_count'] ?? 0;
            //更新购物车数目
            $arr[$key]['updateCartCount'] = $hourUpdateCartData['doc_count'] ?? 0;

            //加购率
            $arr[$key]['addCartRate'] = $arr[$key]['sessions'] ? bcmul(bcdiv($arr[$key]['createCartCount'], $arr[$key]['sessions'], 4),100,2) . '%' : '0%';
            //新增购物车转化率
            $arr[$key]['createCartRate'] = $arr[$key]['createCartCount'] ? bcmul(bcdiv($createCartToOrderNum, $arr[$key]['createCartCount'], 4),100,2) . '%' : '0%';
            //更新购物车转化率
            $arr[$key]['updateCartRate'] = $arr[$key]['updateCartCount'] ? bcmul(bcdiv($updateCartToOrderNum, $arr[$key]['updateCartCount'], 4),100,2) . '%' : '0%';
            //回话转化率
            $arr[$key]['sessionRate'] = $arr[$key]['sessions'] ? bcmul(bcdiv($hourOrderData['doc_count'], $arr[$key]['sessions'], 4),100,2) . '%' : '0%';

            //总回话数
            $allSession += $arr[$key]['sessions'];
            //总新增购物车产生的订单数
            $allCreateCartToOrderNum += $createCartToOrderNum;
            //总更新购物车产生的订单数
            $allUpdateCartToOrderNum += $updateCartToOrderNum;
        }

        //总订单数
        $allOrderCount = $orderData['sumOrder']['value'];

        //总新增购物车数
        $allHourCreateCart = $createCartData['sumCarts']['value'];
        $allHourUpdateCart = $updateCartData['sumCarts']['value'];
        //总销量
        $allQtyOrdered = $orderData['allQtyOrdered']['value'];
        //总销售额
        $allDaySalesAmount = round($orderData['allDaySalesAmount']['value'], 2);
        //总客单价
        $allAvgPrice = round($orderData['allAvgPrice']['value'], 2);

        //加购率
        $addCartRate = $allSession ? bcmul(bcdiv($allHourCreateCart, $allSession, 4),100,2) . '%' : '0%';
        //新增购物车转化率
        $createCartRate = $allHourCreateCart ? bcmul(bcdiv($allCreateCartToOrderNum, $allHourCreateCart, 4),100,2) . '%' : '0%';
        $updateCartRate = $allHourUpdateCart ? bcmul(bcdiv($allUpdateCartToOrderNum, $allHourUpdateCart, 4),100,2) . '%' : '0%';
        //回话转化率
        $sessionRate = $allSession ? bcmul(bcdiv($allOrderCount, $allSession, 4),100,2) . '%' : '0%';

        return compact('arr', 'allSession','allOrderCount', 'allHourCreateCart', 'allHourUpdateCart', 'allQtyOrdered', 'allDaySalesAmount', 'allAvgPrice', 'addCartRate', 'createCartRate', 'updateCartRate', 'sessionRate');
    }

    /**
     * 分时销量统计图表
     * @param $orderData
     * @param $compartOrderData
     *
     * @return array
     * @author crasphb
     * @date   2021/5/14 9:14
     */
    public function formatHourChartsData($orderData,$compartOrderData,$today)
    {
        $hourSale = $orderData['hourSale']['buckets'];
        $hourSaleFormat = array_combine(array_column($hourSale, 'key'), $hourSale);
        $comparthourSaleFormat = [];
        if($compartOrderData) {
            $comparthourSale = $compartOrderData['hourSale']['buckets'];
            $comparthourSaleFormat = array_combine(array_column($comparthourSale, 'key'), $comparthourSale);
        }
        $orderDataFormat = $this->getChartData($hourSaleFormat,$today);
        $compareOrderDataFormat = $this->getChartData($comparthourSaleFormat,$today);
        $orderArr = $this->getHourEcharts($orderDataFormat);
        $compareOrderArr = $this->getHourEcharts($compareOrderDataFormat);
        $xData = array_values($orderArr['hourStr']);
        //订单量趋势
        $daySalesAmountYdata = [
            [
                'value' => array_values($orderArr['daySalesAmount']),
                'name' => '选择时间',
            ],
            [
                'value' => array_values($compareOrderArr['daySalesAmount']),
                'name' => '对比时间',
            ]

        ];
        $daySalesAmountEcharts = $this->getMutilEcharts($xData, $daySalesAmountYdata);
        //订单量趋势
        $orderCounterYdata = [
            [
                'value' => array_values($orderArr['orderCounter']),
                'name' => '选择时间',
            ],
            [
                'value' => array_values($compareOrderArr['orderCounter']),
                'name' => '对比时间',
            ]

        ];
        $orderCounterEcharts = $this->getMutilEcharts($xData, $orderCounterYdata);
        //订单量趋势
        $avgPriceYdata = [
            [
                'value' => array_values($orderArr['avgPrice']),
                'name' => '选择时间',
            ],
            [
                'value' => array_values($compareOrderArr['avgPrice']),
                'name' => '对比时间',
            ]

        ];
        $avgPriceEcharts = $this->getMutilEcharts($xData, $avgPriceYdata);
        //订单量趋势
        $totalQtyOrderedYdata = [
            [
                'value' => array_values($orderArr['totalQtyOrdered']),
                'name' => '选择时间',
            ],
            [
                'value' => array_values($compareOrderArr['totalQtyOrdered']),
                'name' => '对比时间',
            ]

        ];
        $totalQtyOrderedEcharts = $this->getMutilEcharts($xData, $totalQtyOrderedYdata);
        return compact('daySalesAmountEcharts','totalQtyOrderedEcharts','avgPriceEcharts','orderCounterEcharts');

    }

    public function getAllOrderDataFormatData($data, $compareData = [])
    {
        //获取活跃用户数
        $activeUserNum = $data['activeUserNum']['value'];
        //注册用户数
        $registerNum = $data['registerNum']['value'];
        //订单数
        $orderNum = $data['orderNum']['value'];

        $compareActiveUserNumRate = $compareRegisterNumRate = 0;

        if ($compareData) {
            //获取活跃用户数
            $compareActiveUserNum = $compareData['activeUserNum']['value'];
            //注册用户数
            $compareRegisterNum = $compareData['registerNum']['value'];
            //订单数
            $compareOrderNum = $compareData['orderNum']['value'];


            $compareActiveUserNumRate = $compareActiveUserNum ? bcmul(bcdiv(bcsub($activeUserNum, $compareActiveUserNum), $compareActiveUserNum, 4), 100, 2) : 0;
            $compareRegisterNumRate = $compareRegisterNum ? bcmul(bcdiv(bcsub($registerNum, $compareRegisterNum), $compareRegisterNum, 4), 100, 2) : 0;
        }

        $dayBuckets = $data['daySale']['buckets'];
        if($dayBuckets) {
            $list = [];
            $i = 0;
            foreach ($dayBuckets as $key => $value) {
                $list[$i]['day_date'] = date('Y-m-d', strtotime($value['key']));
                $list[$i]['order_num'] = $value['orderNum']['value'];
                $list[$i]['sales_total_money'] = round($value['salesTotalMoney']['value'], 2);
                $list[$i]['sessions'] = $value['sessions']['value'];
                $list[$i]['activeUserNum'] = $value['activeUserNum']['value'];
                $i++;
            }

            $days = $list ? array_column($list, 'day_date') : [];
            $dayActiveUserNum = $list ? array_column($list, 'activeUserNum') : [];
            $dayOrderNum = $list ? array_column($list, 'order_num') : [];
            $daySessions = $list ? array_column($list, 'sessions') : [];
            $dayOrderTotal = $list ? array_column($list, 'sales_total_money') : [];

            $ydataOrderSale = [
                [
                    'value' => array_values($dayOrderTotal),
                    'name' => '销售额',
                ],
                [
                    'value' => array_values($dayOrderNum),
                    'name' => '订单数',
                ]

            ];
            $echartsOrderSale = $this->getMutilEcharts($days, $ydataOrderSale);

            $ydataUser = [
                [
                    'value' => array_values($dayActiveUserNum),
                    'name' => '活跃用户数',
                ],
                [
                    'value' => array_values($daySessions),
                    'name' => '会话数',
                ]

            ];
            $echartsUser = $this->getMutilEcharts($days, $ydataUser);
            return compact('echartsUser','echartsOrderSale','activeUserNum','registerNum','compareActiveUserNumRate','compareRegisterNumRate','orderNum','compareOrderNum');
        }
    }

    /**
     * 分时销量统计图表数据
     * @param $data
     *
     * @return array
     * @author crasphb
     * @date   2021/5/14 9:14
     */
    protected function getChartData($data,$today = true)
    {
        $finalLists = $this->formatHour($today);
        foreach ($finalLists as $key => $finalList) {
            $formatHour = $finalList['hour'];
            if (strlen($finalList['hour']) == 1) {
                $formatHour = '0' . $finalList['hour'];
            }
            if(isset($data[$formatHour])){
                $hourOrderData = $data[$formatHour];

                $finalLists[$key]['daySalesAmount'] = round($hourOrderData['daySalesAmount']['value'], 2);
                $finalLists[$key]['avgPrice'] = round($hourOrderData['avgPrice']['value'], 2);
                $finalLists[$key]['totalQtyOrdered'] = $hourOrderData['totalQtyOrdered']['value'];
                $finalLists[$key]['orderCounter'] = $hourOrderData['doc_count'];
            }else{
                $finalLists[$key]['daySalesAmount'] = '';
                $finalLists[$key]['avgPrice'] = '';
                $finalLists[$key]['totalQtyOrdered'] = '';
                $finalLists[$key]['orderCounter'] = '';
            }

        }
        return $finalLists;
    }
    /**
     * 格式化0-24小时
     *
     * @param bool $today
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 14:40
     */
    public function formatHour($today = true)
    {
        $finalList = [];
        for ($i = 0; $i < 24; $i++) {
            if ($today) {
                $hour = intval(date('H'));
                if ($i <= $hour) {
                    $finalList[$i]['hour'] = $i;
                    $finalList[$i]['hour_created'] = "$i:00 - $i:59";
                }
            } else {
                $finalList[$i]['hour'] = $i;
                $finalList[$i]['hour_created'] = "$i:00 - $i:59";
            }
        }

        return $finalList;
    }
    /**
     * 分时的数表
     *
     * @param $finalLists
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 16:47
     */
    public function getHourEcharts($finalLists)
    {
        $daySalesAmount = $avgPrice = $totalQtyOrdered = $orderCounter = $hourStr = [];
        for ($i = 0; $i < 24; $i++) {
            if ($finalLists[$i]['orderCounter'] || $finalLists[$i]['totalQtyOrdered']) {
                $hourStr[$i] = "$i:00";
                $daySalesAmount[$i] = $finalLists[$i]['daySalesAmount'] ? $finalLists[$i]['daySalesAmount'] : "0";
                $orderCounter[$i] = $finalLists[$i]['orderCounter'] ? $finalLists[$i]['orderCounter'] : "0";
                $totalQtyOrdered[$i] = $finalLists[$i]['totalQtyOrdered'] ? $finalLists[$i]['totalQtyOrdered'] : "0";
                $avgPrice[$i] = $finalLists[$i]['avgPrice'];
            } else {
                $hourStr[$i] = "$i:00";
                $daySalesAmount[$i] = "0";
                $orderCounter[$i] = "0";
                $totalQtyOrdered[$i] = "0";
                $avgPrice[$i] = "0";
            }
        }

        return compact('hourStr','daySalesAmount','orderCounter','totalQtyOrdered','avgPrice');
    }
}