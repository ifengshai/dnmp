<?php
/**
 * Class EsFormatData.php
 * @package app\service\elasticsearch
 * @author  crasphb
 * @date    2021/4/12 11:55
 */

namespace app\service\elasticsearch;


use app\enum\OrderType;
use app\enum\Site;

class EsFormatData
{

    /**
     * 时段销量格式化
     *
     * @param $orderData
     * @param $cartData
     * @param $gaData
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 14:39
     */
    public function formatHourData($orderData, $cartData, $gaData = [],$today = true)
    {
        $hourSale = $orderData['hourSale']['buckets'];
        $hourCart = $cartData['hourCart']['buckets'];
        $hourSaleFormat = array_combine(array_column($hourSale, 'key'), $hourSale);
        $hourCartFormat = array_combine(array_column($hourCart, 'key'), $hourCart);
        $finalLists = $this->formatHour($today);
        //时段销量数据
        $allSession = 0;
        foreach ($finalLists as $key => $finalList) {
            foreach ($gaData as $gaKey => $gaValue) {
                if ((int)$finalList['hour'] == (int)substr($gaValue['ga:dateHour'], 8)) {
                    $finalLists[$key]['sessions'] += $gaValue['ga:sessions'];
                }
            }
            $formatHour = $finalList['hour'];
            if(strlen($finalList['hour']) == 1) {
                $formatHour = '0' . $finalList['hour'];
            }
            $hourOrderData = $hourSaleFormat[$formatHour];
            $hourCartData = $hourCartFormat[$formatHour];
            $finalLists[$key]['daySalesAmount'] = $hourOrderData['daySalesAmount']['value'];
            $finalLists[$key]['avgPrice'] = $hourOrderData['avgPrice']['value'];
            $finalLists[$key]['totalQtyOrdered'] = $hourOrderData['totalQtyOrdered']['value'];
            $finalLists[$key]['orderCounter'] = $hourOrderData['doc_count'];
            //购物车数目
            $finalLists[$key]['cartCount'] = $hourCartData['doc_count'];

            //加购率
            $finalLists[$key]['addCartRate'] = $finalLists[$key]['sessions'] ? bcdiv($hourCartData['doc_count'], $finalLists[$key]['sessions'], 2) . '%' : '0%';
            //新增购物车转化率
            $finalLists[$key]['cartRate'] = $hourOrderData['doc_count'] ? bcdiv($hourCartData['doc_count'], $hourOrderData['doc_count'], 2) . '%' : '0%';
            //回话转化率
            $finalLists[$key]['sessionRate'] = $finalLists[$key]['sessions'] ? bcdiv($hourOrderData['doc_count'], $finalLists[$key]['sessions'], 2) . '%' : '0%';

            //总回话数
            $allSession += $finalLists[$key]['sessions'];
        }

        //总订单数
        $allOrderCount = $orderData['sumOrder']['value'];
        //总新增购物车数
        $allCartAmount = $cartData['sumCart']['value'];
        //总销量
        $allQtyOrdered = $orderData['allQtyOrdered']['value'];
        //总销售额
        $allDaySalesAmount = $orderData['allDaySalesAmount']['value'];
        //总客单价
        $allAvgPrice = $orderData['allAvgPrice']['value'];

        //加购率
        $addCartRate = $allSession ? bcdiv($allCartAmount, $allSession, 2) . '%' : '0%';
        //新增购物车转化率
        $cartRate = $allOrderCount ? bcdiv($allCartAmount, $allOrderCount, 2) . '%' : '0%';
        //回话转化率
        $sessionRate = $allSession ? bcdiv($allOrderCount, $allSession, 2) . '%' : '0%';

        //合计
        //返回图标
        $echartData = $this->getHourEcharts($finalLists);
        //销售量
        $orderitemCounter = $this->hourEcharts($echartData['hourStr'], $echartData['totalQtyOrdered'], '销售量');
        //销售额
        $saleAmount = $this->hourEcharts($echartData['hourStr'], $echartData['daySalesAmount'], '销售额');
        //订单量
        $orderCounter = $this->hourEcharts($echartData['hourStr'], $echartData['orderCounter'], '订单量');
        //客单价
        $grandTotalOrderConversion = $this->hourEcharts($echartData['hourStr'], $echartData['avgPrice'], '客单价');

        return compact('finalLists', 'addCartRate', 'cartRate', 'sessionRate', 'allOrderCount', 'allCartAmount', 'allQtyOrdered', 'allDaySalesAmount','allSession', 'allAvgPrice', 'orderitemCounter', 'saleAmount', 'grandTotalOrderConversion', 'orderCounter');
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
                $hour = date('H');
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
        $echartData = [];
        $echartData['hourStr'] = "";
        $echartData['daySalesAmount'] = "";
        $echartData['orderCounter'] = "";
        $echartData['totalQtyOrdered'] = "";
        $echartData['avgPrice'] = "";

        for ($i = 0; $i < 24; $i++) {
            if ($finalLists[$i]['sessions'] || $finalLists[$i]['quote_counter']) {
                $echartData['hourStr'] .= "$i:00,";
                $echartData['daySalesAmount'] .= $finalLists[$i]['daySalesAmount'] ? $finalLists[$i]['daySalesAmount'] . "," : "0,";
                $echartData['orderCounter'] .= $finalLists[$i]['orderCounter'] ? $finalLists[$i]['orderCounter'] . "," : "0,";
                $echartData['totalQtyOrdered'] .= $finalLists[$i]['totalQtyOrdered'] ? $finalLists[$i]['totalQtyOrdered'] . "," : "0,";
                $echartData['avgPrice'] .= $finalLists[$i]['avgPrice'] . ",";
            } else {
                $echartData['hourStr'] .= "$i:00,";
                $echartData['daySalesAmount'] .= "0,";
                $echartData['orderCounter'] .= "0,";
                $echartData['totalQtyOrdered'] .= "0,";
                $echartData['avgPrice'] .= "0,";
            }
        }
        $echartData['hourStr'] = rtrim($echartData['hourStr'], ',');
        $echartData['daySalesAmount'] = rtrim($echartData['daySalesAmount'], ',');
        $echartData['orderCounter'] = rtrim($echartData['orderCounter'], ',');
        $echartData['totalQtyOrdered'] = rtrim($echartData['totalQtyOrdered'], ',');
        $echartData['avgPrice'] = rtrim($echartData['avgPrice'], ',');

        return $echartData;
    }

    /**
     * 时间的格式
     *
     * @param        $xdata
     * @param        $ydata
     * @param string $name
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 16:47
     */
    public function hourEcharts($xdata, $ydata, $name = '')
    {
        if(!is_array($xdata)) {
            $xdata = explode(',', $xdata);
        }
        if(!is_array($ydata)) {
            $ydata = explode(',', $ydata);
        }

        $echart['xcolumnData'] = $xdata;
        $echart['column'] = [$name];
        $echart['columnData'] = [
            [
                'type'   => 'line',
                'data'   => $ydata,
                'name'   => $name,
                'smooth' => false //平滑曲线
            ],

        ];

        return $echart;
    }

    /**
     * 数据概况 - 数据大盘数据处理
     *
     * @param       $site
     * @param       $data
     * @param array $compareData
     * @param false $siteAll
     *
     * @return array
     * @author crasphb
     * @date   2021/4/15 15:50
     */
    public function formatDashBoardData($site, $data, $compareData = []  ,$siteAll = false)
    {
        if (!$siteAll) {
            $buckets = $data['site']['buckets'];
            $siteKeyData = array_combine(array_column($buckets, 'key'), $buckets);
            $data = $siteKeyData[$site];
        }
        //获取活跃用户数
        $activeUserNum = $data['activeUserNum']['value'];
        //注册用户数
        $registerNum = $data['registerNum']['value'];
        //vip用户数
        $vipUserNum = $data['vipUserNum']['value'];
        //订单数
        $orderNum = $data['orderNum']['value'];
        //销售额
        $salesTotalMoney = $data['salesTotalMoney']['value'];
        //邮费
        $shippingTotalMoney = $data['shippingTotalMoney']['value'];
        //客单价
        $orderUnitPrice = $orderNum ? bcdiv($salesTotalMoney, $orderNum, 2) : 0;


        $compareActiveUserNumRate = $compareRegisterNumRate = $compareVipUserNuRate = $compareOrderNumRate = $compareSalesTotalMoneyRate = $compareShippingTotalMoneyRate = $compareOrderUnitPriceRate = 0;

        if($compareData) {
            if (!$siteAll) {
                $compareBuckets = $compareData['site']['buckets'];
                $compareSiteKeyData = array_combine(array_column($compareBuckets, 'key'), $compareBuckets);
                $compareData = $compareSiteKeyData[$site];
            }
            //获取活跃用户数
            $compareActiveUserNum = $compareData['activeUserNum']['value'];
            //注册用户数
            $compareRegisterNum = $compareData['registerNum']['value'];
            //vip用户数
            $compareVipUserNum = $compareData['vipUserNum']['value'];
            //订单数
            $compareOrderNum = $compareData['orderNum']['value'];
            //销售额
            $compareSalesTotalMoney = $compareData['salesTotalMoney']['value'];
            //邮费
            $compareShippingTotalMoney = $compareData['shippingTotalMoney']['value'];
            //客单价
            $compareOrderUnitPrice = $compareOrderNum ? bcdiv($compareSalesTotalMoney, $compareOrderNum,2) : 0;


            $compareActiveUserNumRate = $compareActiveUserNum ? bcmul(bcdiv(bcsub($activeUserNum,$compareActiveUserNum),$compareActiveUserNum,4),100,2) : 0;
            $compareRegisterNumRate = $compareRegisterNum ? bcmul(bcdiv(bcsub($registerNum,$compareRegisterNum),$compareRegisterNum,4),100,2) : 0;
            $compareVipUserNuRate = $compareVipUserNum ? bcmul(bcdiv(bcsub($vipUserNum,$compareVipUserNum),$compareVipUserNum,4),100,2) : 0;
            $compareOrderNumRate = $compareOrderNum ? bcmul(bcdiv(bcsub($orderNum,$compareOrderNum),$compareOrderNum,4),100,2) : 0;
            $compareSalesTotalMoneyRate = $compareSalesTotalMoney ? bcmul(bcdiv(bcsub($salesTotalMoney,$compareSalesTotalMoney),$compareSalesTotalMoney,4),100,2) : 0;
            $compareShippingTotalMoneyRate = $compareShippingTotalMoney ? bcmul(bcdiv(bcsub($shippingTotalMoney,$compareShippingTotalMoney),$compareShippingTotalMoney,4),100,2) : 0;
            $compareOrderUnitPriceRate = $compareOrderUnitPrice ? bcmul(bcdiv(bcsub($orderUnitPrice,$compareOrderUnitPrice),$compareOrderUnitPrice,4),100,2) : 0;
        }

        //着陆页
        $landingNum = $data['landingNum']['value'];
        //详情页
        $detailNum = $data['detailNum']['value'];
        //加购数
        $cartNum = $data['cartNum']['value'];
        //支付数
        $completeNum = $data['completeNum']['value'];

        $landingNumRate = '100%';
        //详情页
        $detailNumRate = !$landingNum ? '0%' : bcmul(bcdiv($detailNum, $landingNum,4),100,2) . '%';
        //加购数
        $cartNumRate = !$detailNum ? '0%' : bcmul(bcdiv($cartNum, $detailNum,4), 100,2) . '%';
        //支付数
        $completeNumRate = !$cartNum ? '0%' : bcmul(bcdiv($completeNum, $cartNum,4),100, 2) . '%';

        $dayBuckets = $data['daySale']['buckets'];
        $days = $dayBuckets ? array_column($dayBuckets, 'key') : [];
        $dayActiveUserNum = $dayBuckets ? array_column($dayBuckets, 'activeUserNum') : [];
        $dayOrderNum = $dayBuckets ? array_column($dayBuckets, 'orderNum') : [];

        $dayChart = [
            'xColumnName' => array_values($days),
            'columnData'  => [
                [
                    'type'       => 'line',
                    'data'       => array_values($dayActiveUserNum),
                    'name'       => '活跃用户数',
                    'yAxisIndex' => 0,
                    'smooth'     => true //平滑曲线
                ],
                [
                    'type'       => 'line',
                    'data'       => array_values($dayOrderNum),
                    'name'       => '订单数',
                    'yAxisIndex' => 1,
                    'smooth'     => true //平滑曲线
                ],
            ],
        ];

        /**
         * 转化漏斗
         */
        $funnel = [
            'column'     => ['用户购买转化漏斗'],
            'columnData' => [
                ['value' => $landingNum, 'percent' => $landingNumRate, 'name' => '着陆页'],
                ['value' => $detailNum, 'percent' => $detailNumRate, 'name' => '商品详情页'],
                ['value' => $cartNum, 'percent' => $cartNumRate, 'name' => '加购物车'],
                ['value' => $completeNum, 'percent' => $completeNumRate, 'name' => '支付转化'],
            ],
        ];

        return compact('activeUserNum', 'registerNum', 'vipUserNum', 'orderNum', 'salesTotalMoney', 'shippingTotalMoney', 'orderUnitPrice', 'dayChart', 'funnel','compareActiveUserNumRate','compareRegisterNumRate','compareVipUserNuRate','compareOrderNumRate','compareSalesTotalMoneyRate','compareShippingTotalMoneyRate','compareOrderUnitPriceRate');

    }
    
    public function formatTrackData($data  ,$data1)
    {
        $buckets = $data['track_channel']['buckets'];
        $newBuckets = array_column($buckets, 'doc_count', 'key');
        $buckets1 = $data1['track_channel']['buckets'];
        $arr = [];
        $i = 0;
        $sendNum = $delievedNum = $waitTime = $sevenDelievedDay = $tenDelievedDay = $fourteenDelievedDay = $twentyDelievedDay = $twentyupDelievedDay = 0;
        foreach ($buckets1 as $key => $value) {
            $sendNum += $newBuckets[$value['key']];
            $delievedNum += $value['doc_count'];
            $waitTime += $value['sumWaitTime']['value'];
            $arr[$i]['shipment_data_type'] = $value['key'];
            $arr[$i]['send_order_num'] = $newBuckets[$value['key']];
            $arr[$i]['deliverd_order_num'] = $value['doc_count'];
            $arr[$i]['total_deliverd_rate'] = $newBuckets[$value['key']] ? round($value['doc_count'] / $newBuckets[$value['key']] * 100,
                2) : 0;
            $arr[$i]['avg_deliverd_rate'] = $value['doc_count'] ? round($value['sumWaitTime']['value'] / $value['doc_count'] / 86400,
                2) : 0;
            $result = $value['delieveredDays']['buckets'];
            foreach ($result as $k => $v) {

                switch ($v['key']) {
                    case '0.0-6.99':
                        $arr[$i]['serven_deliverd_rate'] = $value['doc_count'] ? round($v['doc_count'] / $value['doc_count'] * 100,
                            2) : 0;
                        $sevenDelievedDay += $v['doc_count'];
                        break;
                    case '7.0-9.99':
                        $arr[$i]['ten_deliverd_rate'] = $value['doc_count'] ? round($v['doc_count'] / $value['doc_count'] * 100,
                            2) : 0;
                        $tenDelievedDay += $v['doc_count'];
                        break;
                    case '10.0-13.99':
                        $arr[$i]['fourteen_deliverd_rate'] = $value['doc_count'] ? round($v['doc_count'] / $value['doc_count'] * 100,
                            2) : 0;
                        $fourteenDelievedDay += $v['doc_count'];
                        break;
                    case '14.0-19.99':
                        $arr[$i]['twenty_deliverd_rate'] = $value['doc_count'] ? round($v['doc_count'] / $value['doc_count'] * 100,
                            2) : 0;
                        $twentyDelievedDay += $v['doc_count'];
                        break;
                    case '20.0-5000000.0':
                        $arr[$i]['gtTwenty_deliverd_rate'] = $value['doc_count'] ? round($v['doc_count'] / $value['doc_count'] * 100,
                            2) : 0;
                        $twentyupDelievedDay += $v['doc_count'];
                        break;
                }
            }
            $i++;
        }
        $allRate = $sendNum ? round($delievedNum/$sendNum*100,2) : 0;
        $sevenRate = $delievedNum ? round($sevenDelievedDay/$delievedNum*100,2) : 0;
        $tenRate = $delievedNum ? round($tenDelievedDay/$delievedNum*100,2) : 0;
        $fourteenRate = $delievedNum ? round($fourteenDelievedDay/$delievedNum*100,2) : 0;
        $twentyRate = $delievedNum ? round($twentyDelievedDay/$delievedNum*100,2) : 0;
        $twentyupRate = $delievedNum ? round($twentyupDelievedDay/$delievedNum*100,2) : 0;
        $allDelievedTime = $delievedNum ? round($waitTime/$delievedNum/86400,2) : 0;
        $arr[] = [
            'shipment_data_type' => '合计',
            'send_order_num' => $sendNum,
            'deliverd_order_num' => $delievedNum,
            'total_deliverd_rate' => $allRate,
            'serven_deliverd_rate' => $sevenRate,
            'ten_deliverd_rate' => $tenRate,
            'fourteen_deliverd_rate' => $fourteenRate,
            'twenty_deliverd_rate' => $twentyRate,
            'gtTwenty_deliverd_rate' => $twentyupRate,
            'avg_deliverd_rate' => $allDelievedTime,
        ];
        //获取物流渠道
        $trackChannel = array_column($buckets, 'key');

        $data = [];
        foreach ($buckets as $kk=>$vv){
            $data[$kk]['name'] = $vv['key'];
            $data[$kk]['value'] = $vv['doc_count'];
        }
        //发货数量统计饼图数据
        $sendEchart = [
            'column' => $trackChannel,
            'columnData' => $data,
        ];
        //妥投比率统计饼图数据
        $delievedEchart = [
            'column' => [
                '7天妥投率',
                '10天妥投率',
                '14天妥投率',
                '20天妥投率',
                '20天以上妥投率',
            ],
            'columnData' => [
                ['value' => $sevenDelievedDay, 'name' => '7天妥投率'],
                ['value' => $tenDelievedDay, 'name' => '10天妥投率'],
                ['value' => $fourteenDelievedDay, 'name' => '14天妥投率'],
                ['value' => $twentyDelievedDay, 'name' => '20天妥投率'],
                ['value' => $twentyupDelievedDay, 'name' => '20天以上妥投率']
            ]
        ];
        return compact( 'arr','sendEchart', 'delievedEchart');
    }

    public function formatPurchaseData($site, $data, $compareData = [])
    {
        //顶部指标
        $orderNum = $data['sumOrder']['value'];
        $allAvgPrice = $data['allAvgPrice']['value'];
        $allDaySalesAmount = $data['allDaySalesAmount']['value'];
        $allShippingAmount = $data['allShippingAmount']['value'];

        $compareOrderNumRate = $compareAllAvgPriceRate = $compareAllDaySalesAmountRate = $compareAllShippingAmountRate = 0;
        if($compareData) {

            $compareOrderNum = $compareData['sumOrder']['value'];
            $compareAllAvgPrice = $compareData['allAvgPrice']['value'];
            $compareAllDaySalesAmount = $compareData['allDaySalesAmount']['value'];
            $compareAllShippingAmount = $compareData['allShippingAmount']['value'];

            $compareOrderNumRate = $compareOrderNum ? bcmul(bcdiv(bcsub($orderNum,$compareOrderNum),$compareOrderNum,4),100,2) : 0;
            $compareAllAvgPriceRate = $compareAllAvgPrice ? bcmul(bcdiv(bcsub($allAvgPrice,$compareAllAvgPrice),$compareAllAvgPrice,4),100,2) : 0;
            $compareAllDaySalesAmountRate = $compareAllDaySalesAmount ? bcmul(bcdiv(bcsub($allDaySalesAmount,$compareAllDaySalesAmount),$compareAllDaySalesAmount,4),100,2) : 0;
            $compareAllShippingAmountRate = $compareAllShippingAmount ? bcmul(bcdiv(bcsub($allShippingAmount,$compareAllShippingAmount),$compareAllShippingAmount,4),100,2) : 0;
        }

        $dayBuckets = $data['daySale']['buckets'];
        $days = $dayBuckets ? array_column($dayBuckets, 'key') : [];
        $daySalesAmount = $dayBuckets ? array_column($dayBuckets, 'daySalesAmount') : [];
        $dayOrderNum = $dayBuckets ? array_column($dayBuckets, 'doc_count') : [];
        //订单量趋势
        $daySalesAmountEcharts = $this->hourEcharts($days,$daySalesAmount,'销售额');
        $dayOrderNumEcharts = $this->hourEcharts($days,$dayOrderNum,'订单量');
        //销售额趋势


        $orderType = $data['orderType']['buckets'];
        $orderTypeData = array_combine(array_column($orderType, 'key'), $orderType);
        //网红单//补发单
        $replacemenOrder = $orderTypeData[OrderType::REPLACEMENT_ORDER] ?? [];
        $socialOrder = $orderTypeData[OrderType::SOCIAL_ORDER] ?? [];

        //订单类型
        //0 ，平邮免邮
        //1.平邮
        //2.商业快递免邮
        //3. 商业快递
        $shipType = $data['shipType']['buckets'];
        $shipTypeData = array_combine(array_column($shipType, 'key'), $shipType);
        foreach ($shipTypeData as $key => $val)
        {
            $shipTypeData[$key]['rate'] = $orderNum ? bcmul(bcdiv($val['doc_count'],$orderNum,4),100,2).'%' : '0%';
        }
        //金额分部
        $priceRangesData = array_combine(array_column($data['priceRanges']['buckets'], 'from'), $data['priceRanges']['buckets']);
        foreach($priceRangesData as $key => $val) {
            $priceRangesData[$key]['rate'] = $orderNum ? bcmul(bcdiv($val['doc_count'],$orderNum,4),100,2).'%' : '0%';
        }

        $countryStr = '';
        //国家分部
        $countrySaleData = $data['countrySale']['buckets'];
        foreach($countrySaleData as $key => $val) {
            $countrySaleDataRrate = $orderNum ? bcmul(bcdiv($val['doc_count'],$orderNum,4),100,2).'%' : '0%';
            $countryStr.= '<tr><td>'.$val['key'].'</td><td>'.$val['doc_count'].'</td><td>'.$countrySaleDataRrate.'</td></tr>';
        }
        return compact('orderNum','allAvgPrice','allDaySalesAmount','allShippingAmount','daySalesAmountEcharts','dayOrderNumEcharts','replacemenOrder','socialOrder','priceRangesData','countryStr','compareOrderNumRate','compareAllAvgPriceRate' ,'compareAllDaySalesAmountRate' , 'compareAllShippingAmountRate','shipTypeData');
    }

    /**
     * 数据概况 -- 底部数据格式化
     * @param $data
     *
     * @return string
     * @author crasphb
     * @date   2021/4/20 11:47
     */
    public function formatDataMarketBottom($data)
    {
        $siteBuckets = $data['site']['buckets'];
        $str = '';
        foreach($siteBuckets as $key => $val) {
            $site = '';
            switch ($val['key']) {
                case Site::ZEELOOL:
                    $site = 'zeelool';
                    break;
                case Site::VOOGUEME:
                    $site = 'voogueme';
                    break;
                case Site::NIHAO:
                    $site = 'nihao';
                    break;
                case Site::MEELOOG:
                    $site = 'meeloog';
                    break;
                case Site::ZEELOOL_ES:
                    $site = 'zeelool_es';
                    break;
                case Site::ZEELOOL_DE:
                    $site = 'zeelool_de';
                    break;
                case Site::ZEELOOL_JP:
                    $site = 'zeelool_jp';
                    break;
                case Site::VOOGUEME_ACC:
                    $site = 'voogmechic';
                    break;
            }
            $storeBuckets = $val['store']['buckets'];
            $i = 1;
            foreach($storeBuckets as $k => $v) {
                $source = '';
                switch ($v['key']) {
                    case 1:
                        $source = '网页端';
                        break;
                    case 4:
                        $source = '移动端';
                        break;
                    case 5:
                        $source = 'IOS';
                        break;
                    case 6:
                        $source = 'Android';
                        break;
                }
                $str .= '<tr>
                            <td style="text-align: center; vertical-align: middle;">'.$i.'</td>
                            <td style="text-align: center; vertical-align: middle;">'.$site.'</td>
                            <td style="text-align: center; vertical-align: middle;">'.$source.'</td>
                            <td style="text-align: center; vertical-align: middle;">'.$v['allDaySalesAmount']['value'].'</td>
                            <td style="text-align: center; vertical-align: middle;">'.$v['allAvgPrice']['value'].'</td>
                            <td style="text-align: center; vertical-align: middle;">'.$v['doc_count'].'</td>
                        </tr>';
                $i++;
            }
        }
        return $str;
    }

    public function formatDataMarketEcharts($data,$start,$end)
    {
        $siteBuckets = $data['site']['buckets'];
        foreach($siteBuckets as $key => $val) {
            $site = '';
            switch ($val['key']) {
                case Site::ZEELOOL:
                    $site = 'zeelool';
                    break;
                case Site::VOOGUEME:
                    $site = 'voogueme';
                    break;
                case Site::NIHAO:
                    $site = 'nihao';
                    break;
                case Site::MEELOOG:
                    $site = 'meeloog';
                    break;
                case Site::ZEELOOL_ES:
                    $site = 'zeelool_es';
                    break;
                case Site::ZEELOOL_DE:
                    $site = 'zeelool_de';
                    break;
                case Site::ZEELOOL_JP:
                    $site = 'zeelool_jp';
                    break;
                case Site::VOOGUEME_ACC:
                    $site = 'voogmechic';
                    break;
            }
        }
    }
}