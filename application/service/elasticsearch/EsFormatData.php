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
}