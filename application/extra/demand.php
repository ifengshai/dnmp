<?php
return [
    //站点
    /*'siteType' => [
        '1' => 'Zeelool',
        '2' => 'Voogueme',
        '3' => 'Nihao',
        '4' => 'Wesee',
        '5' => 'Other',
        '6' => '如弗小程序',
    ],*/
    //项目
    'site' => [
        '1' => 'Zeelool',
        '2' => 'Voogueme',
        '3' => 'Nihao',
        '4' => 'Meeloog',
        '5' => 'Wesee',
        '6' => 'Rufoo',
        '7' => 'Toloog',
        '8' => 'Other',
        '9' => 'ZeeloolEs',
        '10' => 'ZeeloolDe',
        '11' => 'ZeeloolJp',
        '12' => 'Voogmechic',
        
    ],
    //类型
    'siteType' => [
        '1' => 'PC',
        '2' => 'M',
        '3' => 'APP',
        '4' => 'Other',
    ],
    //抄送人
    'copyToUserId' => [
        '52' => 'W王帅鹏',
        '49' => 'C陈见',
        'Z' => '------ Z站 ------',
        '53' => 'W吴方',
        '54' => 'W王小花',
        '73' => 'P彭梦婷',
        '221' => 'W王晨蕾',
        '135' =>'Z郑娜娜',
        'V' => '------ V站 ------',
        '51' => 'L刘明明',
        '79' => 'L李裕祺',
        '78' => 'Z朱美洁',
        '122' => 'S史琛璐',
        'N' => '------ 你好站 ------',
        '65' => 'C陈彩丽',
        '55' => 'L李衡',
        'W' => '------ 批发站 ------',
        '102' => 'Z朱彩霞',
        'ES' => '------ 小语种站 ------',
        '207' => 'Z张俊杰',
        'PM' => '------ 产品 ------',
        '110' => 'W王重阳',
        '80' => 'X谢梦飞',
        '240' => 'X徐婧',
        '303' => 'L李慧娟',
        '363' => 'C崔杰',
        'IT' => '------ 技术 ------',
        '148' => 'Z张晓',
        '181' => 'L李想',
        '191' => 'H黄彬彬',
        '185' => 'C陈奕任',
        '194' => 'Y杨志豪',
        '280' => 'L刘超',
        '200' => 'C陈亚蒙',
        'KF' => '------ 客服 ------',
        '75' => 'W王伟',
        '95' => 'B白青青',
        '117' => 'H韩雨薇',
        '184' => '樊志刚',

    ],
    //类型
    'type' => [
        '1' => 'Bug（原有功能失效）',
        '2' => '维护（不涉及新功能）',
        '3' => '优化（优化原有功能）',
        '4' => '新功能（开发新功能）',
        '5' => '开发（开发时间超过一周）',
    ],
    //优先级
    'priority' => [
        '1' => '低',
        '2' => '低+',
        '3' => '中',
        '4' => '中+',
        '5' => '高',
        '6' => '高+',
    ],

    //整体复杂度
    'allComplexity' => [
        '1' => '简单',
        '2' => '中等',
        '3' => '复杂',
    ],
    //功能模块
    'functional_module' => [
        '1' => '购物车',
        '2' => '个人中心',
        '3' => '列表页',
        '4' => '详情页',
        '5' => '首页',
        '6' => '优惠券',
        '7' => '支付页',
        '8' => 'magento后台',
        '9' => '活动页',
        '10' => '其他',
    ],
    //重要原因
    'important_reasons' => [
        '1' => '不做会造成严重的问题和恶劣的影响的',
        '2' => '做了会产生巨大好处和极佳效果的',
        '3' => '跟核心用户利益有关的',
        '4' => '跟大部分用户权益有关的',
        '5' => '跟效率或成本有关的',
        '6' => '跟用户体验有关的',
        '7' => '不做错误会持续发生，造成严重影响',
        '8' => '在一定时间内可控，但长期会有糟糕的影响',
        '9' => '做了立刻能解决很多问题、产生正面的影响',
        '10' => '做了在一段时间后可以有良好的效果',
        '11' => '其他',
    ],
    //重要程度
    'importance' => [
        '1' => 'S0',
        '2' => 'S1',
        '3' => 'S2',
        '4' => 'S3',
        '5' => 'S4',
    ],
    //紧急程度
    'degree_of_urgency' => [
        '1' => 'P0',
        '2' => 'P1',
        '3' => 'P2',
        '4' => 'P3',
    ],
    //开发难度
    'development_difficulty' => [
        '1' => 'D1',
        '2' => 'D2',
        '3' => 'D3',
    ],
    /*//是否需要测试
    'testGroup' => [
        '1' => '需要',
        '2' => '不需要',
    ],*/
    /*//前端组员
    'web_designer_user' => [
        '' => '------ 请选择 ------',
        '185' => '陈奕任',
        '186' => '李扬',
        '188' => '李蓓',
        '190' => '刘雨东',
        '281' => '杨满花',

    ],
    //后端组员
    'phper_user' => [
        '' => '------ 请选择 ------',
        '191' => '黄彬彬',
        '192' => '卢志恒',
        '204' => '王恒刚',
        '229' => '周正晖',
        '227' => '刘松巍',

    ],
    //app组员
    'app_user' => [
        '' => '------ 请选择 ------',
        '194' => '杨志豪',
    ],
    //测试组员
    'test_user' => [
        '' => '------ 请选择 ------',
        '195' => '马红亚',
        '200' => '陈亚蒙',
        '280' => '刘超',
        '242' => '张鹏',
        '255' => '陈玉晓'
    ],
    

    //测试bug严重类型
    'bug_type' => [
        '1' => '次要',
        '2' => '一般',
        '3' => '严重',
        '4' => '崩溃',
    ],
    //类型 lsw add
    'demand_type' =>[
        1=> 'bug',
        2=> '需求',
        3=> '疑难',
    ],*/

    'php_group_id' => 108,
    'php_group_person_id' => 114,
    'web_group_id' => 107,
    'web_group_person_id' => 113,
    'test_group_id' => 109,
    'test_group_person_id' => 115,
    'app_group_id' => 110,
    'app_group_person_id' => 116,
    'product_group_id' => 105,
    'product_group_person_id' => 111,
    'develop_group_id' => 106,
    'develop_group_person_id' => 112,

];