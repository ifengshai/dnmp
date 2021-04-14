<?php
/**
 * Class EsFormatData.php
 * @package app\service\elasticsearch
 * @author  crasphb
 * @date    2021/4/12 11:55
 */

namespace app\service\elasticsearch;


class EsFormatData
{
    public function formatPurchaseData($site, $purchaseData)
    {
        $start = '2018020500';
        $end = '2021020531';
        $siteDataKeyColumn = array_column($purchaseData, 'key');
        $siteData = $siteDataKeyColumn[$site];
        //总销售额
        $allDaySalesAmount = $siteData['allDaySalesAmount']['value'];
        //总订单数
        $allOrderCount = $siteData['doc_count'];
        //总副数
        $allQtyOrdered = $siteData['allQtyOrdered']['value'];
        //客单价
        $allAvgPrice = $siteData['allAvgPrice']['value'];
        //分时销量
        $hourSale = $siteData['hourSale']['buckets'];
    }

    /**
     * 时段销量格式化
     * @param $orderData
     * @param $cartData
     * @param $gaData
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 14:39
     */
    public function formatHourData($orderData, $cartData, $gaData)
    {
        $hourSale = $orderData['hourSale']['buckets'];
        $hourCart = $cartData['hourCart']['buckets'];
        $hourSaleFormat = array_combine(array_column($hourSale, 'key'), $hourSale);
        $hourCartFormat = array_combine(array_column($hourCart, 'key'), $hourCart);
        $finalLists = $this->formatHour();
        //时段销量数据
        foreach ($finalLists as $key => $finalList) {
            foreach ($gaData as $gaKey => $gaValue) {
                if ((int)$finalList['hour'] == (int)substr($gaValue['ga:dateHour'], 8)) {
                    $finalLists[$key]['sessions'] += $gaValue['ga:sessions'];
                }
            }
            $hourOrderData = $hourSaleFormat['0' . $finalList['hour']];
            $hourCartData = $hourCartFormat['0' . $finalList['hour']];
            $finalLists[$key]['daySalesAmount'] = $hourOrderData['daySalesAmount']['value'];
            $finalLists[$key]['avgPrice'] = $hourOrderData['avgPrice']['value'];
            $finalLists[$key]['totalQtyOrdered'] = $hourOrderData['totalQtyOrdered']['value'];
            $finalLists[$key]['orderCounter'] = $hourOrderData['doc_count'];
            //购物车数目
            $finalLists[$key]['cartCouont'] = $hourCartData['doc_count'];

            //加购率
            $finalLists[$key]['addCartRate'] = $finalLists[$key]['sessions'] ? bcdiv($hourCartData['doc_count'],$finalLists[$key]['sessions'],2) . '%' : '0%';
            //新增购物车转化率
            $finalLists[$key]['cartRate'] = $hourOrderData['doc_count'] ? bcdiv($hourCartData['doc_count'],$hourOrderData['doc_count'],2) . '%' : '0%';
            //回话转化率
            $finalLists[$key]['sessionRate'] = $finalLists[$key]['sessions'] ? bcdiv($hourOrderData['doc_count'],$finalLists[$key]['sessions'],2) . '%' : '0%';
        }
        return $finalLists;
    }

    /**
     * 格式化0-24小时
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
     * 数据概况 - 数据大盘数据处理
     *
     * @param       $site
     * @param       $data
     * @param false $siteAll
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 10:40
     */
    public function formatDashBoardData($site, $data, $siteAll = false)
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
        $orderUnitPrice = bcdiv($salesTotalMoney, $orderNum, 2);

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
        $detailNumRate = !$landingNum ? '0%' : bcdiv($detailNum, $landingNum, 2) . '%';
        //加购数
        $cartNumRate = !$detailNum ? '0%' : bcdiv($cartNum, $detailNum, 2) . '%';
        //支付数
        $completeNumRate = !$cartNum ? '0%' : bcdiv($completeNum, $cartNum, 2) . '%';

        $dayBuckets = $data['daySale']['buckets'];
        $days = array_column($dayBuckets, 'key');
        $dayActiveUserNum = array_column($dayBuckets, 'activeUserNum');
        $dayOrderNum = array_column($dayBuckets, 'orderNum');

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

        return compact('activeUserNum', 'registerNum', 'vipUserNum', 'orderNum', 'salesTotalMoney', 'shippingTotalMoney', 'orderUnitPrice', 'dayChart', 'funnel');

    }
}