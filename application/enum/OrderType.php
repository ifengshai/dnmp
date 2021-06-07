<?php
/**
 * Class OrderType.php
 * @package app\enum
 * @author  crasphb
 * @date    2021/4/8 15:56
 */

namespace app\enum;


/**
 * 订单类型
 * Class OrderType
 * @package app\enum
 * @author  crasphb
 * @date    2021/4/8 16:18
 */
class OrderType
{
    //常规订单
    const REGULAR_ORDER = 1;
    //批发
    const WHOLESALE_ORDER = 2;
    //网红单
    const SOCIAL_ORDER = 3;
    //补发
    const REPLACEMENT_ORDER = 4;
    //补差价
    const DIFFERENCE_ORDER = 5;
    //一件代发
    const PAYROLL_ORDER = 6;
    //vip订单
    const VIP_ORDER = 9;
    //货到付款
    const CASH_DELIVERY_ORDER = 10;
    //便利店支付
    const CONVENIENCE_ORDER = 11;
}