<?php

namespace Util;
use think\Db;

class SKUHelper{

    /* 
    * SKU 过滤字符  GOP01954-01 -> 01954-01
    */
    public static function sku_filter($target_sku)
    {
        $target_sku = str_replace('Z', '', $target_sku);
        $target_sku = str_replace('V', '', $target_sku);
        $target_sku = str_replace('F', '', $target_sku);
        $target_sku = str_replace('P', '', $target_sku);
        $target_sku = str_replace('T', '', $target_sku);
        $target_sku = str_replace('M', '', $target_sku);
        $target_sku = str_replace('N', '', $target_sku);
        $target_sku = str_replace('H', '', $target_sku);
        $target_sku = str_replace('A', '', $target_sku);
        $target_sku = str_replace('X', '', $target_sku);
        $target_sku = str_replace('I', '', $target_sku);
        $target_sku = str_replace('O', '', $target_sku);
        $target_sku = str_replace('G', '', $target_sku);
        $target_sku = str_replace('W', '', $target_sku);
        $target_sku = str_replace('D', '', $target_sku);
        $target_sku = str_replace('E', '', $target_sku);
        return $target_sku;
    }
    
}
