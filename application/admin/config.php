<?php

//配置文件
return [
    'url_common_param'       => true,
    'url_html_suffix'        => '',
    'controller_auto_search' => true,


    'kuaidi' => [
        'yunda' => '韵达快递',
        'youzhengguonei' => '邮政快递包裹',
        'zhongtong' => '中通快递',
        'yuantong' => '圆通速递',
        'shentong' => '申通快递',
        'huitongkuaidi' => '百世快递',
        'shunfeng' => '顺丰速运',
        'jd' => '京东物流',
        'tiantian' => '天天快递',
        'ems' => 'EMS',
        'debangwuliu' => '德邦',
        'debangkuaidi' => '德邦快递'
    ],

    'purchase_status' => [
        0 => '新建',
        1 => '审核中',
        2 => '已审核',
        3 => '已拒绝',
        4 => '已取消',
        5 => '待发货',
        6 => '待收货',
        7 => '已收货',
        8 => '已退款'
    ],
];
