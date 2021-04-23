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

    /**
     * 订单数据 -- 订单数据概况格式化
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
     * 仪表盘 -- 底部数据格式化
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

    /**
     * 仪表盘 -- 中间图标格式化
     * @param $data
     *
     * @return array
     * @author crasphb
     * @date   2021/4/21 11:42
     */
    public function formatDataMarketEcharts($data)
    {
        $siteBuckets = $data['site']['buckets'];
        $xData = [];
        $yData = [];
        foreach($siteBuckets as $key => $val) {
            $daySaleBuckets = $val['daySale']['buckets'];
            $site = '';
            switch ($val['key']) {
                case Site::ZEELOOL:
                    $site = 'zeelool';
                    $xData = array_combine(array_column($daySaleBuckets, 'key'), $daySaleBuckets);
                    $xData = array_keys($xData);
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
            foreach($daySaleBuckets as $k => $v) {
                $yData[$site]['salesTotalMoney'][] = $v['salesTotalMoney']['value'] ?: 0;
                $yData[$site]['avgPrice'][] = $v['avgPrice']['value'] ?: 0;
                $yData[$site]['registerNum'][] = $v['registerNum']['value'] ?: 0;
                $yData[$site]['cartNum'][] = $v['cartNum']['value'] ?: 0;
                $yData[$site]['orderNum'][] = $v['orderNum']['value'] ?: 0;
                $yData[$site]['cartNumRate'][] = $v['orderNum']['value'] ? bcmul(bcdiv($v['cartNum']['value'],$v['orderNum']['value'],4),100) : 0;
            }

        }
        return compact('xData','yData');
    }

    public function formatDataMarketTop($site, $operationData, $order, $cart , $customer,$time,$status,$siteAll = false)
    {
        $cartDay = $this->formatDataMarketCartCustomer($cart,$time);
        $customerDay = $this->formatDataMarketCartCustomer($customer,$time);
        $orderDay = $this->formatDataMarketOrder($order,$status);
        $operationDetail = [];
        foreach($operationData as $key => $val) {
            if(!$siteAll) {
                if($val['order_platform'] == $site) {
                    $yesterday_sales_money =   $val['yesterday_sales_money'];
                    $pastsevenday_sales_money =   $val['pastsevenday_sales_money'];
                    $pastthirtyday_sales_money =   $val['pastthirtyday_sales_money'];
                    $thismonth_sales_money =   $val['thismonth_sales_money'];
                    $lastmonth_sales_money =   $val['lastmonth_sales_money'];
                    $thisyear_sales_money =   $val['thisyear_sales_money'];
                    $lastyear_sales_money =   $val['lastyear_sales_money'];
                    $total_sales_money =   $val['total_sales_money'];
                    $yesterday_order_num =   $val['yesterday_order_num'];
                    $pastsevenday_order_num =   $val['pastsevenday_order_num'];
                    $pastthirtyday_order_num =   $val['pastthirtyday_order_num'];
                    $thismonth_order_num =   $val['thismonth_order_num'];
                    $lastmonth_order_num =   $val['lastmonth_order_num'];
                    $thisyear_order_num =   $val['thisyear_order_num'];
                    $lastyear_order_num =   $val['lastyear_order_num'];
                    $total_order_num =   $val['total_order_num'];
                    $yesterday_order_success =   $val['yesterday_order_success'];
                    $pastsevenday_order_success =   $val['pastsevenday_order_success'];
                    $pastthirtyday_order_success =   $val['pastthirtyday_order_success'];
                    $thismonth_order_success =   $val['thismonth_order_success'];
                    $lastmonth_order_success =   $val['lastmonth_order_success'];
                    $thisyear_order_success =   $val['thisyear_order_success'];
                    $lastyear_order_success =   $val['lastyear_order_success'];
                    $total_order_success =   $val['total_order_success'];
                    $yesterday_unit_price =   $val['yesterday_unit_price'];
                    $pastsevenday_unit_price =   $val['pastsevenday_unit_price'];
                    $pastthirtyday_unit_price =   $val['pastthirtyday_unit_price'];
                    $thismonth_unit_price =   $val['thismonth_unit_price'];
                    $lastmonth_unit_price =   $val['lastmonth_unit_price'];
                    $thisyear_unit_price =   $val['thisyear_unit_price'];
                    $lastyear_unit_price =   $val['lastyear_unit_price'];
                    $total_unit_price =   $val['total_unit_price'];
                    $yesterday_shoppingcart_total =   $val['yesterday_shoppingcart_total'];
                    $pastsevenday_shoppingcart_total =   $val['pastsevenday_shoppingcart_total'];
                    $pastthirtyday_shoppingcart_total =   $val['pastthirtyday_shoppingcart_total'];
                    $thismonth_shoppingcart_total =   $val['thismonth_shoppingcart_total'];
                    $lastmonth_shoppingcart_total =   $val['lastmonth_shoppingcart_total'];
                    $thisyear_shoppingcart_total =   $val['thisyear_shoppingcart_total'];
                    $lastyear_shoppingcart_total =   $val['lastyear_shoppingcart_total'];
                    $total_shoppingcart_total =   $val['total_shoppingcart_total'];
                    $yesterday_shoppingcart_conversion =   $val['yesterday_shoppingcart_conversion'];
                    $pastsevenday_shoppingcart_conversion =   $val['pastsevenday_shoppingcart_conversion'];
                    $pastthirtyday_shoppingcart_conversion =   $val['pastthirtyday_shoppingcart_conversion'];
                    $thismonth_shoppingcart_conversion =   $val['thismonth_shoppingcart_conversion'];
                    $lastmonth_shoppingcart_conversion =   $val['lastmonth_shoppingcart_conversion'];
                    $thisyear_shoppingcart_conversion =   $val['thisyear_shoppingcart_conversion'];
                    $lastyear_shoppingcart_conversion =   $val['lastyear_shoppingcart_conversion'];
                    $total_shoppingcart_conversion =   $val['total_shoppingcart_conversion'];
                    $yesterday_shoppingcart_new =   $val['yesterday_shoppingcart_new'];
                    $pastsevenday_shoppingcart_new =   $val['pastsevenday_shoppingcart_new'];
                    $pastthirtyday_shoppingcart_new =   $val['pastthirtyday_shoppingcart_new'];
                    $thismonth_shoppingcart_new =   $val['thismonth_shoppingcart_new'];
                    $lastmonth_shoppingcart_new =   $val['lastmonth_shoppingcart_new'];
                    $thisyear_shoppingcart_new =   $val['thisyear_shoppingcart_new'];
                    $lastyear_shoppingcart_new =   $val['lastyear_shoppingcart_new'];
                    $total_shoppingcart_new =   $val['total_shoppingcart_new'];
                    $yesterday_shoppingcart_newconversion =   $val['yesterday_shoppingcart_newconversion'];
                    $pastsevenday_shoppingcart_newconversion =   $val['pastsevenday_shoppingcart_newconversion'];
                    $pastthirtyday_shoppingcart_newconversion =   $val['pastthirtyday_shoppingcart_newconversion'];
                    $thismonth_shoppingcart_newconversion =   $val['thismonth_shoppingcart_newconversion'];
                    $lastmonth_shoppingcart_newconversion =   $val['lastmonth_shoppingcart_newconversion'];
                    $thisyear_shoppingcart_newconversion =   $val['thisyear_shoppingcart_newconversion'];
                    $lastyear_shoppingcart_newconversion =   $val['lastyear_shoppingcart_newconversion'];
                    $total_shoppingcart_newconversion =   $val['total_shoppingcart_newconversion'];
                    $yesterday_register_customer =   $val['yesterday_register_customer'];
                    $pastsevenday_register_customer =   $val['pastsevenday_register_customer'];
                    $pastthirtyday_register_customer =   $val['pastthirtyday_register_customer'];
                    $thismonth_register_customer =   $val['thismonth_register_customer'];
                    $lastmonth_register_customer =   $val['lastmonth_register_customer'];
                    $thisyear_register_customer =   $val['thisyear_register_customer'];
                    $lastyear_register_customer =   $val['lastyear_register_customer'];
                    $total_register_customer =   $val['total_register_customer'];
                    $yesterday_sign_customer =   $val['yesterday_sign_customer'];
                    $pastsevenday_sign_customer =   $val['pastsevenday_sign_customer'];
                    $pastthirtyday_sign_customer =   $val['pastthirtyday_sign_customer'];
                    $thismonth_sign_customer =   $val['thismonth_sign_customer'];
                    $lastmonth_sign_customer =   $val['lastmonth_sign_customer'];
                    $thisyear_sign_customer =   $val['thisyear_sign_customer'];
                    $lastyear_sign_customer =   $val['lastyear_sign_customer'];
                    $total_sign_customer =   $val['total_sign_customer'];
                    break;
                }
            }else{
                if($val['order_platform'] <= 4) {
                    $yesterday_sales_money +=  $val['yesterday_sales_money'];
                    $pastsevenday_sales_money +=  $val['pastsevenday_sales_money'];
                    $pastthirtyday_sales_money +=  $val['pastthirtyday_sales_money'];
                    $thismonth_sales_money +=  $val['thismonth_sales_money'];
                    $lastmonth_sales_money +=  $val['lastmonth_sales_money'];
                    $thisyear_sales_money +=  $val['thisyear_sales_money'];
                    $lastyear_sales_money +=  $val['lastyear_sales_money'];
                    $total_sales_money +=  $val['total_sales_money'];
                    $yesterday_order_num +=  $val['yesterday_order_num'];
                    $pastsevenday_order_num +=  $val['pastsevenday_order_num'];
                    $pastthirtyday_order_num +=  $val['pastthirtyday_order_num'];
                    $thismonth_order_num +=  $val['thismonth_order_num'];
                    $lastmonth_order_num +=  $val['lastmonth_order_num'];
                    $thisyear_order_num +=  $val['thisyear_order_num'];
                    $lastyear_order_num +=  $val['lastyear_order_num'];
                    $total_order_num +=  $val['total_order_num'];
                    $yesterday_order_success +=  $val['yesterday_order_success'];
                    $pastsevenday_order_success +=  $val['pastsevenday_order_success'];
                    $pastthirtyday_order_success +=  $val['pastthirtyday_order_success'];
                    $thismonth_order_success +=  $val['thismonth_order_success'];
                    $lastmonth_order_success +=  $val['lastmonth_order_success'];
                    $thisyear_order_success +=  $val['thisyear_order_success'];
                    $lastyear_order_success +=  $val['lastyear_order_success'];
                    $total_order_success +=  $val['total_order_success'];

                    $yesterday_shoppingcart_total +=  $val['yesterday_shoppingcart_total'];
                    $pastsevenday_shoppingcart_total +=  $val['pastsevenday_shoppingcart_total'];
                    $pastthirtyday_shoppingcart_total +=  $val['pastthirtyday_shoppingcart_total'];
                    $thismonth_shoppingcart_total +=  $val['thismonth_shoppingcart_total'];
                    $lastmonth_shoppingcart_total +=  $val['lastmonth_shoppingcart_total'];
                    $thisyear_shoppingcart_total +=  $val['thisyear_shoppingcart_total'];
                    $lastyear_shoppingcart_total +=  $val['lastyear_shoppingcart_total'];
                    $total_shoppingcart_total +=  $val['total_shoppingcart_total'];

                    $yesterday_shoppingcart_new +=  $val['yesterday_shoppingcart_new'];
                    $pastsevenday_shoppingcart_new +=  $val['pastsevenday_shoppingcart_new'];
                    $pastthirtyday_shoppingcart_new +=  $val['pastthirtyday_shoppingcart_new'];
                    $thismonth_shoppingcart_new +=  $val['thismonth_shoppingcart_new'];
                    $lastmonth_shoppingcart_new +=  $val['lastmonth_shoppingcart_new'];
                    $thisyear_shoppingcart_new +=  $val['thisyear_shoppingcart_new'];
                    $lastyear_shoppingcart_new +=  $val['lastyear_shoppingcart_new'];
                    $total_shoppingcart_new +=  $val['total_shoppingcart_new'];

                    $yesterday_register_customer +=  $val['yesterday_register_customer'];
                    $pastsevenday_register_customer +=  $val['pastsevenday_register_customer'];
                    $pastthirtyday_register_customer +=  $val['pastthirtyday_register_customer'];
                    $thismonth_register_customer +=  $val['thismonth_register_customer'];
                    $lastmonth_register_customer +=  $val['lastmonth_register_customer'];
                    $thisyear_register_customer +=  $val['thisyear_register_customer'];
                    $lastyear_register_customer +=  $val['lastyear_register_customer'];
                    $total_register_customer +=  $val['total_register_customer'];
                    $yesterday_sign_customer +=  $val['yesterday_sign_customer'];
                    $pastsevenday_sign_customer +=  $val['pastsevenday_sign_customer'];
                    $pastthirtyday_sign_customer +=  $val['pastthirtyday_sign_customer'];
                    $thismonth_sign_customer +=  $val['thismonth_sign_customer'];
                    $lastmonth_sign_customer +=  $val['lastmonth_sign_customer'];
                    $thisyear_sign_customer +=  $val['thisyear_sign_customer'];
                    $lastyear_sign_customer +=  $val['lastyear_sign_customer'];
                    $total_sign_customer +=  $val['total_sign_customer'];
                }
            }
        }
        if($siteAll) {
            $yesterday_unit_price =   $yesterday_order_success ? bcdiv($yesterday_sales_money,$yesterday_order_success,2) : 0;
            $pastsevenday_unit_price =   $pastsevenday_order_success ? bcdiv($pastsevenday_sales_money,$pastsevenday_order_success,2) : 0;
            $pastthirtyday_unit_price =   $pastthirtyday_order_success ? bcdiv($pastthirtyday_sales_money,$pastthirtyday_order_success,2) : 0;
            $lastmonth_unit_price =   $lastmonth_order_success ? bcdiv($lastmonth_sales_money,$lastmonth_order_success,2) : 0;
            $lastyear_unit_price =   $lastyear_order_success ? bcdiv($lastyear_sales_money,$lastyear_order_success,2) : 0;

            $yesterday_shoppingcart_conversion =   $yesterday_shoppingcart_total ? bcmul(bcdiv($yesterday_order_success,$yesterday_shoppingcart_total,4),100,2) : 0;
            $pastsevenday_shoppingcart_conversion =   $pastsevenday_shoppingcart_total ? bcmul(bcdiv($pastsevenday_order_success,$pastsevenday_shoppingcart_total,4),100,2) : 0;
            $pastthirtyday_shoppingcart_conversion =   $pastthirtyday_shoppingcart_total ? bcmul(bcdiv($pastthirtyday_order_success,$pastthirtyday_shoppingcart_total,4),100,2) : 0;
            $lastmonth_shoppingcart_conversion =   $lastmonth_shoppingcart_total ? bcmul(bcdiv($lastmonth_order_success,$lastmonth_shoppingcart_total,4),100,2) : 0;
            $lastyear_shoppingcart_conversion =   $lastyear_shoppingcart_total ? bcmul(bcdiv($lastyear_order_success,$lastyear_shoppingcart_total,4),100,2) : 0;
                    
            $yesterday_shoppingcart_newconversion =   $yesterday_shoppingcart_new ? bcmul(bcdiv($yesterday_order_success,$yesterday_shoppingcart_new,4),100,2) : 0;
            $pastsevenday_shoppingcart_newconversion =   $pastsevenday_shoppingcart_new ? bcmul(bcdiv($pastsevenday_order_success,$pastsevenday_shoppingcart_new,4),100,2) : 0;
            $pastthirtyday_shoppingcart_newconversion =   $pastthirtyday_shoppingcart_new ? bcmul(bcdiv($pastthirtyday_order_success,$pastthirtyday_shoppingcart_new,4),100,2) : 0;
            $lastmonth_shoppingcart_newconversion =   $lastmonth_shoppingcart_new ? bcmul(bcdiv($lastmonth_order_success,$lastmonth_shoppingcart_new,4),100,2) : 0;
            $lastyear_shoppingcart_newconversion =   $lastyear_shoppingcart_new ? bcmul(bcdiv($lastyear_order_success,$lastyear_shoppingcart_new,4),100,2) : 0;
        }
        $operationDetail = compact('yesterday_sales_money','pastsevenday_sales_money','pastthirtyday_sales_money','thismonth_sales_money','lastmonth_sales_money','thisyear_sales_money','lastyear_sales_money','total_sales_money','yesterday_order_num','pastsevenday_order_num','pastthirtyday_order_num','thismonth_order_num','lastmonth_order_num','thisyear_order_num','lastyear_order_num','total_order_num','yesterday_order_success','pastsevenday_order_success','pastthirtyday_order_success','thismonth_order_success','lastmonth_order_success','thisyear_order_success','lastyear_order_success','total_order_success','yesterday_unit_price','pastsevenday_unit_price','pastthirtyday_unit_price','thismonth_unit_price','lastmonth_unit_price','thisyear_unit_price','lastyear_unit_price','total_unit_price','yesterday_shoppingcart_total','pastsevenday_shoppingcart_total','pastthirtyday_shoppingcart_total','thismonth_shoppingcart_total','lastmonth_shoppingcart_total','thisyear_shoppingcart_total','lastyear_shoppingcart_total','total_shoppingcart_total','yesterday_shoppingcart_conversion','pastsevenday_shoppingcart_conversion','pastthirtyday_shoppingcart_conversion','thismonth_shoppingcart_conversion','lastmonth_shoppingcart_conversion','thisyear_shoppingcart_conversion','lastyear_shoppingcart_conversion','total_shoppingcart_conversion','yesterday_shoppingcart_new','pastsevenday_shoppingcart_new','pastthirtyday_shoppingcart_new','thismonth_shoppingcart_new','lastmonth_shoppingcart_new','thisyear_shoppingcart_new','lastyear_shoppingcart_new','total_shoppingcart_new','yesterday_shoppingcart_newconversion','pastsevenday_shoppingcart_newconversion','pastthirtyday_shoppingcart_newconversion','thismonth_shoppingcart_newconversion','lastmonth_shoppingcart_newconversion','thisyear_shoppingcart_newconversion','lastyear_shoppingcart_newconversion','total_shoppingcart_newconversion','yesterday_register_customer','pastsevenday_register_customer','pastthirtyday_register_customer','thismonth_register_customer','lastmonth_register_customer','thisyear_register_customer','lastyear_register_customer','total_register_customer','yesterday_sign_customer','pastsevenday_sign_customer','pastthirtyday_sign_customer','thismonth_sign_customer','lastmonth_sign_customer','thisyear_sign_customer','lastyear_sign_customer','total_sign_customer');
        //当月加上今天
        $operationDetail['thismonth_sales_money'] = bcadd($operationDetail['thismonth_sales_money'],$orderDay['allDaySalesAmount'],2);
        $operationDetail['thismonth_order_num'] = bcadd($operationDetail['thismonth_order_num'],$orderDay['allCount'],2);
        $operationDetail['thismonth_order_success'] = bcadd($operationDetail['thismonth_order_success'],$orderDay['successCount']);
        $operationDetail['thismonth_unit_price'] = $operationDetail['thismonth_order_success'] ? bcdiv($operationDetail['thismonth_sales_money'],$operationDetail['thismonth_order_success'],2) : 0;

        $operationDetail['thismonth_shoppingcart_total'] = bcadd($operationDetail['thismonth_shoppingcart_total'],$cartDay['dayUpdate']);
        $operationDetail['thismonth_shoppingcart_conversion'] = $operationDetail['thismonth_shoppingcart_total'] ? bcmul(bcdiv($operationDetail['thismonth_order_success'],$operationDetail['thismonth_shoppingcart_total'],4),100,2) : 0;

        $operationDetail['thismonth_shoppingcart_new'] = bcadd($operationDetail['thismonth_shoppingcart_new'],$cartDay['dayCreate']);
        $operationDetail['thismonth_shoppingcart_newconversion'] = $operationDetail['thismonth_shoppingcart_new'] ? bcmul(bcdiv($operationDetail['thismonth_order_success'],$operationDetail['thismonth_shoppingcart_new'],4),100,2) : 0;

        $operationDetail['thismonth_register_customer'] = bcadd($operationDetail['thismonth_register_customer'],$customerDay['dayUpdate']);
        $operationDetail['thismonth_sign_customer'] = bcadd($operationDetail['thismonth_sign_customer'],$customerDay['dayCreate']);

        //今年加上今天
        $operationDetail['thisyear_sales_money'] = bcadd($operationDetail['thisyear_sales_money'],$orderDay['allDaySalesAmount'],2);
        $operationDetail['thisyear_order_num'] = bcadd($operationDetail['thisyear_order_num'],$orderDay['allCount']);
        $operationDetail['thisyear_order_success'] = bcadd($operationDetail['thisyear_order_success'],$orderDay['successCount']);
        $operationDetail['thisyear_unit_price'] = $operationDetail['thisyear_order_success'] ? bcdiv($operationDetail['thisyear_sales_money'],$operationDetail['thisyear_order_success'],2) : 0;

        $operationDetail['thisyear_shoppingcart_total'] = bcadd($operationDetail['thisyear_shoppingcart_total'],$cartDay['dayUpdate']);
        $operationDetail['thisyear_shoppingcart_conversion'] = $operationDetail['thisyear_shoppingcart_total'] ? bcmul(bcdiv($operationDetail['thisyear_order_success'],$operationDetail['thisyear_shoppingcart_total'],4),100,2) : 0;

        $operationDetail['thisyear_shoppingcart_new'] = bcadd($operationDetail['thisyear_shoppingcart_new'],$cartDay['dayCreate']);
        $operationDetail['thisyear_shoppingcart_newconversion'] = $operationDetail['thisyear_shoppingcart_new'] ? bcmul(bcdiv($operationDetail['thisyear_order_success'],$operationDetail['thisyear_shoppingcart_new'],4),100,2) : 0;

        $operationDetail['thisyear_register_customer'] = bcadd($operationDetail['thisyear_register_customer'],$customerDay['dayUpdate']);
        $operationDetail['thisyear_sign_customer'] = bcadd($operationDetail['thisyear_sign_customer'],$customerDay['dayCreate']);


        //总计加上今天
        $operationDetail['total_sales_money'] = bcadd($operationDetail['total_sales_money'],$orderDay['allDaySalesAmount'],2);
        $operationDetail['total_order_num'] = bcadd($operationDetail['total_order_num'],$orderDay['allCount']);
        $operationDetail['total_order_success'] = bcadd($operationDetail['total_order_success'],$orderDay['successCount']);
        $operationDetail['total_unit_price'] = $operationDetail['total_order_success'] ? bcdiv($operationDetail['total_sales_money'],$operationDetail['total_order_success'],2) : 0;

        $operationDetail['total_shoppingcart_total'] = bcadd($operationDetail['total_shoppingcart_total'],$cartDay['dayUpdate']);
        $operationDetail['total_shoppingcart_conversion'] = $operationDetail['total_shoppingcart_total'] ? bcmul(bcdiv($operationDetail['total_order_success'],$operationDetail['total_shoppingcart_total'],4),100,2) : 0;

        $operationDetail['total_shoppingcart_new'] = bcadd($operationDetail['total_shoppingcart_new'],$cartDay['dayCreate']);
        $operationDetail['total_shoppingcart_newconversion'] = $operationDetail['total_shoppingcart_new'] ? bcmul(bcdiv($operationDetail['total_order_success'],$operationDetail['total_shoppingcart_new'],4),100,2) : 0;

        $operationDetail['total_register_customer'] = bcadd($operationDetail['total_register_customer'],$customerDay['dayUpdate']);
        $operationDetail['total_sign_customer'] = bcadd($operationDetail['total_sign_customer'],$customerDay['dayCreate']);

        //今天的
        $operationDetail['today_sales_money'] = round($orderDay['allDaySalesAmount'],2) ?: 0;
        $operationDetail['today_order_num'] = $orderDay['allCount'] ?: 0;
        $operationDetail['today_order_success'] = $orderDay['successCount'] ?: 0;
        $operationDetail['today_unit_price'] = round($orderDay['allAvgPrice'],2);

        $operationDetail['today_shoppingcart_total'] = $cartDay['dayUpdate'] ?: 0;
        $operationDetail['today_shoppingcart_conversion'] = $operationDetail['today_shoppingcart_today'] ? bcmul(bcdiv($operationDetail['today_order_success'],$operationDetail['today_shoppingcart_today'],4),100,2) : 0;

        $operationDetail['today_shoppingcart_new'] = $cartDay['dayCreate'] ?: 0;
        $operationDetail['today_shoppingcart_newconversion'] = $operationDetail['today_shoppingcart_new'] ? bcmul(bcdiv($operationDetail['today_order_success'],$operationDetail['today_shoppingcart_new'],4),100,2) : 0;

        $operationDetail['today_register_customer'] = $customerDay['dayUpdate'] ?: 0;
        $operationDetail['today_sign_customer'] = bcadd($operationDetail['today_sign_customer'],$customerDay['dayCreate'],2);              

        return $operationDetail;
       
    }

    /**
     * 格式化日跟新，新增数据
     * @param $data
     * @param $time
     *
     * @return array
     * @author crasphb
     * @date   2021/4/22 10:38
     */
    public function formatDataMarketCartCustomer($data,$time)
    {
        $dayUpdateAll = $data['dayUpdate']['buckets'];
        $dayCreateAll = $data['dayCreate']['buckets'];
        $dayUpdate = '';
        $dayCreate = '';
        foreach($dayUpdateAll as $key => $val)
        {
            if($time == $val['key']) {
                $dayUpdate = $val['doc_count'];
                break;
            }
        }
        foreach($dayCreateAll as $key => $val)
        {
            if($time == $val['key']) {
                $dayCreate = $val['doc_count'];
                break;
            }
        }
        return compact('dayUpdate','dayCreate');
    }

    /**
     * 获取近日的订单数据
     * @param $data
     * @param $status
     *
     * @return array
     * @author crasphb
     * @date   2021/4/22 10:44
     */
    public function formatDataMarketOrder($data,$status)
    {
        $orderAll = $data['status']['buckets'];
        $allCount = 0;
        $successCount = 0;
        $allDaySalesAmount = 0;
        foreach($orderAll as $key => $val) {
            $count = $val['doc_count'];
            $allCount += $count;
            if(in_array($val['key'],$status)) {
                $successCount += $count;
                $allDaySalesAmount += $val['allDaySalesAmount']['value'];
            }
        }
        $allAvgPrice = $successCount ? bcdiv($allDaySalesAmount,$successCount,2) : 0;
        return compact('allCount','successCount','allDaySalesAmount','allAvgPrice');
    }
}