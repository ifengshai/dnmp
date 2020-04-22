<?php

//工单系统配置
return [
    //措施
    'step' => [
        1 => '更改',
        2 => '退款',
        3 => '取消',
        4 => '催单',
        5 => '暂缓',
        6 => '赠品',
        7 => '补发',
        8 => '补价',
        9 => '优惠卷',
        10 => '积分',
        11 => '退件'
    ],

    //客服问题类型
    'customer_problem_type' => [
        1 => '更改镜框',
        2 => '更改镜片',
        3 => '更改地址',
        4 => '更改快递',
        5 => '关税',
        6 => '重复下单',
        7 => '合并发货',
        8 => '物流损坏',
        9 => '物流超时',
        10 => '错发漏发',
        11 => '处方做错',
        12 => '物流丢件',
        13 => '尺寸不符',
        14 => '镜框质量',
        15 => '镜片质量',
        16 => '实物不符',
        17 => '追加处方',
        18 => '填错处方',
        19 => '问题不明',
        20 => '未使用折扣',
        21 => '不再需要',
        22 => '发货地不满',
        23 => '其他',
    ],

    //仓库问题类型
    'warehouse_problem_type' => [
        1 => '核实处方',
        2 => '不可加工',
        3 => '镜框缺货',
        4 => '镜片缺货',
        5 => '镜片重做',
        6 => '定制片超时',
        7 => '核实地址',
        8 => '系统漏单',
        9 => '配错镜框',
        10 => '物流退件',
        11 => '客户退件',
    ],

    //客服问题类型组 id大于5 采用step04措施
    'customer_problem_group' => [
        1 => [ //更改镜框
            'step' => 'step01'
        ],
        2 => [ //更改镜片
            'step' => 'step02'
        ],
        3 => [ //更改地址
            'step' => 'step01'
        ],
        4 => [ //更改快递
            'step' => 'step05'
        ],
        5 => [ //关税
            'step' => 'step03'
        ],
        6 => [
            'step' => 'step04'
        ]
    ],
    //仓库问题类型组  id大于4 采用step04措施
    'warehouse_problem_group' => [
        1 => [ //核实处方
            'step' => 'step02' //措施key
        ],
        2 => [ //不可加工
            'step' => 'step01' //措施key
        ],
        3 => [ //镜框缺货
            'step' => 'step01' //措施key
        ],
        4 => [ //镜片缺货
            'step' => '' //为空没有后续
        ],
        5 => [
            'step' => 'step04' //措施key
        ],

    ],

    //措施组
    'step01' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单
            ],
        ],
    ],
    'step02' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_lens_group' //仓库镜片负责人
            ],
        ],
    ],
    'step03' => [
        [
            'step_id' => 2, //退款
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'cashier_group' //出纳
            ],
        ],
    ],
    'step04' => [

        [
            'step_id' => 2, //退款
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'cashier_group' //出纳
            ],
        ],
        [
            'step_id' => 3, //取消
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单员
            ],
        ],

        [
            'step_id' => 4, //催单
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单员
            ],
        ],
        [
            'step_id' => 5, //暂缓
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单员
            ],
        ],
        [
            'step_id' => 6, //赠品
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单员
            ],
        ],
        [
            'step_id' => 7, //补发
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单员
            ],
        ],
        [
            'step_id' => 8, //补价
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人

            ],
        ],
        [
            'step_id' => 9, //优惠券
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人
            ],
        ],

        [
            'step_id' => 10, //积分
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人
            ],
        ],

        [
            'step_id' => 11, //退件
            'is_check' => 0,
            'appoint_group' => [
                'warehouse_group' //仓库跟单员
            ],
        ]
    ],

    'step05' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单
            ],
        ],
        [
            'step_id' => 8, //补价
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人

            ],
        ],
    ],

    //仓库跟单员
    'warehouse_group' => [
        71 //马奇
    ],
    //仓库镜片负责人
    'warehouse_lens_group' => [
        60 //陈爱丽
    ],
    //出纳
    'cashier_group' => [
        82 //白晓颜
    ],

    //跟单客服
    'copy_group' => [
        88 //赵晶晶
    ],

    //客服主管组
    'kefumanage' => [
        //白青青组
        95 => [
            105, //郭亚敏
            116, //彭梦莹
            123, //袁倩倩
            125, //马雨萌
            156, //刘梦楠
            114  //李昱皓    
        ],
        //韩雨薇组
        117 => [
            1,
            108, //吴帆
            126, //李闯
            87, //王一安
            88,  //赵津津
            155, //李森
            131  //王骜
        ]
    ],

    //客服经理id
    'customer_manager' => 75,

    //不需要审核的优惠券
    //1，zeelool，2:voogueme,3:nihao
    'check_coupon' => [
        '1' => [
            'id' => '354',
            'desc' => '15 off',
            'sum' => '15',
            'site' => 1
        ],
        '2' => [
            'id' => '355',
            'desc' => '20 off',
            'sum' => '20',
            'site' => 1
        ],
        '3' => [
            'id' => '356',
            'desc' => '25 off',
            'sum' => '25',
            'site' => 2
        ],
        '4' => [
            'id' => '357',
            'desc' => '30 off',
            'sum' => '30',
            'site' => 3
        ],
    ],
    //需要审核的优惠券
    'need_check_coupon' => [
        '5' => [
            'id' => '377',
            'desc' => 'half',
            'sum' => '50',
            'site' => 1
        ],
        '6' => [
            'id' => '378',
            'desc' => 'all',
            'sum' => '100',
            'site' => 2
        ]
    ],
    //客服部门角色组ID
    'customer_department_rule' => [
        1,
        31,
        32,
        33,
        34,
        36
    ],
    //仓库部门角色组ID
    'warehouse_department_rule' => [
        1,
        42,
        43,
        44,
        45,
        46,
        47,
        48,
        49
    ]
];
