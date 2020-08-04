<?php

//工单系统配置
return [
    //问题大类
    'problem_Belong_type' => [
        // 1 => '订单修改',
        2 => '物流仓库',
        3 => '产品质量',
        4 => '客户问题',
        5 => '仓库问题',
        6 => '其他'
    ],
    //措施
    'step' => [
        1 => '更改',
        2 => '退款',
        3 => '取消',
        4 => '催单',
        // 5 => '暂缓',
        6 => '赠品',
        7 => '补发',
        8 => '补价',
        9 => '优惠券',
        10 => '积分',
        11 => '退件',
        12 => '更改镜片',
        13 => '更改地址',
        14 => '更改快递',
        15 => 'VIP退款',
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
    'customer_problem_classify' => [
        '订单修改' => [1,2,3,4,6,7],
        '物流仓库' => [8,9,10,11,12,5],
        '产品质量' => [13,14,15,16],
        '客户问题' => [17,18,19,20,21,22,23]
    ],
    'new_customer_problem_classify' => [
        '订单修改' => [1,2,3,4,6,7],
        '物流仓库' => [8,9,10,11,12,5],
        '产品质量' => [13,14,15,16],
        '客户问题' => [17,18,19,20,21,22,23],
        '仓库问题' => [1,2,3,4,5,6,7,8,9,10,11]
    ],
    //仓库问题类型
    'warehouse_problem_type' => [
        26 => '镜框缺货',
        27 => '镜片缺货',
        28 => '镜片重做',
        29 => '定制片超时',
        25 => '不可加工',
        24 => '核实处方',
        30 => '核实地址',
        31 => '系统漏单',
        32 => '配错镜框',
        33 => '物流退件',
        34 => '客户退件',
    ],

    //客服问题类型组 id大于5 采用step04措施
    'customer_problem_group' => [
        1 => [ //更改镜框
            'step' => 'step06',
        ],
        2 => [ //更改镜片
            'step' => 'step07'
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
    /*客服-措施组专用 start*/
    'step01' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单
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
    'step05' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单
            ],
        ],
        [
            'step_id' => 2, //退款
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'cashier_group' //出纳
            ],
        ],
        [
            'step_id' => 8, //补价
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人

            ],
        ],
    ],
    'step06' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单
            ],
        ],
        [
            'step_id' => 2, //退款
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'cashier_group' //出纳
            ],
        ],
        [
            'step_id' => 8, //补价
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人

            ],
        ],
    ],
    'step07' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_lens_group' //仓库镜片负责人
            ],
        ],
        [
            'step_id' => 2, //退款
            'is_check' => 1,
            'appoint_group' => [ //承接组
                'cashier_group' //出纳
            ],
        ],
        [
            'step_id' => 8, //补价
            'is_check' => 0,
            'appoint_group' => [ //空代表承接给创建人

            ],
        ],
    ],
    /*客服-措施组专用 end*/

    //仓库问题类型组  id大于4 采用step04措施
    'warehouse_problem_group' => [
        1 => [ //核实处方
            'step' => 'step02' //措施key
        ],
        2 => [ //不可加工
            'step' => 'step08' //措施key
        ],
        3 => [ //镜框缺货
            'step' => 'step08' //措施key
        ],
        4 => [ //镜片缺货
            'step' => 'step04' //为空没有后续
        ],
        5 => [
            'step' => 'step04' //措施key
        ],

    ],
    /*仓库-措施组专用 start*/
    'step02' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_lens_group' //仓库镜片负责人
            ],
        ],
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
                //'warehouse_group' //仓库跟单员
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
    'step08' => [
        [
            'step_id' => 1, //更改
            'is_check' => 0,
            'appoint_group' => [ //承接组
                'warehouse_group' //仓库跟单
            ],
        ],
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
                //'warehouse_group' //仓库跟单员
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
    /*仓库-措施组专用 end*/

    /*公共-措施组专用 start*/
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
                //'warehouse_group' //仓库跟单员
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
    /*公共-措施组专用 end*/

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
        169 //白晓颜
    ],

    //跟单客服
    'copy_group' => [
        //88 //赵晶晶
        //198
        105
    ],

    //客服主管组
    'kefumanage' => [
        //白青青组
        95 => [
            87, //王一安
            105, //郭亚敏
            123, //袁倩倩
            
            156, //刘梦楠
            114,  //李昱皓
//            205,//冯俊伟
            231,  //张萌莉
            233,  //张莉
            245, //任雪姣
            246,  //闫梦琦
            250,  //张一凡
            256   //赵寒星  
            // 228  //李鑫
        ],
        //韩雨薇组
        117 => [
            //108, //吴帆     
            88,  //赵津津
            125, //马雨萌
            155, //李森
            116,//彭梦莹
            198,//李丹 
            203,//李赵恒
            209,//杨欢欢
            208,//代阿敏
            210,//李煜然
            //216,//冯雅慧
            //217,//马振艳
            //218,//马朝
            //219, //王晓艳
            220, //陈晓曼
            252,  //马鸿运
            205,//冯俊伟
            267,//刘丹
            268,//赵文静

            
        ]
    ],

    //客服经理id
    'customer_manager' => 75,

    //不需要审核的优惠券
    //1，zeelool，2:voogueme,3:nihao
    'check_coupon' => [
        '1' => [
            'id' => '46',
            'desc' => '5美金',
            'sum' => '5',
            'site' => 1
        ],
        '2' => [
            'id' => '57',
            'desc' => '10美金',
            'sum' => '10',
            'site' => 1
        ],
        '3' => [
            'id' => '47',
            'desc' => '15美金',
            'sum' => '15',
            'site' => 1
        ],
        '4' => [
            'id' => '291',
            'desc' => '20美金',
            'sum' => '20',
            'site' => 1
        ],
        '5' => [
            'id' => '258',
            'desc' => '15% off',
            'sum' => '15',
            'site' => 1
        ],
        '6' => [
            'id' => '669',
            'desc' => '5美金',
            'sum' => '5',
            'site' => 2
        ],
        '7' => [
            'id' => '670',
            'desc' => '10美金',
            'sum' => '10',
            'site' => 2
        ],
        '8' => [
            'id' => '671',
            'desc' => '15美金',
            'sum' => '15',
            'site' => 2
        ],
        '9' => [
            'id' => '672',
            'desc' => '20美金',
            'sum' => '20',
            'site' => 2
        ],
        '10' => [
            'id' => '673',
            'desc' => '15% off',
            'sum' => '15',
            'site' => 2
        ],
        '11' => [
            'id' => '108',
            'desc' => '5美金',
            'sum' => '5',
            'site' => 3
        ],
        '12' => [
            'id' => '109',
            'desc' => '10美金',
            'sum' => '10',
            'site' => 3
        ],
        '13' => [
            'id' => '110',
            'desc' => '15美金',
            'sum' => '15',
            'site' => 3
        ],
        '14' => [
            'id' => '113',
            'desc' => '20美金',
            'sum' => '20',
            'site' => 3
        ],
        '15' => [
            'id' => '112',
            'desc' => '15% off',
            'sum' => '15',
            'site' => 3
        ],
    ],
    //需要审核的优惠券
    'need_check_coupon' => [
        '52' => [
            'id' => '52',
            'desc' => '50% off仅镜架',
            'sum' => '50',
            'site' => 1
        ],
        '274' => [
            'id' => '274',
            'desc' => '50% off整单',
            'sum' => '50',
            'site' => 1
        ],
        '289' => [
            'id' => '289',
            'desc' => '镜架免费',
            'sum' => '100',
            'site' => 1
        ],
        '674' => [
            'id' => '674',
            'desc' => '50% off仅镜架',
            'sum' => '50',
            'site' => 2
        ],
        '668' => [
            'id' => '668',
            'desc' => '50% off整单',
            'sum' => '50',
            'site' => 2
        ],
        '675' => [
            'id' => '675',
            'desc' => '镜架免费',
            'sum' => '100',
            'site' => 2
        ],
        '39' => [
            'id' => '39',
            'desc' => '50% off仅镜架',
            'sum' => '50',
            'site' => 3
        ],
        '22' => [
            'id' => '22',
            'desc' => '50% off整单',
            'sum' => '50',
            'site' => 3
        ],
        '111' => [
            'id' => '111',
            'desc' => '镜架免费',
            'sum' => '100',
            'site' => 3
        ]
    ],
    //客服部门角色组ID
    'customer_department_rule' => [
        31,
        32,
        33,
        34,
        36
    ],
    //仓库部门角色组ID
    'warehouse_department_rule' => [
        42,
        43,
        44,
        45,
        46,
        47,
        48,
        49
    ],
    //财务角色组
    'finance_department_rule' => [
        29,
        51,
        64,
        65
    ],
    'platform'=>[
        1=>'zeelool',
        2=>'voogueme',
        3=>'nihao'
    ],
    'customer_problem_classify_arr' => [
        //订单修改
         1=>[1,2,3,4,6,7],
         2=>[8,9,10,11,12,5],
         3=>[13,14,15,16],
         4=>[17,18,19,20,21,22,23]
    ],
    //新的分类
    'new_customer_problem_classify_arr' =>[
        //前四条是客服工单的问题分类，第五条是仓库工单的问题分类
        1=>[1,2,3,4,6,7],
        2=>[8,9,10,11,12,5],
        3=>[13,14,15,16],
        4=>[17,18,19,20,21,22,23],
        5=>[1,2,3,4,5,6,7,8,9,10,11]
    ],
    //主管超时时间配置
    'manage_time_out' => 86400,
    //措施里面超时配置
    'step_time_out' =>[
        
        1 => 60*60*24,
        2 => 60*60*48,
        3 => 60*60*24,
        4 => 60*60*72,
        6 => 60*60*24,
        7 => 60*60*24,
        9 => 60*60*48,
        10 => 60*60*48
    ],
    //仓库问题类型的超时配置
    'warehouse_time_out' =>[
        // 1 => '核实处方',
        // 2 => '不可加工',
        // 3 => '镜框缺货',
        // 4 => '镜片缺货',
        // 5 => '镜片重做',
        // 6 => '定制片超时',
        // 7 => '核实地址',
        // 8 => '系统漏单',
        // 9 => '配错镜框',
        // 10 => '物流退件',
        // 11 => '客户退件',
        1=> 60*60*72,
        2=> 60*60*72,
        3=> 60*60*72,
        4=> 60*60*12,
        5=> 60*60*12,
        6=> 60*60*12,
        7=> 60*60*72,
        8=> 60*60*12,
        9=> 60*60*12,
        10=>60*60*72,
        11=>60*60*72,
    ],
    //客服数据统计是筛选的分组 A/B组
    'customer_type' =>[
        0=>'全部',
        1=>'A',
        2=>'B'
    ],
    //客服数据统计时筛选的分类 正式/试用期员工
    'customer_category' =>[
        0=>'全部',
        1=>'正式员工',
        2=>'试用期员工'
    ],
    //客服数据统计时筛选的分类 全部/邮件组/电话组
    'customer_workload'=>[
        0=>'全部',
        1=>'邮件组',
        2=>'电话组'
    ],
    //区分正式/试用期员工时间
    'customer_category_time'=>60*60*24*60
];
