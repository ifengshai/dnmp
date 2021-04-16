<?php

return [
    'autoload' => false,
    'hooks'    =>
        [
//          'config_init' =>
//    array (
//      0 => 'nkeditor',
//    ),
            'config_init'      =>
                [
                    0 => 'aws3',
                    1 => 'nkeditor',
                ],
            'upload_after'     =>
                [
                    0 => 'aws3',
                ],
            's3_upload'        =>
                [
                    0 => 'aws3',
                ],
            'express_query'    =>
                [
                    0 => 'express',
                ],
            'admin_login_init' =>
                [
                    0 => 'loginbg',
                ],
            'response_send'    =>
                [
                    0 => 'loginvideo',
                ],
            'testhook'         =>
                [
                    0 => 'markdown',
                ],
        ],
    'route'    =>
        [
            '/example$'           => 'example/index/index',
            '/example/d/[:name]'  => 'example/demo/index',
            '/example/d1/[:name]' => 'example/demo/demo1',
            '/example/d2/[:name]' => 'example/demo/demo2',
        ],
];