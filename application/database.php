<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Env;

return [
    // 数据库类型
    'type'            => Env::get('database.type', 'mysql'),
    // 服务器地址
    'hostname'        => Env::get('database.hostname', '127.0.0.1'),
    // 数据库名
    'database'        => Env::get('database.database', 'fastadmin'),
    // 用户名
    'username'        => Env::get('database.username', 'root'),
    // 密码
    'password'        => Env::get('database.password', 'root'),
    // 端口
    'hostport'        => Env::get('database.hostport', ''),
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => Env::get('database.charset', 'utf8'),
    // 数据库表前缀
    'prefix'          => Env::get('database.prefix', 'fa_'),
    // 数据库调试模式
    'debug'           => Env::get('database.debug', true),
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格检查字段是否存在
    'fields_strict'   => true,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式,默认为Y-m-d H:i:s
    'datetime_format' => false,
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,
    'db_zeelool' => [
        // 数据库类型
        'type'        => Env::get('db_zeelool.type'),
        // 服务器地址
        'hostname'    => Env::get('db_zeelool.hostname'),
        // 数据库名
        'database'    => Env::get('db_zeelool.database'),
        // 数据库用户名
        'username'    => Env::get('db_zeelool.username'),
        // 密码
        'password'    => Env::get('db_zeelool.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_zeelool.charset'),
        'prefix'      => Env::get('db_zeelool.prefix'),
    ],

    'db_voogueme' => [
        // 数据库类型
        'type'        => Env::get('db_voogueme.type'),
        // 服务器地址
        'hostname'    => Env::get('db_voogueme.hostname'),
        // 数据库名
        'database'    => Env::get('db_voogueme.database'),
        // 数据库用户名
        'username'    => Env::get('db_voogueme.username'),
        // 密码
        'password'    => Env::get('db_voogueme.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_voogueme.charset'),
        'prefix'      => Env::get('db_voogueme.prefix'),
    ],

    'db_nihao' => [
        // 数据库类型
        'type'        => Env::get('db_nihao.type'),
        // 服务器地址
        'hostname'    => Env::get('db_nihao.hostname'),
        // 数据库名
        'database'    => Env::get('db_nihao.database'),
        // 数据库用户名
        'username'    => Env::get('db_nihao.username'),
        // 密码
        'password'    => Env::get('db_nihao.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_nihao.charset'),
        'prefix'      => Env::get('db_nihao.prefix'),
    ],
    'db_weseeoptical' => [
        // 数据库类型
        'type'        => Env::get('db_weseeoptical.type'),
        // 服务器地址
        'hostname'    => Env::get('db_weseeoptical.hostname'),
        // 数据库名
        'database'    => Env::get('db_weseeoptical.database'),
        // 数据库用户名
        'username'    => Env::get('db_weseeoptical.username'),
        // 密码
        'password'    => Env::get('db_weseeoptical.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_weseeoptical.charset'),
        'prefix'      => Env::get('db_weseeoptical.prefix'),
    ],
    'db_meeloog' => [
        // 数据库类型
        'type'        => Env::get('db_meeloog.type'),
        // 服务器地址
        'hostname'    => Env::get('db_meeloog.hostname'),
        // 数据库名
        'database'    => Env::get('db_meeloog.database'),
        // 数据库用户名
        'username'    => Env::get('db_meeloog.username'),
        // 密码
        'password'    => Env::get('db_meeloog.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_meeloog.charset'),
        'prefix'      => Env::get('db_meeloog.prefix'),
    ],

    'db_zeelool_online' => [
        // 数据库类型
        'type'        => Env::get('db_zeelool_online.type'),
        // 服务器地址
        'hostname'    => Env::get('db_zeelool_online.hostname'),
        // 数据库名
        'database'    => Env::get('db_zeelool_online.database'),
        // 数据库用户名
        'username'    => Env::get('db_zeelool_online.username'),
        // 密码
        'password'    => Env::get('db_zeelool_online.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_zeelool_online.charset'),
        'prefix'      => Env::get('db_zeelool_online.prefix'),
    ],

    'db_voogueme_online' => [
        // 数据库类型
        'type'        => Env::get('db_voogueme_online.type'),
        // 服务器地址
        'hostname'    => Env::get('db_voogueme_online.hostname'),
        // 数据库名
        'database'    => Env::get('db_voogueme_online.database'),
        // 数据库用户名
        'username'    => Env::get('db_voogueme_online.username'),
        // 密码
        'password'    => Env::get('db_voogueme_online.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_voogueme_online.charset'),
        'prefix'      => Env::get('db_voogueme_online.prefix'),
    ],

    'db_nihao_online' => [
        // 数据库类型
        'type'        => Env::get('db_nihao_online.type'),
        // 服务器地址
        'hostname'    => Env::get('db_nihao_online.hostname'),
        // 数据库名
        'database'    => Env::get('db_nihao_online.database'),
        // 数据库用户名
        'username'    => Env::get('db_nihao_online.username'),
        // 密码
        'password'    => Env::get('db_nihao_online.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_nihao_online.charset'),
        'prefix'      => Env::get('db_nihao_online.prefix'),
    ],
    'db_stock' => [
        // 数据库类型
        'type'        => Env::get('db_stock.type'),
        // 服务器地址
        'hostname'    => Env::get('db_stock.hostname'),
        // 数据库名
        'database'    => Env::get('db_stock.database'),
        // 数据库用户名
        'username'    => Env::get('db_stock.username'),
        // 密码
        'password'    => Env::get('db_stock.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_stock.charset'),
        'prefix'      => Env::get('db_stock.prefix'),
    ],
    'db_rufoo' => [
        // 数据库类型
        'type'        => Env::get('db_rufoo.type'),
        // 服务器地址
        'hostname'    => Env::get('db_rufoo.hostname'),
        // 数据库名
        'database'    => Env::get('db_rufoo.database'),
        // 数据库用户名
        'username'    => Env::get('db_rufoo.username'),
        // 密码
        'password'    => Env::get('db_rufoo.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_rufoo.charset'),
        'prefix'      => Env::get('db_rufoo.prefix'),
    ],
    'db_zeelool_es' => [
        // 数据库类型
        'type'        => Env::get('db_zeelool_es.type'),
        // 服务器地址
        'hostname'    => Env::get('db_zeelool_es.hostname'),
        // 数据库名
        'database'    => Env::get('db_zeelool_es.database'),
        // 数据库用户名
        'username'    => Env::get('db_zeelool_es.username'),
        // 密码
        'password'    => Env::get('db_zeelool_es.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_zeelool_es.charset'),
        'prefix'      => Env::get('db_zeelool_es.prefix'),
    ],
    'db_zeelool_de' => [
        // 数据库类型
        'type'        => Env::get('db_zeelool_de.type'),
        // 服务器地址
        'hostname'    => Env::get('db_zeelool_de.hostname'),
        // 数据库名
        'database'    => Env::get('db_zeelool_de.database'),
        // 数据库用户名
        'username'    => Env::get('db_zeelool_de.username'),
        // 密码
        'password'    => Env::get('db_zeelool_de.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_zeelool_de.charset'),
        'prefix'      => Env::get('db_zeelool_de.prefix'),
    ],
    'db_zeelool_jp' => [
        // 数据库类型
        'type'        => Env::get('db_zeelool_jp.type'),
        // 服务器地址
        'hostname'    => Env::get('db_zeelool_jp.hostname'),
        // 数据库名
        'database'    => Env::get('db_zeelool_jp.database'),
        // 数据库用户名
        'username'    => Env::get('db_zeelool_jp.username'),
        // 密码
        'password'    => Env::get('db_zeelool_jp.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_zeelool_jp.charset'),
        'prefix'      => Env::get('db_zeelool_jp.prefix'),
    ],
    'db_mojing_order' => [
        // 数据库类型
        'type'        => Env::get('db_mojing_order.type'),
        // 服务器地址
        'hostname'    => Env::get('db_mojing_order.hostname'),
        // 数据库名
        'database'    => Env::get('db_mojing_order.database'),
        // 数据库用户名
        'username'    => Env::get('db_mojing_order.username'),
        // 密码
        'password'    => Env::get('db_mojing_order.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_mojing_order.charset'),
        'prefix'      => Env::get('db_mojing_order.prefix'),
    ],
    'db_new_order' => [
        // 数据库类型
        'type'        => Env::get('db_new_order.type'),
        // 服务器地址
        'hostname'    => Env::get('db_new_order.hostname'),
        // 数据库名
        'database'    => Env::get('db_new_order.database'),
        // 数据库用户名
        'username'    => Env::get('db_new_order.username'),
        // 密码
        'password'    => Env::get('db_new_order.password'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_new_order.charset'),
        'prefix'      => Env::get('db_new_order.prefix'),

    ],
];
