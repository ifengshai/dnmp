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
    'db_zeelool_online' => [
        // 数据库类型
        'type'        => Env::get('db_zeelool_online.type', 'mysql'),
        // 服务器地址
        'hostname'    => Env::get('db_zeelool_online.hostname', '127.0.0.1'),
        // 数据库名
        'database'    => Env::get('db_zeelool_online.database', 'fastadmin'),
        // 数据库用户名
        'username'    => Env::get('db_zeelool_online.username', 'root'),
        // 密码
        'password'    => Env::get('db_zeelool_online.password', 'root'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('db_zeelool_online.charset', 'utf8'),
        'prefix'      => Env::get('db_zeelool_online.prefix', 'fa_'),
    ],
    //数据库配置2
    'db_config2' => [
        // 数据库类型
        'type'        => Env::get('database2.type', 'mysql'),
        // 服务器地址
        'hostname'    => Env::get('database2.hostname', '127.0.0.1'),
        // 数据库名
        'database'    => Env::get('database2.database', 'fastadmin'),
        // 数据库用户名
        'username'    => Env::get('database2.username', 'root'),
        // 密码
        'password'    => Env::get('database2.password', 'root'),
        // 数据库编码默认采用utf8
        'charset'     => Env::get('database2.charset', 'utf8'),
        'prefix'      => Env::get('database2.prefix', 'fa_'),
    ],
];
